<script setup lang="ts">
defineProps<{
  variant?: 'warn' | 'info'
}>()
</script>

<template lang="pug">
section.app-banner(:class="`app-banner--${variant ?? 'warn'}`" aria-labelledby="banner-title")
  .app-banner__status(aria-hidden="true")
    slot(name="icon")
  .app-banner__content
    slot(name="content")
  .app-banner__actions(v-if="$slots.actions")
    slot(name="actions")
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.app-banner {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: var(--space-lg);
  padding: var(--space-lg);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(216, 137, 43, 0.08), rgba(216, 137, 43, 0.018)), var(--bg-panel);

  @include tablet {
    flex-direction: row;
    align-items: center;
    padding: var(--space-lg) var(--space-xl);
  }
}

.app-banner--info {
  border: var(--border-width) solid var(--border);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.014)), var(--bg-panel);
  box-shadow: var(--shadow-panel);
  color: var(--text-main);
}

.app-banner--warn {
  border: var(--border-width) solid var(--warn-border);
}

.app-banner__status {
  flex: 0 0 auto;
  color: var(--warn);
}

.app-banner--info .app-banner__status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 3.1rem;
  height: 3.1rem;
  border: 1px solid var(--accent-border);
  border-radius: 50%;
  background: var(--accent-bg);
  color: var(--accent);
}

.app-banner__content {
  flex: 1;
  min-width: 0;
}

.app-banner__actions {
  display: flex;
  flex: 0 0 auto;
  flex-direction: column;
  gap: var(--space-sm);

  @include tablet {
    flex-direction: row;
  }
}

.app-banner__actions .button {
  width: 100%;

  @include tablet {
    width: auto;
  }
}
</style>
