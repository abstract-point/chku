<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { BookOpen, Settings, Sparkles } from '@lucide/vue'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { useCurrentUserQuery } from '@/queries/memberQueries'

const currentUserQuery = useCurrentUserQuery()
const dashboardQuery = useDashboardQuery()
const currentMember = computed(() => currentUserQuery.data.value)
const currentMemberFirstName = computed(() => currentMember.value?.name.split(' ')[0] ?? '')
const canProposeNextBook = computed(() =>
  dashboardQuery.data.value?.turnOrder.some(
    (member) =>
      member.active &&
      currentMemberFirstName.value &&
      member.name.includes(currentMemberFirstName.value),
  ),
)
</script>

<template lang="pug">
main.profile.container
  section.panel(v-if="currentUserQuery.isLoading.value" aria-live="polite")
    p.body-text Загружаем профиль...
  section.panel(v-else-if="currentUserQuery.error.value || !currentMember" aria-live="polite")
    p.body-text Не удалось загрузить профиль.
  .profile__grid(v-else)
    aside.profile__sidebar(aria-label="Профиль участника")
      .panel.profile__hero
        .avatar.avatar--large {{ currentMember.initials }}
        div
          h1.profile__name {{ currentMember.name }}
          p.subtitle-italic Участник клуба с {{ currentMember.memberSince }}

      .profile__stats(aria-label="Статистика участника")
        .profile__stat
          span.profile__stat-value {{ currentMember.stats.read }}
          span.label-text Прочитано
        .profile__stat
          span.profile__stat-value {{ currentMember.stats.proposed }}
          span.label-text Предложено
        .profile__stat
          span.profile__stat-value {{ currentMember.stats.meetings }}
          span.label-text Встреч

      section.panel.profile__turn(v-if="canProposeNextBook" aria-labelledby="profile-turn-title")
        .section-header.section-header--compact
          span#profile-turn-title.label-text Сейчас твоя очередь
          Sparkles.profile__section-icon
        p.body-text
          | Предложи следующую книгу для клуба. После отправки участники подтвердят, что ещё не читали её.
        RouterLink.button.button--primary.label-text.profile__turn-action(to="/propose-selection")
          | Предложить книгу

      section.panel(aria-labelledby="profile-settings-title")
        .section-header.section-header--compact
          span#profile-settings-title.label-text Настройки профиля
          Settings.profile__section-icon
        p.body-text
          | Имя, инициалы, любимый жанр, пароль и двухфакторная защита настраиваются на отдельной странице.
        RouterLink.button.button--secondary.label-text.profile__save(to="/profile/settings") Открыть настройки

    section.profile__history(aria-labelledby="reading-history-title")
      .section-header
        h2#reading-history-title История чтения
        span.label-text Цикл #28 - сейчас

      .panel.profile__book-list
        article.profile__book(v-for="book in currentMember.readingHistory" :key="book.title")
          .book-cover.profile__book-cover(:class="`profile__book-cover--${book.coverVariant ?? 'default'}`")
            span.book-cover__content {{ book.coverTitle }}
          .profile__book-details
            h3.profile__book-title {{ book.title }}
            p.body-text.profile__book-author {{ book.author }}
            .profile__book-meta
              span.label-text Завершено: {{ book.completedLabel }}
              span.label-text Предложил(а): {{ book.proposedBy }}

      RouterLink.button.button--ghost.label-text.profile__archive-link(to="/archive")
        BookOpen.profile__archive-icon
        | Смотреть весь архив
</template>

<style scoped>
.profile__grid {
  display: grid;
  grid-template-columns: minmax(18rem, 1fr) minmax(0, 2fr);
  gap: var(--space-xl);
}

.profile__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.profile__hero {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
}

.profile__name {
  font-size: clamp(2rem, 4vw, 2.5rem);
}

.profile__stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-md);
}

.profile__stat {
  display: grid;
  gap: var(--space-xs);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  text-align: center;
}

.profile__stat-value {
  color: var(--text-main);
  font-size: 2.5rem;
  font-weight: 700;
  line-height: 1;
}

.profile__turn {
  border-color: var(--warn-border);
  background:
    linear-gradient(180deg, rgba(216, 137, 43, 0.06), rgba(216, 137, 43, 0.018)),
    var(--bg-surface);
}

.profile__turn-action {
  width: 100%;
  margin-top: var(--space-md);
}

.profile__section-icon,
.profile__archive-icon {
  width: 1rem;
  height: 1rem;
  color: var(--text-subtle);
}

.profile__save {
  width: 100%;
  margin-top: var(--space-sm);
}

.profile__book-list {
  padding-top: var(--space-sm);
  padding-bottom: var(--space-sm);
}

.profile__book {
  display: flex;
  gap: var(--space-md);
  padding: var(--space-lg) 0;
  border-bottom: var(--border-width) solid var(--border);
}

.profile__book:last-child {
  border-bottom: 0;
}

.profile__book-cover {
  flex: 0 0 3.75rem;
  width: 3.75rem;
  height: 5.625rem;
  font-size: 0.65rem;
}

.profile__book-cover--accent {
  background:
    linear-gradient(135deg, rgba(67, 224, 125, 0.16), rgba(255, 255, 255, 0.02)),
    var(--bg-panel);
  border-color: var(--accent-border);
}

.profile__book-cover--blue {
  background:
    linear-gradient(135deg, rgba(216, 137, 43, 0.18), rgba(255, 255, 255, 0.02)),
    var(--bg-panel);
  border-color: var(--warn-border);
}

.profile__book-details {
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: center;
}

.profile__book-title {
  margin-bottom: var(--space-xs);
  font-size: 1.25rem;
}

.profile__book-author {
  margin-bottom: var(--space-sm);
}

.profile__book-meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  color: var(--text-muted);
}

.profile__archive-link {
  margin-top: var(--space-md);
}

@media (max-width: 960px) {
  .profile__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .profile__hero {
    align-items: flex-start;
    flex-direction: column;
  }

  .profile__stats {
    grid-template-columns: 1fr;
  }

  .profile__book {
    align-items: flex-start;
  }
}
</style>
