import axios from 'axios'
import {
  computed,
  ref,
} from 'vue'
import { defineStore } from 'pinia'

import { bnbaService } from '@/services/bnbaService'

import type {
  BnbaParticipant,
  BnbaParticipantFilterOptions,
  BpntPeriod,
  LaravelErrorResponse,
  PaginationMeta,
} from '@/types/bnba'

const EMPTY_OPTIONS:
  BnbaParticipantFilterOptions = {
    kecamatan: [],
    kelurahan: [],
    e_warungs: [],
  }

const emptyMeta = (): PaginationMeta => ({
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
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
        ref<BnbaParticipantFilterOptions>({
          ...EMPTY_OPTIONS,
        })

      const meta =
        ref<PaginationMeta>(
          emptyMeta(),
        )

      const periodId =
        ref<number | null>(null)

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
        ref<string | null>(null)

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
          axios.isAxiosError<
            LaravelErrorResponse
          >(error)
        ) {
          if (
            error.response?.status
            === 403
          ) {
            errorMessage.value =
              'Anda tidak memiliki akses ke data BNBA ini.'

            return
          }

          errorMessage.value =
            error.response?.data
              ?.message
            ?? fallback

          return
        }

        errorMessage.value =
          fallback
      }

      async function fetchPeriods():
        Promise<void> {
        isLoadingPeriods.value =
          true

        errorMessage.value = null

        try {
          periods.value =
            await bnbaService
              .getPeriods()

          const active =
            periods.value.filter(
              (period) =>
                period.is_active,
            )

          if (
            periodId.value === null
          ) {
            periodId.value =
              active[0]?.id
              ?? null
          }

          if (
            periodId.value !== null
          ) {
            await selectPeriod(
              periodId.value,
            )
          }
        } catch (error) {
          handleError(
            error,
            'Periode BPNT gagal dimuat.',
          )
        } finally {
          isLoadingPeriods.value =
            false
        }
      }

      async function selectPeriod(
        id: number,
      ): Promise<void> {
        periodId.value = id

        search.value = ''
        kecamatan.value = ''
        kelurahan.value = ''
        eWarung.value = ''

        participants.value = []
        meta.value = emptyMeta()

        await Promise.all([
          fetchOptions(),
          fetchParticipants(1),
        ])
      }

      async function fetchOptions():
        Promise<void> {
        if (periodId.value === null) {
          options.value = {
            ...EMPTY_OPTIONS,
          }

          return
        }

        try {
          options.value =
            await bnbaService
              .getParticipantFilterOptions(
                periodId.value,
              )
        } catch (error) {
          handleError(
            error,
            'Pilihan filter gagal dimuat.',
          )
        }
      }

      async function fetchParticipants(
        page = 1,
      ): Promise<void> {
        if (periodId.value === null) {
          participants.value = []
          meta.value = emptyMeta()

          return
        }

        isLoading.value = true
        errorMessage.value = null

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
          isLoading.value = false
        }
      }

      async function applyFilters():
        Promise<void> {
        await fetchParticipants(1)
      }

      async function clearFilters():
        Promise<void> {
        search.value = ''
        kecamatan.value = ''
        kelurahan.value = ''
        eWarung.value = ''

        await fetchParticipants(1)
      }

      async function changePage(
        page: number,
      ): Promise<void> {
        if (
          page < 1
          || page > meta.value.last_page
        ) {
          return
        }

        await fetchParticipants(page)
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