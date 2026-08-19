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
  ]