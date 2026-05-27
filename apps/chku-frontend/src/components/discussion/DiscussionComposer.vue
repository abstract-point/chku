<script setup lang="ts">
defineProps<{
  placeholder?: string
  submitLabel?: string
  isSubmitting?: boolean
}>()

const emit = defineEmits<{
  submit: [text: string]
  cancel: []
}>()

const text = ref('')

function handleSubmit() {
  const trimmed = text.value.trim()
  if (!trimmed) return
  emit('submit', trimmed)
  text.value = ''
}
</script>

<script lang="ts">
import { ref } from 'vue'
</script>

<template lang="pug">
.discussion-composer
  textarea.discussion-composer__input.field-control(
    v-model="text"
    rows="2"
    :placeholder="placeholder"
    @keydown.meta.enter="handleSubmit"
    @keydown.ctrl.enter="handleSubmit"
  )
  .discussion-composer__actions
    button.button.button--secondary.label-text(type="button" @click="$emit('cancel')") {{ $t('discussion.cancel') }}
    button.button.button--primary.label-text(type="button" :disabled="!text.trim() || isSubmitting" @click="handleSubmit")
      | {{ isSubmitting ? '...' : submitLabel }}
</template>

<style scoped lang="scss">
.discussion-composer {
  display: grid;
  gap: var(--space-sm);
}

.discussion-composer__input {
  width: 100%;
  min-height: 3.5rem;
  resize: vertical;
  color: var(--text-main);
  font-size: 0.9rem;
  line-height: 1.5;
  border-radius: var(--radius-inner);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  background: var(--bg-surface);
}

.discussion-composer__input:focus {
  border-color: var(--accent);
  outline: none;
}

.discussion-composer__actions {
  display: flex;
  gap: var(--space-sm);
  justify-content: space-between;
}
</style>
