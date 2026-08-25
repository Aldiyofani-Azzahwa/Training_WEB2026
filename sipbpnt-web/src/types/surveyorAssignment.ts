export interface AssignmentPeriod {
  id: number
  code: string
  name: string
  year: number
}

export interface AssignmentKecamatan {
  id: number
  code: string
  name: string
}

export interface AssignmentKelurahan {
  id: number
  code: string
  name: string
  kecamatan: AssignmentKecamatan
}

export interface AssignmentSurveyor {
  id: number
  name: string
  username: string
  phone: string | null
  is_active: boolean
}

export interface AssignmentActor {
  id: number
  name: string
  username: string
}

export interface SurveyorAssignment {
  id: number
  period: AssignmentPeriod
  kelurahan: AssignmentKelurahan
  surveyor: AssignmentSurveyor
  assigned_by: AssignmentActor
  assigned_at: string | null
  created_at: string | null
  updated_at: string | null
}

export interface SurveyorAssignmentMeta {
  period: AssignmentPeriod
  total_kelurahans: number
  assigned_count: number
  unassigned_count: number
  total_assignments: number
  max_surveyors_per_kelurahan: number
}

export interface SurveyorAssignmentListResponse {
  data: SurveyorAssignment[]
  meta: SurveyorAssignmentMeta
}

export interface SurveyorAssignmentResponse {
  message: string
  data: SurveyorAssignment
}

export interface SurveyorAssignmentPayload {
  kelurahan_id: number
  surveyor_id: number
}

export interface SurveyorAssignmentDeleteResponse {
  message: string
}