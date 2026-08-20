import type {
  RouteRecordRaw,
} from 'vue-router'

export const adminRoutes:
  RouteRecordRaw[] = [
    {
      path:
        'admin/bnba/import',

      name:
        'admin-bnba-import',

      component: () =>
        import(
          '@/views/admin/BnbaImportView.vue'
        ),

      meta: {
        roles: [
          'admin_dinsos',
        ],

        title:
          'Import BNBA',
      },
    },

    {
      path:
        'admin/surveyors',

      name:
        'admin-surveyors',

      component: () =>
        import(
          '@/views/admin/SurveyorView.vue'
        ),

      meta: {
        roles: [
          'admin_dinsos',
        ],

        title:
          'Akun Surveyor',
      },
    },
  ]