<script setup lang="ts">
const props = defineProps<{
  modelValue: string
  id?: string
  name?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  rows?: number
  ariaInvalid?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
  input: [event: Event]
  blur: [event: Event]
}>()

function onInput(event: Event) {
  const target = event.target as HTMLTextAreaElement
  emit('update:modelValue', target.value)
  emit('input', event)
}

function onBlur(event: Event) {
  emit('blur', event)
}
</script>

<template lang="pug">
textarea.app-textarea.field-control(
  :id="id"
  :name="name"
  :value="modelValue"
  :placeholder="placeholder"
  :required="required"
  :disabled="disabled"
  :rows="rows ?? 4"
  :aria-invalid="ariaInvalid || undefined"
  @input="onInput"
  @blur="onBlur"
)
</template>

<style scoped>
.app-textarea {
  width: 100%;
  padding: 0.75rem 0.9rem;
  resize: vertical;
  min-height: 6rem;
}
</style>
