<script setup lang="ts">
import axios from 'axios'

import {
  CheckCircle2,
  ClipboardList,
  MapPin,
  Pencil,
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

const periods =
  ref<BpntPeriod[]>([])

const surveyors =
  ref<SurveyorOption[]>([])

const wilayah =
  ref<Kecamatan[]>([])

const assignments =
  ref<SurveyorAssignment[]>([])

const meta =
  ref<SurveyorAssignmentMeta | null>(
    null,
  )

const periodKelurahanNames =
  ref<string[]>([])

const selectedPeriodId =
  ref<number | null>(null)

const selectedKelurahanId =
  ref<number | null>(null)

const selectedSurveyorId =
  ref<number | null>(null)

const editingAssignmentId =
  ref<number | null>(null)

const loadingInitial =
  ref(false)

const loadingPeriod =
  ref(false)

const saving =
  ref(false)

const deletingId =
  ref<number | null>(null)

const errorMessage =
  ref('')

const successMessage =
  ref('')

const validationErrors =
  ref<ValidationErrors>({})

const eligiblePeriods =
  computed(() =>
    periods.value.filter(
      (period) =>
        period.participants_count > 0,
    ),
  )

const flatKelurahans =
  computed<FlatKelurahan[]>(() =>
    wilayah.value.flatMap(
      (kecamatan) =>
        kecamatan.kelurahans.map(
          (kelurahan) => ({
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

const availableKelurahans =
  computed(() => {
    const allowed =
      new Set(
        periodKelurahanNames
          .value
          .map(
            (name) =>
              name
                .trim()
                .toLocaleLowerCase(
                  'id-ID',
                ),
          ),
      )

    return flatKelurahans
      .value
      .filter(
        (kelurahan) =>
          allowed.has(
            kelurahan
              .name
              .trim()
              .toLocaleLowerCase(
                'id-ID',
              ),
          ),
      )
  })

const assignedKelurahanIds =
  computed(() =>
    new Set(
      assignments.value.map(
        (assignment) =>
          assignment
            .kelurahan
            .id,
      ),
    ),
  )

const selectableKelurahans =
  computed(() => {
    if (
      editingAssignmentId.value
      !== null
    ) {
      return availableKelurahans
        .value
        .filter(
          (kelurahan) =>
            kelurahan.id
            ===
            selectedKelurahanId.value,
        )
    }

    return availableKelurahans
      .value
      .filter(
        (kelurahan) =>
          !assignedKelurahanIds
            .value
            .has(
              kelurahan.id,
            ),
      )
  })

const isEditing =
  computed(() =>
    editingAssignmentId.value
    !== null,
  )

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
  selectedKelurahanId.value =
    null

  selectedSurveyorId.value =
    null

  editingAssignmentId.value =
    null

  validationErrors.value =
    {}
}

function firstFieldError(
  field: string,
): string | null {
  return validationErrors
    .value[field]?.[0]
    ?? null
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
      error
        .response
        ?.data
        ?.errors
      ?? {}

    const firstError =
      Object.values(
        validationErrors.value,
      )[0]?.[0]

    return firstError
      ??
      error
        .response
        ?.data
        ?.message
      ??
      fallback
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
          .getPeriods(),

        surveyorService
          .getActiveOptions(),

        wilayahService
          .getMaster(),
      ])

    periods.value =
      periodData

    surveyors.value =
      surveyorData.data

    wilayah.value =
      wilayahData.data

    const firstPeriod =
      periodData.find(
        (period) =>
          period.participants_count > 0,
      )

    selectedPeriodId.value =
      firstPeriod?.id
      ?? null
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

async function loadSelectedPeriod():
  Promise<void> {
  if (
    selectedPeriodId.value
    === null
  ) {
    assignments.value =
      []

    periodKelurahanNames.value =
      []

    meta.value =
      null

    resetForm()

    return
  }

  loadingPeriod.value =
    true

  resetMessages()
  resetForm()

  try {
    const [
      assignmentData,
      participantOptions,
    ] =
      await Promise.all([
        surveyorAssignmentService
          .getByPeriod(
            selectedPeriodId.value,
          ),

        bnbaService
          .getParticipantFilterOptions(
            selectedPeriodId.value,
          ),
      ])

    assignments.value =
      assignmentData.data

    meta.value =
      assignmentData.meta

    periodKelurahanNames.value =
      participantOptions
        .kelurahan
  } catch (
    error: unknown
  ) {
    assignments.value =
      []

    periodKelurahanNames.value =
      []

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

function editAssignment(
  assignment:
    SurveyorAssignment,
): void {
  resetMessages()

  editingAssignmentId.value =
    assignment.id

  selectedKelurahanId.value =
    assignment
      .kelurahan
      .id

  selectedSurveyorId.value =
    assignment
      .surveyor
      .id
}

function cancelEdit():
  void {
  resetMessages()
  resetForm()
}

async function saveAssignment():
  Promise<void> {
  if (
    selectedPeriodId.value
      === null
    ||
    selectedKelurahanId.value
      === null
    ||
    selectedSurveyorId.value
      === null
  ) {
    const message =
      'Periode, kelurahan, dan Surveyor wajib dipilih.'

    validationErrors.value = {
      form: [
        message,
      ],
    }

    errorMessage.value =
      message

    return
  }

  saving.value =
    true

  resetMessages()

  try {
    const response =
      await surveyorAssignmentService
        .assign({
          period_id:
            selectedPeriodId.value,

          kelurahan_id:
            selectedKelurahanId.value,

          surveyor_id:
            selectedSurveyorId.value,
        })

    const message =
      response.message

    await loadSelectedPeriod()

    successMessage.value =
      message
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
      `Hapus penugasan ${assignment.surveyor.name} untuk Kelurahan ${assignment.kelurahan.name}?`,
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

    const message =
      response.message

    await loadSelectedPeriod()

    successMessage.value =
      message
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
  selectedPeriodId,
  () => {
    if (
      !loadingInitial.value
    ) {
      void loadSelectedPeriod()
    }
  },
)

onMounted(
  async () => {
    await loadInitial()

    if (
      selectedPeriodId.value
      !== null
    ) {
      await loadSelectedPeriod()
    }
  },
)
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8">
    <!-- HEADER -->
    <section
      class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6"
    >
      <div
        class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
      >
        <div>
          <span
            class="text-xs font-extrabold uppercase tracking-[0.16em] text-brand-600"
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
            Tentukan Surveyor untuk setiap
            kelurahan berdasarkan periode
            BPNT. Satu Surveyor dapat
            menangani lebih dari satu
            kelurahan, tetapi satu kelurahan
            hanya memiliki satu Surveyor
            pada periode yang sama.
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
          @click="loadSelectedPeriod"
        >
          <RefreshCw
            :size="17"
            :class="{
              'animate-spin':
                loadingPeriod,
            }"
          />

          Muat Ulang
        </button>
      </div>
    </section>

    <!-- PILIH PERIODE -->
    <section
      class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
    >
      <label class="block max-w-xl">
        <span
          class="text-sm font-extrabold text-slate-700"
        >
          Periode BPNT
        </span>

        <select
          v-model="selectedPeriodId"
          class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
          :disabled="
            loadingInitial
            ||
            loadingPeriod
          "
        >
          <option :value="null">
            Pilih periode
          </option>

          <option
            v-for="
              period
              in eligiblePeriods
            "
            :key="period.id"
            :value="period.id"
          >
            {{ period.name }}
            —
            {{ period.year }}
            ({{ period.participants_count }} KPM)
          </option>
        </select>
      </label>

      <p
        v-if="
          !loadingInitial
          &&
          eligiblePeriods.length === 0
        "
        class="mt-3 text-sm font-semibold text-amber-700"
      >
        Belum ada periode dengan BNBA/KPM
        yang dapat ditugaskan.
      </p>
    </section>

    <!-- STATISTIK -->
    <section
      v-if="
        selectedPeriodId !== null
      "
      class="mt-5 grid gap-4 sm:grid-cols-3"
    >
      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <div class="flex items-center gap-4">
          <span
            class="grid size-11 place-items-center rounded-2xl bg-slate-100 text-slate-600"
          >
            <MapPin :size="20" />
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
                availableKelurahans.length
              }}
            </strong>
          </div>
        </div>
      </article>

      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <div class="flex items-center gap-4">
          <span
            class="grid size-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"
          >
            <UserCheck :size="20" />
          </span>

          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Sudah Ditugaskan
            </span>

            <strong
              class="block text-2xl font-black text-slate-900"
            >
              {{
                meta?.assigned_count
                ??
                assignments.length
              }}
            </strong>
          </div>
        </div>
      </article>

      <article
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
      >
        <div class="flex items-center gap-4">
          <span
            class="grid size-11 place-items-center rounded-2xl bg-amber-50 text-amber-700"
          >
            <ClipboardList :size="20" />
          </span>

          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Belum Ditugaskan
            </span>

            <strong
              class="block text-2xl font-black text-slate-900"
            >
              {{
                meta?.unassigned_count
                ??
                Math.max(
                  0,
                  availableKelurahans.length
                    -
                    assignments.length,
                )
              }}
            </strong>
          </div>
        </div>
      </article>
    </section>

    <!-- SUCCESS -->
    <div
      v-if="successMessage"
      class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"
    >
      {{ successMessage }}
    </div>

    <!-- ERROR -->
    <div
      v-if="errorMessage"
      class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <!-- FORM ASSIGNMENT -->
    <section
      v-if="
        selectedPeriodId !== null
      "
      class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
    >
      <div class="flex items-center gap-3">
        <span
          class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700"
        >
          <Users :size="18" />
        </span>

        <div>
          <h2
            class="text-lg font-black text-slate-900"
          >
            {{
              isEditing
                ? 'Ganti Surveyor'
                : 'Tambah Penugasan'
            }}
          </h2>

          <p
            class="text-sm text-slate-500"
          >
            {{
              isEditing
                ? 'Kelurahan dikunci; pilih Surveyor pengganti.'
                : 'Pilih kelurahan yang belum ditugaskan.'
            }}
          </p>
        </div>
      </div>

      <div
        class="mt-5 grid gap-4 lg:grid-cols-2"
      >
        <!-- KELURAHAN -->
        <label class="block">
          <span
            class="text-sm font-extrabold text-slate-700"
          >
            Kelurahan
          </span>

          <select
            v-model="selectedKelurahanId"
            class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50 disabled:bg-slate-100"
            :disabled="
              isEditing
              ||
              saving
              ||
              loadingPeriod
            "
          >
            <option :value="null">
              Pilih kelurahan
            </option>

            <option
              v-for="
                kelurahan
                in selectableKelurahans
              "
              :key="kelurahan.id"
              :value="kelurahan.id"
            >
              {{ kelurahan.name }}
              —
              {{ kelurahan.kecamatanName }}
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

        <!-- SURVEYOR -->
        <label class="block">
          <span
            class="text-sm font-extrabold text-slate-700"
          >
            Surveyor Aktif
          </span>

          <select
            v-model="selectedSurveyorId"
            class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
            :disabled="
              saving
              ||
              loadingPeriod
            "
          >
            <option :value="null">
              Pilih Surveyor
            </option>

            <option
              v-for="
                surveyor
                in surveyors
              "
              :key="surveyor.id"
              :value="surveyor.id"
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
        </label>
      </div>

      <div
        class="mt-5 flex flex-wrap justify-end gap-2"
      >
        <button
          v-if="isEditing"
          type="button"
          class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
          :disabled="saving"
          @click="cancelEdit"
        >
          Batal
        </button>

        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 text-sm font-extrabold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="
            saving
            ||
            loadingPeriod
          "
          @click="saveAssignment"
        >
          <CheckCircle2 :size="17" />

          {{
            saving
              ? 'Menyimpan...'
              : isEditing
                ? 'Simpan Penggantian'
                : 'Simpan Penugasan'
          }}
        </button>
      </div>
    </section>

    <!-- LIST ASSIGNMENT -->
    <section
      v-if="
        selectedPeriodId !== null
      "
      class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card"
    >
      <div
        v-if="loadingPeriod"
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
          class="mx-auto text-slate-300"
          :size="36"
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
          <thead class="bg-slate-50">
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
                Ditugaskan Oleh
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
              :key="assignment.id"
              class="transition hover:bg-slate-50/70"
            >
              <!-- WILAYAH -->
              <td class="px-5 py-4">
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

              <!-- SURVEYOR -->
              <td class="px-5 py-4">
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

              <!-- ASSIGNED BY -->
              <td
                class="px-5 py-4 text-sm font-semibold text-slate-600"
              >
                {{
                  assignment
                    .assigned_by
                    .name
                }}
              </td>

              <!-- WAKTU -->
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

              <!-- ACTION -->
              <td class="px-5 py-4">
                <div
                  class="flex flex-wrap justify-end gap-2"
                >
                  <button
                    type="button"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-xs font-extrabold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                    :disabled="
                      saving
                      ||
                      deletingId !== null
                    "
                    @click="
                      editAssignment(
                        assignment,
                      )
                    "
                  >
                    <Pencil :size="15" />

                    Ganti Surveyor
                  </button>

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
                    <Trash2 :size="15" />

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