<script setup lang="ts">
import {
  computed,
  type Component,
} from 'vue'

import {
  History,
  House,
  LogOut,
  ReceiptText,
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
        'Transaksi',

      routeName:
        'surveyor-transactions',

      icon:
        ReceiptText,
    },

    {
      label:
        'Riwayat',

      routeName:
        'surveyor-history',

      icon:
        History,
    },
  ]

const userName =
  computed(
    () =>
      authStore.user?.name
      ?? 'Surveyor',
  )

const initials =
  computed(() => {
    return userName.value
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

function isActive(
  routeName: string,
): boolean {
  return route.name
    === routeName
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
  <div class="surveyor-app">
    <div class="surveyor-shell">
      <header class="surveyor-header">
        <div class="header-identity">
          <div
            class="user-avatar"
            aria-hidden="true"
          >
            {{ initials }}
          </div>

          <div class="user-copy">
            <span>Petugas Lapangan</span>
            <strong>{{ userName }}</strong>
          </div>
        </div>

        <button
          type="button"
          class="logout-button"
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

      <main class="surveyor-content">
        <RouterView />
      </main>

      <nav
        class="bottom-navigation"
        aria-label="Navigasi Surveyor"
      >
        <RouterLink
          v-for="item in navigationItems"
          :key="item.routeName"
          :to="{
            name: item.routeName,
          }"
          class="navigation-item"
          :class="{
            active: isActive(item.routeName),
            primary: item.primary,
          }"
          :aria-current="
            isActive(item.routeName)
              ? 'page'
              : undefined
          "
        >
          <span class="navigation-icon">
            <component
              :is="item.icon"
              :size="item.primary ? 25 : 22"
              :stroke-width="2"
            />
          </span>

          <span class="navigation-label">
            {{ item.label }}
          </span>
        </RouterLink>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.surveyor-app {
  min-height: 100vh;
  min-height: 100dvh;
  background:
    linear-gradient(
      180deg,
      #f7faf9 0%,
      #eef5f2 100%
    );
  color: #183b35;
}

.surveyor-shell {
  position: relative;
  min-height: 100vh;
  min-height: 100dvh;
  background: #f8fbfa;
}

.surveyor-header {
  position: sticky;
  z-index: 20;
  top: 0;

  display: flex;
  align-items: center;
  justify-content: space-between;

  min-height: 72px;
  padding:
    calc(12px + env(safe-area-inset-top, 0px))
    18px
    12px;

  border-bottom: 1px solid #e2ece8;
  background: rgb(255 255 255 / 94%);
  backdrop-filter: blur(14px);
}

.header-identity {
  display: flex;
  align-items: center;
  min-width: 0;
  gap: 11px;
}

.user-avatar {
  display: grid;
  flex: 0 0 auto;
  place-items: center;

  width: 42px;
  height: 42px;

  border-radius: 14px;
  background: #006855;
  color: #ffffff;

  font-size: 13px;
  font-weight: 750;
  letter-spacing: 0.04em;

  box-shadow:
    0 8px 18px
    rgb(0 104 85 / 18%);
}

.user-copy {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.user-copy span {
  color: #758680;
  font-size: 11px;
  font-weight: 650;
  letter-spacing: 0.04em;
  line-height: 1.3;
  text-transform: uppercase;
}

.user-copy strong {
  overflow: hidden;
  color: #173f37;
  font-size: 15px;
  font-weight: 720;
  line-height: 1.45;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.logout-button {
  display: grid;
  flex: 0 0 auto;
  place-items: center;

  width: 42px;
  height: 42px;
  padding: 0;

  border: 1px solid #dce8e4;
  border-radius: 14px;
  background: #ffffff;
  color: #566c65;

  transition:
    border-color 160ms ease,
    color 160ms ease,
    background-color 160ms ease;
}

.logout-button:hover {
  border-color: #f2c3c0;
  background: #fff5f4;
  color: #c72c28;
}

.logout-button:disabled {
  opacity: 0.55;
}

.surveyor-content {
  min-height: calc(100dvh - 72px);
  padding:
    20px
    18px
    calc(
      104px
      + env(safe-area-inset-bottom, 0px)
    );
}

.bottom-navigation {
  position: fixed;
  z-index: 30;
  right: 0;
  bottom: 0;
  left: 0;

  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));

  min-height: 74px;
  padding:
    8px
    8px
    calc(
      7px
      + env(safe-area-inset-bottom, 0px)
    );

  border-top: 1px solid #dfe9e5;
  background: rgb(255 255 255 / 97%);
  box-shadow:
    0 -10px 30px
    rgb(27 57 49 / 8%);
  backdrop-filter: blur(16px);
}

.navigation-item {
  position: relative;

  display: flex;
  min-width: 0;
  align-items: center;
  flex-direction: column;
  justify-content: center;
  gap: 3px;

  border-radius: 14px;
  color: #7a8c86;

  text-decoration: none;

  transition:
    color 160ms ease,
    transform 160ms ease;
}

.navigation-item.active {
  color: #006855;
}

.navigation-icon {
  display: grid;
  place-items: center;

  width: 32px;
  height: 29px;
}

.navigation-label {
  overflow: hidden;
  max-width: 100%;

  font-size: 10px;
  font-weight: 650;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navigation-item.primary {
  transform: translateY(-17px);
}

.navigation-item.primary .navigation-icon {
  width: 54px;
  height: 54px;

  border: 5px solid #f8fbfa;
  border-radius: 19px;
  background: #006855;
  color: #ffffff;

  box-shadow:
    0 10px 22px
    rgb(0 104 85 / 26%);
}

.navigation-item.primary.active .navigation-icon {
  background: #e8312d;
  box-shadow:
    0 10px 22px
    rgb(232 49 45 / 25%);
}

.navigation-item.primary .navigation-label {
  margin-top: 1px;
  color: #50665f;
}

.navigation-item.primary.active .navigation-label {
  color: #b31f1c;
}

@media (min-width: 640px) {
  .surveyor-shell {
    width: min(100%, 560px);
    margin-inline: auto;

    border-right: 1px solid #e0eae6;
    border-left: 1px solid #e0eae6;

    box-shadow:
      0 0 45px
      rgb(32 63 54 / 7%);
  }

  .bottom-navigation {
    right: auto;
    left: 50%;

    width: min(100%, 560px);

    transform: translateX(-50%);

    border-right: 1px solid #dfe9e5;
    border-left: 1px solid #dfe9e5;
  }
}
</style>