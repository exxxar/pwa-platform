export async function registerSW() {
    if (!('serviceWorker' in navigator)) {
        console.log('[SW] Service Worker не поддерживается');
        return;
    }

    try {
        const registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/pwa/'
        });

        console.log('[SW] Registered, scope:', registration.scope);

        // Проверяем обновления при каждом визите
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            console.log('[SW] Update found');

            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    // Новая версия доступна — можно показать юзеру кнопку "Обновить"
                    showUpdateNotification();
                }
            });
        });

        // Обработка сообщений от SW (например, при клике на push)
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type === 'NAVIGATE') {
                // Меняем хэш-маршрут
                window.location.hash = event.data.url.replace('/pwa/', '');
            }
        });

    } catch (error) {
        console.error('[SW] Registration failed:', error);
    }
}

function showUpdateNotification() {
    // Можно показать toast/modal с кнопкой "Обновить"
    if (confirm('Доступна новая версия приложения. Обновить сейчас?')) {
        // Говорим SW пропустить ожидание и активироваться
        navigator.serviceWorker.controller?.postMessage('SKIP_WAITING');
        // Перезагружаем страницу после активации новой версии
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            window.location.reload();
        }, { once: true });
    }
}
