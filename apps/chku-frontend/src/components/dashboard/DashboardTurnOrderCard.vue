<script setup lang="ts">
import { computed } from 'vue'
import { Users } from '@lucide/vue'
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
  if (currentProcessMember.value.isChoosingNow) return 'Выбирает книгу'
  if (currentProcessMember.value.isCurrentCycleProposer) return 'Выбрал книгу'
  if (currentProcessMember.value.active) return 'ВЫБИРАЕТ КНИГУ'
  return null
})

const allMembers = computed(() => {
  const result: { member: TurnOrderMember; isActive: boolean; isNext: boolean }[] = []
  if (currentProcessMember.value) {
    result.push({ member: currentProcessMember.value, isActive: true, isNext: false })
  }
  queueMembers.value.forEach((m, i) => {
    result.push({ member: m, isActive: false, isNext: i === 0 })
  })
  return result
})
</script>

<template lang="pug">
section.panel.dashboard-card(aria-labelledby="turn-order-title")
  .section-header.section-header--compact
    .section-header__icon(aria-hidden="true")
      Users(:size="18")
    .section-header__content
      span#turn-order-title.label-text.section-header__title Очередь выбора
      p.section-header__description Книги выбираются по очереди. Следующую книгу можно утвердить, только если её ещё никто не читал.

  .turn-order__list
    .turn-order__card(
      v-for="(item, index) in allMembers"
      :key="item.member.name"
      :class=`{
        'turn-order__card--active': item.isActive,
        'turn-order__card--next': item.isNext
      }`
    )
      template(v-if="item.isActive")
        .turn-order__card-header ТЕКУЩИЙ ЦИКЛ
      .turn-order__card-body
        .turn-order__person
          UserAvatar.turn-order__avatar(
            :name="item.member.name"
            :avatar-url="item.member.avatarUrl"
            size="md"
          )
          .turn-order__info
            span.turn-order__name {{ item.member.name }}
            template(v-if="item.isActive")
              span.turn-order__badge-text {{ currentProcessBadge }}
            span.turn-order__badge-text.turn-order__badge-text--muted(v-else-if="item.isNext") Выбирает следующий

  .turn-order__empty(v-if="!allMembers.length")
    p.body-text Не удалось загрузить очередь.
</template>

<style scoped>
.turn-order__list {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.turn-order__card {
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-inner);
  border: var(--border-width) solid transparent;
  background: transparent;
}

.turn-order__card--active {
  border-color: var(--accent-border);
  background: linear-gradient(135deg, rgba(67, 224, 125, 0.06), rgba(67, 224, 125, 0.015));
  box-shadow: 0 0 16px rgba(67, 224, 125, 0.06);
}

.turn-order__card--next {
  border-color: var(--border-strong);
  background: var(--bg-hover);
}

.turn-order__card-header {
  color: var(--accent);
  font-size: 0.6rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: var(--space-xs);
  opacity: 0.9;
}

.turn-order__card-body {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
}

.turn-order__person {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.turn-order__avatar {
  flex-shrink: 0;
}

.turn-order__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.turn-order__name {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-main);
}

.turn-order__badge-text {
  font-size: 0.65rem;
  font-weight: 500;
  color: var(--accent);
  letter-spacing: 0.02em;
}

.turn-order__badge-text--muted {
  color: var(--text-muted);
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
