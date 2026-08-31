<script setup lang="ts">
import axios from 'axios'

import {
  CalendarCheck2,
  CheckCircle2,
  ClipboardList,
  MapPin,
  RefreshCw,
  Trash2,
  UserCheck,
  Users,
  XCircle,
} from '@lucide/vue'

import {
  computed,
  onMounted,
  ref,
  watch,
} from 'vue'

import {
  bnbaService,
} from '@/services/bnbaService'

import {
  surveyorAssignmentService,
} from '@/services/surveyorAssignmentService'

import {
  surveyorService,
} from '@/services/surveyorService'

import {
  wilayahService,
} from '@/services/wilayahService'

import type {
  BpntPeriod,
} from '@/types/bnba'

import type {
  SurveyorOption,
} from '@/types/surveyor'

import type {
  SurveyorAssignment,
  SurveyorAssignmentMeta,
} from '@/types/surveyorAssignment'

import type {
  Kecamatan,
} from '@/types/wilayah'

interface FlatKelurahan {
  id: number
  code: string
  name: string
  kecamatanId: number
  kecamatanCode: string
  kecamatanName: string
}

type ValidationErrors =
  Record<string, string[]>

const activePeriod =
  ref<BpntPeriod | null>(
    null,
  )

const surveyors =
  ref<SurveyorOption[]>([])

const wilayah =
  ref<Kecamatan[]>([])

const assignments =
  ref<SurveyorAssignment[]>([])

const meta =
  ref<
    SurveyorAssignmentMeta
    | null
  >(null)

const periodKelurahanNames =
  ref<string[]>([])

const selectedKecamatanId =
  ref<number | null>(
    null,
  )

const selectedKelurahanId =
  ref<number | null>(
    null,
  )

const selectedSurveyorId =
  ref<number | null>(
    null,
  )

const loadingInitial =
  ref(false)

const loadingPeriod =
  ref(false)

const saving =
  ref(false)

const deletingId =
  ref<number | null>(
    null,
  )

const errorMessage =
  ref('')

const successMessage =
  ref('')

const validationErrors =
  ref<ValidationErrors>({})

const maxSurveyorsPerKelurahan =
  computed(() =>
    meta.value
      ?.max_surveyors_per_kelurahan
    ?? 3,
  )

const allowedKelurahanNames =
  computed(() =>
    new Set(
      periodKelurahanNames
        .value
        .map(
          (
            name,
          ) =>
            normalizeName(
              name,
            ),
        ),
    ),
  )

const flatKelurahans =
  computed<
    FlatKelurahan[]
  >(() =>
    wilayah.value
      .flatMap(
        (
          kecamatan,
        ) =>
          kecamatan
            .kelurahans
            .map(
              (
                kelurahan,
              ) => ({
                id:
                  kelurahan.id,

                code:
                  kelurahan.code,

                name:
                  kelurahan.name,

                kecamatanId:
                  kecamatan.id,

                kecamatanCode:
                  kecamatan.code,

                kecamatanName:
                  kecamatan.name,
              }),
            ),
      ),
  )

const periodKelurahans =
  computed(() =>
    flatKelurahans
      .value
      .filter(
        (
          kelurahan,
        ) =>
          allowedKelurahanNames
            .value
            .has(
              normalizeName(
                kelurahan.name,
              ),
            ),
      ),
  )

const availableKecamatans =
  computed(() =>
    wilayah.value
      .filter(
        (
          kecamatan,
        ) =>
          periodKelurahans
            .value
            .some(
              (
                kelurahan,
              ) =>
                kelurahan
                  .kecamatanId
                ===
                kecamatan.id,
            ),
      ),
  )

const filteredKelurahans =
  computed(() => {
    if (
      selectedKecamatanId
        .value
      === null
    ) {
      return []
    }

    return periodKelurahans
      .value
      .filter(
        (
          kelurahan,
        ) =>
          kelurahan
            .kecamatanId
          ===
          selectedKecamatanId
            .value,
      )
  })

const assignedSurveyorIds =
  computed(() =>
    new Set(
      assignments
        .value
        .map(
          (
            assignment,
          ) =>
            assignment
              .surveyor
              .id,
        ),
    ),
  )

const availableSurveyors =
  computed(() =>
    surveyors
      .value
      .filter(
        (
          surveyor,
        ) =>
          !assignedSurveyorIds
            .value
            .has(
              surveyor.id,
            ),
      ),
  )

const selectedKelurahan =
  computed(() =>
    periodKelurahans
      .value
      .find(
        (
          kelurahan,
        ) =>
          kelurahan.id
          ===
          selectedKelurahanId
            .value,
      )
    ?? null,
  )

const selectedKelurahanAssignmentCount =
  computed(() => {
    if (
      selectedKelurahanId
        .value
      === null
    ) {
      return 0
    }

    return assignmentCountForKelurahan(
      selectedKelurahanId
        .value,
    )
  })

const selectedKelurahanIsFull =
  computed(() =>
    selectedKelurahanAssignmentCount
      .value
    >=
    maxSurveyorsPerKelurahan
      .value,
  )

const canSave =
  computed(() =>
    activePeriod.value
      !== null
    &&
    selectedKecamatanId.value
      !== null
    &&
    selectedKelurahanId.value
      !== null
    &&
    selectedSurveyorId.value
      !== null
    &&
    !selectedKelurahanIsFull.value
    &&
    !saving.value
    &&
    !loadingPeriod.value,
  )

function normalizeName(
  value: string,
): string {
  return value
    .trim()
    .toLocaleLowerCase(
      'id-ID',
    )
}

function assignmentCountForKelurahan(
  kelurahanId: number,
): number {
  return assignments
    .value
    .filter(
      (
        assignment,
      ) =>
        assignment
          .kelurahan
          .id
        ===
        kelurahanId,
    )
    .length
}

function resetMessages():
  void {
  errorMessage.value =
    ''

  successMessage.value =
    ''

  validationErrors.value =
    {}
}

function resetForm():
  void {
  selectedKecamatanId.value =
    null

  selectedKelurahanId.value =
    null

  selectedSurveyorId.value =
    null

  validationErrors.value =
    {}
}

function firstFieldError(
  field: string,
): string | null {
  return (
    validationErrors
      .value[
        field
      ]?.[0]
    ??
    null
  )
}

function normalizeError(
  error: unknown,
  fallback: string,
): string {
  if (
    axios.isAxiosError<{
      message?: string
      errors?: ValidationErrors
    }>(
      error,
    )
  ) {
    validationErrors.value =
      error.response
        ?.data
        ?.errors
      ??
      {}

    const firstError =
      Object.values(
        validationErrors
          .value,
      )[0]?.[0]

    return (
      firstError
      ??
      error.response
        ?.data
        ?.message
      ??
      fallback
    )
  }

  return fallback
}

function formatDate(
  value: string | null,
): string {
  if (!value) {
    return '-'
  }

  return new Intl
    .DateTimeFormat(
      'id-ID',
      {
        dateStyle:
          'medium',

        timeStyle:
          'short',

        timeZone:
          'Asia/Jakarta',
      },
    )
    .format(
      new Date(
        value,
      ),
    )
}

async function loadAssignmentData():
  Promise<void> {
  if (
    !activePeriod.value
  ) {
    assignments.value =
      []

    periodKelurahanNames
      .value = []

    meta.value =
      null

    resetForm()

    return
  }

  loadingPeriod.value =
    true

  resetForm()

  try {
    const [
      assignmentData,
      participantOptions,
    ] =
      await Promise.all([
        surveyorAssignmentService
          .getActive(),

        bnbaService
          .getParticipantFilterOptions(
            activePeriod
              .value
              .id,
          ),
      ])

    assignments.value =
      assignmentData.data

    meta.value =
      assignmentData.meta

    periodKelurahanNames
      .value =
        participantOptions
          .kelurahan
  } catch (
    error: unknown
  ) {
    assignments.value =
      []

    periodKelurahanNames
      .value = []

    meta.value =
      null

    errorMessage.value =
      normalizeError(
        error,
        'Data penugasan Surveyor gagal dimuat.',
      )
  } finally {
    loadingPeriod.value =
      false
  }
}

async function loadInitial():
  Promise<void> {
  loadingInitial.value =
    true

  resetMessages()

  try {
    const [
      periodData,
      surveyorData,
      wilayahData,
    ] =
      await Promise.all([
        bnbaService
          .getActivePeriod(),

        surveyorService
          .getActiveOptions(),

        wilayahService
          .getMaster(),
      ])

    activePeriod.value =
      periodData

    surveyors.value =
      surveyorData.data

    wilayah.value =
      wilayahData.data

    if (
      activePeriod.value
    ) {
      await loadAssignmentData()
    } else {
      assignments.value =
        []

      periodKelurahanNames
        .value = []

      meta.value =
        null

      resetForm()
    }
  } catch (
    error: unknown
  ) {
    errorMessage.value =
      normalizeError(
        error,
        'Data awal penugasan Surveyor gagal dimuat.',
      )
  } finally {
    loadingInitial.value =
      false
  }
}

async function saveAssignment():
  Promise<void> {
  if (
    !canSave.value
  ) {
    if (
      !activePeriod.value
    ) {
      errorMessage.value =
        'Belum ada periode aktif. Hubungi Admin Dinsos.'

      return
    }

    if (
      selectedKelurahanIsFull
        .value
    ) {
      errorMessage.value =
        'Kelurahan sudah memiliki maksimal 3 Surveyor.'

      return
    }

    errorMessage.value =
      'Kecamatan, kelurahan, dan Surveyor wajib dipilih.'

    return
  }

  saving.value =
    true

  resetMessages()

  try {
    const response =
      await surveyorAssignmentService
        .assign({
          kelurahan_id:
            selectedKelurahanId
              .value as number,

          surveyor_id:
            selectedSurveyorId
              .value as number,
        })

    await loadAssignmentData()

    successMessage.value =
      response.message
  } catch (
    error: unknown
  ) {
    errorMessage.value =
      normalizeError(
        error,
        'Penugasan Surveyor gagal disimpan.',
      )
  } finally {
    saving.value =
      false
  }
}

async function removeAssignment(
  assignment:
    SurveyorAssignment,
): Promise<void> {
  if (
    deletingId.value
    !== null
  ) {
    return
  }

  const confirmed =
    window.confirm(
      `Hapus penugasan ${assignment.surveyor.name} dari Kelurahan ${assignment.kelurahan.name}?`,
    )

  if (!confirmed) {
    return
  }

  deletingId.value =
    assignment.id

  resetMessages()

  try {
    const response =
      await surveyorAssignmentService
        .remove(
          assignment.id,
        )

    await loadAssignmentData()

    successMessage.value =
      response.message
  } catch (
    error: unknown
  ) {
    errorMessage.value =
      normalizeError(
        error,
        'Penugasan Surveyor gagal dihapus.',
      )
  } finally {
    deletingId.value =
      null
  }
}

watch(
  selectedKecamatanId,
  () => {
    selectedKelurahanId
      .value = null
  },
)

watch(
  selectedKelurahanId,
  () => {
    validationErrors.value =
      {}
  },
)

onMounted(
  async () => {
    await loadInitial()
  },
)
</script>

<template>
  <div
    class="p-4 sm:p-6 lg:p-8"
  >
    <section
      class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6"
    >
      <div
        class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
      >
        <div>
          <span
            class="text-xs font-extrabold uppercase tracking-[0.16em] text-orange-600"
          >
            Manager
          </span>

          <h1
            class="mt-1 text-2xl font-black text-slate-900"
          >
            Penugasan Surveyor
          </h1>

          <p
            class="mt-2 max-w-3xl text-sm leading-6 text-slate-500"
          >
            Setiap kelurahan dapat memiliki maksimal
            3 Surveyor. Satu Surveyor hanya dapat
            ditugaskan ke satu kelurahan pada periode
            aktif yang sama.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="
            loadingInitial
            ||
            loadingPeriod
          "
          @click="loadInitial"
        >
          <RefreshCw
            :size="17"
            :class="{
              'animate-spin':
                loadingInitial
                ||
                loadingPeriod,
            }"
          />

          Muat Ulang
        </button>
      </div>
    </section>

    <section
      class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
    >
      <div
        class="flex items-start gap-4"
      >
        <span
          class="grid size-11 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"
        >
          <CalendarCheck2
            :size="20"
          />
        </span>

        <div>
          <span
            class="text-xs font-extrabold uppercase tracking-wide text-slate-400"
          >
            Periode Aktif
          </span>

          <template
            v-if="
              activePeriod
            "
          >
            <strong
              class="mt-1 block text-lg font-black text-slate-900"
            >
              {{ activePeriod.name }}
              —
              {{ activePeriod.year }}
            </strong>

            <p
              class="mt-1 text-sm font-semibold text-slate-500"
            >
              {{ activePeriod.participants_count }}
              KPM · periode ditentukan Admin Dinsos
            </p>
          </template>

          <template
            v-else
          >
            <strong
              class="mt-1 block text-base font-black text-amber-700"
            >
              Belum ada periode aktif
            </strong>

            <p
              class="mt-1 text-sm font-semibold text-slate-500"
            >
              Hubungi Admin Dinsos untuk
              mengaktifkan periode BPNT.
            </p>
          </template>
        </div>
      </div>
    </section>

    <section
      v-if="
        activePeriod
      "
      class="mt-5 grid gap-4 sm:grid-cols-3"
    >
      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <div
          class="flex items-center gap-4"
        >
          <span
            class="grid size-11 place-items-center rounded-2xl bg-slate-100 text-slate-600"
          >
            <MapPin
              :size="20"
            />
          </span>

          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Kelurahan KPM
            </span>

            <strong
              class="block text-2xl font-black text-slate-900"
            >
              {{
                meta?.total_kelurahans
                ??
                periodKelurahans.length
              }}
            </strong>
          </div>
        </div>
      </article>

      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <div
          class="flex items-center gap-4"
        >
          <span
            class="grid size-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"
          >
            <UserCheck
              :size="20"
            />
          </span>

          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Kelurahan Ditugaskan
            </span>

            <strong
              class="block text-2xl font-black text-slate-900"
            >
              {{
                meta?.assigned_count
                ??
                0
              }}
            </strong>
          </div>
        </div>
      </article>

      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <div
          class="flex items-center gap-4"
        >
          <span
            class="grid size-11 place-items-center rounded-2xl bg-amber-50 text-amber-700"
          >
            <ClipboardList
              :size="20"
            />
          </span>

          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Surveyor Ditugaskan
            </span>

            <strong
              class="block text-2xl font-black text-slate-900"
            >
              {{
                meta?.total_assignments
                ??
                assignments.length
              }}
            </strong>
          </div>
        </div>
      </article>
    </section>

    <div
      v-if="
        successMessage
      "
      class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="
        errorMessage
      "
      class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <section
      v-if="
        !loadingInitial
        &&
        !activePeriod
      "
      class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center"
    >
      <XCircle
        :size="36"
        class="mx-auto text-amber-500"
      />

      <strong
        class="mt-3 block text-base font-black text-amber-800"
      >
        Penugasan belum dapat dilakukan
      </strong>

      <p
        class="mt-1 text-sm font-semibold text-amber-700"
      >
        Admin Dinsos belum menentukan
        periode BPNT aktif.
      </p>
    </section>

    <section
      v-if="
        activePeriod
      "
      class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
    >
      <div
        class="flex items-center gap-3"
      >
        <span
          class="grid size-10 place-items-center rounded-xl bg-orange-50 text-orange-700"
        >
          <Users
            :size="18"
          />
        </span>

        <div>
          <h2
            class="text-lg font-black text-slate-900"
          >
            Tambah Penugasan
          </h2>

          <p
            class="text-sm text-slate-500"
          >
            Pilih kecamatan, kelurahan, lalu
            Surveyor yang belum ditugaskan.
          </p>
        </div>
      </div>

      <div
        class="mt-5 grid gap-4 lg:grid-cols-3"
      >
        <label
          class="block"
        >
          <span
            class="text-sm font-extrabold text-slate-700"
          >
            Kecamatan
          </span>

          <select
            v-model="
              selectedKecamatanId
            "
            class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-50"
            :disabled="
              saving
              ||
              loadingPeriod
            "
          >
            <option
              :value="null"
            >
              Pilih kecamatan
            </option>

            <option
              v-for="
                kecamatan
                in availableKecamatans
              "
              :key="
                kecamatan.id
              "
              :value="
                kecamatan.id
              "
            >
              {{ kecamatan.name }}
            </option>
          </select>
        </label>

        <label
          class="block"
        >
          <span
            class="text-sm font-extrabold text-slate-700"
          >
            Kelurahan
          </span>

          <select
            v-model="
              selectedKelurahanId
            "
            class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-50 disabled:bg-slate-100"
            :disabled="
              selectedKecamatanId === null
              ||
              saving
              ||
              loadingPeriod
            "
          >
            <option
              :value="null"
            >
              Pilih kelurahan
            </option>

            <option
              v-for="
                kelurahan
                in filteredKelurahans
              "
              :key="
                kelurahan.id
              "
              :value="
                kelurahan.id
              "
              :disabled="
                assignmentCountForKelurahan(
                  kelurahan.id,
                )
                >=
                maxSurveyorsPerKelurahan
              "
            >
              {{ kelurahan.name }}
              ({{
                assignmentCountForKelurahan(
                  kelurahan.id,
                )
              }}/{{ maxSurveyorsPerKelurahan }})
            </option>
          </select>

          <span
            v-if="
              firstFieldError(
                'kelurahan_id',
              )
            "
            class="mt-1.5 block text-xs font-bold text-red-600"
          >
            {{
              firstFieldError(
                'kelurahan_id',
              )
            }}
          </span>
        </label>

        <label
          class="block"
        >
          <span
            class="text-sm font-extrabold text-slate-700"
          >
            Surveyor Aktif
          </span>

          <select
            v-model="
              selectedSurveyorId
            "
            class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-50 disabled:bg-slate-100"
            :disabled="
              saving
              ||
              loadingPeriod
              ||
              availableSurveyors.length === 0
            "
          >
            <option
              :value="null"
            >
              Pilih Surveyor
            </option>

            <option
              v-for="
                surveyor
                in availableSurveyors
              "
              :key="
                surveyor.id
              "
              :value="
                surveyor.id
              "
            >
              {{ surveyor.name }}
              —
              {{ surveyor.username }}
            </option>
          </select>

          <span
            v-if="
              firstFieldError(
                'surveyor_id',
              )
            "
            class="mt-1.5 block text-xs font-bold text-red-600"
          >
            {{
              firstFieldError(
                'surveyor_id',
              )
            }}
          </span>

          <span
            v-if="
              availableSurveyors.length === 0
            "
            class="mt-1.5 block text-xs font-bold text-amber-700"
          >
            Semua Surveyor aktif sudah memiliki
            penugasan pada periode ini.
          </span>
        </label>
      </div>

      <div
        v-if="
          selectedKelurahan
        "
        class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
      >
        <div
          class="flex flex-wrap items-center justify-between gap-2"
        >
          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Kapasitas Kelurahan
            </span>

            <strong
              class="mt-1 block text-sm font-black text-slate-800"
            >
              {{ selectedKelurahan.name }}
            </strong>
          </div>

          <span
            class="rounded-full px-3 py-1 text-xs font-black"
            :class="
              selectedKelurahanIsFull
                ? 'bg-red-100 text-red-700'
                : 'bg-emerald-100 text-emerald-700'
            "
          >
            {{ selectedKelurahanAssignmentCount }}
            /
            {{ maxSurveyorsPerKelurahan }}
            Surveyor
          </span>
        </div>
      </div>

      <div
        class="mt-5 flex justify-end"
      >
        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 text-sm font-extrabold text-white transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="
            !canSave
          "
          @click="
            saveAssignment
          "
        >
          <CheckCircle2
            :size="17"
          />

          {{
            saving
              ? 'Menyimpan...'
              : 'Simpan Penugasan'
          }}
        </button>
      </div>
    </section>

    <section
      v-if="
        activePeriod
      "
      class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card"
    >
      <div
        v-if="
          loadingPeriod
        "
        class="p-8 text-center text-sm font-semibold text-slate-500"
      >
        Memuat penugasan Surveyor...
      </div>

      <div
        v-else-if="
          assignments.length === 0
        "
        class="p-8 text-center"
      >
        <XCircle
          :size="36"
          class="mx-auto text-slate-300"
        />

        <strong
          class="mt-3 block text-sm font-black text-slate-700"
        >
          Belum ada penugasan
        </strong>

        <p
          class="mt-1 text-sm text-slate-500"
        >
          Gunakan form di atas untuk
          menugaskan Surveyor pertama.
        </p>
      </div>

      <div
        v-else
        class="overflow-x-auto"
      >
        <table
          class="min-w-full divide-y divide-slate-200"
        >
          <thead
            class="bg-slate-50"
          >
            <tr>
              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Wilayah
              </th>

              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Surveyor
              </th>

              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Kapasitas
              </th>

              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Waktu
              </th>

              <th
                class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Aksi
              </th>
            </tr>
          </thead>

          <tbody
            class="divide-y divide-slate-100"
          >
            <tr
              v-for="
                assignment
                in assignments
              "
              :key="
                assignment.id
              "
              class="transition hover:bg-slate-50/70"
            >
              <td
                class="px-5 py-4"
              >
                <strong
                  class="block text-sm font-black text-slate-900"
                >
                  {{
                    assignment
                      .kelurahan
                      .name
                  }}
                </strong>

                <span
                  class="mt-1 block text-xs font-semibold text-slate-500"
                >
                  Kecamatan
                  {{
                    assignment
                      .kelurahan
                      .kecamatan
                      .name
                  }}
                </span>
              </td>

              <td
                class="px-5 py-4"
              >
                <strong
                  class="block text-sm font-black text-slate-900"
                >
                  {{
                    assignment
                      .surveyor
                      .name
                  }}
                </strong>

                <span
                  class="mt-1 block text-xs font-semibold text-slate-500"
                >
                  {{
                    assignment
                      .surveyor
                      .username
                  }}

                  <template
                    v-if="
                      assignment
                        .surveyor
                        .phone
                    "
                  >
                    ·
                    {{
                      assignment
                        .surveyor
                        .phone
                    }}
                  </template>
                </span>
              </td>

              <td
                class="px-5 py-4"
              >
                <span
                  class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700"
                >
                  {{
                    assignmentCountForKelurahan(
                      assignment
                        .kelurahan
                        .id,
                    )
                  }}
                  /
                  {{ maxSurveyorsPerKelurahan }}
                </span>
              </td>

              <td
                class="px-5 py-4 text-sm font-semibold text-slate-500"
              >
                {{
                  formatDate(
                    assignment
                      .assigned_at,
                  )
                }}
              </td>

              <td
                class="px-5 py-4"
              >
                <div
                  class="flex justify-end"
                >
                  <button
                    type="button"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-extrabold text-rose-700 transition hover:bg-rose-100 disabled:opacity-50"
                    :disabled="
                      saving
                      ||
                      deletingId !== null
                    "
                    @click="
                      removeAssignment(
                        assignment,
                      )
                    "
                  >
                    <Trash2
                      :size="15"
                    />

                    {{
                      deletingId
                      === assignment.id
                        ? 'Menghapus...'
                        : 'Hapus'
                    }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>