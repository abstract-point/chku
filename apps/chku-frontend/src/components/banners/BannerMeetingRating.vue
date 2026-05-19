<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Star } from '@lucide/vue'
import AppBanner from '@/components/ui/AppBanner.vue'

defineProps<{
  meetingId: string
}>()
</script>

<template lang="pug">
AppBanner(variant="warn")
  template(#icon)
    Star(:size="22" aria-hidden="true")
  template(#content)
    span.label-text Требуется действие
    h2 Поставь оценку книге
    p.body-text Встреча уже началась. Чтобы завершить цикл, нужна твоя оценка.
  template(#actions)
    RouterLink.button.button--primary.label-text(:to="`/meetings/${meetingId}`") Перейти к встрече
</template>
