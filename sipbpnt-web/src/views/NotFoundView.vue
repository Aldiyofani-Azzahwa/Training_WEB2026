<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <main
    class="grid min-h-screen place-items-center bg-linear-to-b from-brand-50 to-white px-5 py-12 text-center"
  >
    <div class="max-w-xl">
      <span
        class="text-7xl font-black text-brand-500 sm:text-8xl"
      >
        404
      </span>

      <h1
        class="mt-3 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl"
      >
        Halaman tidak ditemukan
      </h1>

      <p class="mt-4 leading-7 text-slate-600">
        Alamat yang Anda buka tidak tersedia,
        telah dipindahkan, atau Anda tidak memiliki
        akses ke halaman tersebut.
      </p>

      <RouterLink
        to="/"
        class="mt-7 inline-flex min-h-12 items-center justify-center rounded-xl bg-brand-500 px-6 py-3 font-bold text-white shadow-lg shadow-brand-500/25 transition hover:-translate-y-0.5 hover:bg-brand-600 focus:ring-4 focus:ring-brand-500/20 focus:outline-none"
      >
        Kembali ke Beranda
      </RouterLink>
    </div>
  </main>
</template>