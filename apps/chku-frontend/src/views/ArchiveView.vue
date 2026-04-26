<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useArchiveQuery } from '@/queries/archiveQueries'
import type { ArchiveBookGenre } from '@/types/club'

type SortMode = 'newest' | 'oldest' | 'rating'

const searchQuery = ref('')
const selectedGenre = ref<ArchiveBookGenre | ''>('')
const selectedMember = ref('')
const sortMode = ref<SortMode>('newest')
const currentPage = ref(1)
const pageSize = 6
const archiveQuery = useArchiveQuery()
const archiveBooks = computed(() => archiveQuery.data.value ?? [])

const genreOptions = computed(() => {
  const genres = new Map<ArchiveBookGenre, string>()

  for (const book of archiveBooks.value) {
    genres.set(book.genre, book.genreLabel)
  }

  return [...genres.entries()].map(([value, label]) => ({ value, label }))
})

const memberOptions = computed(() => {
  return [...new Set(archiveBooks.value.map((book) => book.proposedBy))].sort((left, right) =>
    left.localeCompare(right, 'ru'),
  )
})

const filteredBooks = computed(() => {
  const normalizedQuery = searchQuery.value.trim().toLocaleLowerCase('ru')

  return archiveBooks.value.filter((book) => {
      const matchesQuery =
        !normalizedQuery ||
        [book.title, book.author, book.proposedBy].some((value) =>
          value.toLocaleLowerCase('ru').includes(normalizedQuery),
        )
      const matchesGenre = !selectedGenre.value || book.genre === selectedGenre.value
      const matchesMember = !selectedMember.value || book.proposedBy === selectedMember.value

      return matchesQuery && matchesGenre && matchesMember
    }).sort((left, right) => {
    if (sortMode.value === 'oldest') {
      return left.cycleNumber - right.cycleNumber
    }

    if (sortMode.value === 'rating') {
      return right.rating - left.rating
    }

    return right.cycleNumber - left.cycleNumber
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredBooks.value.length / pageSize)))

const paginatedBooks = computed(() => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = totalPages.value
  }

  const startIndex = (currentPage.value - 1) * pageSize

  return filteredBooks.value.slice(startIndex, startIndex + pageSize)
})

const pageNumbers = computed(() =>
  Array.from({ length: totalPages.value }, (_, index) => index + 1),
)

function resetFilters() {
  searchQuery.value = ''
  selectedGenre.value = ''
  selectedMember.value = ''
  sortMode.value = 'newest'
  currentPage.value = 1
}

function resetPage() {
  currentPage.value = 1
}
</script>

<template lang="pug">
main.archive.container
  .section-header
    h1.archive__title Архив
    span.label-text {{ archiveBooks.length }} книг прочитано

  .archive__controls(aria-label="Фильтры архива")
    input.archive__search(
      v-model="searchQuery"
      type="search"
      placeholder="Искать по книге, автору или участнику"
      aria-label="Поиск по архиву"
      @input="resetPage"
    )

    .archive__filters
      select.archive__select.label-text(v-model="selectedGenre" aria-label="Фильтр по жанру" @change="resetPage")
        option(value="") Все жанры
        option(v-for="genre in genreOptions" :key="genre.value" :value="genre.value") {{ genre.label }}
      select.archive__select.label-text(v-model="selectedMember" aria-label="Фильтр по участнику" @change="resetPage")
        option(value="") Все участники
        option(v-for="member in memberOptions" :key="member" :value="member") {{ member }}
      select.archive__select.label-text(v-model="sortMode" aria-label="Сортировка архива" @change="resetPage")
        option(value="newest") Сначала новые
        option(value="oldest") Сначала старые
        option(value="rating") По рейтингу

  section.panel(v-if="archiveQuery.isLoading.value" aria-live="polite")
    p.body-text Загружаем архив...
  section.panel(v-else-if="archiveQuery.error.value" aria-live="polite")
    p.body-text Не удалось загрузить архив.
  .archive__grid(v-else-if="paginatedBooks.length")
    RouterLink.archive-card(v-for="book in paginatedBooks" :key="book.slug" :to="`/archive/${book.slug}`")
      .archive-card__cover(:style="{ backgroundColor: book.coverColor }" :aria-label="`Обложка книги ${book.title}`")
        span.archive-card__cover-title {{ book.coverTitle }}
      .archive-card__info
        .archive-card__meta
          span.label-text {{ book.cycleLabel }}
          span.badge.label-text(:class="`badge--${book.genre === 'scifi' ? 'action' : book.genre === 'nonfiction' ? 'done' : 'reading'}`")
            | {{ book.genreLabel }}
        h2.archive-card__title {{ book.title }}
        p.body-text.archive-card__author {{ book.author }}
        .archive-card__footer
          .archive-card__proposer
            span.label-text.archive-card__by Выбрал(а)
            span.avatar.archive-card__avatar {{ book.proposerInitials }}
            span.label-text {{ book.proposedBy }}
          span.archive-card__rating.label-text {{ book.rating.toFixed(1) }}/10

  section.panel.archive__empty(v-else aria-live="polite")
    .section-header.section-header--compact
      h2 Ничего не найдено
    p.body-text Попробуй изменить запрос, жанр или участника.
    button.button.button--secondary.label-text(type="button" @click="resetFilters") Сбросить фильтры

  nav.archive__pagination(v-if="totalPages > 1" aria-label="Страницы архива")
    button.archive__page.label-text(
      type="button"
      :disabled="currentPage === 1"
      @click="currentPage -= 1"
    ) Назад
    button.archive__page.label-text(
      v-for="page in pageNumbers"
      :key="page"
      type="button"
      :class="{ 'archive__page--active': page === currentPage }"
      :aria-current="page === currentPage ? 'page' : undefined"
      @click="currentPage = page"
    ) {{ page }}
    button.archive__page.label-text(
      type="button"
      :disabled="currentPage === totalPages"
      @click="currentPage += 1"
    ) Вперёд
</template>

<style scoped>
.archive__title {
  font-size: clamp(1.8rem, 4vw, 2.2rem);
}

.archive__controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.archive__search,
.archive__select {
  min-height: 3rem;
  border: var(--border-width) solid var(--border);
  border-radius: 0;
  background: var(--bg-surface);
  color: var(--text-main);
  outline: none;
}

.archive__search {
  flex: 1;
  max-width: 26rem;
  padding: 0 var(--space-md);
}

.archive__search::placeholder {
  color: var(--text-muted);
}

.archive__select {
  padding: 0 var(--space-md);
  appearance: none;
}

.archive__search:focus,
.archive__select:focus {
  border-color: var(--text-main);
}

.archive__filters {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
}

.archive__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(17.5rem, 1fr));
  gap: var(--space-lg);
}

.archive-card {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  border: var(--border-width) solid var(--border);
  background: var(--bg-surface);
  color: inherit;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.archive-card:hover {
  border-color: var(--border-strong);
  background: var(--bg-panel);
  transform: translateY(-0.15rem);
}

.archive-card__cover {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 23.75rem;
  padding: var(--space-md);
  overflow: hidden;
  border-bottom: var(--border-width) solid var(--border);
  color: var(--bg-base);
  text-align: center;
}

.archive-card__cover::after {
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

.archive-card__cover-title {
  position: relative;
  z-index: 1;
  font-size: 1.35rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: pre-line;
}

.archive-card__info {
  display: flex;
  flex: 1;
  flex-direction: column;
  padding: var(--space-md);
}

.archive-card__meta,
.archive-card__footer,
.archive-card__proposer {
  display: flex;
  align-items: center;
}

.archive-card__meta {
  justify-content: space-between;
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);
}

.archive-card__title {
  margin-bottom: var(--space-xs);
  font-size: 1.2rem;
  line-height: 1.3;
}

.archive-card__author {
  margin-bottom: var(--space-md);
}

.archive-card__footer {
  justify-content: space-between;
  gap: var(--space-md);
  margin-top: auto;
  padding-top: var(--space-sm);
  border-top: var(--border-width) solid var(--border);
}

.archive-card__proposer {
  gap: var(--space-xs);
  color: var(--text-muted);
}

.archive-card__avatar {
  width: 1.25rem;
  height: 1.25rem;
  font-size: 0.45rem;
}

.archive-card__by {
  color: var(--text-muted);
}

.archive-card__rating {
  color: var(--accent-dim);
  white-space: nowrap;
}

.archive__empty {
  max-width: 34rem;
}

.archive__empty .button {
  margin-top: var(--space-md);
}

.archive__pagination {
  display: flex;
  justify-content: center;
  gap: var(--space-sm);
  margin-top: var(--space-xl);
}

.archive__page {
  min-width: 2.5rem;
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  color: var(--text-main);
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}

.archive__page:hover:not(:disabled),
.archive__page--active {
  border-color: var(--text-main);
  background: var(--text-main);
  color: var(--bg-base);
}

.archive__page:disabled {
  color: var(--text-muted);
  opacity: 0.5;
}

@media (max-width: 900px) {
  .archive__controls {
    align-items: stretch;
    flex-direction: column;
  }

  .archive__search {
    max-width: none;
  }

  .archive__filters {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .archive__filters {
    grid-template-columns: 1fr;
  }

  .archive-card__cover {
    height: 18rem;
  }

  .archive-card__footer {
    align-items: flex-start;
    flex-direction: column;
  }

  .archive__pagination {
    flex-wrap: wrap;
  }
}
</style>
