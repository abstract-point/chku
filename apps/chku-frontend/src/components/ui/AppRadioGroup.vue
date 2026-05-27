<script setup lang="ts">
export interface RadioOption {
  label: string
  value: string | number | boolean
}

defineProps<{
  modelValue: string | number | boolean
  name: string
  options: RadioOption[]
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | number | boolean]
  change: [event: Event]
}>()

function onChange(event: Event, value: string | number | boolean) {
  emit('update:modelValue', value)
  emit('change', event)
}
</script>

<template lang="pug">
.app-radio-group(role="radiogroup")
  label.app-radio(v-for="option in options" :key="String(option.value)")
    input.app-radio__input(
      type="radio"
      :name="name"
      :value="option.value"
      :checked="modelValue === option.value"
      :disabled="disabled"
      @change="(e) => onChange(e, option.value)"
    )
    span.app-radio__circle
    span.app-radio__label {{ option.label }}
</template>

<style scoped>
.app-radio-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-lg);
}

.app-radio {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
  cursor: pointer;
  font-size: 0.95rem;
  color: var(--text-main);
}

.app-radio__input {
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

.app-radio__circle {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 1.15rem;
  height: 1.15rem;
  border: var(--border-width) solid var(--border-strong);
  border-radius: 50%;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.025), rgba(255, 255, 255, 0.01)),
    var(--bg-surface);
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease,
    box-shadow 0.15s ease;
}

.app-radio__input:focus-visible + .app-radio__circle {
  outline: 2px solid var(--accent);
  outline-offset: 3px;
}

.app-radio__input:checked + .app-radio__circle {
  border-color: var(--accent);
}

.app-radio__circle::after {
  content: '';
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 50%;
  background: var(--accent);
  opacity: 0;
  transition: opacity 0.15s ease;
}

.app-radio__input:checked + .app-radio__circle::after {
  opacity: 1;
}

.app-radio__input:disabled + .app-radio__circle {
  opacity: 0.5;
  cursor: not-allowed;
}

.app-radio__input:disabled ~ .app-radio__label {
  opacity: 0.5;
  cursor: not-allowed;
}

.app-radio__label {
  line-height: 1.4;
}
</style>
