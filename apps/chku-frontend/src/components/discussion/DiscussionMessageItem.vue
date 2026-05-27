<script setup lang="ts">
import { CornerDownRight } from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import DiscussionComposer from '@/components/discussion/DiscussionComposer.vue'
import { formatRelativeDate } from '@/mappers/date'
import type { DiscussionMessage } from '@/types/dashboard'

const props = defineProps<{
  message: DiscussionMessage
  isSubmitting?: boolean
}>()

const emit = defineEmits<{
  reply: [parentId: number, text: string]
}>()

const showReplyComposer = ref(false)
const formattedDate = computed(() => formatRelativeDate(props.message.createdAt))
</script>

<script lang="ts">
import { ref, computed } from 'vue'
</script>

<template lang="pug">
.discussion-item(:class="{ 'discussion-item--reply': message.parentId !== null }")
  .discussion-item__row
    .discussion-item__header
      UserAvatar.discussion-item__avatar(:name="message.memberName" :avatar-url="message.memberAvatarUrl" size="sm")
      span.discussion-item__name {{ message.memberName }}
      time.discussion-item__time(v-if="formattedDate") {{ formattedDate }}
    p.discussion-item__text {{ message.text }}

  .discussion-item__actions(v-if="message.canReply")
    button.discussion-item__reply-btn(
      type="button"
      @click="showReplyComposer = !showReplyComposer"
    ) {{ showReplyComposer ? $t('discussion.cancel') : $t('discussion.reply') }}

  .discussion-item__composer(v-if="showReplyComposer")
    DiscussionComposer(
      :placeholder="$t('discussion.replyPlaceholder')"
      :submit-label="$t('discussion.reply')"
      :is-submitting="isSubmitting"
      @submit="(text) => { emit('reply', message.id, text); showReplyComposer = false }"
      @cancel="showReplyComposer = false"
    )

  .discussion-item__replies(v-if="message.replies?.length")
    DiscussionMessageItem(
      v-for="reply in message.replies"
      :key="reply.id"
      :message="reply"
      :is-submitting="isSubmitting"
      @reply="(parentId, text) => emit('reply', parentId, text)"
    )
</template>

<style scoped lang="scss">
.discussion-item {
  display: grid;
  gap: var(--space-xs);
}

.discussion-item--reply {
  .discussion-item__row {
    padding-left: var(--space-md);
    border-left: 2px solid var(--accent-border);
  }
}

.discussion-item__row {
  display: grid;
  gap: var(--space-xs);
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
  border: var(--border-width) solid var(--border);
}

.discussion-item--reply .discussion-item__row {
  background: transparent;
  border-color: transparent;
  padding: var(--space-xs) var(--space-sm) var(--space-xs) var(--space-md);
  border-radius: 0;
}

.discussion-item__header {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  min-height: 1.75rem;
}

.discussion-item__avatar {
  flex-shrink: 0;
}

.discussion-item__name {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text-muted);
  white-space: nowrap;
}

.discussion-item__time {
  font-family: var(--font-mono);
  font-size: 0.62rem;
  color: var(--text-subtle);
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.discussion-item__text {
  font-size: 0.88rem;
  line-height: 1.6;
  color: var(--text-main);
  white-space: pre-wrap;
  word-break: break-word;
  margin: 0;
  padding-left: calc(1.75rem + var(--space-sm));
}

.discussion-item--reply .discussion-item__text {
  padding-left: calc(1.75rem + var(--space-sm));
}

.discussion-item__actions {
  display: flex;
  padding-left: var(--space-md);
}

.discussion-item__reply-btn {
  font-family: var(--font-mono);
  font-size: 0.68rem;
  font-weight: 500;
  color: var(--text-subtle);
  background: none;
  border: none;
  cursor: pointer;
  padding: var(--space-xs) 0;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  transition: color 0.15s ease;
}

.discussion-item__reply-btn:hover {
  color: var(--accent);
}

.discussion-item__composer {
  margin-top: var(--space-xs);
  padding-left: var(--space-md);
}

.discussion-item__replies {
  display: grid;
  gap: var(--space-sm);
  padding-left: calc(var(--space-md) + 2px);
}

@media (max-width: 639px) {
  .discussion-item__text {
    padding-left: 0;
  }

  .discussion-item--reply .discussion-item__text {
    padding-left: 0;
  }

  .discussion-item__actions {
    padding-left: var(--space-sm);
  }

  .discussion-item__composer {
    padding-left: var(--space-sm);
  }
}
</style>