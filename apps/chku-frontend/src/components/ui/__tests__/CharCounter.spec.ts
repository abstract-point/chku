import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import CharCounter from '../CharCounter.vue'

describe('CharCounter', () => {
  it('renders current and max values with ru-RU grouping', () => {
    const wrapper = mount(CharCounter, { props: { current: 1234, max: 10000 } })

    expect(wrapper.text().replace(/\s/g, ' ')).toBe('1 234 / 10 000')
    expect(wrapper.classes()).not.toContain('char-counter--warn')
  })

  it('highlights the counter near the limit', () => {
    const wrapper = mount(CharCounter, { props: { current: 450, max: 500 } })

    expect(wrapper.classes()).toContain('char-counter--warn')
  })

  it('stays hidden until the threshold is reached', () => {
    const below = mount(CharCounter, { props: { current: 10, max: 255, threshold: 0.8 } })
    const above = mount(CharCounter, { props: { current: 210, max: 255, threshold: 0.8 } })

    expect(below.find('.char-counter').exists()).toBe(false)
    expect(above.find('.char-counter').exists()).toBe(true)
  })
})
