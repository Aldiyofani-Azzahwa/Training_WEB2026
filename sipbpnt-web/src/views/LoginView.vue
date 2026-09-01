<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import { useAuthStore } from '@/stores/auth'
import { publicSite } from '@/config/publicSite'
import berasTelurImg from '@/assets/images/beras-telur.jpg'

import type {
  LoginPayload,
  ValidationErrorResponse,
} from '@/types/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const showPassword = ref(false)

const usernameError = ref('')
const passwordError = ref('')
const formStatus = ref('')
const formStatusType = ref<'error' | 'info' | ''>('')

const form = reactive<LoginPayload>({
  username: '',
  password: '',
  remember: false,
})

function clearFieldErrors(): void {
  usernameError.value = ''
  passwordError.value = ''
}

function validateForm(): boolean {
  clearFieldErrors()

  let valid = true

  const username = form.username.trim()
  const password = form.password.trim()

  if (!username) {
    usernameError.value = 'Username atau email wajib diisi.'
    valid = false
  }

  if (!password) {
    passwordError.value = 'Kata sandi wajib diisi.'
    valid = false
  }

  return valid
}

async function submitLogin(): Promise<void> {
  formStatus.value = ''
  formStatusType.value = ''

  if (!validateForm()) {
    formStatus.value = 'Periksa kembali data yang Anda masukkan.'
    formStatusType.value = 'error'
    return
  }

  try {
    await authStore.login(form)

    const redirect =
      typeof route.query.redirect === 'string'
        ? route.query.redirect
        : '/dashboard'

    await router.replace(redirect)
  } catch (error) {
    if (axios.isAxiosError<ValidationErrorResponse>(error)) {
      const backendUsernameError =
        error.response?.data.errors?.username?.[0]

      formStatus.value =
        backendUsernameError ??
        error.response?.data.message ??
        'Login gagal. Periksa kembali data Anda.'
      formStatusType.value = 'error'

      return
    }

    formStatus.value = 'Tidak dapat terhubung ke server.'
    formStatusType.value = 'error'
  }
}

function handleForgotPassword(): void {
  formStatus.value =
    'Silakan hubungi administrator untuk reset kata sandi.'
  formStatusType.value = 'info'
}
</script>

<template>
  <main class="login-page">
    <!-- =========================
         BRANDING (desktop only)
    ========================== -->
    <section class="branding-section">
      <div class="branding-content">
        <RouterLink to="/">
          <img
            :src="publicSite.logoPath"
            :alt="`Logo ${publicSite.name}`"
            class="logo desktop-logo"
          />
        </RouterLink>

        <div class="illustration">
          <div class="illustration-circle circle-one" />
          <div class="illustration-circle circle-two" />
          <div class="illustration-circle circle-three" />

          <img
            :src="berasTelurImg"
            alt="Ilustrasi beras dan telur"
            class="illustration-image"
          />
        </div>
      </div>

      <div class="bottom-wave">
        <div class="orange-wave" />
        <div class="green-wave" />
      </div>
    </section>

    <!-- =========================
         LOGIN
    ========================== -->
    <section class="login-section">
      <!-- Mobile decorative background -->
      <div class="mobile-decoration">
        <div class="mobile-orange-wave" />
        <div class="mobile-green-wave" />

        <div class="mobile-circle circle-a" />
        <div class="mobile-circle circle-b" />
      </div>

      <div class="login-container">
        <!-- Mobile logo -->
        <RouterLink to="/">
          <img
            :src="publicSite.logoPath"
            :alt="`Logo ${publicSite.name}`"
            class="logo mobile-logo"
          />
        </RouterLink>

        <div class="login-header">
          <h1>Masuk ke Akun Anda</h1>
          <p>Silakan masuk untuk melanjutkan</p>
        </div>

        <form novalidate @submit.prevent="submitLogin">
          <!-- Username -->
          <div class="form-group">
            <div class="input-wrapper">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M20 21a8 8 0 0 0-16 0" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
              </span>

              <input
                id="username"
                v-model.trim="form.username"
                type="text"
                name="username"
                placeholder="Username atau Email"
                autocomplete="username"
                required
                autofocus
                @input="usernameError = ''"
              />
            </div>

            <small class="error-message">{{ usernameError }}</small>
          </div>

          <!-- Password -->
          <div class="form-group">
            <div class="input-wrapper">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <rect x="4" y="10" width="16" height="11" rx="2" />
                  <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                </svg>
              </span>

              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                name="password"
                placeholder="Kata Sandi"
                autocomplete="current-password"
                required
                @input="passwordError = ''"
              />

              <button
                type="button"
                class="password-toggle"
                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                @click="showPassword = !showPassword"
              >
                <svg
                  v-if="!showPassword"
                  class="eye-open"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                  <circle cx="12" cy="12" r="2.5" />
                </svg>

                <svg
                  v-else
                  class="eye-closed"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M3 3l18 18" />
                  <path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 3.8" />
                  <path d="M6.2 6.2C3.6 8 2 12 2 12s3.5 7 10 7c1.8 0 3.3-.5 4.6-1.2" />
                </svg>
              </button>
            </div>

            <small class="error-message">{{ passwordError }}</small>
          </div>

          <!-- Options -->
          <div class="form-options">
            <label class="remember">
              <input v-model="form.remember" type="checkbox" name="remember" />
              <span class="custom-checkbox" />
              <span>Ingat saya</span>
            </label>

            <button type="button" class="forgot-password" @click="handleForgotPassword">
              Lupa kata sandi?
            </button>
          </div>

          <!-- Login -->
          <button
            type="submit"
            class="login-button"
            :disabled="authStore.loading"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M15 8l4 4-4 4" />
              <path d="M19 12H9" />
              <path d="M12 5V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h5a2 2 0 0 0 2-2v-1" />
            </svg>

            <span>{{ authStore.loading ? 'Memproses...' : 'Masuk' }}</span>
          </button>

          <p
            v-if="formStatus"
            class="form-status"
            :class="formStatusType"
            role="alert"
            aria-live="polite"
          >
            {{ formStatus }}
          </p>
        </form>

        <RouterLink to="/" class="back-home">
          Kembali ke halaman utama
        </RouterLink>

        <footer class="login-footer">
          © 2026 SIPBPNT. All rights reserved.
        </footer>
      </div>
    </section>
  </main>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

:root {
  --orange: #ff9800;
  --orange-dark: #ed8200;
  --orange-light: #fff5e5;

  --green: #087d69;
  --green-dark: #056453;

  --text: #124640;
  --text-light: #727b80;

  --border: #e1e5e4;
}

/* =========================================
   LAYOUT
========================================= */

.login-page {
  min-height: 100vh;

  display: grid;
  grid-template-columns: 1fr 1fr;

  background: #fff;
  color: var(--text);
  font-family: Inter, 'Segoe UI', Roboto, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
}

.login-page button,
.login-page input {
  font: inherit;
}

/* =========================================
   BRANDING
========================================= */

.branding-section {
  position: relative;

  min-height: 100vh;

  overflow: hidden;

  background: linear-gradient(145deg, #ffffff 0%, #fbfcfa 65%, #f1f6ef 100%);

  display: flex;
  justify-content: center;
  align-items: center;
}

.branding-content {
  position: relative;
  z-index: 5;

  width: min(680px, 90%);

  display: flex;
  flex-direction: column;
  align-items: center;

  margin-bottom: 80px;
}

/* =========================================
   LOGO
========================================= */

.logo {
  display: block;
  object-fit: contain;
}

.desktop-logo {
  width: min(280px, 80%);
  margin-bottom: 50px;
}

.mobile-logo {
  display: none;
}

/* =========================================
   ILLUSTRATION
========================================= */

.illustration {
  position: relative;

  width: 480px;
  max-width: 90%;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 40px 0;
}

.illustration-circle {
  position: absolute;
  border-radius: 50%;
  z-index: 1;
}

.circle-one {
  width: 80px;
  height: 80px;
  left: 0;
  top: 20px;
  background: rgba(255, 167, 28, 0.13);
}

.circle-two {
  width: 65px;
  height: 65px;
  right: 20px;
  top: 15px;
  background: rgba(255, 167, 28, 0.12);
}

.circle-three {
  width: 110px;
  height: 110px;
  right: 65px;
  bottom: 15px;
  background: rgba(119, 167, 116, 0.08);
}

.illustration-image {
  position: relative;
  z-index: 3;

  width: 100%;
  max-width: 440px;

  border-radius: 28px;

  box-shadow: 0 25px 45px rgba(40, 70, 66, 0.16);

  object-fit: cover;
}

/* =========================================
   WAVES
========================================= */

.bottom-wave {
  position: absolute;

  left: 0;
  right: 0;
  bottom: 0;

  height: 190px;

  overflow: hidden;
}

.orange-wave {
  position: absolute;

  width: 125%;
  height: 120px;

  left: -10%;
  bottom: 65px;

  background: var(--orange);

  border-radius: 50% 50% 0 0 / 30% 30% 0 0;

  transform: rotate(3deg);
}

.green-wave {
  position: absolute;

  width: 125%;
  height: 125px;

  left: -12%;
  bottom: -25px;

  background: #007661;

  border-radius: 55% 55% 0 0 / 45% 45% 0 0;

  transform: rotate(-4deg);
}

/* =========================================
   LOGIN
========================================= */

.login-section {
  position: relative;

  min-height: 100vh;

  display: flex;
  flex-direction: column;

  justify-content: center;
  align-items: center;

  padding: 50px 70px;

  background: #fff;

  overflow: hidden;
}

.login-container {
  position: relative;
  z-index: 5;

  width: 100%;
  max-width: 500px;
}

/* =========================================
   HEADER
========================================= */

.login-header {
  margin-bottom: 35px;
}

.login-header h1 {
  color: var(--text);

  font-size: clamp(28px, 2.3vw, 36px);
  font-weight: 750;

  line-height: 1.25;

  letter-spacing: -0.7px;

  margin-bottom: 9px;
}

.login-header p {
  color: var(--text-light);
  font-size: 16px;
}

/* =========================================
   FORM
========================================= */

.form-group {
  margin-bottom: 18px;
}

.input-wrapper {
  position: relative;
  width: 100%;
}

.input-wrapper input {
  width: 100%;
  height: 56px;

  padding: 0 52px 0 48px;

  border: 1px solid var(--border);

  border-radius: 12px;

  outline: none;

  color: #454c52;

  background: #fff;

  font-size: 15.5px;

  box-shadow: 0 2px 7px rgba(20, 40, 38, 0.04);

  transition: 0.2s ease;
}

.input-wrapper input::placeholder {
  color: #7d858a;
}

.input-wrapper input:focus {
  border-color: var(--orange);
  box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.11);
}

/* =========================================
   ICON
========================================= */

.input-icon {
  position: absolute;

  left: 16px;
  top: 50%;

  width: 21px;
  height: 21px;

  transform: translateY(-50%);

  color: #7c8789;

  pointer-events: none;
}

.input-icon svg {
  width: 100%;
  height: 100%;
}

/* =========================================
   PASSWORD
========================================= */

.password-toggle {
  position: absolute;

  right: 13px;
  top: 50%;

  width: 30px;
  height: 30px;

  transform: translateY(-50%);

  display: flex;
  align-items: center;
  justify-content: center;

  border: none;

  border-radius: 6px;

  background: transparent;

  color: #7c8789;

  cursor: pointer;
}

.password-toggle:hover {
  color: var(--orange);
  background: var(--orange-light);
}

.password-toggle svg {
  width: 19px;
  height: 19px;
}

/* =========================================
   ERROR
========================================= */

.error-message {
  display: block;

  min-height: 15px;

  margin-top: 5px;
  margin-left: 3px;

  color: #d74c4c;

  font-size: 12px;
}

/* =========================================
   OPTIONS
========================================= */

.form-options {
  display: flex;

  justify-content: space-between;
  align-items: center;

  margin: 2px 0 27px;
}

.remember {
  display: flex;
  align-items: center;

  gap: 8px;

  color: #60696d;

  font-size: 14px;

  cursor: pointer;
}

.remember input {
  position: absolute;
  opacity: 0;
}

.custom-checkbox {
  width: 18px;
  height: 18px;

  border: 1.5px solid #aeb8b7;

  border-radius: 3px;

  background: #fff;

  position: relative;
}

.remember input:checked + .custom-checkbox {
  background: var(--orange);
  border-color: var(--orange);
}

.remember input:checked + .custom-checkbox::after {
  content: '';

  position: absolute;

  width: 5px;
  height: 9px;

  left: 6px;
  top: 2px;

  border: solid #fff;

  border-width: 0 2px 2px 0;

  transform: rotate(45deg);
}

.forgot-password {
  border: none;

  background: transparent;

  color: var(--orange-dark);

  font-size: 14px;
  font-weight: 650;

  cursor: pointer;
}

.forgot-password:hover {
  text-decoration: underline;
}

/* =========================================
   LOGIN BUTTON
========================================= */

.login-button {
  width: 100%;
  height: 56px;

  border: none;

  border-radius: 12px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 9px;

  background: linear-gradient(135deg, #ff9f00, #f58700);

  color: #fff;

  font-size: 15.5px;
  font-weight: 650;

  cursor: pointer;

  box-shadow: 0 8px 20px rgba(255, 145, 0, 0.2);

  transition: 0.2s ease;
}

.login-button svg {
  width: 19px;
  height: 19px;
}

.login-button:hover:not(:disabled) {
  background: linear-gradient(135deg, #f28b00, #df7200);
  box-shadow: 0 10px 25px rgba(255, 145, 0, 0.28);
}

.login-button:active:not(:disabled) {
  transform: translateY(1px);
}

.login-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* =========================================
   STATUS
========================================= */

.form-status {
  min-height: 20px;

  margin-top: 15px;

  text-align: center;

  font-size: 13px;
}

.form-status.success {
  color: #16805e;
}

.form-status.error {
  color: #d74c4c;
}

.form-status.info {
  color: #657174;
}

/* =========================================
   BACK HOME LINK
========================================= */

.back-home {
  display: block;

  margin-top: 26px;

  text-align: center;

  color: var(--text-light);

  font-size: 14px;
  font-weight: 650;

  text-decoration: none;

  transition: 0.2s ease;
}

.back-home:hover {
  color: var(--orange-dark);
}

/* =========================================
   FOOTER
========================================= */

.login-footer {
  position: relative;
  z-index: 5;

  margin-top: auto;

  padding-top: 45px;

  color: #737b7e;

  font-size: 13px;

  text-align: center;
}

/* =========================================
   MOBILE DECORATION
========================================= */

.mobile-decoration {
  display: none;
}

/* =========================================
   TABLET
========================================= */

@media (max-width: 1050px) {
  .login-page {
    grid-template-columns: 43% 57%;
  }

  .login-section {
    padding: 40px 45px;
  }

  .desktop-logo {
    width: 88%;
  }

  .illustration {
    transform: scale(0.85);
  }
}

/* =========================================
   MOBILE
========================================= */

@media (max-width: 768px) {
  .login-page {
    display: block;
    min-height: 100dvh;
  }

  .branding-section {
    display: none;
  }

  .login-section {
    min-height: 100dvh;

    display: flex;
    flex-direction: column;

    justify-content: flex-start;

    padding: 0 20px 22px;

    background: linear-gradient(180deg, #fff 0%, #fff 72%, #fff9f0 100%);
  }

  .mobile-decoration {
    display: block;

    position: absolute;

    left: 0;
    right: 0;
    bottom: 0;

    height: 175px;

    overflow: hidden;

    pointer-events: none;

    z-index: 1;
  }

  .mobile-orange-wave {
    position: absolute;

    width: 125%;
    height: 95px;

    left: -12%;
    bottom: 55px;

    background: var(--orange);

    border-radius: 50% 50% 0 0 / 45% 45% 0 0;

    transform: rotate(-3deg);
  }

  .mobile-green-wave {
    position: absolute;

    width: 125%;
    height: 100px;

    left: -10%;
    bottom: -35px;

    background: var(--green);

    border-radius: 50% 50% 0 0 / 45% 45% 0 0;

    transform: rotate(3deg);
  }

  .mobile-circle {
    position: absolute;
    border-radius: 50%;
  }

  .circle-a {
    width: 75px;
    height: 75px;
    right: -20px;
    bottom: 100px;
    background: rgba(255, 152, 0, 0.1);
  }

  .circle-b {
    width: 45px;
    height: 45px;
    left: 15px;
    bottom: 70px;
    background: rgba(8, 125, 105, 0.08);
  }

  .login-container {
    position: relative;
    z-index: 5;

    width: 100%;
    max-width: 460px;

    margin: 0 auto;
  }

  .mobile-logo {
    display: block;

    width: min(190px, 70%);
    height: auto;

    margin: 32px auto 20px;
  }

  .login-header {
    margin-bottom: 27px;

    text-align: center;
  }

  .login-header h1 {
    font-size: 22px;
    letter-spacing: -0.5px;
  }

  .login-header p {
    font-size: 14px;
    margin-top: 7px;
  }

  .input-wrapper input {
    height: 54px;
    font-size: 14.5px;
    border-radius: 11px;
  }

  .form-group {
    margin-bottom: 17px;
  }

  .form-options {
    margin-bottom: 23px;
  }

  .remember {
    font-size: 13px;
  }

  .forgot-password {
    font-size: 13px;
  }

  .login-button {
    height: 54px;
    border-radius: 11px;
  }

  .login-footer {
    position: relative;
    z-index: 6;

    margin-top: auto;

    padding-top: 35px;

    font-size: 11px;

    color: #737b7e;
  }
}

/* =========================================
   SMALL MOBILE
========================================= */

@media (max-width: 400px) {
  .login-section {
    padding: 0 16px 17px;
  }

  .mobile-logo {
    width: 185px;
    margin: 25px auto 35px;
  }

  .login-header {
    margin-bottom: 24px;
  }

  .login-header h1 {
    font-size: 23px;
  }

  .login-header p {
    font-size: 13.5px;
  }

  .input-wrapper input {
    height: 51px;
  }

  .login-button {
    height: 51px;
  }

  .mobile-decoration {
    height: 145px;
  }
}

/* =========================================
   EXTRA SMALL
========================================= */

@media (max-width: 330px) {
  .mobile-logo {
    width: 165px;
    margin-bottom: 30px;
  }

  .form-options {
    align-items: flex-start;
    gap: 8px;
    flex-direction: column;
  }

  .forgot-password {
    padding: 0;
  }

  .login-footer {
    font-size: 10px;
  }
}
</style>