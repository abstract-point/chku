<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import MeetingForm from '@/components/meetings/MeetingForm.vue'
import { useAuthSession } from '@/queries/authQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { useCreateMeetingMutation } from '@/queries/meetingQueries'
import { useFormErrors } from '@/composables/useFormErrors'
import { ApiError } from '@/api/http'

const router = useRouter()
const { isAdmin } = useAuthSession()
const dashboardQuery = useDashboardQuery()
const createMutation = useCreateMeetingMutation()
const formErrors = useFormErrors()

const cycleId = computed(() => dashboardQuery.data.value?.lifecycle?.currentCycleId)

function handleSubmit(payload: Record<string, unknown>) {
  formErrors.clearAllErrors()
  createMutation.mutate(payload as Parameters<typeof createMutation.mutate>[0], {
    onSuccess: (meeting) => {
      router.push(`/meetings/${meeting.id}`)
    },
    onError: (error) => {
      formErrors.setFromApiError(error)
    },
  })
}
</script>

<template lang="pug">
main.container
  section.panel(v-if="!isAdmin")
    .section-header
      h1 {{ $t('meetings.accessDenied') }}
    p.body-text {{ $t('meetings.accessDeniedText') }}
  section.panel(v-else-if="dashboardQuery.isLoading.value")
    p.body-text {{ $t('common.loading') }}
  section.panel(v-else-if="!cycleId")
    .section-header
      h1 {{ $t('meetings.noCycle') }}
    p.body-text {{ $t('meetings.noCycleText') }}
  section.panel(v-else-if="dashboardQuery.data.value?.nextMeeting && !createMutation.isSuccess.value")
    .section-header
      h1 {{ $t('meetings.alreadyScheduled') }}
    p.body-text {{ $t('meetings.alreadyScheduledText') }}
  MeetingForm(
    v-else
    :cycle-id="cycleId"
    :is-submitting="createMutation.isPending.value"
    :errors="formErrors.fieldErrors.value"
    @submit="handleSubmit"
    @cancel="router.push('/')"
  )
  p.body-text(v-if="createMutation.error.value && !Object.keys(formErrors.fieldErrors.value).length")
    | {{ createMutation.error.value instanceof ApiError ? createMutation.error.value.message : $t('meetings.createError') }}
</template>
