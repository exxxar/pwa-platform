<template>
    <div class="pancake-calculator">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="pancake-hero">
            <div class="hero-background"></div>
            <div class="hero-particles">
                <span v-for="i in 12" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">🥞</div>
                    <div class="hero-sparkle sparkle-1">🍓</div>
                    <div class="hero-sparkle sparkle-2">🍯</div>
                    <div class="hero-sparkle sparkle-3">🍌</div>
                </div>
                <h1 class="hero-title">Сладкие блинчики</h1>
                <p class="hero-subtitle">Собери свою идеальную стопку с любимыми добавками!</p>
            </div>
        </div>

        <div class="calculator-content">

            <!-- ========================================== -->
            <!-- ВИЗУАЛИЗАЦИЯ СТОПКИ -->
            <!-- ========================================== -->
            <div class="stack-preview-section">
                <div class="pancake-stack">
                    <!-- Базовые блины -->
                    <div
                        v-for="(base, index) in selectedBases"
                        :key="'base-' + base.id + '-' + index"
                        class="pancake-layer base-layer"
                        :style="{
                            backgroundColor: base.color,
                            animationDelay: index * 0.1 + 's'
                        }"
                    >
                        <div class="pancake-texture"></div>
                    </div>

                    <!-- Добавки (топпинги) -->
                    <div
                        v-for="(sweet, index) in selectedSweets"
                        :key="'sweet-' + sweet.id + '-' + index"
                        class="pancake-layer topping-layer"
                        :style="{
                            backgroundColor: sweet.color + '40',
                            animationDelay: (selectedBases.length + index) * 0.1 + 's'
                        }"
                    >
                        <span class="topping-emoji">{{ sweet.emoji }}</span>
                    </div>

                    <!-- Тень под стопкой -->
                    <div class="stack-shadow"></div>
                </div>

                <!-- Быстрая статистика -->
                <div class="quick-stats">
                    <div class="stat-item">
                        <i class="fa-solid fa-weight-scale"></i>
                        <span>{{ totalWeight }} г</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fa-solid fa-ruble-sign"></i>
                        <span>{{ formatPrice(totalPrice) }}</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ОСНОВА БЛИНЧИКА -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);">
                        <i class="fa-solid fa-circle"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Основа блинчика</h6>
                        <p class="section-subtitle">Выбери тип теста (можно несколько)</p>
                    </div>
                </div>

                <div class="ingredients-grid">
                    <button
                        v-for="item in baseFillings"
                        :key="item.id"
                        type="button"
                        class="ingredient-card"
                        :class="{
                            'selected': isIngredientSelected(item.id),
                            'has-multiple': count(item.id) > 1,
                            'disabled': item.disabled
                        }"
                        @click="!item.disabled && toggleIngredient(item.id)"
                    >
                        <div class="ingredient-emoji">{{ item.emoji }}</div>
                        <div class="ingredient-info">
                            <div class="ingredient-name">{{ item.title }}</div>
                            <div class="ingredient-desc">{{ item.desc }}</div>
                            <div class="ingredient-details">
                                <span>{{ item.weight }} г</span>
                                <span class="ingredient-price">{{ formatPrice(item.price) }}</span>
                            </div>
                        </div>

                        <!-- Счётчик -->
                        <div v-if="count(item.id) > 0" class="ingredient-counter">
                            <button type="button" class="counter-btn minus" @click.stop="remove(item.id)">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <span class="counter-value">{{ count(item.id) }}</span>
                            <button type="button" class="counter-btn plus" @click.stop="add(item.id)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                        <!-- Галочка -->
                        <div v-else class="ingredient-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- СЛАДКИЕ ДОБАВКИ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                        <i class="fa-solid fa-candy-cane"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Сладкие добавки</h6>
                        <p class="section-subtitle">Фрукты, соусы и топпинги</p>
                    </div>
                </div>

                <div class="ingredients-grid">
                    <button
                        v-for="item in sweetFillings"
                        :key="item.id"
                        type="button"
                        class="ingredient-card"
                        :class="{
                            'selected': isIngredientSelected(item.id),
                            'has-multiple': count(item.id) > 1,
                            'disabled': item.disabled
                        }"
                        @click="!item.disabled && toggleIngredient(item.id)"
                    >
                        <div class="ingredient-emoji">{{ item.emoji }}</div>
                        <div class="ingredient-info">
                            <div class="ingredient-name">{{ item.title }}</div>
                            <div class="ingredient-details">
                                <span>{{ item.weight }} г</span>
                                <span class="ingredient-price">{{ formatPrice(item.price) }}</span>
                            </div>
                        </div>

                        <div v-if="count(item.id) > 0" class="ingredient-counter">
                            <button type="button" class="counter-btn minus" @click.stop="remove(item.id)">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <span class="counter-value">{{ count(item.id) }}</span>
                            <button type="button" class="counter-btn plus" @click.stop="add(item.id)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div v-else class="ingredient-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КОЛИЧЕСТВО -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Количество порций</h6>
                        <p class="section-subtitle">Сколько таких стопок приготовить?</p>
                    </div>
                </div>

                <div class="quantity-selector">
                    <button
                        type="button"
                        class="qty-btn"
                        :disabled="summaryCount <= 1"
                        @click="summaryCount--"
                    >
                        <i class="fa-solid fa-minus"></i>
                    </button>

                    <div class="qty-display">
                        <div class="qty-value">{{ summaryCount }}</div>
                        <div class="qty-label">{{ pluralize(summaryCount, 'порция', 'порции', 'порций') }}</div>
                    </div>

                    <button
                        type="button"
                        class="qty-btn"
                        @click="summaryCount++"
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
                    <div class="summary-price">{{ formatPrice(totalPrice) }}</div>
                    <div class="summary-details">
                        {{ totalWeight }} г · {{ summaryCount }} {{ pluralize(summaryCount, 'шт.', 'шт.', 'шт.') }}
                    </div>
                </div>
                <div class="summary-actions">
                    <button
                        type="button"
                        class="clear-btn"
                        @click="clear"
                    >
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                    <button
                        type="button"
                        class="add-to-cart-btn"
                        :disabled="fillings.length === 0 || summaryCount === 0"
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
    name: 'SweetPancakeCalculator',

    data() {
        return {
            fillings: [],
            summaryCount: 1,

            items: [
                // Основы
                { id: 501, type: 'base', title: 'Классический', desc: 'Тонкий и нежный', weight: 100, price: 80, emoji: '🥞', color: '#f4d47c', disabled: false },
                { id: 502, type: 'base', title: 'Шоколадный', desc: 'С добавлением какао', weight: 110, price: 100, emoji: '🍫', color: '#6f4e37', disabled: false },
                { id: 503, type: 'base', title: 'Овсяный', desc: 'Полезный и сытный', weight: 105, price: 90, emoji: '🌾', color: '#d2b48c', disabled: false },
                { id: 504, type: 'base', title: 'Безглютеновый', desc: 'На рисовой муке', weight: 100, price: 110, emoji: '🌿', color: '#e8f5e9', disabled: false },

                // Сладкие добавки
                { id: 601, type: 'sweet', title: 'Клубника', weight: 30, price: 40, emoji: '🍓', color: '#ff6b6b', disabled: false },
                { id: 602, type: 'sweet', title: 'Банан', weight: 40, price: 35, emoji: '🍌', color: '#ffe135', disabled: false },
                { id: 603, type: 'sweet', title: 'Шоколад', weight: 20, price: 50, emoji: '🍫', color: '#5c3317', disabled: false },
                { id: 604, type: 'sweet', title: 'Мёд', weight: 25, price: 30, emoji: '🍯', color: '#ffa500', disabled: false },
                { id: 605, type: 'sweet', title: 'Сгущёнка', weight: 30, price: 35, emoji: '🥛', color: '#fff8dc', disabled: false },
                { id: 606, type: 'sweet', title: 'Взбитые сливки', weight: 20, price: 45, emoji: '🍦', color: '#ffffff', disabled: false },
                { id: 607, type: 'sweet', title: 'Черника', weight: 25, price: 45, emoji: '🫐', color: '#4169e1', disabled: false },
                { id: 608, type: 'sweet', title: 'Орехи', weight: 15, price: 40, emoji: '🥜', color: '#8b4513', disabled: false }
            ]
        }
    },

    computed: {
        baseFillings() {
            return this.items.filter(i => i.type === 'base')
        },
        sweetFillings() {
            return this.items.filter(i => i.type === 'sweet')
        },
        selectedBases() {
            return this.fillings
                .map(id => this.items.find(i => i.id === id))
                .filter(item => item && item.type === 'base');
        },
        selectedSweets() {
            return this.fillings
                .map(id => this.items.find(i => i.id === id))
                .filter(item => item && item.type === 'sweet');
        },
        totalWeight() {
            return this.summaryCount * this.fillings.reduce((sum, id) => {
                const item = this.items.find(i => i.id === id)
                return sum + (item ? item.weight : 0)
            }, 0)
        },
        totalPrice() {
            return this.summaryCount * this.fillings.reduce((sum, id) => {
                const item = this.items.find(i => i.id === id)
                return sum + (item ? item.price : 0)
            }, 0)
        }
    },

    methods: {
        particleStyle(i) {
            const size = Math.random() * 6 + 3;
            const left = Math.random() * 100;
            const delay = Math.random() * 5;
            const duration = Math.random() * 10 + 10;
            return {
                width: `${size}px`,
                height: `${size}px`,
                left: `${left}%`,
                animationDelay: `${delay}s`,
                animationDuration: `${duration}s`,
            };
        },

        isIngredientSelected(id) {
            return this.fillings.includes(id);
        },

        toggleIngredient(id) {
            if (this.isIngredientSelected(id)) {
                this.remove(id);
            } else {
                this.add(id);
            }
        },

        count(id) {
            return this.fillings.filter(f => f === id).length;
        },

        add(id) {
            this.fillings.push(id);
        },

        remove(id) {
            const index = this.fillings.indexOf(id);
            if (index !== -1) this.fillings.splice(index, 1);
        },

        clear() {
            this.fillings = [];
            this.summaryCount = 1;

            this.$notify?.({
                title: 'Калькулятор',
                text: 'Калькулятор очищен',
                type: 'info',
            });
        },

        addToCart() {
            if (this.fillings.length === 0 || this.summaryCount === 0) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Добавь хотя бы один ингредиент',
                    type: 'warning',
                });
                return;
            }

            const order = {
                items: this.fillings,
                count: this.summaryCount,
                price: this.totalPrice,
                weight: this.totalWeight,
            };

            console.log('PANCAKE ADD TO CART', order);

            this.$notify?.({
                title: 'Корзина',
                text: `Блинчики добавлены в корзину (${this.summaryCount} ${this.pluralize(this.summaryCount, 'порция', 'порции', 'порций')})`,
                type: 'success',
            });

            setTimeout(() => {
                this.clear();
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
        }
    }
}
</script>

<style scoped>
.pancake-calculator {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 120px;
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.pancake-hero {
    position: relative;
    padding: 48px 24px 40px;
    background: linear-gradient(135deg, #f6d365 0%, #fda085 50%, #ff9a9e 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
}

.hero-particles {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.particle {
    position: absolute;
    bottom: -10px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% { transform: translateY(0) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.hero-icon {
    font-size: 4.5rem;
    animation: pancakeBounce 3s ease-in-out infinite;
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.2));
}

@keyframes pancakeBounce {
    0%, 100% { transform: translateY(0) rotate(-5deg); }
    50% { transform: translateY(-15px) rotate(5deg); }
}

.hero-sparkle {
    position: absolute;
    font-size: 1.8rem;
    animation: sparkleFloat 3s ease-in-out infinite;
}

.sparkle-1 { top: -15px; right: -20px; animation-delay: 0s; }
.sparkle-2 { bottom: -15px; left: -20px; animation-delay: 1s; }
.sparkle-3 { top: 50%; right: -30px; animation-delay: 2s; }

@keyframes sparkleFloat {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.8; }
    50% { transform: translateY(-10px) scale(1.2); opacity: 1; }
}

.hero-title {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 8px;
    text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
   ВИЗУАЛИЗАЦИЯ СТОПКИ
   ========================================== */
.stack-preview-section {
    margin-bottom: 32px;
    text-align: center;
}

.pancake-stack {
    position: relative;
    width: 220px;
    height: 220px;
    margin: 0 auto 20px;
    display: flex;
    flex-direction: column-reverse;
    align-items: center;
    justify-content: flex-start;
}

.pancake-layer {
    width: 180px;
    height: 24px;
    border-radius: 50%;
    margin-top: -12px;
    position: relative;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    animation: layerDrop 0.5s cubic-bezier(0.4, 0, 0.2, 1) backwards;
}

.pancake-layer:first-child {
    margin-top: 0;
}

.pancake-texture {
    position: absolute;
    inset: 4px;
    border-radius: 50%;
    border: 2px dashed rgba(0,0,0,0.1);
    opacity: 0.5;
}

.topping-layer {
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed rgba(0,0,0,0.1);
}

.topping-emoji {
    font-size: 1.2rem;
    animation: toppingPop 0.4s cubic-bezier(0.4, 0, 0.2, 1) backwards;
}

@keyframes layerDrop {
    from {
        opacity: 0;
        transform: translateY(-30px) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes toppingPop {
    from {
        opacity: 0;
        transform: scale(0) rotate(-180deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

.stack-shadow {
    width: 160px;
    height: 16px;
    background: radial-gradient(ellipse, rgba(0,0,0,0.2) 0%, transparent 70%);
    margin-top: -8px;
    filter: blur(4px);
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
    color: #fda085;
    font-size: 0.8rem;
}

.stat-divider {
    width: 1px;
    height: 20px;
    background: var(--bs-border-color);
}

/* ==========================================
   КАРТОЧКИ ИНГРЕДИЕНТОВ
   ========================================== */
.ingredients-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
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
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.ingredient-card:hover:not(.disabled) {
    border-color: #fda085;
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(253, 160, 133, 0.2);
}

.ingredient-card.selected {
    border-color: #ff9a9e;
    background: linear-gradient(135deg, rgba(255, 154, 158, 0.08) 0%, rgba(254, 207, 239, 0.04) 100%);
}

.ingredient-card.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--bs-secondary-bg);
}

.ingredient-emoji {
    font-size: 2.2rem;
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
    font-size: 0.85rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.ingredient-desc {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-bottom: 6px;
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
    color: #ff9a9e;
}

.ingredient-check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #ff9a9e;
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

/* Счётчик */
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
    background: #ff9a9e;
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
    color: #ff9a9e;
    min-width: 20px;
}

/* ==========================================
   КОЛИЧЕСТВО
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
    background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(253, 160, 133, 0.3);
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
    color: #fda085;
    line-height: 1;
}

.qty-label {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    margin-top: 4px;
}

/* ==========================================
   STICKY ПАНЕЛЬ
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
    color: #fda085;
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
    background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(253, 160, 133, 0.3);
}

.add-to-cart-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(253, 160, 133, 0.4);
}

.add-to-cart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .pancake-stack {
        width: 180px;
        height: 180px;
    }

    .pancake-layer {
        width: 150px;
        height: 20px;
        margin-top: -10px;
    }

    .hero-title {
        font-size: 1.6rem;
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
