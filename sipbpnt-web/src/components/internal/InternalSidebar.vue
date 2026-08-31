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
  useAuthStore,
} from '@/stores/auth'

import monumentSketch
  from '@/assets/images/mojokerto-monument-sketch.jpg'

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
    <!--
    ILUSTRASI LATAR

    Sketsa Monumen Kentang, Kota Mojokerto.
    Dibaurkan ke gradasi oranye lewat mix-blend-mode
    dan pudar ke arah kiri-bawah pakai mask gradient.
    -->
    <img
      :src="monumentSketch"
      alt=""
      aria-hidden="true"
      class="pointer-events-none absolute -right-10 -top-4 z-0 h-72 w-72 select-none object-cover opacity-70 mix-blend-multiply [mask-image:linear-gradient(to_bottom_left,black_35%,transparent_78%)]"
    />

    <div
      class="relative z-10 px-5 pb-5 pt-6"
    >
      <div
        class="flex items-center gap-3"
      >
        <div
          class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-[#FFF7ED] text-lg font-black text-[#9A3412] shadow-lg"
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
            class="mt-0.5 block text-xs text-orange-100"
          >
            Kota Mojokerto
          </span>
        </div>
      </div>
    </div>

    <div
      class="relative z-10 mx-5 border-t border-white/15"
    />

    <nav
      class="relative z-10 min-h-0 flex-1 overflow-y-auto px-4 py-6 [scrollbar-width:none] [-ms-overflow-style:none]"
      aria-label="Menu utama"
    >
      <div
        class="mb-4 px-3"
      >
        <p
          class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-100"
        >
          Menu Utama
        </p>

        <span
          class="mt-2 block h-[3px] w-6 rounded-full bg-[#FDBA74]"
        />
      </div>

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
      <div
        class="flex items-center justify-center gap-2 border-t border-white/15 pt-5"
      >
        <span
          class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-black/20"
        >
          <CodeXml
            :size="13"
            aria-hidden="true"
          />
        </span>

        <p
          class="text-center text-[10px] text-orange-100"
        >
          SIPBPNT versi pengembangan
        </p>
      </div>
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