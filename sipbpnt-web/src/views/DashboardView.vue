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

const user =
  computed(
    () =>
      authStore.user,
  )

const modules =
  computed(() => {
    if (!authStore.role) {
      return []
    }

    return getInternalNavigation(
      authStore.role,
    ).filter(
      (item) =>
        item.key
        !== 'dashboard',
    )
  })
</script>

<template>
  <div
    class="p-4 sm:p-6 lg:p-8"
  >
    <section
      class="relative overflow-hidden rounded-3xl bg-linear-to-br from-accent-500 to-accent-600 p-6 text-white shadow-brand sm:p-8"
    >
      <div
        class="absolute -top-20 right-0 size-72 rounded-full bg-accent-300/25 blur-3xl"
      />

      <div
        class="relative flex flex-col items-start justify-between gap-6 md:flex-row md:items-center"
      >
        <div>
          <span
            class="text-xs font-extrabold tracking-wider uppercase"
          >
            Selamat datang
          </span>

          <h2
            class="mt-1 text-2xl font-black sm:text-3xl"
          >
            {{ user?.name }}
          </h2>

          <p
            class="mt-2 max-w-2xl leading-7 text-white/80"
          >
            Gunakan menu di sidebar
            untuk mengakses modul SIPBPNT
            sesuai hak akses Anda.
          </p>
        </div>

        <span
          class="shrink-0 rounded-full border border-white/20 bg-white/15 px-4 py-2 text-sm font-extrabold backdrop-blur"
        >
          {{ user?.role_label }}
        </span>
      </div>
    </section>

    <section
      class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <span
          class="text-sm font-bold text-slate-500"
        >
          Periode Aktif
        </span>

        <strong
          class="mt-2 block text-xl font-black text-slate-900"
        >
          Belum tersedia
        </strong>

        <small
          class="mt-1 block text-slate-400"
        >
          Menunggu dashboard API
        </small>
      </article>

      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <span
          class="text-sm font-bold text-slate-500"
        >
          Total KPM
        </span>

        <strong
          class="mt-2 block text-3xl font-black text-slate-900"
        >
          0
        </strong>

        <small
          class="mt-1 block text-slate-400"
        >
          Data dashboard belum tersedia
        </small>
      </article>

      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <span
          class="text-sm font-bold text-slate-500"
        >
          Sudah Transaksi
        </span>

        <strong
          class="mt-2 block text-3xl font-black text-emerald-600"
        >
          0
        </strong>

        <small
          class="mt-1 block text-slate-400"
        >
          Modul transaksi belum tersedia
        </small>
      </article>

      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <span
          class="text-sm font-bold text-slate-500"
        >
          Belum Transaksi
        </span>

        <strong
          class="mt-2 block text-3xl font-black text-amber-600"
        >
          0
        </strong>

        <small
          class="mt-1 block text-slate-400"
        >
          Modul transaksi belum tersedia
        </small>
      </article>
    </section>

    <section
      class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6"
    >
      <div
        class="border-b border-slate-200 pb-5"
      >
        <span
          class="text-xs font-extrabold tracking-wider text-accent-600 uppercase"
        >
          Hak akses
        </span>

        <h2
          class="mt-1 text-xl font-black text-slate-900"
        >
          Modul untuk
          {{ user?.role_label }}
        </h2>
      </div>

      <div
        class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3"
      >
        <template
          v-for="
            module in modules
          "
          :key="module.key"
        >
          <RouterLink
            v-if="
              module.available
              &&
              module.routeName
            "
            :to="{
              name:
                module.routeName,
            }"
            class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-accent-200 hover:bg-accent-50"
          >
            <span
              class="grid size-11 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"
            >
              <component
                :is="
                  module.icon
                "
                :size="20"
              />
            </span>

            <strong
              class="text-sm text-slate-800"
            >
              {{ module.label }}
            </strong>
          </RouterLink>

          <div
            v-else
            class="flex cursor-not-allowed items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-50"
          >
            <span
              class="grid size-11 shrink-0 place-items-center rounded-xl bg-slate-200 text-slate-500"
            >
              <component
                :is="
                  module.icon
                "
                :size="20"
              />
            </span>

            <strong
              class="text-sm text-slate-600"
            >
              {{ module.label }}
            </strong>
          </div>
        </template>
      </div>
    </section>
  </div>
</template>