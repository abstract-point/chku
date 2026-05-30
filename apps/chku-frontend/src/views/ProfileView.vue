<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  BookOpen,
  CalendarCheck,
  ListOrdered,
  MessageSquare,
  Settings,
  Sparkles,
  Star,
} from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import GenrePicker from '@/components/GenrePicker.vue'
import { useBookQueueQuery } from '@/queries/bookQueueQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { useCurrentUserQuery } from '@/queries/memberQueries'
import { useCurrentUserReadingHistoryQuery } from '@/queries/profileQueries'
import { useUpdateProfileMutation } from '@/queries/authQueries'
import { useGenresQuery } from '@/queries/genreQueries'
import type { ProfileBook } from '@/types/club'

const { t } = useI18n()
const currentUserQuery = useCurrentUserQuery()
const dashboardQuery = useDashboardQuery()
const bookQueueQuery = useBookQueueQuery()
const readingHistoryQuery = useCurrentUserReadingHistoryQuery()
const updateProfileMutation = useUpdateProfileMutation()
const genresQuery = useGenresQuery()

const currentMember = computed(() => currentUserQuery.data.value)
const readingHistory = computed(() => readingHistoryQuery.data.value ?? [])
const nextQueueBook = computed(
  () =>
    bookQueueQuery.items.value.find((item) => item.isCurrentCandidate) ??
    bookQueueQuery.items.value.find((item) => item.status === 'queued') ??
    null,
)
const nextQueueLabel = computed(() =>
  nextQueueBook.value?.isCurrentCandidate ? t('profile.inProposal') : t('profile.autoProposal'),
)
const currentMemberFirstName = computed(() => currentMember.value?.name.split(' ')[0] ?? '')
const canProposeNextBook = computed(() =>
  dashboardQuery.data.value?.turnOrder.some(
    (member) =>
      member.active &&
      currentMemberFirstName.value &&
      member.name.includes(currentMemberFirstName.value),
  ),
)

const favoriteGenreIds = computed({
  get: () => currentMember.value?.favoriteGenres?.map((g) => g.id) ?? [],
  set: async (ids: number[]) => {
    if (!currentMember.value) return
    try {
      await updateProfileMutation.mutateAsync({
        name: currentMember.value.name,
        email: currentMember.value.email,
        favorite_genre_ids: ids,
      })
    } catch {
      // mutation error handled by UI
    }
  },
})

function ratingLabel(rating: number | null | undefined) {
  return rating ? `${rating.toFixed(1)}/10` : t('profile.ratingNone')
}

function rsvpLabel(book: ProfileBook) {
  if (book.attendedMeeting) return t('profile.attended')
  if (book.meetingRsvpStatus === 'not_attending') return t('profile.notAttended')
  if (book.meetingRsvpStatus === 'pending') return t('profile.noResponse')
  return t('profile.noRsvp')
}
</script>

<template lang="pug">
main.profile.container
  section.panel(v-if="currentUserQuery.isLoading.value" aria-live="polite")
    p.body-text {{ $t('profile.loading') }}
  section.panel(v-else-if="currentUserQuery.error.value || !currentMember" aria-live="polite")
    p.body-text {{ $t('profile.error') }}
  .profile__grid(v-else)
    aside.profile__sidebar(:aria-label="t('profile.profileAria')")
      .panel.profile__hero
        UserAvatar(:name="currentMember.name" :avatar-url="currentMember.avatarUrl" size="lg")
        div
          h1.profile__name {{ currentMember.name }}
          p.subtitle-italic {{ $t('profile.memberSince', { year: currentMember.memberSince }) }}

      .panel.profile__genres
        GenrePicker(
          v-model="favoriteGenreIds"
          :genres="genresQuery.data.value ?? []"
          :label="$t('genrePicker.favGenres')"
          :disabled="updateProfileMutation.isPending.value"
          :error="updateProfileMutation.error.value?.message"
        )

      .profile__owls(:aria-label="t('profile.owlsAria')")
        .profile__owl-stat
          img.profile__owl-icon.profile__owl-icon--gold(src="/favicon.svg" :alt="t('profile.owlGold')")
          span.profile__stat-value {{ currentMember.stats.goldOwls }}
        .profile__owl-stat
          img.profile__owl-icon.profile__owl-icon--silver(src="/favicon.svg" :alt="t('profile.owlSilver')")
          span.profile__stat-value {{ currentMember.stats.silverOwls }}
        .profile__owl-stat
          img.profile__owl-icon.profile__owl-icon--bronze(src="/favicon.svg" :alt="t('profile.owlBronze')")
          span.profile__stat-value {{ currentMember.stats.bronzeOwls }}

      .profile__stats(:aria-label="t('profile.statsAria')")
        .profile__stat
          span.profile__stat-value {{ currentMember.stats.read }}
          span.label-text {{ $t('profile.read') }}
        .profile__stat
          span.profile__stat-value {{ currentMember.stats.proposed }}
          span.label-text {{ $t('profile.proposed') }}
        .profile__stat
          span.profile__stat-value {{ currentMember.stats.meetings }}
          span.label-text {{ $t('profile.meetings') }}

      section.panel.profile__queue(v-if="!canProposeNextBook" aria-labelledby="profile-queue-title")
        .section-header.section-header--compact
          span#profile-queue-title.label-text {{ $t('profile.myQueue') }}
          ListOrdered.profile__section-icon
        p.body-text
          | {{ $t('profile.queueDesc') }}
        .profile__next-queue(aria-live="polite")
          p.label-text(v-if="bookQueueQuery.isLoading.value") {{ $t('profile.queueLoading') }}
          p.label-text(v-else-if="bookQueueQuery.error.value") {{ $t('profile.queueError') }}
          p.label-text(v-else-if="!nextQueueBook") {{ $t('profile.queueEmpty') }}
          article.profile__next-book(v-else)
            span.profile__next-book-label.label-text {{ nextQueueLabel }}
            strong {{ nextQueueBook.title }}
            small {{ nextQueueBook.author }}
        RouterLink.button.button--primary.label-text.profile__turn-action(to="/propose-selection")
          | {{ $t('profile.openQueue') }}

      section.panel.profile__turn(v-if="canProposeNextBook" aria-labelledby="profile-turn-title")
        .section-header.section-header--compact
          span#profile-turn-title.label-text {{ $t('profile.yourTurn') }}
          Sparkles.profile__section-icon
        p.body-text
          | {{ $t('profile.turnDesc') }}
        .profile__next-queue(aria-live="polite")
          p.label-text(v-if="bookQueueQuery.isLoading.value") {{ $t('profile.queueLoading') }}
          p.label-text(v-else-if="bookQueueQuery.error.value") {{ $t('profile.queueError') }}
          p.label-text(v-else-if="!nextQueueBook") {{ $t('profile.queueEmpty') }}
          article.profile__next-book(v-else)
            span.profile__next-book-label.label-text {{ nextQueueLabel }}
            strong {{ nextQueueBook.title }}
            small {{ nextQueueBook.author }}
        RouterLink.button.button--primary.label-text.profile__turn-action(to="/propose-selection")
          | {{ $t('profile.manageQueue') }}

      section.panel(aria-labelledby="profile-settings-title")
        .section-header.section-header--compact
          span#profile-settings-title.label-text {{ $t('profile.settings') }}
          Settings.profile__section-icon
        p.body-text
          | {{ $t('profile.settingsDesc') }}
        RouterLink.button.button--secondary.label-text.profile__save(to="/profile/settings") {{ $t('profile.openSettings') }}

    section.profile__history(aria-labelledby="reading-history-title")
      .section-header
        h2#reading-history-title {{ $t('profile.history') }}
        span.label-text {{ $t('profile.cycleCurrent', { n: 28 }) }}

      section.panel(v-if="readingHistoryQuery.isLoading.value" aria-live="polite")
        p.body-text {{ $t('profile.historyLoading') }}
      section.panel(v-else-if="readingHistoryQuery.error.value" aria-live="polite")
        p.body-text {{ $t('profile.historyError') }}
      .profile__book-list(v-else-if="readingHistory.length")
        TransitionGroup(name="list" tag="div")
          component.profile__book(
            v-for="book in readingHistory"
            :key="`${book.cycleNumber}-${book.title}`"
            :is="RouterLink"
            :to="`/cycles/${book.cycleNumber}`"
          )
            .book-cover.profile__book-cover(:style="{ '--cover-color': book.coverColor ?? undefined }" :aria-label="$t('archive.coverAria', { title: book.title })")
              img.profile__book-cover-image(v-if="book.coverUrl" :src="book.coverUrl" :alt="book.title")
              span.book-cover__content(v-else) {{ book.coverTitle }}
            .profile__book-details
              .profile__book-meta
                span.label-text {{ book.cycleLabel }}
                span.label-text {{ $t('profile.completed', { label: book.completedLabel }) }}
              h3.profile__book-title {{ book.title }}
              p.body-text.profile__book-author {{ book.author }}
              .profile__book-genres(v-if="book.genres?.length")
                span.badge(v-for="g in book.genres" :key="g.id") {{ g.name }}
              p.body-text.profile__book-description(v-if="book.description") {{ book.description }}
            .profile__book-stats(:aria-label="t('profile.cycleStatsAria')")
              span.profile__book-stat.label-text
                Star.profile__archive-icon
                | {{ $t('profile.myRating', { rating: ratingLabel(book.myRating) }) }}
              span.profile__book-stat.label-text
                Star.profile__archive-icon
                | {{ $t('profile.avgRating', { rating: ratingLabel(book.clubAverageRating) }) }}
              span.profile__book-stat.label-text
                MessageSquare.profile__archive-icon
                | {{ $t('profile.reviewLabel', { has: book.hasReview ? $t('profile.reviewYes') : $t('profile.reviewNo') }) }}
              span.profile__book-stat.label-text
                CalendarCheck.profile__archive-icon
                | {{ $t('profile.meetingLabel', { status: rsvpLabel(book) }) }}

      section.panel.profile__empty(v-else aria-live="polite")
        p.body-text {{ $t('profile.emptyHistory') }}

      RouterLink.button.button--ghost.label-text.profile__archive-link(to="/archive")
        BookOpen.profile__archive-icon
        | {{ $t('profile.viewArchive') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.profile__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);

  @include desktop {
    grid-template-columns: minmax(18rem, 1fr) minmax(0, 2fr);
  }
}

.profile__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.profile__hero {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-lg);

  @include tablet {
    flex-direction: row;
    align-items: center;
  }
}

.profile__name {
  font-size: clamp(2rem, 4vw, 2.5rem);
}

.profile__genres {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  padding-top: var(--space-sm);
  padding-bottom: var(--space-sm);
  border-style: dashed;
}

.profile__owls {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-md);
}

.profile__owl-stat {
  display: grid;
  gap: var(--space-xs);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  text-align: center;
}

.profile__owl-icon {
  width: 2rem;
  height: 2rem;
  margin: 0 auto;
}

.profile__owl-icon--gold {
  filter: invert(78%) sepia(35%) saturate(800%) hue-rotate(355deg) brightness(95%) contrast(90%);
}

.profile__owl-icon--silver {
  filter: invert(82%) sepia(8%) saturate(200%) hue-rotate(170deg) brightness(95%);
}

.profile__owl-icon--bronze {
  filter: invert(68%) sepia(40%) saturate(600%) hue-rotate(345deg) brightness(90%);
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
    linear-gradient(180deg, rgba(216, 137, 43, 0.06), rgba(216, 137, 43, 0.018)), var(--bg-surface);
}

.profile__queue {
  border-color: var(--accent-border);
}

.profile__turn-action {
  width: 100%;
  margin-top: var(--space-md);
}

.profile__next-queue {
  margin-top: var(--space-md);
}

.profile__next-book {
  display: grid;
  gap: var(--space-xs);
  padding: var(--space-md);
  border: var(--border-width) solid var(--accent-border);
  border-radius: var(--radius-inner);
  background:
    linear-gradient(180deg, rgba(67, 224, 125, 0.055), rgba(67, 224, 125, 0.018)), var(--bg-panel);
}

.profile__next-book-label {
  color: var(--accent-dim);
}

.profile__next-book strong {
  overflow: hidden;
  color: var(--text-main);
  font-size: 1rem;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile__next-book small {
  overflow: hidden;
  color: var(--text-muted);
  font-size: 0.84rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile__next-book .body-text {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
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
  display: grid;
  gap: var(--space-md);
}

.profile__book-list > div {
  display: grid;
  gap: var(--space-md);
}

.profile__book {
  display: grid;
  grid-template-columns: auto 1fr;
  grid-template-areas:
    "cover header"
    "stats stats";
  gap: var(--space-sm) var(--space-md);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.026), rgba(255, 255, 255, 0.01)),
    var(--bg-panel);
  color: inherit;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    transform 0.2s ease,
    box-shadow 0.2s ease;

  @include tablet {
    padding: var(--space-lg);
    gap: var(--space-md) var(--space-lg);
  }
}

.profile__book:hover {
  border-color: var(--border-strong);
  transform: translateY(-2px);
  box-shadow:
    var(--shadow-panel),
    0 0.5rem 2rem rgba(0, 0, 0, 0.18);
}

.profile__book-cover {
  grid-area: cover;
  align-self: start;
  width: 4rem;
  height: 6rem;
  flex: 0 0 4rem;
  background:
    radial-gradient(circle at 62% 22%, rgba(255, 255, 255, 0.16), transparent 0.9rem),
    linear-gradient(135deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.035)),
    var(--cover-color);
  box-shadow: inset 0.8rem 0 1.3rem rgba(0, 0, 0, 0.18);
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.55rem;

  @include tablet {
    width: 5rem;
    height: 7.5rem;
    flex: 0 0 5rem;
    font-size: 0.65rem;
  }
}

.profile__book-cover-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile__book-details {
  grid-area: header;
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  min-width: 0;
}

.profile__book-title {
  font-size: 1.1rem;
  margin: 0;
  overflow-wrap: break-word;

  @include tablet {
    font-size: 1.25rem;
  }
}

.profile__book-author {
  margin: 0;
  font-size: 0.85rem;
  color: var(--text-muted);
}

.profile__book-description {
  margin: 0;
  font-size: 0.82rem;
  color: var(--text-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.profile__book-genres {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-xs) var(--space-sm);
  margin-top: var(--space-xs);
}

.profile__book-genres .badge {
  font-size: 0.6rem;
  text-transform: none;
  letter-spacing: 0;
  padding: 0.15rem 0.45rem;
}

.profile__book-meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  color: var(--text-muted);
  font-size: 0.75rem;
}

.profile__book-stats {
  grid-area: stats;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-xs);
  padding-top: var(--space-sm);
  margin-top: var(--space-sm);
  border-top: var(--border-width) solid var(--border);

  @include tablet {
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-sm);
  }
}

.profile__book-stat {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-xs);
  padding: var(--space-xs) var(--space-sm);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
  color: var(--text-muted);
  font-size: 0.6rem;
  white-space: nowrap;

  @include tablet {
    padding: var(--space-sm);
    font-size: 0.65rem;
  }
}

.profile__empty {
  max-width: 36rem;
}

.profile__archive-link {
  margin-top: var(--space-md);
}
</style>
