<script setup lang="ts">
import {
  computed,
} from 'vue'

import {
  LogOut,
} from '@lucide/vue'

import {
  RouterLink,
  RouterView,
  useRouter,
} from 'vue-router'

import {
  useAuthStore,
} from '@/stores/auth'

const router =
  useRouter()

const authStore =
  useAuthStore()

const user =
  computed(
    () =>
      authStore.user,
  )

const initials =
  computed(() => {
    const name =
      user.value?.name
      ?? 'Kepala Dinas'

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

async function logout():
  Promise<void> {
  await authStore.logout()

  await router.replace({
    name:
      'login',
  })
}
</script>

<template>
  <div
    class="min-h-screen bg-slate-50"
  >
    <header
      class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur"
    >
      <div
        class="mx-auto flex min-h-[72px] max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
      >
        <div
          class="flex min-w-0 items-center gap-6"
        >
          <div
            class="flex shrink-0 items-center gap-3"
          >
            <img
              src="/branding/logo-sipbpnt.png"
              alt="Logo SIPBPNT"
              class="size-11 object-contain"
            >

            <strong
              class="hidden text-xl font-black text-government-green-700 sm:block"
            >
              SIPBPNT
            </strong>
          </div>

          <nav
            aria-label="Navigasi Kepala Dinas"
            class="self-stretch"
          >
            <RouterLink
              :to="{
                name:
                  'head-office-dashboard',
              }"
              class="flex h-full items-center border-b-2 border-government-green-600 px-3 text-sm font-extrabold text-government-green-700"
            >
              Dashboard
            </RouterLink>
          </nav>
        </div>

        <div
          class="flex shrink-0 items-center gap-3"
        >
          <div
            class="grid size-11 place-items-center rounded-full bg-government-green-600 text-sm font-black text-white"
          >
            {{ initials }}
          </div>

          <div
            class="hidden max-w-64 leading-tight sm:grid"
          >
            <strong
              class="truncate text-sm text-slate-900"
            >
              {{ user?.name }}
            </strong>

            <small
              class="mt-1 text-xs text-slate-500"
            >
              Kepala Dinas
            </small>
          </div>

          <button
            type="button"
            aria-label="Keluar"
            :disabled="
              authStore.loading
            "
            class="grid size-11 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-60"
            @click="
              logout
            "
          >
            <LogOut
              :size="20"
              aria-hidden="true"
            />
          </button>
        </div>
      </div>
    </header>

    <main
      class="mx-auto min-h-[calc(100vh-72px)] max-w-[1600px]"
    >
      <RouterView />
    </main>
  </div>
</template>