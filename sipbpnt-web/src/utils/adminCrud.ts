import type {
  FormErrors,
  StatusFilter,
} from '@/types/adminCrud'

/**
 * Sama persis dengan pengecekan status yang dulunya
 * ditulis ulang di dalam computed `filteredEWarungs`
 * dan `filteredSurveyors`.
 */
export function matchesStatusFilter(
  isActive: boolean,
  statusFilter: StatusFilter,
): boolean {
  if (
    statusFilter === 'active'
    && !isActive
  ) {
    return false
  }

  if (
    statusFilter === 'inactive'
    && isActive
  ) {
    return false
  }

  return true
}

/**
 * Sama persis dengan fungsi `firstFieldError`
 * yang sebelumnya ada dobel di EWarungView.vue
 * dan SurveyorView.vue.
 */
export function firstFieldError(
  errors: FormErrors,
  field: string,
): string | null {
  return errors[field]?.[0] ?? null
}
