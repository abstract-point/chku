<script setup lang="ts">
import UserAvatar from '@/components/UserAvatar.vue'
import type { MeetingAttendee } from '@/types/dashboard'

defineProps<{
  member: MeetingAttendee
}>()
</script>

<template lang="pug">
.member-tooltip
  UserAvatar(:name="member.name" :avatar-url="member.avatarUrl" :to="`/members/${member.id}`" size="sm")
  .member-tooltip__popover
    .member-tooltip__name {{ member.name }}
    .member-tooltip__meta(v-if="member.favoriteGenre")
      span {{ member.favoriteGenre }}
    .member-tooltip__meta(v-if="member.memberSince")
      span {{ $t('memberDetail.memberSince', { year: member.memberSince }) }}
</template>

<style scoped>
.member-tooltip {
  position: relative;
  display: inline-flex;
  cursor: pointer;
}

.member-tooltip::before {
  content: '';
  position: absolute;
  bottom: 100%;
  left: 0;
  right: 0;
  height: 5rem;
  z-index: 99;
}

.member-tooltip__popover {
  position: absolute;
  bottom: calc(100% + 0.5rem);
  left: 50%;
  transform: translateX(-50%);
  z-index: 100;
  display: none;
  flex-direction: column;
  gap: var(--space-xs);
  min-width: 11rem;
  padding: var(--space-md);
  border: var(--border-width) solid var(--border-strong);
  border-radius: var(--radius-inner);
  background: var(--bg-elevated);
  box-shadow: var(--shadow-panel);
  white-space: nowrap;
}

.member-tooltip:hover .member-tooltip__popover {
  display: flex;
}

.member-tooltip__name {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-main);
}

.member-tooltip__link {
  text-decoration: none;
  color: inherit;
}

.member-tooltip__link:hover {
  color: var(--accent);
}

.member-tooltip__meta {
  font-size: 0.8rem;
  color: var(--text-muted);
  line-height: 1.4;
}

.member-tooltip__profile-link {
  margin-top: var(--space-xs);
  font-size: 0.78rem;
  color: var(--accent);
  text-decoration: none;
}

.member-tooltip__profile-link:hover {
  text-decoration: underline;
}
</style>
