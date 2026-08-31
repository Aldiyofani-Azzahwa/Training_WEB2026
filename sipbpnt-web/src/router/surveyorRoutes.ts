import type {
  RouteRecordRaw,
} from 'vue-router'

import SurveyorLayout
  from '@/layouts/SurveyorLayout.vue'

export const surveyorRoute:
  RouteRecordRaw = {
    path:
      '/surveyor',

    component:
      SurveyorLayout,

    meta: {
      requiresAuth:
        true,

      roles: [
        'surveyor',
      ],
    },

    children: [
      {
        path:
          '',

        name:
          'surveyor-home',

        component: () =>
          import(
            '@/views/surveyor/DashboardView.vue'
          ),

        meta: {
          title:
            'Beranda Surveyor',
        },
      },
      {
        path:
          'kpm',

        name:
          'surveyor-kpm',

        component: () =>
          import(
            '@/views/surveyor/KpmView.vue'
          ),

        meta: {
          title:
            'KPM',
        },
      },
      {
        path:
          'scan-ktp',

        name:
          'surveyor-scan-ktp',

        component: () =>
          import(
            '@/views/surveyor/ScanKtpView.vue'
          ),

        meta: {
          title:
            'Scan KTP',
        },
      },
      {
        path:
          'transaksi',

        name:
          'surveyor-transactions',

        redirect: {
          name:
            'surveyor-kpm',
        },

        meta: {
          title:
            'KPM',
        },
      },
      {
        path:
          'riwayat',

        name:
          'surveyor-history',

        component: () =>
          import(
            '@/views/surveyor/HistoryView.vue'
          ),

        meta: {
          title:
            'Riwayat',
        },
      },
      {
        path:
          'laporan-monitoring',

        name:
          'surveyor-monitoring-report',

        component: () =>
          import(
            '@/views/surveyor/MonitoringReportView.vue'
          ),

        meta: {
          title:
            'Laporan Kelurahan',
        },
      },
    ],
  }