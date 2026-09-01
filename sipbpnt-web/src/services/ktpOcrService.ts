import type {
  LoggerMessage,
  PSM,
  Worker,
} from 'tesseract.js'

import type {
  KtpOcrProgress,
  KtpOcrResult,
  KtpPhotoInspection,
  KtpPhotoQuality,
  KtpPhotoTone,
} from '@/types/ktpOcr'

const KTP_RATIO =
  85.6 / 53.98

const CAPTURE_WIDTH =
  1_920

const OCR_WIDTH =
  1_600

const PREVIEW_WIDTH =
  1_100

const SINGLE_LINE =
  '7' as PSM

const SPARSE_TEXT =
  '11' as PSM

const DIGITS =
  '0123456789'

const LABEL_CHARACTERS =
  'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789:'

interface Region {
  x: number
  y: number
  width: number
  height: number
}

interface OcrReading {
  text: string
  confidence: number
}

export interface NikReading {
  nik: string | null
  confidence: number
}

const LABEL_REGION: Region = {
  x: 0.02,
  y: 0.06,
  width: 0.96,
  height: 0.46,
}

const PREVIEW_REGION: Region = {
  x: 0.02,
  y: 0.1,
  width: 0.96,
  height: 0.34,
}

const NIK_BANDS: Region[] = [
  {
    x: 0.04,
    y: 0.12,
    width: 0.92,
    height: 0.18,
  },
  {
    x: 0.04,
    y: 0.18,
    width: 0.92,
    height: 0.18,
  },
  {
    x: 0.04,
    y: 0.24,
    width: 0.92,
    height: 0.18,
  },
  {
    x: 0.04,
    y: 0.3,
    width: 0.92,
    height: 0.18,
  },
]

let workerPromise:
  Promise<Worker> | null =
  null

let progressListener:
  (
    (
      progress:
        KtpOcrProgress,
    ) => void
  ) | null = null

let recognitionStart =
  0.2

let recognitionEnd =
  0.95

function emitProgress(
  progress: number,
  message: string,
): void {
  progressListener?.({
    progress:
      Math.min(
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

  canvas.width =
    Math.max(
      1,
      Math.round(width),
    )

  canvas.height =
    Math.max(
      1,
      Math.round(height),
    )

  return canvas
}

function getContext(
  canvas: HTMLCanvasElement,
): CanvasRenderingContext2D {
  const context =
    canvas.getContext(
      '2d',
      {
        willReadFrequently:
          true,
      },
    )

  if (!context) {
    throw new Error(
      'Browser tidak mendukung pemrosesan gambar KTP.',
    )
  }

  return context
}

function releaseCanvas(
  canvas: HTMLCanvasElement,
): void {
  canvas.width = 1
  canvas.height = 1
}

export function releaseKtpPhoto(
  photo: HTMLCanvasElement,
): void {
  releaseCanvas(photo)
}

function centeredSource(
  width: number,
  height: number,
): {
  x: number
  y: number
  width: number
  height: number
} {
  if (
    width <= 0
    || height <= 0
  ) {
    throw new Error(
      'Kamera belum siap mengambil foto.',
    )
  }

  if (
    width / height
    > KTP_RATIO
  ) {
    const cropWidth =
      height * KTP_RATIO

    return {
      x:
        (
          width
          - cropWidth
        ) / 2,
      y: 0,
      width: cropWidth,
      height,
    }
  }

  const cropHeight =
    width / KTP_RATIO

  return {
    x: 0,
    y:
      (
        height
        - cropHeight
      ) / 2,
    width,
    height:
      cropHeight,
  }
}

export function captureKtpPhoto(
  video: HTMLVideoElement,
): HTMLCanvasElement {
  const source =
    centeredSource(
      video.videoWidth,
      video.videoHeight,
    )

  const width =
    Math.min(
      CAPTURE_WIDTH,
      Math.round(
        source.width,
      ),
    )

  const height =
    Math.round(
      width / KTP_RATIO,
    )

  const canvas =
    createCanvas(
      width,
      height,
    )

  const context =
    getContext(canvas)

  context.imageSmoothingEnabled =
    true

  context.imageSmoothingQuality =
    'high'

  context.drawImage(
    video,
    source.x,
    source.y,
    source.width,
    source.height,
    0,
    0,
    width,
    height,
  )

  return canvas
}

function cropRegion(
  source:
    HTMLCanvasElement,
  region: Region,
  width: number,
): HTMLCanvasElement {
  const sourceX =
    source.width
    * region.x

  const sourceY =
    source.height
    * region.y

  const sourceWidth =
    source.width
    * region.width

  const sourceHeight =
    source.height
    * region.height

  const height =
    Math.round(
      (
        sourceHeight
        / sourceWidth
      ) * width,
    )

  const padding =
    Math.round(
      width * 0.02,
    )

  const canvas =
    createCanvas(
      width
      + padding * 2,
      height
      + padding * 2,
    )

  const context =
    getContext(canvas)

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
    width,
    height,
  )

  return canvas
}

function grayscale(
  data:
    Uint8ClampedArray,
): Uint8ClampedArray {
  const output =
    new Uint8ClampedArray(
      data.length / 4,
    )

  for (
    let source = 0,
    target = 0;
    source < data.length;
    source += 4,
    target++
  ) {
    output[target] =
      Math.round(
        (
          data[source]
          ?? 0
        ) * 0.299
        + (
          data[
          source + 1
          ]
          ?? 0
        ) * 0.587
        + (
          data[
          source + 2
          ]
          ?? 0
        ) * 0.114,
      )
  }

  return output
}

function histogram(
  values:
    Uint8ClampedArray,
): Uint32Array {
  const result =
    new Uint32Array(
      256,
    )

  for (
    const value
    of values
  ) {
    result[value] =
      (
        result[value]
        ?? 0
      ) + 1
  }

  return result
}

function percentile(
  counts: Uint32Array,
  total: number,
  ratio: number,
): number {
  const target =
    total * ratio

  let count = 0

  for (
    let value = 0;
    value < counts.length;
    value++
  ) {
    count +=
      counts[value]
      ?? 0

    if (
      count >= target
    ) {
      return value
    }
  }

  return 255
}

function normalizeImage(
  source:
    HTMLCanvasElement,
  binary = false,
): HTMLCanvasElement {
  const canvas =
    createCanvas(
      source.width,
      source.height,
    )

  const context =
    getContext(canvas)

  context.drawImage(
    source,
    0,
    0,
  )

  const image =
    context.getImageData(
      0,
      0,
      canvas.width,
      canvas.height,
    )

  const values =
    grayscale(
      image.data,
    )

  const counts =
    histogram(values)

  const low =
    percentile(
      counts,
      values.length,
      0.01,
    )

  const high =
    percentile(
      counts,
      values.length,
      0.99,
    )

  const range =
    Math.max(
      1,
      high - low,
    )

  const threshold =
    145

  for (
    let pixel = 0;
    pixel < values.length;
    pixel++
  ) {
    const normalized =
      Math.min(
        255,
        Math.max(
          0,
          Math.round(
            (
              (
                (
                  values[pixel]
                  ?? 0
                ) - low
              ) * 255
            ) / range,
          ),
        ),
      )

    const value =
      binary
        ? (
          normalized
            < threshold
            ? 0
            : 255
        )
        : normalized

    const index =
      pixel * 4

    image.data[index] =
      value

    image.data[index + 1] =
      value

    image.data[index + 2] =
      value

    image.data[index + 3] =
      255
  }

  context.putImageData(
    image,
    0,
    0,
  )

  return canvas
}

function imageQuality(
  canvas:
    HTMLCanvasElement,
): KtpPhotoQuality {
  const context =
    getContext(canvas)

  const image =
    context.getImageData(
      0,
      0,
      canvas.width,
      canvas.height,
    )

  const values =
    grayscale(
      image.data,
    )

  let total = 0
  let glare = 0
  let dark = 0

  for (
    const value
    of values
  ) {
    total += value

    if (value >= 246) {
      glare++
    }

    if (value <= 25) {
      dark++
    }
  }

  const brightness =
    total
    / Math.max(
      1,
      values.length,
    )

  let variance = 0
  let difference = 0
  let comparisons = 0

  for (
    let index = 1;
    index < values.length;
    index++
  ) {
    const value =
      values[index]
      ?? 0

    const previous =
      values[index - 1]
      ?? value

    variance +=
      (
        value
        - brightness
      ) ** 2

    difference +=
      Math.abs(
        value - previous,
      )

    comparisons++
  }

  const glareRatio =
    glare
    / Math.max(
      1,
      values.length,
    )

  const darkRatio =
    dark
    / Math.max(
      1,
      values.length,
    )

  let tone:
    KtpPhotoTone =
    'normal'

  if (
    glareRatio >= 0.15
  ) {
    tone = 'glare'
  } else if (
    brightness < 65
  ) {
    tone = 'dark'
  } else if (
    brightness > 210
  ) {
    tone = 'bright'
  }

  return {
    brightness,
    contrast:
      Math.sqrt(
        variance
        / Math.max(
          1,
          values.length,
        ),
      ),
    sharpness:
      difference
      / Math.max(
        1,
        comparisons,
      ),
    glareRatio,
    darkRatio,
    tone,
  }
}

export function inspectKtpPhoto(
  photo:
    HTMLCanvasElement,
): KtpPhotoInspection {
  const region =
    cropRegion(
      photo,
      LABEL_REGION,
      520,
    )

  try {
    const quality =
      imageQuality(region)

    if (
      quality.contrast < 2
      || quality.sharpness
      < 0.6
    ) {
      return {
        acceptable: false,
        message:
          'KTP belum terlihat jelas. Dekatkan KTP dan tunggu fokus kamera.',
        quality,
      }
    }

    const messages:
      Record<
        KtpPhotoTone,
        string
      > = {
      dark:
        'Foto gelap. Sistem akan menaikkan kontras tulisan.',
      bright:
        'Foto terang. Sistem akan menormalkan pencahayaan.',
      glare:
        'Pantulan terdeteksi. Sistem tetap mencoba membaca baris NIK.',
      normal:
        'Foto siap dibaca.',
    }

    return {
      acceptable: true,
      message:
        messages[
        quality.tone
        ],
      quality,
    }
  } finally {
    releaseCanvas(region)
  }
}

export function createNikPreviewUrl(
  photo:
    HTMLCanvasElement,
): string {
  const preview =
    cropRegion(
      photo,
      PREVIEW_REGION,
      PREVIEW_WIDTH,
    )

  try {
    return preview
      .toDataURL(
        'image/jpeg',
        0.92,
      )
  } finally {
    releaseCanvas(preview)
  }
}

export function extractNikCandidate(
  text: string,
): string | null {
  const candidates =
    new Set<string>()

  for (
    const line
    of text.split(
      /[\r\n]+/,
    )
  ) {
    const digits =
      line.replace(
        /\D+/g,
        '',
      )

    if (
      digits.length === 16
    ) {
      candidates.add(
        digits,
      )
    }
  }

  const grouped =
    text.match(
      /(?:\d[\s.:/_-]*){16}/g,
    ) ?? []

  for (
    const match
    of grouped
  ) {
    const digits =
      match.replace(
        /\D+/g,
        '',
      )

    if (
      digits.length === 16
    ) {
      candidates.add(
        digits,
      )
    }
  }

  return candidates.size === 1
    ? (
      [...candidates][0]
      ?? null
    )
    : null
}

function extractNikNearLabel(
  text: string,
): string | null {
  for (
    const line
    of text.split(
      /[\r\n]+/,
    )
  ) {
    const label =
      line.match(
        /N[I1]K\s*:?(.*)$/i,
      )

    if (label) {
      const digits =
        (
          label[1]
          ?? ''
        ).replace(
          /\D+/g,
          '',
        )

      if (
        digits.length === 16
      ) {
        return digits
      }
    }
  }

  return extractNikCandidate(
    text,
  )
}

export function hasValidNikStructure(
  nik: string,
): boolean {
  if (
    !/^\d{16}$/.test(nik)
    || nik.slice(
      0,
      6,
    ) === '000000'
  ) {
    return false
  }

  const encodedDay =
    Number(
      nik.slice(
        6,
        8,
      ),
    )

  const day =
    encodedDay > 40
      ? encodedDay - 40
      : encodedDay

  const month =
    Number(
      nik.slice(
        8,
        10,
      ),
    )

  return (
    day >= 1
    && day <= 31
    && month >= 1
    && month <= 12
  )
}

export function selectNikConsensus(
  readings:
    NikReading[],
): {
  nik: string
  confidence: number
} | null {
  const grouped =
    new Map<
      string,
      number[]
    >()

  for (
    const reading
    of readings
  ) {
    if (!reading.nik) {
      continue
    }

    grouped.set(
      reading.nik,
      [
        ...(
          grouped.get(
            reading.nik,
          ) ?? []
        ),
        reading.confidence,
      ],
    )
  }

  const ranked =
    [...grouped.entries()]
      .sort(
        (
          left,
          right,
        ) =>
          right[1].length
          - left[1].length,
      )

  const selected =
    ranked[0]

  if (
    !selected
    || (
      ranked[1]?.[1]
        .length
      ?? 0
    ) === selected[1].length
  ) {
    return null
  }

  return {
    nik:
      selected[0],
    confidence:
      selected[1]
        .reduce(
          (
            total,
            value,
          ) =>
            total + value,
          0,
        )
      / selected[1].length,
  }
}

function setRecognitionProgress(
  start: number,
  end: number,
): void {
  recognitionStart =
    start

  recognitionEnd =
    end
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
      (
        async () => {
          emitProgress(
            0.02,
            'Menyiapkan Tesseract OCR...',
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
                    message.status
                    ===
                    'recognizing text'
                  ) {
                    const progress =
                      recognitionStart
                      + message.progress
                      * (
                        recognitionEnd
                        - recognitionStart
                      )

                    emitProgress(
                      progress,
                      'Membaca tulisan pada KTP...',
                    )
                  }
                },
              },
            )

          emitProgress(
            0.15,
            'Tesseract OCR siap.',
          )

          return worker
        }
      )().catch(
        (
          error: unknown,
        ) => {
          workerPromise = null
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
  mode: PSM,
  whitelist: string,
  start: number,
  end: number,
): Promise<OcrReading> {
  setRecognitionProgress(
    start,
    end,
  )

  await worker
    .setParameters({
      tessedit_pageseg_mode:
        mode,
      tessedit_char_whitelist:
        whitelist,
      preserve_interword_spaces:
        '1',
      user_defined_dpi:
        '300',
    })

  const result =
    await worker.recognize(
      canvas,
    )

  return {
    text:
      result.data.text,
    confidence:
      Number.isFinite(
        result.data
          .confidence,
      )
        ? result.data
          .confidence
        : 0,
  }
}

function successfulResult(
  photo:
    HTMLCanvasElement,
  nik: string,
  confidence: number,
): KtpOcrResult {
  return {
    nik,
    confidence,
    alternatives: [],
    needsReview: true,
    previewUrl:
      createNikPreviewUrl(
        photo,
      ),
    warning:
      'OCR berhasil membaca 16 digit. Cocokkan hasil dengan foto sebelum mencari KPM.',
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

  const labelCrop =
    cropRegion(
      photo,
      LABEL_REGION,
      OCR_WIDTH,
    )

  const normalizedLabel =
    normalizeImage(
      labelCrop,
    )

  try {
    emitProgress(
      0.16,
      'Mencari tulisan NIK...',
    )

    const labelReading =
      await recognizeCanvas(
        worker,
        normalizedLabel,
        SPARSE_TEXT,
        LABEL_CHARACTERS,
        0.16,
        0.42,
      )

    const labelNik =
      extractNikNearLabel(
        labelReading.text,
      )

    if (labelNik) {
      emitProgress(
        1,
        'NIK berhasil dibaca.',
      )

      return successfulResult(
        photo,
        labelNik,
        labelReading
          .confidence,
      )
    }

    releaseCanvas(
      normalizedLabel,
    )

    for (
      let index = 0;
      index < NIK_BANDS.length;
      index++
    ) {
      const region =
        NIK_BANDS[index]
        ?? NIK_BANDS[0]

      if (!region) {
        continue
      }

      const band =
        cropRegion(
          photo,
          region,
          OCR_WIDTH,
        )

      const normalized =
        normalizeImage(
          band,
        )

      const start =
        0.43
        + index * 0.12

      const end =
        start + 0.11

      try {
        emitProgress(
          start,
          `Mencoba posisi NIK ${index + 1}...`,
        )

        const reading =
          await recognizeCanvas(
            worker,
            normalized,
            SINGLE_LINE,
            DIGITS,
            start,
            end,
          )

        const nik =
          extractNikCandidate(
            reading.text,
          )

        if (nik) {
          emitProgress(
            1,
            'NIK berhasil dibaca.',
          )

          return successfulResult(
            photo,
            nik,
            reading.confidence,
          )
        }
      } finally {
        releaseCanvas(band)

        releaseCanvas(
          normalized,
        )
      }
    }

    const binaryCrop =
      cropRegion(
        photo,
        LABEL_REGION,
        OCR_WIDTH,
      )

    const binary =
      normalizeImage(
        binaryCrop,
        true,
      )

    try {
      emitProgress(
        0.92,
        'Mencoba kontras tinggi...',
      )

      const reading =
        await recognizeCanvas(
          worker,
          binary,
          SPARSE_TEXT,
          DIGITS,
          0.92,
          0.99,
        )

      const nik =
        extractNikCandidate(
          reading.text,
        )

      if (nik) {
        emitProgress(
          1,
          'NIK berhasil dibaca.',
        )

        return successfulResult(
          photo,
          nik,
          reading.confidence,
        )
      }
    } finally {
      releaseCanvas(
        binaryCrop,
      )

      releaseCanvas(binary)
    }

    throw new Error(
      'NIK belum terbaca. Dekatkan KTP sampai memenuhi bingkai dan pastikan baris NIK terlihat.',
    )
  } finally {
    releaseCanvas(
      labelCrop,
    )

    if (
      normalizedLabel.width > 1
    ) {
      releaseCanvas(
        normalizedLabel,
      )
    }

    progressListener = null

    setRecognitionProgress(
      0.2,
      0.95,
    )
  }
}

async function dispose():
  Promise<void> {
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
    // Worker gagal dibuat.
  }
}

export const ktpOcrService = {
  prepare: prepareWorker,
  recognizePhoto:
    recognizeNikPhoto,
  dispose,
}