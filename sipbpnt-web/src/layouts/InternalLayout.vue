<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'

import {
  Menu,
  X,
} from '@lucide/vue'

import {
  RouterView,
  useRoute,
  useRouter,
} from 'vue-router'

import InternalSidebar
  from '@/components/internal/InternalSidebar.vue'

import InternalBottomNav
  from '@/components/internal/InternalBottomNav.vue'

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  UserRole,
} from '@/types/auth'

const route =
  useRoute()

const router =
  useRouter()

const authStore =
  useAuthStore()

/*
|--------------------------------------------------------------------------
| Sidebar Roles
|--------------------------------------------------------------------------
|
| Surveyor tidak memakai sidebar.
| Nanti memakai bottom navigation.
|
*/

const SIDEBAR_ROLES:
  UserRole[] = [
    'admin_dinsos',
    'manager',
    'kepala_dinas',
  ]

const supportsSidebar =
  computed(() => {
    const role =
      authStore.role

    if (
      role === null
    ) {
      return false
    }

    return SIDEBAR_ROLES
      .includes(
        role,
      )
  })

/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
*/

const sidebarOpen =
  ref(true)

const isDesktop =
  ref(true)
const sidebarElement =
  ref<HTMLElement | null>(
    null,
  )

const sidebarToggleElement =
  ref<HTMLElement | null>(
    null,
  )

let desktopMedia:
  MediaQueryList | null =
    null

function syncViewport(
  event?:
    MediaQueryListEvent,
): void {
  const desktop =
    event?.matches
    ??
    desktopMedia?.matches
    ??
    true

  isDesktop.value =
    desktop

  /*
   * Desktop:
   * default sidebar terbuka.
   *
   * Mobile:
   * default sidebar tertutup.
   */
  sidebarOpen.value =
    desktop
}

function toggleSidebar():
  void {
  if (
    !supportsSidebar.value
  ) {
    return
  }

  sidebarOpen.value =
    !sidebarOpen.value
}

function closeSidebar():
  void {
  sidebarOpen.value =
    false
}

function handleDocumentClick(
  event: MouseEvent,
): void {
  if (
    !supportsSidebar.value
    ||
    !sidebarOpen.value
    ||
    isDesktop.value
  ) {
    return
  }

  const target =
    event.target

  if (
    !(target instanceof Node)
  ) {
    return
  }

  /*
   * Klik di dalam sidebar:
   * jangan tutup.
   */
  if (
    sidebarElement.value
      ?.contains(target)
  ) {
    return
  }

  /*
   * Klik hamburger:
   * biarkan toggleSidebar()
   * yang menangani.
   */
  if (
    sidebarToggleElement.value
      ?.contains(target)
  ) {
    return
  }

  closeSidebar()
}

/*
|--------------------------------------------------------------------------
| Tutup Sidebar Setelah Navigasi Mobile
|--------------------------------------------------------------------------
*/

watch(
  () =>
    route.fullPath,

  () => {
    if (
      !isDesktop.value
    ) {
      closeSidebar()
    }
  },
)

/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/

const user =
  computed(
    () =>
      authStore.user,
  )

const initials =
  computed(() => {
    const name =
      user.value?.name
      ?? 'User'

    return name
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map(
        (word) =>
          word
            .charAt(0)
            .toUpperCase(),
      )
      .join('')
  })

/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

const pageTitle =
  computed(() => {
    const metaTitle =
      route.meta.title

    if (
      typeof metaTitle
      === 'string'
      &&
      metaTitle.trim()
      !== ''
    ) {
      return metaTitle
    }

    const routeName =
      typeof route.name
      === 'string'
        ? route.name
        : ''

    const titles:
      Record<
        string,
        string
      > = {
        dashboard:
          'Dashboard',

        'admin-bnba-import':
          'Import BNBA',

        'management-bnba':
          'Data BNBA',
      }

    return (
      titles[
        routeName
      ]
      ??
      'Dashboard'
    )
  })

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

async function logout():
  Promise<void> {
  await authStore
    .logout()

  await router
    .replace({
      name:
        'login',
    })
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
  desktopMedia =
    window.matchMedia(
      '(min-width: 1024px)',
    )

  syncViewport()

  desktopMedia
    .addEventListener(
      'change',
      syncViewport,
    )

  document
    .addEventListener(
      'click',
      handleDocumentClick,
    )
})
onBeforeUnmount(() => {
  desktopMedia
    ?.removeEventListener(
      'change',
      syncViewport,
    )

  document
    .removeEventListener(
      'click',
      handleDocumentClick,
    )
})
</script>

<template>
  <div
    class="min-h-screen bg-slate-50"
  >
    <!--
    SIDEBAR

    Hanya tampil di desktop. Di mobile/tablet,
    navigasi dipindah ke InternalBottomNav supaya
    tidak perlu overlay/hamburger drawer lagi.
    -->
   <aside
  v-if="
    supportsSidebar
    &&
    isDesktop
  "
  ref="sidebarElement"
  :class="[
    'fixed inset-y-0 left-0 z-50 w-[280px] transition-transform duration-300 ease-out print:hidden',
    sidebarOpen
      ? 'translate-x-0'
      : '-translate-x-full',
  ]"
>
  <InternalSidebar />
</aside>

    <!-- CONTENT -->
    <div
      :class="[
        'min-h-screen transition-[padding] duration-300 ease-out print:!pl-0',
        supportsSidebar
        &&
        sidebarOpen
        &&
        isDesktop
          ? 'lg:pl-[280px]'
          : 'lg:pl-0',
      ]"
    >
      <!-- HEADER -->
      <header
        class="sticky top-0 z-30 flex min-h-[84px] items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6 lg:px-8 print:hidden"
      >
        <!-- LEFT -->
        <div
          class="flex min-w-0 items-center gap-3"
        >
          <!-- HAMBURGER (desktop collapse toggle only) -->
          <button
  v-if="
    supportsSidebar
    &&
    isDesktop
  "
  ref="sidebarToggleElement"
  type="button"
  :aria-label="
    sidebarOpen
      ? 'Sembunyikan sidebar'
      : 'Tampilkan sidebar'
  "
  :aria-expanded="
    sidebarOpen
  "
  class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
  @click="
    toggleSidebar
  "
>
            <X
              v-if="
                sidebarOpen
              "
              :size="21"
              aria-hidden="true"
            />

            <Menu
              v-else
              :size="22"
              aria-hidden="true"
            />
          </button>

          <!-- TITLE -->
          <div
            class="min-w-0"
          >
            <span
              class="block text-xs font-bold text-slate-500"
            >
              Sistem Informasi BPNT
            </span>

            <h1
              class="mt-0.5 truncate text-xl font-black text-slate-900 sm:text-2xl"
            >
              {{ pageTitle }}
            </h1>
          </div>
        </div>

        <!-- USER -->
        <div
          class="flex shrink-0 items-center gap-3"
        >
          <div
            class="grid size-11 shrink-0 place-items-center rounded-xl bg-red-100 text-sm font-black text-red-700"
          >
            {{ initials }}
          </div>

          <div
            class="hidden min-w-30 leading-tight sm:grid"
          >
            <strong
              class="text-sm text-slate-800"
            >
              {{ user?.name }}
            </strong>

            <small
              class="mt-1 text-xs text-slate-500"
            >
              {{ user?.role_label }}
            </small>
          </div>

          <button
            type="button"
            :disabled="
              authStore.loading
            "
            class="min-h-10 rounded-xl bg-red-50 px-3 py-2 text-xs font-extrabold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60 sm:px-4"
            @click="
              logout
            "
          >
            Keluar
          </button>
        </div>
      </header>

      <!-- PAGE -->
      <main
        class="min-w-0 pb-24 lg:pb-0"
      >
        <RouterView />
      </main>
    </div>

    <!-- BOTTOM NAVIGATION (mobile & tablet) -->
    <InternalBottomNav />
  </div>
</template>
