export interface Kelurahan {
  id: number
  code: string
  name: string
}

export interface Kecamatan {
  id: number
  code: string
  name: string
  kelurahans_count: number
  kelurahans: Kelurahan[]
}

export interface WilayahMeta {
  kecamatans_count: number
  kelurahans_count: number
}

export interface WilayahResponse {
  data: Kecamatan[]
  meta: WilayahMeta
}