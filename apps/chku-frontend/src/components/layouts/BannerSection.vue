<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthSession } from '@/queries/authQueries'
import { useActiveCandidateQuery } from '@/queries/candidateQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { mapCandidateToChoice } from '@/mappers/candidateMapper'
import BannerChooseBook from '@/components/banners/BannerChooseBook.vue'
import BannerMeetingAdmin from '@/components/banners/BannerMeetingAdmin.vue'
import BookCandidateVerificationBanner from '@/components/dashboard/BookCandidateVerificationBanner.vue'
import TwoFactorRequiredBanner from '@/components/TwoFactorRequiredBanner.vue'

const route = useRoute()
const { isAdmin, isAuthenticated, twoFactorEnabled, user } = useAuthSession()
const dashboardQuery = useDashboardQuery()
const activeCandidateQuery = useActiveCandidateQuery({
  enabled: computed(() => isAuthenticated.value),
})

const isDashboardPage = computed(() => route.path === '/')
const isMeetingPage = computed(() => route.name === 'meeting-detail')

function isMeetingTimeReached(date?: string, time?: string): boolean {
  if (!date || !time) return false
  const meetingDate = new Date(`${date}T${time}`)
  return !isNaN(meetingDate.getTime()) && meetingDate <= new Date()
}

const activeBookChoice = computed(() => {
  const candidate = activeCandidateQuery.data.value
  if (!candidate || !Array.isArray(candidate.responses) || !user.value) return null

  if (candidate.canConfirm) {
    return mapCandidateToChoice(candidate)
  }

  const currentResponse = candidate.responses.find(
    (response) => response.member.id === user.value?.id,
  )

  if (candidate.status === 'pending' && (!currentResponse || currentResponse.response === 'pending')) {
    return mapCandidateToChoice(candidate)
  }

  return null
})

const nextMeeting = computed(() => dashboardQuery.data.value?.nextMeeting)

interface Banner {
  type: 'choose_book' | 'meeting_admin' | 'book_candidate' | 'two_factor'
}

const banners = computed<Banner[]>(() => {
  const data = dashboardQuery.data.value
  if (!data) return []

  const result: Banner[] = []

  if (data.lifecycle?.shouldShowChooseBookBanner) {
    result.push({ type: 'choose_book' })
  }

  const meeting = nextMeeting.value
  if (
    isAdmin.value &&
    meeting &&
    meeting.status !== 'finished' &&
    !isDashboardPage.value &&
    !isMeetingPage.value &&
    (meeting.status === 'started' || isMeetingTimeReached(meeting.date, meeting.time))
  ) {
    result.push({ type: 'meeting_admin' })
  }

  if (activeBookChoice.value && !isDashboardPage.value) {
    result.push({ type: 'book_candidate' })
  }

  if (isAdmin.value && !twoFactorEnabled.value) {
    result.push({ type: 'two_factor' })
  }

  return result
})
</script>

<template lang="pug">
template(v-if="banners.length")
  .banner-section.container
    BannerChooseBook(v-if="banners.some((b) => b.type === 'choose_book')")
    BannerMeetingAdmin(v-if="banners.some((b) => b.type === 'meeting_admin') && nextMeeting" :meeting="nextMeeting")
    BookCandidateVerificationBanner(v-if="banners.some((b) => b.type === 'book_candidate')" :choice="activeBookChoice")
    TwoFactorRequiredBanner(v-if="banners.some((b) => b.type === 'two_factor')")
</template>

<style scoped>
.banner-section {
  display: grid;
  gap: var(--space-lg);
  margin-bottom: var(--space-lg);
}
</style>
