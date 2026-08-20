<script setup lang="ts">
import axios from 'axios'

import {
  Eye,
  EyeOff,
  Pencil,
  RefreshCw,
  Search,
  UserCheck,
  UserPlus,
  Users,
  UserX,
  X,
} from '@lucide/vue'

import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'

import {
  surveyorService,
} from '@/services/surveyorService'

import type {
  Surveyor,
  SurveyorCreatePayload,
  SurveyorMeta,
  SurveyorUpdatePayload,
} from '@/types/surveyor'

type StatusFilter =
  | 'all'
  | 'active'
  | 'inactive'

type FormErrors =
  Record<string, string[]>

interface SurveyorFormState {
  name: string
  username: string
  email: string
  phone: string
  password: string
  password_confirmation: string
}

const surveyors =
  ref<Surveyor[]>([])

const meta =
  ref<SurveyorMeta>({
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

const editingSurveyor =
  ref<Surveyor | null>(
    null,
  )

const showPassword =
  ref(false)

const showPasswordConfirmation =
  ref(false)

const formErrors =
  ref<FormErrors>({})

const form =
  reactive<SurveyorFormState>({
    name: '',
    username: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  })

const isEditing =
  computed(
    () =>
      editingSurveyor.value
      !== null,
  )

const filteredSurveyors =
  computed(() => {
    const query =
      searchQuery.value
        .trim()
        .toLocaleLowerCase(
          'id-ID',
        )

    return surveyors.value
      .filter(
        (
          surveyor,
        ) => {
          if (
            statusFilter.value
            === 'active'
            &&
            !surveyor.is_active
          ) {
            return false
          }

          if (
            statusFilter.value
            === 'inactive'
            &&
            surveyor.is_active
          ) {
            return false
          }

          if (!query) {
            return true
          }

          return [
            surveyor.name,
            surveyor.username,
            surveyor.email ?? '',
            surveyor.phone ?? '',
          ]
            .join(' ')
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
  form.username = ''
  form.email = ''
  form.phone = ''
  form.password = ''
  form.password_confirmation =
    ''

  formErrors.value = {}

  showPassword.value =
    false

  showPasswordConfirmation.value =
    false
}

function openCreate():
  void {
  resetMessages()
  resetForm()

  editingSurveyor.value =
    null

  formOpen.value =
    true
}

function openEdit(
  surveyor: Surveyor,
): void {
  resetMessages()
  resetForm()

  editingSurveyor.value =
    surveyor

  form.name =
    surveyor.name

  form.username =
    surveyor.username

  form.email =
    surveyor.email ?? ''

  form.phone =
    surveyor.phone ?? ''

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

  editingSurveyor.value =
    null

  resetForm()
}

function nullableValue(
  value: string,
): string | null {
  const normalized =
    value.trim()

  return normalized === ''
    ? null
    : normalized
}

function firstFieldError(
  field: string,
): string | null {
  return formErrors
    .value[
      field
    ]?.[0]
    ?? null
}

function formatLastLogin(
  value: string | null,
): string {
  if (!value) {
    return 'Belum pernah'
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

async function loadSurveyors():
  Promise<void> {
  loading.value =
    true

  errorMessage.value =
    ''

  try {
    const response =
      await surveyorService
        .getAll()

    surveyors.value =
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
        'Data Surveyor gagal dimuat.'
    } else {
      errorMessage.value =
        'Data Surveyor gagal dimuat.'
    }
  } finally {
    loading.value =
      false
  }
}

async function saveSurveyor():
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
      editingSurveyor.value
    ) {
      const payload:
        SurveyorUpdatePayload = {
          name:
            form.name,

          username:
            form.username,

          email:
            nullableValue(
              form.email,
            ),

          phone:
            nullableValue(
              form.phone,
            ),
        }

      if (
        form.password
        !== ''
      ) {
        payload.password =
          form.password

        payload.password_confirmation =
          form
            .password_confirmation
      }

      const response =
        await surveyorService
          .update(
            editingSurveyor
              .value
              .id,

            payload,
          )

      successMessage.value =
        response.message
    } else {
      const payload:
        SurveyorCreatePayload = {
          name:
            form.name,

          username:
            form.username,

          email:
            nullableValue(
              form.email,
            ),

          phone:
            nullableValue(
              form.phone,
            ),

          password:
            form.password,

          password_confirmation:
            form
              .password_confirmation,
        }

      const response =
        await surveyorService
          .create(
            payload,
          )

      successMessage.value =
        response.message
    }

    formOpen.value =
      false

    editingSurveyor.value =
      null

    resetForm()

    await loadSurveyors()
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
        'Data Surveyor gagal disimpan.'
    } else {
      errorMessage.value =
        'Data Surveyor gagal disimpan.'
    }
  } finally {
    saving.value =
      false
  }
}

async function toggleStatus(
  surveyor: Surveyor,
): Promise<void> {
  if (
    statusChangingId.value
    !== null
  ) {
    return
  }

  const nextStatus =
    !surveyor.is_active

  const action =
    nextStatus
      ? 'mengaktifkan'
      : 'menonaktifkan'

  const confirmed =
    window.confirm(
      `Yakin ingin ${action} akun ${surveyor.name}?`,
    )

  if (!confirmed) {
    return
  }

  statusChangingId.value =
    surveyor.id

  resetMessages()

  try {
    const response =
      await surveyorService
        .setStatus(
          surveyor.id,
          nextStatus,
        )

    successMessage.value =
      response.message

    await loadSurveyors()
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
        'Status Surveyor gagal diubah.'
    } else {
      errorMessage.value =
        'Status Surveyor gagal diubah.'
    }
  } finally {
    statusChangingId.value =
      null
  }
}

onMounted(() => {
  void loadSurveyors()
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
            class="text-xs font-extrabold uppercase tracking-[0.16em] text-brand-600"
          >
            Pengguna Lapangan
          </span>

          <h1
            class="mt-1 text-2xl font-black text-slate-900"
          >
            Akun Surveyor
          </h1>

          <p
            class="mt-2 max-w-3xl text-sm leading-6 text-slate-500"
          >
            Kelola akun petugas Surveyor.
            Akun yang sudah tidak bertugas
            cukup dinonaktifkan dan tidak
            dihapus agar histori tetap aman.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 text-sm font-extrabold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="
            loading
            ||
            saving
          "
          @click="openCreate"
        >
          <UserPlus
            :size="18"
          />

          Tambah Surveyor
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
            <Users
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
            <UserCheck
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
            <UserX
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
            placeholder="Cari nama, username, email, atau nomor HP"
            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
          />
        </label>

        <select
          v-model="statusFilter"
          class="min-h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
        >
          <option
            value="all"
          >
            Semua Status
          </option>

          <option
            value="active"
          >
            Aktif
          </option>

          <option
            value="inactive"
          >
            Nonaktif
          </option>
        </select>

        <button
          type="button"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="loadSurveyors"
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
          surveyors.length
            === 0
        "
        class="p-8 text-center text-sm font-semibold text-slate-500"
      >
        Memuat data Surveyor...
      </div>

      <div
        v-else-if="
          filteredSurveyors.length
          === 0
        "
        class="p-8 text-center"
      >
        <Users
          class="mx-auto text-slate-300"
          :size="36"
        />

        <strong
          class="mt-3 block text-sm font-black text-slate-700"
        >
          Tidak ada Surveyor
          yang sesuai
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
          <thead
            class="bg-slate-50"
          >
            <tr>
              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Surveyor
              </th>

              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Kontak
              </th>

              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Login Terakhir
              </th>

              <th
                class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500"
              >
                Status
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
                surveyor
                in filteredSurveyors
              "
              :key="
                surveyor.id
              "
              class="align-top transition hover:bg-slate-50/70"
            >
              <td
                class="px-5 py-4"
              >
                <strong
                  class="block text-sm font-black text-slate-900"
                >
                  {{
                    surveyor.name
                  }}
                </strong>

                <span
                  class="mt-1 block text-xs font-semibold text-slate-500"
                >
                  @{{
                    surveyor.username
                  }}
                </span>
              </td>

              <td
                class="px-5 py-4 text-sm text-slate-600"
              >
                <span
                  class="block"
                >
                  {{
                    surveyor.phone
                    || '—'
                  }}
                </span>

                <span
                  class="mt-1 block text-xs text-slate-400"
                >
                  {{
                    surveyor.email
                    || 'Email tidak diisi'
                  }}
                </span>
              </td>

              <td
                class="px-5 py-4 text-sm font-semibold text-slate-600"
              >
                {{
                  formatLastLogin(
                    surveyor
                      .last_login_at,
                  )
                }}
              </td>

              <td
                class="px-5 py-4"
              >
                <span
                  class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold"
                  :class="
                    surveyor.is_active
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-slate-100 text-slate-600'
                  "
                >
                  {{
                    surveyor.is_active
                      ? 'Aktif'
                      : 'Nonaktif'
                  }}
                </span>
              </td>

              <td
                class="px-5 py-4"
              >
                <div
                  class="flex justify-end gap-2"
                >
                  <button
                    type="button"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-xs font-extrabold text-slate-700 transition hover:bg-slate-50"
                    @click="
                      openEdit(
                        surveyor,
                      )
                    "
                  >
                    <Pencil
                      :size="14"
                    />

                    Edit
                  </button>

                  <button
                    type="button"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-3 text-xs font-extrabold transition disabled:cursor-not-allowed disabled:opacity-60"
                    :class="
                      surveyor.is_active
                        ? 'bg-rose-50 text-rose-700 hover:bg-rose-100'
                        : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    "
                    :disabled="
                      statusChangingId
                      !== null
                    "
                    @click="
                      toggleStatus(
                        surveyor,
                      )
                    "
                  >
                    <UserX
                      v-if="
                        surveyor
                          .is_active
                      "
                      :size="14"
                    />

                    <UserCheck
                      v-else
                      :size="14"
                    />

                    {{
                      surveyor.is_active
                        ? 'Nonaktifkan'
                        : 'Aktifkan'
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
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
      @click.self="
        closeForm
      "
    >
      <section
        class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl"
      >
        <header
          class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 sm:p-6"
        >
          <div>
            <span
              class="text-xs font-extrabold uppercase tracking-[0.14em] text-brand-600"
            >
              {{
                isEditing
                  ? 'Edit Akun'
                  : 'Akun Baru'
              }}
            </span>

            <h2
              class="mt-1 text-xl font-black text-slate-900"
            >
              {{
                isEditing
                  ? 'Edit Surveyor'
                  : 'Tambah Surveyor'
              }}
            </h2>
          </div>

          <button
            type="button"
            class="grid size-10 place-items-center rounded-xl text-slate-500 transition hover:bg-slate-100"
            :disabled="saving"
            aria-label="Tutup"
            @click="closeForm"
          >
            <X
              :size="20"
            />
          </button>
        </header>

        <form
          class="p-5 sm:p-6"
          @submit.prevent="
            saveSurveyor
          "
        >
          <div
            v-if="errorMessage"
            class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
            role="alert"
          >
            {{ errorMessage }}
          </div>

          <div
            class="grid gap-5 sm:grid-cols-2"
          >
            <label
              class="block sm:col-span-2"
            >
              <span
                class="text-sm font-extrabold text-slate-700"
              >
                Nama Surveyor
              </span>

              <input
                v-model="form.name"
                type="text"
                maxlength="150"
                autocomplete="name"
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
              />

              <span
                v-if="
                  firstFieldError(
                    'name',
                  )
                "
                class="mt-1 block text-xs font-semibold text-red-600"
              >
                {{
                  firstFieldError(
                    'name',
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
                Username
              </span>

              <input
                v-model="
                  form.username
                "
                type="text"
                maxlength="60"
                autocomplete="username"
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
              />

              <span
                v-if="
                  firstFieldError(
                    'username',
                  )
                "
                class="mt-1 block text-xs font-semibold text-red-600"
              >
                {{
                  firstFieldError(
                    'username',
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
                Nomor HP
              </span>

              <input
                v-model="
                  form.phone
                "
                type="tel"
                maxlength="20"
                autocomplete="tel"
                placeholder="Contoh: 081234567890"
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
              />

              <span
                v-if="
                  firstFieldError(
                    'phone',
                  )
                "
                class="mt-1 block text-xs font-semibold text-red-600"
              >
                {{
                  firstFieldError(
                    'phone',
                  )
                }}
              </span>
            </label>

            <label
              class="block sm:col-span-2"
            >
              <span
                class="text-sm font-extrabold text-slate-700"
              >
                Email

                <span
                  class="font-semibold text-slate-400"
                >
                  (opsional)
                </span>
              </span>

              <input
                v-model="
                  form.email
                "
                type="email"
                maxlength="150"
                autocomplete="email"
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
              />

              <span
                v-if="
                  firstFieldError(
                    'email',
                  )
                "
                class="mt-1 block text-xs font-semibold text-red-600"
              >
                {{
                  firstFieldError(
                    'email',
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
                {{
                  isEditing
                    ? 'Kata Sandi Baru'
                    : 'Kata Sandi Awal'
                }}

                <span
                  v-if="isEditing"
                  class="font-semibold text-slate-400"
                >
                  (opsional)
                </span>
              </span>

              <div
                class="relative mt-2"
              >
                <input
                  v-model="
                    form.password
                  "
                  :type="
                    showPassword
                      ? 'text'
                      : 'password'
                  "
                  autocomplete="new-password"
                  class="min-h-11 w-full rounded-xl border border-slate-200 pl-3 pr-11 text-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
                />

                <button
                  type="button"
                  class="absolute right-1 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100"
                  @click="
                    showPassword =
                      !showPassword
                  "
                >
                  <EyeOff
                    v-if="
                      showPassword
                    "
                    :size="17"
                  />

                  <Eye
                    v-else
                    :size="17"
                  />
                </button>
              </div>

              <span
                class="mt-1 block text-xs text-slate-400"
              >
                Minimal 8 karakter.
              </span>

              <span
                v-if="
                  firstFieldError(
                    'password',
                  )
                "
                class="mt-1 block text-xs font-semibold text-red-600"
              >
                {{
                  firstFieldError(
                    'password',
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
                Konfirmasi Kata Sandi
              </span>

              <div
                class="relative mt-2"
              >
                <input
                  v-model="
                    form
                      .password_confirmation
                  "
                  :type="
                    showPasswordConfirmation
                      ? 'text'
                      : 'password'
                  "
                  autocomplete="new-password"
                  class="min-h-11 w-full rounded-xl border border-slate-200 pl-3 pr-11 text-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-50"
                />

                <button
                  type="button"
                  class="absolute right-1 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100"
                  @click="
                    showPasswordConfirmation =
                      !showPasswordConfirmation
                  "
                >
                  <EyeOff
                    v-if="
                      showPasswordConfirmation
                    "
                    :size="17"
                  />

                  <Eye
                    v-else
                    :size="17"
                  />
                </button>
              </div>
            </label>
          </div>

          <div
            class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"
          >
            <button
              type="button"
              class="min-h-11 rounded-xl border border-slate-200 px-5 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
              :disabled="saving"
              @click="closeForm"
            >
              Batal
            </button>

            <button
              type="submit"
              class="min-h-11 rounded-xl bg-brand-600 px-5 text-sm font-extrabold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="saving"
            >
              {{
                saving
                  ? 'Menyimpan...'
                  : (
                    isEditing
                      ? 'Simpan Perubahan'
                      : 'Tambah Surveyor'
                  )
              }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>