<template>
    <div class="promocodes-list">

        <!-- Загрузка -->
        <div v-if="isLoading && !isHydrated" class="loading-state">
            <div v-for="i in 4" :key="i" class="skeleton-card">
                <div class="skeleton-icon shimmer"></div>
                <div class="skeleton-content">
                    <div class="skeleton-line w-60 shimmer"></div>
                    <div class="skeleton-line w-40 shimmer"></div>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else-if="promocodes.length === 0" class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <h4>Промокодов пока нет</h4>
            <p>Создайте первый промокод, нажав кнопку ниже</p>
            <button class="create-btn" @click="$emit('create')">
                <i class="fa-solid fa-plus"></i>
                <span>Создать промокод</span>
            </button>
        </div>

        <!-- Список -->
        <div v-else class="list-wrapper">

            <!-- Статистика -->
            <div class="stats-row">
                <div class="stat-chip active">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>{{ activePromocodes.length }} активных</span>
                </div>
                <div class="stat-chip expired">
                    <i class="fa-solid fa-clock"></i>
                    <span>{{ expiredPromocodes.length }} истекло</span>
                </div>
                <div class="stat-chip total">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>{{ totalCount }} всего</span>
                </div>
            </div>

            <!-- Карточки промокодов -->
            <div class="cards-grid">
                <div
                    v-for="promo in promocodes"
                    :key="promo.id"
                    class="promo-card"
                    :class="{
                        'is-expired': promo.is_expired,
                        'is-inactive': !promo.is_active,
                    }"
                    @click="$emit('select', promo)"
                >
                    <!-- Заголовок карточки -->
                    <div class="card-header">
                        <div class="promo-code">
                            <i class="fa-solid fa-ticket"></i>
                            <span>{{ promo.code }}</span>
                        </div>
                        <div class="card-actions">
                            <button
                                class="icon-btn toggle"
                                :class="{ 'is-active': promo.is_active }"
                                @click.stop="handleToggle(promo)"
                                :title="promo.is_active ? 'Деактивировать' : 'Активировать'"
                            >
                                <i :class="promo.is_active ? 'fa-solid fa-toggle-on' : 'fa-solid fa-toggle-off'"></i>
                            </button>
                            <button
                                class="icon-btn delete"
                                @click.stop="handleDelete(promo)"
                                title="Удалить"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Основная информация -->
                    <div class="card-body">
                        <div class="discount-info">
                            <span class="discount-value">
                                {{ promo.discount_type === 'percent' ? promo.discount + '%' : formatPrice(promo.discount) }}
                            </span>
                            <span class="discount-label">
                                {{ promo.discount_type === 'percent' ? 'скидка' : 'скидка' }}
                            </span>
                        </div>

                        <div v-if="promo.description" class="promo-description">
                            {{ promo.description }}
                        </div>
                    </div>

                    <!-- Мета-информация -->
                    <div class="card-meta">
                        <div class="meta-item">
                            <i class="fa-solid fa-calendar"></i>
                            <span>{{ formatDateRange(promo.starts_at, promo.expires_at) }}</span>
                        </div>
                        <div class="meta-item" v-if="promo.usage_limit">
                            <i class="fa-solid fa-users"></i>
                            <span>{{ promo.used_count || 0 }} / {{ promo.usage_limit }}</span>
                        </div>
                        <div class="meta-item" v-if="promo.min_order_amount">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span>от {{ formatPrice(promo.min_order_amount) }}</span>
                        </div>
                    </div>

                    <!-- Статус -->
                    <div class="card-status">
                        <span v-if="promo.is_expired" class="status-badge expired">
                            <i class="fa-solid fa-clock"></i>
                            Истёк
                        </span>
                        <span v-else-if="!promo.is_active" class="status-badge inactive">
                            <i class="fa-solid fa-pause"></i>
                            Неактивен
                        </span>
                        <span v-else class="status-badge active">
                            <i class="fa-solid fa-check"></i>
                            Активен
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { usePromocodes } from '@/MobileClient/Composables/usePromocodes.js';

export default {
    name: 'PromoCodesList',

    props: {
        bot: {
            type: Object,
            default: null,
        },
    },

    emits: ['create', 'select', 'refresh'],

    setup() {
        const promocodes = usePromocodes();
        return { ...promocodes };
    },

    async mounted() {
        await this.loadPromocodes(this.bot?.id);
    },

    methods: {
        async handleToggle(promo) {
            try {
                await this.toggleActive(promo.id);
                this.$notify?.({
                    title: 'Успешно',
                    text: promo.is_active ? 'Промокод деактивирован' : 'Промокод активирован',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось изменить статус',
                    type: 'error',
                });
            }
        },

        async handleDelete(promo) {
            if (!confirm(`Удалить промокод "${promo.code}"?`)) return;

            try {
                await this.deletePromocode(promo.id);
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Промокод удалён',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить',
                    type: 'error',
                });
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
        },

        formatDate(start) {
            if (!start) return '';
            return new Date(start).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
            });
        },

        formatDateRange(start, end) {
            if (!start && !end) return 'Бессрочно';
            const startStr = start ? this.formatDate(start) : '∞';
            const endStr = end ? this.formatDate(end) : '∞';
            return `${startStr} — ${endStr}`;
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$purple: #8b5cf6;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary:  #f8f9fa;
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.promocodes-list {
    display: flex;
    flex-direction: column;
}

// ==========================================
// ЗАГРУЗКА
// ==========================================
.loading-state {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
}

.skeleton-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: $bg-secondary;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-line {
    height: 12px;
    border-radius: 6px;
    background: $bg-secondary;

    &.w-40 { width: 40%; }
    &.w-60 { width: 60%; }
}

.shimmer {
    background: linear-gradient(
            90deg,
            $bg-secondary 0%,
            color.adjust($bg-secondary, $lightness: -3%) 50%,
            $bg-secondary 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: $bg;
    border: 2px dashed $border;
    border-radius: 16px;

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 16px;
        background: rgba($primary, 0.1);
        color: $primary;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    h4 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 6px;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0 0 20px;
    }
}

.create-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, $primary, $primary-dark);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba($primary, 0.3);
    }
}

// ==========================================
// СТАТИСТИКА
// ==========================================
.stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $text-muted;

    i {
        font-size: 0.75rem;
    }

    &.active {
        background: rgba($success, 0.1);
        color: $success;
        border-color: rgba($success, 0.2);
    }

    &.expired {
        background: rgba($warning, 0.1);
        color: color.adjust($warning, $lightness: -15%);
        border-color: rgba($warning, 0.2);
    }

    &.total {
        background: rgba($primary, 0.1);
        color: $primary;
        border-color: rgba($primary, 0.2);
    }
}

// ==========================================
// КАРТОЧКИ
// ==========================================
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}

.promo-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;

    &:hover {
        border-color: rgba($primary, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    &.is-expired {
        opacity: 0.6;
        background: $bg-secondary;
    }

    &.is-inactive {
        opacity: 0.7;
    }
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.promo-code {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: linear-gradient(135deg, rgba($primary, 0.1), rgba($purple, 0.1));
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-weight: 700;
    font-size: 0.95rem;
    color: $primary;

    i {
        font-size: 0.85rem;
    }
}

.card-actions {
    display: flex;
    gap: 4px;
}

.icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid $border;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: all 0.2s;

    &:hover {
        transform: scale(1.1);
    }

    &.toggle {
        &.is-active {
            color: $success;
            border-color: rgba($success, 0.3);
        }
    }

    &.delete:hover {
        background: $danger;
        border-color: $danger;
        color: white;
    }
}

// Основная информация
.card-body {
    margin-bottom: 12px;
}

.discount-info {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 6px;
}

.discount-value {
    font-size: 1.8rem;
    font-weight: 800;
    background: linear-gradient(135deg, $primary, $purple);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
}

.discount-label {
    font-size: 0.8rem;
    color: $text-muted;
}

.promo-description {
    font-size: 0.85rem;
    color: $text-muted;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

// Мета
.card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid $border;
    margin-bottom: 10px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: $text-muted;

    i {
        font-size: 0.7rem;
        color: $primary;
    }
}

// Статус
.card-status {
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: 600;

        i {
            font-size: 0.65rem;
        }

        &.active {
            background: rgba($success, 0.1);
            color: $success;
        }

        &.inactive {
            background: rgba($text-muted, 0.1);
            color: $text-muted;
        }

        &.expired {
            background: rgba($warning, 0.1);
            color: color.adjust($warning, $lightness: -15%);
        }
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .cards-grid {
        grid-template-columns: 1fr;
    }

    .discount-value {
        font-size: 1.5rem;
    }
}
</style>
