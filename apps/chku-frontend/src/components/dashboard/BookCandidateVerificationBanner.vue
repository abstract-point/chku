<script setup lang="ts">
import { computed } from 'vue'
import { useCandidateResponseMutation } from '@/queries/candidateQueries'
import type { BookChoiceEvent } from '@/types/club'

const props = defineProps<{
  choice: BookChoiceEvent
}>()

const responseMutation = useCandidateResponseMutation()
const isPending = computed(() => responseMutation.isPending.value)

function respond(response: 'read' | 'not_read' | 'not_sure') {
  responseMutation.mutate({
    candidateId: props.choice.id,
    response,
  })
}
</script>

<template lang="pug">
section.book-candidate-banner(aria-labelledby="verification-title")
  div
    span.label-text.book-candidate-banner__label Требуется действие
    h2#verification-title.book-candidate-banner__title Ожидает проверки: «{{ choice.bookTitle }}»
    p.book-candidate-banner__text
      | {{ choice.proposerName }} предложила «{{ choice.bookTitle }}» — {{ choice.author }}. Ты уже читал(а) эту книгу?
  .book-candidate-banner__actions
    button.button.button--secondary.label-text(type="button" :disabled="isPending" @click="respond('read')") Я читал(а)
    button.button.button--secondary.label-text(type="button" :disabled="isPending" @click="respond('not_sure')") Не уверен(а)
    button.button.button--inverted.label-text(type="button" :disabled="isPending" @click="respond('not_read')") Не читал(а)
</template>

<style scoped>
.book-candidate-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  margin-bottom: var(--space-xl);
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  background: var(--bg-panel);
  color: var(--text-main);
}

.book-candidate-banner__label,
.book-candidate-banner__text {
  color: var(--text-muted);
}

.book-candidate-banner__title {
  margin-top: 0.35rem;
  color: var(--text-main);
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: 0;
  line-height: 1.3;
}

.book-candidate-banner__text {
  margin-top: var(--space-xs);
  font-size: 0.75rem;
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
  }

  .book-candidate-banner__actions {
    flex-direction: column;
  }
}
</style>
