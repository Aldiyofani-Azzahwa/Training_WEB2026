import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'

import {
  flushPromises,
  mount,
} from '@vue/test-utils'

import KpmView
  from '@/views/surveyor/KpmView.vue'

import {
  surveyorWorkspaceService,
} from '@/services/surveyorWorkspaceService'

import type {
  SurveyorEWarung,
  SurveyorParticipant,
  SurveyorParticipantActivityCode,
  SurveyorParticipantQuery,
  SurveyorParticipantResponse,
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

vi.mock(
  '@/services/surveyorEWarungSelection',
  async () => {
    const {
      ref,
    } =
      await import('vue')

    const selectedEWarung =
      ref<SurveyorEWarung | null>({
        id:
          7,

        name:
          'E-Warung Makmur',

        is_active:
          true,

        created_at:
          null,

        updated_at:
          null,
      })

    const synchronizeEWarungs =
  vi.fn<
    (
      periodId: number,
      eWarungs: SurveyorEWarung[],
    ) => void
  >(
    (
      _periodId,
      eWarungs,
    ) => {
      const selectedId =
        selectedEWarung.value
          ?.id

      selectedEWarung.value =
        eWarungs.find(
          (eWarung) => {
            return (
              eWarung.id
              ===
              selectedId
            )
          },
        )
        ??
        null
    },
  )

    return {
      useSurveyorEWarungSelection:
        () => ({
          selectedEWarung,
          synchronizeEWarungs,
        }),
    }
  },
)

vi.mock(
  '@/services/surveyorWorkspaceService',
  () => ({
    surveyorWorkspaceService: {
      getContext:
        vi.fn<
          () => Promise<SurveyorWorkspaceContext>
        >(),

      getParticipants:
        vi.fn<
          (
            query?: SurveyorParticipantQuery,
          ) => Promise<SurveyorParticipantResponse>
        >(),

      getActiveEWarungs:
        vi.fn<
          () => Promise<SurveyorEWarung[]>
        >(),

      storeTransaction:
        vi.fn<
          (
            payload: unknown,
          ) => Promise<unknown>
        >(),
    },
  }),
)

const getContextMock =
  vi.mocked(
    surveyorWorkspaceService
      .getContext,
  )

const getParticipantsMock =
  vi.mocked(
    surveyorWorkspaceService
      .getParticipants,
  )

const getActiveEWarungsMock =
  vi.mocked(
    surveyorWorkspaceService
      .getActiveEWarungs,
  )

const storeTransactionMock =
  vi.mocked(
    surveyorWorkspaceService
      .storeTransaction,
  )

const activeEWarung:
  SurveyorEWarung = {
    id:
      7,

    name:
      'E-Warung Makmur',

    is_active:
      true,

    created_at:
      null,

    updated_at:
      null,
  }

const normalContext:
  SurveyorWorkspaceContext = {
    surveyor: {
      id:
        10,

      name:
        'Andi Saputra',

      username:
        'andi.surveyor',
    },

    period: {
      id:
        5,

      code:
        'BPNT-2026-08',

      name:
        'BPNT Agustus 2026',

      year:
        2026,
    },

    assignment: {
      id:
        20,

      kecamatan: {
        id:
          3,

        name:
          'Kranggan',
      },

      kelurahan: {
        id:
          15,

        name:
          'Jagalan',
      },
    },

    kpm_count:
      2,
  }

function makeParticipant(
  id: number,
  kpmId: number,
  fullName: string,
  nik: string,
  activityCode:
    SurveyorParticipantActivityCode,
  activityLabel: string,
  canRecordTransaction: boolean,
): SurveyorParticipant {
  const isFinal =
    activityCode !== 'pending'

  return {
    id,

    kpm: {
      id:
        kpmId,

      nik,

      full_name:
        fullName,

      birth_place:
        'Mojokerto',

      birth_date:
        '1975-01-01',

      address:
        `Alamat ${fullName}`,

      rt:
        '001',

      rw:
        '002',
    },

    wilayah: {
      kelurahan: {
        id:
          15,

        name:
          'Jagalan',
      },

      kecamatan: {
        id:
          3,

        name:
          'Kranggan',
      },
    },

    saldo_bpnt:
      200000,

    activity: {
      code:
        activityCode,

      label:
        activityLabel,

      is_final:
        isFinal,

      can_record_transaction:
        canRecordTransaction,
    },
  }
}

const pendingParticipant =
  makeParticipant(
    101,
    201,
    'Budi Santoso',
    '3576********0001',
    'pending',
    'Belum Transaksi',
    true,
  )

const finalParticipant =
  makeParticipant(
    102,
    202,
    'Siti Aminah',
    '3576********0002',
    'transacted',
    'Sudah Bertransaksi',
    false,
  )

const firstPage:
  SurveyorParticipantResponse = {
    data: [
      pendingParticipant,
    ],

    meta: {
      current_page:
        1,

      last_page:
        2,

      per_page:
        15,

      total:
        2,
    },
  }

const secondPage:
  SurveyorParticipantResponse = {
    data: [
      finalParticipant,
    ],

    meta: {
      current_page:
        2,

      last_page:
        2,

      per_page:
        15,

      total:
        2,
    },
  }

describe(
  'Surveyor KPM View',
  () => {
    beforeEach(() => {
      vi.clearAllMocks()

      getActiveEWarungsMock
        .mockResolvedValue([
          activeEWarung,
        ])
    })

    function mountKpmView() {
      return mount(
        KpmView,
      )
    }

    it(
      'shows only participants returned by assigned-region endpoint',
      async () => {
        getContextMock
          .mockResolvedValue(
            normalContext,
          )

        getParticipantsMock
          .mockResolvedValue(
            firstPage,
          )

        const wrapper =
          mountKpmView()

        await flushPromises()

        expect(
  wrapper
    .get(
      '[data-testid="kpm-page-title"]',
    )
    .text(),
).toContain(
  'Jagalan',
)

        expect(
          wrapper.text(),
        ).toContain(
          'Budi Santoso',
        )

        expect(
          wrapper.text(),
        ).toContain(
          '3576********0001',
        )

        expect(
          getParticipantsMock,
        ).toHaveBeenCalledWith({
          page:
            1,

          per_page:
            15,
        })

        const firstQuery =
          getParticipantsMock
            .mock
            .calls[0]?.[0]

        expect(
          firstQuery,
        ).not.toHaveProperty(
          'period_id',
        )

        expect(
          firstQuery,
        ).not.toHaveProperty(
          'kelurahan_id',
        )

        expect(
          firstQuery,
        ).not.toHaveProperty(
          'kecamatan_id',
        )
      },
    )

    it(
      'does not request participants or E-Warungs without assignment',
      async () => {
        getContextMock
          .mockResolvedValue({
            ...normalContext,

            assignment:
              null,

            kpm_count:
              0,
          })

        const wrapper =
          mountKpmView()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="kpm-no-assignment"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Anda belum memiliki wilayah tugas',
        )

        expect(
          getParticipantsMock,
        ).not.toHaveBeenCalled()

        expect(
          getActiveEWarungsMock,
        ).not.toHaveBeenCalled()
      },
    )

    it(
      'shows empty state when assigned region has no participants',
      async () => {
        getContextMock
          .mockResolvedValue({
            ...normalContext,

            kpm_count:
              0,
          })

        getParticipantsMock
          .mockResolvedValue({
            data:
              [],

            meta: {
              current_page:
                1,

              last_page:
                1,

              per_page:
                15,

              total:
                0,
            },
          })

        const wrapper =
          mountKpmView()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="participants-empty"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Belum ada KPM di wilayah ini',
        )
      },
    )

    it(
      'appends the next page and hides actions for final KPM',
      async () => {
        getContextMock
          .mockResolvedValue(
            normalContext,
          )

        getParticipantsMock
          .mockResolvedValueOnce(
            firstPage,
          )
          .mockResolvedValueOnce(
            secondPage,
          )

        const wrapper =
          mountKpmView()

        await flushPromises()

        await wrapper
          .get(
            '[data-testid="load-more"]',
          )
          .trigger(
            'click',
          )

        await flushPromises()

        expect(
          wrapper
            .findAll(
              '[data-testid="participant-card"]',
            ),
        ).toHaveLength(
          2,
        )

        expect(
          wrapper
            .findAll(
              '[data-testid="kpm-transaction-button"]',
            ),
        ).toHaveLength(
          1,
        )

        expect(
          wrapper
            .findAll(
              '[data-testid="kpm-final-indicator"]',
            ),
        ).toHaveLength(
          1,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Sudah Bertransaksi',
        )
      },
    )

    it(
      'records transaction by participant ID and removes its action',
      async () => {
        getContextMock
          .mockResolvedValue(
            normalContext,
          )

        getParticipantsMock
          .mockResolvedValue(
            firstPage,
          )

        storeTransactionMock
          .mockResolvedValue(
            undefined as never,
          )

        const wrapper =
          mountKpmView()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="kpm-transaction-button"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        await wrapper
          .get(
            '[data-testid="kpm-transaction-button"]',
          )
          .trigger(
            'click',
          )

        expect(
          wrapper
            .find(
              '[data-testid="kpm-transaction-confirmation"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        await wrapper
          .get(
            '[data-testid="kpm-confirm-transaction"]',
          )
          .trigger(
            'click',
          )

        await flushPromises()

        expect(
          storeTransactionMock,
        ).toHaveBeenCalledTimes(
          1,
        )

        expect(
          storeTransactionMock,
        ).toHaveBeenCalledWith({
          bpnt_participant_id:
            101,

          e_warung_id:
            7,
        })

        expect(
          wrapper
            .find(
              '[data-testid="kpm-transaction-button"]',
            )
            .exists(),
        ).toBe(
          false,
        )

        expect(
          wrapper
            .find(
              '[data-testid="kpm-final-indicator"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper
            .find(
              '[data-testid="kpm-transaction-success"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Sudah Bertransaksi',
        )
      },
    )
  },
)