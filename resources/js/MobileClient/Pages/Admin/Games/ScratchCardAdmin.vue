<template>
    <div class="scratch-admin-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-credit-card me-2" style="color: #f5576c;"></i> Скретч-карта
                    </h2>
                    <p class="page-subtitle">Настройка призов, шансов и механики стирания</p>
                </div>
            </div>

            <!-- 🆕 ОБЩИЕ НАСТРОЙКИ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-sliders"></i> Общие настройки</h5>
                <div class="row g-3">
                    <div class="col-md-3 d-flex align-items-end">
                        <label class="modern-switch mb-0">
                            <input type="checkbox" v-model="form.can_play">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Активность игры</span>
                        </label>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-modern">Стоимость хода (бонусов)</label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.move_cost" min="1" max="1000">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-modern">Попыток за период</label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.attempts_per_period" min="1" max="50">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-modern">Период обновления</label>
                        <select class="form-input-modern form-input-sm" v-model.number="form.interval">
                            <option :value="1">Каждый день</option>
                            <option :value="7">Раз в неделю</option>
                            <option :value="30">Раз в месяц</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">
                            Порог стирания
                            <span class="unit-hint">{{ form.reveal_threshold }}%</span>
                        </label>
                        <input type="range" class="form-range-modern" v-model.number="form.reveal_threshold" min="30" max="90" step="5">
                        <div class="range-hint">При {{ form.reveal_threshold }}% стирания приз откроется автоматически</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">
                            Размер кисти
                            <span class="unit-hint">{{ form.brush_size }}px</span>
                        </label>
                        <input type="range" class="form-range-modern" v-model.number="form.brush_size" min="15" max="60" step="5">
                        <div class="range-hint">Ширина области стирания за одно движение</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Текст на защитном слое</label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.scratch_text" maxlength="50" placeholder="✦ СОТРИ ЗДЕСЬ ✦">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Заголовок <span class="char-counter">{{ (form.title || '').length }}/100</span></label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.title" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Подзаголовок <span class="char-counter">{{ (form.subtitle || '').length }}/150</span></label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.subtitle" maxlength="150">
                    </div>
                    <div class="col-12">
                        <label class="form-label-modern">Правила игры <span class="char-counter">{{ (form.rules || '').length }}/4000</span></label>
                        <textarea class="form-input-modern form-input-sm" v-model="form.rules" rows="3" maxlength="4000"></textarea>
                    </div>
                </div>
            </div>

            <!-- 🆕 ЦВЕТА ТИПОВ ПРИЗОВ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-palette"></i> Цвета типов призов</h5>
                <p class="text-muted small mb-3">Настройте цвета для визуального отображения разных типов наград</p>

                <div class="row g-3">
                    <div class="col-md-4 col-6" v-for="type in prizeTypes" :key="type.key">
                        <label class="form-label-modern">
                            <span class="type-label-dot" :style="{ background: form.type_colors[type.key] }"></span>
                            {{ type.label }}
                        </label>
                        <div class="color-input-wrapper">
                            <input type="color" v-model="form.type_colors[type.key]" class="color-picker-sm">
                            <span class="color-hex">{{ form.type_colors[type.key] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 ШАНСЫ ПО РЕДКОСТЯМ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-chart-pie"></i> Шансы выпадения по редкостям</h5>
                <p class="text-muted small mb-3">Укажите процент вероятности. Сумма должна быть равна 100%.</p>

                <div class="rarity-chances">
                    <div v-for="r in rarities" :key="r.key" class="rarity-chance-item" :class="`rarity-${r.key}`">
                        <div class="rarity-label">
                            <span class="rarity-dot"></span>
                            <span>{{ r.label }}</span>
                        </div>
                        <div class="rarity-input-wrapper">
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.rarity_chances[r.key]" min="0" max="100" step="0.1">
                            <span class="percent-sign">%</span>
                        </div>
                    </div>
                </div>

                <div class="chances-total mt-3" :class="{ 'invalid': totalChances !== 100 }">
                    <span class="total-label">Сумма шансов:</span>
                    <span class="total-value">{{ totalChances.toFixed(1) }}%</span>
                    <i v-if="totalChances !== 100" class="fa-solid fa-triangle-exclamation text-danger ms-2"></i>
                    <i v-else class="fa-solid fa-circle-check text-success ms-2"></i>
                </div>
            </div>

            <!-- 🆕 УПРАВЛЕНИЕ ПРИЗАМИ -->
            <div class="modern-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-gift"></i> Призы</h5>
                    <span class="badge-sector">{{ form.prizes.length }} призов</span>
                </div>
                <p class="text-muted small mb-3">Настройте призы для каждого типа. Чем больше призов одной редкости — тем равномернее распределение.</p>

                <div class="prizes-list">
                    <div
                        v-for="(prize, index) in form.prizes"
                        :key="prize.id"
                        class="prize-card"
                        :class="`rarity-${prize.rarity}`"
                    >
                        <div class="prize-header" @click="prize.edit = !prize.edit">
                            <div class="prize-preview">
                                <div class="prize-icon-preview" :class="`type-${prize.type}`" :style="{ background: getTypeGradient(prize.type) }">
                                    <i :class="prize.icon"></i>
                                </div>
                            </div>
                            <div class="prize-info">
                                <div class="prize-name">{{ prize.title || 'Без названия' }}</div>
                                <div class="prize-meta">
                                    <span class="prize-type-tag" :style="{ background: form.type_colors[prize.type] + '20', color: form.type_colors[prize.type] }">
                                        {{ typeLabel(prize.type) }}
                                    </span>
                                    <span class="prize-rarity-tag" :class="`rarity-${prize.rarity}`">
                                        {{ rarityLabel(prize.rarity) }}
                                    </span>
                                    <span class="prize-value-tag">{{ formatPrizeValue(prize) }}</span>
                                </div>
                            </div>
                            <div class="prize-actions">
                                <button class="btn-icon btn-sm" :class="prize.edit ? 'btn-success' : 'btn-secondary'" @click.stop="prize.edit = !prize.edit">
                                    <i class="fa-solid" :class="prize.edit ? 'fa-check' : 'fa-pen'"></i>
                                </button>
                                <button class="btn-icon btn-sm btn-danger" @click.stop="removePrize(index)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div v-if="prize.edit" class="prize-edit-mode fade-in">
                            <div class="row g-3">
                                <!-- ТИП ПРИЗА -->
                                <div class="col-12">
                                    <label class="form-label-modern">Тип приза</label>
                                    <div class="type-selector">
                                        <button
                                            v-for="t in prizeTypes"
                                            :key="t.key"
                                            type="button"
                                            class="type-option"
                                            :class="{ 'active': prize.type === t.key }"
                                            @click="changePrizeType(prize, t.key)"
                                        >
                                            <i :class="t.icon"></i>
                                            <span>{{ t.label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- ИКОНКА -->
                                <div class="col-12 col-md-4">
                                    <label class="form-label-modern">Иконка</label>
                                    <div class="icon-input-wrapper">
                                        <input type="text" v-model="prize.icon" class="form-input-modern form-input-sm text-center" placeholder="fa-solid fa-gift">
                                        <div class="icon-picker-dropdown">
                                            <button
                                                class="icon-btn"
                                                v-for="icon in icons"
                                                :key="icon"
                                                @click="prize.icon = icon"
                                                :class="{ 'is-selected': prize.icon === icon }"
                                            >
                                                <i :class="icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- РЕДКОСТЬ -->
                                <div class="col-12 col-md-4">
                                    <label class="form-label-modern">Редкость</label>
                                    <select v-model="prize.rarity" class="form-input-modern form-input-sm">
                                        <option v-for="r in rarities" :key="r.key" :value="r.key">{{ r.label }}</option>
                                    </select>
                                </div>

                                <!-- НАЗВАНИЕ -->
                                <div class="col-12 col-md-4">
                                    <label class="form-label-modern">Название приза</label>
                                    <input type="text" v-model="prize.title" class="form-input-modern form-input-sm" placeholder="Например: 50 бонусов">
                                </div>

                                <!-- ОПИСАНИЕ -->
                                <div class="col-12">
                                    <label class="form-label-modern">Описание</label>
                                    <input type="text" v-model="prize.description" class="form-input-modern form-input-sm" placeholder="Краткое описание приза">
                                </div>

                                <!-- === ПОЛЯ ПО ТИПАМ === -->

                                <!-- BONUS -->
                                <template v-if="prize.type === 'bonus'">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label-modern">
                                            Количество бонусов
                                            <span class="unit-hint">начисляется сразу</span>
                                        </label>
                                        <input type="number" v-model.number="prize.value" class="form-input-modern form-input-sm" min="1" placeholder="50">
                                    </div>
                                </template>

                                <!-- PRODUCT -->
                                <template v-if="prize.type === 'product'">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label-modern">Название товара</label>
                                        <input type="text" v-model="prize.productName" class="form-input-modern form-input-sm" placeholder="Пицца Маргарита">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label-modern">ID товара <span class="unit-hint">(из каталога)</span></label>
                                        <input type="number" v-model.number="prize.productId" class="form-input-modern form-input-sm" min="1" placeholder="102">
                                    </div>
                                </template>

                                <!-- PRODUCT DISCOUNT -->
                                <template v-if="prize.type === 'product_discount'">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label-modern">Скидка (%)</label>
                                        <input type="number" v-model.number="prize.value" class="form-input-modern form-input-sm" min="1" max="100" placeholder="15">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label-modern">Название товара</label>
                                        <input type="text" v-model="prize.productName" class="form-input-modern form-input-sm" placeholder="пиццу">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label-modern">ID товара</label>
                                        <input type="number" v-model.number="prize.productId" class="form-input-modern form-input-sm" min="1" placeholder="101">
                                    </div>
                                </template>

                                <!-- DELIVERY DISCOUNT -->
                                <template v-if="prize.type === 'delivery_discount'">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label-modern">Значение</label>
                                        <input type="number" v-model.number="prize.value" class="form-input-modern form-input-sm" min="1" placeholder="50">
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label class="form-label-modern">Формат скидки</label>
                                        <div class="format-selector">
                                            <button
                                                type="button"
                                                class="format-option"
                                                :class="{ 'active': prize.isPercent === false }"
                                                @click="prize.isPercent = false"
                                            >
                                                <i class="fa-solid fa-ruble-sign"></i>
                                                <span>Фиксированная (₽)</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="format-option"
                                                :class="{ 'active': prize.isPercent === true }"
                                                @click="prize.isPercent = true"
                                            >
                                                <i class="fa-solid fa-percent"></i>
                                                <span>Процент (%)</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="format-option format-free"
                                                :class="{ 'active': prize.isFreeDelivery }"
                                                @click="prize.isFreeDelivery = !prize.isFreeDelivery"
                                            >
                                                <i class="fa-solid fa-truck-fast"></i>
                                                <span>Бесплатная доставка</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- ORDER DISCOUNT -->
                                <template v-if="prize.type === 'order_discount'">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label-modern">Скидка (%)</label>
                                        <input type="number" v-model.number="prize.value" class="form-input-modern form-input-sm" min="1" max="100" placeholder="20">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label-modern">
                                            Мин. сумма заказа (₽)
                                            <span class="unit-hint">для активации</span>
                                        </label>
                                        <input type="number" v-model.number="prize.minOrderAmount" class="form-input-modern form-input-sm" min="0" placeholder="1500">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="add-prize-block mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="text-muted small">
                            <i class="fa-solid fa-circle-info"></i> Добавьте призы для каждой редкости
                        </div>
                        <button class="btn-modern btn-primary" @click="addPrize" style="max-width: 300px; width: 100%;">
                            <i class="fa-solid fa-plus-circle"></i> Добавить приз
                        </button>
                    </div>
                </div>
            </div>

            <!-- 🆕 ПРЕДПРОСМОТР ПРИЗОВ -->
            <div class="modern-card">
                <h5 class="card-title"><i class="fa-solid fa-eye"></i> Предпросмотр всех призов</h5>
                <p class="text-muted small mb-3">Так пользователи увидят возможные призы в модалке</p>

                <div class="preview-prizes-list">
                    <div
                        v-for="prize in sortedPrizes"
                        :key="prize.id"
                        class="preview-prize-item"
                        :class="`rarity-${prize.rarity}`"
                    >
                        <div class="preview-prize-icon" :style="{ background: getTypeGradient(prize.type) }">
                            <i :class="prize.icon"></i>
                        </div>
                        <div class="preview-prize-info">
                            <div class="preview-prize-title">
                                {{ prize.title }}
                                <span class="preview-rarity-tag" :class="`rarity-${prize.rarity}`">
                                    {{ rarityLabel(prize.rarity) }}
                                </span>
                            </div>
                            <div class="preview-prize-desc">{{ prize.description }}</div>
                        </div>
                        <div class="preview-prize-value">
                            {{ formatPrizeValue(prize) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 КНОПКА СОХРАНЕНИЯ -->
            <div class="save-actions-bar">
                <button
                    class="btn-modern btn-primary btn-lg"
                    @click="saveSettings"
                    :disabled="isSaving || totalChances !== 100"
                >
                    <i v-if="isSaving" class="fa-solid fa-circle-notch fa-spin me-2"></i>
                    <i v-else class="fa-solid fa-floppy-disk me-2"></i>
                    {{ isSaving ? 'Сохранение...' : 'Сохранить настройки скретч-карты' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ScratchCardAdminSettings",
    props: { modelValue: { type: Object, default: () => ({}) } },
    emits: ['update:modelValue'],

    data() {
        return {
            isSaving: false,
            icons: [
                'fa-solid fa-coins', 'fa-solid fa-gem', 'fa-solid fa-percent', 'fa-solid fa-gift', 'fa-solid fa-crown',
                'fa-solid fa-trophy', 'fa-solid fa-star', 'fa-solid fa-pizza-slice', 'fa-solid fa-mug-hot', 'fa-solid fa-burger',
                'fa-solid fa-truck', 'fa-solid fa-truck-fast', 'fa-solid fa-receipt', 'fa-solid fa-box', 'fa-solid fa-bag-shopping',
                'fa-solid fa-ice-cream', 'fa-solid fa-cookie', 'fa-solid fa-cake-candles', 'fa-solid fa-wine-glass', 'fa-solid fa-bowl-food',
                'fa-solid fa-hand-holding-heart', 'fa-solid fa-fire', 'fa-solid fa-bolt', 'fa-solid fa-heart', 'fa-solid fa-shield'
            ],
            prizeTypes: [
                { key: 'bonus', label: 'Бонусы', icon: 'fa-solid fa-coins' },
                { key: 'product', label: 'Товар', icon: 'fa-solid fa-box' },
                { key: 'product_discount', label: 'Скидка на товар', icon: 'fa-solid fa-percent' },
                { key: 'delivery_discount', label: 'Скидка на доставку', icon: 'fa-solid fa-truck' },
                { key: 'order_discount', label: 'Скидка на заказ', icon: 'fa-solid fa-receipt' }
            ],
            rarities: [
                { key: 'common', label: 'Обычный' },
                { key: 'rare', label: 'Редкий' },
                { key: 'epic', label: 'Эпический' },
                { key: 'legendary', label: 'Легендарный' }
            ],
            form: {
                can_play: true,
                move_cost: 30,
                attempts_per_period: 1,
                interval: 1,
                reveal_threshold: 55,
                brush_size: 30,
                scratch_text: '✦ СОТРИ ЗДЕСЬ ✦',
                title: 'Скретч-карта',
                subtitle: 'Сотри защитный слой и узнай свой приз!',
                rules: '',
                type_colors: {
                    bonus: '#ffd700',
                    product: '#28a745',
                    product_discount: '#dc3545',
                    delivery_discount: '#fd7e14',
                    order_discount: '#6f42c1'
                },
                rarity_chances: {
                    common: 60,
                    rare: 25,
                    epic: 12,
                    legendary: 3
                },
                prizes: [
                    { id: 1, type: 'bonus', title: '10 бонусов', description: 'Небольшой бонус к вашему балансу', icon: 'fa-solid fa-coins', value: 10, rarity: 'common', edit: false },
                    { id: 2, type: 'bonus', title: '25 бонусов', description: 'Приятное пополнение баланса', icon: 'fa-solid fa-coins', value: 25, rarity: 'common', edit: false },
                    { id: 3, type: 'delivery_discount', title: 'Скидка 50₽ на доставку', description: 'Скидка на следующую доставку', icon: 'fa-solid fa-truck', value: 50, isPercent: false, rarity: 'common', edit: false },
                    { id: 4, type: 'bonus', title: '75 бонусов', description: 'Хороший бонус на ваш счёт', icon: 'fa-solid fa-gem', value: 75, rarity: 'rare', edit: false },
                    { id: 5, type: 'product_discount', title: 'Скидка 15% на пиццу', description: 'Скидка на любую пиццу', icon: 'fa-solid fa-percent', value: 15, productId: 101, productName: 'пиццу', rarity: 'rare', edit: false },
                    { id: 6, type: 'delivery_discount', title: 'Бесплатная доставка', description: 'Следующая доставка за наш счёт', icon: 'fa-solid fa-truck-fast', value: 100, isPercent: true, isFreeDelivery: true, rarity: 'rare', edit: false },
                    { id: 7, type: 'bonus', title: '200 бонусов', description: 'Отличный бонус!', icon: 'fa-solid fa-gem', value: 200, rarity: 'epic', edit: false },
                    { id: 8, type: 'product', title: 'Пицца Маргарита', description: 'Бесплатная пицца Маргарита', icon: 'fa-solid fa-pizza-slice', productId: 102, productName: 'Пицца Маргарита', rarity: 'epic', edit: false },
                    { id: 9, type: 'order_discount', title: 'Скидка 20% на заказ', description: 'Скидка на заказ от 1500₽', icon: 'fa-solid fa-receipt', value: 20, minOrderAmount: 1500, rarity: 'epic', edit: false },
                    { id: 10, type: 'product', title: 'Сет "Праздничный"', description: 'Большой праздничный сет бесплатно', icon: 'fa-solid fa-gift', productId: 201, productName: 'Сет "Праздничный"', rarity: 'legendary', edit: false },
                    { id: 11, type: 'order_discount', title: 'Скидка 50% на заказ', description: 'Огромная скидка на заказ от 3000₽', icon: 'fa-solid fa-crown', value: 50, minOrderAmount: 3000, rarity: 'legendary', edit: false },
                ]
            }
        };
    },

    computed: {
        totalChances() {
            const c = this.form.rarity_chances || {};
            return (c.common || 0) + (c.rare || 0) + (c.epic || 0) + (c.legendary || 0);
        },

        sortedPrizes() {
            const order = { legendary: 0, epic: 1, rare: 2, common: 3 };
            return [...this.form.prizes].sort((a, b) => (order[a.rarity] || 4) - (order[b.rarity] || 4));
        }
    },

    watch: {
        form: {
            handler: function (newValue) {
                const cleanForm = JSON.parse(JSON.stringify(newValue));
                cleanForm.prizes = cleanForm.prizes.map(item => {
                    const { edit, ...rest } = item;
                    return rest;
                });
                this.$emit("update:modelValue", cleanForm);
            },
            deep: true
        },
        modelValue: {
            handler: function (newValue) {
                if (newValue) {
                    this.form.can_play = newValue.can_play ?? this.form.can_play;
                    this.form.move_cost = newValue.move_cost ?? this.form.move_cost;
                    this.form.attempts_per_period = newValue.attempts_per_period ?? this.form.attempts_per_period;
                    this.form.interval = newValue.interval ?? this.form.interval;
                    this.form.reveal_threshold = newValue.reveal_threshold ?? this.form.reveal_threshold;
                    this.form.brush_size = newValue.brush_size ?? this.form.brush_size;
                    this.form.scratch_text = newValue.scratch_text ?? this.form.scratch_text;
                    this.form.title = newValue.title ?? this.form.title;
                    this.form.subtitle = newValue.subtitle ?? this.form.subtitle;
                    this.form.rules = newValue.rules ?? this.form.rules;

                    if (newValue.type_colors) {
                        this.form.type_colors = { ...this.form.type_colors, ...newValue.type_colors };
                    }
                    if (newValue.rarity_chances) {
                        this.form.rarity_chances = { ...this.form.rarity_chances, ...newValue.rarity_chances };
                    }

                    if (newValue.prizes && Array.isArray(newValue.prizes) && newValue.prizes.length > 0) {
                        this.form.prizes = newValue.prizes.map(item => ({
                            ...item,
                            edit: false
                        }));
                    }
                }
            },
            immediate: true
        }
    },

    methods: {
        typeLabel(type) {
            const found = this.prizeTypes.find(t => t.key === type);
            return found ? found.label : type;
        },

        rarityLabel(rarity) {
            const found = this.rarities.find(r => r.key === rarity);
            return found ? found.label : rarity;
        },

        getTypeGradient(type) {
            const color = this.form.type_colors[type] || '#6c757d';
            return `linear-gradient(135deg, ${color} 0%, ${this.shadeColor(color, -20)} 100%)`;
        },

        shadeColor(color, percent) {
            const f = parseInt(color.slice(1), 16);
            const t = percent < 0 ? 0 : 255;
            const p = percent < 0 ? percent * -1 : percent;
            const R = f >> 16, G = f >> 8 & 0x00FF, B = f & 0x0000FF;
            return "#" + (0x1000000 + (Math.round((t - R) * p / 100) + R) * 0x10000 +
                (Math.round((t - G) * p / 100) + G) * 0x100 +
                (Math.round((t - B) * p / 100) + B)).toString(16).slice(1);
        },

        formatPrizeValue(prize) {
            if (!prize) return '';
            switch (prize.type) {
                case 'bonus': return `+${prize.value || 0}`;
                case 'product': return 'Товар';
                case 'product_discount': return `−${prize.value || 0}%`;
                case 'delivery_discount':
                    if (prize.isFreeDelivery) return 'Бесплатно';
                    return prize.isPercent ? `−${prize.value || 0}%` : `−${prize.value || 0}₽`;
                case 'order_discount': return `−${prize.value || 0}%`;
                default: return prize.value || '';
            }
        },

        changePrizeType(prize, type) {
            prize.type = type;
            // Очистка полей от предыдущего типа
            delete prize.value;
            delete prize.productId;
            delete prize.productName;
            delete prize.isPercent;
            delete prize.isFreeDelivery;
            delete prize.minOrderAmount;

            // Установка дефолтных значений
            if (type === 'bonus') {
                prize.value = 10;
            } else if (type === 'product') {
                prize.productId = null;
                prize.productName = '';
            } else if (type === 'product_discount') {
                prize.value = 10;
                prize.productId = null;
                prize.productName = '';
            } else if (type === 'delivery_discount') {
                prize.value = 50;
                prize.isPercent = false;
                prize.isFreeDelivery = false;
            } else if (type === 'order_discount') {
                prize.value = 10;
                prize.minOrderAmount = 500;
            }
        },

        addPrize() {
            const newId = this.form.prizes.length > 0 ? Math.max(...this.form.prizes.map(p => p.id || 0)) + 1 : 1;

            this.form.prizes.push({
                id: newId,
                type: 'bonus',
                title: `Приз #${newId}`,
                description: 'Описание приза',
                icon: 'fa-solid fa-gift',
                value: 10,
                rarity: 'common',
                edit: true
            });
        },

        removePrize(index) {
            if (confirm(`Удалить приз "${this.form.prizes[index].title}"?`)) {
                this.form.prizes.splice(index, 1);
                this.form.prizes.forEach((item, idx) => { item.id = idx + 1; });
            }
        },

        async saveSettings() {
            if (this.totalChances !== 100) {
                this.$notify?.({ title: 'Ошибка', text: 'Сумма шансов должна быть равна 100%', type: 'error' });
                return;
            }

            if (this.form.prizes.length === 0) {
                this.$notify?.({ title: 'Ошибка', text: 'Добавьте хотя бы один приз', type: 'error' });
                return;
            }

            // Валидация призов
            for (const prize of this.form.prizes) {
                if (!prize.title || !prize.icon) {
                    this.$notify?.({ title: 'Ошибка', text: 'У всех призов должны быть название и иконка', type: 'error' });
                    return;
                }
                if (prize.type === 'bonus' && (!prize.value || prize.value <= 0)) {
                    this.$notify?.({ title: 'Ошибка', text: `Приз "${prize.title}": укажите количество бонусов`, type: 'error' });
                    return;
                }
                if (prize.type === 'product' && !prize.productName) {
                    this.$notify?.({ title: 'Ошибка', text: `Приз "${prize.title}": укажите название товара`, type: 'error' });
                    return;
                }
                if (prize.type === 'product_discount' && (!prize.value || !prize.productName)) {
                    this.$notify?.({ title: 'Ошибка', text: `Приз "${prize.title}": заполните скидку и название товара`, type: 'error' });
                    return;
                }
                if (prize.type === 'order_discount' && (!prize.value || !prize.minOrderAmount)) {
                    this.$notify?.({ title: 'Ошибка', text: `Приз "${prize.title}": заполните скидку и мин. сумму заказа`, type: 'error' });
                    return;
                }
            }

            this.isSaving = true;

            try {
                const cleanForm = JSON.parse(JSON.stringify(this.form));
                cleanForm.prizes = cleanForm.prizes.map(item => {
                    const { edit, ...rest } = item;
                    return rest;
                });

                const response = await axios.put('/admin/tenant-settings/scratch-card', {
                    scratch_card: cleanForm
                });

                this.$notify?.({
                    title: 'Успех',
                    text: response.data.message || 'Настройки сохранены',
                    type: 'success'
                });

            } catch (error) {
                console.error('Ошибка сохранения:', error);
                const errorMsg = error.response?.data?.message || 'Не удалось сохранить настройки';
                this.$notify?.({ title: 'Ошибка', text: errorMsg, type: 'error' });
            } finally {
                this.isSaving = false;
            }
        }
    }
};
</script>

<style scoped>
.scratch-admin-page {
    background-color: #F8FAFC;
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #334155;
}

.modern-header { display: flex; justify-content: space-between; align-items: center; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0F172A; margin: 0; }
.page-subtitle { font-size: 0.9rem; color: #64748B; margin: 4px 0 0 0; }

.modern-card {
    background: white; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
}
.card-title { font-size: 1rem; font-weight: 600; color: #0F172A; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; }
.card-title i { color: #f5576c; }

.form-label-modern { font-size: 0.8rem; font-weight: 600; color: #64748B; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; }
.char-counter { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.unit-hint { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.form-input-modern {
    width: 100%; padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s; background: #F8FAFC;
}
.form-input-modern.form-input-sm { padding: 6px 10px; font-size: 0.85rem; }
.form-input-modern:focus { outline: none; border-color: #f5576c; background: white; box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1); }

/* Слайдеры */
.form-range-modern {
    width: 100%;
    height: 6px;
    -webkit-appearance: none;
    appearance: none;
    background: #E2E8F0;
    border-radius: 3px;
    outline: none;
    margin: 8px 0 4px;
}
.form-range-modern::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(245, 87, 108, 0.4);
}
.form-range-modern::-moz-range-thumb {
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 50%;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 8px rgba(245, 87, 108, 0.4);
}
.range-hint {
    font-size: 0.72rem;
    color: #94A3B8;
    margin-top: 2px;
}

.modern-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
.modern-switch input { display: none; }
.switch-slider { width: 40px; height: 22px; background: #CBD5E1; border-radius: 99px; position: relative; transition: background 0.3s ease; }
.switch-slider::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #f5576c; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(18px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.9rem; }

/* Цвета */
.color-input-wrapper { display: flex; align-items: center; gap: 8px; }
.color-picker-sm { width: 42px; height: 38px; border: 1px solid #E2E8F0; border-radius: 8px; padding: 2px; cursor: pointer; background: white; }
.color-hex { font-family: monospace; color: #64748B; font-size: 0.85rem; background: #F1F5F9; padding: 4px 8px; border-radius: 6px; }
.type-label-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; vertical-align: middle; }

/* Шансы */
.rarity-chances {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.rarity-chance-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
}

.rarity-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 0.9rem;
}

.rarity-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.rarity-common .rarity-dot { background: #6c757d; }
.rarity-rare .rarity-dot { background: #0d6efd; }
.rarity-epic .rarity-dot { background: #6f42c1; }
.rarity-legendary .rarity-dot { background: #ffc107; }

.rarity-input-wrapper {
    display: flex;
    align-items: center;
    gap: 4px;
}

.rarity-input-wrapper input {
    width: 80px;
    text-align: center;
}

.percent-sign {
    color: #64748B;
    font-weight: 600;
}

.chances-total {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: #F0FDF4;
    border: 1px solid #10B981;
    border-radius: 10px;
    font-weight: 600;
    color: #10B981;
}

.chances-total.invalid {
    background: #FEF2F2;
    border-color: #EF4444;
    color: #EF4444;
}

.total-label { font-size: 0.9rem; }
.total-value { font-size: 1.1rem; font-weight: 700; }

.badge-sector { padding: 4px 10px; background: #FEF2F2; color: #f5576c; border-radius: 99px; font-weight: 700; font-size: 0.8rem; }

/* Призы */
.prizes-list { display: flex; flex-direction: column; gap: 10px; }

.prize-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}

.prize-card:hover { border-color: #CBD5E1; }

.prize-card.rarity-legendary {
    border-color: rgba(255, 215, 0, 0.3);
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, transparent 100%);
}
.prize-card.rarity-epic {
    border-color: rgba(111, 66, 193, 0.2);
    background: linear-gradient(135deg, rgba(111, 66, 193, 0.03) 0%, transparent 100%);
}
.prize-card.rarity-rare {
    border-color: rgba(13, 110, 253, 0.2);
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.03) 0%, transparent 100%);
}

.prize-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.prize-header:hover { background: #F1F5F9; }

.prize-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.prize-icon-preview {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;
}

.prize-info { flex: 1; min-width: 0; }

.prize-name {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.95rem;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.prize-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.prize-type-tag {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
}

.prize-rarity-tag {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
}

.prize-rarity-tag.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.prize-rarity-tag.rarity-rare { background: rgba(13, 110, 253, 0.15); color: #0d6efd; }
.prize-rarity-tag.rarity-epic { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.prize-rarity-tag.rarity-legendary { background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%); color: #b8860b; }

.prize-value-tag {
    font-size: 0.8rem;
    font-weight: 700;
    color: #10B981;
}

.prize-actions { display: flex; gap: 6px; }

.btn-icon {
    width: 32px; height: 32px; border-radius: 6px; border: 1px solid #E2E8F0; background: white;
    color: #64748B; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.btn-icon.btn-success { color: #10B981; border-color: #10B981; }
.btn-icon.btn-success:hover { background: #10B981; color: white; }
.btn-icon.btn-danger { color: #EF4444; border-color: #EF4444; }
.btn-icon.btn-danger:hover { background: #EF4444; color: white; }
.btn-icon.btn-secondary { color: #64748B; }
.btn-icon.btn-secondary:hover { background: #F1F5F9; color: #334155; }

.prize-edit-mode {
    padding: 0 12px 12px 12px;
    border-top: 1px dashed #E2E8F0;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Выбор типа */
.type-selector {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 6px;
}

.type-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px 6px;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748B;
    text-align: center;
}

.type-option:hover { border-color: #f5576c; color: #f5576c; }

.type-option.active {
    background: #FEF2F2;
    border-color: #f5576c;
    color: #f5576c;
}

.type-option i { font-size: 1.1rem; }

/* Формат скидки */
.format-selector {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.format-option {
    flex: 1;
    min-width: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748B;
    cursor: pointer;
    transition: all 0.2s;
}

.format-option:hover { border-color: #f5576c; color: #f5576c; }

.format-option.active {
    background: #FEF2F2;
    border-color: #f5576c;
    color: #f5576c;
}

.format-option.format-free.active {
    background: #F0FDF4;
    border-color: #10B981;
    color: #10B981;
}

/* Иконки */
.icon-input-wrapper { position: relative; }
.icon-picker-dropdown {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
    gap: 4px;
    margin-top: 8px;
    padding: 8px;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    max-height: 140px;
    overflow-y: auto;
}

.icon-btn {
    width: 100%;
    aspect-ratio: 1;
    border: 1px solid transparent;
    background: #F8FAFC;
    font-size: 1.1rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f5576c;
}

.icon-btn:hover { background: #E2E8F0; transform: scale(1.15); }

.icon-btn.is-selected {
    border-color: #f5576c;
    background: #FEF2F2;
    box-shadow: 0 0 0 2px rgba(245, 87, 108, 0.2);
}

/* Предпросмотр */
.preview-prizes-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 400px;
    overflow-y: auto;
}

.preview-prize-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    transition: all 0.2s;
}

.preview-prize-item:hover { transform: translateX(4px); }

.preview-prize-item.rarity-legendary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.08) 0%, transparent 100%);
    border-color: rgba(255, 215, 0, 0.3);
}
.preview-prize-item.rarity-epic {
    background: linear-gradient(135deg, rgba(111, 66, 193, 0.05) 0%, transparent 100%);
    border-color: rgba(111, 66, 193, 0.2);
}

.preview-prize-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
    flex-shrink: 0;
}

.preview-prize-info { flex: 1; min-width: 0; }

.preview-prize-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    color: #0F172A;
    margin-bottom: 2px;
    flex-wrap: wrap;
}

.preview-rarity-tag {
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
}

.preview-rarity-tag.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.preview-rarity-tag.rarity-rare { background: rgba(13, 110, 253, 0.15); color: #0d6efd; }
.preview-rarity-tag.rarity-epic { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.preview-rarity-tag.rarity-legendary { background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%); color: #b8860b; }

.preview-prize-desc {
    font-size: 0.75rem;
    color: #64748B;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.preview-prize-value {
    font-weight: 800;
    font-size: 0.95rem;
    color: #10B981;
    flex-shrink: 0;
}

/* Кнопки */
.btn-modern {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s; padding: 8px 16px;
}
.btn-modern.btn-primary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; box-shadow: 0 4px 6px -1px rgba(245, 87, 108, 0.2); }
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(245, 87, 108, 0.3); }
.btn-modern.btn-secondary { background: white; color: #475569; border-color: #E2E8F0; }
.btn-modern.btn-secondary:hover:not(:disabled) { background: #F1F5F9; }
.btn-modern:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-sm { padding: 4px 8px; font-size: 0.8rem; }

.save-actions-bar {
    position: sticky;
    bottom: 20px;
    display: flex;
    justify-content: center;
    margin-top: 20px;
    z-index: 50;
}

.btn-modern.btn-lg {
    padding: 14px 32px;
    font-size: 1.05rem;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(245, 87, 108, 0.3);
}

.btn-modern.btn-lg:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(245, 87, 108, 0.4);
}

@media (max-width: 768px) {
    .prize-header { flex-wrap: wrap; }
    .prize-actions { width: 100%; justify-content: flex-end; margin-top: 4px; }
    .type-selector { grid-template-columns: repeat(2, 1fr); }
    .rarity-chances { grid-template-columns: 1fr; }
    .format-selector { flex-direction: column; }
    .preview-prize-item { flex-wrap: wrap; }
    .preview-prize-value { width: 100%; text-align: right; margin-top: 4px; }
}
</style>
