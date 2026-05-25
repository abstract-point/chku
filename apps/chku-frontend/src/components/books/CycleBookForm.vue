<script setup lang="ts">
import { reactive, watch } from 'vue'
import { Save, X } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppTextarea from '@/components/ui/AppTextarea.vue'
import BookCoverPicker from '@/components/books/BookCoverPicker.vue'
import { useFormErrors } from '@/composables/useFormErrors'
import { useUpdateCycleBookMutation } from '@/queries/cycleQueries'

const props = defineProps<{
  cycleNumber: number | string
  book: {
    title: string
    author: string
    description?: string | null
    coverUrl?: string | null
    genre?: {
      id: number
    } | null
  }
  idPrefix: string
}>()

const emit = defineEmits<{
  cancel: []
  saved: []
}>()

const { t } = useI18n()
const updateBook = useUpdateCycleBookMutation(() => props.cycleNumber)
const formErrors = useFormErrors()

const form = reactive({
  title: '',
  author: '',
  description: '',
  genreId: null as number | null,
  coverFile: null as File | null,
})

  watch(
  () => props.book,
  (book) => {
    form.title = book.title
    form.author = book.author
    form.description = book.description ?? ''
    form.genreId = book.genre?.id ?? null
    form.coverFile = null
  },
  { immediate: true },
)

function saveBook() {
  formErrors.clearAllErrors()
  updateBook.mutate(
    {
      title: form.title.trim(),
      author: form.author.trim(),
      description: form.description.trim() || null,
      genreId: form.genreId,
      coverFile: form.coverFile,
    },
    {
      onSuccess: () => {
        emit('saved')
      },
      onError: (error) => {
        formErrors.setFromApiError(error)
      },
    },
  )
}

function cancel() {
  updateBook.reset()
  formErrors.clearAllErrors()
  emit('cancel')
}
</script>

<template lang="pug">
form.cycle-book-form(@submit.prevent="saveBook" novalidate)
  AppFormField(:label="t('books.titleLabel')" :label-for="`${idPrefix}-title`" required :error="formErrors.getError('title')")
    input.field-control.cycle-book-form__input(
      :id="`${idPrefix}-title`"
      v-model="form.title"
      type="text"
      :placeholder="t('books.titlePlaceholder')"
      :aria-invalid="formErrors.hasError('title')"
    )
  AppFormField(:label="t('books.authorLabel')" :label-for="`${idPrefix}-author`" required :error="formErrors.getError('author')")
    input.field-control.cycle-book-form__input(
      :id="`${idPrefix}-author`"
      v-model="form.author"
      type="text"
      :placeholder="t('books.authorPlaceholder')"
      :aria-invalid="formErrors.hasError('author')"
    )
  AppFormField(:label="t('books.descLabel')" :label-for="`${idPrefix}-description`" :error="formErrors.getError('description')")
    AppTextarea(
      :id="`${idPrefix}-description`"
      v-model="form.description"
      :placeholder="t('books.descPlaceholder')"
      :aria-invalid="formErrors.hasError('description')"
    )
  BookCoverPicker(
    v-model:coverFile="form.coverFile"
    :title="form.title"
    :author="form.author"
  )
  .cycle-book-form__actions
    button.button.button--primary.label-text(type="submit" :disabled="updateBook.isPending.value")
      Save.cycle-book-form__icon
      | {{ updateBook.isPending.value ? $t('books.saving') : $t('books.save') }}
    button.button.button--secondary.label-text(type="button" :disabled="updateBook.isPending.value" @click="cancel")
      X.cycle-book-form__icon
      | {{ $t('books.cancel') }}
  p.cycle-book-form__error(v-if="updateBook.error.value && !Object.keys(formErrors.fieldErrors.value).length")
    | {{ updateBook.error.value.message }}
</template>

<style scoped>
.cycle-book-form {
  display: grid;
  gap: var(--space-md);
}

.cycle-book-form__input {
  width: 100%;
  padding-left: 1rem;
}

.cycle-book-form__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
}

.cycle-book-form__icon {
  width: 1rem;
  height: 1rem;
}

.cycle-book-form__error {
  color: var(--danger);
  font-size: 0.85rem;
}
</style>
