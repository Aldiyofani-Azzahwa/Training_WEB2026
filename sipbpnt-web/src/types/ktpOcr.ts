export type KtpOcrScannerState =
  | 'idle'
  | 'requesting_camera'
  | 'aligning'
  | 'captured'
  | 'processing'
  | 'detected'
  | 'error'

export interface KtpOcrProgress {
  progress: number
  message: string
}

export interface KtpOcrResult {
  nik: string
  confidence: number
}

export interface KtpPhotoInspection {
  acceptable: boolean
  message: string
}