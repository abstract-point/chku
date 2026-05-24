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
          span.label-text {{ $t('memberDetail.favGenre') }}
          span.member-detail__info-value
            Star.member-detail__icon
            span.body-text {{ member.favoriteGenre }}
        .member-detail__info-row
          span.label-text Email
          span.member-detail__info-value
            Mail.member-detail__icon
            span.body-text {{ member.email }}

    section.member-detail__history(aria-labelledby="reading-history-title")
      .section-header
        h2#reading-history-title {{ $t('memberDetail.history') }}
        span.label-text {{ $t('memberDetail.booksN', { n: member.readingHistory.length }) }}

      .panel.member-detail__book-list(v-if="member.readingHistory.length")
        component.member-detail__book(
          v-for="book in member.readingHistory"
          :key="book.title"
          :is="RouterLink"
          :to="`/cycles/${book.cycleNumber}`"
        )
          .book-cover.member-detail__book-cover(:style="{ '--cover-color': book.coverColor ?? undefined }")
            span.book-cover__content {{ book.coverTitle }}
          .member-detail__book-details
            .member-detail__book-meta
              span.label-text {{ book.cycleLabel }}
              span.label-text {{ $t('archive.completed', { label: book.completedLabel }) }}
            h3.member-detail__book-title {{ book.title }}
            p.body-text.member-detail__book-author {{ book.author }}
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

.member-detail__book-list {
  padding-top: var(--space-sm);
  padding-bottom: var(--space-sm);
}

.member-detail__book {
  display: flex;
  gap: var(--space-md);
  padding: var(--space-lg) 0;
  border-bottom: var(--border-width) solid var(--border);
  color: inherit;
  text-decoration: none;
  transition: color 0.2s ease;
}

a.member-detail__book:hover .member-detail__book-title {
  color: var(--accent-dim);
}

.member-detail__book:last-child {
  border-bottom: 0;
}

.member-detail__book-cover {
  flex: 0 0 3.75rem;
  width: 3.75rem;
  height: 5.625rem;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0.035)),
    var(--cover-color, var(--bg-panel));
  font-size: 0.65rem;
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
  margin-bottom: var(--space-sm);
  color: var(--text-muted);
}

.member-detail__book-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-sm);
  margin-top: var(--space-sm);
}

.member-detail__book-stat {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
  min-width: 0;
  padding: var(--space-sm);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  color: var(--text-muted);
}

.member-detail__empty {
  max-width: 36rem;
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

  .member-detail__book-stats {
    grid-template-columns: 1fr;
  }
}
</style>
