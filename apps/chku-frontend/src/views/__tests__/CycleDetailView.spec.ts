import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { ref } from 'vue'

import { archiveBooks } from '@/data/club/archiveBooks'
import type { ArchiveCycle } from '@/types/club'
import CycleDetailView from '../CycleDetailView.vue'

const route = ref({
  params: {
    cycleNumber: '40',
  },
})

vi.mock('vue-router', () => ({
  RouterLink: RouterLinkStub,
  useRoute: () => route.value,
  useRouter: () => ({
    replace: vi.fn<() => void>(),
  }),
}))

function mountCycle(cycleNumber = '40') {
  route.value = {
    params: {
      cycleNumber,
    },
  }

  return mount(CycleDetailView, {
    global: {
      stubs: {
        RouterLink: RouterLinkStub,
      },
    },
  })
}

describe('CycleDetailView', () => {
  afterEach(() => {
    const cycle = archiveBooks.find((item) => item.cycleNumber === 41)
    if (cycle) {
      cycle.memberProgress = []
    }
  })

  it('renders completed cycle details, reviews and discussion', () => {
    const wrapper = mountCycle('41')

    expect(wrapper.text()).toContain('Тайная история')
    expect(wrapper.text()).toContain('Донна Тартт')
    expect(wrapper.text()).toContain('Елена')
    expect(wrapper.text()).toContain('9.2/10')
    expect(wrapper.text()).toContain('Отзывы клуба')
    expect(wrapper.text()).toContain('Дискуссия')
    expect(wrapper.text()).toContain('Встреча в архиве')
    expect(
      wrapper.findAllComponents(RouterLinkStub).some((link) => link.props('to') === '/meetings/1'),
    ).toBe(true)
  })

  it('breaks finished_at ties by member id in the leaderboard', () => {
    const cycle = archiveBooks.find((item) => item.cycleNumber === 41)
    if (!cycle) throw new Error('Expected cycle fixture')
    const memberProgress: ArchiveCycle['memberProgress'] = [
      {
        id: 9,
        member: { id: 6, name: 'Андрей Семёнов' },
        status: 'finished',
        progressPercent: 100,
        currentPage: null,
        notes: null,
        finishedAt: '2026-05-11T00:00:00Z',
      },
      {
        id: 8,
        member: { id: 5, name: 'Александр Семёнов' },
        status: 'finished',
        progressPercent: 100,
        currentPage: null,
        notes: null,
        finishedAt: '2026-05-11T00:00:00Z',
      },
    ]

    const archiveCycle = cycle as ArchiveCycle
    archiveCycle.memberProgress = memberProgress

    const wrapper = mountCycle('41')
    const rows = wrapper.findAll('.cycle-detail__leaderboard-item')

    expect(rows[0]!.text()).toContain('Александр Семёнов')
    expect(rows[0]!.find('.cycle-detail__owl--gold').exists()).toBe(true)
    expect(rows[1]!.text()).toContain('Андрей Семёнов')
    expect(rows[1]!.find('.cycle-detail__owl--silver').exists()).toBe(true)
  })

  it('renders fallback for unknown cycle number', () => {
    const wrapper = mountCycle('999')

    expect(wrapper.text()).toContain('Цикл не найден')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/archive')
  })
})
