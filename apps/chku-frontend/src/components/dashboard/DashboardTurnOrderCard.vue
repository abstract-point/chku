<script setup lang="ts">
import type { TurnOrderMember } from '@/types/dashboard'

defineProps<{
  members: TurnOrderMember[]
}>()
</script>

<template lang="pug">
section.panel.dashboard-card(aria-labelledby="turn-order-title")
  .section-header.section-header--compact
    span#turn-order-title.label-text Очередь выбора
  ul.data-list(role="list")
    li.data-list__item.turn-order__item(
      v-for="member in members"
      :key="member.name"
      :class="{ 'turn-order__item--active': member.active }"
    )
      span.turn-order__name {{ member.name }}
      span.badge.badge--action.label-text(v-if="member.active") {{ member.status }}
      span.label-text.turn-order__status(v-else) {{ member.status }}
</template>

<style scoped>
.turn-order__item {
  color: var(--text-muted);
}

.turn-order__item--active {
  margin: 0 calc(var(--space-lg) * -1);
  padding: 0.65rem var(--space-lg);
  border-bottom: 0;
  border-left: 2px solid var(--accent);
  background: var(--accent-bg);
  color: var(--text-main);
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
