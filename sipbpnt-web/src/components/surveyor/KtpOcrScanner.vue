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
  ShieldCheck,
  Zap,
  ZapOff,
} from '@lucide/vue'

import {
  captureKtpPhoto,
  hasValidNikStructure,
  inspectKtpPhoto,
  ktpOcrService,
  releaseKtpPhoto,
} from '@/services/ktpOcrService'

import type {
  KtpOcrProgress,
  KtpOcrScannerState,
} from '@/types/ktpOcr'

type CameraCapabilities =
  MediaTrackCapabilities & {
    focusMode?: string[]
    torch?: boolean
  }

type CameraConstraintSet =
  MediaTrackConstraintSet & {
    focusMode?: string
    torch?: boolean
  }

const emit =
  defineEmits([
    'detected',
    'manual',
  ])

const videoElement =
  ref<
    HTMLVideoElement | null
  >(null)

const scannerState =
  ref<KtpOcrScannerState>(
    'idle',
  )

const scannerMessage =
  ref(
    'Foto KTP, lalu sistem membaca 16 digit NIK.',
  )

const errorMessage =
  ref('')

const photoPreviewUrl =
  ref('')

const nikPreviewUrl =
  ref('')

const detectedNik =
  ref('')

const detectedNikError =
  ref('')

const detectedNikWarning =
  ref('')

const alternativeNiks =
  ref<string[]>([])

const ocrProgress =
  ref(0)

const torchAvailable =
  ref(false)

const torchEnabled =
  ref(false)

const cameraVisible =
  computed(
    () =>
      [
        'requesting_camera',
        'aligning',
      ].includes(
        scannerState.value,
      ),
  )

const processing =
  computed(
    () =>
      scannerState.value
      === 'processing',
  )

const photoVisible =
  computed(
    () =>
      [
        'captured',
        'processing',
      ].includes(
        scannerState.value,
      ),
  )

let mediaStream:
  MediaStream | null =
    null

let capturedPhoto:
  HTMLCanvasElement | null =
    null

let operationId = 0

function activeVideoTrack():
  MediaStreamTrack | null {
  return mediaStream
    ?.getVideoTracks()[0]
    ?? null
}

function stopCamera():
  void {
  for (
    const track
    of mediaStream
      ?.getTracks()
    ?? []
  ) {
    track.stop()
  }

  mediaStream = null

  if (
    videoElement.value
  ) {
    videoElement.value
      .srcObject = null
  }

  torchAvailable.value =
    false

  torchEnabled.value =
    false
}

function clearPhoto():
  void {
  if (capturedPhoto) {
    releaseKtpPhoto(
      capturedPhoto,
    )
  }

  capturedPhoto = null

  photoPreviewUrl.value =
    ''

  nikPreviewUrl.value =
    ''
}

function clearResult():
  void {
  detectedNik.value = ''

  detectedNikError.value =
    ''

  detectedNikWarning.value =
    ''

  alternativeNiks.value =
    []
}

function resetScanner():
  void {
  operationId++

  stopCamera()
  clearPhoto()
  clearResult()

  scannerState.value =
    'idle'

  scannerMessage.value =
    'Foto KTP, lalu sistem membaca 16 digit NIK.'

  errorMessage.value = ''
  ocrProgress.value = 0
}

function readableError(
  error: unknown,
): string {
  if (
    error
    instanceof DOMException
  ) {
    if (
      error.name
        === 'NotAllowedError'
      || error.name
        === 'SecurityError'
    ) {
      return 'Izin kamera ditolak. Izinkan kamera atau gunakan input NIK manual.'
    }

    if (
      error.name
      === 'NotFoundError'
    ) {
      return 'Kamera tidak ditemukan. Gunakan input NIK manual.'
    }

    if (
      error.name
      === 'NotReadableError'
    ) {
      return 'Kamera sedang digunakan aplikasi lain.'
    }
  }

  if (
    error instanceof Error
    && error.message
      .trim() !== ''
  ) {
    return error.message
  }

  return 'Proses kamera atau OCR gagal. Silakan coba kembali.'
}

function wait(
  milliseconds: number,
): Promise<void> {
  return new Promise(
    (
      resolve,
    ) =>
      window.setTimeout(
        resolve,
        milliseconds,
      ),
  )
}

function waitForVideo(
  video:
    HTMLVideoElement,
): Promise<void> {
  if (
    video.videoWidth > 0
    && video.readyState
      >= HTMLMediaElement
        .HAVE_METADATA
  ) {
    return Promise.resolve()
  }

  return new Promise(
    (
      resolve,
      reject,
    ) => {
      const timeout =
        window.setTimeout(
          () => {
            cleanup()

            reject(
              new Error(
                'Kamera terlalu lama merespons.',
              ),
            )
          },
          10_000,
        )

      const ready =
        (): void => {
          cleanup()
          resolve()
        }

      const failed =
        (): void => {
          cleanup()

          reject(
            new Error(
              'Tampilan kamera gagal dimuat.',
            ),
          )
        }

      function cleanup():
        void {
        window.clearTimeout(
          timeout,
        )

        video.removeEventListener(
          'loadedmetadata',
          ready,
        )

        video.removeEventListener(
          'error',
          failed,
        )
      }

      video.addEventListener(
        'loadedmetadata',
        ready,
        {
          once: true,
        },
      )

      video.addEventListener(
        'error',
        failed,
        {
          once: true,
        },
      )
    },
  )
}

async function applyFocus(
  mode: string,
): Promise<boolean> {
  const track =
    activeVideoTrack()

  if (
    !track
    || typeof track
      .getCapabilities
      !== 'function'
  ) {
    return false
  }

  const capabilities:
    CameraCapabilities =
      track
        .getCapabilities()

  if (
    !capabilities
      .focusMode
      ?.includes(mode)
  ) {
    return false
  }

  const constraint:
    CameraConstraintSet = {
      focusMode: mode,
    }

  try {
    await track
      .applyConstraints({
        advanced: [
          constraint,
        ],
      })

    return true
  } catch {
    return false
  }
}

async function focusCamera():
  Promise<void> {
  if (
    scannerState.value
    !== 'aligning'
  ) {
    return
  }

  scannerMessage.value =
    'Mengunci fokus kamera...'

  if (
    await applyFocus(
      'single-shot',
    )
  ) {
    await wait(500)
  }

  scannerMessage.value =
    'Tempatkan baris NIK di kotak hijau lalu ambil foto.'
}

function updateProgress(
  progress:
    KtpOcrProgress,
): void {
  if (!processing.value) {
    return
  }

  ocrProgress.value =
    Math.round(
      progress.progress
      * 100,
    )

  scannerMessage.value =
    progress.message
}

async function startCamera():
  Promise<void> {
  operationId++

  const currentOperation =
    operationId

  stopCamera()
  clearPhoto()
  clearResult()

  errorMessage.value = ''
  ocrProgress.value = 0

  scannerState.value =
    'requesting_camera'

  scannerMessage.value =
    'Meminta izin kamera...'

  try {
    if (
      !window.isSecureContext
      && window.location
        .hostname
        !== 'localhost'
    ) {
      throw new Error(
        'Kamera hanya dapat digunakan melalui HTTPS atau localhost.',
      )
    }

    if (
      !navigator
        .mediaDevices
        ?.getUserMedia
    ) {
      throw new Error(
        'Browser tidak mendukung kamera.',
      )
    }

    mediaStream =
      await navigator
        .mediaDevices
        .getUserMedia({
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
        })

    if (
      currentOperation
      !== operationId
    ) {
      stopCamera()
      return
    }

    await nextTick()

    const video =
      videoElement.value

    if (!video) {
      throw new Error(
        'Elemen kamera belum tersedia.',
      )
    }

    video.srcObject =
      mediaStream

    await waitForVideo(
      video,
    )

    await video.play()

    const track =
      activeVideoTrack()

    if (
      track
      && typeof track
        .getCapabilities
        === 'function'
    ) {
      const capabilities:
        CameraCapabilities =
          track
            .getCapabilities()

      torchAvailable.value =
        capabilities.torch
        === true
    }

    await applyFocus(
      'continuous',
    )

    scannerState.value =
      'aligning'

    scannerMessage.value =
      'Penuhi bingkai dengan KTP dan tempatkan NIK di kotak hijau.'

    void ktpOcrService
      .prepare(
        updateProgress,
      )
      .catch(
        () => undefined,
      )
  } catch (
    error: unknown
  ) {
    if (
      currentOperation
      !== operationId
    ) {
      return
    }

    stopCamera()

    scannerState.value =
      'error'

    errorMessage.value =
      readableError(
        error,
      )
  }
}

async function processPhoto(
  currentOperation:
    number,
): Promise<void> {
  if (
    !capturedPhoto
    || currentOperation
      !== operationId
  ) {
    return
  }

  scannerState.value =
    'processing'

  scannerMessage.value =
    'Menyiapkan Tesseract OCR...'

  errorMessage.value = ''
  ocrProgress.value = 5

  try {
    const result =
      await ktpOcrService
        .recognizePhoto(
          capturedPhoto,
          updateProgress,
        )

    if (
      currentOperation
      !== operationId
    ) {
      return
    }

    const preview =
      result.previewUrl

    clearPhoto()

    nikPreviewUrl.value =
      preview

    detectedNik.value =
      result.nik

    detectedNikWarning.value =
      result.warning
      ?? 'Periksa kembali seluruh digit NIK.'

    alternativeNiks.value =
      result.alternatives

    scannerState.value =
      'detected'

    scannerMessage.value =
      'NIK terbaca. Periksa hasil sebelum digunakan.'

    ocrProgress.value = 100
  } catch (
    error: unknown
  ) {
    if (
      currentOperation
      !== operationId
    ) {
      return
    }

    scannerState.value =
      'captured'

    scannerMessage.value =
      'NIK belum berhasil dibaca.'

    errorMessage.value =
      readableError(
        error,
      )
  }
}

async function takePhoto():
  Promise<void> {
  const video =
    videoElement.value

  if (
    !video
    || scannerState.value
      !== 'aligning'
  ) {
    return
  }

  const currentOperation =
    operationId

  try {
    await focusCamera()

    clearPhoto()

    capturedPhoto =
      captureKtpPhoto(
        video,
      )

    photoPreviewUrl.value =
      capturedPhoto
        .toDataURL(
          'image/jpeg',
          0.92,
        )

    stopCamera()

    const inspection =
      inspectKtpPhoto(
        capturedPhoto,
      )

    if (
      !inspection.acceptable
    ) {
      scannerState.value =
        'captured'

      errorMessage.value =
        inspection.message

      return
    }

    scannerMessage.value =
      inspection.message

    await processPhoto(
      currentOperation,
    )
  } catch (
    error: unknown
  ) {
    stopCamera()

    scannerState.value =
      capturedPhoto
        ? 'captured'
        : 'error'

    errorMessage.value =
      readableError(
        error,
      )
  }
}

function handleNikInput(
  event: Event,
): void {
  const input =
    event.target

  if (
    !(
      input
      instanceof HTMLInputElement
    )
  ) {
    return
  }

  const value =
    input.value
      .replace(
        /\D+/g,
        '',
      )
      .slice(
        0,
        16,
      )

  input.value = value
  detectedNik.value = value
  detectedNikError.value = ''

  detectedNikWarning.value =
    'Hasil diubah. Cocokkan kembali dengan foto KTP.'
}

function selectAlternative(
  nik: string,
): void {
  detectedNik.value = nik
  detectedNikError.value = ''

  detectedNikWarning.value =
    'Alternatif dipilih. Periksa kembali dengan foto KTP.'
}

function confirmNik():
  void {
  if (
    detectedNik.value
      .length !== 16
  ) {
    detectedNikError.value =
      'NIK harus tepat 16 digit.'

    return
  }

  detectedNikError.value = ''

  if (
    !hasValidNikStructure(
      detectedNik.value,
    )
  ) {
    detectedNikWarning.value =
      'Struktur NIK terlihat tidak biasa. Pencarian tetap dilakukan exact ke BNBA.'
  }

  emit(
    'detected',
    detectedNik.value,
  )
}

async function toggleTorch():
  Promise<void> {
  const track =
    activeVideoTrack()

  if (
    !track
    || !torchAvailable.value
  ) {
    return
  }

  const nextValue =
    !torchEnabled.value

  const constraint:
    CameraConstraintSet = {
      torch: nextValue,
    }

  try {
    await track
      .applyConstraints({
        advanced: [
          constraint,
        ],
      })

    torchEnabled.value =
      nextValue
  } catch {
    torchAvailable.value =
      false
  }
}

function useManual():
  void {
  resetScanner()
  emit('manual')
}

onBeforeUnmount(
  () => {
    operationId++

    stopCamera()
    clearPhoto()

    void ktpOcrService
      .dispose()
  },
)
</script>

<template>
  <article
    class="overflow-hidden rounded-[22px] border border-[#dce9e4] bg-white shadow-[0_12px_28px_rgb(30_65_55_/_6%)]"
    data-testid="ktp-ocr-scanner"
  >
    <header
      class="flex items-start justify-between gap-3 p-[18px] pb-4 lg:p-[22px]"
    >
      <div>
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
          class="mt-1 mb-0 text-[11px] text-[#71837d]"
        >
          Foto sementara dan tidak disimpan.
        </p>
      </div>

      <div
        class="grid size-10 place-items-center rounded-[14px] bg-[#edf6f3] text-[#006855]"
      >
        <Camera :size="22" />
      </div>
    </header>

    <div
      v-if="scannerState === 'idle'"
      class="grid gap-3 px-[18px] pb-[18px] lg:px-[22px]"
    >
      <button
        type="button"
        class="flex min-h-12 items-center justify-center gap-2 rounded-[14px] border-0 bg-[#006855] text-[13px] font-bold text-white"
        data-testid="start-ocr-scanner"
        @click="startCamera"
      >
        <Camera :size="20" />
        Buka Kamera KTP
      </button>

      <button
        type="button"
        class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border border-[#d6e3de] bg-white text-xs font-bold text-[#45655c]"
        data-testid="use-manual-nik"
        @click="useManual"
      >
        <Keyboard :size="18" />
        Masukkan NIK Manual
      </button>
    </div>

    <div
      v-else-if="cameraVisible"
    >
      <div
        class="relative aspect-[1.586/1] overflow-hidden bg-[#102821]"
      >
        <video
          ref="videoElement"
          autoplay
          muted
          playsinline
          class="size-full object-cover"
          data-testid="ktp-camera-preview"
          @click="focusCamera"
        />

        <div
          class="pointer-events-none absolute inset-[5%] rounded-[16px] border-2 border-white/90 shadow-[0_0_0_999px_rgb(5_20_16_/_42%)]"
        >
          <span
            class="absolute top-[12%] right-[4%] left-[4%] h-[36%] rounded-lg border-2 border-dashed border-[#6ff0c9]"
          />

          <span
            class="absolute top-[7%] left-[6%] rounded-md bg-[#006855] px-2 py-1 text-[9px] font-bold text-white"
          >
            Posisikan baris NIK di area hijau
          </span>
        </div>

        <div
          v-if="scannerState === 'requesting_camera'"
          class="absolute inset-0 grid place-items-center bg-[#102821]/85 text-white"
        >
          <LoaderCircle
            :size="30"
            class="animate-spin"
          />
        </div>
      </div>

      <p
        class="m-0 border-y border-[#dce9e4] bg-[#f7fbf9] px-4 py-3 text-[10px] text-[#45655c]"
      >
        {{ scannerMessage }}
      </p>

      <div
        class="grid grid-cols-2 gap-2 p-[14px] sm:grid-cols-3"
      >
        <button
          type="button"
          class="flex min-h-10 items-center justify-center gap-2 rounded-xl border border-[#d6e3de] bg-white text-[11px] font-bold text-[#526a62]"
          @click="resetScanner"
        >
          <CameraOff :size="17" />
          Batal
        </button>

        <button
          type="button"
          class="flex min-h-10 items-center justify-center gap-2 rounded-xl border-0 bg-[#006855] text-[11px] font-bold text-white"
          data-testid="capture-ktp-photo"
          @click="takePhoto"
        >
          <Camera :size="17" />
          Ambil Foto
        </button>

        <button
          v-if="torchAvailable"
          type="button"
          class="col-span-2 flex min-h-10 items-center justify-center gap-2 rounded-xl border border-[#e6d6ae] bg-[#fff9ea] text-[11px] font-bold text-[#9d6200] sm:col-span-1"
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
      data-testid="ktp-photo-result"
    >
      <div
        class="relative aspect-[1.586/1] overflow-hidden bg-[#102821]"
      >
        <img
          :src="photoPreviewUrl"
          alt="Foto KTP sementara"
          class="size-full object-cover"
          data-testid="ktp-photo-preview"
        />

        <div
          v-if="processing"
          class="absolute inset-0 grid place-items-center bg-[#102821]/82 p-6 text-white"
        >
          <div
            class="grid w-full max-w-[280px] gap-3 text-center"
          >
            <LoaderCircle
              :size="30"
              class="mx-auto animate-spin"
            />

            <strong
              class="text-sm"
            >
              {{ scannerMessage }}
            </strong>

            <div
              class="h-1.5 overflow-hidden rounded-full bg-white/25"
            >
              <div
                class="h-full rounded-full bg-[#6ff0c9]"
                :style="{
                  width: `${ocrProgress}%`,
                }"
              />
            </div>

            <small>
              {{ ocrProgress }}%
            </small>
          </div>
        </div>
      </div>

      <div
        v-if="!processing"
        class="grid gap-3 p-[14px]"
      >
        <div
          class="flex gap-3 rounded-[15px] border border-[#efcdca] bg-[#fff8f7] p-4 text-[#c42c28]"
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
            data-testid="retake-ktp-photo"
            @click="startCamera"
          >
            <RefreshCw :size="18" />
            Foto Ulang
          </button>

          <button
            type="button"
            class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border border-[#d6e3de] bg-white text-xs font-bold text-[#45655c]"
            @click="useManual"
          >
            <Keyboard :size="18" />
            Input Manual
          </button>
        </div>
      </div>
    </div>

    <div
      v-else-if="scannerState === 'detected'"
      class="grid gap-3 p-[18px] pt-0 lg:p-[22px] lg:pt-0"
      data-testid="ocr-detected"
    >
      <div
        class="overflow-hidden rounded-[14px] border border-[#c5ded5] bg-[#eff7f4] p-2"
      >
        <span
          class="mb-1 block text-[10px] font-bold text-[#45655c]"
        >
          Periksa baris NIK:
        </span>

        <img
          :src="nikPreviewUrl"
          alt="Potongan area NIK"
          class="max-h-[160px] w-full rounded-lg bg-white object-contain"
          data-testid="nik-crop-preview"
        />
      </div>

      <label
        for="ocr-detected-nik"
        class="text-[11px] font-bold text-[#35594f]"
      >
        Koreksi hasil OCR jika diperlukan
      </label>

      <input
        id="ocr-detected-nik"
        :value="detectedNik"
        type="text"
        inputmode="numeric"
        maxlength="16"
        autocomplete="off"
        class="min-h-[52px] rounded-[14px] border border-[#c5ded5] px-3 text-base font-bold tracking-[0.04em] text-[#244b43] outline-none"
        data-testid="ocr-detected-nik"
        @input="handleNikInput"
      />

      <p
        v-if="detectedNikError"
        class="m-0 text-[11px] text-[#c42c28]"
        role="alert"
      >
        {{ detectedNikError }}
      </p>

      <p
        class="m-0 rounded-xl border border-[#ecd5ad] bg-[#fff9ef] px-3 py-2 text-[10px] text-[#9c5c00]"
      >
        {{ detectedNikWarning }}
      </p>

      <button
        v-for="alternative in alternativeNiks"
        :key="alternative"
        type="button"
        class="min-h-10 rounded-xl border border-[#d6e3de] bg-white px-3 text-left text-xs font-bold"
        @click="selectAlternative(alternative)"
      >
        Alternatif: {{ alternative }}
      </button>

      <button
        type="button"
        class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border-0 bg-[#006855] text-xs font-bold text-white"
        data-testid="confirm-ocr-nik"
        @click="confirmNik"
      >
        <ShieldCheck :size="18" />
        Gunakan NIK yang Sudah Diperiksa
      </button>

      <button
        type="button"
        class="flex min-h-11 items-center justify-center gap-2 rounded-[13px] border border-[#c5ded5] bg-white text-xs font-bold text-[#006855]"
        data-testid="scan-another-ktp"
        @click="startCamera"
      >
        <RefreshCw :size="18" />
        Foto KTP Lain
      </button>
    </div>

    <div
      v-else
      class="grid gap-3 p-[18px] pt-0"
      data-testid="ocr-camera-error"
    >
      <div
        class="flex gap-3 rounded-[15px] border border-[#efcdca] bg-[#fff8f7] p-4 text-[#c42c28]"
      >
        <CircleAlert :size="22" />

        <p
          class="m-0 text-[11px]"
        >
          {{ errorMessage }}
        </p>
      </div>

      <div
        class="grid grid-cols-2 gap-2"
      >
        <button
          type="button"
          class="min-h-11 rounded-xl bg-[#eef8f4] text-xs font-bold text-[#006855]"
          @click="startCamera"
        >
          Coba Lagi
        </button>

        <button
          type="button"
          class="min-h-11 rounded-xl border border-[#d6e3de] bg-white text-xs font-bold"
          @click="useManual"
        >
          Input Manual
        </button>
      </div>
    </div>
  </article>
</template>