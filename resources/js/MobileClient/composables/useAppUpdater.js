// resources/js/MobileClient/composables/useAppUpdater.js
import { ref, onMounted, onUnmounted } from 'vue';

// Версия, с которой собрался текущий клиент.
// Убедитесь, что VITE_APP_VERSION пробрасывается в vite.config.js или .env
const CURRENT_VERSION = import.meta.env.VITE_APP_VERSION || '1.0.0';

const showUpdateToast = ref(false);
let pollInterval = null;

export function useAppUpdater() {
    const checkForUpdate = async () => {
        try {
            // cache: 'no-store' и timestamp гарантируют, что запрос пойдет в сеть, а не в кэш SW
            const response = await fetch(`/app-version?t=${Date.now()}`, {
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) return;

            const data = await response.json();

            if (data.version && data.version !== CURRENT_VERSION) {
                console.log(`[Updater] 🚀 New version available: ${data.version} (current: ${CURRENT_VERSION})`);
                showUpdateToast.value = true;

                // Останавливаем поллинг, чтобы не спамить запросами, раз обновление уже найдено
                if (pollInterval) clearInterval(pollInterval);
            }
        } catch (e) {
            console.warn('[Updater] Failed to check version', e);
        }
    };

    const forceUpdate = async () => {
        showUpdateToast.value = false;

        // 1. Говорим Service Worker'у пропустить ожидание и активировать новую версию
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage('SKIP_WAITING');
        }

        // 2. Даем SW 500мс на очистку старых кэшей (activate event)
        await new Promise(resolve => setTimeout(resolve, 500));

        // 3. Перезагружаем страницу.
        // window.location.reload() заставит браузер запросить новый index.html
        window.location.reload();
    };

    onMounted(() => {
        checkForUpdate(); // Проверяем сразу при загрузке
        pollInterval = setInterval(checkForUpdate, 15 * 60 * 1000); // И каждые 15 минут

        // Проверяем обновления, когда пользователь возвращается в приложение (разворачивает вкладку)
        window.addEventListener('focus', checkForUpdate);
    });

    onUnmounted(() => {
        if (pollInterval) clearInterval(pollInterval);
        window.removeEventListener('focus', checkForUpdate);
    });

    return { showUpdateToast, forceUpdate, CURRENT_VERSION };
}
