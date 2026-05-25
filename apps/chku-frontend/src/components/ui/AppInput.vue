<script setup lang="ts">
import { computed, ref } from 'vue'

const props = defineProps<{
  modelValue?: string | number | null
  type?: string
  id?: string
  name?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  min?: string | number
  max?: string | number
  step?: string | number
  autocomplete?: string
  pattern?: string
  inputmode?: string
  minlength?: string | number
  ariaInvalid?: boolean
  accept?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
  change: [event: Event]
  input: [event: Event]
  blur: [event: Event]
  focus: [event: Event]
}>()

const inputRef = ref<HTMLInputElement | null>(null)

const inputType = computed(() => props.type ?? 'text')

function onInput(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
  emit('input', event)
}

function onChange(event: Event) {
  emit('change', event)
}

function onBlur(event: Event) {
  emit('blur', event)
}

function onFocus(event: Event) {
  emit('focus', event)
}

function focus() {
  inputRef.value?.focus()
}

defineExpose({ focus })
</script>

<template lang="pug">
input.app-input.field-control(
  ref="inputRef"
  :id="id"
  :name="name"
  :type="inputType"
  :value="inputType === 'file' ? undefined : (modelValue ?? '')"
  :placeholder="placeholder"
  :required="required"
  :disabled="disabled"
  :min="min"
  :max="max"
  :step="step"
  :autocomplete="autocomplete"
  :pattern="pattern"
  :inputmode="inputmode"
  :minlength="minlength"
  :aria-invalid="ariaInvalid || undefined"
  :accept="accept"
  @input="onInput"
  @change="onChange"
  @blur="onBlur"
  @focus="onFocus"
)
</template>

<style scoped>
.app-input {
  width: 100%;
  padding: 0.75rem 0.9rem;
}

.app-input[type='file'] {
  padding: 0.55rem 0.9rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.app-input[type='file']::file-selector-button {
  margin-right: var(--space-sm);
  padding: 0.35rem 0.75rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  color: var(--text-muted);
  font-family: var(--font-mono);
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease;
}

.app-input[type='file']::file-selector-button:hover {
  border-color: var(--border-strong);
  background: var(--bg-hover);
}

.app-input[type='time'] {
  padding: 0.6rem 0.9rem;
  font-family: var(--font-mono);
  font-variant-numeric: tabular-nums;
}

.app-input[type='number']::-webkit-inner-spin-button,
.app-input[type='number']::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.app-input[type='number'] {
  -moz-appearance: textfield;
}
</style>
