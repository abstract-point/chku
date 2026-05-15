<script setup lang="ts">
import { computed } from 'vue'
import { Users, Clock3 } from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import type { TurnOrderMember } from '@/types/dashboard'

const props = defineProps<{
  members: TurnOrderMember[]
  cycleStatus?: string | null
}>()

const currentProcessMember = computed(() =>
  props.members.find((m) => m.isCurrentCycleProposer || m.isChoosingNow || m.active),
)

const queueMembers = computed(() =>
  props.members.filter((m) => m !== currentProcessMember.value),
)

const currentProcessBadge = computed(() => {
  if (!currentProcessMember.value) return null
  if (currentProcessMember.value.isChoosingNow) return 'ВЫБИРАЕТ КНИГУ'
  if (currentProcessMember.value.isCurrentCycleProposer) return 'ВЫБРАЛ КНИГУ'
  if (currentProcessMember.value.active) return 'ВЫБИРАЕТ КНИГУ'
  return null
})

function avatarName(name: string): string {
  return name.replace(/^\d+\.\s*/, '')
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
          UserAvatar.turn-order__avatar(
            :name="avatarName(currentProcessMember.name)"
            :avatar-url="currentProcessMember.avatarUrl"
          )
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
        UserAvatar.turn-order__avatar(:name="avatarName(member.name)" :avatar-url="member.avatarUrl")
        span.turn-order__name {{ member.name }}
      .turn-order__badge.turn-order__badge--outline(v-if="index === 0")
        Clock3(:size="14")
        span СЛЕДУЮЩАЯ

  .turn-order__empty(v-if="!currentProcessMember && !queueMembers.length")
    p.body-text Не удалось загрузить очередь.
</template>

<style scoped>
.turn-order__current-process {
  padding: var(--space-md) var(--space-lg);
  border-radius: var(--radius-panel);
  background: var(--accent-bg);
}

.turn-order__current-header {
  color: var(--accent);
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: var(--space-sm);
  opacity: 0.9;
}

.turn-order__item--active {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
}

.turn-order__person {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.turn-order__avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--bg-surface-raised);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-main);
  flex-shrink: 0;
}

.turn-order__item--active .turn-order__avatar {
  background: rgba(255, 255, 255, 0.12);
}

.turn-order__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.turn-order__name {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-main);
}

.turn-order__description {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.turn-order__badge {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
  padding: 0.4rem 0.85rem;
  border-radius: 100px;
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.turn-order__badge--dark {
  background: rgba(0, 0, 0, 0.4);
  color: var(--text-main);
  overflow: hidden;
}

.turn-order__badge--outline {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-muted);
  padding: 0.35rem 0.75rem;
}

.turn-order__badge-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}

.turn-order__queue {
  margin: 0 calc(var(--space-lg) * -1) var(--space-md);
  padding: 0 var(--space-lg);
}

.turn-order__item--queue {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-md) 0;
  border-bottom: var(--border-width) solid var(--border);
}

.turn-order__item--queue:last-child {
  border-bottom: none;
}

.turn-order__index {
  min-width: 1.2rem;
  color: var(--text-subtle);
  font-family: var(--font-mono);
  font-size: 0.95rem;
  font-weight: 500;
}

.turn-order__item--queue .turn-order__avatar {
  width: 32px;
  height: 32px;
  font-size: 0.65rem;
}

.turn-order__item--queue .turn-order__name {
  font-size: 0.85rem;
  font-weight: 500;
}

.turn-order__empty {
  padding: var(--space-md) var(--space-lg);
  text-align: center;
  color: var(--text-muted);
}

.section-header {
  display: flex;
  align-items: flex-start;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.section-header__icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--accent-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent);
  flex-shrink: 0;
}

.section-header__content {
  flex: 1;
}

.section-header__title {
  display: block;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--text-main);
  margin-bottom: 4px;
}

.section-header__description {
  font-size: 0.78rem;
  color: var(--text-muted);
  line-height: 1.5;
  margin: 0;
}
</style>
