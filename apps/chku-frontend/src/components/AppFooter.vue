<script setup lang="ts">
import { computed } from 'vue'
import { BookOpen, Star, Users } from '@lucide/vue'
import { useClubQuery } from '@/queries/clubQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'

const clubQuery = useClubQuery()
const dashboardQuery = useDashboardQuery()
const currentYear = computed(() => new Date().getFullYear())
const clubName = computed(() => clubQuery.data.value?.name ?? 'ЧКУ')

const coreStats = computed(() => {
  const stats = dashboardQuery.data.value?.clubStats ?? []
  return stats.slice(0, 3)
})
</script>

<template lang="pug">
footer.app-footer
  .container.app-footer__inner
    .app-footer__stats(v-if="coreStats.length")
      .app-footer__stat(
        v-for="stat in coreStats"
        :key="stat.label"
      )
        component.app-footer__stat-icon(
          :is="stat.label === 'Прочитано книг' ? BookOpen : stat.label === 'Средний рейтинг' ? Star : Users"
          :size="14"
          aria-hidden="true"
        )
        .app-footer__stat-content
          span.app-footer__stat-value {{ stat.value }}
          span.app-footer__stat-label {{ stat.label }}
    .app-footer__brand
      span.app-footer__brand-name {{ clubName }}
      span.app-footer__copy © {{ currentYear }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.app-footer {
  margin-top: var(--space-xl);
  padding-top: 2rem;
  padding-bottom: 2rem;
  border-top: var(--border-width) solid var(--border);
}

.app-footer__inner {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;

  @include desktop {
    flex-direction: row;
    align-items: center;
    gap: var(--space-lg);
  }
}

.app-footer__stats {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-sm);
  flex: 1;
  min-width: 0;
  padding-bottom: var(--space-sm);
  border-bottom: var(--border-width) solid var(--border);

  @include desktop {
    flex-direction: row;
    gap: var(--space-lg);
    padding-bottom: 0;
    border-bottom: 0;
  }
}

.app-footer__stat {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.app-footer__stat-icon {
  color: var(--warn);
  flex-shrink: 0;
}

.app-footer__stat-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.app-footer__stat-value {
  color: var(--text-main);
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1;
  font-family: var(--font-mono);
}

.app-footer__stat-label {
  color: var(--text-subtle);
  font-size: 0.72rem;
  font-family: var(--font-mono);
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.app-footer__brand {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  flex-shrink: 0;

  @include desktop {
    justify-content: flex-start;
  }
}

.app-footer__brand-name {
  color: var(--text-muted);
  font-family: var(--font-mono);
  font-size: 0.68rem;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.app-footer__copy {
  color: var(--text-subtle);
  font-family: var(--font-mono);
  font-size: 0.7rem;
}
</style>
