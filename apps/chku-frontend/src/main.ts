import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'

import App from './App.vue'
import router from './router'

const storedTheme = localStorage.getItem('chku-theme')
document.documentElement.setAttribute(
  'data-theme',
  storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'dark',
)

const app = createApp(App)
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: true,
    },
  },
})

app.use(createPinia())
app.use(VueQueryPlugin, { queryClient })
app.use(router)

app.mount('#app')
