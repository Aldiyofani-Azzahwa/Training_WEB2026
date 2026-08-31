<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import { MoreHorizontal } from '@lucide/vue'

import { RouterLink, useRoute } from 'vue-router'

import { getInternalNavigation } from '@/config/internalNavigation'

import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const route = useRoute()

/*
|--------------------------------------------------------------------------
| Navigation Items
|--------------------------------------------------------------------------
|
| Bottom nav hanya menampung beberapa ikon utama.
| Sisanya (kalau ada) masuk ke panel "Lainnya".
|
*/

const MAX_VISIBLE = 4

const allItems = computed(() => {
  const role = authStore.role

  if (!role) {
    return []
  }

  return getInternalNavigation(role)
})

const visibleItems = computed(() => allItems.value.slice(0, MAX_VISIBLE))
const overflowItems = computed(() => allItems.value.slice(MAX_VISIBLE))
const hasOverflow = computed(() => overflowItems.value.length > 0)

const isOverflowActive = computed(() =>
  overflowItems.value.some((item) => item.routeName === route.name),
)

/*
|--------------------------------------------------------------------------
| "Lainnya" Sheet
|--------------------------------------------------------------------------
*/

const moreOpen = ref(false)

function toggleMore(): void {
  moreOpen.value = !moreOpen.value
}

function closeMore(): void {
  moreOpen.value = false
}

watch(() => route.fullPath, closeMore)

function isActive(routeName?: string): boolean {
  return typeof routeName === 'string' && route.name === routeName
}
</script>

<template>
  <div v-if="allItems.length > 0" class="lg:hidden">
    <!-- BACKDROP FOR "LAINNYA" SHEET -->
    <button
      v-if="moreOpen"
      type="button"
      aria-label="Tutup menu lainnya"
      class="fixed inset-0 z-40 bg-slate-950/40"
      @click="closeMore"
    />

    <!-- "LAINNYA" SHEET -->
    <div
      v-if="moreOpen"
      class="fixed inset-x-3 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-50 max-h-[55vh] overflow-y-auto rounded-2xl border border-slate-100 bg-white p-2 shadow-xl"
    >
      <p class="px-3 pb-2 pt-1 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">
        Menu Lainnya
      </p>

      <template v-for="item in overflowItems" :key="item.key">
        <RouterLink
          v-if="item.available && typeof item.routeName === 'string'"
          :to="{ name: item.routeName }"
          class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-700 transition-colors active:bg-orange-50"
          exact-active-class="text-orange-600 bg-orange-50"
          @click="closeMore"
        >
          <span
            class="grid size-9 shrink-0 place-items-center rounded-lg bg-orange-50 text-orange-600"
          >
            <component :is="item.icon" :size="18" aria-hidden="true" />
          </span>

          <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
        </RouterLink>

        <div
          v-else
          class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-400"
        >
          <span
            class="grid size-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400"
          >
            <component :is="item.icon" :size="18" aria-hidden="true" />
          </span>

          <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>

          <span
            class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-slate-400"
          >
            Segera
          </span>
        </div>
      </template>
    </div>

    <!-- BOTTOM BAR -->
    <nav
      class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-100 bg-white/95 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_24px_rgba(6,47,40,0.08)] backdrop-blur"
      aria-label="Navigasi utama"
    >
      <div class="mx-auto flex max-w-md items-stretch justify-around px-1">
        <template v-for="item in visibleItems" :key="item.key">
          <RouterLink
            v-if="item.available && typeof item.routeName === 'string'"
            :to="{ name: item.routeName }"
            class="flex min-w-0 flex-1 flex-col items-center gap-1 px-1.5 py-2.5 text-[11px] font-semibold text-slate-500"
          >
            <span
              class="grid size-9 place-items-center rounded-full transition-colors"
              :class="isActive(item.routeName) ? 'bg-orange-100 text-orange-600' : 'text-slate-500'"
            >
              <component :is="item.icon" :size="19" aria-hidden="true" />
            </span>

            <span
              class="max-w-full truncate"
              :class="isActive(item.routeName) ? 'text-orange-600' : ''"
            >
              {{ item.label }}
            </span>
          </RouterLink>

          <div
            v-else
            class="flex min-w-0 flex-1 flex-col items-center gap-1 px-1.5 py-2.5 text-[11px] font-semibold text-slate-300"
          >
            <span class="grid size-9 place-items-center rounded-full">
              <component :is="item.icon" :size="19" aria-hidden="true" />
            </span>

            <span class="max-w-full truncate">{{ item.label }}</span>
          </div>
        </template>

        <!-- MORE BUTTON -->
        <button
          v-if="hasOverflow"
          type="button"
          class="flex min-w-0 flex-1 flex-col items-center gap-1 px-1.5 py-2.5 text-[11px] font-semibold"
          :class="isOverflowActive ? 'text-orange-600' : 'text-slate-500'"
          @click="toggleMore"
        >
          <span
            class="grid size-9 place-items-center rounded-full transition-colors"
            :class="
              moreOpen || isOverflowActive ? 'bg-orange-100 text-orange-600' : 'text-slate-500'
            "
          >
            <MoreHorizontal :size="19" aria-hidden="true" />
          </span>

          <span>Lainnya</span>
        </button>
      </div>
    </nav>
  </div>
</template>
