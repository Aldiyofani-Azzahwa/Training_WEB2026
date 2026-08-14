<script setup lang="ts">
import { computed } from 'vue'
import {
  CheckCircle2,
  CircleAlert,
  Files,
  ShieldAlert,
  TriangleAlert,
} from 'lucide-vue-next'

import type {
  BnbaImport,
  BnbaRowStatus,
} from '@/types/bnba'

const props = defineProps<{
  importData: BnbaImport
  activeStatus: BnbaRowStatus | null
}>()

const emit = defineEmits<{
  filter: [
    status: BnbaRowStatus | null,
  ]
}>()

const cards = computed(() => [
  {
    key: null,
    label: 'Semua Data',
    value:
      props.importData.summary.total,
    icon: Files,
    activeClass:
      'border-slate-500 bg-slate-50 ring-slate-200',
    iconClass:
      'bg-slate-100 text-slate-600',
  },
  {
    key: 'valid' as const,
    label: 'Valid',
    value:
      props.importData.summary.valid,
    icon: CheckCircle2,
    activeClass:
      'border-[#006855] bg-[#006855]/5 ring-[#006855]/15',
    iconClass:
      'bg-[#006855]/10 text-[#006855]',
  },
  {
    key: 'warning' as const,
    label: 'Warning',
    value:
      props.importData.summary.warning,
    icon: TriangleAlert,
    activeClass:
      'border-[#FFAF1C] bg-[#FFAF1C]/5 ring-[#FFAF1C]/20',
    iconClass:
      'bg-[#FFAF1C]/15 text-amber-700',
  },
  {
    key: 'invalid' as const,
    label: 'Invalid',
    value:
      props.importData.summary.invalid,
    icon: CircleAlert,
    activeClass:
      'border-[#E8312D] bg-[#E8312D]/5 ring-[#E8312D]/15',
    iconClass:
      'bg-[#E8312D]/10 text-[#E8312D]',
  },
  {
    key: 'duplicate' as const,
    label: 'Duplicate',
    value:
      props.importData.summary.duplicate,
    icon: ShieldAlert,
    activeClass:
      'border-violet-500 bg-violet-50 ring-violet-100',
    iconClass:
      'bg-violet-100 text-violet-700',
  },
])
</script>

<template>
  <section>
    <div
      class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between"
    >
      <div>
        <h2
          class="text-lg font-bold text-slate-900"
        >
          Hasil validasi
        </h2>

        <p
          class="mt-1 text-sm text-slate-500"
        >
          Klik kartu untuk memfilter data preview.
        </p>
      </div>

      <span
        class="text-sm font-medium text-slate-500"
      >
        {{ importData.original_name }}
      </span>
    </div>

    <div
      class="grid grid-cols-2 gap-3 lg:grid-cols-5"
    >
      <button
        v-for="card in cards"
        :key="card.label"
        type="button"
        class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4"
        :class="
          activeStatus === card.key
            ? card.activeClass
            : 'border-slate-200 ring-transparent'
        "
        @click="emit('filter', card.key)"
      >
        <div
          class="mb-4 flex size-10 items-center justify-center rounded-xl"
          :class="card.iconClass"
        >
          <component
            :is="card.icon"
            :size="20"
            aria-hidden="true"
          />
        </div>

        <p
          class="text-2xl font-bold tracking-tight text-slate-900"
        >
          {{ card.value }}
        </p>

        <p
          class="mt-1 text-sm font-semibold text-slate-500"
        >
          {{ card.label }}
        </p>
      </button>
    </div>
  </section>
</template>