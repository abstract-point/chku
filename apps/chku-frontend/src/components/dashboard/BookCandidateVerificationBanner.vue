<script setup lang="ts">
import type { BookChoiceEvent } from '@/types/club'

defineProps<{
  choice: BookChoiceEvent
}>()
</script>

<template lang="pug">
section.book-candidate-banner.container(aria-labelledby="verification-title")
  div
    span.label-text.book-candidate-banner__label Требуется действие
    h2#verification-title.book-candidate-banner__title Проверка книги ожидает ответа
    p.book-candidate-banner__text
      | {{ choice.proposerName }} предложила «{{ choice.bookTitle }}» — {{ choice.author }}. Ты уже читал(а) эту книгу?
  .book-candidate-banner__actions
    button.button.button--secondary.button--light.label-text(type="button") Я читал(а)
    button.button.button--inverted.label-text(type="button") Не читал(а)
</template>

<style scoped>
.book-candidate-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  margin-bottom: var(--space-lg);
  padding: var(--space-md) var(--space-lg);
  background: var(--color-accent);
  color: var(--color-surface-light);
}

.book-candidate-banner__label,
.book-candidate-banner__text {
  color: rgba(255, 255, 255, 0.86);
}

.book-candidate-banner__title {
  margin-top: var(--space-xs);
  color: var(--color-surface-light);
  font-family: var(--font-sans);
  font-size: 1rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  line-height: 1.3;
  text-transform: uppercase;
}

.book-candidate-banner__text {
  margin-top: var(--space-xs);
  font-size: 0.8rem;
  line-height: 1.5;
}

.book-candidate-banner__actions {
  display: flex;
  flex: 0 0 auto;
  gap: var(--space-sm);
}

@media (max-width: 760px) {
  .book-candidate-banner {
    align-items: stretch;
    flex-direction: column;
    padding: var(--space-md);
  }

  .book-candidate-banner__actions {
    flex-direction: column;
  }
}
</style>
