<script setup lang="ts">
import axios from 'axios'

import {
  computed,
  onMounted,
  ref,
} from 'vue'

import {
  CheckCircle2,
  CircleAlert,
  ClipboardList,
  Download,
  FileText,
  LoaderCircle,
  MapPin,
  Plus,
  RefreshCw,
  Store,
  Trash2,
  Users,
  WalletCards,
} from '@lucide/vue'

import {
  surveyorMonitoringReportService,
} from '@/services/surveyorMonitoringReportService'

import type {
  ValidationErrorResponse,
} from '@/types/auth'

import type {
  SurveyorMonitoringReport,
  UpdateSurveyorMonitoringReportPayload,
} from '@/types/surveyorMonitoringReport'

const MAX_COMMODITIES =
  5

const report =
  ref<SurveyorMonitoringReport | null>(
    null,
  )

const commodities =
  ref<string[]>([])

const socialOfficerName =
  ref('')

const distributionAssistantName =
  ref('')

const loading =
  ref(true)

const downloading =
  ref(false)

const errorMessage =
  ref('')

const successMessage =
  ref('')

const canAddCommodity =
  computed(() => {
    return (
      commodities.value.length
      <
      MAX_COMMODITIES
    )
  })

const formIsValid =
  computed(() => {
    const normalized =
      commodities.value
        .map(
          (commodity) =>
            commodity.trim(),
        )
        .filter(Boolean)

    const unique =
      new Set(
        normalized.map(
          (commodity) =>
            commodity.toLocaleLowerCase(
              'id-ID',
            ),
        ),
      )

    return (
      normalized.length > 0
      &&
      normalized.length
      === commodities.value.length
      &&
      unique.size
      === normalized.length
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

    const validationMessage =
      Object.values(
        error.response.data.errors
        ?? {},
      )[0]?.[0]

    return (
      validationMessage
      ?? error.response.data.message
      ?? fallback
    )
  }

  return fallback
}

function synchronizeForm(
  value: SurveyorMonitoringReport,
): void {
  report.value =
    value

  commodities.value =
    [...value.editable.commodities]

  socialOfficerName.value =
    value.editable.social_officer_name
    ?? ''

  distributionAssistantName.value =
    value.editable.distribution_assistant_name
    ?? (
      value.id === null
        ? value.surveyor.name
        : ''
    )
}

async function loadReport():
  Promise<void> {
  loading.value =
    true

  errorMessage.value =
    ''

  successMessage.value =
    ''

  try {
    synchronizeForm(
      await surveyorMonitoringReportService
        .getReport(),
    )
  } catch (
    error: unknown
  ) {
    report.value =
      null

    errorMessage.value =
      resolveErrorMessage(
        error,
        'Laporan monitoring gagal dimuat.',
      )
  } finally {
    loading.value =
      false
  }
}

function addCommodity():
  void {
  if (!canAddCommodity.value) {
    return
  }

  commodities.value.push('')
}

function removeCommodity(
  index: number,
): void {
  commodities.value.splice(
    index,
    1,
  )
}

function payload():
  UpdateSurveyorMonitoringReportPayload {
  return {
    commodities:
      commodities.value.map(
        (commodity) =>
          commodity.trim(),
      ),

    social_officer_name:
      socialOfficerName.value.trim()
      || null,

    distribution_assistant_name:
      distributionAssistantName.value.trim()
      || null,
  }
}

async function persistReport():
  Promise<SurveyorMonitoringReport> {
  if (!formIsValid.value) {
    throw new Error(
      'Isi minimal satu komoditas dan pastikan tidak ada nama yang kosong atau sama.',
    )
  }

  const updated =
    await surveyorMonitoringReportService
      .updateReport(
        payload(),
      )

  synchronizeForm(
    updated,
  )

  return updated
}

async function handleDownload():
  Promise<void> {
  downloading.value =
    true

  errorMessage.value =
    ''

  successMessage.value =
    ''

  try {
    await persistReport()

    const pdf =
      await surveyorMonitoringReportService
        .downloadPdf()

    const url =
      URL.createObjectURL(
        pdf.blob,
      )

    const link =
      document.createElement(
        'a',
      )

    link.href =
      url

    link.download =
      pdf.filename

    document.body.appendChild(
      link,
    )

    link.click()
    link.remove()

    URL.revokeObjectURL(
      url,
    )

    
  
  } catch (
    error: unknown
  ) {
    errorMessage.value =
      error instanceof Error
      && !axios.isAxiosError(error)
        ? error.message
        : resolveErrorMessage(
            error,
            'PDF laporan gagal dibuat.',
          )
  } finally {
    downloading.value =
      false
  }
}

function formatNumber(
  value: number,
): string {
  return new Intl.NumberFormat(
    'id-ID',
  ).format(
    value,
  )
}

function formatCurrency(
  value: number,
): string {
  return new Intl.NumberFormat(
    'id-ID',
    {
      style:
        'currency',

      currency:
        'IDR',

      maximumFractionDigits:
        0,
    },
  ).format(
    value,
  )
}

onMounted(() => {
  void loadReport()
})
</script>

<template>
  <section
    class="mx-auto w-full max-w-[1080px] space-y-5"
  >
    <header
      class="rounded-[24px] border border-[#dce8e4] bg-white p-5 shadow-[0_14px_35px_rgb(24_59_53_/_7%)] sm:p-6"
    >
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
      >
        <div>
          <div
            class="mb-3 grid size-11 place-items-center rounded-[15px] bg-[#e4f3ee] text-[#006855]"
          >
            <FileText
              :size="23"
              :stroke-width="2"
            />
          </div>

          <p
            class="text-xs font-bold tracking-[0.08em] text-[#6c817a] uppercase"
          >
            Laporan Kelurahan
          </p>

          <h1
            class="mt-1 text-[23px] leading-tight font-bold text-[#173f37] sm:text-[27px]"
          >
            Monitoring dan Evaluasi BPNT
          </h1>

          <p
            class="mt-2 max-w-2xl text-sm leading-6 text-[#61756e]"
          >
            Data KPM, nominal, status, E-Warung, dan evaluasi dihitung otomatis dari periode aktif.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[14px] border border-[#d9e6e1] bg-white px-4 text-sm font-bold text-[#365c53] transition hover:bg-[#f2f8f6] disabled:cursor-not-allowed disabled:opacity-55"
          :disabled="loading || downloading"
          @click="loadReport"
        >
          <RefreshCw
            :size="17"
            :class="{
              'animate-spin':
                loading,
            }"
          />
          Muat Ulang
        </button>
      </div>
    </header>

    <div
      v-if="loading"
      class="grid min-h-56 place-items-center rounded-[22px] border border-[#dce8e4] bg-white"
    >
      <div
        class="flex flex-col items-center gap-3 text-[#61756e]"
      >
        <LoaderCircle
          class="animate-spin text-[#006855]"
          :size="30"
        />

        <span class="text-sm font-semibold">
          Memuat laporan monitoring...
        </span>
      </div>
    </div>

    <div
      v-else-if="!report"
      class="rounded-[22px] border border-[#f0c7c4] bg-[#fff7f6] p-5 text-[#9f2925]"
    >
      <div class="flex items-start gap-3">
        <CircleAlert
          class="mt-0.5 shrink-0"
          :size="21"
        />

        <div>
          <strong class="text-sm">
            Laporan belum dapat dibuka
          </strong>

          <p class="mt-1 text-sm leading-6">
            {{ errorMessage }}
          </p>
        </div>
      </div>
    </div>

    <template v-else>
      <div
        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
      >
        <article
          class="rounded-[20px] border border-[#dce8e4] bg-white p-4"
        >
          <MapPin
            :size="20"
            class="text-[#006855]"
          />

          <p
            class="mt-3 text-xs font-semibold text-[#7a8c86]"
          >
            Kelurahan tugas
          </p>

          <strong
            class="mt-1 block text-base text-[#173f37]"
          >
            {{ report.assignment.kelurahan.name }}
          </strong>
        </article>

        <article
          class="rounded-[20px] border border-[#dce8e4] bg-white p-4"
        >
          <ClipboardList
            :size="20"
            class="text-[#006855]"
          />

          <p
            class="mt-3 text-xs font-semibold text-[#7a8c86]"
          >
            Periode aktif
          </p>

          <strong
            class="mt-1 block text-base text-[#173f37]"
          >
            {{ report.period.allocation_label }}
          </strong>
        </article>

        <article
          class="rounded-[20px] border border-[#dce8e4] bg-white p-4"
        >
          <Users
            :size="20"
            class="text-[#006855]"
          />

          <p
            class="mt-3 text-xs font-semibold text-[#7a8c86]"
          >
            Jumlah KPM
          </p>

          <strong
            class="mt-1 block text-base text-[#173f37]"
          >
            {{ formatNumber(report.summary.total_kpm) }} KPM
          </strong>
        </article>

        <article
          class="rounded-[20px] border border-[#dce8e4] bg-white p-4"
        >
          <WalletCards
            :size="20"
            class="text-[#006855]"
          />

          <p
            class="mt-3 text-xs font-semibold text-[#7a8c86]"
          >
            Total saldo BNBA
          </p>

          <strong
            class="mt-1 block text-base text-[#173f37]"
          >
            {{ formatCurrency(report.summary.total_balance) }}
          </strong>
        </article>
      </div>

      <div
        v-if="errorMessage"
        class="flex items-start gap-3 rounded-[18px] border border-[#f0c7c4] bg-[#fff7f6] p-4 text-sm text-[#9f2925]"
      >
        <CircleAlert
          class="mt-0.5 shrink-0"
          :size="19"
        />

        <span>{{ errorMessage }}</span>
      </div>

      <div
        v-if="successMessage"
        class="flex items-start gap-3 rounded-[18px] border border-[#bfe2d5] bg-[#f2fbf7] p-4 text-sm text-[#176448]"
      >
        <CheckCircle2
          class="mt-0.5 shrink-0"
          :size="19"
        />

        <span>{{ successMessage }}</span>
      </div>

      <section
        class="rounded-[22px] border border-[#dce8e4] bg-white p-5 shadow-[0_12px_30px_rgb(24_59_53_/_5%)] sm:p-6"
      >
        <div
          class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <h2
              class="text-lg font-bold text-[#173f37]"
            >
              Komoditas
            </h2>

            <p
              class="mt-1 text-sm text-[#71847e]"
            >
              Isi maksimal lima jenis komoditas yang disalurkan.
            </p>
          </div>

          <button
            type="button"
            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-[13px] border border-[#cfe0da] bg-[#f5faf8] px-4 text-sm font-bold text-[#006855] disabled:cursor-not-allowed disabled:opacity-45"
            :disabled="!canAddCommodity"
            @click="addCommodity"
          >
            <Plus :size="17" />
            Tambah Komoditas
          </button>
        </div>

        <div
          v-if="commodities.length === 0"
          class="mt-5 rounded-[16px] border border-dashed border-[#cbdcd6] bg-[#f8fbfa] p-5 text-center text-sm text-[#71847e]"
        >
          Belum ada komoditas. Tekan “Tambah Komoditas” untuk mulai mengisi.
        </div>

        <div
          v-else
          class="mt-5 space-y-3"
        >
          <div
            v-for="(_, index) in commodities"
            :key="index"
            class="flex items-center gap-3"
          >
            <span
              class="grid size-9 shrink-0 place-items-center rounded-[12px] bg-[#edf5f2] text-sm font-bold text-[#006855]"
            >
              {{ index + 1 }}
            </span>

            <input
              v-model="commodities[index]"
              data-test="commodity-input"
              type="text"
              maxlength="100"
              class="min-h-11 min-w-0 flex-1 rounded-[13px] border border-[#cfddd8] bg-white px-4 text-sm text-[#254a42] outline-none transition focus:border-[#00846d] focus:ring-3 focus:ring-[#00846d]/10"
              :placeholder="`Nama komoditas ${index + 1}`"
            >

            <button
              type="button"
              class="grid size-11 shrink-0 place-items-center rounded-[13px] border border-[#efcfcc] bg-[#fff8f7] text-[#c33b36] transition hover:bg-[#fff0ee]"
              :aria-label="`Hapus komoditas ${index + 1}`"
              @click="removeCommodity(index)"
            >
              <Trash2 :size="18" />
            </button>
          </div>
        </div>
      </section>

      <section
        class="rounded-[22px] border border-[#dce8e4] bg-white p-5 shadow-[0_12px_30px_rgb(24_59_53_/_5%)] sm:p-6"
      >
        <h2
          class="text-lg font-bold text-[#173f37]"
        >
          Nama Pelapor
        </h2>

        <p
          class="mt-1 text-sm leading-6 text-[#71847e]"
        >
          Nama dapat dikoreksi setiap saat. Kolom tanda tangan tetap kosong pada PDF.
        </p>

        <div
          class="mt-5 grid gap-5 md:grid-cols-2"
        >
          <label class="block">
            <span
              class="text-sm font-bold text-[#365c53]"
            >
              Kasi Sosial & Pemberdayaan Masyarakat
            </span>

            <input
              v-model="socialOfficerName"
              data-test="social-officer-name"
              type="text"
              maxlength="150"
              class="mt-2 min-h-11 w-full rounded-[13px] border border-[#cfddd8] bg-white px-4 text-sm text-[#254a42] outline-none transition focus:border-[#00846d] focus:ring-3 focus:ring-[#00846d]/10"
              placeholder="Isi nama pelapor"
            >
          </label>

          <label class="block">
            <span
              class="text-sm font-bold text-[#365c53]"
            >
              Pendamping Penyaluran BPNT
            </span>

            <input
              v-model="distributionAssistantName"
              data-test="distribution-assistant-name"
              type="text"
              maxlength="150"
              class="mt-2 min-h-11 w-full rounded-[13px] border border-[#cfddd8] bg-white px-4 text-sm text-[#254a42] outline-none transition focus:border-[#00846d] focus:ring-3 focus:ring-[#00846d]/10"
              placeholder="Nama Surveyor"
            >
          </label>
        </div>
      </section>

      <section
        class="rounded-[22px] border border-[#dce8e4] bg-white p-5 sm:p-6"
      >
        <div class="flex items-center gap-3">
          <Store
            :size="21"
            class="text-[#006855]"
          />

          <h2
            class="text-lg font-bold text-[#173f37]"
          >
            Ringkasan Otomatis
          </h2>
        </div>

        <dl
          class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        >
          <div
            class="rounded-[15px] bg-[#f3f8f6] p-4"
          >
            <dt
              class="text-xs font-semibold text-[#71847e]"
            >
              Mengambil
            </dt>

            <dd
              class="mt-1 text-lg font-bold text-[#176448]"
            >
              {{ formatNumber(report.summary.taking) }}
            </dd>
          </div>

          <div
            class="rounded-[15px] bg-[#fff7ef] p-4"
          >
            <dt
              class="text-xs font-semibold text-[#8a6d4f]"
            >
              Tidak Mengambil
            </dt>

            <dd
              class="mt-1 text-lg font-bold text-[#9b5a1a]"
            >
              {{ formatNumber(report.summary.not_taking) }}
            </dd>
          </div>

          <div
            class="rounded-[15px] bg-[#f4f5f8] p-4"
          >
            <dt
              class="text-xs font-semibold text-[#707887]"
            >
              Meninggal/Pindah
            </dt>

            <dd
              class="mt-1 text-lg font-bold text-[#505b70]"
            >
              {{
                formatNumber(
                  report.summary.deceased
                  +
                  report.summary.moved_domicile,
                )
              }}
            </dd>
          </div>

          <div
            class="rounded-[15px] bg-[#fff6f5] p-4"
          >
            <dt
              class="text-xs font-semibold text-[#96716e]"
            >
              Belum Mengambil
            </dt>

            <dd
              class="mt-1 text-lg font-bold text-[#a33c36]"
            >
              {{ formatNumber(report.summary.pending) }}
            </dd>
          </div>
        </dl>

        <div
          class="mt-4 rounded-[16px] bg-[#f8fbfa] p-4"
        >
          <p
            class="text-xs font-bold tracking-[0.04em] text-[#71847e] uppercase"
          >
            E-Warung
          </p>

          <p
            class="mt-2 text-sm leading-6 text-[#365c53]"
          >
            {{
              report.summary.e_warungs.join(', ')
              ||
              'Belum ada transaksi E-Warung.'
            }}
          </p>
        </div>

        <div
          class="mt-3 rounded-[16px] bg-[#f8fbfa] p-4"
        >
          <p
            class="text-xs font-bold tracking-[0.04em] text-[#71847e] uppercase"
          >
            Evaluasi otomatis
          </p>

          <p
            class="mt-2 text-sm leading-6 text-[#365c53]"
          >
            {{ report.summary.evaluation }}
          </p>
        </div>
      </section>

      <div class="flex justify-end pb-5">
  <button
    type="button"
    data-test="download-report"
    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-[14px] bg-[#006855] px-5 text-sm font-bold text-white shadow-[0_10px_22px_rgb(0_104_85_/_22%)] transition hover:bg-[#005746] disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
    :disabled="downloading || !formIsValid"
    @click="handleDownload"
  >
    <LoaderCircle
      v-if="downloading"
      class="animate-spin"
      :size="18"
    />

    <Download
      v-else
      :size="18"
    />

    {{
      downloading
        ? 'Membuat PDF...'
        : 'Unduh PDF'
    }}
  </button>
</div>
    </template>
  </section>
</template>