<script setup lang="ts">
import {
  onMounted,
  ref,
} from 'vue'

import {
  storeToRefs,
} from 'pinia'

import {
  ArrowLeft,
  CheckCircle2,
  FileSpreadsheet,
  LoaderCircle,
  RotateCcw,
  ShieldCheck,
  X,
} from 'lucide-vue-next'

import { RouterLink } from 'vue-router'

import BpntPeriodForm
  from '@/components/bnba/BpntPeriodForm.vue'

import BnbaUploadPanel
  from '@/components/bnba/BnbaUploadPanel.vue'

import BnbaImportSummary
  from '@/components/bnba/BnbaImportSummary.vue'

import BnbaPreviewTable
  from '@/components/bnba/BnbaPreviewTable.vue'

import {
  useBnbaImportStore,
} from '@/stores/bnbaImport'

import type {
  BnbaRowStatus,
  CreateBpntPeriodPayload,
} from '@/types/bnba'

const store =
  useBnbaImportStore()

const {
  activePeriods,
  selectedPeriodId,
  selectedFile,
  uploadProgress,

  currentImport,

  previewRows,
  previewMeta,
  statusFilter,
  search,

  isLoadingPeriods,
  isCreatingPeriod,
  isUploading,
  isLoadingPreview,
  isConfirming,

  errorMessage,
  validationErrors,

  canUpload,
  canConfirm,
} = storeToRefs(store)

const showConfirmDialog =
  ref(false)

const successMessage =
  ref<string | null>(null)

const periodFormKey =
  ref(0)

onMounted(async () => {
  await store.fetchPeriods()
})

async function handleCreatePeriod(
  payload: CreateBpntPeriodPayload,
): Promise<void> {
  successMessage.value = null

  const period =
    await store.createPeriod(
      payload,
    )

  if (!period) {
    return
  }

  successMessage.value =
    'Periode BPNT berhasil dibuat.'

  periodFormKey.value += 1
}

function handlePeriodSelect(
  periodId: number | null,
): void {
  successMessage.value = null

  store.selectPeriod(
    periodId,
  )
}

function handleFileSelect(
  file: File | null,
): void {
  successMessage.value = null

  store.selectFile(file)
}

async function handleUpload():
  Promise<void> {
  successMessage.value = null

  const success =
    await store.uploadFile()

  if (!success) {
    return
  }

  successMessage.value =
    'File BNBA berhasil diproses. Periksa hasil validasi sebelum konfirmasi.'
}

async function handleFilter(
  status:
    BnbaRowStatus
    | null,
): Promise<void> {
  await store.changeStatusFilter(
    status,
  )
}

async function handleSearch(
  keyword: string,
): Promise<void> {
  await store.searchPreview(
    keyword,
  )
}

async function handlePage(
  page: number,
): Promise<void> {
  await store.changePage(page)
}

async function confirmImport():
  Promise<void> {
  successMessage.value = null

  const success =
    await store.confirmImport()

  if (!success) {
    return
  }

  showConfirmDialog.value =
    false

  successMessage.value =
    'Import BNBA berhasil dikonfirmasi dan data valid telah disimpan.'
}

function startNewImport(): void {
  successMessage.value = null
  showConfirmDialog.value = false

  store.resetImport()
}
</script>

<template>
  <div
    class="min-h-screen bg-slate-50"
  >
    <!-- Header internal -->
    <header
      class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur"
    >
      <div
        class="mx-auto flex min-h-16 max-w-[1500px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
      >
        <div
          class="flex min-w-0 items-center gap-3"
        >
          <RouterLink
            to="/dashboard"
            class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
            aria-label="Kembali ke dashboard"
          >
            <ArrowLeft
              :size="20"
              aria-hidden="true"
            />
          </RouterLink>

          <div class="min-w-0">
            <p
              class="truncate text-xs font-semibold uppercase tracking-[0.15em] text-[#E8312D]"
            >
              Admin Dinas Sosial
            </p>

            <p
              class="truncate font-bold text-slate-900"
            >
              Import BNBA
            </p>
          </div>
        </div>

        <div
          class="hidden items-center gap-2 rounded-xl bg-[#006855]/10 px-3 py-2 text-sm font-semibold text-[#006855] sm:flex"
        >
          <ShieldCheck
            :size="17"
            aria-hidden="true"
          />

          Data Sensitif Dilindungi
        </div>
      </div>
    </header>

    <main
      class="mx-auto max-w-[1500px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8"
    >
      <!-- Page heading -->
      <div
        class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
      >
        <div>
          <div
            class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-[#E8312D]/10 text-[#E8312D]"
          >
            <FileSpreadsheet
              :size="25"
              aria-hidden="true"
            />
          </div>

          <h1
            class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl"
          >
            Import Data BNBA
          </h1>

          <p
            class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 sm:text-base"
          >
            Upload data BNBA, periksa hasil validasi,
            lalu konfirmasi data yang layak untuk
            disimpan ke periode BPNT.
          </p>
        </div>

        <button
          v-if="currentImport"
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
          @click="startNewImport"
        >
          <RotateCcw
            :size="18"
            aria-hidden="true"
          />

          Import Baru
        </button>
      </div>

      <!-- Success -->
      <div
        v-if="successMessage"
        class="mb-5 flex items-start gap-3 rounded-2xl border border-[#006855]/20 bg-[#006855]/5 p-4 text-sm text-[#005647]"
      >
        <CheckCircle2
          :size="20"
          class="mt-0.5 shrink-0"
          aria-hidden="true"
        />

        <p class="flex-1 font-medium">
          {{ successMessage }}
        </p>

        <button
          type="button"
          class="shrink-0"
          aria-label="Tutup notifikasi"
          @click="successMessage = null"
        >
          <X
            :size="18"
            aria-hidden="true"
          />
        </button>
      </div>

      <!-- Error -->
      <div
        v-if="errorMessage"
        class="mb-5 flex items-start gap-3 rounded-2xl border border-[#E8312D]/20 bg-[#E8312D]/5 p-4 text-sm text-[#b82320]"
      >
        <X
          :size="20"
          class="mt-0.5 shrink-0"
          aria-hidden="true"
        />

        <p class="flex-1 font-medium">
          {{ errorMessage }}
        </p>

        <button
          type="button"
          class="shrink-0"
          aria-label="Tutup pesan error"
          @click="store.clearError()"
        >
          <X
            :size="18"
            aria-hidden="true"
          />
        </button>
      </div>

      <!-- Before upload -->
      <div
        v-if="!currentImport"
        class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(380px,0.7fr)]"
      >
        <BpntPeriodForm
          :key="periodFormKey"
          :periods="activePeriods"
          :selected-period-id="selectedPeriodId"
          :is-loading="isLoadingPeriods"
          :is-creating="isCreatingPeriod"
          :validation-errors="validationErrors"
          @select="handlePeriodSelect"
          @create="handleCreatePeriod"
        />

        <BnbaUploadPanel
          :selected-file="selectedFile"
          :upload-progress="uploadProgress"
          :is-uploading="isUploading"
          :can-upload="canUpload"
          :has-selected-period="
            selectedPeriodId !== null
          "
          @select-file="handleFileSelect"
          @upload="handleUpload"
        />
      </div>

      <!-- After upload -->
      <div
        v-else
        class="space-y-6"
      >
        <div
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
        >
          <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
          >
            <div>
              <p
                class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400"
              >
                Import #{{ currentImport.id }}
              </p>

              <p
                class="mt-1 font-bold text-slate-900"
              >
                {{ currentImport.original_name }}
              </p>

              <p
                v-if="currentImport.period"
                class="mt-1 text-sm text-slate-500"
              >
                {{ currentImport.period.name }}
                — {{ currentImport.period.year }}
              </p>
            </div>

            <div
              class="flex flex-wrap items-center gap-3"
            >
              <span
                v-if="
                  currentImport.status
                  === 'preview_ready'
                "
                class="rounded-full bg-[#FFAF1C]/20 px-3 py-1.5 text-xs font-bold text-amber-800"
              >
                Menunggu Konfirmasi
              </span>

              <span
                v-else-if="
                  currentImport.status
                  === 'confirmed'
                "
                class="rounded-full bg-[#006855]/10 px-3 py-1.5 text-xs font-bold text-[#006855]"
              >
                Sudah Dikonfirmasi
              </span>
            </div>
          </div>
        </div>

        <BnbaImportSummary
          :import-data="currentImport"
          :active-status="statusFilter"
          @filter="handleFilter"
        />

        <BnbaPreviewTable
          :rows="previewRows"
          :meta="previewMeta"
          :loading="isLoadingPreview"
          :search="search"
          @search="handleSearch"
          @page="handlePage"
        />

        <div
          v-if="
            currentImport.status
            === 'preview_ready'
          "
          class="sticky bottom-4 z-20"
        >
          <div
            class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <p
                class="font-bold text-slate-900"
              >
                Konfirmasi Import BNBA
              </p>

              <p
                class="mt-1 text-sm text-slate-500"
              >
                Hanya data berstatus Valid dan Warning
                yang akan dimasukkan ke data utama.
              </p>
            </div>

            <button
              type="button"
              :disabled="!canConfirm"
              class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-[#006855] px-6 text-sm font-bold text-white transition hover:bg-[#005646] focus:outline-none focus:ring-4 focus:ring-[#006855]/20 disabled:cursor-not-allowed disabled:opacity-50"
              @click="
                showConfirmDialog = true
              "
            >
              <ShieldCheck
                :size="19"
                aria-hidden="true"
              />

              Konfirmasi Import
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- Confirm Dialog -->
    <Teleport to="body">
      <div
        v-if="showConfirmDialog && currentImport"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-import-title"
        @click.self="
          showConfirmDialog = false
        "
      >
        <div
          class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl"
        >
          <div
            class="mb-5 flex items-start justify-between gap-4"
          >
            <div
              class="flex size-12 items-center justify-center rounded-2xl bg-[#006855]/10 text-[#006855]"
            >
              <ShieldCheck
                :size="24"
                aria-hidden="true"
              />
            </div>

            <button
              type="button"
              class="flex size-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100"
              aria-label="Tutup dialog"
              :disabled="isConfirming"
              @click="
                showConfirmDialog = false
              "
            >
              <X
                :size="20"
                aria-hidden="true"
              />
            </button>
          </div>

          <h2
            id="confirm-import-title"
            class="text-xl font-bold text-slate-950"
          >
            Konfirmasi import BNBA?
          </h2>

          <p
            class="mt-2 text-sm leading-6 text-slate-500"
          >
            Setelah dikonfirmasi, data Valid dan Warning
            akan dimasukkan ke kepesertaan BPNT pada
            periode yang dipilih.
          </p>

          <div
            class="mt-5 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-4"
          >
            <div>
              <p
                class="text-xs font-semibold text-slate-400"
              >
                Akan diimport
              </p>

              <p
                class="mt-1 text-xl font-bold text-[#006855]"
              >
                {{
                  currentImport.summary.valid
                  +
                  currentImport.summary.warning
                }}
              </p>
            </div>

            <div>
              <p
                class="text-xs font-semibold text-slate-400"
              >
                Tidak diimport
              </p>

              <p
                class="mt-1 text-xl font-bold text-[#E8312D]"
              >
                {{
                  currentImport.summary.invalid
                  +
                  currentImport.summary.duplicate
                }}
              </p>
            </div>
          </div>

          <div
            class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
          >
            <button
              type="button"
              :disabled="isConfirming"
              class="min-h-11 rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
              @click="
                showConfirmDialog = false
              "
            >
              Batal
            </button>

            <button
              type="button"
              :disabled="isConfirming"
              class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#006855] px-5 text-sm font-bold text-white transition hover:bg-[#005646] disabled:cursor-not-allowed disabled:opacity-50"
              @click="confirmImport"
            >
              <LoaderCircle
                v-if="isConfirming"
                :size="18"
                class="animate-spin"
                aria-hidden="true"
              />

              <ShieldCheck
                v-else
                :size="18"
                aria-hidden="true"
              />

              {{
                isConfirming
                  ? 'Mengonfirmasi...'
                  : 'Ya, Konfirmasi'
              }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>