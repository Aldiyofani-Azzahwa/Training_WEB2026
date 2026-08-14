<script setup lang="ts">
import {
  ref,
  watch,
} from 'vue'

import {
  ChevronLeft,
  ChevronRight,
  Inbox,
  LoaderCircle,
  Search,
} from 'lucide-vue-next'

import type {
  BnbaImportRow,
  PaginationMeta,
} from '@/types/bnba'

const props = defineProps<{
  rows: BnbaImportRow[]
  meta: PaginationMeta
  loading: boolean
  search: string
}>()

const emit = defineEmits<{
  search: [keyword: string]
  page: [page: number]
}>()

const keyword = ref(
  props.search,
)

watch(
  () => props.search,
  (value) => {
    keyword.value = value
  },
)

function submitSearch(): void {
  emit(
    'search',
    keyword.value.trim(),
  )
}

function formatCurrency(
  value: number | null,
): string {
  if (value === null) {
    return '-'
  }

  return new Intl.NumberFormat(
    'id-ID',
    {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    },
  ).format(value)
}

function statusLabel(
  status: BnbaImportRow['status'],
): string {
  const labels = {
    valid: 'Valid',
    warning: 'Warning',
    invalid: 'Invalid',
    duplicate: 'Duplicate',
  }

  return labels[status]
}

function statusClass(
  status: BnbaImportRow['status'],
): string {
  const classes = {
    valid:
      'bg-[#006855]/10 text-[#006855]',
    warning:
      'bg-[#FFAF1C]/20 text-amber-800',
    invalid:
      'bg-[#E8312D]/10 text-[#E8312D]',
    duplicate:
      'bg-violet-100 text-violet-700',
  }

  return classes[status]
}

function messages(
  row: BnbaImportRow,
): string[] {
  if (row.errors.length > 0) {
    return row.errors
  }

  return row.warnings
}
</script>

<template>
  <section
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
  >
    <div
      class="border-b border-slate-200 p-4 sm:p-5"
    >
      <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
      >
        <div>
          <h2
            class="font-bold text-slate-900"
          >
            Preview BNBA
          </h2>

          <p
            class="mt-1 text-sm text-slate-500"
          >
            NIK, NKK, dan nomor rekening
            ditampilkan dalam kondisi termasking.
          </p>
        </div>

        <form
          class="flex w-full gap-2 lg:max-w-md"
          @submit.prevent="submitSearch"
        >
          <div
            class="relative min-w-0 flex-1"
          >
            <Search
              :size="18"
              class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
              aria-hidden="true"
            />

            <input
              v-model="keyword"
              type="search"
              placeholder="Cari nama, wilayah, E-Warung, atau NIK..."
              class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10"
            >
          </div>

          <button
            type="submit"
            class="min-h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            Cari
          </button>
        </form>
      </div>
    </div>

    <div
      v-if="loading"
      class="flex min-h-72 items-center justify-center"
    >
      <div
        class="flex flex-col items-center gap-3 text-slate-500"
      >
        <LoaderCircle
          :size="28"
          class="animate-spin text-[#006855]"
          aria-hidden="true"
        />

        <span class="text-sm font-medium">
          Memuat preview BNBA...
        </span>
      </div>
    </div>

    <div
      v-else-if="rows.length === 0"
      class="flex min-h-72 flex-col items-center justify-center px-6 text-center"
    >
      <div
        class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
      >
        <Inbox
          :size="28"
          aria-hidden="true"
        />
      </div>

      <p
        class="font-bold text-slate-800"
      >
        Data tidak ditemukan
      </p>

      <p
        class="mt-1 text-sm text-slate-500"
      >
        Ubah filter atau kata pencarian.
      </p>
    </div>

    <template v-else>
      <!-- Desktop/tablet -->
      <div
        class="hidden overflow-x-auto md:block"
      >
        <table
          class="min-w-[1180px] w-full text-left"
        >
          <thead
            class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"
          >
            <tr>
              <th class="px-4 py-3">
                Baris
              </th>

              <th class="px-4 py-3">
                Status
              </th>

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
                Nominal
              </th>

              <th class="px-4 py-3">
                Catatan
              </th>
            </tr>
          </thead>

          <tbody
            class="divide-y divide-slate-100"
          >
            <tr
              v-for="row in rows"
              :key="row.id"
              class="align-top transition hover:bg-slate-50/80"
            >
              <td
                class="px-4 py-4 text-sm font-semibold text-slate-500"
              >
                {{ row.row_number }}
              </td>

              <td class="px-4 py-4">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold"
                  :class="
                    statusClass(
                      row.status,
                    )
                  "
                >
                  {{
                    statusLabel(
                      row.status,
                    )
                  }}
                </span>
              </td>

              <td class="px-4 py-4">
                <p
                  class="max-w-52 font-bold text-slate-900"
                >
                  {{ row.full_name || '-' }}
                </p>

                <p
                  class="mt-1 text-xs text-slate-500"
                >
                  Tahun Kepesertaan:
                  {{ row.membership_year || '-' }}
                </p>
              </td>

              <td
                class="px-4 py-4 text-sm"
              >
                <p
                  class="font-mono text-slate-700"
                >
                  {{ row.nik || '-' }}
                </p>

                <p
                  class="mt-1 font-mono text-xs text-slate-500"
                >
                  KK: {{ row.nkk || '-' }}
                </p>

                <p
                  class="mt-1 font-mono text-xs text-slate-500"
                >
                  Rek:
                  {{ row.account_number || '-' }}
                </p>
              </td>

              <td class="px-4 py-4">
                <p
                  class="text-sm font-semibold text-slate-800"
                >
                  {{ row.kelurahan || '-' }}
                </p>

                <p
                  class="mt-1 text-xs text-slate-500"
                >
                  {{ row.kecamatan || '-' }}
                </p>

                <p
                  class="mt-2 max-w-56 text-xs leading-5 text-slate-500"
                >
                  {{ row.address || '-' }}
                </p>
              </td>

              <td
                class="px-4 py-4 text-sm text-slate-700"
              >
                <span
                  class="block max-w-56"
                >
                  {{ row.e_warung_name || '-' }}
                </span>
              </td>

              <td
                class="whitespace-nowrap px-4 py-4 text-sm font-bold text-slate-900"
              >
                {{
                  formatCurrency(
                    row.nominal,
                  )
                }}
              </td>

              <td class="px-4 py-4">
                <div
                  v-if="
                    messages(row).length > 0
                  "
                  class="max-w-64 space-y-1.5"
                >
                  <p
                    v-for="message in messages(row)"
                    :key="message"
                    class="text-xs leading-5"
                    :class="
                      row.errors.length > 0
                        ? 'text-[#E8312D]'
                        : 'text-amber-700'
                    "
                  >
                    • {{ message }}
                  </p>
                </div>

                <span
                  v-else
                  class="text-xs text-slate-400"
                >
                  Tidak ada catatan
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile -->
      <div
        class="divide-y divide-slate-100 md:hidden"
      >
        <article
          v-for="row in rows"
          :key="row.id"
          class="p-4"
        >
          <div
            class="mb-3 flex items-start justify-between gap-3"
          >
            <div class="min-w-0">
              <p
                class="truncate font-bold text-slate-900"
              >
                {{ row.full_name || '-' }}
              </p>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                Baris Excel #{{ row.row_number }}
              </p>
            </div>

            <span
              class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold"
              :class="
                statusClass(
                  row.status,
                )
              "
            >
              {{
                statusLabel(
                  row.status,
                )
              }}
            </span>
          </div>

          <dl
            class="grid grid-cols-1 gap-3 text-sm"
          >
            <div>
              <dt
                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
              >
                NIK
              </dt>

              <dd
                class="mt-1 font-mono font-medium text-slate-700"
              >
                {{ row.nik || '-' }}
              </dd>
            </div>

            <div>
              <dt
                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
              >
                Wilayah
              </dt>

              <dd
                class="mt-1 font-medium text-slate-700"
              >
                {{ row.kelurahan || '-' }},
                {{ row.kecamatan || '-' }}
              </dd>
            </div>

            <div>
              <dt
                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
              >
                E-Warung
              </dt>

              <dd
                class="mt-1 text-slate-700"
              >
                {{ row.e_warung_name || '-' }}
              </dd>
            </div>

            <div>
              <dt
                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
              >
                Nominal
              </dt>

              <dd
                class="mt-1 font-bold text-slate-900"
              >
                {{
                  formatCurrency(
                    row.nominal,
                  )
                }}
              </dd>
            </div>
          </dl>

          <div
            v-if="messages(row).length > 0"
            class="mt-4 rounded-xl bg-slate-50 p-3"
          >
            <p
              v-for="message in messages(row)"
              :key="message"
              class="text-xs leading-5"
              :class="
                row.errors.length > 0
                  ? 'text-[#E8312D]'
                  : 'text-amber-700'
              "
            >
              • {{ message }}
            </p>
          </div>
        </article>
      </div>
    </template>

    <div
      v-if="meta.total > 0"
      class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <p
        class="text-sm text-slate-500"
      >
        Menampilkan halaman
        <strong class="text-slate-700">
          {{ meta.current_page }}
        </strong>
        dari
        <strong class="text-slate-700">
          {{ meta.last_page }}
        </strong>
        —
        {{ meta.total }} data
      </p>

      <div class="flex gap-2">
        <button
          type="button"
          :disabled="
            loading
            || meta.current_page <= 1
          "
          class="inline-flex min-h-10 items-center gap-1 rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
          @click="
            emit(
              'page',
              meta.current_page - 1,
            )
          "
        >
          <ChevronLeft
            :size="17"
            aria-hidden="true"
          />

          Sebelumnya
        </button>

        <button
          type="button"
          :disabled="
            loading
            || meta.current_page
              >= meta.last_page
          "
          class="inline-flex min-h-10 items-center gap-1 rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
          @click="
            emit(
              'page',
              meta.current_page + 1,
            )
          "
        >
          Berikutnya

          <ChevronRight
            :size="17"
            aria-hidden="true"
          />
        </button>
      </div>
    </div>
  </section>
</template>