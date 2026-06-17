import { ref } from 'vue';

// Глобальное хранилище уведомлений
const notificationState = ref({
    containers: new Map(),
});

export function useNotification() {
    const addNotification = (options) => {
        // Находим первый доступный контейнер
        const container = notificationState.value.containers.values().next().value;
        if (container) {
            return container.addNotification(options);
        }
        console.warn('Notification container not found');
        return null;
    };

    const notify = (options) => {
        return addNotification(options);
    };

    const success = (text, title = 'Успех') => {
        return addNotification({ type: 'success', title, text });
    };

    const error = (text, title = 'Ошибка') => {
        return addNotification({ type: 'error', title, text, duration: 7000 });
    };

    const warning = (text, title = 'Внимание') => {
        return addNotification({ type: 'warning', title, text, duration: 6000 });
    };

    const info = (text, title = 'Информация') => {
        return addNotification({ type: 'info', title, text });
    };

    const clearAll = () => {
        notificationState.value.containers.forEach(container => {
            container.clearAll();
        });
    };

    return {
        notify,
        success,
        error,
        warning,
        info,
        clearAll,
    };
}

// Регистрация контейнера
export function registerNotificationContainer(id, container) {
    notificationState.value.containers.set(id, container);
}

// Удаление контейнера
export function unregisterNotificationContainer(id) {
    notificationState.value.containers.delete(id);
}
