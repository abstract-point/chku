import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import MeetingForm from '@/components/meetings/MeetingForm.vue'

function mountForm() {
  return mount(MeetingForm, { props: { cycleId: 7, cycleNumber: 3, isSubmitting: false } })
}

describe('MeetingForm', () => {
  it('shows the empty day placeholder as selected', () => {
    const wrapper = mountForm()
    const daySelect = wrapper.get<HTMLSelectElement>('#meeting-day').element

    expect(daySelect.selectedIndex).toBe(0)
    expect(daySelect.options[daySelect.selectedIndex]?.text).toBe('—')
  })

  it('does not submit without a day instead of silently using the 1st', async () => {
    const wrapper = mountForm()
    await wrapper.get('#meeting-title').setValue('Ноябрьская встреча')

    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('submit')).toBeUndefined()
    expect(wrapper.text()).toContain('Выбери день встречи')
  })

  it('submits the chosen day', async () => {
    const wrapper = mountForm()
    await wrapper.get('#meeting-title').setValue('Ноябрьская встреча')
    await wrapper.get('#meeting-day').setValue('22')

    await wrapper.get('form').trigger('submit')

    const [payload] = wrapper.emitted('submit')?.[0] ?? []
    expect((payload as { date: string }).date).toMatch(/-22$/)
  })
  it('shows the cycle number, not the database id', () => {
    const wrapper = mountForm()

    expect(wrapper.text()).toContain('Цикл #3')
    expect(wrapper.text()).not.toContain('Цикл #7')
  })
})
