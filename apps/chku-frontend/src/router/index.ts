import { createRouter, createWebHistory } from 'vue-router'
import { fetchAuthSession, getCachedAuthSession } from '@/queries/authQueries'
import ArchiveBookView from '../views/ArchiveBookView.vue'
import ArchiveView from '../views/ArchiveView.vue'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import MemberDetailView from '../views/MemberDetailView.vue'
import MembersView from '../views/MembersView.vue'
import ProfileView from '../views/ProfileView.vue'
import ProfileSettingsView from '../views/ProfileSettingsView.vue'
import MeetingDetailView from '../views/MeetingDetailView.vue'
import AddMemberView from '../views/AddMemberView.vue'
import ProposeNewSelectionView from '../views/ProposeNewSelectionView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { title: 'Вход', public: true },
    },
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { title: 'Дашборд', requiresAuth: true },
    },
    {
      path: '/members',
      name: 'members',
      component: MembersView,
      meta: { title: 'Участники', requiresAuth: true },
    },
    {
      path: '/members/add',
      name: 'add-member',
      component: AddMemberView,
      meta: { title: 'Добавить участника', requiresAuth: true },
    },
    {
      path: '/members/:id',
      name: 'member-detail',
      component: MemberDetailView,
      meta: { title: 'Участник', requiresAuth: true },
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfileView,
      meta: { title: 'Профиль', requiresAuth: true },
    },
    {
      path: '/profile/settings',
      name: 'profile-settings',
      component: ProfileSettingsView,
      meta: { title: 'Настройки профиля', requiresAuth: true },
    },
    {
      path: '/archive',
      name: 'archive',
      component: ArchiveView,
      meta: { title: 'Архив', requiresAuth: true },
    },
    {
      path: '/archive/:slug',
      name: 'archive-book',
      component: ArchiveBookView,
      meta: { title: 'Архив', requiresAuth: true },
    },
    {
      path: '/meetings/:id',
      name: 'meeting-detail',
      component: MeetingDetailView,
      meta: { title: 'Встреча', requiresAuth: true },
    },
    {
      path: '/propose-selection',
      name: 'propose-selection',
      component: ProposeNewSelectionView,
      meta: { title: 'Предложить книгу', requiresAuth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const authSession = getCachedAuthSession()

  if (to.meta.public) {
    if (authSession) {
      return '/'
    }

    return true
  }

  if (to.meta.requiresAuth && !authSession) {
    try {
      await fetchAuthSession()
    } catch {
      return { name: 'login', query: { redirect: to.fullPath } }
    }
  }

  return true
})

router.afterEach((to) => {
  const pageTitle = to.meta.title as string | undefined
  const clubName = 'Читальный клуб умничек'
  document.title = pageTitle ? `${pageTitle} | ${clubName}` : clubName
})

export default router
