<template>
    <div class="reward-card" :class="`type-${reward.type}`">
        <!-- Иконка типа -->
        <div class="reward-icon">
            <i :class="getRewardIcon(reward.type)"></i>
        </div>

        <!-- Основная информация -->
        <div class="reward-info">
            <div class="reward-header">
                <span class="reward-amount">
                    +{{ formatAmount(reward.amount) }}
                </span>
                <span class="reward-level-badge" :class="`level-${reward.level}`">
                    Ур. {{ reward.level }}
                </span>
            </div>

            <div class="reward-description">
                {{ reward.description || 'Реферальный бонус' }}
            </div>

            <!-- Мета-информация -->
            <div class="reward-meta">
                <div v-if="reward.from_user" class="meta-item user">
                    <div class="mini-avatar">
                        <img
                            v-if="reward.from_user.avatar"
                            v-lazy="reward.from_user.avatar"
                            :alt="reward.from_user.name"
                        >
                        <span v-else>{{ getInitials(reward.from_user.name) }}</span>
                    </div>
                    <span>{{ reward.from_user.name }}</span>
                </div>

                <div v-if="reward.order" class="meta-item order">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Заказ #{{ reward.order.id }}</span>
                </div>

                <div class="meta-item date">
                    <i class="fa-solid fa-clock"></i>
                    <span>{{ formatDate(reward.created_at) }}</span>
                </div>
            </div>
        </div>

        <!-- Процент (если есть) -->
        <div v-if="reward.percent > 0" class="reward-percent">
            {{ reward.percent }}%
        </div>
    </div>
</template>

<script>
export default {
    name: 'RewardCard',

    props: {
        reward: {
            type: Object,
            required: true,
        },
    },

    methods: {
        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        },

        getRewardIcon(type) {
            const icons = {
                'welcome_bonus': 'fa-solid fa-gift',
                'cashback': 'fa-solid fa-coins',
                'bonus': 'fa-solid fa-star',
                'discount': 'fa-solid fa-percent',
            };
            return icons[type] || 'fa-solid fa-trophy';
        },

        formatAmount(amount) {
            const num = parseFloat(amount || 0);
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(num);
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$warning: #f59e0b;
$purple: #8b5cf6;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.reward-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    transition: all 0.2s ease;
    position: relative;

    &:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }

    // Цветовые акценты по типу
    &.type-welcome_bonus {
        border-left: 3px solid $purple;
        .reward-icon {
            background: linear-gradient(135deg, $purple, #7c3aed);
        }
    }

    &.type-cashback {
        border-left: 3px solid $success;
        .reward-icon {
            background: linear-gradient(135deg, $success, #059669);
        }
    }

    &.type-bonus {
        border-left: 3px solid $warning;
        .reward-icon {
            background: linear-gradient(135deg, $warning, #d97706);
        }
    }

    &.type-discount {
        border-left: 3px solid $primary;
        .reward-icon {
            background: linear-gradient(135deg, $primary, $primary-dark);
        }
    }
}

.reward-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: $text-muted;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.reward-info {
    flex: 1;
    min-width: 0;
}

.reward-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.reward-amount {
    font-size: 1.15rem;
    font-weight: 800;
    background: linear-gradient(135deg, $success, #059669);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
}

.reward-level-badge {
    padding: 2px 8px;
    border-radius: 8px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;

    &.level-1 {
        background: rgba($success, 0.15);
        color: darken($success, 15%);
    }

    &.level-2 {
        background: rgba($primary, 0.15);
        color: $primary;
    }

    &.level-3 {
        background: rgba($purple, 0.15);
        color: $purple;
    }
}

.reward-description {
    font-size: 0.85rem;
    color: $text;
    margin-bottom: 8px;
    line-height: 1.4;
}

.reward-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.75rem;
    color: $text-muted;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;

    i {
        font-size: 0.7rem;
    }

    &.user {
        .mini-avatar {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;

            img, span {
                width: 100%;
                height: 100%;
                display: block;
            }

            img {
                object-fit: cover;
            }

            span {
                background: linear-gradient(135deg, $primary, $primary-dark);
                color: white;
                font-size: 0.5rem;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
    }

    &.order i {
        color: $warning;
    }

    &.date i {
        color: $text-muted;
    }
}

.reward-percent {
    padding: 6px 10px;
    background: rgba($success, 0.1);
    color: $success;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
}

@media (max-width: 480px) {
    .reward-card {
        padding: 12px;
        gap: 10px;
    }

    .reward-icon {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }

    .reward-amount {
        font-size: 1rem;
    }

    .reward-description {
        font-size: 0.8rem;
    }
}
</style>
