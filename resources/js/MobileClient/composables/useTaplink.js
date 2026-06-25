import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useTaplinkStore } from '@/MobileClient/stores/Shop/taplink.js';

/**
 * Composable для работы с TapLink
 */
export function useTaplink() {
    const store = useTaplinkStore();

    // Реактивные ссылки
    const {
        profile,
        links,
        currentSlug,
        isLoading,
        isHydrated,
        lastError,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const profileName = computed(() => store.profileName);
    const profileDescription = computed(() => store.profileDescription);
    const profileAvatar = computed(() => store.profileAvatar);
    const themeColor = computed(() => store.themeColor);
    const gradientBackground = computed(() => store.gradientBackground);
    const socialLinks = computed(() => store.socialLinks);
    const mainLinks = computed(() => store.mainLinks);
    const activeLinks = computed(() => store.activeLinks);
    const linksCount = computed(() => store.linksCount);
    const isLoaded = computed(() => store.isLoaded);
    const canShare = computed(() => !!navigator.share);

    // Методы
    const loadTaplink = async (slug) => {
        try {
            return await store.loadTaplink(slug);
        } catch (error) {
            console.error('Ошибка загрузки taplink:', error);
            throw error;
        }
    };

    const shareProfile = async () => {
        try {
            return await store.shareProfile();
        } catch (error) {
            console.error('Ошибка шеринга:', error);
            throw error;
        }
    };

    const copyLink = async () => {
        try {
            return await store.copyLink();
        } catch (error) {
            console.error('Ошибка копирования:', error);
            throw error;
        }
    };

    return {
        // Состояние
        profile,
        links,
        currentSlug,
        isLoading,
        isHydrated,
        lastError,
        lastSyncAt,

        // Геттеры
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
        canShare,

        // Методы
        loadTaplink,
        shareProfile,
        copyLink,

        // Сброс
        $reset: store.$reset,
    };
}
