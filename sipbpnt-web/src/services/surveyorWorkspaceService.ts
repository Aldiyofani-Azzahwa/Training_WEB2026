import { http } from '@/services/http'

import type { ApiResponse } from '@/types/auth'

import type {
  KpmVerification,
  KpmVerificationResponse,
  StoreKpmVerificationPayload,
  StoreSurveyorTransactionPayload,
  SurveyorActivityQuery,
  SurveyorActivityResponse,
  SurveyorEWarung,
  SurveyorEWarungResponse,
  SurveyorNikLookupResponse,
  SurveyorNikLookupResult,
  SurveyorParticipantQuery,
  SurveyorParticipantResponse,
  SurveyorTransaction,
  SurveyorTransactionResponse,
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

export const surveyorWorkspaceService = {
  async getContext(): Promise<SurveyorWorkspaceContext> {
    const response =
      await http.get<ApiResponse<SurveyorWorkspaceContext>>(
        '/api/v1/surveyor/context',
      )

    return response.data.data
  },

  async getParticipants(
    query: SurveyorParticipantQuery = {},
  ): Promise<SurveyorParticipantResponse> {
    const response =
      await http.get<SurveyorParticipantResponse>(
        '/api/v1/surveyor/participants',
        { params: query },
      )

    return response.data
  },

  async getPendingParticipants(
    query: SurveyorParticipantQuery = {},
  ): Promise<SurveyorParticipantResponse> {
    const response =
      await http.get<SurveyorParticipantResponse>(
        '/api/v1/surveyor/pending-participants',
        { params: query },
      )

    return response.data
  },

  async lookupNik(
    nik: string,
  ): Promise<SurveyorNikLookupResult> {
    const normalizedNik =
      nik.replace(
        /\D+/g,
        '',
      )

    const response =
      await http.post<SurveyorNikLookupResponse>(
        '/api/v1/surveyor/lookup-nik',
        {
          nik:
            normalizedNik,
        },
      )

    return response.data.data
  },

  async getActiveEWarungs(): Promise<SurveyorEWarung[]> {
    const response =
      await http.get<SurveyorEWarungResponse>(
        '/api/v1/surveyor/e-warungs',
      )

    return response.data.data.filter(
      (eWarung) =>
        eWarung.is_active,
    )
  },

  async storeTransaction(
    payload: StoreSurveyorTransactionPayload,
  ): Promise<SurveyorTransaction> {
    const participantIdentity =
      typeof payload.nik === 'string'
        ? {
            nik:
              payload.nik.replace(
                /\D+/g,
                '',
              ),
          }
        : {
            bpnt_participant_id:
              payload.bpnt_participant_id,
          }

    const response =
      await http.post<SurveyorTransactionResponse>(
        '/api/v1/surveyor/transactions',
        {
          ...participantIdentity,

          e_warung_id:
            payload.e_warung_id,
        },
      )

    return response.data.data
  },

  async storeVerification(
    payload: StoreKpmVerificationPayload,
  ): Promise<KpmVerification> {
    const response =
      await http.post<KpmVerificationResponse>(
        '/api/v1/surveyor/kpm-verifications',
        payload,
      )

    return response.data.data
  },

  async getActivityHistory(
    query: SurveyorActivityQuery = {},
  ): Promise<SurveyorActivityResponse> {
    const response =
      await http.get<SurveyorActivityResponse>(
        '/api/v1/surveyor/activity-history',
        {
          params:
            query,
        },
      )

    return response.data
  },
}