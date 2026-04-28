<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template lang="pug">
section.two-factor-banner(aria-labelledby="two-factor-required-title")
  .two-factor-banner__content
    div
      h2#two-factor-required-title.two-factor-banner__title Включите 2FA
      p.two-factor-banner__text
        | Для управления участниками Админу и Разработчику нужна двухфакторная защита.
    RouterLink.button.button--primary.label-text.two-factor-banner__action(to="/profile/settings")
      | Настроить 2FA
</template>

<style scoped>
.two-factor-banner {
  margin-bottom: var(--space-xl);
  border: var(--border-width) solid var(--warn);
  background: var(--warn-bg);
}

.two-factor-banner__content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  padding: var(--space-lg);
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
    align-items: stretch;
    flex-direction: column;
  }

  .two-factor-banner__action {
    width: 100%;
  }
}
</style>
