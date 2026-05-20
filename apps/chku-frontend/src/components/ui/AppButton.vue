<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { Component } from 'vue'

const props = defineProps<{
  variant?: 'primary' | 'secondary' | 'ghost' | 'light' | 'inverted'
  to?: string
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  icon?: Component
  ariaLabel?: string
}>()

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const buttonClasses = computed(() => [
  'button',
  `button--${props.variant ?? 'secondary'}`,
  'label-text',
])

const isLink = computed(() => !!props.to)
</script>

<template lang="pug">
component.button-wrapper(
  :is="isLink ? RouterLink : 'button'"
  :to="isLink ? to : undefined"
  :type="isLink ? undefined : (type ?? 'button')"
  :disabled="isLink ? undefined : disabled"
  :aria-label="ariaLabel"
  :class="buttonClasses"
  @click="(e: MouseEvent) => emit('click', e)"
)
  component(v-if="icon" :is="icon" :size="16" aria-hidden="true")
  slot
</template>

<style scoped>
.button-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  min-height: 2.5rem;
  padding: 0.65rem 1.1rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: transparent;
  color: var(--text-main);
  cursor: pointer;
  font-family: var(--font-mono);
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-decoration: none;
  text-transform: uppercase;
  transition:
    background-color 0.15s ease,
    border-color 0.15s ease,
    color 0.15s ease,
    box-shadow 0.15s ease,
    transform 0.15s ease;
}

.button-wrapper:hover {
  border-color: var(--border-strong);
  background: var(--bg-hover);
  color: var(--text-main);
  transform: translateY(-1px);
}

.button-wrapper:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.button-wrapper:active {
  transform: translateY(1px);
}

.button-wrapper:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 3px;
}

.button-wrapper.button--primary {
  border-color: var(--accent);
  background: var(--accent);
  color: var(--text-inverse);
  box-shadow: 0 0.75rem 1.8rem rgba(67, 224, 125, 0.16);
}

.button-wrapper.button--primary:hover {
  border-color: var(--accent-dim);
  background: var(--accent-dim);
  color: var(--text-inverse);
}

.button-wrapper.button--secondary {
  border-color: var(--border);
}

.button-wrapper.button--secondary:hover {
  border-color: var(--border-strong);
}

.button-wrapper.button--ghost {
  min-height: auto;
  padding-right: 0;
  padding-left: 0;
  border: 0;
  border-bottom: var(--border-width) solid transparent;
}

.button-wrapper.button--ghost:hover {
  border-bottom-color: var(--accent);
  background: transparent;
  color: var(--text-main);
}

.button-wrapper.button--light {
  border-color: rgba(255, 255, 255, 0.36);
  color: var(--text-inverse);
}

.button-wrapper.button--light:hover {
  border-color: var(--text-inverse);
  background: rgba(255, 255, 255, 0.12);
  color: var(--text-inverse);
}

.button-wrapper.button--inverted {
  border-color: var(--accent);
  background: var(--accent);
  color: var(--text-inverse);
  box-shadow: 0 0.75rem 1.8rem rgba(67, 224, 125, 0.16);
}

.button-wrapper.button--inverted:hover {
  border-color: var(--accent-dim);
  background: var(--accent-dim);
  color: var(--text-inverse);
}
</style>
