<script setup lang="ts">
import axios from 'axios'

import {
  CalendarCheck2,
  CheckCircle2,
  LoaderCircle,
  RefreshCw,
  ShieldCheck,
  XCircle,
} from '@lucide/vue'

import {
  computed,
  onMounted,
  ref,
} from 'vue'

import {
  bnbaService,
} from '@/services/bnbaService'

import type {
  BpntPeriod,
  LaravelErrorResponse,
} from '@/types/bnba'

const periods =
  ref<BpntPeriod[]>([])

const loading =
  ref(false)

const processingId =
  ref<number | null>(null)

const processingAction =
  ref<
    'activate'
    | 'deactivate'
    | null
  >(null)

const errorMessage =
  ref('')

const successMessage =
  ref('')

const activePeriod =
  computed<
    BpntPeriod | null
  >(() => {
    return (
      periods.value.find(
        (period) =>
          period.is_active,
      )
      ??
      null
    )
  })

function normalizeError(
  error: unknown,
  fallback: string,
): string {
  if (
    !axios.isAxiosError<
      LaravelErrorResponse
    >(error)
  ) {
    return fallback
  }

  const errors =
    error.response
      ?.data
      ?.errors

  if (errors) {
    const firstError =
      Object.values(
        errors,
      )[0]?.[0]

    if (firstError) {
      return firstError
    }
  }

  return (
    error.response
      ?.data
      ?.message
    ??
    fallback
  )
}

async function loadPeriods():
  Promise<void> {
  loading.value =
    true

  errorMessage.value =
    ''

  try {
    periods.value =
      await bnbaService
        .getPeriods()
  } catch (
    error: unknown
  ) {
    errorMessage.value =
      normalizeError(
        error,
        'Data periode gagal dimuat.',
      )
  } finally {
    loading.value =
      false
  }
}

async function activatePeriod(
  period: BpntPeriod,
): Promise<void> {
  if (
    period.is_active
    ||
    !period.can_activate
    ||
    processingId.value
      !== null
  ) {
    return
  }

  const confirmed =
    window.confirm(
      `Aktifkan ${period.name} (${period.year})?\n\nPeriode aktif sebelumnya akan otomatis dinonaktifkan. Manager dan Surveyor akan menggunakan periode ini secara otomatis.`,
    )

  if (!confirmed) {
    return
  }

  processingId.value =
    period.id

  processingAction.value =
    'activate'

  errorMessage.value =
    ''

  successMessage.value =
    ''

  try {
    const activated =
      await bnbaService
        .activatePeriod(
          period.id,
        )

    successMessage.value =
      `${activated.name} berhasil dijadikan periode aktif.`

    await loadPeriods()
  } catch (
    error: unknown
  ) {
    errorMessage.value =
      normalizeError(
        error,
        'Periode gagal diaktifkan.',
      )
  } finally {
    processingId.value =
      null

    processingAction.value =
      null
  }
}

async function deactivatePeriod(
  period: BpntPeriod,
): Promise<void> {
  if (
    !period.is_active
    ||
    processingId.value
      !== null
  ) {
    return
  }

  const confirmed =
    window.confirm(
      `Nonaktifkan ${period.name} (${period.year})?\n\nSetelah dinonaktifkan, Manager dan Surveyor tidak memiliki periode operasional sampai Admin mengaktifkan periode kembali.`,
    )

  if (!confirmed) {
    return
  }

  processingId.value =
    period.id

  processingAction.value =
    'deactivate'

  errorMessage.value =
    ''

  successMessage.value =
    ''

  try {
    const deactivated =
      await bnbaService
        .deactivatePeriod(
          period.id,
        )

    successMessage.value =
      `${deactivated.name} berhasil dinonaktifkan.`

    await loadPeriods()
  } catch (
    error: unknown
  ) {
    errorMessage.value =
      normalizeError(
        error,
        'Periode gagal dinonaktifkan.',
      )
  } finally {
    processingId.value =
      null

    processingAction.value =
      null
  }
}

function activationHint(
  period: BpntPeriod,
): string {
  if (
    period.is_active
  ) {
    return 'Periode ini sedang digunakan oleh sistem.'
  }

  if (
    period.bnba?.status
    !== 'confirmed'
  ) {
    return 'BNBA harus dikonfirmasi terlebih dahulu.'
  }

  if (
    period.participants_count
    <= 0
  ) {
    return 'BNBA belum menghasilkan data KPM.'
  }

  return 'Siap diaktifkan.'
}

onMounted(
  async () => {
    await loadPeriods()
  },
)
</script>

<template>
  <main
    class="mx-auto max-w-[1300px] px-4 py-7 sm:px-6 lg:px-8"
  >
    <header
      class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
    >
      <div>
        <div
          class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-[#006855]/10 text-[#006855]"
        >
          <CalendarCheck2
            :size="24"
            aria-hidden="true"
          />
        </div>

        <h1
          class="text-2xl font-black text-slate-950 sm:text-3xl"
        >
          Periode Aktif
        </h1>

        <p
          class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 sm:text-base"
        >
          Hanya satu periode yang dapat aktif.
          Periode hanya dapat diaktifkan setelah
          BNBA terkonfirmasi dan data KPM tersedia.
        </p>
      </div>

      <button
        type="button"
        :disabled="
          loading
          ||
          processingId !== null
        "
        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
        @click="loadPeriods"
      >
        <RefreshCw
          :size="17"
          :class="{
            'animate-spin':
              loading,
          }"
        />

        Muat Ulang
      </button>
    </header>

    <div
      v-if="successMessage"
      class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700"
    >
      <CheckCircle2
        :size="20"
        class="mt-0.5 shrink-0"
      />

      <span>
        {{ successMessage }}
      </span>
    </div>

    <div
      v-if="errorMessage"
      class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700"
    >
      <XCircle
        :size="20"
        class="mt-0.5 shrink-0"
      />

      <span>
        {{ errorMessage }}
      </span>
    </div>

    <section
      class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
    >
      <div
        class="flex items-start gap-3"
      >
        <div
          class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"
        >
          <ShieldCheck
            :size="20"
          />
        </div>

        <div>
          <span
            class="text-xs font-black tracking-wider text-slate-500 uppercase"
          >
            Periode yang sedang digunakan
          </span>

          <strong
            v-if="activePeriod"
            class="mt-1 block text-lg font-black text-slate-950"
          >
            {{ activePeriod.name }}
            ·
            {{ activePeriod.year }}
          </strong>

          <strong
            v-else
            class="mt-1 block text-lg font-black text-amber-700"
          >
            Belum ada periode aktif
          </strong>

          <p
            class="mt-1 text-sm leading-6 text-slate-500"
          >
            Manager dan Surveyor tidak memilih
            periode secara bebas. Sistem akan
            menggunakan periode aktif ini.
          </p>
        </div>
      </div>
    </section>

    <section
      v-if="loading"
      class="grid min-h-52 place-items-center rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
      <LoaderCircle
        :size="30"
        class="animate-spin text-[#006855]"
      />
    </section>

    <section
      v-else-if="
        periods.length === 0
      "
      class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm"
    >
      <strong
        class="text-lg font-black text-slate-900"
      >
        Belum ada periode BPNT
      </strong>

      <p
        class="mt-2 text-sm text-slate-500"
      >
        Buat periode dan import BNBA terlebih
        dahulu melalui menu Import BNBA.
      </p>
    </section>

    <section
      v-else
      class="grid gap-4 lg:grid-cols-2"
    >
      <article
        v-for="
          period in periods
        "
        :key="period.id"
        class="rounded-2xl border bg-white p-5 shadow-sm"
        :class="
          period.is_active
            ? 'border-emerald-300 ring-2 ring-emerald-100'
            : 'border-slate-200'
        "
      >
        <div
          class="flex items-start justify-between gap-4"
        >
          <div>
            <div
              class="flex flex-wrap items-center gap-2"
            >
              <h2
                class="text-lg font-black text-slate-950"
              >
                {{ period.name }}
              </h2>

              <span
                v-if="
                  period.is_active
                "
                class="rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-black text-white"
              >
                AKTIF
              </span>
            </div>

            <p
              class="mt-1 text-sm font-semibold text-slate-500"
            >
              Tahun {{ period.year }}
            </p>
          </div>

          <CalendarCheck2
            :size="22"
            :class="
              period.is_active
                ? 'text-emerald-600'
                : 'text-slate-300'
            "
          />
        </div>

        <div
          class="mt-5 grid grid-cols-2 gap-3"
        >
          <div
            class="rounded-xl bg-slate-50 p-3"
          >
            <span
              class="block text-xs font-bold text-slate-500"
            >
              Status BNBA
            </span>

            <strong
              class="mt-1 block text-sm font-black text-slate-900"
            >
              {{
                period.bnba?.status
                  === 'confirmed'
                  ? 'Terkonfirmasi'
                  : period.bnba
                    ? 'Belum dikonfirmasi'
                    : 'Belum ada BNBA'
              }}
            </strong>
          </div>

          <div
            class="rounded-xl bg-slate-50 p-3"
          >
            <span
              class="block text-xs font-bold text-slate-500"
            >
              Jumlah KPM
            </span>

            <strong
              class="mt-1 block text-sm font-black text-slate-900"
            >
              {{ period.participants_count }}
              KPM
            </strong>
          </div>
        </div>

        <p
          class="mt-4 text-xs font-semibold leading-5"
          :class="
            period.is_active
            ||
            period.can_activate
              ? 'text-emerald-700'
              : 'text-amber-700'
          "
        >
          {{ activationHint(period) }}
        </p>

        <button
          v-if="
            !period.is_active
          "
          type="button"
          :disabled="
            !period.can_activate
            ||
            processingId !== null
          "
          class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#006855] px-4 text-sm font-black text-white transition hover:bg-[#005646] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
          @click="
            activatePeriod(
              period,
            )
          "
        >
          <LoaderCircle
            v-if="
              processingId
              === period.id
              &&
              processingAction
              === 'activate'
            "
            :size="17"
            class="animate-spin"
          />

          <CalendarCheck2
            v-else
            :size="17"
          />

          {{
            processingId === period.id
            &&
            processingAction === 'activate'
              ? 'Mengaktifkan...'
              : 'Aktifkan Periode'
          }}
        </button>

        <button
          v-else
          type="button"
          :disabled="
            processingId !== null
          "
          class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-black text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
          @click="
            deactivatePeriod(
              period,
            )
          "
        >
          <LoaderCircle
            v-if="
              processingId
              === period.id
              &&
              processingAction
              === 'deactivate'
            "
            :size="17"
            class="animate-spin"
          />

          <XCircle
            v-else
            :size="17"
          />

          {{
            processingId === period.id
            &&
            processingAction === 'deactivate'
              ? 'Menonaktifkan...'
              : 'Nonaktifkan Periode'
          }}
        </button>
      </article>
    </section>
  </main>
</template>