export interface EWarung {
  id: number
  name: string
  is_active: boolean
  created_at: string | null
  updated_at: string | null
}

export interface EWarungMeta {
  total: number
  active: number
  inactive: number
}

export interface EWarungListResponse {
  data: EWarung[]
  meta: EWarungMeta
}

export interface EWarungResponse {
  message: string
  data: EWarung
}

export interface EWarungDeleteResponse {
  message: string
}

export interface EWarungCreatePayload {
  name: string
}

export interface EWarungUpdatePayload {
  name: string
}