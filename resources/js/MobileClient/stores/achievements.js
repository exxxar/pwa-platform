import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/achievements';

export const useAchievementsStore = defineStore('achievements', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        unlocked: [],           // Полученные достижения
        available: [],          // Доступные (ещё не полученные)
        progress: [],           // Прогресс по всем достижениям
        stats: {},              // Статистика пользователя

        // Состояние загрузки
        isLoading: false,
        isHydrated: false,
        isClaiming: false,

        // Действия над достижениями
        achievementActions: {},

        // Ошибки
        lastError: null,

        // 🆕 Недавно разблокированные (для анимации)
        recentlyUnlocked: [],

        // Время последней синхронизации
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Все достижения с прогрессом
         */
        allAchievements: (state) => state.progress || [],

        /**
         * Полученные достижения
         */
        unlockedAchievements: (state) => state.unlocked || [],

        /**
         * Доступные (не полученные) достижения
         */
        availableAchievements: (state) => state.available || [],

        /**
         * Достижения по категориям
         */
        achievementsByCategory: (state) => {
            const categories = {};
            (state.progress || []).forEach(item => {
                const category = item.achievement?.condition_type || 'other';
                if (!categories[category]) {
                    categories[category] = [];
                }
                categories[category].push(item);
            });
            return categories;
        },

        /**
         * Общий прогресс (средний процент)
         */
        overallProgress: (state) => {
            const progress = state.progress || [];
            if (progress.length === 0) return 0;
            const sum = progress.reduce((acc, item) => acc + (item.progress_percent || 0), 0);
            return Math.round(sum / progress.length);
        },

        /**
         * Количество полученных достижений
         */
        unlockedCount: (state) => (state.unlocked || []).length,

        /**
         * Общее количество достижений
         */
        totalCount: (state) => (state.progress || []).length,

        /**
         * Процент выполнения (получено / всего)
         */
        completionPercent: (state) => {
            const unlocked = (state.unlocked || []).length;
            const total = (state.progress || []).length;
            if (total === 0) return 0;
            return Math.round((unlocked / total) * 100);
        },

        /**
         * Неполученные награды (можно забрать)
         */
        unclaimedRewards: (state) => {
            return (state.unlocked || []).filter(
                item => !item.reward_claimed && item.achievement?.reward_value > 0
            );
        },

        /**
         * Количество неполученных наград
         */
        unclaimedRewardsCount: (state) => {
            return (state.unlocked || []).filter(
                item => !item.reward_claimed && item.achievement?.reward_value > 0
            ).length;
        },

        /**
         * Проверка, получено ли достижение
         */
        isUnlocked: (state) => (achievementId) => {
            return (state.unlocked || []).some(
                item => String(item.achievement_id) === String(achievementId)
            );
        },

        /**
         * Получить прогресс конкретного достижения
         */
        getAchievementProgress: (state) => (achievementId) => {
            return (state.progress || []).find(
                item => String(item.achievement?.id) === String(achievementId)
            );
        },

        /**
         * Статистика пользователя
         */
        userStats: (state) => state.stats || {},

        /**
         * Проверка, загружается ли достижение
         */
        isAchievementLoading: (state) => (id) => {
            return !!state.achievementActions[String(id)];
        },

        /**
         * Недавно разблокированные (для уведомлений)
         */
        recentUnlocks: (state) => state.recentlyUnlocked || [],
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка всех данных о достижениях
         */
        async loadAchievements() {
            this.isLoading = true;
            this.lastError = null;

            try {
                const response = await axios.get(BASE);
                const data = response.data;

                this.unlocked = data.unlocked || [];
                this.available = data.available || [];
                this.progress = data.progress || [];
                this.stats = data.stats || {};

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                return data;
            } catch (error) {
                console.error('[Achievements] Ошибка загрузки:', error);
                this.lastError = error.response?.data?.message || 'Не удалось загрузить достижения';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка только статистики
         */
        async loadStats() {
            try {
                const response = await axios.get(`${BASE}/stats`);
                this.stats = response.data.stats || {};
                return this.stats;
            } catch (error) {
                console.error('[Achievements] Ошибка загрузки статистики:', error);
                throw error;
            }
        },

        // ==========================================
        // ПОЛУЧЕНИЕ НАГРАДЫ
        // ==========================================

        /**
         * Забрать награду за достижение (оптимистично)
         */
        async claimReward(achievementId) {
            this.achievementActions[String(achievementId)] = 'claim';
            this.isClaiming = true;

            // Сохраняем предыдущее состояние для отката
            const unlockedIndex = this.unlocked.findIndex(
                item => String(item.achievement_id) === String(achievementId)
            );
            const previousState = unlockedIndex !== -1
                ? { ...this.unlocked[unlockedIndex] }
                : null;

            // Оптимистично отмечаем как полученное
            if (unlockedIndex !== -1) {
                this.unlocked[unlockedIndex].reward_claimed = 1;
            }

            try {
                const response = await axios.post(`${BASE}/${achievementId}/claim`);

                // Синхронизируем с сервером
                if (response.data?.reward && unlockedIndex !== -1) {
                    this.unlocked[unlockedIndex].reward_claimed = 1;
                }

                return response.data;
            } catch (error) {
                // Откат
                if (previousState && unlockedIndex !== -1) {
                    this.unlocked[unlockedIndex] = previousState;
                }
                console.error('[Achievements] Ошибка получения награды:', error);
                this.lastError = error.response?.data?.message || 'Не удалось получить награду';
                throw error;
            } finally {
                delete this.achievementActions[String(achievementId)];
                this.isClaiming = false;
            }
        },

        /**
         * Забрать все неполученные награды
         */
        async claimAllRewards() {
            const unclaimed = this.unclaimedRewards;
            const results = [];

            for (const item of unclaimed) {
                try {
                    const result = await this.claimReward(item.achievement_id);
                    results.push({ success: true, ...result });
                } catch (error) {
                    results.push({ success: false, error });
                }
            }

            return results;
        },

        // ==========================================
        // 🆕 ОБРАБОТКА НОВЫХ РАЗБЛОКИРОВОК
        // ==========================================

        /**
         * Добавить достижение в список недавно разблокированных
         * (вызывается при получении уведомления от сервера)
         */
        addRecentlyUnlocked(achievement) {
            this.recentlyUnlocked.push({
                ...achievement,
                unlockedAt: new Date().toISOString(),
            });

            // Автоматически убираем через 10 секунд
            setTimeout(() => {
                this.recentlyUnlocked = this.recentlyUnlocked.filter(
                    item => item.id !== achievement.id
                );
            }, 10000);
        },

        /**
         * Очистить список недавних разблокировок
         */
        clearRecentlyUnlocked() {
            this.recentlyUnlocked = [];
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.unlocked = [];
            this.available = [];
            this.progress = [];
            this.stats = {};
            this.isLoading = false;
            this.isHydrated = false;
            this.isClaiming = false;
            this.achievementActions = {};
            this.lastError = null;
            this.recentlyUnlocked = [];
            this.lastSyncAt = null;
        },
    },
});
