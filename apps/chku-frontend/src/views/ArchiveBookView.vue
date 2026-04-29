<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { ArrowLeft, CalendarDays, MessageSquare, Star, UserRound } from '@lucide/vue'
import { useArchiveBookQuery } from '@/queries/archiveQueries'

const route = useRoute()
const slug = computed(() => String(route.params.slug ?? ''))
const bookQuery = useArchiveBookQuery(slug)
const book = computed(() => bookQuery.data.value)
</script>

<template lang="pug">
main.archive-book.container
  section.panel(v-if="bookQuery.isLoading.value" aria-live="polite")
    p.body-text Загружаем книгу...
  section.panel.archive-book__missing(v-else-if="bookQuery.error.value")
    .section-header.section-header--compact
      h1 Книга не найдена
    p.body-text Возможно, ссылка устарела или книга ещё не добавлена в архив.
    RouterLink.button.button--primary.label-text(to="/archive") Вернуться в архив
  template(v-else-if="book")
    nav.archive-book__breadcrumb.label-text(aria-label="Навигация по архиву")
      RouterLink(to="/archive") Архив
      span /
      span {{ book.cycleLabel }}
      span /
      span.archive-book__breadcrumb-current {{ book.title }}

    .archive-book__hero
      .book-cover.archive-book__cover(:style="{ backgroundColor: book.coverColor }" :aria-label="`Обложка книги ${book.title}`")
        .book-cover__content.archive-book__cover-title {{ book.coverTitle }}

      .archive-book__info
        h1.archive-book__title {{ book.title }}
        p.subtitle-italic {{ book.author }}

        .archive-book__meta
          .archive-book__meta-item
            span.label-text.archive-book__muted Выбрал(а)
            .archive-book__member
              UserRound.archive-book__icon
              span.avatar.archive-book__avatar {{ book.proposerInitials }}
              span.label-text {{ book.proposedBy }}
          .archive-book__meta-item
            span.label-text.archive-book__muted Средняя оценка
            span.archive-book__rating.label-text
              Star.archive-book__icon
              | {{ book.rating.toFixed(1) }}/10
          .archive-book__meta-item
            span.label-text.archive-book__muted Цикл
            span.archive-book__cycle.label-text
              CalendarDays.archive-book__icon
              | {{ book.cycleLabel }} · {{ book.completedLabel }}

        .section-header.section-header--compact
          h2 Синопсис
        p.body-text.archive-book__synopsis {{ book.synopsis }}

    .archive-book__content
      section.archive-book__main(aria-labelledby="archive-book-reviews")
        .section-header
          h2#archive-book-reviews Отзывы клуба
          span.label-text {{ book.reviews.length }} отзыва

        article.panel.archive-book__review(v-for="review in book.reviews" :key="`${review.memberName}-${review.rating}`")
          .archive-book__review-header
            .archive-book__member
              span.avatar {{ review.memberInitials }}
              span.label-text {{ review.memberName }}
            span.archive-book__rating.label-text {{ review.rating }}/10
          p.body-text {{ review.text }}

        .section-header.archive-book__discussion-header
          h2 Обсуждение
          span.label-text Встреча клуба

        .panel.archive-book__prompt
          span.label-text.archive-book__muted Главный вопрос
          p.archive-book__prompt-text {{ book.discussionPrompt }}

        .archive-book__discussion
          article.archive-book__message(v-for="message in book.discussion" :key="`${message.memberName}-${message.dateLabel}`")
            .archive-book__message-header
              .archive-book__member
                MessageSquare.archive-book__icon
                span.avatar {{ message.memberInitials }}
                span.label-text {{ message.memberName }}
              span.label-text.archive-book__muted {{ message.dateLabel }}
            p.body-text {{ message.text }}

      aside.archive-book__sidebar(aria-label="Сводка книги")
        section.panel
          .section-header.section-header--compact
            span.label-text Сводка цикла
          ul.data-list
            li.data-list__item
              span.label-text.archive-book__muted Жанр
              span.badge.label-text(:class="`badge--${book.genre === 'scifi' ? 'action' : book.genre === 'nonfiction' ? 'done' : 'reading'}`")
                | {{ book.genreLabel }}
            li.data-list__item
              span.label-text.archive-book__muted Завершено
              span.label-text {{ book.completedLabel }}
            li.data-list__item
              span.label-text.archive-book__muted Встреча
              span.label-text {{ book.meetingLabel }}
            li.data-list__item
              span.label-text.archive-book__muted Оценка
              span.label-text {{ book.rating.toFixed(1) }}/10
            li.data-list__item
              span.label-text.archive-book__muted Отзывов
              span.label-text {{ book.reviews.length }}

        RouterLink.button.button--secondary.label-text.archive-book__back(to="/archive")
          ArrowLeft.archive-book__icon
          | Вернуться в архив

  section.panel.archive-book__missing(v-else)
    .section-header.section-header--compact
      h1 Книга не найдена
    p.body-text Возможно, ссылка устарела или книга ещё не добавлена в архив.
    RouterLink.button.button--primary.label-text(to="/archive") Вернуться в архив
</template>

<style scoped>
.archive-book__breadcrumb {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
}

.archive-book__breadcrumb a:hover,
.archive-book__breadcrumb-current {
  color: var(--text-main);
}

.archive-book__hero {
  display: grid;
  grid-template-columns: 20rem minmax(0, 1fr);
  gap: var(--space-xl);
  margin-bottom: var(--space-xl);
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.014)),
    var(--bg-surface);
  box-shadow: var(--shadow-panel);
}

.archive-book__cover {
  width: 100%;
}

.archive-book__cover-title {
  font-size: 2rem;
  white-space: pre-line;
}

.archive-book__info {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

.archive-book__title {
  margin-bottom: var(--space-xs);
}

.archive-book__meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-lg);
  margin-top: var(--space-md);
  margin-bottom: var(--space-lg);
  padding-bottom: var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
}

.archive-book__meta-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.archive-book__member {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
}

.archive-book__icon {
  flex: 0 0 auto;
  width: 1rem;
  height: 1rem;
  color: var(--text-subtle);
}

.archive-book__avatar {
  width: 1.5rem;
  height: 1.5rem;
  font-size: 0.5rem;
}

.archive-book__muted {
  color: var(--text-muted);
}

.archive-book__rating {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
  color: var(--accent-dim);
}

.archive-book__rating .archive-book__icon {
  color: var(--accent);
}

.archive-book__cycle {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
}

.archive-book__synopsis {
  max-width: 48rem;
}

.archive-book__content {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(18rem, 1fr);
  gap: var(--space-xl);
}

.archive-book__review {
  margin-bottom: var(--space-md);
  padding: var(--space-lg);
}

.archive-book__review:last-of-type {
  margin-bottom: 0;
}

.archive-book__review-header,
.archive-book__message-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
}

.archive-book__discussion-header {
  margin-top: var(--space-xl);
}

.archive-book__prompt {
  margin-bottom: var(--space-md);
}

.archive-book__prompt-text {
  margin-top: var(--space-sm);
  color: var(--text-main);
  font-size: 1.1rem;
  font-weight: 600;
  line-height: 1.35;
}

.archive-book__discussion {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.archive-book__message {
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.archive-book__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.archive-book__back {
  width: 100%;
}

.archive-book__missing {
  max-width: 36rem;
}

.archive-book__missing .button {
  margin-top: var(--space-md);
}

@media (max-width: 960px) {
  .archive-book__hero,
  .archive-book__content {
    grid-template-columns: 1fr;
  }

  .archive-book__cover {
    max-width: 20rem;
  }
}

@media (max-width: 640px) {
  .archive-book__meta,
  .archive-book__review-header,
  .archive-book__message-header {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
