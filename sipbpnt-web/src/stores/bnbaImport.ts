import axios from 'axios'

import {
  computed,
  ref,
} from 'vue'

import {
  defineStore,
} from 'pinia'

import {
  bnbaService,
} from '@/services/bnbaService'

import type {
  BnbaImport,
  BnbaImportRow,
  BnbaRowStatus,
  BpntPeriod,
  CreateBpntPeriodPayload,
  LaravelErrorResponse,
  LaravelValidationErrors,
  PaginationMeta,
  UpdateBpntPeriodPayload,
} from '@/types/bnba'

const DEFAULT_PER_PAGE =
  50

const emptyPagination =
  (): PaginationMeta => ({
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
      | Period
      |--------------------------------------------------------------------------
      */

      const periods =
        ref<BpntPeriod[]>([])

      const selectedPeriodId =
        ref<number | null>(
          null,
        )

      /*
      |--------------------------------------------------------------------------
      | Import
      |--------------------------------------------------------------------------
      */

      const selectedFile =
        ref<File | null>(
          null,
        )

      const uploadProgress =
        ref(0)

      const currentImport =
        ref<BnbaImport | null>(
          null,
        )

      /*
      |--------------------------------------------------------------------------
      | Preview
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
      | Loading
      |--------------------------------------------------------------------------
      */

      const isLoadingPeriods =
        ref(false)

      const isCreatingPeriod =
        ref(false)

      const updatingPeriodId =
        ref<number | null>(
          null,
        )

      const deletingPeriodId =
        ref<number | null>(
          null,
        )

      const isDeletingBnba =
        ref(false)

      const isUploading =
        ref(false)

      const isLoadingPreview =
        ref(false)

      const isConfirming =
        ref(false)

      /*
      |--------------------------------------------------------------------------
      | Error
      |--------------------------------------------------------------------------
      */

      const errorMessage =
        ref<string | null>(
          null,
        )

      const validationErrors =
        ref<LaravelValidationErrors>(
          {},
        )

      /*
      |--------------------------------------------------------------------------
      | Computed
      |--------------------------------------------------------------------------
      */

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

      /*
       * BNBA dianggap sedang dikerjakan
       * sejak file dipilih sampai proses
       * preview / konfirmasi selesai.
       *
       * Selama kondisi ini:
       * - tidak boleh pindah periode
       * - tidak boleh tambah periode
       * - tidak boleh edit periode
       * - tidak boleh hapus periode
       */
      const isBnbaWorking =
        computed(() => {
          return (
            selectedFile.value
            !== null
            ||
            isUploading.value
            ||
            isLoadingPreview.value
            ||
            isConfirming.value
            ||
            isDeletingBnba.value
            ||
            currentImport.value
              ?.status
            === 'preview_ready'
          )
        })

      const showUploadPanel =
        computed(() => {
          return (
            selectedPeriod.value
            !== null
            &&
            selectedPeriod.value
              .bnba
            === null
            &&
            currentImport.value
            === null
          )
        })

      const canUpload =
        computed(() => {
          return (
            showUploadPanel.value
            &&
            selectedFile.value
            !== null
            &&
            !isUploading.value
          )
        })

      const canConfirm =
        computed(() => {
          if (
            currentImport.value
            === null
            ||
            currentImport.value
              .status
            !== 'preview_ready'
            ||
            isConfirming.value
          ) {
            return false
          }

          const validRows =
            currentImport.value
              .summary.valid

          const warningRows =
            currentImport.value
              .summary.warning

          return (
            validRows
            +
            warningRows
          ) > 0
        })

      /*
      |--------------------------------------------------------------------------
      | Error Handler
      |--------------------------------------------------------------------------
      */

      function clearError():
        void {
        errorMessage.value =
          null

        validationErrors.value =
          {}
      }

      function handleError(
        error: unknown,
        fallback: string,
      ): void {
        validationErrors.value =
          {}

        if (
          !axios.isAxiosError<
            LaravelErrorResponse
          >(error)
        ) {
          errorMessage.value =
            fallback

          return
        }

        const status =
          error.response
            ?.status

        const data =
          error.response
            ?.data

        if (
          status === 422
          &&
          data?.errors
        ) {
          validationErrors.value =
            data.errors

          const firstError =
            Object.values(
              data.errors,
            )
              .flat()
              .find(Boolean)

          errorMessage.value =
            firstError
            ?? data.message
            ?? fallback

          return
        }

        if (
          status === 401
        ) {
          errorMessage.value =
            'Sesi login sudah berakhir.'

          return
        }

        if (
          status === 403
        ) {
          errorMessage.value =
            'Anda tidak memiliki izin untuk melakukan tindakan ini.'

          return
        }

        if (
          status === 419
        ) {
          errorMessage.value =
            'Sesi keamanan sudah kedaluwarsa. Muat ulang halaman.'

          return
        }

        /*
         * Jangan bocorkan SQLSTATE
         * atau detail Laravel ke UI.
         */
        if (
          status !== undefined
          &&
          status >= 500
        ) {
          errorMessage.value =
            fallback

          return
        }

        errorMessage.value =
          data?.message
          ?? fallback
      }

      /*
      |--------------------------------------------------------------------------
      | Reset Workspace
      |--------------------------------------------------------------------------
      */

      function clearWorkspace():
        void {
        selectedFile.value =
          null

        uploadProgress.value =
          0

        currentImport.value =
          null

        previewRows.value =
          []

        previewMeta.value =
          emptyPagination()

        statusFilter.value =
          null

        search.value =
          ''
      }

      /*
      |--------------------------------------------------------------------------
      | Period
      |--------------------------------------------------------------------------
      */

      async function fetchPeriods():
        Promise<void> {
        isLoadingPeriods.value =
          true

        clearError()

        try {
          periods.value =
            await bnbaService
              .getPeriods()

          if (
            selectedPeriodId.value
            !== null
          ) {
            const selectedStillExists =
              periods.value.some(
                (period) =>
                  period.id
                  ===
                  selectedPeriodId.value,
              )

            if (
              !selectedStillExists
            ) {
              selectedPeriodId.value =
                null

              clearWorkspace()
            }
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
        payload:
          CreateBpntPeriodPayload,
      ): Promise<
        BpntPeriod | null
      > {
        /*
         * Defense tambahan.
         * UI memang dikunci, tetapi store
         * juga tidak boleh menerima request
         * create saat BNBA sedang diproses.
         */
        if (
          isBnbaWorking.value
        ) {
          errorMessage.value =
            'Selesaikan proses BNBA terlebih dahulu sebelum menambah periode.'

          return null
        }

        isCreatingPeriod.value =
          true

        clearError()

        try {
          const period =
            await bnbaService
              .createPeriod(
                payload,
              )

          await fetchPeriods()

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

      async function updatePeriod(
        periodId: number,
        payload:
          UpdateBpntPeriodPayload,
      ): Promise<boolean> {
        if (
          isBnbaWorking.value
        ) {
          errorMessage.value =
            'Selesaikan proses BNBA terlebih dahulu sebelum mengedit periode.'

          return false
        }

        updatingPeriodId.value =
          periodId

        clearError()

        try {
          await bnbaService
            .updatePeriod(
              periodId,
              payload,
            )

          await fetchPeriods()

          return true
        } catch (error) {
          handleError(
            error,
            'Periode BPNT gagal diperbarui.',
          )

          return false
        } finally {
          updatingPeriodId.value =
            null
        }
      }

      async function deletePeriod(
        periodId: number,
      ): Promise<boolean> {
        if (
          isBnbaWorking.value
        ) {
          errorMessage.value =
            'Selesaikan proses BNBA terlebih dahulu sebelum menghapus periode.'

          return false
        }

        deletingPeriodId.value =
          periodId

        clearError()

        try {
          await bnbaService
            .deletePeriod(
              periodId,
            )

          if (
            selectedPeriodId.value
            === periodId
          ) {
            selectedPeriodId.value =
              null

            clearWorkspace()
          }

          await fetchPeriods()

          return true
        } catch (error) {
          handleError(
            error,
            'Periode BPNT gagal dihapus.',
          )

          return false
        } finally {
          deletingPeriodId.value =
            null
        }
      }

      async function selectPeriod(
        periodId: number,
      ): Promise<void> {
        /*
         * Tidak boleh pindah periode
         * ketika BNBA periode lain
         * sedang dikerjakan.
         */
        if (
          isBnbaWorking.value
          &&
          selectedPeriodId.value
          !== null
          &&
          selectedPeriodId.value
          !== periodId
        ) {
          errorMessage.value =
            'Selesaikan atau hapus proses BNBA pada periode yang sedang dibuka sebelum berpindah periode.'

          return
        }

        const period =
          periods.value.find(
            (item) =>
              item.id
              === periodId,
          )

        if (!period) {
          return
        }

        /*
         * Klik periode yang sama saat
         * sedang bekerja tidak perlu
         * mereset workspace.
         */
        if (
          selectedPeriodId.value
          === periodId
          &&
          isBnbaWorking.value
        ) {
          return
        }

        selectedPeriodId.value =
          periodId

        clearWorkspace()
        clearError()

        /*
         * Kalau periode mempunyai import
         * preview yang belum dikonfirmasi,
         * buka kembali preview tersebut.
         */
        if (
          period.bnba
            ?.status
          === 'preview_ready'
        ) {
          await loadImportPreview(
            period.bnba.id,
          )
        }
      }

      function clearPeriodSelection():
  boolean {
  /*
   * Jangan membatalkan periode
   * saat BNBA sedang dikerjakan.
   *
   * Ini mempertahankan rule:
   * file dipilih / upload / preview /
   * confirm / delete harus diselesaikan
   * terlebih dahulu.
   */
  if (
    isBnbaWorking.value
  ) {
    return false
  }

  selectedPeriodId.value =
    null

  clearWorkspace()
  clearError()

  return true
}

      /*
      |--------------------------------------------------------------------------
      | File
      |--------------------------------------------------------------------------
      */

      function selectFile(
        file: File | null,
      ): void {
        clearError()

        if (
          file === null
        ) {
          selectedFile.value =
            null

          uploadProgress.value =
            0

          return
        }

        if (
          selectedPeriod.value
          === null
        ) {
          errorMessage.value =
            'Klik periode BPNT terlebih dahulu.'

          return
        }

        if (
          selectedPeriod.value
            .bnba
          !== null
        ) {
          errorMessage.value =
            'Periode ini sudah memiliki BNBA.'

          return
        }

        const extension =
          file.name
            .split('.')
            .pop()
            ?.toLowerCase()

        if (
          extension !== 'xlsx'
          &&
          extension !== 'xls'
        ) {
          selectedFile.value =
            null

          errorMessage.value =
            'File harus berformat .xlsx atau .xls.'

          return
        }

        const maximumFileSize =
          10
          * 1024
          * 1024

        if (
          file.size
          >
          maximumFileSize
        ) {
          selectedFile.value =
            null

          errorMessage.value =
            'Ukuran file maksimal 10 MB.'

          return
        }

        selectedFile.value =
          file

        uploadProgress.value =
          0
      }

      /*
      |--------------------------------------------------------------------------
      | Upload
      |--------------------------------------------------------------------------
      */

      async function uploadFile():
        Promise<boolean> {
        if (
          selectedPeriodId.value
          === null
        ) {
          errorMessage.value =
            'Klik periode BPNT terlebih dahulu.'

          return false
        }

        if (
          selectedFile.value
          === null
        ) {
          errorMessage.value =
            'Pilih file BNBA terlebih dahulu.'

          return false
        }

        if (
          selectedPeriod.value
            ?.bnba
          !== null
        ) {
          errorMessage.value =
            'Periode ini sudah memiliki BNBA.'

          return false
        }

        isUploading.value =
          true

        uploadProgress.value =
          0

        clearError()

        try {
          currentImport.value =
            await bnbaService
              .upload(
                selectedPeriodId.value,
                selectedFile.value,
                (
                  progress,
                ) => {
                  uploadProgress.value =
                    progress
                },
              )

          uploadProgress.value =
            100

          await fetchPreview(
            1,
          )

          await fetchPeriods()

          return true
        } catch (error) {
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

      /*
      |--------------------------------------------------------------------------
      | Preview
      |--------------------------------------------------------------------------
      */

      async function loadImportPreview(
        importId: number,
      ): Promise<void> {
        isLoadingPreview.value =
          true

        clearError()

        try {
          const response =
            await bnbaService
              .getPreview(
                importId,
                {
                  page: 1,
                  per_page:
                    DEFAULT_PER_PAGE,
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

      async function fetchPreview(
        page = 1,
      ): Promise<void> {
        if (
          currentImport.value
          === null
        ) {
          previewRows.value =
            []

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
                currentImport.value.id,
                {
                  status:
                    statusFilter.value
                    ?? undefined,

                  search:
                    search.value
                      .trim()
                    || undefined,

                  page,

                  per_page:
                    DEFAULT_PER_PAGE,
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
          BnbaRowStatus | null,
      ): Promise<void> {
        statusFilter.value =
          status

        await fetchPreview(
          1,
        )
      }

      async function searchPreview(
        keyword: string,
      ): Promise<void> {
        search.value =
          keyword.trim()

        await fetchPreview(
          1,
        )
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

        await fetchPreview(
          page,
        )
      }

      /*
      |--------------------------------------------------------------------------
      | Confirm
      |--------------------------------------------------------------------------
      */

      async function confirmImport():
        Promise<boolean> {
        if (
          currentImport.value
          === null
          ||
          !canConfirm.value
        ) {
          return false
        }

        isConfirming.value =
          true

        clearError()

        try {
          await bnbaService
            .confirm(
              currentImport.value.id,
            )

          /*
           * Setelah confirmed:
           *
           * BNBA_WORKING selesai.
           * Periode bisa dikelola lagi.
           */
          clearWorkspace()

          await fetchPeriods()

          return true
        } catch (error) {
          handleError(
            error,
            'Konfirmasi BNBA gagal.',
          )

          return false
        } finally {
          isConfirming.value =
            false
        }
      }

      /*
      |--------------------------------------------------------------------------
      | Delete BNBA
      |--------------------------------------------------------------------------
      */

      async function deleteBnba():
        Promise<boolean> {
        if (
          selectedPeriodId.value
          === null
        ) {
          return false
        }

        isDeletingBnba.value =
          true

        clearError()

        try {
          await bnbaService
            .deletePeriodBnba(
              selectedPeriodId.value,
            )

          clearWorkspace()

          await fetchPeriods()

          return true
        } catch (error) {
          handleError(
            error,
            'Data BNBA gagal dihapus.',
          )

          return false
        } finally {
          isDeletingBnba.value =
            false
        }
      }

      return {
        /*
         * Period
         */
        periods,
        selectedPeriodId,
        selectedPeriod,

        /*
         * Import
         */
        selectedFile,
        uploadProgress,
        currentImport,

        /*
         * Preview
         */
        previewRows,
        previewMeta,
        statusFilter,
        search,

        /*
         * Loading
         */
        isLoadingPeriods,
        isCreatingPeriod,
        updatingPeriodId,
        deletingPeriodId,
        isDeletingBnba,

        isUploading,
        isLoadingPreview,
        isConfirming,

        /*
         * Error
         */
        errorMessage,
        validationErrors,

        /*
         * Computed
         */
        isBnbaWorking,
        showUploadPanel,
        canUpload,
        canConfirm,

        /*
         * Actions
         */
        fetchPeriods,

        createPeriod,
        updatePeriod,
        deletePeriod,

        selectPeriod,
        clearPeriodSelection,

        selectFile,
        uploadFile,

        changeStatusFilter,
        searchPreview,
        changePage,

        confirmImport,
        deleteBnba,

        clearError,
      }
    },
  )