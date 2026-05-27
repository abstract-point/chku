<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { CalendarDays, Play } from '@lucide/vue'
import AppBanner from '@/components/ui/AppBanner.vue'
import type { MeetingSummary } from '@/types/dashboard'
const props = defineProps<{
  meeting: MeetingSummary
}>()

const isStarted = computed(() => props.meeting.status === 'started')
</script>

<template lang="pug">
AppBanner(:variant="isStarted ? 'info' : 'warn'")
  template(#icon)
    Play(:size="22" aria-hidden="true" v-if="isStarted")
    CalendarDays(:size="22" aria-hidden="true" v-else)
  template(#content)
    span.label-text {{ $t('dash.bannerAction') }}
    h2(v-if="isStarted") {{ $t('dash.bannerMeetingStarted') }}
    h2(v-else) {{ $t('dash.bannerMeetingScheduled', { title: meeting.title }) }}
    p.body-text(v-if="isStarted") {{ $t('dash.bannerMeetingStartedText') }}
    p.body-text(v-else) {{ $t('dash.bannerMeetingScheduledText') }}
  template(#actions)
    RouterLink.button.button--primary.label-text(:to="`/meetings/${meeting.id}`") {{ $t('dash.bannerGoToMeeting') }}
</template>
