export interface ManagerMonitoringRegion {
  id: number | null
  name: string | null
}

export interface ManagerMonitoringPeriod {
  id: number
  code: string
  name: string
  year: number
}

export interface ManagerMonitoringSummary {
  total_kpm: number
  transacted: number
  pending: number
  active_verifications: number
  deceased: number
  moved_domicile: number
  not_claimed: number
  outside_assignment: number
  completion_percentage: number
}

export interface ManagerMonitoringKecamatanMetric {
  kecamatan: ManagerMonitoringRegion
  total_kpm: number
  transacted: number
  active_verifications: number
  pending: number
}

export interface ManagerMonitoringKelurahanMetric {
  kecamatan: ManagerMonitoringRegion
  kelurahan: ManagerMonitoringRegion
  total_kpm: number
  transacted: number
  active_verifications: number
  pending: number
}

export interface ManagerMonitoringEWarungMetric {
  id: number
  name: string
  is_active: boolean
  transactions: number
}

export interface ManagerMonitoringSurveyorMetric {
  id: number
  name: string
  username: string
  assignment: {
    kecamatan: ManagerMonitoringRegion
    kelurahan: ManagerMonitoringRegion
  }
  transactions: number
  outside_assignment: number
}

export interface ManagerMonitoringTransaction {
  id: number
  participant: {
    id: number
    kpm: {
      id: number
      nik: string | null
      full_name: string
      address: string
      rt: string | null
      rw: string | null
    }
    wilayah: {
      kecamatan: ManagerMonitoringRegion
      kelurahan: ManagerMonitoringRegion
    }
  }
  surveyor: {
    id: number
    name: string
    username: string
    assignment: {
      kecamatan: ManagerMonitoringRegion
      kelurahan: ManagerMonitoringRegion
    }
  }
  e_warung: {
    id: number
    name: string
    is_active: boolean
  }
  outside_assignment: boolean
  transacted_at: string | null
}

export interface ManagerMonitoringBreakdowns {
  kecamatans: ManagerMonitoringKecamatanMetric[]
  kelurahans: ManagerMonitoringKelurahanMetric[]
  e_warungs: ManagerMonitoringEWarungMetric[]
  surveyors: ManagerMonitoringSurveyorMetric[]
}

export interface ManagerTransactionMonitoringQuery {
  search?: string
  kecamatan_id?: number
  kelurahan_id?: number
  e_warung_id?: number
  surveyor_id?: number
  outside_assignment?: 0 | 1
  page?: number
  per_page?: number
}

export interface ManagerTransactionMonitoringResponse {
  data: {
    period: ManagerMonitoringPeriod | null
    summary: ManagerMonitoringSummary
    breakdowns: ManagerMonitoringBreakdowns
    transactions: ManagerMonitoringTransaction[]
  }
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}