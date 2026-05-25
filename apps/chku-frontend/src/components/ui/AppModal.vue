<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
  isOpen: boolean
  title?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

function handleBackdropClick(event: MouseEvent) {
  if (event.target === event.currentTarget) {
    emit('close')
  }
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && props.isOpen) {
    emit('close')
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<template lang="pug">
Teleport(to="#modal-portal")
  Transition(name="app-modal")
    .app-modal__backdrop(v-if="isOpen" @click="handleBackdropClick")
      .app-modal(role="dialog" aria-modal="true" :aria-labelledby="title ? 'modal-title' : undefined")
        .app-modal__header(v-if="title || $slots.header")
          slot(name="header")
            h2#modal-title.app-modal__title {{ title }}
          button.app-modal__close(type="button" :aria-label="$t('common.close')" @click="$emit('close')")
            | &times;
        .app-modal__body
          slot
        .app-modal__footer(v-if="$slots.footer")
          slot(name="footer")
</template>

<style scoped>
.app-modal__backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-lg);
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(4px);
}

.app-modal {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 44rem;
  max-height: calc(100vh - var(--space-xl) * 2);
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background: var(--bg-panel);
  box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, 0.45);
}

.app-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-lg) var(--space-xl);
  border-bottom: var(--border-width) solid var(--border);
}

.app-modal__title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 600;
  line-height: 1.3;
}

.app-modal__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: none;
  border-radius: var(--radius-inner);
  background: transparent;
  color: var(--text-muted);
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  transition:
    background 0.15s ease,
    color 0.15s ease;
}

.app-modal__close:hover {
  background: var(--bg-surface);
  color: var(--text-main);
}

.app-modal__body {
  flex: 1 1 auto;
  overflow-y: auto;
  padding: var(--space-lg) var(--space-xl);
}

.app-modal__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--space-sm);
  padding: var(--space-lg) var(--space-xl);
  border-top: var(--border-width) solid var(--border);
}

@media (max-width: 640px) {
  .app-modal__backdrop {
    align-items: flex-end;
    padding: 0;
  }

  .app-modal {
    max-height: calc(100vh - var(--space-lg));
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
  }

  .app-modal__header,
  .app-modal__body,
  .app-modal__footer {
    padding: var(--space-md) var(--space-lg);
  }

  .app-modal__footer {
    flex-direction: column;
    align-items: stretch;
  }

  .app-modal__footer .button {
    width: 100%;
  }
}

.app-modal-enter-active,
.app-modal-leave-active {
  transition: opacity 0.2s ease;
}

.app-modal-enter-from,
.app-modal-leave-to {
  opacity: 0;
}
</style>
