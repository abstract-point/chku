<script setup lang="ts">
import { ref, computed } from 'vue'
import { Plus, X } from '@lucide/vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppCheckbox from '@/components/ui/AppCheckbox.vue'
import type { ApiGenre } from '@/api/types'

const props = withDefaults(
  defineProps<{
    genres: ApiGenre[]
    modelValue: number[]
    max?: number
    label?: string
    disabled?: boolean
    error?: string
  }>(),
  {
    max: 5,
    label: '',
    disabled: false,
    error: '',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: number[]]
}>()

const isOpen = ref(false)

const selectedIds = computed(() => new Set(props.modelValue))

const selectedGenres = computed(() =>
  props.genres.filter((g) => selectedIds.value.has(g.id)),
)

const canAddMore = computed(() => selectedGenres.value.length < props.max)

function toggleGenre(id: number) {
  const next = new Set(selectedIds.value)
  if (next.has(id)) {
    next.delete(id)
  } else if (next.size < props.max) {
    next.add(id)
  }
  emit('update:modelValue', Array.from(next))
}

function openModal() {
  if (!props.disabled) {
    isOpen.value = true
  }
}

function closeModal() {
  isOpen.value = false
}
</script>

<template lang="pug">
div
  .genre-picker
    .genre-picker__label(v-if="label")
      span.label-text {{ label }}

    .genre-picker__wrap
      span.genre-picker__badge.badge(v-for="genre in selectedGenres" :key="genre.id" @click="openModal")
        | {{ genre.name }}

      button.genre-picker__add(
        v-if="canAddMore"
        type="button"
        :disabled="disabled"
        @click="openModal"
        :aria-label="$t('genrePicker.add')"
      )
        Plus(:size="14")

    p.genre-picker__error(v-if="error") {{ error }}

  AppModal(:is-open="isOpen" :title="$t('genrePicker.title')" @close="closeModal")
    .genre-picker__modal-body
      .genre-picker__list
        label.genre-picker__item(v-for="genre in genres" :key="genre.id")
          AppCheckbox(
            :model-value="selectedIds.has(genre.id)"
            @update:model-value="() => toggleGenre(genre.id)"
            :disabled="disabled || (!selectedIds.has(genre.id) && !canAddMore)"
          )
          span.genre-picker__item-name {{ genre.name }}

    template(#footer)
      button.button.button--primary.label-text(type="button" @click="closeModal")
        | {{ $t('genrePicker.done') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.genre-picker {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.genre-picker__label {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.genre-picker__wrap {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-sm);
}

.genre-picker__badge {
  cursor: pointer;
  transition:
    background-color 0.15s ease,
    border-color 0.15s ease;
}

.genre-picker__badge:hover {
  border-color: var(--accent-border);
  background: var(--accent-bg);
  color: var(--accent);
}

.genre-picker__add {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  padding: 0;
  border: var(--border-width) dashed var(--border-strong);
  border-radius: var(--radius-inner);
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    color 0.15s ease,
    background-color 0.15s ease;
}

.genre-picker__add:hover:not(:disabled) {
  border-color: var(--accent);
  color: var(--accent);
  background: var(--accent-bg);
}

.genre-picker__add:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.genre-picker__error {
  color: var(--danger);
  font-size: 0.8rem;
  line-height: 1.4;
}

.genre-picker__modal-body {
  max-height: 24rem;
  overflow-y: auto;
}

.genre-picker__list {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.genre-picker__item {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.genre-picker__item:hover {
  background: var(--bg-panel);
}

.genre-picker__item-name {
  font-size: 0.9rem;
  color: var(--text-main);
}
</style>
