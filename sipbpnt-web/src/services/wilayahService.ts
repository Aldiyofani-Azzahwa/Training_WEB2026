import {
  http,
} from '@/services/http'

import type {
  WilayahResponse,
} from '@/types/wilayah'

const BASE_PATH =
  '/api/v1'

class WilayahService {
  async getMaster():
    Promise<WilayahResponse> {
    const response =
      await http.get<
        WilayahResponse
      >(
        `${BASE_PATH}/wilayah`,
      )

    return response.data
  }
}

export const wilayahService =
  new WilayahService()