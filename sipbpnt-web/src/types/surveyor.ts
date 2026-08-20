export interface Surveyor {
  id: number
  name: string
  username: string
  email: string | null
  phone: string | null
  is_active: boolean
  last_login_at: string | null
  created_at: string | null
}

export interface SurveyorMeta {
  total: number
  active: number
  inactive: number
}

export interface SurveyorListResponse {
  data: Surveyor[]
  meta: SurveyorMeta
}

export interface SurveyorResponse {
  message: string
  data: Surveyor
}

export interface SurveyorCreatePayload {
  name: string
  username: string
  email: string | null
  phone: string | null
  password: string
  password_confirmation: string
}

export interface SurveyorUpdatePayload {
  name: string
  username: string
  email: string | null
  phone: string | null
  password?: string
  password_confirmation?: string
}

export interface SurveyorOption {
  id: number
  name: string
  username: string
  phone: string | null
}

export interface SurveyorOptionsResponse {
  data: SurveyorOption[]
}