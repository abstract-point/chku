<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Check, LoaderCircle } from '@lucide/vue'
import { useOpenLibraryCoversQuery } from '@/queries/bookLookupQueries'

const props = defineProps<{
  title: string
  author: string
  modelValue?: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

const debouncedTitle = ref('')
const debouncedAuthor = ref('')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

const cleanTitle = computed(() => props.title.trim())
const cleanAuthor = computed(() => props.author.trim())
const canLoadCovers = computed(() => {
  return cleanTitle.value.length > 0
    && cleanAuthor.value.length > 0
    && cleanTitle.value.length + cleanAuthor.value.length >= 3
})

watch(
  [cleanTitle, cleanAuthor],
  ([title, author]) => {
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }

    debounceTimer = setTimeout(() => {
      debouncedTitle.value = title
      debouncedAuthor.value = author
    }, 500)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
})

const coversQuery = useOpenLibraryCoversQuery(debouncedTitle, debouncedAuthor)
const covers = computed(() => coversQuery.data.value ?? [])
const isWaitingForDebounce = computed(() => {
  return canLoadCovers.value
    && (debouncedTitle.value !== cleanTitle.value || debouncedAuthor.value !== cleanAuthor.value)
})
const isLoading = computed(() => coversQuery.isFetching.value || isWaitingForDebounce.value)

function selectCover(coverUrl?: string | null) {
  if (!coverUrl) return
  emit('update:modelValue', coverUrl)
}

function clearCover() {
  emit('update:modelValue', null)
}
</script>

<template lang="pug">
.open-library-cover-picker
  .open-library-cover-picker__header
    span.label-text Open Library
    span.open-library-cover-picker__status.label-text(v-if="isLoading")
      LoaderCircle.open-library-cover-picker__spinner(:size="15" aria-hidden="true")
      | {{ $t('books.loadingCovers') }}
    span.label-text(v-else-if="!canLoadCovers") {{ $t('books.coverHint') }}
    button.open-library-cover-picker__clear.label-text(
      v-else-if="modelValue"
      type="button"
      @click="clearCover"
    ) {{ $t('books.clearCover') }}

  .open-library-cover-picker__grid(v-if="canLoadCovers && covers.length")
    button.open-library-cover-picker__cover(
      v-for="cover in covers"
      :key="cover.coverId"
      type="button"
      :class="{ 'open-library-cover-picker__cover--selected': cover.coverUrl === modelValue }"
      :aria-pressed="cover.coverUrl === modelValue"
      @click="selectCover(cover.coverUrl)"
    )
      img(:src="cover.thumbnailUrl || cover.coverUrl || ''" :alt="$t('books.coverOption')")
      span.open-library-cover-picker__selected(v-if="cover.coverUrl === modelValue")
        Check(:size="16" aria-hidden="true")
  p.open-library-cover-picker__empty.body-text(v-else-if="canLoadCovers && !isLoading && !coversQuery.error.value")
    | {{ $t('books.noCoversFound') }}
  p.open-library-cover-picker__empty.body-text(v-else-if="coversQuery.error.value")
    | {{ $t('books.coverLoadError') }}
</template>

<style scoped>
.open-library-cover-picker {
  display: grid;
  gap: var(--space-sm);
}

.open-library-cover-picker__header,
.open-library-cover-picker__status {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.open-library-cover-picker__header {
  justify-content: space-between;
  min-height: 1.7rem;
}

.open-library-cover-picker__status {
  color: var(--text-muted);
}

.open-library-cover-picker__spinner {
  animation: cover-picker-spin 0.9s linear infinite;
}

.open-library-cover-picker__clear {
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
}

.open-library-cover-picker__clear:hover {
  color: var(--text-main);
}

.open-library-cover-picker__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(4.2rem, 1fr));
  gap: var(--space-sm);
}

.open-library-cover-picker__cover {
  position: relative;
  overflow: hidden;
  padding: 0;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  cursor: pointer;
  aspect-ratio: 2 / 3;
}

.open-library-cover-picker__cover:hover,
.open-library-cover-picker__cover--selected {
  border-color: var(--accent);
}

.open-library-cover-picker__cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.open-library-cover-picker__selected {
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

.open-library-cover-picker__empty {
  color: var(--text-muted);
}

@keyframes cover-picker-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
