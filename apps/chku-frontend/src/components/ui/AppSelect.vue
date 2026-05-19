<script setup lang="ts">
import { computed } from 'vue'

export interface SelectOption {
  label: string
  value: string | number | null
}

const props = defineProps<{
  modelValue: string | number | null
  options: SelectOption[]
  id?: string
  name?: string
  required?: boolean
  disabled?: boolean
  ariaInvalid?: boolean
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
  change: [event: Event]
}>()

const selectId = computed(() => props.id ?? props.name)

function onChange(event: Event) {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', target.value)
  emit('change', event)
}
</script>

<template lang="pug">
select.app-select.field-control(
  :id="selectId"
  :name="name"
  :value="modelValue ?? ''"
  :required="required"
  :disabled="disabled"
  :aria-invalid="ariaInvalid || undefined"
  @change="onChange"
)
  option(v-if="placeholder" :value="null") {{ placeholder }}
  option(v-for="option in options" :key="String(option.value)" :value="option.value ?? null")
    | {{ option.label }}
</template>

<style scoped>
.app-select {
  width: 100%;
  padding: 0.75rem 0.9rem;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23999999' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1rem;
  padding-right: 2.5rem;
}
</style>
