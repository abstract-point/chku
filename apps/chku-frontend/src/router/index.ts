import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '@/components/layouts/MainLayout.vue'
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
import MeetingCreateView from '../views/MeetingCreateView.vue'
import MeetingEditView from '../views/MeetingEditView.vue'
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
      component: MainLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'home',
          component: HomeView,
          meta: { title: 'Дашборд' },
        },
        {
          path: 'members',
          name: 'members',
          component: MembersView,
          meta: { title: 'Участники' },
        },
        {
          path: 'members/add',
          name: 'add-member',
          component: AddMemberView,
          meta: { title: 'Добавить участника' },
        },
        {
          path: 'members/:id',
          name: 'member-detail',
          component: MemberDetailView,
          meta: { title: 'Участник' },
        },
        {
          path: 'profile',
          name: 'profile',
          component: ProfileView,
          meta: { title: 'Профиль' },
        },
        {
          path: 'profile/settings',
          name: 'profile-settings',
          component: ProfileSettingsView,
          meta: { title: 'Настройки профиля' },
        },
        {
          path: 'archive',
          name: 'archive',
          component: ArchiveView,
          meta: { title: 'Архив' },
        },
        {
          path: 'archive/:slug',
          name: 'archive-book',
          component: ArchiveBookView,
          meta: { title: 'Архив' },
        },
        {
          path: 'meetings/create',
          name: 'meeting-create',
          component: MeetingCreateView,
          meta: { title: 'Новая встреча' },
        },
        {
          path: 'meetings/:id',
          name: 'meeting-detail',
          component: MeetingDetailView,
          meta: { title: 'Встреча' },
        },
        {
          path: 'meetings/:id/edit',
          name: 'meeting-edit',
          component: MeetingEditView,
          meta: { title: 'Редактирование встречи' },
        },
        {
          path: 'propose-selection',
          name: 'propose-selection',
          component: ProposeNewSelectionView,
          meta: { title: 'Предложить книгу' },
        },
      ],
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
