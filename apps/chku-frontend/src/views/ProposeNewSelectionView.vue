<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { ArrowDown, ArrowUp, BookMarked, Plus, Trash2 } from '@lucide/vue'
import {
  useBookQueueQuery,
  useCreateBookQueueItemMutation,
  useRemoveBookQueueItemMutation,
  useReorderBookQueueMutation,
} from '@/queries/bookQueueQueries'

const queueQuery = useBookQueueQuery()
const createQueueItem = useCreateBookQueueItemMutation()
const removeQueueItem = useRemoveBookQueueItemMutation()
const reorderQueue = useReorderBookQueueMutation()

const form = reactive({
  title: '',
  author: '',
  description: '',
  reason: '',
})
const submitAttempted = ref(false)
const items = computed(() => queueQuery.items.value)

function isFilled(value: string) {
  return value.trim().length > 0
}

function resetForm() {
  form.title = ''
  form.author = ''
  form.description = ''
  form.reason = ''
  submitAttempted.value = false
}

function submitBook() {
  submitAttempted.value = true

  if (!isFilled(form.title) || !isFilled(form.author)) return

  createQueueItem.mutate(
    {
      title: form.title.trim(),
      author: form.author.trim(),
      description: form.description.trim(),
      reason: form.reason.trim(),
    },
    { onSuccess: resetForm },
  )
}

function statusLabel(status: string) {
  return (
    {
      queued: 'В очереди',
      in_verification: 'На проверке',
      approved: 'Принята',
      rejected: 'Отклонена',
      removed: 'Удалена',
    }[status] ?? status
  )
}

function move(index: number, direction: -1 | 1) {
  const nextIndex = index + direction
  if (nextIndex < 0 || nextIndex >= items.value.length) return

  const ordered = [...items.value]
  const current = ordered[index]
  ordered[index] = ordered[nextIndex]
  ordered[nextIndex] = current
  reorderQueue.mutate(ordered.map((item) => item.id))
}
</script>

<template lang="pug">
main.proposal.container
  .proposal__header
    span.label-text.proposal__eyebrow Личный кабинет
    h1 Моя очередь книг
    p.body-text.proposal__intro
      | Первая книга из списка автоматически уйдёт на проверку, когда подойдёт твоя очередь выбирать.

  .proposal__grid
    section.panel.proposal__form-panel(aria-labelledby="queue-form-title")
      .section-header.section-header--compact
        h2#queue-form-title Добавить книгу
        Plus.proposal__button-icon
      form(@submit.prevent="submitBook" novalidate)
        .proposal__field
          label.label-text(for="book-title") Название книги
          input#book-title.field-control.proposal__input(
            v-model="form.title"
            type="text"
            placeholder="Например, Мастер и Маргарита"
            :aria-invalid="submitAttempted && !isFilled(form.title)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(form.title)") Укажи название книги.

        .proposal__field
          label.label-text(for="book-author") Автор
          input#book-author.field-control.proposal__input(
            v-model="form.author"
            type="text"
            placeholder="Например, Михаил Булгаков"
            :aria-invalid="submitAttempted && !isFilled(form.author)"
          )
          p.proposal__error(v-if="submitAttempted && !isFilled(form.author)") Укажи автора.

        .proposal__field
          label.label-text(for="book-description") Краткое описание
          textarea#book-description.field-control.proposal__input.proposal__textarea(
            v-model="form.description"
            placeholder="Коротко опиши, о чём книга."
          )

        .proposal__field
          label.label-text(for="book-reason") Почему эта книга?
          textarea#book-reason.field-control.proposal__input.proposal__textarea(
            v-model="form.reason"
            placeholder="Какие темы она может открыть для клуба?"
          )

        .proposal__actions
          button.button.button--primary.label-text(type="submit" :disabled="createQueueItem.isPending.value")
            Plus.proposal__button-icon(v-if="!createQueueItem.isPending.value")
            | {{ createQueueItem.isPending.value ? 'Добавляем...' : 'Добавить в очередь' }}
          p.proposal__error(v-if="createQueueItem.error.value") Не удалось добавить книгу.

    section.proposal__queue(aria-labelledby="queue-title")
      .section-header
        h2#queue-title Очередь
        span.label-text {{ items.length }} книг

      section.panel(v-if="queueQuery.isLoading.value")
        p.body-text Загружаем очередь...
      section.panel(v-else-if="queueQuery.error.value")
        p.body-text Не удалось загрузить очередь.
      section.panel.proposal__empty(v-else-if="items.length === 0")
        BookMarked.proposal__empty-icon
        h3 Выберите книгу
        p.body-text Добавь хотя бы одну книгу, чтобы система смогла предложить её, когда подойдёт твоя очередь.
      .panel.proposal__book-list(v-else)
        article.proposal__book(v-for="(item, index) in items" :key="item.id")
          .proposal__book-index {{ index + 1 }}
          .proposal__book-main
            .proposal__book-header
              div
                h3.proposal__book-title {{ item.title }}
                p.body-text {{ item.author }}
              .proposal__badges
                span.badge.badge--action.label-text(v-if="index === 0 && item.status === 'queued'")
                  | Автоматически уйдёт в предложку
                span.badge.label-text(:class="{ 'badge--done': item.status === 'approved', 'badge--action': item.status === 'in_verification' }")
                  | {{ statusLabel(item.status) }}
            p.body-text(v-if="item.reason") {{ item.reason }}
          .proposal__book-actions
            button.button.button--secondary.label-text(type="button" :disabled="index === 0 || reorderQueue.isPending.value" @click="move(index, -1)" aria-label="Поднять книгу")
              ArrowUp.proposal__button-icon
            button.button.button--secondary.label-text(type="button" :disabled="index === items.length - 1 || reorderQueue.isPending.value" @click="move(index, 1)" aria-label="Опустить книгу")
              ArrowDown.proposal__button-icon
            button.button.button--secondary.label-text(type="button" :disabled="removeQueueItem.isPending.value || item.status === 'in_verification'" @click="removeQueueItem.mutate(item.id)" aria-label="Удалить книгу")
              Trash2.proposal__button-icon
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
  grid-template-columns: minmax(18rem, 0.85fr) minmax(0, 1.4fr);
  gap: var(--space-xl);
  align-items: start;
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
  min-height: 6.5rem;
}

.proposal__error {
  color: var(--danger);
  font-size: 0.8rem;
  line-height: 1.4;
}

.proposal__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: var(--space-xl);
}

.proposal__button-icon,
.proposal__empty-icon {
  flex: 0 0 auto;
  width: 1rem;
  height: 1rem;
}

.proposal__book-list {
  padding-top: var(--space-sm);
  padding-bottom: var(--space-sm);
}

.proposal__book {
  display: grid;
  grid-template-columns: 2rem minmax(0, 1fr) auto;
  gap: var(--space-md);
  padding: var(--space-lg) 0;
  border-bottom: var(--border-width) solid var(--border);
}

.proposal__book:last-child {
  border-bottom: 0;
}

.proposal__book-index {
  color: var(--text-subtle);
  font-family: var(--font-mono);
}

.proposal__book-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-sm);
}

.proposal__book-title {
  margin-bottom: var(--space-xs);
  font-size: 1.15rem;
}

.proposal__badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: var(--space-xs);
}

.proposal__book-actions {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.proposal__book-actions .button {
  width: 2.25rem;
  min-width: 2.25rem;
  padding: 0;
}

.proposal__empty {
  display: grid;
  justify-items: start;
  gap: var(--space-sm);
}

.proposal__empty-icon {
  color: var(--warn);
}

@media (max-width: 960px) {
  .proposal__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .proposal__book {
    grid-template-columns: 1fr;
  }

  .proposal__book-actions {
    justify-content: stretch;
  }

  .proposal__book-actions .button {
    flex: 1;
  }
}
</style>
