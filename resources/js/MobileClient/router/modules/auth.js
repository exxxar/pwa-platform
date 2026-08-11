import { defineAsyncComponent } from 'vue';

const AuthPage = defineAsyncComponent(() => import('@/MobileClient/Pages/AuthPage.vue'));

export default [
    { path: '/auth', name: 'Auth', component: AuthPage, meta: { public: true} },
];
