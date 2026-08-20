import {
  http,
} from '@/services/http'

import type {
  SurveyorAssignmentDeleteResponse,
  SurveyorAssignmentListResponse,
  SurveyorAssignmentPayload,
  SurveyorAssignmentResponse,
} from '@/types/surveyorAssignment'

class SurveyorAssignmentService {
  async getByPeriod(
    periodId: number,
  ): Promise<SurveyorAssignmentListResponse> {
    const response =
      await http.get<
        SurveyorAssignmentListResponse
      >(
        '/api/v1/manager/surveyor-assignments',
        {
          params: {
            period_id:
              periodId,
          },
        },
      )

    return response.data
  }

  async assign(
    payload:
      SurveyorAssignmentPayload,
  ): Promise<SurveyorAssignmentResponse> {
    const response =
      await http.put<
        SurveyorAssignmentResponse
      >(
        '/api/v1/manager/surveyor-assignments',
        payload,
      )

    return response.data
  }

  async remove(
    assignmentId: number,
  ): Promise<SurveyorAssignmentDeleteResponse> {
    const response =
      await http.delete<
        SurveyorAssignmentDeleteResponse
      >(
        `/api/v1/manager/surveyor-assignments/${assignmentId}`,
      )

    return response.data
  }
}

export const surveyorAssignmentService =
  new SurveyorAssignmentService()