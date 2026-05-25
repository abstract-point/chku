<script setup lang="ts">
const props = defineProps<{
  modelValue: boolean
  id?: string
  name?: string
  disabled?: boolean
  ariaInvalid?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  change: [event: Event]
}>()

function onChange(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.checked)
  emit('change', event)
}
</script>

<template lang="pug">
label.app-checkbox
  input.app-checkbox__input(
    type="checkbox"
    :id="id"
    :name="name"
    :checked="modelValue"
    :disabled="disabled"
    :aria-invalid="ariaInvalid || undefined"
    @change="onChange"
  )
  span.app-checkbox__check
  span.app-checkbox__label
    slot
</template>

<style scoped>
.app-checkbox {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
  cursor: pointer;
  font-size: 0.95rem;
  color: var(--text-main);
}

.app-checkbox__input {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.app-checkbox__check {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 1.15rem;
  height: 1.15rem;
  border: var(--border-width) solid var(--border-strong);
  border-radius: 0.3rem;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.025), rgba(255, 255, 255, 0.01)),
    var(--bg-surface);
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease,
    box-shadow 0.15s ease;
}

.app-checkbox__input:focus-visible + .app-checkbox__check {
  outline: 2px solid var(--accent);
  outline-offset: 3px;
}

.app-checkbox__input:checked + .app-checkbox__check {
  border-color: var(--accent);
  background: var(--accent);
}

.app-checkbox__input[aria-invalid='true'] + .app-checkbox__check {
  border-color: var(--danger);
  box-shadow: 0 0 0 3px var(--danger-bg);
}

.app-checkbox__check::after {
  content: '';
  width: 0.4rem;
  height: 0.7rem;
  border: solid var(--text-inverse);
  border-width: 0 0.12rem 0.12rem 0;
  transform: rotate(45deg) translateY(-1px);
  opacity: 0;
  transition: opacity 0.15s ease;
}

.app-checkbox__input:checked + .app-checkbox__check::after {
  opacity: 1;
}

.app-checkbox__input:disabled + .app-checkbox__check {
  opacity: 0.5;
  cursor: not-allowed;
}

.app-checkbox__input:disabled ~ .app-checkbox__label {
  opacity: 0.5;
  cursor: not-allowed;
}

.app-checkbox__label {
  line-height: 1.4;
}
</style>
