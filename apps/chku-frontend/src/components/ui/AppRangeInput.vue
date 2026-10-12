<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  modelValue: number | null
  min?: number
  max?: number
  step?: number
  ariaLabel?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number]
  input: [event: Event]
}>()

const minValue = computed(() => props.min ?? 0)
const maxValue = computed(() => props.max ?? 100)
const stepValue = computed(() => props.step ?? 1)

const normalizedValue = computed(() => {
  const val = Number(props.modelValue) || 0
  return Math.min(maxValue.value, Math.max(minValue.value, val))
})

const rangePercent = computed(() => {
  const range = maxValue.value - minValue.value
  if (range === 0) return 0
  return ((normalizedValue.value - minValue.value) / range) * 100
})

function onInput(event: Event) {
  const target = event.target as HTMLInputElement
  const val = parseFloat(target.value)
  emit('update:modelValue', val)
  emit('input', event)
}
</script>

<template lang="pug">
.app-range-input
  input.app-range-input__slider(
    type="range"
    :min="minValue"
    :max="maxValue"
    :step="stepValue"
    :value="normalizedValue"
    :style="{ '--range-value': `${rangePercent}%` }"
    :aria-label="ariaLabel"
    @input="onInput"
  )
  .app-range-input__scale(v-if="$slots.scale")
    slot(name="scale")
</template>

<style scoped>
.app-range-input {
  display: grid;
  gap: var(--space-sm);
}

.app-range-input__slider {
  width: 100%;
  height: 1.7rem;
  margin: 0;
  cursor: pointer;
  background: transparent;
  -webkit-appearance: none;
  appearance: none;
}

.app-range-input__slider:focus-visible {
  outline: none;
}

.app-range-input__slider:focus-visible::-webkit-slider-thumb {
  box-shadow: 0 0 0 3px var(--accent-bg);
}

.app-range-input__slider:focus-visible::-moz-range-thumb {
  box-shadow: 0 0 0 3px var(--accent-bg);
}

.app-range-input__slider::-webkit-slider-runnable-track {
  height: 0.2rem;
  border-radius: 999px;
  background: linear-gradient(
    90deg,
    var(--accent) 0 var(--range-value),
    var(--border) var(--range-value) 100%
  );
}

/* Same visual size as the Firefox thumb: 1rem content + 0.15rem border on each side. */
.app-range-input__slider::-webkit-slider-thumb {
  box-sizing: border-box;
  width: 1.3rem;
  height: 1.3rem;
  margin-top: -0.55rem;
  border: 0.15rem solid var(--accent);
  border-radius: 50%;
  background: var(--bg-surface);
  -webkit-appearance: none;
  appearance: none;
  box-shadow: 0 0 0 0.1rem var(--bg-surface);
}

.app-range-input__slider::-moz-range-track {
  height: 0.2rem;
  border-radius: 999px;
  background: var(--border);
}

.app-range-input__slider::-moz-range-progress {
  height: 0.2rem;
  border-radius: 999px;
  background: var(--accent);
}

.app-range-input__slider::-moz-range-thumb {
  box-sizing: border-box;
  width: 1.3rem;
  height: 1.3rem;
  border: 0.15rem solid var(--accent);
  border-radius: 50%;
  background: var(--bg-surface);
  box-shadow: 0 0 0 0.1rem var(--bg-surface);
}

.app-range-input__scale {
  display: flex;
  justify-content: space-between;
  gap: var(--space-md);
}
</style>
