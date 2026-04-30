<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ChevronDown, LogOut, Moon, Settings, Sun, User } from '@lucide/vue'
import AppLogo from '@/components/AppLogo.vue'
import { useAuthSession, useLogoutMutation } from '@/queries/authQueries'
import { useClubStore } from '@/stores/club'

const club = useClubStore()
const { isAuthenticated, isDeveloper, roles, user } = useAuthSession()
const logoutMutation = useLogoutMutation()
const router = useRouter()
const theme = ref<'light' | 'dark'>('dark')
const isUserMenuOpen = ref(false)
const menuRoot = ref<HTMLElement | null>(null)

const roleLabel = computed(() => {
  if (isDeveloper.value) return 'Разработчик'
  if (roles.value.includes('admin')) return 'Администратор'
  return 'Участник'
})

const userInitials = computed(() => {
  if (user.value?.initials) return user.value.initials
  const parts = user.value?.name.trim().split(/\s+/) ?? []
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return parts[0]?.slice(0, 2).toUpperCase() ?? ''
})

function getPreferredTheme() {
  const storedTheme = localStorage.getItem('chku-theme')

  if (storedTheme === 'dark' || storedTheme === 'light') {
    return storedTheme
  }

  if (!window.matchMedia) {
    return 'dark'
  }

  return 'dark'
}

function applyTheme(nextTheme: 'light' | 'dark') {
  theme.value = nextTheme
  document.documentElement.setAttribute('data-theme', nextTheme)
  localStorage.setItem('chku-theme', nextTheme)
}

async function handleLogout() {
  isUserMenuOpen.value = false
  await logoutMutation.mutateAsync()
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
    template(v-if="isAuthenticated")
      RouterLink(to="/") Дашборд
      RouterLink(to="/members") Участники
      RouterLink(to="/archive") Архив
      .app-header__menu(ref="menuRoot" v-if="user")
        button.app-header__user(
          type="button"
          :aria-expanded="isUserMenuOpen"
          aria-haspopup="menu"
          @click.stop="isUserMenuOpen = !isUserMenuOpen"
        )
          .app-header__avatar {{ userInitials }}
          span.app-header__user-name {{ user.name }}
          ChevronDown.app-header__chevron(:size="14" :class="{ 'app-header__chevron--open': isUserMenuOpen }")
        .app-header__dropdown(v-if="isUserMenuOpen" role="menu")
          .app-header__dropdown-header
            .app-header__dropdown-avatar {{ userInitials }}
            .app-header__dropdown-info
              .app-header__dropdown-name {{ user.name }}
              .app-header__dropdown-role {{ roleLabel }}
          .app-header__dropdown-divider
          RouterLink.app-header__dropdown-item(to="/profile" role="menuitem" @click="closeUserMenu")
            User.app-header__dropdown-item-icon(:size="16")
            span Профиль
          RouterLink.app-header__dropdown-item(to="/profile/settings" role="menuitem" @click="closeUserMenu")
            Settings.app-header__dropdown-item-icon(:size="16")
            span Настройки
          .app-header__dropdown-divider
          .app-header__dropdown-item.app-header__dropdown-item--theme
            .app-header__dropdown-item-left
              Sun.app-header__dropdown-item-icon(:size="16")
              span Тема
            .app-header__theme-toggle
              button.app-header__theme-btn(
                type="button"
                :class="{ 'app-header__theme-btn--active': theme === 'light' }"
                @click.stop="applyTheme('light')"
              )
                Sun(:size="14")
              button.app-header__theme-btn(
                type="button"
                :class="{ 'app-header__theme-btn--active': theme === 'dark' }"
                @click.stop="applyTheme('dark')"
              )
                Moon(:size="14")
          .app-header__dropdown-divider
          button.app-header__dropdown-item.app-header__dropdown-item--danger(
            type="button"
            role="menuitem"
            @click="handleLogout"
          )
            LogOut.app-header__dropdown-item-icon(:size="16")
            span Выйти
    template(v-else)
      RouterLink(to="/login") Вход
</template>

<style scoped>
.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-lg);
  padding-top: 1.1rem;
  padding-bottom: 1.1rem;
  margin-bottom: var(--space-lg);
  border-bottom: var(--border-width) solid var(--border);
}

.app-header__brand {
  display: inline-flex;
  align-items: center;
  gap: var(--space-md);
  color: var(--text-main);
  text-decoration: none;
}

.app-header__brand-name {
  display: block;
  color: var(--text-muted);
  font-size: 0.9rem;
  font-weight: 500;
  letter-spacing: 0;
  line-height: 1.3;
}

.app-header__nav {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  flex-wrap: wrap;
}

.app-header__nav > a {
  position: relative;
  color: var(--text-muted);
  font-size: 0.92rem;
  font-weight: 500;
  letter-spacing: 0;
  transition: color 0.2s ease;
  white-space: nowrap;
}

.app-header__nav > a:hover,
.app-header__nav > a.router-link-exact-active {
  color: var(--text-main);
}

.app-header__nav > a.router-link-exact-active::after {
  position: absolute;
  right: 50%;
  bottom: -0.85rem;
  width: 0.28rem;
  height: 0.28rem;
  border-radius: 50%;
  background: var(--accent);
  content: '';
  transform: translateX(50%);
}

.app-header__menu {
  position: relative;
}

.app-header__user {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
  min-height: 2.9rem;
  padding: 0.35rem 0.7rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: rgba(255, 255, 255, 0.015);
  color: var(--text-main);
  cursor: pointer;
  transition: border-color 0.15s ease;
}

.app-header__user:hover {
  border-color: var(--border-strong);
}

.app-header__avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border: 1.5px solid var(--warn);
  border-radius: 50%;
  color: var(--warn);
  font-family: var(--font-mono);
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
}

.app-header__user-name {
  font-size: 0.9rem;
  font-weight: 500;
}

.app-header__chevron {
  color: var(--text-muted);
  transition: transform 0.15s ease;
}

.app-header__chevron--open {
  transform: rotate(180deg);
}

.app-header__dropdown {
  position: absolute;
  z-index: 20;
  top: calc(100% + var(--space-sm));
  right: 0;
  display: flex;
  flex-direction: column;
  width: min(18rem, calc(100vw - 2rem));
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background: var(--bg-surface);
  box-shadow: var(--shadow-soft);
  overflow: hidden;
}

.app-header__dropdown-header {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: var(--space-md);
}

.app-header__dropdown-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.75rem;
  height: 2.75rem;
  border: 1.5px solid var(--warn);
  border-radius: 50%;
  color: var(--warn);
  font-family: var(--font-mono);
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  flex-shrink: 0;
}

.app-header__dropdown-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.app-header__dropdown-name {
  color: var(--text-main);
  font-size: 0.85rem;
  font-weight: 600;
}

.app-header__dropdown-role {
  color: var(--text-muted);
  font-size: 0.75rem;
}

.app-header__dropdown-divider {
  height: var(--border-width);
  background: var(--border);
}

.app-header__dropdown-item {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  width: 100%;
  padding: 0.7rem var(--space-md);
  background: transparent;
  color: var(--text-muted);
  font-size: 0.8rem;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition:
    color 0.15s ease,
    background-color 0.15s ease;
}

.app-header__dropdown-item:hover,
.app-header__dropdown-item.router-link-exact-active {
  background: var(--bg-hover);
  color: var(--text-main);
}

.app-header__dropdown-item-icon {
  flex-shrink: 0;
  color: var(--text-muted);
}

.app-header__dropdown-item:hover .app-header__dropdown-item-icon,
.app-header__dropdown-item.router-link-exact-active .app-header__dropdown-item-icon {
  color: var(--text-main);
}

.app-header__dropdown-item--theme {
  justify-content: space-between;
  padding-right: var(--space-sm);
  cursor: default;
}

.app-header__dropdown-item--theme:hover {
  background: transparent;
  color: var(--text-muted);
}

.app-header__dropdown-item--theme:hover .app-header__dropdown-item-icon {
  color: var(--text-muted);
}

.app-header__dropdown-item-left {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.app-header__theme-toggle {
  display: inline-flex;
  align-items: center;
  border: var(--border-width) solid var(--border);
  border-radius: 999px;
  padding: 2px;
  gap: 2px;
}

.app-header__theme-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 50%;
  color: var(--text-muted);
  cursor: pointer;
  transition:
    color 0.15s ease,
    border-color 0.15s ease;
}

.app-header__theme-btn--active {
  border: 1.5px solid var(--warn);
  color: var(--warn);
}

.app-header__dropdown-item--danger {
  color: var(--warn);
}

.app-header__dropdown-item--danger:hover {
  background: var(--warn-bg);
  color: var(--warn);
}

.app-header__dropdown-item--danger .app-header__dropdown-item-icon {
  color: var(--warn);
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
    font-size: 0.82rem;
  }
}
</style>
