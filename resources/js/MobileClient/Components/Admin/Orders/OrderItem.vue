<template>
    <div
        class="order-card"
        :class="{ 'has-review': item.review }"
        @click="select(item)"
    >
        <!-- Шапка карточки -->
        <div class="order-header">
            <div class="order-number">
                <i class="fa-solid fa-receipt"></i>
                <span>Заказ #{{ item.id }}</span>
            </div>
            <div
                class="cashback-status"
                :class="item.is_cashback_crediting ? 'success' : 'warning'"
            >
                <i class="fa-solid" :class="item.is_cashback_crediting ? 'fa-check-circle' : 'fa-clock'"></i>
                <span>{{ item.is_cashback_crediting ? 'Баллы начислены' : 'Баллы не начислены' }}</span>
            </div>
        </div>

        <!-- Мета-информация -->
        <div class="order-meta">
            <div class="meta-item">
                <i class="fa-solid fa-calendar"></i>
                <span>{{ formatDate(item.created_at) }}</span>
            </div>
            <div class="meta-item">
                <i class="fa-solid fa-clock"></i>
                <span>{{ formatTime(item.created_at) }}</span>
            </div>
        </div>

        <!-- Товары -->
        <div v-if="products.length > 0" class="order-products">
            <div class="products-header">
                <i class="fa-solid fa-bag-shopping"></i>
                <span>Товары ({{ products.length }})</span>
            </div>
            <ul class="products-list">
                <li v-for="(product, idx) in displayedProducts" :key="idx">
                    {{ product.title }}
                </li>
            </ul>
            <button
                v-if="products.length > 3"
                class="show-more-btn"
                @click.stop="showAllProducts = !showAllProducts"
            >
                <i class="fa-solid" :class="showAllProducts ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                <span>{{ showAllProducts ? 'Скрыть' : `Показать ещё (${products.length - 3})` }}</span>
            </button>
        </div>

        <!-- Цена -->
        <div class="order-price">
            <span class="price-label">Итого:</span>
            <span class="price-value">{{ formatPrice(item.summary_price) }}</span>
        </div>

        <!-- Отзыв -->
        <div v-if="item.review" class="order-review">
            <ReviewCard
                :is-admin="true"
                v-model="item.review"
            />
        </div>

        <!-- Кнопка начисления CashBack -->
        <div v-if="!item.is_cashback_crediting" class="order-action">
            <!-- Прогресс-бар таймера -->
            <div v-if="spent_time_counter > 0" class="timer-progress">
                <div
                    class="timer-progress-bar"
                    :style="{ width: `${(spent_time_counter / 10) * 100}%` }"
                ></div>
            </div>

            <button
                type="button"
                class="btn-cashback"
                :disabled="spent_time_counter > 0 || isLoading"
                @click.stop="addCashBack"
            >
                <span v-if="isLoading" class="spinner-small"></span>
                <template v-else-if="spent_time_counter > 0">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <span>Подождите {{ spent_time_counter }} сек.</span>
                </template>
                <template v-else>
                    <i class="fa-solid fa-coins"></i>
                    <span>Начислить баллы</span>
                </template>
            </button>
        </div>
    </div>
</template>

<script>
import ReviewCard from '@/MobileClient/Components/Shop/ReviewCard.vue'

export default {
    name: 'OrderItem',

    components: {
        ReviewCard,
    },

    props: {
        item: {
            type: Object,
            required: true,
        },
    },

    emits: ['select'],

    data() {
        return {
            spent_time_counter: 0,
            timerInterval: null,
            isLoading: false,
            showAllProducts: false,
        }
    },

    computed: {
        products() {
            return this.item.product_details?.[0]?.products || []
        },

        displayedProducts() {
            if (this.showAllProducts) return this.products
            return this.products.slice(0, 3)
        },
    },

    mounted() {
        const savedCounter = localStorage.getItem('cashman_order_cashback_add_counter')
        if (savedCounter !== null) {
            const time = parseInt(savedCounter)
            if (time > 0) {
                this.startTimer(time)
            }
        }
    },

    beforeUnmount() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval)
        }
    },

    methods: {
        select(item) {
            this.$emit('select', item)
        },

        async addCashBack() {
            if (this.spent_time_counter > 0 || this.isLoading) return

            this.isLoading = true
            this.startTimer()

            try {
                await this.$store.dispatch('addCashBackToOrder', {
                    order_id: this.item.id,
                })

                this.item.is_cashback_crediting = true
                this.$notify?.({
                    title: 'Успех',
                    text: 'Баллы успешно начислены',
                    type: 'success',
                })
            } catch (err) {
                console.error('Ошибка начисления CashBack:', err)
                this.item.is_cashback_crediting = false
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось начислить баллы',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        startTimer(time = 10) {
            if (this.timerInterval) {
                clearInterval(this.timerInterval)
            }

            this.spent_time_counter = Math.min(time, 10)

            this.timerInterval = setInterval(() => {
                if (this.spent_time_counter > 0) {
                    this.spent_time_counter--
                    localStorage.setItem('cashman_order_cashback_add_counter', this.spent_time_counter)
                } else {
                    clearInterval(this.timerInterval)
                    this.timerInterval = null
                    this.spent_time_counter = 0
                    localStorage.removeItem('cashman_order_cashback_add_counter')
                }
            }, 1000)
        },

        formatDate(dateString) {
            if (!dateString) return ''
            const date = new Date(dateString)
            return date.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            })
        },

        formatTime(dateString) {
            if (!dateString) return ''
            const date = new Date(dateString)
            return date.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit',
            })
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0)
        },
    },
}
</script>

<style lang="scss" scoped>
@use "sass:color";
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-warning: #f59e0b;
$admin-danger: #ef4444;

.order-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;

    &:active {
        transform: scale(0.98);
        background: rgba($admin-primary, 0.02);
    }

    &.has-review {
        border-color: rgba($admin-primary, 0.3);
    }
}

// ==========================================
// ШАПКА КАРТОЧКИ
// ==========================================
.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.order-number {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-text;

    i {
        color: $admin-primary;
        font-size: 1rem;
    }
}

.cashback-status {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;

    &.success {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }

    &.warning {
        background: rgba($admin-warning, 0.1);
        color: $admin-warning;
    }
}

// ==========================================
// МЕТА-ИНФОРМАЦИЯ
// ==========================================
.order-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid $admin-border;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $admin-text-muted;

    i {
        font-size: 0.75rem;
    }
}

// ==========================================
// ТОВАРЫ
// ==========================================
.order-products {
    margin-bottom: 12px;
}

.products-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text;
    margin-bottom: 8px;

    i {
        color: $admin-primary;
        font-size: 0.9rem;
    }
}

.products-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;

    li {
        font-size: 0.85rem;
        color: $admin-text-muted;
        padding-left: 16px;
        position: relative;
        line-height: 1.4;

        &::before {
            content: '•';
            position: absolute;
            left: 4px;
            color: $admin-primary;
        }
    }
}

.show-more-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 8px;
    margin-top: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 6px;
    color: $admin-primary;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;

    &:active {
        background: rgba($admin-primary, 0.08);
        transform: scale(0.98);
    }
}

// ==========================================
// ЦЕНА
// ==========================================
.order-price {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 0;
    border-top: 1px solid $admin-border;
    border-bottom: 1px solid $admin-border;
    margin-bottom: 12px;
}

.price-label {
    font-size: 0.9rem;
    color: $admin-text-muted;
}

.price-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: $admin-text;
}

// ==========================================
// ОТЗЫВ
// ==========================================
.order-review {
    margin-bottom: 12px;
    padding: 12px;
    background: $admin-bg;
    border-radius: 8px;
}

// ==========================================
// ДЕЙСТВИЕ (НАЧИСЛЕНИЕ CASHBACK)
// ==========================================
.order-action {
    position: relative;
}

.timer-progress {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: $admin-border;
    border-radius: 3px 3px 0 0;
    overflow: hidden;
}

.timer-progress-bar {
    height: 100%;
    background: $admin-warning;
    transition: width 1s linear;
}

.btn-cashback {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    background: $admin-success;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:active:not(:disabled) {
        transform: scale(0.98);
        background: color.adjust($admin-success, $lightness: -5%);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        background: $admin-text-muted;
    }

    i {
        font-size: 1rem;
    }
}

.spinner-small {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
