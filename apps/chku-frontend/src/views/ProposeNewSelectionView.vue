<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { BookMarked, CheckCircle2, Send } from '@lucide/vue'
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
      .proposal__form-note
        BookMarked.proposal__note-icon
        p.body-text Кандидат попадёт на проверку активным участникам клуба. Утвердить его можно только после ответов всех участников.
      form(@submit.prevent="submitProposal" novalidate)
        .proposal__field
          label.label-text(for="book-title") Название книги
          input#book-title.field-control.proposal__input(
            v-model="proposal.title"
            type="text"
            placeholder="Например, Мастер и Маргарита"
            :aria-invalid="submitAttempted && !isFilled(proposal.title)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(proposal.title)") Укажи название книги.

        .proposal__field
          label.label-text(for="book-author") Автор
          input#book-author.field-control.proposal__input(
            v-model="proposal.author"
            type="text"
            placeholder="Например, Михаил Булгаков"
            :aria-invalid="submitAttempted && !isFilled(proposal.author)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(proposal.author)") Укажи автора.

        .proposal__field
          label.label-text(for="book-description") Краткое описание
          textarea#book-description.field-control.proposal__input.proposal__textarea(
            v-model="proposal.description"
            placeholder="Коротко опиши, о чём книга."
            :aria-invalid="submitAttempted && !isFilled(proposal.description)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(proposal.description)") Добавь краткое описание.

        .proposal__field
          label.label-text(for="book-reason") Почему эта книга?
          textarea#book-reason.field-control.proposal__input.proposal__textarea(
            v-model="proposal.reason"
            placeholder="Какие темы и разговоры она может открыть для клуба?"
            :aria-invalid="submitAttempted && !isFilled(proposal.reason)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(proposal.reason)") Объясни выбор книги.

        .proposal__actions
          button.button.button--secondary.label-text(type="button" @click="router.push({ name: 'profile' })") Отмена
          button.button.button--primary.label-text(type="submit" :disabled="createCandidateMutation.isPending.value")
            Send.proposal__button-icon(v-if="!createCandidateMutation.isPending.value")
            | {{ createCandidateMutation.isPending.value ? 'Отправляем...' : 'Отправить на проверку' }}
          p.proposal__error(v-if="createCandidateMutation.error.value") Не удалось отправить предложение.

    aside.proposal__guidelines(aria-label="Правила выбора")
      .section-header.section-header--compact
        span.label-text Правила выбора
      .proposal__guideline
        CheckCircle2.proposal__guideline-icon
        h3 Доступность
        p.body-text
          | Лучше выбрать книгу, которую легко найти в бумажном, электронном или аудиоформате.
      .proposal__guideline
        CheckCircle2.proposal__guideline-icon
        h3 Объём
        p.body-text
          | Ориентир для стандартного цикла чтения — книга, которую реально прочитать примерно за четыре недели.
      .proposal__guideline
        CheckCircle2.proposal__guideline-icon
        h3 Потенциал обсуждения
        p.body-text
          | Подойдут сложные темы, неоднозначные герои и вопросы, о которых интересно спорить на встрече.
      .proposal__guideline
        CheckCircle2.proposal__guideline-icon
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

.proposal__form-note {
  display: flex;
  align-items: flex-start;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
  padding: var(--space-md);
  border: var(--border-width) solid var(--accent-border);
  border-radius: var(--radius-inner);
  background: var(--accent-bg);
}

.proposal__note-icon,
.proposal__button-icon,
.proposal__guideline-icon {
  flex: 0 0 auto;
  width: 1rem;
  height: 1rem;
  color: var(--accent);
}

.proposal__field {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  margin-bottom: var(--space-lg);
}

.proposal__input {
  width: 100%;
  padding: 0.75rem 0.9rem;
}

.proposal__textarea {
  min-height: 7.5rem;
}

.proposal__error {
  color: var(--danger);
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
  position: relative;
  padding-bottom: var(--space-md);
  padding-left: 1.75rem;
  border-bottom: var(--border-width) solid var(--border);
}

.proposal__guideline-icon {
  position: absolute;
  top: 0.2rem;
  left: 0;
  color: var(--text-subtle);
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
