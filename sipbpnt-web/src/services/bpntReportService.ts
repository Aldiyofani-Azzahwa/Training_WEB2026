import {
  http,
} from '@/services/http'

import type {
  BpntReport,
  BpntReportDetailResponse,
  BpntReportListResponse,
} from '@/types/bpntReport'

class BpntReportService {
  async getReports():
    Promise<BpntReport[]> {
    const response =
      await http.get<
        BpntReportListResponse
      >(
        '/api/v1/reports',
      )

    return response.data.data
  }

  async getReport(
    periodId: number,
  ): Promise<BpntReport> {
    const response =
      await http.get<
        BpntReportDetailResponse
      >(
        `/api/v1/reports/${periodId}`,
      )

    return response.data.data
  }

  async finalize(
    periodId: number,
  ): Promise<BpntReport> {
    const response =
      await http.post<
        BpntReportDetailResponse
      >(
        `/api/v1/manager/reports/${periodId}/finalize`,
      )

    return response.data.data
  }

  excelUrl(
    periodId: number,
  ): string {
    return `/api/v1/reports/${periodId}/excel`
  }
}

export const bpntReportService =
  new BpntReportService()
