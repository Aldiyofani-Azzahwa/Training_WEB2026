<script setup lang="ts">
import axios from 'axios'

import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import {
  ArrowRight,
  CalendarDays,
  CircleAlert,
  MapPin,
  RefreshCw,
  ScanLine,
  Store,
  Users,
} from '@lucide/vue'

import {
  RouterLink,
} from 'vue-router'

import {
  useSurveyorEWarungSelection,
} from '@/services/surveyorEWarungSelection'

import {
  surveyorWorkspaceService,
} from '@/services/surveyorWorkspaceService'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  ValidationErrorResponse,
} from '@/types/auth'

import type {
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

const authStore =
  useAuthStore()

const {
  activeEWarungs,
  selectedEWarung,
  synchronizeEWarungs,
  selectEWarung,
  clearSelectedEWarung,
} =
  useSurveyorEWarungSelection()

const context =
  ref<SurveyorWorkspaceContext | null>(
    null,
  )

const loading =
  ref(true)

const eWarungsLoading =
  ref(false)

const errorMessage =
  ref('')

const eWarungError =
  ref('')

const firstName =
  computed(() => {
    const name =
      context.value
        ?.surveyor
        .name
        .trim()
      ||
      authStore.user
        ?.name
        .trim()
      ||
      'Surveyor'

    return name
      .split(/\s+/)[0]
  })

const formattedKpmCount =
  computed(() => {
    return new Intl.NumberFormat(
      'id-ID',
    ).format(
      context.value
        ?.kpm_count
      ?? 0,
    )
  })

const isOperational =
  computed(() => {
    return Boolean(
      context.value
        ?.period
      &&
      context.value
        .assignment,
    )
  })

function resolveErrorMessage(
  error: unknown,
  fallback: string,
): string {
  if (
    axios.isAxiosError<
      ValidationErrorResponse
    >(error)
  ) {
    if (!error.response) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'
    }

    if (
      error.response.status === 401
      ||
      error.response.status === 419
    ) {
      return 'Sesi Anda sudah berakhir. Silakan masuk kembali.'
    }

    if (
      error.response.status === 403
    ) {
      return 'Akun Anda tidak memiliki akses ke halaman Surveyor.'
    }

    return (
      error.response
        .data
        .message
      ||
      fallback
    )
  }

  return fallback
}

async function loadEWarungs(
  silent = false,
): Promise<void> {
  if (
    !context.value
    ||
    !isOperational.value
  ) {
    clearSelectedEWarung()

    return
  }

  if (!silent) {
    eWarungsLoading.value =
      true
  }

  eWarungError.value =
    ''

  try {
    const eWarungs =
      await surveyorWorkspaceService
        .getActiveEWarungs()

    synchronizeEWarungs(
      context.value
        .surveyor
        .id,

      eWarungs,
    )
  } catch (
    error: unknown
  ) {
    eWarungError.value =
      resolveErrorMessage(
        error,
        'Daftar E-Warung aktif gagal dimuat.',
      )
  } finally {
    eWarungsLoading.value =
      false
  }
}

async function loadDashboard():
  Promise<void> {
  loading.value =
    true

  errorMessage.value =
    ''

  try {
    context.value =
      await surveyorWorkspaceService
        .getContext()

    if (isOperational.value) {
      await loadEWarungs()
    } else {
      clearSelectedEWarung()
    }
  } catch (
    error: unknown
  ) {
    context.value =
      null

    errorMessage.value =
      resolveErrorMessage(
        error,
        'Dashboard Surveyor gagal dimuat.',
      )
  } finally {
    loading.value =
      false
  }
}

function handleEWarungChange(
  event: Event,
): void {
  if (!context.value) {
    return
  }

  const target =
    event.target

  if (
    !(
      target
      instanceof HTMLSelectElement
    )
  ) {
    return
  }

  const eWarungId =
    Number(
      target.value,
    )

  if (
    !Number.isInteger(
      eWarungId,
    )
    ||
    eWarungId < 1
  ) {
    clearSelectedEWarung()

    return
  }

  selectEWarung(
    context.value
      .surveyor
      .id,

    eWarungId,
  )
}

function refreshWhenVisible():
  void {
  if (
    document.visibilityState
      === 'visible'
    &&
    isOperational.value
  ) {
    void loadEWarungs(
      true,
    )
  }
}

function refreshWhenFocused():
  void {
  if (isOperational.value) {
    void loadEWarungs(
      true,
    )
  }
}

onMounted(() => {
  void loadDashboard()

  document.addEventListener(
    'visibilitychange',
    refreshWhenVisible,
  )

  window.addEventListener(
    'focus',
    refreshWhenFocused,
  )
})

onBeforeUnmount(() => {
  document.removeEventListener(
    'visibilitychange',
    refreshWhenVisible,
  )

  window.removeEventListener(
    'focus',
    refreshWhenFocused,
  )
})
</script>

<template>
  <section
    class="grid w-full min-w-0 gap-[18px] lg:gap-6"
  >
    <header>
      <span
        class="text-xs font-[750] tracking-[0.08em] text-[#c12723] uppercase"
      >
        Beranda Surveyor
      </span>

      <h1
        class="mt-[5px] mb-0 text-[clamp(28px,8vw,36px)] leading-tight font-bold text-[#3f2817] lg:text-[38px]"
      >
        Halo, {{ firstName }}
      </h1>

      <p
        class="mt-0 text-[13px] leading-[1.6] text-[#837871]"
      >
        Halaman ini berisi informasi periode, wilayah tugas,
        dan tempat bertugas Anda.
      </p>
    </header>

    <div
      v-if="loading"
      class="grid gap-3"
      aria-live="polite"
      data-testid="dashboard-loading"
    >
      <div
        class="h-[23px] w-[58%] animate-pulse overflow-hidden rounded-[13px] bg-[#efeae7]"
      />

      <div
        class="h-[190px] animate-pulse overflow-hidden rounded-[22px] bg-[#efeae7]"
      />

      <span class="sr-only">
        Memuat dashboard Surveyor
      </span>
    </div>

    <article
      v-else-if="errorMessage"
      class="grid justify-items-center gap-2 rounded-[22px] border border-[#efcdca] bg-white px-5 py-7 text-center text-[#c42c28]"
      role="alert"
      data-testid="dashboard-error"
    >
      <CircleAlert
        :size="30"
        :stroke-width="1.9"
      />

      <strong
        class="text-base font-bold text-[#4b3424]"
      >
        Dashboard belum dapat dimuat
      </strong>

      <p
        class="m-0 text-[13px] leading-[1.6] text-[#837871]"
      >
        {{ errorMessage }}
      </p>

      <button
        type="button"
        class="mt-2 inline-flex min-h-[42px] items-center gap-2 rounded-[13px] border-0 bg-[#682b00] px-[15px] font-bold text-white transition-colors duration-150 hover:bg-[#572400] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#682b00]"
        data-testid="dashboard-retry"
        @click="loadDashboard"
      >
        <RefreshCw :size="18" />

        Coba Lagi
      </button>
    </article>

    <article
      v-else-if="
        context
        &&
        !context.period
      "
      class="grid justify-items-center gap-2 rounded-[22px] border border-[#ecd9b8] bg-white px-5 py-7 text-center text-[#b76500]"
      data-testid="no-active-period"
    >
      <CalendarDays :size="31" />

      <strong
        class="text-base font-bold text-[#4b3424]"
      >
        Belum ada periode aktif
      </strong>

      <p
        class="m-0 text-[13px] leading-[1.6] text-[#837871]"
      >
        Periode BPNT belum diaktifkan
        oleh Admin Dinsos.
      </p>
    </article>

    <template
      v-else-if="
        context
        &&
        context.period
      "
    >
      <article
        class="flex min-w-0 items-center gap-[13px] rounded-[22px] border border-[#f0dbb9] bg-[linear-gradient(145deg,#fffaf0,#fff3dc)] p-[18px] shadow-[0_12px_28px_rgb(30_65_55_/_6%)] lg:p-[22px]"
        data-testid="active-period"
      >
        <div
          class="grid size-[45px] shrink-0 place-items-center rounded-[15px] bg-white text-[#bd6800]"
        >
          <CalendarDays :size="23" />
        </div>

        <div
          class="flex min-w-0 flex-col"
        >
          <span
            class="text-[11px] font-[650] text-[#867c75]"
          >
            Periode BPNT Aktif
          </span>

          <strong
            class="overflow-hidden text-[17px] leading-[1.35] font-bold text-ellipsis whitespace-nowrap text-[#4b3424]"
          >
            {{ context.period.name }}
          </strong>

          <small
            class="text-[11px] text-[#8a7359]"
          >
            {{ context.period.code }}
            ·
            {{ context.period.year }}
          </small>
        </div>
      </article>

      <article
        v-if="!context.assignment"
        class="grid justify-items-center gap-2 rounded-[22px] border border-[#ecd9b8] bg-white px-5 py-7 text-center text-[#b76500]"
        data-testid="no-assignment"
      >
        <MapPin :size="31" />

        <strong
          class="text-base font-bold text-[#4b3424]"
        >
          Anda belum memiliki wilayah tugas
        </strong>

        <p
          class="m-0 text-[13px] leading-[1.6] text-[#837871]"
        >
          Hubungi Manager BPNT agar wilayah
          tugas dapat ditetapkan.
        </p>
      </article>

      <template v-else>
        <article
          class="grid min-w-0 gap-4 rounded-[22px] border border-[#e9e1dc] bg-white p-[18px] shadow-[0_12px_28px_rgb(30_65_55_/_6%)] lg:p-[22px]"
          data-testid="surveyor-assignment"
        >
          <header
            class="flex items-center justify-between gap-3"
          >
            <div
              class="flex min-w-0 flex-col"
            >
              <span
                class="text-[11px] font-[650] text-[#867c75]"
              >
                Wilayah Tugas
              </span>

              <strong
                class="overflow-hidden text-[17px] leading-[1.35] font-bold text-ellipsis whitespace-nowrap text-[#4b3424]"
              >
                {{
                  context.assignment
                    .kelurahan
                    .name
                }}
              </strong>
            </div>

            <div
              class="grid size-[45px] shrink-0 place-items-center rounded-[15px] bg-[#f5ede8] text-[#682b00]"
            >
              <MapPin :size="23" />
            </div>
          </header>

          <dl
            class="m-0 grid grid-cols-2 gap-[10px] max-[380px]:grid-cols-1"
          >
            <div
              class="rounded-[14px] bg-[#faf8f7] p-3"
            >
              <dt
                class="text-[10px] text-[#8d837b]"
              >
                Kecamatan
              </dt>

              <dd
                class="mt-[3px] mb-0 text-[13px] font-[720] text-[#4e3828]"
              >
                {{
                  context.assignment
                    .kecamatan
                    .name
                }}
              </dd>
            </div>

            <div
              class="rounded-[14px] bg-[#faf8f7] p-3"
            >
              <dt
                class="text-[10px] text-[#8d837b]"
              >
                Kelurahan
              </dt>

              <dd
                class="mt-[3px] mb-0 text-[13px] font-[720] text-[#4e3828]"
              >
                {{
                  context.assignment
                    .kelurahan
                    .name
                }}
              </dd>
            </div>
          </dl>

          <div
            class="flex items-center gap-[9px] border-t border-[#eeeae7] pt-[14px] text-[#c12723]"
          >
            <Users :size="22" />

            <span
              class="text-[15px] font-[750] text-[#4b3424]"
            >
              {{ formattedKpmCount }} KPM
            </span>
          </div>
        </article>

        <article
          class="grid w-full min-w-0 gap-[13px] overflow-hidden rounded-[22px] border border-[#e4d7cd] bg-white p-[18px] shadow-[0_12px_28px_rgb(30_65_55_/_6%)] lg:p-[22px]"
          data-testid="e-warung-card"
        >
          <header
            class="flex min-w-0 items-center justify-start gap-3"
          >
            <div
              class="grid size-[45px] shrink-0 place-items-center rounded-[15px] bg-[#f5ede8] text-[#682b00]"
            >
              <Store :size="23" />
            </div>

            <div
              class="flex min-w-0 flex-1 flex-col overflow-hidden"
            >
              <span
                class="text-[11px] font-[650] text-[#867c75]"
              >
                Tempat Bertugas Saat Ini
              </span>

              <strong
                class="block max-w-full overflow-hidden text-[17px] leading-[1.35] font-bold text-ellipsis whitespace-nowrap text-[#4b3424]"
                :title="
                  selectedEWarung
                    ?.name
                  ??
                  'Belum memilih E-Warung'
                "
              >
                {{
                  selectedEWarung
                    ?.name
                  ??
                  'Belum memilih E-Warung'
                }}
              </strong>
            </div>
          </header>

          <label
            for="surveyor-e-warung"
            class="text-xs font-bold text-[#594435]"
          >
            Pilih E-Warung aktif
          </label>

          <div
            class="w-full min-w-0 max-w-full overflow-hidden"
          >
            <select
              id="surveyor-e-warung"
              class="block min-h-[50px] w-full min-w-0 max-w-full appearance-none truncate rounded-[14px] border border-[#ded5cf] bg-[#fdfcfb] px-[13px] pr-10 text-base font-[650] text-[#4b3424] outline-none transition-[border-color,box-shadow] duration-150 focus:border-[#8d6243] focus:shadow-[0_0_0_3px_rgb(0_104_85_/_9%)] disabled:cursor-not-allowed disabled:bg-[#f5f3f1] disabled:text-[#958d87] lg:min-h-[52px] lg:text-sm"
              :value="
                selectedEWarung
                  ?.id
                ?? ''
              "
              :disabled="
                eWarungsLoading
                ||
                activeEWarungs.length === 0
              "
              data-testid="e-warung-select"
              @change="handleEWarungChange"
            >
              <option value="">
                {{
                  eWarungsLoading
                    ? 'Memuat E-Warung...'
                    : 'Pilih E-Warung'
                }}
              </option>

              <option
                v-for="eWarung in activeEWarungs"
                :key="eWarung.id"
                :value="eWarung.id"
              >
                {{ eWarung.name }}
              </option>
            </select>
          </div>

          <p
            v-if="eWarungError"
            class="m-0 text-[11px] leading-[1.5] text-[#c42c28]"
            role="alert"
          >
            {{ eWarungError }}
          </p>

          <p
            v-else-if="
              !eWarungsLoading
              &&
              activeEWarungs.length === 0
            "
            class="m-0 text-[11px] leading-[1.5] text-[#a75a00]"
          >
            Belum ada E-Warung aktif.
            Hubungi Admin Dinsos.
          </p>

          <p
            v-else
            class="m-0 text-[11px] leading-[1.5] text-[#837871]"
          >
            Pilihan ini otomatis dipakai untuk
            transaksi berikutnya. E-Warung
            nonaktif tidak ditampilkan.
          </p>
        </article>

        <RouterLink
          :to="{
            name: 'surveyor-scan-ktp',
          }"
          class="flex min-w-0 items-center gap-[13px] rounded-[22px] bg-[linear-gradient(135deg,#682b00,#833700)] p-[17px] text-white no-underline shadow-[0_15px_28px_rgb(0_104_85_/_20%)] transition-[transform,box-shadow] duration-150 hover:-translate-y-0.5 hover:shadow-[0_18px_34px_rgb(0_104_85_/_24%)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#682b00] lg:px-[22px] lg:py-5"
          data-testid="scan-ktp-action"
        >
          <span
            class="grid size-[52px] shrink-0 place-items-center rounded-[17px] bg-[rgb(255_255_255_/_14%)]"
          >
            <ScanLine :size="28" />
          </span>

          <span
            class="flex min-w-0 flex-1 flex-col"
          >
            <strong class="text-base">
              Scan KTP
            </strong>

            <small
              class="overflow-hidden text-[11px] text-ellipsis whitespace-nowrap text-[rgb(255_255_255_/_76%)]"
            >
              {{
                selectedEWarung
                  ? `Transaksi di ${selectedEWarung.name}`
                  : 'Cari KPM; pilih E-Warung sebelum transaksi'
              }}
            </small>
          </span>

          <ArrowRight
            :size="21"
            class="shrink-0"
          />
        </RouterLink>
      </template>
    </template>
  </section>
</template>