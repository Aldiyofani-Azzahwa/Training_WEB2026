export type BpntReportStatusCode =
  | 'draft'
  | 'final'

export type BpntResolutionCode =
  | 'transacted'
  | 'pending'
  | 'deceased'
  | 'moved_domicile'
  | 'not_claimed'

export interface BpntReportRegion {
  id: number | null
  name: string | null
}

export interface BpntReportPeriod {
  id: number
  code: string
  name: string
  year: number
  is_active: boolean
}

export interface BpntReportSummary {
  total_kpm: number
  transacted: number
  pending: number
  active_verifications: number
  deceased: number
  moved_domicile: number
  not_claimed: number
  completion_percentage: number
}

export interface BpntReportWilayahRow {
  kecamatan: BpntReportRegion
  kelurahan: BpntReportRegion
  total_kpm: number
  transacted: number
  pending: number
  deceased: number
  moved_domicile: number
  not_claimed: number
}

export interface BpntReportSurveyorRow {
  id: number
  name: string
  username: string
  assignment: {
    kecamatan: BpntReportRegion
    kelurahan: BpntReportRegion
  }
  transactions: number
  verifications: number
}

export interface BpntReportEWarungRow {
  id: number
  name: string
  transactions: number
}

export interface BpntReportParticipantRow {
  participant_id: number
  nik: string | null
  full_name: string
  address: string
  rt: string | null
  rw: string | null
  wilayah: {
    kecamatan: BpntReportRegion
    kelurahan: BpntReportRegion
  }
  resolution: {
    code: BpntResolutionCode
    label: string
    reason: string | null
  }
  surveyor: {
    id: number
    name: string
  } | null
  e_warung: {
    id: number
    name: string
  } | null
  resolved_at: string | null
}

export interface BpntReportSnapshot {
  period: Omit<BpntReportPeriod, 'is_active'>
  generated_at: string
  summary: BpntReportSummary
  wilayah: BpntReportWilayahRow[]
  surveyors: BpntReportSurveyorRow[]
  e_warungs: BpntReportEWarungRow[]
  participants: BpntReportParticipantRow[]
}

export interface BpntReport {
  id: number | null
  period: BpntReportPeriod
  status: {
    code: BpntReportStatusCode
    label: string
  }
  summary: BpntReportSummary
  can_finalize: boolean
  blocking_reason: string | null
  finalized_by: {
    id: number
    name: string
  } | null
  finalized_at: string | null
  snapshot: BpntReportSnapshot | null
}

export interface BpntReportListResponse {
  data: BpntReport[]
}

export interface BpntReportDetailResponse {
  data: BpntReport
}