import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'

import ArchiveView from '../ArchiveView.vue'

function mountArchive() {
  return mount(ArchiveView, {
    global: {
      stubs: {
        RouterLink: RouterLinkStub,
      },
    },
  })
}

describe('ArchiveView', () => {
  it('renders archive heading and book cards', () => {
    const wrapper = mountArchive()

    expect(wrapper.text()).toContain('Архив')
    expect(wrapper.text()).toContain('8 книг прочитано')
    expect(wrapper.text()).toContain('Тайная история')
    expect(wrapper.text()).toContain('Дюна')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/archive/ten-istoriya')
  })

  it('filters books by search query', async () => {
    const wrapper = mountArchive()

    await wrapper.get('input[type="search"]').setValue('Харари')

    expect(wrapper.text()).toContain('Sapiens')
    expect(wrapper.text()).not.toContain('Тайная история')
  })

  it('filters books by genre and member', async () => {
    const wrapper = mountArchive()
    const selects = wrapper.findAll('select')

    await selects[0]!.setValue('scifi')
    await selects[1]!.setValue('Анна')

    expect(wrapper.text()).toContain('Солярис')
    expect(wrapper.text()).not.toContain('Дюна')
  })

  it('sorts books by rating', async () => {
    const wrapper = mountArchive()
    const selects = wrapper.findAll('select')

    await selects[2]!.setValue('rating')

    const links = wrapper.findAllComponents(RouterLinkStub)
    expect(links[0]!.props('to')).toBe('/archive/ten-istoriya')
    expect(wrapper.text()).toContain('9.2/10')
  })

  it('shows empty state and resets filters', async () => {
    const wrapper = mountArchive()

    await wrapper.get('input[type="search"]').setValue('несуществующая книга')

    expect(wrapper.text()).toContain('Ничего не найдено')

    await wrapper.get('button.button--secondary').trigger('click')

    expect(wrapper.text()).toContain('Тайная история')
  })
})
