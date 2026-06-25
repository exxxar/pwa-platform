import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/stories';

export const useStoriesStore = defineStore('stories', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        stories: [],
        stories_paginate_object: null,

        // Состояние загрузки
        isLoading: false,
        isHydrated: false,

        // Действия над историями: { [storyId]: 'delete' | 'update' }
        storyActions: {},

        // Ошибки
        lastError: null,
        errors: [],

        // Время последней синхронизации
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Все истории
         */
        getStories: (state) => state.stories || [],

        /**
         * Пагинация
         */
        getStoriesPaginateObject: (state) => state.stories_paginate_object || null,

        /**
         * Найти историю по ID
         */
        getStoryById: (state) => (id) => {
            return state.stories.find(item => String(item.id) === String(id)) || null;
        },

        /**
         * Истории отсортированные (новые сверху)
         */
        sortedStories: (state) => {
            return [...(state.stories || [])].sort((a, b) => {
                const dateA = new Date(a.created_at || a.published_at || 0);
                const dateB = new Date(b.created_at || b.published_at || 0);
                return dateB - dateA;
            });
        },

        /**
         * Активные (опубликованные) истории
         */
        activeStories: (state) => {
            return (state.stories || []).filter(s =>
                s.is_active !== false &&
                s.is_published !== false &&
                !s.is_archived
            );
        },

        /**
         * Архивные истории
         */
        archivedStories: (state) => {
            return (state.stories || []).filter(s => s.is_archived);
        },

        /**
         * Недавно добавленные (за последние 24 часа)
         */
        recentStories: (state) => {
            const dayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
            return (state.stories || []).filter(s => {
                const date = new Date(s.created_at || s.published_at || 0);
                return date > dayAgo;
            });
        },

        /**
         * Проверка, загружается ли конкретная история
         */
        isStoryLoading: (state) => (id) => {
            return !!state.storyActions[String(id)];
        },

        /**
         * Общее количество историй
         */
        storiesCount: (state) => state.stories?.length || 0,

        /**
         * Количество активных историй
         */
        activeStoriesCount: (state) => {
            return (state.stories || []).filter(s =>
                s.is_active !== false && !s.is_archived
            ).length;
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка списка историй
         */
        async loadStories(payload = { page: 1, size: 20 }) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const page = payload.page || 1;
                const size = payload.size || 20;
                const link = `${BASE}?page=${page}&size=${size}`;

                const response = await axios.get(link);
                const dataObject = response.data;

                this.stories = dataObject.data || [];
                const { data, ...paginate } = dataObject;
                this.stories_paginate_object = paginate;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                // Сохраняем в localStorage как fallback
                localStorage.setItem('cashman_stories', JSON.stringify(this.stories));
                localStorage.setItem('cashman_stories_paginate_object', JSON.stringify(paginate));

                return paginate;
            } catch (err) {
                console.error('[Stories Store] Ошибка загрузки историй:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить истории';
                this.errors = err.response?.data?.errors || [];

                // Fallback: пробуем загрузить из localStorage
                const cached = localStorage.getItem('cashman_stories');
                if (cached && this.stories.length === 0) {
                    try {
                        this.stories = JSON.parse(cached);
                    } catch {
                        this.stories = [];
                    }
                }

                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка одной истории
         */
        async fetchStory(payload = { id: null }) {
            if (!payload.id) throw new Error('Не указан ID истории');

            try {
                const response = await axios.get(`${BASE}/${payload.id}`);
                const story = response.data?.data || response.data;

                // Обновляем в списке, если есть
                if (story?.id) {
                    const index = this.stories.findIndex(s => String(s.id) === String(story.id));
                    if (index !== -1) {
                        this.stories[index] = { ...this.stories[index], ...story };
                    }
                }

                return story;
            } catch (err) {
                console.error('[Stories Store] Ошибка загрузки истории:', err);
                throw err;
            }
        },

        // ==========================================
        // CRUD
        // ==========================================

        /**
         * Создание/обновление истории
         */
        async saveStory(payload = { storyForm: {} }) {
            this.lastError = null;

            const storyId = payload.storyForm?.id;
            const isUpdate = !!storyId;

            if (isUpdate) {
                this.storyActions[String(storyId)] = 'update';
            }

            // Сохраняем предыдущее состояние для отката
            let previousStory = null;
            let previousIndex = -1;
            if (isUpdate) {
                previousIndex = this.stories.findIndex(s => String(s.id) === String(storyId));
                if (previousIndex !== -1) {
                    previousStory = { ...this.stories[previousIndex] };
                }
            }

            try {
                const response = await axios.post(BASE, payload.storyForm);
                const savedStory = response.data?.data || response.data;

                if (savedStory?.id) {
                    if (isUpdate) {
                        // Обновляем существующую
                        const index = this.stories.findIndex(s => String(s.id) === String(savedStory.id));
                        if (index !== -1) {
                            this.stories[index] = { ...this.stories[index], ...savedStory };
                        }
                    } else {
                        // Добавляем новую в начало
                        this.stories.unshift(savedStory);
                    }
                }

                return savedStory;
            } catch (err) {
                console.error('[Stories Store] Ошибка сохранения истории:', err);
                this.lastError = err.response?.data?.message || 'Не удалось сохранить историю';
                throw err;
            } finally {
                if (isUpdate) {
                    delete this.storyActions[String(storyId)];
                }
            }
        },

        /**
         * Удаление истории (оптимистично)
         */
        async deleteStory(payload = { id: null }) {
            if (!payload.id) throw new Error('Не указан ID истории');

            this.storyActions[String(payload.id)] = 'delete';

            // Сохраняем для отката
            const previousStories = [...this.stories];
            const removedIndex = this.stories.findIndex(s => String(s.id) === String(payload.id));
            const removedStory = removedIndex !== -1 ? this.stories[removedIndex] : null;

            // Оптимистично удаляем
            if (removedIndex !== -1) {
                this.stories.splice(removedIndex, 1);
            }

            try {
                const response = await axios.delete(`${BASE}/${payload.id}`);
                return response.data;
            } catch (err) {
                // Откат
                if (removedStory && removedIndex !== -1) {
                    this.stories.splice(removedIndex, 0, removedStory);
                }
                console.error('[Stories Store] Ошибка удаления истории:', err);
                this.lastError = err.response?.data?.message || 'Не удалось удалить историю';
                throw err;
            } finally {
                delete this.storyActions[String(payload.id)];
            }
        },

        /**
         * Архивация истории (оптимистично)
         */
        async archiveStory(storyId) {
            if (!storyId) throw new Error('Не указан ID истории');

            this.storyActions[String(storyId)] = 'archive';

            const story = this.getStoryById(storyId);
            const previousState = story?.is_archived;

            if (story) {
                story.is_archived = !story.is_archived;
            }

            try {
                const response = await axios.post(`${BASE}/${storyId}/archive`);
                const updated = response.data?.data || response.data;

                if (story && updated) {
                    Object.assign(story, updated);
                }

                return updated;
            } catch (err) {
                if (story) {
                    story.is_archived = previousState;
                }
                console.error('[Stories Store] Ошибка архивации:', err);
                throw err;
            } finally {
                delete this.storyActions[String(storyId)];
            }
        },

        /**
         * Переключение активности истории (оптимистично)
         */
        async toggleStoryActive(storyId) {
            if (!storyId) throw new Error('Не указан ID истории');

            this.storyActions[String(storyId)] = 'toggle-active';

            const story = this.getStoryById(storyId);
            const previousState = story?.is_active;

            if (story) {
                story.is_active = !story.is_active;
            }

            try {
                const response = await axios.post(`${BASE}/${storyId}/toggle-active`);
                const updated = response.data?.data || response.data;

                if (story && updated) {
                    Object.assign(story, updated);
                }

                return updated;
            } catch (err) {
                if (story) {
                    story.is_active = previousState;
                }
                console.error('[Stories Store] Ошибка переключения активности:', err);
                throw err;
            } finally {
                delete this.storyActions[String(storyId)];
            }
        },

        /**
         * Отметить историю как просмотренную
         */
        markAsViewed(storyId) {
            const story = this.getStoryById(storyId);
            if (story) {
                story.is_viewed = true;
                story.viewed_at = new Date().toISOString();
            }
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.stories = [];
            this.stories_paginate_object = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.storyActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('cashman_stories');
            localStorage.removeItem('cashman_stories_paginate_object');
        },
    },
});
