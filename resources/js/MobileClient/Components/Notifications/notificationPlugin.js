import { createApp } from 'vue';
import NotificationContainer from './NotificationContainer.vue';
import { useNotification, registerNotificationContainer, unregisterNotificationContainer } from './useNotification.js';

export default {
    install(app, options = {}) {
        const position = options.position || 'top-right';

        // Создаём глобальный контейнер
        const containerId = 'global-notification-container';

        // Создаём элемент для контейнера
        const containerEl = document.createElement('div');
        containerEl.id = containerId;
        document.body.appendChild(containerEl);

        // Создаём Vue приложение для контейнера
        const containerApp = createApp(NotificationContainer, {
            position,
        });

        // Монтируем контейнер
        const containerInstance = containerApp.mount(containerEl);

        // Регистрируем контейнер
        registerNotificationContainer(containerId, containerInstance);

        // Добавляем глобальный метод $notify
        app.config.globalProperties.$notify = (options) => {
            return containerInstance.addNotification(options);
        };

        // Добавляем глобальный метод $notify.success, $notify.error и т.д.
        app.config.globalProperties.$notify.success = (text, title) => {
            return containerInstance.addNotification({ type: 'success', title: title || 'Успех', text });
        };

        app.config.globalProperties.$notify.error = (text, title) => {
            return containerInstance.addNotification({ type: 'error', title: title || 'Ошибка', text, duration: 7000 });
        };

        app.config.globalProperties.$notify.warning = (text, title) => {
            return containerInstance.addNotification({ type: 'warning', title: title || 'Внимание', text, duration: 6000 });
        };

        app.config.globalProperties.$notify.info = (text, title) => {
            return containerInstance.addNotification({ type: 'info', title: title || 'Информация', text });
        };

        app.config.globalProperties.$notify.clearAll = () => {
            containerInstance.clearAll();
        };

        // Cleanup при размонтировании
        app.config.globalProperties.$notify.destroy = () => {
            unregisterNotificationContainer(containerId);
            containerApp.unmount();
            containerEl.remove();
        };
    },
};
