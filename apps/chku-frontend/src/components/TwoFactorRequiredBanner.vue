<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { ShieldAlert } from '@lucide/vue'
</script>

<template lang="pug">
section.two-factor-banner(aria-labelledby="two-factor-required-title")
  .two-factor-banner__content
    .two-factor-banner__icon
      ShieldAlert
    .two-factor-banner__copy
      h2#two-factor-required-title.two-factor-banner__title Включите 2FA
      p.two-factor-banner__text
        | Для управления участниками Админу и Разработчику нужна двухфакторная защита.
    RouterLink.button.button--primary.label-text.two-factor-banner__action(to="/profile/settings")
      | Настроить 2FA
</template>

<style scoped>
.two-factor-banner {
  margin-bottom: var(--space-xl);
  border: var(--border-width) solid var(--warn-border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.014)),
    var(--bg-panel);
  box-shadow: var(--shadow-panel);
}

.two-factor-banner__content {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  padding: var(--space-lg) var(--space-xl);
}

.two-factor-banner__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 3.1rem;
  height: 3.1rem;
  border: var(--border-width) solid var(--warn-border);
  border-radius: 50%;
  background: rgba(216, 137, 43, 0.08);
  color: var(--warn);
}

.two-factor-banner__icon svg {
  width: 1.25rem;
  height: 1.25rem;
}

.two-factor-banner__copy {
  flex: 1;
  min-width: 0;
}

.two-factor-banner__title {
  margin-bottom: var(--space-xs);
  font-size: 1rem;
}

.two-factor-banner__text {
  max-width: 58ch;
  color: var(--text-muted);
  font-size: 0.82rem;
  line-height: 1.6;
}

.two-factor-banner__action {
  flex: 0 0 auto;
}

@media (max-width: 640px) {
  .two-factor-banner__content {
    align-items: flex-start;
    flex-direction: column;
    padding: var(--space-lg);
  }

  .two-factor-banner__action {
    width: 100%;
  }
}
</style>
