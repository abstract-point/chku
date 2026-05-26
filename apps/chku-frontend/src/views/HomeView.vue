<script setup lang="ts">
import DashboardBookSelectionCycle from '@/components/dashboard/DashboardBookSelectionCycle.vue'
import DashboardCurrentCycle from '@/components/dashboard/DashboardCurrentCycle.vue'
import DashboardMeetingSection from '@/components/dashboard/DashboardMeetingSection.vue'
import DashboardTurnOrderCard from '@/components/dashboard/DashboardTurnOrderCard.vue'
import { useI18n } from 'vue-i18n'
import { useAuthSession } from '@/queries/authQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'

const { t } = useI18n()

const dashboardQuery = useDashboardQuery()
const { isAdmin } = useAuthSession()
</script>

<template lang="pug">
main.dashboard.container
  section.panel(v-if="dashboardQuery.isLoading.value" aria-live="polite")
    p.body-text {{ $t('common.loadingDash') }}
  section.panel(v-else-if="dashboardQuery.error.value" aria-live="polite")
    p.body-text {{ $t('common.errorDash') }}
  template(v-else-if="dashboardQuery.data.value")
    .dashboard__grid
      DashboardBookSelectionCycle(
        v-if="dashboardQuery.data.value.activeCandidate"
        :candidate="dashboardQuery.data.value.activeCandidate"
        :cycle-number="dashboardQuery.data.value.lifecycle?.currentCycleNumber"
      )
      DashboardCurrentCycle(
        v-else-if="dashboardQuery.data.value.currentBook"
        :book="dashboardQuery.data.value.currentBook"
        :members="dashboardQuery.data.value.memberProgress"
      )
      section.panel(v-else)
        .section-header.section-header--compact
          h2 {{ $t('dash.notStartedTitle') }}
        p.body-text {{ $t('dash.notStartedText') }}
      aside.dashboard__sidebar(aria-label="Сводка клуба")
        DashboardMeetingSection(
          :meeting="dashboardQuery.data.value.nextMeeting"
          :current-cycle-status="dashboardQuery.data.value.lifecycle?.currentCycleStatus"
          :is-admin="isAdmin"
        )
        DashboardTurnOrderCard(
          :members="dashboardQuery.data.value.turnOrder"
          :cycle-status="dashboardQuery.data.value.lifecycle?.currentCycleStatus"
        )
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.dashboard__grid {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);

  @include desktop {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(21rem, 0.85fr);
    gap: var(--space-lg);
    align-items: start;
  }
}

.dashboard__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}
</style>
