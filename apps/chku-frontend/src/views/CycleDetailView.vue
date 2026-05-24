<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, CalendarDays, MapPin, MessageSquare, Monitor, Pencil, Star } from '@lucide/vue'
import CycleBookForm from '@/components/books/CycleBookForm.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { formatDateLabel, formatTimeLabel } from '@/mappers/date'
import { useCycleQuery } from '@/queries/cycleQueries'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const cycleNumber = computed(() => String(route.params.cycleNumber ?? ''))
const cycleQuery = useCycleQuery(cycleNumber)
const cycle = computed(() => cycleQuery.data.value)
const meeting = computed(() => cycle.value?.meeting)
const isEditingBook = ref(false)

watch(
  cycle,
  (value) => {
    if (value && value.status !== 'completed') {
      router.replace('/')
    }
  },
  { immediate: true },
)

function cancelEdit() {
  isEditingBook.value = false
}
</script>

<template lang="pug">
main.cycle-detail.container
  section.panel(v-if="cycleQuery.isLoading.value" aria-live="polite")
    p.body-text {{ $t('archiveBook.loading') }}
  section.panel.cycle-detail__missing(v-else-if="cycleQuery.error.value")
    .section-header.section-header--compact
      h1 {{ $t('cycle.notFound') }}
    p.body-text {{ $t('cycle.notFoundText') }}
    RouterLink.button.button--primary.label-text(to="/archive") {{ $t('archiveBook.backToArchive') }}
  template(v-else-if="cycle")
    nav.cycle-detail__breadcrumb.label-text(:aria-label="t('archiveBook.navAria')")
      RouterLink(to="/archive") {{ $t('nav.archive') }}
      span /
      span.cycle-detail__breadcrumb-current {{ cycle.cycleLabel }}

    .cycle-detail__hero
      .book-cover.cycle-detail__cover(:style="{ backgroundColor: cycle.book.coverColor ?? undefined }" :aria-label="t('archiveBook.coverAria', { title: cycle.book.title })")
        img.cycle-detail__cover-image(v-if="cycle.book.coverUrl" :src="cycle.book.coverUrl" :alt="cycle.book.title")
        .book-cover__content.cycle-detail__cover-title(v-else) {{ cycle.coverTitle }}

      .cycle-detail__info
        .cycle-detail__heading
          span.badge.label-text(:class="cycle.status === 'completed' ? 'badge--done' : 'badge--action'") {{ cycle.statusLabel }}
          button.button.button--secondary.label-text(v-if="cycle.canEditBook && !isEditingBook" type="button" @click="isEditingBook = true")
            Pencil.cycle-detail__icon
            | {{ $t('cycle.editBook') }}
        template(v-if="isEditingBook")
          CycleBookForm(
            :cycle-number="cycle.cycleNumber"
            :book="cycle.book"
            id-prefix="cycle-book"
            @cancel="cancelEdit"
            @saved="isEditingBook = false"
          )
        template(v-else)
          h1.cycle-detail__title {{ cycle.book.title }}
          p.subtitle-italic {{ cycle.book.author }}
          .cycle-detail__meta
            .cycle-detail__meta-item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.chosenBy') }}
              .cycle-detail__member
                UserAvatar(:name="cycle.proposedBy" :avatar-url="cycle.proposerAvatarUrl" size="sm")
                span.label-text {{ cycle.proposedBy }}
            .cycle-detail__meta-item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.avgRating') }}
              span.cycle-detail__rating.label-text
                Star.cycle-detail__icon
                | {{ cycle.rating.toFixed(1) }}/10
            .cycle-detail__meta-item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.cycle') }}
              span.cycle-detail__cycle.label-text
                CalendarDays.cycle-detail__icon
                | {{ cycle.cycleLabel }}{{ cycle.completedLabel ? ` · ${cycle.completedLabel}` : '' }}
          p.body-text.cycle-detail__synopsis {{ cycle.book.description }}

    .cycle-detail__content
      section.cycle-detail__main
        section.panel(v-if="cycle.status === 'proposed' && cycle.candidate")
          .section-header
            h2 {{ $t('cycle.verification') }}
            span.label-text {{ cycle.candidate.status }}
          p.body-text(v-if="cycle.candidate.reason") {{ cycle.candidate.reason }}
          ul.data-list
            li.data-list__item(v-for="response in cycle.candidate.responses" :key="response.id")
              span.label-text {{ response.member.name }}
              span.label-text {{ response.response }}
          RouterLink.button.button--secondary.label-text(to="/") {{ $t('cycle.openDashboard') }}

        section.panel(v-if="cycle.status === 'active'")
          .section-header
            h2 {{ $t('cycle.progress') }}
            span.label-text {{ cycle.memberProgress.length }}
          ul.data-list
            li.data-list__item(v-for="progress in cycle.memberProgress" :key="progress.id")
              span.label-text {{ progress.member.name }}
              span.label-text {{ progress.progressPercent ?? 0 }}%
          RouterLink.button.button--secondary.label-text(to="/") {{ $t('cycle.openDashboard') }}

        template(v-if="cycle.status === 'completed'")
          .section-header
            h2 {{ $t('archiveBook.reviews') }}
            span.label-text {{ $t('archiveBook.reviewN', { n: cycle.reviews.length }) }}
          article.panel.cycle-detail__review(v-for="review in cycle.reviews" :key="`${review.memberName}-${review.rating}`")
            .cycle-detail__review-header
              .cycle-detail__member
                UserAvatar(:name="review.memberName" :avatar-url="review.memberAvatarUrl" size="sm")
                span.label-text {{ review.memberName }}
              span.cycle-detail__rating.label-text {{ review.rating }}/10
            p.body-text {{ review.text }}

          .section-header.cycle-detail__discussion-header
            h2 {{ $t('archiveBook.discussion') }}
            span.label-text {{ $t('archiveBook.clubMeeting') }}
          .panel.cycle-detail__prompt
            span.label-text.cycle-detail__muted {{ $t('archiveBook.mainQuestion') }}
            p.cycle-detail__prompt-text {{ cycle.discussionPrompt }}
          .cycle-detail__discussion
            article.cycle-detail__message(v-for="message in cycle.discussion" :key="`${message.memberName}-${message.dateLabel}`")
              .cycle-detail__message-header
                .cycle-detail__member
                  MessageSquare.cycle-detail__icon
                  UserAvatar(:name="message.memberName" :avatar-url="message.memberAvatarUrl" size="sm")
                  span.label-text {{ message.memberName }}
                span.label-text.cycle-detail__muted {{ message.dateLabel }}
              p.body-text {{ message.text }}

      aside.cycle-detail__sidebar(:aria-label="t('archiveBook.bookSummaryAria')")
        section.panel
          .section-header.section-header--compact
            span.label-text {{ $t('archiveBook.cycleSummary') }}
          ul.data-list
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.genre') }}
              span.badge.label-text {{ cycle.genreLabel }}
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.completed') }}
              span.label-text {{ cycle.completedLabel ?? cycle.statusLabel }}
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.meeting') }}
              span.label-text {{ meeting ? formatDateLabel(meeting.date ?? undefined) : cycle.meetingLabel }}
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.rating') }}
              span.label-text {{ cycle.rating.toFixed(1) }}/10
        section.panel.cycle-detail__meeting(v-if="meeting")
          .section-header.section-header--compact
            span.label-text {{ $t('archiveBook.meetingArchive') }}
            CalendarDays.cycle-detail__icon
          h3.cycle-detail__meeting-title {{ meeting.title }}
          ul.data-list
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.date') }}
              span.label-text {{ formatDateLabel(meeting.date ?? undefined) }} · {{ formatTimeLabel(meeting.time ?? undefined) }}
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.format') }}
              span.label-text.cycle-detail__meeting-format
                Monitor.cycle-detail__icon(v-if="meeting.isOnline")
                MapPin.cycle-detail__icon(v-else)
                | {{ meeting.isOnline ? $t('archiveBook.online') : meeting.place }}
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.participants') }}
              span.label-text {{ meeting.attendingCount }}/{{ meeting.rsvpCount }}
          RouterLink.button.button--secondary.label-text.cycle-detail__meeting-link(:to="`/meetings/${meeting.id}`")
            CalendarDays.cycle-detail__icon
            | {{ $t('archiveBook.openMeeting') }}
        RouterLink.button.button--secondary.label-text.cycle-detail__back(to="/archive")
          ArrowLeft.cycle-detail__icon
          | {{ $t('archiveBook.backBtn') }}
</template>

<style scoped>
.cycle-detail__breadcrumb,
.cycle-detail__member,
.cycle-detail__rating,
.cycle-detail__cycle,
.cycle-detail__meeting-format {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
}

.cycle-detail__breadcrumb {
  flex-wrap: wrap;
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
}

.cycle-detail__breadcrumb-current {
  color: var(--text-main);
}

.cycle-detail__hero {
  display: grid;
  grid-template-columns: 18rem minmax(0, 1fr);
  gap: var(--space-xl);
  margin-bottom: var(--space-xl);
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background: var(--bg-surface);
  box-shadow: var(--shadow-panel);
}

.cycle-detail__cover {
  position: relative;
  width: 100%;
  overflow: hidden;
}

.cycle-detail__cover-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cycle-detail__cover-title {
  font-size: 1.6rem;
  white-space: pre-line;
}

.cycle-detail__heading,
.cycle-detail__review-header,
.cycle-detail__message-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
}

.cycle-detail__title {
  margin: var(--space-md) 0 var(--space-xs);
}

.cycle-detail__meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-lg);
  margin: var(--space-md) 0;
  padding-bottom: var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
}

.cycle-detail__meta-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.cycle-detail__muted {
  color: var(--text-muted);
}

.cycle-detail__content {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(17rem, 22rem);
  gap: var(--space-xl);
}

.cycle-detail__main,
.cycle-detail__sidebar,
.cycle-detail__discussion {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.cycle-detail__review,
.cycle-detail__prompt {
  padding: var(--space-lg);
}

.cycle-detail__prompt-text {
  margin: var(--space-xs) 0 0;
  font-size: 1.1rem;
}

.cycle-detail__message {
  padding-bottom: var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
}

.cycle-detail__icon {
  width: 1rem;
  height: 1rem;
}

.cycle-detail__meeting-link,
.cycle-detail__back {
  width: 100%;
  justify-content: center;
}

@media (max-width: 900px) {
  .cycle-detail__hero,
  .cycle-detail__content {
    grid-template-columns: 1fr;
  }

  .cycle-detail__cover {
    width: min(100%, 14rem);
  }
}
</style>
