<template>
    <div class="daily-bonus-admin-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-calendar-check me-2" style="color: #fa709a;"></i> Ежедневный бонус
                    </h2>
                    <p class="page-subtitle">Настройка призов по дням серии и правил игры</p>
                </div>
            </div>

            <!-- 🆕 ОСНОВНЫЕ НАСТРОЙКИ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-sliders"></i> Общие настройки</h5>
                <div class="row g-3">
                    <div class="col-md-4 d-flex align-items-end">
                        <label class="modern-switch mb-0">
                            <input type="checkbox" v-model="form.can_play">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Активность игры</span>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Дней в серии</label>
                        <select class="form-input-modern form-input-sm" v-model.number="form.streak_days">
                            <option :value="5">5 дней</option>
                            <option :value="7">7 дней</option>
                            <option :value="10">10 дней</option>
                            <option :value="14">14 дней</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Сброс серии при пропуске</label>
                        <select class="form-input-modern form-input-sm" v-model.number="form.streak_reset_days">
                            <option :value="1">Через 1 день</option>
                            <option :value="2">Через 2 дня</option>
                            <option :value="3">Через 3 дня</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Заголовок страницы <span class="char-counter" v-if="form.title">{{ form.title.length }}/100</span></label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.title" maxlength="100" placeholder="Ежедневный бонус">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Подзаголовок <span class="char-counter" v-if="form.subtitle">{{ form.subtitle.length }}/150</span></label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.subtitle" maxlength="150" placeholder="Заходи каждый день и открывай сундучки!">
                    </div>
                    <div class="col-12">
                        <label class="form-label-modern">Правила игры <span class="char-counter" v-if="form.rules">{{ form.rules.length }}/4000</span></label>
                        <textarea class="form-input-modern form-input-sm" v-model="form.rules" rows="3" maxlength="4000" placeholder="Опишите правила ежедневного бонуса..."></textarea>
                    </div>
                </div>
            </div>

            <!-- 🆕 ЦВЕТА ТИПОВ ПРИЗОВ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-palette"></i> Цвета типов призов</h5>
                <p class="text-muted small mb-3">Настройте цвета для визуального отображения разных типов наград</p>

                <div class="row g-3">
                    <div class="col-md-3 col-6" v-for="type in prizeTypes" :key="type.key">
                        <label class="form-label-modern">{{ type.label }}</label>
                        <div class="color-input-wrapper">
                            <input type="color" v-model="form.type_colors[type.key]" class="color-picker-sm">
                            <span class="color-hex">{{ form.type_colors[type.key] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 НАГРАДЫ ПО ДНЯМ СЕРИИ -->
            <div class="modern-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-calendar-days"></i> Награды по дням серии</h5>
                    <span class="badge-sector">{{ form.rewards.length }} / {{ form.streak_days }}</span>
                </div>
                <p class="text-muted small mb-3">Настройте приз для каждого дня серии. Последний день — джекпот!</p>

                <div class="rewards-list">
                    <div
                        v-for="(reward, index) in form.rewards"
                        :key="index"
                        class="reward-card"
                        :class="{ 'is-jackpot': index === form.streak_days - 1 }"
                    >
                        <div class="reward-header" @click="reward.edit = !reward.edit">
                            <div class="reward-day-badge" :class="{ 'jackpot': index === form.streak_days - 1 }">
                                <i v-if="index === form.streak_days - 1" class="fa-solid fa-crown"></i>
                                <span v-else>День {{ index + 1 }}</span>
                            </div>
                            <div class="reward-preview">
                                <div class="reward-icon-preview" :style="{ background: getGradient(reward.type) }">
                                    <i :class="reward.icon"></i>
                                </div>
                                <div class="reward-info">
                                    <div class="reward-title">{{ reward.title || 'Без названия' }}</div>
                                    <div class="reward-meta">
                                        <span class="reward-type-tag" :style="{ background: form.type_colors[reward.type] + '20', color: form.type_colors[reward.type] }">
                                            {{ prizeTypeLabel(reward.type) }}
                                        </span>
                                        <span v-if="reward.type === 'bonus' || reward.type === 'discount'" class="reward-range">
                                            {{ reward.min }} - {{ reward.max }}
                                        </span>
                                        <span v-else-if="reward.type === 'product'" class="reward-range">
                                            {{ reward.products?.length || 0 }} товаров
                                        </span>
                                        <span v-else-if="reward.type === 'jackpot'" class="reward-range">
                                            {{ reward.options?.length || 0 }} вариантов
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button class="btn-icon btn-sm" :class="reward.edit ? 'btn-success' : 'btn-secondary'" @click.stop="reward.edit = !reward.edit">
                                <i class="fa-solid" :class="reward.edit ? 'fa-check' : 'fa-pen'"></i>
                            </button>
                        </div>

                        <div v-if="reward.edit" class="reward-edit-mode fade-in">
                            <div class="row g-3">
                                <!-- ТИП ПРИЗА -->
                                <div class="col-12">
                                    <label class="form-label-modern">Тип приза</label>
                                    <div class="type-selector">
                                        <button
                                            v-for="type in prizeTypes"
                                            :key="type.key"
                                            type="button"
                                            class="type-option"
                                            :class="{ 'active': reward.type === type.key }"
                                            @click="changeRewardType(reward, type.key)"
                                            :disabled="type.key === 'jackpot' && index !== form.streak_days - 1"
                                        >
                                            <i :class="type.icon"></i>
                                            <span>{{ type.label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- ИКОНКА -->
                                <div class="col-12 col-md-4">
                                    <label class="form-label-modern">Иконка</label>
                                    <div class="icon-input-wrapper">
                                        <input type="text" v-model="reward.icon" class="form-input-modern form-input-sm text-center" placeholder="fa-solid fa-gift">
                                        <div class="icon-picker-dropdown">
                                            <button
                                                class="icon-btn"
                                                v-for="icon in icons"
                                                :key="icon"
                                                @click="reward.icon = icon"
                                                :class="{ 'is-selected': reward.icon === icon }"
                                            >
                                                <i :class="icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- НАЗВАНИЕ -->
                                <div class="col-12 col-md-8">
                                    <label class="form-label-modern">Название приза</label>
                                    <input type="text" v-model="reward.title" class="form-input-modern form-input-sm" placeholder="Например: Бонусы или Скидка на заказ">
                                </div>

                                <!-- МИН/МАКС для bonus/discount -->
                                <template v-if="reward.type === 'bonus' || reward.type === 'discount'">
                                    <div class="col-6">
                                        <label class="form-label-modern">
                                            Минимальное значение
                                            <span class="unit-hint">{{ reward.type === 'bonus' ? 'бонусов' : '%' }}</span>
                                        </label>
                                        <input type="number" v-model.number="reward.min" class="form-input-modern form-input-sm" min="1">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label-modern">
                                            Максимальное значение
                                            <span class="unit-hint">{{ reward.type === 'bonus' ? 'бонусов' : '%' }}</span>
                                        </label>
                                        <input type="number" v-model.number="reward.max" class="form-input-modern form-input-sm" min="1">
                                    </div>
                                </template>

                                <!-- ТОВАРЫ для product -->
                                <template v-if="reward.type === 'product'">
                                    <div class="col-12">
                                        <label class="form-label-modern">
                                            Список товаров (один из них выпадет случайно)
                                            <span class="items-count">{{ reward.products?.length || 0 }}</span>
                                        </label>
                                        <div class="products-editor">
                                            <div v-for="(product, pIndex) in reward.products" :key="pIndex" class="product-row">
                                                <input type="text" v-model="reward.products[pIndex]" class="form-input-modern form-input-sm" placeholder="Название товара">
                                                <button class="btn-icon btn-sm btn-danger" @click="removeProduct(reward, pIndex)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                            <button class="add-product-btn" @click="addProduct(reward)">
                                                <i class="fa-solid fa-plus"></i>
                                                <span>Добавить товар</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- ВАРИАНТЫ ДЖЕКПОТА -->
                                <template v-if="reward.type === 'jackpot'">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label-modern mb-0">
                                                Варианты джекпота
                                                <span class="items-count">{{ reward.options?.length || 0 }}</span>
                                            </label>
                                            <button class="btn-modern btn-secondary btn-sm" @click="addJackpotOption(reward)">
                                                <i class="fa-solid fa-plus"></i> Добавить вариант
                                            </button>
                                        </div>
                                        <div class="jackpot-options-list">
                                            <div v-for="(option, oIndex) in reward.options" :key="oIndex" class="jackpot-option-card">
                                                <div class="option-header">
                                                    <div class="option-badge">#{{ oIndex + 1 }}</div>
                                                    <button class="btn-icon btn-sm btn-danger" @click="removeJackpotOption(reward, oIndex)">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                                <div class="row g-2 mt-2">
                                                    <div class="col-4">
                                                        <label class="form-label-modern">Тип</label>
                                                        <select v-model="option.type" class="form-input-modern form-input-sm">
                                                            <option value="bonus">Бонусы</option>
                                                            <option value="discount">Скидка</option>
                                                            <option value="product">Товар</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="form-label-modern">Значение</label>
                                                        <input type="number" v-model.number="option.value" class="form-input-modern form-input-sm" min="1">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="form-label-modern">Иконка</label>
                                                        <input type="text" v-model="option.icon" class="form-input-modern form-input-sm text-center">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label-modern">Название</label>
                                                        <input type="text" v-model="option.title" class="form-input-modern form-input-sm" placeholder="ДЖЕКПОТ! 500 бонусов">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Кнопки управления днями -->
                <div class="days-management mt-3 pt-3 border-top">
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn-modern btn-secondary btn-sm" @click="addDay" :disabled="form.streak_days >= 14">
                            <i class="fa-solid fa-plus"></i> Добавить день
                        </button>
                        <button class="btn-modern btn-secondary btn-sm" @click="removeDay" :disabled="form.streak_days <= 3">
                            <i class="fa-solid fa-minus"></i> Убрать день
                        </button>
                    </div>
                </div>
            </div>

            <!-- 🆕 ПРЕДПРОСМОТР СЕРИИ -->
            <div class="modern-card">
                <h5 class="card-title"><i class="fa-solid fa-eye"></i> Предпросмотр прогресса серии</h5>
                <p class="text-muted small mb-3">Так пользователи увидят серию дней на странице</p>

                <div class="streak-preview">
                    <div
                        v-for="(reward, index) in form.rewards"
                        :key="index"
                        class="preview-day"
                        :class="{
                            'is-completed': index < 3,
                            'is-current': index === 3,
                            'is-jackpot': index === form.streak_days - 1
                        }"
                    >
                        <div class="preview-day-circle">
                            <i v-if="index < 3" class="fa-solid fa-check"></i>
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <div class="preview-day-reward">
                            <i :class="reward.icon"></i>
                        </div>
                        <div class="preview-day-label">
                            День {{ index + 1 }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 КНОПКА СОХРАНЕНИЯ -->
            <div class="save-actions-bar">
                <button
                    class="btn-modern btn-primary btn-lg"
                    @click="saveSettings"
                    :disabled="isSaving"
                >
                    <i v-if="isSaving" class="fa-solid fa-circle-notch fa-spin me-2"></i>
                    <i v-else class="fa-solid fa-floppy-disk me-2"></i>
                    {{ isSaving ? 'Сохранение...' : 'Сохранить настройки ежедневного бонуса' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "DailyBonusAdminSettings",
    props: { modelValue: { type: Object, default: () => ({}) } },
    emits: ['update:modelValue'],

    data() {
        return {
            isSaving: false,
            icons: [
                'fa-solid fa-coins', 'fa-solid fa-gem', 'fa-solid fa-percent', 'fa-solid fa-gift', 'fa-solid fa-crown',
                'fa-solid fa-trophy', 'fa-solid fa-star', 'fa-solid fa-fire', 'fa-solid fa-bolt', 'fa-solid fa-heart',
                'fa-solid fa-pizza-slice', 'fa-solid fa-mug-hot', 'fa-solid fa-burger', 'fa-solid fa-ice-cream', 'fa-solid fa-cookie',
                'fa-solid fa-cake-candles', 'fa-solid fa-wine-glass', 'fa-solid fa-bottle-droplet', 'fa-solid fa-bowl-food', 'fa-solid fa-shrimp'
            ],
            prizeTypes: [
                { key: 'bonus', label: 'Бонусы', icon: 'fa-solid fa-coins' },
                { key: 'discount', label: 'Скидка', icon: 'fa-solid fa-percent' },
                { key: 'product', label: 'Товар', icon: 'fa-solid fa-gift' },
                { key: 'jackpot', label: 'ДЖЕКПОТ', icon: 'fa-solid fa-crown' }
            ],
            form: {
                can_play: true,
                streak_days: 7,
                streak_reset_days: 1,
                title: 'Ежедневный бонус',
                subtitle: 'Заходи каждый день и открывай сундучки!',
                rules: 'Открывай сундучок каждый день и получай призы! Пропустишь день — серия начнётся сначала.',
                type_colors: {
                    bonus: '#ffd700',
                    discount: '#ff6b6b',
                    product: '#4facfe',
                    jackpot: '#ffd700'
                },
                rewards: [
                    { type: 'bonus', min: 5, max: 15, icon: 'fa-solid fa-coins', title: 'Бонусы', edit: false },
                    { type: 'bonus', min: 15, max: 30, icon: 'fa-solid fa-coins', title: 'Бонусы', edit: false },
                    { type: 'discount', min: 5, max: 15, icon: 'fa-solid fa-percent', title: 'Скидка на заказ', edit: false },
                    { type: 'bonus', min: 30, max: 70, icon: 'fa-solid fa-gem', title: 'Бонусы', edit: false },
                    { type: 'bonus', min: 70, max: 150, icon: 'fa-solid fa-gem', title: 'Бонусы', edit: false },
                    { type: 'product', products: ['Пицца Маргарита', 'Ролл Филадельфия'], icon: 'fa-solid fa-gift', title: 'Ценный приз', edit: false },
                    {
                        type: 'jackpot',
                        icon: 'fa-solid fa-crown',
                        title: 'ДЖЕКПОТ',
                        options: [
                            { type: 'bonus', value: 500, icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ! 500 бонусов' },
                            { type: 'discount', value: 50, icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ! Скидка 50%' }
                        ],
                        edit: false
                    }
                ]
            }
        };
    },

    watch: {
        form: {
            handler: function (newValue) {
                const cleanForm = JSON.parse(JSON.stringify(newValue));
                cleanForm.rewards = cleanForm.rewards.map(item => {
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
                    this.form.streak_days = newValue.streak_days ?? this.form.streak_days;
                    this.form.streak_reset_days = newValue.streak_reset_days ?? this.form.streak_reset_days;
                    this.form.title = newValue.title ?? this.form.title;
                    this.form.subtitle = newValue.subtitle ?? this.form.subtitle;
                    this.form.rules = newValue.rules ?? this.form.rules;

                    if (newValue.type_colors) {
                        this.form.type_colors = { ...this.form.type_colors, ...newValue.type_colors };
                    }

                    if (newValue.rewards && Array.isArray(newValue.rewards) && newValue.rewards.length > 0) {
                        this.form.rewards = newValue.rewards.map(item => ({
                            ...item,
                            edit: false
                        }));
                    }
                }
            },
            immediate: true
        },
        'form.streak_days': function (newVal, oldVal) {
            if (newVal > oldVal) {
                // Добавляем дни
                while (this.form.rewards.length < newVal) {
                    this.form.rewards.push({
                        type: 'bonus',
                        min: 10,
                        max: 30,
                        icon: 'fa-solid fa-coins',
                        title: 'Бонусы',
                        edit: false
                    });
                }
                // Последний день делаем джекпотом
                const lastIdx = this.form.rewards.length - 1;
                if (this.form.rewards[lastIdx].type !== 'jackpot') {
                    this.form.rewards[lastIdx] = {
                        type: 'jackpot',
                        icon: 'fa-solid fa-crown',
                        title: 'ДЖЕКПОТ',
                        options: [
                            { type: 'bonus', value: 500, icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ! 500 бонусов' }
                        ],
                        edit: false
                    };
                }
            } else if (newVal < oldVal) {
                // Убираем дни
                this.form.rewards = this.form.rewards.slice(0, newVal);
                // Последний день делаем джекпотом
                const lastIdx = this.form.rewards.length - 1;
                if (this.form.rewards[lastIdx].type !== 'jackpot') {
                    this.form.rewards[lastIdx] = {
                        type: 'jackpot',
                        icon: 'fa-solid fa-crown',
                        title: 'ДЖЕКПОТ',
                        options: [
                            { type: 'bonus', value: 500, icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ! 500 бонусов' }
                        ],
                        edit: false
                    };
                }
            }
        }
    },

    methods: {
        prizeTypeLabel(type) {
            const found = this.prizeTypes.find(t => t.key === type);
            return found ? found.label : type;
        },

        getGradient(type) {
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

        changeRewardType(reward, type) {
            reward.type = type;
            // Очищаем поля, специфичные для других типов
            if (type === 'bonus' || type === 'discount') {
                reward.min = reward.min || 10;
                reward.max = reward.max || 30;
                delete reward.products;
                delete reward.options;
            } else if (type === 'product') {
                reward.products = reward.products || ['Новый товар'];
                delete reward.min;
                delete reward.max;
                delete reward.options;
            } else if (type === 'jackpot') {
                reward.options = reward.options || [
                    { type: 'bonus', value: 500, icon: 'fa-solid fa-crown', title: 'ДЖЕКПОТ! 500 бонусов' }
                ];
                delete reward.min;
                delete reward.max;
                delete reward.products;
            }
        },

        addProduct(reward) {
            if (!reward.products) reward.products = [];
            reward.products.push('');
        },

        removeProduct(reward, index) {
            if (reward.products?.length > 1) {
                reward.products.splice(index, 1);
            }
        },

        addJackpotOption(reward) {
            if (!reward.options) reward.options = [];
            reward.options.push({
                type: 'bonus',
                value: 100,
                icon: 'fa-solid fa-crown',
                title: 'ДЖЕКПОТ!'
            });
        },

        removeJackpotOption(reward, index) {
            if (reward.options?.length > 1) {
                reward.options.splice(index, 1);
            }
        },

        addDay() {
            if (this.form.streak_days >= 14) return;
            this.form.streak_days++;
        },

        removeDay() {
            if (this.form.streak_days <= 3) return;
            this.form.streak_days--;
        },

        async saveSettings() {
            // Валидация
            if (this.form.rewards.length !== this.form.streak_days) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: `Количество наград (${this.form.rewards.length}) не совпадает с количеством дней (${this.form.streak_days})`,
                    type: 'error'
                });
                return;
            }

            // Проверка последнего дня на джекпот
            const lastReward = this.form.rewards[this.form.rewards.length - 1];
            if (lastReward.type !== 'jackpot') {
                this.$notify?.({
                    title: 'Предупреждение',
                    text: 'Последний день серии должен быть ДЖЕКПОТОМ',
                    type: 'warning'
                });
                return;
            }

            // Проверка на пустые товары
            for (const reward of this.form.rewards) {
                if (reward.type === 'product' && (!reward.products || reward.products.some(p => !p.trim()))) {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: 'У всех товаров должны быть заполнены названия',
                        type: 'error'
                    });
                    return;
                }
                if (reward.type === 'jackpot' && (!reward.options || reward.options.length === 0)) {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: 'У джекпота должен быть хотя бы один вариант',
                        type: 'error'
                    });
                    return;
                }
            }

            this.isSaving = true;

            try {
                const cleanForm = JSON.parse(JSON.stringify(this.form));
                cleanForm.rewards = cleanForm.rewards.map(item => {
                    const { edit, ...rest } = item;
                    return rest;
                });

                const response = await axios.put('/admin/tenant-settings/daily-bonus', {
                    daily_bonus: cleanForm
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
.daily-bonus-admin-page {
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
.card-title i { color: #fa709a; }

.form-label-modern { font-size: 0.8rem; font-weight: 600; color: #64748B; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; }
.char-counter { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.unit-hint { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.items-count { padding: 2px 8px; background: #EEF2FF; color: #fa709a; border-radius: 99px; font-weight: 700; font-size: 0.7rem; }
.form-input-modern {
    width: 100%; padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s; background: #F8FAFC;
}
.form-input-modern.form-input-sm { padding: 6px 10px; font-size: 0.85rem; }
.form-input-modern:focus { outline: none; border-color: #fa709a; background: white; box-shadow: 0 0 0 3px rgba(250, 112, 154, 0.1); }

.modern-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
.modern-switch input { display: none; }
.switch-slider { width: 40px; height: 22px; background: #CBD5E1; border-radius: 99px; position: relative; transition: background 0.3s ease; }
.switch-slider::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #fa709a; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(18px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.9rem; }

/* Цвета */
.color-input-wrapper { display: flex; align-items: center; gap: 8px; }
.color-picker-sm { width: 42px; height: 38px; border: 1px solid #E2E8F0; border-radius: 8px; padding: 2px; cursor: pointer; background: white; }
.color-hex { font-family: monospace; color: #64748B; font-size: 0.85rem; background: #F1F5F9; padding: 4px 8px; border-radius: 6px; }

.badge-sector { padding: 4px 10px; background: #FEF2F2; color: #fa709a; border-radius: 99px; font-weight: 700; font-size: 0.8rem; }

/* Награды */
.rewards-list { display: flex; flex-direction: column; gap: 10px; }

.reward-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}

.reward-card:hover { border-color: #CBD5E1; }

.reward-card.is-jackpot {
    border-color: rgba(255, 215, 0, 0.3);
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, transparent 100%);
}

.reward-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.reward-header:hover { background: #F1F5F9; }

.reward-day-badge {
    min-width: 64px;
    padding: 6px 10px;
    background: #fa709a;
    color: white;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.8rem;
    text-align: center;
    flex-shrink: 0;
}

.reward-day-badge.jackpot {
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
}

.reward-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
}

.reward-icon-preview {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;
}

.reward-info { flex: 1; min-width: 0; }

.reward-title {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.9rem;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.reward-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.reward-type-tag {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
}

.reward-range {
    font-size: 0.75rem;
    color: #64748B;
    font-weight: 600;
}

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

.reward-edit-mode {
    padding: 0 12px 12px 12px;
    border-top: 1px dashed #E2E8F0;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Выбор типа приза */
.type-selector {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.type-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 10px 8px;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748B;
}

.type-option:hover:not(:disabled) {
    border-color: #fa709a;
    color: #fa709a;
}

.type-option.active {
    background: #FEF2F2;
    border-color: #fa709a;
    color: #fa709a;
}

.type-option:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.type-option i { font-size: 1.2rem; }

/* Иконки */
.icon-input-wrapper { position: relative; }
.icon-picker-dropdown {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
    gap: 6px;
    margin-top: 8px;
    padding: 10px;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    max-height: 160px;
    overflow-y: auto;
}

.icon-btn {
    width: 100%;
    aspect-ratio: 1;
    border: 1px solid transparent;
    background: #F8FAFC;
    font-size: 1.2rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fa709a;
}

.icon-btn:hover { background: #E2E8F0; transform: scale(1.15); }

.icon-btn.is-selected {
    border-color: #fa709a;
    background: #FEF2F2;
    box-shadow: 0 0 0 2px rgba(250, 112, 154, 0.2);
}

/* Товары */
.products-editor {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.product-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.product-row .form-input-modern { flex: 1; }

.add-product-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px;
    background: transparent;
    border: 1px dashed #CBD5E1;
    border-radius: 8px;
    color: #64748B;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.85rem;
    font-weight: 500;
}

.add-product-btn:hover {
    border-color: #fa709a;
    color: #fa709a;
    background: #FEF2F2;
}

/* Варианты джекпота */
.jackpot-options-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.jackpot-option-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 12px;
    border-left: 3px solid #ffd700;
}

.option-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.option-badge {
    padding: 2px 8px;
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.75rem;
}

/* Управление днями */
.days-management {
    display: flex;
    justify-content: center;
}

/* Предпросмотр серии */
.streak-preview {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    padding: 20px;
    background: linear-gradient(135deg, #FFF1F2 0%, #FEF3C7 100%);
    border-radius: 12px;
    border: 1px dashed #E2E8F0;
    overflow-x: auto;
}

.preview-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 60px;
}

.preview-day-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #F1F5F9;
    border: 2px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    color: #64748B;
    transition: all 0.3s;
}

.preview-day.is-completed .preview-day-circle {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    border-color: #fa709a;
    color: white;
}

.preview-day.is-current .preview-day-circle {
    border-color: #fa709a;
    color: #fa709a;
    animation: pulse 2s ease-in-out infinite;
}

.preview-day.is-jackpot .preview-day-circle {
    background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%);
    border-color: #ffd700;
    color: white;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.preview-day-reward {
    font-size: 1.2rem;
}

.preview-day-label {
    font-size: 0.7rem;
    color: #64748B;
    font-weight: 600;
}

/* Кнопки */
.btn-modern {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s; padding: 8px 16px;
}
.btn-modern.btn-primary { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; box-shadow: 0 4px 6px -1px rgba(250, 112, 154, 0.2); }
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(250, 112, 154, 0.3); }
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
    box-shadow: 0 8px 20px rgba(250, 112, 154, 0.3);
}

.btn-modern.btn-lg:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(250, 112, 154, 0.4);
}

@media (max-width: 768px) {
    .reward-header { flex-wrap: wrap; }
    .type-selector { grid-template-columns: repeat(2, 1fr); }
    .streak-preview { flex-wrap: wrap; justify-content: center; }
}
</style>
