<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { ArrowDown, ArrowUp, BookMarked, CheckCircle2, GitBranch, Plus, Send, Trash2 } from '@lucide/vue'
import AppTabs from '@/components/ui/AppTabs.vue'
import {
  useBookQueueQuery,
  useCreateBookQueueItemMutation,
  useMakeBookQueueItemCandidateMutation,
  useRejectedBookQueueQuery,
  useRemoveBookQueueItemMutation,
  useReorderBookQueueMutation,
} from '@/queries/bookQueueQueries'
import type { BookQueueItem } from '@/types/club'

const queueQuery = useBookQueueQuery()
const rejectedQuery = useRejectedBookQueueQuery()
const createQueueItem = useCreateBookQueueItemMutation()
const removeQueueItem = useRemoveBookQueueItemMutation()
const makeCandidate = useMakeBookQueueItemCandidateMutation()
const reorderQueue = useReorderBookQueueMutation()

const activeTab = ref<'queue' | 'rejected'>('queue')

const form = reactive({
  title: '',
  author: '',
  description: '',
  reason: '',
})
const submitAttempted = ref(false)
const items = computed(() => queueQuery.items.value)
const rejectedItems = computed(() => rejectedQuery.items.value)
const currentCandidate = computed(() => items.value.find((item) => item.isCurrentCandidate) ?? null)
const nextCandidate = computed(() => items.value.find((item) => item.status === 'queued') ?? null)

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

function itemBadge(item: BookQueueItem) {
  if (item.isCurrentCandidate) return 'Сейчас в предложке'
  if (nextCandidate.value?.id === item.id) return 'Кандидат на следующий цикл'
  return statusLabel(item.status)
}

function itemBadgeClass(item: BookQueueItem) {
  return {
    'badge--done': item.isCurrentCandidate,
    'badge--action': nextCandidate.value?.id === item.id && !item.isCurrentCandidate,
  }
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

function promote(item: BookQueueItem) {
  if (!item.canBecomeCandidate || makeCandidate.isPending.value) return
  makeCandidate.mutate(item.id)
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template lang="pug">
main.proposal.container
  .proposal__header
    span.label-text.proposal__eyebrow Личный кабинет
    h1 Моя очередь книг
    p.body-text.proposal__intro
      | Голова списка — ближайший кандидат. Когда твоя очередь уже наступила, текущая предложенная книга остаётся первой в цепочке.

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
        span.label-text {{ items.length }} книг в цепочке

      AppTabs(
        :tabs="[{ id: 'queue', label: 'Очередь' }, { id: 'rejected', label: 'Отклонённые' }]"
        v-model="activeTab"
        aria-label="Фильтр книг"
      )

      section.panel(v-if="activeTab === 'queue' && queueQuery.isLoading.value")
        p.body-text Загружаем очередь...
      section.panel(v-else-if="activeTab === 'queue' && queueQuery.error.value")
        p.body-text Не удалось загрузить очередь.
      section.panel.proposal__empty(v-else-if="activeTab === 'queue' && items.length === 0")
        BookMarked.proposal__empty-icon
        h3 Выберите книгу
        p.body-text Добавь хотя бы одну книгу, чтобы система смогла предложить её, когда подойдёт твоя очередь.
      .panel.proposal__book-list(v-else-if="activeTab === 'queue'")
        .proposal__queue-summary(aria-live="polite")
          .proposal__summary-item(v-if="currentCandidate")
            CheckCircle2.proposal__summary-icon
            div
              span.label-text Сейчас в предложке
              strong {{ currentCandidate.title }}
          .proposal__summary-item(v-if="nextCandidate && nextCandidate.id !== currentCandidate?.id")
            GitBranch.proposal__summary-icon
            div
              span.label-text Ближайший кандидат
              strong {{ nextCandidate.title }}
        article.proposal__book(
          v-for="(item, index) in items"
          :key="item.id"
          :class="{ 'proposal__book--active': item.isCurrentCandidate, 'proposal__book--next': nextCandidate?.id === item.id && !item.isCurrentCandidate }"
        )
          .proposal__book-node(aria-hidden="true")
            span
          .proposal__book-main
            .proposal__book-header
              .proposal__book-title-wrap
                h3.proposal__book-title {{ item.title }}
                p.proposal__book-author {{ item.author }}
              span.badge.badge--sm(:class="itemBadgeClass(item)")
                | {{ itemBadge(item) }}
            p.proposal__book-meta(v-if="item.description") {{ item.description }}
            p.proposal__book-reason(v-if="item.reason")
              span Почему: {{ item.reason }}
          .proposal__book-actions
            button.button.button--primary.label-text(
              type="button"
              :disabled="!item.canBecomeCandidate || makeCandidate.isPending.value"
              @click="promote(item)"
              aria-label="Сделать книгу кандидатом"
              title="Сделать кандидатом"
            )
              Send.proposal__button-icon
              span.proposal__action-text Сделать кандидатом
            button.button.button--secondary.label-text(
              type="button"
              :disabled="index === 0 || reorderQueue.isPending.value"
              @click="move(index, -1)"
              aria-label="Поднять книгу"
              title="Поднять"
            )
              ArrowUp.proposal__button-icon
            button.button.button--secondary.label-text(
              type="button"
              :disabled="index === items.length - 1 || reorderQueue.isPending.value"
              @click="move(index, 1)"
              aria-label="Опустить книгу"
              title="Опустить"
            )
              ArrowDown.proposal__button-icon
            button.button.button--secondary.label-text(
              type="button"
              :disabled="removeQueueItem.isPending.value || item.status === 'in_verification'"
              @click="removeQueueItem.mutate(item.id)"
              aria-label="Удалить книгу"
              title="Удалить"
            )
              Trash2.proposal__button-icon
        p.proposal__error(v-if="makeCandidate.error.value") Не удалось сделать книгу кандидатом.

      section.panel(v-if="activeTab === 'rejected' && rejectedQuery.isLoading.value")
        p.body-text Загружаем список...
      section.panel(v-else-if="activeTab === 'rejected' && rejectedQuery.error.value")
        p.body-text Не удалось загрузить список.
      section.panel.proposal__empty(v-else-if="activeTab === 'rejected' && rejectedItems.length === 0")
        BookMarked.proposal__empty-icon
        h3 Пока нет отклонённых книг
        p.body-text Книги, которые не прошли проверку участников, появятся здесь.
      .panel.proposal__book-list(v-else-if="activeTab === 'rejected'")
        article.proposal__book.proposal__book--rejected(v-for="item in rejectedItems" :key="item.id")
          .proposal__book-main
            .proposal__book-header
              .proposal__book-title-wrap
                h3.proposal__book-title {{ item.title }}
                p.proposal__book-author {{ item.author }}
              span.badge.badge--sm.badge--danger Отклонена
            p.proposal__book-meta(v-if="item.description") {{ item.description }}
            p.proposal__book-reason(v-if="item.reason")
              span Почему: {{ item.reason }}
            .proposal__book-rejection(v-if="item.rejectionInfo")
              | Отклонена {{ formatDate(item.rejectionInfo.rejectedAt) }}
              template(v-if="item.rejectionInfo.rejectedByMembers.length")
                |  · Прочитали: {{ item.rejectionInfo.rejectedByMembers.join(', ') }}
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
  display: grid;
  gap: var(--space-sm);
}

.proposal__book {
  position: relative;
  display: grid;
  grid-template-columns: 1.4rem minmax(0, 1fr) auto;
  gap: var(--space-md);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.026), rgba(255, 255, 255, 0.01)),
    var(--bg-panel);
}

.proposal__book--active {
  border-color: var(--accent-border);
  background:
    linear-gradient(180deg, rgba(67, 224, 125, 0.08), rgba(67, 224, 125, 0.02)),
    var(--bg-panel);
}

.proposal__book--next {
  border-color: var(--warn-border);
  background:
    linear-gradient(180deg, rgba(216, 137, 43, 0.07), rgba(216, 137, 43, 0.018)),
    var(--bg-panel);
}

.proposal__book--rejected {
  grid-template-columns: minmax(0, 1fr);
}

.proposal__book-node {
  position: relative;
  display: flex;
  justify-content: center;
  padding-top: 0.25rem;
}

.proposal__book-node::before {
  position: absolute;
  top: 1.2rem;
  bottom: calc(-1 * var(--space-md) - var(--space-sm));
  left: 50%;
  width: 1px;
  background: var(--border-strong);
  content: '';
}

.proposal__book:last-of-type .proposal__book-node::before {
  display: none;
}

.proposal__book-node span {
  position: relative;
  z-index: 1;
  width: 0.7rem;
  height: 0.7rem;
  border: var(--border-width) solid var(--border-strong);
  border-radius: 999px;
  background: var(--bg-surface);
}

.proposal__book--active .proposal__book-node span {
  border-color: var(--accent);
  background: var(--accent);
  box-shadow: 0 0 0 0.25rem var(--accent-bg);
}

.proposal__book--next .proposal__book-node span {
  border-color: var(--warn);
  background: var(--warn);
  box-shadow: 0 0 0 0.25rem var(--warn-bg);
}

.proposal__book-main {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  min-width: 0;
}

.proposal__book-header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: var(--space-sm);
  align-items: start;
  min-width: 0;
}

.proposal__book-title-wrap {
  min-width: 0;
}

.proposal__book-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 500;
  line-height: 1.3;
  overflow-wrap: break-word;
  color: var(--text-main);
}

.proposal__book-author {
  margin: 0.15rem 0 0;
  font-size: 0.85rem;
  color: var(--text-muted);
  line-height: 1.3;
  overflow-wrap: break-word;
}

.proposal__book-meta {
  margin: 0;
  font-size: 0.82rem;
  color: var(--text-muted);
  line-height: 1.4;
  overflow-wrap: break-word;
}

.proposal__book-reason {
  margin: 0;
  font-size: 0.82rem;
  color: var(--text-muted);
  line-height: 1.4;
  overflow-wrap: break-word;
}

.proposal__book-reason span {
  color: var(--text-secondary);
}

.proposal__book-rejection {
  margin: var(--space-xs) 0 0;
  padding-top: var(--space-xs);
  border-top: var(--border-width) solid var(--border);
  font-size: 0.8rem;
  color: var(--text-muted);
  font-family: var(--font-mono);
  line-height: 1.4;
  overflow-wrap: break-word;
}

.proposal__book-actions {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
  padding: var(--space-xs);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
  align-self: start;
}

.proposal__book-actions .button {
  min-height: 2.25rem;
  min-width: 2.25rem;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  touch-action: manipulation;
}

.proposal__book-actions .button--primary {
  min-width: auto;
  padding: 0 var(--space-sm);
  gap: var(--space-xs);
}

.proposal__action-text {
  display: none;
}

.proposal__queue-summary {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);
}

.proposal__summary-item {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  min-width: 0;
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.proposal__summary-item strong {
  display: block;
  overflow: hidden;
  color: var(--text-main);
  font-size: 0.92rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.proposal__summary-icon {
  flex: 0 0 auto;
  width: 1rem;
  height: 1rem;
  color: var(--accent);
}

.proposal__empty {
  display: grid;
  justify-items: start;
  gap: var(--space-sm);
}

.proposal__empty-icon {
  color: var(--warn);
}

.badge--sm {
  font-size: 0.7rem;
  padding: 0.15rem 0.5rem;
  font-family: var(--font-mono);
  letter-spacing: 0.02em;
  white-space: nowrap;
  align-self: start;
  margin-top: 0.15rem;
}

@media (max-width: 960px) {
  .proposal__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .proposal__queue-summary {
    grid-template-columns: 1fr;
  }

  .proposal__book {
    grid-template-columns: 1rem minmax(0, 1fr);
  }

  .proposal__book--rejected {
    grid-template-columns: minmax(0, 1fr);
  }

  .proposal__book-header {
    grid-template-columns: 1fr;
    gap: var(--space-xs);
  }

  .proposal__book-actions {
    grid-column: 2;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--space-xs);
    width: 100%;
  }

  .proposal__book-actions .button {
    width: 100%;
    min-height: 2.75rem;
    min-width: 0;
  }

  .proposal__book-actions .button--primary {
    grid-column: 1 / -1;
  }

  .proposal__action-text {
    display: inline;
  }
}
</style>
