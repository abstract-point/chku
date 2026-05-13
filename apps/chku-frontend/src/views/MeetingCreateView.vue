<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import MeetingForm from '@/components/meetings/MeetingForm.vue'
import { useAuthSession } from '@/queries/authQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { useCreateMeetingMutation } from '@/queries/meetingQueries'

const router = useRouter()
const { isAdmin } = useAuthSession()
const dashboardQuery = useDashboardQuery()
const createMutation = useCreateMeetingMutation()

const cycleId = computed(() => dashboardQuery.data.value?.lifecycle?.currentCycleId)

function handleSubmit(payload: Record<string, unknown>) {
  createMutation.mutate(payload as Parameters<typeof createMutation.mutate>[0], {
    onSuccess: (meeting) => {
      router.push(`/meetings/${meeting.id}`)
    },
  })
}
</script>

<template lang="pug">
main.container
  section.panel(v-if="!isAdmin")
    .section-header
      h1 Доступ запрещён
    p.body-text Только администратор может создавать встречи.
  section.panel(v-else-if="dashboardQuery.isLoading.value")
    p.body-text Загружаем...
  section.panel(v-else-if="!cycleId")
    .section-header
      h1 Нет активного цикла
    p.body-text Чтобы создать встречу, начните новый цикл чтения.
  section.panel(v-else-if="dashboardQuery.data.value?.nextMeeting && !createMutation.isSuccess.value")
    .section-header
      h1 Встреча уже назначена
    p.body-text У текущего цикла уже есть встреча. Вы можете её отредактировать.
  MeetingForm(
    v-else
    :cycle-id="cycleId"
    :is-submitting="createMutation.isPending.value"
    @submit="handleSubmit"
  )
  p.body-text(v-if="createMutation.error.value") Не удалось создать встречу.
</template>
