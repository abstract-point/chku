export function formatDateLabel(date?: string) {
  if (!date) return 'Дата уточняется'

  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'long',
  }).format(new Date(`${date}T00:00:00`))
}

export function formatTimeLabel(time?: string) {
  if (!time) return 'Время уточняется'

  return time.slice(0, 5)
}

