import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import BookCoverPicker from '../BookCoverPicker.vue'

function mountPicker(props: {
  coverFile?: File | null
} = {}) {
  return mount(BookCoverPicker, {
    props: {
      coverFile: props.coverFile ?? null,
    },
  })
}

describe('BookCoverPicker', () => {
  it('shows upload button when no file selected', () => {
    const wrapper = mountPicker()
    expect(wrapper.text()).toContain('Загрузить свою обложку')
  })

  it('emits update:coverFile when a file is selected', async () => {
    const wrapper = mountPicker()
    const file = new File([''], 'cover.jpg', { type: 'image/jpeg' })

    const input = wrapper.find('input[type="file"]')
    const mockFileList = {
      0: file,
      length: 1,
      item: () => file,
    }
    Object.defineProperty(input.element, 'files', { value: mockFileList })
    await input.trigger('change')

    expect(wrapper.emitted('update:coverFile')).toBeTruthy()
  })

  it('shows preview and remove button when file is selected', () => {
    const file = new File([''], 'cover.jpg', { type: 'image/jpeg' })
    const wrapper = mountPicker({ coverFile: file })

    expect(wrapper.text()).toContain('Удалить загруженную обложку')
    expect(wrapper.find('.book-cover-picker__upload-preview').exists()).toBe(true)
  })

  it('emits update:coverFile with null on remove', async () => {
    const file = new File([''], 'cover.jpg', { type: 'image/jpeg' })
    const wrapper = mountPicker({ coverFile: file })

    await wrapper.find('.book-cover-picker__remove-upload').trigger('click')

    expect(wrapper.emitted('update:coverFile')).toEqual([[null]])
  })
})
