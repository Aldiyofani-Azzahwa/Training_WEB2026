<script setup lang="ts">
import {
  computed,
  reactive,
  ref,
} from 'vue'

import {
  CalendarDays,
  Check,
  Edit3,
  LoaderCircle,
  Plus,
  Trash2,
  X,
} from '@lucide/vue'

import type {
  BpntPeriod,
  CreateBpntPeriodPayload,
  LaravelValidationErrors,
  UpdateBpntPeriodPayload,
} from '@/types/bnba'

type PeriodFormMode =
  | 'normal'
  | 'create_period'
  | 'edit_period'

const props = defineProps<{
  periods: BpntPeriod[]

  selectedPeriodId:
    number | null

  isLoading:
    boolean

  isCreating:
    boolean

  updatingPeriodId:
    number | null

  deletingPeriodId:
    number | null

  validationErrors:
    LaravelValidationErrors

  /*
   * true ketika proses BNBA
   * sedang berjalan.
   */
  bnbaLocked:
    boolean
}>()

const emit = defineEmits<{
  select: [
    periodId: number,
  ]

  create: [
    payload:
      CreateBpntPeriodPayload,
  ]

  update: [
    periodId: number,
    payload:
      UpdateBpntPeriodPayload,
  ]

  delete: [
    period:
      BpntPeriod,
  ]

  modeChange: [
    mode:
      PeriodFormMode,
  ]
}>()

const showCreateForm =
  ref(false)

const editingPeriodId =
  ref<number | null>(
    null,
  )

const createForm =
  reactive({
    name: '',

    year:
      String(
        new Date()
          .getFullYear(),
      ),
  })

const editForm =
  reactive({
    name: '',
    year: '',
  })

const currentMode =
  computed<PeriodFormMode>(
    () => {
      if (
        showCreateForm.value
      ) {
        return 'create_period'
      }

      if (
        editingPeriodId.value
        !== null
      ) {
        return 'edit_period'
      }

      return 'normal'
    },
  )

const canCreate =
  computed(() => {
    const year =
      Number(
        createForm.year,
      )

    return (
      createForm
        .name
        .trim()
      !== ''
      &&
      Number.isInteger(
        year,
      )
      &&
      year >= 2000
      &&
      year <= 2100
      &&
      !props.isCreating
      &&
      !props.bnbaLocked
    )
  })

function notifyMode():
  void {
  emit(
    'modeChange',
    currentMode.value,
  )
}

function toggleCreateForm():
  void {
  if (
    props.bnbaLocked
    ||
    editingPeriodId.value
    !== null
  ) {
    return
  }

  showCreateForm.value =
    !showCreateForm.value

  notifyMode()
}

function submitCreate():
  void {
  if (
    !canCreate.value
  ) {
    return
  }

  emit(
    'create',
    {
      name:
        createForm
          .name
          .trim(),

      year:
        Number(
          createForm.year,
        ),
    },
  )
}

function beginEdit(
  period: BpntPeriod,
): void {
  if (
    props.bnbaLocked
    ||
    showCreateForm.value
    ||
    editingPeriodId.value
    !== null
  ) {
    return
  }

  editingPeriodId.value =
    period.id

  editForm.name =
    period.name

  editForm.year =
    String(
      period.year,
    )

  notifyMode()
}

function cancelEdit():
  void {
  editingPeriodId.value =
    null

  editForm.name =
    ''

  editForm.year =
    ''

  notifyMode()
}

function submitEdit(
  period: BpntPeriod,
): void {
  const year =
    Number(
      editForm.year,
    )

  if (
    props.bnbaLocked
    ||
    editForm
      .name
      .trim()
    === ''
    ||
    !Number.isInteger(
      year,
    )
    ||
    year < 2000
    ||
    year > 2100
  ) {
    return
  }

  emit(
    'update',
    period.id,
    {
      name:
        editForm
          .name
          .trim(),

      year,
    },
  )
}

function canSelectPeriod(
  periodId: number,
): boolean {
  if (
    props.bnbaLocked
  ) {
    return false
  }

  if (
    showCreateForm.value
  ) {
    return false
  }

  if (
    editingPeriodId.value
    !== null
  ) {
    return false
  }

  return (
    props.deletingPeriodId
    !== periodId
  )
}

function selectPeriod(
  periodId: number,
): void {
  if (
    !canSelectPeriod(
      periodId,
    )
  ) {
    return
  }

  emit(
    'select',
    periodId,
  )
}

function canEditPeriod(
  periodId: number,
): boolean {
  if (
    props.bnbaLocked
    ||
    showCreateForm.value
  ) {
    return false
  }

  if (
    editingPeriodId.value
    === null
  ) {
    return true
  }

  return (
    editingPeriodId.value
    === periodId
  )
}

function canDeletePeriod(
  period: BpntPeriod,
): boolean {
  return (
    period.can_delete
    &&
    !props.bnbaLocked
    &&
    !showCreateForm.value
    &&
    editingPeriodId.value
    === null
    &&
    props.deletingPeriodId
    !== period.id
  )
}

function bnbaLabel(
  period: BpntPeriod,
): string {
  if (
    period.bnba
    === null
  ) {
    return 'Belum ada BNBA'
  }

  if (
    period.bnba.status
    === 'preview_ready'
  ) {
    return 'BNBA menunggu konfirmasi'
  }

  if (
    period.bnba.status
    === 'confirmed'
  ) {
    return `${period.participants_count} KPM terkonfirmasi`
  }

  return 'BNBA gagal diproses'
}
</script>

<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
  >
    <!-- HEADER -->
    <header
      class="border-b border-slate-200 p-5 sm:p-6"
    >
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
      >
        <div>
          <div
            class="mb-2 flex items-center gap-2 text-[#006855]"
          >
            <CalendarDays
              :size="20"
              aria-hidden="true"
            />

            <span
              class="text-sm font-semibold"
            >
              Periode BPNT
            </span>
          </div>

          <h2
            class="text-lg font-bold text-slate-900"
          >
            Daftar Periode
          </h2>

          <p
            class="mt-1 text-sm leading-6 text-slate-500"
          >
            Klik periode untuk membuka
            pengelolaan BNBA.
          </p>
        </div>

        <button
          type="button"
          :disabled="
            bnbaLocked
            ||
            editingPeriodId
            !== null
          "
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-[#006855] px-4 text-sm font-bold text-[#006855] transition hover:bg-[#006855]/5 disabled:cursor-not-allowed disabled:border-slate-300 disabled:text-slate-400"
          @click="
            toggleCreateForm
          "
        >
          <X
            v-if="
              showCreateForm
            "
            :size="18"
            aria-hidden="true"
          />

          <Plus
            v-else
            :size="18"
            aria-hidden="true"
          />

          {{
            showCreateForm
              ? 'Tutup'
              : 'Tambah Periode'
          }}
        </button>
      </div>

      <!-- CREATE -->
      <form
        v-if="
          showCreateForm
        "
        class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5"
        @submit.prevent="
          submitCreate
        "
      >
        <div
          class="grid gap-4 sm:grid-cols-[1fr_180px]"
        >
          <div>
            <label
              for="new-period-name"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Nama periode
            </label>

            <input
              id="new-period-name"
              v-model="
                createForm.name
              "
              type="text"
              maxlength="150"
              placeholder="Contoh: BPNT Juli"
              class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10"
            >

            <p
              v-if="
                validationErrors
                  .name?.[0]
              "
              class="mt-1.5 text-xs text-red-600"
            >
              {{
                validationErrors
                  .name[0]
              }}
            </p>
          </div>

          <div>
            <label
              for="new-period-year"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Tahun
            </label>

            <input
              id="new-period-year"
              v-model="
                createForm.year
              "
              type="number"
              min="2000"
              max="2100"
              class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855] focus:ring-4 focus:ring-[#006855]/10"
            >

            <p
              v-if="
                validationErrors
                  .year?.[0]
              "
              class="mt-1.5 text-xs text-red-600"
            >
              {{
                validationErrors
                  .year[0]
              }}
            </p>
          </div>
        </div>

        <div
          class="mt-4 flex justify-end gap-2"
        >
          <button
            type="button"
            :disabled="
              isCreating
            "
            class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 disabled:opacity-50"
            @click="
              toggleCreateForm
            "
          >
            Batal
          </button>

          <button
            type="submit"
            :disabled="
              !canCreate
            "
            class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-[#006855] px-5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
          >
            <LoaderCircle
              v-if="
                isCreating
              "
              :size="16"
              class="animate-spin"
            />

            <Check
              v-else
              :size="16"
            />

            Simpan Periode
          </button>
        </div>
      </form>
    </header>

    <!-- LIST -->
    <div
      class="p-5 sm:p-6"
    >
      <div
        v-if="
          isLoading
        "
        class="flex min-h-32 items-center justify-center"
      >
        <LoaderCircle
          :size="24"
          class="animate-spin text-[#006855]"
        />
      </div>

      <div
        v-else-if="
          periods.length
          === 0
        "
        class="rounded-2xl border border-dashed border-slate-300 p-10 text-center"
      >
        <strong
          class="text-slate-800"
        >
          Belum ada periode BPNT
        </strong>

        <p
          class="mt-2 text-sm text-slate-500"
        >
          Tambahkan periode BPNT untuk
          memulai pengelolaan BNBA.
        </p>
      </div>

      <div
        v-else
        class="grid gap-3"
      >
        <article
          v-for="
            period in periods
          "
          :key="
            period.id
          "
          :aria-disabled="
            !canSelectPeriod(
              period.id,
            )
          "
          :class="[
            'rounded-2xl border p-4 transition sm:p-5',
            selectedPeriodId
            === period.id
              ? 'border-[#006855] bg-[#006855]/5 ring-2 ring-[#006855]/10'
              : 'border-slate-200',
            canSelectPeriod(
              period.id,
            )
              ? 'cursor-pointer hover:border-[#006855]/40 hover:bg-slate-50'
              : 'cursor-default',
            (
              showCreateForm
              ||
              (
                editingPeriodId
                !== null
                &&
                editingPeriodId
                !== period.id
              )
              ||
              bnbaLocked
            )
              ? 'opacity-60'
              : '',
          ]"
          tabindex="0"
          @click="
            selectPeriod(
              period.id,
            )
          "
          @keydown.enter="
            selectPeriod(
              period.id,
            )
          "
        >
          <!-- NORMAL CARD -->
          <template
            v-if="
              editingPeriodId
              !== period.id
            "
          >
            <div
              class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
              <div
                class="min-w-0"
              >
                <strong
                  class="block truncate text-base text-slate-900"
                >
                  {{ period.name }}
                </strong>

                <div
                  class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-500"
                >
                  <span>
                    Tahun
                    {{ period.year }}
                  </span>

                  <span>
                    {{
                      bnbaLabel(
                        period,
                      )
                    }}
                  </span>
                </div>
              </div>

              <div
                class="flex shrink-0 flex-wrap gap-2"
              >
                <button
                  type="button"
                  :disabled="
                    !canEditPeriod(
                      period.id,
                    )
                  "
                  class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 px-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                  @click.stop="
                    beginEdit(
                      period,
                    )
                  "
                >
                  <Edit3
                    :size="15"
                    aria-hidden="true"
                  />

                  Edit
                </button>

                <button
                  v-if="
                    period.can_delete
                  "
                  type="button"
                  :disabled="
                    !canDeletePeriod(
                      period,
                    )
                  "
                  class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 text-xs font-bold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40"
                  @click.stop="
                    emit(
                      'delete',
                      period,
                    )
                  "
                >
                  <LoaderCircle
                    v-if="
                      deletingPeriodId
                      === period.id
                    "
                    :size="15"
                    class="animate-spin"
                  />

                  <Trash2
                    v-else
                    :size="15"
                  />

                  Hapus
                </button>
              </div>
            </div>
          </template>

          <!-- EDIT -->
          <form
            v-else
            class="grid gap-4"
            @click.stop
            @submit.prevent="
              submitEdit(
                period,
              )
            "
          >
            <div
              class="grid gap-4 sm:grid-cols-[1fr_180px]"
            >
              <div>
                <label
                  :for="
                    `edit-period-name-${period.id}`
                  "
                  class="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Nama periode
                </label>

                <input
                  :id="
                    `edit-period-name-${period.id}`
                  "
                  v-model="
                    editForm.name
                  "
                  type="text"
                  maxlength="150"
                  class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855]"
                >
              </div>

              <div>
                <label
                  :for="
                    `edit-period-year-${period.id}`
                  "
                  class="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Tahun
                </label>

                <input
                  :id="
                    `edit-period-year-${period.id}`
                  "
                  v-model="
                    editForm.year
                  "
                  type="number"
                  min="2000"
                  max="2100"
                  :disabled="
                    !period.can_edit_year
                  "
                  class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#006855] disabled:bg-slate-100 disabled:text-slate-500"
                >
              </div>
            </div>

            <small
              v-if="
                !period.can_edit_year
              "
              class="text-slate-500"
            >
              Tahun dikunci selama periode
              masih memiliki BNBA.
            </small>

            <div
              class="flex justify-end gap-2"
            >
              <button
                type="button"
                :disabled="
                  updatingPeriodId
                  === period.id
                "
                class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 disabled:opacity-50"
                @click="
                  cancelEdit
                "
              >
                Batal
              </button>

              <button
                type="submit"
                :disabled="
                  updatingPeriodId
                  === period.id
                "
                class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-[#006855] px-4 text-sm font-bold text-white disabled:opacity-50"
              >
                <LoaderCircle
                  v-if="
                    updatingPeriodId
                    === period.id
                  "
                  :size="16"
                  class="animate-spin"
                />

                <Check
                  v-else
                  :size="16"
                />

                Simpan
              </button>
            </div>
          </form>

          <!-- ACCORDION CONTENT -->
          <div
            v-if="
              selectedPeriodId
              === period.id
              && editingPeriodId === null
            "
            class="mt-4 cursor-default"
            @click.stop
          >
            <slot :period="period" />
          </div>
        </article>
      </div>
    </div>
  </section>
</template>