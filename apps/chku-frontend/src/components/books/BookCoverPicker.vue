<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Check, ImageUp, LoaderCircle, X } from '@lucide/vue'
import { useBookCoverSearchQuery } from '@/queries/bookLookupQueries'

const props = defineProps<{
  title: string
  author: string
  modelValue?: string | null
  coverFile?: File | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
  'update:coverFile': [value: File | null]
}>()

const isbn = ref('')
const debouncedTitle = ref('')
const debouncedAuthor = ref('')
const debouncedIsbn = ref('')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

const cleanTitle = computed(() => props.title.trim())
const cleanAuthor = computed(() => props.author.trim())
const canLoadCovers = computed(() => {
  if (debouncedIsbn.value) return true
  return cleanTitle.value.length > 0
    && cleanAuthor.value.length > 0
    && cleanTitle.value.length + cleanAuthor.value.length >= 3
})

watch(
  [cleanTitle, cleanAuthor, isbn],
  ([title, author, isbnVal]) => {
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }

    debounceTimer = setTimeout(() => {
      debouncedTitle.value = title
      debouncedAuthor.value = author
      debouncedIsbn.value = isbnVal.trim()
    }, 500)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
})

const coversQuery = useBookCoverSearchQuery(debouncedTitle, debouncedAuthor, debouncedIsbn)
const covers = computed(() => coversQuery.data.value ?? [])
const isWaitingForDebounce = computed(() => {
  return canLoadCovers.value
    && (debouncedTitle.value !== cleanTitle.value
      || debouncedAuthor.value !== cleanAuthor.value
      || debouncedIsbn.value !== isbn.value.trim())
})
const isLoading = computed(() => coversQuery.isFetching.value || isWaitingForDebounce.value)

function selectCover(coverUrl?: string | null) {
  if (!coverUrl) return
  emit('update:coverFile', null)
  emit('update:modelValue', coverUrl)
}

function clearCover() {
  emit('update:modelValue', null)
  emit('update:coverFile', null)
}

const fileInput = ref<HTMLInputElement | null>(null)
const uploadedPreviewUrl = computed(() => {
  if (props.coverFile) {
    return URL.createObjectURL(props.coverFile)
  }
  return null
})

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  emit('update:modelValue', null)
  emit('update:coverFile', file)
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function triggerFileInput() {
  fileInput.value?.click()
}

function removeUploadedFile() {
  emit('update:coverFile', null)
}

function sourceLabel(source: string): string {
  return source === 'google_books' ? 'Google Books' : 'Open Library'
}
</script>

<template lang="pug">
.book-cover-picker
  .book-cover-picker__header
    span.label-text {{ $t('books.coverSearch') }}
    span.book-cover-picker__status.label-text(v-if="isLoading")
      LoaderCircle.book-cover-picker__spinner(:size="15" aria-hidden="true")
      | {{ $t('books.loadingCovers') }}
    span.label-text(v-else-if="!canLoadCovers") {{ $t('books.coverHint') }}
    button.book-cover-picker__clear.label-text(
      v-else-if="modelValue || coverFile"
      type="button"
      @click="clearCover"
    ) {{ $t('books.clearCover') }}

  .book-cover-picker__isbn
    label.label-text(for="cover-isbn") {{ $t('books.isbnLabel') }}
    input#cover-isbn.field-control.book-cover-picker__isbn-input(
      v-model="isbn"
      type="text"
      :placeholder="$t('books.isbnPlaceholder')"
    )
    span.book-cover-picker__isbn-hint.body-text {{ $t('books.isbnHint') }}

  .book-cover-picker__grid(v-if="canLoadCovers && covers.length")
    button.book-cover-picker__cover(
      v-for="cover in covers"
      :key="cover.coverUrl"
      type="button"
      :class="{ 'book-cover-picker__cover--selected': cover.coverUrl === modelValue }"
      :aria-pressed="cover.coverUrl === modelValue"
      @click="selectCover(cover.coverUrl)"
    )
      img(:src="cover.thumbnailUrl || cover.coverUrl || ''" :alt="$t('books.coverOption')")
      span.book-cover-picker__source.label-text {{ sourceLabel(cover.source) }}
      span.book-cover-picker__selected(v-if="cover.coverUrl === modelValue")
        Check(:size="16" aria-hidden="true")

  p.book-cover-picker__empty.body-text(v-else-if="canLoadCovers && !isLoading && !coversQuery.error.value")
    | {{ $t('books.noCoversFound') }}
  p.book-cover-picker__empty.body-text(v-else-if="coversQuery.error.value")
    | {{ $t('books.coverLoadError') }}

  .book-cover-picker__upload
    input.book-cover-picker__file-input(
      ref="fileInput"
      type="file"
      accept="image/*"
      @change="onFileChange"
    )
    template(v-if="!coverFile")
      button.button.button--secondary.label-text(type="button" @click="triggerFileInput")
        ImageUp.book-cover-picker__upload-icon
        | {{ $t('books.uploadCover') }}
    template(v-else)
      .book-cover-picker__upload-preview
        img(:src="uploadedPreviewUrl || ''" :alt="$t('books.uploadedCover')")
        button.book-cover-picker__remove-upload(type="button" @click="removeUploadedFile")
          X(:size="14" aria-hidden="true")
          span.sr-only {{ $t('books.removeCover') }}
</template>

<style scoped>
.book-cover-picker {
  display: grid;
  gap: var(--space-md);
}

.book-cover-picker__header,
.book-cover-picker__status {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.book-cover-picker__header {
  justify-content: space-between;
  min-height: 1.7rem;
}

.book-cover-picker__status {
  color: var(--text-muted);
}

.book-cover-picker__spinner {
  animation: cover-picker-spin 0.9s linear infinite;
}

.book-cover-picker__clear {
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
}

.book-cover-picker__clear:hover {
  color: var(--text-main);
}

.book-cover-picker__isbn {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.book-cover-picker__isbn-input {
  width: 100%;
}

.book-cover-picker__isbn-hint {
  color: var(--text-muted);
  font-size: 0.75rem;
}

.book-cover-picker__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(4.2rem, 1fr));
  gap: var(--space-sm);
}

.book-cover-picker__cover {
  position: relative;
  overflow: hidden;
  padding: 0;
  padding-bottom: 1.1rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  cursor: pointer;
}

.book-cover-picker__cover:hover,
.book-cover-picker__cover--selected {
  border-color: var(--accent);
}

.book-cover-picker__cover img {
  width: 100%;
  aspect-ratio: 2 / 3;
  object-fit: cover;
  display: block;
}

.book-cover-picker__source {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0.15rem 0.25rem;
  background: rgba(0, 0, 0, 0.6);
  color: var(--text-muted);
  font-size: 0.6rem;
  text-align: center;
}

.book-cover-picker__selected {
  position: absolute;
  top: 0.35rem;
  right: 0.35rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 999px;
  background: var(--accent);
  color: var(--bg-base);
}

.book-cover-picker__empty {
  color: var(--text-muted);
}

.book-cover-picker__upload {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.book-cover-picker__file-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.book-cover-picker__upload-icon {
  width: 1rem;
  height: 1rem;
}

.book-cover-picker__upload-preview {
  position: relative;
  width: 4.2rem;
  aspect-ratio: 2 / 3;
  border-radius: var(--radius-inner);
  overflow: hidden;
  border: var(--border-width) solid var(--border);
}

.book-cover-picker__upload-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.book-cover-picker__remove-upload {
  position: absolute;
  top: 0.2rem;
  right: 0.2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.2rem;
  height: 1.2rem;
  border-radius: 999px;
  background: var(--danger);
  color: var(--bg-base);
  border: none;
  cursor: pointer;
  padding: 0;
}

@keyframes cover-picker-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
