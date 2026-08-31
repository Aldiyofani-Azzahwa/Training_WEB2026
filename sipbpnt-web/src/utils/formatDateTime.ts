/**
 * Sama persis dengan `formatDate` (EWarungView.vue)
 * dan `formatLastLogin` (SurveyorView.vue).
 * Opsi Intl.DateTimeFormat-nya tidak diubah sama
 * sekali, cuma teks fallback saat value null yang
 * bisa beda per pemanggil (dulu '-' di EWarung,
 * 'Belum pernah' di Surveyor).
 */
export function formatDateTimeMedium(
  value: string | null,
  fallback = '-',
): string {
  if (!value) {
    return fallback
  }

  return new Intl
    .DateTimeFormat(
      'id-ID',
      {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Jakarta',
      },
    )
    .format(new Date(value))
}

/**
 * Sama persis dengan `formatDateTime` yang
 * sebelumnya ada dobel di TransactionMonitoringView.vue
 * (month: 'short') dan BpntReportView.vue
 * (month: 'long'). Gaya bulannya tetap bisa
 * diatur per pemanggil lewat parameter `month`
 * supaya tampilannya tidak berubah.
 */
export function formatDateTimeLong(
  value: string | null,
  month: 'short' | 'long' = 'long',
): string {
  if (value === null) {
    return '-'
  }

  const date =
    new Date(value)

  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return '-'
  }

  return new Intl.DateTimeFormat(
    'id-ID',
    {
      day: '2-digit',
      month,
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Asia/Jakarta',
    },
  ).format(date)
}
