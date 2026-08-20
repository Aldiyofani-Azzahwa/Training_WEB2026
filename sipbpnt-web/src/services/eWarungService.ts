import {
  http,
} from '@/services/http'

import type {
  EWarungCreatePayload,
  EWarungDeleteResponse,
  EWarungListResponse,
  EWarungResponse,
  EWarungUpdatePayload,
} from '@/types/eWarung'

class EWarungService {
  async getAll():
    Promise<EWarungListResponse> {
    const response =
      await http.get<
        EWarungListResponse
      >(
        '/api/v1/admin/e-warungs',
      )

    return response.data
  }

  async create(
    payload:
      EWarungCreatePayload,
  ): Promise<EWarungResponse> {
    const response =
      await http.post<
        EWarungResponse
      >(
        '/api/v1/admin/e-warungs',
        payload,
      )

    return response.data
  }

  async update(
    id: number,
    payload:
      EWarungUpdatePayload,
  ): Promise<EWarungResponse> {
    const response =
      await http.patch<
        EWarungResponse
      >(
        `/api/v1/admin/e-warungs/${id}`,
        payload,
      )

    return response.data
  }

  async setStatus(
    id: number,
    isActive: boolean,
  ): Promise<EWarungResponse> {
    const response =
      await http.patch<
        EWarungResponse
      >(
        `/api/v1/admin/e-warungs/${id}/status`,
        {
          is_active:
            isActive,
        },
      )

    return response.data
  }

  async delete(
    id: number,
  ): Promise<EWarungDeleteResponse> {
    const response =
      await http.delete<
        EWarungDeleteResponse
      >(
        `/api/v1/admin/e-warungs/${id}`,
      )

    return response.data
  }
}

export const eWarungService =
  new EWarungService()