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
  BnbaParticipant,
  BnbaParticipantFilterOptions,
  BpntPeriod,
  LaravelErrorResponse,
  PaginationMeta,
} from '@/types/bnba'

const createEmptyMeta =
  (): PaginationMeta => ({
    current_page: 1,
    last_page: 1,
    per_page: 25,
    total: 0,
  })

const createEmptyOptions =
  (): BnbaParticipantFilterOptions => ({
    kecamatan: [],
    kelurahan: [],
    e_warungs: [],
  })

export const useBnbaParticipantsStore =
  defineStore(
    'bnbaParticipants',
    () => {
      const periods =
        ref<BpntPeriod[]>([])

      const participants =
        ref<BnbaParticipant[]>([])

      const options =
        ref<BnbaParticipantFilterOptions>(
          createEmptyOptions(),
        )

      const meta =
        ref<PaginationMeta>(
          createEmptyMeta(),
        )

      const periodId =
        ref<number | null>(
          null,
        )

      const search =
        ref('')

      const kecamatan =
        ref('')

      const kelurahan =
        ref('')

      const eWarung =
        ref('')

      const isLoading =
        ref(false)

      const isLoadingPeriods =
        ref(false)

      const errorMessage =
        ref<string | null>(
          null,
        )

      const selectedPeriod =
        computed(
          () =>
            periods.value.find(
              (period) =>
                period.id
                === periodId.value,
            )
            ?? null,
        )

      function handleError(
        error: unknown,
        fallback: string,
      ): void {
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
            'Anda tidak memiliki akses ke data BNBA.'

          return
        }

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
          error.response
            ?.data
            ?.message
          ?? fallback
      }

      function resetParticipants():
        void {
        participants.value =
          []

        meta.value =
          createEmptyMeta()

        options.value =
          createEmptyOptions()
      }

      async function fetchPeriods(
        preferredPeriodId?:
          number,
      ): Promise<void> {
        isLoadingPeriods.value =
          true

        errorMessage.value =
          null

        try {
          const allPeriods =
            await bnbaService
              .getPeriods()

          /*
           * Data BNBA hanya menampilkan
           * periode dengan BNBA CONFIRMED.
           *
           * Tidak ada lagi:
           * period.status
           * period.current_bnba
           */
          periods.value =
            allPeriods.filter(
              (period) =>
                period.bnba
                  ?.status
                === 'confirmed',
            )

          if (
            periods.value.length
            === 0
          ) {
            periodId.value =
              null

            resetParticipants()

            return
          }

          const preferredExists =
            preferredPeriodId
            !== undefined
            &&
            periods.value.some(
              (period) =>
                period.id
                === preferredPeriodId,
            )

          if (
            preferredExists
            &&
            preferredPeriodId
            !== undefined
          ) {
            periodId.value =
              preferredPeriodId
          } else {
            const currentStillExists =
              periodId.value
              !== null
              &&
              periods.value.some(
                (period) =>
                  period.id
                  === periodId.value,
              )

            if (
              !currentStillExists
            ) {
              /*
               * Tidak mencari active lagi.
               * Ambil periode pertama yang
               * punya BNBA confirmed.
               */
              periodId.value =
                periods.value[0]
                  ?.id
                ?? null
            }
          }

          if (
            periodId.value
            !== null
          ) {
            await selectPeriod(
              periodId.value,
            )
          }
        } catch (error) {
          handleError(
            error,
            'Periode BNBA gagal dimuat.',
          )
        } finally {
          isLoadingPeriods.value =
            false
        }
      }

      async function selectPeriod(
        id: number,
      ): Promise<void> {
        const exists =
          periods.value.some(
            (period) =>
              period.id
              === id,
          )

        if (!exists) {
          return
        }

        periodId.value =
          id

        search.value = ''
        kecamatan.value = ''
        kelurahan.value = ''
        eWarung.value = ''

        resetParticipants()

        await Promise.all([
          fetchOptions(),
          fetchParticipants(1),
        ])
      }

      async function fetchOptions():
        Promise<void> {
        if (
          periodId.value
          === null
        ) {
          options.value =
            createEmptyOptions()

          return
        }

        try {
          options.value =
            await bnbaService
              .getParticipantFilterOptions(
                periodId.value,
              )
        } catch (error) {
          options.value =
            createEmptyOptions()

          handleError(
            error,
            'Pilihan filter BNBA gagal dimuat.',
          )
        }
      }

      async function fetchParticipants(
        page = 1,
      ): Promise<void> {
        if (
          periodId.value
          === null
        ) {
          participants.value =
            []

          meta.value =
            createEmptyMeta()

          return
        }

        isLoading.value =
          true

        errorMessage.value =
          null

        try {
          const response =
            await bnbaService
              .getParticipants({
                period_id:
                  periodId.value,

                search:
                  search.value.trim()
                  || undefined,

                kecamatan:
                  kecamatan.value
                  || undefined,

                kelurahan:
                  kelurahan.value
                  || undefined,

                e_warung:
                  eWarung.value
                  || undefined,

                page,

                per_page: 25,
              })

          participants.value =
            response.data

          meta.value =
            response.meta
        } catch (error) {
          handleError(
            error,
            'Data BNBA gagal dimuat.',
          )
        } finally {
          isLoading.value =
            false
        }
      }

      async function applyFilters():
        Promise<void> {
        await fetchParticipants(
          1,
        )
      }

      async function clearFilters():
        Promise<void> {
        search.value = ''
        kecamatan.value = ''
        kelurahan.value = ''
        eWarung.value = ''

        await fetchParticipants(
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
          meta.value.last_page
        ) {
          return
        }

        await fetchParticipants(
          page,
        )
      }

      return {
        periods,
        participants,
        options,
        meta,

        periodId,
        search,
        kecamatan,
        kelurahan,
        eWarung,

        isLoading,
        isLoadingPeriods,
        errorMessage,

        selectedPeriod,

        fetchPeriods,
        selectPeriod,

        fetchParticipants,

        applyFilters,
        clearFilters,
        changePage,
      }
    },
  )