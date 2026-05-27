<script setup lang="ts">
import { MessageSquare, CornerDownRight } from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import DiscussionComposer from '@/components/discussion/DiscussionComposer.vue'
import type { DiscussionMessage } from '@/types/dashboard'

const props = defineProps<{
  message: DiscussionMessage
  isSubmitting?: boolean
}>()

const emit = defineEmits<{
  reply: [parentId: number, text: string]
}>()

const showReplyComposer = ref(false)
</script>

<script lang="ts">
import { ref } from 'vue'
</script>

<template lang="pug">
.discussion-item(:class="{ 'discussion-item--reply': message.parentId !== null }")
  .discussion-item__main
    .discussion-item__header
      .discussion-item__member
        MessageSquare.discussion-item__icon(v-if="message.parentId === null" :size="16")
        CornerDownRight.discussion-item__icon(v-else :size="14")
        UserAvatar(:name="message.memberName" :avatar-url="message.memberAvatarUrl" size="sm")
        span.discussion-item__name {{ message.memberName }}
    p.discussion-item__text {{ message.text }}

    button.discussion-item__reply-btn(
      v-if="message.canReply"
      type="button"
      @click="showReplyComposer = !showReplyComposer"
    ) {{ showReplyComposer ? $t('discussion.cancel') : $t('discussion.reply') }}

    .discussion-item__reply-composer(v-if="showReplyComposer")
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
  gap: var(--space-sm);
}

.discussion-item--reply {
  padding-top: var(--space-sm);
}

.discussion-item__header {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.discussion-item__icon {
  color: var(--text-subtle);
  flex-shrink: 0;
}

.discussion-item__name {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-subtle);
}

.discussion-item__text {
  font-size: 0.9rem;
  line-height: 1.6;
  color: var(--text-main);
  white-space: pre-wrap;
}

.discussion-item__reply-btn {
  font-size: 0.8rem;
  color: var(--text-subtle);
  padding: 0.125rem 0;
}

.discussion-item__reply-btn:hover {
  color: var(--accent);
}

.discussion-item__reply-composer {
  margin-top: var(--space-sm);
}

.discussion-item__replies {
  margin-left: var(--space-lg);
  padding-left: var(--space-md);
  border-left: 1px solid var(--border);
  display: grid;
  gap: var(--space-md);
}
</style>
