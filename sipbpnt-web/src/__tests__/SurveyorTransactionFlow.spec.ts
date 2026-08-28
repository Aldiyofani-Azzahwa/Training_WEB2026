import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'

import {
  createPinia,
  setActivePinia,
} from 'pinia'

import {
  flushPromises,
  mount,
} from '@vue/test-utils'

import DashboardView
  from '@/views/surveyor/DashboardView.vue'

import ScanKtpView
  from '@/views/surveyor/ScanKtpView.vue'

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
  StoreSurveyorTransactionPayload,
  SurveyorEWarung,
  SurveyorNikLookupResult,
  SurveyorTransaction,
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

vi.mock(
  '@/services/surveyorWorkspaceService',
  () => ({
    surveyorWorkspaceService: {
      getContext:
        vi.fn<
          () => Promise<SurveyorWorkspaceContext>
        >(),

      getActiveEWarungs:
        vi.fn<
          () => Promise<SurveyorEWarung[]>
        >(),

      lookupNik:
        vi.fn<
          (
            nik: string,
          ) => Promise<SurveyorNikLookupResult>
        >(),

      storeTransaction:
        vi.fn<
          (
            payload:
              StoreSurveyorTransactionPayload,
          ) => Promise<SurveyorTransaction>
        >(),
    },
  }),
)

const getContextMock =
  vi.mocked(
    surveyorWorkspaceService
      .getContext,
  )

const getActiveEWarungsMock =
  vi.mocked(
    surveyorWorkspaceService
      .getActiveEWarungs,
  )

const lookupNikMock =
  vi.mocked(
    surveyorWorkspaceService
      .lookupNik,
  )

const storeTransactionMock =
  vi.mocked(
    surveyorWorkspaceService
      .storeTransaction,
  )

const context:
  SurveyorWorkspaceContext = {
    surveyor: {
      id: 10,
      name: 'Andi Saputra',
      username: 'andi.surveyor',
    },

    period: {
      id: 5,
      code: 'BPNT-2026-08',
      name: 'BPNT Agustus 2026',
      year: 2026,
    },

    assignment: {
      id: 20,

      kecamatan: {
        id: 3,
        name: 'Kranggan',
      },

      kelurahan: {
        id: 15,
        name: 'Jagalan',
      },
    },

    kpm_count: 245,
  }

const activeEWarung:
  SurveyorEWarung = {
    id: 7,
    name: 'E-Warung Makmur',
    is_active: true,
    created_at: null,
    updated_at: null,
  }

const inactiveEWarung:
  SurveyorEWarung = {
    id: 8,
    name: 'E-Warung Nonaktif',
    is_active: false,
    created_at: null,
    updated_at: null,
  }

const lookupResult:
  SurveyorNikLookupResult = {
    participant: {
      id: 101,

      kpm: {
        id: 201,
        nik: '3576********0001',
        full_name: 'Budi Santoso',
        birth_place: 'Mojokerto',
        birth_date: '1975-01-01',
        address: 'Jalan Merdeka Nomor 1',
        rt: '001',
        rw: '002',
      },

      wilayah: {
        kelurahan: {
          id: 15,
          name: 'Jagalan',
        },

        kecamatan: {
          id: 3,
          name: 'Kranggan',
        },
      },

      saldo_bpnt: 200000,
    },

    scope: {
      outside_assignment: false,
      label: 'KPM KELURAHAN Jagalan',

      surveyor_kelurahan: {
        id: 15,
        name: 'Jagalan',
      },
    },
  }

const transaction:
  SurveyorTransaction = {
    id: 900,

    status: {
      code: 'transacted',
      label: 'Sudah Bertransaksi',
    },

    period:
      context.period!,

    participant:
      lookupResult.participant,

    e_warung: {
      id: activeEWarung.id,
      name: activeEWarung.name,
    },

    surveyor: {
      id: context.surveyor.id,
      name: context.surveyor.name,
    },

    outside_assignment: false,

    transacted_at:
      '2026-08-24T14:00:00+07:00',
  }

describe(
  'Surveyor transaction flow',
  () => {
    beforeEach(() => {
      vi.clearAllMocks()

      window.localStorage.clear()

      useSurveyorEWarungSelection()
        .clearSelectedEWarung()

      const pinia =
        createPinia()

      setActivePinia(
        pinia,
      )

      useAuthStore(
        pinia,
      ).$patch({
        initialized: true,

        user: {
          id: 10,
          name: 'Andi Saputra',
          username: 'andi.surveyor',
          email: null,
          phone: null,
          role: 'surveyor',
          role_label: 'Surveyor',
          is_active: true,
          last_login_at: null,
          modules: [],
        },
      })

      getContextMock
        .mockResolvedValue(
          context,
        )

      getActiveEWarungsMock
        .mockResolvedValue([
          activeEWarung,
          inactiveEWarung,
        ])

      lookupNikMock
        .mockResolvedValue(
          lookupResult,
        )

      storeTransactionMock
        .mockResolvedValue(
          transaction,
        )
    })

    it(
      'only shows active E-Warung and persists dashboard selection',
      async () => {
        const wrapper =
          mount(
            DashboardView,
            {
              global: {
                stubs: {
                  RouterLink: {
                    template:
                      '<a><slot /></a>',
                  },
                },
              },
            },
          )

        await flushPromises()

        const select =
          wrapper.get<HTMLSelectElement>(
            '[data-testid="e-warung-select"]',
          )

        expect(
          select.text(),
        ).toContain(
          'E-Warung Makmur',
        )

        expect(
          select.text(),
        ).not.toContain(
          'E-Warung Nonaktif',
        )

        await select.setValue(
          '7',
        )

        expect(
          wrapper.text(),
        ).toContain(
          'E-Warung Makmur',
        )

        expect(
          window.localStorage
            .getItem(
              'sipbpnt.surveyor.e-warung.v1',
            ),
        ).toContain(
          '"e_warung_id":7',
        )
      },
    )

    it(
      'uses dashboard E-Warung when marking KPM as transacted',
      async () => {
        const selection =
          useSurveyorEWarungSelection()

        selection
          .synchronizeEWarungs(
            context.surveyor.id,
            [
              activeEWarung,
            ],
          )

        selection
          .selectEWarung(
            context.surveyor.id,
            activeEWarung.id,
          )

        const wrapper =
          mount(
            ScanKtpView,
            {
              global: {
                stubs: {
                  RouterLink: {
                    template:
                      '<a><slot /></a>',
                  },
                },
              },
            },
          )

        await flushPromises()

        await wrapper
          .get<HTMLInputElement>(
            '[data-testid="nik-input"]',
          )
          .setValue(
            '3576010101010001',
          )

        await wrapper
          .get('form')
          .trigger(
            'submit',
          )

        await flushPromises()

        await wrapper
          .get(
            '[data-testid="transaction-button"]',
          )
          .trigger(
            'click',
          )

        expect(
          wrapper
            .find(
              '[data-testid="transaction-confirmation"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        await wrapper
          .get(
            '[data-testid="confirm-transaction"]',
          )
          .trigger(
            'click',
          )

        await flushPromises()

        expect(
          storeTransactionMock,
        ).toHaveBeenCalledWith({
          nik:
            '3576010101010001',

          e_warung_id:
            7,
        })

        expect(
          wrapper
            .find(
              '[data-testid="transaction-success"]',
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