import {
  http,
} from '@/services/http'

import type {
  SurveyorMonitoringReport,
  SurveyorMonitoringReportPdf,
  SurveyorMonitoringReportResponse,
  UpdateSurveyorMonitoringReportPayload,
} from '@/types/surveyorMonitoringReport'

class SurveyorMonitoringReportService {
  async getReport():
    Promise<SurveyorMonitoringReport> {
    const response =
      await http.get<
        SurveyorMonitoringReportResponse
      >(
        '/api/v1/surveyor/monitoring-report',
      )

    return response.data.data
  }

  async updateReport(
    payload:
      UpdateSurveyorMonitoringReportPayload,
  ): Promise<SurveyorMonitoringReport> {
    const response =
      await http.put<
        SurveyorMonitoringReportResponse
      >(
        '/api/v1/surveyor/monitoring-report',
        payload,
      )

    return response.data.data
  }

  async downloadPdf():
    Promise<SurveyorMonitoringReportPdf> {
    const response =
      await http.get<Blob>(
        '/api/v1/surveyor/monitoring-report/pdf',
        {
          responseType:
            'blob',
        },
      )

    const disposition =
      String(
        response.headers[
          'content-disposition'
        ]
        ?? '',
      )

    const match =
      disposition.match(
        /filename="?([^";]+)"?/i,
      )

    return {
      blob:
        response.data,

      filename:
        match?.[1]
        ?? 'laporan-monitoring-surveyor.pdf',
    }
  }
}

export const surveyorMonitoringReportService =
  new SurveyorMonitoringReportService()