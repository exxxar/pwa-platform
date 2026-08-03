
import { ref, onMounted, onUnmounted } from 'vue';

const CURRENT_VERSION = import.meta.env.VITE_APP_VERSION || '1.0.0';
const showUpdateToast = ref(false);
let pollInterval = null;

export function useAppUpdater() {
    const checkForUpdate = async () => {
        try {
            const response = await fetch(`/app-version?t=${Date.now()}`, {
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) return;
            const data = await response.json();

            if (data.version && data.version !== CURRENT_VERSION) {
                console.log(`[Updater] 🚀 New version available: ${data.version} (current bundle: ${CURRENT_VERSION})`);
                showUpdateToast.value = true;
                if (pollInterval) clearInterval(pollInterval);
            }
        } catch (e) {
            console.warn('[Updater] Failed to check version', e);
        }
    };

    const forceUpdate = async () => {
        showUpdateToast.value = false;

        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            // 1. Отправляем команду новому SW активироваться
            navigator.serviceWorker.controller.postMessage('SKIP_WAITING');

            // 2. Ждем подтверждения от SW, что он активировался и перехватил контроль
            await new Promise((resolve) => {
                const listener = (event) => {
                    if (event.data && event.data.type === 'SW_ACTIVATED') {
                        console.log('[Updater] ✅ SW confirmed activation. Ready to reload.');
                        navigator.serviceWorker.removeEventListener('message', listener);
                        resolve();
                    }
                };
                navigator.serviceWorker.addEventListener('message', listener);

                // Fallback: если по какой-то причине сообщение не пришло, перезагружаем через 2.5 сек
                setTimeout(() => {
                    navigator.serviceWorker.removeEventListener('message', listener);
                    resolve();
                }, 2500);
            });
        }

        // 3. 🆕 ЖЕСТКАЯ ПЕРЕЗАГРУЗКА С ОБХОДОМ КЭША
        // Добавляем уникальный параметр к URL, чтобы браузер и SW точно пошли в сеть за новым index.html
        const url = new URL(window.location.href);
        url.searchParams.set('bypass_sw_cache', Date.now().toString());

        // Используем window.location.href вместо reload() для гарантированного сетевого запроса
        window.location.href = url.toString();
    };

    onMounted(() => {
        checkForUpdate();
        pollInterval = setInterval(checkForUpdate, 15 * 60 * 1000);
        window.addEventListener('focus', checkForUpdate);
    });

    onUnmounted(() => {
        if (pollInterval) clearInterval(pollInterval);
        window.removeEventListener('focus', checkForUpdate);
    });

    return { showUpdateToast, forceUpdate, CURRENT_VERSION };
}
