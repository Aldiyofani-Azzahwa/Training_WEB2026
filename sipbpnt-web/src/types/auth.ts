export type UserRole =
  | 'admin_dinsos'
  | 'manager'
  | 'surveyor'
  | 'kepala_dinas'

export interface AuthUser {
  id: number
  name: string
  username: string
  email: string | null
  phone: string | null
  role: UserRole
  role_label: string
  is_active: boolean
  last_login_at: string | null
  modules: string[]
}

export interface LoginPayload {
  username: string
  password: string
  remember: boolean
}

export interface ApiResponse<T> {
  message?: string
  data: T
}

export interface ValidationErrorResponse {
  message: string
  errors?: Record<string, string[]>
}