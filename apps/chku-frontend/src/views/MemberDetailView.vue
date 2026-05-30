<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, CalendarCheck, Mail, MessageSquare, Star } from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { useMemberQuery } from '@/queries/memberQueries'
import type { ProfileBook } from '@/types/club'

const { t } = useI18n()
const route = useRoute()
const memberId = computed(() => Number(route.params.id))
const memberQuery = useMemberQuery(memberId)
const member = computed(() => memberQuery.data.value)

function ratingLabel(rating: number | null | undefined) {
  return rating ? `${rating.toFixed(1)}/10` : 'нет'
}

function rsvpLabel(book: ProfileBook) {
  if (book.attendedMeeting) return t('memberDetail.attended')
  if (book.meetingRsvpStatus === 'not_attending') return t('memberDetail.notAttended')
  if (book.meetingRsvpStatus === 'pending') return t('memberDetail.noResponse')
  return t('memberDetail.noRsvp')
}
</script>

<template lang="pug">
section.panel.container(v-if="memberQuery.isLoading.value" aria-live="polite")
  p.body-text {{ $t('memberDetail.loading') }}
section.panel.container(v-else-if="memberQuery.error.value" aria-live="polite")
  .section-header.section-header--compact
    h2 {{ $t('memberDetail.notFound') }}
  p.body-text {{ $t('memberDetail.notFoundText') }}
  RouterLink.button.button--secondary.label-text(to="/members") {{ $t('memberDetail.backToList') }}
main.member-detail.container(v-else-if="member")
  .section-header
    RouterLink.button.button--ghost.label-text(to="/members")
      ArrowLeft.member-detail__icon
      | {{ $t('memberDetail.backBtn') }}

  .member-detail__grid
    aside.member-detail__sidebar(:aria-label="t('memberDetail.profileAria')")
      .panel.member-detail__hero
        UserAvatar(:name="member.name" :avatar-url="member.avatarUrl" size="lg")
        div
          h1.member-detail__name {{ member.name }}
          p.subtitle-italic {{ $t('memberDetail.memberSince', { year: member.memberSince }) }}

      .member-detail__owls(:aria-label="t('profile.owlsAria')")
        .member-detail__owl-stat
          img.member-detail__owl-icon.member-detail__owl-icon--gold(src="/favicon.svg" :alt="t('profile.owlGold')")
          span.member-detail__stat-value {{ member.stats.goldOwls }}
        .member-detail__owl-stat
          img.member-detail__owl-icon.member-detail__owl-icon--silver(src="/favicon.svg" :alt="t('profile.owlSilver')")
          span.member-detail__stat-value {{ member.stats.silverOwls }}
        .member-detail__owl-stat
          img.member-detail__owl-icon.member-detail__owl-icon--bronze(src="/favicon.svg" :alt="t('profile.owlBronze')")
          span.member-detail__stat-value {{ member.stats.bronzeOwls }}

      .member-detail__stats(:aria-label="t('memberDetail.statsAria')")
        .member-detail__stat
          span.member-detail__stat-value {{ member.stats.read }}
          span.label-text {{ $t('memberDetail.read') }}
        .member-detail__stat
          span.member-detail__stat-value {{ member.stats.proposed }}
          span.label-text {{ $t('memberDetail.proposed') }}
        .member-detail__stat
          span.member-detail__stat-value {{ member.stats.meetings }}
          span.label-text {{ $t('memberDetail.meetings') }}

      section.panel(aria-labelledby="member-info-title")
        .section-header.section-header--compact
          span#member-info-title.label-text {{ $t('memberDetail.about') }}
        .member-detail__info-row
          span.label-text {{ $t('memberDetail.status') }}
          span.member-detail__status
            span.status-dot(:class="{ 'status-dot--active': member.isActive }")
            span.label-text {{ member.isActive ? $t('memberDetail.active') : $t('memberDetail.inactive') }}
        .member-detail__info-row
          span.label-text {{ $t('genrePicker.favGenres') }}
          span.member-detail__info-value.member-detail__genres
            template(v-if="member.favoriteGenres?.length")
              span.badge(v-for="g in member.favoriteGenres" :key="g.id") {{ g.name }}
            span.body-text(v-else) —
        .member-detail__info-row
          span.label-text Email
          span.member-detail__info-value
            Mail.member-detail__icon
            span.body-text {{ member.email }}

    section.member-detail__history(aria-labelledby="reading-history-title")
      .section-header
        h2#reading-history-title {{ $t('memberDetail.history') }}
        span.label-text {{ $t('memberDetail.booksN', { n: member.readingHistory.length }) }}

      .member-detail__book-list(v-if="member.readingHistory.length")
        component.member-detail__book(
          v-for="book in member.readingHistory"
          :key="book.title"
          :is="RouterLink"
          :to="`/cycles/${book.cycleNumber}`"
        )
          .book-cover.member-detail__book-cover(:style="{ '--cover-color': book.coverColor ?? undefined }" :aria-label="$t('archive.coverAria', { title: book.title })")
            img.member-detail__book-cover-image(v-if="book.coverUrl" :src="book.coverUrl" :alt="book.title")
            span.book-cover__content(v-else) {{ book.coverTitle }}
          .member-detail__book-details
            .member-detail__book-meta
              span.label-text {{ book.cycleLabel }}
              span.label-text {{ $t('archive.completed', { label: book.completedLabel }) }}
            h3.member-detail__book-title {{ book.title }}
            p.body-text.member-detail__book-author {{ book.author }}
            .member-detail__book-genres(v-if="book.genres?.length")
              span.badge(v-for="g in book.genres" :key="g.id") {{ g.name }}
            p.body-text.member-detail__book-description(v-if="book.description") {{ book.description }}
          .member-detail__book-stats(:aria-label="t('memberDetail.statsAria')")
            span.member-detail__book-stat.label-text
              Star.member-detail__icon
              | {{ $t('memberDetail.rating', { rating: ratingLabel(book.myRating) }) }}
            span.member-detail__book-stat.label-text
              Star.member-detail__icon
              | {{ $t('memberDetail.average', { rating: ratingLabel(book.clubAverageRating) }) }}
            span.member-detail__book-stat.label-text
              MessageSquare.member-detail__icon
              | {{ $t('memberDetail.reviewLabel', { has: book.hasReview ? $t('memberDetail.reviewYes') : $t('memberDetail.reviewNo') }) }}
            span.member-detail__book-stat.label-text
              CalendarCheck.member-detail__icon
              | {{ $t('memberDetail.meetingLabel', { status: rsvpLabel(book) }) }}

      section.panel.member-detail__empty(v-else aria-live="polite")
        p.body-text {{ $t('memberDetail.emptyHistory') }}

section.panel.container(v-else aria-live="polite")
  .section-header.section-header--compact
    h2 {{ $t('memberDetail.notFound') }}
  p.body-text {{ $t('memberDetail.notFoundText') }}
  RouterLink.button.button--secondary.label-text(to="/members") {{ $t('memberDetail.backToList') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.member-detail__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);

  @include desktop {
    grid-template-columns: minmax(18rem, 1fr) minmax(0, 2fr);
  }
}

.member-detail__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.member-detail__hero {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-lg);

  @include tablet {
    flex-direction: row;
    align-items: center;
  }
}

.member-detail__name {
  font-size: clamp(2rem, 4vw, 2.5rem);
}

.member-detail__owls {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-md);
}

.member-detail__owl-stat {
  display: grid;
  gap: var(--space-xs);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  text-align: center;
}

.member-detail__owl-icon {
  width: 2rem;
  height: 2rem;
  margin: 0 auto;
}

.member-detail__owl-icon--gold {
  filter: invert(78%) sepia(35%) saturate(800%) hue-rotate(355deg) brightness(95%) contrast(90%);
}

.member-detail__owl-icon--silver {
  filter: invert(82%) sepia(8%) saturate(200%) hue-rotate(170deg) brightness(95%);
}

.member-detail__owl-icon--bronze {
  filter: invert(68%) sepia(40%) saturate(600%) hue-rotate(345deg) brightness(90%);
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
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
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
  padding: var(--space-md) 0;
  border-bottom: var(--border-width) solid var(--border);
}

.member-detail__info-row:last-child {
  border-bottom: 0;
}

.member-detail__status,
.member-detail__info-value {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--space-sm);
  text-align: right;
}

.member-detail__icon {
  width: 1rem;
  height: 1rem;
  color: var(--text-subtle);
}

.member-detail__genres {
  flex-wrap: wrap;
  gap: var(--space-xs);
}

.member-detail__book-list {
  display: grid;
  gap: var(--space-md);
}

.member-detail__book {
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

.member-detail__book:hover {
  border-color: var(--border-strong);
  transform: translateY(-2px);
  box-shadow:
    var(--shadow-panel),
    0 0.5rem 2rem rgba(0, 0, 0, 0.18);
}

.member-detail__book-cover {
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

.member-detail__book-cover-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-detail__book-details {
  grid-area: header;
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  min-width: 0;
}

.member-detail__book-title {
  font-size: 1.1rem;
  margin: 0;
  overflow-wrap: break-word;

  @include tablet {
    font-size: 1.25rem;
  }
}

.member-detail__book-author {
  margin: 0;
  font-size: 0.85rem;
  color: var(--text-muted);
}

.member-detail__book-description {
  margin: 0;
  font-size: 0.82rem;
  color: var(--text-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.member-detail__book-genres {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-xs) var(--space-sm);
  margin-top: var(--space-xs);
}

.member-detail__book-genres .badge {
  font-size: 0.6rem;
  text-transform: none;
  letter-spacing: 0;
  padding: 0.15rem 0.45rem;
}

.member-detail__book-meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  color: var(--text-muted);
  font-size: 0.75rem;
}

.member-detail__book-stats {
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

.member-detail__book-stat {
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

.member-detail__empty {
  max-width: 36rem;
}
</style>
