import type {
  Component,
} from 'vue'

import {
  Activity,
  BarChart3,
  ClipboardList,
  CreditCard,
  Database,
  FileSpreadsheet,
  FileText,
  History,
  LayoutDashboard,
  Search,
  Store,
  Users,
} from '@lucide/vue'

import type {
  UserRole,
} from '@/types/auth'

export interface InternalNavigationItem {
  key: string
  label: string
  icon: Component
  routeName?: string
  available: boolean
}

const dashboard:
  InternalNavigationItem = {
    key:
      'dashboard',

    label:
      'Dashboard',

    icon:
      LayoutDashboard,

    routeName:
      'dashboard',

    available:
      true,
  }

const navigationByRole:
  Record<
    UserRole,
    InternalNavigationItem[]
  > = {
    admin_dinsos: [
      dashboard,

      {
        key:
          'import-bnba',

        label:
          'Import BNBA',

        icon:
          FileSpreadsheet,

        routeName:
          'admin-bnba-import',

        available:
          true,
      },

      {
        key:
          'data-bnba',

        label:
          'Data BNBA',

        icon:
          Database,

        routeName:
          'management-bnba',

        available:
          true,
      },

      {
        key:
          'surveyor-accounts',

        label:
          'Akun Surveyor',

        icon:
          Users,

        routeName:
          'admin-surveyors',

        available:
          true,
      },

      {
        key:
          'ewarung',

        label:
          'E-Warung',

        icon:
          Store,

        routeName:
          'admin-e-warungs',

        available:
          true,
      },

      {
        key:
          'rekapitulasi',

        label:
          'Rekapitulasi',

        icon:
          BarChart3,

        available:
          false,
      },

      {
        key:
          'laporan',

        label:
          'Laporan',

        icon:
          FileText,

        available:
          false,
      },
    ],

    manager: [
      dashboard,

      {
        key:
          'data-bnba',

        label:
          'Data BNBA',

        icon:
          Database,

        routeName:
          'management-bnba',

        available:
          true,
      },

      {
        key:
          'surveyor',

        label:
          'Penugasan Surveyor',

        icon:
          Users,

        routeName:
          'manager-surveyor-assignments',

        available:
          true,
      },

      {
        key:
          'monitoring',

        label:
          'Monitoring Transaksi',

        icon:
          Activity,

        available:
          false,
      },

      {
        key:
          'laporan',

        label:
          'Laporan',

        icon:
          FileText,

        available:
          false,
      },
    ],

    surveyor: [
      dashboard,

      {
        key:
          'search-kpm',

        label:
          'Pencarian KPM',

        icon:
          Search,

        available:
          false,
      },

      {
        key:
          'transaction',

        label:
          'Transaksi',

        icon:
          CreditCard,

        available:
          false,
      },

      {
        key:
          'followup',

        label:
          'Pendataan KPM',

        icon:
          ClipboardList,

        available:
          false,
      },

      {
        key:
          'history',

        label:
          'Riwayat',

        icon:
          History,

        available:
          false,
      },
    ],

    kepala_dinas: [
      dashboard,

      {
        key:
          'monitoring',

        label:
          'Monitoring',

        icon:
          Activity,

        available:
          false,
      },

      {
        key:
          'rekapitulasi',

        label:
          'Rekapitulasi',

        icon:
          BarChart3,

        available:
          false,
      },

      {
        key:
          'laporan',

        label:
          'Laporan',

        icon:
          FileText,

        available:
          false,
      },
    ],
  }

export function getInternalNavigation(
  role: UserRole,
): InternalNavigationItem[] {
  return navigationByRole[
    role
  ]
}