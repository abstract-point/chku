import i18n from '@/i18n'

const df = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long' })
const tf = new Intl.DateTimeFormat('ru-RU', { hour: '2-digit', minute: '2-digit' })
const shortDf = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short' })
const fullDf = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })

export function formatDateLabel(date?: string) {
  if (!date) return i18n.global.t('dates.unknownDate')

  return df.format(new Date(`${date}T00:00:00`))
}

export function formatTimeLabel(time?: string) {
  if (!time) return i18n.global.t('dates.unknownTime')

  return time.slice(0, 5)
}

export function formatRelativeDate(iso?: string | null): string {
  if (!iso) return ''
  const date = new Date(iso)
  if (isNaN(date.getTime())) return ''
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / 86_400_000)
  const timeStr = tf.format(date)

  if (diffDays === 0) return timeStr
  if (diffDays === 1) return i18n.global.t('dates.yesterdayAt', { time: timeStr })
  if (diffDays < 7) return i18n.global.t('dates.daysAgo', { n: diffDays })
  if (date.getFullYear() === now.getFullYear()) return shortDf.format(date)
  return fullDf.format(date)
}
