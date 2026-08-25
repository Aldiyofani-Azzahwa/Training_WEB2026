import axios from 'axios'

import {
  createRouter,
  createWebHistory,
  type RouteLocationNormalized,
  type RouteRecordRaw,
} from 'vue-router'

import InternalLayout
  from '@/layouts/InternalLayout.vue'

import PublicLayout
  from '@/layouts/PublicLayout.vue'

import {
  http,
} from '@/services/http'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  ApiResponse,
  AuthUser,
  UserRole,
} from '@/types/auth'

import {
  adminRoutes,
} from './adminRoutes'

import {
  managementRoutes,
} from './managementRoutes'

import {
  surveyorRoute,
} from './surveyorRoutes'

const routes:
  RouteRecordRaw[] = [
    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */

    {
      path:
        '/',

      component:
        PublicLayout,

      children: [
        {
          path:
            '',

          name:
            'home',

          component: () =>
            import(
              '@/views/LandingView.vue'
            ),
        },

        {
          path:
            'tentang-bpnt',

          name:
            'about-bpnt',

          component: () =>
            import(
              '@/views/AboutBpntView.vue'
            ),
        },

        {
          path:
            'tentang-sipbpnt',

          name:
            'about-sipbpnt',

          component: () =>
            import(
              '@/views/AboutSipbpntView.vue'
            ),
        },

        {
          path:
            'manfaat',

          name:
            'benefits',

          component: () =>
            import(
              '@/views/BenefitsView.vue'
            ),
        },

        {
          path:
            'faq',

          name:
            'faq',

          component: () =>
            import(
              '@/views/FaqView.vue'
            ),
        },

        {
          path:
            'kontak',

          name:
            'contact',

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
      path:
        '/login',

      name:
        'login',

      component: () =>
        import(
          '@/views/LoginView.vue'
        ),

      meta: {
        guestOnly:
          true,

        title:
          'Login',
      },
    },

    /*
    |--------------------------------------------------------------------------
    | Internal Admin / Manager / Kepala Dinas
    |--------------------------------------------------------------------------
    */

    {
      path:
        '/dashboard',

      component:
        InternalLayout,

      meta: {
        requiresAuth:
          true,

        roles: [
          'admin_dinsos',
          'manager',
          'kepala_dinas',
        ],
      },

      children: [
        {
          path:
            '',

          name:
            'dashboard',

          component: () =>
            import(
              '@/views/DashboardView.vue'
            ),

          meta: {
            title:
              'Dashboard',
          },
        },

        ...adminRoutes,

        ...managementRoutes,
      ],
    },

    /*
    |--------------------------------------------------------------------------
    | Surveyor Mobile Workspace
    |--------------------------------------------------------------------------
    */

    surveyorRoute,

    /*
    |--------------------------------------------------------------------------
    | Legacy Redirect
    |--------------------------------------------------------------------------
    */

    {
      path:
        '/admin/bnba/import',

      redirect: {
        name:
          'admin-bnba-import',
      },
    },

    {
      path:
        '/management/bnba',

      redirect: {
        name:
          'management-bnba',
      },
    },

    /*
    |--------------------------------------------------------------------------
    | Not Found
    |--------------------------------------------------------------------------
    */

    {
      path:
        '/:pathMatch(.*)*',

      name:
        'not-found',

      component: () =>
        import(
          '@/views/NotFoundView.vue'
        ),
    },
  ]

const router =
  createRouter({
    history:
      createWebHistory(
        import.meta.env
          .BASE_URL,
      ),

    routes,

    scrollBehavior(
      to,
      _from,
      savedPosition,
    ) {
      if (savedPosition) {
        return savedPosition
      }

      if (to.hash) {
        return {
          el:
            to.hash,

          behavior:
            'smooth',
        }
      }

      return {
        top: 0,
      }
    },
  })

async function restoreAuthSession():
  Promise<void> {
  const authStore =
    useAuthStore()

  if (
    authStore.initialized
  ) {
    return
  }

  try {
    const response =
      await http.get<
        ApiResponse<AuthUser>
      >(
        '/api/v1/auth/me',
      )

    authStore.$patch({
      user:
        response.data.data,

      initialized:
        true,
    })
  } catch (
    error: unknown
  ) {
    if (
      axios.isAxiosError(
        error,
      )
      &&
      (
        error.response
          ?.status
        === 401
        ||
        error.response
          ?.status
        === 419
      )
    ) {
      authStore.$patch({
        user:
          null,

        initialized:
          true,
      })

      return
    }

    authStore.$patch({
      user:
        null,

      initialized:
        true,
    })
  }
}

function requiresAuthentication(
  to:
    RouteLocationNormalized,
): boolean {
  return Boolean(
    to.meta.requiresAuth,
  )
}

function isGuestOnly(
  to:
    RouteLocationNormalized,
): boolean {
  return Boolean(
    to.meta.guestOnly,
  )
}

function getRequiredRoles(
  to:
    RouteLocationNormalized,
): UserRole[] {
  const roles =
    to.meta.roles

  if (
    !Array.isArray(
      roles,
    )
  ) {
    return []
  }

  return roles
}

function hasRequiredRole(
  to:
    RouteLocationNormalized,
): boolean {
  const authStore =
    useAuthStore()

  const requiredRoles =
    getRequiredRoles(
      to,
    )

  if (
    requiredRoles.length
    === 0
  ) {
    return true
  }

  if (!authStore.user) {
    return false
  }

  return requiredRoles
    .includes(
      authStore.user.role,
    )
}

function authenticatedHomeRoute(
  role: UserRole | null,
): {
  name: 'dashboard' | 'surveyor-home'
} {
  if (
    role
    === 'surveyor'
  ) {
    return {
      name:
        'surveyor-home',
    }
  }

  return {
    name:
      'dashboard',
  }
}

router.beforeEach(
  async (
    to,
  ) => {
    const authStore =
      useAuthStore()

    await restoreAuthSession()

    if (
      isGuestOnly(to)
      &&
      authStore.isAuthenticated
    ) {
      return authenticatedHomeRoute(
        authStore.role,
      )
    }

    if (
      requiresAuthentication(
        to,
      )
      &&
      !authStore.isAuthenticated
    ) {
      return {
        name:
          'login',

        query: {
          redirect:
            to.fullPath,
        },
      }
    }

    if (
      requiresAuthentication(
        to,
      )
      &&
      authStore.isAuthenticated
      &&
      !hasRequiredRole(to)
    ) {
      return authenticatedHomeRoute(
        authStore.role,
      )
    }

    return true
  },
)

export default router