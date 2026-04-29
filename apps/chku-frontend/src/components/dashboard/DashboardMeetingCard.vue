<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { MapPin } from '@lucide/vue'
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
    p.dashboard-card__date-extra.body-text {{ meeting.dayTimeLabel }}
    p.body-text.dashboard-card__text
      MapPin(:size="17")
      span {{ meeting.place }}
  .dashboard-card__avatars(aria-label="Участники встречи")
    span.avatar.avatar--outlined(v-for="initials in meeting.participantInitials" :key="initials") {{ initials }}
    span.avatar.avatar--more +{{ meeting.extraParticipantsCount }}
  button.button.button--primary.dashboard-card__button(type="button") Подтвердить участие
  RouterLink.button.button--ghost.label-text.dashboard-card__link(:to="`/meetings/${meeting.id}`") Подробнее о встрече
</template>

<style scoped>
.dashboard-card__title {
  margin-bottom: 0.15rem;
  font-size: clamp(2.3rem, 7vw, 3.6rem);
  line-height: 0.95;
}

.dashboard-card__text {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.dashboard-card__date-extra {
  margin-bottom: var(--space-md);
  color: var(--text-main);
  font-size: 0.98rem;
}

.dashboard-card__avatars {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.dashboard-card__button {
  width: 100%;
  margin-top: var(--space-sm);
}

.dashboard-card__link {
  width: 100%;
  margin-top: var(--space-sm);
}
</style>
