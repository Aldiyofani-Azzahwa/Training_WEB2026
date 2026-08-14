<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import {
  CalendarDays,
  ChevronDown,
  LoaderCircle,
  Plus,
  X,
} from 'lucide-vue-next'

import type {
  BpntPeriod,
  CreateBpntPeriodPayload,
  LaravelValidationErrors,
} from '@/types/bnba'

const props = defineProps<{
  periods: BpntPeriod[]
  selectedPeriodId: number | null
  isLoading: boolean
  isCreating: boolean
  validationErrors: LaravelValidationErrors
}>()

const emit = defineEmits<{
  select: [periodId: number | null]
  create: [payload: CreateBpntPeriodPayload]
}>()

const showCreateForm = ref(false)

const form = reactive({
  code: '',
  name: '',
  year: '',
})

const canSubmit = computed(() => {
  const year = Number(form.year)

  return (
    form.code.trim() !== ''
    && form.name.trim() !== ''
    && Number.isInteger(year)
    && year >= 2000
    && year <= 2100
    && !props.isCreating
  )
})

function handlePeriodChange(
  event: Event,
): void {
  const target =
    event.target as HTMLSelectElement

  const value = target.value

  emit(
    'select',
    value === ''
      ? null
      : Number(value),
  )
}

function submitPeriod(): void {
  if (!canSubmit.value) {
    return
  }

  emit('create', {
    code: form.code.trim(),
    name: form.name.trim(),
    year: Number(form.year),
    is_active: true,
  })
}
</script>

<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
  >
    <div class="p-5 sm:p-6">
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
      >
        <div>
          <div
            class="mb-2 flex items-center gap-2 text-[#006855]"
          >
            <CalendarDays
              :size="20"
              aria-hidden="true"
            />

            <span
              class="text-sm font-semibold"
            >
              Periode BPNT
            </span>
          </div>

          <h2
            class="text-lg font-bold text-slate-900"
          >
            Pilih periode import
          </h2>

          <p
            class="mt-1 max-w-2xl text-sm leading-6 text-slate-500"
          >
            Data BNBA akan dihubungkan dengan
            periode BPNT yang dipilih.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-[#006855] px-4 text-sm font-semibold text-[#006855] transition hover:bg-[#006855]/5 focus:outline-none focus:ring-2 focus:ring-[#006855]/30"
          @click="showCreateForm = !showCreateForm"
        >
          <X
            v-if="showCreateForm"
            :size="18"
            aria-hidden="true"
          />

          <Plus
            v-else
            :size="18"
            aria-hidden="true"
          />

          {{
            showCreateForm
              ? 'Tutup'
              : 'Buat Periode'
          }}
        </button>
      </div>

      <div class="mt-5">
        <label
          for="bpnt-period"
          class="mb-2 block text-sm font-semibold text-slate-700"
        >
          Periode aktif
        </label>

        <div class="relative">
          <select
            id="bpnt-period"
            :value="selectedPeriodId ?? ''"
            :disabled="isLoading"
            class="min-h-12 w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 pr-11 text-sm font-medium text-slate-800 outline-none transition focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10 disabled:cursor-not-allowed disabled:bg-slate-100"
            @change="handlePeriodChange"
          >
            <option value="">
              {{
                isLoading
                  ? 'Memuat periode...'
                  : 'Pilih periode BPNT'
              }}
            </option>

            <option
              v-for="period in periods"
              :key="period.id"
              :value="period.id"
            >
              {{ period.name }}
              — {{ period.year }}
              {{ period.is_active ? '' : '(Tidak Aktif)' }}
            </option>
          </select>

          <ChevronDown
            :size="18"
            class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"
            aria-hidden="true"
          />
        </div>

        <p
          v-if="periods.length === 0 && !isLoading"
          class="mt-2 text-sm text-amber-700"
        >
          Belum ada periode BPNT. Buat periode
          terlebih dahulu sebelum melakukan import.
        </p>
      </div>
    </div>

    <div
      v-if="showCreateForm"
      class="border-t border-slate-200 bg-slate-50/70 p-5 sm:p-6"
    >
      <div class="mb-5">
        <h3
          class="font-bold text-slate-900"
        >
          Periode BPNT baru
        </h3>

        <p
          class="mt-1 text-sm text-slate-500"
        >
          Gunakan informasi periode resmi yang
          diterima Dinas Sosial.
        </p>
      </div>

      <form
        class="grid gap-4 sm:grid-cols-2"
        @submit.prevent="submitPeriod"
      >
        <div>
          <label
            for="period-code"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Kode periode
          </label>

          <input
            id="period-code"
            v-model="form.code"
            type="text"
            maxlength="50"
            placeholder="Contoh: BPNT-2026-01"
            class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10"
          >

          <p
            v-if="validationErrors.code?.[0]"
            class="mt-1.5 text-sm text-[#E8312D]"
          >
            {{ validationErrors.code[0] }}
          </p>
        </div>

        <div>
          <label
            for="period-year"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Tahun
          </label>

          <input
            id="period-year"
            v-model="form.year"
            type="number"
            min="2000"
            max="2100"
            placeholder="2026"
            class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10"
          >

          <p
            v-if="validationErrors.year?.[0]"
            class="mt-1.5 text-sm text-[#E8312D]"
          >
            {{ validationErrors.year[0] }}
          </p>
        </div>

        <div class="sm:col-span-2">
          <label
            for="period-name"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Nama periode
          </label>

          <input
            id="period-name"
            v-model="form.name"
            type="text"
            maxlength="150"
            placeholder="Contoh: BPNT Tahun 2026"
            class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10"
          >

          <p
            v-if="validationErrors.name?.[0]"
            class="mt-1.5 text-sm text-[#E8312D]"
          >
            {{ validationErrors.name[0] }}
          </p>
        </div>

        <div
          class="flex justify-end sm:col-span-2"
        >
          <button
            type="submit"
            :disabled="!canSubmit"
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#006855] px-5 text-sm font-bold text-white transition hover:bg-[#005646] focus:outline-none focus:ring-4 focus:ring-[#006855]/20 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <LoaderCircle
              v-if="isCreating"
              :size="18"
              class="animate-spin"
              aria-hidden="true"
            />

            <Plus
              v-else
              :size="18"
              aria-hidden="true"
            />

            {{
              isCreating
                ? 'Menyimpan...'
                : 'Simpan Periode'
            }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>