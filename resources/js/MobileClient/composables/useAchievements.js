import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useAchievementsStore } from '@/MobileClient/stores/achievements.js';

/**
 * Composable для работы с достижениями
 */
export function useAchievements() {
    const store = useAchievementsStore();

    // Реактивные ссылки
    const {
        unlocked,
        available,
        progress,
        stats,
        isLoading,
        isHydrated,
        isClaiming,
        achievementActions,
        lastError,
        recentlyUnlocked,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const allAchievements = computed(() => store.allAchievements);
    const unlockedAchievements = computed(() => store.unlockedAchievements);
    const availableAchievements = computed(() => store.availableAchievements);
    const achievementsByCategory = computed(() => store.achievementsByCategory);
    const overallProgress = computed(() => store.overallProgress);
    const unlockedCount = computed(() => store.unlockedCount);
    const totalCount = computed(() => store.totalCount);
    const completionPercent = computed(() => store.completionPercent);
    const unclaimedRewards = computed(() => store.unclaimedRewards);
    const unclaimedRewardsCount = computed(() => store.unclaimedRewardsCount);
    const userStats = computed(() => store.userStats);
    const recentUnlocks = computed(() => store.recentlyUnlocks);

    /**
     * Проверка, получено ли достижение
     */
    const isUnlocked = (achievementId) => {
        return store.isUnlocked(achievementId);
    };

    /**
     * Получить прогресс достижения
     */
    const getAchievementProgress = (achievementId) => {
        return store.getAchievementProgress(achievementId);
    };

    /**
     * Проверка загрузки достижения
     */
    const isAchievementLoading = (id) => {
        return store.isAchievementLoading(id);
    };

    // ==========================================
    // МЕТОДЫ
    // ==========================================

    const loadAchievements = async () => {
        try {
            return await store.loadAchievements();
        } catch (error) {
            console.error('Ошибка загрузки достижений:', error);
            throw error;
        }
    };

    const loadStats = async () => {
        try {
            return await store.loadStats();
        } catch (error) {
            console.error('Ошибка загрузки статистики:', error);
            throw error;
        }
    };

    const claimReward = async (achievementId) => {
        try {
            return await store.claimReward(achievementId);
        } catch (error) {
            console.error('Ошибка получения награды:', error);
            throw error;
        }
    };

    const claimAllRewards = async () => {
        try {
            return await store.claimAllRewards();
        } catch (error) {
            console.error('Ошибка получения всех наград:', error);
            throw error;
        }
    };

    const addRecentlyUnlocked = (achievement) => {
        store.addRecentlyUnlocked(achievement);
    };

    const clearRecentlyUnlocked = () => {
        store.clearRecentlyUnlocked();
    };

    // ==========================================
    // УТИЛИТЫ
    // ==========================================

    /**
     * Форматирование названия типа условия
     */
    const formatConditionType = (type) => {
        const labels = {
            orders_count: 'Заказы',
            orders_sum: 'Сумма заказов',
            reviews_count: 'Отзывы',
            cashback_earned: 'Кэшбэк получен',
            cashback_spent: 'Кэшбэк потрачен',
            friends_invited: 'Друзья',
            games_played: 'Игры',
            products_viewed: 'Просмотры',
            days_registered: 'Дней с нами',
            products_in_cart: 'В корзине',
        };
        return labels[type] || type;
    };

    /**
     * Форматирование типа награды
     */
    const formatRewardType = (type) => {
        const labels = {
            cashback: '₽',
            discount: '%',
            points: 'баллов',
            none: '',
        };
        return labels[type] || '';
    };

    /**
     * Форматирование значения награды
     */
    const formatRewardValue = (value, type) => {
        if (!value) return '';
        const formatted = new Intl.NumberFormat('ru-RU').format(value);
        return `${formatted} ${formatRewardType(type)}`.trim();
    };

    /**
     * Иконка для категории
     */
    const getCategoryIcon = (type) => {
        const icons = {
            orders_count: 'fa-solid fa-bag-shopping',
            orders_sum: 'fa-solid fa-money-bill-wave',
            reviews_count: 'fa-solid fa-comment',
            cashback_earned: 'fa-solid fa-coins',
            cashback_spent: 'fa-solid fa-wallet',
            friends_invited: 'fa-solid fa-user-group',
            games_played: 'fa-solid fa-gamepad',
            products_viewed: 'fa-solid fa-eye',
            days_registered: 'fa-solid fa-calendar',
            products_in_cart: 'fa-solid fa-cart-plus',
        };
        return icons[type] || 'fa-solid fa-trophy';
    };

    /**
     * Цвет для категории
     */
    const getCategoryColor = (type) => {
        const colors = {
            orders_count: '#667eea',
            orders_sum: '#10b981',
            reviews_count: '#f59e0b',
            cashback_earned: '#fbbf24',
            cashback_spent: '#ef4444',
            friends_invited: '#8b5cf6',
            games_played: '#ec4899',
            products_viewed: '#06b6d4',
            days_registered: '#6366f1',
            products_in_cart: '#14b8a6',
        };
        return colors[type] || '#6b7280';
    };

    return {
        // Состояние
        unlocked,
        available,
        progress,
        stats,
        isLoading,
        isHydrated,
        isClaiming,
        achievementActions,
        lastError,
        recentlyUnlocked,
        lastSyncAt,

        // Геттеры
        allAchievements,
        unlockedAchievements,
        availableAchievements,
        achievementsByCategory,
        overallProgress,
        unlockedCount,
        totalCount,
        completionPercent,
        unclaimedRewards,
        unclaimedRewardsCount,
        userStats,
        recentUnlocks,
        isUnlocked,
        getAchievementProgress,
        isAchievementLoading,

        // Методы
        loadAchievements,
        loadStats,
        claimReward,
        claimAllRewards,
        addRecentlyUnlocked,
        clearRecentlyUnlocked,

        // Утилиты
        formatConditionType,
        formatRewardType,
        formatRewardValue,
        getCategoryIcon,
        getCategoryColor,

        // Сброс
        $reset: store.$reset,
    };
}
