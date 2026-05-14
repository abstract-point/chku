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
    .section-header__icon(aria-hidden="true")
      Users(:size="18")
    .section-header__content
      span#turn-order-title.label-text.section-header__title Очередь выбора
      p.section-header__description Книги выбираются по очереди. Следующую книгу можно утвердить, только если её ещё никто не читал.

  template(v-if="currentProcessMember")
    .turn-order__current-process
      .turn-order__current-header СЕЙЧАС ВЫБИРАЕТ
      .turn-order__item.turn-order__item--active
        .turn-order__person
          .turn-order__avatar {{ getAvatarInitials(currentProcessMember.name) }}
          .turn-order__info
            span.turn-order__name {{ currentProcessMember.name }}
            span.turn-order__description После проверки книга может стать следующей.
        .turn-order__badge.turn-order__badge--dark
          span.turn-order__badge-dot
          span {{ currentProcessBadge }}

  .turn-order__queue(v-if="queueMembers.length")
    .turn-order__item.turn-order__item--queue(
      v-for="(member, index) in queueMembers"
      :key="member.name"
    )
      .turn-order__person
        span.turn-order__index {{ index + 1 }}
        .turn-order__avatar {{ getAvatarInitials(member.name) }}
        span.turn-order__name {{ member.name }}
      .turn-order__badge.turn-order__badge--outline(v-if="index === 0")
        Clock3(:size="14")
        span СЛЕДУЮЩАЯ

  .turn-order__empty(v-if="!currentProcessMember && !queueMembers.length")
    p.body-text Не удалось загрузить очередь.
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
