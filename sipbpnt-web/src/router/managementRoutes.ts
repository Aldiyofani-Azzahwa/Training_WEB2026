import type {
  RouteRecordRaw,
} from 'vue-router'

export const managementRoutes:
  RouteRecordRaw[] = [
    {
      path:
        'management/bnba',

      name:
        'management-bnba',

      component: () =>
        import(
          '@/views/management/BnbaConfirmedView.vue'
        ),

      meta: {
        roles: [
          'admin_dinsos',
          'manager',
        ],

        title:
          'Data BNBA',
      },
    },

    {
      path:
        'manager/surveyor-assignments',

      name:
        'manager-surveyor-assignments',

      component: () =>
        import(
          '@/views/manager/SurveyorAssignmentView.vue'
        ),

      meta: {
        roles: [
          'manager',
        ],

        title:
          'Penugasan Surveyor',
      },
    },

    {
      path:
        'manager/transaction-monitoring',

      name:
        'manager-transaction-monitoring',

      component: () =>
        import(
          '@/views/manager/TransactionMonitoringView.vue'
        ),

      meta: {
        roles: [
          'manager',
        ],

        title:
          'Monitoring Transaksi',
      },
    },

    {
      path:
        'reports',

      name:
        'bpnt-reports',

      component: () =>
        import(
          '@/views/reports/BpntReportView.vue'
        ),

      meta: {
        roles: [
          'admin_dinsos',
          'manager',
        ],

        title:
          'Laporan BPNT',
      },
    },
  ]