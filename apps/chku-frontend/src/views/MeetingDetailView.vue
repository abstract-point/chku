<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  AlertTriangle,
  BookOpen,
  CalendarDays,
  CheckCircle,
  Link as LinkIcon,
  MapPin,
  MessageSquare,
  Monitor,
  Pencil,
  Play,
  Send,
  UserMinus,
  Users,
  X,
} from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import MeetingFinishModal from '@/components/meetings/MeetingFinishModal.vue'
import {
  useAddMeetingTopicMutation,
  useFinishMeetingMutation,
  useMeetingQuery,
  useRemoveMeetingRsvpMutation,
  useStartMeetingMutation,
  useUpdateMeetingRsvpMutation,
} from '@/queries/meetingQueries'
import { useAuthSession } from '@/queries/authQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import { useSaveRatingReviewMutation } from '@/queries/readingCycleQueries'

const route = useRoute()
const { user, isAdmin } = useAuthSession()
const meetingId = computed(() => String(route.params.id ?? ''))
const meetingQuery = useMeetingQuery(meetingId, computed(() => user.value?.id))
const dashboardQuery = useDashboardQuery()
const updateRsvpMutation = useUpdateMeetingRsvpMutation(meetingId)
const removeRsvpMutation = useRemoveMeetingRsvpMutation(meetingId)
const addTopicMutation = useAddMeetingTopicMutation(meetingId)
const startMeetingMutation = useStartMeetingMutation(meetingId)
const finishMeetingMutation = useFinishMeetingMutation(meetingId)
const saveRatingReviewMutation = useSaveRatingReviewMutation()
const meeting = computed(() => meetingQuery.data.value)
const isArchived = computed(() => meeting.value?.status === 'finished')
const memberProgress = computed(() => dashboardQuery.data.value?.memberProgress ?? [])
const newTopic = ref('')
const rsvpStatus = ref<'attending' | 'not_attending' | 'pending'>('pending')
const localTopics = ref<string[]>([])
const rating = ref<number | null>(null)
const review = ref('')
const ratingSubmitted = ref(false)
const isFinishModalOpen = ref(false)
const minMeetingAttendees = 2

const missingRatingAttendees = computed(() => {
  if (!meeting.value) return []
  return meeting.value.attendees.filter((attendee) =>
    meeting.value!.missingRatingMemberIds.includes(attendee.id),
  )
})
const hasMeetingQuorum = computed(() => (meeting.value?.attendees.length ?? 0) >= minMeetingAttendees)
const isCurrentUserAttending = computed(() => rsvpStatus.value === 'attending')
const currentUserId = computed(() => user.value?.id)
const shouldShowRatingForm = computed(() => meeting.value?.status === 'started' && rsvpStatus.value === 'attending')
const adminAttendeesCount = computed(() => meeting.value?.attendees.filter((a) => a.isAdmin).length ?? 0)

function canRemoveAttendee(attendee: { id: number; isAdmin?: boolean }) {
  if (attendee.isAdmin) return false
  if (attendee.id === currentUserId.value) {
    return adminAttendeesCount.value > 1
  }
  return true
}

function removeAttendee(memberId: number) {
  removeRsvpMutation.mutate(memberId)
}

watchEffect(() => {
  rsvpStatus.value = meeting.value?.rsvpStatus ?? 'pending'
  localTopics.value = meeting.value?.topics ?? []
})

function setRsvp(status: 'attending' | 'not_attending') {
  updateRsvpMutation.mutate(status, {
    onSuccess: () => {
      rsvpStatus.value = status
    },
  })
}

function submitTopic() {
  const topic = newTopic.value.trim()
  if (!topic || !meeting.value) return

  addTopicMutation.mutate(topic, {
    onSuccess: () => {
      localTopics.value = [...localTopics.value, topic]
      newTopic.value = ''
    },
  })
}

function submitRatingReview() {
  ratingSubmitted.value = true
  if (!rating.value) return

  saveRatingReviewMutation.mutate({
    rating: rating.value,
    review: review.value.trim(),
  })
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
    p.body-text Загружаем встречу...
  section.panel.meeting-detail__missing(v-else-if="meetingQuery.error.value")
    .section-header.section-header--compact
      h1 Встреча не найдена
    p.body-text Возможно, ссылка устарела или встреча ещё не назначена.
    RouterLink.button.button--primary.label-text(to="/") Вернуться на дашборд
  template(v-else-if="meeting")
    nav.meeting-detail__breadcrumb.label-text(aria-label="Навигация")
      RouterLink(to="/") Дашборд
      span /
      span {{ meeting.cycleLabel }}
      span /
      span.meeting-detail__breadcrumb-current {{ meeting.title }}

    .section-header
      .meeting-detail__header-row
        h1.meeting-detail__title {{ meeting.title }}
      span.label-text {{ meeting.cycleLabel }}

    .meeting-detail__grid
      .meeting-detail__main
        form.panel.meeting-detail__rating(v-if="shouldShowRatingForm" @submit.prevent="submitRatingReview" novalidate)
          .section-header.section-header--compact
            h2 Оценка и отзыв
          .meeting-detail__rating-row
            label.label-text(for="meeting-rating") Оценка
            input#meeting-rating.field-control.meeting-detail__input(
              v-model.number="rating"
              type="number"
              min="1"
              max="10"
              placeholder="1-10"
              :aria-invalid="ratingSubmitted && !rating"
            )
          p.proposal__error(v-if="ratingSubmitted && !rating") Укажи оценку от 1 до 10.
          .meeting-detail__rating-row
            label.label-text(for="meeting-review") Отзыв
            textarea#meeting-review.field-control.meeting-detail__input.meeting-detail__review(
              v-model="review"
              placeholder="Короткий отзыв, если хочется зафиксировать впечатление."
            )
          button.button.button--primary.label-text(type="submit" :disabled="saveRatingReviewMutation.isPending.value")
            | {{ saveRatingReviewMutation.isPending.value ? 'Сохраняем...' : 'Сохранить оценку' }}
          p.body-text(v-if="saveRatingReviewMutation.isSuccess.value") Оценка сохранена.
          p.proposal__error(v-if="saveRatingReviewMutation.error.value") Не удалось сохранить оценку.

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

          .meeting-detail__admin-panel(v-if="isAdmin && !isArchived")
            .meeting-detail__admin-head
              span.label-text Управление встречей
              .meeting-detail__admin-actions
                RouterLink.button.button--ghost.label-text(:to="`/meetings/${meeting.id}/edit`")
                  Pencil.meeting-detail__button-icon
                  | Редактировать
                template(v-if="meeting.status === 'scheduled'")
                  button.button.button--secondary.label-text(
                    type="button"
                    :disabled="!meeting.canStart || startMeetingMutation.isPending.value"
                    @click="startMeeting"
                  ) {{ startMeetingMutation.isPending.value ? 'Начинаем...' : 'Начать встречу' }}
                template(v-else-if="meeting.status === 'started'")
                  button.button.button--primary.label-text(
                    type="button"
                    :disabled="!meeting.canFinish || finishMeetingMutation.isPending.value"
                    @click="openFinishModal"
                  ) {{ finishMeetingMutation.isPending.value ? 'Завершаем...' : 'Закончить встречу и цикл' }}
            .meeting-detail__admin-alerts
              template(v-if="meeting.status === 'scheduled'")
                .inline-alert(v-if="!hasMeetingQuorum")
                  AlertTriangle(:size="14")
                  span Нужно минимум 2 участника со статусом «Буду».
                p.proposal__error(v-if="startMeetingMutation.error.value") Не удалось начать встречу.
              template(v-else-if="meeting.status === 'started'")
                .inline-alert(v-if="missingRatingAttendees.length")
                  AlertTriangle(:size="14")
                  span Нужны оценки:
                  span.meeting-detail__missing-rating
                    template(v-for="(attendee, idx) in missingRatingAttendees" :key="attendee.id")
                      | {{ attendee.name }}{{ idx < missingRatingAttendees.length - 1 ? ', ' : '' }}
                p.proposal__error(v-if="finishMeetingMutation.error.value") Не удалось завершить встречу.

        section.meeting-detail__topics-section(aria-labelledby="meeting-topics-title")
          .section-header.section-header--compact
            h2#meeting-topics-title Темы для обсуждения
          ul.meeting-detail__topics(v-if="localTopics.length")
            li.meeting-detail__topic(v-for="(topic, index) in localTopics" :key="`${topic}-${index}`")
              MessageSquare.meeting-detail__topic-icon(:size="16" aria-hidden="true")
              span {{ topic }}
          p.body-text(v-else) Тем пока нет.
          .meeting-detail__add-topic(v-if="!isArchived")
            span.label-text Добавить тему
            .meeting-detail__add-topic-row
              input.meeting-detail__input(
                class="field-control"
                v-model="newTopic"
                type="text"
                placeholder="Предложить вопрос..."
                @keydown.enter.prevent="submitTopic"
              )
              button.button.button--secondary.label-text(type="button" @click="submitTopic")
                Send.meeting-detail__button-icon
                | Отправить

      aside.meeting-detail__sidebar(aria-label="Сводка встречи")
        section.panel.meeting-detail__participants(aria-labelledby="meeting-participants-title")
          .section-header.section-header--compact
            h2#meeting-participants-title Участники встречи
            .meeting-detail__participant-count
              span.label-text {{ meeting.attendees.length }}
              Users(:size="15" aria-hidden="true")
          .inline-alert.inline-alert--success(v-if="!isArchived && rsvpStatus === 'attending'") Вы идёте на эту встречу
          .inline-alert(v-else-if="!isArchived && rsvpStatus === 'not_attending'") Вы не сможете прийти
          button.button.button--primary.label-text(
            v-if="!isArchived && !isCurrentUserAttending"
            type="button"
            :disabled="updateRsvpMutation.isPending.value"
            @click="setRsvp('attending')"
          ) Буду на встрече
          ul.data-list
            li.data-list__item(v-for="attendee in meeting.attendees" :key="attendee.id")
              RouterLink.meeting-detail__attendee(:to="`/members/${attendee.id}`")
                UserAvatar(:name="attendee.name" :avatar-url="attendee.avatarUrl" size="sm")
                span.body-text {{ attendee.name }}
              button.meeting-detail__decline-button(
                v-if="!isArchived && attendee.id === currentUserId"
                type="button"
                title="Не смогу"
                aria-label="Не смогу"
                :disabled="updateRsvpMutation.isPending.value"
                @click="setRsvp('not_attending')"
              )
                X(:size="15" aria-hidden="true")
              button.meeting-detail__remove-button(
                v-if="isAdmin && !isArchived && canRemoveAttendee(attendee)"
                type="button"
                title="Удалить из участников"
                aria-label="Удалить из участников"
                :disabled="removeRsvpMutation.isPending.value"
                @click="removeAttendee(attendee.id)"
              )
                UserMinus(:size="15" aria-hidden="true")
          p.body-text(v-if="!meeting.attendees.length") Пока никто не подтвердил участие.

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
      h1 Встреча не найдена
    p.body-text Возможно, ссылка устарела или встреча ещё не назначена.
    RouterLink.button.button--primary.label-text(to="/") Вернуться на дашборд
</template>

<style scoped>
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
  grid-template-columns: minmax(0, 2fr) minmax(18rem, 1fr);
  gap: var(--space-xl);
  align-items: start;
}

.meeting-detail__main {
  display: grid;
  gap: var(--space-lg);
}

.meeting-detail__info {
  display: grid;
  gap: var(--space-lg);
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
  align-items: center;
  gap: var(--space-sm);
  flex-wrap: wrap;
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
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-md);
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
    linear-gradient(180deg, rgba(67, 224, 125, 0.07), rgba(67, 224, 125, 0.018)),
    var(--bg-panel);
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

.meeting-detail__missing-rating {
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
}

.meeting-detail__rating-row {
  display: grid;
  gap: var(--space-sm);
}

.meeting-detail__review {
  min-height: 6rem;
}

.proposal__error {
  color: var(--danger);
  font-size: 0.8rem;
  line-height: 1.4;
}

.meeting-detail__add-topic-row {
  display: flex;
  gap: var(--space-sm);
}

.meeting-detail__input {
  flex: 1;
  padding: 0.75rem 0.9rem;
}

.meeting-detail__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.meeting-detail__participants {
  display: grid;
  gap: var(--space-md);
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

.meeting-detail__decline-button {
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

.meeting-detail__decline-button:hover:not(:disabled) {
  border-color: var(--danger);
  color: var(--danger);
}

.meeting-detail__decline-button:disabled {
  cursor: not-allowed;
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

@media (max-width: 960px) {
  .meeting-detail__grid {
    grid-template-columns: 1fr;
  }

  .meeting-detail__info-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .meeting-detail__admin-actions,
  .meeting-detail__add-topic-row {
    align-items: stretch;
    flex-direction: column;
  }

  .meeting-detail__admin-actions .button,
  .meeting-detail__add-topic-row .button {
    align-self: stretch;
    width: 100%;
  }
}
</style>
