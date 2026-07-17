<template>
    <div class="order-periscope" :class="{ 'is-expanded': isExpanded }">

        <!-- Фон с анимацией волн -->
        <div class="ocean-background">
            <div class="wave wave-1"></div>
            <div class="wave wave-2"></div>
            <div class="wave wave-3"></div>
        </div>

        <!-- Главная карточка (закрытое состояние) -->
        <div v-if="!isExpanded" class="periscope-card" @click="expandPeriscope">
            <div class="periscope-icon">
                <i class="fa-solid fa-binoculars"></i>
            </div>
            <div class="periscope-info">
                <h3>Перископ заказов</h3>
                <p>Подсмотрите, что заказывают другие</p>
                <div class="orders-count">
                    <i class="fa-solid fa-fire"></i>
                    <span>{{ recentOrders.length }} заказов за сегодня</span>
                </div>
            </div>
            <div class="expand-arrow">
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </div>

        <!-- Раскрытое состояние -->
        <div v-else class="periscope-expanded">

            <!-- Заголовок с кнопкой закрытия -->
            <div class="expanded-header">
                <div class="header-icon">
                    <i class="fa-solid fa-binoculars"></i>
                </div>
                <div class="header-info">
                    <h3>Перископ заказов</h3>
                    <p>Последние заказы посетителей</p>
                </div>
                <button class="close-btn" @click="collapsePeriscope">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- 1. СОСТОЯНИЕ ЗАГРУЗКИ -->
            <div v-if="isLoading" class="periscope-loading">
                <div class="loading-spinner"></div>
                <p>Загружаем последние заказы...</p>
            </div>

            <!-- 2. СПИСОК ЗАКАЗОВ -->
            <div v-else-if="recentOrders.length > 0" class="orders-list">
                <div
                    v-for="order in recentOrders"
                    :key="order.id"
                    class="order-card"
                >
                    <div class="order-header">
                        <div class="order-number">
                            <i class="fa-solid fa-receipt"></i>
                            <span>Заказ #{{ order.id }}</span>
                        </div>
                        <div class="order-time">
                            <i class="fa-regular fa-clock"></i>
                            <span>{{ formatTime(order.created_at) }}</span>
                        </div>
                    </div>

                    <div class="order-items">
                        <div
                            v-for="(item, idx) in order.items.slice(0, 3)"
                            :key="idx"
                            class="order-item"
                        >
                            <span class="item-name">{{ item.product.title }}</span>
                            <span class="item-qty">×{{ item.quantity }}</span>
                        </div>
                        <div v-if="order.items.length > 3" class="more-items">
                            + ещё {{ order.items.length - 3 }} {{ pluralize(order.items.length - 3, 'товар', 'товара', 'товаров') }}
                        </div>
                    </div>

                    <div class="order-footer">
                        <div class="order-total">
                            <span>Итого:</span>
                            <strong>{{ formatPrice(order.total) }}</strong>
                        </div>
                        <button class="order-same-btn" @click="orderSame(order)">
                            <i class="fa-solid fa-cart-plus"></i>
                            <span>Заказать такое же</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. ПУСТОЕ СОСТОЯНИЕ -->
            <div v-else class="empty-state">
                <i class="fa-solid fa-binoculars"></i>
                <p>Пока нет заказов</p>
                <span>Загляните позже!</span>
            </div>

        </div>
    </div>
</template>

<script>
import { useOrdersStore } from '@/MobileClient/stores/Shop/orders';
import { storeToRefs } from 'pinia';

export default {
    name: "OrderPeriscope",

    setup() {
        const store = useOrdersStore();

        // 🆕 Получаем РЕАКТИВНЫЕ ссылки на состояние (это ключевой момент!)
        const { randomRecentOrders, isLoadingRandom } = storeToRefs(store);

        return {
            store,
            randomRecentOrders,
            isLoadingRandom,
            loadRandomOrders: store.loadRandomOrders,
            repeatOrder: store.repeatOrder,
        };
    },

    data() {
        return {
            isExpanded: false,
        };
    },

    computed: {
        recentOrders() {
            // Во Vue 3 ref, возвращённые из setup(), автоматически разворачиваются в this
            console.log("🔍 Текущие заказы в компоненте:", this.randomRecentOrders);
            return this.randomRecentOrders || [];
        },
        isLoading() {
            return this.isLoadingRandom;
        }
    },

    mounted() {
        this.loadRecentOrders();
    },

    methods: {
        async loadRecentOrders() {
            try {
                console.log("🔄 Начинаем загрузку заказов...");
                await this.loadRandomOrders();
                console.log("✅ Загрузка завершена. Массив заказов:", this.randomRecentOrders);
            } catch (error) {
                console.error('❌ Ошибка загрузки заказов:', error);
            }
        },

        expandPeriscope() {
            this.isExpanded = true;
        },

        collapsePeriscope() {
            this.isExpanded = false;
        },

        async orderSame(order) {
            try {
                await this.repeatOrder({ order_id: order.id });
                this.$notify?.({
                    title: 'Добавлено в корзину',
                    text: `Товары из заказа #${order.id} добавлены`,
                    type: 'success',
                });
                this.$router.push({ name: 'Cart' }).catch(() => {});
            } catch (error) {
                console.error('Ошибка добавления заказа:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось добавить товары',
                    type: 'error',
                });
            }
        },

        formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMinutes = Math.floor((now - date) / (1000 * 60));

            if (diffMinutes < 1) return 'Только что';
            if (diffMinutes < 60) return `${diffMinutes} мин. назад`;

            const diffHours = Math.floor(diffMinutes / 60);
            if (diffHours < 24) return `${diffHours} ч. назад`;

            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
        },

        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
    },
};
</script>
<style lang="scss" scoped>
@use 'sass:color';

// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$ocean-dark: #0c4a6e;
$ocean-medium: #0369a1;
$ocean-light: #0ea5e9;
$ocean-foam: #e0f2fe;
$sand: #fef3c7;
$white: #ffffff;
$text: #1f2937;
$text-muted: #6b7280;

// ==========================================
// БАЗА
// ==========================================
.order-periscope {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    margin: 20px 16px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);

    &.is-expanded {
        box-shadow: 0 20px 60px rgba($ocean-dark, 0.3);
    }
}

// ==========================================
// ФОН С ВОЛНАМИ
// ==========================================
.ocean-background {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, $ocean-dark 0%, $ocean-medium 50%, $ocean-light 100%);
    z-index: 0;
    overflow: hidden;
}

.wave {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 200%;
    height: 100px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50% 50% 0 0;
    animation: wave 8s linear infinite;

    &.wave-1 {
        bottom: 0;
        opacity: 0.7;
        animation-duration: 8s;
    }

    &.wave-2 {
        bottom: 20px;
        opacity: 0.5;
        animation-duration: 12s;
        animation-delay: -2s;
    }

    &.wave-3 {
        bottom: 40px;
        opacity: 0.3;
        animation-duration: 16s;
        animation-delay: -4s;
    }
}

@keyframes wave {
    0% {
        transform: translateX(0) translateY(0);
    }
    50% {
        transform: translateX(-25%) translateY(-10px);
    }
    100% {
        transform: translateX(-50%) translateY(0);
    }
}

// ==========================================
// ГЛАВНАЯ КАРТОЧКА (ЗАКРЫТОЕ СОСТОЯНИЕ)
// ==========================================
.periscope-card {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover {
        background: rgba(255, 255, 255, 0.05);
    }
}

.periscope-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.periscope-info {
    flex: 1;
    color: white;

    h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 4px 0;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    p {
        font-size: 0.85rem;
        opacity: 0.9;
        margin: 0 0 8px 0;
    }
}

.orders-count {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;

    i {
        color: #fbbf24;
        animation: pulse 2s ease-in-out infinite;
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.expand-arrow {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
    transition: transform 0.3s ease;

    .periscope-card:hover & {
        transform: translateY(3px);
    }
}

// ==========================================
// РАСКРЫТОЕ СОСТОЯНИЕ
// ==========================================
.periscope-expanded {
    position: relative;
    z-index: 1;
    animation: expandIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes expandIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.expanded-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.header-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
    flex-shrink: 0;
}

.header-info {
    flex: 1;
    color: white;

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 2px 0;
    }

    p {
        font-size: 0.8rem;
        opacity: 0.9;
        margin: 0;
    }
}

.close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;

    &:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }
}

// ==========================================
// СПИСОК ЗАКАЗОВ
// ==========================================
.orders-list {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 500px;
    overflow-y: auto;

    &::-webkit-scrollbar {
        width: 6px;
    }

    &::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }

    &::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }
}

.order-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
}

.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.order-number {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    color: $ocean-dark;

    i {
        font-size: 1rem;
    }
}

.order-time {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $text-muted;

    i {
        font-size: 0.85rem;
    }
}

.order-items {
    margin-bottom: 12px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 0.9rem;

    .item-name {
        color: $text;
        font-weight: 500;
    }

    .item-qty {
        color: $text-muted;
        font-weight: 600;
    }
}

.more-items {
    padding: 6px 0;
    font-size: 0.85rem;
    color: $ocean-medium;
    font-weight: 600;
    font-style: italic;
}

.order-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.order-total {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: $text-muted;

    strong {
        font-size: 1.1rem;
        color: $ocean-dark;
    }
}

.order-same-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: linear-gradient(135deg, $ocean-medium 0%, $ocean-light 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($ocean-medium, 0.3);

    i {
        font-size: 0.9rem;
    }

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba($ocean-medium, 0.4);
    }

    &:active {
        transform: translateY(0);
    }
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: white;

    i {
        font-size: 3rem;
        opacity: 0.5;
        margin-bottom: 12px;
        animation: float 3s ease-in-out infinite;
    }

    p {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 0 4px 0;
    }

    span {
        font-size: 0.9rem;
        opacity: 0.8;
    }
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 640px) {
    .order-periscope {
        margin: 16px 0px;
    }

    .periscope-card {
        padding: 16px;
    }

    .periscope-icon {
        width: 56px;
        height: 56px;
        font-size: 1.5rem;
    }

    .periscope-info h3 {
        font-size: 1.1rem;
    }

    .order-card {
        padding: 14px;
    }

    .order-same-btn {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
}
</style>
