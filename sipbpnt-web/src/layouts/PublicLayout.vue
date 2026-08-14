<script setup lang="ts">
import { RouterView } from 'vue-router'

import PublicFooter from '@/components/landing/PublicFooter.vue'
import PublicNavbar from '@/components/landing/PublicNavbar.vue'
</script>

<template>
  <div class="min-h-screen bg-white">
    <a
      href="#main-content"
      class="fixed left-4 top-4 z-[100] -translate-y-24 rounded-xl bg-government-800 px-4 py-3 text-sm font-medium text-white transition-transform focus:translate-y-0"
    >
      Langsung ke konten utama
    </a>

    <PublicNavbar />

    <main id="main-content">
      <RouterView />
    </main>

    <PublicFooter />
  </div>
</template>