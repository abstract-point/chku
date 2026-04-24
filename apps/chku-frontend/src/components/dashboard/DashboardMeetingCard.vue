<script setup lang="ts">
import type { MeetingSummary } from '@/types/dashboard'

defineProps<{
  meeting: MeetingSummary
}>()
</script>

<template lang="pug">
section.panel.dashboard-card(aria-labelledby="meeting-title")
  .section-header.section-header--compact
    span#meeting-title.label-text Ближайшая встреча
  h3.dashboard-card__title {{ meeting.dateLabel }}
  p.body-text.dashboard-card__text {{ meeting.place }}
  .dashboard-card__avatars(aria-label="Участники встречи")
    span.avatar.avatar--outlined(v-for="initials in meeting.participantInitials" :key="initials") {{ initials }}
    span.avatar.avatar--more +{{ meeting.extraParticipantsCount }}
  button.button.button--secondary.label-text.dashboard-card__button(type="button") Отметить участие
</template>

<style scoped>
.dashboard-card__title {
  margin-bottom: var(--space-xs);
}

.dashboard-card__text {
  margin-bottom: var(--space-md);
}

.dashboard-card__avatars {
  display: flex;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.dashboard-card__button {
  width: 100%;
}
</style>
