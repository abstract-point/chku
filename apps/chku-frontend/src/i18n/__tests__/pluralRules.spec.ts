import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import ru from '@/i18n/ru'
import { russianPlural } from '@/i18n/pluralRules'

describe('russianPlural', () => {
  it.each([
    [1, 0],
    [21, 0],
    [2, 1],
    [4, 1],
    [23, 1],
    [0, 2],
    [5, 2],
    [11, 2],
    [12, 2],
    [14, 2],
    [111, 2],
  ])('%i -> form %i', (n, form) => {
    expect(russianPlural(n, 3)).toBe(form)
  })

  it('declines club counters', () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
      pluralRules: { ru: russianPlural },
    })
    const { t } = i18n.global

    expect(t('archive.ratingsN', { n: 2 }, 2)).toBe('2 оценки')
    expect(t('archive.reviewsN', { n: 1 }, 1)).toBe('1 отзыв')
    expect(t('dash.clubProgressCount', { n: 5 }, 5)).toBe('5 участников')
    expect(t('members.count', { n: 3 }, 3)).toBe('3 человека в клубе')
  })
})
