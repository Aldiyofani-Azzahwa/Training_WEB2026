<script setup lang="ts">
import axios from 'axios'

import {
  computed,
  onMounted,
  ref,
} from 'vue'

import {
  BadgeCheck,
  CircleAlert,
  CircleCheck,
  MapPin,
  RefreshCw,
  ScanLine,
  Search,
  ShieldCheck,
  Store,
  X,
} from '@lucide/vue'

import { RouterLink } from 'vue-router'

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
  SurveyorNikLookupResult,
  SurveyorParticipant,
  SurveyorTransaction,
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

const NIK_LENGTH =
  16

const {
  selectedEWarung,
  synchronizeEWarungs,
} =
  useSurveyorEWarungSelection()

const context =
  ref<SurveyorWorkspaceContext | null>(
    null,
  )

const nik =
  ref('')

const lookedUpNik =
  ref('')

const result =
  ref<SurveyorNikLookupResult | null>(
    null,
  )

const transaction =
  ref<SurveyorTransaction | null>(
    null,
  )

const workspaceLoading =
  ref(true)

const lookupLoading =
  ref(false)

const transactionLoading =
  ref(false)

const confirmationOpen =
  ref(false)

const workspaceError =
  ref('')

const lookupError =
  ref('')

const validationError =
  ref('')

const transactionError =
  ref('')

const nikLength =
  computed(
    () =>
      nik.value.length,
  )

const canLookup =
  computed(
    () =>
      nik.value.length
        === NIK_LENGTH
      &&
      !lookupLoading.value,
  )

const participant =
  computed<SurveyorParticipant | null>(
    () =>
      result.value
        ?.participant
      ?? null,
  )

const participantActivity =
  computed(
    () =>
      participant.value
        ?.activity
      ?? null,
  )

const participantCanRecordTransaction =
  computed(
    () =>
      participantActivity.value
        ?.can_record_transaction
      ?? true,
  )

const canOpenConfirmation =
  computed(
    () =>
      Boolean(
        result.value
        &&
        participant.value
        &&
        lookedUpNik.value.length
          === NIK_LENGTH
        &&
        selectedEWarung.value
        &&
        participantCanRecordTransaction.value
        &&
        !transaction.value
        &&
        !transactionLoading.value,
      ),
  )

const participantKelurahan =
  computed(
    () =>
      participant.value
        ?.wilayah
        .kelurahan
        .name
      ?? '-',
  )

const participantKecamatan =
  computed(
    () =>
      participant.value
        ?.wilayah
        .kecamatan
        .name
      ?? '-',
  )

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
  value: SurveyorParticipant,
): string {
  return value
    .kpm
    .full_name
    .split(
      /\s+/,
    )
    .filter(
      Boolean,
    )
    .slice(
      0,
      2,
    )
    .map(
      (word) =>
        word
          .charAt(
            0,
          )
          .toUpperCase(),
    )
    .join('')
}

function participantAddress(
  value: SurveyorParticipant,
): string {
  return [
    value.kpm.address,

    value.kpm.rt
      ? `RT ${value.kpm.rt}`
      : null,

    value.kpm.rw
      ? `RW ${value.kpm.rw}`
      : null,
  ]
    .filter(
      (
        part,
      ): part is string =>
        typeof part
          === 'string'
        &&
        part.trim()
          !== '',
    )
    .join(
      ', ',
    )
}

function resolveErrorMessage(
  error: unknown,
  fallback: string,
): string {
  if (
    axios.isAxiosError<
      ValidationErrorResponse
    >(
      error,
    )
  ) {
    const response =
      error.response

    if (!response) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'
    }

    if (
      response.status
        === 401
      ||
      response.status
        === 419
    ) {
      return 'Sesi Anda sudah berakhir. Silakan masuk kembali.'
    }

    if (
      response.status
        === 403
    ) {
      return 'Akun Anda tidak memiliki akses ke fitur Surveyor.'
    }

    const fields = [
      'kpm',
      'e_warung_id',
      'nik',
      'period',
      'assignment',
    ]

    for (
      const field
      of fields
    ) {
      const message =
        response
          .data
          .errors
          ?.[field]
          ?.[0]

      if (message) {
        return message
      }
    }

    return (
      response
        .data
        .message
      ||
      fallback
    )
  }

  return fallback
}

function clearResult():
  void {
  result.value =
    null

  lookedUpNik.value =
    ''

  transaction.value =
    null

  transactionError.value =
    ''

  confirmationOpen.value =
    false
}

function handleNikInput(
  event: Event,
): void {
  const target =
    event.target

  if (
    !(
      target
      instanceof HTMLInputElement
    )
  ) {
    return
  }

  const normalized =
    target
      .value
      .replace(
        /\D+/g,
        '',
      )
      .slice(
        0,
        NIK_LENGTH,
      )

  nik.value =
    normalized

  target.value =
    normalized

  validationError.value =
    ''

  lookupError.value =
    ''

  clearResult()
}

function resetLookup():
  void {
  nik.value =
    ''

  lookupError.value =
    ''

  validationError.value =
    ''

  clearResult()
}

async function refreshEWarungs():
  Promise<void> {
  if (
    !context.value
      ?.assignment
  ) {
    return
  }

  const eWarungs =
    await surveyorWorkspaceService
      .getActiveEWarungs()

  synchronizeEWarungs(
    context.value
      .surveyor
      .id,

    eWarungs,
  )
}

async function loadWorkspace():
  Promise<void> {
  workspaceLoading.value =
    true

  workspaceError.value =
    ''

  try {
    context.value =
      await surveyorWorkspaceService
        .getContext()

    if (
      context.value.period
      &&
      context.value.assignment
    ) {
      try {
        await refreshEWarungs()
      } catch (
        error: unknown
      ) {
        transactionError.value =
          resolveErrorMessage(
            error,
            'Pilihan E-Warung belum dapat diverifikasi.',
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
        'Workspace Surveyor belum dapat dimuat.',
      )
  } finally {
    workspaceLoading.value =
      false
  }
}

async function submitLookup():
  Promise<void> {
  lookupError.value =
    ''

  validationError.value =
    ''

  clearResult()

  const normalizedNik =
    nik.value
      .replace(
        /\D+/g,
        '',
      )
      .slice(
        0,
        NIK_LENGTH,
      )

  nik.value =
    normalizedNik

  if (
    normalizedNik.length
    !== NIK_LENGTH
  ) {
    validationError.value =
      'NIK harus terdiri dari 16 digit angka.'

    return
  }

  lookupLoading.value =
    true

  try {
    result.value =
      await surveyorWorkspaceService
        .lookupNik(
          normalizedNik,
        )

    /*
     * Raw NIK hanya disimpan sementara
     * apabila transaksi masih diperbolehkan.
     *
     * Jika status KPM sudah final, raw NIK
     * langsung dibersihkan dari memory.
     */
    lookedUpNik.value =
      result.value
        .participant
        .activity
        ?.can_record_transaction
      === false
        ? ''
        : normalizedNik

    nik.value =
      ''
  } catch (
    error: unknown
  ) {
    lookupError.value =
      resolveErrorMessage(
        error,
        'KPM dengan NIK tersebut tidak ditemukan pada periode aktif.',
      )
  } finally {
    lookupLoading.value =
      false
  }
}

function openConfirmation():
  void {
  transactionError.value =
    ''

  if (
    !selectedEWarung.value
  ) {
    transactionError.value =
      'Pilih E-Warung tempat bertugas melalui Beranda.'

    return
  }

  if (
    !canOpenConfirmation.value
  ) {
    return
  }

  confirmationOpen.value =
    true
}

async function confirmTransaction():
  Promise<void> {
  if (
    !context.value
    ||
    lookedUpNik.value.length
      !== NIK_LENGTH
  ) {
    return
  }

  transactionLoading.value =
    true

  transactionError.value =
    ''

  try {
    /*
     * E-Warung divalidasi kembali tepat
     * sebelum transaksi disimpan.
     */
    await refreshEWarungs()

    if (
      !selectedEWarung.value
    ) {
      confirmationOpen.value =
        false

      transactionError.value =
        'E-Warung sebelumnya sudah tidak aktif. Pilih E-Warung aktif lain melalui Beranda.'

      return
    }

    transaction.value =
      await surveyorWorkspaceService
        .storeTransaction({
          nik:
            lookedUpNik.value,

          e_warung_id:
            selectedEWarung.value
              .id,
        })

    lookedUpNik.value =
      ''

    confirmationOpen.value =
      false
  } catch (
    error: unknown
  ) {
    transactionError.value =
      resolveErrorMessage(
        error,
        'Transaksi KPM gagal disimpan.',
      )
  } finally {
    transactionLoading.value =
      false
  }
}

onMounted(
  () => {
    void loadWorkspace()
  },
)
</script>

<template>
  <section class="lookup-page">
    <div
      v-if="workspaceLoading"
      class="workspace-loading"
      data-testid="lookup-workspace-loading"
    >
      <div class="skeleton title-skeleton" />
      <div class="skeleton card-skeleton" />
    </div>

    <article
      v-else-if="workspaceError"
      class="state-card error-state"
      role="alert"
      data-testid="lookup-workspace-error"
    >
      <CircleAlert :size="29" />

      <strong>
        Halaman belum dapat dimuat
      </strong>

      <p>
        {{ workspaceError }}
      </p>

      <button
        type="button"
        class="retry-button"
        @click="loadWorkspace"
      >
        <RefreshCw :size="18" />

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
      data-testid="lookup-no-period"
    >
      <ScanLine :size="30" />

      <strong>
        Belum ada periode aktif
      </strong>

      <p>
        Pencarian KPM belum dapat digunakan.
      </p>
    </article>

    <article
      v-else-if="
        context
        &&
        !context.assignment
      "
      class="state-card warning-state"
      data-testid="lookup-no-assignment"
    >
      <MapPin :size="30" />

      <strong>
        Anda belum memiliki wilayah tugas
      </strong>

      <p>
        Hubungi Manager BPNT sebelum menggunakan pencarian KPM.
      </p>
    </article>

    <template v-else-if="context">
      <header class="page-header">
        <div>
          <span>
            Exact NIK Lookup
          </span>

          <h1>
            Scan KTP
          </h1>

          <p>
            Masukkan NIK 16 digit untuk mencari KPM pada periode aktif.
          </p>
        </div>

        <div class="header-icon">
          <ScanLine :size="27" />
        </div>
      </header>

      <article
        class="active-store"
        :class="{
          missing:
            !selectedEWarung,
        }"
        data-testid="active-e-warung"
      >
        <Store :size="22" />

        <div>
          <span>
            Tempat Bertugas Saat Ini
          </span>

          <strong>
            {{
              selectedEWarung
                ?.name
              ??
              'Belum memilih E-Warung'
            }}
          </strong>
        </div>

        <RouterLink
          :to="{
            name:
              'surveyor-home',
          }"
        >
          {{
            selectedEWarung
              ? 'Ganti'
              : 'Pilih'
          }}
        </RouterLink>
      </article>

      <article class="manual-lookup-card">
        <div class="lookup-mode">
          <ShieldCheck :size="18" />

          Exact NIK Lookup
        </div>

        <form
          novalidate
          @submit.prevent="submitLookup"
        >
          <label for="surveyor-nik">
            Masukkan NIK
          </label>

          <div
            class="nik-input-wrapper"
            :class="{
              invalid:
                validationError,
            }"
          >
            <Search :size="21" />

            <input
              id="surveyor-nik"
              :value="nik"
              type="text"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="16"
              autocomplete="off"
              enterkeyhint="search"
              placeholder="16 digit NIK"
              data-testid="nik-input"
              @input="handleNikInput"
            />

            <span>
              {{ nikLength }}/16
            </span>
          </div>

          <p
            v-if="validationError"
            class="field-error"
            role="alert"
            data-testid="nik-validation-error"
          >
            {{ validationError }}
          </p>

          <button
            type="submit"
            class="lookup-button"
            :disabled="!canLookup"
            data-testid="lookup-submit"
          >
            <RefreshCw
              v-if="lookupLoading"
              :size="19"
              class="spinning"
            />

            <Search
              v-else
              :size="19"
            />

            {{
              lookupLoading
                ? 'Mencari KPM...'
                : 'Cari KPM'
            }}
          </button>
        </form>
      </article>

      <article
        v-if="lookupError"
        class="message error-message"
        role="alert"
        data-testid="lookup-error"
      >
        <CircleAlert :size="22" />

        <p>
          {{ lookupError }}
        </p>
      </article>

      <article
        v-if="
          result
          &&
          participant
        "
        class="result-card"
        data-testid="lookup-result"
      >
        <header>
          <div
            class="scope-badge"
            :class="{
              outside:
                result
                  .scope
                  .outside_assignment,
            }"
          >
            <BadgeCheck :size="18" />

            {{
              result
                .scope
                .label
            }}
          </div>

          <span>
            KPM ditemukan
          </span>
        </header>

        <div class="participant-profile">
          <div class="avatar">
            {{
              participantInitials(
                participant,
              )
            }}
          </div>

          <div>
            <strong>
              {{
                participant
                  .kpm
                  .full_name
              }}
            </strong>

            <span>
              NIK
              {{
                participant
                  .kpm
                  .nik
              }}
            </span>
          </div>
        </div>

        <dl>
          <div>
            <dt>
              Wilayah Asli KPM
            </dt>

            <dd>
              {{ participantKelurahan }},
              {{ participantKecamatan }}
            </dd>
          </div>

          <div>
            <dt>
              Alamat
            </dt>

            <dd>
              {{
                participantAddress(
                  participant,
                )
              }}
            </dd>
          </div>
        </dl>

        <div class="saldo-card">
          <span>
            Saldo BPNT
          </span>

          <strong>
            {{
              formatCurrency(
                participant
                  .saldo_bpnt,
              )
            }}
          </strong>
        </div>

        <div
          v-if="
            result
              .scope
              .outside_assignment
          "
          class="scope-notice outside-notice"
          data-testid="outside-assignment-notice"
        >
          <MapPin :size="20" />

          <p>
            KPM berasal dari
            <strong>
              {{ participantKelurahan }}
            </strong>,
            sedangkan wilayah tugas Anda
            <strong>
              {{
                result
                  .scope
                  .surveyor_kelurahan
                  .name
              }}
            </strong>.
            KPM luar wilayah tetap diperbolehkan melakukan transaksi.
          </p>
        </div>

        <div
          v-else
          class="scope-notice inside-notice"
          data-testid="inside-assignment-notice"
        >
          <BadgeCheck :size="20" />

          <p>
            KPM berada dalam wilayah tugas Anda.
          </p>
        </div>

        <article
          v-if="transaction"
          class="transaction-success"
          data-testid="transaction-success"
        >
          <CircleCheck :size="28" />

          <div>
            <strong>
              Sudah Bertransaksi
            </strong>

            <span>
              {{
                transaction
                  .e_warung
                  .name
              }}
            </span>
          </div>
        </article>

        <article
          v-else-if="
            participantActivity
              ?.is_final
          "
          class="transaction-success"
          data-testid="participant-final-status"
        >
          <CircleCheck :size="28" />

          <div>
            <strong>
              {{
                participantActivity
                  .label
              }}
            </strong>

            <span>
              Status KPM sudah final pada periode aktif.
            </span>
          </div>
        </article>

        <template v-else>
          <button
            type="button"
            class="transaction-button"
            :disabled="
              !canOpenConfirmation
            "
            data-testid="transaction-button"
            @click="openConfirmation"
          >
            Sudah Bertransaksi
          </button>

          <p
            v-if="
              !selectedEWarung
            "
            class="transaction-note"
          >
            Pilih E-Warung tempat bertugas melalui Beranda sebelum mencatat transaksi.
          </p>
        </template>

        <p
          v-if="transactionError"
          class="field-error transaction-error"
          role="alert"
          data-testid="transaction-error"
        >
          {{ transactionError }}
        </p>

        <button
          type="button"
          class="new-lookup-button"
          data-testid="new-lookup"
          @click="resetLookup"
        >
          Cari NIK Lain
        </button>
      </article>

      <div
        v-if="
          confirmationOpen
          &&
          participant
          &&
          selectedEWarung
        "
        class="modal-backdrop"
        data-testid="transaction-confirmation"
      >
        <article
          class="confirmation-card"
          role="dialog"
          aria-modal="true"
          aria-labelledby="transaction-confirmation-title"
        >
          <button
            type="button"
            class="close-button"
            aria-label="Tutup konfirmasi"
            :disabled="transactionLoading"
            @click="
              confirmationOpen
                = false
            "
          >
            <X :size="20" />
          </button>

          <div class="confirmation-icon">
            <Store :size="27" />
          </div>

          <h2 id="transaction-confirmation-title">
            Konfirmasi transaksi
          </h2>

          <p>
            Tandai
            <strong>
              {{
                participant
                  .kpm
                  .full_name
              }}
            </strong>
            sudah bertransaksi di
            <strong>
              {{
                selectedEWarung
                  .name
              }}
            </strong>?
          </p>

          <small>
            Satu KPM hanya dapat memiliki satu transaksi dalam satu periode.
          </small>

          <div class="confirmation-actions">
            <button
              type="button"
              class="cancel-button"
              :disabled="transactionLoading"
              @click="
                confirmationOpen
                  = false
              "
            >
              Batal
            </button>

            <button
              type="button"
              class="confirm-button"
              :disabled="transactionLoading"
              data-testid="confirm-transaction"
              @click="confirmTransaction"
            >
              <RefreshCw
                v-if="transactionLoading"
                :size="18"
                class="spinning"
              />

              {{
                transactionLoading
                  ? 'Menyimpan...'
                  : 'Ya, Sudah Bertransaksi'
              }}
            </button>
          </div>
        </article>
      </div>
    </template>
  </section>
</template>

<style scoped>
.lookup-page {
  display: grid;
  gap: 17px;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 13px;
}

.page-header span {
  color: #c12723;
  font-size: 11px;
  font-weight: 750;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.page-header h1 {
  margin: 4px 0;
  color: #173f37;
  font-size: 28px;
}

.page-header p {
  max-width: 330px;
  margin: 0;
  color: #72847e;
  font-size: 13px;
  line-height: 1.55;
}

.header-icon {
  display: grid;
  width: 49px;
  height: 49px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 16px;
  background: #e8f5f0;
  color: #006855;
}

.active-store {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 14px;
  border: 1px solid #cfe3dc;
  border-radius: 17px;
  background: #f5fbf8;
  color: #006855;
}

.active-store.missing {
  border-color: #ecd5ad;
  background: #fff9ef;
  color: #ae6100;
}

.active-store > div {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.active-store span {
  color: #72847e;
  font-size: 10px;
}

.active-store strong {
  overflow: hidden;
  color: #244b43;
  font-size: 13px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.active-store a {
  flex: 0 0 auto;
  color: currentcolor;
  font-size: 12px;
  font-weight: 750;
}

.manual-lookup-card,
.result-card {
  padding: 18px;
  border: 1px solid #dce9e4;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 12px 28px rgb(30 65 55 / 6%);
}

.lookup-mode {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 14px;
  padding: 6px 9px;
  border-radius: 999px;
  background: #e8f5f0;
  color: #006855;
  font-size: 10px;
  font-weight: 750;
}

.manual-lookup-card label {
  display: block;
  margin-bottom: 7px;
  color: #35594f;
  font-size: 13px;
  font-weight: 700;
}

.nik-input-wrapper {
  display: flex;
  min-height: 54px;
  align-items: center;
  gap: 9px;
  padding: 0 13px;
  border: 1px solid #d6e3de;
  border-radius: 15px;
  color: #70857e;
}

.nik-input-wrapper:focus-within {
  border-color: #4e9b88;
  box-shadow: 0 0 0 3px rgb(0 104 85 / 9%);
}

.nik-input-wrapper.invalid {
  border-color: #dc5d58;
}

.nik-input-wrapper input {
  min-width: 0;
  flex: 1;
  border: 0;
  outline: 0;
  color: #244b43;
  font-size: 16px;
  font-weight: 650;
}

.nik-input-wrapper > span {
  font-size: 10px;
}

.lookup-button,
.transaction-button,
.confirm-button {
  display: flex;
  width: 100%;
  min-height: 48px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 15px;
  border: 0;
  border-radius: 14px;
  background: #006855;
  color: #ffffff;
  font-size: 13px;
  font-weight: 750;
}

.lookup-button:disabled,
.transaction-button:disabled,
.confirm-button:disabled {
  opacity: 0.48;
}

.message,
.scope-notice {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  padding: 13px;
  border-radius: 15px;
}

.error-message {
  border: 1px solid #efcdca;
  background: #fff8f7;
  color: #c42c28;
}

.message p,
.scope-notice p {
  margin: 0;
  font-size: 11px;
  line-height: 1.55;
}

.result-card {
  display: grid;
  gap: 15px;
}

.result-card > header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.result-card > header > span {
  color: #72847e;
  font-size: 10px;
}

.scope-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 9px;
  border-radius: 999px;
  background: #e5f6f0;
  color: #006855;
  font-size: 10px;
  font-weight: 750;
}

.scope-badge.outside {
  background: #fff1dc;
  color: #b86100;
}

.participant-profile {
  display: flex;
  align-items: center;
  gap: 11px;
}

.avatar {
  display: grid;
  width: 49px;
  height: 49px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 16px;
  background: #e8f5f0;
  color: #006855;
  font-size: 12px;
  font-weight: 800;
}

.participant-profile > div:last-child {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.participant-profile strong {
  overflow: hidden;
  color: #244b43;
  font-size: 16px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.participant-profile span {
  color: #7b8c86;
  font-size: 11px;
}

.result-card dl {
  display: grid;
  gap: 9px;
  margin: 0;
}

.result-card dl div {
  padding: 11px;
  border-radius: 13px;
  background: #f8fbfa;
}

.result-card dt {
  color: #7a8c86;
  font-size: 10px;
}

.result-card dd {
  margin: 3px 0 0;
  color: #35594f;
  font-size: 12px;
  line-height: 1.45;
}

.saldo-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 13px;
  border-radius: 14px;
  background: #173f37;
  color: #ffffff;
}

.saldo-card span {
  font-size: 11px;
}

.saldo-card strong {
  font-size: 17px;
}

.inside-notice {
  background: #edf8f4;
  color: #006855;
}

.outside-notice {
  background: #fff6e8;
  color: #9c5600;
}

.transaction-button {
  margin: 0;
  background: #c12723;
}

.transaction-note,
.field-error {
  margin: 0;
  color: #a85a00;
  font-size: 11px;
  line-height: 1.5;
  text-align: center;
}

.field-error,
.transaction-error {
  color: #c42c28;
}

.new-lookup-button {
  min-height: 40px;
  border: 1px solid #d6e3de;
  border-radius: 12px;
  background: #ffffff;
  color: #45655c;
  font-weight: 700;
}

.transaction-success {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 14px;
  border: 1px solid #b9dfd2;
  border-radius: 15px;
  background: #eaf8f3;
  color: #006855;
}

.transaction-success div {
  display: flex;
  flex-direction: column;
}

.transaction-success span {
  color: #52736a;
  font-size: 11px;
}

.modal-backdrop {
  position: fixed;
  z-index: 80;
  inset: 0;
  display: grid;
  place-items: end center;
  padding: 18px;
  background: rgb(16 40 34 / 55%);
}

.confirmation-card {
  position: relative;
  width: min(100%, 520px);
  padding: 24px 19px 19px;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 22px 50px rgb(12 36 29 / 28%);
}

.close-button {
  position: absolute;
  top: 13px;
  right: 13px;
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border: 0;
  border-radius: 12px;
  background: #f1f5f3;
  color: #526a62;
}

.confirmation-icon {
  display: grid;
  width: 53px;
  height: 53px;
  place-items: center;
  border-radius: 17px;
  background: #e8f5f0;
  color: #006855;
}

.confirmation-card h2 {
  margin: 15px 0 6px;
  color: #173f37;
  font-size: 20px;
}

.confirmation-card p {
  margin: 0;
  color: #5e746d;
  font-size: 13px;
  line-height: 1.6;
}

.confirmation-card small {
  display: block;
  margin-top: 9px;
  color: #a65a00;
  font-size: 10px;
  line-height: 1.5;
}

.confirmation-actions {
  display: grid;
  grid-template-columns: 0.75fr 1.25fr;
  gap: 9px;
  margin-top: 18px;
}

.confirmation-actions button {
  min-height: 47px;
  margin: 0;
  border-radius: 14px;
  font-size: 12px;
  font-weight: 750;
}

.cancel-button {
  border: 1px solid #d6e3de;
  background: #ffffff;
  color: #526a62;
}

.state-card {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 28px 20px;
  border: 1px solid #ecd9b8;
  border-radius: 22px;
  background: #ffffff;
  color: #b76500;
  text-align: center;
}

.error-state {
  border-color: #efcdca;
  color: #c42c28;
}

.state-card strong {
  color: #244b43;
}

.state-card p {
  margin: 0;
  color: #71837d;
  font-size: 12px;
}

.retry-button {
  display: flex;
  min-height: 42px;
  align-items: center;
  gap: 7px;
  margin-top: 8px;
  padding: 0 14px;
  border: 0;
  border-radius: 12px;
  background: #006855;
  color: #ffffff;
  font-weight: 700;
}

.workspace-loading {
  display: grid;
  gap: 12px;
}

.skeleton {
  border-radius: 13px;
  background: #e7efec;
}

.title-skeleton {
  width: 55%;
  height: 24px;
}

.card-skeleton {
  height: 230px;
  border-radius: 22px;
}

.spinning {
  animation: spin 800ms linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>