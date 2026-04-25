import { describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { ref } from 'vue'

import MeetingDetailView from '../MeetingDetailView.vue'

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
      },
    },
  })
}

describe('MeetingDetailView', () => {
  it('renders meeting details, topics and attendees', () => {
    const wrapper = mountMeetingDetail()

    expect(wrapper.text()).toContain('Октябрьская встреча')
    expect(wrapper.text()).toContain('Цикл №42 · Завершение')
    expect(wrapper.text()).toContain('18 октября')
    expect(wrapper.text()).toContain('19:00 — 21:00')
    expect(wrapper.text()).toContain('Библиотека имени Некрасова, зал «Сад»')
    expect(wrapper.text()).toContain('Темы для обсуждения')
    expect(wrapper.text()).toContain('Значение Кладбища забытых книг.')
    expect(wrapper.text()).toContain('Екатерина Л.')
    expect(wrapper.text()).toContain('Тень ветра')
    expect(wrapper.text()).toContain('Карлос Руис Сафон')
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

  it('switches RSVP status', async () => {
    const wrapper = mountMeetingDetail()
    const buttons = wrapper.findAll('.meeting-detail__rsvp button')

    expect(buttons).toHaveLength(2)

    await buttons[0]!.trigger('click')
    expect(buttons[0]!.classes()).toContain('button--primary')
    expect(buttons[1]!.classes()).toContain('button--secondary')

    await buttons[1]!.trigger('click')
    expect(buttons[0]!.classes()).toContain('button--secondary')
    expect(buttons[1]!.classes()).toContain('button--primary')
  })

  it('renders fallback for unknown meeting id', () => {
    const wrapper = mountMeetingDetail('unknown-meeting')

    expect(wrapper.text()).toContain('Встреча не найдена')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/')
  })
})
