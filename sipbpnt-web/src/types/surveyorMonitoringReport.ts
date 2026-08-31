export interface SurveyorMonitoringReportPeriod {
  id: number
  code: string
  name: string
  year: number
  allocation_label: string
}

export interface SurveyorMonitoringReportWilayah {
  id: number
  name: string
}

export interface SurveyorMonitoringReportAssignment {
  id: number
  kecamatan: SurveyorMonitoringReportWilayah
  kelurahan: SurveyorMonitoringReportWilayah
}

export interface SurveyorMonitoringReportReason {
  label: string
  count: number
}

export interface SurveyorMonitoringReportSummary {
  total_kpm: number
  taking: number
  not_taking: number
  deceased: number
  moved_domicile: number
  not_claimed: number
  pending: number
  total_balance: number
  e_warungs: string[]
  reason_summary: SurveyorMonitoringReportReason[]
  evaluation: string
}

export interface SurveyorMonitoringReportEditable {
  commodities: string[]
  social_officer_name: string | null
  distribution_assistant_name: string | null
}

export interface SurveyorMonitoringReportSurveyor {
  id: number
  name: string
}

export interface SurveyorMonitoringReport {
  id: number | null
  period: SurveyorMonitoringReportPeriod
  surveyor: SurveyorMonitoringReportSurveyor
  assignment: SurveyorMonitoringReportAssignment
  editable: SurveyorMonitoringReportEditable
  summary: SurveyorMonitoringReportSummary
  updated_at: string | null
}

export interface UpdateSurveyorMonitoringReportPayload {
  commodities: string[]
  social_officer_name: string | null
  distribution_assistant_name: string | null
}

export interface SurveyorMonitoringReportResponse {
  message?: string
  data: SurveyorMonitoringReport
}

export interface SurveyorMonitoringReportPdf {
  blob: Blob
  filename: string
}