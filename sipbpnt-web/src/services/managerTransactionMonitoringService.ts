import {
  http,
} from '@/services/http'

import type {
  ManagerTransactionMonitoringQuery,
  ManagerTransactionMonitoringResponse,
} from '@/types/managerTransactionMonitoring'

class ManagerTransactionMonitoringService {
  async getMonitoring(
    query:
      ManagerTransactionMonitoringQuery = {},
  ): Promise<ManagerTransactionMonitoringResponse> {
    const response =
      await http.get<
        ManagerTransactionMonitoringResponse
      >(
        '/api/v1/manager/transaction-monitoring',
        {
          params:
            query,
        },
      )

    return response.data
  }
}

export const managerTransactionMonitoringService =
  new ManagerTransactionMonitoringService()