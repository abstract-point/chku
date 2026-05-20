<script setup lang="ts">
import { CalendarPlus } from '@lucide/vue'
import { RouterLink } from 'vue-router'
import DashboardMeetingCard from '@/components/dashboard/DashboardMeetingCard.vue'
import type { MeetingSummary } from '@/types/dashboard'

defineProps<{
  meeting?: MeetingSummary | null
  currentCycleStatus?: string | null
  isAdmin: boolean
}>()
</script>

<template lang="pug">
DashboardMeetingCard(v-if="meeting" :meeting="meeting")
section.panel.dashboard-meeting(v-else-if="currentCycleStatus === 'active'")
  .section-header.section-header--compact
    span.label-text Следующая встреча
  .dashboard-meeting__empty
    CalendarPlus.dashboard-meeting__empty-icon(:size="22")
    h3 Встреча ещё не назначена
    p.body-text Администратор добавит дату и место, когда клуб договорится о встрече.
    RouterLink.button.button--primary.label-text.dashboard-meeting__button(
      v-if="isAdmin"
      to="/meetings/create"
    ) Назначить встречу
</template>

<style scoped>
.dashboard-meeting__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-lg) var(--space-md);
  text-align: center;
}

.dashboard-meeting__empty-icon {
  color: var(--warn);
  margin-bottom: var(--space-sm);
}

.dashboard-meeting__button {
  justify-content: center;
  width: min(100%, 16rem);
  margin-top: var(--space-sm);
}
</style>
