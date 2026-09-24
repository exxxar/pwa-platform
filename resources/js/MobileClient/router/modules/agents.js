import { defineAsyncComponent } from 'vue';

const AgentDashboard = defineAsyncComponent(() => import('@/MobileClient/Pages/Agent/AgentDashboard.vue'));
const AgentOnboarding = defineAsyncComponent(() => import('@/MobileClient/Pages/Agent/AgentOnboarding.vue'));
const AgentTenantCreate = defineAsyncComponent(() => import('@/MobileClient/Components/CostCalculator.vue'));

export default [
    { path: '/agents', redirect: { name: 'AgentDashboard' } },
    {
        path: '/agent/agent-main',
        name: 'AgentDashboard',
        component: AgentDashboard,
    },
    {
        path: '/agent/tenants/create',
        name: 'AgentTenantCreate',
        component: AgentTenantCreate, // Путь к вашему файлу

    },
    {
        path: '/agent/onboarding',
        name: 'AgentOnboarding',
        component: AgentOnboarding,
    }



];
