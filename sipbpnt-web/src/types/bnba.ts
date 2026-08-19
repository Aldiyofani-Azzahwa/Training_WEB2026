export type BnbaRowStatus =
  | 'valid'
  | 'warning'
  | 'invalid'
  | 'duplicate'

export type BnbaImportStatus =
  | 'preview_ready'
  | 'confirmed'
  | 'failed'

export interface BnbaImportSummary {
  total: number
  valid: number
  warning: number
  invalid: number
  duplicate: number
}

export interface BpntPeriodBnba {
  id: number
  status: BnbaImportStatus
  original_name: string
  summary: BnbaImportSummary
  confirmed_at: string | null
  created_at: string | null
}

export interface BpntPeriod {
  id: number
  code: string
  name: string
  year: number

  imports_count: number
  participants_count: number

  can_delete: boolean
  can_edit_year: boolean

  bnba:
    BpntPeriodBnba | null

  created_at: string | null
  updated_at: string | null
}

export interface CreateBpntPeriodPayload {
  name: string
  year: number
}

export interface UpdateBpntPeriodPayload {
  name: string
  year: number
}

export interface BnbaImportPeriod {
  id: number
  code: string
  name: string
  year: number
}

export interface BnbaImportUser {
  id: number
  name: string
  username: string
}

export interface BnbaImport {
  id: number

  period?:
    BnbaImportPeriod

  status:
    BnbaImportStatus

  original_name: string

  summary:
    BnbaImportSummary

  uploaded_by?:
    BnbaImportUser | null

  confirmed_at:
    string | null

  created_at:
    string | null
}

export interface BnbaMonthlyStatuses {
  jan: string | null
  feb: string | null
  mar: string | null
  apr: string | null
  may: string | null
  jun: string | null
  jul: string | null
  aug: string | null
  sep: string | null
  oct: string | null
  nov: string | null
  dec: string | null
}

export interface BnbaImportRow {
  id: number

  row_number: number

  status:
    BnbaRowStatus

  membership_year:
    string | null

  nik: string | null
  nkk: string | null

  full_name:
    string | null

  birth_place:
    string | null

  birth_date:
    string | null

  mother_name:
    string | null

  address:
    string | null

  rt: string | null
  rw: string | null

  kelurahan:
    string | null

  kecamatan:
    string | null

  account_number:
    string | null

  e_warung_name:
    string | null

  source_status:
    string | null

  source_description:
    string | null

  monthly_statuses:
    Partial<BnbaMonthlyStatuses>

  sk_status:
    string | null

  sk_description:
    string | null

  apbn_march_status:
    string | null

  welfare_rank:
    number | null

  nominal:
    number | null

  errors: string[]
  warnings: string[]
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface BpntPeriodListResponse {
  data: BpntPeriod[]
}

export interface BpntPeriodResponse {
  message: string
  data: BpntPeriod
}

export interface ApiMessageResponse {
  message: string

  data?: {
    imports_deleted?: number
    participants_deleted?: number
  }
}

export interface BnbaImportResponse {
  message: string
  data: BnbaImport
}

export interface BnbaImportHistoryResponse {
  data: BnbaImport[]
  meta: PaginationMeta
}

export interface BnbaPreviewResponse {
  data: {
    import: BnbaImport
    rows: BnbaImportRow[]
  }

  meta: PaginationMeta
}

export interface BnbaPreviewParams {
  status?: BnbaRowStatus
  search?: string
  page?: number
  per_page?: number
}

export interface BnbaHistoryParams {
  page?: number
  per_page?: number
}

export interface LaravelValidationErrors {
  [field: string]: string[]
}

export interface LaravelErrorResponse {
  message?: string
  errors?: LaravelValidationErrors
}

export interface BnbaParticipantKpm {
  id: number

  nik: string | null
  nkk: string | null

  full_name: string

  birth_place: string | null
  birth_date: string | null
  mother_name: string | null

  address: string

  rt: string | null
  rw: string | null

  kelurahan: string
  kecamatan: string

  account_number: string | null
}

export interface BnbaParticipantPeriod {
  id: number
  code: string
  name: string
  year: number
}

export interface BnbaParticipantImport {
  id: number
  row_number: number
  confirmed_at: string | null
}

export interface BnbaParticipant {
  id: number

  period:
    BnbaParticipantPeriod

  kpm:
    BnbaParticipantKpm

  membership_year:
    string | null

  e_warung_name:
    string | null

  source_status:
    string | null

  source_description:
    string | null

  monthly_statuses:
    Partial<BnbaMonthlyStatuses>

  sk_status:
    string | null

  sk_description:
    string | null

  apbn_march_status:
    string | null

  welfare_rank:
    number | null

  entitlement_amount:
    number

  import:
    BnbaParticipantImport
}

export interface BnbaParticipantFilters {
  period_id: number

  search?: string
  kecamatan?: string
  kelurahan?: string
  e_warung?: string

  page?: number
  per_page?: number
}

export interface BnbaParticipantListResponse {
  data: BnbaParticipant[]
  meta: PaginationMeta
}

export interface BnbaParticipantFilterOptions {
  kecamatan: string[]
  kelurahan: string[]
  e_warungs: string[]
}

export interface BnbaParticipantFilterOptionsResponse {
  data: BnbaParticipantFilterOptions
}