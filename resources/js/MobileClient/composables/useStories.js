import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useStoriesStore } from '@/MobileClient/stores/Shop/stories.js';

/**
 * Composable для работы с историями
 */
export function useStories() {
    const store = useStoriesStore();

    // Реактивные ссылки на состояние
    const {
        stories,
        stories_paginate_object,
        isLoading,
        isHydrated,
        isStoring,
        storyActions,
        lastError,
        errors,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedStories = computed(() => store.sortedStories);
    const activeStories = computed(() => store.activeStories);
    const archivedStories = computed(() => store.archivedStories);
    const recentStories = computed(() => store.recentStories);
    const storiesCount = computed(() => store.storiesCount);

    /**
     * Проверка, просмотрена ли история (реактивно)
     */
    const isViewed = (storyId) => {
        return store.isViewed(storyId);
    };

    /**
     * Отметить историю как просмотренную
     */
    const markAsViewed = (storyId) => {
        store.markAsViewed(storyId);
    };

    // ==========================================
    // Безопасные методы
    // ==========================================

    const loadStories = async (payload = {}) => {
        try {
            return await store.loadStories(payload);
        } catch (error) {
            console.error('Ошибка загрузки историй:', error);
            throw error;
        }
    };

    const fetchStory = async (storyId) => {
        try {
            return await store.fetchStory(storyId);
        } catch (error) {
            console.error('Ошибка загрузки истории:', error);
            throw error;
        }
    };

    const saveStory = async (storyForm) => {
        try {
            return await store.saveStory(storyForm);
        } catch (error) {
            console.error('Ошибка сохранения истории:', error);
            throw error;
        }
    };

    const deleteStory = async (storyId) => {
        try {
            return await store.deleteStory(storyId);
        } catch (error) {
            console.error('Ошибка удаления истории:', error);
            throw error;
        }
    };

    const archiveStory = async (storyId) => {
        try {
            return await store.archiveStory(storyId);
        } catch (error) {
            console.error('Ошибка архивации:', error);
            throw error;
        }
    };

    const toggleActive = async (storyId) => {
        try {
            return await store.toggleStoryActive(storyId);
        } catch (error) {
            console.error('Ошибка переключения активности:', error);
            throw error;
        }
    };

    return {
        // Состояние
        stories,
        stories_paginate_object,
        isLoading,
        isHydrated,
        isStoring,
        storyActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры
        sortedStories,
        activeStories,
        archivedStories,
        recentStories,
        storiesCount,
        getStoryById: store.getStoryById,
        isViewed,

        // Методы
        loadStories,
        fetchStory,
        saveStory,
        deleteStory,
        archiveStory,
        toggleActive,
        markAsViewed,
        loadPartnersStories: store.loadPartnersStories,
        // Сброс
        $reset: store.$reset,
    };
}
