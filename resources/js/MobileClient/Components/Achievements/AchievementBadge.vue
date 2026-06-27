<template>
    <router-link
        :to="{ name: 'Achievements' }"
        class="achievement-badge-link"
    >
        <div class="badge-icon">
            <i class="fa-solid fa-trophy"></i>
            <span v-if="unclaimedRewardsCount > 0" class="badge-dot"></span>
        </div>
        <div class="badge-info">
            <span class="badge-title">Достижения</span>
            <span class="badge-subtitle">
                {{ unlockedCount }}/{{ totalCount }}
            </span>
        </div>
        <span v-if="unclaimedRewardsCount > 0" class="rewards-count">
            +{{ unclaimedRewardsCount }}
        </span>
    </router-link>
</template>

<script>
import { useAchievements } from '@/MobileClient/Composables/useAchievements.js';

export default {
    name: 'AchievementBadge',

    setup() {
        const achievements = useAchievements();
        return {
            unlockedCount: achievements.unlockedCount,
            totalCount: achievements.totalCount,
            unclaimedRewardsCount: achievements.unclaimedRewardsCount,
        };
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$success: #22c55e;
$warning: #f59e0b;
$bg: var(--bs-body-bg, #ffffff);
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);

.achievement-badge-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    text-decoration: none;
    color: $text;
    transition: all 0.2s;

    &:hover {
        background: $bg;
        border-color: $primary;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
}

.badge-icon {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, $warning 0%, #d97706 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.badge-dot {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 10px;
    height: 10px;
    background: $success;
    border: 2px solid $bg;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.badge-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.badge-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
}

.badge-subtitle {
    font-size: 0.75rem;
    color: $text-muted;
}

.rewards-count {
    padding: 3px 8px;
    background: rgba($success, 0.1);
    color: $success;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}
</style>
