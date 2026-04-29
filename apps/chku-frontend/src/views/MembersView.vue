<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMembersQuery } from '@/queries/memberQueries'
import { http } from '@/api/http'

const auth = useAuthStore()
const membersQuery = useMembersQuery()
const canManageMembers = computed(() => auth.isAdmin && auth.twoFactorEnabled)

async function deactivateMember(id: number) {
  if (!confirm('Деактивировать участника?')) return
  try {
    await http.post(`/members/${id}/deactivate`)
    membersQuery.refetch()
  } catch (e: unknown) {
    alert((e as Error).message || 'Ошибка деактивации')
  }
}
</script>

<template lang="pug">
main.members.container
  .section-header
    h1.members__title Участники
    span.label-text {{ membersQuery.data.value?.length ?? 0 }} человек в клубе
    RouterLink.button.button--primary.label-text(v-if="canManageMembers" to="/members/add") Добавить участника

  section.panel.members__notice(v-if="auth.isAdmin && !auth.twoFactorEnabled")
    p.body-text Управление участниками станет доступно после включения 2FA.
    RouterLink.button.button--secondary.label-text(to="/profile/settings") Настроить 2FA

  section.panel(v-if="membersQuery.isLoading.value" aria-live="polite")
    p.body-text Загружаем участников...
  section.panel(v-else-if="membersQuery.error.value" aria-live="polite")
    p.body-text Не удалось загрузить список участников.
  .members__grid(v-else)
    RouterLink.member-card(
      v-for="member in membersQuery.data.value"
      :key="member.id"
      :class="{ 'member-card--inactive': !member.isActive }"
      :to="`/members/${member.id}`"
    )
      .member-card__hero
        span.avatar.member-card__avatar {{ member.initials }}
        .member-card__info
          h2.member-card__name {{ member.name }}
          span.badge.label-text(:class="member.isActive ? 'badge--reading' : 'badge--muted'") {{ member.isActive ? 'Активен' : 'Неактивен' }}
      .member-card__stats
        .member-card__stat
          span.member-card__stat-value {{ member.stats.read }}
          span.label-text Прочитано
        .member-card__stat
          span.member-card__stat-value {{ member.stats.proposed }}
          span.label-text Предложено
        .member-card__stat
          span.member-card__stat-value {{ member.stats.meetings }}
          span.label-text Встреч
      button.member-card__deactivate(
        v-if="canManageMembers && member.isActive && member.id !== auth.user?.id"
        type="button"
        @click.prevent="deactivateMember(member.id)"
      ) Деактивировать
</template>

<style scoped>
.members__title {
  font-size: clamp(1.8rem, 4vw, 2.2rem);
}

.members__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(17.5rem, 1fr));
  gap: var(--space-lg);
}

.members__notice {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);
}

.member-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  background: var(--bg-surface);
  color: inherit;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.member-card:hover {
  border-color: var(--border-strong);
  background: var(--bg-panel);
  transform: translateY(-0.15rem);
}

.member-card--inactive {
  border-color: var(--border);
  background: var(--bg-panel);
  color: var(--text-muted);
  opacity: 0.58;
}

.member-card--inactive:hover {
  border-color: var(--border);
  background: var(--bg-panel);
}

.member-card--inactive .member-card__stat-value {
  color: var(--text-muted);
}

.badge--muted {
  border-color: var(--border);
  background: transparent;
  color: var(--text-muted);
}

.member-card__hero {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.member-card__avatar {
  width: 3rem;
  height: 3rem;
  font-size: 1rem;
  flex-shrink: 0;
}

.member-card__info {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.member-card__name {
  font-size: 1.1rem;
  line-height: 1.3;
}

.member-card__stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-sm);
  padding-top: var(--space-md);
  border-top: var(--border-width) solid var(--border);
}

.member-card__stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-xs);
  text-align: center;
}

.member-card__stat-value {
  color: var(--text-main);
  font-size: 1.4rem;
  font-weight: 700;
  line-height: 1;
}

.member-card__deactivate {
  margin-top: var(--space-sm);
  padding: 0.35rem 0.6rem;
  border: var(--border-width) solid var(--border);
  background: transparent;
  color: var(--text-muted);
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    color 0.15s ease,
    background-color 0.15s ease;
}

.member-card__deactivate:hover {
  border-color: var(--warn);
  color: var(--warn);
  background: rgba(173, 110, 83, 0.08);
}

@media (max-width: 640px) {
  .members__notice {
    align-items: stretch;
    flex-direction: column;
  }

  .member-card__stats {
    grid-template-columns: 1fr;
    align-items: flex-start;
  }

  .member-card__stat {
    flex-direction: row;
    gap: var(--space-sm);
  }
}
</style>
