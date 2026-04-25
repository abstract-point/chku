<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { members } from '@/data/members'

const route = useRoute()
const memberId = computed(() => Number(route.params.id))
const member = computed(() => members.find((m) => m.id === memberId.value))
const activeMembersCount = computed(() => members.filter((m) => m.isActive).length)
</script>

<template lang="pug">
main.member-detail.container(v-if="member")
  .section-header
    RouterLink.button.button--ghost.label-text(to="/members") ← Назад к списку

  .member-detail__grid
    aside.member-detail__sidebar(aria-label="Профиль участника")
      .member-detail__hero
        .avatar.avatar--large {{ member.initials }}
        div
          h1.member-detail__name {{ member.name }}
          p.subtitle-italic Участник клуба с {{ member.memberSince }}

      .member-detail__stats(aria-label="Статистика участника")
        .member-detail__stat
          span.member-detail__stat-value {{ member.stats.read }}
          span.label-text Прочитано
        .member-detail__stat
          span.member-detail__stat-value {{ member.stats.proposed }}
          span.label-text Предложено
        .member-detail__stat
          span.member-detail__stat-value {{ member.stats.meetings }}
          span.label-text Встреч

      section.panel(aria-labelledby="member-info-title")
        .section-header.section-header--compact
          span#member-info-title.label-text О участнике
        .member-detail__info-row
          span.label-text Статус
          span.badge.label-text(:class="member.isActive ? 'badge--reading' : 'badge--done'") {{ member.isActive ? 'Активен' : 'Неактивен' }}
        .member-detail__info-row
          span.label-text Любимый жанр
          span.body-text {{ member.favoriteGenre }}
        .member-detail__info-row
          span.label-text Email
          span.body-text {{ member.email }}

    section.member-detail__history(aria-labelledby="reading-history-title")
      .section-header
        h2#reading-history-title История чтения
        span.label-text {{ member.readingHistory.length }} книг

      .member-detail__book-list
        article.member-detail__book(v-for="book in member.readingHistory" :key="book.title")
          .member-detail__book-cover(:class="`member-detail__book-cover--${book.coverVariant ?? 'default'}`")
            span {{ book.coverTitle }}
          .member-detail__book-details
            h3.member-detail__book-title {{ book.title }}
            p.body-text.member-detail__book-author {{ book.author }}
            .member-detail__book-meta
              span.label-text Завершено: {{ book.completedLabel }}
              span.label-text Предложил(а): {{ book.proposedBy }}

section.panel.container(v-else aria-live="polite")
  .section-header.section-header--compact
    h2 Участник не найден
  p.body-text Участника с таким идентификатором не существует.
  RouterLink.button.button--secondary.label-text(to="/members") Вернуться к списку участников
</template>

<style scoped>
.member-detail__grid {
  display: grid;
  grid-template-columns: minmax(18rem, 1fr) minmax(0, 2fr);
  gap: var(--space-xl);
}

.member-detail__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.member-detail__hero {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
}

.member-detail__name {
  font-size: clamp(2rem, 4vw, 2.5rem);
}

.member-detail__stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-md);
}

.member-detail__stat {
  display: grid;
  gap: var(--space-xs);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  background: var(--bg-surface);
  text-align: center;
}

.member-detail__stat-value {
  color: var(--text-main);
  font-size: 2.5rem;
  font-weight: 700;
  line-height: 1;
}

.member-detail__info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-sm) 0;
  border-bottom: var(--border-width) solid var(--border);
}

.member-detail__info-row:last-child {
  border-bottom: 0;
}

.member-detail__book {
  display: flex;
  gap: var(--space-md);
  padding: var(--space-md) 0;
  border-bottom: var(--border-width) solid var(--border);
}

.member-detail__book:last-child {
  border-bottom: 0;
}

.member-detail__book-cover {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 3.75rem;
  width: 3.75rem;
  height: 5.625rem;
  padding: var(--space-xs);
  overflow: hidden;
  background: var(--text-main);
  color: var(--bg-base);
  font-size: 0.65rem;
  font-weight: 600;
  line-height: 1.2;
  text-align: center;
  white-space: pre-line;
}

.member-detail__book-cover::after {
  position: absolute;
  inset: 0 0 0 10%;
  content: '';
  background: linear-gradient(
    to right,
    rgba(255, 255, 255, 0.1) 0%,
    rgba(255, 255, 255, 0) 5%,
    rgba(0, 0, 0, 0.1) 100%
  );
}

.member-detail__book-cover--accent {
  background: var(--accent);
}

.member-detail__book-cover--blue {
  background: var(--warn);
}

.member-detail__book-details {
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: center;
}

.member-detail__book-title {
  margin-bottom: var(--space-xs);
  font-size: 1.25rem;
}

.member-detail__book-author {
  margin-bottom: var(--space-sm);
}

.member-detail__book-meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  color: var(--text-muted);
}

@media (max-width: 960px) {
  .member-detail__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .member-detail__hero {
    align-items: flex-start;
    flex-direction: column;
  }

  .member-detail__stats {
    grid-template-columns: 1fr;
  }

  .member-detail__book {
    align-items: flex-start;
  }
}
</style>
