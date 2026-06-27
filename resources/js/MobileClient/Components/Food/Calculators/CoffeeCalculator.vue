<template>
    <div class="coffee-calculator">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="coffee-hero">
            <div class="hero-background"></div>
            <div class="hero-steam">
                <span v-for="i in 5" :key="i" class="steam" :style="steamStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">☕</div>
                    <div class="hero-sparkle sparkle-1">✨</div>
                    <div class="hero-sparkle sparkle-2">🫘</div>
                    <div class="hero-sparkle sparkle-3">🥛</div>
                </div>
                <h1 class="hero-title">Собери свой кофе</h1>
                <p class="hero-subtitle">Выбери основу, молоко, сиропы и создай идеальный напиток!</p>
            </div>
        </div>

        <div class="calculator-content">

            <!-- ========================================== -->
            <!-- ВИЗУАЛИЗАЦИЯ СТАКАНЧИКА -->
            <!-- ========================================== -->
            <div class="cup-preview-section">
                <div class="cup-preview">
                    <svg viewBox="0 0 200 320" class="cup-svg">
                        <!-- Тень стаканчика -->
                        <ellipse cx="100" cy="310" rx="70" ry="8" fill="rgba(0,0,0,0.15)" />

                        <!-- Корпус стаканчика -->
                        <defs>
                            <clipPath id="cupClip">
                                <path d="M 40 40 L 160 40 L 150 300 L 50 300 Z" />
                            </clipPath>
                            <linearGradient id="cupGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                                <stop offset="50%" style="stop-color:#f8f8f8;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#e8e8e8;stop-opacity:1" />
                            </linearGradient>
                        </defs>

                        <!-- Содержимое стаканчика (обрезается по форме) -->
                        <g clip-path="url(#cupClip)">
                            <!-- Слои напитка снизу вверх -->
                            <rect
                                v-for="(layer, index) in cupLayers"
                                :key="index"
                                x="0"
                                :y="layer.y"
                                width="200"
                                :height="layer.height"
                                :fill="layer.color"
                                class="cup-layer"
                                :style="{ animationDelay: index * 0.1 + 's' }"
                            />

                            <!-- Пузырьки для газированных/пены -->
                            <circle
                                v-if="hasFoam"
                                v-for="i in 8"
                                :key="'bubble-' + i"
                                :cx="50 + Math.random() * 100"
                                :cy="foamY + Math.random() * 20"
                                :r="2 + Math.random() * 3"
                                fill="rgba(255,255,255,0.6)"
                                class="bubble"
                            />
                        </g>

                        <!-- Контур стаканчика -->
                        <path
                            d="M 40 40 L 160 40 L 150 300 L 50 300 Z"
                            fill="none"
                            stroke="rgba(0,0,0,0.2)"
                            stroke-width="2"
                        />

                        <!-- Блик на стаканчике -->
                        <path
                            d="M 55 50 L 60 280"
                            stroke="rgba(255,255,255,0.4)"
                            stroke-width="3"
                            stroke-linecap="round"
                        />

                        <!-- Крышка (если выбрано) -->
                        <g v-if="withLid">
                            <rect x="35" y="30" width="130" height="15" rx="3" fill="#8b4513" />
                            <rect x="85" y="20" width="30" height="12" rx="3" fill="#8b4513" />
                        </g>
                    </svg>

                    <!-- Название напитка -->
                    <div v-if="drinkName" class="drink-name-badge">
                        {{ drinkName }}
                    </div>
                </div>

                <!-- Быстрая статистика -->
                <div class="quick-stats">
                    <div class="stat-item">
                        <i class="fa-solid fa-mug-hot"></i>
                        <span>{{ totalVolume }} мл</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fa-solid fa-ruble-sign"></i>
                        <span>{{ formatPrice(totalPrice) }}</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fa-solid fa-bolt"></i>
                        <span>{{ caffeineAmount }} мг кофеина</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ТИП НАПИТКА (ОСНОВА) -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);">
                        <i class="fa-solid fa-mug-hot"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Тип напитка</h6>
                        <p class="section-subtitle">Выбери классический рецепт или собери свой</p>
                    </div>
                </div>

                <div class="drink-types-grid">
                    <button
                        v-for="drink in drinkTypes"
                        :key="drink.id"
                        type="button"
                        class="drink-type-card"
                        :class="{ 'selected': selectedDrink === drink.id }"
                        @click="selectDrink(drink.id)"
                    >
                        <div class="drink-type-emoji">{{ drink.emoji }}</div>
                        <div class="drink-type-name">{{ drink.name }}</div>
                        <div class="drink-type-volume">{{ drink.volume }} мл</div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- РАЗМЕР -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #8b4513 0%, #d2691e 100%);">
                        <i class="fa-solid fa-expand"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Размер стаканчика</h6>
                        <p class="section-subtitle">Выбери объём напитка</p>
                    </div>
                </div>

                <div class="size-options">
                    <button
                        v-for="size in sizes"
                        :key="size.id"
                        type="button"
                        class="size-card"
                        :class="{ 'selected': selectedSize === size.id }"
                        @click="selectSize(size.id)"
                    >
                        <div class="size-icon">
                            <div class="size-cup" :style="{ height: size.cupHeight + 'px' }"></div>
                        </div>
                        <div class="size-info">
                            <div class="size-name">{{ size.name }}</div>
                            <div class="size-volume">{{ size.volume }} мл</div>
                            <div class="size-extra" v-if="size.priceExtra > 0">
                                +{{ formatPrice(size.priceExtra) }}
                            </div>
                        </div>
                        <div class="size-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- МОЛОКО -->
            <!-- ========================================== -->
            <div class="section" v-if="needsMilk">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #f5f5dc 0%, #ddd 100%); color: #6f4e37;">
                        <i class="fa-solid fa-glass-water"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Молоко</h6>
                        <p class="section-subtitle">Выбери тип молока</p>
                    </div>
                </div>

                <div class="milk-options">
                    <button
                        v-for="milk in milkTypes"
                        :key="milk.id"
                        type="button"
                        class="milk-card"
                        :class="{ 'selected': selectedMilk === milk.id }"
                        @click="selectMilk(milk.id)"
                    >
                        <div class="milk-color" :style="{ background: milk.color }"></div>
                        <div class="milk-info">
                            <div class="milk-name">{{ milk.name }}</div>
                            <div class="milk-extra" v-if="milk.priceExtra > 0">
                                +{{ formatPrice(milk.priceExtra) }}
                            </div>
                        </div>
                        <div class="milk-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КОЛИЧЕСТВО ШОТОВ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #3e2723 0%, #5d4037 100%);">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Шоты эспрессо</h6>
                        <p class="section-subtitle">Добавь крепости</p>
                    </div>
                </div>

                <div class="shots-selector">
                    <button
                        v-for="shot in shotsOptions"
                        :key="shot.count"
                        type="button"
                        class="shot-card"
                        :class="{ 'selected': espressoShots === shot.count }"
                        @click="espressoShots = shot.count"
                    >
                        <div class="shot-cups">
                            <span v-for="i in shot.count" :key="i" class="mini-cup">☕</span>
                        </div>
                        <div class="shot-info">
                            <div class="shot-count">{{ shot.count }} {{ pluralize(shot.count, 'шот', 'шота', 'шотов') }}</div>
                            <div class="shot-extra" v-if="shot.priceExtra > 0">
                                +{{ formatPrice(shot.priceExtra) }}
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- СИРОПЫ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
                        <i class="fa-solid fa-droplet"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Сиропы</h6>
                        <p class="section-subtitle">Добавь вкус (до 3-х)</p>
                    </div>
                </div>

                <div class="syrups-grid">
                    <button
                        v-for="syrup in syrups"
                        :key="syrup.id"
                        type="button"
                        class="syrup-card"
                        :class="{
                            'selected': selectedSyrups.includes(syrup.id),
                            'disabled': selectedSyrups.length >= 3 && !selectedSyrups.includes(syrup.id)
                        }"
                        @click="toggleSyrup(syrup.id)"
                    >
                        <div class="syrup-color" :style="{ background: syrup.color }"></div>
                        <div class="syrup-info">
                            <div class="syrup-name">{{ syrup.name }}</div>
                            <div class="syrup-extra">+{{ formatPrice(syrup.price) }}</div>
                        </div>
                        <div class="syrup-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ТОППИНГИ -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Топпинги</h6>
                        <p class="section-subtitle">Дополнительные украшения</p>
                    </div>
                </div>

                <div class="toppings-grid">
                    <button
                        v-for="topping in toppings"
                        :key="topping.id"
                        type="button"
                        class="topping-card"
                        :class="{ 'selected': selectedToppings.includes(topping.id) }"
                        @click="toggleTopping(topping.id)"
                    >
                        <div class="topping-emoji">{{ topping.emoji }}</div>
                        <div class="topping-info">
                            <div class="topping-name">{{ topping.name }}</div>
                            <div class="topping-extra">+{{ formatPrice(topping.price) }}</div>
                        </div>
                        <div class="topping-check">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ДОПОЛНИТЕЛЬНО -->
            <!-- ========================================== -->
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Дополнительно</h6>
                        <p class="section-subtitle">Особые пожелания</p>
                    </div>
                </div>

                <div class="extras-list">
                    <label class="extra-toggle" :class="{ 'active': withLid }">
                        <input type="checkbox" v-model="withLid" class="extra-checkbox">
                        <div class="extra-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div class="extra-info">
                            <div class="extra-name">Крышка с собой</div>
                            <div class="extra-desc">Для напитков навынос</div>
                        </div>
                    </label>

                    <label class="extra-toggle" :class="{ 'active': isIced }">
                        <input type="checkbox" v-model="isIced" class="extra-checkbox">
                        <div class="extra-icon">
                            <i class="fa-solid fa-snowflake"></i>
                        </div>
                        <div class="extra-info">
                            <div class="extra-name">Со льдом</div>
                            <div class="extra-desc">Холодный напиток</div>
                        </div>
                    </label>
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
                        {{ totalVolume }} мл · {{ espressoShots }} {{ pluralize(espressoShots, 'шот', 'шота', 'шотов') }}
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
    name: "CoffeeCalculator",

    data() {
        return {
            // Состояние
            selectedDrink: 'latte',
            selectedSize: 'm',
            selectedMilk: 'classic',
            espressoShots: 1,
            selectedSyrups: [],
            selectedToppings: [],
            withLid: false,
            isIced: false,

            // Типы напитков
            drinkTypes: [
                { id: 'espresso', name: 'Эспрессо', emoji: '☕', volume: 30, milk: false, foam: false, espresso: 1.0, water: 0, milkPart: 0, foamPart: 0 },
                { id: 'americano', name: 'Американо', emoji: '☕', volume: 200, milk: false, foam: false, espresso: 0.3, water: 0.7, milkPart: 0, foamPart: 0 },
                { id: 'latte', name: 'Латте', emoji: '🥛', volume: 300, milk: true, foam: true, espresso: 0.2, water: 0, milkPart: 0.6, foamPart: 0.2 },
                { id: 'cappuccino', name: 'Капучино', emoji: '☕', volume: 200, milk: true, foam: true, espresso: 0.33, water: 0, milkPart: 0.33, foamPart: 0.34 },
                { id: 'flatwhite', name: 'Флэт Уайт', emoji: '🥛', volume: 180, milk: true, foam: false, espresso: 0.4, water: 0, milkPart: 0.6, foamPart: 0 },
                { id: 'mocha', name: 'Мокко', emoji: '🍫', volume: 300, milk: true, foam: true, espresso: 0.2, water: 0, milkPart: 0.5, foamPart: 0.15, chocolate: 0.15 },
                { id: 'raf', name: 'Раф', emoji: '🍦', volume: 300, milk: true, foam: true, espresso: 0.2, water: 0, milkPart: 0.6, foamPart: 0.2, cream: true },
                { id: 'custom', name: 'Свой', emoji: '✨', volume: 300, milk: true, foam: true, espresso: 0.3, water: 0, milkPart: 0.5, foamPart: 0.2 },
            ],

            // Размеры
            sizes: [
                { id: 's', name: 'Маленький', volume: 200, cupHeight: 50, priceExtra: 0 },
                { id: 'm', name: 'Средний', volume: 300, cupHeight: 65, priceExtra: 50 },
                { id: 'l', name: 'Большой', volume: 400, cupHeight: 80, priceExtra: 100 },
            ],

            // Молоко
            milkTypes: [
                { id: 'none', name: 'Без молока', color: 'transparent', priceExtra: 0 },
                { id: 'classic', name: 'Классическое', color: '#f5f5dc', priceExtra: 0 },
                { id: 'coconut', name: 'Кокосовое', color: '#fff8e7', priceExtra: 70 },
                { id: 'almond', name: 'Миндальное', color: '#f5e6d3', priceExtra: 80 },
                { id: 'oat', name: 'Овсяное', color: '#e8dcc4', priceExtra: 90 },
                { id: 'lactose_free', name: 'Безлактозное', color: '#fafafa', priceExtra: 60 },
            ],

            // Шоты
            shotsOptions: [
                { count: 1, priceExtra: 0 },
                { count: 2, priceExtra: 60 },
                { count: 3, priceExtra: 120 },
            ],

            // Сиропы
            syrups: [
                { id: 'vanilla', name: 'Ваниль', color: '#f3e5ab', price: 50 },
                { id: 'caramel', name: 'Карамель', color: '#c68e17', price: 50 },
                { id: 'chocolate', name: 'Шоколад', color: '#5c3317', price: 50 },
                { id: 'hazelnut', name: 'Орех', color: '#8b4513', price: 50 },
                { id: 'coconut_syrup', name: 'Кокос', color: '#fff8dc', price: 50 },
                { id: 'amaretto', name: 'Амаретто', color: '#cd853f', price: 60 },
                { id: 'banana', name: 'Банан', color: '#ffe135', price: 50 },
                { id: 'raspberry', name: 'Малина', color: '#e30b5c', price: 50 },
            ],

            // Топпинги
            toppings: [
                { id: 'whipped_cream', name: 'Взбитые сливки', emoji: '🍦', price: 70 },
                { id: 'cinnamon', name: 'Корица', emoji: '🌰', price: 30 },
                { id: 'marshmallow', name: 'Маршмеллоу', emoji: '🍡', price: 50 },
                { id: 'choco_crumbs', name: 'Шоколад. крошка', emoji: '🍫', price: 40 },
                { id: 'coconut_flakes', name: 'Кокос. стружка', emoji: '🥥', price: 40 },
                { id: 'cocoa', name: 'Какао', emoji: '🟫', price: 30 },
            ],
        };
    },

    computed: {
        currentDrink() {
            return this.drinkTypes.find(d => d.id === this.selectedDrink);
        },

        currentSize() {
            return this.sizes.find(s => s.id === this.selectedSize);
        },

        currentMilk() {
            return this.milkTypes.find(m => m.id === this.selectedMilk);
        },

        needsMilk() {
            return this.currentDrink?.milk !== false && this.selectedMilk !== 'none';
        },

        totalVolume() {
            return this.currentSize?.volume || 300;
        },

        hasFoam() {
            return this.currentDrink?.foam && !this.isIced;
        },

        foamY() {
            // Позиция пены сверху
            const totalHeight = 260;
            const foamPart = this.currentDrink?.foamPart || 0;
            return 40 + totalHeight * (1 - foamPart - 0.05);
        },

        drinkName() {
            if (!this.currentDrink) return '';
            const sizeName = this.currentSize?.name.toLowerCase() || '';
            return `${this.currentDrink.name} ${sizeName}`;
        },

        caffeineAmount() {
            // Примерно 63 мг на шот эспрессо
            return this.espressoShots * 63;
        },

        // Слои стаканчика
        cupLayers() {
            const layers = [];
            const totalHeight = 260;
            const startY = 40;
            const drink = this.currentDrink;

            if (!drink) return layers;

            let currentY = startY + totalHeight;

            // 1. Сиропы (внизу, тонкие слои)
            this.selectedSyrups.forEach((syrupId, index) => {
                const syrup = this.syrups.find(s => s.id === syrupId);
                if (syrup) {
                    const height = 15;
                    currentY -= height;
                    layers.push({
                        y: currentY,
                        height: height,
                        color: syrup.color,
                    });
                }
            });

            // 2. Эспрессо
            const espressoHeight = totalHeight * (drink.espresso * (this.espressoShots / (drink.espresso > 0 ? 1 : 1)));
            currentY -= espressoHeight;
            layers.push({
                y: currentY,
                height: espressoHeight,
                color: '#3e2723',
            });

            // 3. Вода (для американо)
            if (drink.water > 0) {
                const waterHeight = totalHeight * drink.water;
                currentY -= waterHeight;
                layers.push({
                    y: currentY,
                    height: waterHeight,
                    color: 'rgba(200, 220, 240, 0.6)',
                });
            }

            // 4. Шоколад (для мокко)
            if (drink.chocolate) {
                const chocoHeight = totalHeight * drink.chocolate;
                // Вставляем перед молоком
                const insertIndex = layers.length - 1;
                currentY -= chocoHeight;
                layers.splice(insertIndex, 0, {
                    y: currentY,
                    height: chocoHeight,
                    color: '#5c3317',
                });
            }

            // 5. Молоко
            if (drink.milkPart > 0 && this.selectedMilk !== 'none') {
                const milkHeight = totalHeight * drink.milkPart;
                currentY -= milkHeight;
                const milkColor = this.currentMilk?.color || '#f5f5dc';
                layers.push({
                    y: currentY,
                    height: milkHeight,
                    color: milkColor,
                });
            }

            // 6. Пена (сверху)
            if (this.hasFoam && drink.foamPart > 0) {
                const foamHeight = totalHeight * drink.foamPart;
                currentY -= foamHeight;
                layers.push({
                    y: currentY,
                    height: foamHeight,
                    color: '#fff8e7',
                });
            }

            // 7. Лёд (если холодный)
            if (this.isIced) {
                // Добавляем полупрозрачный слой сверху
                layers.push({
                    y: startY,
                    height: 30,
                    color: 'rgba(200, 230, 255, 0.4)',
                });
            }

            return layers;
        },

        // Цена
        basePrice() {
            const drinkPrices = {
                espresso: 120,
                americano: 150,
                latte: 220,
                cappuccino: 200,
                flatwhite: 230,
                mocha: 250,
                raf: 260,
                custom: 180,
            };
            return drinkPrices[this.selectedDrink] || 200;
        },

        totalPrice() {
            let price = this.basePrice;

            // Размер
            price += this.currentSize?.priceExtra || 0;

            // Молоко
            price += this.currentMilk?.priceExtra || 0;

            // Шоты
            const shotOption = this.shotsOptions.find(s => s.count === this.espressoShots);
            price += shotOption?.priceExtra || 0;

            // Сиропы
            this.selectedSyrups.forEach(id => {
                const syrup = this.syrups.find(s => s.id === id);
                price += syrup?.price || 0;
            });

            // Топпинги
            this.selectedToppings.forEach(id => {
                const topping = this.toppings.find(t => t.id === id);
                price += topping?.price || 0;
            });

            return price;
        },

        canAddToCart() {
            return this.selectedDrink && this.selectedSize;
        },
    },

    watch: {
        selectedDrink(newDrink) {
            const drink = this.drinkTypes.find(d => d.id === newDrink);
            if (drink && !drink.milk) {
                this.selectedMilk = 'none';
            } else if (drink?.milk && this.selectedMilk === 'none') {
                this.selectedMilk = 'classic';
            }
        },
    },

    methods: {
        steamStyle(i) {
            const left = 30 + i * 10;
            const delay = i * 0.5;
            const duration = 3 + Math.random() * 2;
            return {
                left: `${left}%`,
                animationDelay: `${delay}s`,
                animationDuration: `${duration}s`,
            };
        },

        selectDrink(id) {
            this.selectedDrink = id;
        },

        selectSize(id) {
            this.selectedSize = id;
        },

        selectMilk(id) {
            this.selectedMilk = id;
        },

        toggleSyrup(id) {
            const index = this.selectedSyrups.indexOf(id);
            if (index !== -1) {
                this.selectedSyrups.splice(index, 1);
            } else {
                if (this.selectedSyrups.length >= 3) {
                    this.$notify?.({
                        title: 'Сиропы',
                        text: 'Максимум 3 сиропа',
                        type: 'warning',
                    });
                    return;
                }
                this.selectedSyrups.push(id);
            }
        },

        toggleTopping(id) {
            const index = this.selectedToppings.indexOf(id);
            if (index !== -1) {
                this.selectedToppings.splice(index, 1);
            } else {
                this.selectedToppings.push(id);
            }
        },

        clearCalc() {
            this.selectedDrink = 'latte';
            this.selectedSize = 'm';
            this.selectedMilk = 'classic';
            this.espressoShots = 1;
            this.selectedSyrups = [];
            this.selectedToppings = [];
            this.withLid = false;
            this.isIced = false;

            this.$notify?.({
                title: 'Калькулятор',
                text: 'Калькулятор очищен',
                type: 'info',
            });
        },

        addToCart() {
            if (!this.canAddToCart) return;

            const order = {
                drink: this.currentDrink,
                size: this.currentSize,
                milk: this.currentMilk,
                shots: this.espressoShots,
                syrups: this.selectedSyrups,
                toppings: this.selectedToppings,
                withLid: this.withLid,
                isIced: this.isIced,
                price: this.totalPrice,
                volume: this.totalVolume,
            };

            console.log('COFFEE ADD TO CART', order);

            this.$notify?.({
                title: 'Корзина',
                text: `${this.drinkName} добавлен в корзину`,
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
.coffee-calculator {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 120px;
    overflow-x: hidden; /* ← Убираем горизонтальный скролл */
    width: 100%;
    max-width: 100vw;
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.coffee-hero {
    position: relative;
    padding: 40px 20px 32px;
    background: linear-gradient(135deg, #3e2723 0%, #5d4037 50%, #8d6e63 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 215, 0, 0.05) 0%, transparent 50%);
}

/* Пар от кофе */
.hero-steam {
    position: absolute;
    bottom: 50%;
    left: 0;
    right: 0;
    height: 100px;
    pointer-events: none;
    overflow: hidden; /* ← Пар не вылезает */
}

.steam {
    position: absolute;
    bottom: 0;
    width: 8px;
    height: 40px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    filter: blur(6px);
    animation: steamRise linear infinite;
}

@keyframes steamRise {
    0% {
        transform: translateY(0) scaleX(1);
        opacity: 0;
    }
    20% {
        opacity: 0.6;
    }
    100% {
        transform: translateY(-150px) scaleX(2);
        opacity: 0;
    }
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
    font-size: 3.5rem;
    animation: iconBounce 3s ease-in-out infinite;
}

@keyframes iconBounce {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(5deg); }
}

.hero-sparkle {
    position: absolute;
    font-size: 1.3rem;
    animation: sparkleFloat 3s ease-in-out infinite;
}

.sparkle-1 { top: -5px; right: -10px; animation-delay: 0s; }
.sparkle-2 { bottom: -5px; left: -10px; animation-delay: 1s; }
.sparkle-3 { top: 50%; right: -18px; animation-delay: 2s; }

@keyframes sparkleFloat {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.8; }
    50% { transform: translateY(-10px) scale(1.2); opacity: 1; }
}

.hero-title {
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0;
    padding: 0 8px; /* ← Отступ по бокам для длинных текстов */
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.calculator-content {
    padding: 16px;
    max-width: 100%;
    box-sizing: border-box;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.section {
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ВИЗУАЛИЗАЦИЯ СТАКАНЧИКА
   ========================================== */
.cup-preview-section {
    margin-bottom: 28px;
    text-align: center;
}

.cup-preview {
    position: relative;
    width: 180px;
    height: 290px;
    margin: 0 auto 16px;
}

.cup-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.15));
}

.cup-layer {
    animation: layerFill 0.6s cubic-bezier(0.4, 0, 0.2, 1) backwards;
}

@keyframes layerFill {
    from {
        transform: scaleY(0);
        transform-origin: bottom;
        opacity: 0;
    }
    to {
        transform: scaleY(1);
        transform-origin: bottom;
        opacity: 1;
    }
}

.bubble {
    animation: bubbleFloat 2s ease-in-out infinite;
}

@keyframes bubbleFloat {
    0%, 100% { transform: translateY(0); opacity: 0.6; }
    50% { transform: translateY(-5px); opacity: 0.9; }
}

.drink-name-badge {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 14px;
    background: linear-gradient(135deg, #3e2723 0%, #5d4037 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    max-width: 90%;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Быстрая статистика */
.quick-stats {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    max-width: 100%;
    flex-wrap: wrap; /* ← Перенос на узких экранах */
    justify-content: center;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-body-color);
    white-space: nowrap;
}

.stat-item i {
    color: #8d6e63;
    font-size: 0.75rem;
}

.stat-divider {
    width: 1px;
    height: 18px;
    background: var(--bs-border-color);
    flex-shrink: 0;
}

/* ==========================================
   ТИПЫ НАПИТКОВ
   ========================================== */
.drink-types-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.drink-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 10px 4px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    min-width: 0; /* ← Важно для grid */
    overflow: hidden;
}

.drink-type-card:hover {
    border-color: #8d6e63;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(141, 110, 99, 0.15);
}

.drink-type-card.selected {
    border-color: #5d4037;
    background: linear-gradient(135deg, rgba(93, 64, 55, 0.08) 0%, rgba(141, 110, 99, 0.04) 100%);
    box-shadow: 0 4px 16px rgba(93, 64, 55, 0.2);
}

.drink-type-emoji {
    font-size: 1.5rem;
    line-height: 1;
}

.drink-type-name {
    font-weight: 700;
    font-size: 0.7rem;
    color: var(--bs-body-color);
    line-height: 1.2;
    word-break: break-word;
}

.drink-type-volume {
    font-size: 0.6rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   РАЗМЕРЫ
   ========================================== */
.size-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.size-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 10px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    position: relative;
    min-width: 0;
}

.size-card:hover {
    border-color: #8d6e63;
}

.size-card.selected {
    border-color: #5d4037;
    background: linear-gradient(135deg, rgba(93, 64, 55, 0.08) 0%, rgba(141, 110, 99, 0.04) 100%);
}

.size-icon {
    width: 24px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    height: 50px;
    flex-shrink: 0;
}

.size-cup {
    width: 16px;
    background: linear-gradient(180deg, #8d6e63 0%, #5d4037 100%);
    border-radius: 2px 2px 4px 4px;
    transition: height 0.3s ease;
}

.size-info {
    flex: 1;
    min-width: 0;
}

.size-name {
    font-weight: 700;
    font-size: 0.8rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.size-volume {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
}

.size-extra {
    font-size: 0.65rem;
    color: #8d6e63;
    font-weight: 600;
    margin-top: 2px;
}

.size-check {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #5d4037;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.size-card.selected .size-check {
    opacity: 1;
    transform: scale(1);
}

/* ==========================================
   МОЛОКО
   ========================================== */
.milk-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.milk-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    min-width: 0;
}

.milk-card:hover {
    border-color: #8d6e63;
}

.milk-card.selected {
    border-color: #5d4037;
    background: linear-gradient(135deg, rgba(93, 64, 55, 0.05) 0%, transparent 100%);
}

.milk-color {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.milk-info {
    flex: 1;
    min-width: 0;
}

.milk-name {
    font-weight: 600;
    font-size: 0.75rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
    word-break: break-word;
}

.milk-extra {
    font-size: 0.7rem;
    color: #8d6e63;
    font-weight: 600;
}

.milk-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #5d4037;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.55rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.milk-card.selected .milk-check {
    opacity: 1;
    transform: scale(1);
}

/* ==========================================
   ШОТЫ
   ========================================== */
.shots-selector {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.shot-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 12px 8px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 0;
}

.shot-card:hover {
    border-color: #8d6e63;
}

.shot-card.selected {
    border-color: #5d4037;
    background: linear-gradient(135deg, rgba(62, 39, 35, 0.1) 0%, rgba(93, 64, 55, 0.05) 100%);
}

.shot-cups {
    display: flex;
    gap: 2px;
    font-size: 1.1rem;
}

.mini-cup {
    animation: cupBounce 0.5s ease backwards;
}

.shot-card.selected .mini-cup:nth-child(1) { animation-delay: 0s; }
.shot-card.selected .mini-cup:nth-child(2) { animation-delay: 0.1s; }
.shot-card.selected .mini-cup:nth-child(3) { animation-delay: 0.2s; }

@keyframes cupBounce {
    0% { transform: scale(0) rotate(-180deg); }
    70% { transform: scale(1.2) rotate(10deg); }
    100% { transform: scale(1) rotate(0deg); }
}

.shot-info {
    text-align: center;
}

.shot-count {
    font-weight: 700;
    font-size: 0.8rem;
    color: var(--bs-body-color);
}

.shot-extra {
    font-size: 0.7rem;
    color: #8d6e63;
    font-weight: 600;
    margin-top: 2px;
}

/* ==========================================
   СИРОПЫ И ТОППИНГИ
   ========================================== */
.syrups-grid,
.toppings-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.syrup-card,
.topping-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    min-width: 0;
}

.syrup-card:hover:not(.disabled),
.topping-card:hover {
    border-color: #8d6e63;
    transform: translateY(-2px);
}

.syrup-card.selected,
.topping-card.selected {
    border-color: #5d4037;
    background: linear-gradient(135deg, rgba(93, 64, 55, 0.05) 0%, transparent 100%);
}

.syrup-card.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.syrup-color {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: inset 0 -2px 4px rgba(0, 0, 0, 0.2);
}

.topping-emoji {
    font-size: 1.3rem;
    flex-shrink: 0;
}

.syrup-info,
.topping-info {
    flex: 1;
    min-width: 0;
}

.syrup-name,
.topping-name {
    font-weight: 600;
    font-size: 0.75rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
    word-break: break-word;
}

.syrup-extra,
.topping-extra {
    font-size: 0.7rem;
    color: #8d6e63;
    font-weight: 600;
}

.syrup-check,
.topping-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #5d4037;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.55rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.syrup-card.selected .syrup-check,
.topping-card.selected .topping-check {
    opacity: 1;
    transform: scale(1);
}

/* ==========================================
   ДОПОЛНИТЕЛЬНО
   ========================================== */
.extras-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.extra-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.extra-toggle:hover {
    border-color: #8d6e63;
}

.extra-toggle.active {
    border-color: #5d4037;
    background: linear-gradient(135deg, rgba(93, 64, 55, 0.05) 0%, transparent 100%);
}

.extra-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.extra-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(141, 110, 99, 0.1);
    color: #5d4037;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.extra-toggle.active .extra-icon {
    background: linear-gradient(135deg, #5d4037 0%, #8d6e63 100%);
    color: white;
}

.extra-info {
    flex: 1;
    min-width: 0;
}

.extra-name {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.extra-desc {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
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
    padding: 12px 16px;
    max-width: 100vw;
    box-sizing: border-box;
}

.summary-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    max-width: 600px;
    margin: 0 auto;
}

.summary-info {
    flex: 1;
    min-width: 0;
}

.summary-label {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.summary-price {
    font-size: 1.3rem;
    font-weight: 800;
    color: #5d4037;
    line-height: 1;
}

.summary-details {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.summary-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.clear-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.clear-btn:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
}

.add-to-cart-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    background: linear-gradient(135deg, #5d4037 0%, #8d6e63 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(93, 64, 55, 0.3);
    white-space: nowrap;
}

.add-to-cart-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(93, 64, 55, 0.4);
}

.add-to-cart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ==========================================
   АДАПТИВ: ПЛАНШЕТЫ И БОЛЬШИЕ ТЕЛЕФОНЫ
   ========================================== */
@media (max-width: 576px) {
    .coffee-hero {
        padding: 36px 16px 28px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .hero-subtitle {
        font-size: 0.9rem;
    }

    .calculator-content {
        padding: 14px 12px;
    }

    .cup-preview {
        width: 160px;
        height: 260px;
    }

    .quick-stats {
        gap: 10px;
        padding: 8px 14px;
    }

    .stat-item {
        font-size: 0.75rem;
    }

    .summary-price {
        font-size: 1.2rem;
    }

    .add-to-cart-btn {
        padding: 12px 16px;
        font-size: 0.85rem;
    }
}

/* ==========================================
   АДАПТИВ: УЗКИЕ ТЕЛЕФОНЫ (iPhone SE, etc.)
   ========================================== */
@media (max-width: 400px) {
    .coffee-hero {
        padding: 32px 12px 24px;
    }

    .hero-icon {
        font-size: 3rem;
    }

    .hero-title {
        font-size: 1.35rem;
    }

    .hero-subtitle {
        font-size: 0.85rem;
    }

    .hero-sparkle {
        font-size: 1.1rem;
    }

    .calculator-content {
        padding: 12px 10px;
    }

    .section-header {
        gap: 10px;
        margin-bottom: 12px;
    }

    .section-icon {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }

    .section-title {
        font-size: 0.9rem;
    }

    .section-subtitle {
        font-size: 0.7rem;
    }

    .cup-preview {
        width: 140px;
        height: 230px;
    }

    .drink-name-badge {
        font-size: 0.7rem;
        padding: 4px 12px;
    }

    .quick-stats {
        gap: 8px;
        padding: 8px 12px;
        flex-direction: column; /* ← Вертикально на очень узких */
    }

    .stat-divider {
        width: 40px;
        height: 1px;
    }

    .drink-types-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }

    .drink-type-card {
        padding: 8px 2px;
        gap: 2px;
    }

    .drink-type-emoji {
        font-size: 1.3rem;
    }

    .drink-type-name {
        font-size: 0.65rem;
    }

    .drink-type-volume {
        font-size: 0.55rem;
    }

    .size-options,
    .shots-selector {
        gap: 6px;
    }

    .size-card {
        padding: 10px 8px;
        gap: 6px;
    }

    .size-name {
        font-size: 0.75rem;
    }

    .size-volume,
    .size-extra {
        font-size: 0.65rem;
    }

    .milk-options,
    .syrups-grid,
    .toppings-grid {
        gap: 6px;
    }

    .milk-card,
    .syrup-card,
    .topping-card {
        padding: 8px;
        gap: 6px;
    }

    .milk-color {
        width: 24px;
        height: 24px;
    }

    .milk-name,
    .syrup-name,
    .topping-name {
        font-size: 0.7rem;
    }

    .shot-card {
        padding: 10px 6px;
    }

    .shot-cups {
        font-size: 1rem;
    }

    .shot-count {
        font-size: 0.75rem;
    }

    .extra-toggle {
        padding: 10px 12px;
        gap: 10px;
    }

    .extra-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
    }

    .extra-name {
        font-size: 0.8rem;
    }

    .extra-desc {
        font-size: 0.65rem;
    }

    /* Sticky панель */
    .summary-panel {
        padding: 10px 12px;
    }

    .summary-content {
        gap: 8px;
    }

    .summary-label {
        font-size: 0.65rem;
    }

    .summary-price {
        font-size: 1.1rem;
    }

    .summary-details {
        font-size: 0.65rem;
    }

    .clear-btn {
        width: 40px;
        height: 40px;
        font-size: 0.85rem;
    }

    .add-to-cart-btn {
        padding: 10px 14px;
        font-size: 0.8rem;
        gap: 6px;
    }

    .add-to-cart-btn span {
        display: none; /* ← Скрываем текст на очень узких */
    }
}

/* ==========================================
   АДАПТИВ: ОЧЕНЬ УЗКИЕ ЭКРАНЫ (< 360px)
   ========================================== */
@media (max-width: 359px) {
    .drink-types-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .size-options {
        grid-template-columns: repeat(3, 1fr);
    }

    .milk-options,
    .syrups-grid,
    .toppings-grid {
        grid-template-columns: 1fr;
    }

    .hero-title {
        font-size: 1.2rem;
    }

    .cup-preview {
        width: 120px;
        height: 200px;
    }
}

/* ==========================================
   БЕЗОПАСНЫЕ ЗОНЫ (safe-area для iPhone с чёлкой)
   ========================================== */
@supports (padding: max(0px)) {
    .summary-panel {
        padding-bottom: max(12px, env(safe-area-inset-bottom));
    }
}
</style>
