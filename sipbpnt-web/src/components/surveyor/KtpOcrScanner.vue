<script setup lang="ts">
import {
  computed,
  nextTick,
  onBeforeUnmount,
  ref,
} from 'vue'

import {
  Camera,
  CameraOff,
  CircleAlert,
  Keyboard,
  LoaderCircle,
  RefreshCw,
  ScanLine,
  ShieldCheck,
  Zap,
  ZapOff,
} from '@lucide/vue'

import {
  captureKtpPhoto,
  inspectKtpPhoto,
  ktpOcrService,
  releaseKtpPhoto,
} from '@/services/ktpOcrService'

import type {
  KtpOcrProgress,
  KtpOcrScannerState,
} from '@/types/ktpOcr'

type TorchCapabilities =
  MediaTrackCapabilities & {
    torch?: boolean
  }

type TorchConstraintSet =
  MediaTrackConstraintSet & {
    torch?: boolean
  }

const emit =
  defineEmits<{
    detected: [nik: string]
    manual: []
  }>()

const videoElement =
  ref<HTMLVideoElement | null>(
    null,
  )

const scannerState =
  ref<KtpOcrScannerState>(
    'idle',
  )

const scannerMessage = ref(
  'Foto KTP sekali, lalu sistem membaca 16 digit NIK.',
)

const errorMessage = ref('')

const photoPreviewUrl =
  ref('')

const ocrProgress = ref(0)

const torchAvailable =
  ref(false)

const torchEnabled =
  ref(false)

const canForceRead =
  ref(false)

const cameraVisible =
  computed(() => {
    return [
      'requesting_camera',
      'aligning',
    ].includes(
      scannerState.value,
    )
  })

const photoVisible =
  computed(() => {
    return [
      'captured',
      'processing',
    ].includes(
      scannerState.value,
    )
  })

const processing = computed(
  () =>
    scannerState.value ===
    'processing',
)

const requestingCamera =
  computed(
    () =>
      scannerState.value ===
      'requesting_camera',
  )

let mediaStream:
  MediaStream | null = null

let capturedPhoto:
  HTMLCanvasElement | null =
    null

let operationId = 0

function stopMediaTracks(): void {
  for (
    const track of
      mediaStream?.getTracks() ??
    []
  ) {
    track.stop()
  }

  mediaStream = null

  if (videoElement.value) {
    videoElement.value.srcObject =
      null
  }

  torchAvailable.value = false

  torchEnabled.value = false
}

function clearCapturedPhoto(): void {
  if (capturedPhoto) {
    releaseKtpPhoto(
      capturedPhoto,
    )
  }

  capturedPhoto = null

  photoPreviewUrl.value = ''

  canForceRead.value = false
}

function cancelScanner(): void {
  operationId++

  stopMediaTracks()

  clearCapturedPhoto()

  scannerState.value = 'idle'

  scannerMessage.value =
    'Foto KTP sekali, lalu sistem membaca 16 digit NIK.'

  errorMessage.value = ''

  ocrProgress.value = 0
}

function cameraErrorMessage(
  error: unknown,
): string {
  if (
    error instanceof
    DOMException
  ) {
    if (
      error.name ===
        'NotAllowedError' ||
      error.name ===
        'SecurityError'
    ) {
      return 'Izin kamera ditolak. Izinkan kamera pada pengaturan browser atau gunakan input NIK manual.'
    }

    if (
      error.name ===
        'NotFoundError' ||
      error.name ===
        'DevicesNotFoundError'
    ) {
      return 'Kamera tidak ditemukan pada perangkat ini. Gunakan input NIK manual.'
    }

    if (
      error.name ===
        'NotReadableError' ||
      error.name ===
        'TrackStartError'
    ) {
      return 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi kamera lalu coba kembali.'
    }
  }

  if (
    error instanceof Error &&
    error.message.trim() !== ''
  ) {
    return error.message
  }

  return 'Kamera gagal dibuka. Gunakan input NIK manual atau coba kembali.'
}

function waitForVideoReady(
  video: HTMLVideoElement,
): Promise<void> {
  if (
    video.readyState >=
      HTMLMediaElement.HAVE_METADATA &&
    video.videoWidth > 0
  ) {
    return Promise.resolve()
  }

  return new Promise(
    (resolve, reject) => {
      const timeout =
        window.setTimeout(
          () => {
            cleanup()

            reject(
              new Error(
                'Kamera terlalu lama merespons. Silakan coba kembali.',
              ),
            )
          },
          10_000,
        )

      const handleReady =
        (): void => {
          cleanup()
          resolve()
        }

      const handleError =
        (): void => {
          cleanup()

          reject(
            new Error(
              'Tampilan kamera gagal dimuat.',
            ),
          )
        }

      function cleanup(): void {
        window.clearTimeout(
          timeout,
        )

        video.removeEventListener(
          'loadedmetadata',
          handleReady,
        )

        video.removeEventListener(
          'error',
          handleError,
        )
      }

      video.addEventListener(
        'loadedmetadata',
        handleReady,
        {
          once: true,
        },
      )

      video.addEventListener(
        'error',
        handleError,
        {
          once: true,
        },
      )
    },
  )
}

function updateOcrProgress(
  progress: KtpOcrProgress,
): void {
  if (
    scannerState.value !==
    'processing'
  ) {
    return
  }

  ocrProgress.value =
    Math.round(
      progress.progress * 100,
    )

  scannerMessage.value =
    progress.message
}

async function startCamera(): Promise<void> {
  operationId++

  const activeOperationId =
    operationId

  stopMediaTracks()

  clearCapturedPhoto()

  errorMessage.value = ''

  ocrProgress.value = 0

  scannerState.value =
    'requesting_camera'

  scannerMessage.value =
    'Meminta izin kamera...'

  try {
    if (
      !window.isSecureContext &&
      window.location
        .hostname !==
        'localhost'
    ) {
      throw new Error(
        'Kamera hanya dapat digunakan melalui HTTPS atau localhost.',
      )
    }

    if (
      !navigator.mediaDevices
        ?.getUserMedia
    ) {
      throw new Error(
        'Browser ini belum mendukung akses kamera. Gunakan input NIK manual.',
      )
    }

    mediaStream =
      await navigator.mediaDevices.getUserMedia(
        {
          audio: false,

          video: {
            facingMode: {
              ideal:
                'environment',
            },

            width: {
              ideal: 1_920,
            },

            height: {
              ideal: 1_080,
            },

            frameRate: {
              ideal: 24,
              max: 30,
            },
          },
        },
      )

    if (
      activeOperationId !==
      operationId
    ) {
      stopMediaTracks()

      return
    }

    await nextTick()

    const video =
      videoElement.value

    if (!video) {
      throw new Error(
        'Tampilan kamera belum tersedia.',
      )
    }

    video.srcObject =
      mediaStream

    await waitForVideoReady(
      video,
    )

    await video.play()

    const videoTrack =
      mediaStream.getVideoTracks()[0]

    if (videoTrack) {
      const capabilities: TorchCapabilities =
        typeof videoTrack.getCapabilities ===
        'function'
          ? videoTrack.getCapabilities()
          : {}

      torchAvailable.value =
        capabilities.torch ===
        true
    }

    scannerState.value =
      'aligning'

    scannerMessage.value =
      'Penuhi bingkai dengan KTP, tunggu fokus, lalu tekan Ambil Foto.'

    void ktpOcrService
      .prepare(
        updateOcrProgress,
      )
      .catch(() => {
        // Kesalahan persiapan akan ditampilkan saat foto dibaca.
      })
  } catch (error: unknown) {
    if (
      activeOperationId !==
      operationId
    ) {
      return
    }

    stopMediaTracks()

    scannerState.value =
      'error'

    errorMessage.value =
      cameraErrorMessage(
        error,
      )
  }
}

async function processCapturedPhoto(
  activeOperationId =
    operationId,
): Promise<void> {
  if (
    activeOperationId !==
      operationId ||
    processing.value ||
    !capturedPhoto
  ) {
    return
  }

  scannerState.value =
    'processing'

  scannerMessage.value =
    'Menyiapkan foto NIK...'

  errorMessage.value = ''

  canForceRead.value = false

  ocrProgress.value =
    Math.max(
      4,
      ocrProgress.value,
    )

  try {
    const result =
      await ktpOcrService.recognizePhoto(
        capturedPhoto,
        updateOcrProgress,
      )

    if (
      activeOperationId !==
      operationId
    ) {
      return
    }

    clearCapturedPhoto()

    scannerState.value =
      'detected'

    scannerMessage.value =
      'NIK berhasil dibaca. Periksa kembali sebelum mencari KPM.'

    ocrProgress.value = 100

    emit(
      'detected',
      result.nik,
    )
  } catch (error: unknown) {
    if (
      activeOperationId !==
      operationId
    ) {
      return
    }

    scannerState.value =
      'captured'

    scannerMessage.value =
      'NIK belum berhasil dibaca dari foto ini.'

    errorMessage.value =
      cameraErrorMessage(
        error,
      )
  }
}

async function takePhoto(): Promise<void> {
  if (
    scannerState.value !==
      'aligning' ||
    !videoElement.value
  ) {
    return
  }

  const activeOperationId =
    operationId

  try {
    clearCapturedPhoto()

    capturedPhoto =
      captureKtpPhoto(
        videoElement.value,
      )

    photoPreviewUrl.value =
      capturedPhoto.toDataURL(
        'image/jpeg',
        0.92,
      )

    stopMediaTracks()

    const inspection =
      inspectKtpPhoto(
        capturedPhoto,
      )

    if (
      !inspection.acceptable
    ) {
      scannerState.value =
        'captured'

      scannerMessage.value =
        'Periksa kualitas foto sebelum membaca NIK.'

      errorMessage.value =
        inspection.message

      canForceRead.value = true

      return
    }

    scannerMessage.value =
      inspection.message

    await processCapturedPhoto(
      activeOperationId,
    )
  } catch (error: unknown) {
    if (
      activeOperationId !==
      operationId
    ) {
      return
    }

    stopMediaTracks()

    scannerState.value =
      capturedPhoto
        ? 'captured'
        : 'error'

    errorMessage.value =
      cameraErrorMessage(
        error,
      )
  }
}

async function toggleTorch(): Promise<void> {
  const videoTrack =
    mediaStream?.getVideoTracks()[0]

  if (
    !videoTrack ||
    !torchAvailable.value
  ) {
    return
  }

  const nextValue =
    !torchEnabled.value

  try {
    await videoTrack.applyConstraints(
      {
        advanced: [
          {
            torch:
              nextValue,
          } as TorchConstraintSet,
        ],
      },
    )

    torchEnabled.value =
      nextValue
  } catch {
    torchAvailable.value =
      false

    torchEnabled.value =
      false
  }
}

function useManualInput(): void {
  cancelScanner()

  emit('manual')
}

onBeforeUnmount(() => {
  operationId++

  stopMediaTracks()

  clearCapturedPhoto()

  void ktpOcrService.dispose()
})
</script>

<template>
  <article
    class="overflow-hidden rounded-[22px] border border-[#dce9e4] bg-white shadow-[0_12px_28px_rgb(30_65_55_/_6%)]"
    data-testid="ktp-ocr-scanner"
  >
    <header
      class="flex items-start justify-between gap-3 px-[18px] pt-[18px] lg:px-[22px] lg:pt-[22px]"
    >
      <div class="min-w-0">
        <div
          class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-[#e8f5f0] px-2.5 py-1 text-[10px] font-bold text-[#006855]"
        >
          <ShieldCheck
            :size="15"
          />

          Diproses di perangkat
        </div>

        <h2
          class="m-0 text-base font-bold text-[#173f37]"
        >
          Foto KTP & Baca NIK
        </h2>

        <p
          class="mt-1 mb-0 text-[11px] leading-[1.55] text-[#71837d]"
        >
          Foto hanya digunakan
          sementara untuk membaca
          NIK dan tidak disimpan.
        </p>
      </div>

      <div
        class="grid size-10 shrink-0 place-items-center rounded-[14px] bg-[#edf6f3] text-[#006855]"
      >
        <Camera :size="22" />
      </div>
    </header>

    <div
      v-if="
        scannerState === 'idle'
      "
      class="grid gap-3 p-[18px] pt-4 lg:p-[22px] lg:pt-4"
    >
      <button
        type="button"
        class="flex min-h-12 w-full items-center justify-center gap-2 rounded-[14px] border-0 bg-[#006855] text-[13px] font-bold text-white transition-colors hover:bg-[#005746]"
        data-testid="start-ocr-scanner"
        @click="startCamera"
      >
        <Camera :size="20" />

        Buka Kamera KTP
      </button>

      <button
        type="button"
        class="flex min-h-11 w-full items-center justify-center gap-2 rounded-[13px] border border-[#d6e3de] bg-white text-xs font-bold text-[#45655c] transition-colors hover:bg-[#f4f8f6]"
        data-testid="use-manual-nik"
        @click="useManualInput"
      >
        <Keyboard :size="18" />

        Masukkan NIK Manual
      </button>
    </div>

    <div
      v-else-if="cameraVisible"
      class="mt-4"
    >
      <div
        class="relative aspect-[1.586/1] w-full overflow-hidden bg-[#102821]"
      >
        <video
          ref="videoElement"
          autoplay
          muted
          playsinline
          class="size-full object-cover"
          data-testid="ktp-camera-preview"
        />

        <div
          class="pointer-events-none absolute inset-[5%] rounded-[16px] border-2 border-white/90 shadow-[0_0_0_999px_rgb(5_20_16_/_42%)]"
        >
          <span
            class="absolute top-[16%] right-[6%] left-[6%] h-[24%] rounded-[8px] border-2 border-dashed border-[#6ff0c9] bg-[#006855]/8"
          />

          <span
            class="absolute top-[11%] left-[7%] rounded-md bg-[#006855]/88 px-2 py-1 text-[9px] font-bold text-white"
          >
            Pastikan NIK berada di
            area ini
          </span>
        </div>

        <div
          v-if="requestingCamera"
          class="absolute inset-0 grid place-items-center bg-[#102821]/85 text-white"
        >
          <div
            class="grid justify-items-center gap-2 text-center"
          >
            <LoaderCircle
              :size="28"
              class="animate-spin"
            />

            <span
              class="text-xs font-semibold"
            >
              Membuka kamera...
            </span>
          </div>
        </div>
      </div>

      <div
        class="flex items-center gap-2 border-y border-[#dce9e4] bg-[#f7fbf9] px-4 py-3 text-[#45655c]"
      >
        <Camera
          :size="17"
          class="shrink-0 text-[#006855]"
        />

        <p
          class="m-0 flex-1 text-[10px] leading-[1.5]"
          aria-live="polite"
        >
          {{ scannerMessage }}
        </p>
      </div>

      <div
        class="grid grid-cols-2 gap-2 p-[14px] sm:grid-cols-3"
      >
        <button
          type="button"
          class="flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-[#d6e3de] bg-white text-[11px] font-bold text-[#526a62] disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="
            requestingCamera
          "
          @click="cancelScanner"
        >
          <CameraOff
            :size="17"
          />

          Batal
        </button>

        <button
          type="button"
          class="flex min-h-10 items-center justify-center gap-1.5 rounded-xl border-0 bg-[#006855] text-[11px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="
            requestingCamera
          "
          data-testid="capture-ktp-photo"
          @click="takePhoto"
        >
          <Camera :size="17" />

          Ambil Foto
        </button>

        <button
          v-if="torchAvailable"
          type="button"
          class="col-span-2 flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-[#e6d6ae] bg-[#fff9ea] text-[11px] font-bold text-[#9d6200] sm:col-span-1"
          @click="toggleTorch"
        >
          <ZapOff
            v-if="torchEnabled"
            :size="17"
          />

          <Zap
            v-else
            :size="17"
          />

          {{
            torchEnabled
              ? 'Matikan Lampu'
              : 'Nyalakan Lampu'
          }}
        </button>
      </div>
    </div>

    <div
      v-else-if="photoVisible"
      class="mt-4"
      data-testid="ktp-photo-result"
    >
      <div
        class="relative aspect-[1.586/1] w-full overflow-hidden bg-[#102821]"
      >
        <img
          :src="photoPreviewUrl"
          alt="Pratinjau foto KTP sementara"
          class="size-full object-cover"
          data-testid="ktp-photo-preview"
        />

        <div
          v-if="processing"
          class="absolute inset-0 grid place-items-center bg-[#102821]/82 p-6 text-white"
        >
          <div
            class="grid w-full max-w-[280px] justify-items-center gap-3 text-center"
          >
            <LoaderCircle
              :size="31"
              class="animate-spin"
            />

            <strong
              class="text-sm"
            >
              {{ scannerMessage }}
            </strong>

            <div
              class="h-1.5 w-full overflow-hidden rounded-full bg-white/25"
            >
              <div
                class="h-full rounded-full bg-[#6ff0c9] transition-[width] duration-300"
                :style="{
                  width: `${ocrProgress}%`,
                }"
              />
            </div>

            <small
              class="text-[10px] text-white/75"
            >
              {{ ocrProgress }}%
            </small>
          </div>
        </div>
      </div>

      <div
        v-if="!processing"
        class="m-[14px] flex items-start gap-3 rounded-[15px] border border-[#efcdca] bg-[#fff8f7] p-4 text-[#c42c28]"
        role="alert"
      >
        <CircleAlert
          :size="22"
          class="shrink-0"
        />

        <p
          class="m-0 text-[11px] leading-[1.55]"
        >
          {{ errorMessage }}
        </p>
      </div>

      <div
        v-if="!processing"
        class="grid gap-2 p-[14px] pt-0 sm:grid-cols-2"
      >
        <button
          type="button"
          class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border border-[#c5ded5] bg-[#eef8f4] text-xs font-bold text-[#006855]"
          data-testid="retake-ktp-photo"
          @click="startCamera"
        >
          <RefreshCw
            :size="18"
          />

          Foto Ulang
        </button>

        <button
          v-if="canForceRead"
          type="button"
          class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border-0 bg-[#006855] text-xs font-bold text-white"
          data-testid="read-photo-anyway"
          @click="
            processCapturedPhoto()
          "
        >
          <ScanLine
            :size="18"
          />

          Tetap Baca Foto
        </button>

        <button
          v-else
          type="button"
          class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border border-[#d6e3de] bg-white text-xs font-bold text-[#45655c]"
          @click="useManualInput"
        >
          <Keyboard
            :size="18"
          />

          Input NIK Manual
        </button>
      </div>
    </div>

    <div
      v-else-if="
        scannerState ===
        'detected'
      "
      class="grid gap-3 p-[18px] pt-4 lg:p-[22px] lg:pt-4"
      data-testid="ocr-detected"
    >
      <div
        class="flex items-start gap-3 rounded-[15px] border border-[#b9dfd2] bg-[#eaf8f3] p-4 text-[#006855]"
      >
        <ShieldCheck
          :size="23"
          class="shrink-0"
        />

        <div>
          <strong
            class="text-sm"
          >
            NIK berhasil dibaca
          </strong>

          <p
            class="mt-1 mb-0 text-[11px] leading-[1.5] text-[#52736a]"
          >
            Periksa kembali 16 digit
            NIK pada kolom pencarian
            sebelum melanjutkan.
          </p>
        </div>
      </div>

      <button
        type="button"
        class="flex min-h-11 w-full items-center justify-center gap-2 rounded-[13px] border border-[#c5ded5] bg-white text-xs font-bold text-[#006855]"
        data-testid="scan-another-ktp"
        @click="startCamera"
      >
        <RefreshCw
          :size="18"
        />

        Foto KTP Lain
      </button>
    </div>

    <div
      v-else
      class="grid gap-3 p-[18px] pt-4 lg:p-[22px] lg:pt-4"
      data-testid="ocr-camera-error"
    >
      <div
        class="flex items-start gap-3 rounded-[15px] border border-[#efcdca] bg-[#fff8f7] p-4 text-[#c42c28]"
        role="alert"
      >
        <CircleAlert
          :size="22"
          class="shrink-0"
        />

        <p
          class="m-0 text-[11px] leading-[1.55]"
        >
          {{ errorMessage }}
        </p>
      </div>

      <div
        class="grid grid-cols-2 gap-2"
      >
        <button
          type="button"
          class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border border-[#c5ded5] bg-[#eef8f4] text-xs font-bold text-[#006855]"
          @click="startCamera"
        >
          <RefreshCw
            :size="18"
          />

          Coba Lagi
        </button>

        <button
          type="button"
          class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border border-[#d6e3de] bg-white text-xs font-bold text-[#45655c]"
          @click="useManualInput"
        >
          <Keyboard
            :size="18"
          />

          Input Manual
        </button>
      </div>
    </div>
  </article>
</template>