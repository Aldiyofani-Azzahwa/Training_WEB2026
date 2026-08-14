import axios from 'axios'
import {
  computed,
  ref,
} from 'vue'
import { defineStore } from 'pinia'

import { bnbaService } from '@/services/bnbaService'

import type {
  BnbaImport,
  BnbaImportRow,
  BnbaRowStatus,
  BpntPeriod,
  CreateBpntPeriodPayload,
  LaravelErrorResponse,
  LaravelValidationErrors,
  PaginationMeta,
} from '@/types/bnba'

const DEFAULT_PER_PAGE = 50

const emptyPagination = (): PaginationMeta => ({
  current_page: 1,
  last_page: 1,
  per_page: DEFAULT_PER_PAGE,
  total: 0,
})

export const useBnbaImportStore =
  defineStore(
    'bnbaImport',
    () => {
      /*
      |--------------------------------------------------------------------------
      | State - Period
      |--------------------------------------------------------------------------
      */

      const periods =
        ref<BpntPeriod[]>([])

      const selectedPeriodId =
        ref<number | null>(null)

      /*
      |--------------------------------------------------------------------------
      | State - Upload
      |--------------------------------------------------------------------------
      */

      const selectedFile =
        ref<File | null>(null)

      const uploadProgress =
        ref(0)

      const currentImport =
        ref<BnbaImport | null>(null)

      /*
      |--------------------------------------------------------------------------
      | State - Preview
      |--------------------------------------------------------------------------
      */

      const previewRows =
        ref<BnbaImportRow[]>([])

      const previewMeta =
        ref<PaginationMeta>(
          emptyPagination(),
        )

      const statusFilter =
        ref<BnbaRowStatus | null>(
          null,
        )

      const search =
        ref('')

      /*
      |--------------------------------------------------------------------------
      | State - History
      |--------------------------------------------------------------------------
      */

      const importHistory =
        ref<BnbaImport[]>([])

      const historyMeta =
        ref<PaginationMeta>(
          emptyPagination(),
        )

      /*
      |--------------------------------------------------------------------------
      | Loading State
      |--------------------------------------------------------------------------
      */

      const isLoadingPeriods =
        ref(false)

      const isCreatingPeriod =
        ref(false)

      const isUploading =
        ref(false)

      const isLoadingPreview =
        ref(false)

      const isConfirming =
        ref(false)

      const isLoadingHistory =
        ref(false)

      /*
      |--------------------------------------------------------------------------
      | Error
      |--------------------------------------------------------------------------
      */

      const errorMessage =
        ref<string | null>(null)

      const validationErrors =
        ref<LaravelValidationErrors>(
          {},
        )

      /*
      |--------------------------------------------------------------------------
      | Computed
      |--------------------------------------------------------------------------
      */

      const activePeriods =
        computed(
          () =>
            periods.value.filter(
              (period) =>
                period.is_active,
            ),
        )

      const selectedPeriod =
        computed(
          () =>
            periods.value.find(
              (period) =>
                period.id
                ===
                selectedPeriodId.value,
            )
            ?? null,
        )

      const hasImport =
        computed(
          () =>
            currentImport.value
            !== null,
        )

      const canUpload =
        computed(
          () =>
            selectedPeriodId.value
              !== null
            &&
            selectedFile.value
              !== null
            &&
            !isUploading.value,
        )

      const canConfirm =
        computed(() => {
          if (
            !currentImport.value
            || isConfirming.value
          ) {
            return false
          }

          if (
            currentImport.value.status
            !== 'preview_ready'
          ) {
            return false
          }

          const {
            valid,
            warning,
          } =
            currentImport.value
              .summary

          return (
            valid + warning
          ) > 0
        })

      const hasProblemRows =
        computed(() => {
          if (!currentImport.value) {
            return false
          }

          const {
            invalid,
            duplicate,
          } =
            currentImport.value
              .summary

          return (
            invalid + duplicate
          ) > 0
        })

      /*
      |--------------------------------------------------------------------------
      | Error Handler
      |--------------------------------------------------------------------------
      */

      function clearError(): void {
        errorMessage.value = null
        validationErrors.value = {}
      }

      function handleError(
        error: unknown,
        fallbackMessage: string,
      ): void {
        validationErrors.value = {}

        if (
          !axios.isAxiosError<
            LaravelErrorResponse
          >(error)
        ) {
          errorMessage.value =
            fallbackMessage

          return
        }

        const status =
          error.response?.status

        const response =
          error.response?.data

        if (
          status === 422
          && response?.errors
        ) {
          validationErrors.value =
            response.errors

          const firstError =
            Object.values(
              response.errors,
            )
              .flat()
              .find(Boolean)

          errorMessage.value =
            firstError
            ?? response.message
            ?? fallbackMessage

          return
        }

        if (status === 401) {
          errorMessage.value =
            'Sesi login sudah berakhir. Silakan login kembali.'

          return
        }

        if (status === 403) {
          errorMessage.value =
            'Anda tidak memiliki izin untuk melakukan tindakan ini.'

          return
        }

        if (status === 419) {
          errorMessage.value =
            'Sesi keamanan sudah kedaluwarsa. Silakan muat ulang halaman.'

          return
        }

        errorMessage.value =
          response?.message
          ?? fallbackMessage
      }

      /*
      |--------------------------------------------------------------------------
      | Period Actions
      |--------------------------------------------------------------------------
      */

      async function fetchPeriods():
        Promise<void> {
        isLoadingPeriods.value = true

        clearError()

        try {
          periods.value =
            await bnbaService
              .getPeriods()

          const selectedStillExists =
            periods.value.some(
              (period) =>
                period.id
                ===
                selectedPeriodId.value
                &&
                period.is_active,
            )

          if (
            !selectedStillExists
          ) {
            selectedPeriodId.value =
              activePeriods.value[0]
                ?.id
              ?? null
          }
        } catch (error) {
          handleError(
            error,
            'Daftar periode BPNT gagal dimuat.',
          )
        } finally {
          isLoadingPeriods.value =
            false
        }
      }

      async function createPeriod(
        payload: CreateBpntPeriodPayload,
      ): Promise<BpntPeriod | null> {
        isCreatingPeriod.value = true

        clearError()

        try {
          const period =
            await bnbaService
              .createPeriod(
                payload,
              )

          periods.value = [
            period,
            ...periods.value,
          ]

          if (period.is_active) {
            selectedPeriodId.value =
              period.id
          }

          return period
        } catch (error) {
          handleError(
            error,
            'Periode BPNT gagal dibuat.',
          )

          return null
        } finally {
          isCreatingPeriod.value =
            false
        }
      }

      function selectPeriod(
        periodId: number | null,
      ): void {
        selectedPeriodId.value =
          periodId
      }

      /*
      |--------------------------------------------------------------------------
      | File Actions
      |--------------------------------------------------------------------------
      */

      function selectFile(
        file: File | null,
      ): void {
        clearError()

        if (!file) {
          selectedFile.value = null
          uploadProgress.value = 0

          return
        }

        const extension =
          file.name
            .split('.')
            .pop()
            ?.toLowerCase()

        if (
          extension !== 'xlsx'
          && extension !== 'xls'
        ) {
          selectedFile.value = null

          errorMessage.value =
            'File harus berformat .xlsx atau .xls.'

          return
        }

        const maxSize =
          10 * 1024 * 1024

        if (file.size > maxSize) {
          selectedFile.value = null

          errorMessage.value =
            'Ukuran file maksimal 10 MB.'

          return
        }

        selectedFile.value = file
        uploadProgress.value = 0
      }

      /*
      |--------------------------------------------------------------------------
      | Import Actions
      |--------------------------------------------------------------------------
      */

      async function uploadFile():
        Promise<boolean> {
        clearError()

        if (
          selectedPeriodId.value
          === null
        ) {
          errorMessage.value =
            'Pilih periode BPNT terlebih dahulu.'

          return false
        }

        if (!selectedFile.value) {
          errorMessage.value =
            'Pilih file BNBA terlebih dahulu.'

          return false
        }

        isUploading.value = true
        uploadProgress.value = 0

        try {
          const imported =
            await bnbaService.upload(
              selectedPeriodId.value,
              selectedFile.value,
              (progress) => {
                uploadProgress.value =
                  progress
              },
            )

          currentImport.value =
            imported

          uploadProgress.value =
            100

          statusFilter.value =
            null

          search.value = ''

          await fetchPreview(1)

          return true
        } catch (error) {
          uploadProgress.value = 0

          handleError(
            error,
            'File BNBA gagal diproses.',
          )

          return false
        } finally {
          isUploading.value =
            false
        }
      }

      async function fetchPreview(
        page = 1,
      ): Promise<void> {
        if (!currentImport.value) {
          previewRows.value = []

          previewMeta.value =
            emptyPagination()

          return
        }

        isLoadingPreview.value =
          true

        clearError()

        try {
          const response =
            await bnbaService
              .getPreview(
                currentImport.value
                  .id,
                {
                  status:
                    statusFilter.value
                    ?? undefined,

                  search:
                    search.value.trim()
                    || undefined,

                  page,

                  per_page:
                    previewMeta.value
                      .per_page
                    || DEFAULT_PER_PAGE,
                },
              )

          currentImport.value =
            response.data.import

          previewRows.value =
            response.data.rows

          previewMeta.value =
            response.meta
        } catch (error) {
          handleError(
            error,
            'Preview BNBA gagal dimuat.',
          )
        } finally {
          isLoadingPreview.value =
            false
        }
      }

      async function changeStatusFilter(
        status:
          BnbaRowStatus
          | null,
      ): Promise<void> {
        statusFilter.value = status

        await fetchPreview(1)
      }

      async function searchPreview(
        keyword: string,
      ): Promise<void> {
        search.value =
          keyword.trim()

        await fetchPreview(1)
      }

      async function changePage(
        page: number,
      ): Promise<void> {
        if (
          page < 1
          ||
          page
          >
          previewMeta.value
            .last_page
        ) {
          return
        }

        await fetchPreview(page)
      }

      async function confirmImport():
        Promise<boolean> {
        if (!currentImport.value) {
          errorMessage.value =
            'Data import belum tersedia.'

          return false
        }

        if (!canConfirm.value) {
          errorMessage.value =
            'Tidak ada data yang dapat dikonfirmasi.'

          return false
        }

        isConfirming.value =
          true

        clearError()

        try {
          currentImport.value =
            await bnbaService
              .confirm(
                currentImport.value
                  .id,
              )

          await fetchPreview(
            previewMeta.value
              .current_page,
          )

          return true
        } catch (error) {
          handleError(
            error,
            'Konfirmasi import BNBA gagal.',
          )

          return false
        } finally {
          isConfirming.value =
            false
        }
      }

      /*
      |--------------------------------------------------------------------------
      | History
      |--------------------------------------------------------------------------
      */

      async function fetchHistory(
        page = 1,
      ): Promise<void> {
        isLoadingHistory.value =
          true

        clearError()

        try {
          const response =
            await bnbaService
              .getImportHistory({
                page,
                per_page: 15,
              })

          importHistory.value =
            response.data

          historyMeta.value =
            response.meta
        } catch (error) {
          handleError(
            error,
            'Riwayat import BNBA gagal dimuat.',
          )
        } finally {
          isLoadingHistory.value =
            false
        }
      }

      /*
      |--------------------------------------------------------------------------
      | Reset
      |--------------------------------------------------------------------------
      */

      function resetImport(): void {
        selectedFile.value = null

        uploadProgress.value = 0

        currentImport.value = null

        previewRows.value = []

        previewMeta.value =
          emptyPagination()

        statusFilter.value = null

        search.value = ''

        clearError()
      }

      return {
        /*
        |----------------------------------------------------------------------
        | State
        |----------------------------------------------------------------------
        */

        periods,
        selectedPeriodId,
        selectedFile,
        uploadProgress,

        currentImport,

        previewRows,
        previewMeta,
        statusFilter,
        search,

        importHistory,
        historyMeta,

        isLoadingPeriods,
        isCreatingPeriod,
        isUploading,
        isLoadingPreview,
        isConfirming,
        isLoadingHistory,

        errorMessage,
        validationErrors,

        /*
        |----------------------------------------------------------------------
        | Computed
        |----------------------------------------------------------------------
        */

        activePeriods,
        selectedPeriod,
        hasImport,
        canUpload,
        canConfirm,
        hasProblemRows,

        /*
        |----------------------------------------------------------------------
        | Actions
        |----------------------------------------------------------------------
        */

        fetchPeriods,
        createPeriod,
        selectPeriod,
        selectFile,

        uploadFile,
        fetchPreview,
        changeStatusFilter,
        searchPreview,
        changePage,
        confirmImport,

        fetchHistory,

        resetImport,
        clearError,
      }
    },
  )