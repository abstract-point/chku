<script setup lang="ts">
import { BookOpen, Calendar, ListPlus, Star, Users } from '@lucide/vue'
import { RouterLink } from 'vue-router'
import DashboardBookSelectionCycle from '@/components/dashboard/DashboardBookSelectionCycle.vue'
import DashboardCurrentCycle from '@/components/dashboard/DashboardCurrentCycle.vue'
import DashboardMeetingCard from '@/components/dashboard/DashboardMeetingCard.vue'
import DashboardTurnOrderCard from '@/components/dashboard/DashboardTurnOrderCard.vue'
import { useAuthSession } from '@/queries/authQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { useCompleteCurrentCycleMutation } from '@/queries/readingCycleQueries'

const dashboardQuery = useDashboardQuery()
const { isAdmin } = useAuthSession()
const completeCycleMutation = useCompleteCurrentCycleMutation()
const statIcons = [BookOpen, Star, Users, Calendar]
</script>

<template lang="pug">
main.dashboard.container
  section.panel(v-if="dashboardQuery.isLoading.value" aria-live="polite")
    p.body-text Загружаем дашборд...
  section.panel(v-else-if="dashboardQuery.error.value" aria-live="polite")
    p.body-text Не удалось загрузить дашборд.
  template(v-else-if="dashboardQuery.data.value")
    section.choose-book-banner(v-if="dashboardQuery.data.value.lifecycle?.shouldShowChooseBookBanner")
      ListPlus.choose-book-banner__icon(:size="22" aria-hidden="true")
      .choose-book-banner__content
        span.label-text Требуется действие
        h2 Выберите книгу
        p.body-text Твоя очередь подошла, но в личной очереди нет книг для проверки.
      RouterLink.button.button--primary.label-text(to="/propose-selection") Управлять очередью
    .dashboard__grid
      DashboardBookSelectionCycle(
        v-if="dashboardQuery.data.value.activeCandidate"
        :candidate="dashboardQuery.data.value.activeCandidate"
      )
      DashboardCurrentCycle(
        v-else-if="dashboardQuery.data.value.currentBook"
        :book="dashboardQuery.data.value.currentBook"
        :members="dashboardQuery.data.value.memberProgress"
      )
      section.panel(v-else)
        .section-header.section-header--compact
          h2 Текущий цикл ещё не начат
        p.body-text Когда книга будет утверждена, она появится здесь.
      aside.dashboard__sidebar(aria-label="Сводка клуба")
        DashboardMeetingCard(v-if="dashboardQuery.data.value.nextMeeting" :meeting="dashboardQuery.data.value.nextMeeting")
        section.panel.dashboard-card(v-if="isAdmin && dashboardQuery.data.value.lifecycle?.canCompleteCycle")
          .section-header.section-header--compact
            span.label-text Завершение цикла
          p.body-text Все активные участники поставили оценки. Цикл можно отправить в архив.
          button.button.button--primary.label-text.dashboard-card__button(
            type="button"
            :disabled="completeCycleMutation.isPending.value"
            @click="completeCycleMutation.mutate()"
          ) {{ completeCycleMutation.isPending.value ? 'Завершаем...' : 'Завершить цикл' }}
        DashboardTurnOrderCard(:members="dashboardQuery.data.value.turnOrder")
        section.panel.panel--filled.dashboard-stats(aria-labelledby="club-stats-title")
          .section-header.section-header--compact
            span#club-stats-title.label-text Клубная сводка
          .dashboard-stats__grid
            .dashboard-stats__item(v-for="(stat, index) in dashboardQuery.data.value.clubStats" :key="stat.label")
              .dashboard-stats__primary
                component.dashboard-stats__icon(:is="statIcons[index % statIcons.length]" :size="26" aria-hidden="true")
                span.dashboard-stats__value {{ stat.value }}
              span.label-text {{ stat.label }}
</template>

<style scoped>
.dashboard__grid {
  display: grid;
  grid-template-columns: minmax(0, 1.55fr) minmax(21rem, 0.85fr);
  gap: var(--space-lg);
  align-items: start;
}

.dashboard__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.choose-book-banner {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  margin-bottom: var(--space-xl);
  padding: var(--space-lg) var(--space-xl);
  border: var(--border-width) solid var(--warn-border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(216, 137, 43, 0.08), rgba(216, 137, 43, 0.018)),
    var(--bg-panel);
}

.choose-book-banner__icon {
  flex: 0 0 auto;
  color: var(--warn);
}

.choose-book-banner__content {
  flex: 1;
}

.choose-book-banner h2 {
  margin: 0.2rem 0;
  font-size: 1.2rem;
}

.dashboard-stats__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0;
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
}

.dashboard-stats__item {
  display: grid;
  min-height: 7rem;
  align-content: center;
  gap: var(--space-sm);
  padding: var(--space-md);
  border-right: var(--border-width) solid var(--border);
  border-bottom: var(--border-width) solid var(--border);
}

.dashboard-stats__item:nth-child(2n) {
  border-right: 0;
}

.dashboard-stats__item:nth-last-child(-n + 2) {
  border-bottom: 0;
}

.dashboard-stats__primary {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.dashboard-stats__icon {
  color: var(--warn);
}

.dashboard-stats__value {
  color: var(--text-main);
  font-size: 1.4rem;
  font-weight: 700;
  line-height: 1;
}

@media (max-width: 960px) {
  .dashboard__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 760px) {
  .choose-book-banner {
    align-items: stretch;
    flex-direction: column;
    padding: var(--space-lg);
  }
}
</style>
