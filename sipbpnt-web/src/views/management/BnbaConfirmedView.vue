<script setup lang="ts">
import {
  onMounted,
} from 'vue'
import {
  storeToRefs,
} from 'pinia'

import {
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  Database,
  LoaderCircle,
  RotateCcw,
  Search,
} from 'lucide-vue-next'

import { RouterLink } from 'vue-router'

import {
  useBnbaParticipantsStore,
} from '@/stores/bnbaParticipants'

const store =
  useBnbaParticipantsStore()

const {
  periods,
  participants,
  options,
  meta,

  periodId,
  search,
  kecamatan,
  kelurahan,
  eWarung,

  isLoading,
  isLoadingPeriods,
  errorMessage,

  selectedPeriod,
} = storeToRefs(store)

onMounted(async () => {
  await store.fetchPeriods()
})

function formatCurrency(
  value: number,
): string {
  return new Intl.NumberFormat(
    'id-ID',
    {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    },
  ).format(value)
}

async function handlePeriodChange(
  event: Event,
): Promise<void> {
  const target =
    event.target as HTMLSelectElement

  const value =
    Number(target.value)

  if (
    !Number.isInteger(value)
    || value <= 0
  ) {
    return
  }

  await store.selectPeriod(value)
}
</script>

<template>
  <div
    class="min-h-screen bg-slate-50"
  >
    <header
      class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur"
    >
      <div
        class="mx-auto flex min-h-16 max-w-[1500px] items-center gap-3 px-4 sm:px-6 lg:px-8"
      >
        <RouterLink
          to="/dashboard"
          class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50"
          aria-label="Kembali ke dashboard"
        >
          <ArrowLeft
            :size="20"
            aria-hidden="true"
          />
        </RouterLink>

        <div>
          <p
            class="text-xs font-semibold uppercase tracking-[0.15em] text-[#006855]"
          >
            Data BPNT
          </p>

          <p
            class="font-bold text-slate-900"
          >
            BNBA Terkonfirmasi
          </p>
        </div>
      </div>
    </header>

    <main
      class="mx-auto max-w-[1500px] px-4 py-7 sm:px-6 lg:px-8"
    >
      <div class="mb-7">
        <div
          class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-[#006855]/10 text-[#006855]"
        >
          <Database
            :size="24"
            aria-hidden="true"
          />
        </div>

        <h1
          class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl"
        >
          Data BNBA Terkonfirmasi
        </h1>

        <p
          class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 sm:text-base"
        >
          Menampilkan data KPM yang telah melewati
          proses import dan konfirmasi.
        </p>
      </div>

      <div
        v-if="errorMessage"
        class="mb-5 rounded-2xl border border-[#E8312D]/20 bg-[#E8312D]/5 p-4 text-sm font-medium text-[#b82320]"
      >
        {{ errorMessage }}
      </div>

      <section
        class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <div
          class="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
        >
          <div>
            <label
              for="period"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Periode
            </label>

            <select
              id="period"
              :value="periodId ?? ''"
              :disabled="isLoadingPeriods"
              class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10"
              @change="handlePeriodChange"
            >
              <option value="">
                Pilih periode
              </option>

              <option
                v-for="period in periods"
                :key="period.id"
                :value="period.id"
              >
                {{ period.name }}
                — {{ period.year }}
              </option>
            </select>
          </div>

          <div>
            <label
              for="kecamatan"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Kecamatan
            </label>

            <select
              id="kecamatan"
              v-model="kecamatan"
              class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855]"
            >
              <option value="">
                Semua Kecamatan
              </option>

              <option
                v-for="item in options.kecamatan"
                :key="item"
                :value="item"
              >
                {{ item }}
              </option>
            </select>
          </div>

          <div>
            <label
              for="kelurahan"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Kelurahan
            </label>

            <select
              id="kelurahan"
              v-model="kelurahan"
              class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855]"
            >
              <option value="">
                Semua Kelurahan
              </option>

              <option
                v-for="item in options.kelurahan"
                :key="item"
                :value="item"
              >
                {{ item }}
              </option>
            </select>
          </div>

          <div>
            <label
              for="ewarung"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              E-Warung
            </label>

            <select
              id="ewarung"
              v-model="eWarung"
              class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855]"
            >
              <option value="">
                Semua E-Warung
              </option>

              <option
                v-for="item in options.e_warungs"
                :key="item"
                :value="item"
              >
                {{ item }}
              </option>
            </select>
          </div>

          <div>
            <label
              for="search"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Pencarian
            </label>

            <div class="relative">
              <Search
                :size="17"
                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                aria-hidden="true"
              />

              <input
                id="search"
                v-model="search"
                type="search"
                placeholder="Nama atau NIK"
                class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 text-sm outline-none focus:border-[#006855]"
                @keyup.enter="
                  store.applyFilters()
                "
              >
            </div>
          </div>
        </div>

        <div
          class="mt-4 flex flex-wrap justify-end gap-2"
        >
          <button
            type="button"
            class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700"
            @click="store.clearFilters()"
          >
            <RotateCcw
              :size="16"
              aria-hidden="true"
            />

            Reset
          </button>

          <button
            type="button"
            class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-[#006855] px-5 text-sm font-bold text-white"
            @click="store.applyFilters()"
          >
            <Search
              :size="16"
              aria-hidden="true"
            />

            Terapkan Filter
          </button>
        </div>
      </section>

      <div
        v-if="selectedPeriod"
        class="mb-4 text-sm text-slate-500"
      >
        Periode:
        <strong class="text-slate-800">
          {{ selectedPeriod.name }}
        </strong>
        · {{ meta.total }} KPM
      </div>

      <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <div
          v-if="isLoading"
          class="flex min-h-72 items-center justify-center"
        >
          <LoaderCircle
            :size="28"
            class="animate-spin text-[#006855]"
          />
        </div>

        <div
          v-else-if="participants.length === 0"
          class="flex min-h-72 items-center justify-center p-6 text-center text-sm text-slate-500"
        >
          Belum ada data BNBA terkonfirmasi
          untuk filter yang dipilih.
        </div>

        <template v-else>
          <div
            class="hidden overflow-x-auto md:block"
          >
            <table
              class="min-w-[1050px] w-full text-left"
            >
              <thead
                class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"
              >
                <tr>
                  <th class="px-4 py-3">
                    KPM
                  </th>

                  <th class="px-4 py-3">
                    NIK / NKK
                  </th>

                  <th class="px-4 py-3">
                    Wilayah
                  </th>

                  <th class="px-4 py-3">
                    E-Warung
                  </th>

                  <th class="px-4 py-3">
                    Status Sumber
                  </th>

                  <th class="px-4 py-3">
                    Nominal
                  </th>
                </tr>
              </thead>

              <tbody
                class="divide-y divide-slate-100"
              >
                <tr
                  v-for="participant in participants"
                  :key="participant.id"
                  class="align-top hover:bg-slate-50"
                >
                  <td class="px-4 py-4">
                    <p
                      class="font-bold text-slate-900"
                    >
                      {{
                        participant.kpm.full_name
                      }}
                    </p>

                    <p
                      class="mt-1 text-xs text-slate-500"
                    >
                      Kepesertaan:
                      {{
                        participant.membership_year
                        || '-'
                      }}
                    </p>
                  </td>

                  <td
                    class="px-4 py-4 font-mono text-sm text-slate-700"
                  >
                    <p>
                      {{
                        participant.kpm.nik
                        || '-'
                      }}
                    </p>

                    <p
                      class="mt-1 text-xs text-slate-500"
                    >
                      KK:
                      {{
                        participant.kpm.nkk
                        || '-'
                      }}
                    </p>
                  </td>

                  <td class="px-4 py-4">
                    <p
                      class="text-sm font-semibold text-slate-800"
                    >
                      {{
                        participant.kpm.kelurahan
                      }}
                    </p>

                    <p
                      class="mt-1 text-xs text-slate-500"
                    >
                      {{
                        participant.kpm.kecamatan
                      }}
                    </p>

                    <p
                      class="mt-2 max-w-60 text-xs leading-5 text-slate-500"
                    >
                      {{
                        participant.kpm.address
                      }}
                    </p>
                  </td>

                  <td
                    class="max-w-60 px-4 py-4 text-sm text-slate-700"
                  >
                    {{
                      participant.e_warung_name
                      || '-'
                    }}
                  </td>

                  <td class="px-4 py-4">
                    <p
                      class="text-sm font-medium text-slate-700"
                    >
                      {{
                        participant.source_status
                        || '-'
                      }}
                    </p>

                    <p
                      class="mt-1 text-xs text-slate-500"
                    >
                      {{
                        participant.source_description
                        || '-'
                      }}
                    </p>
                  </td>

                  <td
                    class="whitespace-nowrap px-4 py-4 text-sm font-bold text-slate-900"
                  >
                    {{
                      formatCurrency(
                        participant.entitlement_amount,
                      )
                    }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            class="divide-y divide-slate-100 md:hidden"
          >
            <article
              v-for="participant in participants"
              :key="participant.id"
              class="p-4"
            >
              <div
                class="flex items-start justify-between gap-3"
              >
                <div>
                  <p
                    class="font-bold text-slate-900"
                  >
                    {{
                      participant.kpm.full_name
                    }}
                  </p>

                  <p
                    class="mt-1 font-mono text-xs text-slate-500"
                  >
                    {{
                      participant.kpm.nik
                    }}
                  </p>
                </div>

                <p
                  class="whitespace-nowrap text-sm font-bold text-[#006855]"
                >
                  {{
                    formatCurrency(
                      participant.entitlement_amount,
                    )
                  }}
                </p>
              </div>

              <div
                class="mt-4 space-y-2 text-sm"
              >
                <p class="text-slate-600">
                  <strong>Wilayah:</strong>
                  {{
                    participant.kpm.kelurahan
                  }},
                  {{
                    participant.kpm.kecamatan
                  }}
                </p>

                <p class="text-slate-600">
                  <strong>E-Warung:</strong>
                  {{
                    participant.e_warung_name
                    || '-'
                  }}
                </p>

                <p class="text-slate-600">
                  <strong>Status:</strong>
                  {{
                    participant.source_status
                    || '-'
                  }}
                </p>
              </div>
            </article>
          </div>
        </template>

        <div
          v-if="meta.total > 0"
          class="flex flex-col gap-3 border-t border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <p class="text-sm text-slate-500">
            Halaman
            {{ meta.current_page }}
            dari
            {{ meta.last_page }}
            · {{ meta.total }} data
          </p>

          <div class="flex gap-2">
            <button
              type="button"
              :disabled="
                isLoading
                || meta.current_page <= 1
              "
              class="inline-flex min-h-10 items-center gap-1 rounded-xl border border-slate-300 px-3 text-sm font-semibold disabled:opacity-40"
              @click="
                store.changePage(
                  meta.current_page - 1,
                )
              "
            >
              <ChevronLeft
                :size="17"
              />

              Sebelumnya
            </button>

            <button
              type="button"
              :disabled="
                isLoading
                || meta.current_page
                  >= meta.last_page
              "
              class="inline-flex min-h-10 items-center gap-1 rounded-xl border border-slate-300 px-3 text-sm font-semibold disabled:opacity-40"
              @click="
                store.changePage(
                  meta.current_page + 1,
                )
              "
            >
              Berikutnya

              <ChevronRight
                :size="17"
              />
            </button>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>