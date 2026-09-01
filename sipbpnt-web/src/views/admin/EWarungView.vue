<script setup lang="ts">
import axios from 'axios'

import {
  CheckCircle2,
  Pencil,
  Plus,
  RefreshCw,
  Search,
  Store,
  Trash2,
  X,
  XCircle,
} from '@lucide/vue'

import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'

import {
  eWarungService,
} from '@/services/eWarungService'

import type {
  EWarung,
  EWarungCreatePayload,
  EWarungMeta,
  EWarungUpdatePayload,
} from '@/types/eWarung'

import type {
  FormErrors,
  StatusFilter,
} from '@/types/adminCrud'

import {
  firstFieldError as sharedFirstFieldError,
  matchesStatusFilter,
} from '@/utils/adminCrud'

import { formatDateTimeMedium } from '@/utils/formatDateTime'

interface EWarungFormState {
  name: string
}

const eWarungs =
  ref<EWarung[]>([])

const meta =
  ref<EWarungMeta>({
    total: 0,
    active: 0,
    inactive: 0,
  })

const loading =
  ref(false)

const saving =
  ref(false)

const statusChangingId =
  ref<number | null>(null)

const deletingId =
  ref<number | null>(null)

const errorMessage =
  ref('')

const successMessage =
  ref('')

const searchQuery =
  ref('')

const statusFilter =
  ref<StatusFilter>(
    'all',
  )

const formOpen =
  ref(false)

const editingEWarung =
  ref<EWarung | null>(
    null,
  )

const formErrors =
  ref<FormErrors>({})

const form =
  reactive<EWarungFormState>({
    name: '',
  })

const isEditing =
  computed(
    () =>
      editingEWarung.value
      !== null,
  )

const filteredEWarungs =
  computed(() => {
    const query =
      searchQuery.value
        .trim()
        .toLocaleLowerCase(
          'id-ID',
        )

    return eWarungs.value
      .filter(
        (
          eWarung,
        ) => {
          if (
            !matchesStatusFilter(
              eWarung.is_active,
              statusFilter.value,
            )
          ) {
            return false
          }

          if (!query) {
            return true
          }

          return eWarung.name
            .toLocaleLowerCase(
              'id-ID',
            )
            .includes(
              query,
            )
        },
      )
  })

function resetMessages():
  void {
  errorMessage.value =
    ''

  successMessage.value =
    ''
}

function resetForm():
  void {
  form.name = ''
  formErrors.value = {}
}

function openCreate():
  void {
  resetMessages()
  resetForm()

  editingEWarung.value =
    null

  formOpen.value =
    true
}

function openEdit(
  eWarung: EWarung,
): void {
  resetMessages()
  resetForm()

  editingEWarung.value =
    eWarung

  form.name =
    eWarung.name

  formOpen.value =
    true
}

function closeForm():
  void {
  if (saving.value) {
    return
  }

  formOpen.value =
    false

  editingEWarung.value =
    null

  resetForm()
}

function firstFieldError(
  field: string,
): string | null {
  return sharedFirstFieldError(
    formErrors.value,
    field,
  )
}

function formatDate(
  value: string | null,
): string {
  return formatDateTimeMedium(
    value,
  )
}

async function loadEWarungs():
  Promise<void> {
  loading.value =
    true

  errorMessage.value =
    ''

  try {
    const response =
      await eWarungService
        .getAll()

    eWarungs.value =
      response.data

    meta.value =
      response.meta
  } catch (
    error: unknown
  ) {
    if (
      axios.isAxiosError<{
        message?: string
      }>(
        error,
      )
    ) {
      errorMessage.value =
        error
          .response
          ?.data
          ?.message
        ??
        'Data E-Warung gagal dimuat.'
    } else {
      errorMessage.value =
        'Data E-Warung gagal dimuat.'
    }
  } finally {
    loading.value =
      false
  }
}

async function saveEWarung():
  Promise<void> {
  if (saving.value) {
    return
  }

  saving.value =
    true

  formErrors.value =
    {}

  errorMessage.value =
    ''

  successMessage.value =
    ''

  try {
    if (
      editingEWarung.value
    ) {
      const payload:
        EWarungUpdatePayload = {
          name:
            form.name,
        }

      const response =
        await eWarungService
          .update(
            editingEWarung
              .value
              .id,
            payload,
          )

      successMessage.value =
        response.message
    } else {
      const payload:
        EWarungCreatePayload = {
          name:
            form.name,
        }

      const response =
        await eWarungService
          .create(
            payload,
          )

      successMessage.value =
        response.message
    }

    formOpen.value =
      false

    editingEWarung.value =
      null

    resetForm()

    await loadEWarungs()
  } catch (
    error: unknown
  ) {
    if (
      axios.isAxiosError<{
        message?: string
        errors?: FormErrors
      }>(
        error,
      )
    ) {
      formErrors.value =
        error
          .response
          ?.data
          ?.errors
        ?? {}

      errorMessage.value =
        error
          .response
          ?.data
          ?.message
        ??
        'Data E-Warung gagal disimpan.'
    } else {
      errorMessage.value =
        'Data E-Warung gagal disimpan.'
    }
  } finally {
    saving.value =
      false
  }
}

async function toggleStatus(
  eWarung: EWarung,
): Promise<void> {
  if (
    statusChangingId.value
    !== null
  ) {
    return
  }

  const nextStatus =
    !eWarung.is_active

  const action =
    nextStatus
      ? 'mengaktifkan'
      : 'menonaktifkan'

  const confirmed =
    window.confirm(
      `Yakin ingin ${action} ${eWarung.name}?`,
    )

  if (!confirmed) {
    return
  }

  statusChangingId.value =
    eWarung.id

  resetMessages()

  try {
    const response =
      await eWarungService
        .setStatus(
          eWarung.id,
          nextStatus,
        )

    successMessage.value =
      response.message

    await loadEWarungs()
  } catch (
    error: unknown
  ) {
    if (
      axios.isAxiosError<{
        message?: string
      }>(
        error,
      )
    ) {
      errorMessage.value =
        error
          .response
          ?.data
          ?.message
        ??
        'Status E-Warung gagal diubah.'
    } else {
      errorMessage.value =
        'Status E-Warung gagal diubah.'
    }
  } finally {
    statusChangingId.value =
      null
  }
}

async function deleteEWarung(
  eWarung: EWarung,
): Promise<void> {
  if (
    deletingId.value
    !== null
  ) {
    return
  }

  const confirmed =
    window.confirm(
      `Hapus ${eWarung.name}? E-Warung yang sudah memiliki histori transaksi tidak dapat dihapus.`,
    )

  if (!confirmed) {
    return
  }

  deletingId.value =
    eWarung.id

  resetMessages()

  try {
    const response =
      await eWarungService
        .delete(
          eWarung.id,
        )

    successMessage.value =
      response.message

    await loadEWarungs()
  } catch (
    error: unknown
  ) {
    if (
      axios.isAxiosError<{
        message?: string
        errors?: FormErrors
      }>(
        error,
      )
    ) {
      errorMessage.value =
        error
          .response
          ?.data
          ?.errors
          ?.e_warung
          ?.[0]
        ??
        error
          .response
          ?.data
          ?.message
        ??
        'E-Warung gagal dihapus.'
    } else {
      errorMessage.value =
        'E-Warung gagal dihapus.'
    }
  } finally {
    deletingId.value =
      null
  }
}

onMounted(() => {
  void loadEWarungs()
})
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
            Master Operasional
          </span>

          <h1
            class="mt-1 text-2xl font-black text-slate-900"
          >
            Master E-Warung
          </h1>

          <p
            class="mt-2 max-w-3xl text-sm leading-6 text-slate-500"
          >
            Kelola E-Warung yang digunakan
            pada transaksi BPNT. E-Warung
            yang tidak digunakan lagi cukup
            dinonaktifkan apabila sudah
            mempunyai histori transaksi.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-orange-600 px-4 text-sm font-extrabold text-white transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="
            loading
            ||
            saving
          "
          @click="openCreate"
        >
          <Plus
            :size="18"
          />

          Tambah E-Warung
        </button>
      </div>
    </section>

    <section
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
            <Store
              :size="20"
            />
          </span>

          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Total
            </span>

            <strong
              class="block text-2xl font-black text-slate-900"
            >
              {{ meta.total }}
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
            <CheckCircle2
              :size="20"
            />
          </span>

          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Aktif
            </span>

            <strong
              class="block text-2xl font-black text-slate-900"
            >
              {{ meta.active }}
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
            class="grid size-11 place-items-center rounded-2xl bg-rose-50 text-rose-700"
          >
            <XCircle
              :size="20"
            />
          </span>

          <div>
            <span
              class="text-xs font-bold uppercase tracking-wide text-slate-400"
            >
              Nonaktif
            </span>

            <strong
              class="block text-2xl font-black text-slate-900"
            >
              {{ meta.inactive }}
            </strong>
          </div>
        </div>
      </article>
    </section>

    <section
      class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-5"
    >
      <div
        class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_auto]"
      >
        <label
          class="relative block"
        >
          <Search
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            :size="18"
          />

          <input
            v-model="searchQuery"
            type="search"
            placeholder="Cari nama E-Warung"
            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-800 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-50"
          />
        </label>

        <select
          v-model="statusFilter"
          class="min-h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-50"
        >
          <option value="all">
            Semua Status
          </option>

          <option value="active">
            Aktif
          </option>

          <option value="inactive">
            Nonaktif
          </option>
        </select>

        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="loadEWarungs"
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
      </div>
    </section>

    <div
      v-if="successMessage"
      class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="
        errorMessage
        &&
        !formOpen
      "
      class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <section
      class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card"
    >
      <div
        v-if="
          loading
          &&
          eWarungs.length === 0
        "
        class="p-8 text-center text-sm font-semibold text-slate-500"
      >
        Memuat data E-Warung...
      </div>

      <div
        v-else-if="
          filteredEWarungs.length === 0
        "
        class="p-8 text-center"
      >
        <Store
          class="mx-auto text-slate-300"
          :size="36"
        />

        <strong
          class="mt-3 block text-sm font-black text-slate-700"
        >
          Tidak ada E-Warung yang sesuai
        </strong>

        <p
          class="mt-1 text-sm text-slate-500"
        >
          Ubah kata pencarian
          atau filter status.
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
                E-Warung
              </th>

              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Status
              </th>

              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Diperbarui
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
              v-for="eWarung in filteredEWarungs"
              :key="eWarung.id"
              class="align-middle transition hover:bg-slate-50/70"
            >
              <td class="px-5 py-4">
                <div
                  class="flex items-center gap-3"
                >
                  <span
                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-orange-50 text-orange-700"
                  >
                    <Store
                      :size="18"
                    />
                  </span>

                  <div>
                    <strong
                      class="block text-sm font-black text-slate-900"
                    >
                      {{ eWarung.name }}
                    </strong>

                    <span
                      class="mt-1 block text-xs font-semibold text-slate-400"
                    >
                      ID {{ eWarung.id }}
                    </span>
                  </div>
                </div>
              </td>

              <td class="px-5 py-4">
                <span
                  v-if="eWarung.is_active"
                  class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-700"
                >
                  <CheckCircle2
                    :size="14"
                  />

                  Aktif
                </span>

                <span
                  v-else
                  class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-extrabold text-rose-700"
                >
                  <XCircle
                    :size="14"
                  />

                  Nonaktif
                </span>
              </td>

              <td
                class="px-5 py-4 text-sm font-semibold text-slate-500"
              >
                {{ formatDate(eWarung.updated_at) }}
              </td>

              <td class="px-5 py-4">
                <div
                  class="flex flex-wrap justify-end gap-2"
                >
                  <button
                    type="button"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-xs font-extrabold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="
                      saving
                      ||
                      statusChangingId !== null
                      ||
                      deletingId !== null
                    "
                    @click="openEdit(eWarung)"
                  >
                    <Pencil
                      :size="15"
                    />

                    Edit
                  </button>

                  <button
                    type="button"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border px-3 text-xs font-extrabold transition disabled:cursor-not-allowed disabled:opacity-50"
                    :class="
                      eWarung.is_active
                        ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    "
                    :disabled="
                      statusChangingId !== null
                      ||
                      deletingId !== null
                    "
                    @click="toggleStatus(eWarung)"
                  >
                    <RefreshCw
                      :size="15"
                      :class="{
                        'animate-spin':
                          statusChangingId
                          === eWarung.id,
                      }"
                    />

                    {{
                      eWarung.is_active
                        ? 'Nonaktifkan'
                        : 'Aktifkan'
                    }}
                  </button>

                  <button
                    type="button"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-extrabold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="
                      deletingId !== null
                      ||
                      statusChangingId !== null
                    "
                    @click="deleteEWarung(eWarung)"
                  >
                    <Trash2
                      :size="15"
                    />

                    {{
                      deletingId === eWarung.id
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

    <div
      v-if="formOpen"
      class="fixed inset-0 z-50 grid place-items-center bg-slate-950/45 p-4 backdrop-blur-sm"
      @click.self="closeForm"
    >
      <section
        class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white shadow-2xl"
      >
        <header
          class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 sm:p-6"
        >
          <div>
            <span
              class="text-xs font-extrabold uppercase tracking-[0.16em] text-orange-600"
            >
              {{
                isEditing
                  ? 'Perbarui Master'
                  : 'Master Baru'
              }}
            </span>

            <h2
              class="mt-1 text-xl font-black text-slate-900"
            >
              {{
                isEditing
                  ? 'Edit E-Warung'
                  : 'Tambah E-Warung'
              }}
            </h2>
          </div>

          <button
            type="button"
            class="grid size-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-50 disabled:opacity-50"
            :disabled="saving"
            aria-label="Tutup form"
            @click="closeForm"
          >
            <X
              :size="19"
            />
          </button>
        </header>

        <form
          class="p-5 sm:p-6"
          @submit.prevent="saveEWarung"
        >
          <div
            v-if="errorMessage"
            class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
            role="alert"
          >
            {{ errorMessage }}
          </div>

          <label class="block">
            <span
              class="text-sm font-extrabold text-slate-700"
            >
              Nama E-Warung
            </span>

            <input
              v-model="form.name"
              type="text"
              maxlength="150"
              autocomplete="off"
              placeholder="Contoh: E-WAROENG ANGGREK SURODINAWAN"
              class="mt-2 min-h-11 w-full rounded-xl border bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition focus:ring-4"
              :class="
                firstFieldError('name')
                  ? 'border-red-300 focus:border-red-400 focus:ring-red-50'
                  : 'border-slate-200 focus:border-orange-400 focus:ring-orange-50'
              "
            />

            <span
              v-if="firstFieldError('name')"
              class="mt-1.5 block text-xs font-bold text-red-600"
            >
              {{ firstFieldError('name') }}
            </span>
          </label>

          <p
            class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs font-semibold leading-5 text-slate-500"
          >
            Perhatikan Dalam Pembuatan Nama E-Warung 
          </p>

          <div
            class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
          >
            <button
              type="button"
              class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
              :disabled="saving"
              @click="closeForm"
            >
              Batal
            </button>

            <button
              type="submit"
              class="inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-5 text-sm font-extrabold text-white transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="saving"
            >
              {{
                saving
                  ? 'Menyimpan...'
                  : isEditing
                    ? 'Simpan Perubahan'
                    : 'Tambah E-Warung'
              }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>