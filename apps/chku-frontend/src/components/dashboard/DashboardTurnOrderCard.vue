<script setup lang="ts">
import { computed } from 'vue'
import { Users, Clock3 } from '@lucide/vue'
import type { TurnOrderMember } from '@/types/dashboard'

const props = defineProps<{
  members: TurnOrderMember[]
  cycleStatus?: string | null
}>()

const currentProcessMember = computed(() =>
  props.members.find((m) => m.isCurrentCycleProposer || m.isChoosingNow),
)

const queueMembers = computed(() =>
  props.members.filter((m) => !m.isCurrentCycleProposer && !m.isChoosingNow),
)

const currentProcessBadge = computed(() => {
  if (!currentProcessMember.value) return null
  if (currentProcessMember.value.isChoosingNow) return 'ВЫБИРАЕТ КНИГУ'
  if (currentProcessMember.value.isCurrentCycleProposer) return 'ВЫБРАЛ КНИГУ'
  return null
})

function getAvatarInitials(name: string): string {
  const cleanName = name.replace(/^\d+\.\s*/, '')
  const parts = cleanName.trim().split(/\s+/)
  const firstLetter = parts[0]?.[0]?.toUpperCase() ?? ''
  const numberPrefix = name.match(/^(\d+)/)?.[1] ?? ''
  return `${numberPrefix}${firstLetter}`
}
</script>

<template lang="pug">
section.panel.dashboard-card(aria-labelledby="turn-order-title")
  .section-header.section-header--compact
    span#turn-order-title.label-text Очередь выбора

  template(v-if="currentProcessMember")
    .turn-order__current-process
      .turn-order__item.turn-order__item--active
        .turn-order__person
          span.turn-order__name {{ displayMemberName(currentProcessMember.name) }}
        span.badge.badge--action.label-text {{ currentProcessBadge }}

  ul.data-list(v-if="queueMembers.length" role="list")
    li.data-list__item.turn-order__item(
      v-for="(member, index) in queueMembers"
      :key="member.name"
      :class="{ 'turn-order__item--next': member.active }"
    )
      .turn-order__person
        span.turn-order__index {{ index + 1 }}.
        span.turn-order__name {{ displayMemberName(member.name) }}
      span.badge.badge--action.label-text(v-if="member.active") {{ member.status }}
      span.label-text.turn-order__status(v-else-if="member.status") {{ member.status }}
</template>

<style scoped>
.turn-order__current-process {
  margin: 0 calc(var(--space-lg) * -1) var(--space-xs);
  padding: 0.65rem var(--space-lg);
  border-bottom: var(--border-width) solid var(--border);
  border-left: 2px solid var(--accent);
  background: var(--accent-bg);
  color: var(--text-main);
}

.turn-order__current-process .turn-order__item--active {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
}

.turn-order__item {
  color: var(--text-muted);
  min-height: 3rem;
}

.turn-order__item--next {
  margin: 0 calc(var(--space-lg) * -1);
  padding: 0.65rem var(--space-lg);
  border-bottom: 0;
  border-left: 2px solid var(--border);
  background: var(--bg-surface-raised);
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

.turn-order__item--active .turn-order__name,
.turn-order__item--next .turn-order__name {
  font-weight: 500;
}

.turn-order__status {
  color: var(--text-muted);
  font-size: 0.5rem;
}
</style>
