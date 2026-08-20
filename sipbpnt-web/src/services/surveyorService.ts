import {
  http,
} from '@/services/http'

import type {
  SurveyorCreatePayload,
  SurveyorListResponse,
  SurveyorOptionsResponse,
  SurveyorResponse,
  SurveyorUpdatePayload,
} from '@/types/surveyor'

class SurveyorService {
  async getAll():
    Promise<SurveyorListResponse> {
    const response =
      await http.get<
        SurveyorListResponse
      >(
        '/api/v1/admin/surveyors',
      )

    return response.data
  }

  async create(
    payload:
      SurveyorCreatePayload,
  ): Promise<SurveyorResponse> {
    const response =
      await http.post<
        SurveyorResponse
      >(
        '/api/v1/admin/surveyors',
        payload,
      )

    return response.data
  }

  async update(
    id: number,
    payload:
      SurveyorUpdatePayload,
  ): Promise<SurveyorResponse> {
    const response =
      await http.patch<
        SurveyorResponse
      >(
        `/api/v1/admin/surveyors/${id}`,
        payload,
      )

    return response.data
  }

  async setStatus(
    id: number,
    isActive: boolean,
  ): Promise<SurveyorResponse> {
    const response =
      await http.patch<
        SurveyorResponse
      >(
        `/api/v1/admin/surveyors/${id}/status`,
        {
          is_active:
            isActive,
        },
      )

    return response.data
  }

  async getActiveOptions():
    Promise<SurveyorOptionsResponse> {
    const response =
      await http.get<
        SurveyorOptionsResponse
      >(
        '/api/v1/surveyors/options',
      )

    return response.data
  }
}

export const surveyorService =
  new SurveyorService()