<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AppLogo from '@/components/AppLogo.vue'
import { useAuthStore } from '@/stores/auth'
import { useClubStore } from '@/stores/club'

const club = useClubStore()
const auth = useAuthStore()
const router = useRouter()
const theme = ref<'light' | 'dark'>('light')
const isUserMenuOpen = ref(false)
const menuRoot = ref<HTMLElement | null>(null)

const roleLabel = computed(() => {
  if (auth.isDeveloper) return 'Разработчик'
  if (auth.roles.includes('admin')) return 'Администратор'
  return 'Участник'
})

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
  isUserMenuOpen.value = false
  await auth.logout()
  router.push('/login')
}

function closeUserMenu() {
  isUserMenuOpen.value = false
}

function handleDocumentClick(event: MouseEvent) {
  if (!menuRoot.value || menuRoot.value.contains(event.target as Node)) return
  closeUserMenu()
}

onMounted(() => {
  applyTheme(getPreferredTheme())
  document.addEventListener('click', handleDocumentClick)

  window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (!localStorage.getItem('chku-theme')) {
      applyTheme(event.matches ? 'dark' : 'light')
    }
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
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
      .app-header__menu(ref="menuRoot" v-if="auth.user")
        button.app-header__user(
          type="button"
          :aria-expanded="isUserMenuOpen"
          aria-haspopup="menu"
          @click.stop="isUserMenuOpen = !isUserMenuOpen"
        )
          span.app-header__user-name {{ auth.user.name }}
          span.app-header__chevron(aria-hidden="true") {{ isUserMenuOpen ? '↑' : '↓' }}
        .app-header__dropdown(v-if="isUserMenuOpen" role="menu")
          .app-header__role
            span.label-text Роль
            strong {{ roleLabel }}
          RouterLink.app-header__dropdown-item(to="/profile" role="menuitem" @click="closeUserMenu") Профиль
          RouterLink.app-header__dropdown-item(to="/profile/settings" role="menuitem" @click="closeUserMenu") Настройки
          button.app-header__dropdown-item(type="button" role="menuitem" @click="toggleTheme")
            | {{ theme === 'dark' ? 'Светлая тема' : 'Тёмная тема' }}
          button.app-header__dropdown-item(type="button" role="menuitem" @click="handleLogout") Выйти
    template(v-else)
      RouterLink(to="/login") Вход
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

.app-header__menu {
  position: relative;
}

.app-header__user {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
  padding: 0;
  color: var(--text-main);
}

.app-header__user-name,
.app-header__chevron {
  font-size: 0.75rem;
  font-weight: 500;
}

.app-header__chevron {
  color: var(--text-muted);
}

.app-header__dropdown {
  position: absolute;
  z-index: 20;
  top: calc(100% + var(--space-sm));
  right: 0;
  display: grid;
  width: min(15rem, calc(100vw - 2rem));
  border: var(--border-width) solid var(--border);
  background: var(--bg-surface);
  box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.08);
}

.app-header__role {
  display: grid;
  gap: var(--space-xs);
  padding: var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
}

.app-header__role strong {
  color: var(--text-main);
  font-size: 0.8rem;
}

.app-header__dropdown-item {
  display: flex;
  width: 100%;
  padding: 0.75rem var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
  background: transparent;
  color: var(--text-muted);
  font-size: 0.75rem;
  font-weight: 500;
  letter-spacing: 0;
  text-align: left;
  text-transform: none;
  cursor: pointer;
  transition:
    color 0.15s ease,
    background-color 0.15s ease;
}

.app-header__dropdown-item:last-child {
  border-bottom: 0;
}

.app-header__dropdown-item:hover,
.app-header__dropdown-item.router-link-exact-active {
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

  .app-header__dropdown {
    right: auto;
    left: 0;
  }

  .app-header__brand-name {
    font-size: 0.55rem;
  }
}
</style>
