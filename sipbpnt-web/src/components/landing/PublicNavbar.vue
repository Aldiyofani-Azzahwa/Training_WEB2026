<script setup lang="ts">
import {
  computed,
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
} from 'lucide-vue-next'

import BrandLogo from './BrandLogo.vue'

import {
  publicNavigation,
  type PublicNavigationItem,
} from '@/config/publicSite'

const route = useRoute()

const isScrolled = ref(false)
const mobileMenuOpen = ref(false)

const isHomePage = computed<boolean>(() => {
  return route.name === 'home'
})

const useSolidNavbar = computed<boolean>(() => {
  return (
    !isHomePage.value ||
    isScrolled.value ||
    mobileMenuOpen.value
  )
})

function updateScrollState(): void {
  isScrolled.value = window.scrollY > 30
}

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
    event.key === 'Escape' &&
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

watch(mobileMenuOpen, (isOpen) => {
  document.body.style.overflow =
    isOpen ? 'hidden' : ''
})

onMounted(() => {
  updateScrollState()

  window.addEventListener(
    'scroll',
    updateScrollState,
    {
      passive: true,
    },
  )

  window.addEventListener(
    'keydown',
    handleEscape,
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    'scroll',
    updateScrollState,
  )

  window.removeEventListener(
    'keydown',
    handleEscape,
  )

  document.body.style.overflow = ''
})
</script>

<template>
  <header
    :class="[
      'fixed inset-x-0 top-0 z-50 border-b transition-all duration-300',
      useSolidNavbar
        ? 'border-white/15 bg-[#E8312D]'
        : 'border-white/20 bg-[rgba(232,49,45,0.75)]',
    ]"
  >
    <div
      :class="[
        'mx-auto flex w-full max-w-7xl items-center justify-between px-5 transition-[height] duration-300 sm:px-6 lg:px-8',
        useSolidNavbar
          ? 'h-[74px]'
          : 'h-[82px]',
      ]"
    >
      <!-- Logo tanpa background putih -->
      <RouterLink
        :to="{ name: 'home' }"
        class="shrink-0 rounded-lg bg-transparent"
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
            'group relative rounded-lg px-3 py-2 text-[13px] font-medium transition-colors duration-200',
            isActive(item.routeName)
              ? 'text-white'
              : 'text-white/90 hover:text-[#FFAF1C]',
          ]"
        >
          {{ item.label }}

          <span
            :class="[
              'absolute inset-x-3 -bottom-0.5 h-0.5 origin-left rounded-full bg-[#FFAF1C] transition-transform duration-300',
              isActive(item.routeName)
                ? 'scale-x-100'
                : 'scale-x-0 group-hover:scale-x-100',
            ]"
            aria-hidden="true"
          />
        </RouterLink>
      </nav>

      <div class="flex items-center gap-2">
        <RouterLink
          :to="{ name: 'login' }"
          class="hidden min-h-11 items-center justify-center gap-2 rounded-[14px] bg-white px-5 text-sm font-semibold text-[#E8312D] transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#FFAF1C] hover:text-government-900 sm:inline-flex"
        >
          Masuk Sistem

          <ArrowRight
            :size="17"
            class="text-[#006855]"
            aria-hidden="true"
          />
        </RouterLink>

        <button
          type="button"
          class="flex size-11 items-center justify-center rounded-[14px] border border-white/30 bg-white text-[#E8312D] transition-colors hover:bg-[#FFAF1C] lg:hidden"
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
        'overflow-hidden border-t bg-[#E8312D] transition-all duration-300 lg:hidden',
        mobileMenuOpen
          ? 'max-h-[600px] border-white/15 opacity-100'
          : 'pointer-events-none max-h-0 border-transparent opacity-0',
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
            'flex min-h-12 items-center justify-between rounded-xl px-4 text-sm font-medium transition-colors',
            isActive(item.routeName)
              ? 'bg-white text-[#E8312D]'
              : 'text-white hover:bg-white/10',
          ]"
          @click="closeMobileMenu"
        >
          {{ item.label }}

          <span
            v-if="isActive(item.routeName)"
            class="size-2 rounded-full bg-[#FFAF1C]"
            aria-hidden="true"
          />
        </RouterLink>

        <RouterLink
          :to="{ name: 'login' }"
          class="mt-3 inline-flex min-h-12 items-center justify-center gap-2 rounded-[14px] bg-white px-5 text-sm font-semibold text-[#E8312D] transition-colors hover:bg-[#FFAF1C]"
          @click="closeMobileMenu"
        >
          Masuk Sistem

          <ArrowRight
            :size="17"
            class="text-[#006855]"
            aria-hidden="true"
          />
        </RouterLink>
      </nav>
    </div>
  </header>
</template>