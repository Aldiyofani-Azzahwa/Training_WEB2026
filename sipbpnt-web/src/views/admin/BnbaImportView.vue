<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
  nextTick,
} from 'vue'

import {
  storeToRefs,
} from 'pinia'

import {
  CheckCircle2,
  Database,
  FileSpreadsheet,
  LoaderCircle,
  ShieldCheck,
  Trash2,
  X,
} from '@lucide/vue'

import {
  RouterLink,
} from 'vue-router'

import BnbaImportSummary
  from '@/components/bnba/BnbaImportSummary.vue'

import BnbaPreviewTable
  from '@/components/bnba/BnbaPreviewTable.vue'

import BnbaUploadPanel
  from '@/components/bnba/BnbaUploadPanel.vue'

import BpntPeriodForm
  from '@/components/bnba/BpntPeriodForm.vue'

import {
  useBnbaImportStore,
} from '@/stores/bnbaImport'

import type {
  BnbaRowStatus,
  BpntPeriod,
  CreateBpntPeriodPayload,
  UpdateBpntPeriodPayload,
} from '@/types/bnba'

type PeriodFormMode =
  | 'normal'
  | 'create_period'
  | 'edit_period'

const store =
  useBnbaImportStore()

const {
  periods,

  selectedPeriodId,
  selectedPeriod,

  selectedFile,
  uploadProgress,

  currentImport,

  previewRows,
  previewMeta,
  statusFilter,
  search,

  isLoadingPeriods,
  isCreatingPeriod,

  updatingPeriodId,
  deletingPeriodId,

  isDeletingBnba,
  downloadingPeriodId,

  isUploading,
  isLoadingPreview,
  isConfirming,

  errorMessage,
  validationErrors,

  isBnbaWorking,
  showUploadPanel,
  canUpload,
  canConfirm,
} = storeToRefs(
  store,
)

const successMessage =
  ref<string | null>(
    null,
  )

const bnbaInteractionArea =
  ref<HTMLElement | null>(
    null,
  )

let successTimer:
  ReturnType<
    typeof setTimeout
  >
  | null =
    null

const periodFormKey =
  ref(0)

const periodFormMode =
  ref<PeriodFormMode>(
    'normal',
  )

const isPeriodFormBusy =
  computed(
    () =>
      periodFormMode.value
      !== 'normal',
  )

const canShowBnbaWorkspace =
  computed(() => {
    return (
      selectedPeriod.value
      !== null
      &&
      !isPeriodFormBusy.value
    )
  })

  watch(
  successMessage,
  (
    message,
  ) => {
    if (
      successTimer
      !== null
    ) {
      clearTimeout(
        successTimer,
      )

      successTimer =
        null
    }

    if (
      message === null
    ) {
      return
    }

    successTimer =
      setTimeout(
        () => {
          successMessage.value =
            null

          successTimer =
            null
        },
        3000,
      )
  },
)

let errorTimer: ReturnType<typeof setTimeout> | null = null

watch(
  errorMessage,
  (
    message,
  ) => {
    if (errorTimer !== null) {
      clearTimeout(errorTimer)
      errorTimer = null
    }

    if (
      message === null
    ) {
      return
    }

    nextTick(() => {
      const errorEl = document.getElementById('error-notification')
      if (errorEl) {
        errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    })

    errorTimer = setTimeout(
      () => {
        store.clearError()
        errorTimer = null
      },
      5000,
    )
  },
)

function handleDocumentClick(
  event: MouseEvent,
): void {
  /*
   * Tidak ada periode dipilih.
   */
  if (
    selectedPeriodId.value
    === null
  ) {
    return
  }

  /*
   * Form tambah/edit periode
   * sedang aktif.
   */
  if (
    isPeriodFormBusy.value
  ) {
    return
  }

  /*
   * BNBA sedang dikerjakan.
   * Jangan cancel selection.
   */
  if (
    isBnbaWorking.value
  ) {
    return
  }

  const target =
    event.target

  if (
    !(target instanceof Node)
  ) {
    return
  }

  /*
   * Klik masih berada di area
   * periode / workspace BNBA.
   */
  if (
    bnbaInteractionArea.value
      ?.contains(target)
  ) {
    return
  }

  store.clearPeriodSelection()

  successMessage.value =
    null
}

onMounted(
  async () => {
    document
      .addEventListener(
        'click',
        handleDocumentClick,
      )

    await store
      .fetchPeriods()
  },
)

onBeforeUnmount(
  () => {
    document
      .removeEventListener(
        'click',
        handleDocumentClick,
      )

    if (
      successTimer
      !== null
    ) {
      clearTimeout(
        successTimer,
      )
    }
  },
)

/*
|--------------------------------------------------------------------------
| Period Form Mode
|--------------------------------------------------------------------------
*/

function handlePeriodFormModeChange(
  mode: PeriodFormMode,
): void {
  periodFormMode.value =
    mode
}

/*
|--------------------------------------------------------------------------
| Period Create
|--------------------------------------------------------------------------
*/

async function handleCreatePeriod(
  payload:
    CreateBpntPeriodPayload,
): Promise<void> {
  successMessage.value =
    null

  const result =
    await store
      .createPeriod(
        payload,
      )

  if (!result) {
    return
  }

  /*
   * Setelah berhasil,
   * form kembali NORMAL.
   */
  periodFormMode.value =
    'normal'

  periodFormKey.value +=
    1

  successMessage.value =
  'Periode berhasil dibuat.'
}

/*
|--------------------------------------------------------------------------
| Period Update
|--------------------------------------------------------------------------
*/

async function handleUpdatePeriod(
  periodId: number,
  payload:
    UpdateBpntPeriodPayload,
): Promise<void> {
  successMessage.value =
    null

  const success =
    await store
      .updatePeriod(
        periodId,
        payload,
      )

  if (!success) {
    return
  }

  periodFormMode.value =
    'normal'

  periodFormKey.value +=
    1

  successMessage.value =
    'Periode berhasil diperbarui.'
}

/*
|--------------------------------------------------------------------------
| Period Delete
|--------------------------------------------------------------------------
*/

async function handleDeletePeriod(
  period: BpntPeriod,
): Promise<void> {
  if (
    isPeriodFormBusy.value
    ||
    isBnbaWorking.value
  ) {
    return
  }

  successMessage.value =
    null

  const confirmed =
    window.confirm(
      `Hapus periode "${period.name}"?`,
    )

  if (!confirmed) {
    return
  }

  const success =
    await store
      .deletePeriod(
        period.id,
      )

  if (!success) {
    return
  }

  successMessage.value =
    'Periode berhasil dihapus.'
}

/*
|--------------------------------------------------------------------------
| Period Select
|--------------------------------------------------------------------------
*/

async function handleSelectPeriod(
  periodId: number,
): Promise<void> {
  /*
   * Saat Tambah/Edit periode berlangsung,
   * card lain tidak boleh membuka workspace.
   */
  if (
    isPeriodFormBusy.value
  ) {
    return
  }

  /*
   * Store juga punya guard,
   * tetapi kita blok dari UI lebih dulu.
   */
  if (
    isBnbaWorking.value
    &&
    selectedPeriodId.value
    !== periodId
  ) {
    return
  }

  successMessage.value =
    null

  await store
    .selectPeriod(
      periodId,
    )
}

/*
|--------------------------------------------------------------------------
| File
|--------------------------------------------------------------------------
*/

function handleFileSelect(
  file: File | null,
): void {
  /*
   * File hanya boleh dipilih
   * ketika form periode NORMAL.
   */
  if (
    isPeriodFormBusy.value
  ) {
    return
  }

  successMessage.value =
    null

  store.selectFile(
    file,
  )
}

/*
|--------------------------------------------------------------------------
| Upload
|--------------------------------------------------------------------------
*/

async function handleUpload():
  Promise<void> {
  if (
    isPeriodFormBusy.value
  ) {
    return
  }

  successMessage.value =
    null

  const success =
    await store
      .uploadFile()

  if (!success) {
    return
  }

  successMessage.value =
    'File BNBA berhasil dibaca. Periksa preview sebelum konfirmasi.'
}

/*
|--------------------------------------------------------------------------
| Confirm
|--------------------------------------------------------------------------
*/

async function handleConfirm():
  Promise<void> {
  if (
    currentImport.value
    === null
    ||
    isPeriodFormBusy.value
  ) {
    return
  }

  const confirmed =
    window.confirm(
      'Konfirmasi data BNBA ini?',
    )

  if (!confirmed) {
    return
  }

  successMessage.value =
    null

  const success =
    await store
      .confirmImport()

  if (!success) {
    return
  }

  successMessage.value =
     'Data BNBA berhasil dikonfirmasi.'
}

/*
|--------------------------------------------------------------------------
| Delete BNBA
|--------------------------------------------------------------------------
*/

async function handleDeleteBnba():
  Promise<void> {
  if (
    isPeriodFormBusy.value
  ) {
    return
  }

  const period =
    selectedPeriod.value

  if (!period) {
    return
  }

  const total =
    period.participants_count

  const message =
    total > 0
      ? `Hapus seluruh BNBA dari periode "${period.name}"?\n\n${total} data KPM akan dihapus.`
      : `Hapus data BNBA dari periode "${period.name}"?`

  const confirmed =
    window.confirm(
      message,
    )

  if (!confirmed) {
    return
  }

  successMessage.value =
    null

  const success =
    await store
      .deleteBnba()

  if (!success) {
    return
  }

  successMessage.value =
    'Data BNBA berhasil dihapus. Periode dapat menerima file BNBA baru.'
}

/*
|--------------------------------------------------------------------------
| Download BNBA
|--------------------------------------------------------------------------
*/

async function handleDownloadBnba(
  periodId: number,
): Promise<void> {
  await store.downloadBnba(
    periodId,
  )
}

/*
|--------------------------------------------------------------------------
| Preview
|--------------------------------------------------------------------------
*/

async function handleFilter(
  status:
    BnbaRowStatus | null,
): Promise<void> {
  await store
    .changeStatusFilter(
      status,
    )
}

async function handleSearch(
  keyword: string,
): Promise<void> {
  await store
    .searchPreview(
      keyword,
    )
}

async function handlePage(
  page: number,
): Promise<void> {
  await store
    .changePage(
      page,
    )
}
</script>

<template>
  <main
    class="mx-auto max-w-[1500px] px-4 py-7 sm:px-6 lg:px-8"
  >
    <!-- PAGE HEADER -->
    <div
      class="mb-7"
    >
      <div
        class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-[#E8312D]/10 text-[#E8312D]"
      >
        <FileSpreadsheet
          :size="24"
          aria-hidden="true"
        />
      </div>

      <h1
        class="text-3xl font-bold text-slate-950"
      >
        Import BNBA
      </h1>

      <p
        class="mt-2 max-w-3xl text-slate-500"
      >
        Tambahkan periode, klik periode,
        kemudian kelola data BNBA.
      </p>
    </div>
    <div ref="bnbaInteractionArea">

    <!-- SUCCESS -->
    <div
      v-if="
        successMessage
      "
      class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"
    >
      <CheckCircle2
        :size="20"
        class="mt-0.5 shrink-0"
        aria-hidden="true"
      />

      <span
        class="flex-1"
      >
        {{ successMessage }}
      </span>

      <button
        type="button"
        aria-label="Tutup notifikasi"
        @click="
          successMessage = null
        "
      >
        <X
          :size="18"
        />
      </button>
    </div>

    <!-- ERROR -->
    <div
      v-if="
        errorMessage
      "
      id="error-notification"
      class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      <X
        :size="20"
        class="mt-0.5 shrink-0"
        aria-hidden="true"
      />

      <span
        class="flex-1"
      >
        {{ errorMessage }}
      </span>
    </div>

    <!-- PERIOD MANAGEMENT -->
    <BpntPeriodForm
      :key="
        periodFormKey
      "
      :periods="
        periods
      "
      :selected-period-id="
        selectedPeriodId
      "
      :is-loading="
        isLoadingPeriods
      "
      :is-creating="
        isCreatingPeriod
      "
      :updating-period-id="
        updatingPeriodId
      "
      :deleting-period-id="
        deletingPeriodId
      "
      :validation-errors="
        validationErrors
      "
      :bnba-locked="
        isBnbaWorking
      "
      :downloading-period-id="
        downloadingPeriodId
      "
      @mode-change="
        handlePeriodFormModeChange
      "
      @download="
        handleDownloadBnba
      "
      @select="
        handleSelectPeriod
      "
      @create="
        handleCreatePeriod
      "
      @update="
        handleUpdatePeriod
      "
      @delete="
        handleDeletePeriod
      "
    >
      <!-- BNBA WORKSPACE (ACCORDION) -->
      <template #default="{ period }">
        <div
          v-if="
            canShowBnbaWorkspace
          "
          ref="bnbaInteractionArea"
          class="border-t border-slate-200 pt-5 mt-2"
        >
          <!-- UPLOAD -->
          <BnbaUploadPanel
            v-if="
              showUploadPanel
            "
            :selected-file="
              selectedFile
            "
            :upload-progress="
              uploadProgress
            "
            :is-uploading="
              isUploading
            "
            :can-upload="
              canUpload
            "
            @select-file="
              handleFileSelect
            "
            @upload="
              handleUpload
            "
          />

      <!-- CONFIRMED BNBA -->
      <section
        v-if="
          selectedPeriod
            ?.bnba
            ?.status
          === 'confirmed'
          &&
          currentImport
          === null
        "
        class="mt-6 rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm"
      >
        <div
          class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
        >
          <div
            class="flex items-start gap-4"
          >
            <div
              class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"
            >
              <Database
                :size="24"
                aria-hidden="true"
              />
            </div>

            <div>
              <strong
                class="text-slate-900"
              >
                BNBA Terkonfirmasi
              </strong>

              <p
                class="mt-1 text-sm text-slate-500"
              >
                {{
                  selectedPeriod
                    ?.participants_count
                }}
                KPM
              </p>

              <p
                class="mt-1 text-xs text-slate-400"
              >
                {{
                  selectedPeriod
                    ?.bnba
                    ?.original_name
                }}
              </p>
            </div>
          </div>

          <div
            class="flex flex-wrap gap-2"
          >
            <RouterLink
              :to="{
                name:
                  'management-bnba',

                query: {
                  period:
                    selectedPeriod?.id,
                },
              }"
              class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
            >
              Lihat Data
            </RouterLink>

            <button
              type="button"
              :disabled="
                isDeletingBnba
              "
              class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-red-600 px-4 text-sm font-bold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
              @click="
                handleDeleteBnba
              "
            >
              <LoaderCircle
                v-if="
                  isDeletingBnba
                "
                :size="16"
                class="animate-spin"
              />

              <Trash2
                v-else
                :size="16"
              />

              Hapus BNBA
            </button>
          </div>
        </div>
      </section>

      <!-- LOADING EXISTING PREVIEW -->
      <section
        v-else-if="
          selectedPeriod
            ?.bnba
            ?.status
          === 'preview_ready'
          &&
          currentImport
          === null
          &&
          isLoadingPreview
        "
        class="mt-6 flex min-h-32 items-center justify-center rounded-2xl border border-slate-200 bg-white"
      >
        <LoaderCircle
          :size="24"
          class="animate-spin text-[#006855]"
        />
      </section>



      <!-- PREVIEW -->
      <div
        v-if="
          currentImport
          !== null
          &&
          currentImport.status
          === 'preview_ready'
        "
        class="mt-6 space-y-6"
      >
        <!-- PREVIEW HEADER -->
        <section
          class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <strong
              class="text-slate-900"
            >
              {{
                currentImport
                  .original_name
              }}
            </strong>

            <p
              class="mt-1 text-sm text-slate-500"
            >
              Menunggu konfirmasi
            </p>
          </div>

          <button
            type="button"
            :disabled="
              isDeletingBnba
              ||
              isConfirming
            "
            class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-bold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
            @click="
              handleDeleteBnba
            "
          >
            <LoaderCircle
              v-if="
                isDeletingBnba
              "
              :size="16"
              class="animate-spin"
            />

            <Trash2
              v-else
              :size="16"
            />

            Hapus BNBA
          </button>
        </section>

        <!-- SUMMARY -->
        <BnbaImportSummary
          :import-data="
            currentImport
          "
          :active-status="
            statusFilter
          "
          @filter="
            handleFilter
          "
        />

        <!-- TABLE -->
        <BnbaPreviewTable
          :rows="
            previewRows
          "
          :meta="
            previewMeta
          "
          :loading="
            isLoadingPreview
          "
          :search="
            search
          "
          @search="
            handleSearch
          "
          @page="
            handlePage
          "
        />

        <!-- CONFIRM -->
        <section
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
        >
          <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <strong
                class="text-slate-900"
              >
                Konfirmasi BNBA
              </strong>

              <p
                class="mt-1 text-sm leading-6 text-slate-500"
              >
                Setelah dikonfirmasi,
                baris Valid dan Warning
                menjadi data KPM periode ini.
              </p>
            </div>

            <button
              type="button"
              :disabled="
                !canConfirm
              "
              class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#006855] px-6 font-bold text-white transition hover:bg-[#005646] disabled:cursor-not-allowed disabled:opacity-50"
              @click="
                handleConfirm
              "
            >
              <LoaderCircle
                v-if="
                  isConfirming
                "
                :size="18"
                class="animate-spin"
              />

              <ShieldCheck
                v-else
                :size="18"
              />

              {{
                isConfirming
                  ? 'Mengonfirmasi...'
                  : 'Konfirmasi BNBA'
              }}
            </button>
          </div>
        </section>
      </div>
        </div>
      </template>
    </BpntPeriodForm>
  </div>
  </main>

</template>