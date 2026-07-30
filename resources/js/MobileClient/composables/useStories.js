import { storeToRefs } from 'pinia';
import { useStoriesStore } from '@/MobileClient/stores/Shop/stories.js';

/**
 * Composable для работы с историями
 */
export function useStories() {
    const store = useStoriesStore();

    // ✅ ИСПРАВЛЕНИЕ 1: Извлекаем ВСЁ (состояние и геттеры) через storeToRefs.
    // Импорт 'computed' больше не нужен. Pinia автоматически обеспечит реактивность геттеров.
    const {
        // --- Состояние (State) ---
        stories,
        stories_paginate_object,
        isLoading,
        isHydrated,
        isStoring,
        storyActions,
        lastError,
        errors,
        lastSyncAt,

        // --- Геттеры (Getters) ---
        sortedStories,
        activeStories,
        archivedStories,
        recentStories,
        storiesCount,
    } = storeToRefs(store);

    // ==========================================
    // ПАРАМЕТРИЗИРОВАННЫЕ ХЕЛПЕРЫ
    // ==========================================
    const isViewed = (storyId) => store.isViewed(storyId);
    const markAsViewed = (storyId) => store.markAsViewed(storyId);
    const getStoryById = (id) => store.getStoryById(id);

    // ==========================================
    // МЕТОДЫ (ACTIONS) С ОБРАБОТКОЙ ОШИБОК
    // ==========================================

    const loadStories = async (payload = {}) => {
        try {
            return await store.loadStories(payload);
        } catch (error) {
            console.error('[useStories] Ошибка загрузки историй:', error);
            throw error;
        }
    };

    const fetchStory = async (storyId) => {
        try {
            return await store.fetchStory(storyId);
        } catch (error) {
            console.error(`[useStories] Ошибка загрузки истории ${storyId}:`, error);
            throw error;
        }
    };

    const saveStory = async (storyForm) => {
        try {
            return await store.saveStory(storyForm);
        } catch (error) {
            console.error('[useStories] Ошибка сохранения истории:', error);
            throw error;
        }
    };

    const deleteStory = async (storyId) => {
        try {
            return await store.deleteStory(storyId);
        } catch (error) {
            console.error(`[useStories] Ошибка удаления истории ${storyId}:`, error);
            throw error;
        }
    };

    const archiveStory = async (storyId) => {
        try {
            return await store.archiveStory(storyId);
        } catch (error) {
            console.error(`[useStories] Ошибка архивации истории ${storyId}:`, error);
            throw error;
        }
    };

    const toggleActive = async (storyId) => {
        try {
            return await store.toggleStoryActive(storyId);
        } catch (error) {
            console.error(`[useStories] Ошибка переключения активности истории ${storyId}:`, error);
            throw error;
        }
    };

    // ==========================================
    // ВОЗВРАЩАЕМЫЕ ЗНАЧЕНИЯ
    // ==========================================
    return {
        // Состояние (Refs)
        stories,
        stories_paginate_object,
        isLoading,
        isHydrated,
        isStoring,
        storyActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры (Refs)
        sortedStories,
        activeStories,
        archivedStories,
        recentStories,
        storiesCount,

        // Хелперы
        isViewed,
        markAsViewed,
        getStoryById,

        // Методы (Actions)
        loadStories,
        fetchStory,
        saveStory,
        deleteStory,
        archiveStory,
        toggleActive,
        loadPartnersStories: store.loadPartnersStories, // Прямой маппинг, если доп. логика не нужна

        // Сброс стора
        $reset: store.$reset,
    };
}
