<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { BookCheck, BookMarked, CheckCircle2, GitBranch, Pencil, Plus, Trash2, X } from '@lucide/vue'
import AppTabs from '@/components/ui/AppTabs.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppTextarea from '@/components/ui/AppTextarea.vue'
import FilePicker from '@/components/ui/FilePicker.vue'
import GenrePicker from '@/components/GenrePicker.vue'
import {
  useBookQueueQuery,
  useCreateBookQueueItemMutation,
  useMakeBookQueueItemCandidateMutation,
  useRejectedBookQueueQuery,
  useRemoveBookQueueItemMutation,
  useUpdateBookQueueItemMutation,
} from '@/queries/bookQueueQueries'
import { useFormErrors } from '@/composables/useFormErrors'
import { useAuthSession } from '@/queries/authQueries'
import { useGenresQuery } from '@/queries/genreQueries'
import type { BookQueueItem } from '@/types/club'

const { t } = useI18n()
const { isAdmin } = useAuthSession()
const queueQuery = useBookQueueQuery()
const rejectedQuery = useRejectedBookQueueQuery()
const createQueueItem = useCreateBookQueueItemMutation()
const removeQueueItem = useRemoveBookQueueItemMutation()
const makeCandidate = useMakeBookQueueItemCandidateMutation()
const updateQueueItem = useUpdateBookQueueItemMutation()
const genresQuery = useGenresQuery()

const activeTab = ref<'queue' | 'rejected'>('queue')
const editingId = ref<number | null>(null)
const editForms = reactive<Record<number, { title: string; author: string; description: string; coverFile: File | null; genreIds: number[] }>>({})

const form = reactive({
  title: '',
  author: '',
  description: '',
  coverFile: null as File | null,
  genreIds: [] as number[],
})
const formErrors = useFormErrors()

const isWide = ref(false)
const showForm = ref(false)

function updateWide() {
  const mq = window.matchMedia('(min-width: 1280px)')
  isWide.value = mq.matches
  if (mq.matches) {
    showForm.value = true
  }
}

onMounted(() => {
  updateWide()
  window.addEventListener('resize', updateWide)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateWide)
})

function toggleForm() {
  showForm.value = !showForm.value
}

const items = computed(() => queueQuery.items.value)
const rejectedItems = computed(() => rejectedQuery.items.value)
const currentCandidate = computed(() => items.value.find((item) => item.isCurrentCandidate) ?? null)
const nextCandidate = computed(() => items.value.find((item) => item.status === 'queued') ?? null)

function resetForm() {
  form.title = ''
  form.author = ''
  form.description = ''
  form.coverFile = null
  form.genreIds = []
  formErrors.clearAllErrors()
}

function submitBook() {
  formErrors.clearAllErrors()

  createQueueItem.mutate(
    {
      title: form.title.trim(),
      author: form.author.trim(),
      description: form.description.trim(),
      coverFile: form.coverFile,
      genre_ids: form.genreIds,
    },
    {
      onSuccess: resetForm,
      onError: (error) => {
        formErrors.setFromApiError(error)
      },
    },
  )
}

function promote(item: BookQueueItem) {
  if (!item.canBecomeCandidate || makeCandidate.isPending.value) return
  makeCandidate.mutate(item.id)
}

function startEdit(item: BookQueueItem) {
  editingId.value = item.id
  editForms[item.id] = {
    title: item.title,
    author: item.author,
    description: item.description ?? '',
    coverFile: null,
    genreIds: item.genres?.map((g) => g.id) ?? [],
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
      title: editForm.title.trim(),
      author: editForm.author.trim(),
      description: editForm.description.trim() || null,
      coverFile: editForm.coverFile,
      genre_ids: editForm.genreIds,
    },
    { onSuccess: cancelEdit },
  )
}

function isEditable(item: BookQueueItem) {
  if (isAdmin.value) return true
  return item.status === 'queued' || item.status === 'in_verification'
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}
</script>

<template lang="pug">
main.proposal.container
  .proposal__header
    span.label-text.proposal__eyebrow {{ $t('books.eyebrow') }}
    h1 {{ $t('books.title') }}
    p.body-text.proposal__intro
      | {{ $t('books.intro') }}

  .proposal__grid
    Transition(name="expand")
      section.panel.proposal__form-panel(v-show="showForm" aria-labelledby="queue-form-title")
        .section-header.section-header--compact
          h2#queue-form-title {{ $t('books.addBook') }}
          Plus.proposal__button-icon
        form.proposal__form-fields(@submit.prevent="submitBook" novalidate)
          AppFormField(:label="t('books.titleLabel')" label-for="book-title" required :error="formErrors.getError('title')" :hint="`${form.title.length}/255`")
            input#book-title.field-control.proposal__text-input(
              v-model="form.title"
              type="text"
              :maxlength="255"
              :placeholder="t('books.titlePlaceholder')"
              :aria-invalid="formErrors.hasError('title')"
            )

          AppFormField(:label="t('books.authorLabel')" label-for="book-author" required :error="formErrors.getError('author')" :hint="`${form.author.length}/255`")
            input#book-author.field-control.proposal__text-input(
              v-model="form.author"
              type="text"
              :maxlength="255"
              :placeholder="t('books.authorPlaceholder')"
              :aria-invalid="formErrors.hasError('author')"
            )

          AppFormField(:label="t('books.descLabel')" label-for="book-description" :error="formErrors.getError('description')" :hint="`${form.description.length}/500`")
            AppTextarea#book-description(
              v-model="form.description"
              :maxlength="500"
              :placeholder="t('books.descPlaceholder')"
              :aria-invalid="formErrors.hasError('description')"
            )

          AppFormField(:label="t('genrePicker.bookGenres')" label-for="book-genres" :error="formErrors.getError('genre_ids')")
            GenrePicker#book-genres(
              v-model="form.genreIds"
              :genres="genresQuery.data.value ?? []"
              :disabled="genresQuery.isLoading.value || createQueueItem.isPending.value"
              :error="formErrors.getError('genre_ids')"
            )

          AppFormField(:label="t('books.coverLabel')" label-for="book-cover" :error="formErrors.getError('cover')")
            FilePicker#book-cover(
              v-model="form.coverFile"
              variant="cover"
              :cover-title="form.title"
              accept="image/*"
            )

          .proposal__actions
            button.button.button--secondary.label-text(
              v-if="!isWide"
              type="button"
              @click="toggleForm"
            )
              X.proposal__button-icon
              | {{ $t('common.cancel') }}
            .proposal__actions-right
              button.button.button--primary.label-text(type="submit" :disabled="createQueueItem.isPending.value")
                Plus.proposal__button-icon(v-if="!createQueueItem.isPending.value")
                | {{ createQueueItem.isPending.value ? $t('books.adding') : $t('books.addToQueue') }}
              p.proposal__error(v-if="createQueueItem.error.value && !Object.keys(formErrors.fieldErrors.value).length")
                | {{ createQueueItem.error.value.message }}

    section.proposal__queue(aria-labelledby="queue-title")
      button.button.button--primary.label-text.proposal__toggle-form(
        v-if="!isWide && !showForm"
        type="button"
        @click="toggleForm"
      )
        Plus.proposal__button-icon
        | {{ $t('books.addBook') }}
      .section-header
        h2#queue-title {{ $t('books.queueTitle') }}
        span.label-text {{ $t('books.queueCount', { n: items.length }) }}

      AppTabs(
        :tabs="[{ id: 'queue', label: $t('books.tabQueue') }, { id: 'rejected', label: $t('books.tabRejected') }]"
        v-model="activeTab"
        :aria-label="t('books.filterAria')"
      )

      section.panel(v-if="activeTab === 'queue' && queueQuery.isLoading.value")
        p.body-text {{ $t('common.loadingQueue') }}
      section.panel(v-else-if="activeTab === 'queue' && queueQuery.error.value")
        p.body-text {{ $t('common.errorQueue') }}
      section.panel.proposal__empty(v-else-if="activeTab === 'queue' && items.length === 0")
        BookMarked.proposal__empty-icon
        h3 {{ $t('books.emptyTitle') }}
        p.body-text {{ $t('books.emptyText') }}
      .proposal__book-list(v-else-if="activeTab === 'queue'")
        .proposal__queue-summary(aria-live="polite")
          .proposal__summary-item(v-if="currentCandidate")
            CheckCircle2.proposal__summary-icon
            div
              span.label-text {{ $t('books.inProposal') }}
              strong {{ currentCandidate.title }}
          .proposal__summary-item(v-if="nextCandidate && nextCandidate.id !== currentCandidate?.id")
            GitBranch.proposal__summary-icon
            div
              span.label-text {{ $t('books.nextCandidate') }}
              strong {{ nextCandidate.title }}
        TransitionGroup.proposal__book-items(name="list" tag="div")
          article.proposal__book(
            v-for="(item, index) in items"
            :key="item.id"
            :class="{ 'proposal__book--active': item.isCurrentCandidate, 'proposal__book--next': nextCandidate?.id === item.id && !item.isCurrentCandidate }"
          )
            .proposal__book-cover(
              :style="{ '--cover-color': item.coverColor ?? undefined }"
              :aria-label="$t('archive.coverAria', { title: item.title })"
            )
              img.proposal__book-cover-image(v-if="item.coverUrl" :src="item.coverUrl" :alt="item.title")
              span.proposal__book-cover-title(v-else) {{ item.title }}
            .proposal__book-header
              .proposal__book-title-wrap
                h3.proposal__book-title {{ item.title }}
                p.proposal__book-author {{ item.author }}
                .proposal__book-genres(v-if="editingId !== item.id && item.genres?.length")
                  span.badge.badge--sm(v-for="genre in item.genres" :key="genre.id") {{ genre.name }}

            template(v-if="editingId === item.id")
              .proposal__book-edit
                .proposal__field
                  label.label-text(:for="`edit-title-${item.id}`") {{ $t('books.titleLabel') }}
                  input.field-control.proposal__input(
                    :id="`edit-title-${item.id}`"
                    v-model="editForms[item.id].title"
                    type="text"
                    :maxlength="255"
                    :placeholder="t('books.titlePlaceholder')"
                  )
                .proposal__field
                  label.label-text(:for="`edit-author-${item.id}`") {{ $t('books.authorLabel') }}
                  input.field-control.proposal__input(
                    :id="`edit-author-${item.id}`"
                    v-model="editForms[item.id].author"
                    type="text"
                    :maxlength="255"
                    :placeholder="t('books.authorPlaceholder')"
                  )
                .proposal__field
                  label.label-text(:for="`edit-desc-${item.id}`") {{ $t('books.descLabel') }}
                  textarea.field-control.proposal__textarea(
                    :id="`edit-desc-${item.id}`"
                    v-model="editForms[item.id].description"
                    :maxlength="500"
                    :placeholder="t('books.descPlaceholder')"
                  )
                  span.label-text {{ `${editForms[item.id]?.description?.length ?? 0}/500` }}
                .proposal__field
                  label.label-text(:for="`edit-genres-${item.id}`") {{ $t('genrePicker.bookGenres') }}
                  GenrePicker(
                    :id="`edit-genres-${item.id}`"
                    v-model="editForms[item.id].genreIds"
                    :genres="genresQuery.data.value ?? []"
                    :disabled="genresQuery.isLoading.value || updateQueueItem.isPending.value"
                  )
                .proposal__field
                  label.label-text(:for="`edit-cover-${item.id}`") {{ $t('books.coverLabel') }}
                  FilePicker(
                    :id="`edit-cover-${item.id}`"
                    v-model="editForms[item.id].coverFile"
                    variant="cover"
                    :cover-title="editForms[item.id].title"
                    :existing-url="item.coverUrl"
                    accept="image/*"
                  )
                .proposal__edit-actions
                  button.button.button--secondary.label-text(
                    type="button"
                    :disabled="updateQueueItem.isPending.value"
                    @click="cancelEdit"
                  )
                    X.proposal__button-icon
                    | {{ $t('books.cancel') }}
                  button.button.button--primary.label-text(
                    type="button"
                    :disabled="updateQueueItem.isPending.value"
                    @click="saveEdit(item)"
                  )
                    | {{ updateQueueItem.isPending.value ? $t('books.saving') : $t('books.save') }}
                p.proposal__error(v-if="updateQueueItem.error.value") {{ $t('books.editError') }}
            template(v-else)
              .proposal__book-content(v-if="item.description")
                p.proposal__book-meta {{ item.description }}
            .proposal__book-actions
              button.button.button--secondary.label-text(
                v-if="index !== 0"
                type="button"
                :disabled="!item.canBecomeCandidate || makeCandidate.isPending.value"
                @click="promote(item)"
                :aria-label="t('books.promoteAria')"
                :title="t('books.promoteTitle')"
              )
                BookCheck.proposal__button-icon
              button.button.button--secondary.label-text(
                v-if="isEditable(item)"
                type="button"
                :disabled="updateQueueItem.isPending.value"
                @click="startEdit(item)"
                :aria-label="t('books.editAria')"
                :title="t('books.editTitle')"
              )
                Pencil.proposal__button-icon
              button.button.button--secondary.label-text(
                type="button"
                :disabled="removeQueueItem.isPending.value || item.status === 'in_verification'"
                @click="removeQueueItem.mutate(item.id)"
                :aria-label="t('books.deleteAria')"
                :title="t('books.deleteTitle')"
              )
                Trash2.proposal__button-icon
        p.proposal__error(v-if="makeCandidate.error.value") {{ $t('books.promoteError') }}

      section.panel(v-if="activeTab === 'rejected' && rejectedQuery.isLoading.value")
        p.body-text {{ $t('common.loadingList') }}
      section.panel(v-else-if="activeTab === 'rejected' && rejectedQuery.error.value")
        p.body-text {{ $t('common.errorList') }}
      section.panel.proposal__empty(v-else-if="activeTab === 'rejected' && rejectedItems.length === 0")
        BookMarked.proposal__empty-icon
        h3 {{ $t('books.rejectedTitle') }}
        p.body-text {{ $t('books.rejectedText') }}
      .proposal__book-list(v-else-if="activeTab === 'rejected'")
        TransitionGroup.proposal__book-items(name="list" tag="div")
          article.proposal__book.proposal__book--rejected(v-for="item in rejectedItems" :key="item.id")
            .proposal__book-cover(
              :style="{ '--cover-color': item.coverColor ?? undefined }"
              :aria-label="$t('archive.coverAria', { title: item.title })"
            )
              img.proposal__book-cover-image(v-if="item.coverUrl" :src="item.coverUrl" :alt="item.title")
              span.proposal__book-cover-title(v-else) {{ item.title }}
            .proposal__book-header
              .proposal__book-title-wrap
                h3.proposal__book-title {{ item.title }}
                p.proposal__book-author {{ item.author }}
              span.badge.badge--sm.badge--danger {{ $t('books.rejectedBadge') }}
            .proposal__book-content(v-if="item.description || item.rejectionInfo")
              p.proposal__book-meta(v-if="item.description") {{ item.description }}
              .proposal__book-rejection(v-if="item.rejectionInfo")
                | {{ $t('books.rejectedBy', { date: formatDate(item.rejectionInfo.rejectedAt) }) }}
                template(v-if="item.rejectionInfo.rejectedByMembers.length")
                  | · {{ $t('books.readBy', { members: item.rejectionInfo.rejectedByMembers.join(', ') }) }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

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
  display: flex;
  flex-direction: column;
  gap: var(--space-xl);
  align-items: start;

  @include desktop {
    display: grid;
    grid-template-columns: minmax(18rem, 0.85fr) minmax(0, 1.4fr);
  }
}

.proposal__form-panel,
.proposal__queue {
  width: 100%;
}

.proposal__toggle-form {
  margin-bottom: var(--space-md);
  align-self: flex-start;
}

/* Expand transition for the form panel */
.expand-enter-active,
.expand-leave-active {
  transition:
    max-height 0.35s ease,
    opacity 0.25s ease,
    padding 0.25s ease,
    margin 0.25s ease;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
  padding-top: 0;
  padding-bottom: 0;
  margin-top: 0;
  margin-bottom: 0;
  border-width: 0;
}

.expand-enter-to,
.expand-leave-from {
  max-height: 100vh;
  opacity: 1;
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
  padding: 0.75rem 0.9rem;
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

.proposal__text-input {
  width: 100%;
  padding: 0.65rem 0.9rem;
}

.proposal__actions {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: var(--space-sm);
  margin-top: var(--space-xl);
}

.proposal__actions-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: var(--space-sm);
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
  grid-template-columns: auto minmax(0, 1fr);
  grid-template-areas:
    'cover header'
    'content content'
    'actions actions';
  gap: var(--space-sm) var(--space-md);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.026), rgba(255, 255, 255, 0.01)), var(--bg-panel);
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease,
    box-shadow 0.2s ease;

  @include tablet {
    grid-template-columns: auto minmax(0, 1fr) auto;
    grid-template-areas:
      'cover header actions'
      'cover content actions';
  }
}

.proposal__book:hover {
  transform: translateY(-2px);
  box-shadow:
    var(--shadow-panel),
    0 0.5rem 2rem rgba(0, 0, 0, 0.18);
  border-color: var(--border-strong);
}

.proposal__book--active {
  border-color: var(--accent-border);
  background:
    linear-gradient(180deg, rgba(67, 224, 125, 0.055), rgba(67, 224, 125, 0.018)), var(--bg-panel);
}

.proposal__book--next {
  border-color: var(--warn-border);
  background:
    linear-gradient(180deg, rgba(216, 137, 43, 0.07), rgba(216, 137, 43, 0.018)), var(--bg-panel);
}

.proposal__book--rejected {
  grid-template-columns: auto minmax(0, 1fr);
  grid-template-areas:
    'cover header'
    'content content';

  @include tablet {
    grid-template-columns: auto minmax(0, 1fr);
    grid-template-areas:
      'cover header'
      'cover content';
  }
}

.proposal__book-cover {
  grid-area: cover;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  align-self: start;
  aspect-ratio: 9 / 13;
  width: 4.5rem;
  padding: var(--space-xs);
  overflow: hidden;
  border: var(--border-width) solid rgba(255, 255, 255, 0.14);
  border-radius: var(--radius-inner);
  background:
    radial-gradient(circle at 62% 22%, rgba(255, 255, 255, 0.16), transparent 0.9rem),
    linear-gradient(135deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.035)),
    var(--cover-color);
  box-shadow: inset 0.8rem 0 1.3rem rgba(0, 0, 0, 0.18);
  color: rgba(255, 255, 255, 0.72);
  text-align: center;
  font-family: var(--font-mono);
  font-size: 0.6rem;
  line-height: 1.4;

  @include tablet {
    width: 6.5rem;
  }
}

.proposal__book-cover::after {
  position: absolute;
  inset: 0 0 0 10%;
  content: '';
  background: linear-gradient(
    to right,
    rgba(255, 255, 255, 0.11) 0%,
    rgba(255, 255, 255, 0) 5%,
    rgba(0, 0, 0, 0.1) 100%
  );
}

.proposal__book-cover-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.proposal__book-cover-title {
  position: relative;
  z-index: 1;
  max-width: 3.5rem;
  font-size: 0.58rem;
  font-weight: 500;
  letter-spacing: 0.1em;
  line-height: 1.5;
  text-transform: uppercase;
  white-space: pre-line;
}

.proposal__book-header {
  grid-area: header;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: var(--space-sm);
  min-width: 0;
}

.proposal__book-title-wrap {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
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
  margin: 0;
  font-size: 0.85rem;
  color: var(--text-muted);
  line-height: 1.3;
  overflow-wrap: break-word;
}

.proposal__book-content,
.proposal__book-edit {
  grid-area: content;
  font-size: 0.82rem;
  color: var(--text-muted);
  line-height: 1.4;
  overflow-wrap: break-word;
}

.proposal__book-content {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm) var(--space-md);
}

.proposal__book-meta {
  margin: 0;
}

.proposal__book-rejection {
  margin: 0;
}

.proposal__book-rejection {
  padding-top: var(--space-xs);
  border-top: var(--border-width) solid var(--border);
  font-family: var(--font-mono);
  font-size: 0.8rem;
}

.proposal__book-genres {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-xs) var(--space-sm);
  margin-top: var(--space-xs);
}

.proposal__book-genres .badge {
  font-size: 0.6rem;
  text-transform: none;
  letter-spacing: 0;
  padding: 0.15rem 0.45rem;
}

.proposal__book-actions {
  grid-area: actions;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: var(--space-xs);
  padding: var(--space-xs);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  align-self: start;
  width: auto;
  justify-self: end;

  @include tablet {
    flex-direction: column;
    align-items: center;
    justify-self: auto;
  }
}

.proposal__book-actions .button {
  min-height: 2.25rem;
  min-width: 2.25rem;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  touch-action: manipulation;

  @include mobile-only {
    min-height: 2.75rem;
    min-width: 2.75rem;
  }
}

.proposal__action-text {
  display: none;
}

.proposal__edit-actions {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-top: var(--space-sm);

  @include tablet {
    flex-direction: row;
  }
}

.proposal__edit-actions .button {
  width: 100%;
  justify-content: center;

  @include tablet {
    width: auto;
  }
}

.proposal__queue-summary {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);

  @include tablet {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
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
</style>
