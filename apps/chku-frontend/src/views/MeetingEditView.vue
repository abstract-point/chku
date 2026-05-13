<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MeetingForm from '@/components/meetings/MeetingForm.vue'
import { useAuthSession } from '@/queries/authQueries'
import { useMeetingQuery, useUpdateMeetingMutation } from '@/queries/meetingQueries'

const route = useRoute()
const router = useRouter()
const { isAdmin, user } = useAuthSession()
const meetingId = computed(() => String(route.params.id ?? ''))
const meetingQuery = useMeetingQuery(meetingId, computed(() => user.value?.id))
const updateMutation = useUpdateMeetingMutation(meetingId)

const meeting = computed(() => meetingQuery.data.value)

function handleSubmit(payload: Record<string, unknown>) {
  updateMutation.mutate(payload as Parameters<typeof updateMutation.mutate>[0], {
    onSuccess: () => {
      router.push(`/meetings/${meetingId.value}`)
    },
  })
}
</script>

<template lang="pug">
main.container
  section.panel(v-if="!isAdmin")
    .section-header
      h1 Доступ запрещён
    p.body-text Только администратор может редактировать встречи.
  section.panel(v-else-if="meetingQuery.isLoading.value")
    p.body-text Загружаем встречу...
  section.panel(v-else-if="meetingQuery.error.value")
    .section-header
      h1 Встреча не найдена
    p.body-text Возможно, ссылка устарела.
  MeetingForm(
    v-else-if="meeting"
    :meeting="meeting"
    :cycle-id="meeting.cycleId"
    :is-submitting="updateMutation.isPending.value"
    @submit="handleSubmit"
  )
  p.body-text(v-if="updateMutation.error.value") Не удалось обновить встречу.
</template>
