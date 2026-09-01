export interface SurveyorWorkspaceUser {
  id: number
  name: string
  username: string
}

export interface SurveyorWorkspacePeriod {
  id: number
  code: string
  name: string
  year: number
}

export interface SurveyorWorkspaceWilayah {
  id: number
  name: string
}

export interface SurveyorWorkspaceAssignment {
  id: number
  kecamatan: SurveyorWorkspaceWilayah
  kelurahan: SurveyorWorkspaceWilayah
}

export interface SurveyorWorkspaceContext {
  surveyor: SurveyorWorkspaceUser
  period: SurveyorWorkspacePeriod | null
  assignment: SurveyorWorkspaceAssignment | null
  kpm_count: number
}

export interface SurveyorParticipantKpm {
  id: number
  nik: string
  full_name: string
  birth_place: string | null
  birth_date: string | null
  address: string
  rt: string | null
  rw: string | null
}

export interface SurveyorParticipantWilayah {
  kelurahan: {
    id: number | null
    name: string | null
  }

  kecamatan: {
    id: number | null
    name: string | null
  }
}

export type SurveyorParticipantActivityCode =
  | 'pending'
  | 'transacted'
  | 'deceased'
  | 'moved_domicile'
  | 'not_claimed'

export interface SurveyorParticipantActivity {
  code: SurveyorParticipantActivityCode
  label: string
  is_final: boolean
  can_record_transaction: boolean
}

export interface SurveyorParticipant {
  id: number
  kpm: SurveyorParticipantKpm
  wilayah: SurveyorParticipantWilayah
  saldo_bpnt: number
  activity?: SurveyorParticipantActivity | null
}

export interface SurveyorParticipantPagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface SurveyorParticipantResponse {
  data: SurveyorParticipant[]
  meta: SurveyorParticipantPagination
}

export interface SurveyorParticipantQuery {
  search?: string
  page?: number
  per_page?: number
  status?: 'all' | 'belum' | 'sudah'
}

export interface SurveyorLookupScope {
  outside_assignment: boolean
  label: string

  surveyor_kelurahan: {
    id: number
    name: string
  }
}

export interface SurveyorNikLookupResult {
  participant: SurveyorParticipant
  scope: SurveyorLookupScope
}

export interface SurveyorNikLookupResponse {
  data: SurveyorNikLookupResult
}

export interface SurveyorEWarung {
  id: number
  name: string
  is_active: boolean
  created_at: string | null
  updated_at: string | null
}

export interface SurveyorEWarungResponse {
  data: SurveyorEWarung[]
}

export interface StoreSurveyorTransactionByNikPayload {
  nik: string
  e_warung_id: number
  bpnt_participant_id?: never
}

export interface StoreSurveyorTransactionByParticipantPayload {
  bpnt_participant_id: number
  e_warung_id: number
  nik?: never
}

export type StoreSurveyorTransactionPayload =
  | StoreSurveyorTransactionByNikPayload
  | StoreSurveyorTransactionByParticipantPayload

export interface SurveyorTransaction {
  id: number

  status: {
    code: 'transacted'
    label: 'Sudah Bertransaksi'
  }

  period: SurveyorWorkspacePeriod
  participant: SurveyorParticipant

  e_warung: {
    id: number
    name: string
  }

  surveyor: {
    id: number
    name: string
  }

  outside_assignment: boolean
  transacted_at: string
}

export interface SurveyorTransactionResponse {
  message: string
  data: SurveyorTransaction
}

export type KpmVerificationStatus =
  | 'deceased'
  | 'moved_domicile'
  | 'not_claimed'

export interface StoreKpmVerificationPayload {
  bpnt_participant_id: number
  status: KpmVerificationStatus
  reason?: string
}

export interface KpmVerification {
  id: number

  status: {
    code: KpmVerificationStatus
    label: string
  }

  reason: string | null
  is_cancelled: boolean
  period: SurveyorWorkspacePeriod
  participant: SurveyorParticipant

  surveyor: {
    id: number
    name: string
  }

  verified_at: string
  cancelled_at: string | null

  cancelled_by: {
    id: number
    name: string
  } | null
}

export interface KpmVerificationResponse {
  message: string
  data: KpmVerification
}

export interface SurveyorActivityQuery {
  transaction_page?: number
  verification_page?: number
  per_page?: number
}

export interface SurveyorActivityResponse {
  data: {
    transactions: SurveyorTransaction[]
    verifications: KpmVerification[]
  }

  meta: {
    transactions: SurveyorParticipantPagination
    verifications: SurveyorParticipantPagination
  }
}