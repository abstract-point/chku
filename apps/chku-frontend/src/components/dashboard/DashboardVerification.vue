<script setup lang="ts">
import type { BookChoiceEvent } from '@/types/club'

defineProps<{
  choice: BookChoiceEvent
}>()
</script>

<template lang="pug">
section.dashboard__verification.container(aria-labelledby="verification-title")
  div
    span.label-text.dashboard__verification-label Требуется действие
    h2#verification-title.dashboard__verification-title Проверка книги ожидает ответа
    p.dashboard__verification-text
      | {{ choice.proposerName }} предложила «{{ choice.bookTitle }}». Ты уже читал(а) эту книгу?
  .dashboard__verification-actions
    button.button.button--secondary.button--light.label-text(type="button") Я читал(а)
    button.button.button--inverted.label-text(type="button") Не читал(а)
</template>

<style scoped>
.dashboard__verification {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  margin-bottom: var(--space-lg);
  padding: var(--space-md) var(--space-lg);
  background: var(--color-accent);
  color: var(--color-surface-light);
}

.dashboard__verification-label,
.dashboard__verification-text {
  color: rgba(255, 255, 255, 0.86);
}

.dashboard__verification-title {
  margin-top: var(--space-xs);
  color: var(--color-surface-light);
  font-family: var(--font-sans);
  font-size: 1rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  line-height: 1.3;
  text-transform: uppercase;
}

.dashboard__verification-text {
  margin-top: var(--space-xs);
  font-size: 0.8rem;
  line-height: 1.5;
}

.dashboard__verification-actions {
  display: flex;
  flex: 0 0 auto;
  gap: var(--space-sm);
}

@media (max-width: 760px) {
  .dashboard__verification {
    align-items: stretch;
    flex-direction: column;
    padding: var(--space-md);
  }

  .dashboard__verification-actions {
    flex-direction: column;
  }
}
</style>
