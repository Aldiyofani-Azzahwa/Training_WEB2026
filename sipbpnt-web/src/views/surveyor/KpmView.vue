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
  KpmVerificationStatus,
  SurveyorParticipant,
  SurveyorParticipantActivity,
  SurveyorParticipantPagination,
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

const PER_PAGE =
  15

const SEARCH_DELAY =
  350

const verificationOptions = [
  {
    value:
      'deceased',
    label:
      'Meninggal',
  },
  {
    value:
      'moved_domicile',
    label:
      'Pindah Domisili',
  },
  {
    value:
      'not_claimed',
    label:
      'Tidak Mengambil',
  },
] as const satisfies ReadonlyArray<{
  value: KpmVerificationStatus
  label: string
}>

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

const verificationParticipant =
  ref<SurveyorParticipant | null>(
    null,
  )

const verificationStatus =
  ref<KpmVerificationStatus | ''>(
    '',
  )

const verificationReason =
  ref('')

const verificationLoading =
  ref(false)

const verificationError =
  ref('')

const {
  selectedEWarung,
  synchronizeEWarungs,
} =
  useSurveyorEWarungSelection()

const requiresVerificationReason =
  computed(() => {
    return (
      verificationStatus.value
      ===
      'not_claimed'
    )
  })

const canSubmitVerification =
  computed(() => {
    if (
      !verificationParticipant.value
      ||
      !verificationStatus.value
      ||
      verificationLoading.value
    ) {
      return false
    }

    if (
      requiresVerificationReason.value
    ) {
      return (
        verificationReason.value
          .trim()
          .length
        >
        0
      )
    }

    return true
  })

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
      ? `KPM KELURAHAN ${kelurahan}`
      : 'KPM KELURAHAN'
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
  const surveyorId =
    context.value
      ?.surveyor
      .id

  if (
    surveyorId === undefined
  ) {
    return
  }

  const activeEWarungs =
    await surveyorWorkspaceService
      .getActiveEWarungs()

  synchronizeEWarungs(
    surveyorId,
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

function openVerificationConfirmation(
  participant: SurveyorParticipant,
): void {
  transactionError.value =
    ''

  transactionSuccess.value =
    ''

  verificationError.value =
    ''

  if (
    activityOf(
      participant,
    ).code
    !==
    'pending'
  ) {
    return
  }

  verificationParticipant.value =
    participant

  verificationStatus.value =
    ''

  verificationReason.value =
    ''
}

function closeVerificationConfirmation():
  void {
  if (
    verificationLoading.value
  ) {
    return
  }

  verificationParticipant.value =
    null

  verificationStatus.value =
    ''

  verificationReason.value =
    ''

  verificationError.value =
    ''
}

function selectVerificationStatus(
  status: KpmVerificationStatus,
): void {
  verificationStatus.value =
    status

  if (
    status !== 'not_claimed'
  ) {
    verificationReason.value =
      ''
  }

  verificationError.value =
    ''
}

function markParticipantAsVerified(
  participantId: number,
  status: KpmVerificationStatus,
  label: string,
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
              status,

            label,

            is_final:
              true,

            can_record_transaction:
              false,
          },
        }
      },
    )
}

async function confirmVerification():
  Promise<void> {
  const participant =
    verificationParticipant.value

  const status =
    verificationStatus.value

  if (
    !participant
    ||
    !status
    ||
    !canSubmitVerification.value
  ) {
    return
  }

  verificationLoading.value =
    true

  verificationError.value =
    ''

  transactionSuccess.value =
    ''

  try {
    const verification =
      await surveyorWorkspaceService
        .storeVerification({
          bpnt_participant_id:
            participant.id,

          status,

          ...(status === 'not_claimed'
            ? {
                reason:
                  verificationReason.value
                    .trim(),
              }
            : {}),
        })

    markParticipantAsVerified(
      participant.id,
      verification.status.code,
      verification.status.label,
    )

    verificationParticipant.value =
      null

    verificationStatus.value =
      ''

    verificationReason.value =
      ''

    transactionSuccess.value =
      `${participant.kpm.full_name} berhasil diperbarui menjadi ${verification.status.label}.`
  } catch (
    error: unknown
  ) {
    verificationError.value =
      resolveErrorMessage(
        error,
        'Status KPM belum dapat disimpan.',
      )
  } finally {
    verificationLoading.value =
      false
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
  <section
    class="grid w-full min-w-0 gap-[17px] lg:gap-5"
  >
    <div
      v-if="workspaceLoading"
      class="grid gap-3"
      data-testid="kpm-loading"
      aria-live="polite"
    >
      <div
        class="h-[27px] w-[62%] animate-pulse rounded-xl bg-[#efeae7]"
      />

      <div
        class="h-[14px] w-[43%] animate-pulse rounded-xl bg-[#efeae7]"
      />

      <div
        class="mt-[5px] h-[52px] w-full animate-pulse rounded-[17px] bg-[#efeae7]"
      />

      <div
        class="h-[196px] w-full animate-pulse rounded-[20px] bg-[#efeae7]"
      />

      <div
        class="h-[196px] w-full animate-pulse rounded-[20px] bg-[#efeae7]"
      />

      <span class="sr-only">
        Memuat data KPM
      </span>
    </div>

    <article
      v-else-if="workspaceError"
      class="grid justify-items-center rounded-[22px] border border-[#efcdca] bg-white px-[21px] py-[31px] text-center"
      role="alert"
      data-testid="kpm-workspace-error"
    >
      <div
        class="mb-[13px] grid size-[58px] place-items-center rounded-[19px] bg-[#fff0ef] text-[#cd2b27]"
      >
        <CircleAlert
          :size="29"
          :stroke-width="1.9"
        />
      </div>

      <strong class="text-base font-bold text-[#4b3424]">
        Data KPM belum dapat dimuat
      </strong>

      <p
        class="mt-[7px] mb-0 max-w-80 text-[13px] leading-[1.6] text-[#897e77]"
      >
        {{ workspaceError }}
      </p>

      <button
        type="button"
        class="mt-[17px] inline-flex min-h-[45px] items-center justify-center gap-2 rounded-[14px] border-0 bg-[#682b00] px-[17px] text-[13px] font-bold text-white transition-colors hover:bg-[#572400]"
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
      class="grid justify-items-center rounded-[22px] border border-[#ecd9b8] bg-white px-[21px] py-[31px] text-center"
      data-testid="kpm-no-period"
    >
      <div
        class="mb-[13px] grid size-[58px] place-items-center rounded-[19px] bg-[#fff3de] text-[#bc6800]"
      >
        <UsersRound
          :size="30"
          :stroke-width="1.8"
        />
      </div>

      <strong class="text-base font-bold text-[#4b3424]">
        Belum ada periode aktif
      </strong>

      <p
        class="mt-[7px] mb-0 max-w-80 text-[13px] leading-[1.6] text-[#897e77]"
      >
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
      class="grid justify-items-center rounded-[22px] border border-[#ecd9b8] bg-white px-[21px] py-[31px] text-center"
      data-testid="kpm-no-assignment"
    >
      <div
        class="mb-[13px] grid size-[58px] place-items-center rounded-[19px] bg-[#fff3de] text-[#bc6800]"
      >
        <MapPin
          :size="30"
          :stroke-width="1.8"
        />
      </div>

      <strong class="text-base font-bold text-[#4b3424]">
        Anda belum memiliki wilayah tugas
      </strong>

      <p
        class="mt-[7px] mb-0 max-w-80 text-[13px] leading-[1.6] text-[#897e77]"
      >
        Hubungi Manager BPNT agar Kelurahan
        assignment dapat ditetapkan.
      </p>
    </article>

    <template v-else-if="context">
      <header
        class="flex items-start justify-between gap-[14px]"
      >
        <div class="min-w-0">
          <span
            class="text-xs font-[750] tracking-[0.08em] text-[#c12723] uppercase"
          >
            Data KPM
          </span>

          <h1
            class="my-1 break-words text-[27px] leading-[1.22] font-bold text-[#3f2817] lg:text-[34px]"
            data-testid="kpm-page-title"
          >
            {{ pageTitle }}
          </h1>

          <p class="m-0 text-[13px] text-[#847a72]">
            {{ totalParticipants.toLocaleString('id-ID') }}
            KPM pada wilayah tugas Anda.
          </p>
        </div>

        <div
          class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#f5ede8] text-[#682b00]"
        >
          <UsersRound
            :size="25"
            :stroke-width="1.9"
          />
        </div>
      </header>

      <article
        class="flex w-full min-w-0 items-center gap-[11px] overflow-hidden rounded-2xl border px-[14px] py-[13px]"
        :class="
          selectedEWarungValue
            ? 'border-[#e4d8cf] bg-[#faf5f2] text-[#682b00]'
            : 'border-[#ecd9b8] bg-[#fff9ed] text-[#bc6800]'
        "
        data-testid="kpm-selected-e-warung"
      >
        <Store
          :size="21"
          :stroke-width="1.9"
          class="shrink-0"
        />

        <div
          class="flex min-w-0 flex-1 flex-col overflow-hidden"
        >
          <span class="text-[10px] text-[#81766f]">
            E-Warung transaksi
          </span>

          <strong
            class="block max-w-full overflow-hidden text-[13px] font-bold text-ellipsis whitespace-nowrap text-[#4b3424]"
            :title="
              selectedEWarungValue
                ?.name
              ??
              'Belum dipilih pada Beranda'
            "
          >
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
        class="flex min-h-[52px] w-full min-w-0 items-center gap-[10px] rounded-[17px] border border-[#e6ded9] bg-white px-[13px] shadow-[0_8px_22px_rgb(30_65_55_/_5%)] transition-[border-color,box-shadow] focus-within:border-[#a27758] focus-within:shadow-[0_0_0_3px_rgb(0_104_85_/_9%)]"
        role="search"
        @submit.prevent="submitSearch"
      >
        <Search
          :size="20"
          :stroke-width="1.9"
          class="shrink-0 text-[#8a7e75]"
        />

        <input
          v-model="searchTerm"
          type="search"
          name="search"
          class="min-w-0 flex-1 border-0 bg-transparent text-base text-[#4b3424] outline-none placeholder:text-[#a49c96] lg:text-sm"
          :placeholder="searchPlaceholder"
          aria-label="Cari nama atau NIK KPM dalam wilayah tugas"
          autocomplete="off"
          enterkeyhint="search"
          data-testid="kpm-search"
        />

        <button
          v-if="hasSearch"
          type="button"
          class="grid size-[34px] shrink-0 place-items-center rounded-[11px] border-0 bg-[#f5f3f1] p-0 text-[#7f746c] transition-colors hover:bg-[#ece7e4] hover:text-[#594435]"
          aria-label="Hapus pencarian"
          @click="clearSearch"
        >
          <X
            :size="18"
            :stroke-width="2"
          />
        </button>
      </form>

      <p
        class="-mt-[7px] mx-0 mb-0 text-[11px] leading-[1.5] text-[#8a8078]"
      >
        Pencarian pada halaman ini hanya mencakup
        KPM di Kelurahan

        <strong class="text-[#665345]">
          {{ context.assignment?.kelurahan.name }}
        </strong>.
      </p>

      <article
        v-if="transactionSuccess"
        class="flex items-start gap-[10px] rounded-[15px] border border-[#e1cdbf] bg-[#faf4ef] px-[14px] py-[13px] text-[#743508]"
        role="status"
        data-testid="kpm-transaction-success"
      >
        <CheckCircle2
          :size="22"
          :stroke-width="2"
          class="shrink-0"
        />

        <p class="m-0 text-xs leading-[1.5]">
          {{ transactionSuccess }}
        </p>
      </article>

      <article
        v-if="transactionError"
        class="flex items-start gap-[10px] rounded-[15px] border border-[#efcdca] bg-[#fff8f7] px-[14px] py-[13px] text-[#a3312d]"
        role="alert"
        data-testid="kpm-transaction-error"
      >
        <CircleAlert
          :size="22"
          :stroke-width="1.9"
          class="shrink-0"
        />

        <p class="m-0 text-xs leading-[1.5]">
          {{ transactionError }}
        </p>
      </article>

      <div
        v-if="participantsLoading"
        class="grid gap-3"
        data-testid="participants-loading"
      >
        <div
          v-for="number in 3"
          :key="number"
          class="h-[196px] w-full animate-pulse rounded-[20px] bg-[#efeae7]"
        />
      </div>

      <article
        v-else-if="participantsError"
        class="grid grid-cols-[auto_minmax(0,1fr)] items-start gap-[11px] rounded-[17px] border border-[#efcdca] bg-[#fff8f7] p-[15px] text-[#c42c28]"
        role="alert"
        data-testid="participants-error"
      >
        <CircleAlert
          :size="23"
          :stroke-width="1.9"
        />

        <div class="min-w-0">
          <strong class="block text-[13px] text-[#8f2724]">
            Daftar KPM gagal dimuat
          </strong>

          <p
            class="mt-[3px] mb-0 text-[11px] leading-[1.5] text-[#9a5a56]"
          >
            {{ participantsError }}
          </p>
        </div>

        <button
          type="button"
          class="col-start-2 w-max rounded-[9px] border-0 bg-[#c42c28] px-[11px] py-[6px] text-[11px] font-bold text-white transition-colors hover:bg-[#aa2522]"
          @click="loadParticipants(true)"
        >
          Coba Lagi
        </button>
      </article>

      <article
        v-else-if="
          participants.length === 0
        "
        class="grid justify-items-center rounded-[22px] border border-dashed border-[#ddd3cc] bg-white px-[21px] py-[31px] text-center"
        data-testid="participants-empty"
      >
        <div
          class="mb-[13px] grid size-[58px] place-items-center rounded-[19px] bg-[#f5efea] text-[#682b00]"
        >
          <UserRound
            :size="29"
            :stroke-width="1.8"
          />
        </div>

        <strong class="text-base font-bold text-[#4b3424]">
          {{
            hasSearch
              ? 'KPM tidak ditemukan'
              : 'Belum ada KPM di wilayah ini'
          }}
        </strong>

        <p
          class="mt-[7px] mb-0 max-w-80 text-[13px] leading-[1.6] text-[#897e77]"
        >
          {{
            hasSearch
              ? 'Periksa kembali nama atau NIK yang dimasukkan.'
              : 'Data KPM akan tampil setelah BNBA tersedia pada periode aktif.'
          }}
        </p>
      </article>

      <div
        v-else
        class="grid gap-3"
        data-testid="participant-list"
      >
        <article
          v-for="participant in participants"
          :key="participant.id"
          class="w-full min-w-0 overflow-hidden rounded-[20px] border border-[#e9e3df] bg-white shadow-[0_9px_25px_rgb(30_65_55_/_5%)]"
          data-testid="participant-card"
        >
          <header
            class="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-3 p-4"
          >
            <div
              class="row-span-2 grid size-[45px] place-items-center rounded-[15px] bg-[#f5ede8] text-xs font-extrabold text-[#682b00]"
            >
              {{ participantInitials(participant) }}
            </div>

            <div class="flex min-w-0 flex-col">
              <strong
                class="overflow-hidden text-[15px] leading-[1.4] font-bold text-ellipsis whitespace-nowrap text-[#4b3424]"
                :title="participant.kpm.full_name"
              >
                {{ participant.kpm.full_name }}
              </strong>

              <span class="mt-0.5 text-[11px] text-[#8c827b]">
                NIK {{ participant.kpm.nik }}
              </span>
            </div>

            <span
              class="w-max max-w-full rounded-full px-[9px] py-[5px] text-[10px] font-[750]"
              :class="
                activityOf(participant).code
                  === 'transacted'
                  ? 'bg-[#f5ece5] text-[#743508]'
                  : isVerifiedActivity(participant)
                    ? 'bg-[#edf1f7] text-[#53637a]'
                    : 'bg-[#fff3de] text-[#a85c00]'
              "
              data-testid="kpm-activity-badge"
            >
              {{ activityOf(participant).label }}
            </span>
          </header>

          <div
            class="mx-4 flex min-w-0 items-start gap-[10px] rounded-[14px] bg-[#faf9f8] p-3 text-[#806f63]"
          >
            <MapPin
              :size="18"
              :stroke-width="1.8"
              class="mt-0.5 shrink-0"
            />

            <div class="min-w-0">
              <strong class="text-xs text-[#665345]">
                {{
                  participant
                    .wilayah
                    .kelurahan
                    .name
                  ??
                  '-'
                }}
              </strong>

              <p
                class="mt-0.5 mb-0 overflow-hidden text-[11px] leading-[1.5] text-[#8d837b] line-clamp-2"
              >
                {{ participantAddress(participant) }}
              </p>
            </div>
          </div>

          <footer
            class="mt-[14px] flex items-center justify-between gap-3 border-t border-[#f1efed] bg-[#fcfdfc] px-4 py-[13px] max-[430px]:flex-col max-[430px]:items-stretch"
          >
            <div class="flex flex-col">
              <span class="text-[11px] font-[650] text-[#877c74]">
                Saldo BPNT
              </span>

              <strong class="text-[15px] text-[#682b00]">
                {{ formatCurrency(participant.saldo_bpnt) }}
              </strong>
            </div>

            <div
              v-if="
                activityOf(participant)
                  .can_record_transaction
              "
              class="flex flex-wrap justify-end gap-2 max-[520px]:mt-3 max-[520px]:grid max-[520px]:w-full max-[520px]:grid-cols-2"
            >
              <button
                type="button"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#682b00] px-4 py-2.5 text-xs font-bold text-white transition hover:bg-[#562400] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="
                  !selectedEWarungValue
                  ||
                  transactionLoadingId !== null
                  ||
                  verificationLoading
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

              <button
                type="button"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-[#d7a33c] bg-[#fff9ed] px-4 py-2.5 text-xs font-bold text-[#995b00] transition hover:bg-[#fff2d5] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="
                  transactionLoadingId !== null
                  ||
                  verificationLoading
                "
                data-testid="kpm-verification-button"
                @click="
                  openVerificationConfirmation(
                    participant,
                  )
                "
              >
                <CircleAlert
                  :size="17"
                  :stroke-width="2"
                />

                Update Status
              </button>
            </div>

            <div
              v-else
              class="inline-flex items-center gap-[6px] text-[11px] font-[750] text-[#743508]"
              data-testid="kpm-final-indicator"
            >
              <CheckCircle2
                :size="18"
                :stroke-width="2"
              />

              Selesai
            </div>
          </footer>

          <p
            v-if="
              activityOf(participant)
                .can_record_transaction
              &&
              !selectedEWarungValue
            "
            class="m-0 px-4 pb-[14px] text-[10px] leading-[1.5] text-[#9a6b2e]"
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
        class="inline-flex min-h-[45px] items-center justify-center gap-2 rounded-[14px] border-0 bg-[#682b00] px-[17px] text-[13px] font-bold text-white transition-colors hover:bg-[#572400] disabled:cursor-not-allowed disabled:opacity-65"
        :disabled="loadingMore"
        data-testid="load-more"
        @click="loadMore"
      >
        <RefreshCw
          :size="18"
          :stroke-width="2"
          :class="{
            'animate-spin':
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
        class="-mt-[5px] mb-0 text-center text-[11px] text-[#918780]"
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
      class="fixed inset-0 z-[80] grid items-end bg-[rgb(16_40_34_/_48%)] p-[18px] min-[560px]:items-center"
      data-testid="kpm-transaction-confirmation"
      @click.self="closeTransactionConfirmation"
    >
      <article
        class="mx-auto mt-0 mb-[max(70px,env(safe-area-inset-bottom))] w-[min(100%,430px)] rounded-3xl bg-white p-[23px] text-center shadow-[0_24px_70px_rgb(0_32_25_/_24%)] min-[560px]:mb-0"
        role="dialog"
        aria-modal="true"
        aria-labelledby="transaction-confirmation-title"
      >
        <div
          class="mx-auto mb-[13px] grid size-[58px] place-items-center rounded-[19px] bg-[#f5ede8] text-[#682b00]"
        >
          <ClipboardCheck
            :size="29"
            :stroke-width="1.9"
          />
        </div>

        <span
          class="text-[11px] font-[750] tracking-[0.06em] text-[#c12723] uppercase"
        >
          Konfirmasi transaksi
        </span>

        <h2
          id="transaction-confirmation-title"
          class="my-[5px] break-words text-xl font-bold text-[#4b3424]"
        >
          {{ confirmationParticipant.kpm.full_name }}
        </h2>

        <p class="m-0 text-[13px] leading-[1.6] text-[#837871]">
          Tandai KPM ini sudah bertransaksi di

          <strong class="text-[#4b3424]">
            {{ selectedEWarungValue?.name }}
          </strong>?
        </p>

        <div
          class="mt-5 grid grid-cols-[1fr_1.5fr] gap-[9px]"
        >
          <button
            type="button"
            class="min-h-[44px] rounded-[13px] border border-[#e3ddd8] bg-white font-bold text-[#6c5f56] transition-colors hover:bg-[#f8f6f5] disabled:cursor-not-allowed disabled:opacity-[0.55]"
            :disabled="
              transactionLoadingId !== null
            "
            @click="closeTransactionConfirmation"
          >
            Batal
          </button>

          <button
            type="button"
            class="inline-flex min-h-[44px] items-center justify-center gap-[7px] rounded-[13px] border-0 bg-[#682b00] px-[13px] text-[11px] font-[750] text-white transition-colors hover:bg-[#572400] disabled:cursor-not-allowed disabled:opacity-[0.55]"
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
              class="animate-spin"
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

    <div
      v-if="verificationParticipant"
      class="fixed inset-0 z-[80] grid place-items-end bg-[rgb(16_40_34_/_55%)] p-0 backdrop-blur-sm sm:place-items-center sm:p-5"
      data-testid="kpm-verification-confirmation"
      @click.self="closeVerificationConfirmation"
    >
      <article
        class="w-full rounded-t-[24px] bg-white p-5 shadow-2xl sm:max-w-lg sm:rounded-[24px] sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="verification-confirmation-title"
      >
        <header class="mb-5 flex items-start justify-between gap-4">
          <div>
            <span
              class="text-[11px] font-bold tracking-[0.08em] text-[#c12723] uppercase"
            >
              Verifikasi KPM
            </span>

            <h2
              id="verification-confirmation-title"
              class="mt-1 text-xl font-bold text-[#3f2817]"
            >
              {{ verificationParticipant.kpm.full_name }}
            </h2>

            <p class="mt-1 text-sm text-[#847a72]">
              Data yang sudah di input tidak dapat diubah kembali. Pastikan status KPM sudah benar sebelum menyimpan.
            </p>
          </div>

          <button
            type="button"
            class="grid size-10 shrink-0 place-items-center rounded-xl border border-[#e8e1dc] text-[#7b6f66] transition hover:bg-[#f7f4f2] disabled:opacity-50"
            :disabled="verificationLoading"
            aria-label="Tutup"
            @click="closeVerificationConfirmation"
          >
            <X
              :size="20"
              :stroke-width="2"
            />
          </button>
        </header>

        <fieldset class="grid gap-2.5">
          <legend class="mb-2 text-sm font-bold text-[#4b3424]">
            Pilih status KPM
          </legend>

          <label
            v-for="option in verificationOptions"
            :key="option.value"
            class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition"
            :class="
              verificationStatus === option.value
                ? 'border-[#682b00] bg-[#f8f2ed] text-[#682b00]'
                : 'border-[#e8e1dc] bg-white text-[#65584f] hover:bg-[#faf8f7]'
            "
          >
            <input
              type="radio"
              name="verification-status"
              class="size-4 accent-[#682b00]"
              :value="option.value"
              :checked="
                verificationStatus
                ===
                option.value
              "
              @change="
                selectVerificationStatus(
                  option.value,
                )
              "
            >

            <span class="text-sm font-bold">
              {{ option.label }}
            </span>
          </label>
        </fieldset>

        <div
          v-if="requiresVerificationReason"
          class="mt-4"
        >
          <label
            for="verification-reason"
            class="mb-2 block text-sm font-bold text-[#4b3424]"
          >
            Alasan tidak mengambil
          </label>

          <textarea
            id="verification-reason"
            v-model="verificationReason"
            rows="4"
            maxlength="500"
            class="w-full resize-none rounded-xl border border-[#e6ded9] bg-white px-3.5 py-3 text-sm text-[#4b3424] outline-none transition placeholder:text-[#a79e98] focus:border-[#a27758] focus:ring-4 focus:ring-[#682b00]/10"
            placeholder="Tuliskan alasan KPM tidak mengambil bantuan"
            data-testid="kpm-verification-reason"
          />

          <p class="mt-1 text-right text-xs text-[#938a83]">
            {{ verificationReason.length }}/500
          </p>
        </div>

        <article
          v-if="verificationError"
          class="mt-4 flex items-start gap-2 rounded-xl border border-[#f0c6c3] bg-[#fff4f3] p-3 text-sm text-[#a52723]"
          role="alert"
          data-testid="kpm-verification-error"
        >
          <CircleAlert
            :size="19"
            :stroke-width="2"
            class="mt-0.5 shrink-0"
          />

          <p>
            {{ verificationError }}
          </p>
        </article>

        <div class="mt-6 grid grid-cols-2 gap-3">
          <button
            type="button"
            class="min-h-11 rounded-xl border border-[#e8e1dc] bg-white px-4 text-sm font-bold text-[#6d6057] transition hover:bg-[#f8f6f5] disabled:opacity-50"
            :disabled="verificationLoading"
            @click="closeVerificationConfirmation"
          >
            Batal
          </button>

          <button
            type="button"
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#682b00] px-4 text-sm font-bold text-white transition hover:bg-[#562400] disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!canSubmitVerification"
            data-testid="kpm-confirm-verification"
            @click="confirmVerification"
          >
            <LoaderCircle
              v-if="verificationLoading"
              :size="18"
              class="animate-spin"
            />

            {{
              verificationLoading
                ? 'Menyimpan...'
                : 'Simpan'
            }}
          </button>
        </div>
      </article>
    </div>
  </section>
</template>