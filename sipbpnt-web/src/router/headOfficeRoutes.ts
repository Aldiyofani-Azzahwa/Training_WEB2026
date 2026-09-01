import type {
    RouteRecordRaw,
} from 'vue-router'

import HeadOfficeLayout
    from '@/layouts/HeadOfficeLayout.vue'

export const headOfficeRoute:
    RouteRecordRaw = {
    path:
        '/kepala-dinas',

    component:
        HeadOfficeLayout,

    meta: {
        requiresAuth:
            true,

        roles: [
            'kepala_dinas',
        ],
    },

    children: [
        {
            path:
                '',

            name:
                'head-office-dashboard',

            component: () =>
                import(
                    '@/views/head-office/HeadOfficeDashboardView.vue'
                ),

            meta: {
                roles: [
                    'kepala_dinas',
                ],

                title:
                    'Dashboard Kepala Dinas',
            },
        },
    ],
}