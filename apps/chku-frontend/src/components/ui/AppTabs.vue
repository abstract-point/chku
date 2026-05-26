<script setup lang="ts">
import { computed } from 'vue'

export interface Tab {
  id: string
  label: string
}

const props = defineProps<{
  tabs: Tab[]
  modelValue: string
  ariaLabel?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const activeIndex = computed(() => props.tabs.findIndex((t) => t.id === props.modelValue))

function selectTab(id: string) {
  emit('update:modelValue', id)
}

function onKeydown(event: KeyboardEvent, index: number) {
  if (event.key === 'ArrowRight') {
    event.preventDefault()
    const next = (index + 1) % props.tabs.length
    selectTab(props.tabs[next]!.id)
  } else if (event.key === 'ArrowLeft') {
    event.preventDefault()
    const prev = (index - 1 + props.tabs.length) % props.tabs.length
    selectTab(props.tabs[prev]!.id)
  } else if (event.key === 'Home') {
    event.preventDefault()
    selectTab(props.tabs[0]!.id)
  } else if (event.key === 'End') {
    event.preventDefault()
    selectTab(props.tabs[props.tabs.length - 1]!.id)
  }
}
</script>

<template lang="pug">
nav.app-tabs(role="tablist" :aria-label="ariaLabel")
  button.app-tabs__tab(
    v-for="(tab, index) in tabs"
    :key="tab.id"
    type="button"
    role="tab"
    :id="`tab-${tab.id}`"
    :aria-selected="modelValue === tab.id"
    :aria-controls="`panel-${tab.id}`"
    :tabindex="modelValue === tab.id ? 0 : -1"
    :class="{ 'app-tabs__tab--active': modelValue === tab.id }"
    @click="selectTab(tab.id)"
    @keydown="onKeydown($event, index)"
  )
    | {{ tab.label }}
</template>

<script lang="ts">
export default {
  name: 'AppTabs',
}
</script>

<style scoped>
.app-tabs {
  display: flex;
  gap: var(--space-sm);
  border-bottom: var(--border-width) solid var(--border);
  margin-bottom: var(--space-lg);
}

.app-tabs__tab {
  position: relative;
  padding: var(--space-sm) var(--space-md);
  border: none;
  border-radius: var(--radius-inner) var(--radius-inner) 0 0;
  background: transparent;
  color: var(--text-muted);
  font-family: var(--font-sans);
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.4;
  cursor: pointer;
  transition: color 0.15s ease;
  touch-action: manipulation;
  min-height: 2.75rem;
}

.app-tabs__tab:hover {
  color: var(--text-secondary);
}

.app-tabs__tab:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.app-tabs__tab--active {
  color: var(--text-main);
}

.app-tabs__tab--active::after {
  position: absolute;
  right: 0;
  bottom: -1px;
  left: 0;
  height: 2px;
  background: var(--accent);
  content: '';
}
</style>
