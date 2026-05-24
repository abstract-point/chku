import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

import LoginView from '../LoginView.vue'

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
  }),
}))

describe('LoginView', () => {
  it('renders the email placeholder without i18n linked-format errors', () => {
    const wrapper = mount(LoginView)

    expect(wrapper.find('#login-email').attributes('placeholder')).toBe('you@example.com')
  })
})
