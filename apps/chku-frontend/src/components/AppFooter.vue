<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useClubStore } from '@/stores/club'

const club = useClubStore()
const currentYear = computed(() => new Date().getFullYear())
</script>

<template lang="pug">
footer.app-footer
  .container.app-footer__inner
    .app-footer__brand
      span.app-footer__brand-name {{ club.name }}
      span.app-footer__copy © {{ currentYear }}
    nav.app-footer__nav(aria-label="Нижняя навигация")
      RouterLink(to="/") Дашборд
      RouterLink(to="/archive") Архив
      RouterLink(to="/profile") Профиль
</template>

<style scoped>
.app-footer {
  padding-top: var(--space-lg);
  padding-bottom: var(--space-lg);
  border-top: var(--border-width) solid var(--border);
}

.app-footer__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  flex-wrap: wrap;
}

.app-footer__brand {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.app-footer__brand-name {
  color: var(--text-muted);
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.app-footer__copy {
  color: var(--text-subtle);
  font-size: 0.7rem;
}

.app-footer__nav {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  flex-wrap: wrap;
}

.app-footer__nav a {
  color: var(--text-muted);
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  transition: color 0.2s ease;
  white-space: nowrap;
}

.app-footer__nav a:hover,
.app-footer__nav a.router-link-exact-active {
  color: var(--text-main);
}

@media (max-width: 760px) {
  .app-footer__inner {
    align-items: flex-start;
    flex-direction: column;
  }

  .app-footer__nav {
    width: 100%;
    gap: var(--space-md);
    overflow-x: auto;
    padding-bottom: var(--space-xs);
  }
}
</style>
