import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'

import BannerMeetingAdmin from '../BannerMeetingAdmin.vue'
import type { MeetingSummary } from '@/types/dashboard'

function createMeeting(status: MeetingSummary['status']): MeetingSummary {
  return {
    id: '1',
    title: 'Октябрьская встреча',
    dateLabel: '18 октября',
    dayTimeLabel: 'Среда · 19:00',
    date: '2024-10-18',
    time: '19:00',
    place: 'Библиотека',
    isOnline: false,
    status,
    canStart: status === 'scheduled',
    canFinish: status === 'started',
    attendees: [],
  }
}

describe('BannerMeetingAdmin', () => {
  it('renders scheduled variant with link', () => {
    const wrapper = mount(BannerMeetingAdmin, {
      props: { meeting: createMeeting('scheduled') },
      global: { stubs: { RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('Встреча «Октябрьская встреча» запланирована')
    expect(wrapper.text()).toContain('Перейти к управлению встречей')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/meetings/1')
  })

  it('renders started variant with link', () => {
    const wrapper = mount(BannerMeetingAdmin, {
      props: { meeting: createMeeting('started') },
      global: { stubs: { RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('Встреча идёт сейчас')
    expect(wrapper.text()).toContain('Перейти к управлению встречей')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/meetings/1')
  })
})
