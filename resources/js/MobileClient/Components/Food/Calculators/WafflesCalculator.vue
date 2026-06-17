<template>
    <div class="waffle-calculator">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="waffle-hero">
            <div class="hero-background"></div>
            <div class="hero-particles">
                <span v-for="i in 15" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">🧇</div>
                    <div class="hero-sparkle sparkle-1">🍓</div>
                    <div class="hero-sparkle sparkle-2">🍫</div>
                    <div class="hero-sparkle sparkle-3">🍌</div>
                </div>
                <h1 class="hero-title">Гонконгские вафли</h1>
                <p class="hero-subtitle">Собери свою идеальную вафлю из свежих ингредиентов!</p>
            </div>
        </div>

        <div class="calculator-content">

            <!-- ========================================== -->
            <!-- ВИЗУАЛИЗАЦИЯ ВАФЛИ -->
            <!-- ========================================== -->
            <div class="waffle-preview-section">
                <div class="waffle-preview">
                    <svg viewBox="0 0 300 300" class="waffle-svg">
                        <!-- Тень -->
                        <ellipse cx="150" cy="280" rx="120" ry="10" fill="rgba(0,0,0,0.15)" />

                        <!-- Основа вафли (круглые шарики - фирменная форма) -->
                        <g class="waffle-base" :class="baseClass">
                            <!-- Ряды шариков 5x5 -->
                            <template v-for="row in 5" :key="'row-' + row">
                                <circle
                                    v-for="col in 5"
                                    :key="'base-' + row + '-' + col"
                                    :cx="60 + (col - 1) * 45"
                                    :cy="60 + (row - 1) * 45"
                                    r="20"
                                    class="waffle-ball"
                                />
                            </template>
                        </g>

                        <!-- Начинка внутри (между шариками) -->
                        <g v-if="hasFilling" class="waffle-filling">
                            <circle v-for="(filling, index) in visibleFillings"
                                    :key="'filling-' + index"
                                    :cx="filling.cx"
                                    :cy="filling.cy"
                                    r="8"
                                    :fill="filling.color"
                                    class="filling-dot"
                                    :style="{ animationDelay: index * 0.1 + 's' }"
                            />
                        </g>

                        <!-- Фрукты сверху -->
                        <g v-if="hasFruits" class="waffle-fruits">
                            <g v-for="(fruit, index) in visibleFruits"
                               :key="'fruit-' + index"
                               class="fruit-item"
                               :style="{ animationDelay: index * 0.15 + 's' }"
                            >
                                <text
                                    :x="fruit.x"
                                    :y="fruit.y"
                                    font-size="24"
                                    text-anchor="middle"
                                >{{ fruit.emoji }}</text>
                            </g>
                        </g>

                        <!-- Соусы (капли сверху) -->
                        <g v-if="hasSauces" class="waffle-sauces">
                            <path v-for="(sauce, index) in visibleSauces"
                                  :key="'sauce-' + index"
                                  :d="sauce.path"
                                  :fill="sauce.color"
                                  class="sauce-drip"
                                  :style="{ animationDelay: index * 0.2 + 's' }"
                            />
                        </g>
                    </svg>

                    <!-- Название композиции -->
                    <div v-if="waffleName" class="waffle-name-badge">
                        {{ waffleName }}
                    </div>
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
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>{{ fillings.length }} ингредиентов</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ОСНОВА ВАФЛИ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);">
                        <i class="fa-solid fa-circle"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Основа вафли</h6>
                        <p class="section-subtitle">Выбери тип теста</p>
                    </div>
                </div>

                <div class="base-grid">
                    <button
                        v-for="base in baseItems"
                        :key="base.id"
                        type="button"
                        class="base-card"
                        :class="{ 'selected': baseId === base.id }"
                        @click="baseId = base.id"
                    >
                        <div class="base-emoji">{{ base.emoji }}</div>
                        <div class="base-info">
                            <div class="base-name">{{ base.title }}</div>
                            <div class="base-desc">{{ base.description }}</div>
                            <div class="base-meta">
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
            <!-- СЕКЦИИ ИНГРЕДИЕНТОВ -->
            <!-- ========================================== -->
            <div v-for="section in sections" :key="section.key" class="section">
                <div class="section-header">
                    <div class="section-icon" :style="{ background: section.gradient }">
                        <i :class="section.icon"></i>
                    </div>
                    <div>
                        <h6 class="section-title">{{ section.title }}</h6>
                        <p class="section-subtitle">{{ section.subtitle }}</p>
                    </div>
                </div>

                <div class="ingredients-grid">
                    <button
                        v-for="item in section.items"
                        :key="item.id"
                        type="button"
                        class="ingredient-card"
                        :class="{
                            'selected': isIngredientSelected(item.id),
                            'has-multiple': count(item.id) > 1
                        }"
                        @click="toggleIngredient(item.id)"
                    >
                        <div class="ingredient-emoji">{{ item.emoji }}</div>
                        <div class="ingredient-info">
                            <div class="ingredient-name">{{ item.title }}</div>
                            <div class="ingredient-details">
                                <span>{{ item.weight }} г</span>
                                <span class="ingredient-price">{{ formatPrice(item.price) }}</span>
                            </div>
                        </div>

                        <!-- Счётчик -->
                        <div v-if="count(item.id) > 0" class="ingredient-counter">
                            <button
                                type="button"
                                class="counter-btn minus"
                                @click.stop="remove(item.id)"
                            >
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <span class="counter-value">{{ count(item.id) }}</span>
                            <button
                                type="button"
                                class="counter-btn plus"
                                @click.stop="add(item.id)"
                            >
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
            <!-- КОЛИЧЕСТВО ВАФЕЛЬ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Количество вафель</h6>
                        <p class="section-subtitle">Сколько таких вафель приготовить?</p>
                    </div>
                </div>

                <div class="quantity-selector">
                    <button
                        type="button"
                        class="qty-btn"
                        :disabled="summaryCount <= 0"
                        @click="summaryCount--"
                    >
                        <i class="fa-solid fa-minus"></i>
                    </button>

                    <div class="qty-display">
                        <div class="qty-value">{{ summaryCount }}</div>
                        <div class="qty-label">{{ pluralize(summaryCount, 'вафля', 'вафли', 'вафель') }}</div>
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
    name: "HongKongWaffleCalculator",

    data() {
        return {
            baseId: null,
            fillings: [],
            summaryCount: 1,

            baseItems: [
                {
                    id: 1501,
                    title: 'Классическая',
                    description: 'Нежное ванильное тесто',
                    weight: 120,
                    price: 150,
                    emoji: '🧇',
                    color: '#f4d47c'
                },
                {
                    id: 1502,
                    title: 'Шоколадная',
                    description: 'С натуральным какао',
                    weight: 130,
                    price: 170,
                    emoji: '🍫',
                    color: '#6f4e37'
                },
                {
                    id: 1503,
                    title: 'Матча',
                    description: 'С японским зелёным чаем',
                    weight: 125,
                    price: 180,
                    emoji: '🍵',
                    color: '#8fbc8f'
                },
            ],

            sections: [
                {
                    key: 'filling',
                    title: 'Начинка',
                    subtitle: 'Добавь внутрь вафли',
                    icon: 'fa-solid fa-candy-cane',
                    gradient: 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)',
                    items: [
                        { id: 1601, title: 'Маршмеллоу', weight: 30, price: 40, emoji: '🍡', color: '#fff5ee' },
                        { id: 1602, title: 'Шоколадные капли', weight: 20, price: 50, emoji: '🍫', color: '#5c3317' },
                        { id: 1603, title: 'Нутелла', weight: 35, price: 60, emoji: '🟫', color: '#3e2723' },
                        { id: 1604, title: 'Сгущёнка', weight: 30, price: 45, emoji: '🥛', color: '#fff8dc' },
                    ]
                },
                {
                    key: 'fruits',
                    title: 'Фрукты и ягоды',
                    subtitle: 'Свежие украшения',
                    icon: 'fa-solid fa-apple-whole',
                    gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                    items: [
                        { id: 1701, title: 'Клубника', weight: 40, price: 45, emoji: '🍓', color: '#dc143c' },
                        { id: 1702, title: 'Банан', weight: 50, price: 40, emoji: '🍌', color: '#ffe135' },
                        { id: 1703, title: 'Киви', weight: 35, price: 50, emoji: '🥝', color: '#8fbc8f' },
                        { id: 1704, title: 'Голубика', weight: 25, price: 55, emoji: '🫐', color: '#4169e1' },
                        { id: 1705, title: 'Манго', weight: 40, price: 60, emoji: '🥭', color: '#ffa500' },
                    ]
                },
                {
                    key: 'sauce',
                    title: 'Соусы и топпинги',
                    subtitle: 'Финальный штрих',
                    icon: 'fa-solid fa-droplet',
                    gradient: 'linear-gradient(135deg, #ffd700 0%, #ff9800 100%)',
                    items: [
                        { id: 1801, title: 'Шоколадный соус', weight: 25, price: 35, emoji: '🍫', color: '#3e2723' },
                        { id: 1802, title: 'Карамель', weight: 25, price: 35, emoji: '🍯', color: '#c68e17' },
                        { id: 1803, title: 'Сгущёнка', weight: 25, price: 30, emoji: '🥛', color: '#fff8dc' },
                        { id: 1804, title: 'Кленовый сироп', weight: 25, price: 45, emoji: '🍁', color: '#b87333' },
                    ]
                },
                {
                    key: 'topping',
                    title: 'Посыпки',
                    subtitle: 'Для красоты и вкуса',
                    icon: 'fa-solid fa-star',
                    gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    items: [
                        { id: 1901, title: 'Кокосовая стружка', weight: 10, price: 25, emoji: '🥥', color: '#fff8dc' },
                        { id: 1902, title: 'Орехи', weight: 15, price: 40, emoji: '🥜', color: '#8b4513' },
                        { id: 1903, title: 'Шоколадная крошка', weight: 15, price: 35, emoji: '🍫', color: '#3e2723' },
                        { id: 1904, title: 'Сахарная пудра', weight: 5, price: 15, emoji: '✨', color: '#ffffff' },
                    ]
                }
            ],
        };
    },

    computed: {
        allItems() {
            return [
                ...this.baseItems,
                ...this.sections.flatMap(s => s.items)
            ];
        },

        currentBase() {
            return this.baseItems.find(b => b.id === this.baseId);
        },

        baseClass() {
            if (!this.currentBase) return '';
            return `base-${this.currentBase.id}`;
        },

        hasFilling() {
            return this.fillings.some(id => {
                const item = this.allItems.find(i => i.id === id);
                return item && this.sections[0].items.some(s => s.id === id);
            });
        },

        hasFruits() {
            return this.fillings.some(id => this.sections[1].items.some(s => s.id === id));
        },

        hasSauces() {
            return this.fillings.some(id =>
                this.sections[2].items.some(s => s.id === id) ||
                this.sections[3].items.some(s => s.id === id)
            );
        },

        visibleFillings() {
            const positions = [
                { cx: 105, cy: 105 }, { cx: 150, cy: 105 }, { cx: 195, cy: 105 },
                { cx: 105, cy: 150 }, { cx: 150, cy: 150 }, { cx: 195, cy: 150 },
                { cx: 105, cy: 195 }, { cx: 150, cy: 195 }, { cx: 195, cy: 195 },
            ];

            return this.fillings
                .map((id, index) => {
                    const item = this.allItems.find(i => i.id === id);
                    if (!item || !this.sections[0].items.some(s => s.id === id)) return null;
                    return {
                        ...item,
                        cx: positions[index % positions.length].cx,
                        cy: positions[index % positions.length].cy,
                    };
                })
                .filter(Boolean)
                .slice(0, 9);
        },

        visibleFruits() {
            const positions = [
                { x: 90, y: 90 }, { x: 150, y: 80 }, { x: 210, y: 90 },
                { x: 80, y: 150 }, { x: 150, y: 150 }, { x: 220, y: 150 },
                { x: 100, y: 210 }, { x: 150, y: 220 }, { x: 200, y: 210 },
            ];

            return this.fillings
                .map((id, index) => {
                    const item = this.allItems.find(i => i.id === id);
                    if (!item || !this.sections[1].items.some(s => s.id === id)) return null;
                    return {
                        ...item,
                        x: positions[index % positions.length].x,
                        y: positions[index % positions.length].y,
                    };
                })
                .filter(Boolean)
                .slice(0, 9);
        },

        visibleSauces() {
            const saucePaths = [
                'M 100 80 Q 110 100 105 120 Q 100 140 110 160',
                'M 150 70 Q 160 90 155 110 Q 150 130 160 150',
                'M 200 80 Q 210 100 205 120 Q 200 140 210 160',
                'M 120 180 Q 130 200 125 220',
                'M 180 180 Q 190 200 185 220',
            ];

            let sauceIndex = 0;
            return this.fillings
                .map(id => {
                    const item = this.allItems.find(i => i.id === id);
                    if (!item) return null;

                    const isSauce = this.sections[2].items.some(s => s.id === id) ||
                        this.sections[3].items.some(s => s.id === id);
                    if (!isSauce) return null;

                    const path = saucePaths[sauceIndex % saucePaths.length];
                    sauceIndex++;

                    return {
                        ...item,
                        path: path,
                    };
                })
                .filter(Boolean)
                .slice(0, 5);
        },

        waffleName() {
            if (!this.currentBase) return '';
            const parts = [this.currentBase.title];

            const fruits = this.fillings
                .map(id => this.allItems.find(i => i.id === id))
                .filter(item => item && this.sections[1].items.some(s => s.id === item.id))
                .slice(0, 2);

            if (fruits.length > 0) {
                parts.push('с ' + fruits.map(f => f.title.toLowerCase()).join(' и '));
            }

            return parts.join(' ');
        },

        totalWeight() {
            const base = this.currentBase?.weight || 0;
            const extra = this.fillings.reduce((sum, id) => {
                const item = this.allItems.find(i => i.id === id);
                return sum + (item?.weight || 0);
            }, 0);
            return (base + extra) * this.summaryCount;
        },

        totalPrice() {
            const base = this.currentBase?.price || 0;
            const extra = this.fillings.reduce((sum, id) => {
                const item = this.allItems.find(i => i.id === id);
                return sum + (item?.price || 0);
            }, 0);
            return (base + extra) * this.summaryCount;
        },

        canAddToCart() {
            return this.baseId !== null && this.summaryCount > 0;
        },
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
            this.baseId = null;
            this.fillings = [];
            this.summaryCount = 1;

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
                    text: 'Выбери основу вафли',
                    type: 'warning',
                });
                return;
            }

            const order = {
                base: this.currentBase,
                fillings: this.fillings,
                count: this.summaryCount,
                price: this.totalPrice,
                weight: this.totalWeight,
            };

            console.log('WAFFLE ADD TO CART', order);

            this.$notify?.({
                title: 'Корзина',
                text: `${this.waffleName || 'Вафля'} добавлена в корзину`,
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
        },
    },
};
</script>

<style scoped>
.waffle-calculator {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 120px;
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.waffle-hero {
    position: relative;
    padding: 48px 24px 40px;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 50%, #ff6b35 100%);
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
    animation: waffleBounce 3s ease-in-out infinite;
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.2));
}

@keyframes waffleBounce {
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
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
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
   ВИЗУАЛИЗАЦИЯ ВАФЛИ
   ========================================== */
.waffle-preview-section {
    margin-bottom: 32px;
    text-align: center;
}

.waffle-preview {
    position: relative;
    width: 280px;
    height: 280px;
    margin: 0 auto 20px;
}

.waffle-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.15));
}

/* Шарики вафли */
.waffle-ball {
    fill: #f4d47c;
    stroke: #d4a017;
    stroke-width: 2;
    transition: fill 0.4s ease;
    animation: ballAppear 0.5s cubic-bezier(0.4, 0, 0.2, 1) backwards;
}

.waffle-base.base-1501 .waffle-ball { fill: #f4d47c; stroke: #d4a017; }
.waffle-base.base-1502 .waffle-ball { fill: #6f4e37; stroke: #3e2723; }
.waffle-base.base-1503 .waffle-ball { fill: #8fbc8f; stroke: #556b2f; }

@keyframes ballAppear {
    from {
        opacity: 0;
        transform: scale(0);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Начинка внутри */
.filling-dot {
    animation: fillingPop 0.5s cubic-bezier(0.4, 0, 0.2, 1) backwards;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

@keyframes fillingPop {
    from {
        opacity: 0;
        transform: scale(0) rotate(-180deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

/* Фрукты */
.fruit-item {
    animation: fruitDrop 0.6s cubic-bezier(0.4, 0, 0.2, 1) backwards;
}

@keyframes fruitDrop {
    from {
        opacity: 0;
        transform: translateY(-30px) scale(0.5);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Соусы */
.sauce-drip {
    animation: sauceDrip 0.8s ease-out backwards;
    opacity: 0.85;
}

@keyframes sauceDrip {
    from {
        opacity: 0;
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
    }
    to {
        opacity: 0.85;
        stroke-dashoffset: 0;
    }
}

/* Бейдж названия */
.waffle-name-badge {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    padding: 6px 16px;
    background: linear-gradient(135deg, #ff9800 0%, #ff6b35 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
    max-width: 90%;
    overflow: hidden;
    text-overflow: ellipsis;
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
    color: #ff9800;
    font-size: 0.8rem;
}

.stat-divider {
    width: 1px;
    height: 20px;
    background: var(--bs-border-color);
}

/* ==========================================
   ОСНОВА ВАФЛИ
   ========================================== */
.base-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.base-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.base-card:hover {
    border-color: #ff9800;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(255, 152, 0, 0.15);
}

.base-card.selected {
    border-color: #ff6b35;
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.08) 0%, rgba(255, 107, 53, 0.04) 100%);
    box-shadow: 0 4px 20px rgba(255, 107, 53, 0.2);
}

.base-emoji {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.base-info {
    flex: 1;
    min-width: 0;
}

.base-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.base-desc {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    margin-bottom: 6px;
}

.base-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.base-price {
    font-weight: 700;
    color: #ff6b35;
}

.base-check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ff6b35;
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
    border-color: #ff9800;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.15);
}

.ingredient-card.selected {
    border-color: #ff6b35;
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.05) 0%, rgba(255, 107, 53, 0.02) 100%);
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
    color: #ff6b35;
}

.ingredient-check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #ff6b35;
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
    background: #ff6b35;
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
    color: #ff6b35;
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
    background: linear-gradient(135deg, #ff9800 0%, #ff6b35 100%);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
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
    color: #ff6b35;
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
    color: #ff6b35;
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
    background: linear-gradient(135deg, #ff9800 0%, #ff6b35 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(255, 107, 53, 0.3);
}

.add-to-cart-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255, 107, 53, 0.4);
}

.add-to-cart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .waffle-preview {
        width: 240px;
        height: 240px;
    }

    .hero-title {
        font-size: 1.6rem;
    }

    .base-grid {
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
