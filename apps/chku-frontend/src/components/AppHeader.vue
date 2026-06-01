<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { BookMarked, ChevronDown, LogOut, Menu, Moon, Settings, Sun, User, X } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import AppLogo from '@/components/AppLogo.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { useAuthSession, useLogoutMutation } from '@/queries/authQueries'
import { useClubQuery } from '@/queries/clubQueries'

const { t } = useI18n()

const clubQuery = useClubQuery()
const { isAuthenticated, isDeveloper, roles, user } = useAuthSession()
const logoutMutation = useLogoutMutation()
const router = useRouter()
const theme = ref<'light' | 'dark'>('dark')
const isUserMenuOpen = ref(false)
const isNavDrawerOpen = ref(false)
const isUserDrawerOpen = ref(false)
const menuRoot = ref<HTMLElement | null>(null)
const clubName = computed(() => clubQuery.data.value?.name ?? 'ЧКУ')
const isHeaderHidden = ref(false)
const lastScrollY = ref(0)
const headerRef = ref<HTMLElement | null>(null)

const roleLabel = computed(() => {
  if (isDeveloper.value) return t('nav.roleDev')
  if (roles.value.includes('admin')) return t('nav.roleAdmin')
  return t('nav.roleMember')
})

function getPreferredTheme(): 'light' | 'dark' {
  const storedTheme = localStorage.getItem('chku-theme')

  if (storedTheme === 'dark' || storedTheme === 'light') {
    return storedTheme
  }

  if (window.matchMedia?.('(prefers-color-scheme: light)').matches) {
    return 'light'
  }

  return 'dark'
}

function applyTheme(nextTheme: 'light' | 'dark') {
  theme.value = nextTheme
  document.documentElement.setAttribute('data-theme', nextTheme)
  localStorage.setItem('chku-theme', nextTheme)
}

async function handleLogout() {
  closeDrawers()
  await logoutMutation.mutateAsync()
  router.push('/login')
}

function closeDrawers() {
  isNavDrawerOpen.value = false
  isUserDrawerOpen.value = false
  isUserMenuOpen.value = false
}

function openNavDrawer() {
  isUserDrawerOpen.value = false
  isNavDrawerOpen.value = true
}

function openUserDrawer() {
  isNavDrawerOpen.value = false
  isUserDrawerOpen.value = true
}

function handleDocumentClick(event: MouseEvent) {
  if (!menuRoot.value || menuRoot.value.contains(event.target as Node)) return
  closeDrawers()
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    closeDrawers()
  }
}

function handleScroll() {
  const currentY = window.scrollY

  if (currentY <= 80) {
    isHeaderHidden.value = false
    lastScrollY.value = currentY
    return
  }

  const delta = currentY - lastScrollY.value

  if (delta > 3) {
    isHeaderHidden.value = true
  } else if (delta < -3) {
    isHeaderHidden.value = false
  }

  lastScrollY.value = currentY
}

onMounted(() => {
  applyTheme(getPreferredTheme())
  document.addEventListener('click', handleDocumentClick)
  document.addEventListener('keydown', handleKeydown)
  window.addEventListener('scroll', handleScroll, { passive: true })

  if (headerRef.value) {
    document.documentElement.style.setProperty('--header-height', `${headerRef.value.offsetHeight}px`)
  }

  window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (!localStorage.getItem('chku-theme')) {
      applyTheme(event.matches ? 'dark' : 'light')
    }
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  document.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template lang="pug">
header.app-header(ref="headerRef" :class="{ 'app-header--hidden': isHeaderHidden }")
  .app-header__mobile(v-if="isAuthenticated")
    button.app-header__drawer-toggle(
      type="button"
      :aria-label="$t('nav.openMenu')"
      :aria-expanded="isNavDrawerOpen"
      @click.stop="isNavDrawerOpen ? closeDrawers() : openNavDrawer()"
    )
      Menu(v-if="!isNavDrawerOpen" :size="22")
      X(v-else :size="22")

  RouterLink.app-header__brand(to="/")
    AppLogo
    span.app-header__brand-name {{ clubName }}

  nav.app-header__nav(v-if="isAuthenticated")
    RouterLink(to="/") {{ $t('nav.dashboard') }}
    RouterLink(to="/members") {{ $t('nav.members') }}
    RouterLink(to="/archive") {{ $t('nav.archive') }}

  .app-header__mobile(v-if="isAuthenticated && user")
    button.app-header__drawer-toggle(
      type="button"
      :aria-label="$t('nav.openUserMenu')"
      :aria-expanded="isUserDrawerOpen"
      @click.stop="isUserDrawerOpen ? closeDrawers() : openUserDrawer()"
    )
      UserAvatar(:name="user.name" :avatar-url="user.avatarUrl" size="sm")

  .app-header__desktop-user(v-if="isAuthenticated && user")
    .app-header__menu(ref="menuRoot")
      button.app-header__user(
        type="button"
        :aria-expanded="isUserMenuOpen"
        aria-haspopup="menu"
        @click.stop="isUserMenuOpen = !isUserMenuOpen"
      )
        UserAvatar(:name="user.name" :avatar-url="user.avatarUrl" size="sm")
        span.app-header__user-name {{ user.name }}
        ChevronDown.app-header__chevron(:size="14" :class="{ 'app-header__chevron--open': isUserMenuOpen }")
      .app-header__dropdown(v-if="isUserMenuOpen" role="menu")
        .app-header__dropdown-header
          UserAvatar(:name="user.name" :avatar-url="user.avatarUrl")
          .app-header__dropdown-info
            .app-header__dropdown-name {{ user.name }}
            .app-header__dropdown-role {{ roleLabel }}
        .app-header__dropdown-divider
        RouterLink.app-header__dropdown-item(to="/profile" role="menuitem" @click="closeDrawers")
          User.app-header__dropdown-item-icon(:size="16")
          span {{ $t('nav.profile') }}
        RouterLink.app-header__dropdown-item(to="/propose-selection" role="menuitem" @click="closeDrawers")
          BookMarked.app-header__dropdown-item-icon(:size="16")
          span {{ $t('nav.myQueue') }}
        RouterLink.app-header__dropdown-item(to="/profile/settings" role="menuitem" @click="closeDrawers")
          Settings.app-header__dropdown-item-icon(:size="16")
          span {{ $t('nav.settings') }}
        .app-header__dropdown-divider
        .app-header__dropdown-item.app-header__dropdown-item--theme
          .app-header__dropdown-item-left
            Sun.app-header__dropdown-item-icon(:size="16")
            span {{ $t('nav.theme') }}
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
          span {{ $t('nav.logout') }}

  nav.app-header__nav(v-else)
    RouterLink(to="/login") {{ $t('nav.login') }}

// Overlay
.app-header__overlay(
  v-if="isNavDrawerOpen || isUserDrawerOpen"
  @click="closeDrawers"
  aria-hidden="true"
)

// Nav drawer (left)
.app-header__drawer.app-header__drawer--nav(
  v-if="isAuthenticated"
  :class="{ 'app-header__drawer--open': isNavDrawerOpen }"
  role="dialog"
  aria-modal="true"
  aria-label="$t('nav.mainAria')"
)
  .app-header__drawer-header
    AppLogo
    button.app-header__drawer-close(
      type="button"
      :aria-label="$t('nav.closeMenu')"
      @click="closeDrawers"
    )
      X(:size="20")
  nav.app-header__drawer-nav
    RouterLink.app-header__drawer-link(to="/" @click="closeDrawers")
      span {{ $t('nav.dashboard') }}
    RouterLink.app-header__drawer-link(to="/members" @click="closeDrawers")
      span {{ $t('nav.members') }}
    RouterLink.app-header__drawer-link(to="/archive" @click="closeDrawers")
      span {{ $t('nav.archive') }}

// User drawer (right)
.app-header__drawer.app-header__drawer--user(
  v-if="isAuthenticated && user"
  :class="{ 'app-header__drawer--open': isUserDrawerOpen }"
  role="dialog"
  aria-modal="true"
  :aria-label="$t('nav.userMenu')"
)
  .app-header__drawer-header
    .app-header__drawer-user
      UserAvatar(:name="user.name" :avatar-url="user.avatarUrl")
      .app-header__drawer-user-info
        span.app-header__drawer-user-name {{ user.name }}
        span.app-header__drawer-user-role {{ roleLabel }}
    button.app-header__drawer-close(
      type="button"
      :aria-label="$t('nav.closeUserMenu')"
      @click="closeDrawers"
    )
      X(:size="20")
  nav.app-header__drawer-nav
    RouterLink.app-header__drawer-link(to="/profile" @click="closeDrawers")
      User(:size="18")
      span {{ $t('nav.profile') }}
    RouterLink.app-header__drawer-link(to="/propose-selection" @click="closeDrawers")
      BookMarked(:size="18")
      span {{ $t('nav.myQueue') }}
    RouterLink.app-header__drawer-link(to="/profile/settings" @click="closeDrawers")
      Settings(:size="18")
      span {{ $t('nav.settings') }}
  .app-header__drawer-section
    .app-header__drawer-label.label-text {{ $t('nav.theme') }}
    .app-header__theme-toggle.app-header__theme-toggle--drawer
      button.app-header__theme-btn(
        type="button"
        :class="{ 'app-header__theme-btn--active': theme === 'light' }"
        @click.stop="applyTheme('light')"
      )
        Sun(:size="14")
        span {{ $t('nav.light') }}
      button.app-header__theme-btn(
        type="button"
        :class="{ 'app-header__theme-btn--active': theme === 'dark' }"
        @click.stop="applyTheme('dark')"
      )
        Moon(:size="14")
        span {{ $t('nav.dark') }}
  button.app-header__drawer-logout(
    type="button"
    @click="handleLogout"
  )
    LogOut(:size="18")
    span {{ $t('nav.logout') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding-top: 1.1rem;
  padding-bottom: 1.1rem;
  margin-bottom: 0;
  border-bottom: var(--border-width) solid var(--border);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 30;
  max-width: var(--container-width);
  margin-left: auto;
  margin-right: auto;
  padding-left: var(--space-md);
  padding-right: var(--space-md);
  background: color-mix(in srgb, var(--bg-base) 10%, transparent);
  -webkit-backdrop-filter: blur(16px);
  backdrop-filter: blur(16px);
  transition: transform 0.25s ease;

  @include desktop {
    position: static;
    left: auto;
    right: auto;
    max-width: none;
    margin-left: 0;
    margin-right: 0;
    padding-left: 0;
    padding-right: 0;
    margin-bottom: var(--space-lg);
    background: transparent;
    -webkit-backdrop-filter: none;
    backdrop-filter: none;
  }
}

.app-header--hidden {
  transform: translateY(-100%);

  @include desktop {
    transform: none;
  }
}

.app-header__mobile {
  display: flex;
  align-items: center;
  gap: var(--space-sm);

  @include desktop {
    display: none;
  }
}

.app-header__drawer-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: rgba(255, 255, 255, 0.015);
  color: var(--text-main);
  cursor: pointer;
  transition: border-color 0.15s ease;

  &:hover {
    border-color: var(--border-strong);
  }
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

  @include mobile-only {
    display: none;
  }
}

.app-header__nav {
  display: none;
  align-items: center;
  gap: var(--space-lg);
  flex-wrap: wrap;

  @include desktop {
    display: flex;
  }
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

.app-header__desktop-user {
  display: none;

  @include desktop {
    display: block;
  }
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

.app-header__theme-toggle--drawer {
  width: 100%;
  justify-content: center;
  padding: var(--space-sm);
}

.app-header__theme-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-xs);
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

.app-header__theme-toggle--drawer .app-header__theme-btn {
  width: auto;
  height: auto;
  padding: var(--space-xs) var(--space-sm);
  border-radius: var(--radius-inner);
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

// Overlay
.app-header__overlay {
  position: fixed;
  inset: 0;
  z-index: 40;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  animation: overlayFade 0.2s ease;

  @include desktop {
    display: none;
  }
}

@keyframes overlayFade {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

// Drawers
.app-header__drawer {
  position: fixed;
  top: 0;
  bottom: 0;
  z-index: 50;
  display: flex;
  flex-direction: column;
  width: min(66vw, 20rem);
  max-width: 20rem;
  background: var(--bg-surface);
  border: var(--border-width) solid var(--border);
  box-shadow: var(--shadow-soft);
  transform: translateX(-120%);
  transition: transform 0.25s ease;

  @include desktop {
    display: none;
  }
}

.app-header__drawer--nav {
  left: 0;
  border-radius: 0 var(--radius-panel) var(--radius-panel) 0;
  border-left: 0;
}

.app-header__drawer--user {
  right: 0;
  border-radius: var(--radius-panel) 0 0 var(--radius-panel);
  border-right: 0;
  transform: translateX(120%);
}

.app-header__drawer--open {
  transform: translateX(0);
}

.app-header__drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  padding: var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
}

.app-header__drawer-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    color 0.15s ease;
}

.app-header__drawer-close:hover {
  border-color: var(--border-strong);
  color: var(--text-main);
}

.app-header__drawer-user {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.app-header__drawer-user-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.app-header__drawer-user-name {
  color: var(--text-main);
  font-size: 0.9rem;
  font-weight: 600;
}

.app-header__drawer-user-role {
  color: var(--text-muted);
  font-size: 0.75rem;
}

.app-header__drawer-nav {
  display: flex;
  flex-direction: column;
  padding: var(--space-sm) 0;
  flex: 1;
  overflow-y: auto;
}

.app-header__drawer-link {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: 0.85rem var(--space-md);
  color: var(--text-muted);
  font-size: 0.92rem;
  font-weight: 500;
  text-decoration: none;
  transition:
    color 0.15s ease,
    background-color 0.15s ease;
}

.app-header__drawer-link:hover,
.app-header__drawer-link.router-link-exact-active {
  background: var(--bg-hover);
  color: var(--text-main);
}

.app-header__drawer-section {
  padding: var(--space-md);
  border-top: var(--border-width) solid var(--border);
}

.app-header__drawer-label {
  display: block;
  margin-bottom: var(--space-sm);
}

.app-header__drawer-logout {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  padding: 0.85rem var(--space-md);
  border-top: var(--border-width) solid var(--border);
  background: transparent;
  color: var(--warn);
  font-size: 0.92rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.app-header__drawer-logout:hover {
  background: var(--warn-bg);
}
</style>
