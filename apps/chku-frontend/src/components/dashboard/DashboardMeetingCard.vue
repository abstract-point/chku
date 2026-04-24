<script setup lang="ts">
import type { MeetingSummary } from '@/types/dashboard'

defineProps<{
  meeting: MeetingSummary
}>()
</script>

<template lang="pug">
section.panel.dashboard-card(aria-labelledby="meeting-title")
  .section-header.section-header--compact
    span#meeting-title.label-text Следующая встреча
  .dashboard-card__meta
    h3.dashboard-card__title {{ meeting.dateLabel }}
    p.body-text.dashboard-card__text {{ meeting.place }}
  .dashboard-card__avatars(aria-label="Участники встречи")
    span.avatar.avatar--outlined(v-for="initials in meeting.participantInitials" :key="initials") {{ initials }}
    span.avatar.avatar--more +{{ meeting.extraParticipantsCount }}
  button.button.button--primary.dashboard-card__button(type="button") Подтвердить участие
</template>

<style scoped>
.dashboard-card__title {
  margin-bottom: 0.15rem;
  font-size: 1.1rem;
}

.dashboard-card__text {
  margin-bottom: var(--space-md);
}

.dashboard-card__avatars {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.dashboard-card__button {
  width: 100%;
}
</style>
