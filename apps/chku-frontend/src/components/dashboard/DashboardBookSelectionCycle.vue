<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check, Clock3, Pencil, X } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import CycleBookForm from '@/components/books/CycleBookForm.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import type { ApiBookCandidate, ApiBookCandidateResponse } from '@/api/types'
import { useAuthSession } from '@/queries/authQueries'
import { useCandidateResponseMutation, useConfirmCandidateMutation } from '@/queries/candidateQueries'

const { t } = useI18n()
const props = defineProps<{
  candidate: ApiBookCandidate
  cycleNumber?: number | null
}>()

const { user } = useAuthSession()
const responseMutation = useCandidateResponseMutation()
const confirmMutation = useConfirmCandidateMutation()
const isBookFormOpen = ref(false)
const isPending = computed(() => responseMutation.isPending.value || confirmMutation.isPending.value)
const coverTitleLines = computed(() => props.candidate.book.title.split('\n'))
const editCycleNumber = computed(() => props.candidate.cycleNumber ?? props.cycleNumber ?? null)
const currentResponse = computed(() =>
  props.candidate.responses.find((response) => response.member.id === user.value?.id),
)
const canRespond = computed(
  () => props.candidate.status === 'pending' && currentResponse.value?.response === 'pending',
)
const statusLabel = computed(() =>
  props.candidate.status === 'awaiting_owner_confirmation'
    ? t('dash.awaitingOwner')
    : t('dash.awaitingVerification'),
)
const pendingCount = computed(
  () => props.candidate.responses.filter((response) => response.response === 'pending').length,
)
const notReadCount = computed(
  () => props.candidate.responses.filter((response) => response.response === 'not_read').length,
)
const readCount = computed(
  () => props.candidate.responses.filter((response) => response.response === 'read').length,
)
const responseSummary = computed(() => {
  if (props.candidate.status === 'awaiting_owner_confirmation') {
    return t('dash.summaryAllClear')
  }

  if (readCount.value > 0) {
    return t('dash.summaryHasRead')
  }

  return t('dash.summaryCounts', { notRead: notReadCount.value, pending: pendingCount.value })
})

function responseLabel(response: ApiBookCandidateResponse['response']) {
  if (response === 'not_read') return t('dash.respNotRead')
  if (response === 'read') return t('dash.respRead')
  return t('dash.respPending')
}

function responseIcon(response: ApiBookCandidateResponse['response']) {
  if (response === 'not_read') return Check
  if (response === 'read') return X
  return Clock3
}

function responseModifier(response: ApiBookCandidateResponse['response']) {
  if (response === 'not_read') return 'book-selection__response-status--success'
  if (response === 'read') return 'book-selection__response-status--danger'
  return 'book-selection__response-status--pending'
}

function respond(response: 'read' | 'not_read') {
  responseMutation.mutate({
    candidateId: props.candidate.id,
    response,
  })
}

function confirm() {
  confirmMutation.mutate(props.candidate.id)
}
</script>

<template lang="pug">
section.dashboard__main.book-selection(aria-labelledby="book-selection-title")
  .section-header
    h2#book-selection-title {{ $t('dash.bookSelection') }}
    button.button.button--secondary.label-text(
      v-if="candidate.canEditBook && editCycleNumber && !isBookFormOpen"
      type="button"
      @click="isBookFormOpen = true"
    )
      Pencil.book-selection__button-icon
      | {{ $t('cycle.editBook') }}
    span.label-text(v-else) {{ statusLabel }}

  article.current-book
    .book-cover.current-book__cover(:style="{ backgroundColor: candidate.book.coverColor ?? undefined }" :aria-label="$t('archive.coverAria', { title: candidate.book.title })")
      img.current-book__cover-image(v-if="candidate.book.coverUrl" :src="candidate.book.coverUrl" :alt="candidate.book.title")
      .book-cover__content(v-else)
        span.current-book__cover-label.label-text {{ $t('dash.proposedBy', { name: candidate.proposer.name }) }}
        template(v-for="line in coverTitleLines" :key="line")
          | {{ line }}
          br

    .current-book__details
      CycleBookForm(
        v-if="isBookFormOpen && editCycleNumber"
        :cycle-number="editCycleNumber"
        :book="candidate.book"
        id-prefix="candidate-book"
        @cancel="isBookFormOpen = false"
        @saved="isBookFormOpen = false"
      )
      template(v-else)
        .current-book__meta
          h1 {{ candidate.book.title }}
          p.subtitle-italic {{ candidate.book.author }}
        p.body-text.current-book__description
          | {{ candidate.description || candidate.book.description }}
        .panel.panel--filled.book-selection__reason(v-if="candidate.reason")
          span.label-text {{ $t('dash.whyThisBook') }}
          p.body-text {{ candidate.reason }}
        .book-selection__actions(v-if="canRespond || candidate.canConfirm")
          button.button.button--secondary.label-text(
            v-if="canRespond"
            type="button"
            :disabled="isPending"
            @click="respond('read')"
          ) {{ $t('dash.iveRead') }}
          button.button.button--inverted.label-text(
            v-if="canRespond"
            type="button"
            :disabled="isPending"
            @click="respond('not_read')"
          ) {{ $t('dash.notRead') }}
          button.button.button--primary.label-text(
            v-if="candidate.canConfirm"
            type="button"
            :disabled="isPending"
            @click="confirm"
          ) {{ $t('dash.confirmBook') }}
        p.body-text.book-selection__note(v-else-if="candidate.status === 'awaiting_owner_confirmation'")
          | {{ $t('dash.waitingOwner') }}
        p.body-text.book-selection__note(v-else-if="currentResponse && currentResponse.response !== 'pending'")
          | {{ $t('dash.responseSaved', { response: responseLabel(currentResponse.response) }) }}

  .section-header.dashboard__section-spaced
    h3 {{ $t('dash.clubResponses') }}
    span.label-text {{ responseSummary }}

  ul.data-list.club-progress(role="list")
    li.data-list__item.club-progress__item(v-for="response in candidate.responses" :key="response.id")
      .member-status
        UserAvatar(:name="response.member.name" :avatar-url="response.member.avatarUrl" size="sm")
        span.member-status__name {{ response.member.name }}
      span.label-text.book-selection__response-status(:class="responseModifier(response.response)")
        component(:is="responseIcon(response.response)" :size="15" aria-hidden="true")
        | {{ responseLabel(response.response) }}
</template>

<style scoped>
.dashboard__main {
  min-width: 0;
  padding: var(--space-xl);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.012)),
    var(--bg-surface);
  box-shadow: var(--shadow-panel);
}

.dashboard__section-spaced {
  margin-top: var(--space-xl);
}

.current-book {
  display: grid;
  grid-template-columns: minmax(11rem, 14rem) minmax(0, 1fr);
  gap: clamp(var(--space-lg), 4vw, var(--space-xl));
  margin-bottom: var(--space-xl);
}

.current-book__cover {
  position: relative;
  overflow: hidden;
  width: 100%;
}

.current-book__cover-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.current-book__cover-label {
  display: block;
  margin-bottom: var(--space-sm);
  font-size: 0.5rem;
  opacity: 0.7;
}

.current-book__details {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.current-book__meta {
  margin-bottom: var(--space-md);
}

.current-book__meta h1 {
  font-size: clamp(2.4rem, 5vw, 4.25rem);
  line-height: 1;
}

.current-book__description {
  max-width: 36rem;
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
  font-size: 1rem;
}

.book-selection__reason {
  display: grid;
  gap: var(--space-xs);
  padding: var(--space-md);
  border-radius: var(--radius-inner);
}

.book-selection__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-top: var(--space-md);
}

.book-selection__note {
  margin-top: var(--space-md);
  color: var(--text-muted);
}

.club-progress {
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
}

.club-progress__item {
  min-height: 3.4rem;
  padding: 0.7rem var(--space-md);
}

.member-status {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.member-status__name {
  color: var(--text-main);
  font-size: 0.82rem;
}

.book-selection__response-status {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
}

.book-selection__response-status--success {
  color: var(--accent);
}

.book-selection__response-status--danger {
  color: var(--danger);
}

.book-selection__response-status--pending {
  color: var(--text-muted);
}

.book-selection__button-icon {
  width: 1rem;
  height: 1rem;
}

@media (max-width: 760px) {
  .dashboard__main {
    padding: var(--space-lg);
  }

  .current-book {
    grid-template-columns: 1fr;
  }

  .current-book__cover {
    width: min(100%, 13rem);
  }

  .book-selection__actions {
    flex-direction: column;
  }

  .book-selection__actions .button {
    width: 100%;
  }
}
</style>
