<template>
    <div>
        <button v-if="!subscription && permission !== 'denied'" @click="handleSubscribe">
            🔔 Включить уведомления
        </button>
        <button v-else-if="subscription" @click="handleUnsubscribe">
            🔕 Отключить уведомления
        </button>
        <p v-else>Уведомления заблокированы в настройках браузера</p>
    </div>
</template>

<script setup>
import { usePushNotifications } from '@/MobileClient/composables/usePushNotifications';

const { subscription, permission, subscribe, unsubscribe } = usePushNotifications();

async function handleSubscribe() {
    try {
        await subscribe();
    } catch (e) {
        console.error(e);
    }
}

async function handleUnsubscribe() {
    await unsubscribe();
}
</script>
