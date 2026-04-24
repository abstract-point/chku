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
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfileView,
    },
    {
      path: '/archive',
      name: 'archive',
      component: ArchiveView,
    },
    {
      path: '/archive/:slug',
      name: 'archive-book',
      component: ArchiveBookView,
    },
    {
      path: '/propose-selection',
      name: 'propose-selection',
      component: ProposeNewSelectionView,
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

export default router
