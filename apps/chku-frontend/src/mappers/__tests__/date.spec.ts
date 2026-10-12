import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { formatRelativeDate } from '@/mappers/date'

describe('formatRelativeDate', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date(2026, 9, 13, 0, 30))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('shows only time for messages from today', () => {
    expect(formatRelativeDate(new Date(2026, 9, 13, 0, 10).toISOString())).toBe('00:10')
  })

  it('treats late last evening as yesterday even within 24 hours', () => {
    expect(formatRelativeDate(new Date(2026, 9, 12, 23, 0).toISOString())).toBe('вчера, 23:00')
  })

  it('treats early yesterday morning as yesterday', () => {
    expect(formatRelativeDate(new Date(2026, 9, 12, 4, 46).toISOString())).toBe('вчера, 04:46')
  })

  it('counts calendar days for older messages', () => {
    expect(formatRelativeDate(new Date(2026, 9, 11, 22, 46).toISOString())).toBe('2 дн. назад')
  })
})
