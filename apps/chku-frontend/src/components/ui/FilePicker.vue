<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import { BookOpen, X } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import AppInput from '@/components/ui/AppInput.vue'
import UserAvatar from '@/components/UserAvatar.vue'

const props = withDefaults(
  defineProps<{
    modelValue: File | null
    id?: string
    accept?: string
    variant?: 'avatar' | 'cover'
    name?: string
    existingUrl?: string | null
    coverTitle?: string
    coverColor?: string | null
  }>(),
  {
    variant: 'avatar',
    accept: 'image/jpeg,image/png,image/webp',
    id: undefined,
    name: '',
    existingUrl: null,
    coverTitle: '',
    coverColor: null,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: File | null]
}>()

const { t } = useI18n()
const previewUrl = ref<string | null>(null)
const inputKey = ref(0)

watch(
  () => props.modelValue,
  (file, _oldFile, onCleanup) => {
    if (!file) {
      previewUrl.value = null
      return
    }
    const url = URL.createObjectURL(file)
    previewUrl.value = url
    onCleanup(() => URL.revokeObjectURL(url))
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
})

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0] ?? null
  if (file) {
    emit('update:modelValue', file)
  }
}

function remove() {
  emit('update:modelValue', null)
  inputKey.value++
}
</script>

<template lang="pug">
.file-picker
  .file-picker__row
    .file-picker__preview(
      :class="`file-picker__preview--${variant}`"
      :style="variant === 'cover' && !modelValue && !existingUrl && coverColor ? { backgroundColor: coverColor } : undefined"
    )
      template(v-if="variant === 'avatar'")
        UserAvatar(
          :name="name"
          :avatar-url="previewUrl ?? existingUrl"
          size="lg"
        )
      template(v-else)
        template(v-if="previewUrl || existingUrl")
          img.file-picker__cover-image(
            :src="previewUrl ?? existingUrl ?? undefined"
            :alt="t('books.uploadedCover')"
          )
        template(v-else)
          .file-picker__cover-placeholder
            BookOpen.file-picker__cover-icon(aria-hidden="true")
            span.file-picker__cover-placeholder-text(v-if="coverTitle") {{ coverTitle }}
            span.file-picker__cover-placeholder-text(v-else) {{ t('books.noCover') }}
      button.file-picker__remove(
        v-if="modelValue"
        type="button"
        @click="remove"
        :aria-label="t('common.removeFile')"
      )
        X(:size="14" aria-hidden="true")
    AppInput.file-picker__input(
      :key="inputKey"
      :id="id"
      type="file"
      :accept="accept"
      @change="onFileChange"
    )
</template>

<style scoped>
.file-picker {
  display: inline-block;
}

.file-picker__row {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.file-picker__preview {
  position: relative;
  flex-shrink: 0;
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
}

.file-picker__preview--avatar {
  width: 6rem;
  height: 6rem;
}

.file-picker__preview--cover {
  width: 4.2rem;
  aspect-ratio: 2 / 3;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(circle at 62% 22%, rgba(255, 255, 255, 0.16), transparent 0.9rem),
    linear-gradient(135deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.035)), var(--bg-panel);
  box-shadow: inset 0.8rem 0 1.3rem rgba(0, 0, 0, 0.18);
  color: rgba(255, 255, 255, 0.72);
  text-align: center;
}

.file-picker__cover-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.file-picker__cover-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--space-xs);
  padding: var(--space-xs);
}

.file-picker__cover-icon {
  width: 1.2rem;
  height: 1.2rem;
  opacity: 0.5;
}

.file-picker__cover-placeholder-text {
  font-family: var(--font-mono);
  font-size: 0.58rem;
  font-weight: 500;
  letter-spacing: 0.1em;
  line-height: 1.4;
  text-transform: uppercase;
  white-space: pre-line;
  max-width: 3.5rem;
}

.file-picker__remove {
  position: absolute;
  top: 0.2rem;
  right: 0.2rem;
  z-index: 1;
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

.file-picker__input {
  flex: 1;
  min-width: 0;
}

.file-picker__input :deep(.app-input[type='file']) {
  padding: 0.55rem 0.9rem;
}
</style>
