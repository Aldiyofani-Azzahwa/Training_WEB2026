import type {
  RouteRecordRaw,
} from 'vue-router'

export const managementRoutes:
  RouteRecordRaw[] = [
    {
      path:
        '/management/bnba',

      name:
        'management-bnba',

      component: () =>
        import(
          '@/views/management/BnbaConfirmedView.vue'
        ),

      meta: {
        requiresAuth: true,

        roles: [
          'admin_dinsos',
          'manager',
        ],
      },
    },
  ]