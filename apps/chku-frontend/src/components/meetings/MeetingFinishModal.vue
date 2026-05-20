<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { AlertTriangle, CheckCircle2 } from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppCheckbox from '@/components/ui/AppCheckbox.vue'
import type { BookProgressMember, MeetingDetail } from '@/types/dashboard'

const props = defineProps<{
  isOpen: boolean
  meeting: MeetingDetail
  memberProgress: BookProgressMember[]
}>()

const { t } = useI18n()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm'): void
}>()

const confirmed = ref(false)

watch(
  () => props.isOpen,
  (open) => {
    if (open) confirmed.value = false
  },
)

const hasMissingRatings = computed(() => props.meeting.missingRatingMemberIds.length > 0)

const attendeesWithRatings = computed(() =>
  props.meeting.attendees.map((attendee) => {
    const rating = props.meeting.ratings.find((r) => r.memberId === attendee.id)
    return { ...attendee, ratingValue: rating?.value ?? null }
  }),
)

const attendeesWithReviews = computed(() =>
  props.meeting.attendees.map((attendee) => {
    const review = props.meeting.reviews.find((r) => r.memberId === attendee.id)
    return { ...attendee, reviewText: review?.text ?? null }
  }),
)

const progressSorted = computed(() =>
  [...props.memberProgress]
    .filter((m) => m.progress !== undefined && m.progress !== null)
    .sort((a, b) => (b.progress ?? 0) - (a.progress ?? 0)),
)

const topProgressMembers = computed(() => progressSorted.value.slice(0, 3))

function handleConfirm() {
  if (!confirmed.value || hasMissingRatings.value) return
  emit('confirm')
}
</script>

<template lang="pug">
AppModal(:is-open="isOpen" :title="t('meetings.finishTitle')" @close="$emit('close')")
  template(#default)
    .finish-modal__section
      h3.finish-modal__subtitle {{ $t('meetings.finishMeeting') }}
      p.body-text {{ meeting.title }}
      p.body-text {{ meeting.dateLabel }} · {{ meeting.timeLabel }}

    .finish-modal__section
      h3.finish-modal__subtitle {{ $t('meetings.finishBook') }}
      p.body-text «{{ meeting.book.title }}» — {{ meeting.book.author }}

    .finish-modal__section
      h3.finish-modal__subtitle {{ $t('meetings.finishParticipants') }}
      ul.finish-modal__list(v-if="attendeesWithRatings.length")
        li.finish-modal__list-item(v-for="item in attendeesWithRatings" :key="item.id")
          UserAvatar(:name="item.name" :avatar-url="item.avatarUrl" size="sm")
          span.body-text {{ item.name }}
          span.finish-modal__badge(v-if="item.ratingValue !== null") {{ item.ratingValue }}/10
          span.finish-modal__badge.finish-modal__badge--warn(v-else) {{ $t('meetings.finishNoRating') }}

    .finish-modal__section
      h3.finish-modal__subtitle {{ $t('meetings.finishReviews') }}
      ul.finish-modal__list(v-if="attendeesWithReviews.length")
        li.finish-modal__list-item(v-for="item in attendeesWithReviews" :key="item.id")
          UserAvatar(:name="item.name" :avatar-url="item.avatarUrl" size="sm")
          span.body-text {{ item.name }}
          span.finish-modal__review(v-if="item.reviewText") {{ item.reviewText }}
          span.finish-modal__badge.finish-modal__badge--subtle(v-else) {{ $t('meetings.finishNoReview') }}

    .finish-modal__section(v-if="memberProgress.length")
      h3.finish-modal__subtitle {{ $t('meetings.finishProgress') }}
      ul.finish-modal__list
        li.finish-modal__list-item(v-for="item in progressSorted" :key="item.name")
          span.body-text {{ item.name }}
          .finish-modal__progress-track(v-if="item.progress && item.progress > 0")
            .progress.finish-modal__progress-bar(:aria-label="`${item.name}: ${item.progress}%`")
              .progress__bar(:style="{ '--progress-value': `${item.progress}%` }")
            span.label-text {{ item.progress }}%
          span.label-text(v-else) {{ $t('meetings.finishNotStarted') }}
          span.finish-modal__badge.finish-modal__badge--accent(v-if="topProgressMembers.includes(item)") {{ $t('meetings.finishContender') }}

    .finish-modal__alert(v-if="hasMissingRatings")
      AlertTriangle(:size="18")
      p.body-text {{ $t('meetings.finishCannot') }}

    AppCheckbox(v-model="confirmed")
      span.body-text {{ $t('meetings.finishConfirm') }}

  template(#footer)
    button.button.button--secondary.label-text(type="button" @click="$emit('close')") {{ $t('meetings.finishCancel') }}
    button.button.button--primary.label-text(
      type="button"
      :disabled="!confirmed || hasMissingRatings"
      @click="handleConfirm"
    ) {{ $t('meetings.finishBtn') }}
</template>

<style scoped>
.finish-modal__subtitle {
  margin-bottom: var(--space-sm);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-main);
}

.finish-modal__section {
  margin-bottom: var(--space-lg);
}

.finish-modal__list {
  display: grid;
  gap: var(--space-sm);
  margin: 0;
  padding: 0;
  list-style: none;
}

.finish-modal__list-item {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.finish-modal__badge {
  margin-left: auto;
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-inner);
  background: var(--accent-bg);
  color: var(--accent);
  font-size: 0.72rem;
  font-weight: 600;
}

.finish-modal__badge--warn {
  background: rgba(224, 67, 67, 0.12);
  color: var(--danger);
}

.finish-modal__badge--subtle {
  background: var(--bg-panel);
  color: var(--text-muted);
}

.finish-modal__badge--accent {
  background: rgba(216, 137, 43, 0.15);
  color: var(--warn);
}

.finish-modal__review {
  flex: 1 1 auto;
  min-width: 0;
  margin-left: var(--space-sm);
  color: var(--text-muted);
  font-size: 0.82rem;
  font-style: italic;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.finish-modal__progress-track {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  width: 10rem;
}

.finish-modal__progress-bar {
  flex: 1;
  margin: 0;
}

.finish-modal__alert {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-md);
  border: var(--border-width) solid var(--danger-border, rgba(224, 67, 67, 0.35));
  border-radius: var(--radius-inner);
  background: rgba(224, 67, 67, 0.08);
  color: var(--danger);
  margin-bottom: var(--space-lg);
}
</style>
