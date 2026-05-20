<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { BookMarked, CheckCircle2, GitBranch, Pencil, Plus, Send, Trash2, X } from '@lucide/vue'
import AppTabs from '@/components/ui/AppTabs.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppTextarea from '@/components/ui/AppTextarea.vue'
import {
  useBookQueueQuery,
  useCreateBookQueueItemMutation,
  useMakeBookQueueItemCandidateMutation,
  useRejectedBookQueueQuery,
  useRemoveBookQueueItemMutation,
  useUpdateBookQueueItemMutation,
} from '@/queries/bookQueueQueries'
import { useFormErrors } from '@/composables/useFormErrors'
import type { BookQueueItem } from '@/types/club'

const queueQuery = useBookQueueQuery()
const rejectedQuery = useRejectedBookQueueQuery()
const createQueueItem = useCreateBookQueueItemMutation()
const removeQueueItem = useRemoveBookQueueItemMutation()
const makeCandidate = useMakeBookQueueItemCandidateMutation()
const updateQueueItem = useUpdateBookQueueItemMutation()

const activeTab = ref<'queue' | 'rejected'>('queue')
const editingId = ref<number | null>(null)
const editForms = reactive<Record<number, { description: string; reason: string }>>({})

const form = reactive({
  title: '',
  author: '',
  description: '',
  reason: '',
})
const formErrors = useFormErrors()
const items = computed(() => queueQuery.items.value)
const rejectedItems = computed(() => rejectedQuery.items.value)
const currentCandidate = computed(() => items.value.find((item) => item.isCurrentCandidate) ?? null)
const nextCandidate = computed(() => items.value.find((item) => item.status === 'queued') ?? null)

function resetForm() {
  form.title = ''
  form.author = ''
  form.description = ''
  form.reason = ''
  formErrors.clearAllErrors()
}

function submitBook() {
  formErrors.clearAllErrors()

  createQueueItem.mutate(
    {
      title: form.title.trim(),
      author: form.author.trim(),
      description: form.description.trim(),
      reason: form.reason.trim(),
    },
    {
      onSuccess: resetForm,
      onError: (error) => {
        formErrors.setFromApiError(error)
      },
    },
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

function promote(item: BookQueueItem) {
  if (!item.canBecomeCandidate || makeCandidate.isPending.value) return
  makeCandidate.mutate(item.id)
}

function startEdit(item: BookQueueItem) {
  editingId.value = item.id
  editForms[item.id] = {
    description: item.description ?? '',
    reason: item.reason ?? '',
  }
}

function cancelEdit() {
  editingId.value = null
}

function saveEdit(item: BookQueueItem) {
  const editForm = editForms[item.id]
  if (!editForm) return
  updateQueueItem.mutate(
    {
      id: item.id,
      description: editForm.description.trim() || null,
      reason: editForm.reason.trim() || null,
    },
    { onSuccess: cancelEdit },
  )
}

function isEditable(item: BookQueueItem) {
  return item.status === 'queued' || item.status === 'in_verification'
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
      form.proposal__form-fields(@submit.prevent="submitBook" novalidate)
        AppFormField(label="Название книги" label-for="book-title" required :error="formErrors.getError('title')")
          AppInput#book-title(
            v-model="form.title"
            type="text"
            placeholder="Например, Мастер и Маргарита"
            :aria-invalid="formErrors.hasError('title')"
          )

        AppFormField(label="Автор" label-for="book-author" required :error="formErrors.getError('author')")
          AppInput#book-author(
            v-model="form.author"
            type="text"
            placeholder="Например, Михаил Булгаков"
            :aria-invalid="formErrors.hasError('author')"
          )

        AppFormField(label="Краткое описание" label-for="book-description" :error="formErrors.getError('description')")
          AppTextarea#book-description(
            v-model="form.description"
            placeholder="Коротко опиши, о чём книга."
            :aria-invalid="formErrors.hasError('description')"
          )

        AppFormField(label="Почему эта книга?" label-for="book-reason" :error="formErrors.getError('reason')")
          AppTextarea#book-reason(
            v-model="form.reason"
            placeholder="Какие темы она может открыть для клуба?"
            :aria-invalid="formErrors.hasError('reason')"
          )

        .proposal__actions
          button.button.button--primary.label-text(type="submit" :disabled="createQueueItem.isPending.value")
            Plus.proposal__button-icon(v-if="!createQueueItem.isPending.value")
            | {{ createQueueItem.isPending.value ? 'Добавляем...' : 'Добавить в очередь' }}
          p.proposal__error(v-if="createQueueItem.error.value && !Object.keys(formErrors.fieldErrors.value).length")
            | {{ createQueueItem.error.value.message }}

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
      .proposal__book-list(v-else-if="activeTab === 'queue'")
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
        TransitionGroup.proposal__book-items(name="list" tag="div")
          article.proposal__book(
            v-for="(item, index) in items"
            :key="item.id"
            :class="{ 'proposal__book--active': item.isCurrentCandidate, 'proposal__book--next': nextCandidate?.id === item.id && !item.isCurrentCandidate }"
          )
            .proposal__book-main
              template(v-if="editingId === item.id")
                .proposal__book-header
                  .proposal__book-title-wrap
                    h3.proposal__book-title {{ item.title }}
                    p.proposal__book-author {{ item.author }}
                  span.badge.badge--sm(:class="itemBadgeClass(item)")
                    | {{ itemBadge(item) }}
                .proposal__field
                  label.label-text(:for="`edit-desc-${item.id}`") Краткое описание
                  textarea.field-control.proposal__textarea(
                    :id="`edit-desc-${item.id}`"
                    v-model="editForms[item.id].description"
                    placeholder="Коротко опиши, о чём книга."
                  )
                .proposal__field
                  label.label-text(:for="`edit-reason-${item.id}`") Почему эта книга?
                  textarea.field-control.proposal__textarea(
                    :id="`edit-reason-${item.id}`"
                    v-model="editForms[item.id].reason"
                    placeholder="Какие темы она может открыть для клуба?"
                  )
                .proposal__edit-actions
                  button.button.button--primary.label-text(
                    type="button"
                    :disabled="updateQueueItem.isPending.value"
                    @click="saveEdit(item)"
                  )
                    | {{ updateQueueItem.isPending.value ? 'Сохраняем...' : 'Сохранить' }}
                  button.button.button--secondary.label-text(
                    type="button"
                    :disabled="updateQueueItem.isPending.value"
                    @click="cancelEdit"
                  )
                    X.proposal__button-icon
                    | Отмена
                p.proposal__error(v-if="updateQueueItem.error.value") Не удалось сохранить изменения.
              template(v-else)
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
              button.button.button--secondary.label-text(
                v-if="index !== 0"
                type="button"
                :disabled="!item.canBecomeCandidate || makeCandidate.isPending.value"
                @click="promote(item)"
                aria-label="Сделать книгу кандидатом"
                title="Сделать кандидатом"
              )
                Send.proposal__button-icon
              button.button.button--secondary.label-text(
                v-if="isEditable(item)"
                type="button"
                :disabled="updateQueueItem.isPending.value"
                @click="startEdit(item)"
                aria-label="Редактировать книгу"
                title="Редактировать"
              )
                Pencil.proposal__button-icon
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
      .proposal__book-list(v-else-if="activeTab === 'rejected'")
        TransitionGroup.proposal__book-items(name="list" tag="div")
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

.proposal__form-fields {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
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
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.proposal__book-items {
  display: grid;
  gap: var(--space-sm);
}

.proposal__book {
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: var(--space-md);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.026), rgba(255, 255, 255, 0.01)),
    var(--bg-panel);
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.proposal__book:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-panel), 0 0.5rem 2rem rgba(0, 0, 0, 0.18);
  border-color: var(--border-strong);
}

.proposal__book--active {
  border-color: var(--accent-border);
  background:
    linear-gradient(180deg, rgba(67, 224, 125, 0.055), rgba(67, 224, 125, 0.018)),
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
  flex-direction: column;
  align-items: center;
  gap: var(--space-xs);
  padding: var(--space-xs);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
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

.proposal__action-text {
  display: none;
}

.proposal__edit-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-top: var(--space-sm);
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
    grid-template-columns: minmax(0, 1fr);
  }

  .proposal__book--rejected {
    grid-template-columns: minmax(0, 1fr);
  }

  .proposal__book-header {
    grid-template-columns: 1fr;
    gap: var(--space-xs);
  }

  .proposal__book-actions {
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: flex-end;
    width: 100%;
  }

  .proposal__book-actions .button {
    min-height: 2.75rem;
    min-width: 2.75rem;
  }

  .proposal__edit-actions {
    flex-direction: column;
  }

  .proposal__edit-actions .button {
    width: 100%;
    justify-content: center;
  }
}
</style>
