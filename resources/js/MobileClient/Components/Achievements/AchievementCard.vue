<template>
    <div
        class="achievement-card"
        :class="{
            'is-unlocked': isUnlocked,
            'is-progress': !isUnlocked && progress > 0,
            'is-locked': !isUnlocked && progress === 0,
            'has-reward': hasReward && !rewardClaimed,
        }"
    >
        <!-- Иконка -->
        <div class="achievement-icon" :style="iconStyle">
            <i :class="achievement.icon || 'fa-solid fa-trophy'"></i>
            <div v-if="isUnlocked" class="unlock-badge">
                <i class="fa-solid fa-check"></i>
            </div>
            <div v-if="hasReward && !rewardClaimed" class="reward-badge">
                <i class="fa-solid fa-gift"></i>
            </div>
        </div>

        <!-- Информация -->
        <div class="achievement-info">
            <div class="achievement-header">
                <h4 class="achievement-title">{{ achievement.title }}</h4>
                <span v-if="isUnlocked" class="unlocked-date">
                    {{ formatDate(unlockedAt) }}
                </span>
            </div>

            <p class="achievement-description">{{ achievement.description }}</p>

            <!-- Прогресс-бар (если не получено) -->
            <div v-if="!isUnlocked" class="achievement-progress">
                <div class="progress-bar">
                    <div
                        class="progress-fill"
                        :style="{
                            width: progress + '%',
                            background: categoryColor
                        }"
                    ></div>
                </div>
                <div class="progress-text">
                    <span>{{ formatValue(currentValue) }}</span>
                    <span class="progress-target">/ {{ formatValue(targetValue) }}</span>
                </div>
            </div>

            <!-- Награда -->
            <div v-if="hasReward" class="achievement-reward">
                <i class="fa-solid fa-gift"></i>
                <span>Награда: <strong>{{ formatRewardValue(rewardValue, rewardType) }}</strong></span>
                <button
                    v-if="isUnlocked && !rewardClaimed"
                    class="claim-btn"
                    @click.stop="$emit('claim', achievement.id)"
                    :disabled="isClaiming"
                >
                    <i v-if="isClaiming" class="fa-solid fa-spinner fa-spin"></i>
                    <template v-else>Забрать</template>
                </button>
                <span v-else-if="rewardClaimed" class="claimed-badge">
                    <i class="fa-solid fa-check"></i> Получено
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import { useAchievements } from '@/MobileClient/Composables/useAchievements.js';

export default {
    name: 'AchievementCard',

    props: {
        achievement: { type: Object, required: true },
        progress: { type: Number, default: 0 },
        currentValue: { type: Number, default: 0 },
        targetValue: { type: Number, default: 0 },
        isUnlocked: { type: Boolean, default: false },
        unlockedAt: { type: [String, Date], default: null },
        rewardClaimed: { type: Boolean, default: false },
        isClaiming: { type: Boolean, default: false },
    },

    emits: ['claim'],

    setup() {
        const achievements = useAchievements();
        return { ...achievements };
    },

    computed: {
        hasReward() {
            return this.achievement?.reward_type &&
                this.achievement?.reward_type !== 'none' &&
                this.achievement?.reward_value > 0;
        },

        rewardValue() {
            return this.achievement?.reward_value || 0;
        },

        rewardType() {
            return this.achievement?.reward_type || 'none';
        },

        categoryColor() {
            return this.getCategoryColor(this.achievement?.condition_type);
        },

        iconStyle() {
            if (this.isUnlocked) {
                return {
                    background: `linear-gradient(135deg, ${this.categoryColor} 0%, ${this.categoryColor}dd 100%)`,
                };
            }
            return {
                background: this.progress > 0
                    ? `linear-gradient(135deg, ${this.categoryColor}40 0%, ${this.categoryColor}20 100%)`
                    : 'var(--bs-secondary-bg, #f3f4f6)',
                color: this.progress > 0 ? this.categoryColor : 'var(--bs-secondary-color, #9ca3af)',
            };
        },
    },

    methods: {
        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
            });
        },

        formatValue(value) {
            if (typeof value !== 'number') return value;
            return new Intl.NumberFormat('ru-RU').format(value);
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$success: #22c55e;
$warning: #f59e0b;

.achievement-card {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    transition: all 0.3s;

    &:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    &.is-unlocked {
        border-color: rgba($success, 0.3);
        background: linear-gradient(135deg, rgba($success, 0.03) 0%, $bg 100%);
    }

    &.has-reward:not(.is-unlocked) {
        border-color: rgba($warning, 0.3);
    }
}

.achievement-icon {
    position: relative;
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

    .is-unlocked & {
        animation: iconPulse 2s ease-in-out infinite;
    }
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.unlock-badge {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: $success;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    border: 2px solid $bg;
}

.reward-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: $warning;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    border: 2px solid $bg;
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}

.achievement-info {
    flex: 1;
    min-width: 0;
}

.achievement-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}

.achievement-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: $text;
}

.unlocked-date {
    font-size: 0.75rem;
    color: $text-muted;
    flex-shrink: 0;
}

.achievement-description {
    margin: 0 0 10px;
    font-size: 0.85rem;
    color: $text-muted;
    line-height: 1.4;
}

.achievement-progress {
    margin-bottom: 10px;
}

.progress-bar {
    height: 6px;
    background: $bg-secondary;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 6px;
}

.progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

.progress-text {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: $text-muted;

    span:first-child {
        font-weight: 600;
        color: $text;
    }
}

.achievement-reward {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba($warning, 0.08);
    border-radius: 10px;
    font-size: 0.85rem;
    color: darken($warning, 20%);

    i:first-child {
        color: $warning;
    }

    strong {
        color: darken($warning, 30%);
    }
}

.claim-btn {
    margin-left: auto;
    padding: 6px 14px;
    background: $warning;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: darken($warning, 10%);
        transform: translateY(-1px);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.claimed-badge {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 4px;
    color: $success;
    font-weight: 600;
    font-size: 0.8rem;
}

@media (max-width: 480px) {
    .achievement-card {
        gap: 12px;
        padding: 12px;
    }

    .achievement-icon {
        width: 52px;
        height: 52px;
        font-size: 1.4rem;
    }

    .achievement-title {
        font-size: 0.9rem;
    }
}
</style>
