import axios from 'axios';
import { useQueueStore } from '@/MobileClient/stores/useQueueStore'; // Путь к вашему стору

// Функция для определения типа задачи по URL (для красивого UI)
function getTaskMeta(url, method) {
    if (url.includes('/basket/checkout') || url.includes('/basket/inc-product')) {
        return { type: 'order', title: 'Действие с корзиной / Заказ' };
    }
    if (url.includes('/dialogs/') && (method === 'post' || method === 'put')) {
        return { type: 'message', title: 'Сообщение в чат' };
    }
    if (url.includes('/feedback') || url.includes('/reviews')) {
        return { type: 'feedback', title: 'Отправка отзыва' };
    }
    return { type: 'request', title: 'Фоновый запрос' };
}

// RESPONSE INTERCEPTOR (Ловим ошибки)
axios.interceptors.response.use(
    (response) => {
        // Если запрос прошел успешно, значит интернет есть
        const queueStore = useQueueStore();
        if (queueStore.isOffline) {
            queueStore.setOffline(false);
            // Запускаем обработку очереди в фоне
            setTimeout(() => queueStore.processQueue(), 500);
        }
        return response;
    },
    (error) => {
        const config = error.config;

        // Определяем реальную сетевую ошибку (мобильный обрыв связи, таймаут, нет сети)
        const isNetworkError = !error.response ||
            error.code === 'ERR_NETWORK' ||
            error.code === 'ECONNABORTED' ||
            error.message === 'Network Error';

        if (isNetworkError && config && !config._isRetry) {
            config._isRetry = true; // Защита от бесконечного цикла
            const queueStore = useQueueStore();
            const meta = getTaskMeta(config.url, config.method);

            queueStore.addTask(config, meta);
            console.warn(`[Queue] Запрос добавлен в очередь: ${meta.title}`);
        }

        return Promise.reject(error);
    }
);

// Слушаем нативные события браузера (для мгновенного UI)
window.addEventListener('online', () => {
    const queueStore = useQueueStore();
    queueStore.setOffline(false);
    queueStore.processQueue();
});

window.addEventListener('offline', () => {
    const queueStore = useQueueStore();
    queueStore.setOffline(true);
});
