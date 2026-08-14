import type {
  RouteRecordRaw,
} from 'vue-router'

export const adminRoutes:
  RouteRecordRaw[] = [
    {
      path:
        '/admin/bnba/import',

      name:
        'admin-bnba-import',

      component: () =>
        import(
          '@/views/admin/BnbaImportView.vue'
        ),

      meta: {
        requiresAuth: true,

        roles: [
          'admin_dinsos',
        ],
      },
    },
  ]