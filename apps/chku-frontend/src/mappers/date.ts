import i18n from '@/i18n'

const df = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long' })

export function formatDateLabel(date?: string) {
  if (!date) return i18n.global.t('dates.unknownDate')

  return df.format(new Date(`${date}T00:00:00`))
}

export function formatTimeLabel(time?: string) {
  if (!time) return i18n.global.t('dates.unknownTime')

  return time.slice(0, 5)
}
