import { storeToRefs } from 'pinia';
import { useTaplinkStore } from '@/MobileClient/stores/Shop/taplink.js';

/**
 * Composable для работы с TapLink
 */
export function useTaplink() {
    const store = useTaplinkStore();

    // ✅ ИСПРАВЛЕНИЕ 1: Извлекаем и состояние, и геттеры через storeToRefs.
    // Pinia автоматически обеспечит реактивность геттеров. Импорт 'computed' больше не нужен.
    const {
        // --- Состояние (State) ---
        profile,
        links,
        currentSlug,
        isLoading,
        isHydrated,
        lastError,
        lastSyncAt,

        // --- Геттеры (Getters) ---
        profileName,
        profileDescription,
        profileAvatar,
        themeColor,
        gradientBackground,
        socialLinks,
        mainLinks,
        activeLinks,
        linksCount,
        isLoaded,
    } = storeToRefs(store);

    // ✅ ИСПРАВЛЕНИЕ 2: canShare не является геттером стора.
    // navigator.share — это статическая возможность браузера, она не меняется реактивно.
    // Нет смысла делать это computed(). Простой константы достаточно (с защитой от SSR, если вдруг).
    const canShare = typeof navigator !== 'undefined' && !!navigator.share;

    // ==========================================
    // Безопасные методы (Actions) с обработкой ошибок
    // ==========================================

    const loadTaplink = async (slug) => {
        try {
            return await store.loadTaplink(slug);
        } catch (error) {
            console.error('[useTaplink] Ошибка загрузки taplink:', error);
            throw error;
        }
    };

    const shareProfile = async () => {
        try {
            return await store.shareProfile();
        } catch (error) {
            console.error('[useTaplink] Ошибка шеринга:', error);
            throw error;
        }
    };

    const copyLink = async () => {
        try {
            return await store.copyLink();
        } catch (error) {
            console.error('[useTaplink] Ошибка копирования:', error);
            throw error;
        }
    };

    // ==========================================
    // Возврат API композабла
    // ==========================================
    return {
        // Состояние (Refs)
        profile,
        links,
        currentSlug,
        isLoading,
        isHydrated,
        lastError,
        lastSyncAt,

        // Геттеры (Refs)
        profileName,
        profileDescription,
        profileAvatar,
        themeColor,
        gradientBackground,
        socialLinks,
        mainLinks,
        activeLinks,
        linksCount,
        isLoaded,

        // Свойства окружения
        canShare,

        // Методы (Actions)
        loadTaplink,
        shareProfile,
        copyLink,

        // Сброс стора
        $reset: store.$reset,
    };
}
