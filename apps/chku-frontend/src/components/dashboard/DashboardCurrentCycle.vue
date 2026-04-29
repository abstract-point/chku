<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { BookProgressMember, CurrentBook } from '@/types/dashboard'

const props = defineProps<{
  book: CurrentBook
  members: BookProgressMember[]
}>()

const coverTitleLines = computed(() => props.book.coverTitle.split('\n'))
</script>

<template lang="pug">
section.dashboard__main(aria-labelledby="current-cycle-title")
  .section-header
    h2#current-cycle-title Текущий цикл
    span.label-text Цикл #42 • Завершение 15 окт

  article.current-book
    .book-cover.current-book__cover(:aria-label="`Обложка книги ${book.title}`")
      .book-cover__content
        span.current-book__cover-label.label-text Выбрал {{ book.selectedBy }}
        template(v-for="line in coverTitleLines" :key="line")
          | {{ line }}
          br

    .current-book__details
      .current-book__meta
        h1 {{ book.title }}
        p.subtitle-italic {{ book.author }}
      p.body-text.current-book__description
        | {{ book.description }}

      .panel.panel--filled.current-book__progress
        .current-book__progress-header
          span.label-text Мой прогресс
          span.label-text {{ book.progressLabel }}
        .progress(:aria-label="`Мой прогресс чтения ${book.progress}%`")
          .progress__bar(:style="{ '--progress-value': `${book.progress}%` }")
        button.button.button--secondary.label-text(type="button") Обновить прогресс

  .section-header.dashboard__section-spaced
    h3 Прогресс клуба
    span.label-text 4 из 6 участников активны

  ul.data-list.club-progress(role="list")
    li.data-list__item.club-progress__item(v-for="member in members" :key="member.name")
      .member-status
        span.avatar {{ member.initials }}
        span.member-status__name {{ member.name }}
      span.label-text(v-if="member.status") {{ member.status }}
      .member-status__progress(v-else-if="member.progress")
        .progress.member-status__progress-track(:aria-label="`${member.name}: ${member.progress}%`")
          .progress__bar(:style="{ '--progress-value': `${member.progress}%` }")
        span.label-text {{ member.progress }}%
      span.badge.badge--reading.label-text(v-else) {{ member.badge }}
  RouterLink.button.button--ghost.label-text.club-progress__link(to="/members") Все участники клуба
</template>

<style scoped>
.dashboard__main {
  padding: var(--space-xl);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.012)),
    var(--bg-surface);
  box-shadow: var(--shadow-panel);
}

.dashboard__main {
  min-width: 0;
}

.dashboard__section-spaced {
  margin-top: var(--space-xl);
}

.current-book {
  display: grid;
  grid-template-columns: minmax(11rem, 14rem) minmax(0, 1fr);
  gap: clamp(var(--space-lg), 4vw, var(--space-xl));
  margin-bottom: var(--space-xl);
}

.current-book__cover {
  width: 100%;
}

.current-book__cover-label {
  display: block;
  margin-bottom: var(--space-sm);
  font-size: 0.5rem;
  opacity: 0.7;
}

.current-book__details {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.current-book__meta h1 {
  font-size: clamp(2.4rem, 5vw, 4.25rem);
  line-height: 1;
}

.current-book__meta {
  margin-bottom: var(--space-md);
}

.current-book__description {
  max-width: 36rem;
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
  font-size: 1rem;
}

.current-book__progress {
  padding: var(--space-md);
  border-radius: var(--radius-inner);
}

.current-book__progress-header {
  display: flex;
  justify-content: space-between;
  gap: var(--space-md);
}

.current-book__progress .button {
  width: 100%;
  margin-top: var(--space-sm);
}

.club-progress {
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
}

.club-progress__item {
  min-height: 3.4rem;
  padding: 0.7rem var(--space-md);
}

.club-progress__link {
  width: 100%;
  margin-top: var(--space-md);
}

.member-status {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.member-status__name {
  color: var(--text-main);
  font-size: 0.82rem;
}

.member-status__progress {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  width: 9.5rem;
}

.member-status__progress-track {
  flex: 1;
  margin: 0;
}

@media (max-width: 760px) {
  .dashboard__main {
    padding: var(--space-lg);
  }

  .current-book {
    grid-template-columns: 1fr;
  }

  .current-book__cover {
    width: min(100%, 13rem);
  }

  .current-book__progress-header {
    flex-direction: column;
    gap: var(--space-xs);
  }

  .member-status__progress {
    width: 100%;
  }
}
</style>
