import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { ref } from 'vue'

import MeetingDetailView from '../MeetingDetailView.vue'
import { patchMeetingDetail, resetAuthSession, resetMeetingDetail, setAuthRoles } from '@/test/setup'

const route = ref({
  params: {
    id: 'october-42',
  },
})

vi.mock('vue-router', () => ({
  RouterLink: RouterLinkStub,
  useRoute: () => route.value,
}))

function mountMeetingDetail(id = 'october-42') {
  route.value = {
    params: {
      id,
    },
  }

  return mount(MeetingDetailView, {
    global: {
      stubs: {
        RouterLink: RouterLinkStub,
        AppModal: true,
        MeetingFinishModal: true,
      },
    },
  })
}

describe('MeetingDetailView', () => {
  afterEach(() => {
    resetAuthSession()
    resetMeetingDetail()
  })

  it('renders meeting details, topics and attendees', () => {
    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).toContain('Октябрьская встреча')
    expect(wrapper.text()).toContain('Цикл №42 · Завершение')
    expect(wrapper.text()).toContain('Основная информация о встрече')
    expect(wrapper.text()).toContain('18 октября')
    expect(wrapper.text()).toContain('19:00 — 21:00')
    expect(wrapper.text()).toContain('Библиотека имени Некрасова, зал «Сад»')
    expect(wrapper.text()).toContain('Темы для обсуждения')
    expect(wrapper.text()).toContain('Значение Кладбища забытых книг.')
    expect(wrapper.text()).toContain('Участники встречи')
    expect(wrapper.text()).toContain('Екатерина Л.')
    expect(wrapper.text()).toContain('Тень ветра')
    expect(wrapper.text()).toContain('Карлос Руис Сафон')
    expect(wrapper.text()).not.toContain('Ваш RSVP')
  })

  it('allows adding a new topic', async () => {
    const wrapper = mountMeetingDetail()
    const input = wrapper.find('input[type="text"]')
    const button = wrapper.find('button[type="button"]')

    await input.setValue('Новая тема для обсуждения')
    await button.trigger('click')

    expect(wrapper.text()).toContain('Новая тема для обсуждения')
    expect((input.element as HTMLInputElement).value).toBe('')
  })

  it('shows pending participation state in participants block', () => {
    patchMeetingDetail({
      rsvpStatus: 'pending',
      attendees: [{ id: 2, name: 'Михаил К.', status: 'attending' }],
    })

    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).not.toContain('Вы участвуете во встрече')
    expect(wrapper.text()).not.toContain('Вы не сможете прийти')
    expect(wrapper.text()).toContain('Буду на встрече')
  })

  it('shows not attending participation state in participants block', () => {
    patchMeetingDetail({
      rsvpStatus: 'not_attending',
      attendees: [{ id: 2, name: 'Михаил К.', status: 'attending' }],
    })

    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).toContain('Вы не сможете прийти')
    expect(wrapper.text()).toContain('Буду на встрече')
  })

  it('allows current attendee to decline from participants list', async () => {
    patchMeetingDetail({
      rsvpStatus: 'attending',
      attendees: [{ id: 1, name: 'Екатерина Л.', status: 'attending' }],
    })

    const wrapper = mountMeetingDetail()
    const declineButton = wrapper.find('.meeting-detail__decline-text')

    expect(wrapper.text()).toContain('Вы участвуете во встрече')
    expect(wrapper.text()).not.toContain('Буду на встрече')
    expect(declineButton.exists()).toBe(true)
    expect(wrapper.text()).toContain('Не смогу')

    await declineButton.trigger('click')

    expect(wrapper.text()).toContain('Вы не сможете прийти')
  })

  it('shows meeting status block for everyone', () => {
    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).toContain('Статус встречи')
    expect(wrapper.text()).toContain('Встреча запланирована')
  })

  it('shows scheduled admin control with start button', () => {
    setAuthRoles(['admin'])

    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).toContain('Управление встречей')
    expect(wrapper.text()).toContain('Начать встречу')
    expect(wrapper.text()).not.toContain('Закончить встречу и цикл')
  })

  it('disables start control until meeting has two attendees', () => {
    setAuthRoles(['admin'])
    patchMeetingDetail({
      canStart: false,
      attendees: [{ id: 1, name: 'Екатерина Л.', status: 'attending' }],
    })

    const wrapper = mountMeetingDetail()
    const startButton = wrapper
      .findAll('button')
      .find((button) => button.text().includes('Начать встречу'))

    expect(startButton).toBeTruthy()
    expect((startButton!.element as HTMLButtonElement).disabled).toBe(true)
    expect(wrapper.text()).toContain('Нужно минимум 2 участника со статусом «Буду».')
  })

  it('allows start control when meeting has two attendees', () => {
    setAuthRoles(['admin'])
    patchMeetingDetail({
      canStart: true,
      attendees: [
        { id: 1, name: 'Екатерина Л.', status: 'attending' },
        { id: 2, name: 'Михаил К.', status: 'attending' },
      ],
    })

    const wrapper = mountMeetingDetail()
    const startButton = wrapper
      .findAll('button')
      .find((button) => button.text().includes('Начать встречу'))

    expect(startButton).toBeTruthy()
    expect((startButton!.element as HTMLButtonElement).disabled).toBe(false)
    expect(wrapper.text()).not.toContain('Нужно минимум 2 участника со статусом «Буду».')
  })

  it('shows started admin control with finish button', () => {
    setAuthRoles(['admin'])
    patchMeetingDetail({ status: 'started', canStart: false, canFinish: true })

    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).toContain('Управление встречей')
    expect(wrapper.text()).toContain('Закончить встречу и цикл')
    expect(wrapper.text()).not.toContain('Начать встречу')
  })

  it('shows rating and review form only when meeting is started and user is attending', () => {
    const scheduledWrapper = mountMeetingDetail()

    expect(scheduledWrapper.text()).not.toContain('Оценка и отзыв')
    expect(scheduledWrapper.find('input[type="number"]').exists()).toBe(false)

    patchMeetingDetail({ status: 'started', rsvpStatus: 'attending' })
    const startedWrapper = mountMeetingDetail()

    expect(startedWrapper.text()).toContain('Оценка и отзыв')
    expect(startedWrapper.find('input[type="number"]').exists()).toBe(true)
  })

  it('hides rating form when meeting is started but user is not attending', () => {
    patchMeetingDetail({ status: 'started', rsvpStatus: 'not_attending' })
    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).not.toContain('Оценка и отзыв')
    expect(wrapper.find('input[type="number"]').exists()).toBe(false)
  })

  it('shows saved rating instead of form when user has already rated', () => {
    patchMeetingDetail({
      status: 'started',
      rsvpStatus: 'attending',
      ratings: [{ memberId: 1, value: 8 }],
      reviews: [{ memberId: 1, text: 'Отличная книга!' }],
    })

    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).toContain('Оценка и отзыв')
    expect(wrapper.text()).toContain('8')
    expect(wrapper.text()).toContain('из 10')
    expect(wrapper.text()).toContain('Отличная книга!')
    expect(wrapper.text()).toContain('Редактировать')
    expect(wrapper.find('#meeting-rating').exists()).toBe(false)
  })

  it('hides edit button for finished meeting', () => {
    patchMeetingDetail({
      status: 'finished',
      rsvpStatus: 'attending',
      ratings: [{ memberId: 1, value: 8 }],
    })

    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).not.toContain('Редактировать')
  })

  it('shows rating form when edit button is clicked', async () => {
    patchMeetingDetail({
      status: 'started',
      rsvpStatus: 'attending',
      ratings: [{ memberId: 1, value: 8 }],
      reviews: [{ memberId: 1, text: 'Отличная книга!' }],
    })

    const wrapper = mountMeetingDetail()

    const editButton = wrapper
      .findAll('button')
      .find((button) => button.text().includes('Редактировать'))
    expect(editButton).toBeTruthy()

    await editButton!.trigger('click')

    const ratingInput = wrapper.find('input[type="number"]')
    expect(ratingInput.exists()).toBe(true)
    expect((ratingInput.element as HTMLInputElement).value).toBe('8')
    expect((wrapper.find('#meeting-review').element as HTMLTextAreaElement).value).toBe(
      'Отличная книга!',
    )
    expect(wrapper.text()).toContain('Отмена')
  })

  it('hides admin panel for finished meeting', () => {
    setAuthRoles(['admin'])
    patchMeetingDetail({ status: 'finished', canStart: false, canFinish: false })

    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).toContain('Встреча завершена')
    expect(wrapper.text()).not.toContain('Управление встречей')
    expect(wrapper.text()).not.toContain('Редактировать')
    expect(wrapper.text()).not.toContain('Ваш RSVP')
    expect(wrapper.text()).not.toContain('Добавить тему')
    expect(wrapper.text()).not.toContain('Оценка и отзыв')
  })

  it('renders fallback for unknown meeting id', () => {
    const wrapper = mountMeetingDetail('unknown-meeting')

    expect(wrapper.text()).toContain('Встреча не найдена')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/')
  })
})
