<script setup lang="ts">
import type { TurnOrderMember } from '@/types/dashboard'

defineProps<{
  members: TurnOrderMember[]
}>()

function displayMemberName(name: string) {
  return name.replace(/^\d+\.\s*/, '')
}
</script>

<template lang="pug">
section.panel.dashboard-card(aria-labelledby="turn-order-title")
  .section-header.section-header--compact
    span#turn-order-title.label-text Очередь выбора
  ul.data-list(role="list")
    li.data-list__item.turn-order__item(
      v-for="(member, index) in members"
      :key="member.name"
      :class="{ 'turn-order__item--active': member.active }"
    )
      .turn-order__person
        span.turn-order__index {{ index + 1 }}.
        span.turn-order__name {{ displayMemberName(member.name) }}
      span.badge.badge--action.label-text(v-if="member.active") {{ member.status }}
      span.label-text.turn-order__status(v-else) {{ member.status }}
</template>

<style scoped>
.turn-order__item {
  color: var(--text-muted);
  min-height: 3rem;
}

.turn-order__item--active {
  margin: 0 calc(var(--space-lg) * -1);
  padding: 0.65rem var(--space-lg);
  border-bottom: 0;
  border-left: 2px solid var(--accent);
  background: var(--accent-bg);
  color: var(--text-main);
}

.turn-order__person {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.turn-order__index {
  min-width: 1.4rem;
  color: var(--text-subtle);
  font-family: var(--font-mono);
  font-size: 0.78rem;
}

.turn-order__name {
  font-size: 0.8rem;
}

.turn-order__item--active .turn-order__name {
  font-weight: 500;
}

.turn-order__status {
  color: var(--text-muted);
  font-size: 0.5rem;
}
</style>
