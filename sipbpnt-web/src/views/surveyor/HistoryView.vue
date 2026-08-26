<script setup lang="ts">
import axios from 'axios'

import {
  computed,
  onMounted,
  ref,
} from 'vue'

import {
  CheckCircle2,
  CircleAlert,
  ClipboardCheck,
  History,
  LoaderCircle,
  MapPin,
  ReceiptText,
  RefreshCw,
  Store,
} from '@lucide/vue'

import {
  surveyorWorkspaceService,
} from '@/services/surveyorWorkspaceService'

import type {
  ValidationErrorResponse,
} from '@/types/auth'

import type {
  KpmVerification,
  SurveyorParticipantPagination,
  SurveyorTransaction,
} from '@/types/surveyorWorkspace'

type HistoryTab =
  | 'transactions'
  | 'verifications'

const PER_PAGE =
  10

const activeTab =
  ref<HistoryTab>(
    'transactions',
  )

const transactions =
  ref<SurveyorTransaction[]>([])

const verifications =
  ref<KpmVerification[]>([])

const transactionMeta =
  ref<SurveyorParticipantPagination | null>(
    null,
  )

const verificationMeta =
  ref<SurveyorParticipantPagination | null>(
    null,
  )

const historyLoading =
  ref(true)

const loadingMore =
  ref<HistoryTab | null>(
    null,
  )

const historyError =
  ref('')

const loadMoreError =
  ref('')

const transactionTotal =
  computed(() => {
    return (
      transactionMeta.value
        ?.total
      ??
      transactions.value.length
    )
  })

const verificationTotal =
  computed(() => {
    return (
      verificationMeta.value
        ?.total
      ??
      verifications.value.length
    )
  })

const transactionHasMore =
  computed(() => {
    if (!transactionMeta.value) {
      return false
    }

    return (
      transactionMeta.value.current_page
      <
      transactionMeta.value.last_page
    )
  })

const verificationHasMore =
  computed(() => {
    if (!verificationMeta.value) {
      return false
    }

    return (
      verificationMeta.value.current_page
      <
      verificationMeta.value.last_page
    )
  })

const activeHasMore =
  computed(() => {
    return activeTab.value
      ===
      'transactions'
      ? transactionHasMore.value
      : verificationHasMore.value
  })

const activeLoadingMore =
  computed(() => {
    return (
      loadingMore.value
      ===
      activeTab.value
    )
  })

function setActiveTab(
  tab: HistoryTab,
): void {
  activeTab.value =
    tab

  loadMoreError.value =
    ''
}

function formatDateTime(
  value: string | null,
): string {
  if (!value) {
    return '-'
  }

  const date =
    new Date(
      value,
    )

  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return value
  }

  return new Intl.DateTimeFormat(
    'id-ID',
    {
      day:
        '2-digit',

      month:
        'short',

      year:
        'numeric',

      hour:
        '2-digit',

      minute:
        '2-digit',
    },
  ).format(
    date,
  )
}

function participantAddress(
  transaction:
    | SurveyorTransaction
    | KpmVerification,
): string {
  const participant =
    transaction.participant

  const addressParts = [
    participant.kpm.address,

    participant.kpm.rt
      ? `RT ${participant.kpm.rt}`
      : null,

    participant.kpm.rw
      ? `RW ${participant.kpm.rw}`
      : null,
  ]

  return addressParts
    .filter(
      (
        part,
      ): part is string => {
        return (
          typeof part === 'string'
          &&
          part.trim() !== ''
        )
      },
    )
    .join(', ')
}

function participantInitials(
  fullName: string,
): string {
  return fullName
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map(
      (word) => {
        return word
          .charAt(0)
          .toUpperCase()
      },
    )
    .join('')
}

function verificationBadgeClass(
  verification: KpmVerification,
): string {
  if (
    verification.is_cancelled
  ) {
    return 'border-[#e2e7e5] bg-[#f2f5f4] text-[#66746f]'
  }

  switch (
    verification.status.code
  ) {
    case 'deceased':
      return 'border-[#d9dde5] bg-[#f0f2f6] text-[#536176]'

    case 'moved_domicile':
      return 'border-[#c9ddea] bg-[#eef7fc] text-[#3b6f8b]'

    case 'not_claimed':
      return 'border-[#ecd9b8] bg-[#fff8e9] text-[#9a620d]'

    default:
      return 'border-[#d9e6e1] bg-[#f4f8f6] text-[#566c65]'
  }
}

function resolveErrorMessage(
  error: unknown,
): string {
  if (
    axios.isAxiosError<
      ValidationErrorResponse
    >(error)
  ) {
    if (!error.response) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'
    }

    if (
      error.response.status === 401
      ||
      error.response.status === 419
    ) {
      return 'Sesi Anda sudah berakhir. Silakan masuk kembali.'
    }

    if (
      error.response.status === 403
    ) {
      return 'Akun Anda tidak memiliki akses ke riwayat Surveyor.'
    }

    return (
      error.response
        .data
        .message
      ||
      'Riwayat aktivitas belum dapat dimuat.'
    )
  }

  return 'Riwayat aktivitas belum dapat dimuat.'
}

async function loadHistory():
  Promise<void> {
  historyLoading.value =
    true

  historyError.value =
    ''

  loadMoreError.value =
    ''

  try {
    const response =
      await surveyorWorkspaceService
        .getActivityHistory({
          transaction_page:
            1,

          verification_page:
            1,

          per_page:
            PER_PAGE,
        })

    transactions.value =
      response.data.transactions

    verifications.value =
      response.data.verifications

    transactionMeta.value =
      response.meta.transactions

    verificationMeta.value =
      response.meta.verifications
  } catch (
    error: unknown
  ) {
    transactions.value =
      []

    verifications.value =
      []

    transactionMeta.value =
      null

    verificationMeta.value =
      null

    historyError.value =
      resolveErrorMessage(
        error,
      )
  } finally {
    historyLoading.value =
      false
  }
}

async function loadMoreTransactions():
  Promise<void> {
  if (
    loadingMore.value
    ||
    !transactionMeta.value
    ||
    !transactionHasMore.value
  ) {
    return
  }

  loadingMore.value =
    'transactions'

  loadMoreError.value =
    ''

  const nextPage =
    transactionMeta.value.current_page
    + 1

  try {
    const response =
      await surveyorWorkspaceService
        .getActivityHistory({
          transaction_page:
            nextPage,

          verification_page:
            1,

          per_page:
            PER_PAGE,
        })

    transactions.value = [
      ...transactions.value,
      ...response.data.transactions,
    ]

    transactionMeta.value =
      response.meta.transactions
  } catch (
    error: unknown
  ) {
    loadMoreError.value =
      resolveErrorMessage(
        error,
      )
  } finally {
    loadingMore.value =
      null
  }
}

async function loadMoreVerifications():
  Promise<void> {
  if (
    loadingMore.value
    ||
    !verificationMeta.value
    ||
    !verificationHasMore.value
  ) {
    return
  }

  loadingMore.value =
    'verifications'

  loadMoreError.value =
    ''

  const nextPage =
    verificationMeta.value.current_page
    + 1

  try {
    const response =
      await surveyorWorkspaceService
        .getActivityHistory({
          transaction_page:
            1,

          verification_page:
            nextPage,

          per_page:
            PER_PAGE,
        })

    verifications.value = [
      ...verifications.value,
      ...response.data.verifications,
    ]

    verificationMeta.value =
      response.meta.verifications
  } catch (
    error: unknown
  ) {
    loadMoreError.value =
      resolveErrorMessage(
        error,
      )
  } finally {
    loadingMore.value =
      null
  }
}

function loadMoreHistory():
  void {
  if (
    activeTab.value
    ===
    'transactions'
  ) {
    void loadMoreTransactions()

    return
  }

  void loadMoreVerifications()
}

onMounted(() => {
  void loadHistory()
})
</script>

<template>
  <section
    class="grid w-full min-w-0 gap-5"
  >
    <header
      class="flex items-start justify-between gap-4"
    >
      <div class="min-w-0">
        <span
          class="text-xs font-bold tracking-[0.08em] text-[#c12723] uppercase"
        >
          Aktivitas Surveyor
        </span>

        <h1
          class="my-1 text-[27px] leading-tight font-bold text-[#173f37] lg:text-[34px]"
        >
          Riwayat
        </h1>

        <p
          class="m-0 text-sm leading-[1.6] text-[#72847e]"
        >
          Riwayat transaksi dan verifikasi status KPM.
        </p>
      </div>

      <div
        class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#edf3f7] text-[#527083]"
      >
        <History
          :size="25"
          :stroke-width="1.9"
        />
      </div>
    </header>

    <div
      v-if="historyLoading"
      class="grid gap-3"
      data-testid="history-loading"
      aria-live="polite"
    >
      <div
        class="grid grid-cols-2 gap-3"
      >
        <div
          class="h-[86px] animate-pulse rounded-[18px] bg-[#e7efec]"
        />

        <div
          class="h-[86px] animate-pulse rounded-[18px] bg-[#e7efec]"
        />
      </div>

      <div
        class="h-[52px] animate-pulse rounded-[16px] bg-[#e7efec]"
      />

      <div
        v-for="number in 3"
        :key="number"
        class="h-[190px] animate-pulse rounded-[20px] bg-[#e7efec]"
      />

      <span class="sr-only">
        Memuat riwayat aktivitas
      </span>
    </div>

    <article
      v-else-if="historyError"
      class="grid justify-items-center rounded-[22px] border border-[#efcdca] bg-white px-6 py-9 text-center"
      role="alert"
      data-testid="history-error"
    >
      <div
        class="mb-4 grid size-[60px] place-items-center rounded-[20px] bg-[#fff0ef] text-[#cd2b27]"
      >
        <CircleAlert
          :size="30"
          :stroke-width="1.9"
        />
      </div>

      <strong
        class="text-base font-bold text-[#244b43]"
      >
        Riwayat belum dapat dimuat
      </strong>

      <p
        class="mt-2 max-w-sm text-sm leading-[1.6] text-[#778984]"
      >
        {{ historyError }}
      </p>

      <button
        type="button"
        class="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#006855] px-5 text-sm font-bold text-white transition hover:bg-[#005746]"
        data-testid="history-retry"
        @click="loadHistory"
      >
        <RefreshCw
          :size="18"
          :stroke-width="2"
        />

        Coba Lagi
      </button>
    </article>

    <template v-else>
      <div
        class="grid grid-cols-2 gap-3"
      >
        <article
          class="rounded-[18px] border border-[#cfe4dc] bg-[#f2faf7] p-4"
        >
          <div
            class="mb-3 grid size-10 place-items-center rounded-xl bg-[#006855] text-white"
          >
            <ReceiptText
              :size="20"
              :stroke-width="2"
            />
          </div>

          <span
            class="block text-[11px] font-semibold text-[#71837d]"
          >
            Transaksi
          </span>

          <strong
            class="mt-0.5 block text-2xl font-extrabold text-[#173f37]"
          >
            {{ transactionTotal.toLocaleString('id-ID') }}
          </strong>
        </article>

        <article
          class="rounded-[18px] border border-[#e8d9bc] bg-[#fff9ed] p-4"
        >
          <div
            class="mb-3 grid size-10 place-items-center rounded-xl bg-[#c57a0b] text-white"
          >
            <ClipboardCheck
              :size="20"
              :stroke-width="2"
            />
          </div>

          <span
            class="block text-[11px] font-semibold text-[#807362]"
          >
            Verifikasi KPM
          </span>

          <strong
            class="mt-0.5 block text-2xl font-extrabold text-[#173f37]"
          >
            {{ verificationTotal.toLocaleString('id-ID') }}
          </strong>
        </article>
      </div>

      <div
        class="grid grid-cols-2 gap-1 rounded-[16px] border border-[#dfe9e5] bg-[#edf3f0] p-1"
        role="tablist"
        aria-label="Jenis riwayat"
      >
        <button
          type="button"
          role="tab"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-3 text-xs font-bold transition"
          :class="
            activeTab === 'transactions'
              ? 'bg-white text-[#006855] shadow-sm'
              : 'text-[#748780] hover:text-[#45665d]'
          "
          :aria-selected="
            activeTab
            ===
            'transactions'
          "
          data-testid="transactions-tab"
          @click="
            setActiveTab(
              'transactions',
            )
          "
        >
          <ReceiptText
            :size="17"
            :stroke-width="2"
          />

          Transaksi
        </button>

        <button
          type="button"
          role="tab"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-3 text-xs font-bold transition"
          :class="
            activeTab === 'verifications'
              ? 'bg-white text-[#9a620d] shadow-sm'
              : 'text-[#748780] hover:text-[#45665d]'
          "
          :aria-selected="
            activeTab
            ===
            'verifications'
          "
          data-testid="verifications-tab"
          @click="
            setActiveTab(
              'verifications',
            )
          "
        >
          <ClipboardCheck
            :size="17"
            :stroke-width="2"
          />

          Verifikasi
        </button>
      </div>

      <template
        v-if="
          activeTab
          ===
          'transactions'
        "
      >
        <article
          v-if="
            transactions.length
            ===
            0
          "
          class="grid justify-items-center rounded-[22px] border border-dashed border-[#cdded8] bg-white px-6 py-10 text-center"
          data-testid="transactions-empty"
        >
          <div
            class="mb-4 grid size-[60px] place-items-center rounded-[20px] bg-[#e8f5f0] text-[#006855]"
          >
            <ReceiptText
              :size="30"
              :stroke-width="1.8"
            />
          </div>

          <strong
            class="text-base font-bold text-[#244b43]"
          >
            Belum ada transaksi
          </strong>

          <p
            class="mt-2 max-w-sm text-sm leading-[1.6] text-[#778984]"
          >
            Transaksi yang dicatat melalui KPM atau
            Scan KTP akan tampil di halaman ini.
          </p>
        </article>

        <div
          v-else
          class="grid gap-3 lg:grid-cols-2"
          data-testid="transaction-history-list"
        >
          <article
            v-for="transaction in transactions"
            :key="transaction.id"
            class="min-w-0 overflow-hidden rounded-[20px] border border-[#dfe9e5] bg-white shadow-[0_9px_25px_rgb(30_65_55_/_5%)]"
          >
            <header
              class="flex items-start gap-3 p-4"
            >
              <div
                class="grid size-11 shrink-0 place-items-center rounded-[14px] bg-[#e8f5f0] text-xs font-extrabold text-[#006855]"
              >
                {{
                  participantInitials(
                    transaction
                      .participant
                      .kpm
                      .full_name,
                  )
                }}
              </div>

              <div class="min-w-0 flex-1">
                <strong
                  class="block overflow-hidden text-[15px] font-bold text-ellipsis whitespace-nowrap text-[#244b43]"
                  :title="
                    transaction
                      .participant
                      .kpm
                      .full_name
                  "
                >
                  {{
                    transaction
                      .participant
                      .kpm
                      .full_name
                  }}
                </strong>

                <span
                  class="mt-0.5 block text-[11px] text-[#7b8c86]"
                >
                  NIK
                  {{
                    transaction
                      .participant
                      .kpm
                      .nik
                  }}
                </span>
              </div>

              <span
                class="shrink-0 rounded-full bg-[#e5f5ee] px-2.5 py-1.5 text-[10px] font-bold text-[#08745e]"
              >
                Sudah Bertransaksi
              </span>
            </header>

            <div
              class="mx-4 grid gap-3 rounded-[15px] bg-[#f8faf9] p-3"
            >
              <div
                class="flex items-start gap-2.5"
              >
                <Store
                  :size="18"
                  :stroke-width="1.9"
                  class="mt-0.5 shrink-0 text-[#006855]"
                />

                <div class="min-w-0">
                  <span
                    class="block text-[10px] text-[#7a8c86]"
                  >
                    E-Warung
                  </span>

                  <strong
                    class="block overflow-hidden text-xs font-bold text-ellipsis whitespace-nowrap text-[#35594f]"
                  >
                    {{ transaction.e_warung.name }}
                  </strong>
                </div>
              </div>

              <div
                class="flex items-start gap-2.5"
              >
                <MapPin
                  :size="18"
                  :stroke-width="1.9"
                  class="mt-0.5 shrink-0 text-[#638079]"
                />

                <div class="min-w-0">
                  <span
                    class="block text-[10px] text-[#7a8c86]"
                  >
                    Wilayah KPM
                  </span>

                  <strong
                    class="block text-xs font-bold text-[#45665d]"
                  >
                    {{
                      transaction
                        .participant
                        .wilayah
                        .kelurahan
                        .name
                      ??
                      '-'
                    }}
                  </strong>

                  <p
                    class="mt-0.5 line-clamp-2 text-[11px] leading-[1.5] text-[#7b8d87]"
                  >
                    {{ participantAddress(transaction) }}
                  </p>
                </div>
              </div>
            </div>

            <footer
              class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-[#edf1ef] bg-[#fcfdfc] px-4 py-3"
            >
              <div>
                <span
                  class="block text-[10px] text-[#7b8c86]"
                >
                  {{ transaction.period.name }}
                </span>

                <strong
                  class="text-[11px] font-bold text-[#50665f]"
                >
                  {{ formatDateTime(transaction.transacted_at) }}
                </strong>
              </div>

              <span
                v-if="transaction.outside_assignment"
                class="rounded-full border border-[#ecd9b8] bg-[#fff8e9] px-2.5 py-1 text-[10px] font-bold text-[#9a620d]"
              >
                KPM Luar Wilayah
              </span>
            </footer>
          </article>
        </div>
      </template>

      <template v-else>
        <article
          v-if="
            verifications.length
            ===
            0
          "
          class="grid justify-items-center rounded-[22px] border border-dashed border-[#cdded8] bg-white px-6 py-10 text-center"
          data-testid="verifications-empty"
        >
          <div
            class="mb-4 grid size-[60px] place-items-center rounded-[20px] bg-[#fff3de] text-[#b56a00]"
          >
            <ClipboardCheck
              :size="30"
              :stroke-width="1.8"
            />
          </div>

          <strong
            class="text-base font-bold text-[#244b43]"
          >
            Belum ada verifikasi KPM
          </strong>

          <p
            class="mt-2 max-w-sm text-sm leading-[1.6] text-[#778984]"
          >
            Status Meninggal, Pindah Domisili, atau
            Tidak Mengambil akan tampil di sini.
          </p>
        </article>

        <div
          v-else
          class="grid gap-3 lg:grid-cols-2"
          data-testid="verification-history-list"
        >
          <article
            v-for="verification in verifications"
            :key="verification.id"
            class="min-w-0 overflow-hidden rounded-[20px] border border-[#dfe9e5] bg-white shadow-[0_9px_25px_rgb(30_65_55_/_5%)]"
          >
            <header
              class="flex items-start gap-3 p-4"
            >
              <div
                class="grid size-11 shrink-0 place-items-center rounded-[14px] bg-[#fff3de] text-xs font-extrabold text-[#9a620d]"
              >
                {{
                  participantInitials(
                    verification
                      .participant
                      .kpm
                      .full_name,
                  )
                }}
              </div>

              <div class="min-w-0 flex-1">
                <strong
                  class="block overflow-hidden text-[15px] font-bold text-ellipsis whitespace-nowrap text-[#244b43]"
                  :title="
                    verification
                      .participant
                      .kpm
                      .full_name
                  "
                >
                  {{
                    verification
                      .participant
                      .kpm
                      .full_name
                  }}
                </strong>

                <span
                  class="mt-0.5 block text-[11px] text-[#7b8c86]"
                >
                  NIK
                  {{
                    verification
                      .participant
                      .kpm
                      .nik
                  }}
                </span>
              </div>

              <span
                class="shrink-0 rounded-full border px-2.5 py-1.5 text-[10px] font-bold"
                :class="
                  verificationBadgeClass(
                    verification,
                  )
                "
              >
                {{
                  verification.is_cancelled
                    ? 'Dibatalkan'
                    : verification.status.label
                }}
              </span>
            </header>

            <div
              class="mx-4 grid gap-3 rounded-[15px] bg-[#f8faf9] p-3"
            >
              <div
                class="flex items-start gap-2.5"
              >
                <MapPin
                  :size="18"
                  :stroke-width="1.9"
                  class="mt-0.5 shrink-0 text-[#638079]"
                />

                <div class="min-w-0">
                  <span
                    class="block text-[10px] text-[#7a8c86]"
                  >
                    Wilayah KPM
                  </span>

                  <strong
                    class="block text-xs font-bold text-[#45665d]"
                  >
                    {{
                      verification
                        .participant
                        .wilayah
                        .kelurahan
                        .name
                      ??
                      '-'
                    }}
                  </strong>

                  <p
                    class="mt-0.5 line-clamp-2 text-[11px] leading-[1.5] text-[#7b8d87]"
                  >
                    {{ participantAddress(verification) }}
                  </p>
                </div>
              </div>

              <div
                v-if="verification.reason"
                class="rounded-xl border border-[#ecd9b8] bg-[#fffaf0] p-3"
              >
                <span
                  class="block text-[10px] font-semibold text-[#8d754f]"
                >
                  Alasan tidak mengambil
                </span>

                <p
                  class="mt-1 whitespace-pre-wrap text-xs leading-[1.55] text-[#664d28]"
                >
                  {{ verification.reason }}
                </p>
              </div>

              <div
                v-if="verification.is_cancelled"
                class="rounded-xl border border-[#e0e5e3] bg-white p-3"
              >
                <div
                  class="flex items-start gap-2"
                >
                  <CircleAlert
                    :size="18"
                    :stroke-width="2"
                    class="mt-0.5 shrink-0 text-[#66746f]"
                  />

                  <div>
                    <strong
                      class="block text-xs text-[#4e5e58]"
                    >
                      Dibatalkan Manager
                    </strong>

                    <p
                      class="mt-1 text-[11px] leading-[1.5] text-[#778680]"
                    >
                      {{
                        verification.cancelled_by
                          ?.name
                        ??
                        'Manager BPNT'
                      }}

                      ·

                      {{
                        formatDateTime(
                          verification.cancelled_at,
                        )
                      }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <footer
              class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-[#edf1ef] bg-[#fcfdfc] px-4 py-3"
            >
              <div>
                <span
                  class="block text-[10px] text-[#7b8c86]"
                >
                  {{ verification.period.name }}
                </span>

                <strong
                  class="text-[11px] font-bold text-[#50665f]"
                >
                  {{ formatDateTime(verification.verified_at) }}
                </strong>
              </div>

              <span
                v-if="!verification.is_cancelled"
                class="inline-flex items-center gap-1 rounded-full bg-[#e8f5f0] px-2.5 py-1 text-[10px] font-bold text-[#08745e]"
              >
                <CheckCircle2
                  :size="13"
                  :stroke-width="2"
                />

                Status final
              </span>
            </footer>
          </article>
        </div>
      </template>

      <article
        v-if="loadMoreError"
        class="flex items-start gap-2 rounded-xl border border-[#efcdca] bg-[#fff8f7] p-3 text-xs text-[#a3312d]"
        role="alert"
      >
        <CircleAlert
          :size="18"
          :stroke-width="2"
          class="shrink-0"
        />

        <p>
          {{ loadMoreError }}
        </p>
      </article>

      <button
        v-if="activeHasMore"
        type="button"
        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#006855] px-5 text-sm font-bold text-white transition hover:bg-[#005746] disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="activeLoadingMore"
        data-testid="history-load-more"
        @click="loadMoreHistory"
      >
        <LoaderCircle
          v-if="activeLoadingMore"
          :size="18"
          class="animate-spin"
        />

        <RefreshCw
          v-else
          :size="18"
          :stroke-width="2"
        />

        {{
          activeLoadingMore
            ? 'Memuat...'
            : 'Muat Riwayat Berikutnya'
        }}
      </button>

      <p
        v-if="
          activeTab === 'transactions'
          &&
          transactions.length > 0
        "
        class="text-center text-[11px] text-[#80918b]"
      >
        Menampilkan
        {{ transactions.length.toLocaleString('id-ID') }}
        dari
        {{ transactionTotal.toLocaleString('id-ID') }}
        transaksi
      </p>

      <p
        v-if="
          activeTab === 'verifications'
          &&
          verifications.length > 0
        "
        class="text-center text-[11px] text-[#80918b]"
      >
        Menampilkan
        {{ verifications.length.toLocaleString('id-ID') }}
        dari
        {{ verificationTotal.toLocaleString('id-ID') }}
        verifikasi
      </p>
    </template>
  </section>
</template>