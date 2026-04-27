<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AppLogo from '@/components/AppLogo.vue'
import { useAuthStore } from '@/stores/auth'
import { useClubStore } from '@/stores/club'

const club = useClubStore()
const auth = useAuthStore()
const router = useRouter()
const theme = ref<'light' | 'dark'>('light')

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

async function handleLogout() {
  await auth.logout()
  router.push('/login')
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
header.app-header
  RouterLink.app-header__brand(to="/")
    AppLogo
    span.app-header__brand-name {{ club.name }}

  nav.app-header__nav(aria-label="Основная навигация")
    template(v-if="auth.isAuthenticated")
      RouterLink(to="/") Дашборд
      RouterLink(to="/members") Участники
      RouterLink(to="/archive") Архив
      RouterLink(to="/profile") Профиль
      .app-header__user(v-if="auth.user")
        span.app-header__user-name {{ auth.user.name }}
        span.app-header__user-role(v-if="auth.isAdmin") {{ auth.isDeveloper ? 'Разработчик' : 'Администратор' }}
      button.app-header__action(type="button" @click="handleLogout") Выйти
    template(v-else)
      RouterLink(to="/login") Вход
    button.app-header__theme(type="button" :aria-label="theme === 'dark' ? 'Переключить на светлую тему' : 'Переключить на тёмную тему'" @click="toggleTheme")
      svg(v-if="theme === 'light'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18")
        path(d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z")
      svg(v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18")
        circle(cx="12" cy="12" r="5")
        line(x1="12" y1="1" x2="12" y2="3")
        line(x1="12" y1="21" x2="12" y2="23")
        line(x1="4.22" y1="4.22" x2="5.64" y2="5.64")
        line(x1="18.36" y1="18.36" x2="19.78" y2="19.78")
        line(x1="1" y1="12" x2="3" y2="12")
        line(x1="21" y1="12" x2="23" y2="12")
        line(x1="4.22" y1="19.78" x2="5.64" y2="18.36")
        line(x1="18.36" y1="5.64" x2="19.78" y2="4.22")
</template>

<style scoped>
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

.app-header__user {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.app-header__user-name {
  color: var(--text-main);
  font-size: 0.75rem;
  font-weight: 500;
}

.app-header__user-role {
  color: var(--accent);
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.app-header__action {
  padding: 0.35rem 0.6rem;
  border: var(--border-width) solid var(--border);
  background: transparent;
  color: var(--text-muted);
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    color 0.15s ease,
    background-color 0.15s ease;
}

.app-header__action:hover {
  border-color: var(--border-strong);
  background: var(--bg-hover);
  color: var(--text-main);
}

.app-header__theme {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: var(--border-width) solid var(--border);
  color: var(--text-muted);
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
