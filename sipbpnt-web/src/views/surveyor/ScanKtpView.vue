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
  Store,
  X,
} from '@lucide/vue'

import {
  RouterLink,
} from 'vue-router'

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
  <section
    class="grid w-full min-w-0 gap-[17px] lg:gap-5"
  >
    <div
      v-if="workspaceLoading"
      class="grid gap-3"
      data-testid="lookup-workspace-loading"
    >
      <div
        class="h-6 w-[55%] animate-pulse rounded-[13px] bg-[#e7efec]"
      />

      <div
        class="h-[230px] animate-pulse rounded-[22px] bg-[#e7efec]"
      />
    </div>

    <article
      v-else-if="workspaceError"
      class="grid justify-items-center gap-2 rounded-[22px] border border-[#efcdca] bg-white px-5 py-7 text-center text-[#c42c28]"
      role="alert"
      data-testid="lookup-workspace-error"
    >
      <CircleAlert :size="29" />

      <strong class="text-[#244b43]">
        Halaman belum dapat dimuat
      </strong>

      <p
        class="m-0 text-xs text-[#71837d]"
      >
        {{ workspaceError }}
      </p>

      <button
        type="button"
        class="mt-2 flex min-h-[42px] items-center gap-[7px] rounded-xl border-0 bg-[#006855] px-[14px] font-bold text-white transition-colors hover:bg-[#005746]"
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
      class="grid justify-items-center gap-2 rounded-[22px] border border-[#ecd9b8] bg-white px-5 py-7 text-center text-[#b76500]"
      data-testid="lookup-no-period"
    >
      <ScanLine :size="30" />

      <strong class="text-[#244b43]">
        Belum ada periode aktif
      </strong>

      <p
        class="m-0 text-xs text-[#71837d]"
      >
        Pencarian KPM belum dapat digunakan.
      </p>
    </article>

    <article
      v-else-if="
        context
        &&
        !context.assignment
      "
      class="grid justify-items-center gap-2 rounded-[22px] border border-[#ecd9b8] bg-white px-5 py-7 text-center text-[#b76500]"
      data-testid="lookup-no-assignment"
    >
      <MapPin :size="30" />

      <strong class="text-[#244b43]">
        Anda belum memiliki wilayah tugas
      </strong>

      <p
        class="m-0 text-xs text-[#71837d]"
      >
        Hubungi Manager BPNT sebelum
        menggunakan pencarian KPM.
      </p>
    </article>

    <template v-else-if="context">
      <header
        class="flex items-start justify-between gap-[13px]"
      >
        <div class="min-w-0">
          <h1
            class="my-1 text-[28px] leading-tight font-bold text-[#173f37] lg:text-[34px]"
          >
            Scan KTP
          </h1>

          <p
            class="m-0 max-w-[330px] text-[13px] leading-[1.55] text-[#72847e]"
          >
            Masukkan NIK 16 digit untuk mencari
            KPM
          </p>
        </div>

        <div
          class="grid size-[49px] shrink-0 place-items-center rounded-2xl bg-[#e8f5f0] text-[#006855]"
        >
          <ScanLine :size="27" />
        </div>
      </header>

      <article
        class="flex w-full min-w-0 items-center gap-[11px] overflow-hidden rounded-[17px] border p-[14px]"
        :class="
          selectedEWarung
            ? 'border-[#cfe3dc] bg-[#f5fbf8] text-[#006855]'
            : 'border-[#ecd5ad] bg-[#fff9ef] text-[#ae6100]'
        "
        data-testid="active-e-warung"
      >
        <Store
          :size="22"
          class="shrink-0"
        />

        <div
          class="flex min-w-0 flex-1 flex-col overflow-hidden"
        >
          <span
            class="text-[10px] text-[#72847e]"
          >
            Tempat Bertugas Saat Ini
          </span>

          <strong
            class="block max-w-full overflow-hidden text-[13px] font-bold text-ellipsis whitespace-nowrap text-[#244b43]"
            :title="
              selectedEWarung
                ?.name
              ??
              'Belum memilih E-Warung'
            "
          >
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
          class="shrink-0 text-xs font-[750] text-current"
        >
          {{
            selectedEWarung
              ? 'Ganti'
              : 'Pilih'
          }}
        </RouterLink>
      </article>

      <article
        class="rounded-[22px] border border-[#dce9e4] bg-white p-[18px] shadow-[0_12px_28px_rgb(30_65_55_/_6%)] lg:p-[22px]"
      >
        <form
          novalidate
          @submit.prevent="submitLookup"
        >
          <label
            for="surveyor-nik"
            class="mb-[7px] block text-[13px] font-bold text-[#35594f]"
          >
            Masukkan NIK
          </label>

          <div
            class="flex min-h-[54px] w-full min-w-0 items-center gap-[9px] rounded-[15px] border px-[13px] text-[#70857e] transition-[border-color,box-shadow] focus-within:border-[#4e9b88] focus-within:shadow-[0_0_0_3px_rgb(0_104_85_/_9%)]"
            :class="
              validationError
                ? 'border-[#dc5d58]'
                : 'border-[#d6e3de]'
            "
          >
            <Search
              :size="21"
              class="shrink-0"
            />

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
              class="min-w-0 flex-1 border-0 bg-transparent text-base font-[650] text-[#244b43] outline-none placeholder:text-[#9aa8a3]"
              data-testid="nik-input"
              @input="handleNikInput"
            />

            <span class="shrink-0 text-[10px]">
              {{ nikLength }}/16
            </span>
          </div>

          <p
            v-if="validationError"
            class="m-0 mt-2 text-center text-[11px] leading-[1.5] text-[#c42c28]"
            role="alert"
            data-testid="nik-validation-error"
          >
            {{ validationError }}
          </p>

          <button
            type="submit"
            class="mt-[15px] flex min-h-12 w-full items-center justify-center gap-2 rounded-[14px] border-0 bg-[#006855] text-[13px] font-[750] text-white transition-colors hover:bg-[#005746] disabled:cursor-not-allowed disabled:opacity-48"
            :disabled="!canLookup"
            data-testid="lookup-submit"
          >
            <RefreshCw
              v-if="lookupLoading"
              :size="19"
              class="animate-spin"
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
        class="flex items-start gap-[9px] rounded-[15px] border border-[#efcdca] bg-[#fff8f7] p-[13px] text-[#c42c28]"
        role="alert"
        data-testid="lookup-error"
      >
        <CircleAlert
          :size="22"
          class="shrink-0"
        />

        <p
          class="m-0 text-[11px] leading-[1.55]"
        >
          {{ lookupError }}
        </p>
      </article>

      <article
        v-if="
          result
          &&
          participant
        "
        class="grid min-w-0 gap-[15px] rounded-[22px] border border-[#dce9e4] bg-white p-[18px] shadow-[0_12px_28px_rgb(30_65_55_/_6%)] lg:p-[22px]"
        data-testid="lookup-result"
      >
        <header
          class="flex items-center justify-between gap-2"
        >
          <div
            class="inline-flex items-center gap-[6px] rounded-full px-[9px] py-[5px] text-[10px] font-[750]"
            :class="
              result
                .scope
                .outside_assignment
                ? 'bg-[#fff1dc] text-[#b86100]'
                : 'bg-[#e5f6f0] text-[#006855]'
            "
          >
            <BadgeCheck :size="18" />

            {{
              result
                .scope
                .label
            }}
          </div>
        </header>

        <div
          class="flex min-w-0 items-center gap-[11px]"
        >
          <div
            class="grid size-[49px] shrink-0 place-items-center rounded-2xl bg-[#e8f5f0] text-xs font-extrabold text-[#006855]"
          >
            {{
              participantInitials(
                participant,
              )
            }}
          </div>

          <div
            class="flex min-w-0 flex-1 flex-col overflow-hidden"
          >
            <strong
              class="overflow-hidden text-base font-bold text-ellipsis whitespace-nowrap text-[#244b43]"
              :title="
                participant
                  .kpm
                  .full_name
              "
            >
              {{
                participant
                  .kpm
                  .full_name
              }}
            </strong>

            <span
              class="text-[11px] text-[#7b8c86]"
            >
              NIK
              {{
                participant
                  .kpm
                  .nik
              }}
            </span>
          </div>
        </div>

        <dl class="m-0 grid gap-[9px]">
          <div
            class="rounded-[13px] bg-[#f8fbfa] p-[11px]"
          >
            <dt
              class="text-[10px] text-[#7a8c86]"
            >
              Wilayah Asli KPM
            </dt>

            <dd
              class="mt-[3px] mb-0 text-xs leading-[1.45] text-[#35594f]"
            >
              {{ participantKelurahan }},
              {{ participantKecamatan }}
            </dd>
          </div>

          <div
            class="rounded-[13px] bg-[#f8fbfa] p-[11px]"
          >
            <dt
              class="text-[10px] text-[#7a8c86]"
            >
              Alamat
            </dt>

            <dd
              class="mt-[3px] mb-0 break-words text-xs leading-[1.45] text-[#35594f]"
            >
              {{
                participantAddress(
                  participant,
                )
              }}
            </dd>
          </div>
        </dl>

        <div
          class="flex items-center justify-between gap-3 rounded-[14px] bg-[#173f37] p-[13px] text-white"
        >
          <span class="text-[11px]">
            Saldo
          </span>

          <strong
            class="text-[17px]"
          >
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
          class="flex items-start gap-[9px] rounded-[15px] bg-[#fff6e8] p-[13px] text-[#9c5600]"
          data-testid="outside-assignment-notice"
        >
          <MapPin
            :size="20"
            class="shrink-0"
          />

          <p
            class="m-0 text-[11px] leading-[1.55]"
          >
            KPM berasal dari

            <strong>
              {{ participantKelurahan }}
            </strong>,
          </p>
        </div>

        <article
          v-if="transaction"
          class="flex items-center gap-[11px] rounded-[15px] border border-[#b9dfd2] bg-[#eaf8f3] p-[14px] text-[#006855]"
          data-testid="transaction-success"
        >
          <CircleCheck
            :size="28"
            class="shrink-0"
          />

          <div class="flex min-w-0 flex-col">
            <strong>
              Sudah Bertransaksi
            </strong>

            <span
              class="overflow-hidden text-[11px] text-ellipsis whitespace-nowrap text-[#52736a]"
            >
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
          class="flex items-center gap-[11px] rounded-[15px] border border-[#b9dfd2] bg-[#eaf8f3] p-[14px] text-[#006855]"
          data-testid="participant-final-status"
        >
          <CircleCheck
            :size="28"
            class="shrink-0"
          />

          <div class="flex min-w-0 flex-col">
            <strong>
              {{
                participantActivity
                  .label
              }}
            </strong>
          </div>
        </article>

        <template v-else>
          <button
            type="button"
            class="flex min-h-12 w-full items-center justify-center gap-2 rounded-[14px] border-0 bg-[#c12723] text-[13px] font-[750] text-white transition-colors hover:bg-[#a9201d] disabled:cursor-not-allowed disabled:opacity-48"
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
            class="m-0 text-center text-[11px] leading-[1.5] text-[#a85a00]"
          >
            Pilih E-Warung tempat bertugas
            melalui Beranda sebelum mencatat
            transaksi.
          </p>
        </template>

        <p
          v-if="transactionError"
          class="m-0 text-center text-[11px] leading-[1.5] text-[#c42c28]"
          role="alert"
          data-testid="transaction-error"
        >
          {{ transactionError }}
        </p>

        <button
          type="button"
          class="min-h-10 rounded-xl border border-[#d6e3de] bg-white font-bold text-[#45655c] transition-colors hover:bg-[#f4f8f6]"
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
        class="fixed inset-0 z-[80] grid items-end justify-items-center bg-[rgb(16_40_34_/_55%)] p-[18px] min-[560px]:items-center"
        data-testid="transaction-confirmation"
      >
        <article
          class="relative w-[min(100%,520px)] rounded-3xl bg-white px-[19px] pt-6 pb-[19px] shadow-[0_22px_50px_rgb(12_36_29_/_28%)]"
          role="dialog"
          aria-modal="true"
          aria-labelledby="transaction-confirmation-title"
        >
          <button
            type="button"
            class="absolute top-[13px] right-[13px] grid size-[38px] place-items-center rounded-xl border-0 bg-[#f1f5f3] text-[#526a62] transition-colors hover:bg-[#e2eae6] disabled:cursor-not-allowed disabled:opacity-50"
            aria-label="Tutup konfirmasi"
            :disabled="transactionLoading"
            @click="
              confirmationOpen
                = false
            "
          >
            <X :size="20" />
          </button>

          <div
            class="grid size-[53px] place-items-center rounded-[17px] bg-[#e8f5f0] text-[#006855]"
          >
            <Store :size="27" />
          </div>

          <h2
            id="transaction-confirmation-title"
            class="mt-[15px] mb-[6px] break-words text-xl font-bold text-[#173f37]"
          >
            Konfirmasi transaksi
          </h2>

          <p
            class="m-0 text-[13px] leading-[1.6] text-[#5e746d]"
          >
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

          <small
            class="mt-[9px] block text-[10px] leading-[1.5] text-[#a65a00]"
          >
            Transaksi hanya bisa di catat sekali. Pastikan KPM sudah benar-benar bertransaksi sebelum menekan tombol konfirmasi.
          </small>

          <div
            class="mt-[18px] grid grid-cols-[0.75fr_1.25fr] gap-[9px]"
          >
            <button
              type="button"
              class="min-h-[47px] rounded-[14px] border border-[#d6e3de] bg-white text-xs font-[750] text-[#526a62] transition-colors hover:bg-[#f4f8f6] disabled:cursor-not-allowed disabled:opacity-48"
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
              class="flex min-h-[47px] items-center justify-center gap-2 rounded-[14px] border-0 bg-[#006855] text-xs font-[750] text-white transition-colors hover:bg-[#005746] disabled:cursor-not-allowed disabled:opacity-48"
              :disabled="transactionLoading"
              data-testid="confirm-transaction"
              @click="confirmTransaction"
            >
              <RefreshCw
                v-if="transactionLoading"
                :size="18"
                class="animate-spin"
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