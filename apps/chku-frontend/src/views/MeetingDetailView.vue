<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  AlertTriangle,
  BookOpen,
  CalendarDays,
  CheckCircle,
  Link as LinkIcon,
  MapPin,
  Monitor,
  Pencil,
  Play,
  Star,
  UserMinus,
  Users,
} from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import MeetingFinishModal from '@/components/meetings/MeetingFinishModal.vue'
import DiscussionBlock from '@/components/discussion/DiscussionBlock.vue'
import AppRangeInput from '@/components/ui/AppRangeInput.vue'
import SecondaryButton from '@/components/ui/SecondaryButton.vue'
import {
  useFinishMeetingMutation,
  useMeetingQuery,
  useRemoveMeetingRsvpMutation,
  useStartMeetingMutation,
  useUpdateMeetingRsvpMutation,
} from '@/queries/meetingQueries'
import { useCreateDiscussionMessageMutation } from '@/queries/discussionQueries'
import { useAuthSession } from '@/queries/authQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { useSaveRatingReviewMutation } from '@/queries/readingCycleQueries'

const route = useRoute()
const { user, isAdmin } = useAuthSession()
const { t } = useI18n()
const meetingId = computed(() => String(route.params.id ?? ''))
const meetingQuery = useMeetingQuery(
  meetingId,
  computed(() => user.value?.id),
)
const dashboardQuery = useDashboardQuery()
const updateRsvpMutation = useUpdateMeetingRsvpMutation(meetingId)
const removeRsvpMutation = useRemoveMeetingRsvpMutation(meetingId)
const startMeetingMutation = useStartMeetingMutation(meetingId)
const finishMeetingMutation = useFinishMeetingMutation(meetingId)
const saveRatingReviewMutation = useSaveRatingReviewMutation()
const discussionMutation = useCreateDiscussionMessageMutation()
const meeting = computed(() => meetingQuery.data.value)
const isArchived = computed(() => meeting.value?.status === 'finished')
const memberProgress = computed(() => dashboardQuery.data.value?.memberProgress ?? [])
const rsvpStatus = ref<'attending' | 'not_attending' | 'pending'>('pending')
const rating = ref<number | null>(null)
const review = ref('')
const ratingSubmitted = ref(false)
const isFinishModalOpen = ref(false)
const isEditingRating = ref(false)
const minMeetingAttendees = 2
const currentUserRating = computed(
  () => meeting.value?.ratings.find((item) => item.memberId === user.value?.id)?.value ?? null,
)
const currentUserReview = computed(
  () => meeting.value?.reviews.find((item) => item.memberId === user.value?.id)?.text ?? '',
)
const hasSubmittedRating = computed(() => currentUserRating.value !== null)
const shouldShowRatingForm = computed(
  () => meeting.value?.status === 'started' && rsvpStatus.value === 'attending',
)
const gridLayoutClass = computed(() => {
  const hasAdmin = isAdmin.value && !isArchived.value
  const hasRating = shouldShowRatingForm.value
  if (hasAdmin && hasRating) return 'meeting-detail__grid--layout-full'
  if (hasAdmin) return 'meeting-detail__grid--layout-no-rating'
  if (hasRating) return 'meeting-detail__grid--layout-no-admin'
  return 'meeting-detail__grid--layout-info-only'
})
const missingRatingAttendees = computed(() =>
  meeting.value
    ? meeting.value.attendees.filter((attendee) =>
        meeting.value?.missingRatingMemberIds.includes(attendee.id),
      )
    : [],
)
const missingReadingAttendees = computed(() =>
  meeting.value
    ? meeting.value.attendees.filter((attendee) =>
        meeting.value?.missingReadingMemberIds.includes(attendee.id),
      )
    : [],
)
const hasMeetingQuorum = computed(
  () => (meeting.value?.attendees.length ?? 0) >= minMeetingAttendees,
)
const isMeetingTime = computed(() => {
  if (!meeting.value?.date || !meeting.value.time) return true

  return new Date(`${meeting.value.date}T${meeting.value.time}`).getTime() <= Date.now()
})
const isCurrentUserAttending = computed(() => rsvpStatus.value === 'attending')

function canRemoveAttendee(attendee: { id: number; isAdmin?: boolean }) {
  return attendee.id !== user.value?.id && !attendee.isAdmin
}

function handleDiscussionCreate(text: string) {
  if (!meeting.value?.cycleId) return
  discussionMutation.mutate(
    { cycleNumber: meeting.value.cycleId, text },
    { onSuccess: () => meetingQuery.refetch() },
  )
}

function handleDiscussionReply(parentId: number, text: string) {
  if (!meeting.value?.cycleId) return
  discussionMutation.mutate(
    { cycleNumber: meeting.value.cycleId, text, parentId },
    { onSuccess: () => meetingQuery.refetch() },
  )
}

function removeAttendee(memberId: number) {
  removeRsvpMutation.mutate(memberId)
}

watchEffect(() => {
  rsvpStatus.value = meeting.value?.rsvpStatus ?? 'pending'
})

function setRsvp(status: 'attending' | 'not_attending') {
  updateRsvpMutation.mutate(status, {
    onSuccess: () => {
      rsvpStatus.value = status
    },
  })
}

function editRating() {
  rating.value = currentUserRating.value ?? null
  review.value = currentUserReview.value ?? ''
  isEditingRating.value = true
}

function submitRatingReview() {
  ratingSubmitted.value = true
  if (!rating.value) return

  saveRatingReviewMutation.mutate(
    {
      rating: rating.value,
      review: review.value.trim(),
    },
    {
      onSuccess: () => {
        isEditingRating.value = false
      },
    },
  )
}

function startMeeting() {
  startMeetingMutation.mutate()
}

function openFinishModal() {
  isFinishModalOpen.value = true
}

function handleFinishConfirm() {
  finishMeetingMutation.mutate(undefined, {
    onSuccess: () => {
      isFinishModalOpen.value = false
    },
  })
}
</script>

<template lang="pug">
main.meeting-detail.container
  section.panel(v-if="meetingQuery.isLoading.value" aria-live="polite")
    p.body-text {{ $t('common.loadingMeeting') }}
  section.panel.meeting-detail__missing(v-else-if="meetingQuery.error.value")
    .section-header.section-header--compact
      h1 {{ $t('meetings.notFound') }}
    p.body-text {{ $t('meetings.notFoundText') }}
    RouterLink.button.button--primary.label-text(to="/") {{ $t('meetings.backToDash') }}
  template(v-else-if="meeting")
    nav.meeting-detail__breadcrumb.label-text(:aria-label="t('meetings.breadcrumbAria')")
      RouterLink(to="/") {{ $t('nav.dashboard') }}
      span /
      span {{ meeting.cycleLabel }}
      span /
      span.meeting-detail__breadcrumb-current {{ meeting.title }}

    .section-header
      .meeting-detail__header-row
        h1.meeting-detail__title {{ meeting.title }}
      span.label-text {{ meeting.cycleLabel }}

    .meeting-detail__grid(:class="gridLayoutClass")
      .meeting-detail__main
        section.panel.meeting-detail__admin-panel(v-if="isAdmin && !isArchived")
          .meeting-detail__admin-head
            span.label-text {{ $t('meetings.adminPanel') }}
            .meeting-detail__admin-actions
              SecondaryButton(:to="`/meetings/${meeting.id}/edit`" :icon="Pencil") {{ $t('meetings.edit') }}
              template(v-if="meeting.status === 'scheduled'")
                button.button.button--primary.label-text(
                  type="button"
                  :disabled="!meeting.canStart || startMeetingMutation.isPending.value"
                  @click="startMeeting"
                ) {{ startMeetingMutation.isPending.value ? $t('meetings.starting') : $t('meetings.start') }}
              template(v-else-if="meeting.status === 'started'")
                button.button.button--primary.label-text(
                  type="button"
                  :disabled="!meeting.canFinish || finishMeetingMutation.isPending.value"
                  @click="openFinishModal"
                ) {{ finishMeetingMutation.isPending.value ? $t('meetings.finishing') : $t('meetings.finish') }}
          .meeting-detail__admin-alerts
            template(v-if="meeting.status === 'scheduled'")
              .inline-alert(v-if="!hasMeetingQuorum")
                AlertTriangle(:size="14")
                span {{ $t('meetings.needQuorum') }}
              .inline-alert(v-if="missingReadingAttendees.length")
                AlertTriangle(:size="14")
                span {{ $t('meetings.needReading') }}
                span.meeting-detail__missing-reading
                  template(v-for="(attendee, idx) in missingReadingAttendees" :key="attendee.id")
                    | {{ attendee.name }}{{ idx < missingReadingAttendees.length - 1 ? ', ' : '' }}
              .inline-alert(v-if="!isMeetingTime")
                AlertTriangle(:size="14")
                span {{ $t('meetings.notYet', { date: meeting.dateLabel, time: meeting.timeLabel }) }}
              .inline-alert.inline-alert--success(v-if="meeting.canStart")
                CheckCircle(:size="14")
                span {{ $t('meetings.readyStart') }}
              p.proposal__error(v-if="startMeetingMutation.error.value") {{ $t('meetings.startError') }}
            template(v-else-if="meeting.status === 'started'")
              .inline-alert.inline-alert--success(v-if="meeting.canFinish")
                CheckCircle(:size="14")
                span {{ $t('meetings.readyFinish') }}
              .inline-alert(v-if="missingRatingAttendees.length")
                AlertTriangle(:size="14")
                span {{ $t('meetings.needRatings') }}
                span.meeting-detail__missing-rating
                  template(v-for="(attendee, idx) in missingRatingAttendees" :key="attendee.id")
                    | {{ attendee.name }}{{ idx < missingRatingAttendees.length - 1 ? ', ' : '' }}
              p.proposal__error(v-if="finishMeetingMutation.error.value") {{ $t('meetings.finishError') }}

        form.panel.meeting-detail__rating(v-if="shouldShowRatingForm" @submit.prevent="submitRatingReview" novalidate)
          template(v-if="hasSubmittedRating && !isEditingRating")
            .section-header.section-header--compact
              h2 {{ $t('meetings.ratingReview') }}
            .meeting-detail__rating-submitted
              .meeting-detail__rating-submitted-value
                Star.meeting-detail__rating-submitted-icon(:size="20" aria-hidden="true")
                span.meeting-detail__rating-number {{ currentUserRating }}
                span.label-text {{ $t('meetings.of10') }}
              p.meeting-detail__rating-submitted-review(v-if="currentUserReview") {{ currentUserReview }}
              SecondaryButton(
                v-if="!isArchived"
                type="button"
                :icon="Pencil"
                @click="editRating"
              ) {{ $t('common.edit') }}
          template(v-else)
            .section-header.section-header--compact
              h2 {{ $t('meetings.ratingReview') }}
            .meeting-detail__rating-prompt
              Star.meeting-detail__rating-prompt-icon(:size="22" aria-hidden="true")
              .meeting-detail__rating-prompt-text
                span.label-text {{ $t('meetings.ratingAction') }}
                h2 {{ $t('meetings.rateBook') }}
                p.body-text {{ $t('meetings.rateBookText') }}
            .meeting-detail__rating-row
              label.label-text(for="meeting-rating") {{ $t('meetings.rating') }}
              .meeting-detail__rating-input-group
                AppRangeInput#meeting-rating(
                  v-model="rating"
                  :min="1"
                  :max="10"
                  :step="0.1"
                  :aria-label="t('meetings.ratingAria')"
                )
                .meeting-detail__rating-number-input
                  input.field-control(
                    v-model.number="rating"
                    type="number"
                    min="1"
                    max="10"
                    step="0.1"
                    :placeholder="t('meetings.rating')"
                    :aria-invalid="ratingSubmitted && !rating"
                  )
                  span.label-text {{ $t('meetings.of10') }}
            p.proposal__error(v-if="ratingSubmitted && !rating") {{ $t('meetings.ratingRequired') }}
            .meeting-detail__rating-row
              label.label-text(for="meeting-review") {{ $t('meetings.review') }}
              textarea#meeting-review.field-control.meeting-detail__input.meeting-detail__review(
                v-model="review"
                :placeholder="t('meetings.reviewPlaceholder')"
              )
            .meeting-detail__rating-actions
              button.button.button--primary.label-text(type="submit" :disabled="saveRatingReviewMutation.isPending.value")
                | {{ saveRatingReviewMutation.isPending.value ? $t('meetings.savingRating') : $t('meetings.saveRating') }}
              button.button.button--ghost.label-text(
                v-if="isEditingRating"
                type="button"
                @click="isEditingRating = false"
              ) {{ $t('common.cancel') }}
            p.body-text(v-if="saveRatingReviewMutation.isSuccess.value") {{ $t('meetings.ratingSaved') }}
            p.proposal__error(v-if="saveRatingReviewMutation.error.value") {{ $t('meetings.ratingError') }}

        section.panel.meeting-detail__info(aria-labelledby="meeting-info-title")
          .section-header.section-header--compact
            h2#meeting-info-title Основная информация о встрече
            span.label-text {{ meeting.status === 'finished' ? 'Завершена' : meeting.status === 'started' ? 'Идёт сейчас' : 'Запланирована' }}

          .meeting-detail__status-row(v-if="meeting.status === 'finished'")
            CheckCircle.meeting-detail__status-icon.meeting-detail__status-icon--accent
            div
              span.label-text Статус встречи
              h3.meeting-detail__status-heading Встреча завершена
          .meeting-detail__status-row(v-else-if="meeting.status === 'started'")
            Play.meeting-detail__status-icon.meeting-detail__status-icon--accent
            div
              span.label-text Статус встречи
              h3.meeting-detail__status-heading Встреча идёт сейчас
          .meeting-detail__status-row(v-else)
            CalendarDays.meeting-detail__status-icon
            div
              span.label-text Статус встречи
              h3.meeting-detail__status-heading Встреча запланирована

          .meeting-detail__info-grid
            .meeting-detail__info-item.meeting-detail__info-item--primary
              CalendarDays.meeting-detail__hero-icon
              div
                span.label-text Дата и время
                h3.meeting-detail__hero-heading {{ meeting.dateLabel }}
                p.subtitle-italic {{ meeting.timeLabel }}
            .meeting-detail__info-item
              MapPin.meeting-detail__hero-icon(v-if="!meeting.isOnline")
              Monitor.meeting-detail__hero-icon(v-else)
              div
                span.label-text {{ meeting.isOnline ? 'Онлайн' : 'Место' }}
                h3(v-if="!meeting.isOnline") {{ meeting.place }}
                p.body-text(v-if="meeting.placeAddress") {{ meeting.placeAddress }}
                p.body-text(v-if="meeting.placeReservation") {{ meeting.placeReservation }}
            .meeting-detail__info-item(v-if="meeting.meetingLink")
              LinkIcon.meeting-detail__hero-icon
              div
                span.label-text Ссылка на встречу
                a.meeting-detail__link.body-text(:href="meeting.meetingLink" target="_blank" rel="noopener noreferrer") {{ meeting.meetingLink }}
            .meeting-detail__info-item
              BookOpen.meeting-detail__hero-icon
              div
                span.label-text Книга
                h3.meeting-detail__book-title {{ meeting.book.title }}
                p.subtitle-italic {{ meeting.book.author }}

        section.panel.meeting-detail__participants(aria-labelledby="meeting-participants-title")
          .section-header.section-header--compact
            h2#meeting-participants-title {{ $t('meetings.participants') }}
            .meeting-detail__participant-count
              span.label-text {{ meeting.attendees.length }}
              Users(:size="15" aria-hidden="true")
          .meeting-detail__rsvp-status(v-if="!isArchived")
            .inline-alert.inline-alert--success(v-if="rsvpStatus === 'attending'")
              CheckCircle(:size="14" aria-hidden="true")
              span {{ $t('meetings.youAttending') }}
              button.meeting-detail__decline-text(type="button" :disabled="updateRsvpMutation.isPending.value" @click="setRsvp('not_attending')")
                | {{ $t('meetings.notAttending') }}
            .inline-alert(v-else-if="rsvpStatus === 'not_attending'")
              span {{ $t('meetings.youNotAttending') }}
            button.button.button--primary.label-text(
              v-if="!isCurrentUserAttending"
              type="button"
              :disabled="updateRsvpMutation.isPending.value"
              @click="setRsvp('attending')"
            ) {{ $t('meetings.willAttend') }}
          ul.data-list
            li.data-list__item(v-for="attendee in meeting.attendees" :key="attendee.id")
              RouterLink.meeting-detail__attendee(:to="`/members/${attendee.id}`")
                UserAvatar(:name="attendee.name" :avatar-url="attendee.avatarUrl" size="sm")
                span.body-text {{ attendee.name }}
              button.meeting-detail__remove-button(
                v-if="isAdmin && !isArchived && canRemoveAttendee(attendee)"
                type="button"
                :title="t('meetings.removeAttendee')"
                :aria-label="t('meetings.removeAttendee')"
                :disabled="removeRsvpMutation.isPending.value"
                @click="removeAttendee(attendee.id)"
              )
                UserMinus(:size="15" aria-hidden="true")
          p.body-text(v-if="!meeting.attendees.length") {{ $t('meetings.noParticipants') }}

        .meeting-detail__discussion
          DiscussionBlock(
            :discussion="meeting.discussion ?? []"
            :readonly="isArchived"
            :is-submitting="discussionMutation.isPending.value"
            @create="handleDiscussionCreate"
            @reply="handleDiscussionReply"
          )

    MeetingFinishModal(
      v-if="!isArchived"
      :is-open="isFinishModalOpen"
      :meeting="meeting"
      :member-progress="memberProgress"
      @close="isFinishModalOpen = false"
      @confirm="handleFinishConfirm"
    )

      section.panel.meeting-detail__participants(aria-labelledby="meeting-participants-title")
        .section-header.section-header--compact
          h2#meeting-participants-title {{ $t('meetings.participants') }}
          .meeting-detail__participant-count
            span.label-text {{ meeting.attendees.length }}
            Users(:size="15" aria-hidden="true")
        .meeting-detail__rsvp-status(v-if="!isArchived")
          .inline-alert.inline-alert--success(v-if="rsvpStatus === 'attending'")
            CheckCircle(:size="14" aria-hidden="true")
            span {{ $t('meetings.youAttending') }}
            button.meeting-detail__decline-text(type="button" :disabled="updateRsvpMutation.isPending.value" @click="setRsvp('not_attending')")
              | {{ $t('meetings.notAttending') }}
          .inline-alert(v-else-if="rsvpStatus === 'not_attending'")
            span {{ $t('meetings.youNotAttending') }}
          button.button.button--primary.label-text(
            v-if="!isCurrentUserAttending"
            type="button"
            :disabled="updateRsvpMutation.isPending.value"
            @click="setRsvp('attending')"
          ) {{ $t('meetings.willAttend') }}
        ul.data-list
          li.data-list__item(v-for="attendee in meeting.attendees" :key="attendee.id")
            RouterLink.meeting-detail__attendee(:to="`/members/${attendee.id}`")
              UserAvatar(:name="attendee.name" :avatar-url="attendee.avatarUrl" size="sm")
              span.body-text {{ attendee.name }}
            button.meeting-detail__remove-button(
              v-if="isAdmin && !isArchived && canRemoveAttendee(attendee)"
              type="button"
              :title="t('meetings.removeAttendee')"
              :aria-label="t('meetings.removeAttendee')"
              :disabled="removeRsvpMutation.isPending.value"
              @click="removeAttendee(attendee.id)"
            )
              UserMinus(:size="15" aria-hidden="true")
        p.body-text(v-if="!meeting.attendees.length") {{ $t('meetings.noParticipants') }}

    MeetingFinishModal(
      v-if="!isArchived"
      :is-open="isFinishModalOpen"
      :meeting="meeting"
      :member-progress="memberProgress"
      @close="isFinishModalOpen = false"
      @confirm="handleFinishConfirm"
    )

  section.panel.meeting-detail__missing(v-else)
    .section-header.section-header--compact
      h1 {{ $t('meetings.notFound') }}
    p.body-text {{ $t('meetings.notFoundText') }}
    RouterLink.button.button--primary.label-text(to="/") {{ $t('meetings.backToDash') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;
.meeting-detail__breadcrumb {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
}

.meeting-detail__breadcrumb a:hover,
.meeting-detail__breadcrumb-current {
  color: var(--text-main);
}

.meeting-detail__title {
  margin-bottom: 0;
}

.meeting-detail__header-row {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  flex-wrap: wrap;
}

.meeting-detail__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-lg);
  align-items: start;

  grid-template-areas:
    'admin'
    'rating'
    'info'
    'participants'
    'discussion';

  &--layout-no-admin {
    grid-template-areas:
      'rating'
      'info'
      'participants'
      'discussion';
  }

  &--layout-no-rating {
    grid-template-areas:
      'admin'
      'info'
      'participants'
      'discussion';
  }

  &--layout-info-only {
    grid-template-areas:
      'info'
      'participants'
      'discussion';
  }

  @include desktop {
    grid-template-columns: minmax(0, 2fr) minmax(18rem, 1fr);
    gap: var(--space-xl);

    grid-template-areas:
      'admin       participants'
      'rating      participants'
      'info        participants'
      'discussion  participants';

    &--layout-no-admin {
      grid-template-areas:
        'rating      participants'
        'info        participants'
        'discussion  participants';
    }

    &--layout-no-rating {
      grid-template-areas:
        'admin       participants'
        'info        participants'
        'discussion  participants';
    }

    &--layout-info-only {
      grid-template-areas:
        'info        participants'
        'discussion  participants';
    }
  }
}

.meeting-detail__main {
  display: contents;
}

.meeting-detail__info {
  display: grid;
  gap: var(--space-lg);
  grid-area: info;
}

.meeting-detail__status-row {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.meeting-detail__status-icon {
  flex-shrink: 0;
  width: 1.25rem;
  height: 1.25rem;
  color: var(--text-subtle);
}

.meeting-detail__status-icon--accent {
  color: var(--accent);
}

.meeting-detail__status-heading {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.meeting-detail__admin-panel {
  display: grid;
  gap: var(--space-md);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
  grid-area: admin;
}

.meeting-detail__admin-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  flex-wrap: wrap;
}

.meeting-detail__admin-actions {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: var(--space-sm);
  flex-wrap: wrap;

  @include tablet {
    flex-direction: row;
    align-items: center;
  }

  .button {
    align-self: stretch;
    width: 100%;

    @include tablet {
      align-self: auto;
      width: auto;
    }
  }
}

.meeting-detail__admin-alerts {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.meeting-detail__admin-alerts .inline-alert {
  align-items: center;
}

.meeting-detail__info-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);

  @include tablet {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.meeting-detail__info-item {
  display: flex;
  align-items: flex-start;
  gap: var(--space-md);
  min-width: 0;
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.meeting-detail__info-item--primary {
  border-color: var(--accent-border);
  background:
    linear-gradient(180deg, rgba(67, 224, 125, 0.07), rgba(67, 224, 125, 0.018)), var(--bg-panel);
}

.meeting-detail__hero-icon,
.meeting-detail__button-icon {
  flex: 0 0 auto;
  width: 1rem;
  height: 1rem;
  color: var(--text-subtle);
}

.meeting-detail__info-item--primary .meeting-detail__hero-icon {
  color: var(--accent);
}

.meeting-detail__hero-heading {
  margin-bottom: 0;
  font-size: 1.5rem;
}

.meeting-detail__link {
  display: inline-block;
  max-width: 100%;
  overflow-wrap: anywhere;
  color: var(--accent);
}

.meeting-detail__missing-rating,
.meeting-detail__missing-reading {
  color: var(--text-main);
}

.meeting-detail__topics-section {
  display: grid;
  gap: var(--space-md);
}

.meeting-detail__topics {
  display: grid;
  gap: 0;
  margin-bottom: 0;
  padding-left: 0;
  list-style: none;
}

.meeting-detail__topic {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) 0;
  border-bottom: var(--border-width) solid var(--border);
  color: var(--text-muted);
  font-size: 0.95rem;
  line-height: 1.6;
}

.meeting-detail__topic:last-child {
  border-bottom: 0;
}

.meeting-detail__topic-icon {
  flex-shrink: 0;
  color: var(--text-subtle);
}

.meeting-detail__add-topic {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  margin-top: var(--space-md);
  padding-top: var(--space-md);
  border-top: var(--border-width) solid var(--border);
}

.meeting-detail__rating {
  display: grid;
  gap: var(--space-md);
  grid-area: rating;
}

.meeting-detail__rating-row {
  display: grid;
  gap: var(--space-sm);
}

.meeting-detail__review {
  min-height: 6rem;
}

.meeting-detail__rating-prompt {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-lg);
  padding: var(--space-lg);
  border: var(--border-width) solid var(--warn-border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(216, 137, 43, 0.08), rgba(216, 137, 43, 0.018)), var(--bg-panel);

  @include tablet {
    flex-direction: row;
    align-items: center;
    padding: var(--space-lg) var(--space-xl);
  }
}

.meeting-detail__rating-prompt-icon {
  flex-shrink: 0;
  color: var(--warn);
}

.meeting-detail__rating-prompt-text {
  display: grid;
  gap: var(--space-xs);
}

.meeting-detail__rating-prompt-text h2 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.meeting-detail__rating-prompt-text p {
  margin: 0;
}

.meeting-detail__rating-submitted {
  display: grid;
  gap: var(--space-md);
}

.meeting-detail__rating-submitted-value {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.meeting-detail__rating-submitted-icon {
  color: var(--warn);
}

.meeting-detail__rating-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-main);
}

.meeting-detail__rating-submitted-review {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.6;
}

.meeting-detail__rating-actions {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: var(--space-sm);
  flex-wrap: wrap;

  @include tablet {
    flex-direction: row;
    align-items: center;
  }

  .button {
    align-self: stretch;
    width: 100%;

    @include tablet {
      align-self: auto;
      width: auto;
    }
  }
}

.meeting-detail__rating-input-group {
  display: grid;
  gap: var(--space-md);
}

.meeting-detail__rating-number-input {
  display: flex;
  align-items: center;
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: calc(var(--radius-inner) - 2px);
  background: var(--bg-surface);
}

.meeting-detail__rating-number-input input {
  flex: 1;
  min-height: 2.5rem;
  padding: 0 var(--space-sm);
  border: 0;
  border-radius: 0;
  background: transparent;
  font-variant-numeric: tabular-nums;
}

.meeting-detail__rating-number-input input::-webkit-inner-spin-button,
.meeting-detail__rating-number-input input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.meeting-detail__rating-number-input input[type='number'] {
  -moz-appearance: textfield;
}

.meeting-detail__rating-number-input span {
  display: grid;
  place-items: center;
  padding: 0 var(--space-sm);
  height: 2.5rem;
  border-left: var(--border-width) solid var(--border);
  background: rgba(255, 255, 255, 0.02);
}

.meeting-detail__rsvp-status {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.meeting-detail__rsvp-status .inline-alert {
  align-items: center;
  flex-wrap: wrap;
}

.meeting-detail__decline-text {
  margin-left: auto;
  padding: 0;
  border: 0;
  border-bottom: var(--border-width) solid transparent;
  background: transparent;
  color: var(--text-subtle);
  cursor: pointer;
  font-family: var(--font-mono);
  font-size: 0.68rem;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  transition:
    color 0.15s ease,
    border-color 0.15s ease;
}

.meeting-detail__decline-text:hover:not(:disabled) {
  border-bottom-color: var(--danger);
  color: var(--danger);
}

.meeting-detail__decline-text:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.proposal__error {
  color: var(--danger);
  font-size: 0.8rem;
  line-height: 1.4;
}

.meeting-detail__add-topic-row {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: var(--space-sm);

  @include tablet {
    flex-direction: row;
    align-items: center;
  }

  .button {
    align-self: stretch;
    width: 100%;

    @include tablet {
      align-self: auto;
      width: auto;
    }
  }
}

.meeting-detail__input {
  flex: 1;
  padding: 0.75rem 0.9rem;
}

.meeting-detail__participants {
  display: grid;
  gap: var(--space-md);
  grid-area: participants;
}

.meeting-detail__discussion {
  grid-area: discussion;
}

.meeting-detail__participant-count {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
  color: var(--text-muted);
}

.meeting-detail__attendee {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
  text-decoration: none;
  color: inherit;
}

.meeting-detail__attendee:hover {
  color: var(--accent);
}

.meeting-detail__attendee:hover .user-avatar {
  border-color: var(--accent-border);
}

.meeting-detail__remove-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.9rem;
  height: 1.9rem;
  margin-left: auto;
  border: var(--border-width) solid var(--border);
  border-radius: 50%;
  background: var(--bg-surface);
  color: var(--text-subtle);
  cursor: pointer;
}

.meeting-detail__remove-button:hover:not(:disabled) {
  border-color: var(--danger);
  color: var(--danger);
}

.meeting-detail__remove-button:disabled {
  cursor: not-allowed;
}

.meeting-detail__book-title {
  margin-bottom: var(--space-xs);
  font-size: 1.1rem;
}

.meeting-detail__missing {
  max-width: 36rem;
}

.meeting-detail__missing .button {
  margin-top: var(--space-md);
}
</style>
