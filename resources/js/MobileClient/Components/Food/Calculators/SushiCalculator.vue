<template>
    <div class="roll-calculator">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="roll-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">🍣</div>
                    <div class="hero-sparkle sparkle-1">🐟</div>
                    <div class="hero-sparkle sparkle-2">🥒</div>
                    <div class="hero-sparkle sparkle-3">🍚</div>
                </div>
                <h1 class="hero-title">Собери свой ролл</h1>
                <p class="hero-subtitle">Выбери ингредиенты и создай идеальный ролл!</p>
            </div>
        </div>

        <div class="calculator-content">

            <!-- ========================================== -->
            <!-- ВИЗУАЛИЗАЦИЯ РОЛЛА -->
            <!-- ========================================== -->
            <div class="roll-preview-section">
                <div class="roll-preview">
                    <div class="roll-outer">
                        <div class="roll-base" :class="baseClass"></div>
                        <div class="roll-coating" v-if="roll_coating" :class="coatingClass"></div>
                        <div class="roll-filling">
                            <div
                                v-for="(filling, index) in visibleFillings"
                                :key="filling.id"
                                class="filling-piece"
                                :class="'filling-' + filling.id"
                                :style="fillingPosition(index)"
                            ></div>
                        </div>
                    </div>
                    <div class="roll-shadow"></div>
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
                        <span>{{ fillings.length }}/ {{max_fillings}} ингредиентов</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ОСНОВА РОЛЛА -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" :style="{ background: 'linear-gradient(135deg, #f39c12 0%, #e67e22 100%)' }">
                        <i class="fa-solid fa-circle"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Основа ролла</h6>
                        <p class="section-subtitle">Выбери основу (можно несколько)</p>
                    </div>
                </div>

                <div class="ingredients-grid">
                    <button
                        v-for="ingredient in getIngredientsByType(11)"
                        :key="ingredient.id"
                        type="button"
                        class="ingredient-card"
                        :class="{ 'selected': isIngredientSelected(ingredient.id) }"
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
                        <div class="ingredient-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ПОКРЫТИЕ РОЛЛА -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" :style="{ background: 'linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%)' }">
                        <i class="fa-solid fa-palette"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Покрытие ролла</h6>
                        <p class="section-subtitle">Выбери одно покрытие</p>
                    </div>
                </div>

                <div class="ingredients-grid">
                    <button
                        v-for="ingredient in getIngredientsByType(9)"
                        :key="ingredient.id"
                        type="button"
                        class="ingredient-card"
                        :class="{ 'selected': roll_coating === ingredient.id }"
                        @click="selectCoating(ingredient.id)"
                    >
                        <div class="ingredient-emoji">{{ ingredient.emoji }}</div>
                        <div class="ingredient-info">
                            <div class="ingredient-name">{{ ingredient.title }}</div>
                            <div class="ingredient-details">
                                <span>{{ ingredient.weight }} г</span>
                                <span class="ingredient-price">{{ formatPrice(ingredient.price) }}</span>
                            </div>
                        </div>
                        <div class="ingredient-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- НАЧИНКА -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" :style="{ background: 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)' }">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Начинка</h6>
                        <p class="section-subtitle">
                            Выбрано {{ fillings.length }} из {{max_fillings}} ингредиентов
                        </p>
                    </div>
                </div>

                <!-- Прогресс-бар -->
                <div class="filling-progress">
                    <div class="progress-bar">
                        <div
                            class="progress-fill"
                            :style="{ width: (fillings.length / max_fillings * 100) + '%' }"
                            :class="{ 'is-full': fillings.length >= max_fillings }"
                        ></div>
                    </div>
                    <div class="progress-text">
                        {{ fillings.length >= max_fillings ? 'Максимум достигнут' : `Осталось ${max_fillings - fillings.length}` }}
                    </div>
                </div>

                <div class="ingredients-grid">
                    <button
                        v-for="ingredient in getIngredientsByType(10)"
                        :key="ingredient.id"
                        type="button"
                        class="ingredient-card"
                        :class="{
                            'selected': isIngredientSelected(ingredient.id),
                            'has-multiple': hasManyItems(ingredient.id) > 1,
                            'disabled': fillings.length >= max_fillings && !isIngredientSelected(ingredient.id)
                        }"
                        @click="toggleFilling(ingredient.id)"
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
                                :disabled="fillings.length >=  max_fillings"
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
            <!-- КОЛИЧЕСТВО ПОРЦИЙ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" :style="{ background: 'linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%)' }">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Количество порций</h6>
                        <p class="section-subtitle">Цена указана за одну порцию (8 роллов)</p>
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
                        <div class="qty-label">{{ pluralize(summary_count, 'порция', 'порции', 'порций') }}</div>
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
                        {{ summary_weight }} г · {{ summary_count }} {{ pluralize(summary_count, 'порция', 'порции', 'порций') }}
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
                        :disabled="fillings.length === 0"
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
    name: "SushiRollCalculator",

    data() {
        return {
            max_fillings: 8,
            roll_coating: null,
            summary_count: 1,
            fillings: [],
            message: '',

            ingredients: [
                // Покрытие (type 9)
                { id: 77, type: 9, title: 'Кунжут', price: 30, weight: 10, emoji: '🌾' },
                { id: 78, type: 9, title: 'Икра масаго', price: 60, weight: 15, emoji: '🟠' },

                // Начинка (type 10)
                { id: 1, type: 10, title: 'Лосось', price: 120, weight: 40, emoji: '🐟' },
                { id: 2, type: 10, title: 'Угорь', price: 140, weight: 40, emoji: '🍱' },
                { id: 3, type: 10, title: 'Сыр креметта', price: 60, weight: 30, emoji: '🧀' },
                { id: 4, type: 10, title: 'Огурец', price: 20, weight: 20, emoji: '🥒' },

                // Основа (type 11)
                { id: 11, type: 11, title: 'Рис', price: 50, weight: 100, emoji: '🍚' },
                { id: 12, type: 11, title: 'Нори', price: 20, weight: 5, emoji: '🌿' },
            ],
        };
    },

    computed: {
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
            const bases = this.fillings.filter(id => {
                const item = this.ingredients.find(i => i.id === id);
                return item && item.type === 11;
            });
            if (bases.length === 0) return '';
            if (bases.includes(11) && bases.includes(12)) return 'base-rice-nori';
            if (bases.includes(11)) return 'base-rice';
            if (bases.includes(12)) return 'base-nori';
            return '';
        },

        coatingClass() {
            if (!this.roll_coating) return '';
            return `coating-${this.roll_coating}`;
        },

        visibleFillings() {
            return this.fillings
                .map(id => this.ingredients.find(i => i.id === id))
                .filter(item => item && item.type === 10);
        },
    },

    watch: {
        roll_coating(newVal, oldVal) {
            if (oldVal) this.removeItem(oldVal);
            if (newVal) this.addItem(newVal);
        },
    },

    methods: {
        getIngredientsByType(type) {
            return this.ingredients.filter(i => i.type === type);
        },

        selectCoating(id) {
            if (this.roll_coating === id) {
                this.removeItem(id);
                this.roll_coating = null;
            } else {
                if (this.roll_coating) {
                    this.removeItem(this.roll_coating);
                }
                this.roll_coating = id;
                this.addItem(id);
            }
        },

        toggleIngredient(id) {
            if (this.isIngredientSelected(id)) {
                this.removeItem(id);
            } else {
                this.addItem(id);
            }
        },

        toggleFilling(id) {
            if (this.isIngredientSelected(id)) {
                this.removeItem(id);
            } else {
                if (this.fillings.length >= this.max_fillings) {
                    this.$notify?.({
                        title: 'Начинка',
                        text: `Максимум ${this.max_fillings} ингредиентов`,
                        type: 'warning',
                    });
                    return;
                }
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

        fillingPosition(index) {
            const positions = [
                { top: '20%', left: '30%' },
                { top: '40%', left: '60%' },
                { top: '60%', left: '25%' },
                { top: '30%', left: '55%' },
                { top: '50%', left: '40%' },
                { top: '70%', left: '50%' },
            ];
            return positions[index % positions.length];
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
            this.roll_coating = null;
            this.summary_count = 1;

            this.$notify?.({
                title: 'Калькулятор',
                text: 'Калькулятор очищен',
                type: 'info',
            });
        },

        addToCart() {
            if (this.fillings.length === 0) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Добавь хотя бы один ингредиент',
                    type: 'warning',
                });
                return;
            }

            console.log('ROLL ADD TO CART', {
                coating: this.roll_coating,
                items: this.fillings,
                count: this.summary_count,
                price: this.summary_price,
                weight: this.summary_weight,
            });

            this.$notify?.({
                title: 'Корзина',
                text: `Ролл добавлен в корзину (${this.summary_count} ${this.pluralize(this.summary_count, 'порция', 'порции', 'порций')})`,
                type: 'success',
            });

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
.roll-calculator {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 120px;
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.roll-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 50%, #43e97b 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
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
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
   ВИЗУАЛИЗАЦИЯ РОЛЛА
   ========================================== */
.roll-preview-section {
    margin-bottom: 32px;
    text-align: center;
}

.roll-preview {
    position: relative;
    width: 240px;
    height: 240px;
    margin: 0 auto 20px;
}

.roll-outer {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

.roll-base {
    position: absolute;
    inset: 0;
    background: #f5f5dc;
    border: 10px solid #2c3e50;
    transition: all 0.3s ease;
    border-radius: 50%;
}

.roll-base.base-rice {
    background: #fff8dc;
    border-color: #2c3e50;
}

.roll-base.base-nori {
    background: #2c3e50;
    border-color: #1a252f;
}

.roll-base.base-rice-nori {
    background: linear-gradient(135deg, #fff8dc 0%, #2c3e50 100%);
    border-color: #1a252f;
}

.roll-coating {
    position: absolute;
    inset: 10%;
    border-radius: 50%;
    opacity: 0.8;
    animation: layerAppear 0.4s ease;
}

.roll-coating.coating-77 {
    background: radial-gradient(circle, #f5deb3 0%, #deb887 100%);
}

.roll-coating.coating-78 {
    background: radial-gradient(circle, #ff6347 0%, #ff4500 100%);
}

.roll-filling {
    position: absolute;
    inset: 25%;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
}

.filling-piece {
    position: absolute;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    animation: fillingAppear 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.filling-1 { background: #fa8072; } /* Лосось */
.filling-2 { background: #8b4513; } /* Угорь */
.filling-3 { background: #ffd700; } /* Сыр */
.filling-4 { background: #228b22; } /* Огурец */

@keyframes layerAppear {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 0.8;
        transform: scale(1);
    }
}

@keyframes fillingAppear {
    from {
        opacity: 0;
        transform: scale(0) rotate(-180deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

.roll-shadow {
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
   ПРОГРЕСС-БАР НАЧИНКИ
   ========================================== */
.filling-progress {
    margin-bottom: 16px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: var(--bs-secondary-bg);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progress-fill.is-full {
    background: linear-gradient(90deg, #198754 0%, #20c997 100%);
}

.progress-text {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    text-align: center;
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

.ingredient-card:hover:not(.disabled) {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.1);
}

.ingredient-card.selected {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
}

.ingredient-card.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.ingredient-emoji {
    font-size: 2rem;
    line-height: 1;
    transition: transform 0.3s ease;
}

.ingredient-card:hover:not(.disabled) .ingredient-emoji {
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

.counter-btn:hover:not(:disabled) {
    transform: scale(1.1);
}

.counter-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.counter-value {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-primary);
    min-width: 20px;
}

/* ==========================================
   КОЛИЧЕСТВО ПОРЦИЙ
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
    .roll-preview {
        width: 200px;
        height: 200px;
    }

    .hero-title {
        font-size: 1.5rem;
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
