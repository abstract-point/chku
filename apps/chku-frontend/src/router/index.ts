import { createRouter, createWebHistory } from 'vue-router'
import { useClubStore } from '@/stores/club'
import ArchiveBookView from '../views/ArchiveBookView.vue'
import ArchiveView from '../views/ArchiveView.vue'
import HomeView from '../views/HomeView.vue'
import ProfileView from '../views/ProfileView.vue'
import ProposeNewSelectionView from '../views/ProposeNewSelectionView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { title: 'Дашборд' },
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfileView,
      meta: { title: 'Профиль' },
    },
    {
      path: '/archive',
      name: 'archive',
      component: ArchiveView,
      meta: { title: 'Архив' },
    },
    {
      path: '/archive/:slug',
      name: 'archive-book',
      component: ArchiveBookView,
      meta: { title: 'Архив' },
    },
    {
      path: '/propose-selection',
      name: 'propose-selection',
      component: ProposeNewSelectionView,
      meta: { title: 'Предложить книгу' },
      beforeEnter: () => {
        const club = useClubStore()

        if (!club.canProposeNextBook) {
          return { name: 'profile' }
        }

        return true
      },
    },
  ],
})

router.afterEach((to) => {
  const pageTitle = to.meta.title as string | undefined
  const clubName = 'Читальный клуб умничек'
  document.title = pageTitle ? `${pageTitle} | ${clubName}` : clubName
})

export default router
