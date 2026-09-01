import {
    http,
} from '@/services/http'

import type {
    HeadOfficeDashboard,
    HeadOfficeDashboardQuery,
    HeadOfficeDashboardResponse,
} from '@/types/headOfficeDashboard'

class HeadOfficeDashboardService {
    async getDashboard(
        query: HeadOfficeDashboardQuery = {},
    ): Promise<HeadOfficeDashboard> {
        const response =
            await http.get<
                HeadOfficeDashboardResponse
            >(
                '/api/v1/head-office/dashboard',
                {
                    params: query,
                },
            )

        return response.data.data
    }
}

export const headOfficeDashboardService =
    new HeadOfficeDashboardService()