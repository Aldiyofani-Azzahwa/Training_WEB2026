import type {
  RouteRecordRaw,
} from 'vue-router'

export const adminRoutes:
  RouteRecordRaw[] = [
    {
      path:
        'admin/periode-aktif',

      name:
        'admin-active-period',

      component: () =>
        import(
          '@/views/admin/ActivePeriodView.vue'
        ),

      meta: {
        roles: [
          'admin_dinsos',
        ],

        title:
          'Periode Aktif',
      },
    },

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
        'admin/e-warungs',

      name:
        'admin-e-warungs',

      component: () =>
        import(
          '@/views/admin/EWarungView.vue'
        ),

      meta: {
        roles: [
          'admin_dinsos',
        ],

        title:
          'Master E-Warung',
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