<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user)

const initials = computed(() => {
  const name = user.value?.name ?? 'User'

  return name
    .split(' ')
    .slice(0, 2)
    .map((word) =>
      word.charAt(0).toUpperCase(),
    )
    .join('')
})

async function logout(): Promise<void> {
  await authStore.logout()
  await router.replace('/login')
}
</script>

<template>
  <div
    class="min-h-screen bg-slate-50 lg:grid lg:grid-cols-[280px_1fr]"
  >
    <!-- Sidebar -->
    <aside
      class="bg-linear-to-b from-slate-800 to-slate-950 px-4 py-4 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:px-5 lg:py-6"
    >
      <div
        class="flex items-center gap-3 lg:border-b lg:border-white/10 lg:px-2 lg:pb-6"
      >
        <span
          class="grid size-11 place-items-center rounded-2xl bg-linear-to-br from-brand-500 to-accent-400 text-xl font-black text-white shadow-lg shadow-brand-500/20"
        >
          S
        </span>

        <span class="grid leading-tight">
          <strong class="text-lg tracking-wide">
            SIPBPNT
          </strong>

          <small class="mt-0.5 text-xs text-white/55">
            Kota Mojokerto
          </small>
        </span>
      </div>

      <nav class="mt-7 hidden gap-1.5 lg:grid">
        <span
          class="px-3 pb-2 text-xs font-extrabold tracking-widest text-white/40 uppercase"
        >
          Menu utama
        </span>

        <button
          v-for="module in user?.modules"
          :key="module"
          type="button"
          :class="[
            'flex min-h-12 w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition',
            module === 'Dashboard'
              ? 'bg-brand-500/20 text-white'
              : 'text-white/70 hover:bg-white/10 hover:text-white',
          ]"
        >
          <span
            :class="[
              'size-2 rounded-full',
              module === 'Dashboard'
                ? 'bg-accent-400'
                : 'bg-current',
            ]"
          />

          {{ module }}
        </button>
      </nav>

      <div
        class="mt-auto hidden border-t border-white/10 px-3 pt-5 text-xs text-white/40 lg:block"
      >
        SIPBPNT versi pengembangan
      </div>
    </aside>

    <!-- Main -->
    <main class="min-w-0">
      <header
        class="flex min-h-21 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8"
      >
        <div>
          <span class="text-xs font-bold text-slate-500">
            Sistem Informasi BPNT
          </span>

          <h1
            class="mt-0.5 text-xl font-black text-slate-900 sm:text-2xl"
          >
            Dashboard
          </h1>
        </div>

        <div class="flex items-center gap-3">
          <div
            class="grid size-11 place-items-center rounded-xl bg-brand-100 text-sm font-black text-brand-700"
          >
            {{ initials }}
          </div>

          <div class="hidden min-w-30 leading-tight sm:grid">
            <strong class="text-sm text-slate-800">
              {{ user?.name }}
            </strong>

            <small class="mt-1 text-xs text-slate-500">
              {{ user?.role_label }}
            </small>
          </div>

          <button
            type="button"
            :disabled="authStore.loading"
            class="min-h-10 rounded-xl bg-red-50 px-3 py-2 text-xs font-extrabold text-red-700 transition hover:bg-red-100 focus:ring-4 focus:ring-red-500/15 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
            @click="logout"
          >
            Keluar
          </button>
        </div>
      </header>

      <div class="p-4 sm:p-6 lg:p-8">
        <!-- Welcome -->
        <section
          class="relative overflow-hidden rounded-3xl bg-linear-to-br from-brand-500 to-brand-700 p-6 text-white shadow-brand sm:p-8"
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

              <h2 class="mt-1 text-2xl font-black sm:text-3xl">
                {{ user?.name }}
              </h2>

              <p
                class="mt-2 max-w-2xl leading-7 text-white/80"
              >
                Sistem telah berhasil terhubung
                dengan backend Laravel dan
                autentikasi berjalan menggunakan
                session.
              </p>
            </div>

            <span
              class="shrink-0 rounded-full border border-white/20 bg-white/15 px-4 py-2 text-sm font-extrabold backdrop-blur"
            >
              {{ user?.role_label }}
            </span>
          </div>
        </section>

        <!-- Summary -->
        <section
          class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
          <article
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
          >
            <span class="text-sm font-bold text-slate-500">
              Periode Aktif
            </span>

            <strong
              class="mt-2 block text-xl font-black text-slate-900"
            >
              Belum tersedia
            </strong>

            <small class="mt-1 block text-slate-400">
              Menunggu pembuatan periode
            </small>
          </article>

          <article
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
          >
            <span class="text-sm font-bold text-slate-500">
              Total KPM
            </span>

            <strong
              class="mt-2 block text-3xl font-black text-slate-900"
            >
              0
            </strong>

            <small class="mt-1 block text-slate-400">
              Menunggu import BNBA
            </small>
          </article>

          <article
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
          >
            <span class="text-sm font-bold text-slate-500">
              Sudah Transaksi
            </span>

            <strong
              class="mt-2 block text-3xl font-black text-emerald-600"
            >
              0
            </strong>

            <small class="mt-1 block text-slate-400">
              Belum ada transaksi
            </small>
          </article>

          <article
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
          >
            <span class="text-sm font-bold text-slate-500">
              Belum Transaksi
            </span>

            <strong
              class="mt-2 block text-3xl font-black text-amber-600"
            >
              0
            </strong>

            <small class="mt-1 block text-slate-400">
              Belum ada transaksi
            </small>
          </article>
        </section>

        <!-- Modules -->
        <section
          class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6"
        >
          <div
            class="flex flex-col items-start justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center"
          >
            <div>
              <span
                class="text-xs font-extrabold tracking-wider text-brand-600 uppercase"
              >
                Hak akses
              </span>

              <h2
                class="mt-1 text-xl font-black text-slate-900"
              >
                Modul untuk {{ user?.role_label }}
              </h2>
            </div>

            <span
              class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-extrabold text-amber-800"
            >
              Dalam pengembangan
            </span>
          </div>

          <div
            class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3"
          >
            <article
              v-for="module in user?.modules"
              :key="module"
              class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-200 hover:bg-brand-50"
            >
              <span
                class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-100 font-black text-brand-700"
              >
                {{ module.charAt(0) }}
              </span>

              <div class="grid">
                <strong class="text-sm text-slate-800">
                  {{ module }}
                </strong>

                <small class="mt-1 text-xs leading-5 text-slate-500">
                  Modul akan diaktifkan sesuai
                  tahapan pembangunan.
                </small>
              </div>
            </article>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>