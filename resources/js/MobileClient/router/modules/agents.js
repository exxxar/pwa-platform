import { defineAsyncComponent } from 'vue';

const AgentDashboard = defineAsyncComponent(() => import('@/MobileClient/Pages/Agent/AgentDashboard.vue'));
const AgentOnboarding = defineAsyncComponent(() => import('@/MobileClient/Pages/Agent/AgentOnboarding.vue'));

export default [
    { path: '/agents', redirect: { name: 'AgentDashboard' } },
    {
        path: '/agents/agent-main',
        name: 'AgentDashboard',
        component: AgentDashboard,
    },

    {
        path: '/agent/onboarding',
        name: 'AgentOnboarding',
        component: AgentOnboarding,
    }



];
