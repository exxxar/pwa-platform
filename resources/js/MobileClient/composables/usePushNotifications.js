import { ref, onMounted } from 'vue';
import axios from 'axios';

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY;

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

export function usePushNotifications() {
    const isSupported = ref('serviceWorker' in navigator && 'PushManager' in window);
    const permission = ref(Notification ? Notification.permission : 'default');
    const subscription = ref(null);

    async function registerSW() {
        const registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/pwa/',
        });
        return registration;
    }

    async function subscribe() {
        if (!isSupported.value) return;

        const registration = await registerSW();
        permission.value = await Notification.requestPermission();

        if (permission.value !== 'granted') {
            throw new Error('Разрешение не получено');
        }

        const sub = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });

        // Отправляем на сервер
        await axios.post('/push/subscribe', sub.toJSON());
        subscription.value = sub;
    }

    async function unsubscribe() {
        if (subscription.value) {
            const endpoint = subscription.value.endpoint;
            await subscription.value.unsubscribe();
            await axios.post('/push/unsubscribe', { endpoint });
            subscription.value = null;
        }
    }

    async function checkExistingSubscription() {
        if (!isSupported.value) return;
        const registration = await navigator.serviceWorker.getRegistration();
        if (registration) {
            subscription.value = await registration.pushManager.getSubscription();
        }
    }

    onMounted(checkExistingSubscription);

    return {
        isSupported,
        permission,
        subscription,
        subscribe,
        unsubscribe,
    };
}
