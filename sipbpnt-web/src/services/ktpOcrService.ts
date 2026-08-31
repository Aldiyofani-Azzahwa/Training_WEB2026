import type {
  LoggerMessage,
  PSM,
  Worker,
} from 'tesseract.js'

import type {
  KtpOcrProgress,
  KtpOcrResult,
  KtpPhotoInspection,
} from '@/types/ktpOcr'

const KTP_ASPECT_RATIO =
  85.6 / 53.98

const MAX_CAPTURE_WIDTH =
  1_920

const INSPECTION_WIDTH = 480

const PRIMARY_OCR_WIDTH =
  1_280

const WIDE_OCR_WIDTH =
  1_440

const MIN_BRIGHTNESS = 48

const MAX_BRIGHTNESS = 218

const MIN_CONTRAST = 22

const MIN_SHARPNESS = 8

const SINGLE_LINE_PSM =
  '7' as PSM

const SPARSE_TEXT_PSM =
  '11' as PSM

interface NikRegion {
  x: number
  y: number
  width: number
  height: number
}

interface RecognitionAttempt {
  nik: string | null
  confidence: number
}

const PRIMARY_NIK_REGION:
  NikRegion = {
  x: 0.075,

  y: 0.16,

  width: 0.875,

  height: 0.235,
}

const WIDE_NIK_REGION:
  NikRegion = {
  x: 0.035,

  y: 0.09,

  width: 0.93,

  height: 0.4,
}

let workerPromise:
  Promise<Worker> | null =
    null

let progressListener:
  | ((
      progress:
        KtpOcrProgress,
    ) => void)
  | null = null

function emitProgress(
  progress: number,
  message: string,
): void {
  progressListener?.({
    progress: Math.min(
      1,
      Math.max(
        0,
        progress,
      ),
    ),

    message,
  })
}

function createCanvas(
  width: number,
  height: number,
): HTMLCanvasElement {
  const canvas =
    document.createElement(
      'canvas',
    )

  canvas.width = Math.max(
    1,
    Math.round(width),
  )

  canvas.height = Math.max(
    1,
    Math.round(height),
  )

  return canvas
}

function canvasContext(
  canvas: HTMLCanvasElement,
): CanvasRenderingContext2D {
  const context =
    canvas.getContext('2d', {
      willReadFrequently:
        true,
    })

  if (!context) {
    throw new Error(
      'Browser tidak mendukung pemrosesan foto KTP.',
    )
  }

  return context
}

function visibleVideoSource(
  video: HTMLVideoElement,
): {
  sx: number
  sy: number
  sw: number
  sh: number
} {
  const sourceWidth =
    video.videoWidth

  const sourceHeight =
    video.videoHeight

  if (
    sourceWidth <= 0 ||
    sourceHeight <= 0
  ) {
    throw new Error(
      'Kamera belum siap mengambil foto.',
    )
  }

  const sourceRatio =
    sourceWidth /
    sourceHeight

  if (
    sourceRatio >
    KTP_ASPECT_RATIO
  ) {
    const sw =
      sourceHeight *
      KTP_ASPECT_RATIO

    return {
      sx:
        (sourceWidth -
          sw) /
        2,

      sy: 0,

      sw,

      sh: sourceHeight,
    }
  }

  const sh =
    sourceWidth /
    KTP_ASPECT_RATIO

  return {
    sx: 0,

    sy:
      (sourceHeight -
        sh) /
      2,

    sw: sourceWidth,

    sh,
  }
}

export function captureKtpPhoto(
  video: HTMLVideoElement,
): HTMLCanvasElement {
  const source =
    visibleVideoSource(
      video,
    )

  const targetWidth =
    Math.min(
      MAX_CAPTURE_WIDTH,
      Math.round(
        source.sw,
      ),
    )

  const targetHeight =
    Math.round(
      targetWidth /
        KTP_ASPECT_RATIO,
    )

  const canvas =
    createCanvas(
      targetWidth,
      targetHeight,
    )

  const context =
    canvasContext(canvas)

  context.imageSmoothingEnabled =
    true

  context.imageSmoothingQuality =
    'high'

  context.drawImage(
    video,
    source.sx,
    source.sy,
    source.sw,
    source.sh,
    0,
    0,
    canvas.width,
    canvas.height,
  )

  return canvas
}

function cropRegion(
  source:
    HTMLCanvasElement,
  region: NikRegion,
  targetWidth: number,
): HTMLCanvasElement {
  const sourceX =
    source.width *
    region.x

  const sourceY =
    source.height *
    region.y

  const sourceWidth =
    source.width *
    region.width

  const sourceHeight =
    source.height *
    region.height

  const padding =
    Math.round(
      targetWidth *
        0.025,
    )

  const targetHeight =
    Math.round(
      (sourceHeight /
        sourceWidth) *
        targetWidth,
    )

  const canvas =
    createCanvas(
      targetWidth +
        padding * 2,

      targetHeight +
        padding * 2,
    )

  const context =
    canvasContext(canvas)

  context.fillStyle =
    '#ffffff'

  context.fillRect(
    0,
    0,
    canvas.width,
    canvas.height,
  )

  context.imageSmoothingEnabled =
    true

  context.imageSmoothingQuality =
    'high'

  context.drawImage(
    source,
    sourceX,
    sourceY,
    sourceWidth,
    sourceHeight,
    padding,
    padding,
    targetWidth,
    targetHeight,
  )

  return canvas
}

function grayscaleValues(
  data:
    Uint8ClampedArray,
): Uint8ClampedArray {
  const values =
    new Uint8ClampedArray(
      data.length / 4,
    )

  for (
    let sourceIndex = 0,
      targetIndex = 0;
    sourceIndex <
    data.length;
    sourceIndex += 4,
      targetIndex++
  ) {
    values[targetIndex] =
      Math.round(
        (data[
          sourceIndex
        ] ?? 0) *
          0.299 +
          (data[
            sourceIndex +
              1
          ] ?? 0) *
            0.587 +
          (data[
            sourceIndex +
              2
          ] ?? 0) *
            0.114,
      )
  }

  return values
}

function average(
  values:
    Uint8ClampedArray,
): number {
  if (
    values.length === 0
  ) {
    return 0
  }

  let total = 0

  for (const value of values) {
    total += value
  }

  return (
    total /
    values.length
  )
}

function standardDeviation(
  values:
    Uint8ClampedArray,
  mean: number,
): number {
  if (
    values.length === 0
  ) {
    return 0
  }

  let variance = 0

  for (const value of values) {
    const difference =
      value - mean

    variance +=
      difference *
      difference
  }

  return Math.sqrt(
    variance /
      values.length,
  )
}

function edgeDifference(
  values:
    Uint8ClampedArray,
  width: number,
): number {
  let total = 0

  let comparisons = 0

  for (
    let index =
      width + 1;
    index <
    values.length;
    index++
  ) {
    if (
      index %
        width ===
      0
    ) {
      continue
    }

    const value =
      values[index] ??
      0

    total += Math.abs(
      value -
        (values[
          index - 1
        ] ?? value),
    )

    total += Math.abs(
      value -
        (values[
          index - width
        ] ?? value),
    )

    comparisons += 2
  }

  return comparisons > 0
    ? total /
        comparisons
    : 0
}

function releaseCanvas(
  canvas:
    HTMLCanvasElement,
): void {
  canvas.width = 1

  canvas.height = 1
}

export function releaseKtpPhoto(
  photo:
    HTMLCanvasElement,
): void {
  releaseCanvas(photo)
}

export function inspectKtpPhoto(
  photo:
    HTMLCanvasElement,
): KtpPhotoInspection {
  const region =
    cropRegion(
      photo,
      PRIMARY_NIK_REGION,
      INSPECTION_WIDTH,
    )

  try {
    const context =
      canvasContext(region)

    const image =
      context.getImageData(
        0,
        0,
        region.width,
        region.height,
      )

    const grayscale =
      grayscaleValues(
        image.data,
      )

    const brightness =
      average(grayscale)

    const contrast =
      standardDeviation(
        grayscale,
        brightness,
      )

    const sharpness =
      edgeDifference(
        grayscale,
        region.width,
      )

    if (
      brightness <
      MIN_BRIGHTNESS
    ) {
      return {
        acceptable: false,

        message:
          'Foto terlalu gelap. Nyalakan lampu atau pindah ke tempat yang lebih terang.',
      }
    }

    if (
      brightness >
      MAX_BRIGHTNESS
    ) {
      return {
        acceptable: false,

        message:
          'Foto terlalu terang atau memantul. Miringkan KTP sedikit lalu foto ulang.',
      }
    }

    if (
      contrast <
      MIN_CONTRAST
    ) {
      return {
        acceptable: false,

        message:
          'Tulisan NIK belum terlihat jelas. Dekatkan KTP lalu foto ulang.',
      }
    }

    if (
      sharpness <
      MIN_SHARPNESS
    ) {
      return {
        acceptable: false,

        message:
          'Foto terlihat buram. Tunggu kamera fokus lalu foto ulang.',
      }
    }

    return {
      acceptable: true,

      message:
        'Kualitas foto baik. Membaca NIK...',
    }
  } finally {
    releaseCanvas(region)
  }
}

function otsuThreshold(
  grayscale:
    Uint8ClampedArray,
): number {
  const histogram =
    new Uint32Array(256)

  let totalSum = 0

  for (const value of grayscale) {
    histogram[value] =
      (histogram[
        value
      ] ?? 0) + 1

    totalSum += value
  }

  let backgroundWeight = 0

  let backgroundSum = 0

  let bestVariance = -1

  let bestThreshold = 128

  for (
    let threshold = 0;
    threshold < 256;
    threshold++
  ) {
    backgroundWeight +=
      histogram[
        threshold
      ] ?? 0

    if (
      backgroundWeight ===
      0
    ) {
      continue
    }

    const foregroundWeight =
      grayscale.length -
      backgroundWeight

    if (
      foregroundWeight ===
      0
    ) {
      break
    }

    backgroundSum +=
      threshold *
      (histogram[
        threshold
      ] ?? 0)

    const backgroundMean =
      backgroundSum /
      backgroundWeight

    const foregroundMean =
      (totalSum -
        backgroundSum) /
      foregroundWeight

    const meanDifference =
      backgroundMean -
      foregroundMean

    const variance =
      backgroundWeight *
      foregroundWeight *
      meanDifference *
      meanDifference

    if (
      variance >
      bestVariance
    ) {
      bestVariance =
        variance

      bestThreshold =
        threshold
    }
  }

  return bestThreshold
}

function enhanceNikImage(
  canvas:
    HTMLCanvasElement,
  binary: boolean,
): HTMLCanvasElement {
  const output =
    createCanvas(
      canvas.width,
      canvas.height,
    )

  const outputContext =
    canvasContext(output)

  outputContext.drawImage(
    canvas,
    0,
    0,
  )

  const image =
    outputContext.getImageData(
      0,
      0,
      output.width,
      output.height,
    )

  const grayscale =
    grayscaleValues(
      image.data,
    )

  const threshold =
    binary
      ? otsuThreshold(
          grayscale,
        )
      : 0

  for (
    let pixelIndex = 0;
    pixelIndex <
    grayscale.length;
    pixelIndex++
  ) {
    const sourceValue =
      grayscale[
        pixelIndex
      ] ?? 0

    const value = binary
      ? sourceValue >
        threshold
        ? 255
        : 0
      : Math.min(
          255,
          Math.max(
            0,
            (sourceValue -
              128) *
              1.55 +
              128,
          ),
        )

    const dataIndex =
      pixelIndex * 4

    image.data[
      dataIndex
    ] = value

    image.data[
      dataIndex + 1
    ] = value

    image.data[
      dataIndex + 2
    ] = value

    image.data[
      dataIndex + 3
    ] = 255
  }

  outputContext.putImageData(
    image,
    0,
    0,
  )

  return output
}

export function extractNikCandidate(
  text: string,
): string | null {
  const candidates =
    new Set<string>()

  for (
    const line of text.split(
      /[\r\n]+/,
    )
  ) {
    const digits =
      line.replace(
        /\D+/g,
        '',
      )

    if (
      digits.length ===
      16
    ) {
      candidates.add(
        digits,
      )
    }
  }

  const groupedMatches =
    text.match(
      /(?:\d[\s.:/_-]*){16}/g,
    ) ?? []

  for (
    const match of groupedMatches
  ) {
    const digits =
      match.replace(
        /\D+/g,
        '',
      )

    if (
      digits.length ===
      16
    ) {
      candidates.add(
        digits,
      )
    }
  }

  const allDigits =
    text.replace(
      /\D+/g,
      '',
    )

  if (
    allDigits.length ===
    16
  ) {
    candidates.add(
      allDigits,
    )
  }

  if (
    candidates.size !==
    1
  ) {
    return null
  }

  return (
    [...candidates][0] ??
    null
  )
}

async function prepareWorker(
  onProgress?: (
    progress:
      KtpOcrProgress,
  ) => void,
): Promise<Worker> {
  progressListener =
    onProgress ?? null

  if (!workerPromise) {
    workerPromise =
      (async () => {
        emitProgress(
          0.02,
          'Menyiapkan mesin OCR...',
        )

        const {
          createWorker,
          OEM,
        } =
          await import(
            'tesseract.js'
          )

        const worker =
          await createWorker(
            'eng',

            OEM.LSTM_ONLY,

            {
              logger: (
                message:
                  LoggerMessage,
              ) => {
                if (
                  message.status ===
                  'recognizing text'
                ) {
                  emitProgress(
                    0.25 +
                      message.progress *
                        0.7,

                    'Membaca 16 digit NIK...',
                  )

                  return
                }

                emitProgress(
                  message.progress *
                    0.2,

                  'Menyiapkan mesin OCR...',
                )
              },
            },

            {
              load_system_dawg:
                '0',

              load_freq_dawg:
                '0',

              load_number_dawg:
                '1',
            },
          )

        await worker.setParameters(
          {
            tessedit_char_whitelist:
              '0123456789',

            tessedit_pageseg_mode:
              SINGLE_LINE_PSM,

            preserve_interword_spaces:
              '0',

            user_defined_dpi:
              '300',
          },
        )

        emitProgress(
          1,
          'Mesin OCR siap.',
        )

        return worker
      })().catch(
        (
          error: unknown,
        ) => {
          workerPromise =
            null

          throw error
        },
      )
  }

  return workerPromise
}

async function recognizeCanvas(
  worker: Worker,
  canvas:
    HTMLCanvasElement,
  pageSegmentationMode:
    PSM,
): Promise<RecognitionAttempt> {
  await worker.setParameters({
    tessedit_pageseg_mode:
      pageSegmentationMode,
  })

  const recognition =
    await worker.recognize(
      canvas,
    )

  return {
    nik: extractNikCandidate(
      recognition.data
        .text,
    ),

    confidence:
      Number.isFinite(
        recognition.data
          .confidence,
      )
        ? recognition.data
            .confidence
        : 0,
  }
}

async function recognizeRegion(
  worker: Worker,
  photo:
    HTMLCanvasElement,
  region: NikRegion,
  targetWidth: number,
  binary: boolean,
  pageSegmentationMode:
    PSM,
): Promise<RecognitionAttempt> {
  const cropped =
    cropRegion(
      photo,
      region,
      targetWidth,
    )

  const enhanced =
    enhanceNikImage(
      cropped,
      binary,
    )

  try {
    return await recognizeCanvas(
      worker,
      enhanced,
      pageSegmentationMode,
    )
  } finally {
    releaseCanvas(
      cropped,
    )

    releaseCanvas(
      enhanced,
    )
  }
}

async function recognizeNikPhoto(
  photo:
    HTMLCanvasElement,
  onProgress?: (
    progress:
      KtpOcrProgress,
  ) => void,
): Promise<KtpOcrResult> {
  progressListener =
    onProgress ?? null

  const worker =
    await prepareWorker(
      onProgress,
    )

  try {
    emitProgress(
      0.22,
      'Membaca baris NIK dari foto...',
    )

    const primaryAttempt =
      await recognizeRegion(
        worker,
        photo,
        PRIMARY_NIK_REGION,
        PRIMARY_OCR_WIDTH,
        false,
        SINGLE_LINE_PSM,
      )

    if (
      primaryAttempt.nik
    ) {
      emitProgress(
        1,
        'NIK berhasil dibaca.',
      )

      return {
        nik:
          primaryAttempt.nik,

        confidence:
          primaryAttempt.confidence,
      }
    }

    emitProgress(
      0.48,
      'Memperjelas warna angka NIK...',
    )

    const binaryAttempt =
      await recognizeRegion(
        worker,
        photo,
        PRIMARY_NIK_REGION,
        PRIMARY_OCR_WIDTH,
        true,
        SINGLE_LINE_PSM,
      )

    if (
      binaryAttempt.nik
    ) {
      emitProgress(
        1,
        'NIK berhasil dibaca.',
      )

      return {
        nik:
          binaryAttempt.nik,

        confidence:
          binaryAttempt.confidence,
      }
    }

    emitProgress(
      0.72,
      'Mencari NIK pada area foto yang lebih lebar...',
    )

    const wideAttempt =
      await recognizeRegion(
        worker,
        photo,
        WIDE_NIK_REGION,
        WIDE_OCR_WIDTH,
        false,
        SPARSE_TEXT_PSM,
      )

    if (
      wideAttempt.nik
    ) {
      emitProgress(
        1,
        'NIK berhasil dibaca.',
      )

      return {
        nik:
          wideAttempt.nik,

        confidence:
          wideAttempt.confidence,
      }
    }

    throw new Error(
      'NIK 16 digit belum terbaca. Pastikan seluruh KTP memenuhi bingkai, posisi mendatar, tidak silau, dan tulisan NIK fokus.',
    )
  } finally {
    progressListener =
      null
  }
}

async function dispose(): Promise<void> {
  const activeWorker =
    workerPromise

  workerPromise = null

  progressListener = null

  if (!activeWorker) {
    return
  }

  try {
    const worker =
      await activeWorker

    await worker.terminate()
  } catch {
    // Worker yang gagal dibuat tidak memerlukan terminasi tambahan.
  }
}

export const ktpOcrService = {
  prepare: prepareWorker,

  recognizePhoto:
    recognizeNikPhoto,

  dispose,
}