<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import BookCandidateVerificationBanner from '@/components/dashboard/BookCandidateVerificationBanner.vue'
import { useClubStore } from '@/stores/club'

const club = useClubStore()
const theme = ref<'light' | 'dark'>('light')
const themeLabel = computed(() => (theme.value === 'dark' ? 'Светлая' : 'Тёмная'))
const themeIcon = computed(() => (theme.value === 'dark' ? '◑' : '◐'))

function getPreferredTheme() {
  const storedTheme = localStorage.getItem('chku-theme')

  if (storedTheme === 'dark' || storedTheme === 'light') {
    return storedTheme
  }

  if (!window.matchMedia) {
    return 'light'
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function applyTheme(nextTheme: 'light' | 'dark') {
  theme.value = nextTheme
  document.documentElement.setAttribute('data-theme', nextTheme)
  localStorage.setItem('chku-theme', nextTheme)
}

function toggleTheme() {
  applyTheme(theme.value === 'dark' ? 'light' : 'dark')
}

onMounted(() => {
  applyTheme(getPreferredTheme())

  window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (!localStorage.getItem('chku-theme')) {
      applyTheme(event.matches ? 'dark' : 'light')
    }
  })
})
</script>

<template lang="pug">
.app-shell
  .container
    header.app-header
      RouterLink.app-header__brand(to="/")
        span.app-header__brand-mark {{ club.shortName }}
        span.app-header__brand-name {{ club.name }}

      nav.app-header__nav(aria-label="Основная навигация")
        RouterLink(to="/") Дашборд
        RouterLink(to="/archive") Архив
        RouterLink(to="/profile") Профиль
        button.app-header__theme(type="button" aria-label="Переключить тему" @click="toggleTheme")
          span {{ themeIcon }}
          span {{ themeLabel }}

  BookCandidateVerificationBanner(v-if="club.activeBookChoice" :choice="club.activeBookChoice")
  RouterView
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  padding-bottom: var(--space-xl);
  background: var(--bg-base);
}

.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  padding-top: var(--space-lg);
  padding-bottom: var(--space-lg);
  margin-bottom: var(--space-xl);
  border-bottom: var(--border-width) solid var(--border);
}

.app-header__brand {
  display: inline-block;
  color: var(--text-main);
  text-decoration: none;
}

.app-header__brand-mark {
  display: block;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  line-height: 1.1;
  text-transform: uppercase;
}

.app-header__brand-name {
  display: block;
  margin-top: 2px;
  color: var(--text-muted);
  font-size: 0.6rem;
  font-weight: 400;
  letter-spacing: 0.15em;
  line-height: 1.3;
  text-transform: uppercase;
}

.app-header__nav {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  flex-wrap: wrap;
}

.app-header__nav a {
  color: var(--text-muted);
  font-size: 0.75rem;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  transition: color 0.2s ease;
  white-space: nowrap;
}

.app-header__nav a:hover,
.app-header__nav a.router-link-exact-active {
  color: var(--text-main);
}

.app-header__theme {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.65rem;
  border: var(--border-width) solid var(--border);
  color: var(--text-muted);
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  transition:
    border-color 0.15s ease,
    color 0.15s ease,
    background-color 0.15s ease;
}

.app-header__theme:hover {
  border-color: var(--border-strong);
  background: var(--bg-hover);
  color: var(--text-main);
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
    font-size: 0.55rem;
  }
}
</style>
