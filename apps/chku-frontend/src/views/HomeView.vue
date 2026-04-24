<script setup lang="ts">
import DashboardCurrentCycle from '@/components/dashboard/DashboardCurrentCycle.vue'
import DashboardMeetingCard from '@/components/dashboard/DashboardMeetingCard.vue'
import DashboardTurnOrderCard from '@/components/dashboard/DashboardTurnOrderCard.vue'
import { currentBook, memberProgress, nextMeeting, turnOrder } from '@/data/dashboard'

const clubStats = [
  { value: '41', label: 'Прочитано книг' },
  { value: '4.8', label: 'Средний рейтинг' },
  { value: '6', label: 'Участников' },
  { value: '12', label: 'Встреч в год' },
]
</script>

<template lang="pug">
main.dashboard.container
  .dashboard__grid
    DashboardCurrentCycle(:book="currentBook" :members="memberProgress")
    aside.dashboard__sidebar(aria-label="Сводка клуба")
      DashboardMeetingCard(:meeting="nextMeeting")
      DashboardTurnOrderCard(:members="turnOrder")
      section.panel.panel--filled.dashboard-stats(aria-labelledby="club-stats-title")
        .section-header.section-header--compact
          span#club-stats-title.label-text Клубная сводка
        .dashboard-stats__grid
          .dashboard-stats__item(v-for="stat in clubStats" :key="stat.label")
            span.dashboard-stats__value {{ stat.value }}
            span.label-text {{ stat.label }}
</template>

<style scoped>
.dashboard__grid {
  display: grid;
  grid-template-columns: minmax(0, 1.6fr) minmax(18rem, 1fr);
  gap: var(--space-xl);
  align-items: start;
}

.dashboard__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.dashboard-stats__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-md);
}

.dashboard-stats__item {
  display: grid;
  gap: 0.15rem;
}

.dashboard-stats__value {
  color: var(--text-main);
  font-size: 1.4rem;
  font-weight: 700;
  line-height: 1;
}

@media (max-width: 960px) {
  .dashboard__grid {
    grid-template-columns: 1fr;
  }
}
</style>
