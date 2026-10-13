<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    current: number
    max: number
    id?: string
    /** Share of the limit (0–1) after which the counter becomes visible. */
    threshold?: number
  }>(),
  {
    id: undefined,
    threshold: 0,
  },
)

const isVisible = computed(() => props.current >= props.max * props.threshold)
const isNearLimit = computed(() => props.current >= props.max * 0.9)
const label = computed(
  () => `${props.current.toLocaleString('ru-RU')} / ${props.max.toLocaleString('ru-RU')}`,
)
</script>

<template lang="pug">
span.char-counter(
  v-if="isVisible"
  :id="id"
  :class="{ 'char-counter--warn': isNearLimit }"
) {{ label }}
</template>

<style scoped>
.char-counter {
  display: block;
  margin-left: auto;
  text-align: right;
  color: var(--text-subtle);
  font-family: var(--font-mono);
  font-size: 0.68rem;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.04em;
  line-height: 1.4;
  white-space: nowrap;
}

.char-counter--warn {
  color: var(--warn);
}
</style>
