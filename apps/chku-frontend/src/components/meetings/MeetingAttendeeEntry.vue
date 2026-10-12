<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { UserPlus } from '@lucide/vue'
import AppCheckbox from '@/components/ui/AppCheckbox.vue'
import AppSelect from '@/components/ui/AppSelect.vue'
import SecondaryButton from '@/components/ui/SecondaryButton.vue'
import { ApiError } from '@/api/http'
import { useEnterMeetingAttendeeMutation } from '@/queries/meetingQueries'
import type { BookProgressMember, MeetingDetail } from '@/types/dashboard'

const props = defineProps<{
  meeting: MeetingDetail
  members: BookProgressMember[]
}>()

const { t } = useI18n()
const enterMutation = useEnterMeetingAttendeeMutation(() => props.meeting.id)

const isOpen = ref(false)
const memberId = ref<number | null>(null)
const finishedReading = ref(false)
const rating = ref<number | null>(null)
const review = ref('')
const submitted = ref(false)

const memberOptions = computed(() =>
  props.members.map((member) => ({ label: member.name, value: member.id })),
)
const selectedMember = computed(() => props.members.find((member) => member.id === memberId.value))
const alreadyFinished = computed(() => selectedMember.value?.progress === 100)
const isStarted = computed(() => props.meeting.status === 'started')
const canEnterRating = computed(
  () => isStarted.value && (alreadyFinished.value || finishedReading.value),
)
const existingRating = computed(
  () => props.meeting.ratings.find((item) => item.memberId === memberId.value)?.value ?? null,
)
const existingReview = computed(
  () => props.meeting.reviews.find((item) => item.memberId === memberId.value)?.text ?? '',
)
const isRatingInvalid = computed(
  () => rating.value !== null && (rating.value < 1 || rating.value > 10),
)
const errorMessage = computed(() => {
  const error = enterMutation.error.value
  if (!error) return ''
  return error instanceof ApiError ? error.message : t('meetings.entryError')
})

watch(memberId, () => {
  finishedReading.value = alreadyFinished.value
  rating.value = existingRating.value
  review.value = existingReview.value
  submitted.value = false
  enterMutation.reset()
})

function open() {
  isOpen.value = true
}

function close() {
  isOpen.value = false
  memberId.value = null
  enterMutation.reset()
}

function submit() {
  submitted.value = true
  if (memberId.value === null || isRatingInvalid.value) return

  enterMutation.mutate(
    {
      memberId: memberId.value,
      finishedReading: finishedReading.value,
      rating: canEnterRating.value ? rating.value : null,
      review: canEnterRating.value ? review.value.trim() : '',
    },
    { onSuccess: close },
  )
}
</script>

<template lang="pug">
.attendee-entry
  SecondaryButton(v-if="!isOpen" type="button" :icon="UserPlus" @click="open") {{ $t('meetings.entryOpen') }}
  form.attendee-entry__form(v-else @submit.prevent="submit" novalidate)
    .attendee-entry__head
      span.label-text {{ $t('meetings.entryTitle') }}
      p.body-text.attendee-entry__hint {{ $t('meetings.entryHint') }}
    .attendee-entry__field
      label.label-text(for="attendee-entry-member") {{ $t('meetings.entryMember') }}
      AppSelect#attendee-entry-member(
        :model-value="memberId"
        :options="memberOptions"
        :placeholder="t('meetings.entryMemberPlaceholder')"
        :aria-invalid="submitted && memberId === null"
        @update:model-value="memberId = $event ? Number($event) : null"
      )
      p.proposal__error(v-if="submitted && memberId === null") {{ $t('meetings.entryMemberRequired') }}
    template(v-if="memberId !== null")
      AppCheckbox(
        v-model="finishedReading"
        :disabled="alreadyFinished"
      ) {{ alreadyFinished ? $t('meetings.entryAlreadyFinished') : $t('meetings.entryFinished') }}
      template(v-if="canEnterRating")
        .attendee-entry__field
          label.label-text(for="attendee-entry-rating") {{ $t('meetings.rating') }}
          .attendee-entry__rating
            input#attendee-entry-rating.field-control.attendee-entry__input(
              v-model.number="rating"
              type="number"
              min="1"
              max="10"
              step="0.1"
              :placeholder="t('meetings.entryRatingPlaceholder')"
              :aria-invalid="isRatingInvalid || undefined"
            )
            span.label-text {{ $t('meetings.of10') }}
          p.proposal__error(v-if="isRatingInvalid") {{ $t('meetings.ratingRequired') }}
        .attendee-entry__field
          label.label-text(for="attendee-entry-review") {{ $t('meetings.review') }}
          textarea#attendee-entry-review.field-control.attendee-entry__input.attendee-entry__review(
            v-model="review"
            maxlength="10000"
            :placeholder="t('meetings.entryReviewPlaceholder')"
          )
      p.body-text.attendee-entry__hint(v-else-if="!isStarted") {{ $t('meetings.entryRatingAfterStart') }}
      p.body-text.attendee-entry__hint(v-else) {{ $t('meetings.entryRatingNeedsFinished') }}
    p.proposal__error(v-if="errorMessage") {{ errorMessage }}
    .attendee-entry__actions
      button.button.button--secondary.label-text(type="button" @click="close") {{ $t('common.cancel') }}
      button.button.button--primary.label-text(type="submit" :disabled="enterMutation.isPending.value")
        | {{ enterMutation.isPending.value ? $t('meetings.entrySaving') : $t('meetings.entrySave') }}
</template>

<style scoped>
.attendee-entry__form {
  display: grid;
  gap: var(--space-md);
  padding-top: var(--space-md);
  border-top: 1px solid var(--border);
}

.attendee-entry__head {
  display: grid;
  gap: var(--space-xs);
}

.attendee-entry__hint {
  margin: 0;
  color: var(--text-muted);
}

.attendee-entry__field {
  display: grid;
  gap: var(--space-xs);
}

.attendee-entry__rating {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  max-width: 12rem;
}

.attendee-entry__input {
  width: 100%;
  padding: 0.75rem 0.9rem;
  font: inherit;
}

.attendee-entry__review {
  min-height: 5rem;
  resize: vertical;
}

.attendee-entry__actions {
  display: flex;
  justify-content: space-between;
  gap: var(--space-sm);
}

@media (max-width: 640px) {
  .attendee-entry__actions {
    flex-direction: column-reverse;
  }
}
</style>
