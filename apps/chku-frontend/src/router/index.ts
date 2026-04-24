import { createRouter, createWebHistory } from 'vue-router'
import { useClubStore } from '@/stores/club'
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
