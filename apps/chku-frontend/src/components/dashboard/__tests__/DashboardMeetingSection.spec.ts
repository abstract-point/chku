import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import DashboardMeetingSection from '@/components/dashboard/DashboardMeetingSection.vue'
import { nextMeeting } from '@/data/dashboard'
import type { MeetingSummary } from '@/types/dashboard'

type DashboardMeetingSectionProps = {
  meeting?: MeetingSummary | null
  currentCycleStatus?: string | null
  isAdmin: boolean
}

function mountSection(props: DashboardMeetingSectionProps) {
  return mount(DashboardMeetingSection, {
    props,
    global: {
      stubs: {
        RouterLink: RouterLinkStub,
      },
    },
  })
}

describe('DashboardMeetingSection', () => {
  it('renders scheduled meeting details when a meeting exists', () => {
    const wrapper = mountSection({
      meeting: nextMeeting,
      currentCycleStatus: 'active',
      isAdmin: false,
    })

    expect(wrapper.text()).toContain('Следующая встреча')
    expect(wrapper.text()).toContain('18 октября')
    expect(wrapper.findAllComponents(RouterLinkStub).some((link) => link.props('to') === '/meetings/october-42')).toBe(true)
  })

  it('renders started meeting state', () => {
    const wrapper = mountSection({
      meeting: { ...nextMeeting, status: 'started' },
      currentCycleStatus: 'active',
      isAdmin: false,
    })

    expect(wrapper.text()).toContain('Встреча идёт')
  })

  it('shows create action to admins when active cycle has no meeting', () => {
    const wrapper = mountSection({
      meeting: null,
      currentCycleStatus: 'active',
      isAdmin: true,
    })

    expect(wrapper.text()).toContain('Встреча ещё не назначена')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/meetings/create')
  })

  it('shows empty state without create action to regular members', () => {
    const wrapper = mountSection({
      meeting: null,
      currentCycleStatus: 'active',
      isAdmin: false,
    })

    expect(wrapper.text()).toContain('Встреча ещё не назначена')
    expect(wrapper.findComponent(RouterLinkStub).exists()).toBe(false)
  })
})
