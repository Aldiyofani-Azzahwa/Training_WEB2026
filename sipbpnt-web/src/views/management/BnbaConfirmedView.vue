<script setup lang="ts">
import {
  onMounted,
} from 'vue'

import {
  storeToRefs,
} from 'pinia'

import {
  ChevronLeft,
  ChevronRight,
  Database,
  LoaderCircle,
  RotateCcw,
  Search,
} from '@lucide/vue'

import {
  useRoute,
} from 'vue-router'

import {
  useBnbaParticipantsStore,
} from '@/stores/bnbaParticipants'

const route =
  useRoute()

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
} = storeToRefs(
  store,
)

onMounted(
  async () => {
    const rawQuery =
      Array.isArray(
        route.query.period,
      )
        ? route.query
            .period[0]
        : route.query.period

    const parsedPeriodId =
      typeof rawQuery
      === 'string'
        ? Number(
            rawQuery,
          )
        : Number.NaN

    const preferredPeriodId =
      Number.isInteger(
        parsedPeriodId,
      )
      &&
      parsedPeriodId > 0
        ? parsedPeriodId
        : undefined

    await store
      .fetchPeriods(
        preferredPeriodId,
      )
  },
)

function formatCurrency(
  value: number,
): string {
  return new Intl.NumberFormat(
    'id-ID',
    {
      style:
        'currency',

      currency:
        'IDR',

      maximumFractionDigits:
        0,
    },
  ).format(
    value,
  )
}

async function handlePeriodChange(
  event: Event,
): Promise<void> {
  const select =
    event.currentTarget

  if (
    !(
      select
      instanceof HTMLSelectElement
    )
  ) {
    return
  }

  const value =
    Number(
      select.value,
    )

  if (
    !Number.isInteger(
      value,
    )
    ||
    value <= 0
  ) {
    return
  }

  await store
    .selectPeriod(
      value,
    )
}
</script>

<template>
  <main
    class="mx-auto max-w-[1500px] px-4 py-7 sm:px-6 lg:px-8"
  >
    <!-- HEADER -->
    <div
      class="mb-7"
    >
      <div
        class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-[#006855]/10 text-[#006855]"
      >
        <Database
          :size="24"
          aria-hidden="true"
        />
      </div>

      <h1
        class="text-2xl font-bold text-slate-950 sm:text-3xl"
      >
        Data BNBA Terkonfirmasi
      </h1>

      <p
        class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 sm:text-base"
      >
        Menampilkan data KPM dari periode
        yang telah memiliki BNBA terkonfirmasi.
      </p>
    </div>

    <!-- ERROR -->
    <div
      v-if="
        errorMessage
      "
      class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700"
    >
      {{ errorMessage }}
    </div>

    <!-- FILTER -->
    <section
      class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
    >
      <div
        class="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
      >
        <!-- PERIODE -->
        <div>
          <label
            for="period"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Periode
          </label>

          <select
            id="period"
            :value="
              periodId
              ?? ''
            "
            :disabled="
              isLoadingPeriods
              ||
              periods.length
              === 0
            "
            class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10 disabled:bg-slate-100"
            @change="
              handlePeriodChange
            "
          >
            <option
              value=""
              disabled
            >
              {{
                isLoadingPeriods
                  ? 'Memuat periode...'
                  : 'Pilih periode'
              }}
            </option>

            <option
              v-for="
                period in periods
              "
              :key="
                period.id
              "
              :value="
                period.id
              "
            >
              {{ period.name }}
            </option>
          </select>
        </div>

        <!-- KECAMATAN -->
        <div>
          <label
            for="kecamatan"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Kecamatan
          </label>

          <select
            id="kecamatan"
            v-model="
              kecamatan
            "
            :disabled="
              periodId === null
            "
            class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm disabled:bg-slate-100"
          >
            <option value="">
              Semua Kecamatan
            </option>

            <option
              v-for="
                item in options.kecamatan
              "
              :key="
                item
              "
              :value="
                item
              "
            >
              {{ item }}
            </option>
          </select>
        </div>

        <!-- KELURAHAN -->
        <div>
          <label
            for="kelurahan"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Kelurahan
          </label>

          <select
            id="kelurahan"
            v-model="
              kelurahan
            "
            :disabled="
              periodId === null
            "
            class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm disabled:bg-slate-100"
          >
            <option value="">
              Semua Kelurahan
            </option>

            <option
              v-for="
                item in options.kelurahan
              "
              :key="
                item
              "
              :value="
                item
              "
            >
              {{ item }}
            </option>
          </select>
        </div>

        <!-- E WARUNG -->
        <div>
          <label
            for="ewarung"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            E-Warung
          </label>

          <select
            id="ewarung"
            v-model="
              eWarung
            "
            :disabled="
              periodId === null
            "
            class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm disabled:bg-slate-100"
          >
            <option value="">
              Semua E-Warung
            </option>

            <option
              v-for="
                item in options.e_warungs
              "
              :key="
                item
              "
              :value="
                item
              "
            >
              {{ item }}
            </option>
          </select>
        </div>

        <!-- SEARCH -->
        <div>
          <label
            for="search"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Pencarian
          </label>

          <div
            class="relative"
          >
            <Search
              :size="17"
              class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            />

            <input
              id="search"
              v-model="
                search
              "
              type="search"
              :disabled="
                periodId === null
              "
              placeholder="Nama atau NIK..."
              class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm disabled:bg-slate-100"
              @keyup.enter="
                store.applyFilters()
              "
            >
          </div>
        </div>
      </div>

      <div
        class="mt-4 flex justify-end gap-2"
      >
        <button
          type="button"
          :disabled="
            periodId === null
            ||
            isLoading
          "
          class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 disabled:opacity-50"
          @click="
            store.clearFilters()
          "
        >
          <RotateCcw
            :size="16"
          />

          Reset
        </button>

        <button
          type="button"
          :disabled="
            periodId === null
            ||
            isLoading
          "
          class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-[#006855] px-5 text-sm font-bold text-white disabled:opacity-50"
          @click="
            store.applyFilters()
          "
        >
          <LoaderCircle
            v-if="
              isLoading
            "
            :size="16"
            class="animate-spin"
          />

          <Search
            v-else
            :size="16"
          />

          Terapkan Filter
        </button>
      </div>
    </section>

    <!-- PERIOD SUMMARY -->
    <section
      v-if="
        selectedPeriod
      "
      class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
    >
      <div
        class="flex flex-wrap gap-x-10 gap-y-4"
      >
        <div>
          <p
            class="text-xs font-semibold text-slate-400"
          >
            Periode
          </p>

          <strong
            class="mt-1 block text-slate-900"
          >
            {{ selectedPeriod.name }}
          </strong>
        </div>

        <div>
          <p
            class="text-xs font-semibold text-slate-400"
          >
            Tahun
          </p>

          <strong
            class="mt-1 block text-slate-900"
          >
            {{ selectedPeriod.year }}
          </strong>
        </div>

        <div>
          <p
            class="text-xs font-semibold text-slate-400"
          >
            Total
          </p>

          <strong
            class="mt-1 block text-slate-900"
          >
            {{ meta.total }} KPM
          </strong>
        </div>
      </div>
    </section>

    <!-- TABLE -->
    <section
      class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
      <div
        v-if="
          isLoading
        "
        class="flex min-h-72 flex-col items-center justify-center gap-3"
      >
        <LoaderCircle
          :size="28"
          class="animate-spin text-[#006855]"
        />

        <span
          class="text-sm text-slate-500"
        >
          Memuat data BNBA...
        </span>
      </div>

      <div
        v-else-if="
          participants.length
          === 0
        "
        class="flex min-h-72 flex-col items-center justify-center p-6 text-center"
      >
        <Database
          :size="36"
          class="text-slate-300"
        />

        <strong
          class="mt-4 text-slate-800"
        >
          {{
            periods.length === 0
              ? 'Belum ada BNBA terkonfirmasi'
              : 'Data tidak ditemukan'
          }}
        </strong>
      </div>

      <template
        v-else
      >
        <div
          class="overflow-x-auto"
        >
          <table
            class="w-full min-w-[1000px] text-left"
          >
            <thead
              class="bg-slate-50 text-xs uppercase text-slate-500"
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
                v-for="
                  participant in participants
                "
                :key="
                  participant.id
                "
                class="align-top hover:bg-slate-50"
              >
                <td
                  class="px-4 py-4"
                >
                  <strong
                    class="text-slate-900"
                  >
                    {{
                      participant
                        .kpm
                        .full_name
                    }}
                  </strong>

                  <p
                    class="mt-1 text-xs text-slate-500"
                  >
                    Kepesertaan:
                    {{
                      participant
                        .membership_year
                      || '-'
                    }}
                  </p>
                </td>

                <td
                  class="px-4 py-4 font-mono text-sm"
                >
                  <p>
                    {{
                      participant
                        .kpm
                        .nik
                      || '-'
                    }}
                  </p>

                  <p
                    class="mt-1 text-xs text-slate-500"
                  >
                    KK:
                    {{
                      participant
                        .kpm
                        .nkk
                      || '-'
                    }}
                  </p>
                </td>

                <td
                  class="px-4 py-4"
                >
                  <strong
                    class="text-sm text-slate-800"
                  >
                    {{
                      participant
                        .kpm
                        .kelurahan
                    }}
                  </strong>

                  <p
                    class="mt-1 text-xs text-slate-500"
                  >
                    {{
                      participant
                        .kpm
                        .kecamatan
                    }}
                  </p>

                  <p
                    class="mt-2 max-w-60 text-xs leading-5 text-slate-500"
                  >
                    {{
                      participant
                        .kpm
                        .address
                    }}
                  </p>
                </td>

                <td
                  class="max-w-60 px-4 py-4 text-sm"
                >
                  {{
                    participant
                      .e_warung_name
                    || '-'
                  }}
                </td>

                <td
                  class="px-4 py-4 text-sm"
                >
                  <p>
                    {{
                      participant
                        .source_status
                      || '-'
                    }}
                  </p>

                  <p
                    class="mt-1 text-xs text-slate-500"
                  >
                    {{
                      participant
                        .source_description
                      || '-'
                    }}
                  </p>
                </td>

                <td
                  class="whitespace-nowrap px-4 py-4 font-bold text-slate-900"
                >
                  {{
                    formatCurrency(
                      participant
                        .entitlement_amount,
                    )
                  }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div
          v-if="
            meta.total > 0
          "
          class="flex flex-col gap-3 border-t border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <p
            class="text-sm text-slate-500"
          >
            Halaman
            {{ meta.current_page }}
            dari
            {{ meta.last_page }}
            ·
            {{ meta.total }} data
          </p>

          <div
            class="flex gap-2"
          >
            <button
              type="button"
              :disabled="
                isLoading
                ||
                meta.current_page
                <= 1
              "
              class="inline-flex min-h-10 items-center gap-1 rounded-xl border border-slate-300 px-3 text-sm font-semibold disabled:opacity-40"
              @click="
                store.changePage(
                  meta.current_page
                  - 1,
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
                ||
                meta.current_page
                >= meta.last_page
              "
              class="inline-flex min-h-10 items-center gap-1 rounded-xl border border-slate-300 px-3 text-sm font-semibold disabled:opacity-40"
              @click="
                store.changePage(
                  meta.current_page
                  + 1,
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
      </template>
    </section>
  </main>
</template>