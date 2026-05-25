<script setup lang="ts">
import { computed, ref } from 'vue'
import { ImageUp, X } from '@lucide/vue'

const props = defineProps<{
  coverFile?: File | null
}>()

const emit = defineEmits<{
  'update:coverFile': [value: File | null]
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const uploadedPreviewUrl = computed(() => {
  if (props.coverFile) {
    return URL.createObjectURL(props.coverFile)
  }
  return null
})

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  emit('update:coverFile', file)
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function triggerFileInput() {
  fileInput.value?.click()
}

function removeUploadedFile() {
  emit('update:coverFile', null)
}
</script>

<template lang="pug">
.book-cover-picker
  .book-cover-picker__upload
    input.book-cover-picker__file-input(
      ref="fileInput"
      type="file"
      accept="image/*"
      @change="onFileChange"
    )
    template(v-if="!coverFile")
      button.button.button--secondary.label-text(type="button" @click="triggerFileInput")
        ImageUp.book-cover-picker__upload-icon
        | {{ $t('books.uploadCover') }}
    template(v-else)
      .book-cover-picker__upload-preview
        img(:src="uploadedPreviewUrl || ''" :alt="$t('books.uploadedCover')")
        button.book-cover-picker__remove-upload(type="button" @click="removeUploadedFile")
          X(:size="14" aria-hidden="true")
          span.sr-only {{ $t('books.removeCover') }}
</template>

<style scoped>
.book-cover-picker {
  display: grid;
  gap: var(--space-md);
}

.book-cover-picker__upload {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.book-cover-picker__file-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.book-cover-picker__upload-icon {
  width: 1rem;
  height: 1rem;
}

.book-cover-picker__upload-preview {
  position: relative;
  width: 4.2rem;
  aspect-ratio: 2 / 3;
  border-radius: var(--radius-inner);
  overflow: hidden;
  border: var(--border-width) solid var(--border);
}

.book-cover-picker__upload-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.book-cover-picker__remove-upload {
  position: absolute;
  top: 0.2rem;
  right: 0.2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.2rem;
  height: 1.2rem;
  border-radius: 999px;
  background: var(--danger);
  color: var(--bg-base);
  border: none;
  cursor: pointer;
  padding: 0;
}
</style>
