import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/stories';

export const useStoriesStore = defineStore('stories', {
    state: () => ({
        stories: [],
        stories_paginate_object: null,

        // 🆕 Просмотренные истории (в памяти + localStorage)
        viewedStoryIds: JSON.parse(localStorage.getItem('viewed_stories') || '[]'),

        isLoading: false,
        isHydrated: false,
        isStoring: false,
        storyActions: {},
        lastError: null,
        errors: [],
        lastSyncAt: null,
    }),

    getters: {
        getStories: (state) => state.stories || [],

        getStoriesPaginateObject: (state) => state.stories_paginate_object || null,

        getStoryById: (state) => (id) => {
            return state.stories.find(item => String(item.id) === String(id)) || null;
        },

        sortedStories: (state) => {
            return [...(state.stories || [])].sort((a, b) => {
                const dateA = new Date(a.created_at || a.published_at || 0);
                const dateB = new Date(b.created_at || b.published_at || 0);
                return dateB - dateA;
            });
        },

        activeStories: (state) => {
            return (state.stories || []).filter(s =>
                s.is_active !== false &&
                s.is_published !== false &&
                !s.is_archived
            );
        },

        archivedStories: (state) => {
            return (state.stories || []).filter(s => s.is_archived);
        },

        recentStories: (state) => {
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            return (state.stories || []).filter(r => {
                const date = new Date(r.created_at || 0);
                return date >= weekAgo;
            });
        },

        // 🆕 Проверка, просмотрена ли история
        isViewed: (state) => (storyId) => {
            return state.viewedStoryIds.includes(String(storyId));
        },

        storiesCount: (state) => state.stories?.length || 0,
    },

    actions: {
        // ==========================================
        // 🆕 ПРОСМОТРЕННЫЕ ИСТОРИИ
        // ==========================================

        /**
         * Отметить историю как просмотренную
         */
        markAsViewed(storyId) {
            const id = String(storyId);
            if (!this.viewedStoryIds.includes(id)) {
                this.viewedStoryIds.push(id);
                localStorage.setItem('viewed_stories', JSON.stringify(this.viewedStoryIds));
            }
        },

        /**
         * Сбросить историю просмотров
         */
        clearViewedHistory() {
            this.viewedStoryIds = [];
            localStorage.removeItem('viewed_stories');
        },

        // ==========================================
        // ЗАГРУЗКА
        // ==========================================
        async loadPartnersStories(partnerId = null) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const page =  1;
                const size = 20;
                const link = `${BASE}?page=${page}&size=${size}`;

                const response = await axios.get(link, {
                    params: { partner_id: partnerId }
                });
                const dataObject = response.data;

                this.stories = dataObject.data || [];
                const { data, ...paginate } = dataObject;
                this.stories_paginate_object = paginate;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                localStorage.setItem('cashman_stories', JSON.stringify(this.stories));
                localStorage.setItem('cashman_stories_paginate_object', JSON.stringify(paginate));

                return paginate;
            } catch (err) {
                console.error('[Stories Store] Ошибка загрузки:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить истории';
                this.errors = err.response?.data?.errors || [];

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

                localStorage.setItem('cashman_stories', JSON.stringify(this.stories));
                localStorage.setItem('cashman_stories_paginate_object', JSON.stringify(paginate));

                return paginate;
            } catch (err) {
                console.error('[Stories Store] Ошибка загрузки:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить истории';
                this.errors = err.response?.data?.errors || [];

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

        async fetchStory(payload = { id: null }) {
            try {
                const response = await axios.get(`${BASE}/${payload.id}`);
                return response.data;
            } catch (err) {
                console.error('[Stories Store] Ошибка загрузки истории:', err);
                throw err;
            }
        },

        // ==========================================
        // CRUD
        // ==========================================

        async saveStory(storyForm) {
            this.isStoring = true;

            const storyId = storyForm?.id;
            if (storyId) {
                this.storyActions[String(storyId)] = 'update';
            }

            const previousState = storyId ? { ...this.getStoryById(storyId) } : null;

            try {
                const response = await axios.post(BASE, storyForm);
                const savedStory = response.data?.data || response.data;

                if (savedStory?.id) {
                    if (storyId) {
                        const index = this.stories.findIndex(s => String(s.id) === String(savedStory.id));
                        if (index !== -1) {
                            this.stories[index] = { ...this.stories[index], ...savedStory };
                        }
                    } else {
                        this.stories.unshift(savedStory);
                    }
                }

                return savedStory;
            } catch (err) {
                console.error('[Stories Store] Ошибка сохранения:', err);
                throw err;
            } finally {
                this.isStoring = false;
                if (storyId) {
                    delete this.storyActions[String(storyId)];
                }
            }
        },

        async deleteStory(storyId) {
            this.storyActions[String(storyId)] = 'delete';

            const removedIndex = this.stories.findIndex(s => String(s.id) === String(storyId));
            const removedStory = removedIndex !== -1 ? this.stories[removedIndex] : null;

            if (removedIndex !== -1) {
                this.stories.splice(removedIndex, 1);
            }

            try {
                const response = await axios.delete(`${BASE}/${storyId}`);
                return response.data;
            } catch (err) {
                if (removedStory && removedIndex !== -1) {
                    this.stories.splice(removedIndex, 0, removedStory);
                }
                console.error('[Stories Store] Ошибка удаления:', err);
                throw err;
            } finally {
                delete this.storyActions[String(storyId)];
            }
        },

        async archiveStory(storyId) {
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

        async toggleStoryActive(storyId) {
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

        $reset() {
            this.stories = [];
            this.stories_paginate_object = null;
            this.viewedStoryIds = [];
            this.isLoading = false;
            this.isHydrated = false;
            this.isStoring = false;
            this.storyActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('cashman_stories');
            localStorage.removeItem('cashman_stories_paginate_object');
        },
    },
});
