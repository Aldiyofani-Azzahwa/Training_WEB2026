<script setup lang="ts">
import {
  computed,
  type Component,
} from 'vue'

import {
  ClipboardList,
  History,
  House,
  LogOut,
  ScanLine,
  UsersRound,
} from '@lucide/vue'

import {
  RouterLink,
  RouterView,
  useRoute,
  useRouter,
} from 'vue-router'

import {
  useAuthStore,
} from '@/stores/auth'

interface NavigationItem {
  label: string
  routeName: string
  icon: Component
  primary?: boolean
}

const route =
  useRoute()

const router =
  useRouter()

const authStore =
  useAuthStore()

const navigationItems:
  NavigationItem[] = [
    {
      label:
        'Beranda',

      routeName:
        'surveyor-home',

      icon:
        House,
    },
    {
      label:
        'KPM',

      routeName:
        'surveyor-kpm',

      icon:
        UsersRound,
    },
    {
      label:
        'Scan KTP',

      routeName:
        'surveyor-scan-ktp',

      icon:
        ScanLine,

      primary:
        true,
    },
    {
      label:
        'Riwayat',

      routeName:
        'surveyor-history',

      icon:
        History,
    },
    {
      label:
        'Laporan',

      routeName:
        'surveyor-monitoring-report',

      icon:
        ClipboardList,
    },
  ]

const userName =
  computed(() => {
    return (
      authStore.user?.name
      ??
      'Surveyor'
    )
  })

const initials =
  computed(() => {
    return userName.value
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map(
        (word) => {
          return word
            .charAt(0)
            .toUpperCase()
        },
      )
      .join('')
  })

function isActive(
  routeName: string,
): boolean {
  return (
    route.name
    ===
    routeName
  )
}

async function handleLogout():
  Promise<void> {
  try {
    await authStore.logout()
  } finally {
    await router.replace({
      name:
        'login',
    })
  }
}
</script>

<template>
  <div
    class="min-h-screen min-h-dvh bg-gradient-to-b from-[#faf8f7] to-[#f5f1ee] text-[#3b2718] lg:px-6 lg:pt-6"
  >
    <div
      class="relative mx-auto min-h-screen min-h-dvh w-full bg-[#fbf9f8] lg:min-h-[calc(100dvh-24px)] lg:max-w-[1360px] lg:rounded-t-[26px] lg:border-x lg:border-[#eae4e0] lg:shadow-[0_0_45px_rgb(63_45_32_/_7%)]"
    >
      <header
        class="sticky top-0 z-20 flex min-h-[72px] items-center justify-between border-b border-[#ece6e2] bg-white/94 px-[18px] pt-[calc(12px+env(safe-area-inset-top,0px))] pb-3 backdrop-blur-[14px] lg:rounded-t-[26px] lg:px-[30px]"
      >
        <div
          class="flex min-w-0 items-center gap-[11px]"
        >
          <div
            class="grid size-[42px] shrink-0 place-items-center rounded-[14px] bg-[#682b00] text-[13px] font-bold tracking-[0.04em] text-white shadow-[0_8px_18px_rgb(104_43_0_/_18%)]"
            aria-hidden="true"
          >
            {{ initials }}
          </div>

          <div
            class="flex min-w-0 flex-col"
          >
            <span
              class="text-[11px] font-semibold leading-[1.3] tracking-[0.04em] text-[#867c75] uppercase"
            >
              Petugas Lapangan
            </span>

            <strong
              class="overflow-hidden text-[15px] leading-[1.45] font-bold text-ellipsis whitespace-nowrap text-[#3f2817]"
            >
              {{ userName }}
            </strong>
          </div>
        </div>

        <button
          type="button"
          class="grid size-[42px] shrink-0 place-items-center rounded-[14px] border border-[#e8e1dc] bg-white p-0 text-[#6c5f56] transition-colors hover:border-[#f2c3c0] hover:bg-[#fff5f4] hover:text-[#c72c28] disabled:cursor-not-allowed disabled:opacity-55"
          aria-label="Keluar dari aplikasi"
          :disabled="authStore.loading"
          @click="handleLogout"
        >
          <LogOut
            :size="21"
            :stroke-width="2"
          />
        </button>
      </header>

      <main
        class="min-h-[calc(100dvh-72px)] px-[18px] pt-5 pb-[calc(104px+env(safe-area-inset-bottom,0px))] lg:px-[30px] lg:pt-7 lg:pb-[118px]"
      >
        <RouterView />
      </main>

      <nav
        class="fixed right-0 bottom-0 left-0 z-30 grid min-h-[74px] grid-cols-5 border-t border-[#e9e3df] bg-white/97 px-2 pt-2 pb-[calc(7px+env(safe-area-inset-bottom,0px))] shadow-[0_-10px_30px_rgb(57_40_27_/_8%)] backdrop-blur-2xl lg:right-auto lg:left-1/2 lg:w-[calc(100%-48px)] lg:max-w-[1360px] lg:-translate-x-1/2 lg:border-x"
        aria-label="Navigasi Surveyor"
      >
        <RouterLink
          v-for="item in navigationItems"
          :key="item.routeName"
          :to="{
            name: item.routeName,
          }"
          class="relative flex min-w-0 flex-col items-center justify-center gap-[3px] rounded-[14px] text-[#8c827a] no-underline transition"
          :class="[
            {
              '-translate-y-[17px]':
                item.primary,

              'text-[#682b00]':
                isActive(item.routeName)
                &&
                !item.primary,
            },
          ]"
          :aria-current="
            isActive(item.routeName)
              ? 'page'
              : undefined
          "
        >
          <span
            class="grid place-items-center"
            :class="
              item.primary
                ? [
                    'size-[54px] rounded-[19px] border-[5px] border-[#fbf9f8] text-white',
                    isActive(item.routeName)
                      ? 'bg-[#EA580C] shadow-[0_10px_22px_rgb(234_88_12_/_25%)]'
                      : 'bg-[#682b00] shadow-[0_10px_22px_rgb(104_43_0_/_26%)]',
                  ]
                : 'h-[29px] w-8'
            "
          >
            <component
              :is="item.icon"
              :size="
                item.primary
                  ? 25
                  : 22
              "
              :stroke-width="2"
            />
          </span>

          <span
            class="max-w-full overflow-hidden text-[10px] leading-[1.2] font-semibold text-ellipsis whitespace-nowrap lg:text-xs"
            :class="{
              'mt-px text-[#665950]':
                item.primary
                &&
                !isActive(item.routeName),

              'mt-px text-[#C2410C]':
                item.primary
                &&
                isActive(item.routeName),
            }"
          >
            {{ item.label }}
          </span>
        </RouterLink>
      </nav>
    </div>
  </div>
</template>