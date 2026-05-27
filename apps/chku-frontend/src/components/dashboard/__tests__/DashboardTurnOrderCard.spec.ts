import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import DashboardTurnOrderCard from '@/components/dashboard/DashboardTurnOrderCard.vue'
import type { TurnOrderMember } from '@/types/dashboard'

function mountCard(members: TurnOrderMember[]) {
  return mount(DashboardTurnOrderCard, {
    props: {
      members,
      cycleStatus: 'active',
    },
  })
}

describe('DashboardTurnOrderCard', () => {
  it('highlights the next selector separately from the current cycle proposer', () => {
    const wrapper = mountCard([
      {
        name: 'Иван Лысенко',
        active: true,
        isCurrentCycleProposer: true,
      },
      {
        name: 'Денис Перов',
        isNextSelector: true,
      },
      {
        name: 'Александр Семёнов',
      },
    ])

    const cards = wrapper.findAll('.turn-order__card')

    expect(cards[0]?.classes()).toContain('turn-order__card--active')
    expect(cards[0]?.text()).toContain('Выбрал книгу')
    expect(cards[1]?.classes()).toContain('turn-order__card--next')
    expect(cards[1]?.text()).toContain('Выбирает следующий')
    expect(cards[2]?.classes()).not.toContain('turn-order__card--next')
  })
})
