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

import DashboardView
  from '@/views/surveyor/DashboardView.vue'

import {
  surveyorWorkspaceService,
} from '@/services/surveyorWorkspaceService'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
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
    },
  }),
)

const getContextMock =
  vi.mocked(
    surveyorWorkspaceService
      .getContext,
  )

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
      245,
  }

describe(
  'Surveyor Dashboard',
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

      const authStore =
        useAuthStore(
          pinia,
        )

      authStore.$patch({
        initialized:
          true,

        user: {
          id:
            10,

          name:
            'Andi Saputra',

          username:
            'andi.surveyor',

          email:
            null,

          phone:
            null,

          role:
            'surveyor',

          role_label:
            'Surveyor',

          is_active:
            true,

          last_login_at:
            null,

          modules:
            [],
        },
      })
    })

    function mountDashboard() {
      return mount(
        DashboardView,
        {
          global: {
            plugins: [
              pinia,
            ],

            stubs: {
              RouterLink: {
                props: [
                  'to',
                ],

                template:
                  '<a data-testid="router-link"><slot /></a>',
              },
            },
          },
        },
      )
    }

    it(
      'shows active period and assignment data',
      async () => {
        getContextMock
          .mockResolvedValue(
            normalContext,
          )

        const wrapper =
          mountDashboard()

        await flushPromises()

        expect(
          wrapper.text(),
        ).toContain(
          'Halo, Andi',
        )

        expect(
          wrapper.text(),
        ).toContain(
          'BPNT Agustus 2026',
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Kranggan',
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Jagalan',
        )

        expect(
          wrapper.text(),
        ).toContain(
          '245 KPM',
        )

        expect(
          wrapper
            .find(
              '[data-testid="scan-ktp-action"]',
            )
            .exists(),
        ).toBe(
          true,
        )
      },
    )

    it(
      'shows safe state when no active period exists',
      async () => {
        getContextMock
          .mockResolvedValue({
            ...normalContext,

            period:
              null,

            assignment:
              null,

            kpm_count:
              0,
          })

        const wrapper =
          mountDashboard()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="no-active-period"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Belum ada periode aktif',
        )

        expect(
          wrapper
            .find(
              '[data-testid="scan-ktp-action"]',
            )
            .exists(),
        ).toBe(
          false,
        )
      },
    )

    it(
      'shows safe state when Surveyor has no assignment',
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
          mountDashboard()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="active-period"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper
            .find(
              '[data-testid="no-assignment"]',
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
          wrapper
            .find(
              '[data-testid="scan-ktp-action"]',
            )
            .exists(),
        ).toBe(
          false,
        )
      },
    )

    it(
      'shows an error state and can retry loading',
      async () => {
        getContextMock
          .mockRejectedValueOnce(
            new Error(
              'Network error',
            ),
          )
          .mockResolvedValueOnce(
            normalContext,
          )

        const wrapper =
          mountDashboard()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="dashboard-error"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Dashboard belum dapat dimuat',
        )

        await wrapper
          .get(
            '[data-testid="dashboard-retry"]',
          )
          .trigger(
            'click',
          )

        await flushPromises()

        expect(
          getContextMock,
        ).toHaveBeenCalledTimes(
          2,
        )

        expect(
          wrapper
            .find(
              '[data-testid="surveyor-assignment"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Jagalan',
        )
      },
    )
  },
)