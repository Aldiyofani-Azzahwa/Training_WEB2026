export interface HeadOfficeRegion {
    id: number
    code: string
    name: string
}

export interface HeadOfficePeriod {
    id: number
    code: string
    name: string
    year: number
}

export type HeadOfficeScopeLevel =
    | 'kota'
    | 'kecamatan'
    | 'kelurahan'

export interface HeadOfficeScope {
    level: HeadOfficeScopeLevel
    kecamatan: HeadOfficeRegion | null
    kelurahan: HeadOfficeRegion | null
}

export interface HeadOfficeSummary {
    total_kpm: number
    transacted: number
    not_transacted: number
    amount_disbursed: number
    completion_percentage: number
}

export interface HeadOfficeTrendItem {
    date: string
    label: string
    daily: number
    cumulative: number
}

export interface HeadOfficeKecamatanMetric
    extends HeadOfficeSummary {
    kecamatan: HeadOfficeRegion
}

export interface HeadOfficeKelurahanMetric
    extends HeadOfficeSummary {
    kecamatan: HeadOfficeRegion
    kelurahan: HeadOfficeRegion
}

export interface HeadOfficeRegions {
    kecamatans: HeadOfficeKecamatanMetric[]
    kelurahans: HeadOfficeKelurahanMetric[]
}

export interface HeadOfficeDashboard {
    period: HeadOfficePeriod | null
    scope: HeadOfficeScope
    summary: HeadOfficeSummary
    trend: HeadOfficeTrendItem[]
    regions: HeadOfficeRegions
    updated_at: string | null
}

export interface HeadOfficeDashboardQuery {
    kecamatan_id?: number
    kelurahan_id?: number
}

export interface HeadOfficeDashboardResponse {
    data: HeadOfficeDashboard
}