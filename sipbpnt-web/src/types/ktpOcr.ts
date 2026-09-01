export type KtpOcrScannerState =
  | 'idle'
  | 'requesting_camera'
  | 'aligning'
  | 'captured'
  | 'processing'
  | 'detected'
  | 'error'

export type KtpPhotoTone =
  | 'dark'
  | 'normal'
  | 'bright'
  | 'glare'

export interface KtpOcrProgress {
  progress: number
  message: string
}

export interface KtpPhotoQuality {
  brightness: number
  contrast: number
  sharpness: number
  glareRatio: number
  darkRatio: number
  tone: KtpPhotoTone
}

export interface KtpPhotoInspection {
  acceptable: boolean
  message: string
  quality: KtpPhotoQuality
}

export interface KtpOcrResult {
  nik: string
  confidence: number
  alternatives: string[]
  needsReview: boolean
  previewUrl: string
  warning?: string
}