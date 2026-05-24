import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import BookCoverPicker from '../BookCoverPicker.vue'

vi.mock('@/queries/bookLookupQueries', () => ({
  useBookCoverSearchQuery: () => ({
    data: { value: [
      {
        source: 'google_books',
        title: 'Мастер и Маргарита',
        authors: ['Михаил Булгаков'],
        coverUrl: 'https://example.com/cover.jpg',
        thumbnailUrl: 'https://example.com/thumb.jpg',
        confidence: 90,
      },
    ]},
    isFetching: { value: false },
    error: { value: null },
  }),
}))

function mountPicker(props: {
  title?: string
  author?: string
  modelValue?: string | null
  coverFile?: File | null
} = {}) {
  return mount(BookCoverPicker, {
    props: {
      title: props.title ?? '',
      author: props.author ?? '',
      modelValue: props.modelValue ?? null,
      coverFile: props.coverFile ?? null,
    },
  })
}

describe('BookCoverPicker', () => {
  it('renders cover search results', async () => {
    const wrapper = mountPicker({ title: 'Мастер и Маргарита', author: 'Булгаков' })
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.book-cover-picker__grid').exists()).toBe(true)
    expect(wrapper.find('.book-cover-picker__cover').exists()).toBe(true)
    expect(wrapper.text()).toContain('Google Books')
  })

  it('emits update:modelValue on cover click', async () => {
    const wrapper = mountPicker({ title: 'Мастер и Маргарита', author: 'Булгаков' })
    await wrapper.vm.$nextTick()

    await wrapper.find('.book-cover-picker__cover').trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:coverFile')).toBeTruthy()
  })

  it('shows upload button when no file selected', () => {
    const wrapper = mountPicker()
    expect(wrapper.text()).toContain('Загрузить свою обложку')
  })
})
