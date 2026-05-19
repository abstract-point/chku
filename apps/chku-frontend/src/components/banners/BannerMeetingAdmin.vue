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
    span.label-text Требуется действие
    h2(v-if="isStarted") Встреча идёт сейчас
    h2(v-else) Встреча «{{ meeting.title }}» запланирована
    p.body-text(v-if="isStarted") Перейди на страницу встречи, чтобы завершить её и закрыть цикл.
    p.body-text(v-else) Назначенное время встречи наступило. Можно начать встречу.
  template(#actions)
    RouterLink.button.button--primary.label-text(:to="`/meetings/${meeting.id}`") Перейти к управлению встречей
</template>
