<script setup lang="ts">
import DashboardCurrentCycle from '@/components/dashboard/DashboardCurrentCycle.vue'
import DashboardMeetingCard from '@/components/dashboard/DashboardMeetingCard.vue'
import DashboardTurnOrderCard from '@/components/dashboard/DashboardTurnOrderCard.vue'
import { useDashboardQuery } from '@/queries/dashboardQueries'

const dashboardQuery = useDashboardQuery()
</script>

<template lang="pug">
main.dashboard.container
  section.panel(v-if="dashboardQuery.isLoading.value" aria-live="polite")
    p.body-text Загружаем дашборд...
  section.panel(v-else-if="dashboardQuery.error.value" aria-live="polite")
    p.body-text Не удалось загрузить дашборд.
  .dashboard__grid(v-else-if="dashboardQuery.data.value")
    DashboardCurrentCycle(
      v-if="dashboardQuery.data.value.currentBook"
      :book="dashboardQuery.data.value.currentBook"
      :members="dashboardQuery.data.value.memberProgress"
    )
    section.panel(v-else)
      .section-header.section-header--compact
        h2 Текущий цикл ещё не начат
      p.body-text Когда книга будет утверждена, она появится здесь.
    aside.dashboard__sidebar(aria-label="Сводка клуба")
      DashboardMeetingCard(v-if="dashboardQuery.data.value.nextMeeting" :meeting="dashboardQuery.data.value.nextMeeting")
      DashboardTurnOrderCard(:members="dashboardQuery.data.value.turnOrder")
      section.panel.panel--filled.dashboard-stats(aria-labelledby="club-stats-title")
        .section-header.section-header--compact
          span#club-stats-title.label-text Клубная сводка
        .dashboard-stats__grid
          .dashboard-stats__item(v-for="stat in dashboardQuery.data.value.clubStats" :key="stat.label")
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
