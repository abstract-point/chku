<script setup lang="ts">
import { computed, type Component } from 'vue'
import { useRoute, type RouteRecordName } from 'vue-router'
import { useAuthSession } from '@/queries/authQueries'
import { useActiveCandidateQuery } from '@/queries/candidateQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { mapCandidateToChoice } from '@/mappers/candidateMapper'
import BannerChooseBook from '@/components/banners/BannerChooseBook.vue'
import BannerMeetingAdmin from '@/components/banners/BannerMeetingAdmin.vue'
import BookCandidateVerificationBanner from '@/components/dashboard/BookCandidateVerificationBanner.vue'
import TwoFactorRequiredBanner from '@/components/TwoFactorRequiredBanner.vue'
import BannerMeetingRating from '@/components/banners/BannerMeetingRating.vue'

const route = useRoute()
const { isAdmin, isAuthenticated, twoFactorEnabled, user } = useAuthSession()
const dashboardQuery = useDashboardQuery()
const activeCandidateQuery = useActiveCandidateQuery({
  enabled: computed(() => isAuthenticated.value),
})

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

  if (
    candidate.status === 'pending' &&
    (!currentResponse || currentResponse.response === 'pending')
  ) {
    return mapCandidateToChoice(candidate)
  }

  return null
})

const nextMeeting = computed(() => dashboardQuery.data.value?.nextMeeting)

interface BannerConfig {
  id: string
  component: Component
  condition: () => boolean
  showOn?: RouteRecordName[]
  hideOn?: RouteRecordName[]
  props?: () => Record<string, unknown>
}

const bannerConfigs: BannerConfig[] = [
  {
    id: 'choose_book',
    component: BannerChooseBook,
    condition: () => !!dashboardQuery.data.value?.lifecycle?.shouldShowChooseBookBanner,
  },
  {
    id: 'meeting_admin',
    component: BannerMeetingAdmin,
    condition: () =>
      isAdmin.value &&
      !!nextMeeting.value &&
      nextMeeting.value.status !== 'finished' &&
      (nextMeeting.value.status === 'started' ||
        isMeetingTimeReached(nextMeeting.value.date, nextMeeting.value.time)),
    hideOn: ['meeting-detail'],
    props: () => ({ meeting: nextMeeting.value }),
  },
  {
    id: 'book_candidate',
    component: BookCandidateVerificationBanner,
    condition: () => !!activeBookChoice.value,
    hideOn: ['home'],
    props: () => ({ choice: activeBookChoice.value }),
  },
  {
    id: 'meeting_rating',
    component: BannerMeetingRating,
    condition: () => {
      const data = dashboardQuery.data.value
      const meeting = nextMeeting.value
      const missingRatings = data?.lifecycle?.missingRatings ?? []
      return (
        !!meeting &&
        meeting.status === 'started' &&
        !!user.value &&
        missingRatings.some((m) => m.id === user.value!.id)
      )
    },
    hideOn: ['meeting-detail'],
    props: () => ({ meetingId: nextMeeting.value?.id }),
  },
  {
    id: 'two_factor',
    component: TwoFactorRequiredBanner,
    condition: () => isAdmin.value && !twoFactorEnabled.value,
  },
]

const visibleBanners = computed(() => {
  const currentRouteName = route.name

  return bannerConfigs.filter((config) => {
    if (!config.condition()) return false

    if (config.showOn && currentRouteName) {
      if (!config.showOn.includes(currentRouteName)) return false
    }

    if (config.hideOn && currentRouteName) {
      if (config.hideOn.includes(currentRouteName)) return false
    }

    return true
  })
})
</script>

<template lang="pug">
template(v-if="visibleBanners.length")
  .banner-section.container
    component(
      v-for="banner in visibleBanners"
      :key="banner.id"
      :is="banner.component"
      v-bind="banner.props ? banner.props() : undefined"
    )
</template>

<style scoped>
.banner-section {
  display: grid;
  gap: var(--space-lg);
  margin-bottom: var(--space-lg);
}
</style>
