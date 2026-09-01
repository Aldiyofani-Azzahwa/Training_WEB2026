<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
} from 'vue'

import axios from 'axios'

import {
  Activity,
  AlertCircle,
  CheckCircle2,
  Clock3,
  RefreshCw,
  Users,
  WalletCards,
} from '@lucide/vue'

import HeadOfficeMap
  from '@/components/head-office/HeadOfficeMap.vue'

import {
  headOfficeDashboardService,
} from '@/services/headOfficeDashboardService'

import type {
  HeadOfficeDashboard,
  HeadOfficeKecamatanMetric,
  HeadOfficeKelurahanMetric,
  HeadOfficeSummary,
  HeadOfficeTrendItem,
} from '@/types/headOfficeDashboard'

type ChartMode =
  | 'transaksi'
  | 'persentase'

interface TableRow {
  key: string
  type:
    | 'kecamatan'
    | 'kelurahan'
  id: number
  kecamatanId: number
  name: string
  totalKpm: number
  transacted: number
  notTransacted: number
  completionPercentage: number
  amountDisbursed: number
}

const EMPTY_SUMMARY:
  HeadOfficeSummary = {
    total_kpm:
      0,

    transacted:
      0,

    not_transacted:
      0,

    amount_disbursed:
      0,

    completion_percentage:
      0,
  }

const dashboard =
  ref<HeadOfficeDashboard | null>(
    null,
  )

const loading =
  ref(false)

const errorMessage =
  ref<string | null>(
    null,
  )

const selectedKecamatanId =
  ref<number | null>(
    null,
  )

const selectedKelurahanId =
  ref<number | null>(
    null,
  )

const chartMode =
  ref<ChartMode>(
    'transaksi',
  )

let requestVersion =
  0

const summary =
  computed(
    () =>
      dashboard.value
        ?.summary
      ??
      EMPTY_SUMMARY,
  )

const selectedScopeLabel =
  computed(() => {
    const data =
      dashboard.value

    if (!data) {
      return 'Kota Mojokerto'
    }

    if (
      data.scope
        .kelurahan
    ) {
      return (
        data.scope
          .kelurahan
          .name
      )
    }

    if (
      data.scope
        .kecamatan
    ) {
      return (
        data.scope
          .kecamatan
          .name
      )
    }

    return 'Kota Mojokerto'
  })

const tableRows =
  computed<TableRow[]>(() => {
    const data =
      dashboard.value

    if (!data) {
      return []
    }

    if (
      selectedKecamatanId
        .value
      !== null
    ) {
      return data
        .regions
        .kelurahans
        .filter(
          (
            metric:
              HeadOfficeKelurahanMetric,
          ) =>
            metric
              .kecamatan
              .id
            === selectedKecamatanId
              .value,
        )
        .map(
          (
            metric:
              HeadOfficeKelurahanMetric,
          ): TableRow => ({
            key:
              `kelurahan-${metric.kelurahan.id}`,

            type:
              'kelurahan',

            id:
              metric
                .kelurahan
                .id,

            kecamatanId:
              metric
                .kecamatan
                .id,

            name:
              metric
                .kelurahan
                .name,

            totalKpm:
              metric
                .total_kpm,

            transacted:
              metric
                .transacted,

            notTransacted:
              metric
                .not_transacted,

            completionPercentage:
              metric
                .completion_percentage,

            amountDisbursed:
              metric
                .amount_disbursed,
          }),
        )
    }

    return data
      .regions
      .kecamatans
      .map(
        (
          metric:
            HeadOfficeKecamatanMetric,
        ): TableRow => ({
          key:
            `kecamatan-${metric.kecamatan.id}`,

          type:
            'kecamatan',

          id:
            metric
              .kecamatan
              .id,

          kecamatanId:
            metric
              .kecamatan
              .id,

          name:
            metric
              .kecamatan
              .name,

          totalKpm:
            metric
              .total_kpm,

          transacted:
            metric
              .transacted,

          notTransacted:
            metric
              .not_transacted,

          completionPercentage:
            metric
              .completion_percentage,

          amountDisbursed:
            metric
              .amount_disbursed,
        }),
      )
  })

const highestCompletion =
  computed(
    () =>
      [...tableRows.value]
        .sort(
          (
            left,
            right,
          ) =>
            right
              .completionPercentage
            -
            left
              .completionPercentage,
        )
        .at(0)
      ??
      null,
  )

const conclusionStatus =
  computed(() => {
    const percentage =
      summary.value
        .completion_percentage

    if (
      percentage
      >= 90
    ) {
      return {
        label:
          'Sangat Baik',

        className:
          'bg-emerald-600 text-white',
      }
    }

    if (
      percentage
      >= 75
    ) {
      return {
        label:
          'Berjalan Baik',

        className:
          'bg-government-green-600 text-white',
      }
    }

    return {
      label:
        'Perlu Perhatian',

      className:
        'bg-orange-500 text-white',
    }
  })

const maxChartValue =
  computed(() => {
    if (
      chartMode.value
      === 'persentase'
    ) {
      return 100
    }

    const maximum =
      Math.max(
        ...tableRows.value.map(
          (item) =>
            item.transacted,
        ),
        0,
      )

    return maximum > 0
      ? maximum
      : 1
  })

const chartBars =
  computed(() => {
    const items =
      tableRows.value

    if (
      items.length
      === 0
    ) {
      return []
    }

    const max =
      maxChartValue.value

    const cellWidth =
      720
      /
      items.length

    const barWidth =
      Math.min(
        48,
        cellWidth * 0.5,
      )

    return items.map(
      (item, index) => {
        const value =
          chartMode.value
          === 'persentase'
            ? item.completionPercentage
            : item.transacted

        const x =
          (index * cellWidth)
          +
          (cellWidth / 2)

        const height =
          (value / max)
          * 190

        const y =
          220 - height

        return {
          key: item.key,
          name: item.name,
          value,
          x: x - barWidth / 2,
          y,
          width: barWidth,
          height: Math.max(height, 4),
          labelX: x,
          labelValue:
            chartMode.value
            === 'persentase'
              ? formatPercentage(value)
              : formatNumber(value),
        }
      }
    )
  })

async function loadDashboard():
  Promise<void> {
  const version =
    ++requestVersion

  loading.value =
    true

  errorMessage.value =
    null

  try {
    const result =
      await headOfficeDashboardService
        .getDashboard({
          ...(selectedKecamatanId
            .value !== null
            ? {
                kecamatan_id:
                  selectedKecamatanId
                    .value,
              }
            : {}),

          ...(selectedKelurahanId
            .value !== null
            ? {
                kelurahan_id:
                  selectedKelurahanId
                    .value,
              }
            : {}),
        })

    if (
      version
      !== requestVersion
    ) {
      return
    }

    dashboard.value =
      result
  } catch (
    error: unknown
  ) {
    if (
      version
      !== requestVersion
    ) {
      return
    }

    if (
      axios.isAxiosError(
        error,
      )
    ) {
      errorMessage.value =
        error.response
          ?.data
          ?.message
        ??
        'Dashboard gagal dimuat.'
    } else {
      errorMessage.value =
        'Dashboard gagal dimuat.'
    }
  } finally {
    if (
      version
      === requestVersion
    ) {
      loading.value =
        false
    }
  }
}

async function clearSelection():
  Promise<void> {
  selectedKecamatanId
    .value =
      null

  selectedKelurahanId
    .value =
      null

  await loadDashboard()
}

async function selectKecamatan(
  kecamatanId: number,
): Promise<void> {
  selectedKecamatanId
    .value =
      kecamatanId

  selectedKelurahanId
    .value =
      null

  await loadDashboard()
}

async function selectKelurahan(
  payload: {
    kecamatanId: number
    kelurahanId: number
  },
): Promise<void> {
  selectedKecamatanId
    .value =
      payload
        .kecamatanId

  selectedKelurahanId
    .value =
      payload
        .kelurahanId

  await loadDashboard()
}

async function selectTableRow(
  row: TableRow,
): Promise<void> {
  if (
    row.type
    === 'kecamatan'
  ) {
    await selectKecamatan(
      row.id,
    )

    return
  }

  await selectKelurahan({
    kecamatanId:
      row.kecamatanId,

    kelurahanId:
      row.id,
  })
}

function formatNumber(
  value: number,
): string {
  return value
    .toLocaleString(
      'id-ID',
    )
}

function formatCurrency(
  value: number,
): string {
  return new Intl
    .NumberFormat(
      'id-ID',
      {
        style:
          'currency',

        currency:
          'IDR',

        maximumFractionDigits:
          0,
      },
    )
    .format(value)
}

function formatPercentage(
  value: number,
): string {
  return value
    .toLocaleString(
      'id-ID',
      {
        minimumFractionDigits:
          1,

        maximumFractionDigits:
          1,
      },
    )
    + '%'
}

function formatDateTime(
  value:
    string | null,
): string {
  if (!value) {
    return 'Belum tersedia'
  }

  const date =
    new Date(value)

  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return 'Belum tersedia'
  }

  return new Intl
    .DateTimeFormat(
      'id-ID',
      {
        dateStyle:
          'long',

        timeStyle:
          'short',

        timeZone:
          'Asia/Jakarta',
      },
    )
    .format(date)
}

onMounted(() => {
  void loadDashboard()
})
</script>

<template>
  <div
       class="p-4 sm:p-6 lg:p-8"
  >
    <header
      class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start"
    >
      <div>
        <h1
          class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl"
        >
          Dashboard Kepala Dinas
        </h1>

        <p
          class="mt-1 text-sm text-slate-600 sm:text-base"
        >
          Ringkasan penyaluran BPNT Kota Mojokerto
        </p>
      </div>

      <div
        class="flex flex-wrap items-center gap-2"
      >
        <span
          v-if="
            dashboard?.period
          "
          class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm"
        >
          Periode:
          {{
            dashboard
              .period
              .name
          }}
        </span>

        <button
          type="button"
          :disabled="
            loading
          "
          class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-extrabold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          @click="
            loadDashboard
          "
        >
          <RefreshCw
            :size="17"
            :class="{
              'animate-spin':
                loading,
            }"
            aria-hidden="true"
          />

          Perbarui
        </button>
      </div>
    </header>

    <div
      v-if="
        dashboard
          ?.updated_at
      "
      class="mt-3 inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800"
    >
      <Clock3
        :size="15"
        aria-hidden="true"
      />

      Data diperbarui
      {{
        formatDateTime(
          dashboard
            .updated_at,
        )
      }}
    </div>

    <div
      v-if="
        errorMessage
      "
      class="mt-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800"
    >
      <AlertCircle
        :size="22"
        class="mt-0.5 shrink-0"
        aria-hidden="true"
      />

      <div>
        <strong
          class="block"
        >
          Data tidak dapat dimuat
        </strong>

        <p
          class="mt-1 text-sm"
        >
          {{ errorMessage }}
        </p>
      </div>
    </div>

    <section
      v-if="
        !loading
        &&
        dashboard
        &&
        !dashboard.period
      "
      class="mt-6 rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm"
    >
      <Clock3
        :size="42"
        class="mx-auto text-slate-400"
        aria-hidden="true"
      />

      <h2
        class="mt-4 text-xl font-black text-slate-900"
      >
        Belum ada periode aktif
      </h2>

      <p
        class="mt-2 text-sm text-slate-600"
      >
        Dashboard akan tersedia setelah Admin mengaktifkan periode BNBA.
      </p>
    </section>

    <template
      v-if="
        dashboard
        &&
        dashboard.period
      "
    >
      <section
        class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
      >
        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <div
            class="flex items-center gap-4"
          >
            <span
              class="grid size-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"
            >
              <Users
                :size="24"
                aria-hidden="true"
              />
            </span>

            <div>
              <span
                class="text-sm font-bold text-slate-600"
              >
                Total KPM
              </span>

              <strong
                class="mt-1 block text-3xl font-black text-slate-950"
              >
                {{
                  formatNumber(
                    summary
                      .total_kpm,
                  )
                }}
              </strong>
            </div>
          </div>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <div
            class="flex items-center gap-4"
          >
            <span
              class="grid size-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"
            >
              <CheckCircle2
                :size="24"
                aria-hidden="true"
              />
            </span>

            <div>
              <span
                class="text-sm font-bold text-slate-600"
              >
                Sudah Transaksi
              </span>

              <div
                class="mt-1 flex items-end gap-2"
              >
                <strong
                  class="text-3xl font-black text-slate-950"
                >
                  {{
                    formatNumber(
                      summary
                        .transacted,
                    )
                  }}
                </strong>

                <span
                  class="mb-1 text-sm font-black text-emerald-700"
                >
                  {{
                    formatPercentage(
                      summary
                        .completion_percentage,
                    )
                  }}
                </span>
              </div>
            </div>
          </div>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <div
            class="flex items-center gap-4"
          >
            <span
              class="grid size-12 shrink-0 place-items-center rounded-2xl bg-orange-50 text-orange-600"
            >
              <Activity
                :size="24"
                aria-hidden="true"
              />
            </span>

            <div>
              <span
                class="text-sm font-bold text-slate-600"
              >
                Belum Transaksi
              </span>

              <strong
                class="mt-1 block text-3xl font-black text-slate-950"
              >
                {{
                  formatNumber(
                    summary
                      .not_transacted,
                  )
                }}
              </strong>
            </div>
          </div>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <div
            class="flex items-center gap-4"
          >
            <span
              class="grid size-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"
            >
              <WalletCards
                :size="24"
                aria-hidden="true"
              />
            </span>

            <div
              class="min-w-0"
            >
              <span
                class="text-sm font-bold text-slate-600"
              >
                Total Tersalurkan
              </span>

              <strong
                class="mt-1 block truncate text-2xl font-black text-slate-950"
              >
                {{
                  formatCurrency(
                    summary
                      .amount_disbursed,
                  )
                }}
              </strong>
            </div>
          </div>
        </article>
      </section>

      <section
        class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(360px,1fr)]"
      >
        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
          <div
            class="border-b border-slate-200 px-5 py-4"
          >
            <h2
              class="font-black text-slate-950"
            >
              Peta Penyaluran Kota Mojokerto
            </h2>

            <p
              class="mt-1 text-xs text-slate-500"
            >
              Klik kecamatan untuk menampilkan kelurahan dan ringkasannya.
            </p>
          </div>

          <HeadOfficeMap
            :kecamatans="
              dashboard
                .regions
                .kecamatans
            "
            :kelurahans="
              dashboard
                .regions
                .kelurahans
            "
            :selected-kecamatan-id="
              selectedKecamatanId
            "
            :selected-kelurahan-id="
              selectedKelurahanId
            "
            @clear="
              clearSelection
            "
            @select-kecamatan="
              selectKecamatan
            "
            @select-kelurahan="
              selectKelurahan
            "
          />
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <div
            class="flex flex-wrap items-start justify-between gap-3"
          >
            <div>
              <h2
                class="font-black text-slate-950"
              >
                Perkembangan Transaksi
              </h2>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                Wilayah:
                {{ selectedScopeLabel }}
              </p>
            </div>

            <div
              class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1"
            >
              <button
                type="button"
                :class="[
                  'rounded-lg px-3 py-1.5 text-xs font-extrabold transition',
                  chartMode
                    === 'transaksi'
                    ? 'bg-government-green-600 text-white shadow-sm'
                    : 'text-slate-600',
                ]"
                @click="
                  chartMode =
                    'transaksi'
                "
              >
                Transaksi
              </button>

              <button
                type="button"
                :class="[
                  'rounded-lg px-3 py-1.5 text-xs font-extrabold transition',
                  chartMode
                    === 'persentase'
                    ? 'bg-government-green-600 text-white shadow-sm'
                    : 'text-slate-600',
                ]"
                @click="
                  chartMode =
                    'persentase'
                "
              >
                Persentase
              </button>
            </div>
          </div>

          <strong
            class="mt-5 block text-3xl font-black text-government-green-700"
          >
            {{
              formatNumber(
                summary
                  .transacted,
              )
            }}

            <small
              class="text-sm font-bold"
            >
              transaksi
            </small>
          </strong>

          <div
            class="mt-6"
          >
            <svg
              viewBox="0 0 720 280"
              role="img"
              aria-label="Grafik pencapaian wilayah"
              class="h-auto w-full overflow-visible"
            >
              <line
                v-for="
                  ratio in [
                    0,
                    0.25,
                    0.5,
                    0.75,
                    1,
                  ]
                "
                :key="
                  ratio
                "
                x1="0"
                x2="720"
                :y1="
                  220
                  -
                  ratio
                  * 190
                "
                :y2="
                  220
                  -
                  ratio
                  * 190
                "
                stroke="#e2e8f0"
                stroke-width="1"
                stroke-dasharray="4"
              />

              <rect
                v-for="
                  bar in chartBars
                "
                :key="
                  bar.key
                "
                :x="
                  bar.x
                "
                :y="
                  bar.y
                "
                :width="
                  bar.width
                "
                :height="
                  bar.height
                "
                rx="6"
                fill="#059669"
              />

              <text
                v-for="
                  bar in chartBars
                "
                :key="
                  bar.key + '-label'
                "
                :x="
                  bar.labelX
                "
                :y="
                  bar.y - 10
                "
                text-anchor="middle"
                fill="#0f172a"
                font-size="13"
                font-weight="bold"
              >
                {{ bar.labelValue }}
              </text>

              <text
                v-for="
                  bar in chartBars
                "
                :key="
                  bar.key + '-name'
                "
                :x="
                  bar.labelX
                "
                y="245"
                text-anchor="middle"
                fill="#64748b"
                font-size="13"
                font-weight="600"
              >
                {{ bar.name }}
              </text>
            </svg>

            <p
              v-if="
                chartBars.length
                === 0
              "
              class="py-16 text-center text-sm text-slate-500"
            >
              Belum ada data wilayah.
            </p>
          </div>
        </article>
      </section>

      <section
        class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(360px,1fr)]"
      >
        <article
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
          <div
            class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4"
          >
            <div>
              <h2
                class="font-black text-slate-950"
              >
                Ringkasan Wilayah
              </h2>

              <p
                class="mt-1 text-xs text-slate-500"
              >
                {{
                  selectedKecamatanId
                    === null
                    ? 'Data per kecamatan'
                    : 'Data kelurahan pada kecamatan terpilih'
                }}
              </p>
            </div>

            <button
              v-if="
                selectedKecamatanId
                !== null
              "
              type="button"
              class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-extrabold text-slate-700 hover:bg-slate-200"
              @click="
                clearSelection
              "
            >
              Kembali ke kecamatan
            </button>
          </div>

          <div
            class="overflow-x-auto"
          >
            <table
              class="w-full min-w-[760px] border-collapse text-sm"
            >
              <thead>
                <tr
                  class="border-b border-slate-200 bg-slate-50 text-left text-xs text-slate-500"
                >
                  <th
                    class="px-5 py-3 font-extrabold"
                  >
                    Wilayah
                  </th>

                  <th
                    class="px-4 py-3 text-right font-extrabold"
                  >
                    Total KPM
                  </th>

                  <th
                    class="px-4 py-3 text-right font-extrabold"
                  >
                    Sudah
                  </th>

                  <th
                    class="px-4 py-3 text-right font-extrabold"
                  >
                    Belum
                  </th>

                  <th
                    class="px-4 py-3 text-right font-extrabold"
                  >
                    Penyelesaian
                  </th>

                  <th
                    class="px-5 py-3 text-right font-extrabold"
                  >
                    Tersalurkan
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="
                    row in tableRows
                  "
                  :key="
                    row.key
                  "
                  :class="[
                    'border-b border-slate-100 last:border-0',
                    (
                      row.type
                        === 'kecamatan'
                      &&
                      row.id
                        === selectedKecamatanId
                    )
                    ||
                    (
                      row.type
                        === 'kelurahan'
                      &&
                      row.id
                        === selectedKelurahanId
                    )
                      ? 'bg-emerald-50'
                      : 'hover:bg-slate-50',
                  ]"
                >
                  <td
                    class="px-5 py-4"
                  >
                    <button
                      type="button"
                      class="font-extrabold text-slate-800 hover:text-government-green-700"
                      @click="
                        selectTableRow(
                          row,
                        )
                      "
                    >
                      {{ row.name }}
                    </button>
                  </td>

                  <td
                    class="px-4 py-4 text-right font-bold text-slate-700"
                  >
                    {{
                      formatNumber(
                        row
                          .totalKpm,
                      )
                    }}
                  </td>

                  <td
                    class="px-4 py-4 text-right font-bold text-emerald-700"
                  >
                    {{
                      formatNumber(
                        row
                          .transacted,
                      )
                    }}
                  </td>

                  <td
                    class="px-4 py-4 text-right font-bold text-orange-600"
                  >
                    {{
                      formatNumber(
                        row
                          .notTransacted,
                      )
                    }}
                  </td>

                  <td
                    class="px-4 py-4 text-right font-black text-government-green-700"
                  >
                    {{
                      formatPercentage(
                        row
                          .completionPercentage,
                      )
                    }}
                  </td>

                  <td
                    class="px-5 py-4 text-right font-bold text-slate-700"
                  >
                    {{
                      formatCurrency(
                        row
                          .amountDisbursed,
                      )
                    }}
                  </td>
                </tr>

                <tr
                  v-if="
                    tableRows.length
                    === 0
                  "
                >
                  <td
                    colspan="6"
                    class="px-5 py-10 text-center text-slate-500"
                  >
                    Belum ada data wilayah pada periode ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <h2
            class="font-black text-slate-950"
          >
            Kesimpulan Penyaluran
          </h2>

          <div
            class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5"
          >
            <span
              :class="[
                'inline-flex rounded-lg px-3 py-1.5 text-xs font-black',
                conclusionStatus
                  .className,
              ]"
            >
              {{
                conclusionStatus
                  .label
              }}
            </span>

            <div
              class="mt-5 grid gap-4"
            >
              <p
                class="text-sm text-slate-700"
              >
                <strong
                  class="mr-2 text-2xl font-black text-emerald-700"
                >
                  {{
                    formatPercentage(
                      summary
                        .completion_percentage,
                    )
                  }}
                </strong>

                KPM telah bertransaksi.
              </p>

              <p
                class="text-sm text-slate-700"
              >
                <strong
                  class="mr-2 text-2xl font-black text-orange-600"
                >
                  {{
                    formatNumber(
                      summary
                        .not_transacted,
                    )
                  }}
                </strong>

                KPM masih belum bertransaksi.
              </p>

              <p
                v-if="
                  highestCompletion
                "
                class="border-t border-emerald-100 pt-4 text-sm leading-6 text-slate-700"
              >
                <strong>
                  {{
                    highestCompletion
                      .name
                  }}
                </strong>

                memiliki penyelesaian tertinggi:

                <strong
                  class="text-government-green-700"
                >
                  {{
                    formatPercentage(
                      highestCompletion
                        .completionPercentage,
                    )
                  }}
                </strong>
              </p>
            </div>
          </div>

          <p
            class="mt-4 text-xs leading-5 text-slate-500"
          >
            Data mengikuti periode aktif dan wilayah yang dipilih pada peta.
          </p>
        </article>
      </section>
    </template>
  </div>
</template>