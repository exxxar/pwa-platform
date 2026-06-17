<template>
    <div class="pizza-calculator">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="pizza-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">🍕</div>
                    <div class="hero-sparkle sparkle-1">🧀</div>
                    <div class="hero-sparkle sparkle-2">🍅</div>
                    <div class="hero-sparkle sparkle-3">🥓</div>
                </div>
                <h1 class="hero-title">Собери свою пиццу</h1>
                <p class="hero-subtitle">Выбери ингредиенты и создай идеальную пиццу!</p>
            </div>
        </div>

        <div class="calculator-content">

            <!-- ========================================== -->
            <!-- ВИЗУАЛИЗАЦИЯ ПИЦЦЫ -->
            <!-- ========================================== -->
            <div class="pizza-preview-section">
                <div class="pizza-preview">
                    <div class="pizza-plate">
                        <div class="pizza-base" :class="baseClass"></div>
                        <div class="pizza-sauce" v-if="hasSauce"></div>
                        <div class="pizza-cheese" v-if="hasCheese"></div>
                        <div class="pizza-toppings">
                            <div
                                v-for="topping in visibleToppings"
                                :key="topping.id"
                                class="topping"
                                :class="'topping-' + topping.id"
                            ></div>
                        </div>
                    </div>
                    <div class="pizza-shadow"></div>
                </div>

                <!-- Быстрая статистика -->
                <div class="quick-stats">
                    <div class="stat-item">
                        <i class="fa-solid fa-weight-scale"></i>
                        <span>{{ summary_weight }} г</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fa-solid fa-ruble-sign"></i>
                        <span>{{ formatPrice(summary_price) }}</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>{{ selectedIngredientsCount }} ингредиентов</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ОСНОВА ПИЦЦЫ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-circle"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Основа пиццы</h6>
                        <p class="section-subtitle">Выбери тип теста</p>
                    </div>
                </div>

                <div class="base-options">
                    <button
                        v-for="base in bases"
                        :key="base.id"
                        type="button"
                        class="base-card"
                        :class="{ 'selected': pizza_base === base.id }"
                        @click="selectBase(base.id)"
                    >
                        <div class="base-icon">
                            <i class="fa-solid fa-circle"></i>
                        </div>
                        <div class="base-info">
                            <div class="base-name">{{ base.title }}</div>
                            <div class="base-details">
                                <span>{{ base.weight }} г</span>
                                <span class="base-price">{{ formatPrice(base.price) }}</span>
                            </div>
                        </div>
                        <div class="base-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ИНГРЕДИЕНТЫ ПО КАТЕГОРИЯМ -->
            <!-- ========================================== -->
            <div v-for="category in categories" :key="category.id" class="section">
                <div class="section-header">
                    <div class="section-icon" :style="{ background: category.gradient }">
                        <i :class="category.icon"></i>
                    </div>
                    <div>
                        <h6 class="section-title">{{ category.title }}</h6>
                        <p class="section-subtitle">{{ category.description }}</p>
                    </div>
                </div>

                <div class="ingredients-grid">
                    <button
                        v-for="ingredient in getIngredientsByCategory(category.id)"
                        :key="ingredient.id"
                        type="button"
                        class="ingredient-card"
                        :class="{
                            'selected': isIngredientSelected(ingredient.id),
                            'has-multiple': hasManyItems(ingredient.id) > 1
                        }"
                        @click="toggleIngredient(ingredient.id)"
                    >
                        <div class="ingredient-emoji">{{ ingredient.emoji }}</div>
                        <div class="ingredient-info">
                            <div class="ingredient-name">{{ ingredient.title }}</div>
                            <div class="ingredient-details">
                                <span>{{ ingredient.weight }} г</span>
                                <span class="ingredient-price">{{ formatPrice(ingredient.price) }}</span>
                            </div>
                        </div>

                        <!-- Счётчик для множественных -->
                        <div v-if="hasManyItems(ingredient.id) > 0" class="ingredient-counter">
                            <button
                                type="button"
                                class="counter-btn minus"
                                @click.stop="removeItem(ingredient.id)"
                            >
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <span class="counter-value">{{ hasManyItems(ingredient.id) }}</span>
                            <button
                                type="button"
                                class="counter-btn plus"
                                @click.stop="addItem(ingredient.id)"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                        <!-- Галочка выбора -->
                        <div v-else class="ingredient-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КОЛИЧЕСТВО ПИЦЦ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Количество пицц</h6>
                        <p class="section-subtitle">Сколько таких пицц сделать?</p>
                    </div>
                </div>

                <div class="quantity-selector">
                    <button
                        type="button"
                        class="qty-btn"
                        :disabled="summary_count <= 1"
                        @click="decrementSummary"
                    >
                        <i class="fa-solid fa-minus"></i>
                    </button>

                    <div class="qty-display">
                        <div class="qty-value">{{ summary_count }}</div>
                        <div class="qty-label">{{ pluralize(summary_count, 'пицца', 'пиццы', 'пицц') }}</div>
                    </div>

                    <button
                        type="button"
                        class="qty-btn"
                        @click="incrementSummary"
                    >
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- STICKY ПАНЕЛЬ ИТОГА -->
        <!-- ========================================== -->
        <div class="summary-panel">
            <div class="summary-content">
                <div class="summary-info">
                    <div class="summary-label">Итого к оплате</div>
                    <div class="summary-price">{{ formatPrice(summary_price) }}</div>
                    <div class="summary-details">
                        {{ summary_weight }} г · {{ summary_count }} {{ pluralize(summary_count, 'шт.', 'шт.', 'шт.') }}
                    </div>
                </div>
                <div class="summary-actions">
                    <button
                        type="button"
                        class="clear-btn"
                        @click="clearCalc"
                    >
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                    <button
                        type="button"
                        class="add-to-cart-btn"
                        :disabled="!canAddToCart"
                        @click="addToCart"
                    >
                        <i class="fa-solid fa-cart-plus"></i>
                        <span>В корзину</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "PizzaCalculator",

    data() {
        return {
            pizza_base: null,
            summary_count: 1,
            fillings: [],
            message: '',

            categories: [
                {
                    id: 1,
                    title: 'Соусы',
                    description: 'Выбери основу вкуса',
                    icon: 'fa-solid fa-droplet',
                    gradient: 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)'
                },
                {
                    id: 2,
                    title: 'Сыры',
                    description: 'Добавь сырный вкус',
                    icon: 'fa-solid fa-cheese',
                    gradient: 'linear-gradient(135deg, #ffd700 0%, #ff9800 100%)'
                },
                {
                    id: 3,
                    title: 'Мясо',
                    description: 'Сытные добавки',
                    icon: 'fa-solid fa-drumstick-bite',
                    gradient: 'linear-gradient(135deg, #c0392b 0%, #e74c3c 100%)'
                },
                {
                    id: 4,
                    title: 'Овощи',
                    description: 'Свежие ингредиенты',
                    icon: 'fa-solid fa-leaf',
                    gradient: 'linear-gradient(135deg, #27ae60 0%, #2ecc71 100%)'
                }
            ],

            ingredients: [
                // Основа (type: 8)
                { id: 59, type: 8, title: 'Тонкое тесто', price: 200, weight: 200, emoji: '🫓' },
                { id: 60, type: 8, title: 'Толстое тесто', price: 220, weight: 250, emoji: '🍞' },

                // Соусы (type: 1)
                { id: 1, type: 1, title: 'Томатный соус', price: 30, weight: 30, emoji: '🍅' },
                { id: 2, type: 1, title: 'Сливочный соус', price: 40, weight: 30, emoji: '🥛' },

                // Сыры (type: 2)
                { id: 3, type: 2, title: 'Моцарелла', price: 80, weight: 50, emoji: '🧀' },
                { id: 4, type: 2, title: 'Чеддер', price: 70, weight: 40, emoji: '🧀' },

                // Мясо (type: 3)
                { id: 5, type: 3, title: 'Пепперони', price: 120, weight: 50, emoji: '🥓' },
                { id: 6, type: 3, title: 'Ветчина', price: 100, weight: 50, emoji: '🍖' },

                // Овощи (type: 4)
                { id: 7, type: 4, title: 'Грибы', price: 40, weight: 30, emoji: '🍄' },
                { id: 8, type: 4, title: 'Оливки', price: 35, weight: 20, emoji: '🫒' }
            ],
        };
    },

    computed: {
        bases() {
            return this.ingredients.filter(i => i.type === 8);
        },

        selectedIngredientsCount() {
            return this.fillings.length;
        },

        price() {
            return this.fillings.reduce((sum, id) => {
                const item = this.ingredients.find(i => i.id === id);
                return item ? sum + item.price : sum;
            }, 0);
        },

        weight() {
            return this.fillings.reduce((sum, id) => {
                const item = this.ingredients.find(i => i.id === id);
                return item ? sum + item.weight : sum;
            }, 0);
        },

        summary_price() {
            return this.price * this.summary_count;
        },

        summary_weight() {
            return this.weight * this.summary_count;
        },

        baseClass() {
            if (!this.pizza_base) return '';
            const base = this.ingredients.find(i => i.id === this.pizza_base);
            return base ? `base-${base.id}` : '';
        },

        hasSauce() {
            return this.fillings.some(id => {
                const item = this.ingredients.find(i => i.id === id);
                return item && item.type === 1;
            });
        },

        hasCheese() {
            return this.fillings.some(id => {
                const item = this.ingredients.find(i => i.id === id);
                return item && item.type === 2;
            });
        },

        visibleToppings() {
            return this.fillings
                .map(id => this.ingredients.find(i => i.id === id))
                .filter(item => item && (item.type === 3 || item.type === 4));
        },

        canAddToCart() {
            return this.pizza_base !== null && this.fillings.length > 0;
        },
    },

    methods: {
        getIngredientsByCategory(type) {
            return this.ingredients.filter(i => i.type === type);
        },

        selectBase(id) {
            if (this.pizza_base) {
                this.removeItem(this.pizza_base);
            }
            this.pizza_base = id;
            this.addItem(id);
        },

        toggleIngredient(id) {
            if (this.isIngredientSelected(id)) {
                // Если уже выбран — убираем все вхождения
                this.fillings = this.fillings.filter(i => i !== id);
            } else {
                this.addItem(id);
            }
        },

        isIngredientSelected(id) {
            return this.fillings.includes(id);
        },

        hasManyItems(id) {
            return this.fillings.filter(i => i === id).length;
        },

        addItem(id) {
            this.fillings.push(id);
        },

        removeItem(id) {
            const index = this.fillings.indexOf(id);
            if (index !== -1) {
                this.fillings.splice(index, 1);
            }
        },

        incrementSummary() {
            this.summary_count++;
        },

        decrementSummary() {
            if (this.summary_count > 1) {
                this.summary_count--;
            }
        },

        clearCalc() {
            this.fillings = [];
            this.pizza_base = null;
            this.summary_count = 1;

            this.$notify?.({
                title: 'Калькулятор',
                text: 'Калькулятор очищен',
                type: 'info',
            });
        },

        addToCart() {
            if (!this.canAddToCart) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Выбери основу и хотя бы один ингредиент',
                    type: 'warning',
                });
                return;
            }

            // TODO: Реальная логика добавления в корзину
            console.log('PIZZA ADD TO CART', {
                base: this.pizza_base,
                items: this.fillings,
                count: this.summary_count,
                price: this.summary_price,
                weight: this.summary_weight,
            });

            this.$notify?.({
                title: 'Корзина',
                text: `Пицца добавлена в корзину (${this.summary_count} ${this.pluralize(this.summary_count, 'шт.', 'шт.', 'шт.')})`,
                type: 'success',
            });

            // Сброс после добавления
            setTimeout(() => {
                this.clearCalc();
            }, 1000);
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
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

<style scoped>
.pizza-calculator {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 120px; /* Отступ под sticky-панель */
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.pizza-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 50%, #c0392b 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 215, 0, 0.1) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 16px;
}

.hero-icon {
    font-size: 4rem;
    animation: iconBounce 3s ease-in-out infinite;
}

@keyframes iconBounce {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(5deg); }
}

.hero-sparkle {
    position: absolute;
    font-size: 1.5rem;
    animation: sparkleFloat 3s ease-in-out infinite;
}

.sparkle-1 { top: -10px; right: -15px; animation-delay: 0s; }
.sparkle-2 { bottom: -10px; left: -15px; animation-delay: 1s; }
.sparkle-3 { top: 50%; right: -25px; animation-delay: 2s; }

@keyframes sparkleFloat {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.8; }
    50% { transform: translateY(-10px) scale(1.2); opacity: 1; }
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0;
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.calculator-content {
    padding: 20px 16px;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.section {
    margin-bottom: 28px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ВИЗУАЛИЗАЦИЯ ПИЦЦЫ
   ========================================== */
.pizza-preview-section {
    margin-bottom: 32px;
    text-align: center;
}

.pizza-preview {
    position: relative;
    width: 240px;
    height: 240px;
    margin: 0 auto 20px;
}

.pizza-plate {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

.pizza-base {
    position: absolute;
    inset: 0;
    background: #f4e4c1;
    border: 8px solid #d4a574;
    transition: all 0.3s ease;
    border-radius: 50%;
}

.pizza-base.base-59 {
    background: #f4e4c1;
    border-color: #d4a574;
}

.pizza-base.base-60 {
    background: #e8d4a8;
    border-color: #c9984a;
    border-width: 12px;
}

.pizza-sauce {
    position: absolute;
    inset: 15%;
    border-radius: 50%;
    background: radial-gradient(circle, #c0392b 0%, #a93226 100%);
    animation: layerAppear 0.4s ease;
}

.pizza-cheese {
    position: absolute;
    inset: 20%;
    border-radius: 50%;
    background: radial-gradient(circle, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
    animation: layerAppear 0.4s ease 0.1s backwards;
}

.pizza-toppings {
    position: absolute;
    inset: 25%;
    border-radius: 50%;
}

.topping {
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    animation: toppingAppear 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes layerAppear {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes toppingAppear {
    from {
        opacity: 0;
        transform: scale(0) rotate(-180deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

/* Позиции для топпингов */
.topping-5 { top: 20%; left: 30%; background: #c0392b; } /* Пепперони */
.topping-6 { top: 40%; left: 60%; background: #e67e22; } /* Ветчина */
.topping-7 { top: 60%; left: 25%; background: #8b4513; } /* Грибы */
.topping-8 { top: 30%; left: 55%; background: #2c3e50; } /* Оливки */

.pizza-shadow {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 20px;
    background: radial-gradient(ellipse, rgba(0, 0, 0, 0.2) 0%, transparent 70%);
    filter: blur(8px);
}

/* Быстрая статистика */
.quick-stats {
    display: inline-flex;
    align-items: center;
    gap: 16px;
    padding: 12px 20px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.stat-item i {
    color: var(--bs-primary);
    font-size: 0.8rem;
}

.stat-divider {
    width: 1px;
    height: 20px;
    background: var(--bs-border-color);
}

/* ==========================================
   ОСНОВА ПИЦЦЫ
   ========================================== */
.base-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.base-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.base-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.1);
}

.base-card.selected {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.2);
}

.base-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.base-info {
    flex: 1;
    min-width: 0;
}

.base-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.base-details {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.base-price {
    font-weight: 700;
    color: var(--bs-primary);
}

.base-check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.base-card.selected .base-check {
    opacity: 1;
    transform: scale(1);
}

/* ==========================================
   ИНГРЕДИЕНТЫ
   ========================================== */
.ingredients-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px;
}

.ingredient-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.ingredient-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.1);
}

.ingredient-card.selected {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
}

.ingredient-emoji {
    font-size: 2rem;
    line-height: 1;
    transition: transform 0.3s ease;
}

.ingredient-card:hover .ingredient-emoji {
    transform: scale(1.15) rotate(-5deg);
}

.ingredient-info {
    width: 100%;
}

.ingredient-name {
    font-weight: 700;
    font-size: 0.8rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    line-height: 1.2;
}

.ingredient-details {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
}

.ingredient-price {
    font-weight: 700;
    color: var(--bs-primary);
}

.ingredient-check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.ingredient-card.selected .ingredient-check {
    opacity: 1;
    transform: scale(1);
}

/* Счётчик для множественных */
.ingredient-counter {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
}

.counter-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--bs-primary);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.counter-btn:hover {
    transform: scale(1.1);
}

.counter-value {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-primary);
    min-width: 20px;
}

/* ==========================================
   КОЛИЧЕСТВО ПИЦЦ
   ========================================== */
.quantity-selector {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding: 20px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
}

.qty-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--bs-primary);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.qty-btn:hover:not(:disabled) {
    transform: scale(1.1);
}

.qty-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.qty-display {
    text-align: center;
}

.qty-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--bs-primary);
    line-height: 1;
}

.qty-label {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    margin-top: 4px;
}

/* ==========================================
   STICKY ПАНЕЛЬ ИТОГА
   ========================================== */
.summary-panel {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--bs-body-bg);
    border-top: 1px solid var(--bs-border-color);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
    z-index: 100;
    padding: 16px;
}

.summary-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    max-width: 600px;
    margin: 0 auto;
}

.summary-info {
    flex: 1;
}

.summary-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.summary-price {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--bs-primary);
    line-height: 1;
}

.summary-details {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin-top: 4px;
}

.summary-actions {
    display: flex;
    gap: 10px;
}

.clear-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.clear-btn:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
}

.add-to-cart-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.add-to-cart-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.add-to-cart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .pizza-preview {
        width: 200px;
        height: 200px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .base-options {
        grid-template-columns: 1fr;
    }

    .ingredients-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .summary-price {
        font-size: 1.3rem;
    }

    .add-to-cart-btn {
        padding: 12px 20px;
        font-size: 0.9rem;
    }

    .add-to-cart-btn span {
        display: none;
    }
}
</style>
