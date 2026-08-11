import { defineAsyncComponent } from 'vue';

const PrivacyPolicy = defineAsyncComponent(() => import('@/MobileClient/Pages/PrivacyPolicy.vue'));

export default [
    { path: '/privacy-policy', name: 'PrivacyPolicy', component: PrivacyPolicy, meta: { public: true } },
    { path: '/terms-of-service', name: 'TermsOfService', component: PrivacyPolicy, meta: { public: true } },
    { path: '/cookie-policy', name: 'CookiePolicy', component: PrivacyPolicy, meta: { public: true } },
];
