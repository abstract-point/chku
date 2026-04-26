<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCreateCandidateMutation } from '@/queries/candidateQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import type { BookProposalForm } from '@/types/club'

const router = useRouter()
const dashboardQuery = useDashboardQuery()
const createCandidateMutation = useCreateCandidateMutation()

const proposal = reactive<BookProposalForm>({
  title: '',
  author: '',
  description: '',
  reason: '',
})
const submitAttempted = ref(false)

function isFilled(value: string) {
  return value.trim().length > 0
}

function submitProposal() {
  submitAttempted.value = true

  if (
    !isFilled(proposal.title) ||
    !isFilled(proposal.author) ||
    !isFilled(proposal.description) ||
    !isFilled(proposal.reason)
  ) {
    return
  }

  createCandidateMutation.mutate(
    {
      title: proposal.title.trim(),
      author: proposal.author.trim(),
      description: proposal.description.trim(),
      reason: proposal.reason.trim(),
    },
    {
      onSuccess: () => router.push({ name: 'home' }),
    },
  )
}
</script>

<template lang="pug">
main.proposal.container
  .proposal__header
    span.label-text.proposal__eyebrow {{ dashboardQuery.data?.currentBook ? 'Следующий цикл' : 'Новый цикл' }} • Твоя очередь
    h1 Предложить следующую книгу
    p.body-text.proposal__intro
      | Сейчас твоя очередь направить чтение клуба. Отправь книгу-кандидата на проверку: участники подтвердят, что ещё не читали её.

  .section-header
    h2 Форма предложения
    span.label-text Черновик

  .proposal__grid
    .panel.proposal__form-panel
      form(@submit.prevent="submitProposal" novalidate)
        .proposal__field
          label.label-text(for="book-title") Название книги
          input#book-title.proposal__input(
            v-model="proposal.title"
            type="text"
            placeholder="Например, Мастер и Маргарита"
            :aria-invalid="submitAttempted && !isFilled(proposal.title)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(proposal.title)") Укажи название книги.

        .proposal__field
          label.label-text(for="book-author") Автор
          input#book-author.proposal__input(
            v-model="proposal.author"
            type="text"
            placeholder="Например, Михаил Булгаков"
            :aria-invalid="submitAttempted && !isFilled(proposal.author)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(proposal.author)") Укажи автора.

        .proposal__field
          label.label-text(for="book-description") Краткое описание
          textarea#book-description.proposal__input.proposal__textarea(
            v-model="proposal.description"
            placeholder="Коротко опиши, о чём книга."
            :aria-invalid="submitAttempted && !isFilled(proposal.description)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(proposal.description)") Добавь краткое описание.

        .proposal__field
          label.label-text(for="book-reason") Почему эта книга?
          textarea#book-reason.proposal__input.proposal__textarea(
            v-model="proposal.reason"
            placeholder="Какие темы и разговоры она может открыть для клуба?"
            :aria-invalid="submitAttempted && !isFilled(proposal.reason)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(proposal.reason)") Объясни выбор книги.

        .proposal__actions
          button.button.button--secondary.label-text(type="button" @click="router.push({ name: 'profile' })") Отмена
          button.button.button--primary.label-text(type="submit" :disabled="createCandidateMutation.isPending.value")
            | {{ createCandidateMutation.isPending.value ? 'Отправляем...' : 'Отправить на проверку' }}
          p.proposal__error(v-if="createCandidateMutation.error.value") Не удалось отправить предложение.

    aside.proposal__guidelines(aria-label="Правила выбора")
      .section-header.section-header--compact
        span.label-text Правила выбора
      .proposal__guideline
        h3 Доступность
        p.body-text
          | Лучше выбрать книгу, которую легко найти в бумажном, электронном или аудиоформате.
      .proposal__guideline
        h3 Объём
        p.body-text
          | Ориентир для стандартного цикла чтения — книга, которую реально прочитать примерно за четыре недели.
      .proposal__guideline
        h3 Потенциал обсуждения
        p.body-text
          | Подойдут сложные темы, неоднозначные герои и вопросы, о которых интересно спорить на встрече.
      .proposal__guideline
        h3 Главное правило
        p.body-text
          | Книгу можно утвердить только если никто из активных участников клуба раньше её не читал.
</template>

<style scoped>
.proposal__header {
  margin-bottom: var(--space-xl);
}

.proposal__eyebrow {
  display: block;
  margin-bottom: var(--space-sm);
  color: var(--text-muted);
}

.proposal__intro {
  margin-top: var(--space-md);
}

.proposal__grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(18rem, 1fr);
  gap: var(--space-xl);
  margin-top: var(--space-xl);
}

.proposal__field {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  margin-bottom: var(--space-lg);
}

.proposal__input {
  width: 100%;
  padding: 0.75rem;
  border: var(--border-width) solid var(--border);
  border-radius: 0;
  background: var(--bg-surface);
  color: var(--text-main);
  outline: none;
  transition: border-color 0.2s ease;
}

.proposal__input:focus {
  border-color: var(--text-main);
}

.proposal__input[aria-invalid='true'] {
  border-color: var(--warn);
}

.proposal__textarea {
  min-height: 7.5rem;
}

.proposal__error {
  color: var(--warn);
  font-size: 0.8rem;
  line-height: 1.4;
}

.proposal__actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-md);
  margin-top: var(--space-xl);
}

.proposal__guidelines {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.proposal__guideline {
  padding-bottom: var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
}

.proposal__guideline:last-child {
  border-bottom: 0;
}

.proposal__guideline h3 {
  margin-bottom: var(--space-xs);
  font-size: 1.25rem;
}

@media (max-width: 960px) {
  .proposal__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .proposal__actions {
    align-items: stretch;
    flex-direction: column-reverse;
  }
}
</style>
