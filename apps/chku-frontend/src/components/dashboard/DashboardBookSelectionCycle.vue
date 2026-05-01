<script setup lang="ts">
import { computed } from 'vue'
import { Check, Clock3, X } from '@lucide/vue'
import type { ApiBookCandidate, ApiBookCandidateResponse } from '@/api/types'
import { useAuthSession } from '@/queries/authQueries'
import { useCandidateResponseMutation, useConfirmCandidateMutation } from '@/queries/candidateQueries'

const props = defineProps<{
  candidate: ApiBookCandidate
}>()

const { user } = useAuthSession()
const responseMutation = useCandidateResponseMutation()
const confirmMutation = useConfirmCandidateMutation()
const isPending = computed(() => responseMutation.isPending.value || confirmMutation.isPending.value)
const coverTitleLines = computed(() => props.candidate.book.title.split('\n'))
const currentResponse = computed(() =>
  props.candidate.responses.find((response) => response.member.id === user.value?.id),
)
const canRespond = computed(
  () => props.candidate.status === 'pending' && currentResponse.value?.response === 'pending',
)
const statusLabel = computed(() =>
  props.candidate.status === 'awaiting_owner_confirmation'
    ? 'Ожидает подтверждения'
    : 'Ожидает проверки',
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
    return 'Все активные участники ответили, что не читали книгу.'
  }

  if (readCount.value > 0) {
    return 'Есть ответ «читал(а)», этот кандидат не пройдёт проверку.'
  }

  return `Ответили «не читал(а)»: ${notReadCount.value}. Ждём ответов: ${pendingCount.value}.`
})

function responseLabel(response: ApiBookCandidateResponse['response']) {
  if (response === 'not_read') return 'Не читал(а)'
  if (response === 'read') return 'Читал(а)'
  return 'Ждём ответ'
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
    h2#book-selection-title Выбор книги
    span.label-text {{ statusLabel }}

  article.current-book
    .book-cover.current-book__cover(:aria-label="`Обложка книги ${candidate.book.title}`")
      .book-cover__content
        span.current-book__cover-label.label-text Предложил(а) {{ candidate.proposer.name }}
        template(v-for="line in coverTitleLines" :key="line")
          | {{ line }}
          br

    .current-book__details
      .current-book__meta
        h1 {{ candidate.book.title }}
        p.subtitle-italic {{ candidate.book.author }}
      p.body-text.current-book__description
        | {{ candidate.description || candidate.book.description }}
      .panel.panel--filled.book-selection__reason(v-if="candidate.reason")
        span.label-text Почему эта книга
        p.body-text {{ candidate.reason }}
      .book-selection__actions(v-if="canRespond || candidate.canConfirm")
        button.button.button--secondary.label-text(
          v-if="canRespond"
          type="button"
          :disabled="isPending"
          @click="respond('read')"
        ) Я читал(а)
        button.button.button--inverted.label-text(
          v-if="canRespond"
          type="button"
          :disabled="isPending"
          @click="respond('not_read')"
        ) Не читал(а)
        button.button.button--primary.label-text(
          v-if="candidate.canConfirm"
          type="button"
          :disabled="isPending"
          @click="confirm"
        ) Подтвердить книгу
      p.body-text.book-selection__note(v-else-if="candidate.status === 'awaiting_owner_confirmation'")
        | Ожидаем финальное подтверждение владельца.
      p.body-text.book-selection__note(v-else-if="currentResponse && currentResponse.response !== 'pending'")
        | Твой ответ сохранён: {{ responseLabel(currentResponse.response) }}.

  .section-header.dashboard__section-spaced
    h3 Ответы клуба
    span.label-text {{ responseSummary }}

  ul.data-list.club-progress(role="list")
    li.data-list__item.club-progress__item(v-for="response in candidate.responses" :key="response.id")
      .member-status
        span.avatar {{ response.member.initials }}
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
  width: 100%;
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
