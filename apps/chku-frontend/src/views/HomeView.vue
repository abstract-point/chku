<script setup lang="ts">
import DashboardCurrentCycle from '@/components/dashboard/DashboardCurrentCycle.vue'
import DashboardMeetingCard from '@/components/dashboard/DashboardMeetingCard.vue'
import DashboardTurnOrderCard from '@/components/dashboard/DashboardTurnOrderCard.vue'
import { currentBook, memberProgress, nextMeeting, turnOrder } from '@/data/dashboard'
</script>

<template lang="pug">
main.dashboard.container
  .dashboard__grid
    DashboardCurrentCycle(:book="currentBook" :members="memberProgress")
    aside.dashboard__sidebar(aria-label="Сводка клуба")
      DashboardMeetingCard(:meeting="nextMeeting")
      DashboardTurnOrderCard(:members="turnOrder")
</template>

<style scoped>
.dashboard__grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(18rem, 1fr);
  gap: var(--space-xl);
}

.dashboard__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

@media (max-width: 960px) {
  .dashboard__grid {
    grid-template-columns: 1fr;
  }
}
</style>
