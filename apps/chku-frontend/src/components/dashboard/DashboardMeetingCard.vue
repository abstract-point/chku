<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowRight, Link as LinkIcon, MapPin, Monitor } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import { useAuthSession } from '@/queries/authQueries'
import { useUpdateMeetingRsvpMutation } from '@/queries/meetingQueries'
import MemberTooltip from '@/components/dashboard/MemberTooltip.vue'
import type { MeetingSummary } from '@/types/dashboard'

const { t } = useI18n()
const router = useRouter()
const props = defineProps<{
  meeting: MeetingSummary
}>()

const { user } = useAuthSession()
const rsvpMutation = useUpdateMeetingRsvpMutation(() => props.meeting.id)

const myRsvpStatus = computed(() =>
  user.value
    ? (props.meeting.attendees.find((a) => a.id === user.value!.id)?.status ?? 'pending')
    : 'pending',
)

const attendingMembers = computed(() =>
  props.meeting.attendees.filter((a) => a.status === 'attending'),
)

const visibleAttendees = computed(() => attendingMembers.value.slice(0, 4))
const extraCount = computed(() => Math.max(0, attendingMembers.value.length - 4))
const sectionLabel = computed(() =>
  props.meeting.status === 'started' ? t('dash.meetingInProgress') : t('dash.nextMeeting'),
)

function setRsvp(status: 'attending' | 'not_attending') {
  rsvpMutation.mutate(status)
}

function goToMeeting() {
  router.push(`/meetings/${props.meeting.id}`)
}
</script>

<template lang="pug">
section.panel.dashboard-card(aria-labelledby="meeting-title")
  .section-header.section-header--compact
    span#meeting-title.label-text {{ sectionLabel }}
  .dashboard-card__meta-link(@click="goToMeeting")
    .dashboard-card__meta
      h3.dashboard-card__title {{ meeting.dateLabel }}
      p.dashboard-card__date-extra.body-text {{ meeting.dayTimeLabel }}
      p.body-text.dashboard-card__text(v-if="!meeting.isOnline")
        MapPin(:size="17")
        span {{ meeting.place }}
      p.body-text.dashboard-card__text(v-else)
        Monitor(:size="17")
        span {{ $t('dash.online') }}
      p.body-text.dashboard-card__text(v-if="meeting.link")
        LinkIcon(:size="17")
        a.dashboard-card__meeting-link(
          :href="meeting.link"
          target="_blank"
          rel="noopener noreferrer"
          @click.stop
        ) {{ meeting.link }}
  .dashboard-card__avatars(v-if="attendingMembers.length" :aria-label="$t('dash.meetingAttendeesAria')")
    MemberTooltip(v-for="member in visibleAttendees" :key="member.id" :member="member")
    span.avatar.avatar--more(v-if="extraCount > 0") +{{ extraCount }}
  .inline-alert.inline-alert--success(v-if="myRsvpStatus === 'attending'") {{ $t('dash.youAttending') }}
  .inline-alert(v-else-if="myRsvpStatus === 'not_attending'") {{ $t('dash.youNotAttending') }}
  button.button.button--primary.dashboard-card__button(
    v-else
    type="button"
    :disabled="rsvpMutation.isPending.value"
    @click="setRsvp('attending')"
  ) {{ $t('dash.confirmAttendance') }}
  button.button.button--secondary.dashboard-card__button(
    v-if="myRsvpStatus === 'pending'"
    type="button"
    :disabled="rsvpMutation.isPending.value"
    @click="setRsvp('not_attending')"
  ) {{ $t('dash.cannotAttend') }}
  RouterLink.button.button--secondary.dashboard-card__link(:to="`/meetings/${meeting.id}`")
    span {{ $t('dash.meetingDetails') }}
    ArrowRight(:size="16")
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

.dashboard-card__meeting-link {
  color: var(--accent);
  text-decoration: none;
  word-break: break-all;
}

.dashboard-card__meeting-link:hover {
  text-decoration: underline;
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

.dashboard-card__meta-link {
  display: block;
  cursor: pointer;
}

.dashboard-card__meta-link:hover .dashboard-card__title {
  color: var(--accent);
}
</style>
