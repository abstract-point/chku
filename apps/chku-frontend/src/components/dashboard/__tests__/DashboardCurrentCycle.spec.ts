import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { nextTick } from 'vue'
import { currentBook, memberProgress } from '@/data/dashboard'

import DashboardCurrentCycle from '../DashboardCurrentCycle.vue'

const route = vi.hoisted(() => ({
  value: {
    query: {} as Record<string, string>,
    hash: '',
  },
}))

vi.mock('vue-router', () => ({
  RouterLink: RouterLinkStub,
  useRoute: () => route.value,
}))

describe('DashboardCurrentCycle', () => {
  afterEach(() => {
    route.value = { query: {}, hash: '' }
    vi.restoreAllMocks()
  })

  it('opens progress form from progress action route', async () => {
    route.value = {
      query: { action: 'update-progress' },
      hash: '#reading-progress',
    }
    const scrollIntoView = vi.fn()
    window.HTMLElement.prototype.scrollIntoView = scrollIntoView

    const wrapper = mount(DashboardCurrentCycle, {
      props: {
        book: { ...currentBook, progress: 42, progressLabel: '42%' },
        members: memberProgress,
      },
      global: {
        stubs: {
          RouterLink: RouterLinkStub,
        },
      },
    })

    await nextTick()

    expect(wrapper.find('.reading-progress-form').exists()).toBe(true)
    expect(scrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth', block: 'center' })
  })

  it('does not open progress form from route when book is finished', async () => {
    route.value = {
      query: { action: 'update-progress' },
      hash: '#reading-progress',
    }

    const wrapper = mount(DashboardCurrentCycle, {
      props: {
        book: { ...currentBook, progress: 100, progressLabel: '100%' },
        members: memberProgress,
      },
      global: {
        stubs: {
          RouterLink: RouterLinkStub,
        },
      },
    })

    await nextTick()

    expect(wrapper.find('.reading-progress-form').exists()).toBe(false)
  })

  it('does not reopen progress form after handling the same action route', async () => {
    route.value = {
      query: { action: 'update-progress' },
      hash: '#reading-progress',
    }

    const wrapper = mount(DashboardCurrentCycle, {
      props: {
        book: { ...currentBook, progress: 42, progressLabel: '42%' },
        members: memberProgress,
      },
      global: {
        stubs: {
          RouterLink: RouterLinkStub,
        },
      },
    })

    await nextTick()
    await wrapper.find('.reading-progress-form__actions .button--secondary').trigger('click')
    await wrapper.setProps({
      book: { ...currentBook, progress: 55, progressLabel: '55%' },
    } as never)

    expect(wrapper.find('.reading-progress-form').exists()).toBe(false)
  })
})
