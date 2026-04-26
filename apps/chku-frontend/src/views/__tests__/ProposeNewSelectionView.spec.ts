import { describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import ProposeNewSelectionView from '../ProposeNewSelectionView.vue'

const push = vi.fn()

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push,
  }),
}))

function mountProposal() {
  return mount(ProposeNewSelectionView)
}

describe('ProposeNewSelectionView', () => {
  it('renders the proposal form and selection guidelines', () => {
    setActivePinia(createPinia())

    const wrapper = mountProposal()

    expect(wrapper.text()).toContain('Предложить следующую книгу')
    expect(wrapper.text()).toContain('Форма предложения')
    expect(wrapper.text()).toContain('Название книги')
    expect(wrapper.text()).toContain('Правила выбора')
    expect(wrapper.text()).toContain('Главное правило')
  })

  it('validates required fields before submitting', async () => {
    setActivePinia(createPinia())
    push.mockClear()

    const wrapper = mountProposal()

    await wrapper.find('form').trigger('submit')

    expect(wrapper.text()).toContain('Укажи название книги.')
    expect(push).not.toHaveBeenCalled()
  })

  it('creates a book choice event and returns to dashboard', async () => {
    setActivePinia(createPinia())
    push.mockClear()
    const wrapper = mountProposal()

    await wrapper.get('#book-title').setValue('Лавр')
    await wrapper.get('#book-author').setValue('Евгений Водолазкин')
    await wrapper.get('#book-description').setValue('История странника и его попытки искупить прошлое.')
    await wrapper.get('#book-reason').setValue('Подойдёт для разговора о времени, вине и милосердии.')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(push).toHaveBeenCalledWith({ name: 'home' })
  })
})
