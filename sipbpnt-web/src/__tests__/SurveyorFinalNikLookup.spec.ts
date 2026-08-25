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

import ScanKtpView
  from '@/views/surveyor/ScanKtpView.vue'

import {
  surveyorWorkspaceService,
} from '@/services/surveyorWorkspaceService'

import type {
  SurveyorEWarung,
  SurveyorNikLookupResult,
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

const context:
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
      245,
  }

const transactedResult:
  SurveyorNikLookupResult = {
    participant: {
      id:
        101,

      kpm: {
        id:
          201,

        nik:
          '3576********0001',

        full_name:
          'Budi Santoso',

        birth_place:
          'Mojokerto',

        birth_date:
          '1975-01-01',

        address:
          'Jalan Merdeka Nomor 1',

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
          'transacted',

        label:
          'Sudah Bertransaksi',

        is_final:
          true,

        can_record_transaction:
          false,
      },
    },

    scope: {
      outside_assignment:
        false,

      label:
        'KPM Wilayah Jagalan',

      surveyor_kelurahan: {
        id:
          15,

        name:
          'Jagalan',
      },
    },
  }

describe(
  'Surveyor final exact NIK lookup',
  () => {
    beforeEach(() => {
      vi.clearAllMocks()

      getContextMock
        .mockResolvedValue(
          context,
        )

      getActiveEWarungsMock
        .mockResolvedValue([])

      lookupNikMock
        .mockResolvedValue(
          transactedResult,
        )
    })

    it(
      'hides transaction action for an already transacted KPM',
      async () => {
        const wrapper =
          mount(
            ScanKtpView,
            {
              global: {
                stubs: {
                  RouterLink:
                    true,
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

        expect(
          wrapper
            .find(
              '[data-testid="participant-final-status"]',
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

        expect(
          wrapper
            .find(
              '[data-testid="transaction-button"]',
            )
            .exists(),
        ).toBe(
          false,
        )
      },
    )
  },
)