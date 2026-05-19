import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { h } from 'vue'

import AppModal from '../AppModal.vue'

describe('AppModal', () => {
  afterEach(() => {
    document.querySelector('.app-modal__backdrop')?.remove()
  })

  it('renders when open', () => {
    mount(AppModal, {
      props: { isOpen: true, title: 'Test Modal' },
      slots: {
        default: () => h('p', 'Modal content'),
      },
      attachTo: document.body,
    })

    expect(document.body.textContent).toContain('Test Modal')
    expect(document.body.textContent).toContain('Modal content')
  })

  it('does not render when closed', () => {
    mount(AppModal, {
      props: { isOpen: false, title: 'Test Modal' },
      attachTo: document.body,
    })

    expect(document.querySelector('.app-modal__backdrop')).toBeNull()
  })

  it('emits close on backdrop click', async () => {
    const wrapper = mount(AppModal, {
      props: { isOpen: true },
      attachTo: document.body,
    })

    const backdrop = document.querySelector('.app-modal__backdrop')
    expect(backdrop).not.toBeNull()
    await backdrop!.dispatchEvent(new MouseEvent('click', { bubbles: true }))
    expect(wrapper.emitted('close')).toHaveLength(1)
  })

  it('emits close on close button click', async () => {
    const wrapper = mount(AppModal, {
      props: { isOpen: true, title: 'Test' },
      attachTo: document.body,
    })

    const closeBtn = document.querySelector('.app-modal__close')
    expect(closeBtn).not.toBeNull()
    await closeBtn!.dispatchEvent(new MouseEvent('click', { bubbles: true }))
    expect(wrapper.emitted('close')).toHaveLength(1)
  })

  it('emits close on Escape key', async () => {
    const wrapper = mount(AppModal, {
      props: { isOpen: true },
      attachTo: document.body,
    })

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await vi.waitFor(() => expect(wrapper.emitted('close')).toHaveLength(1))
  })

  it('renders footer slot when provided', () => {
    mount(AppModal, {
      props: { isOpen: true },
      slots: {
        footer: () => h('button', 'Action'),
      },
      attachTo: document.body,
    })

    expect(document.body.textContent).toContain('Action')
  })
})
