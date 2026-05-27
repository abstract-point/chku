import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import FilePicker from '../FilePicker.vue'

function mountPicker(
  props: {
    modelValue?: File | null
    variant?: 'avatar' | 'cover'
    name?: string
    existingUrl?: string | null
    coverTitle?: string
  } = {},
) {
  return mount(FilePicker, {
    props: {
      modelValue: props.modelValue ?? null,
      variant: props.variant ?? 'avatar',
      name: props.name ?? '',
      existingUrl: props.existingUrl ?? null,
      coverTitle: props.coverTitle ?? '',
    },
  })
}

describe('FilePicker', () => {
  describe('avatar variant', () => {
    it('shows UserAvatar with initials when no file and no existingUrl', () => {
      const wrapper = mountPicker({ variant: 'avatar', name: 'Иван' })
      expect(wrapper.text()).toContain('И')
    })

    it('shows UserAvatar with existing url', () => {
      const wrapper = mountPicker({
        variant: 'avatar',
        name: 'Иван',
        existingUrl: 'https://example.com/avatar.png',
      })
      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toBe('https://example.com/avatar.png')
    })

    it('emits update:modelValue on file select', async () => {
      const wrapper = mountPicker({ variant: 'avatar' })
      const file = new File([''], 'avatar.jpg', { type: 'image/jpeg' })
      const input = wrapper.find('input[type="file"]')
      const mockFileList = { 0: file, length: 1, item: () => file }
      Object.defineProperty(input.element, 'files', { value: mockFileList })
      await input.trigger('change')
      expect(wrapper.emitted('update:modelValue')).toEqual([[file]])
    })

    it('shows remove button when file is selected', () => {
      const file = new File([''], 'avatar.jpg', { type: 'image/jpeg' })
      const wrapper = mountPicker({ variant: 'avatar', modelValue: file })
      expect(wrapper.find('.file-picker__remove').exists()).toBe(true)
    })

    it('emits update:modelValue with null on remove click', async () => {
      const file = new File([''], 'avatar.jpg', { type: 'image/jpeg' })
      const wrapper = mountPicker({ variant: 'avatar', modelValue: file })
      await wrapper.find('.file-picker__remove').trigger('click')
      expect(wrapper.emitted('update:modelValue')).toEqual([[null]])
    })
  })

  describe('cover variant', () => {
    it('shows placeholder with coverTitle when no file and no existingUrl', () => {
      const wrapper = mountPicker({ variant: 'cover', coverTitle: 'Тайная\nистория' })
      expect(wrapper.text()).toContain('Тайная')
      expect(wrapper.text()).toContain('история')
    })

    it('shows placeholder with noCover text when no coverTitle', () => {
      const wrapper = mountPicker({ variant: 'cover' })
      expect(wrapper.text()).toContain('Нет обложки')
    })

    it('shows cover image when existingUrl provided', () => {
      const wrapper = mountPicker({
        variant: 'cover',
        existingUrl: 'https://example.com/cover.jpg',
      })
      const img = wrapper.find('img')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toBe('https://example.com/cover.jpg')
    })

    it('shows cover image preview when file selected', () => {
      const file = new File([''], 'cover.jpg', { type: 'image/jpeg' })
      const wrapper = mountPicker({ variant: 'cover', modelValue: file })
      expect(wrapper.find('.file-picker__cover-image').exists()).toBe(true)
      expect(wrapper.find('.file-picker__remove').exists()).toBe(true)
    })

    it('emits update:modelValue with null on remove click', async () => {
      const file = new File([''], 'cover.jpg', { type: 'image/jpeg' })
      const wrapper = mountPicker({ variant: 'cover', modelValue: file })
      await wrapper.find('.file-picker__remove').trigger('click')
      expect(wrapper.emitted('update:modelValue')).toEqual([[null]])
    })
  })
})
