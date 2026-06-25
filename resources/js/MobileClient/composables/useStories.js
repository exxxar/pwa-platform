import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useStoriesStore } from '@/stores/stories.js';

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
    const activeStoriesCount = computed(() => store.activeStoriesCount);

    /**
     * Проверка, загружается ли история
     */
    const isStoryLoading = (storyId) => {
        return store.isStoryLoading(storyId);
    };

    /**
     * Проверка, просмотрена ли история
     */
    const isViewed = (storyId) => {
        const story = store.getStoryById(storyId);
        return story?.is_viewed || false;
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
            return await store.fetchStory({ id: storyId });
        } catch (error) {
            console.error('Ошибка загрузки истории:', error);
            throw error;
        }
    };

    const saveStory = async (storyForm) => {
        try {
            return await store.saveStory({ storyForm });
        } catch (error) {
            console.error('Ошибка сохранения истории:', error);
            throw error;
        }
    };

    const deleteStory = async (storyId) => {
        try {
            return await store.deleteStory({ id: storyId });
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

    const markAsViewed = (storyId) => {
        store.markAsViewed(storyId);
    };

    return {
        // Состояние
        stories,
        stories_paginate_object,
        isLoading,
        isHydrated,
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
        activeStoriesCount,
        getStoryById: store.getStoryById,
        isStoryLoading,
        isViewed,

        // Методы
        loadStories,
        fetchStory,
        saveStory,
        deleteStory,
        archiveStory,
        toggleActive,
        markAsViewed,

        // Сброс
        $reset: store.$reset,
    };
}
