<script setup lang="ts">
import { Plus } from '@lucide/vue'
import DiscussionComposer from '@/components/discussion/DiscussionComposer.vue'
import DiscussionMessageItem from '@/components/discussion/DiscussionMessageItem.vue'
import type { DiscussionMessage } from '@/types/dashboard'

const props = defineProps<{
  discussion: DiscussionMessage[]
  isSubmitting?: boolean
  readonly?: boolean
}>()

const emit = defineEmits<{
  create: [text: string]
  reply: [parentId: number, text: string]
}>()

const showNewComposer = ref(false)
</script>

<script lang="ts">
import { ref } from 'vue'
</script>

<template lang="pug">
section.discussion-block(aria-labelledby="discussion-title")
  .section-header
    h2#discussion-title {{ $t('discussion.title') }}
    span.label-text {{ $t('discussion.subtitle') }}

  .discussion-block__empty(v-if="!discussion.length && !showNewComposer")
    p.body-text {{ $t('discussion.empty') }}
    button.button.button--secondary.label-text(
      v-if="!readonly"
      type="button"
      @click="showNewComposer = true"
    ) {{ $t('discussion.addMessage') }}

  .discussion-block__list(v-if="discussion.length")
    DiscussionMessageItem(
      v-for="message in discussion"
      :key="message.id"
      :message="message"
      :readonly="readonly"
      :is-submitting="isSubmitting"
      @reply="(parentId, text) => emit('reply', parentId, text)"
    )

    button.discussion-block__add-btn(
      v-if="!readonly && !showNewComposer"
      type="button"
      @click="showNewComposer = true"
    )
      Plus(:size="14")
      | {{ $t('discussion.addMessage') }}

  .discussion-block__composer(v-if="showNewComposer")
    DiscussionComposer(
      :placeholder="$t('discussion.newPlaceholder')"
      :submit-label="$t('discussion.addMessage')"
      :is-submitting="isSubmitting"
      @submit="(text) => { emit('create', text); showNewComposer = false }"
      @cancel="showNewComposer = false"
    )
</template>

<style scoped lang="scss">
.discussion-block {
  display: grid;
  gap: var(--space-lg);
}

.discussion-block__empty {
  padding: var(--space-lg) 0;
  text-align: center;
  display: grid;
  gap: var(--space-md);
  justify-items: center;
}

.discussion-block__list {
  display: grid;
  gap: var(--space-md);
}

.discussion-block__add-btn {
  justify-self: start;
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
  padding: 0;
  border: none;
  background: transparent;
  color: var(--text-muted);
  font-family: var(--font-mono);
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  cursor: pointer;
  transition: color 0.15s ease;
}

.discussion-block__add-btn:hover {
  color: var(--accent);
}

.discussion-block__composer {
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  padding: var(--space-md);
  background: var(--bg-surface);
}
</style>