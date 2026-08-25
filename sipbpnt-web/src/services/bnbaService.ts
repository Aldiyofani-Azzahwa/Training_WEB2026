import type {
  AxiosProgressEvent,
} from 'axios'

import {
  http,
} from '@/services/http'

import type {
  ActiveBpntPeriodResponse,
  ApiMessageResponse,
  BnbaHistoryParams,
  BnbaImport,
  BnbaImportHistoryResponse,
  BnbaImportResponse,
  BnbaParticipantFilterOptions,
  BnbaParticipantFilterOptionsResponse,
  BnbaParticipantFilters,
  BnbaParticipantListResponse,
  BnbaPreviewParams,
  BnbaPreviewResponse,
  BpntPeriod,
  BpntPeriodListResponse,
  BpntPeriodResponse,
  CreateBpntPeriodPayload,
  UpdateBpntPeriodPayload,
} from '@/types/bnba'

const BASE_PATH =
  '/api/v1'

class BnbaService {
  async getPeriods():
    Promise<BpntPeriod[]> {
    const response =
      await http.get<
        BpntPeriodListResponse
      >(
        `${BASE_PATH}/bpnt-periods`,
      )

    return response.data.data
  }

  async getActivePeriod():
    Promise<BpntPeriod | null> {
    const response =
      await http.get<
        ActiveBpntPeriodResponse
      >(
        `${BASE_PATH}/bpnt-periods/active`,
      )

    return response.data.data
  }

  async createPeriod(
    payload:
      CreateBpntPeriodPayload,
  ): Promise<BpntPeriod> {
    const response =
      await http.post<
        BpntPeriodResponse
      >(
        `${BASE_PATH}/bpnt-periods`,
        payload,
      )

    return response.data.data
  }

  async updatePeriod(
    periodId: number,
    payload:
      UpdateBpntPeriodPayload,
  ): Promise<BpntPeriod> {
    const response =
      await http.patch<
        BpntPeriodResponse
      >(
        `${BASE_PATH}/bpnt-periods/${periodId}`,
        payload,
      )

    return response.data.data
  }

  async activatePeriod(
    periodId: number,
  ): Promise<BpntPeriod> {
    const response =
      await http.put<
        BpntPeriodResponse
      >(
        `${BASE_PATH}/bpnt-periods/${periodId}/activate`,
      )

    return response.data.data
  }

  async deactivatePeriod(
    periodId: number,
  ): Promise<BpntPeriod> {
    const response =
      await http.put<
        BpntPeriodResponse
      >(
        `${BASE_PATH}/bpnt-periods/${periodId}/deactivate`,
      )

    return response.data.data
  }

  async deletePeriod(
    periodId: number,
  ): Promise<void> {
    await http.delete<
      ApiMessageResponse
    >(
      `${BASE_PATH}/bpnt-periods/${periodId}`,
    )
  }

  async deletePeriodBnba(
    periodId: number,
  ): Promise<void> {
    await http.delete<
      ApiMessageResponse
    >(
      `${BASE_PATH}/bpnt-periods/${periodId}/bnba`,
    )
  }

  async getImportHistory(
    params:
      BnbaHistoryParams = {},
  ): Promise<
    BnbaImportHistoryResponse
  > {
    const response =
      await http.get<
        BnbaImportHistoryResponse
      >(
        `${BASE_PATH}/bnba/imports`,
        {
          params,
        },
      )

    return response.data
  }

  async upload(
    periodId: number,
    file: File,
    onProgress?: (
      progress: number,
    ) => void,
  ): Promise<BnbaImport> {
    const formData =
      new FormData()

    formData.append(
      'period_id',
      String(periodId),
    )

    formData.append(
      'file',
      file,
    )

    const response =
      await http.post<
        BnbaImportResponse
      >(
        `${BASE_PATH}/bnba/imports`,
        formData,
        {
          onUploadProgress: (
            event:
              AxiosProgressEvent,
          ) => {
            if (
              !onProgress
              ||
              !event.total
            ) {
              return
            }

            const progress =
              Math.round(
                (
                  event.loaded
                  * 100
                )
                /
                event.total,
              )

            onProgress(
              Math.min(
                progress,
                100,
              ),
            )
          },
        },
      )

    return response.data.data
  }

  async getPreview(
    importId: number,
    params:
      BnbaPreviewParams = {},
  ): Promise<BnbaPreviewResponse> {
    const response =
      await http.get<
        BnbaPreviewResponse
      >(
        `${BASE_PATH}/bnba/imports/${importId}/preview`,
        {
          params,
        },
      )

    return response.data
  }

  async confirm(
    importId: number,
  ): Promise<BnbaImport> {
    const response =
      await http.post<
        BnbaImportResponse
      >(
        `${BASE_PATH}/bnba/imports/${importId}/confirm`,
      )

    return response.data.data
  }

  async getParticipants(
    params:
      BnbaParticipantFilters,
  ): Promise<
    BnbaParticipantListResponse
  > {
    const response =
      await http.get<
        BnbaParticipantListResponse
      >(
        `${BASE_PATH}/bnba/participants`,
        {
          params,
        },
      )

    return response.data
  }

  async getParticipantFilterOptions(
    periodId: number,
  ): Promise<
    BnbaParticipantFilterOptions
  > {
    const response =
      await http.get<
        BnbaParticipantFilterOptionsResponse
      >(
        `${BASE_PATH}/bnba/participants/options`,
        {
          params: {
            period_id:
              periodId,
          },
        },
      )

    return response.data.data
  }
}

export const bnbaService =
  new BnbaService()