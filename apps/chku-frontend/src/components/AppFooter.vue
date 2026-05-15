<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
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

const currentCycleState = computed(() => {
  const lifecycle = dashboardQuery.data.value?.lifecycle
  const state = lifecycle?.state ?? 'awaiting_next_book'
  const currentBook = dashboardQuery.data.value?.currentBook

  if (state === 'reading' && currentBook) {
    return {
      label: 'Читаем',
      value: currentBook.title,
      progress: currentBook.progressLabel ?? `${currentBook.progress}%`,
    }
  }

  if (state === 'pending' || state === 'verification') {
    return {
      label: 'Кандидат',
      value: 'На проверке',
      progress: null,
    }
  }

  return {
    label: 'Статус',
    value: 'Книга не выбрана',
    progress: null,
  }
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
      .app-footer__stat.app-footer__stat--divider
        .app-footer__stat-content
          span.app-footer__stat-label {{ currentCycleState.label }}
          span.app-footer__stat-value.app-footer__stat-value--compact {{ currentCycleState.value }}
          span.app-footer__stat-progress(v-if="currentCycleState.progress") {{ currentCycleState.progress }}
    .app-footer__brand
      span.app-footer__brand-name {{ clubName }}
      span.app-footer__copy © {{ currentYear }}
    nav.app-footer__nav(aria-label="Нижняя навигация")
      RouterLink(to="/") Дашборд
      RouterLink(to="/archive") Архив
      RouterLink(to="/profile") Профиль
</template>

<style scoped>
.app-footer {
  margin-top: var(--space-xl);
  padding-top: 1.2rem;
  padding-bottom: 1.2rem;
  border-top: var(--border-width) solid var(--border);
}

.app-footer__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  flex-wrap: wrap;
}

.app-footer__stats {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  flex-wrap: wrap;
  flex: 1;
  min-width: 0;
}

.app-footer__stat {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.app-footer__stat--divider {
  padding-left: var(--space-lg);
  margin-left: var(--space-xs);
  border-left: var(--border-width) solid var(--border);
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

.app-footer__stat-value--compact {
  font-size: 0.88rem;
  font-weight: 600;
}

.app-footer__stat-label {
  color: var(--text-subtle);
  font-size: 0.72rem;
  font-family: var(--font-mono);
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.app-footer__stat-progress {
  color: var(--text-muted);
  font-size: 0.75rem;
  font-family: var(--font-mono);
}

.app-footer__brand {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  flex-shrink: 0;
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

.app-footer__nav {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  flex-wrap: wrap;
  flex-shrink: 0;
}

.app-footer__nav a {
  color: var(--text-muted);
  font-size: 0.82rem;
  font-weight: 500;
  letter-spacing: 0;
  transition: color 0.2s ease;
  white-space: nowrap;
}

.app-footer__nav a:hover,
.app-footer__nav a.router-link-exact-active {
  color: var(--text-main);
}

@media (max-width: 960px) {
  .app-footer__inner {
    flex-direction: column;
    align-items: stretch;
    gap: var(--space-md);
  }

  .app-footer__stats {
    justify-content: center;
    padding-bottom: var(--space-sm);
    border-bottom: var(--border-width) solid var(--border);
  }

  .app-footer__brand {
    justify-content: center;
  }

  .app-footer__nav {
    justify-content: center;
    gap: var(--space-md);
  }
}

@media (max-width: 640px) {
  .app-footer__stats {
    flex-direction: column;
    align-items: center;
    gap: var(--space-sm);
  }

  .app-footer__stat--divider {
    padding-left: 0;
    margin-left: 0;
    border-left: 0;
    padding-top: var(--space-sm);
    margin-top: var(--space-xs);
    border-top: var(--border-width) solid var(--border);
    width: 100%;
    text-align: center;
  }
}
</style>
