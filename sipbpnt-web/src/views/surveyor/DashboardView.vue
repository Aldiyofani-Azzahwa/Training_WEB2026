<script setup lang="ts">
import axios from 'axios'

import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import {
  ArrowRight,
  CalendarDays,
  CircleAlert,
  MapPin,
  RefreshCw,
  ScanLine,
  Store,
  Users,
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

import {
  useAuthStore,
} from '@/stores/auth'

import type {
  ValidationErrorResponse,
} from '@/types/auth'

import type {
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

const authStore =
  useAuthStore()

const {
  activeEWarungs,
  selectedEWarung,
  synchronizeEWarungs,
  selectEWarung,
  clearSelectedEWarung,
} =
  useSurveyorEWarungSelection()

const context =
  ref<SurveyorWorkspaceContext | null>(
    null,
  )

const loading =
  ref(true)

const eWarungsLoading =
  ref(false)

const errorMessage =
  ref('')

const eWarungError =
  ref('')

const firstName =
  computed(() => {
    const name =
      context.value
        ?.surveyor
        .name
        .trim()
      ||
      authStore.user
        ?.name
        .trim()
      ||
      'Surveyor'

    return name
      .split(/\s+/)[0]
  })

const formattedKpmCount =
  computed(() => {
    return new Intl.NumberFormat(
      'id-ID',
    ).format(
      context.value
        ?.kpm_count
      ?? 0,
    )
  })

const isOperational =
  computed(() => {
    return Boolean(
      context.value
        ?.period
      &&
      context.value
        .assignment,
    )
  })

function resolveErrorMessage(
  error: unknown,
  fallback: string,
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
      return 'Akun Anda tidak memiliki akses ke halaman Surveyor.'
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

async function loadEWarungs(
  silent = false,
): Promise<void> {
  if (
    !context.value
    ||
    !isOperational.value
  ) {
    clearSelectedEWarung()

    return
  }

  if (!silent) {
    eWarungsLoading.value =
      true
  }

  eWarungError.value =
    ''

  try {
    const eWarungs =
      await surveyorWorkspaceService
        .getActiveEWarungs()

    synchronizeEWarungs(
      context.value
        .surveyor
        .id,

      eWarungs,
    )
  } catch (
    error: unknown
  ) {
    eWarungError.value =
      resolveErrorMessage(
        error,
        'Daftar E-Warung aktif gagal dimuat.',
      )
  } finally {
    eWarungsLoading.value =
      false
  }
}

async function loadDashboard():
  Promise<void> {
  loading.value =
    true

  errorMessage.value =
    ''

  try {
    context.value =
      await surveyorWorkspaceService
        .getContext()

    if (isOperational.value) {
      await loadEWarungs()
    } else {
      clearSelectedEWarung()
    }
  } catch (
    error: unknown
  ) {
    context.value =
      null

    errorMessage.value =
      resolveErrorMessage(
        error,
        'Dashboard Surveyor gagal dimuat.',
      )
  } finally {
    loading.value =
      false
  }
}

function handleEWarungChange(
  event: Event,
): void {
  if (!context.value) {
    return
  }

  const target =
    event.target

  if (
    !(
      target
      instanceof HTMLSelectElement
    )
  ) {
    return
  }

  const eWarungId =
    Number(
      target.value,
    )

  if (
    !Number.isInteger(
      eWarungId,
    )
    ||
    eWarungId < 1
  ) {
    clearSelectedEWarung()

    return
  }

  selectEWarung(
    context.value
      .surveyor
      .id,

    eWarungId,
  )
}

function refreshWhenVisible():
  void {
  if (
    document.visibilityState
      === 'visible'
    &&
    isOperational.value
  ) {
    void loadEWarungs(
      true,
    )
  }
}

function refreshWhenFocused():
  void {
  if (isOperational.value) {
    void loadEWarungs(
      true,
    )
  }
}

onMounted(() => {
  void loadDashboard()

  document.addEventListener(
    'visibilitychange',
    refreshWhenVisible,
  )

  window.addEventListener(
    'focus',
    refreshWhenFocused,
  )
})

onBeforeUnmount(() => {
  document.removeEventListener(
    'visibilitychange',
    refreshWhenVisible,
  )

  window.removeEventListener(
    'focus',
    refreshWhenFocused,
  )
})
</script>

<template>
  <section class="dashboard-page">
    <header class="welcome-copy">
      <span class="eyebrow">
        Beranda Surveyor
      </span>

      <h1>
        Halo, {{ firstName }}
      </h1>

      <p>
        Informasi periode, wilayah tugas,
        dan tempat bertugas Anda.
      </p>
    </header>

    <div
      v-if="loading"
      class="loading-state"
      aria-live="polite"
      data-testid="dashboard-loading"
    >
      <div class="skeleton skeleton-title" />
      <div class="skeleton skeleton-card" />

      <span class="sr-only">
        Memuat dashboard Surveyor
      </span>
    </div>

    <article
      v-else-if="errorMessage"
      class="state-card error-state"
      role="alert"
      data-testid="dashboard-error"
    >
      <CircleAlert
        :size="30"
        :stroke-width="1.9"
      />

      <strong>
        Dashboard belum dapat dimuat
      </strong>

      <p>
        {{ errorMessage }}
      </p>

      <button
        type="button"
        class="retry-button"
        data-testid="dashboard-retry"
        @click="loadDashboard"
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
      data-testid="no-active-period"
    >
      <CalendarDays :size="31" />

      <strong>
        Belum ada periode aktif
      </strong>

      <p>
        Periode BPNT belum diaktifkan
        oleh Admin Dinsos.
      </p>
    </article>

    <template
      v-else-if="
        context
        &&
        context.period
      "
    >
      <article
        class="period-card"
        data-testid="active-period"
      >
        <div class="card-icon period-icon">
          <CalendarDays :size="23" />
        </div>

        <div class="card-copy">
          <span>
            Periode BPNT Aktif
          </span>

          <strong>
            {{ context.period.name }}
          </strong>

          <small>
            {{ context.period.code }}
            ·
            {{ context.period.year }}
          </small>
        </div>
      </article>

      <article
        v-if="!context.assignment"
        class="state-card warning-state"
        data-testid="no-assignment"
      >
        <MapPin :size="31" />

        <strong>
          Anda belum memiliki wilayah tugas
        </strong>

        <p>
          Hubungi Manager BPNT agar wilayah
          tugas dapat ditetapkan.
        </p>
      </article>

      <template v-else>
        <article
          class="assignment-card"
          data-testid="surveyor-assignment"
        >
          <header>
            <div>
              <span>Wilayah Tugas</span>

              <strong>
                {{
                  context.assignment
                    .kelurahan
                    .name
                }}
              </strong>
            </div>

            <div class="card-icon assignment-icon">
              <MapPin :size="23" />
            </div>
          </header>

          <dl>
            <div>
              <dt>Kecamatan</dt>

              <dd>
                {{
                  context.assignment
                    .kecamatan
                    .name
                }}
              </dd>
            </div>

            <div>
              <dt>Kelurahan</dt>

              <dd>
                {{
                  context.assignment
                    .kelurahan
                    .name
                }}
              </dd>
            </div>
          </dl>

          <div class="kpm-summary">
            <Users :size="22" />

            <span>
              {{ formattedKpmCount }} KPM
            </span>
          </div>
        </article>

        <article
          class="e-warung-card"
          data-testid="e-warung-card"
        >
          <header>
            <div class="card-icon store-icon">
              <Store :size="23" />
            </div>

            <div class="card-copy">
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
          </header>

          <label for="surveyor-e-warung">
            Pilih E-Warung aktif
          </label>

          <select
            id="surveyor-e-warung"
            :value="
              selectedEWarung
                ?.id
              ?? ''
            "
            :disabled="
              eWarungsLoading
              ||
              activeEWarungs.length === 0
            "
            data-testid="e-warung-select"
            @change="handleEWarungChange"
          >
            <option value="">
              {{
                eWarungsLoading
                  ? 'Memuat E-Warung...'
                  : 'Pilih E-Warung'
              }}
            </option>

            <option
              v-for="eWarung in activeEWarungs"
              :key="eWarung.id"
              :value="eWarung.id"
            >
              {{ eWarung.name }}
            </option>
          </select>

          <p
            v-if="eWarungError"
            class="field-error"
            role="alert"
          >
            {{ eWarungError }}
          </p>

          <p
            v-else-if="
              !eWarungsLoading
              &&
              activeEWarungs.length === 0
            "
            class="field-warning"
          >
            Belum ada E-Warung aktif.
            Hubungi Admin Dinsos.
          </p>

          <p
            v-else
            class="selection-help"
          >
            Pilihan ini otomatis dipakai untuk
            transaksi berikutnya. E-Warung
            nonaktif tidak ditampilkan.
          </p>
        </article>

        <RouterLink
          :to="{
            name: 'surveyor-scan-ktp',
          }"
          class="scan-action"
          data-testid="scan-ktp-action"
        >
          <span class="scan-icon">
            <ScanLine :size="28" />
          </span>

          <span class="scan-copy">
            <strong>Scan KTP</strong>

            <small>
              {{
                selectedEWarung
                  ? `Transaksi di ${selectedEWarung.name}`
                  : 'Cari KPM; pilih E-Warung sebelum transaksi'
              }}
            </small>
          </span>

          <ArrowRight :size="21" />
        </RouterLink>
      </template>
    </template>
  </section>
</template>

<style scoped>
.dashboard-page {
  display: grid;
  gap: 18px;
}

.eyebrow {
  color: #c12723;
  font-size: 12px;
  font-weight: 750;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.welcome-copy h1 {
  margin: 5px 0 2px;
  color: #173f37;
  font-size: clamp(28px, 8vw, 36px);
}

.welcome-copy p,
.state-card p {
  margin: 0;
  color: #71837d;
  font-size: 13px;
  line-height: 1.6;
}

.period-card,
.assignment-card,
.e-warung-card {
  padding: 18px;

  border: 1px solid #dce9e4;
  border-radius: 22px;
  background: #ffffff;

  box-shadow:
    0 12px 28px
    rgb(30 65 55 / 6%);
}

.period-card {
  display: flex;
  align-items: center;
  gap: 13px;

  border-color: #f0dbb9;

  background:
    linear-gradient(
      145deg,
      #fffaf0,
      #fff3dc
    );
}

.card-icon {
  display: grid;
  width: 45px;
  height: 45px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 15px;
}

.period-icon {
  background: #ffffff;
  color: #bd6800;
}

.assignment-icon,
.store-icon {
  background: #e8f5f0;
  color: #006855;
}

.card-copy {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.card-copy span,
.assignment-card header span {
  color: #758680;
  font-size: 11px;
  font-weight: 650;
}

.card-copy strong,
.assignment-card header strong {
  overflow: hidden;
  color: #244b43;
  font-size: 17px;
  line-height: 1.35;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.card-copy small {
  color: #8a7359;
  font-size: 11px;
}

.assignment-card {
  display: grid;
  gap: 16px;
}

.assignment-card header,
.e-warung-card header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.assignment-card header > div:first-child {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.assignment-card dl {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin: 0;
}

.assignment-card dl div {
  padding: 12px;
  border-radius: 14px;
  background: #f7faf9;
}

.assignment-card dt {
  color: #7b8d87;
  font-size: 10px;
}

.assignment-card dd {
  margin: 3px 0 0;
  color: #284e46;
  font-size: 13px;
  font-weight: 720;
}

.kpm-summary {
  display: flex;
  align-items: center;
  gap: 9px;

  padding-top: 14px;
  border-top: 1px solid #e7eeeb;

  color: #c12723;
}

.kpm-summary span {
  color: #244b43;
  font-size: 15px;
  font-weight: 750;
}

.e-warung-card {
  display: grid;
  gap: 13px;
  border-color: #cde4db;
}

.e-warung-card header {
  justify-content: flex-start;
}

.e-warung-card label {
  color: #35594f;
  font-size: 12px;
  font-weight: 700;
}

.e-warung-card select {
  width: 100%;
  min-height: 50px;
  padding: 0 13px;

  border: 1px solid #cfded8;
  border-radius: 14px;
  outline: none;

  background: #fbfdfc;
  color: #244b43;

  font-size: 13px;
  font-weight: 650;
}

.e-warung-card select:focus {
  border-color: #438d7b;

  box-shadow:
    0 0 0 3px
    rgb(0 104 85 / 9%);
}

.selection-help,
.field-error,
.field-warning {
  margin: 0;
  font-size: 11px;
  line-height: 1.5;
}

.selection-help {
  color: #71837d;
}

.field-error {
  color: #c42c28;
}

.field-warning {
  color: #a75a00;
}

.scan-action {
  display: flex;
  align-items: center;
  gap: 13px;

  padding: 17px;
  border-radius: 22px;

  background:
    linear-gradient(
      135deg,
      #006855,
      #00836c
    );

  color: #ffffff;
  text-decoration: none;

  box-shadow:
    0 15px 28px
    rgb(0 104 85 / 20%);
}

.scan-icon {
  display: grid;
  width: 52px;
  height: 52px;
  flex: 0 0 auto;
  place-items: center;

  border-radius: 17px;
  background: rgb(255 255 255 / 14%);
}

.scan-copy {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.scan-copy strong {
  font-size: 16px;
}

.scan-copy small {
  overflow: hidden;

  color: rgb(255 255 255 / 76%);

  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
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
  font-size: 16px;
}

.retry-button {
  display: inline-flex;
  min-height: 42px;
  align-items: center;
  gap: 8px;

  margin-top: 8px;
  padding: 0 15px;

  border: 0;
  border-radius: 13px;
  background: #006855;
  color: #ffffff;

  font-weight: 700;
}

.loading-state {
  display: grid;
  gap: 12px;
}

.skeleton {
  overflow: hidden;
  border-radius: 13px;
  background: #e7efec;
}

.skeleton-title {
  width: 58%;
  height: 23px;
}

.skeleton-card {
  height: 190px;
  border-radius: 22px;
}

.sr-only {
  position: absolute;
  overflow: hidden;
  width: 1px;
  height: 1px;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

@media (max-width: 380px) {
  .assignment-card dl {
    grid-template-columns: 1fr;
  }
}
</style>