<script setup lang="ts">
const props = defineProps<{
  label?: string
  labelFor?: string
  hint?: string
  error?: string
  required?: boolean
}>()
</script>

<template lang="pug">
.app-form-field
  label.app-form-field__label.label-text(v-if="label" :for="labelFor")
    | {{ label }}
    span.app-form-field__required(v-if="required" aria-hidden="true") *
  .app-form-field__control
    slot
  p.app-form-field__hint(v-if="hint && !error") {{ hint }}
  p.app-form-field__error(v-if="error" aria-live="polite") {{ error }}
</template>

<style scoped>
.app-form-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.app-form-field__label {
  display: block;
}

.app-form-field__required {
  margin-left: 0.15rem;
  color: var(--danger);
}

.app-form-field__hint {
  margin: 0;
  color: var(--text-subtle);
  font-size: 0.8rem;
}

.app-form-field__error {
  margin: 0;
  color: var(--danger);
  font-size: 0.85rem;
}
</style>
