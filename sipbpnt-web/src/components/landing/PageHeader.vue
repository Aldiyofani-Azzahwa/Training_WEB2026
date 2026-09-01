<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { ChevronRight } from '@lucide/vue'

import {
  revealDirective as vReveal,
} from '@/directives/reveal'

withDefaults(
  defineProps<{
    eyebrow: string
    title: string
    description: string
  }>(),
  {}
)
</script>

<template>
  <section
    class="relative isolate overflow-hidden bg-cream-100 pb-40 pt-40 sm:pb-44 sm:pt-44"
  >
   

    <div
      class="relative mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8"
    >
      <nav
        v-reveal="{
          direction: 'left',
        }"
        class="mb-7 flex flex-wrap items-center gap-2 text-xs text-government-500"
        aria-label="Breadcrumb"
      >
        <RouterLink
          :to="{ name: 'home' }"
          class="transition-colors hover:text-[#0F766E]"
        >
          Beranda
        </RouterLink>

        <ChevronRight
          :size="14"
          aria-hidden="true"
        />

        <span class="text-government-700">
          {{ eyebrow }}
        </span>
      </nav>

      <div class="max-w-3xl">
        <span
          v-reveal="{
            direction: 'left',
            delay: 70,
          }"
          class="inline-block border-l-4 border-[#F97316] pl-3 text-sm font-medium uppercase tracking-[0.12em] text-[#EA580C]"
        >
          {{ eyebrow }}
        </span>

        <h1
          v-reveal="{
            direction: 'left',
            delay: 140,
          }"
          class="mt-5 text-[38px] font-semibold leading-[1.15] tracking-[-0.035em] text-government-800 sm:text-5xl lg:text-[56px]"
        >
          {{ title }}
        </h1>

        <p
          v-reveal="{
            direction: 'up',
            delay: 220,
          }"
          class="mt-6 max-w-2xl text-base leading-8 text-government-500 sm:text-lg"
        >
          {{ description }}
        </p>
      </div>
    </div>

  </section>
</template>