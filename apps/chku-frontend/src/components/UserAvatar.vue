<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = withDefaults(
  defineProps<{
    name: string
    avatarUrl?: string | null
    size?: 'sm' | 'md' | 'lg'
    to?: string
    ariaLabel?: string
  }>(),
  {
    avatarUrl: null,
    size: 'md',
    to: undefined,
    ariaLabel: undefined,
  },
)

const imageFailed = ref(false)

const initials = computed(() =>
  props.name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .toUpperCase(),
)

const shouldShowImage = computed(() => Boolean(props.avatarUrl) && !imageFailed.value)
const component = computed(() => (props.to ? RouterLink : 'span'))
const label = computed(() => props.ariaLabel ?? `${t('settings.avatar')} ${props.name}`)

watch(
  () => props.avatarUrl,
  () => {
    imageFailed.value = false
  },
)
</script>

<template lang="pug">
component.user-avatar(
  :is="component"
  :to="to"
  :class="`user-avatar--${size}`"
  :aria-label="label"
)
  img.user-avatar__image(
    v-if="shouldShowImage"
    :src="avatarUrl ?? undefined"
    :alt="label"
    loading="lazy"
    @error="imageFailed = true"
  )
  span.user-avatar__initials(v-else aria-hidden="true") {{ initials }}
</template>

<style scoped>
.user-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: 0.45rem;
  background: var(--bg-panel);
  color: var(--text-muted);
  font-family: var(--font-mono);
  font-weight: 700;
  text-decoration: none;
  text-transform: uppercase;
}

.user-avatar--sm {
  width: 1.75rem;
  height: 1.75rem;
  font-size: 0.58rem;
}

.user-avatar--md {
  width: 2.25rem;
  height: 2.25rem;
  font-size: 0.66rem;
}

.user-avatar--lg {
  width: 6rem;
  height: 6rem;
  border-radius: var(--radius-inner);
  background: var(--text-main);
  color: var(--bg-base);
  font-size: 1.6rem;
}

.user-avatar__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-avatar__initials {
  display: inline-flex;
  max-width: 100%;
  padding: 0 0.2rem;
  overflow: hidden;
  line-height: 1;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
