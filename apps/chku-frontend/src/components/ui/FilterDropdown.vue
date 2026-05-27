<script setup lang="ts">
import type { Component } from 'vue'
import { ChevronDown } from '@lucide/vue'

export interface FilterOption {
  value: string
  label: string
}

defineProps<{
  modelValue: string
  options: FilterOption[]
  placeholder?: string
  ariaLabel?: string
  leadingIcon?: Component
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
  change: []
}>()

function onChange(event: Event) {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', target.value)
  emit('change')
}
</script>

<template lang="pug">
label.filter-dropdown
  component.filter-dropdown__icon(v-if="leadingIcon" :is="leadingIcon" :size="17" aria-hidden="true")
  select.filter-dropdown__select.field-control(
    :value="modelValue"
    :aria-label="ariaLabel"
    @change="onChange"
  )
    option(v-if="placeholder" value="") {{ placeholder }}
    option(v-for="option in options" :key="option.value" :value="option.value") {{ option.label }}
  ChevronDown.filter-dropdown__chevron(:size="16" aria-hidden="true")
</template>

<style scoped lang="scss">
.filter-dropdown {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.filter-dropdown__icon {
  position: absolute;
  z-index: 1;
  left: 0.85rem;
  color: var(--text-subtle);
  pointer-events: none;
}

.filter-dropdown__select {
  width: 100%;
  padding: 0 2.4rem 0 2.6rem;
  appearance: none;
  font-size: 0.9rem;
  cursor: pointer;
}

.filter-dropdown__select:first-child {
  padding-left: var(--space-md);
}

.filter-dropdown__chevron {
  position: absolute;
  right: 0.85rem;
  color: var(--text-subtle);
  pointer-events: none;
}
</style>
