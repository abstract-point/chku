<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import AppFooter from '@/components/AppFooter.vue'
import BookCandidateVerificationBanner from '@/components/dashboard/BookCandidateVerificationBanner.vue'
import { mapCandidateToChoice } from '@/mappers/candidateMapper'
import { useActiveCandidateQuery } from '@/queries/candidateQueries'
import { useClubStore } from '@/stores/club'

const club = useClubStore()
const activeCandidateQuery = useActiveCandidateQuery()
const activeBookChoice = computed(() =>
  activeCandidateQuery.data.value ? mapCandidateToChoice(activeCandidateQuery.data.value) : null,
)
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
        svg.app-header__brand-mark(viewBox="0 0 1254 1254" xmlns="http://www.w3.org/2000/svg")
          g(transform="translate(0,1254) scale(0.1,-0.1)" fill="currentColor" stroke="none")
            path(d="M2260 8183 c0 -582 4 -993 10 -1048 41 -359 237 -654 535 -803 134 -67 269 -104 430 -116 39 -3 382 -6 763 -6 l692 0 0 -613 0 -613 178 -32 c147 -27 360 -75 434 -98 17 -5 18 57 20 1173 l3 1178 116 -160 c462 -636 783 -1079 909 -1250 81 -110 265 -362 410 -560 144 -198 268 -366 274 -373 10 -10 39 -5 146 22 74 18 203 46 285 60 83 15 166 30 186 34 l37 7 -177 240 c-442 604 -771 1052 -960 1310 -112 154 -274 373 -358 488 -97 130 -151 212 -147 220 9 14 1005 1341 1248 1662 91 120 166 223 166 227 0 4 -157 8 -349 8 l-348 0 -719 -957 -719 -958 -3 958 -2 957 -315 0 -315 0 -2 -1162 -3 -1163 -670 -3 c-450 -2 -691 1 -735 8 -157 27 -266 116 -327 270 -17 42 -18 116 -23 1045 l-5 1000 -332 3 -333 2 0 -957z")
            path(d="M7534 9116 c3 -13 72 -167 152 -343 80 -175 226 -493 324 -708 98 -214 294 -642 435 -950 142 -308 258 -565 258 -570 0 -6 -127 -325 -283 -710 -156 -385 -293 -726 -306 -758 l-23 -57 316 2 316 3 126 305 c70 168 169 409 220 535 52 127 126 309 166 405 40 96 110 265 155 375 45 110 119 290 165 400 46 110 123 299 173 420 50 121 158 384 240 585 113 276 386 945 439 1078 4 9 -65 12 -324 12 l-329 0 -171 -427 c-389 -971 -566 -1408 -573 -1416 -4 -4 -37 59 -75 140 -90 196 -641 1413 -713 1576 l-57 127 -318 0 -319 0 6 -24z")
            path(d="M2260 4915 l0 -715 528 0 c569 -1 909 -11 1177 -35 428 -40 823 -116 1160 -225 418 -135 781 -342 871 -496 l26 -44 244 0 244 0 25 39 c129 205 614 448 1156 580 577 140 1038 181 2062 181 l527 0 0 715 0 715 -225 0 -225 0 -2 -462 -3 -463 -120 5 c-276 12 -888 4 -1075 -14 -765 -72 -1342 -220 -1825 -470 -223 -115 -406 -246 -499 -358 l-37 -44 -97 96 c-198 196 -500 363 -911 504 -406 139 -829 224 -1361 273 -211 19 -907 26 -1087 10 l-93 -8 0 466 0 465 -230 0 -230 0 0 -715z")
        span.app-header__brand-name {{ club.name }}

      nav.app-header__nav(aria-label="Основная навигация")
        RouterLink(to="/") Дашборд
        RouterLink(to="/members") Участники
        RouterLink(to="/archive") Архив
        RouterLink(to="/profile") Профиль
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

  .container(v-if="activeBookChoice")
    BookCandidateVerificationBanner(:choice="activeBookChoice")
  main.app-main
    RouterView
  AppFooter
</template>

<style scoped>
.app-shell {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: var(--bg-base);
}

.app-main {
  flex: 1;
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
  width: 2.2rem;
  height: 2.2rem;
  color: var(--text-main);
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
