<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { useClubStore } from '@/stores/club'

const club = useClubStore()
</script>

<template lang="pug">
.app-shell
  header.app-header
    RouterLink.brand-link(to="/")
      span.brand-mark {{ club.shortName }}
      span.brand-name {{ club.name }}

    nav.app-nav(aria-label="Основная навигация")
      RouterLink(to="/") Главная

  RouterView
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
}

.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  width: min(100%, 1120px);
  margin: 0 auto;
  padding: 1.25rem 1.5rem;
}

.brand-link {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--color-heading);
  font-weight: 700;
  text-decoration: none;
}

.brand-mark {
  display: grid;
  width: 2.75rem;
  height: 2.75rem;
  place-items: center;
  border-radius: 0.5rem;
  background: var(--color-accent);
  color: var(--color-accent-text);
  font-size: 0.9rem;
  font-weight: 700;
}

.brand-name {
  font-size: 1rem;
  font-weight: 700;
}

.app-nav {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.app-nav a {
  color: var(--color-muted);
  font-weight: 600;
  text-decoration: none;
}

.app-nav a.router-link-exact-active {
  color: var(--color-heading);
}
</style>
