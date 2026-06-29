import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import router from './router'
import { setupEcho } from './composables/echo'

setupEcho()

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.mount('#app')
