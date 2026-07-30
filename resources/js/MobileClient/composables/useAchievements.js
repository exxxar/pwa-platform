import { storeToRefs } from 'pinia';
import { useAchievementsStore } from '@/MobileClient/stores/achievements.js';

/**
 * Composable для работы с достижениями
 */
export function useAchievements() {
    const store = useAchievementsStore();

    // ✅ ИСПРАВЛЕНИЕ 1: Извлекаем ВСЁ (и состояние, и геттеры) через storeToRefs.
    // Pinia автоматически превратит геттеры в реактивные ref-ссылки.
    // Ручные computed() здесь не нужны и даже вредны (лишние пересчеты).
    const {
        // --- Состояние (State) ---
        unlocked,
        available,
        progress,
        stats,
        isLoading,
        isHydrated,
        isClaiming,
        achievementActions,
        lastError,
        recentlyUnlocked, // Массив/объект состояния
        lastSyncAt,

        // --- Геттеры (Getters) ---
        // Pinia сама обеспечит их реактивность
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
        recentUnlocks, // Геттер (обратите внимание на 's' на конце, как в оригинале)
    } = storeToRefs(store);

    // ==========================================
    // Методы с аргументами (Parameterized Getters/Actions)
    // ==========================================
    // Возвращаем их как есть. Если в сторе это геттеры, возвращающие функции,
    // Vue корректно отследит их при использовании в шаблоне.
    const isUnlocked = (achievementId) => store.isUnlocked(achievementId);
    const getAchievementProgress = (achievementId) => store.getAchievementProgress(achievementId);
    const isAchievementLoading = (id) => store.isAchievementLoading(id);

    // ==========================================
    // МЕТОДЫ (Actions) с безопасной обработкой ошибок
    // ==========================================
    const loadAchievements = async () => {
        try {
            return await store.loadAchievements();
        } catch (error) {
            console.error('[useAchievements] Ошибка загрузки достижений:', error);
            throw error;
        }
    };

    const loadStats = async () => {
        try {
            return await store.loadStats();
        } catch (error) {
            console.error('[useAchievements] Ошибка загрузки статистики:', error);
            throw error;
        }
    };

    const claimReward = async (achievementId) => {
        try {
            return await store.claimReward(achievementId);
        } catch (error) {
            console.error(`[useAchievements] Ошибка получения награды ${achievementId}:`, error);
            throw error;
        }
    };

    const claimAllRewards = async () => {
        try {
            return await store.claimAllRewards();
        } catch (error) {
            console.error('[useAchievements] Ошибка получения всех наград:', error);
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
    // УТИЛИТЫ (Чистые функции, реактивность не требуется)
    // ==========================================

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

    const formatRewardType = (type) => {
        const labels = {
            cashback: '₽',
            discount: '%',
            points: 'баллов',
            none: '',
        };
        return labels[type] || '';
    };

    const formatRewardValue = (value, type) => {
        if (!value) return '';
        const formatted = new Intl.NumberFormat('ru-RU').format(value);
        return `${formatted} ${formatRewardType(type)}`.trim();
    };

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

    // ==========================================
    // Возврат API композабла
    // ==========================================
    return {
        // Состояние (Refs)
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

        // Геттеры (Refs)
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

        // Методы с аргументами
        isUnlocked,
        getAchievementProgress,
        isAchievementLoading,

        // Экшены (Actions)
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

        // Сброс стора
        $reset: store.$reset,
    };
}
