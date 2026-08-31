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
  type Pinia,
} from 'pinia'

import {
  flushPromises,
  mount,
} from '@vue/test-utils'

import {
  bnbaService,
} from '@/services/bnbaService'

import {
  bpntReportService,
} from '@/services/bpntReportService'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  BpntReport,
  BpntReportSnapshot,
} from '@/types/bpntReport'

import type {
  BpntPeriod,
} from '@/types/bnba'

import BpntReportView
  from '@/views/reports/BpntReportView.vue'

vi.mock(
  '@/services/bnbaService',
  () => ({
    bnbaService: {
      getActivePeriod:
        vi.fn<
          () => Promise<BpntPeriod | null>
        >(),
    },
  }),
)

vi.mock(
  '@/services/bpntReportService',
  () => ({
    bpntReportService: {
      getReport:
        vi.fn<
          (
            periodId: number,
          ) => Promise<BpntReport>
        >(),

      excelUrl:
        vi.fn<
          (
            periodId: number,
          ) => string
        >(),
    },
  }),
)

const getActivePeriodMock =
  vi.mocked(
    bnbaService
      .getActivePeriod,
  )

const getReportMock =
  vi.mocked(
    bpntReportService
      .getReport,
  )

const excelUrlMock =
  vi.mocked(
    bpntReportService
      .excelUrl,
  )

const activePeriod:
  BpntPeriod = {
    id:
      5,

    code:
      'BPNT-2026-08',

    name:
      'BPNT Agustus 2026',

    year:
      2026,

    is_active:
      true,

    imports_count:
      1,

    participants_count:
      2,

    assignments_count:
      1,

    can_activate:
      false,

    can_delete:
      false,

    can_edit_year:
      false,

    can_delete_bnba:
      false,

    bnba:
      null,

    created_at:
      '2026-08-01T08:00:00+07:00',

    updated_at:
      '2026-08-26T13:40:00+07:00',
  }

const snapshot:
  BpntReportSnapshot = {
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

    generated_at:
      '2026-08-26T13:40:00+07:00',

    summary: {
      total_kpm:
        2,

      transacted:
        1,

      pending:
        0,

      active_verifications:
        1,

      deceased:
        0,

      moved_domicile:
        0,

      not_claimed:
        1,

      completion_percentage:
        100,
    },

    wilayah: [
      {
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

        total_kpm:
          2,

        transacted:
          1,

        pending:
          0,

        deceased:
          0,

        moved_domicile:
          0,

        not_claimed:
          1,
      },
    ],

    surveyors: [
      {
        id:
          10,

        name:
          'Andi Saputra',

        username:
          'andi.surveyor',

        assignment: {
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

        transactions:
          1,

        verifications:
          1,
      },
    ],

    e_warungs: [
      {
        id:
          8,

        name:
          'E-Warung Makmur',

        transactions:
          1,
      },
    ],

    participants: [
      {
        participant_id:
          100,

        nik:
          '3576********0001',

        full_name:
          'Budi Santoso',

        address:
          'Jalan Merdeka Nomor 1',

        rt:
          '001',

        rw:
          '002',

        wilayah: {
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

        resolution: {
          code:
            'transacted',

          label:
            'Sudah Transaksi',

          reason:
            null,
        },

        surveyor: {
          id:
            10,

          name:
            'Andi Saputra',
        },

        e_warung: {
          id:
            8,

          name:
            'E-Warung Makmur',
        },

        resolved_at:
          '2026-08-26T13:20:00+07:00',
      },
    ],
  }

const draftReport:
  BpntReport = {
    id:
      null,

    period: {
      ...snapshot.period,

      is_active:
        true,
    },

    status: {
      code:
        'draft',

      label:
        'Draft',
    },

    summary:
      snapshot.summary,

    can_finalize:
      true,

    blocking_reason:
      null,

    finalized_by:
      null,

    finalized_at:
      null,

    snapshot,
  }

const finalReport:
  BpntReport = {
    ...draftReport,

    id:
      30,

    status: {
      code:
        'final',

      label:
        'Final',
    },

    can_finalize:
      false,

    finalized_by: {
      id:
        2,

      name:
        'Manager BPNT',
    },

    finalized_at:
      '2026-08-26T13:40:00+07:00',
  }

describe(
  'BPNT Report',
  () => {
    let pinia:
      Pinia

    beforeEach(() => {
      vi.clearAllMocks()

      pinia =
        createPinia()

      setActivePinia(
        pinia,
      )

      useAuthStore(
        pinia,
      ).$patch({
        initialized:
          true,

        user: {
          id:
            2,

          name:
            'Manager BPNT',

          username:
            'manager.bpnt',

          email:
            null,

          phone:
            null,

          role:
            'manager',

          role_label:
            'Manager',

          is_active:
            true,

          last_login_at:
            null,

          modules:
            [],
        },
      })

      getActivePeriodMock
        .mockResolvedValue(
          activePeriod,
        )

      getReportMock
        .mockResolvedValue(
          draftReport,
        )

      excelUrlMock
        .mockReturnValue(
          '/api/v1/reports/5/excel',
        )
    })

    function mountReport() {
      return mount(
        BpntReportView,
        {
          global: {
            plugins: [
              pinia,
            ],
          },
        },
      )
    }

    it(
      'loads only the active-period report without a period selector',
      async () => {
        const wrapper =
          mountReport()

        await flushPromises()

        expect(
          getActivePeriodMock,
        ).toHaveBeenCalledTimes(1)

        expect(
          getReportMock,
        ).toHaveBeenCalledWith(5)

        expect(
          wrapper
            .find(
              '[data-testid="report-period-select"]',
            )
            .exists(),
        ).toBe(false)

        expect(
          wrapper.text(),
        ).toContain(
          'BPNT Agustus 2026',
        )

        expect(
          wrapper
            .get(
              '[data-testid="report-status"]',
            )
            .text(),
        ).toContain(
          'Draft',
        )

        expect(
          wrapper.text(),
        ).not.toContain(
          'Unduh Excel',
        )

        expect(
  wrapper
    .find(
      '[data-testid="report-finalize-button"]',
    )
    .exists(),
).toBe(true)
      },
    )

    it(
      'shows final report to Admin without Manager validation action',
      async () => {
        useAuthStore(
          pinia,
        ).$patch({
          user: {
            id:
              3,

            name:
              'Admin Dinsos',

            username:
              'admin.dinsos',

            email:
              null,

            phone:
              null,

            role:
              'admin_dinsos',

            role_label:
              'Admin Dinsos',

            is_active:
              true,

            last_login_at:
              null,

            modules:
              [],
          },
        })

        getReportMock
          .mockResolvedValue(
            finalReport,
          )

        const wrapper =
          mountReport()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="report-final-information"]',
            )
            .exists(),
        ).toBe(true)

        expect(
          wrapper
            .find(
              '[data-testid="report-finalize-button"]',
            )
            .exists(),
        ).toBe(false)
      },
    )

    it(
      'shows an empty state when there is no active period',
      async () => {
        getActivePeriodMock
          .mockResolvedValue(null)

        const wrapper =
          mountReport()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="report-no-active-period"]',
            )
            .exists(),
        ).toBe(true)

        expect(
          getReportMock,
        ).not.toHaveBeenCalled()
      },
    )

    it(
      'shows at most 50 KPM on each detail page',
      async () => {
        const baseParticipant =
          snapshot.participants[0]

        if (!baseParticipant) {
          throw new Error(
            'Fixture participant is required.',
          )
        }

        const participants =
          Array.from(
            {
              length:
                51,
            },
            (_, index) => ({
              ...baseParticipant,

              participant_id:
                index + 1,

              full_name:
                `KPM ${String(
                  index + 1,
                ).padStart(
                  2,
                  '0',
                )}`,
            }),
          )

        getReportMock
          .mockResolvedValue({
            ...finalReport,

            summary: {
              ...finalReport.summary,

              total_kpm:
                participants.length,
            },

            snapshot: {
              ...snapshot,

              participants,
            },
          })

        const wrapper =
          mountReport()

        await flushPromises()

        expect(
          wrapper.findAll(
            '[data-testid="report-participant-row"]',
          ),
        ).toHaveLength(50)

        expect(
          wrapper
            .get(
              '[data-testid="report-participant-range"]',
            )
            .text(),
        ).toContain(
          'Menampilkan 1–50 dari 51 KPM',
        )

        await wrapper
          .get(
            '[aria-label="Halaman KPM berikutnya"]',
          )
          .trigger('click')

        expect(
          wrapper.findAll(
            '[data-testid="report-participant-row"]',
          ),
        ).toHaveLength(1)

        expect(
          wrapper
            .get(
              '[data-testid="report-participant-range"]',
            )
            .text(),
        ).toContain(
          'Menampilkan 51–51 dari 51 KPM',
        )
      },
    )
  },
)