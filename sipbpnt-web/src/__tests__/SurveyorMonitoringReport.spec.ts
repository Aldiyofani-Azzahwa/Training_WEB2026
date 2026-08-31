import {
  flushPromises,
  mount,
} from '@vue/test-utils'

import {
  afterEach,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'

import MonitoringReportView from '@/views/surveyor/MonitoringReportView.vue'

import {
  surveyorMonitoringReportService,
} from '@/services/surveyorMonitoringReportService'

import type {
  SurveyorMonitoringReport,
  SurveyorMonitoringReportPdf,
  UpdateSurveyorMonitoringReportPayload,
} from '@/types/surveyorMonitoringReport'

vi.mock(
  '@/services/surveyorMonitoringReportService',
  () => ({
    surveyorMonitoringReportService: {
      getReport:
        vi.fn<
          () =>
            Promise<SurveyorMonitoringReport>
        >(),

      updateReport:
        vi.fn<
          (
            payload:
              UpdateSurveyorMonitoringReportPayload,
          ) =>
            Promise<SurveyorMonitoringReport>
        >(),

      downloadPdf:
        vi.fn<
          () =>
            Promise<SurveyorMonitoringReportPdf>
        >(),
    },
  }),
)

const report:
  SurveyorMonitoringReport = {
  id: 1,

  period: {
    id: 11,

    code: 'BPNT-2026-03',

    name: 'Maret 2026',

    year: 2026,

    allocation_label:
      'Maret 2026',
  },

  surveyor: {
    id: 9,

    name: 'SURVEYOR TEST',
  },

  assignment: {
    id: 4,

    kecamatan: {
      id: 1,

      name:
        'PRAJURIT KULON',
    },

    kelurahan: {
      id: 2,

      name:
        'PRAJURIT KULON',
    },
  },

  editable: {
    commodities: [
      'Beras',
    ],

    social_officer_name:
      null,

    distribution_assistant_name:
      'SURVEYOR TEST',
  },

  summary: {
    total_kpm: 100,

    taking: 80,

    not_taking: 12,

    deceased: 2,

    moved_domicile: 3,

    not_claimed: 7,

    pending: 8,

    total_balance:
      15_000_000,

    e_warungs: [
      'E-Warung Satu',
    ],

    reason_summary: [
      {
        label: 'Meninggal',

        count: 2,
      },
    ],

    evaluation:
      'Evaluasi otomatis laporan.',
  },

  updated_at:
    '2026-08-30T10:00:00+07:00',
}

const createObjectUrlMock =
  vi.fn<
    (
      blob: Blob,
    ) => string
  >(
    () =>
      'blob:monitoring-report',
  )

const revokeObjectUrlMock =
  vi.fn<
    (
      url: string,
    ) => void
  >()

describe(
  'Surveyor monitoring report',
  () => {
    beforeEach(() => {
      vi.clearAllMocks()

      Object.defineProperty(
        URL,
        'createObjectURL',
        {
          configurable: true,

          value:
            createObjectUrlMock,
        },
      )

      Object.defineProperty(
        URL,
        'revokeObjectURL',
        {
          configurable: true,

          value:
            revokeObjectUrlMock,
        },
      )

      vi.spyOn(
        HTMLAnchorElement.prototype,
        'click',
      ).mockImplementation(
        () => undefined,
      )

      vi.mocked(
        surveyorMonitoringReportService.getReport,
      ).mockResolvedValue(
        structuredClone(
          report,
        ),
      )

      vi.mocked(
        surveyorMonitoringReportService.updateReport,
      ).mockResolvedValue(
        structuredClone(
          report,
        ),
      )

      vi.mocked(
        surveyorMonitoringReportService.downloadPdf,
      ).mockResolvedValue({
        blob: new Blob(
          [
            'pdf',
          ],
          {
            type:
              'application/pdf',
          },
        ),

        filename:
          'laporan-monitoring.pdf',
      })
    })

    afterEach(() => {
      vi.restoreAllMocks()
    })

    it(
      'loads automatic summary and does not show finalization',
      async () => {
        const wrapper =
          mount(
            MonitoringReportView,
          )

        await flushPromises()

        expect(
          wrapper.text(),
        ).toContain(
          'PRAJURIT KULON',
        )

        expect(
          wrapper.text(),
        ).toContain(
          '15.000.000',
        )

        expect(
          wrapper.text(),
        ).not.toContain(
          'Finalisasi',
        )
      },
    )

    it(
      'saves only commodities and reporter names before downloading',
      async () => {
        const wrapper =
          mount(
            MonitoringReportView,
          )

        await flushPromises()

        await wrapper
          .get(
            '[data-test="commodity-input"]',
          )
          .setValue(
            'Telur',
          )

        await wrapper
          .get(
            '[data-test="social-officer-name"]',
          )
          .setValue(
            'NAMA KASI',
          )

        await wrapper
          .get(
            '[data-test="download-report"]',
          )
          .trigger(
            'click',
          )

        await flushPromises()

        expect(
          surveyorMonitoringReportService.updateReport,
        ).toHaveBeenCalledWith({
          commodities: [
            'Telur',
          ],

          social_officer_name:
            'NAMA KASI',

          distribution_assistant_name:
            'SURVEYOR TEST',
        })

        expect(
          surveyorMonitoringReportService.downloadPdf,
        ).toHaveBeenCalledTimes(
          1,
        )
      },
    )
  },
)