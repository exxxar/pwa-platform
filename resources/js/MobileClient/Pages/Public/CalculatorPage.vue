<template>
    <div class="calculator-page">

        <!-- Шапка -->
        <header class="calculator-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fa-solid fa-calculator"></i>
                </div>
                <h1>Калькулятор стоимости</h1>
                <p class="subtitle">Рассчитайте стоимость вашего заказа</p>
            </div>
        </header>

        <!-- Основной контент -->
        <main class="calculator-main">

            <!-- Список товаров -->
            <section class="items-section">
                <h2 class="section-title">
                    <i class="fa-solid fa-list"></i>
                    Ваш заказ
                </h2>

                <div v-if="items.length === 0" class="empty-state">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <p>Добавьте товары для расчёта</p>
                    <button class="add-btn" @click="addItem">
                        <i class="fa-solid fa-plus"></i>
                        Добавить товар
                    </button>
                </div>

                <div v-else class="items-list">
                    <div
                        v-for="(item, index) in items"
                        :key="index"
                        class="item-card"
                    >
                        <div class="item-info">
                            <input
                                type="text"
                                v-model="item.name"
                                placeholder="Название товара"
                                class="item-name-input"
                            >
                            <div class="item-controls">
                                <div class="quantity-control">
                                    <button @click="decrementQuantity(index)">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input
                                        type="number"
                                        v-model.number="item.quantity"
                                        min="1"
                                        class="quantity-input"
                                    >
                                    <button @click="incrementQuantity(index)">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                                <div class="price-control">
                                    <input
                                        type="number"
                                        v-model.number="item.price"
                                        min="0"
                                        placeholder="Цена"
                                        class="price-input"
                                    >
                                    <span class="currency">₽</span>
                                </div>
                            </div>
                        </div>
                        <div class="item-total">
                            {{ formatPrice(item.price * item.quantity) }}
                        </div>
                        <button class="remove-btn" @click="removeItem(index)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>

                    <button class="add-btn secondary" @click="addItem">
                        <i class="fa-solid fa-plus"></i>
                        Добавить ещё
                    </button>
                </div>
            </section>

            <!-- Итог -->
            <section v-if="items.length > 0" class="summary-section">
                <div class="summary-card">
                    <div class="summary-row">
                        <span>Товаров:</span>
                        <strong>{{ totalItems }} шт.</strong>
                    </div>
                    <div class="summary-row">
                        <span>Сумма:</span>
                        <strong>{{ formatPrice(subtotal) }}</strong>
                    </div>
                    <div class="summary-row total">
                        <span>Итого:</span>
                        <strong>{{ formatPrice(total) }}</strong>
                    </div>
                </div>
            </section>

        </main>

        <!-- Футер -->
        <footer class="calculator-footer">
            <p>
                © {{ new Date().getFullYear() }} {{ platform.name }}.
                Все права защищены.
            </p>
        </footer>

    </div>
</template>

<script>
export default {
    name: 'PublicCalculator',

    props: {
        platform: {
            type: Object,
            default: () => ({
                name: 'PWA Platform',
                logo: '/images/logo.svg',
            }),
        },
    },

    data() {
        return {
            items: [],
        };
    },

    computed: {
        totalItems() {
            return this.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
        },

        subtotal() {
            return this.items.reduce((sum, item) => {
                return sum + ((item.price || 0) * (item.quantity || 0));
            }, 0);
        },

        total() {
            return this.subtotal; // Можно добавить скидки/доставку
        },
    },

    methods: {
        addItem() {
            this.items.push({
                name: '',
                quantity: 1,
                price: 0,
            });
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        incrementQuantity(index) {
            this.items[index].quantity++;
        },

        decrementQuantity(index) {
            if (this.items[index].quantity > 1) {
                this.items[index].quantity--;
            }
        },

        formatPrice(value) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value || 0);
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$bg: #f8f9fa;
$card-bg: #ffffff;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$danger: #ef4444;
$success: #10b981;

.calculator-page {
    min-height: 100vh;
    background: $bg;
    display: flex;
    flex-direction: column;
}

// ==========================================
// ШАПКА
// ==========================================
.calculator-header {
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    padding: 40px 20px;
    text-align: center;

    .header-content {
        max-width: 600px;
        margin: 0 auto;
    }

    .logo {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }

    h1 {
        font-size: 1.8rem;
        font-weight: 800;
        margin: 0 0 8px;
    }

    .subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin: 0;
    }
}

// ==========================================
// ОСНОВНОЙ КОНТЕНТ
// ==========================================
.calculator-main {
    flex: 1;
    padding: 20px 16px;
    max-width: 800px;
    margin: 0 auto;
    width: 100%;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 16px;
    color: $text;

    i {
        color: $primary;
    }
}

// ==========================================
// СПИСОК ТОВАРОВ
// ==========================================
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: $card-bg;
    border: 2px dashed $border;
    border-radius: 16px;
    color: $text-muted;

    i {
        font-size: 3rem;
        opacity: 0.3;
        margin-bottom: 16px;
    }

    p {
        margin: 0 0 20px;
        font-size: 1rem;
    }
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    transition: all 0.2s;

    &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
}

.item-info {
    flex: 1;
    min-width: 0;
}

.item-name-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: 0.95rem;
    margin-bottom: 10px;

    &:focus {
        outline: none;
        border-color: $primary;
    }
}

.item-controls {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 4px;
    background: $bg;
    border-radius: 8px;
    padding: 4px;

    button {
        width: 32px;
        height: 32px;
        border: none;
        background: $card-bg;
        border-radius: 6px;
        cursor: pointer;
        color: $primary;
        transition: all 0.2s;

        &:hover {
            background: $primary;
            color: white;
        }
    }

    .quantity-input {
        width: 50px;
        text-align: center;
        border: none;
        background: transparent;
        font-size: 0.95rem;
        font-weight: 600;

        &:focus {
            outline: none;
        }
    }
}

.price-control {
    display: flex;
    align-items: center;
    gap: 4px;
    background: $bg;
    border-radius: 8px;
    padding: 4px 8px;

    .price-input {
        width: 100px;
        border: none;
        background: transparent;
        font-size: 0.95rem;
        font-weight: 600;

        &:focus {
            outline: none;
        }
    }

    .currency {
        color: $text-muted;
        font-weight: 600;
    }
}

.item-total {
    font-size: 1.1rem;
    font-weight: 700;
    color: $success;
    min-width: 100px;
    text-align: right;
}

.remove-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: rgba($danger, 0.1);
    color: $danger;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $danger;
        color: white;
    }
}

.add-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
    margin-top: 12px;

    &:hover {
        background: $primary-dark;
        transform: translateY(-1px);
    }

    &.secondary {
        background: transparent;
        color: $primary;
        border: 2px dashed $primary;

        &:hover {
            background: rgba($primary, 0.05);
        }
    }
}

// ==========================================
// ИТОГ
// ==========================================
.summary-section {
    margin-top: 24px;
}

.summary-card {
    background: $card-bg;
    border: 2px solid $primary;
    border-radius: 16px;
    padding: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.95rem;
    color: $text;

    &.total {
        border-top: 2px solid $border;
        margin-top: 8px;
        padding-top: 16px;
        font-size: 1.2rem;

        strong {
            color: $primary;
            font-size: 1.4rem;
        }
    }
}

// ==========================================
// ФУТЕР
// ==========================================
.calculator-footer {
    padding: 20px;
    text-align: center;
    color: $text-muted;
    font-size: 0.85rem;
    border-top: 1px solid $border;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .calculator-header {
        padding: 30px 16px;

        h1 {
            font-size: 1.4rem;
        }
    }

    .item-card {
        flex-wrap: wrap;
    }

    .item-total {
        width: 100%;
        text-align: left;
        min-width: 0;
    }

    .price-control .price-input {
        width: 80px;
    }
}
</style>
