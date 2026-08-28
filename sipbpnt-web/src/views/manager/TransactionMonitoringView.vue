<script setup lang="ts">
import axios from 'axios'

import {
  Activity,
  BarChart3,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  CircleAlert,
  ClipboardCheck,
  MapPin,
  RefreshCw,
  Search,
  SlidersHorizontal,
  Store,
  UserRound,
  UsersRound,
  X,
} from '@lucide/vue'

import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import {
  managerTransactionMonitoringService,
} from '@/services/managerTransactionMonitoringService'

import type {
  ManagerMonitoringBreakdowns,
  ManagerMonitoringPeriod,
  ManagerMonitoringSummary,
  ManagerMonitoringTransaction,
  ManagerTransactionMonitoringQuery,
} from '@/types/managerTransactionMonitoring'

type OutsideFilter =
  | ''
  | 'inside'
  | 'outside'

const emptySummary:
  ManagerMonitoringSummary = {
    total_kpm: 0,
    transacted: 0,
    pending: 0,
    active_verifications: 0,
    deceased: 0,
    moved_domicile: 0,
    not_claimed: 0,
    outside_assignment: 0,
    completion_percentage: 0,
  }

const emptyBreakdowns:
  ManagerMonitoringBreakdowns = {
    kecamatans: [],
    kelurahans: [],
    e_warungs: [],
    surveyors: [],
  }

const period =
  ref<ManagerMonitoringPeriod | null>(
    null,
  )

const summary =
  ref<ManagerMonitoringSummary>({
    ...emptySummary,
  })

const breakdowns =
  ref<ManagerMonitoringBreakdowns>({
    ...emptyBreakdowns,
  })

const transactions =
  ref<ManagerMonitoringTransaction[]>([])

const currentPage =
  ref(1)

const lastPage =
  ref(1)

const totalTransactions =
  ref(0)

const perPage =
  ref(20)

const search =
  ref('')

const selectedKecamatanId =
  ref('')

const selectedKelurahanId =
  ref('')

const selectedEWarungId =
  ref('')

const selectedSurveyorId =
  ref('')

const selectedOutside =
  ref<OutsideFilter>('')

const loading =
  ref(false)

const errorMessage =
  ref('')

const showAdditionalFilters =
  ref(false)

const lastUpdatedAt =
  ref<Date | null>(null)

let searchTimer:
  ReturnType<typeof setTimeout>
  | null = null

let requestSequence =
  0

const normalizedProgress =
  computed(() =>
    Math.min(
      100,
      Math.max(
        0,
        summary.value
          .completion_percentage,
      ),
    ),
  )

const transactionPercentage =
  computed(() => {
    if (
      summary.value.total_kpm
      === 0
    ) {
      return 0
    }

    return Math.min(
      100,
      Math.max(
        0,
        summary.value.transacted
          / summary.value.total_kpm
          * 100,
      ),
    )
  })

const verificationPercentage =
  computed(() => {
    if (
      summary.value.total_kpm
      === 0
    ) {
      return 0
    }

    return Math.min(
      100,
      Math.max(
        0,
        summary.value
          .active_verifications
          / summary.value.total_kpm
          * 100,
      ),
    )
  })

const pendingPercentage =
  computed(() => {
    if (
      summary.value.total_kpm
      === 0
    ) {
      return 0
    }

    return Math.min(
      100,
      Math.max(
        0,
        summary.value.pending
          / summary.value.total_kpm
          * 100,
      ),
    )
  })

const donutBackground =
  computed(() => {
    const transactionEnd =
      transactionPercentage.value

    const verificationEnd =
      Math.min(
        100,
        transactionEnd
          + verificationPercentage.value,
      )

    return [
      'conic-gradient(',
      `#059669 0% ${transactionEnd}%,`,
      `#2563eb ${transactionEnd}% ${verificationEnd}%,`,
      `#f59e0b ${verificationEnd}% 100%`,
      ')',
    ].join(' ')
  })

const formattedLastUpdated =
  computed(() => {
    if (
      lastUpdatedAt.value
      === null
    ) {
      return '-'
    }

    return new Intl.DateTimeFormat(
      'id-ID',
      {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
      },
    ).format(
      lastUpdatedAt.value,
    )
  })

const maxSurveyorTransactions =
  computed(() =>
    Math.max(
      1,
      ...breakdowns.value
        .surveyors
        .map(
          (surveyor) =>
            surveyor.transactions,
        ),
    ),
  )

const kecamatanOptions =
  computed(() =>
    breakdowns.value
      .kecamatans
      .filter(
        (row) =>
          row.kecamatan.id
          !== null,
      ),
  )

const kelurahanOptions =
  computed(() => {
    const kecamatanId =
      Number(
        selectedKecamatanId.value,
      )

    return breakdowns.value
      .kelurahans
      .filter((row) => {
        if (
          selectedKecamatanId.value
          === ''
        ) {
          return true
        }

        return row.kecamatan.id
          === kecamatanId
      })
  })

const hasFilters =
  computed(() =>
    search.value.trim()
      !== ''
    || selectedKecamatanId.value
      !== ''
    || selectedKelurahanId.value
      !== ''
    || selectedEWarungId.value
      !== ''
    || selectedSurveyorId.value
      !== ''
    || selectedOutside.value
      !== '',
  )

const pageStart =
  computed(() => {
    if (
      totalTransactions.value
      === 0
    ) {
      return 0
    }

    return (
      currentPage.value
      - 1
    ) * perPage.value + 1
  })

const pageEnd =
  computed(() =>
    Math.min(
      currentPage.value
        * perPage.value,
      totalTransactions.value,
    ),
  )

function numberValue(
  value: string,
): number | undefined {
  if (
    value === ''
  ) {
    return undefined
  }

  return Number(value)
}

function buildQuery():
  ManagerTransactionMonitoringQuery {
  const query:
    ManagerTransactionMonitoringQuery = {
      page: currentPage.value,
      per_page: perPage.value,
    }

  const normalizedSearch =
    search.value.trim()

  if (
    normalizedSearch !== ''
  ) {
    query.search =
      normalizedSearch
  }

  const kecamatanId =
    numberValue(
      selectedKecamatanId.value,
    )

  if (
    kecamatanId !== undefined
  ) {
    query.kecamatan_id =
      kecamatanId
  }

  const kelurahanId =
    numberValue(
      selectedKelurahanId.value,
    )

  if (
    kelurahanId !== undefined
  ) {
    query.kelurahan_id =
      kelurahanId
  }

  const eWarungId =
    numberValue(
      selectedEWarungId.value,
    )

  if (
    eWarungId !== undefined
  ) {
    query.e_warung_id =
      eWarungId
  }

  const surveyorId =
    numberValue(
      selectedSurveyorId.value,
    )

  if (
    surveyorId !== undefined
  ) {
    query.surveyor_id =
      surveyorId
  }

  if (
    selectedOutside.value
    !== ''
  ) {
    query.outside_assignment =
      selectedOutside.value
      === 'outside'
        ? 1
        : 0
  }

  return query
}

function resolveErrorMessage(
  error: unknown,
): string {
  if (
    axios.isAxiosError(error)
  ) {
    const payload:
      unknown =
        error.response?.data

    const message =
      typeof payload === 'object'
      && payload !== null
      && 'message' in payload
        ? payload.message
        : null

    if (
      typeof message === 'string'
      && message.trim() !== ''
    ) {
      return message
    }
  }

  return 'Data monitoring belum dapat dimuat. Silakan coba kembali.'
}

async function loadMonitoring():
  Promise<void> {
  const sequence =
    ++requestSequence

  loading.value =
    true

  errorMessage.value =
    ''

  try {
    const response =
      await managerTransactionMonitoringService
        .getMonitoring(
          buildQuery(),
        )

    if (
      sequence
      !== requestSequence
    ) {
      return
    }

    period.value =
      response.data.period

    summary.value =
      response.data.summary

    breakdowns.value =
      response.data.breakdowns

    transactions.value =
      response.data.transactions

    currentPage.value =
      response.meta.current_page

    lastPage.value =
      response.meta.last_page

    perPage.value =
      response.meta.per_page

    totalTransactions.value =
      response.meta.total

    lastUpdatedAt.value =
      new Date()
  } catch (error: unknown) {
    if (
      sequence
      !== requestSequence
    ) {
      return
    }

    errorMessage.value =
      resolveErrorMessage(
        error,
      )
  } finally {
    if (
      sequence
      === requestSequence
    ) {
      loading.value =
        false
    }
  }
}

function clearSearchTimer():
  void {
  if (
    searchTimer !== null
  ) {
    clearTimeout(
      searchTimer,
    )

    searchTimer =
      null
  }
}

function scheduleSearch():
  void {
  clearSearchTimer()

  searchTimer =
    setTimeout(
      () => {
        currentPage.value =
          1

        void loadMonitoring()
      },
      400,
    )
}

function applySearch():
  void {
  clearSearchTimer()

  currentPage.value =
    1

  void loadMonitoring()
}

function applyFilters():
  void {
  currentPage.value =
    1

  void loadMonitoring()
}

function changeKecamatan():
  void {
  selectedKelurahanId.value =
    ''

  applyFilters()
}

function resetFilters():
  void {
  clearSearchTimer()

  search.value =
    ''

  selectedKecamatanId.value =
    ''

  selectedKelurahanId.value =
    ''

  selectedEWarungId.value =
    ''

  selectedSurveyorId.value =
    ''

  selectedOutside.value =
    ''

  currentPage.value =
    1

  void loadMonitoring()
}

function goToPage(
  page: number,
): void {
  if (
    page < 1
    || page > lastPage.value
    || page === currentPage.value
    || loading.value
  ) {
    return
  }

  currentPage.value =
    page

  void loadMonitoring()
}

function formatDateTime(
  value: string | null,
): string {
  if (
    value === null
  ) {
    return '-'
  }

  const date =
    new Date(value)

  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return '-'
  }

  return new Intl.DateTimeFormat(
    'id-ID',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Asia/Jakarta',
    },
  ).format(date)
}

function regionName(
  name: string | null,
): string {
  return name
    ?? '-'
}

function progressPercentage(
  completed: number,
  total: number,
): number {
  if (
    total <= 0
  ) {
    return 0
  }

  return Math.min(
    100,
    Math.max(
      0,
      completed
        / total
        * 100,
    ),
  )
}

function handledKpm(
  transacted: number,
  verifications: number,
): number {
  return transacted
    + verifications
}

onMounted(() => {
  void loadMonitoring()
})

onBeforeUnmount(() => {
  clearSearchTimer()
})
</script>

<template>
  <section
    class="mx-auto grid w-full max-w-[1800px] gap-4 p-4 sm:p-6 xl:gap-5 xl:p-7"
  >
    <header
      class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
    >
      <div>
        <div
          class="mb-1.5 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-emerald-700"
        >
          <Activity :size="15" />
          Manager BPNT
        </div>

        <h1
          class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl"
        >
          Monitoring Transaksi
        </h1>

        <p
          class="mt-1.5 max-w-3xl text-sm leading-6 text-slate-600"
        >
          Pantau progres transaksi bantuan,
          tindak lanjut KPM, dan aktivitas
          Surveyor pada periode aktif.
        </p>
      </div>

      <div
        class="flex flex-col gap-2 sm:flex-row sm:items-center"
      >
        <p class="text-xs text-slate-500">
          Terakhir diperbarui:

          <strong
            class="font-semibold text-slate-700"
          >
            {{ formattedLastUpdated }} WIB
          </strong>
        </p>

        <button
          type="button"
          class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800 disabled:opacity-60"
          :disabled="loading"
          @click="loadMonitoring"
        >
          <RefreshCw
            :size="16"
            :class="{
              'animate-spin': loading,
            }"
          />

          Refresh
        </button>
      </div>
    </header>

    <div
      v-if="
        loading
        &&
        !period
        &&
        !errorMessage
      "
      data-testid="monitoring-loading"
      class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"
    >
      <div
        v-for="index in 5"
        :key="index"
        class="h-32 animate-pulse rounded-2xl border border-slate-200 bg-white"
      />
    </div>

    <div
      v-else-if="
        errorMessage
        &&
        !period
      "
      data-testid="monitoring-error"
      class="grid min-h-72 place-items-center rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center"
    >
      <div class="max-w-md">
        <span
          class="mx-auto grid size-14 place-items-center rounded-2xl bg-rose-100 text-rose-700"
        >
          <CircleAlert :size="28" />
        </span>

        <h2
          class="mt-4 text-lg font-bold text-rose-950"
        >
          Monitoring belum dapat dimuat
        </h2>

        <p
          class="mt-2 text-sm leading-6 text-rose-800"
        >
          {{ errorMessage }}
        </p>

        <button
          type="button"
          data-testid="monitoring-retry"
          class="mt-5 inline-flex items-center gap-2 rounded-xl bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-800 disabled:opacity-60"
          :disabled="loading"
          @click="loadMonitoring"
        >
          <RefreshCw
            :size="17"
            :class="{
              'animate-spin': loading,
            }"
          />

          Coba lagi
        </button>
      </div>
    </div>

    <div
      v-else-if="!period"
      data-testid="no-active-period"
      class="grid min-h-72 place-items-center rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center"
    >
      <div class="max-w-md">
        <span
          class="mx-auto grid size-14 place-items-center rounded-2xl bg-amber-100 text-amber-700"
        >
          <CircleAlert :size="28" />
        </span>

        <h2
          class="mt-4 text-lg font-bold text-amber-950"
        >
          Belum ada periode aktif
        </h2>

        <p
          class="mt-2 text-sm leading-6 text-amber-800"
        >
          Monitoring transaksi tersedia setelah
          Admin Dinsos mengaktifkan periode BPNT.
        </p>
      </div>
    </div>

    <template v-else>
      <div
        v-if="errorMessage"
        class="flex flex-col gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 sm:flex-row sm:items-center sm:justify-between"
      >
        <span>
          {{ errorMessage }}
        </span>

        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-300 bg-white px-3 py-2 font-semibold text-rose-800 hover:bg-rose-100"
          @click="loadMonitoring"
        >
          <RefreshCw :size="16" />
          Muat ulang
        </button>
      </div>

      <!-- FILTER -->
      <article
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
      >
        <form
          class="grid gap-3 md:grid-cols-2 xl:grid-cols-7"
          @submit.prevent="applySearch"
        >
          <label class="grid gap-1.5">
            <span
              class="text-[11px] font-bold text-slate-700"
            >
              Periode BPNT
            </span>

            <div
              data-testid="monitoring-period"
              class="flex h-11 items-center rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-800"
            >
              <ClipboardCheck
                :size="16"
                class="mr-2 shrink-0 text-emerald-700"
              />

              <span class="truncate">
                {{ period.name }}
              </span>
            </div>
          </label>

          <label class="grid gap-1.5">
            <span
              class="text-[11px] font-bold text-slate-700"
            >
              Kecamatan
            </span>

            <select
              v-model="selectedKecamatanId"
              class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-3 focus:ring-emerald-100"
              @change="changeKecamatan"
            >
              <option value="">
                Semua Kecamatan
              </option>

              <option
                v-for="row in kecamatanOptions"
                :key="
                  row.kecamatan.id
                  ?? row.kecamatan.name
                  ?? 'unknown'
                "
                :value="
                  String(
                    row.kecamatan.id,
                  )
                "
              >
                {{
                  regionName(
                    row.kecamatan.name,
                  )
                }}
              </option>
            </select>
          </label>

          <label class="grid gap-1.5">
            <span
              class="text-[11px] font-bold text-slate-700"
            >
              Desa / Kelurahan
            </span>

            <select
              v-model="selectedKelurahanId"
              class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-3 focus:ring-emerald-100"
              @change="applyFilters"
            >
              <option value="">
                Semua Kelurahan
              </option>

              <option
                v-for="row in kelurahanOptions"
                :key="
                  row.kelurahan.id
                  ?? row.kelurahan.name
                  ?? 'unknown'
                "
                :value="
                  String(
                    row.kelurahan.id,
                  )
                "
              >
                {{
                  regionName(
                    row.kelurahan.name,
                  )
                }}
              </option>
            </select>
          </label>
          <label
            class="grid gap-1.5 md:col-span-2"
          >
            <span
              class="text-[11px] font-bold text-slate-700"
            >
              Cari NIK / Nama
            </span>

            <span class="relative">
              <Search
                :size="17"
                class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
              />

              <input
                v-model="search"
                data-testid="monitoring-search"
                type="search"
                inputmode="search"
                maxlength="100"
                placeholder="Masukkan NIK atau nama KPM"
                class="h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-3 focus:ring-emerald-100"
                @input="scheduleSearch"
              >
            </span>
          </label>

          <div
            class="grid content-end gap-2 sm:grid-cols-2 xl:grid-cols-1"
          >
            <button
              type="submit"
              class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 disabled:opacity-60"
              :disabled="loading"
            >
              <Search :size="16" />
              Terapkan Filter
            </button>
          </div>
        </form>

        <div
          class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3"
        >
          <button
            v-if="hasFilters"
            type="button"
            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
            @click="resetFilters"
          >
            <X :size="14" />
            Reset
          </button>

          <button
            type="button"
            class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
            :aria-expanded="
              showAdditionalFilters
            "
            @click="
              showAdditionalFilters =
                !showAdditionalFilters
            "
          >
            <SlidersHorizontal :size="15" />
            Filter Lainnya
          </button>
        </div>

        <div
          v-if="showAdditionalFilters"
          class="mt-3 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-2"
        >
          <label class="grid gap-1.5">
            <span
              class="text-[11px] font-bold text-slate-700"
            >
              E-Warung
            </span>

            <select
              v-model="selectedEWarungId"
              class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none focus:border-emerald-600 focus:ring-3 focus:ring-emerald-100"
              @change="applyFilters"
            >
              <option value="">
                Semua E-Warung
              </option>

              <option
                v-for="
                  eWarung in
                    breakdowns.e_warungs
                "
                :key="eWarung.id"
                :value="
                  String(
                    eWarung.id,
                  )
                "
              >
                {{ eWarung.name
                }}{{
                  eWarung.is_active
                    ? ''
                    : ' (Nonaktif)'
                }}
              </option>
            </select>
          </label>

          <label class="grid gap-1.5">
            <span
              class="text-[11px] font-bold text-slate-700"
            >
              Surveyor
            </span>

            <select
              v-model="selectedSurveyorId"
              class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none focus:border-emerald-600 focus:ring-3 focus:ring-emerald-100"
              @change="applyFilters"
            >
              <option value="">
                Semua Surveyor
              </option>

              <option
                v-for="
                  surveyor in
                    breakdowns.surveyors
                "
                :key="surveyor.id"
                :value="
                  String(
                    surveyor.id,
                  )
                "
              >
                {{ surveyor.name }}
              </option>
            </select>
          </label>
        </div>
      </article>

      <!-- SUMMARY -->
      <div
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"
      >
        <article
          class="rounded-2xl border border-blue-100 bg-gradient-to-br from-white to-blue-50 p-4 shadow-sm"
        >
          <div
            class="flex items-start gap-3"
          >
            <span
              class="grid size-11 shrink-0 place-items-center rounded-full bg-blue-100 text-blue-700"
            >
              <UsersRound :size="22" />
            </span>

            <div class="min-w-0">
              <p
                class="text-[11px] font-bold uppercase tracking-wide text-blue-700"
              >
                Total KPM
              </p>

              <strong
                data-testid="summary-total-kpm"
                class="mt-1 block text-2xl font-black text-slate-950"
              >
                {{
                  summary.total_kpm
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                KPM terdaftar
              </p>
            </div>
          </div>
        </article>

        <article
          class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-white to-emerald-50 p-4 shadow-sm"
        >
          <div
            class="flex items-start gap-3"
          >
            <span
              class="grid size-11 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700"
            >
              <CheckCircle2 :size="22" />
            </span>

            <div class="min-w-0">
              <p
                class="text-[11px] font-bold uppercase tracking-wide text-emerald-700"
              >
                Sudah Transaksi
              </p>

              <strong
                data-testid="summary-transacted"
                class="mt-1 block text-2xl font-black text-slate-950"
              >
                {{
                  summary.transacted
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                {{
                  transactionPercentage
                    .toLocaleString(
                      'id-ID',
                      {
                        maximumFractionDigits:
                          1,
                      },
                    )
                }}% dari total KPM
              </p>
            </div>
          </div>
        </article>

        <article
          class="rounded-2xl border border-amber-100 bg-gradient-to-br from-white to-amber-50 p-4 shadow-sm"
        >
          <div
            class="flex items-start gap-3"
          >
            <span
              class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-700"
            >
              <CircleAlert :size="22" />
            </span>

            <div class="min-w-0">
              <p
                class="text-[11px] font-bold uppercase tracking-wide text-amber-700"
              >
                Belum Transaksi
              </p>

              <strong
                data-testid="summary-pending"
                class="mt-1 block text-2xl font-black text-slate-950"
              >
                {{
                  summary.pending
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                {{
                  pendingPercentage
                    .toLocaleString(
                      'id-ID',
                      {
                        maximumFractionDigits:
                          1,
                      },
                    )
                }}% dari total KPM
              </p>
            </div>
          </div>
        </article>

        <article
          class="rounded-2xl border border-sky-100 bg-gradient-to-br from-white to-sky-50 p-4 shadow-sm"
        >
          <div
            class="flex items-start gap-3"
          >
            <span
              class="grid size-11 shrink-0 place-items-center rounded-full bg-sky-100 text-sky-700"
            >
              <ClipboardCheck :size="22" />
            </span>

            <div class="min-w-0">
              <p
                class="text-[11px] font-bold uppercase tracking-wide text-sky-700"
              >
                Verifikasi Final
              </p>

              <strong
                class="mt-1 block text-2xl font-black text-slate-950"
              >
                {{
                  summary
                    .active_verifications
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                Status KPM nontransaksi
              </p>
            </div>
          </div>
        </article>

        <article
          class="rounded-2xl border border-violet-100 bg-gradient-to-br from-white to-violet-50 p-4 shadow-sm"
        >
          <div
            class="flex items-start gap-3"
          >
            <span
              class="grid size-11 shrink-0 place-items-center rounded-full bg-violet-100 text-violet-700"
            >
              <BarChart3 :size="22" />
            </span>

            <div class="min-w-0 flex-1">
              <p
                class="text-[11px] font-bold uppercase tracking-wide text-violet-700"
              >
                Persentase Selesai
              </p>

              <strong
                class="mt-1 block text-2xl font-black text-slate-950"
              >
                {{
                  normalizedProgress
                    .toLocaleString(
                      'id-ID',
                      {
                        maximumFractionDigits:
                          1,
                      },
                    )
                }}%
              </strong>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                Transaksi + verifikasi final
              </p>
            </div>
          </div>

          <div
            class="mt-3 h-1.5 overflow-hidden rounded-full bg-violet-100"
          >
            <div
              class="h-full rounded-full bg-violet-600"
              :style="{
                width:
                  normalizedProgress
                  + '%',
              }"
            />
          </div>
        </article>
      </div>

      <!-- PROGRESS -->
      <div
        class="grid gap-4 xl:grid-cols-12"
      >
        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-5"
        >
          <header
            class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5"
          >
            <div>
              <h2
                class="text-sm font-black uppercase tracking-wide text-slate-900"
              >
                Progres Penanganan per Kecamatan
              </h2>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                Transaksi dan verifikasi final aktif.
              </p>
            </div>

            <MapPin
              :size="19"
              class="text-emerald-700"
            />
          </header>

          <div
            class="divide-y divide-slate-100 px-4"
          >
            <div
              v-for="
                row in
                  breakdowns.kecamatans
                    .slice(0, 7)
              "
              :key="
                row.kecamatan.id
                ?? row.kecamatan.name
                ?? 'unknown'
              "
              class="grid gap-2 py-3"
            >
              <div
                class="flex items-center justify-between gap-3 text-xs"
              >
                <strong
                  class="truncate text-slate-800"
                >
                  {{
                    regionName(
                      row.kecamatan.name,
                    )
                  }}
                </strong>

                <span
                  class="shrink-0 font-bold text-slate-600"
                >
                  {{
                    handledKpm(
                      row.transacted,
                      row.active_verifications,
                    ).toLocaleString(
                      'id-ID',
                    )
                  }}
                  /
                  {{
                    row.total_kpm
                      .toLocaleString(
                        'id-ID',
                      )
                  }}
                </span>
              </div>

              <div
                class="flex items-center gap-3"
              >
                <div
                  class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"
                >
                  <div
                    class="h-full rounded-full bg-emerald-600"
                    :style="{
                      width:
                        progressPercentage(
                          handledKpm(
                            row.transacted,
                            row.active_verifications,
                          ),
                          row.total_kpm,
                        )
                        + '%',
                    }"
                  />
                </div>

                <span
                  class="w-10 text-right text-xs font-black text-slate-800"
                >
                  {{
                    Math.round(
                      progressPercentage(
                        handledKpm(
                          row.transacted,
                          row.active_verifications,
                        ),
                        row.total_kpm,
                      ),
                    )
                  }}%
                </span>
              </div>
            </div>

            <p
              v-if="
                breakdowns.kecamatans
                  .length === 0
              "
              class="py-10 text-center text-sm text-slate-500"
            >
              Belum ada data kecamatan.
            </p>
          </div>
        </article>

        <!-- DONUT -->
        <article
          class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-3"
        >
          <header
            class="border-b border-slate-200 px-4 py-3.5"
          >
            <h2
              class="text-sm font-black uppercase tracking-wide text-slate-900"
            >
              Status Penanganan KPM
            </h2>
          </header>

          <div
            class="grid place-items-center p-5"
          >
            <div
              class="relative size-44 rounded-full"
              :style="{
                background:
                  donutBackground,
              }"
            >
              <div
                class="absolute inset-7 grid place-items-center rounded-full bg-white text-center shadow-inner"
              >
                <div>
                  <strong
                    class="block text-xl font-black text-slate-950"
                  >
                    {{
                      summary.total_kpm
                        .toLocaleString(
                          'id-ID',
                        )
                    }}
                  </strong>

                  <span
                    class="text-[11px] text-slate-500"
                  >
                    Total KPM
                  </span>
                </div>
              </div>
            </div>

            <div
              class="mt-5 grid w-full gap-2.5 text-xs"
            >
              <div
                class="flex items-center justify-between gap-3"
              >
                <span
                  class="flex items-center gap-2 text-slate-600"
                >
                  <i
                    class="size-2.5 rounded-full bg-emerald-600"
                  />
                  Sudah transaksi
                </span>

                <strong
                  class="text-slate-900"
                >
                  {{
                    summary.transacted
                      .toLocaleString(
                        'id-ID',
                      )
                  }}
                </strong>
              </div>

              <div
                class="flex items-center justify-between gap-3"
              >
                <span
                  class="flex items-center gap-2 text-slate-600"
                >
                  <i
                    class="size-2.5 rounded-full bg-blue-600"
                  />
                  Verifikasi final
                </span>

                <strong
                  class="text-slate-900"
                >
                  {{
                    summary
                      .active_verifications
                      .toLocaleString(
                        'id-ID',
                      )
                  }}
                </strong>
              </div>

              <div
                class="flex items-center justify-between gap-3"
              >
                <span
                  class="flex items-center gap-2 text-slate-600"
                >
                  <i
                    class="size-2.5 rounded-full bg-amber-500"
                  />
                  Belum transaksi
                </span>

                <strong
                  class="text-slate-900"
                >
                  {{
                    summary.pending
                      .toLocaleString(
                        'id-ID',
                      )
                  }}
                </strong>
              </div>
            </div>
          </div>
        </article>

        <!-- FINAL STATUS -->
        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-4"
        >
          <header
            class="border-b border-slate-200 px-4 py-3.5"
          >
            <h2
              class="text-sm font-black uppercase tracking-wide text-slate-900"
            >
              Status Verifikasi Final
            </h2>

            <p
              class="mt-1 text-xs text-slate-500"
            >
              Manager dapat meninjau status
              yang ditetapkan Surveyor.
            </p>
          </header>

          <div class="grid gap-2.5 p-4">
            <div
              class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3"
            >
              <span
                class="grid size-10 shrink-0 place-items-center rounded-xl bg-slate-200 text-slate-700"
              >
                <ClipboardCheck :size="19" />
              </span>

              <div class="min-w-0 flex-1">
                <p
                  class="text-xs font-bold text-slate-800"
                >
                  Meninggal
                </p>

                <p
                  class="mt-0.5 text-[11px] text-slate-500"
                >
                  Verifikasi final aktif
                </p>
              </div>

              <strong
                class="text-xl font-black text-slate-950"
              >
                {{
                  summary.deceased
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>
            </div>

            <div
              class="flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50 p-3"
            >
              <span
                class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-700"
              >
                <MapPin :size="19" />
              </span>

              <div class="min-w-0 flex-1">
                <p
                  class="text-xs font-bold text-blue-950"
                >
                  Pindah Domisili
                </p>

                <p
                  class="mt-0.5 text-[11px] text-blue-700"
                >
                  Verifikasi final aktif
                </p>
              </div>

              <strong
                class="text-xl font-black text-blue-950"
              >
                {{
                  summary.moved_domicile
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>
            </div>

            <div
              class="flex items-center gap-3 rounded-xl border border-amber-100 bg-amber-50 p-3"
            >
              <span
                class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700"
              >
                <CircleAlert :size="19" />
              </span>

              <div class="min-w-0 flex-1">
                <p
                  class="text-xs font-bold text-amber-950"
                >
                  Tidak Mengambil
                </p>

                <p
                  class="mt-0.5 text-[11px] text-amber-700"
                >
                  Disertai alasan Surveyor
                </p>
              </div>

              <strong
                class="text-xl font-black text-amber-950"
              >
                {{
                  summary.not_claimed
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>
            </div>

            <div
              class="flex items-center gap-3 rounded-xl border border-rose-100 bg-rose-50 p-3"
            >
              <span
                class="grid size-10 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700"
              >
                <Activity :size="19" />
              </span>

              <strong
                class="text-xl font-black text-rose-950"
              >
                {{
                  summary
                    .outside_assignment
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>
            </div>
          </div>
        </article>
      </div>

      <!-- TRANSACTIONS + SURVEYORS -->
      <div
        class="grid gap-4 xl:grid-cols-12"
      >
        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-8"
        >
          <header
            class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <h2
                class="text-sm font-black uppercase tracking-wide text-slate-900"
              >
                Transaksi Terbaru
              </h2>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                {{
                  totalTransactions
                    .toLocaleString(
                      'id-ID',
                    )
                }}
                transaksi ditemukan
              </p>
            </div>

            <button
              type="button"
              class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
              :disabled="loading"
              @click="loadMonitoring"
            >
              <RefreshCw
                :size="14"
                :class="{
                  'animate-spin':
                    loading,
                }"
              />

              Perbarui
            </button>
          </header>

          <div
            v-if="
              loading
              &&
              transactions.length === 0
            "
            class="grid gap-3 p-4"
          >
            <div
              v-for="index in 4"
              :key="index"
              class="h-20 animate-pulse rounded-xl bg-slate-100"
            />
          </div>

          <div
            v-else-if="
              transactions.length === 0
            "
            data-testid="monitoring-empty"
            class="grid min-h-56 place-items-center p-6 text-center"
          >
            <div>
              <span
                class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-500"
              >
                <Search :size="23" />
              </span>

              <h3
                class="mt-3 font-bold text-slate-900"
              >
                Transaksi tidak ditemukan
              </h3>

              <p
                class="mt-1 text-sm text-slate-500"
              >
                Ubah kata pencarian atau
                filter yang digunakan.
              </p>
            </div>
          </div>

          <template v-else>
            <!-- MOBILE -->
            <div
              class="grid gap-3 p-4 lg:hidden"
            >
              <article
                v-for="
                  transaction in
                    transactions
                "
                :key="transaction.id"
                class="rounded-xl border border-slate-200 p-4"
              >
                <div
                  class="flex items-start justify-between gap-3"
                >
                  <div class="min-w-0">
                    <h3
                      class="truncate font-bold text-slate-900"
                    >
                      {{
                        transaction
                          .participant
                          .kpm
                          .full_name
                      }}
                    </h3>

                    <p
                      class="mt-1 font-mono text-xs text-slate-500"
                    >
                      {{
                        transaction
                          .participant
                          .kpm
                          .nik
                        ?? '-'
                      }}
                    </p>
                  </div>

                  
                </div>

                <dl
                  class="mt-4 grid gap-2 text-xs text-slate-600"
                >
                  <div
                    class="flex justify-between gap-3"
                  >
                    <dt>Wilayah</dt>

                    <dd
                      class="text-right font-semibold text-slate-800"
                    >
                      {{
                        regionName(
                          transaction
                            .participant
                            .wilayah
                            .kelurahan
                            .name,
                        )
                      }},
                      {{
                        regionName(
                          transaction
                            .participant
                            .wilayah
                            .kecamatan
                            .name,
                        )
                      }}
                    </dd>
                  </div>

                  <div
                    class="flex justify-between gap-3"
                  >
                    <dt>Surveyor</dt>

                    <dd
                      class="text-right font-semibold text-slate-800"
                    >
                      {{
                        transaction
                          .surveyor
                          .name
                      }}
                    </dd>
                  </div>

                  <div
                    class="flex justify-between gap-3"
                  >
                    <dt>E-Warung</dt>

                    <dd
                      class="text-right font-semibold text-slate-800"
                    >
                      {{
                        transaction
                          .e_warung
                          .name
                      }}
                    </dd>
                  </div>
                </dl>

                <p
                  class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500"
                >
                  {{
                    formatDateTime(
                      transaction
                        .transacted_at,
                    )
                  }}
                  WIB
                </p>
              </article>
            </div>

            <!-- DESKTOP -->
            <div
              class="hidden overflow-x-auto lg:block"
            >
              <table
                class="w-full min-w-[900px] border-collapse text-left"
              >
                <thead
                  class="bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-500"
                >
                  <tr>
                    <th class="px-4 py-3">
                      Tanggal / Jam
                    </th>
                    <th class="px-4 py-3">
                      NIK
                    </th>
                    <th class="px-4 py-3">
                      Nama KPM
                    </th>
                    <th class="px-4 py-3">
                      Kecamatan
                    </th>
                    <th class="px-4 py-3">
                      Kelurahan
                    </th>
                    <th class="px-4 py-3">
                      Surveyor
                    </th>
                    <th class="px-4 py-3">
                      E-Warung
                    </th>
                    <th class="px-4 py-3">
                      Status
                    </th>
                  </tr>
                </thead>

                <tbody
                  class="divide-y divide-slate-100 text-xs"
                >
                  <tr
                    v-for="
                      transaction in
                        transactions
                    "
                    :key="transaction.id"
                    class="transition hover:bg-slate-50"
                  >
                    <td
                      class="whitespace-nowrap px-4 py-3 text-slate-600"
                    >
                      {{
                        formatDateTime(
                          transaction
                            .transacted_at,
                        )
                      }}
                      WIB
                    </td>

                    <td
                      class="whitespace-nowrap px-4 py-3 font-mono text-slate-600"
                    >
                      {{
                        transaction
                          .participant
                          .kpm
                          .nik
                        ?? '-'
                      }}
                    </td>

                    <td
                      class="px-4 py-3 font-bold text-slate-900"
                    >
                      {{
                        transaction
                          .participant
                          .kpm
                          .full_name
                      }}
                    </td>

                    <td
                      class="px-4 py-3 text-slate-700"
                    >
                      {{
                        regionName(
                          transaction
                            .participant
                            .wilayah
                            .kecamatan
                            .name,
                        )
                      }}
                    </td>

                    <td
                      class="px-4 py-3 text-slate-700"
                    >
                      {{
                        regionName(
                          transaction
                            .participant
                            .wilayah
                            .kelurahan
                            .name,
                        )
                      }}
                    </td>

                    <td
                      class="px-4 py-3 text-slate-700"
                    >
                      {{
                        transaction
                          .surveyor
                          .name
                      }}
                    </td>

                    <td
                      class="px-4 py-3 text-slate-700"
                    >
                      {{
                        transaction
                          .e_warung
                          .name
                      }}
                    </td>

                    <td class="px-4 py-3">
                      <span
                        class="inline-flex whitespace-nowrap rounded-md px-2 py-1 font-bold"
                        :class="
                          transaction
                            .outside_assignment
                            ? 'bg-rose-100 text-rose-700'
                            : 'bg-emerald-100 text-emerald-700'
                        "
                      >
                        {{
                          transaction
                            .outside_assignment
                            ? 'Luar Wilayah'
                            : 'Berhasil'
                        }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <footer
              class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between"
            >
              <p
                class="text-xs text-slate-500"
              >
                Menampilkan
                {{ pageStart }}–{{ pageEnd }}
                dari
                {{
                  totalTransactions
                    .toLocaleString(
                      'id-ID',
                    )
                }}
                transaksi
              </p>

              <div
                class="flex items-center gap-2"
              >
                <button
                  type="button"
                  aria-label="Halaman sebelumnya"
                  class="grid size-9 place-items-center rounded-lg border border-slate-300 text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="
                    currentPage <= 1
                    ||
                    loading
                  "
                  @click="
                    goToPage(
                      currentPage - 1,
                    )
                  "
                >
                  <ChevronLeft :size="17" />
                </button>

                <span
                  class="min-w-24 text-center text-xs font-bold text-slate-700"
                >
                  {{ currentPage }}
                  /
                  {{ lastPage }}
                </span>

                <button
                  type="button"
                  aria-label="Halaman berikutnya"
                  class="grid size-9 place-items-center rounded-lg border border-slate-300 text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="
                    currentPage >= lastPage
                    ||
                    loading
                  "
                  @click="
                    goToPage(
                      currentPage + 1,
                    )
                  "
                >
                  <ChevronRight :size="17" />
                </button>
              </div>
            </footer>
          </template>
        </article>

        <!-- SURVEYOR ACTIVITY -->
        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-4"
        >
          <header
            class="border-b border-slate-200 px-4 py-3.5"
          >
            <h2
              class="text-sm font-black uppercase tracking-wide text-slate-900"
            >
              Aktivitas Surveyor Periode Ini
            </h2>

            <p
              class="mt-1 text-xs text-slate-500"
            >
              Jumlah transaksi yang dicatat
              masing-masing Surveyor.
            </p>
          </header>

          <div
            class="divide-y divide-slate-100 px-4"
          >
            <div
              v-for="
                surveyor in
                  breakdowns.surveyors
                    .slice(0, 7)
              "
              :key="surveyor.id"
              class="grid gap-2 py-3"
            >
              <div
                class="flex items-center gap-3"
              >
                <span
                  class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-700"
                >
                  <UserRound :size="17" />
                </span>

                <div
                  class="min-w-0 flex-1"
                >
                  <div
                    class="flex items-center justify-between gap-3"
                  >
                    <strong
                      class="truncate text-xs text-slate-800"
                    >
                      {{ surveyor.name }}
                    </strong>

                    <span
                      class="shrink-0 text-xs font-black text-slate-800"
                    >
                      {{
                        surveyor
                          .transactions
                          .toLocaleString(
                            'id-ID',
                          )
                      }}
                    </span>
                  </div>

                  <p
                    class="mt-0.5 truncate text-[11px] text-slate-500"
                  >
                    {{
                      regionName(
                        surveyor
                          .assignment
                          .kelurahan
                          .name,
                      )
                    }},
                    {{
                      regionName(
                        surveyor
                          .assignment
                          .kecamatan
                          .name,
                      )
                    }}
                  </p>
                </div>
              </div>

              <div
                class="ml-12 flex items-center gap-3"
              >
                <div
                  class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100"
                >
                  <div
                    class="h-full rounded-full bg-emerald-600"
                    :style="{
                      width:
                        progressPercentage(
                          surveyor
                            .transactions,
                          maxSurveyorTransactions,
                        )
                        + '%',
                    }"
                  />
                </div>

                <span
                  v-if="
                    surveyor
                      .outside_assignment
                    > 0
                  "
                  class="shrink-0 text-[10px] font-bold text-rose-700"
                >
                  {{
                    surveyor
                      .outside_assignment
                  }}
                  luar wilayah
                </span>
              </div>
            </div>

            <p
              v-if="
                breakdowns.surveyors
                  .length === 0
              "
              class="py-10 text-center text-sm text-slate-500"
            >
              Belum ada aktivitas Surveyor.
            </p>
          </div>
        </article>
      </div>

      <!-- E-WARUNG -->
      <article
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <header
          class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5"
        >
          <div>
            <h2
              class="text-sm font-black uppercase tracking-wide text-slate-900"
            >
              Aktivitas E-Warung
            </h2>

            <p
              class="mt-1 text-xs text-slate-500"
            >
              Ringkasan transaksi pada seluruh
              E-Warung dalam periode aktif.
            </p>
          </div>

          <Store
            :size="19"
            class="text-emerald-700"
          />
        </header>

        <div
          class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4"
        >
          <article
            v-for="
              eWarung in
                breakdowns.e_warungs
            "
            :key="eWarung.id"
            class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-3.5"
          >
            <div class="min-w-0">
              <p
                class="truncate text-sm font-bold text-slate-900"
              >
                {{ eWarung.name }}
              </p>

              <p
                class="mt-1 text-[11px] font-semibold"
                :class="
                  eWarung.is_active
                    ? 'text-emerald-700'
                    : 'text-slate-500'
                "
              >
                {{
                  eWarung.is_active
                    ? 'Aktif'
                    : 'Nonaktif'
                }}
              </p>
            </div>

            <div
              class="shrink-0 text-right"
            >
              <strong
                class="text-xl font-black text-slate-950"
              >
                {{
                  eWarung.transactions
                    .toLocaleString(
                      'id-ID',
                    )
                }}
              </strong>

              <p
                class="text-[10px] text-slate-500"
              >
                transaksi
              </p>
            </div>
          </article>

          <p
            v-if="
              breakdowns.e_warungs
                .length === 0
            "
            class="py-8 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-4"
          >
            Belum ada aktivitas E-Warung.
          </p>
        </div>
      </article>
    </template>
  </section>
</template>