<template>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">{{ unlockedCount }} / {{ totalCount }}</div>
                <div class="stat-label">Достижений</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">{{ completionPercent }}%</div>
                <div class="stat-label">Выполнено</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i class="fa-solid fa-gift"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">{{ unclaimedRewardsCount }}</div>
                <div class="stat-label">Наград ждать</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">{{ formatNumber(stats.orders_count || 0) }}</div>
                <div class="stat-label">Заказов</div>
            </div>
        </div>
    </div>
</template>

<script>
import { useAchievements } from '@/MobileClient/Composables/useAchievements.js';

export default {
    name: 'AchievementStats',

    setup() {
        const achievements = useAchievements();
        return {
            unlockedCount: achievements.unlockedCount,
            totalCount: achievements.totalCount,
            completionPercent: achievements.completionPercent,
            unclaimedRewardsCount: achievements.unclaimedRewardsCount,
            stats: achievements.userStats,
        };
    },

    methods: {
        formatNumber(value) {
            return new Intl.NumberFormat('ru-RU').format(value);
        },
    },
};
</script>

<style lang="scss" scoped>
$bg: var(--bs-body-bg, #ffffff);
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    transition: all 0.2s;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
}

.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.stat-info {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: $text;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.75rem;
    color: $text-muted;
    margin-top: 2px;
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-card {
        padding: 10px;
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }

    .stat-value {
        font-size: 0.95rem;
    }
}
</style>
