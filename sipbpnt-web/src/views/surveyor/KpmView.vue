<script setup lang="ts">
import axios from 'axios'

import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'

import {
  CheckCircle2,
  CircleAlert,
  ClipboardCheck,
  LoaderCircle,
  MapPin,
  RefreshCw,
  Search,
  Store,
  UserRound,
  UsersRound,
  X,
} from '@lucide/vue'

import {
  useSurveyorEWarungSelection,
} from '@/services/surveyorEWarungSelection'

import {
  surveyorWorkspaceService,
} from '@/services/surveyorWorkspaceService'

import type {
  ValidationErrorResponse,
} from '@/types/auth'

import type {
  SurveyorParticipant,
  SurveyorParticipantActivity,
  SurveyorParticipantPagination,
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

const PER_PAGE =
  15

const SEARCH_DELAY =
  350

const context =
  ref<SurveyorWorkspaceContext | null>(
    null,
  )

const participants =
  ref<SurveyorParticipant[]>([])

const pagination =
  ref<SurveyorParticipantPagination | null>(
    null,
  )

const searchTerm =
  ref('')

const workspaceLoading =
  ref(true)

const participantsLoading =
  ref(false)

const loadingMore =
  ref(false)

const transactionLoadingId =
  ref<number | null>(
    null,
  )

const workspaceError =
  ref('')

const participantsError =
  ref('')

const transactionError =
  ref('')

const transactionSuccess =
  ref('')

const confirmationParticipant =
  ref<SurveyorParticipant | null>(
    null,
  )

const {
  selectedEWarung,
  synchronizeEWarungs,
} = useSurveyorEWarungSelection()

let searchTimer:
  ReturnType<typeof setTimeout>
  | null =
    null

let participantRequestSequence =
  0

const pageTitle =
  computed(() => {
    const kelurahan =
      context.value
        ?.assignment
        ?.kelurahan
        .name

    return kelurahan
      ? `KPM Wilayah ${kelurahan}`
      : 'KPM Wilayah'
  })

const searchPlaceholder =
  computed(() => {
    const kelurahan =
      context.value
        ?.assignment
        ?.kelurahan
        .name

    return kelurahan
      ? `Cari KPM di ${kelurahan}`
      : 'Cari nama atau NIK'
  })

const totalParticipants =
  computed(() => {
    return (
      pagination.value
        ?.total
      ??
      context.value
        ?.kpm_count
      ??
      0
    )
  })

const hasMorePages =
  computed(() => {
    if (!pagination.value) {
      return false
    }

    return (
      pagination.value.current_page
      <
      pagination.value.last_page
    )
  })

const hasSearch =
  computed(() => {
    return (
      searchTerm.value
        .trim()
        .length
      >
      0
    )
  })

const selectedEWarungValue =
  computed(() => {
    return selectedEWarung.value
  })

const fallbackActivity:
  SurveyorParticipantActivity = {
    code:
      'pending',

    label:
      'Belum Transaksi',

    is_final:
      false,

    can_record_transaction:
      true,
  }

function activityOf(
  participant: SurveyorParticipant,
): SurveyorParticipantActivity {
  return (
    participant.activity
    ??
    fallbackActivity
  )
}

function isVerifiedActivity(
  participant: SurveyorParticipant,
): boolean {
  const activity =
    activityOf(
      participant,
    )

  return (
    activity.is_final
    &&
    activity.code !== 'transacted'
  )
}

function formatCurrency(
  amount: number,
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
    amount,
  )
}

function participantInitials(
  participant: SurveyorParticipant,
): string {
  return participant
    .kpm
    .full_name
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

function participantAddress(
  participant: SurveyorParticipant,
): string {
  const parts = [
    participant.kpm.address,

    participant.kpm.rt
      ? `RT ${participant.kpm.rt}`
      : null,

    participant.kpm.rw
      ? `RW ${participant.kpm.rw}`
      : null,
  ]

  return parts
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

function resolveErrorMessage(
  error: unknown,
  fallback =
    'Data KPM belum dapat dimuat.',
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
      return 'Akun Anda tidak memiliki akses ke data KPM Surveyor.'
    }

    return (
      error.response
        .data
        .message
      ||
      fallback
    )
  }

  return fallback
}

async function refreshEWarungs():
  Promise<void> {
  const periodId =
    context.value
      ?.period
      ?.id

  if (
    periodId === undefined
  ) {
    return
  }

  const activeEWarungs =
    await surveyorWorkspaceService
      .getActiveEWarungs()

  synchronizeEWarungs(
    periodId,
    activeEWarungs,
  )
}

function clearSearchTimer():
  void {
  if (
    searchTimer === null
  ) {
    return
  }

  clearTimeout(
    searchTimer,
  )

  searchTimer =
    null
}

async function loadParticipants(
  reset: boolean,
): Promise<void> {
  if (
    !context.value?.period
    ||
    !context.value.assignment
  ) {
    participants.value =
      []

    pagination.value =
      null

    return
  }

  const requestSequence =
    ++participantRequestSequence

  participantsError.value =
    ''

  if (reset) {
    participantsLoading.value =
      true
  } else {
    loadingMore.value =
      true
  }

  const requestedPage =
    reset
      ? 1
      : (
          pagination.value
            ?.current_page
          ??
          0
        ) + 1

  const normalizedSearch =
    searchTerm.value
      .trim()

  try {
    const response =
      await surveyorWorkspaceService
        .getParticipants({
          page:
            requestedPage,

          per_page:
            PER_PAGE,

          ...(normalizedSearch
            ? {
                search:
                  normalizedSearch,
              }
            : {}),
        })

    if (
      requestSequence
      !==
      participantRequestSequence
    ) {
      return
    }

    participants.value =
      reset
        ? response.data
        : [
            ...participants.value,
            ...response.data,
          ]

    pagination.value =
      response.meta
  } catch (
    error: unknown
  ) {
    if (
      requestSequence
      !==
      participantRequestSequence
    ) {
      return
    }

    participantsError.value =
      resolveErrorMessage(
        error,
      )
  } finally {
    if (
      requestSequence
      ===
      participantRequestSequence
    ) {
      participantsLoading.value =
        false

      loadingMore.value =
        false
    }
  }
}

async function loadWorkspace():
  Promise<void> {
  workspaceLoading.value =
    true

  workspaceError.value =
    ''

  participantsError.value =
    ''

  transactionError.value =
    ''

  participants.value =
    []

  pagination.value =
    null

  try {
    context.value =
      await surveyorWorkspaceService
        .getContext()

    if (
      context.value.period
      &&
      context.value.assignment
    ) {
      await loadParticipants(
        true,
      )

      try {
        await refreshEWarungs()
      } catch (
        error: unknown
      ) {
        transactionError.value =
          resolveErrorMessage(
            error,
            'Daftar E-Warung aktif belum dapat diperbarui.',
          )
      }
    }
  } catch (
    error: unknown
  ) {
    context.value =
      null

    workspaceError.value =
      resolveErrorMessage(
        error,
      )
  } finally {
    workspaceLoading.value =
      false
  }
}

function submitSearch():
  void {
  clearSearchTimer()

  participantRequestSequence++

  void loadParticipants(
    true,
  )
}

function clearSearch():
  void {
  if (
    searchTerm.value === ''
  ) {
    return
  }

  clearSearchTimer()

  searchTerm.value =
    ''
}

function loadMore():
  void {
  if (
    loadingMore.value
    ||
    !hasMorePages.value
  ) {
    return
  }

  void loadParticipants(
    false,
  )
}

function openTransactionConfirmation(
  participant: SurveyorParticipant,
): void {
  transactionError.value =
    ''

  transactionSuccess.value =
    ''

  if (
    !activityOf(
      participant,
    ).can_record_transaction
  ) {
    return
  }

  if (
    !selectedEWarung.value
  ) {
    transactionError.value =
      'Pilih E-Warung aktif terlebih dahulu melalui Beranda Surveyor.'

    return
  }

  confirmationParticipant.value =
    participant
}

function closeTransactionConfirmation():
  void {
  if (
    transactionLoadingId.value
    !== null
  ) {
    return
  }

  confirmationParticipant.value =
    null
}

function markParticipantAsTransacted(
  participantId: number,
): void {
  participants.value =
    participants.value.map(
      (participant) => {
        if (
          participant.id
          !==
          participantId
        ) {
          return participant
        }

        return {
          ...participant,

          activity: {
            code:
              'transacted',

            label:
              'Sudah Bertransaksi',

            is_final:
              true,

            can_record_transaction:
              false,
          },
        }
      },
    )
}

async function confirmTransaction():
  Promise<void> {
  const participant =
    confirmationParticipant.value

  if (
    !participant
    ||
    transactionLoadingId.value
      !== null
  ) {
    return
  }

  transactionError.value =
    ''

  transactionSuccess.value =
    ''

  transactionLoadingId.value =
    participant.id

  try {
    await refreshEWarungs()

    const eWarung =
      selectedEWarung.value

    if (!eWarung) {
      confirmationParticipant.value =
        null

      transactionError.value =
        'E-Warung yang dipilih sudah tidak aktif. Pilih kembali melalui Beranda Surveyor.'

      return
    }

    await surveyorWorkspaceService
      .storeTransaction({
        bpnt_participant_id:
          participant.id,

        e_warung_id:
          eWarung.id,
      })

    markParticipantAsTransacted(
      participant.id,
    )

    confirmationParticipant.value =
      null

    transactionSuccess.value =
      `${participant.kpm.full_name} berhasil diperbarui menjadi Sudah Bertransaksi.`
  } catch (
    error: unknown
  ) {
    confirmationParticipant.value =
      null

    transactionError.value =
      resolveErrorMessage(
        error,
        'Status transaksi KPM belum dapat disimpan.',
      )

    await loadParticipants(
      true,
    )
  } finally {
    transactionLoadingId.value =
      null
  }
}

watch(
  searchTerm,
  () => {
    clearSearchTimer()

    participantRequestSequence++

    searchTimer =
      setTimeout(
        () => {
          searchTimer =
            null

          void loadParticipants(
            true,
          )
        },
        SEARCH_DELAY,
      )
  },
)

onMounted(() => {
  void loadWorkspace()
})

onBeforeUnmount(() => {
  clearSearchTimer()

  participantRequestSequence++
})
</script>

<template>
  <section class="kpm-page">
    <div
      v-if="workspaceLoading"
      class="workspace-loading"
      data-testid="kpm-loading"
      aria-live="polite"
    >
      <div class="skeleton title-skeleton" />
      <div class="skeleton subtitle-skeleton" />
      <div class="skeleton search-skeleton" />
      <div class="skeleton card-skeleton" />
      <div class="skeleton card-skeleton" />

      <span class="sr-only">
        Memuat data KPM
      </span>
    </div>

    <article
      v-else-if="workspaceError"
      class="state-card error-state"
      role="alert"
      data-testid="kpm-workspace-error"
    >
      <div class="state-icon error-icon">
        <CircleAlert
          :size="29"
          :stroke-width="1.9"
        />
      </div>

      <strong>
        Data KPM belum dapat dimuat
      </strong>

      <p>
        {{ workspaceError }}
      </p>

      <button
        type="button"
        class="retry-button"
        @click="loadWorkspace"
      >
        <RefreshCw
          :size="18"
          :stroke-width="2"
        />

        Coba Lagi
      </button>
    </article>

    <article
      v-else-if="
        context
        &&
        !context.period
      "
      class="state-card warning-state"
      data-testid="kpm-no-period"
    >
      <div class="state-icon warning-icon">
        <UsersRound
          :size="30"
          :stroke-width="1.8"
        />
      </div>

      <strong>
        Belum ada periode aktif
      </strong>

      <p>
        Daftar KPM belum tersedia karena periode
        BPNT belum diaktifkan oleh Admin Dinsos.
      </p>
    </article>

    <article
      v-else-if="
        context
        &&
        !context.assignment
      "
      class="state-card warning-state"
      data-testid="kpm-no-assignment"
    >
      <div class="state-icon warning-icon">
        <MapPin
          :size="30"
          :stroke-width="1.8"
        />
      </div>

      <strong>
        Anda belum memiliki wilayah tugas
      </strong>

      <p>
        Hubungi Manager BPNT agar Kelurahan
        assignment dapat ditetapkan.
      </p>
    </article>

    <template v-else-if="context">
      <header class="page-header">
        <div>
          <span class="eyebrow">
            Data KPM
          </span>

          <h1 data-testid="kpm-page-title">
            {{ pageTitle }}
          </h1>

          <p>
            {{ totalParticipants.toLocaleString('id-ID') }}
            KPM pada wilayah tugas Anda.
          </p>
        </div>

        <div class="header-icon">
          <UsersRound
            :size="25"
            :stroke-width="1.9"
          />
        </div>
      </header>

      <article
        class="e-warung-summary"
        :class="{
          'e-warung-missing':
            !selectedEWarungValue,
        }"
        data-testid="kpm-selected-e-warung"
      >
        <Store
          :size="21"
          :stroke-width="1.9"
        />

        <div>
          <span>
            E-Warung transaksi
          </span>

          <strong>
            {{
              selectedEWarungValue
                ?.name
              ??
              'Belum dipilih pada Beranda'
            }}
          </strong>
        </div>
      </article>

      <form
        class="search-form"
        role="search"
        @submit.prevent="submitSearch"
      >
        <Search
          :size="20"
          :stroke-width="1.9"
          class="search-icon"
        />

        <input
          v-model="searchTerm"
          type="search"
          name="search"
          :placeholder="searchPlaceholder"
          aria-label="Cari nama atau NIK KPM dalam wilayah tugas"
          autocomplete="off"
          enterkeyhint="search"
          data-testid="kpm-search"
        />

        <button
          v-if="hasSearch"
          type="button"
          class="clear-search"
          aria-label="Hapus pencarian"
          @click="clearSearch"
        >
          <X
            :size="18"
            :stroke-width="2"
          />
        </button>
      </form>

      <p class="search-scope">
        Pencarian pada halaman ini hanya mencakup
        KPM di Kelurahan

        <strong>
          {{ context.assignment?.kelurahan.name }}
        </strong>.
      </p>

      <article
        v-if="transactionSuccess"
        class="transaction-message success-message"
        role="status"
        data-testid="kpm-transaction-success"
      >
        <CheckCircle2
          :size="22"
          :stroke-width="2"
        />

        <p>
          {{ transactionSuccess }}
        </p>
      </article>

      <article
        v-if="transactionError"
        class="transaction-message transaction-error"
        role="alert"
        data-testid="kpm-transaction-error"
      >
        <CircleAlert
          :size="22"
          :stroke-width="1.9"
        />

        <p>
          {{ transactionError }}
        </p>
      </article>

      <div
        v-if="participantsLoading"
        class="participant-loading"
        data-testid="participants-loading"
      >
        <div
          v-for="number in 3"
          :key="number"
          class="skeleton card-skeleton"
        />
      </div>

      <article
        v-else-if="participantsError"
        class="inline-error"
        role="alert"
        data-testid="participants-error"
      >
        <CircleAlert
          :size="23"
          :stroke-width="1.9"
        />

        <div>
          <strong>
            Daftar KPM gagal dimuat
          </strong>

          <p>
            {{ participantsError }}
          </p>
        </div>

        <button
          type="button"
          @click="loadParticipants(true)"
        >
          Coba Lagi
        </button>
      </article>

      <article
        v-else-if="
          participants.length === 0
        "
        class="state-card empty-state"
        data-testid="participants-empty"
      >
        <div class="state-icon empty-icon">
          <UserRound
            :size="29"
            :stroke-width="1.8"
          />
        </div>

        <strong>
          {{
            hasSearch
              ? 'KPM tidak ditemukan'
              : 'Belum ada KPM di wilayah ini'
          }}
        </strong>

        <p>
          {{
            hasSearch
              ? 'Periksa kembali nama atau NIK yang dimasukkan.'
              : 'Data KPM akan tampil setelah BNBA tersedia pada periode aktif.'
          }}
        </p>
      </article>

      <div
        v-else
        class="participant-list"
        data-testid="participant-list"
      >
        <article
          v-for="participant in participants"
          :key="participant.id"
          class="participant-card"
          data-testid="participant-card"
        >
          <header class="participant-header">
            <div class="participant-avatar">
              {{ participantInitials(participant) }}
            </div>

            <div class="participant-identity">
              <strong>
                {{ participant.kpm.full_name }}
              </strong>

              <span>
                NIK {{ participant.kpm.nik }}
              </span>
            </div>

            <span
              class="activity-badge"
              :class="{
                transacted:
                  activityOf(participant).code
                  === 'transacted',

                verified:
                  isVerifiedActivity(participant),
              }"
              data-testid="kpm-activity-badge"
            >
              {{ activityOf(participant).label }}
            </span>
          </header>

          <div class="participant-address">
            <MapPin
              :size="18"
              :stroke-width="1.8"
            />

            <div>
              <strong>
                {{
                  participant
                    .wilayah
                    .kelurahan
                    .name
                  ??
                  '-'
                }}
              </strong>

              <p>
                {{ participantAddress(participant) }}
              </p>
            </div>
          </div>

          <footer class="participant-footer">
            <div class="balance-copy">
              <span>
                Saldo BPNT
              </span>

              <strong>
                {{ formatCurrency(participant.saldo_bpnt) }}
              </strong>
            </div>

            <button
              v-if="
                activityOf(participant)
                  .can_record_transaction
              "
              type="button"
              class="transaction-button"
              :disabled="
                !selectedEWarungValue
                ||
                transactionLoadingId
                  !== null
              "
              data-testid="kpm-transaction-button"
              @click="
                openTransactionConfirmation(
                  participant,
                )
              "
            >
              <ClipboardCheck
                :size="17"
                :stroke-width="2"
              />

              Sudah Bertransaksi
            </button>

            <div
              v-else
              class="final-indicator"
              data-testid="kpm-final-indicator"
            >
              <CheckCircle2
                :size="18"
                :stroke-width="2"
              />

              Status final
            </div>
          </footer>

          <p
            v-if="
              activityOf(participant)
                .can_record_transaction
              &&
              !selectedEWarungValue
            "
            class="selection-note"
          >
            Pilih E-Warung aktif melalui Beranda
            untuk memperbarui transaksi.
          </p>
        </article>
      </div>

      <button
        v-if="
          !participantsLoading
          &&
          hasMorePages
        "
        type="button"
        class="load-more-button"
        :disabled="loadingMore"
        data-testid="load-more"
        @click="loadMore"
      >
        <RefreshCw
          :size="18"
          :stroke-width="2"
          :class="{
            spinning:
              loadingMore,
          }"
        />

        {{
          loadingMore
            ? 'Memuat...'
            : 'Muat KPM Berikutnya'
        }}
      </button>

      <p
        v-if="
          participants.length > 0
        "
        class="list-summary"
      >
        Menampilkan
        {{ participants.length.toLocaleString('id-ID') }}
        dari
        {{ totalParticipants.toLocaleString('id-ID') }}
        KPM
      </p>
    </template>

    <div
      v-if="confirmationParticipant"
      class="modal-backdrop"
      data-testid="kpm-transaction-confirmation"
      @click.self="closeTransactionConfirmation"
    >
      <article
        class="confirmation-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="transaction-confirmation-title"
      >
        <div class="confirmation-icon">
          <ClipboardCheck
            :size="29"
            :stroke-width="1.9"
          />
        </div>

        <span>
          Konfirmasi transaksi
        </span>

        <h2 id="transaction-confirmation-title">
          {{ confirmationParticipant.kpm.full_name }}
        </h2>

        <p>
          Tandai KPM ini sudah bertransaksi di

          <strong>
            {{ selectedEWarungValue?.name }}
          </strong>?
        </p>

        <div class="confirmation-actions">
          <button
            type="button"
            class="cancel-button"
            :disabled="
              transactionLoadingId !== null
            "
            @click="closeTransactionConfirmation"
          >
            Batal
          </button>

          <button
            type="button"
            class="confirm-button"
            :disabled="
              transactionLoadingId !== null
            "
            data-testid="kpm-confirm-transaction"
            @click="confirmTransaction"
          >
            <LoaderCircle
              v-if="
                transactionLoadingId !== null
              "
              :size="18"
              class="spinning"
            />

            {{
              transactionLoadingId !== null
                ? 'Menyimpan...'
                : 'Ya, Sudah Bertransaksi'
            }}
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.kpm-page {
  display: grid;
  gap: 17px;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.eyebrow {
  color: #c12723;
  font-size: 12px;
  font-weight: 750;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.page-header h1 {
  margin: 4px 0;
  color: #173f37;
  font-size: 27px;
  line-height: 1.22;
}

.page-header p {
  margin: 0;
  color: #72847e;
  font-size: 13px;
}

.header-icon {
  display: grid;
  flex: 0 0 auto;
  place-items: center;

  width: 48px;
  height: 48px;

  border-radius: 16px;
  background: #e8f5f0;
  color: #006855;
}

.e-warung-summary {
  display: flex;
  align-items: center;
  gap: 11px;

  padding: 13px 14px;

  border: 1px solid #cfe4dc;
  border-radius: 16px;
  background: #f2faf7;
  color: #006855;
}

.e-warung-summary div {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.e-warung-summary span {
  color: #6f817b;
  font-size: 10px;
}

.e-warung-summary strong {
  overflow: hidden;
  color: #244b43;
  font-size: 13px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.e-warung-summary.e-warung-missing {
  border-color: #ecd9b8;
  background: #fff9ed;
  color: #bc6800;
}

.search-form {
  display: flex;
  align-items: center;
  gap: 10px;

  min-height: 52px;
  padding: 0 13px;

  border: 1px solid #d9e6e1;
  border-radius: 17px;
  background: #ffffff;

  box-shadow:
    0 8px 22px
    rgb(30 65 55 / 5%);
}

.search-form:focus-within {
  border-color: #58a28f;

  box-shadow:
    0 0 0 3px
    rgb(0 104 85 / 9%);
}

.search-icon {
  flex: 0 0 auto;
  color: #758a83;
}

.search-form input {
  min-width: 0;
  flex: 1;

  border: 0;
  outline: 0;
  background: transparent;
  color: #244b43;

  font-size: 14px;
}

.search-form input::placeholder {
  color: #96a49f;
}

.clear-search {
  display: grid;
  flex: 0 0 auto;
  place-items: center;

  width: 34px;
  height: 34px;
  padding: 0;

  border: 0;
  border-radius: 11px;
  background: #f1f5f3;
  color: #6c7f79;
}

.search-scope {
  margin: -7px 2px 0;
  color: #788a84;
  font-size: 11px;
  line-height: 1.5;
}

.search-scope strong {
  color: #45665d;
}

.transaction-message {
  display: flex;
  align-items: flex-start;
  gap: 10px;

  padding: 13px 14px;
  border-radius: 15px;
}

.transaction-message p {
  margin: 0;
  font-size: 12px;
  line-height: 1.5;
}

.success-message {
  border: 1px solid #bfe1d4;
  background: #effaf6;
  color: #08745e;
}

.transaction-error {
  border: 1px solid #efcdca;
  background: #fff8f7;
  color: #a3312d;
}

.participant-list {
  display: grid;
  gap: 12px;
}

.participant-card {
  overflow: hidden;

  border: 1px solid #dfe9e5;
  border-radius: 20px;
  background: #ffffff;

  box-shadow:
    0 9px 25px
    rgb(30 65 55 / 5%);
}

.participant-header {
  display: grid;
  grid-template-columns:
    auto
    minmax(0, 1fr);
  align-items: center;
  gap: 12px;

  padding: 16px;
}

.participant-avatar {
  display: grid;
  grid-row: 1 / 3;
  place-items: center;

  width: 45px;
  height: 45px;

  border-radius: 15px;
  background: #e8f5f0;
  color: #006855;

  font-size: 12px;
  font-weight: 800;
}

.participant-identity {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.participant-identity strong {
  overflow: hidden;
  color: #244b43;
  font-size: 15px;
  line-height: 1.4;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.participant-identity span {
  margin-top: 2px;
  color: #7b8c86;
  font-size: 11px;
}

.activity-badge {
  width: max-content;
  max-width: 100%;
  padding: 5px 9px;

  border-radius: 999px;
  background: #fff3de;
  color: #a85c00;

  font-size: 10px;
  font-weight: 750;
}

.activity-badge.transacted {
  background: #e5f5ee;
  color: #08745e;
}

.activity-badge.verified {
  background: #edf1f7;
  color: #53637a;
}

.participant-address {
  display: flex;
  align-items: flex-start;
  gap: 10px;

  margin: 0 16px;
  padding: 12px;

  border-radius: 14px;
  background: #f8faf9;
  color: #638079;
}

.participant-address > svg {
  flex: 0 0 auto;
  margin-top: 2px;
}

.participant-address div {
  min-width: 0;
}

.participant-address strong {
  color: #45665d;
  font-size: 12px;
}

.participant-address p {
  display: -webkit-box;
  overflow: hidden;

  margin: 2px 0 0;
  color: #7b8d87;

  font-size: 11px;
  line-height: 1.5;

  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
}

.participant-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;

  margin-top: 14px;
  padding: 13px 16px;

  border-top: 1px solid #edf1ef;
  background: #fcfdfc;
}

.balance-copy {
  display: flex;
  flex-direction: column;
}

.participant-footer span {
  color: #748780;
  font-size: 11px;
  font-weight: 650;
}

.participant-footer strong {
  color: #006855;
  font-size: 15px;
}

.transaction-button,
.confirm-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;

  min-height: 42px;
  padding: 0 13px;

  border: 0;
  border-radius: 13px;
  background: #006855;
  color: #ffffff;

  font-size: 11px;
  font-weight: 750;
}

.transaction-button:disabled,
.confirm-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.final-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  color: #08745e;
  font-size: 11px;
  font-weight: 750;
}

.selection-note {
  margin: 0;
  padding: 0 16px 14px;

  color: #9a6b2e;
  font-size: 10px;
  line-height: 1.5;
}

.load-more-button,
.retry-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;

  min-height: 45px;
  padding: 0 17px;

  border: 0;
  border-radius: 14px;
  background: #006855;
  color: #ffffff;

  font-size: 13px;
  font-weight: 700;
}

.load-more-button:disabled {
  opacity: 0.65;
}

.list-summary {
  margin: -5px 0 0;
  color: #80918b;
  font-size: 11px;
  text-align: center;
}

.inline-error {
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: start;
  gap: 11px;

  padding: 15px;

  border: 1px solid #efcdca;
  border-radius: 17px;
  background: #fff8f7;
  color: #c42c28;
}

.inline-error div {
  min-width: 0;
}

.inline-error strong {
  display: block;
  color: #8f2724;
  font-size: 13px;
}

.inline-error p {
  margin: 3px 0 0;
  color: #9a5a56;
  font-size: 11px;
  line-height: 1.5;
}

.inline-error button {
  grid-column: 2;

  width: max-content;
  padding: 6px 11px;

  border: 0;
  border-radius: 9px;
  background: #c42c28;
  color: #ffffff;

  font-size: 11px;
  font-weight: 700;
}

.state-card {
  display: grid;
  justify-items: center;

  padding: 31px 21px;

  border-radius: 22px;
  background: #ffffff;
  text-align: center;
}

.warning-state {
  border: 1px solid #ecd9b8;
}

.error-state {
  border: 1px solid #efcdca;
}

.empty-state {
  border: 1px dashed #ccddd7;
}

.state-icon {
  display: grid;
  place-items: center;

  width: 58px;
  height: 58px;
  margin-bottom: 13px;

  border-radius: 19px;
}

.warning-icon {
  background: #fff3de;
  color: #bc6800;
}

.error-icon {
  background: #fff0ef;
  color: #cd2b27;
}

.empty-icon {
  background: #eaf5f1;
  color: #006855;
}

.state-card strong {
  color: #244b43;
  font-size: 16px;
}

.state-card p {
  max-width: 320px;
  margin: 7px 0 0;
  color: #778984;
  font-size: 13px;
  line-height: 1.6;
}

.state-card .retry-button {
  margin-top: 17px;
}

.workspace-loading,
.participant-loading {
  display: grid;
  gap: 12px;
}

.skeleton {
  position: relative;
  overflow: hidden;

  border-radius: 12px;
  background: #e7efec;
}

.skeleton::after {
  position: absolute;
  inset: 0;

  background:
    linear-gradient(
      90deg,
      transparent,
      rgb(255 255 255 / 65%),
      transparent
    );

  content: "";
  transform: translateX(-100%);
  animation: skeleton-loading 1.2s infinite;
}

.title-skeleton {
  width: 62%;
  height: 27px;
}

.subtitle-skeleton {
  width: 43%;
  height: 14px;
}

.search-skeleton {
  width: 100%;
  height: 52px;
  margin-top: 5px;
  border-radius: 17px;
}

.card-skeleton {
  width: 100%;
  height: 196px;
  border-radius: 20px;
}

.spinning {
  animation: spin 800ms linear infinite;
}

.modal-backdrop {
  position: fixed;
  z-index: 80;
  inset: 0;

  display: grid;
  align-items: end;

  padding: 18px;
  background: rgb(16 40 34 / 48%);
}

.confirmation-card {
  width: min(100%, 430px);
  padding: 23px;

  border-radius: 24px;
  margin:
    0
    auto
    max(
      70px,
      env(safe-area-inset-bottom)
    );

  background: #ffffff;

  box-shadow:
    0 24px 70px
    rgb(0 32 25 / 24%);

  text-align: center;
}

.confirmation-icon {
  display: grid;
  place-items: center;

  width: 58px;
  height: 58px;
  margin: 0 auto 13px;

  border-radius: 19px;
  background: #e8f5f0;
  color: #006855;
}

.confirmation-card > span {
  color: #c12723;
  font-size: 11px;
  font-weight: 750;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.confirmation-card h2 {
  margin: 5px 0;
  color: #244b43;
  font-size: 20px;
}

.confirmation-card p {
  margin: 0;
  color: #71837d;
  font-size: 13px;
  line-height: 1.6;
}

.confirmation-card p strong {
  color: #244b43;
}

.confirmation-actions {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 9px;
  margin-top: 20px;
}

.cancel-button {
  min-height: 44px;

  border: 1px solid #d8e3df;
  border-radius: 13px;
  background: #ffffff;
  color: #566c65;

  font-weight: 700;
}

.sr-only {
  position: absolute;

  overflow: hidden;

  width: 1px;
  height: 1px;
  padding: 0;

  border: 0;
  margin: -1px;

  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

@keyframes skeleton-loading {
  100% {
    transform: translateX(100%);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (min-width: 560px) {
  .modal-backdrop {
    align-items: center;
  }

  .confirmation-card {
    margin-bottom: 0;
  }
}
</style>