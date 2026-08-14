<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import { useAuthStore } from '@/stores/auth'

import type {
  LoginPayload,
  ValidationErrorResponse,
} from '@/types/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const showPassword = ref(false)
const errorMessage = ref('')

const form = reactive<LoginPayload>({
  username: '',
  password: '',
  remember: false,
})

async function submitLogin(): Promise<void> {
  errorMessage.value = ''

  try {
    await authStore.login(form)

    const redirect =
      typeof route.query.redirect === 'string'
        ? route.query.redirect
        : '/dashboard'

    await router.replace(redirect)
  } catch (error) {
    if (
      axios.isAxiosError<ValidationErrorResponse>(
        error,
      )
    ) {
      const usernameError =
        error.response?.data.errors?.username?.[0]

      errorMessage.value =
        usernameError ??
        error.response?.data.message ??
        'Login gagal. Periksa kembali data Anda.'

      return
    }

    errorMessage.value =
      'Tidak dapat terhubung ke server.'
  }
}
</script>

<template>
  <main
    class="grid min-h-screen bg-white lg:grid-cols-[0.9fr_1.1fr]"
  >
    <!-- Introduction -->
    <section
      class="relative flex min-h-80 flex-col overflow-hidden bg-linear-to-br from-brand-500 to-brand-700 px-6 py-7 text-white sm:px-10 lg:min-h-screen lg:px-16 lg:py-10"
    >
      <div
        class="absolute -top-20 -left-20 size-72 rounded-full bg-accent-300/25 blur-3xl"
      />

      <div
        class="absolute right-0 bottom-0 size-80 rounded-full bg-brand-900/20 blur-3xl"
      />

      <RouterLink
        to="/"
        class="relative z-10 flex items-center gap-3 self-start"
      >
        <span
          class="grid size-12 place-items-center rounded-2xl bg-white text-xl font-black text-brand-600 shadow-lg"
        >
          S
        </span>

        <span class="grid leading-tight">
          <strong class="text-lg tracking-wide">
            SIPBPNT
          </strong>

          <small class="mt-0.5 text-xs text-white/75">
            Kota Mojokerto
          </small>
        </span>
      </RouterLink>

      <div
        class="relative z-10 my-auto mt-14 max-w-xl lg:mt-auto"
      >
        <span
          class="inline-flex rounded-full bg-white/15 px-3 py-1.5 text-xs font-extrabold tracking-wider uppercase"
        >
          Sistem internal
        </span>

        <h1
          class="mt-5 text-4xl leading-tight font-black tracking-tight sm:text-5xl lg:text-6xl"
        >
          Pendataan BPNT lebih mudah, cepat, dan
          terintegrasi.
        </h1>

        <p
          class="mt-6 max-w-lg text-base leading-8 text-white/80 lg:text-lg"
        >
          Gunakan akun resmi yang diberikan oleh
          administrator Dinas Sosial Kota Mojokerto.
        </p>

        <div
          class="mt-8 hidden max-w-lg rounded-2xl border border-white/20 bg-white/10 p-5 backdrop-blur lg:grid"
        >
          <strong>Keamanan data</strong>

          <span class="mt-1 text-sm text-white/75">
            Jangan memberikan username dan kata
            sandi kepada pihak lain.
          </span>
        </div>
      </div>
    </section>

    <!-- Login Form -->
    <section
      class="grid min-h-150 place-items-center bg-white px-5 py-14 sm:px-8 lg:min-h-screen"
    >
      <div class="w-full max-w-md">
        <div class="mb-8">
          <span class="text-sm font-extrabold text-brand-600">
            Selamat datang
          </span>

          <h2
            class="mt-1 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl"
          >
            Masuk ke SIPBPNT
          </h2>

          <p class="mt-2 text-slate-500">
            Masukkan username dan kata sandi Anda.
          </p>
        </div>

        <div
          v-if="errorMessage"
          class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"
          role="alert"
          aria-live="polite"
        >
          {{ errorMessage }}
        </div>

        <form
          novalidate
          @submit.prevent="submitLogin"
        >
          <div class="mb-5">
            <label
              for="username"
              class="mb-2 block text-sm font-bold text-slate-700"
            >
              Username
            </label>

            <input
              id="username"
              v-model.trim="form.username"
              name="username"
              type="text"
              inputmode="text"
              autocomplete="username"
              placeholder="Masukkan username"
              required
              autofocus
              class="min-h-13 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-800 placeholder:text-slate-400 transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 focus:outline-none"
            />
          </div>

          <div class="mb-5">
            <label
              for="password"
              class="mb-2 block text-sm font-bold text-slate-700"
            >
              Kata sandi
            </label>

            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                name="password"
                :type="
                  showPassword
                    ? 'text'
                    : 'password'
                "
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                required
                class="min-h-13 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-28 text-base text-slate-800 placeholder:text-slate-400 transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 focus:outline-none"
              />

              <button
                type="button"
                class="absolute top-1/2 right-2 min-h-9 -translate-y-1/2 rounded-lg bg-brand-100 px-3 text-xs font-extrabold text-brand-700 transition hover:bg-brand-200 focus:ring-4 focus:ring-brand-500/15 focus:outline-none"
                :aria-label="
                  showPassword
                    ? 'Sembunyikan kata sandi'
                    : 'Tampilkan kata sandi'
                "
                @click="
                  showPassword = !showPassword
                "
              >
                {{
                  showPassword
                    ? 'Sembunyikan'
                    : 'Lihat'
                }}
              </button>
            </div>
          </div>

          <label
            class="mb-6 inline-flex cursor-pointer items-center gap-3 text-sm font-semibold text-slate-600"
          >
            <input
              v-model="form.remember"
              type="checkbox"
              class="size-4.5 rounded border-slate-300 text-brand-500 accent-brand-500 focus:ring-brand-500"
            />

            <span>Ingat sesi saya</span>
          </label>

          <button
            type="submit"
            :disabled="
              authStore.loading ||
              !form.username ||
              !form.password
            "
            class="inline-flex min-h-13 w-full items-center justify-center rounded-xl bg-brand-500 px-6 py-3 font-bold text-white shadow-lg shadow-brand-500/25 transition hover:-translate-y-0.5 hover:bg-brand-600 focus:ring-4 focus:ring-brand-500/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
          >
            {{
              authStore.loading
                ? 'Memproses...'
                : 'Masuk Sistem'
            }}
          </button>
        </form>

        <RouterLink
          to="/"
          class="mt-7 block text-center text-sm font-bold text-slate-500 transition hover:text-brand-600"
        >
          Kembali ke halaman utama
        </RouterLink>
      </div>
    </section>
  </main>
</template>