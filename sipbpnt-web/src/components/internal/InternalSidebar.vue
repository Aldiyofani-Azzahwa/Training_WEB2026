<script setup lang="ts">
import {
  computed,
} from 'vue'

import {
  RouterLink,
} from 'vue-router'

import {
  getInternalNavigation,
} from '@/config/internalNavigation'

import {
  useAuthStore,
} from '@/stores/auth'

const authStore =
  useAuthStore()

const navigationItems =
  computed(() => {
    const role =
      authStore.role

    if (
      role === null
    ) {
      return []
    }

    return getInternalNavigation(
      role,
    )
  })
</script>

<template>
  <div
    class="flex h-full w-full flex-col bg-gradient-to-b from-[#073657] to-[#052944] text-white"
  >
    <div
      class="px-5 pb-5 pt-6"
    >
      <div
        class="flex items-center gap-3"
      >
        <div
          class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#E8312D] to-[#FFAF1C] text-lg font-black text-white shadow-lg"
        >
          S
        </div>

        <div class="min-w-0">
          <strong
            class="block truncate text-lg font-black tracking-wide text-white"
          >
            SIPBPNT
          </strong>

          <span
            class="mt-0.5 block text-xs text-slate-300"
          >
            Kota Mojokerto
          </span>
        </div>
      </div>
    </div>

    <div
      class="mx-5 border-t border-white/10"
    />

    <nav
      class="min-h-0 flex-1 overflow-y-auto px-4 py-6"
      aria-label="Menu utama"
    >
      <p
        class="mb-4 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400"
      >
        Menu Utama
      </p>

      <div class="space-y-2">
        <template
          v-for="item in navigationItems"
          :key="item.key"
        >
          <RouterLink
            v-if="
              item.available
              &&
              typeof item.routeName
              === 'string'
            "
            :to="{
              name: item.routeName,
            }"
            class="group flex min-h-12 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
            exact-active-class="bg-blue-600 !text-white shadow-lg shadow-blue-950/20"
          >
            <component
              :is="item.icon"
              :size="18"
              class="shrink-0"
              aria-hidden="true"
            />

            <div class="min-w-0 flex-1">
              <span class="block truncate">
                {{ item.label }}
              </span>
            </div>
          </RouterLink>

          <div
            v-else
            class="flex min-h-12 cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-500"
          >
            <component
              :is="item.icon"
              :size="18"
              class="shrink-0"
              aria-hidden="true"
            />

            <div class="min-w-0 flex-1">
              <span class="block truncate">
                {{ item.label }}
              </span>
            </div>

            <span
              class="shrink-0 rounded-full bg-white/5 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-slate-400"
            >
              Segera
            </span>
          </div>
        </template>
      </div>
    </nav>

    <div
      class="mt-auto px-5 pb-6"
    >
      <div
        class="border-t border-white/10 pt-5"
      >
        <p
          class="text-center text-[10px] text-slate-400"
        >
          SIPBPNT versi pengembangan
        </p>
      </div>
    </div>
  </div>
</template>