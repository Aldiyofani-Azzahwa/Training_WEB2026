import axios from 'axios'
import {
  createRouter,
  createWebHistory,
  type RouteLocationNormalized,
  type RouteRecordRaw,
} from 'vue-router'

import PublicLayout from '@/layouts/PublicLayout.vue'
import { http } from '@/services/http'
import { useAuthStore } from '@/stores/auth'

import { adminRoutes } from './adminRoutes'
import { managementRoutes } from './managementRoutes'

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

const routes: RouteRecordRaw[] = [
  /*
  |--------------------------------------------------------------------------
  | Public Pages
  |--------------------------------------------------------------------------
  */

  {
    path: '/',
    component: PublicLayout,

    children: [
      {
        path: '',
        name: 'home',

        component: () =>
          import(
            '@/views/LandingView.vue'
          ),
      },

      {
        path: 'tentang-bpnt',
        name: 'about-bpnt',

        component: () =>
          import(
            '@/views/AboutBpntView.vue'
          ),
      },

      {
        path: 'tentang-sipbpnt',
        name: 'about-sipbpnt',

        component: () =>
          import(
            '@/views/AboutSipbpntView.vue'
          ),
      },

      {
        path: 'manfaat',
        name: 'benefits',

        component: () =>
          import(
            '@/views/BenefitsView.vue'
          ),
      },

      {
        path: 'faq',
        name: 'faq',

        component: () =>
          import(
            '@/views/FaqView.vue'
          ),
      },

      {
        path: 'kontak',
        name: 'contact',

        component: () =>
          import(
            '@/views/ContactView.vue'
          ),
      },
    ],
  },

  /*
  |--------------------------------------------------------------------------
  | Login
  |--------------------------------------------------------------------------
  */

  {
    path: '/login',
    name: 'login',

    component: () =>
      import(
        '@/views/LoginView.vue'
      ),

    meta: {
      guestOnly: true,
    },
  },

  /*
  |--------------------------------------------------------------------------
  | Dashboard
  |--------------------------------------------------------------------------
  */

  {
    path: '/dashboard',
    name: 'dashboard',

    component: () =>
      import(
        '@/views/DashboardView.vue'
      ),

    meta: {
      requiresAuth: true,
    },
  },

  /*
  |--------------------------------------------------------------------------
  | Admin Dinas Sosial
  |--------------------------------------------------------------------------
  |
  | Source:
  | src/router/adminRoutes.ts
  |
  */

  ...adminRoutes,

  /*
  |--------------------------------------------------------------------------
  | Management
  |--------------------------------------------------------------------------
  |
  | Source:
  | src/router/managementRoutes.ts
  |
  | Digunakan oleh:
  | - admin_dinsos
  | - manager
  |
  */

  ...managementRoutes,

  /*
  |--------------------------------------------------------------------------
  | Not Found
  |--------------------------------------------------------------------------
  |
  | Catch-all wajib berada paling bawah.
  |
  */

  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',

    component: () =>
      import(
        '@/views/NotFoundView.vue'
      ),
  },
]

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const router = createRouter({
  history: createWebHistory(
    import.meta.env.BASE_URL,
  ),

  routes,

  scrollBehavior(
    to,
    _from,
    savedPosition,
  ) {
    /*
    |--------------------------------------------------------------------------
    | Browser Back / Forward
    |--------------------------------------------------------------------------
    */

    if (savedPosition) {
      return savedPosition
    }

    /*
    |--------------------------------------------------------------------------
    | Anchor Link
    |--------------------------------------------------------------------------
    */

    if (to.hash) {
      return {
        el: to.hash,
        behavior: 'smooth',
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Default
    |--------------------------------------------------------------------------
    */

    return {
      top: 0,
    }
  },
})

/*
|--------------------------------------------------------------------------
| Restore Sanctum Session
|--------------------------------------------------------------------------
|
| Router tidak bergantung pada method custom auth store.
|
| Yang digunakan:
|
| - authStore.initialized
| - authStore.user
| - authStore.isAuthenticated
| - authStore.$patch()
|
| $patch() merupakan API bawaan Pinia.
|
*/

async function restoreAuthSession():
  Promise<void> {
  const authStore =
    useAuthStore()

  /*
  |--------------------------------------------------------------------------
  | Sudah Diinisialisasi
  |--------------------------------------------------------------------------
  */

  if (authStore.initialized) {
    return
  }

  try {
    /*
    |--------------------------------------------------------------------------
    | Restore User
    |--------------------------------------------------------------------------
    */

    const response =
      await http.get(
        '/api/v1/auth/me',
      )

    authStore.$patch({
      user:
        response.data.data,

      initialized:
        true,
    })
  } catch (error: unknown) {
    /*
    |--------------------------------------------------------------------------
    | Guest / Session Expired
    |--------------------------------------------------------------------------
    */

    if (
      axios.isAxiosError(error)
      &&
      (
        error.response?.status === 401
        ||
        error.response?.status === 419
      )
    ) {
      authStore.$patch({
        user: null,
        initialized: true,
      })

      return
    }

    /*
    |--------------------------------------------------------------------------
    | Network / Server Error
    |--------------------------------------------------------------------------
    |
    | Initialization tetap diselesaikan agar router tidak terus memanggil
    | /api/v1/auth/me pada setiap navigasi.
    |
    */

    authStore.$patch({
      user: null,
      initialized: true,
    })
  }
}

/*
|--------------------------------------------------------------------------
| Requires Authentication
|--------------------------------------------------------------------------
*/

function requiresAuthentication(
  to: RouteLocationNormalized,
): boolean {
  return Boolean(
    to.meta.requiresAuth,
  )
}

/*
|--------------------------------------------------------------------------
| Guest Only
|--------------------------------------------------------------------------
*/

function isGuestOnly(
  to: RouteLocationNormalized,
): boolean {
  return Boolean(
    to.meta.guestOnly,
  )
}

/*
|--------------------------------------------------------------------------
| Required Roles
|--------------------------------------------------------------------------
|
| Mengubah meta.roles menjadi array string yang aman.
|
*/

function getRequiredRoles(
  to: RouteLocationNormalized,
): string[] {
  const roles = to.meta.roles

  if (!Array.isArray(roles)) {
    return []
  }

  return roles.filter(
    (role) =>
      typeof role === 'string',
  )
}

/*
|--------------------------------------------------------------------------
| Role Authorization
|--------------------------------------------------------------------------
|
| Ini hanya navigation guard frontend.
|
| Authorization utama tetap harus dilakukan Laravel.
|
*/

function hasRequiredRole(
  to: RouteLocationNormalized,
): boolean {
  const authStore =
    useAuthStore()

  const requiredRoles =
    getRequiredRoles(to)

  /*
  |--------------------------------------------------------------------------
  | Route Tidak Membatasi Role
  |--------------------------------------------------------------------------
  */

  if (
    requiredRoles.length === 0
  ) {
    return true
  }

  /*
  |--------------------------------------------------------------------------
  | User Tidak Ada
  |--------------------------------------------------------------------------
  */

  if (!authStore.user) {
    return false
  }

  return requiredRoles.includes(
    authStore.user.role,
  )
}

/*
|--------------------------------------------------------------------------
| Global Navigation Guard
|--------------------------------------------------------------------------
*/

router.beforeEach(
  async (to) => {
    const authStore =
      useAuthStore()

    /*
    |--------------------------------------------------------------------------
    | Restore Authentication
    |--------------------------------------------------------------------------
    |
    | Hanya berjalan sampai initialized menjadi true.
    |
    */

    await restoreAuthSession()

    /*
    |--------------------------------------------------------------------------
    | Guest Only
    |--------------------------------------------------------------------------
    |
    | User yang sudah login tidak boleh membuka /login.
    |
    */

    if (
      isGuestOnly(to)
      &&
      authStore.isAuthenticated
    ) {
      return {
        name: 'dashboard',
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | User guest yang membuka protected route dikirim ke login.
    |
    */

    if (
      requiresAuthentication(to)
      &&
      !authStore.isAuthenticated
    ) {
      return {
        name: 'login',

        query: {
          redirect:
            to.fullPath,
        },
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Role Guard
    |--------------------------------------------------------------------------
    |
    | Contoh:
    |
    | /admin/bnba/import
    | -> admin_dinsos
    |
    | /management/bnba
    | -> admin_dinsos, manager
    |
    */

    if (
      requiresAuthentication(to)
      &&
      authStore.isAuthenticated
      &&
      !hasRequiredRole(to)
    ) {
      return {
        name: 'dashboard',
      }
    }

    return true
  },
)

export default router