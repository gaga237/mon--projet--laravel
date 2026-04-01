import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

// On configure axios pour toujours envoyer le token Sanctum s'il est présent
import axios from 'axios';
axios.defaults.withCredentials = true;
axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

const app = createApp(App);

app.use(router);

app.mount('#app');
