<template>
    <div class="burger-builder-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="burger-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">🍔</div>
                    <div class="hero-sparkle sparkle-1">🧀</div>
                    <div class="hero-sparkle sparkle-2">🥓</div>
                    <div class="hero-sparkle sparkle-3">🍟</div>
                </div>
                <h1 class="hero-title">Собери свой бургер</h1>
                <p class="hero-subtitle">Ты шеф-повар! Выбери ингредиенты и создай идеальный вкус</p>
            </div>
        </div>

        <div class="builder-content">

            <!-- ========================================== -->
            <!-- ВИЗУАЛИЗАЦИЯ БУРГЕРА -->
            <!-- ========================================== -->
            <div class="burger-preview-section">
                <div class="burger-stack">

                    <!-- 1. Нижняя булка (динамический цвет) -->
                    <div v-if="selectedBottomBun" class="burger-layer layer-bun-bottom" :style="{ backgroundColor: selectedBottomBun.color, borderBottomColor: selectedBottomBun.crustColor }" key="bun-bottom">
                        <div class="layer-texture"></div>
                    </div>

                    <!-- 2. Котлеты -->
                    <div
                        v-for="(item, index) in getLayersByCategory('patties')"
                        :key="'patty-' + index"
                        class="burger-layer layer-patty"
                        :style="{ animationDelay: index * 0.1 + 's' }"
                    >
                        <span class="layer-emoji">{{ item.emoji }}</span>
                    </div>

                    <!-- 3. Сыр -->
                    <div
                        v-for="(item, index) in getLayersByCategory('cheese')"
                        :key="'cheese-' + index"
                        class="burger-layer layer-cheese"
                        :style="{ backgroundColor: item.color, animationDelay: index * 0.1 + 's' }"
                    >
                        <span class="layer-emoji">{{ item.emoji }}</span>
                    </div>

                    <!-- 4. Овощи -->
                    <div
                        v-for="(item, index) in getLayersByCategory('veggies')"
                        :key="'veggie-' + index"
                        class="burger-layer layer-veggie"
                        :style="{ backgroundColor: item.color, animationDelay: index * 0.1 + 's' }"
                    >
                        <span class="layer-emoji">{{ item.emoji }}</span>
                    </div>

                    <!-- 5. Соусы -->
                    <div
                        v-for="(item, index) in getLayersByCategory('sauces')"
                        :key="'sauce-' + index"
                        class="burger-layer layer-sauce"
                        :style="{ backgroundColor: item.color, animationDelay: index * 0.1 + 's' }"
                    >
                        <span class="layer-emoji">{{ item.emoji }}</span>
                    </div>

                    <!-- 6. Добавки -->
                    <div
                        v-for="(item, index) in getLayersByCategory('extras')"
                        :key="'extra-' + index"
                        class="burger-layer layer-extra"
                        :style="{ backgroundColor: item.color, animationDelay: index * 0.1 + 's' }"
                    >
                        <span class="layer-emoji">{{ item.emoji }}</span>
                    </div>

                    <!-- 7. Верхняя булка (динамический цвет + опциональный кунжут) -->
                    <div v-if="selectedTopBun" class="burger-layer layer-bun-top" :style="{ backgroundColor: selectedTopBun.color, borderTopColor: selectedTopBun.crustColor }" key="bun-top">
                        <div v-if="selectedTopBun.hasSesame" class="sesame-seeds"></div>
                    </div>

                    <!-- Подсказка, если бургер пустой (теоретически недостижимо из-за дефолтных булок, но оставим для надежности) -->
                    <div v-if="totalItemsCount === 0" class="empty-burger-hint">
                        <i class="fa-solid fa-arrow-down"></i>
                        <span>Добавь булку и котлету, чтобы начать</span>
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
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КАТЕГОРИИ ИНГРЕДИЕНТОВ -->
            <!-- ========================================== -->
            <div class="sticky-controls">
                <div class="categories-scroll">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        class="category-tab"
                        :class="{ 'active': activeCategory === cat.id }"
                        @click="activeCategory = cat.id"
                    >
                        <i :class="cat.icon"></i>
                        <span>{{ cat.name }}</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- СПИСОК ИНГРЕДИЕНТОВ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="ingredients-grid">
                    <div
                        v-for="item in filteredIngredients"
                        :key="item.id"
                        class="ingredient-card"
                        :class="{ 'is-selected': getQty(item.id) > 0 }"
                    >
                        <div class="ingredient-emoji">{{ item.emoji }}</div>
                        <div class="ingredient-info">
                            <div class="ingredient-name">{{ item.name }}</div>
                            <div class="ingredient-details">
                                <span>{{ item.weight }} г</span>
                                <span class="ingredient-price">{{ formatPrice(item.price) }}</span>
                            </div>
                        </div>

                        <div class="ingredient-actions">
                            <!-- Для булок показываем только кнопку добавления/замены, убрать нельзя -->
                            <button
                                v-if="item.category === 'buns'"
                                type="button"
                                class="replace-btn"
                                :class="{ 'is-active': getQty(item.id) > 0 }"
                                @click="replaceBun(item.id)"
                            >
                                <i class="fa-solid" :class="getQty(item.id) > 0 ? 'fa-check' : 'fa-plus'"></i>
                                <span>{{ getQty(item.id) > 0 ? 'Выбрано' : 'Выбрать' }}</span>
                            </button>

                            <!-- Для остальных ингредиентов обычный степер -->
                            <div v-else-if="getQty(item.id) > 0" class="qty-stepper">
                                <button type="button" class="stepper-btn" @click="updateQty(item.id, -1)">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                <span class="qty-value">{{ getQty(item.id) }}</span>
                                <button type="button" class="stepper-btn" @click="updateQty(item.id, 1)">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                            <button v-else type="button" class="add-btn" @click="updateQty(item.id, 1)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <BurgerSummary
                v-if="totalItemsCount > 0"
                :cart="cart"
                :ingredients="ingredients"
            />
            <!-- ========================================== -->
            <!-- КОММЕНТАРИЙ ПОВАРУ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                        <i class="fa-solid fa-fire-burner"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Пожелания к приготовлению</h6>
                        <p class="section-subtitle">Прожарка, без лука, дополнительный соус и т.д.</p>
                    </div>
                </div>

                <div class="comment-wrapper">
                    <textarea
                        v-model="kitchenComment"
                        class="comment-input"
                        placeholder="Например: котлету well done, без красного лука, соус отдельно..."
                        rows="3"
                    ></textarea>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- STICKY ПАНЕЛЬ ИТОГА -->
        <!-- ========================================== -->
        <div class="summary-panel" :class="{ 'is-invalid': !isValidOrder }">
            <div class="summary-content">
                <div class="summary-info">
                    <div class="summary-label">Итого</div>
                    <div class="summary-price">{{ formatPrice(totalPrice) }}</div>
                    <div class="summary-details">
                        {{ totalWeight }} г · {{ totalItemsCount }} ингредиентов
                    </div>
                </div>
                <button
                    type="button"
                    class="checkout-btn"
                    :disabled="!isValidOrder"
                    @click="submitOrder"
                >
                    <span>{{ isValidOrder ? 'В корзину' : 'Добавьте котлету' }}</span>
                    <i class="fa-solid" :class="isValidOrder ? 'fa-cart-plus' : 'fa-circle-exclamation'"></i>
                </button>
            </div>
        </div>

    </div>
</template>

<script>

import BurgerSummary from './BurgerSummary.vue';

export default {
    name: "BurgerBuilder",
    components:{
        BurgerSummary,
    },
    data() {
        return {
            activeCategory: 'patties',
            kitchenComment: '',
            // 3) ДЕФОЛТНЫЕ БУЛКИ: Сразу выбираем классическую нижнюю и с кунжутом верхнюю
            cart: { 101: 1, 102: 1 },

            categories: [
                { id: 'buns', name: 'Булки', icon: 'fa-solid fa-bread-slice' },
                { id: 'patties', name: 'Котлеты', icon: 'fa-solid fa-drumstick-bite' },
                { id: 'cheese', name: 'Сыры', icon: 'fa-solid fa-cheese' },
                { id: 'veggies', name: 'Овощи', icon: 'fa-solid fa-leaf' },
                { id: 'sauces', name: 'Соусы', icon: 'fa-solid fa-jar' },
                { id: 'extras', name: 'Добавки', icon: 'fa-solid fa-bacon' },
            ],

            ingredients: [
                // 2) РАСШИРЕННЫЙ ВЫБОР БУЛОК С ЦВЕТАМИ
                { id: 101, category: 'buns', name: 'Классическая бриошь (низ)', weight: 90, price: 60, emoji: '🍞', layer: 'bottom', color: '#f4d47c', crustColor: '#d4a017' },
                { id: 102, category: 'buns', name: 'Бриошь с кунжутом (верх)', weight: 50, price: 40, emoji: '🥯', layer: 'top', color: '#f9e79f', crustColor: '#fff3cd', hasSesame: true },

                { id: 103, category: 'buns', name: 'Черная булка (низ)', weight: 95, price: 80, emoji: '🖤', layer: 'bottom', color: '#2c2c2c', crustColor: '#1a1a1a' },
                { id: 104, category: 'buns', name: 'Черная булка (верх)', weight: 55, price: 50, emoji: '🖤', layer: 'top', color: '#3d3d3d', crustColor: '#2c2c2c', hasSesame: false },

                { id: 105, category: 'buns', name: 'Цельнозерновая (низ)', weight: 100, price: 70, emoji: '🌾', layer: 'bottom', color: '#8d6e63', crustColor: '#5d4037' },
                { id: 106, category: 'buns', name: 'Цельнозерновая (верх)', weight: 60, price: 50, emoji: '🌾', layer: 'top', color: '#a1887f', crustColor: '#6d4c41', hasSesame: true },

                { id: 107, category: 'buns', name: 'Чиабатта (низ)', weight: 85, price: 75, emoji: '🥖', layer: 'bottom', color: '#d7ccc8', crustColor: '#8d6e63' },
                { id: 108, category: 'buns', name: 'Чиабатта (верх)', weight: 50, price: 45, emoji: '🥖', layer: 'top', color: '#efebe9', crustColor: '#a1887f', hasSesame: false },

                // Котлеты
                { id: 201, category: 'patties', name: 'Говяжья классическая', weight: 150, price: 190, emoji: '🥩', layer: 'patty' },
                { id: 202, category: 'patties', name: 'Двойная говяжья', weight: 300, price: 350, emoji: '🥩🥩', layer: 'patty' },
                { id: 203, category: 'patties', name: 'Куриная', weight: 130, price: 160, emoji: '🍗', layer: 'patty' },
                { id: 204, category: 'patties', name: 'Растительная', weight: 140, price: 250, emoji: '🌱', layer: 'patty' },

                // Сыры
                { id: 301, category: 'cheese', name: 'Чеддер', weight: 20, price: 40, emoji: '🧀', layer: 'cheese', color: '#ffc107' },
                { id: 302, category: 'cheese', name: 'Дорблю', weight: 20, price: 60, emoji: '🧀', layer: 'cheese', color: '#e8e4d9' },
                { id: 303, category: 'cheese', name: 'Моцарелла', weight: 25, price: 50, emoji: '🧀', layer: 'cheese', color: '#ffffff' },

                // Овощи
                { id: 401, category: 'veggies', name: 'Салат Айсберг', weight: 15, price: 20, emoji: '🥬', layer: 'veggie', color: '#4caf50' },
                { id: 402, category: 'veggies', name: 'Помидор', weight: 30, price: 25, emoji: '🍅', layer: 'veggie', color: '#ff5252' },
                { id: 403, category: 'veggies', name: 'Красный лук', weight: 20, price: 15, emoji: '🧅', layer: 'veggie', color: '#e1bee7' },
                { id: 404, category: 'veggies', name: 'Маринованные огурцы', weight: 25, price: 20, emoji: '🥒', layer: 'veggie', color: '#66bb6a' },
                { id: 405, category: 'veggies', name: 'Халапеньо', weight: 15, price: 30, emoji: '🌶️', layer: 'veggie', color: '#2e7d32' },

                // Соусы
                { id: 501, category: 'sauces', name: 'Фирменный бургер-соус', weight: 30, price: 30, emoji: '🥫', layer: 'sauce', color: '#ffab91' },
                { id: 502, category: 'sauces', name: 'Барбекю', weight: 30, price: 30, emoji: '🥫', layer: 'sauce', color: '#5d4037' },
                { id: 503, category: 'sauces', name: 'Сырный', weight: 30, price: 40, emoji: '🥫', layer: 'sauce', color: '#ffd54f' },
                { id: 504, category: 'sauces', name: 'Острый чили', weight: 20, price: 25, emoji: '🥫', layer: 'sauce', color: '#d32f2f' },

                // Добавки
                { id: 601, category: 'extras', name: 'Бекон хрустящий', weight: 20, price: 70, emoji: '🥓', layer: 'extra', color: '#8d6e63' },
                { id: 602, category: 'extras', name: 'Яйцо глазунья', weight: 50, price: 50, emoji: '🍳', layer: 'extra', color: '#fff9c4' },
                { id: 603, category: 'extras', name: 'Карамелизованный лук', weight: 30, price: 40, emoji: '🧅', layer: 'extra', color: '#a1887f' },
            ]
        };
    },

    computed: {
        filteredIngredients() {
            return this.ingredients.filter(i => i.category === this.activeCategory);
        },

        // Вычисляем текущую выбранную нижнюю булку для динамических стилей
        selectedBottomBun() {
            const bottomBunId = Object.keys(this.cart).find(id => {
                const item = this.ingredients.find(i => i.id === Number(id));
                return item && item.category === 'buns' && item.layer === 'bottom';
            });
            return bottomBunId ? this.ingredients.find(i => i.id === Number(bottomBunId)) : null;
        },

        // Вычисляем текущую выбранную верхнюю булку для динамических стилей
        selectedTopBun() {
            const topBunId = Object.keys(this.cart).find(id => {
                const item = this.ingredients.find(i => i.id === Number(id));
                return item && item.category === 'buns' && item.layer === 'top';
            });
            return topBunId ? this.ingredients.find(i => i.id === Number(topBunId)) : null;
        },

        totalItemsCount() {
            return Object.values(this.cart).reduce((sum, qty) => sum + qty, 0);
        },

        totalWeight() {
            return Object.entries(this.cart).reduce((sum, [id, qty]) => {
                const item = this.ingredients.find(i => i.id === Number(id));
                return sum + (item ? item.weight * qty : 0);
            }, 0);
        },

        totalPrice() {
            return Object.entries(this.cart).reduce((sum, [id, qty]) => {
                const item = this.ingredients.find(i => i.id === Number(id));
                return sum + (item ? item.price * qty : 0);
            }, 0);
        },

        hasBottomBun() {
            return !!this.selectedBottomBun;
        },

        hasTopBun() {
            return !!this.selectedTopBun;
        },

        isValidOrder() {
            return this.hasBottomBun && this.hasTopBun && this.getCategoryQty('patties') > 0;
        }
    },

    methods: {
        getQty(id) {
            return this.cart[id] || 0;
        },

        getCategoryQty(category) {
            return Object.entries(this.cart).reduce((sum, [id, qty]) => {
                const item = this.ingredients.find(i => i.id === Number(id));
                if (item && item.category === category) {
                    return sum + qty;
                }
                return sum;
            }, 0);
        },

        // 1) ЛОГИКА ЗАМЕНЫ БУЛОК: Нельзя удалить, можно только заменить
        replaceBun(id) {
            const newItem = this.ingredients.find(i => i.id === id);
            if (!newItem) return;

            // Удаляем старую булку того же типа (верх или низ)
            Object.keys(this.cart).forEach(cartId => {
                const cartItem = this.ingredients.find(i => i.id === Number(cartId));
                if (cartItem && cartItem.category === 'buns' && cartItem.layer === newItem.layer) {
                    delete this.cart[cartId];
                }
            });

            // Добавляем новую
            this.cart[id] = 1;
        },

        updateQty(id, delta) {
            const item = this.ingredients.find(i => i.id === id);
            if (!item || item.category === 'buns') return; // Булки обрабатываются через replaceBun

            const currentQty = this.getQty(id);
            const newQty = currentQty + delta;

            if (newQty <= 0) {
                delete this.cart[id];
            } else {
                this.cart[id] = newQty;
            }
        },

        getLayersByCategory(category) {
            const layers = [];
            Object.entries(this.cart).forEach(([id, qty]) => {
                const item = this.ingredients.find(i => i.id === Number(id));
                if (item && item.category === category) {
                    for (let i = 0; i < qty; i++) {
                        layers.push(item);
                    }
                }
            });
            return layers;
        },

        submitOrder() {
            if (!this.isValidOrder) return;

            const orderData = {
                items: Object.entries(this.cart).map(([id, qty]) => ({
                    ingredientId: Number(id),
                    quantity: qty,
                    ingredient: this.ingredients.find(i => i.id === Number(id))
                })),
                comment: this.kitchenComment,
                total: this.totalPrice,
                weight: this.totalWeight
            };

            console.log('BURGER ORDER SUBMITTED:', orderData);

            this.$notify?.({
                title: 'Бургер добавлен!',
                text: 'Мы уже начали его собирать.',
                type: 'success',
            });

            // Сброс к дефолтным булкам
            this.cart = { 101: 1, 102: 1 };
            this.kitchenComment = '';
            this.activeCategory = 'patties';
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price);
        }
    }
};
</script>

<style scoped>
/* ... (Все предыдущие стили остаются без изменений, кроме добавления .replace-btn) ... */

.burger-builder-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 140px;
}

/* HERO и остальные стили остаются точно такими же, как в предыдущем ответе */
.burger-hero { position: relative; padding: 40px 24px 32px; background: linear-gradient(135deg, #000000 0%, #8b0000 50%, #ff0000 100%); color: white; text-align: center; overflow: hidden; }
.hero-background { position: absolute; inset: 0; background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.15) 0%, transparent 50%); }
.hero-content { position: relative; z-index: 1; }
.hero-icon-wrapper { position: relative; display: inline-block; margin-bottom: 16px; }
.hero-icon { font-size: 4.5rem; animation: bounce 3s ease-in-out infinite; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2)); }
.hero-sparkle { position: absolute; font-size: 1.8rem; animation: float 3s ease-in-out infinite; }
.sparkle-1 { top: -15px; right: -20px; animation-delay: 0s; }
.sparkle-2 { bottom: -15px; left: -20px; animation-delay: 1s; }
.sparkle-3 { top: 50%; right: -30px; animation-delay: 2s; }
@keyframes bounce { 0%, 100% { transform: translateY(0) rotate(-5deg); } 50% { transform: translateY(-15px) rotate(5deg); } }
@keyframes float { 0%, 100% { transform: translateY(0) scale(1); opacity: 0.8; } 50% { transform: translateY(-10px) scale(1.2); opacity: 1; } }
.hero-title { font-size: 2rem; font-weight: 800; margin-bottom: 8px; text-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.hero-subtitle { font-size: 1rem; opacity: 0.95; margin: 0; }

.builder-content { padding: 20px 16px; }
.section { margin-bottom: 28px; }
.section-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
.section-icon { width: 44px; height: 44px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.section-title { margin: 0; font-weight: 700; font-size: 1.05rem; color: var(--bs-body-color); }
.section-subtitle { margin: 4px 0 0; font-size: 0.75rem; color: var(--bs-secondary-color); line-height: 1.4; }

/* ВИЗУАЛИЗАЦИЯ */
.burger-preview-section { margin-bottom: 32px; text-align: center; }
.burger-stack { position: relative; width: 220px; height: 320px; margin: 0 auto 20px; display: flex; flex-direction: column-reverse; align-items: center; justify-content: flex-start; padding-bottom: 10px; }
.burger-layer { width: 180px; position: relative; margin-top: -6px; animation: dropIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) backwards; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; }
@keyframes dropIn { from { opacity: 0; transform: translateY(-50px) scale(0.8); } to { opacity: 1; transform: translateY(0) scale(1); } }

/* Динамические стили булок теперь используют inline CSS из data, но базовые формы остаются */
.layer-bun-bottom { height: 35px; border-radius: 0 0 25px 25px; border-bottom-width: 4px; border-bottom-style: solid; width: 190px; }
.layer-texture { position: absolute; inset: 4px; border-radius: 0 0 20px 20px; border: 2px dashed rgba(0,0,0,0.1); opacity: 0.5; }
.layer-patty { height: 28px; background: linear-gradient(to bottom, #5d4037, #3e2723); border-radius: 14px; border: 2px solid #281815; width: 195px; }
.layer-cheese { height: 14px; border-radius: 4px; width: 200px; margin-top: -8px; z-index: 2; position: relative; }
.layer-cheese::after { content: ''; position: absolute; bottom: -6px; left: 10%; width: 15px; height: 10px; background: inherit; border-radius: 0 0 10px 10px; box-shadow: 30px 0 0 inherit, 60px 2px 0 inherit, 100px -1px 0 inherit, 140px 1px 0 inherit; }
.layer-veggie { height: 18px; border-radius: 8px; width: 185px; border-top: 3px solid rgba(0,0,0,0.1); border-bottom: 3px solid rgba(0,0,0,0.1); }
.layer-sauce { height: 12px; border-radius: 10px; width: 170px; margin-top: -2px; border: 2px dashed rgba(255,255,255,0.4); }
.layer-extra { height: 16px; width: 180px; border-radius: 8px; }
.layer-bun-top { height: 55px; border-radius: 50px 50px 15px 15px; border-top-width: 4px; border-top-style: solid; width: 190px; position: relative; z-index: 10; margin-top: -8px; }
.sesame-seeds { position: absolute; top: 12px; left: 20%; width: 100%; }
.sesame-seeds::before { content: ''; position: absolute; width: 5px; height: 5px; background: #fff; border-radius: 50%; box-shadow: 25px 5px 0 #fff, 50px -2px 0 #fff, 75px 8px 0 #fff, 100px 3px 0 #fff, 125px 6px 0 #fff, 40px 15px 0 #fff, 90px 12px 0 #fff; }
.layer-emoji { font-size: 1.3rem; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3)); z-index: 5; position: relative; }
.empty-burger-hint { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--bs-secondary-color); font-size: 0.85rem; opacity: 0.7; }
.empty-burger-hint i { font-size: 1.5rem; animation: bounce 2s infinite; }

.quick-stats { display: inline-flex; align-items: center; gap: 16px; padding: 12px 20px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 14px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
.stat-item { display: flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 600; color: var(--bs-body-color); }
.stat-item i { color: #ff6b6b; font-size: 0.8rem; }
.stat-divider { width: 1px; height: 20px; background: var(--bs-border-color); }

.sticky-controls { position: sticky; top: 0; z-index: 50; background: var(--bs-body-bg); padding: 12px 0 16px; margin: -20px -16px 20px; border-bottom: 1px solid var(--bs-border-color); box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
.categories-scroll { display: flex; gap: 8px; overflow-x: auto; padding: 0 16px; scrollbar-width: none; }
.categories-scroll::-webkit-scrollbar { display: none; }
.category-tab { display: flex; align-items: center; gap: 6px; padding: 8px 16px; background: var(--bs-secondary-bg); border: 2px solid transparent; border-radius: 20px; color: var(--bs-body-color); font-weight: 600; font-size: 0.85rem; white-space: nowrap; cursor: pointer; transition: all 0.2s ease; }
.category-tab.active { background: rgba(255, 107, 107, 0.1); border-color: #ff6b6b; color: #ff6b6b; }
.category-tab i { font-size: 1rem; }

.ingredients-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
.ingredient-card { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 16px 12px; background: var(--bs-body-bg); border: 2px solid var(--bs-border-color); border-radius: 16px; cursor: pointer; transition: all 0.2s ease; text-align: center; }
.ingredient-card:hover { border-color: #ff9a9e; transform: translateY(-3px); box-shadow: 0 6px 16px rgba(255, 154, 158, 0.2); }
.ingredient-card.is-selected { border-color: #ff6b6b; background: linear-gradient(135deg, rgba(255, 107, 107, 0.05) 0%, transparent 100%); }
.ingredient-emoji { font-size: 2.2rem; line-height: 1; transition: transform 0.3s ease; }
.ingredient-card:hover .ingredient-emoji { transform: scale(1.15) rotate(-5deg); }
.ingredient-info { width: 100%; }
.ingredient-name { font-weight: 700; font-size: 0.85rem; color: var(--bs-body-color); margin-bottom: 4px; line-height: 1.2; }
.ingredient-details { display: flex; align-items: center; justify-content: space-between; font-size: 0.7rem; color: var(--bs-secondary-color); }
.ingredient-price { font-weight: 700; color: #ff6b6b; }

.ingredient-actions { width: 100%; display: flex; justify-content: center; margin-top: 4px; }

/* Новая кнопка для булок */
.replace-btn {
    width: 100%;
    padding: 8px;
    border-radius: 20px;
    border: 2px solid #ff6b6b;
    background: transparent;
    color: #ff6b6b;
    font-weight: 700;
    font-size: 0.8rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s ease;
}
.replace-btn.is-active {
    background: #ff6b6b;
    color: white;
}
.replace-btn:hover {
    transform: translateY(-2px);
}

.add-btn { width: 36px; height: 36px; border-radius: 50%; background: #ff6b6b; border: none; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3); }
.add-btn:hover { transform: scale(1.1); }
.qty-stepper { display: flex; align-items: center; gap: 8px; background: var(--bs-body-bg); border: 2px solid #ff6b6b; border-radius: 20px; padding: 4px; }
.stepper-btn { width: 28px; height: 28px; border-radius: 50%; background: #ff6b6b; border: none; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; }
.stepper-btn:hover { background: #ff5252; }
.qty-value { font-weight: 800; font-size: 0.9rem; color: var(--bs-body-color); min-width: 20px; text-align: center; }

.comment-wrapper { position: relative; }
.comment-input { width: 100%; padding: 14px 16px; background: var(--bs-body-bg); border: 2px solid var(--bs-border-color); border-radius: 14px; color: var(--bs-body-color); font-size: 0.95rem; font-family: inherit; line-height: 1.5; outline: none; resize: none; transition: all 0.2s ease; }
.comment-input:focus { border-color: #ff9a9e; box-shadow: 0 0 0 4px rgba(255, 154, 158, 0.1); }
.comment-input::placeholder { color: var(--bs-secondary-color); }

.summary-panel { position: fixed; bottom: 0; left: 0; right: 0; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); box-shadow: 0 -4px 20px rgba(0,0,0,0.08); z-index: 100; padding: 16px; transition: all 0.3s ease; }
.summary-panel.is-invalid { border-top-color: #ffc107; background: linear-gradient(to top, rgba(255, 193, 7, 0.05) 0%, var(--bs-body-bg) 100%); }
.summary-content { display: flex; align-items: center; justify-content: space-between; gap: 16px; max-width: 600px; margin: 0 auto; }
.summary-info { flex: 1; }
.summary-label { font-size: 0.75rem; color: var(--bs-secondary-color); margin-bottom: 2px; }
.summary-price { font-size: 1.6rem; font-weight: 800; color: #ff6b6b; line-height: 1; }
.summary-details { font-size: 0.75rem; color: var(--bs-secondary-color); margin-top: 4px; }
.checkout-btn { display: flex; align-items: center; gap: 10px; padding: 16px 24px; background: linear-gradient(135deg, #ff6b6b 0%, #ff9a9e 100%); border: none; border-radius: 14px; color: white; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(255, 107, 107, 0.3); }
.summary-panel.is-invalid .checkout-btn { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3); color: #1a1a1a; }
.checkout-btn:hover:not(:disabled) { transform: translateY(-2px); }
.checkout-btn:disabled { opacity: 0.8; cursor: not-allowed; transform: none; }

@media (max-width: 576px) {
    .burger-stack { width: 180px; height: 280px; }
    .burger-layer { width: 150px; }
    .layer-bun-bottom, .layer-bun-top { width: 160px; }
    .layer-patty { width: 165px; }
    .layer-cheese { width: 170px; }
    .layer-veggie { width: 155px; }
    .layer-sauce { width: 140px; }
    .layer-extra { width: 150px; }
    .hero-title { font-size: 1.6rem; }
    .ingredients-grid { grid-template-columns: repeat(2, 1fr); }
    .summary-price { font-size: 1.4rem; }
    .checkout-btn span { display: none; }
    .checkout-btn { padding: 16px; }
}
</style>
