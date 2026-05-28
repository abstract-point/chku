import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import DashboardTurnOrderCard from '@/components/dashboard/DashboardTurnOrderCard.vue'
import type { TurnOrderMember } from '@/types/dashboard'

function mountCard(members: TurnOrderMember[], isAdmin = false) {
  return mount(DashboardTurnOrderCard, {
    props: {
      members,
      cycleStatus: 'active',
      isAdmin,
    },
  })
}

const proposerMember: TurnOrderMember = {
  memberId: 1,
  name: 'Иван Лысенко',
  active: true,
  isCurrentCycleProposer: true,
}

const nextMember: TurnOrderMember = {
  memberId: 2,
  name: 'Денис Перов',
  isNextSelector: true,
}

const thirdMember: TurnOrderMember = {
  memberId: 3,
  name: 'Александр Семёнов',
}

describe('DashboardTurnOrderCard', () => {
  it('highlights the next selector separately from the current cycle proposer', () => {
    const wrapper = mountCard([proposerMember, nextMember, thirdMember])

    const cards = wrapper.findAll('.turn-order__card')

    expect(cards[0]?.classes()).toContain('turn-order__card--active')
    expect(cards[0]?.text()).toContain('Выбрал книгу')
    expect(cards[1]?.classes()).toContain('turn-order__card--next')
    expect(cards[1]?.text()).toContain('Выбирает следующий')
    expect(cards[2]?.classes()).not.toContain('turn-order__card--next')
  })

  it('shows edit button only when isAdmin is true', () => {
    const wrapper = mountCard([proposerMember, nextMember], false)
    expect(wrapper.find('.section-header__action').exists()).toBe(false)

    const adminWrapper = mountCard([proposerMember, nextMember], true)
    expect(adminWrapper.find('.section-header__action').exists()).toBe(true)
  })

  it('enters edit mode on edit button click', async () => {
    const wrapper = mountCard([proposerMember, nextMember, thirdMember], true)

    await wrapper.find('.section-header__action').trigger('click')

    expect(wrapper.find('.turn-order__actions-bar').exists()).toBe(true)
    expect(wrapper.find('.turn-order__lock-icon').exists()).toBe(true)
    expect(wrapper.findAll('.turn-order__move-btn').length).toBeGreaterThan(0)
  })

  it('locks the head member in edit mode', async () => {
    const wrapper = mountCard([proposerMember, nextMember, thirdMember], true)

    await wrapper.find('.section-header__action').trigger('click')

    const headCard = wrapper.findAll('.turn-order__card')[0]
    expect(headCard?.classes()).toContain('turn-order__card--head')
    expect(headCard?.find('.turn-order__lock-icon').exists()).toBe(true)
  })

  it('exits edit mode on cancel', async () => {
    const wrapper = mountCard([proposerMember, nextMember, thirdMember], true)

    await wrapper.find('.section-header__action').trigger('click')
    expect(wrapper.find('.turn-order__actions-bar').exists()).toBe(true)

    const actionBar = wrapper.find('.turn-order__actions-bar')
    const list = actionBar.findAll('button')
    await list[list.length - 1]!.trigger('click')

    expect(wrapper.find('.turn-order__actions-bar').exists()).toBe(false)
  })

  it('moves a member down and up in edit mode', async () => {
    const wrapper = mountCard([proposerMember, nextMember, thirdMember], true)

    await wrapper.find('.section-header__action').trigger('click')

    const moveBtns = wrapper.findAll('.turn-order__move-btn')
    expect(moveBtns.length).toBe(4)

    await moveBtns[1]!.trigger('click')

    const actionBar = wrapper.find('.turn-order__actions-bar')
    const saveBtn = actionBar.findAll('button')[0]!
    await saveBtn.trigger('click')

    expect(wrapper.find('.turn-order__actions-bar').exists()).toBe(false)
  })
})
