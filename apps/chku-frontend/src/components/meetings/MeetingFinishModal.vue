<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { AlertTriangle } from '@lucide/vue'
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
type OwlMedal = NonNullable<BookProgressMember['medal']>

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm'): void
}>()

const owlsApproved = ref(false)

watch(
  () => props.isOpen,
  (open) => {
    if (open) owlsApproved.value = false
  },
)

const hasMissingRatings = computed(() => props.meeting.missingRatingMemberIds.length > 0)

const attendeesWithRatingsAndReviews = computed(() =>
  props.meeting.attendees.map((attendee) => {
    const rating = props.meeting.ratings.find((r) => r.memberId === attendee.id)
    const review = props.meeting.reviews.find((r) => r.memberId === attendee.id)
    return { ...attendee, ratingValue: rating?.value ?? null, reviewText: review?.text ?? null }
  }),
)

const progressSorted = computed(() =>
  [...props.memberProgress]
    .filter((m) => m.progress !== undefined && m.progress !== null)
    .sort((a, b) => {
      const progressDiff = (b.progress ?? 0) - (a.progress ?? 0)
      if (progressDiff !== 0) return progressDiff
      if (a.finishedAt && b.finishedAt) {
        const finishedAtDiff = new Date(a.finishedAt).getTime() - new Date(b.finishedAt).getTime()
        if (finishedAtDiff !== 0) return finishedAtDiff
      }
      if (a.finishedAt) return -1
      if (b.finishedAt) return 1
      return a.id - b.id
    }),
)

const attendingMemberIds = computed(
  () => new Set(props.meeting.attendees.map((attendee) => attendee.id)),
)
const medalOrder: OwlMedal[] = ['gold', 'silver', 'bronze']

const owlAwardMembers = computed(() =>
  progressSorted.value
    .filter(
      (member) =>
        member.progress === 100 &&
        Boolean(member.finishedAt) &&
        attendingMemberIds.value.has(member.id),
    )
    .sort((a, b) => {
      const finishedAtDiff = new Date(a.finishedAt!).getTime() - new Date(b.finishedAt!).getTime()
      if (finishedAtDiff !== 0) return finishedAtDiff
      return a.id - b.id
    })
    .slice(0, 3)
    .map((member, index) => {
      const medal = medalOrder[index] ?? 'bronze'
      return {
        ...member,
        medal,
      }
    }),
)

const owlAwardByMemberId = computed(
  () => new Map(owlAwardMembers.value.map((member) => [member.id, member])),
)

const progressRows = computed(() =>
  progressSorted.value.map((member) => ({
    ...member,
    owlAward: owlAwardByMemberId.value.get(member.id) ?? null,
  })),
)

const allOwlsApproved = computed(() => owlAwardMembers.value.length === 0 || owlsApproved.value)

function medalLabel(medal: BookProgressMember['medal']) {
  if (medal === 'gold') return t('meetings.finishOwlGold')
  if (medal === 'silver') return t('meetings.finishOwlSilver')
  if (medal === 'bronze') return t('meetings.finishOwlBronze')
  return ''
}

function handleConfirm() {
  if (hasMissingRatings.value || !allOwlsApproved.value) return
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
      h3.finish-modal__subtitle {{ $t('meetings.finishParticipantsReviews') }}
      ul.finish-modal__list(v-if="attendeesWithRatingsAndReviews.length")
        li.finish-modal__review-item(v-for="item in attendeesWithRatingsAndReviews" :key="item.id")
          .finish-modal__review-member
            UserAvatar(:name="item.name" :avatar-url="item.avatarUrl" size="sm")
            span.body-text {{ item.name }}
          p.finish-modal__review(v-if="item.reviewText") {{ item.reviewText }}
          p.finish-modal__review.finish-modal__review--empty(v-else) {{ $t('meetings.finishNoReview') }}
          span.finish-modal__badge.finish-modal__rating-badge(v-if="item.ratingValue !== null") {{ item.ratingValue }}/10
          span.finish-modal__badge.finish-modal__badge--warn.finish-modal__rating-badge(v-else) {{ $t('meetings.finishNoRating') }}

    .finish-modal__section(v-if="memberProgress.length")
      h3.finish-modal__subtitle {{ $t('meetings.finishProgress') }}
      ul.finish-modal__list
        li.finish-modal__progress-item(v-for="item in progressRows" :key="item.id")
          .finish-modal__progress-member
            span.body-text {{ item.name }}
            .finish-modal__progress-track(v-if="item.progress && item.progress > 0")
              .progress.finish-modal__progress-bar(:aria-label="`${item.name}: ${item.progress}%`")
                .progress__bar(:style="{ '--progress-value': `${item.progress}%` }")
              span.label-text {{ item.progress }}%
            span.label-text(v-else) {{ $t('meetings.finishNotStarted') }}
          .finish-modal__owl(v-if="item.owlAward")
            img.finish-modal__owl-icon(
              :class="`finish-modal__owl-icon--${item.owlAward.medal}`"
              src="/owl.svg"
              :alt="medalLabel(item.owlAward.medal)"
              :title="medalLabel(item.owlAward.medal)"
            )

    .finish-modal__alert(v-if="hasMissingRatings")
      AlertTriangle(:size="18")
      p.body-text {{ $t('meetings.finishCannot') }}

    .finish-modal__alert.finish-modal__alert--warn(v-if="!hasMissingRatings && !allOwlsApproved")
      AlertTriangle(:size="18")
      p.body-text {{ $t('meetings.finishApproveOwlsWarning') }}

    AppCheckbox(v-if="owlAwardMembers.length" v-model="owlsApproved")
      span.body-text {{ $t('meetings.finishApproveOwls') }}

  template(#footer)
    button.button.button--secondary.label-text(type="button" @click="$emit('close')") {{ $t('meetings.finishCancel') }}
    button.button.button--primary.label-text(
      type="button"
      :disabled="hasMissingRatings || !allOwlsApproved"
      @click="handleConfirm"
    ) {{ $t('meetings.finishBtn') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

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

.finish-modal__review-item {
  display: grid;
  grid-template-columns: minmax(10rem, 0.7fr) minmax(0, 1fr) auto;
  align-items: start;
  gap: var(--space-md);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.finish-modal__review-member,
.finish-modal__progress-member {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  min-width: 0;
}

.finish-modal__badge {
  flex-shrink: 0;
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
  min-width: 0;
  margin: 0;
  color: var(--text-muted);
  font-size: 0.82rem;
  font-style: italic;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

.finish-modal__review--empty {
  color: var(--text-subtle);
}

.finish-modal__rating-badge {
  justify-self: end;
}

.finish-modal__progress-item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
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

.finish-modal__owl {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.finish-modal__owl-icon {
  width: 1.15rem;
  height: 1.15rem;
}

.finish-modal__owl-icon--gold {
  filter: invert(78%) sepia(35%) saturate(800%) hue-rotate(355deg) brightness(95%) contrast(90%);
}

.finish-modal__owl-icon--silver {
  filter: invert(82%) sepia(8%) saturate(200%) hue-rotate(170deg) brightness(95%);
}

.finish-modal__owl-icon--bronze {
  filter: invert(68%) sepia(40%) saturate(600%) hue-rotate(345deg) brightness(90%);
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

.finish-modal__alert--warn {
  border-color: rgba(216, 137, 43, 0.35);
  background: rgba(216, 137, 43, 0.1);
  color: var(--warn);
}

@include mobile-only {
  .finish-modal__review-item,
  .finish-modal__progress-item {
    grid-template-columns: minmax(0, 1fr) auto;
    gap: var(--space-sm);
  }

  .finish-modal__review-member {
    grid-column: 1;
    grid-row: 1;
  }

  .finish-modal__rating-badge {
    grid-column: 2;
    grid-row: 1;
    align-self: center;
  }

  .finish-modal__review-member,
  .finish-modal__progress-member,
  .finish-modal__owl {
    flex-wrap: wrap;
  }

  .finish-modal__progress-track {
    width: 100%;
  }

  .finish-modal__review {
    grid-column: 1 / -1;
    grid-row: 2;
  }
}
</style>
