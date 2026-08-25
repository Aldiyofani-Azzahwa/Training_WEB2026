<script setup lang="ts">
import {
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'

import {
  RouterLink,
  useRoute,
} from 'vue-router'

import {
  ArrowRight,
  Menu,
  X,
} from '@lucide/vue'

import BrandLogo from './BrandLogo.vue'

import {
  publicNavigation,
  type PublicNavigationItem,
} from '@/config/publicSite'

const route = useRoute()

const mobileMenuOpen = ref(false)

function isActive(
  routeName: PublicNavigationItem['routeName'],
): boolean {
  return route.name === routeName
}

function closeMobileMenu(): void {
  mobileMenuOpen.value = false
}

function toggleMobileMenu(): void {
  mobileMenuOpen.value =
    !mobileMenuOpen.value
}

function handleEscape(
  event: KeyboardEvent,
): void {
  if (
    event.key === 'Escape'
    &&
    mobileMenuOpen.value
  ) {
    closeMobileMenu()
  }
}

watch(
  () => route.fullPath,
  () => {
    closeMobileMenu()
  },
)

watch(
  mobileMenuOpen,
  (isOpen) => {
    document.body.style.overflow =
      isOpen
        ? 'hidden'
        : ''
  },
)

onMounted(() => {
  window.addEventListener(
    'keydown',
    handleEscape,
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    'keydown',
    handleEscape,
  )

  document.body.style.overflow = ''
})
</script>

<template>
  <header class="fixed inset-x-0 top-0 z-50">
    <!-- Navbar utama -->
    <div class="border-b border-slate-200 bg-white shadow-sm">
      <div
        class="mx-auto flex h-[68px] w-full max-w-7xl items-center justify-between px-5 sm:px-6 lg:px-8"
      >
        <!-- Logo -->
        <RouterLink
          :to="{ name: 'home' }"
          class="shrink-0 rounded-lg"
          aria-label="Beranda SIPBPNT"
          @click="closeMobileMenu"
        >
          <BrandLogo />
        </RouterLink>

        <!-- Navigasi desktop -->
        <nav
          class="hidden items-center gap-1 lg:flex"
          aria-label="Navigasi utama"
        >
          <RouterLink
            v-for="item in publicNavigation"
            :key="item.routeName"
            :to="{ name: item.routeName }"
            :aria-current="
              isActive(item.routeName)
                ? 'page'
                : undefined
            "
            :class="[
              'group relative rounded-lg px-3 py-2 text-[13px] font-bold uppercase tracking-wide transition-colors duration-200',
              isActive(item.routeName)
                ? 'text-[#F58700]'
                : 'text-slate-800 hover:text-[#F58700]',
            ]"
          >
            {{ item.label }}

            <span
              :class="[
                'absolute inset-x-3 -bottom-0.5 h-0.5 origin-left rounded-full bg-[#F58700] transition-transform duration-300',
                isActive(item.routeName)
                  ? 'scale-x-100'
                  : 'scale-x-0 group-hover:scale-x-100',
              ]"
              aria-hidden="true"
            />
          </RouterLink>

          <RouterLink
            :to="{ name: 'login' }"
            class="group relative ml-2 flex items-center gap-1.5 rounded-lg px-3 py-2 text-[13px] font-bold uppercase tracking-wide text-slate-800 transition-colors duration-200 hover:text-[#F58700]"
          >
            Log Masuk

            <ArrowRight
              :size="15"
              class="text-[#087D69] transition-transform duration-200 group-hover:translate-x-0.5"
              aria-hidden="true"
            />
          </RouterLink>
        </nav>

        <!-- Tombol menu mobile -->
        <button
          type="button"
          class="flex size-11 items-center justify-center rounded-[14px] border border-slate-200 bg-white text-slate-800 transition-colors hover:border-[#F58700]/40 hover:text-[#F58700] lg:hidden"
          :aria-expanded="mobileMenuOpen"
          aria-controls="mobile-navigation"
          :aria-label="
            mobileMenuOpen
              ? 'Tutup navigasi'
              : 'Buka navigasi'
          "
          @click="toggleMobileMenu"
        >
          <X
            v-if="mobileMenuOpen"
            :size="21"
            aria-hidden="true"
          />

          <Menu
            v-else
            :size="21"
            aria-hidden="true"
          />
        </button>
      </div>
    </div>

    <!-- Navigasi mobile -->
    <div
      id="mobile-navigation"
      :class="[
        'overflow-hidden border-b border-slate-200 bg-white transition-all duration-300 lg:hidden',
        mobileMenuOpen
          ? 'max-h-[600px] opacity-100'
          : 'pointer-events-none max-h-0 opacity-0',
      ]"
    >
      <nav
        class="mx-auto flex max-w-7xl flex-col gap-1 px-5 py-5 sm:px-6"
        aria-label="Navigasi perangkat seluler"
      >
        <RouterLink
          v-for="item in publicNavigation"
          :key="item.routeName"
          :to="{ name: item.routeName }"
          :aria-current="
            isActive(item.routeName)
              ? 'page'
              : undefined
          "
          :class="[
            'flex min-h-12 items-center justify-between rounded-xl px-4 text-sm font-bold uppercase tracking-wide transition-colors',
            isActive(item.routeName)
              ? 'bg-[#FFF5E5] text-[#F58700]'
              : 'text-slate-800 hover:bg-slate-50',
          ]"
          @click="closeMobileMenu"
        >
          {{ item.label }}

          <span
            v-if="isActive(item.routeName)"
            class="size-2 rounded-full bg-[#F58700]"
            aria-hidden="true"
          />
        </RouterLink>

        <RouterLink
          :to="{ name: 'login' }"
          class="mt-3 inline-flex min-h-12 items-center justify-center gap-2 rounded-[14px] bg-[#087D69] px-5 text-sm font-bold uppercase tracking-wide text-white transition-colors hover:bg-[#056453]"
          @click="closeMobileMenu"
        >
          Log Masuk

          <ArrowRight
            :size="17"
            aria-hidden="true"
          />
        </RouterLink>
      </nav>
    </div>
  </header>
</template>