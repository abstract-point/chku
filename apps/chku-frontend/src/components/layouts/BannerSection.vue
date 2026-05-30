<script setup lang="ts">
import { computed, type Component } from 'vue'
import { useRoute, type RouteRecordName } from 'vue-router'
import { useAuthSession } from '@/queries/authQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import BannerMeetingAdmin from '@/components/banners/BannerMeetingAdmin.vue'
import TwoFactorRequiredBanner from '@/components/TwoFactorRequiredBanner.vue'
import BannerNextAction from '@/components/banners/BannerNextAction.vue'

const route = useRoute()
const { isAdmin, isAuthenticated, twoFactorEnabled } = useAuthSession()
const dashboardQuery = useDashboardQuery()

function isMeetingTimeReached(date?: string, time?: string): boolean {
  if (!date || !time) return false
  const meetingDate = new Date(`${date}T${time}`)
  return !isNaN(meetingDate.getTime()) && meetingDate <= new Date()
}

const nextMeeting = computed(() => dashboardQuery.data.value?.nextMeeting)
const nextAction = computed(() => dashboardQuery.data.value?.nextAction)

function actionHideOn(): RouteRecordName[] | undefined {
  if (!nextAction.value) return undefined

  if (['respond_candidate', 'confirm_candidate'].includes(nextAction.value.type)) {
    return ['home']
  }

  if (['rsvp_meeting', 'rate_book', 'write_review'].includes(nextAction.value.type)) {
    return ['meeting-detail']
  }

  return undefined
}

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
    id: 'next_action',
    component: BannerNextAction,
    condition: () =>
      isAuthenticated.value && !!nextAction.value && nextAction.value.type !== 'none',
    props: () => ({ action: nextAction.value }),
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
    id: 'two_factor',
    component: TwoFactorRequiredBanner,
    condition: () => isAdmin.value && !twoFactorEnabled.value,
  },
]

const visibleBanners = computed(() => {
  const currentRouteName = route.name

  return bannerConfigs.filter((config) => {
    if (!config.condition()) return false

    if (config.id === 'next_action' && currentRouteName) {
      const hiddenRoutes = actionHideOn()
      if (hiddenRoutes?.includes(currentRouteName)) return false
    }

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
