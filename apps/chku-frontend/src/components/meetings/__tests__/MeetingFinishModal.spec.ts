import { afterEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import MeetingFinishModal from '@/components/meetings/MeetingFinishModal.vue'
import type { BookProgressMember, MeetingDetail } from '@/types/dashboard'

const longReview =
  'Очень подробный отзыв о книге, который занимает много места и должен быть визуально ограничен тремя строками в модальном окне завершения встречи.'

const meeting = {
  id: '42',
  title: 'Финальная встреча',
  cycleLabel: 'Цикл #42',
  cycleId: 42,
  dateLabel: '18 октября',
  timeLabel: '19:00',
  isOnline: false,
  status: 'started',
  canStart: false,
  canFinish: true,
  missingRatingMemberIds: [],
  missingReadingMemberIds: [],
  rsvpStatus: 'attending',
  discussion: [],
  attendees: [
    { id: 1, name: 'Елена', status: 'attending' },
    { id: 2, name: 'Михаил', status: 'attending' },
    { id: 3, name: 'Анна', status: 'attending' },
  ],
  book: {
    title: 'Тень ветра',
    author: 'Карлос Руис Сафон',
  },
  ratings: [
    { memberId: 1, value: 8 },
    { memberId: 2, value: 9 },
    { memberId: 3, value: 7 },
  ],
  reviews: [
    { memberId: 1, text: longReview },
    { memberId: 2, text: 'Сильная книга.' },
  ],
} satisfies MeetingDetail

const memberProgress = [
  { id: 1, name: 'Елена', progress: 100, finishedAt: '2024-10-14T10:02:00Z' },
  { id: 2, name: 'Михаил', progress: 100, finishedAt: '2024-10-14T10:01:00Z' },
  { id: 3, name: 'Анна', progress: 100, finishedAt: '2024-10-14T10:03:00Z' },
  { id: 4, name: 'Дмитрий', progress: 100, finishedAt: '2024-10-14T09:00:00Z' },
] satisfies BookProgressMember[]

function mountModal() {
  return mount(MeetingFinishModal, {
    props: {
      isOpen: true,
      meeting,
      memberProgress,
    },
    global: {
      stubs: {
        AppModal: {
          props: ['isOpen', 'title'],
          template: `
            <section v-if="isOpen">
              <h2>{{ title }}</h2>
              <slot />
              <footer><slot name="footer" /></footer>
            </section>
          `,
        },
        UserAvatar: true,
      },
    },
  })
}

describe('MeetingFinishModal', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('shows ratings with reviews and requires owl approvals before confirming', async () => {
    const wrapper = mountModal()

    expect(wrapper.text()).toContain('Участники, оценки и отзывы')
    expect(wrapper.findAll('.finish-modal__review-item')).toHaveLength(3)
    expect(wrapper.text()).toContain('8/10')
    expect(wrapper.text()).toContain(longReview)
    expect(wrapper.find('.finish-modal__review').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('Я подтверждаю, что данные о прочтении участников достоверны')

    expect(wrapper.findAll('.finish-modal__owl-icon--gold')).toHaveLength(1)
    expect(wrapper.findAll('.finish-modal__owl-icon--silver')).toHaveLength(1)
    expect(wrapper.findAll('.finish-modal__owl-icon--bronze')).toHaveLength(1)
    expect(wrapper.findAll('.finish-modal__owl .finish-modal__badge--accent')).toHaveLength(0)
    expect(wrapper.text()).toContain('Для завершения цикла утвердите сов.')
    expect(wrapper.text()).toContain('Утвердить сов')

    const confirmButton = wrapper.findAll('button').find((button) => button.text().includes('Завершить'))
    expect(confirmButton).toBeTruthy()
    expect((confirmButton!.element as HTMLButtonElement).disabled).toBe(true)

    const checkboxes = wrapper.findAll('input[type="checkbox"]')
    expect(checkboxes).toHaveLength(1)
    await checkboxes[0]!.setValue(true)

    expect((confirmButton!.element as HTMLButtonElement).disabled).toBe(false)
    await confirmButton!.trigger('click')

    expect(wrapper.emitted('confirm')).toHaveLength(1)
  })
})
