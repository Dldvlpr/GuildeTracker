// src/main.ts
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useUserStore } from '@/stores/userStore'

const app = createApp(App)
app.use(createPinia())
app.use(router)

const API_BASE = import.meta.env.VITE_API_BASE_URL

await useUserStore().initFromApi(API_BASE)

app.mount('#app')
