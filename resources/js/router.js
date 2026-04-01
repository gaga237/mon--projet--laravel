import { createRouter, createWebHistory } from 'vue-router';

// On importera les vues ici
// import Login from './views/Login.vue';
// import Register from './views/Register.vue';
// import Chat from './views/Chat.vue';

const routes = [
    {
        path: '/',
        name: 'Chat',
        // component: Chat,
        component: () => import('./views/Chat.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/login',
        name: 'Login',
        // component: Login,
        component: () => import('./views/Login.vue'),
        meta: { guest: true }
    },
    {
        path: '/register',
        name: 'Register',
        // component: Register,
        component: () => import('./views/Register.vue'),
        meta: { guest: true }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Middleware de navigation pour protéger les routes
router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('auth_token');

    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'Login' });
    } else if (to.meta.guest && isAuthenticated) {
        next({ name: 'Chat' });
    } else {
        next();
    }
});

export default router;
