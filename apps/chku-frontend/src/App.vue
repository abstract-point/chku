<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import BookCandidateVerificationBanner from '@/components/dashboard/BookCandidateVerificationBanner.vue'
import { useClubStore } from '@/stores/club'

const club = useClubStore()
</script>

<template lang="pug">
.app-shell
  header.app-header.container
    RouterLink.app-header__brand(to="/")
      span.app-header__brand-mark {{ club.shortName }}
      span.app-header__brand-name {{ club.name }}

    nav.app-header__nav(aria-label="Основная навигация")
      RouterLink.label-text(to="/") Дашборд
      RouterLink.label-text(to="/archive") Архив
      RouterLink.label-text(to="/profile") Профиль

  BookCandidateVerificationBanner(v-if="club.activeBookChoice" :choice="club.activeBookChoice")
  RouterView
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  padding-bottom: var(--space-xl);
}

.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  padding-top: var(--space-lg);
  padding-bottom: var(--space-lg);
  margin-bottom: var(--space-lg);
  border-bottom: var(--border-width) solid var(--color-border);
}

.app-header__brand {
  display: inline-flex;
  align-items: center;
  gap: var(--space-md);
  color: var(--color-heading);
  text-decoration: none;
}

.app-header__brand-mark {
  display: grid;
  width: 2.75rem;
  height: 2.75rem;
  place-items: center;
  border: var(--border-width) solid var(--color-border-strong);
  color: var(--color-heading);
  font-family: var(--font-sans);
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.1em;
}

.app-header__brand-name {
  font-family: var(--font-serif);
  font-size: 1.5rem;
  letter-spacing: 0.05em;
  line-height: 1;
  text-transform: uppercase;
}

.app-header__nav {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
}

.app-header__nav a {
  color: var(--color-heading);
  transition: color 0.2s ease;
}

.app-header__nav a:hover,
.app-header__nav a.router-link-exact-active {
  color: var(--color-accent);
}

@media (max-width: 760px) {
  .app-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .app-header__nav {
    width: 100%;
    gap: var(--space-md);
    overflow-x: auto;
    padding-bottom: var(--space-xs);
  }

  .app-header__brand-name {
    font-size: 1.2rem;
  }
}
</style>
