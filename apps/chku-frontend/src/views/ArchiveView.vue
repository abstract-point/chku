<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { CalendarCheck, ChevronDown, MessageSquare, Search, Star } from '@lucide/vue'
import { useArchiveQuery } from '@/queries/archiveQueries'
import type { ArchiveBookGenre } from '@/types/club'

type SortMode = 'newest' | 'oldest' | 'rating'

const searchQuery = ref('')
const selectedGenre = ref<ArchiveBookGenre | ''>('')
const selectedMember = ref('')
const { t } = useI18n()
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

  return archiveBooks.value
    .filter((book) => {
      const matchesQuery =
        !normalizedQuery ||
        [book.title, book.author, book.proposedBy].some((value) =>
          value.toLocaleLowerCase('ru').includes(normalizedQuery),
        )
      const matchesGenre = !selectedGenre.value || book.genre === selectedGenre.value
      const matchesMember = !selectedMember.value || book.proposedBy === selectedMember.value

      return matchesQuery && matchesGenre && matchesMember
    })
    .sort((left, right) => {
      if (sortMode.value === 'oldest') {
        return left.cycleNumber - right.cycleNumber
      }

      if (sortMode.value === 'rating') {
        return (right.averageRating ?? right.rating) - (left.averageRating ?? left.rating)
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

function getGenreBadgeClass(genre: ArchiveBookGenre) {
  if (genre === 'scifi') return 'badge--action'
  if (genre === 'nonfiction') return 'badge--done'
  return 'badge--reading'
}

function ratingLabel(rating: number | undefined) {
  return typeof rating === 'number' ? `${rating.toFixed(1)}/10` : t('profile.ratingNone')
}
</script>

<template lang="pug">
main.archive.container
  .archive__header
    h1.archive__title {{ $t('archive.title') }}
    span.archive__count
      strong {{ archiveBooks.length }}
      |  {{ $t('archive.cycles', { n: archiveBooks.length }).replace(/^\d+\s*/, '') }}

  .archive__controls(:aria-label="t('archive.filtersAria')")
    label.archive__search
      Search.archive__search-icon(:size="18" aria-hidden="true")
      input.field-control(
        v-model="searchQuery"
        type="search"
        :placeholder="t('archive.searchPlaceholder')"
        :aria-label="t('archive.searchAria')"
        @input="resetPage"
      )

    .archive__filters
      label.archive__select-wrap
        select.archive__select.field-control(v-model="selectedGenre" :aria-label="t('archive.genreAria')" @change="resetPage")
          option(value="") {{ $t('archive.allGenres') }}
          option(v-for="genre in genreOptions" :key="genre.value" :value="genre.value") {{ genre.label }}
        ChevronDown(:size="16" aria-hidden="true")
      label.archive__select-wrap
        select.archive__select.field-control(v-model="selectedMember" :aria-label="t('archive.memberAria')" @change="resetPage")
          option(value="") {{ $t('archive.allMembers') }}
          option(v-for="member in memberOptions" :key="member" :value="member") {{ member }}
        ChevronDown(:size="16" aria-hidden="true")
      label.archive__select-wrap
        select.archive__select.field-control(v-model="sortMode" :aria-label="t('archive.sortAria')" @change="resetPage")
          option(value="newest") {{ $t('archive.sortNewest') }}
          option(value="oldest") {{ $t('archive.sortOldest') }}
          option(value="rating") {{ $t('archive.sortRating') }}
        ChevronDown(:size="16" aria-hidden="true")

  section.panel(v-if="archiveQuery.isLoading.value" aria-live="polite")
    p.body-text {{ $t('common.loadingArchive') }}
  section.panel(v-else-if="archiveQuery.error.value" aria-live="polite")
    p.body-text {{ $t('common.errorArchive') }}
  TransitionGroup.archive__grid(name="list" tag="div" v-else-if="paginatedBooks.length")
    RouterLink.archive-card(v-for="book in paginatedBooks" :key="book.slug" :to="`/archive/${book.slug}`")
      .archive-card__cover(:style="{ '--cover-color': book.coverColor }" :aria-label="t('archive.coverAria', { title: book.title })")
        span.archive-card__cover-title {{ book.coverTitle }}
      .archive-card__info
        .archive-card__meta
          span.label-text {{ book.cycleLabel }}
          span.badge.label-text(:class="getGenreBadgeClass(book.genre)")
            | {{ book.genreLabel }}
        h2.archive-card__title {{ book.title }}
        p.body-text.archive-card__author {{ book.author }}
        .archive-card__details
          span.label-text {{ $t('archive.completed', { label: book.completedLabel }) }}
          span.label-text {{ $t('archive.proposedBy', { name: book.proposedBy }) }}
        .archive-card__stats(:aria-label="t('archive.statsAria')")
          span.archive-card__stat.label-text
            Star(:size="15" aria-hidden="true")
            span {{ ratingLabel(book.averageRating ?? book.rating) }}
          span.archive-card__stat.label-text
            Star(:size="15" aria-hidden="true")
            span {{ $t('archive.ratingsN', { n: book.ratingsCount ?? book.reviews.length }) }}
          span.archive-card__stat.label-text
            MessageSquare(:size="15" aria-hidden="true")
            span {{ $t('archive.reviewsN', { n: book.reviewsCount ?? book.reviews.length }) }}
          span.archive-card__stat.label-text
            CalendarCheck(:size="15" aria-hidden="true")
            span {{ book.attendingCount ?? 0 }}/{{ book.rsvpCount ?? 0 }} RSVP

  section.panel.archive__empty(v-else aria-live="polite")
    .section-header.section-header--compact
      h2 {{ $t('archive.noResults') }}
    p.body-text {{ $t('archive.noResultsText') }}
    button.button.button--secondary.label-text(type="button" @click="resetFilters") {{ $t('archive.resetFilters') }}

  nav.archive__pagination(v-if="totalPages > 1" :aria-label="t('archive.paginationAria')")
    button.archive__page.label-text(
      type="button"
      :disabled="currentPage === 1"
      @click="currentPage -= 1"
    ) {{ $t('archive.back') }}
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
    ) {{ $t('archive.forward') }}
</template>

<style scoped>
.archive__title {
  font-size: clamp(2.4rem, 5vw, 4rem);
}

.archive__header {
  display: flex;
  align-items: baseline;
  gap: var(--space-lg);
  margin-bottom: var(--space-lg);
}

.archive__count {
  color: var(--text-muted);
  font-family: var(--font-mono);
  font-size: 0.82rem;
}

.archive__count strong {
  color: var(--accent);
  font-weight: 700;
}

.archive__controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.archive__search {
  position: relative;
  flex: 1;
  max-width: 31rem;
}

.archive__search input {
  width: 100%;
  padding: 0 1rem 0 2.8rem;
}

.archive__search-icon {
  position: absolute;
  z-index: 1;
  top: 50%;
  left: 0.95rem;
  color: var(--text-subtle);
  transform: translateY(-50%);
}

.archive__select-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.archive__select-wrap svg {
  position: absolute;
  right: 0.85rem;
  color: var(--text-subtle);
  pointer-events: none;
}

.archive__select {
  min-width: 11rem;
  padding: 0 2.4rem 0 var(--space-md);
  appearance: none;
  font-size: 0.9rem;
}

.archive__filters {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
}

.archive__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-lg);
}

.archive-card {
  display: flex;
  align-items: stretch;
  gap: var(--space-lg);
  min-height: 100%;
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.014)),
    var(--bg-surface);
  box-shadow: var(--shadow-panel);
  color: inherit;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.archive-card:hover {
  border-color: var(--border-strong);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.045), rgba(255, 255, 255, 0.018)),
    var(--bg-panel);
  transform: translateY(-2px);
}

.archive-card__cover {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 10rem;
  align-self: flex-start;
  aspect-ratio: 9 / 13;
  padding: var(--space-md);
  overflow: hidden;
  border: var(--border-width) solid rgba(255, 255, 255, 0.14);
  border-radius: var(--radius-inner);
  background:
    radial-gradient(circle at 62% 22%, rgba(255, 255, 255, 0.16), transparent 0.9rem),
    linear-gradient(135deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.035)),
    var(--cover-color);
  box-shadow: inset 0.8rem 0 1.3rem rgba(0, 0, 0, 0.18);
  color: rgba(255, 255, 255, 0.72);
  text-align: center;
}

.archive-card__cover::after {
  position: absolute;
  inset: 0 0 0 10%;
  content: '';
  background: linear-gradient(
    to right,
    rgba(255, 255, 255, 0.11) 0%,
    rgba(255, 255, 255, 0) 5%,
    rgba(0, 0, 0, 0.1) 100%
  );
}

.archive-card__cover-title {
  position: relative;
  z-index: 1;
  max-width: 7rem;
  font-family: var(--font-mono);
  font-size: 0.74rem;
  font-weight: 500;
  letter-spacing: 0.12em;
  line-height: 1.55;
  text-transform: uppercase;
  white-space: pre-line;
}

.archive-card__info {
  display: flex;
  flex: 1;
  flex-direction: column;
  padding: var(--space-xs) 0;
}

.archive-card__meta {
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
  font-size: 1.35rem;
  line-height: 1.3;
}

.archive-card__author {
  margin-bottom: var(--space-md);
}

.archive-card__details {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm) var(--space-md);
  margin-bottom: var(--space-md);
  color: var(--text-muted);
}

.archive-card__stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-sm);
  margin-top: auto;
  padding-top: var(--space-md);
  border-top: var(--border-width) solid var(--border);
}

.archive-card__stat {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
  min-width: 0;
  color: var(--text-muted);
  white-space: nowrap;
  font-size: 0.78rem;
}

.archive-card__stat:first-child {
  color: var(--accent-dim);
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
  border-radius: var(--radius-inner);
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
  color: var(--text-inverse);
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

  .archive__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .archive__header {
    align-items: flex-start;
    flex-direction: column;
    gap: var(--space-sm);
  }

  .archive__filters {
    grid-template-columns: 1fr;
  }

  .archive__grid {
    grid-template-columns: 1fr;
  }

  .archive-card {
    flex-direction: column;
  }

  .archive-card__cover {
    width: min(100%, 12rem);
    flex-basis: auto;
  }

  .archive__pagination {
    flex-wrap: wrap;
  }
}
</style>
