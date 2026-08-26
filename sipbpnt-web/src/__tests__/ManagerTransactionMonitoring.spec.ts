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

import {
  managerTransactionMonitoringService,
} from '@/services/managerTransactionMonitoringService'

import type {
  ManagerTransactionMonitoringQuery,
  ManagerTransactionMonitoringResponse,
} from '@/types/managerTransactionMonitoring'

import TransactionMonitoringView
  from '@/views/manager/TransactionMonitoringView.vue'

vi.mock(
  '@/services/managerTransactionMonitoringService',
  () => ({
    managerTransactionMonitoringService: {
      getMonitoring:
        vi.fn<
          (
            query?: ManagerTransactionMonitoringQuery,
          ) => Promise<ManagerTransactionMonitoringResponse>
        >(),
    },
  }),
)

const getMonitoringMock =
  vi.mocked(
    managerTransactionMonitoringService
      .getMonitoring,
  )

const normalResponse:
  ManagerTransactionMonitoringResponse = {
    data: {
      period: {
        id: 5,
        code: 'BPNT-2026-08',
        name: 'BPNT Agustus 2026',
        year: 2026,
      },

      summary: {
        total_kpm: 245,
        transacted: 120,
        pending: 115,
        active_verifications: 10,
        deceased: 3,
        moved_domicile: 2,
        not_claimed: 5,
        outside_assignment: 4,
        completion_percentage: 53.06,
      },

      breakdowns: {
        kecamatans: [
          {
            kecamatan: {
              id: 3,
              name: 'Kranggan',
            },
            total_kpm: 245,
            transacted: 120,
            active_verifications: 10,
            pending: 115,
          },
        ],

        kelurahans: [
          {
            kecamatan: {
              id: 3,
              name: 'Kranggan',
            },
            kelurahan: {
              id: 15,
              name: 'Jagalan',
            },
            total_kpm: 245,
            transacted: 120,
            active_verifications: 10,
            pending: 115,
          },
        ],

        e_warungs: [
          {
            id: 8,
            name: 'E-Warung Makmur',
            is_active: true,
            transactions: 120,
          },
        ],

        surveyors: [
          {
            id: 10,
            name: 'Andi Saputra',
            username: 'andi.surveyor',
            assignment: {
              kecamatan: {
                id: 3,
                name: 'Kranggan',
              },
              kelurahan: {
                id: 15,
                name: 'Jagalan',
              },
            },
            transactions: 120,
            outside_assignment: 4,
          },
        ],
      },

      transactions: [
        {
          id: 90,
          participant: {
            id: 100,
            kpm: {
              id: 80,
              nik: '3576********0001',
              full_name: 'Budi Santoso',
              address: 'Jl. Contoh',
              rt: '001',
              rw: '002',
            },
            wilayah: {
              kecamatan: {
                id: 3,
                name: 'Kranggan',
              },
              kelurahan: {
                id: 15,
                name: 'Jagalan',
              },
            },
          },

          surveyor: {
            id: 10,
            name: 'Andi Saputra',
            username: 'andi.surveyor',
            assignment: {
              kecamatan: {
                id: 3,
                name: 'Kranggan',
              },
              kelurahan: {
                id: 15,
                name: 'Jagalan',
              },
            },
          },

          e_warung: {
            id: 8,
            name: 'E-Warung Makmur',
            is_active: true,
          },

          outside_assignment: false,
          transacted_at:
            '2026-08-26T10:00:00+07:00',
        },
      ],
    },

    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 1,
    },
  }

describe(
  'Manager Transaction Monitoring',
  () => {
    beforeEach(() => {
      vi.clearAllMocks()
    })

    it(
      'shows active-period summary and transaction data',
      async () => {
        getMonitoringMock
          .mockResolvedValue(
            normalResponse,
          )

        const wrapper =
          mount(
            TransactionMonitoringView,
          )

        await flushPromises()

        expect(
          getMonitoringMock,
        ).toHaveBeenCalledWith({
          page: 1,
          per_page: 20,
        })

        expect(
          wrapper.text(),
        ).toContain(
          'BPNT Agustus 2026',
        )

        expect(
          wrapper
            .get(
              '[data-testid="summary-total-kpm"]',
            )
            .text(),
        ).toBe('245')

        expect(
          wrapper
            .get(
              '[data-testid="summary-transacted"]',
            )
            .text(),
        ).toBe('120')

        expect(
          wrapper.text(),
        ).toContain(
          'Budi Santoso',
        )

        expect(
          wrapper.text(),
        ).toContain(
          'E-Warung Makmur',
        )
      },
    )

    it(
      'shows a safe state when no active period exists',
      async () => {
        getMonitoringMock
          .mockResolvedValue({
            data: {
              period: null,

              summary: {
                total_kpm: 0,
                transacted: 0,
                pending: 0,
                active_verifications: 0,
                deceased: 0,
                moved_domicile: 0,
                not_claimed: 0,
                outside_assignment: 0,
                completion_percentage: 0,
              },

              breakdowns: {
                kecamatans: [],
                kelurahans: [],
                e_warungs: [],
                surveyors: [],
              },

              transactions: [],
            },

            meta: {
              current_page: 1,
              last_page: 1,
              per_page: 20,
              total: 0,
            },
          })

        const wrapper =
          mount(
            TransactionMonitoringView,
          )

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="no-active-period"]',
            )
            .exists(),
        ).toBe(true)

        expect(
          wrapper.text(),
        ).toContain(
          'Belum ada periode aktif',
        )
      },
    )

    it(
      'shows an error state and retries loading',
      async () => {
        getMonitoringMock
          .mockRejectedValueOnce(
            new Error(
              'Network error',
            ),
          )
          .mockResolvedValueOnce(
            normalResponse,
          )

        const wrapper =
          mount(
            TransactionMonitoringView,
          )

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="monitoring-error"]',
            )
            .exists(),
        ).toBe(true)

        await wrapper
          .get(
            '[data-testid="monitoring-retry"]',
          )
          .trigger('click')

        await flushPromises()

        expect(
          getMonitoringMock,
        ).toHaveBeenCalledTimes(2)

        expect(
          wrapper.text(),
        ).toContain(
          'Budi Santoso',
        )
      },
    )
  },
)