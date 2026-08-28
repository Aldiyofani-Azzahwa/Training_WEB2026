<script setup lang="ts">
import axios from 'axios'

import {
  AlertCircle,
  ChevronLeft,
  ChevronRight,
  FileCheck2,
  FileSpreadsheet,
  LoaderCircle,
  Printer,
  RefreshCw,
  Search,
  ShieldCheck,
  Store,
  UserRound,
  UsersRound,
} from '@lucide/vue'

import {
  computed,
  onMounted,
  ref,
  watch,
} from 'vue'

import {
  bnbaService,
} from '@/services/bnbaService'

import {
  bpntReportService,
} from '@/services/bpntReportService'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  BpntReport,
  BpntResolutionCode,
} from '@/types/bpntReport'

import type {
  BpntPeriod,
} from '@/types/bnba'

const PARTICIPANTS_PER_PAGE =
  50

const authStore =
  useAuthStore()

const activePeriod =
  ref<BpntPeriod | null>(null)

const report =
  ref<BpntReport | null>(null)

const search =
  ref('')

const resolution =
  ref('')

const currentPage =
  ref(1)

const loading =
  ref(false)

const finalizing =
  ref(false)

const errorMessage =
  ref('')

const isManager =
  computed(() =>
    authStore.role === 'manager',
  )

const isFinal =
  computed(() =>
    report.value?.status.code
      === 'final',
  )

const snapshot =
  computed(() =>
    report.value?.snapshot
      ?? null,
  )

const filteredParticipants =
  computed(() => {
    const keyword =
      search.value
        .trim()
        .toLocaleLowerCase('id-ID')

    return (
      snapshot.value?.participants
      ?? []
    ).filter((participant) => {
      if (
        resolution.value !== ''
        && participant.resolution.code
          !== resolution.value
      ) {
        return false
      }

      if (keyword === '') {
        return true
      }

      return [
        participant.full_name,
        participant.nik ?? '',
        participant.wilayah.kecamatan.name ?? '',
        participant.wilayah.kelurahan.name ?? '',
      ].some(
        (value) =>
          value
            .toLocaleLowerCase('id-ID')
            .includes(keyword),
      )
    })
  })

const lastPage =
  computed(() =>
    Math.max(
      1,
      Math.ceil(
        filteredParticipants.value.length
          / PARTICIPANTS_PER_PAGE,
      ),
    ),
  )

const paginatedParticipants =
  computed(() => {
    const offset =
      (currentPage.value - 1)
      * PARTICIPANTS_PER_PAGE

    return filteredParticipants.value.slice(
      offset,
      offset + PARTICIPANTS_PER_PAGE,
    )
  })

const pageStart =
  computed(() => {
    if (
      filteredParticipants.value.length
      === 0
    ) {
      return 0
    }

    return (
      (currentPage.value - 1)
      * PARTICIPANTS_PER_PAGE
    ) + 1
  })

const pageEnd =
  computed(() =>
    Math.min(
      currentPage.value
        * PARTICIPANTS_PER_PAGE,
      filteredParticipants.value.length,
    ),
  )

const unavailableMessage =
  computed(() =>
    isManager.value
      ? 'Laporan periode aktif belum tersedia.'
      : 'Laporan final periode aktif belum tersedia.',
  )

function resolveErrorMessage(
  error: unknown,
): string {
  if (axios.isAxiosError(error)) {
    const payload:
      unknown =
        error.response?.data

    if (
      typeof payload === 'object'
      && payload !== null
      && 'message' in payload
      && typeof payload.message
        === 'string'
      && payload.message.trim() !== ''
    ) {
      return payload.message
    }

    if (
      typeof payload === 'object'
      && payload !== null
      && 'errors' in payload
      && typeof payload.errors
        === 'object'
      && payload.errors !== null
    ) {
      const firstError =
        Object.values(
          payload.errors,
        ).flat()[0]

      if (
        typeof firstError
        === 'string'
      ) {
        return firstError
      }
    }
  }

  return 'Laporan belum dapat dimuat. Silakan coba kembali.'
}

async function loadActiveReport():
  Promise<void> {
  loading.value =
    true

  errorMessage.value =
    ''

  activePeriod.value =
    null

  report.value =
    null

  try {
    activePeriod.value =
      await bnbaService
        .getActivePeriod()

    if (activePeriod.value === null) {
      return
    }

    report.value =
      await bpntReportService
        .getReport(
          activePeriod.value.id,
        )

    search.value =
      ''

    resolution.value =
      ''

    currentPage.value =
      1
  } catch (error: unknown) {
    errorMessage.value =
      resolveErrorMessage(error)
  } finally {
    loading.value =
      false
  }
}

async function finalizeReport():
  Promise<void> {
  if (
    report.value === null
    || !isManager.value
    || isFinal.value
    || !report.value.can_finalize
    || finalizing.value
  ) {
    return
  }

  const confirmed =
    window.confirm(
      [
        'Validasi laporan final?',
        'Setelah divalidasi, data periode ini',
        'akan dibekukan dan tidak dapat diubah.',
      ].join(' '),
    )

  if (!confirmed) {
    return
  }

  finalizing.value =
    true

  errorMessage.value =
    ''

  try {
    report.value =
      await bpntReportService
        .finalize(
          report.value.period.id,
        )
  } catch (error: unknown) {
    errorMessage.value =
      resolveErrorMessage(error)
  } finally {
    finalizing.value =
      false
  }
}

function exportExcel(): void {
  if (
    report.value === null
    || !isFinal.value
  ) {
    return
  }

  window.location.assign(
    bpntReportService.excelUrl(
      report.value.period.id,
    ),
  )
}

function printPdf(): void {
  if (isFinal.value) {
    window.print()
  }
}

function formatDateTime(
  value: string | null,
): string {
  if (value === null) {
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
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Asia/Jakarta',
    },
  ).format(date)
}

function statusClass(
  code: BpntResolutionCode,
): string {
  const classes:
    Record<
      BpntResolutionCode,
      string
    > = {
      transacted:
        'bg-emerald-100 text-emerald-800',
      pending:
        'bg-amber-100 text-amber-800',
      deceased:
        'bg-slate-200 text-slate-800',
      moved_domicile:
        'bg-blue-100 text-blue-800',
      not_claimed:
        'bg-orange-100 text-orange-800',
    }

  return classes[code]
}

function goToPage(
  page: number,
): void {
  currentPage.value =
    Math.min(
      Math.max(page, 1),
      lastPage.value,
    )
}

watch(
  [
    search,
    resolution,
  ],
  () => {
    currentPage.value =
      1
  },
)

onMounted(() => {
  void loadActiveReport()
})
</script>

<template>
  <section
    class="mx-auto grid w-full max-w-[1800px] gap-5 p-4 sm:p-6 xl:p-8 print:max-w-none print:p-0"
  >
    <header
      class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between print:border-b print:border-slate-300 print:pb-4"
    >
      <div>
        <p
          class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 print:text-slate-600"
        >
          <FileCheck2 :size="16" />
          Laporan BPNT
        </p>

        <h1
          class="mt-2 text-2xl font-black text-slate-950 sm:text-3xl"
        >
          Laporan Penyaluran BPNT
        </h1>

        <p
          class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 print:hidden"
        >
          Laporan mengikuti periode BPNT aktif.
          Admin Dinsos dan Kepala Dinas menerima data
          setelah divalidasi oleh Manager melalui
          halaman Monitoring.
        </p>
      </div>

      <div
        class="flex flex-wrap gap-2 print:hidden"
      >
      <button
  v-if="
    isManager
    && report !== null
    && !isFinal
  "
  type="button"
  data-testid="report-finalize-button"
  class="inline-flex h-10 items-center gap-2 rounded-xl bg-blue-700 px-4 text-sm font-bold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
  :disabled="
    !report.can_finalize
    || finalizing
  "
  :title="
    report.can_finalize
      ? 'Validasi laporan periode aktif'
      : report.blocking_reason
        ?? 'Laporan belum dapat divalidasi.'
  "
  @click="finalizeReport"
>
  <LoaderCircle
    v-if="finalizing"
    :size="16"
    class="animate-spin"
  />

  <ShieldCheck
    v-else
    :size="16"
  />

  {{
    finalizing
      ? 'Memvalidasi...'
      : 'Validasi Laporan Final'
  }}
</button>
        <button
          v-if="isFinal"
          type="button"
          class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-50"
          @click="printPdf"
        >
          <Printer :size="16" />
          Cetak / Simpan PDF
        </button>

        <button
          v-if="isFinal"
          type="button"
          class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-800"
          @click="exportExcel"
        >
          <FileSpreadsheet :size="16" />
          Unduh Excel
        </button>

        <button
          type="button"
          class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading"
          @click="loadActiveReport"
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
      v-if="errorMessage"
      role="alert"
      class="flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 print:hidden"
    >
      <AlertCircle
        :size="20"
        class="shrink-0"
      />

      {{ errorMessage }}
    </div>

    <div
      v-if="loading"
      data-testid="report-loading"
      class="grid min-h-72 place-items-center rounded-2xl border border-slate-200 bg-white"
    >
      <LoaderCircle
        :size="32"
        class="animate-spin text-emerald-700"
      />
    </div>

    <div
      v-else-if="activePeriod === null"
      data-testid="report-no-active-period"
      class="grid min-h-72 place-items-center rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center"
    >
      <div>
        <FileCheck2
          :size="34"
          class="mx-auto text-slate-400"
        />

        <h2
          class="mt-3 font-black text-slate-900"
        >
          Belum ada periode BPNT aktif
        </h2>

        <p
          class="mt-1 text-sm text-slate-500"
        >
          Laporan akan tersedia setelah Admin Dinsos
          mengaktifkan periode BPNT.
        </p>
      </div>
    </div>

    <div
      v-else-if="report === null"
      data-testid="report-unavailable"
      class="grid min-h-72 place-items-center rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center"
    >
      <div>
        <FileCheck2
          :size="34"
          class="mx-auto text-slate-400"
        />

        <h2
          class="mt-3 font-black text-slate-900"
        >
          {{ unavailableMessage }}
        </h2>

        <p
          class="mt-1 text-sm text-slate-500"
        >
          {{ activePeriod?.name ?? '-' }}
          ·
          {{ activePeriod?.code ?? '-' }}
        </p>
      </div>
    </div>

    <template v-else>
      <article
        class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 lg:flex-row lg:items-end lg:justify-between print:border-0 print:p-0"
      >
        <div
          data-testid="report-active-period"
        >
          <p
            class="text-xs font-bold uppercase text-slate-500"
          >
            Periode Aktif
          </p>

          <h2
            class="mt-1 text-xl font-black text-slate-950"
          >
            {{ report.period.name }}
          </h2>

          <p
            class="mt-1 text-xs text-slate-600"
          >
            {{ report.period.code }}
            · Tahun {{ report.period.year }}
          </p>
        </div>

        <div
          class="flex flex-wrap gap-2"
        >
          <span
            data-testid="report-status"
            class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black uppercase"
            :class="
              isFinal
                ? 'bg-emerald-100 text-emerald-800'
                : 'bg-amber-100 text-amber-800'
            "
          >
            <ShieldCheck :size="15" />
            {{ report.status.label }}
          </span>

          <span
            v-if="report.period.is_active"
            class="rounded-full bg-blue-100 px-3 py-1.5 text-xs font-bold text-blue-800 print:hidden"
          >
            Periode Aktif
          </span>
        </div>
      </article>

      <template v-if="snapshot">
        <article
          v-if="isFinal"
          data-testid="report-final-information"
          class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between print:border-slate-300 print:bg-white"
        >
          <div>
            <h2
              class="font-black text-emerald-950 print:text-slate-950"
            >
              Laporan Final
            </h2>

            <p
              class="mt-1 text-xs text-emerald-800 print:text-slate-600"
            >
              Divalidasi oleh
              {{ report.finalized_by?.name ?? '-' }}
              pada
              {{ formatDateTime(report.finalized_at) }}
              WIB.
            </p>
          </div>

          <strong
            class="text-xs text-emerald-800 print:text-slate-600"
          >
            Data telah dibekukan
          </strong>
        </article>

        <div
          class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
        >
          <article
            class="rounded-2xl border border-blue-100 bg-blue-50 p-4"
          >
            <p
              class="text-xs font-bold uppercase text-blue-700"
            >
              Total KPM
            </p>

            <strong
              class="mt-2 block text-2xl font-black text-slate-950"
            >
              {{
                report.summary.total_kpm
                  .toLocaleString('id-ID')
              }}
            </strong>
          </article>

          <article
            class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"
          >
            <p
              class="text-xs font-bold uppercase text-emerald-700"
            >
              Sudah Transaksi
            </p>

            <strong
              class="mt-2 block text-2xl font-black text-slate-950"
            >
              {{
                report.summary.transacted
                  .toLocaleString('id-ID')
              }}
            </strong>
          </article>

          <article
            class="rounded-2xl border border-amber-100 bg-amber-50 p-4"
          >
            <p
              class="text-xs font-bold uppercase text-amber-700"
            >
              Belum Transaksi
            </p>

            <strong
              class="mt-2 block text-2xl font-black text-slate-950"
            >
              {{
                report.summary.pending
                  .toLocaleString('id-ID')
              }}
            </strong>
          </article>

          <article
            class="rounded-2xl border border-violet-100 bg-violet-50 p-4"
          >
            <p
              class="text-xs font-bold uppercase text-violet-700"
            >
              Penyelesaian
            </p>

            <strong
              class="mt-2 block text-2xl font-black text-slate-950"
            >
              {{
                report.summary
                  .completion_percentage
                  .toLocaleString('id-ID')
              }}%
            </strong>
          </article>
        </div>

        <div
          class="grid gap-4 xl:grid-cols-2"
        >
          <article
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
          >
            <header
              class="border-b border-slate-200 px-4 py-3"
            >
              <h2
                class="font-black text-slate-900"
              >
                Rekap Status KPM
              </h2>
            </header>

            <div
              class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3"
            >
              <div
                class="rounded-xl bg-emerald-50 p-3"
              >
                <p class="text-xs text-emerald-700">
                  Transaksi
                </p>

                <strong class="mt-1 block text-xl">
                  {{ report.summary.transacted }}
                </strong>
              </div>

              <div
                class="rounded-xl bg-slate-100 p-3"
              >
                <p class="text-xs text-slate-600">
                  Meninggal
                </p>

                <strong class="mt-1 block text-xl">
                  {{ report.summary.deceased }}
                </strong>
              </div>

              <div
                class="rounded-xl bg-blue-50 p-3"
              >
                <p class="text-xs text-blue-700">
                  Pindah Domisili
                </p>

                <strong class="mt-1 block text-xl">
                  {{ report.summary.moved_domicile }}
                </strong>
              </div>

              <div
                class="rounded-xl bg-orange-50 p-3"
              >
                <p class="text-xs text-orange-700">
                  Tidak Mengambil
                </p>

                <strong class="mt-1 block text-xl">
                  {{ report.summary.not_claimed }}
                </strong>
              </div>

              <div
                class="rounded-xl bg-amber-50 p-3"
              >
                <p class="text-xs text-amber-700">
                  Belum Selesai
                </p>

                <strong class="mt-1 block text-xl">
                  {{ report.summary.pending }}
                </strong>
              </div>

              <div
                class="rounded-xl bg-violet-50 p-3"
              >
                <p class="text-xs text-violet-700">
                  Total Verifikasi
                </p>

                <strong class="mt-1 block text-xl">
                  {{
                    report.summary
                      .active_verifications
                  }}
                </strong>
              </div>
            </div>
          </article>

          <article
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
          >
            <header
              class="border-b border-slate-200 px-4 py-3"
            >
              <h2
                class="font-black text-slate-900"
              >
                Aktivitas E-Warung
              </h2>
            </header>

            <div
              class="divide-y divide-slate-100 px-4"
            >
              <div
                v-for="eWarung in snapshot.e_warungs"
                :key="eWarung.id"
                class="flex items-center justify-between gap-3 py-3 text-sm"
              >
                <span
                  class="flex min-w-0 items-center gap-2 font-semibold text-slate-700"
                >
                  <Store
                    :size="16"
                    class="shrink-0 text-emerald-700"
                  />

                  <span class="truncate">
                    {{ eWarung.name }}
                  </span>
                </span>

                <strong>
                  {{ eWarung.transactions }}
                  transaksi
                </strong>
              </div>

              <p
                v-if="snapshot.e_warungs.length === 0"
                class="py-8 text-center text-sm text-slate-500"
              >
                Belum ada transaksi E-Warung.
              </p>
            </div>
          </article>
        </div>

        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
        >
          <header
            class="border-b border-slate-200 px-4 py-3"
          >
            <h2
              class="font-black text-slate-900"
            >
              Rekap Wilayah Asal KPM
            </h2>
          </header>

          <div class="overflow-x-auto">
            <table
              class="w-full min-w-[860px] text-left text-xs"
            >
              <thead
                class="bg-slate-50 font-black uppercase text-slate-500"
              >
                <tr>
                  <th class="px-4 py-3">
                    Kecamatan
                  </th>

                  <th class="px-4 py-3">
                    Kelurahan
                  </th>

                  <th class="px-4 py-3">
                    Total
                  </th>

                  <th class="px-4 py-3">
                    Transaksi
                  </th>

                  <th class="px-4 py-3">
                    Belum
                  </th>

                  <th class="px-4 py-3">
                    Meninggal
                  </th>

                  <th class="px-4 py-3">
                    Pindah
                  </th>

                  <th class="px-4 py-3">
                    Tidak Mengambil
                  </th>
                </tr>
              </thead>

              <tbody
                class="divide-y divide-slate-100"
              >
                <tr
                  v-for="row in snapshot.wilayah"
                  :key="
                    row.kelurahan.id
                    ?? row.kelurahan.name
                    ?? 'unknown'
                  "
                >
                  <td class="px-4 py-3">
                    {{
                      row.kecamatan.name
                      ?? '-'
                    }}
                  </td>

                  <td
                    class="px-4 py-3 font-bold"
                  >
                    {{
                      row.kelurahan.name
                      ?? '-'
                    }}
                  </td>

                  <td class="px-4 py-3">
                    {{ row.total_kpm }}
                  </td>

                  <td
                    class="px-4 py-3 text-emerald-700"
                  >
                    {{ row.transacted }}
                  </td>

                  <td
                    class="px-4 py-3 text-amber-700"
                  >
                    {{ row.pending }}
                  </td>

                  <td class="px-4 py-3">
                    {{ row.deceased }}
                  </td>

                  <td class="px-4 py-3">
                    {{ row.moved_domicile }}
                  </td>

                  <td class="px-4 py-3">
                    {{ row.not_claimed }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
        >
          <header
            class="border-b border-slate-200 px-4 py-3"
          >
            <h2
              class="font-black text-slate-900"
            >
              Aktivitas Surveyor
            </h2>
          </header>

          <div
            class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3"
          >
            <div
              v-for="surveyor in snapshot.surveyors"
              :key="surveyor.id"
              class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
            >
              <span
                class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-700"
              >
                <UserRound :size="18" />
              </span>

              <div class="min-w-0 flex-1">
                <p
                  class="truncate text-sm font-bold text-slate-900"
                >
                  {{ surveyor.name }}
                </p>

                <p
                  class="truncate text-xs text-slate-500"
                >
                  {{
                    surveyor.assignment
                      .kelurahan.name
                    ?? '-'
                  }}
                </p>
              </div>

              <div class="text-right text-xs">
                <strong
                  class="block text-emerald-700"
                >
                  {{ surveyor.transactions }}
                  transaksi
                </strong>

                <span class="text-blue-700">
                  {{ surveyor.verifications }}
                  verifikasi
                </span>
              </div>
            </div>
          </div>
        </article>

        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white print:hidden"
        >
          <header
            class="flex flex-col gap-3 border-b border-slate-200 p-4 lg:flex-row lg:items-center lg:justify-between"
          >
            <div>
              <h2
                class="flex items-center gap-2 font-black text-slate-900"
              >
                <UsersRound :size="18" />
                Detail Penyelesaian KPM
              </h2>

              <p
                data-testid="report-participant-range"
                class="mt-1 text-xs text-slate-500"
              >
                Menampilkan
                {{ pageStart }}–{{ pageEnd }}
                dari
                {{ filteredParticipants.length }}
                KPM
              </p>
            </div>

            <div
              class="grid gap-2 sm:grid-cols-2"
            >
              <label class="relative">
                <Search
                  :size="16"
                  class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                />

                <input
                  v-model="search"
                  type="search"
                  placeholder="Cari NIK atau nama"
                  class="h-10 rounded-xl border border-slate-300 pl-9 pr-3 text-xs outline-none focus:border-emerald-600 focus:ring-3 focus:ring-emerald-100"
                >
              </label>

              <select
                v-model="resolution"
                class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-xs outline-none focus:border-emerald-600"
              >
                <option value="">
                  Semua Status
                </option>

                <option value="transacted">
                  Sudah Transaksi
                </option>

                <option value="pending">
                  Belum Transaksi
                </option>

                <option value="deceased">
                  Meninggal
                </option>

                <option value="moved_domicile">
                  Pindah Domisili
                </option>

                <option value="not_claimed">
                  Tidak Mengambil
                </option>
              </select>
            </div>
          </header>

          <div class="overflow-x-auto">
            <table
              class="w-full min-w-[1150px] text-left text-xs"
            >
              <thead
                class="bg-slate-50 font-black uppercase text-slate-500"
              >
                <tr>
                  <th class="px-4 py-3">
                    NIK
                  </th>

                  <th class="px-4 py-3">
                    Nama KPM
                  </th>

                  <th class="px-4 py-3">
                    Kecamatan Asal
                  </th>

                  <th class="px-4 py-3">
                    Kelurahan Asal
                  </th>

                  <th class="px-4 py-3">
                    Status
                  </th>

                  <th class="px-4 py-3">
                    Alasan
                  </th>

                  <th class="px-4 py-3">
                    Surveyor
                  </th>

                  <th class="px-4 py-3">
                    E-Warung
                  </th>
                </tr>
              </thead>

              <tbody
                class="divide-y divide-slate-100"
              >
                <tr
                  v-for="
                    participant in
                      paginatedParticipants
                  "
                  :key="
                    participant.participant_id
                  "
                  data-testid="report-participant-row"
                >
                  <td
                    class="whitespace-nowrap px-4 py-3 font-mono text-slate-600"
                  >
                    {{ participant.nik ?? '-' }}
                  </td>

                  <td
                    class="px-4 py-3 font-bold text-slate-900"
                  >
                    {{ participant.full_name }}
                  </td>

                  <td class="px-4 py-3">
                    {{
                      participant.wilayah
                        .kecamatan.name
                      ?? '-'
                    }}
                  </td>

                  <td class="px-4 py-3">
                    {{
                      participant.wilayah
                        .kelurahan.name
                      ?? '-'
                    }}
                  </td>

                  <td class="px-4 py-3">
                    <span
                      class="whitespace-nowrap rounded-full px-2.5 py-1 font-bold"
                      :class="
                        statusClass(
                          participant
                            .resolution.code,
                        )
                      "
                    >
                      {{
                        participant.resolution
                          .label
                      }}
                    </span>
                  </td>

                  <td
                    class="max-w-72 px-4 py-3 text-slate-600"
                  >
                    {{
                      participant.resolution
                        .reason
                      ?? '-'
                    }}
                  </td>

                  <td class="px-4 py-3">
                    {{
                      participant.surveyor
                        ?.name
                      ?? '-'
                    }}
                  </td>

                  <td class="px-4 py-3">
                    {{
                      participant.e_warung
                        ?.name
                      ?? '-'
                    }}
                  </td>
                </tr>

                <tr
                  v-if="
                    paginatedParticipants
                      .length === 0
                  "
                >
                  <td
                    colspan="8"
                    class="px-4 py-10 text-center text-sm text-slate-500"
                  >
                    Tidak ada KPM yang sesuai dengan
                    pencarian atau filter.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <footer
            data-testid="report-participant-pagination"
            class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between"
          >
            <p class="text-xs text-slate-500">
              Maksimal 50 KPM per halaman
            </p>

            <div
              class="flex items-center gap-2"
            >
              <button
                type="button"
                aria-label="Halaman KPM sebelumnya"
                class="grid size-9 place-items-center rounded-lg border border-slate-300 text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="currentPage <= 1"
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
                aria-label="Halaman KPM berikutnya"
                class="grid size-9 place-items-center rounded-lg border border-slate-300 text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="
                  currentPage >= lastPage
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
        </article>

        <footer
          class="hidden border-t border-slate-300 pt-4 text-xs text-slate-600 print:block"
        >
          Laporan final SIPBPNT —
          {{ report.period.name }}.
          Divalidasi oleh
          {{ report.finalized_by?.name ?? '-' }}
          pada
          {{ formatDateTime(report.finalized_at) }}
          WIB.
        </footer>
      </template>
    </template>
  </section>
</template>