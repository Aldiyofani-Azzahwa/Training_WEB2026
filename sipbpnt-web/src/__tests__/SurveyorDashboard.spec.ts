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
  useSurveyorEWarungSelection,
} from '@/services/surveyorEWarungSelection'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  SurveyorEWarung,
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
      245,
  }

describe(
  'Surveyor Dashboard',
  () => {
    let pinia:
      Pinia

    beforeEach(() => {
      vi.clearAllMocks()

      window.localStorage.clear()

      useSurveyorEWarungSelection()
        .clearSelectedEWarung()

      getActiveEWarungsMock
        .mockResolvedValue([
          activeEWarung,
        ])

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
      'shows active period, assignment, and active E-Warung data',
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
              '[data-testid="e-warung-card"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper
            .find(
              '[data-testid="e-warung-select"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'E-Warung Makmur',
        )

        expect(
          getActiveEWarungsMock,
        ).toHaveBeenCalledTimes(
          1,
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

        wrapper.unmount()
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

        expect(
          getActiveEWarungsMock,
        ).not.toHaveBeenCalled()

        wrapper.unmount()
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

        expect(
          getActiveEWarungsMock,
        ).not.toHaveBeenCalled()

        wrapper.unmount()
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
          getActiveEWarungsMock,
        ).toHaveBeenCalledTimes(
          1,
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

        wrapper.unmount()
      },
    )

    it(
      'keeps the selected E-Warung after dashboard remount',
      async () => {
        getContextMock
          .mockResolvedValue(
            normalContext,
          )

        const firstWrapper =
          mountDashboard()

        await flushPromises()

        const firstSelect =
          firstWrapper
            .get<HTMLSelectElement>(
              '[data-testid="e-warung-select"]',
            )

        await firstSelect.setValue(
          String(
            activeEWarung.id,
          ),
        )

        expect(
          useSurveyorEWarungSelection()
            .selectedEWarung
            .value
            ?.id,
        ).toBe(
          activeEWarung.id,
        )

        expect(
          firstSelect
            .element
            .value,
        ).toBe(
          String(
            activeEWarung.id,
          ),
        )

        firstWrapper.unmount()

        const secondWrapper =
          mountDashboard()

        await flushPromises()

        const secondSelect =
          secondWrapper
            .get<HTMLSelectElement>(
              '[data-testid="e-warung-select"]',
            )

        expect(
          secondSelect
            .element
            .value,
        ).toBe(
          String(
            activeEWarung.id,
          ),
        )

        expect(
          secondWrapper.text(),
        ).toContain(
          'E-Warung Makmur',
        )

        secondWrapper.unmount()
      },
    )

    it(
      'does not display inactive E-Warung',
      async () => {
        getContextMock
          .mockResolvedValue(
            normalContext,
          )

        getActiveEWarungsMock
          .mockResolvedValue([
            activeEWarung,

            {
              id:
                8,

              name:
                'E-Warung Nonaktif',

              is_active:
                false,

              created_at:
                null,

              updated_at:
                null,
            },
          ])

        const wrapper =
          mountDashboard()

        await flushPromises()

        expect(
          wrapper.text(),
        ).toContain(
          'E-Warung Makmur',
        )

        expect(
          wrapper.text(),
        ).not.toContain(
          'E-Warung Nonaktif',
        )

        wrapper.unmount()
      },
    )
  },
)