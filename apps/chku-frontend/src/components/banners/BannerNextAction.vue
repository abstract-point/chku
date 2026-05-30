<script setup lang="ts">
import {
  BookCheck,
  BookOpenCheck,
  CalendarCheck,
  ListPlus,
  PencilLine,
  Star,
  TrendingUp,
} from '@lucide/vue'
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { ApiNextAction } from '@/api/types'
import AppBanner from '@/components/ui/AppBanner.vue'

const props = defineProps<{
  action: ApiNextAction
}>()

const icon = computed(() => {
  return (
    {
      respond_candidate: BookCheck,
      confirm_candidate: BookOpenCheck,
      add_book_to_queue: ListPlus,
      update_progress: TrendingUp,
      rsvp_meeting: CalendarCheck,
      rate_book: Star,
      write_review: PencilLine,
      none: BookCheck,
    }[props.action.type] ?? BookCheck
  )
})

const actionLabel = computed(() => {
  return (
    {
      respond_candidate: 'Ответить',
      confirm_candidate: 'Подтвердить',
      add_book_to_queue: 'Управлять очередью',
      update_progress: 'Обновить прогресс',
      rsvp_meeting: 'Отметить RSVP',
      rate_book: 'Поставить оценку',
      write_review: 'Оставить отзыв',
      none: 'Перейти',
    }[props.action.type] ?? 'Перейти'
  )
})
</script>

<template lang="pug">
AppBanner(variant="action")
  template(#icon)
    component(:is="icon" :size="22" aria-hidden="true")
  template(#content)
    span.label-text {{ $t('dash.bannerAction') }}
    h2 {{ action.title }}
    p.body-text {{ action.description }}
  template(#actions)
    RouterLink.button.button--primary.label-text(:to="action.actionUrl") {{ actionLabel }}
</template>
