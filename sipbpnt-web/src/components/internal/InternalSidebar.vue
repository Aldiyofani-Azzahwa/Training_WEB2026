<script setup lang="ts">
import {
  computed,
} from 'vue'

import {
  ChevronRight,
  CodeXml,
} from '@lucide/vue'

import {
  RouterLink,
} from 'vue-router'

import {
  getInternalNavigation,
} from '@/config/internalNavigation'

import {
  publicSite,
} from '@/config/publicSite'

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
    class="relative flex h-full w-full flex-col overflow-hidden bg-gradient-to-b from-[#F97316] via-[#C2410C] to-[#7C2D12] text-white"
  >

    <div
      class="relative z-10 px-5 pb-5 pt-6"
    >
      <div
        class="flex items-center gap-3"
      >
        <img
          :src="publicSite.logoPath"
          :alt="`Logo ${publicSite.name}`"
          class="h-11 w-auto object-contain drop-shadow-md"
        />


      </div>
    </div>

    <div
      class="relative z-10 mx-5 border-t border-white/15"
    />

    <nav
      class="relative z-10 min-h-0 flex-1 overflow-y-auto overscroll-none px-4 py-6 [scrollbar-width:none] [-ms-overflow-style:none]"
      aria-label="Menu utama"
    >
      

      <div class="space-y-2">
        <template
          v-for="(item, index) in navigationItems"
          :key="item.key"
        >
          <div
            v-if="index > 0"
            class="mx-1 border-t border-white/15"
          />

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
            class="group flex min-h-14 items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-bold text-white transition"
            exact-active-class="router-link-exact-active !bg-[#FFF7ED] shadow-lg shadow-black/10"
          >
            <span
              class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white transition group-[.router-link-exact-active]:bg-[#FFEDD5] group-[.router-link-exact-active]:text-[#C2410C]"
            >
              <component
                :is="item.icon"
                :size="18"
                aria-hidden="true"
              />
            </span>

            <div class="min-w-0 flex-1">
              <span
                class="block truncate group-[.router-link-exact-active]:text-[#C2410C]"
              >
                {{ item.label }}
              </span>
            </div>

            <ChevronRight
              :size="16"
              class="shrink-0 text-white/50 transition group-[.router-link-exact-active]:text-[#EA580C]"
              aria-hidden="true"
            />
          </RouterLink>

          <div
            v-else
            class="flex min-h-14 cursor-not-allowed items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-bold text-white/50"
          >
            <span
              class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white/50"
            >
              <component
                :is="item.icon"
                :size="18"
                aria-hidden="true"
              />
            </span>

            <div class="min-w-0 flex-1">
              <span class="block truncate">
                {{ item.label }}
              </span>
            </div>

            <span
              class="shrink-0 rounded-full bg-white/10 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-orange-100"
            >
              Segera
            </span>
          </div>
        </template>
      </div>
    </nav>

    <div
      class="relative z-10 mt-auto px-5 pb-6"
    >
      
    </div>
  </div>
</template>

<style scoped>
nav::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}
</style>