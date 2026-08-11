<template>
    <div class="hunt-admin-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-map-location-dot me-2" style="color: #4facfe;"></i> Охота за сокровищами
                    </h2>
                    <p class="page-subtitle">Настройка уровней, бустеров и наград</p>
                </div>
            </div>

            <!-- 🆕 ОБЩИЕ НАСТРОЙКИ -->
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
                        <label class="form-label-modern">
                            Процент выигрыша при ловушке
                            <span class="unit-hint">{{ form.trap_penalty_percent }}%</span>
                        </label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.trap_penalty_percent" min="0" max="100">
                        <div class="form-hint">Сколько % от заработка сохраняется при попадании в ловушку</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">
                            Длительность радара
                            <span class="unit-hint">мс</span>
                        </label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.radar_duration" min="1000" max="10000" step="500">
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

            <!-- 🆕 БУСТЕРЫ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-wand-magic-sparkles"></i> Бустеры</h5>
                <p class="text-muted small mb-3">Настройка стоимости и параметров специальных возможностей</p>

                <div class="boosters-editor">
                    <div
                        v-for="b in boosterKeys"
                        :key="b"
                        class="booster-editor-card"
                    >
                        <div class="booster-editor-header">
                            <div class="booster-emoji">{{ boosterEmojis[b] }}</div>
                            <div class="booster-editor-info">
                                <div class="booster-editor-name">{{ boosterLabels[b] }}</div>
                                <div class="booster-editor-desc">{{ boosterDescriptions[b] }}</div>
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <label class="form-label-modern">Стоимость</label>
                                <input type="number" class="form-input-modern form-input-sm" v-model.number="form.boosters[b].cost" min="1">
                            </div>
                            <div class="col-6">
                                <label class="form-label-modern">
                                    <span class="modern-switch-inline">
                                        <input type="checkbox" v-model="form.boosters[b].enabled">
                                        <span class="switch-slider-sm"></span>
                                    </span>
                                    Активен
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 УРОВНИ (вкладки) -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-layer-group"></i> Уровни игры</h5>
                <p class="text-muted small mb-3">Настройте параметры каждого уровня, сетку карты и награды</p>

                <!-- Вкладки уровней -->
                <div class="levels-tabs">
                    <button
                        v-for="lvlId in levelIds"
                        :key="lvlId"
                        class="level-tab-btn"
                        :class="{ active: activeLevel === lvlId }"
                        :style="activeLevel === lvlId ? {
                            background: `linear-gradient(135deg, ${form.levels[lvlId].color} 0%, ${form.levels[lvlId].color_secondary} 100%)`,
                            color: isLightColor(form.levels[lvlId].color) ? '#1a1a1a' : 'white',
                            borderColor: form.levels[lvlId].color
                        } : {}"
                        @click="activeLevel = lvlId"
                    >
                        <i :class="form.levels[lvlId].icon"></i>
                        <span>{{ form.levels[lvlId].name || `Уровень ${lvlId}` }}</span>
                        <span class="level-cost-badge">{{ form.levels[lvlId].cost }} 💰</span>
                    </button>
                </div>

                <!-- Содержимое уровня -->
                <div class="level-content fade-in" :key="activeLevel">
                    <div class="row g-3">
                        <!-- Название и иконка -->
                        <div class="col-md-5">
                            <label class="form-label-modern">Название уровня</label>
                            <input type="text" class="form-input-modern form-input-sm" v-model="form.levels[activeLevel].name" maxlength="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-modern">Иконка (FontAwesome)</label>
                            <div class="icon-input-wrapper">
                                <input type="text" class="form-input-modern form-input-sm text-center" v-model="form.levels[activeLevel].icon">
                                <div class="icon-picker-dropdown">
                                    <button
                                        class="icon-btn"
                                        v-for="icon in levelIcons"
                                        :key="icon"
                                        @click="form.levels[activeLevel].icon = icon"
                                        :class="{ 'is-selected': form.levels[activeLevel].icon === icon }"
                                    >
                                        <i :class="icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-modern">
                                Стоимость входа
                                <span class="unit-hint">бонусов</span>
                            </label>
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.levels[activeLevel].cost" min="1">
                        </div>

                        <!-- Описание -->
                        <div class="col-12">
                            <label class="form-label-modern">Описание <span class="char-counter">{{ (form.levels[activeLevel].description || '').length }}/200</span></label>
                            <textarea class="form-input-modern form-input-sm" v-model="form.levels[activeLevel].description" rows="2" maxlength="200"></textarea>
                        </div>

                        <!-- Цвета -->
                        <div class="col-md-4">
                            <label class="form-label-modern">Основной цвет</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="form.levels[activeLevel].color" class="color-picker-sm">
                                <span class="color-hex">{{ form.levels[activeLevel].color }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-modern">Вторичный цвет</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="form.levels[activeLevel].color_secondary" class="color-picker-sm">
                                <span class="color-hex">{{ form.levels[activeLevel].color_secondary }}</span>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="level-color-preview" :style="levelPreviewStyle">
                                <i :class="form.levels[activeLevel].icon"></i>
                                <span>{{ form.levels[activeLevel].name }}</span>
                            </div>
                        </div>

                        <!-- Параметры карты -->
                        <div class="col-12">
                            <div class="map-params-section">
                                <h6 class="subsection-title">
                                    <i class="fa-solid fa-table-cells"></i>
                                    Параметры карты
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label-modern">
                                            Размер карты
                                            <span class="unit-hint">{{ form.levels[activeLevel].size }}×{{ form.levels[activeLevel].size }}</span>
                                        </label>
                                        <select class="form-input-modern form-input-sm" v-model.number="form.levels[activeLevel].size">
                                            <option :value="3">3×3 (9 клеток)</option>
                                            <option :value="4">4×4 (16 клеток)</option>
                                            <option :value="5">5×5 (25 клеток)</option>
                                            <option :value="6">6×6 (36 клеток)</option>
                                            <option :value="7">7×7 (49 клеток)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-modern">
                                            💎 Сокровищ
                                            <span class="unit-hint" :class="mapBalanceClass">
                                                {{ form.levels[activeLevel].treasures }}
                                            </span>
                                        </label>
                                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.levels[activeLevel].treasures" :min="1" :max="totalCells - 2">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-modern">
                                            💣 Ловушек
                                            <span class="unit-hint" :class="mapBalanceClass">{{ form.levels[activeLevel].traps }}</span>
                                        </label>
                                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.levels[activeLevel].traps" :min="0" :max="totalCells - form.levels[activeLevel].treasures - form.levels[activeLevel].hints">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-modern">
                                            🧭 Подсказок
                                            <span class="unit-hint" :class="mapBalanceClass">{{ form.levels[activeLevel].hints }}</span>
                                        </label>
                                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.levels[activeLevel].hints" :min="0" :max="totalCells - form.levels[activeLevel].treasures - form.levels[activeLevel].traps">
                                    </div>
                                </div>

                                <!-- Индикатор баланса карты -->
                                <div class="map-balance-indicator" :class="mapBalanceClass">
                                    <div class="balance-bar">
                                        <div class="balance-treasures" :style="{ width: treasuresPercent + '%' }"></div>
                                        <div class="balance-traps" :style="{ width: trapsPercent + '%' }"></div>
                                        <div class="balance-hints" :style="{ width: hintsPercent + '%' }"></div>
                                    </div>
                                    <div class="balance-stats">
                                        <span>💎 {{ treasuresPercent.toFixed(0) }}%</span>
                                        <span>💣 {{ trapsPercent.toFixed(0) }}%</span>
                                        <span>🧭 {{ hintsPercent.toFixed(0) }}%</span>
                                        <span>⬜ {{ emptyPercent.toFixed(0) }}% пусто</span>
                                        <span class="balance-total">
                                            Всего: {{ usedCells }}/{{ totalCells }} клеток
                                        </span>
                                    </div>
                                </div>

                                <!-- Предпросмотр карты -->
                                <div class="map-preview-wrapper">
                                    <div class="map-preview-label">Предпросмотр сетки</div>
                                    <div
                                        class="map-preview-grid"
                                        :style="{
                                            gridTemplateColumns: `repeat(${form.levels[activeLevel].size}, 1fr)`,
                                            gridTemplateRows: `repeat(${form.levels[activeLevel].size}, 1fr)`
                                        }"
                                    >
                                        <div
                                            v-for="i in totalCells"
                                            :key="i"
                                            class="map-preview-cell"
                                        >
                                            <i class="fa-solid fa-question"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Таблица наград -->
                        <div class="col-12">
                            <div class="rewards-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="subsection-title mb-0">
                                        <i class="fa-solid fa-gem"></i>
                                        Сокровища по редкостям
                                    </h6>
                                    <button class="btn-modern btn-secondary btn-sm" @click="addReward">
                                        <i class="fa-solid fa-plus"></i> Добавить
                                    </button>
                                </div>
                                <p class="text-muted small mb-3">
                                    Настройте эмодзи, диапазон наград и вес выпадения для каждого типа сокровищ
                                </p>

                                <div class="rewards-list">
                                    <div
                                        v-for="(reward, rIdx) in form.levels[activeLevel].rewards"
                                        :key="rIdx"
                                        class="reward-editor-card"
                                        :class="`tier-${reward.tier}`"
                                    >
                                        <div class="reward-editor-header">
                                            <div class="reward-emoji-input-wrapper">
                                                <input
                                                    type="text"
                                                    v-model="reward.emoji"
                                                    class="reward-emoji-input"
                                                    maxlength="4"
                                                >
                                            </div>
                                            <div class="reward-editor-info">
                                                <input
                                                    type="text"
                                                    v-model="reward.name"
                                                    class="reward-name-input"
                                                    placeholder="Название"
                                                >
                                                <div class="reward-tier-selector">
                                                    <button
                                                        v-for="tier in tiers"
                                                        :key="tier.key"
                                                        type="button"
                                                        class="tier-btn"
                                                        :class="{ active: reward.tier === tier.key, [`tier-${tier.key}`]: true }"
                                                        @click="reward.tier = tier.key"
                                                    >
                                                        {{ tier.label }}
                                                    </button>
                                                </div>
                                            </div>
                                            <button class="btn-icon btn-sm btn-danger" @click="removeReward(rIdx)" :disabled="form.levels[activeLevel].rewards.length <= 1">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>

                                        <div class="reward-editor-fields">
                                            <div class="row g-2">
                                                <div class="col-4">
                                                    <label class="form-label-modern">Мин. приз</label>
                                                    <input type="number" class="form-input-modern form-input-sm" v-model.number="reward.min" min="1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label-modern">Макс. приз</label>
                                                    <input type="number" class="form-input-modern form-input-sm" v-model.number="reward.max" min="1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label-modern">
                                                        Вес
                                                        <span class="unit-hint">{{ getRewardWeightPercent(reward).toFixed(1) }}%</span>
                                                    </label>
                                                    <input type="number" class="form-input-modern form-input-sm" v-model.number="reward.weight" min="1" max="100">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Визуализация весов -->
                                <div class="weights-chart mt-3">
                                    <div class="weights-title">Распределение шансов</div>
                                    <div
                                        v-for="(reward, idx) in form.levels[activeLevel].rewards"
                                        :key="idx"
                                        class="weight-bar-row"
                                    >
                                        <div class="weight-label">
                                            <span class="weight-emoji">{{ reward.emoji }}</span>
                                            <span>{{ reward.name || 'Без названия' }}</span>
                                        </div>
                                        <div class="weight-bar">
                                            <div
                                                class="weight-fill"
                                                :class="`tier-${reward.tier}`"
                                                :style="{ width: getRewardWeightPercent(reward) + '%' }"
                                            >
                                                <span v-if="getRewardWeightPercent(reward) > 8">
                                                    {{ getRewardWeightPercent(reward).toFixed(0) }}%
                                                </span>
                                            </div>
                                        </div>
                                        <div class="weight-value">
                                            {{ reward.min }}–{{ reward.max }} 💰
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 ПРЕДПРОСМОТР УРОВНЕЙ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-eye"></i> Предпросмотр уровней</h5>
                <p class="text-muted small mb-3">Так пользователи увидят выбор уровней</p>

                <div class="levels-preview-grid">
                    <div
                        v-for="lvlId in levelIds"
                        :key="lvlId"
                        class="level-preview-card"
                        :style="{
                            background: `linear-gradient(135deg, ${form.levels[lvlId].color} 0%, ${form.levels[lvlId].color_secondary} 100%)`
                        }"
                    >
                        <div class="level-preview-icon">
                            <i :class="form.levels[lvlId].icon"></i>
                        </div>
                        <div class="level-preview-name">{{ form.levels[lvlId].name }}</div>
                        <div class="level-preview-desc">{{ form.levels[lvlId].description }}</div>
                        <div class="level-preview-stats">
                            <div class="level-preview-stat">
                                <i class="fa-solid fa-table-cells"></i>
                                <span>{{ form.levels[lvlId].size }}×{{ form.levels[lvlId].size }}</span>
                            </div>
                            <div class="level-preview-stat">
                                <i class="fa-solid fa-gem"></i>
                                <span>{{ form.levels[lvlId].treasures }}</span>
                            </div>
                            <div class="level-preview-stat danger">
                                <i class="fa-solid fa-skull"></i>
                                <span>{{ form.levels[lvlId].traps }}</span>
                            </div>
                        </div>
                        <div class="level-preview-cost">
                            <i class="fa-solid fa-ticket"></i>
                            {{ form.levels[lvlId].cost }} бонусов
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 ВАЛИДАЦИЯ -->
            <div class="modern-card" v-if="validationErrors.length > 0">
                <h5 class="card-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Ошибки валидации</h5>
                <div class="validation-errors-list">
                    <div v-for="(err, idx) in validationErrors" :key="idx" class="validation-error-item">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>{{ err }}</span>
                    </div>
                </div>
            </div>

            <!-- 🆕 КНОПКА СОХРАНЕНИЯ -->
            <div class="save-actions-bar">
                <button
                    class="btn-modern btn-primary btn-lg"
                    @click="saveSettings"
                    :disabled="isSaving || validationErrors.length > 0"
                >
                    <i v-if="isSaving" class="fa-solid fa-circle-notch fa-spin me-2"></i>
                    <i v-else class="fa-solid fa-floppy-disk me-2"></i>
                    {{ isSaving ? 'Сохранение...' : 'Сохранить настройки игры' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TreasureHuntAdminSettings",
    props: { modelValue: { type: Object, default: () => ({}) } },
    emits: ['update:modelValue'],

    data() {
        return {
            isSaving: false,
            activeLevel: 1,
            levelIds: [1, 2, 3],
            boosterKeys: ['radar', 'shield', 'compass'],
            boosterLabels: {
                radar: 'Радар',
                shield: 'Щит',
                compass: 'Компас'
            },
            boosterEmojis: {
                radar: '📡',
                shield: '🛡️',
                compass: '🧭'
            },
            boosterDescriptions: {
                radar: 'Подсвечивает одну клетку с сокровищем на 3 секунды',
                shield: 'Защищает от следующей ловушки',
                compass: 'Показывает расстояние до ближайшего сокровища'
            },
            levelIcons: [
                'fa-solid fa-umbrella-beach', 'fa-solid fa-mountain', 'fa-solid fa-landmark',
                'fa-solid fa-tree', 'fa-solid fa-water', 'fa-solid fa-volcano',
                'fa-solid fa-house-chimney', 'fa-solid fa-tower-observation', 'fa-solid fa-dungeon',
                'fa-solid fa-ship', 'fa-solid fa-anchor', 'fa-solid fa-compass-drafting',
                'fa-solid fa-hat-wizard', 'fa-solid fa-dragon', 'fa-solid fa-chess-rook'
            ],
            tiers: [
                { key: 'common', label: 'Обычное' },
                { key: 'rare', label: 'Редкое' },
                { key: 'legendary', label: 'Легендарное' }
            ],
            form: {
                can_play: true,
                title: 'Охота за сокровищами',
                subtitle: 'Найди спрятанные сокровища на карте!',
                rules: '',
                trap_penalty_percent: 50,
                radar_duration: 3000,
                boosters: {
                    radar: { cost: 30, enabled: true },
                    shield: { cost: 50, enabled: true },
                    compass: { cost: 20, enabled: true }
                },
                levels: {
                    1: {
                        name: 'Остров',
                        icon: 'fa-solid fa-umbrella-beach',
                        description: 'Небольшой остров с сокровищами. Идеально для начинающих!',
                        size: 4,
                        treasures: 3,
                        traps: 2,
                        hints: 2,
                        cost: 30,
                        color: '#43e97b',
                        color_secondary: '#38f9d7',
                        rewards: [
                            { emoji: '💰', name: 'Обычное', min: 30, max: 80, tier: 'common', weight: 70 },
                            { emoji: '💎', name: 'Редкое', min: 150, max: 250, tier: 'rare', weight: 25 },
                            { emoji: '👑', name: 'Легендарное', min: 400, max: 600, tier: 'legendary', weight: 5 }
                        ]
                    },
                    2: {
                        name: 'Пещера',
                        icon: 'fa-solid fa-mountain',
                        description: 'Тёмная пещера с большими сокровищами, но и больше ловушек!',
                        size: 5,
                        treasures: 5,
                        traps: 4,
                        hints: 3,
                        cost: 60,
                        color: '#667eea',
                        color_secondary: '#764ba2',
                        rewards: [
                            { emoji: '💰', name: 'Обычное', min: 50, max: 150, tier: 'common', weight: 60 },
                            { emoji: '💎', name: 'Редкое', min: 250, max: 450, tier: 'rare', weight: 30 },
                            { emoji: '👑', name: 'Легендарное', min: 700, max: 1200, tier: 'legendary', weight: 10 }
                        ]
                    },
                    3: {
                        name: 'Храм',
                        icon: 'fa-solid fa-landmark',
                        description: 'Древний храм с несметными богатствами. Только для опытных!',
                        size: 6,
                        treasures: 8,
                        traps: 6,
                        hints: 4,
                        cost: 120,
                        color: '#ffd700',
                        color_secondary: '#ff9800',
                        rewards: [
                            { emoji: '💰', name: 'Обычное', min: 100, max: 300, tier: 'common', weight: 50 },
                            { emoji: '💎', name: 'Редкое', min: 400, max: 800, tier: 'rare', weight: 35 },
                            { emoji: '👑', name: 'Легендарное', min: 1000, max: 1500, tier: 'legendary', weight: 15 }
                        ]
                    }
                }
            }
        };
    },

    computed: {
        levelPreviewStyle() {
            const lvl = this.form.levels[this.activeLevel];
            return {
                background: `linear-gradient(135deg, ${lvl.color} 0%, ${lvl.color_secondary} 100%)`,
                color: this.isLightColor(lvl.color) ? '#1a1a1a' : 'white'
            };
        },

        totalCells() {
            const size = this.form.levels[this.activeLevel].size;
            return size * size;
        },

        usedCells() {
            const lvl = this.form.levels[this.activeLevel];
            return (lvl.treasures || 0) + (lvl.traps || 0) + (lvl.hints || 0);
        },

        treasuresPercent() {
            if (this.totalCells === 0) return 0;
            return ((this.form.levels[this.activeLevel].treasures || 0) / this.totalCells) * 100;
        },

        trapsPercent() {
            if (this.totalCells === 0) return 0;
            return ((this.form.levels[this.activeLevel].traps || 0) / this.totalCells) * 100;
        },

        hintsPercent() {
            if (this.totalCells === 0) return 0;
            return ((this.form.levels[this.activeLevel].hints || 0) / this.totalCells) * 100;
        },

        emptyPercent() {
            return Math.max(0, 100 - this.treasuresPercent - this.trapsPercent - this.hintsPercent);
        },

        mapBalanceClass() {
            if (this.usedCells > this.totalCells) return 'error';
            if (this.usedCells > this.totalCells * 0.9) return 'warning';
            return 'ok';
        },

        totalRewardWeight() {
            const rewards = this.form.levels[this.activeLevel].rewards || [];
            return rewards.reduce((sum, r) => sum + (r.weight || 0), 0);
        },

        validationErrors() {
            const errors = [];

            if (!this.form.title || !this.form.title.trim()) {
                errors.push('Укажите заголовок игры');
            }

            if (this.form.trap_penalty_percent < 0 || this.form.trap_penalty_percent > 100) {
                errors.push('Процент при ловушке должен быть от 0 до 100');
            }

            for (const b of this.boosterKeys) {
                const booster = this.form.boosters[b];
                if (!booster.cost || booster.cost <= 0) {
                    errors.push(`Бустер "${this.boosterLabels[b]}": стоимость должна быть > 0`);
                }
            }

            for (const lvlId of this.levelIds) {
                const lvl = this.form.levels[lvlId];
                const name = lvl.name || `Уровень ${lvlId}`;
                const total = lvl.size * lvl.size;
                const used = (lvl.treasures || 0) + (lvl.traps || 0) + (lvl.hints || 0);

                if (!lvl.name || !lvl.name.trim()) {
                    errors.push(`Уровень ${lvlId}: не указано название`);
                }
                if (!lvl.cost || lvl.cost <= 0) {
                    errors.push(`"${name}": стоимость должна быть > 0`);
                }
                if (!lvl.size || lvl.size < 3) {
                    errors.push(`"${name}": минимальный размер карты 3×3`);
                }
                if (!lvl.treasures || lvl.treasures < 1) {
                    errors.push(`"${name}": должно быть хотя бы 1 сокровище`);
                }
                if (used > total) {
                    errors.push(`"${name}": сумма объектов (${used}) превышает размер карты (${total})`);
                }
                if (lvl.treasures + lvl.traps >= total) {
                    errors.push(`"${name}": нет места для пустых клеток`);
                }

                if (!lvl.rewards || lvl.rewards.length === 0) {
                    errors.push(`"${name}": добавьте хотя бы одну награду`);
                } else {
                    lvl.rewards.forEach((r, idx) => {
                        if (!r.emoji) {
                            errors.push(`"${name}", награда #${idx + 1}: укажите эмодзи`);
                        }
                        if (!r.name || !r.name.trim()) {
                            errors.push(`"${name}", награда #${idx + 1}: укажите название`);
                        }
                        if (!r.min || r.min < 1) {
                            errors.push(`"${name}", награда #${idx + 1}: мин. приз должен быть > 0`);
                        }
                        if (!r.max || r.max < r.min) {
                            errors.push(`"${name}", награда #${idx + 1}: макс. должен быть ≥ мин.`);
                        }
                        if (!r.weight || r.weight <= 0) {
                            errors.push(`"${name}", награда #${idx + 1}: вес должен быть > 0`);
                        }
                    });
                }
            }

            return errors;
        }
    },

    watch: {
        form: {
            handler: function (newValue) {
                this.$emit("update:modelValue", JSON.parse(JSON.stringify(newValue)));
            },
            deep: true
        },
        modelValue: {
            handler: function (newValue) {
                if (newValue) {
                    this.form.can_play = newValue.can_play ?? this.form.can_play;
                    this.form.title = newValue.title ?? this.form.title;
                    this.form.subtitle = newValue.subtitle ?? this.form.subtitle;
                    this.form.rules = newValue.rules ?? this.form.rules;
                    this.form.trap_penalty_percent = newValue.trap_penalty_percent ?? this.form.trap_penalty_percent;
                    this.form.radar_duration = newValue.radar_duration ?? this.form.radar_duration;

                    if (newValue.boosters) {
                        for (const b of this.boosterKeys) {
                            if (newValue.boosters[b]) {
                                this.form.boosters[b] = { ...this.form.boosters[b], ...newValue.boosters[b] };
                            }
                        }
                    }

                    if (newValue.levels) {
                        for (const lvlId of this.levelIds) {
                            if (newValue.levels[lvlId]) {
                                this.form.levels[lvlId] = { ...this.form.levels[lvlId], ...newValue.levels[lvlId] };
                            }
                        }
                    }
                }
            },
            immediate: true
        }
    },

    methods: {
        isLightColor(hex) {
            if (!hex) return false;
            const c = hex.substring(1);
            const rgb = parseInt(c, 16);
            const r = (rgb >> 16) & 0xff;
            const g = (rgb >> 8) & 0xff;
            const b = (rgb >> 0) & 0xff;
            const luma = 0.299 * r + 0.587 * g + 0.114 * b;
            return luma > 180;
        },

        getRewardWeightPercent(reward) {
            if (this.totalRewardWeight === 0) return 0;
            return ((reward.weight || 0) / this.totalRewardWeight) * 100;
        },

        addReward() {
            this.form.levels[this.activeLevel].rewards.push({
                emoji: '💎',
                name: 'Новый приз',
                min: 50,
                max: 100,
                tier: 'common',
                weight: 10
            });
        },

        removeReward(index) {
            if (this.form.levels[this.activeLevel].rewards.length <= 1) return;
            if (confirm('Удалить эту награду?')) {
                this.form.levels[this.activeLevel].rewards.splice(index, 1);
            }
        },

        async saveSettings() {
            if (this.validationErrors.length > 0) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: `Найдено ошибок: ${this.validationErrors.length}. Исправьте их перед сохранением.`,
                    type: 'error'
                });
                return;
            }

            this.isSaving = true;

            try {
                const cleanForm = JSON.parse(JSON.stringify(this.form));

                const response = await axios.put('/admin/tenant-settings/treasure-hunt', {
                    treasure_hunt: cleanForm
                });

                this.$notify?.({
                    title: 'Успех',
                    text: response.data.message || 'Настройки игры сохранены',
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
.hunt-admin-page {
    background-color: #F8FAFC;
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #334155;
}

.modern-header { display: flex; justify-content: space-between; align-items: center; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px; }
.page-subtitle { font-size: 0.9rem; color: #64748B; margin: 4px 0 0 0; }

.modern-card {
    background: white; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
}
.card-title { font-size: 1rem; font-weight: 600; color: #0F172A; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; }
.card-title i { color: #4facfe; }
.card-title.text-danger { color: #EF4444; }
.card-title.text-danger i { color: #EF4444; }

.form-label-modern { font-size: 0.8rem; font-weight: 600; color: #64748B; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; }
.char-counter { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.unit-hint { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.unit-hint.error { color: #EF4444; font-weight: 700; }
.unit-hint.warning { color: #F59E0B; font-weight: 700; }
.form-hint { font-size: 0.72rem; color: #94A3B8; margin-top: 2px; }

.form-input-modern {
    width: 100%; padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s; background: #F8FAFC;
    font-family: inherit;
}
.form-input-modern.form-input-sm { padding: 6px 10px; font-size: 0.85rem; }
.form-input-modern:focus { outline: none; border-color: #4facfe; background: white; box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1); }
textarea.form-input-modern { resize: vertical; min-height: 60px; }

.modern-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
.modern-switch input { display: none; }
.switch-slider { width: 40px; height: 22px; background: #CBD5E1; border-radius: 99px; position: relative; transition: background 0.3s ease; }
.switch-slider::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #4facfe; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(18px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.9rem; }

.modern-switch-inline { display: inline-flex; align-items: center; margin-right: 6px; vertical-align: middle; cursor: pointer; }
.modern-switch-inline input { display: none; }
.switch-slider-sm { width: 28px; height: 16px; background: #CBD5E1; border-radius: 99px; position: relative; transition: background 0.3s ease; display: inline-block; }
.switch-slider-sm::after {
    content: ''; position: absolute; top: 1px; left: 1px; width: 14px; height: 14px; background: white; border-radius: 50%;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch-inline input:checked + .switch-slider-sm { background: #4facfe; }
.modern-switch-inline input:checked + .switch-slider-sm::after { transform: translateX(12px); }

.color-input-wrapper { display: flex; align-items: center; gap: 8px; }
.color-picker-sm { width: 42px; height: 38px; border: 1px solid #E2E8F0; border-radius: 8px; padding: 2px; cursor: pointer; background: white; }
.color-hex { font-family: monospace; color: #64748B; font-size: 0.85rem; background: #F1F5F9; padding: 4px 8px; border-radius: 6px; }

/* ========================================== */
/* БУСТЕРЫ                                     */
/* ========================================== */
.boosters-editor {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.booster-editor-card {
    padding: 14px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    transition: all 0.2s;
}

.booster-editor-card:hover {
    border-color: #4facfe;
    box-shadow: 0 2px 8px rgba(79, 172, 254, 0.1);
}

.booster-editor-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.booster-emoji {
    font-size: 2rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 12px;
    border: 1px solid #E2E8F0;
    flex-shrink: 0;
}

.booster-editor-info { flex: 1; min-width: 0; }

.booster-editor-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: #0F172A;
    margin-bottom: 2px;
}

.booster-editor-desc {
    font-size: 0.72rem;
    color: #64748B;
    line-height: 1.3;
}

/* ========================================== */
/* ВКЛАДКИ УРОВНЕЙ                             */
/* ========================================== */
.levels-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 20px;
}

.level-tab-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    color: #64748B;
    font-size: 0.85rem;
    font-weight: 600;
}

.level-tab-btn:hover {
    border-color: #4facfe;
}

.level-tab-btn.active {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-color: transparent;
}

.level-tab-btn i {
    font-size: 1.3rem;
}

.level-cost-badge {
    display: inline-block;
    padding: 2px 8px;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(4px);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.level-content {
    padding-top: 8px;
}

.fade-in {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Превью цвета */
.level-color-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    width: 100%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.level-color-preview i {
    font-size: 1.2rem;
}

/* Секция параметров карты */
.map-params-section {
    padding: 16px;
    background: #F8FAFC;
    border: 1px dashed #CBD5E1;
    border-radius: 12px;
}

.subsection-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #0F172A;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.subsection-title i {
    color: #4facfe;
}

/* Индикатор баланса карты */
.map-balance-indicator {
    margin-top: 12px;
    padding: 12px;
    border-radius: 10px;
}

.map-balance-indicator.ok {
    background: #F0FDF4;
    border: 1px solid #10B981;
}

.map-balance-indicator.warning {
    background: #FFFBEB;
    border: 1px solid #F59E0B;
}

.map-balance-indicator.error {
    background: #FEF2F2;
    border: 1px solid #EF4444;
}

.balance-bar {
    display: flex;
    height: 10px;
    border-radius: 5px;
    overflow: hidden;
    background: #E2E8F0;
    margin-bottom: 8px;
}

.balance-treasures {
    background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
    transition: width 0.3s;
}

.balance-traps {
    background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
    transition: width 0.3s;
}

.balance-hints {
    background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
    transition: width 0.3s;
}

.balance-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.map-balance-indicator.ok .balance-stats { color: #065F46; }
.map-balance-indicator.warning .balance-stats { color: #78350F; }
.map-balance-indicator.error .balance-stats { color: #991B1B; }

.balance-total {
    margin-left: auto;
    font-weight: 700;
}

/* Предпросмотр сетки */
.map-preview-wrapper {
    margin-top: 16px;
}

.map-preview-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748B;
    margin-bottom: 8px;
}

.map-preview-grid {
    display: grid;
    gap: 4px;
    max-width: 240px;
    aspect-ratio: 1;
    padding: 10px;
    background: linear-gradient(135deg, #F1F5F9 0%, #E2E8F0 100%);
    border-radius: 10px;
    border: 1px solid #CBD5E1;
}

.map-preview-cell {
    aspect-ratio: 1;
    border-radius: 4px;
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(0, 0, 0, 0.3);
    font-size: 0.7rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* Секция наград */
.rewards-section {
    padding-top: 16px;
    border-top: 1px dashed #E2E8F0;
    margin-top: 16px;
}

.rewards-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.reward-editor-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 12px;
    transition: all 0.2s;
}

.reward-editor-card:hover {
    border-color: #CBD5E1;
}

.reward-editor-card.tier-rare {
    border-color: rgba(79, 172, 254, 0.3);
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.05) 0%, transparent 100%);
}

.reward-editor-card.tier-legendary {
    border-color: rgba(255, 215, 0, 0.4);
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.08) 0%, transparent 100%);
}

.reward-editor-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.reward-emoji-input-wrapper {
    flex-shrink: 0;
}

.reward-emoji-input {
    width: 56px;
    height: 56px;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    background: white;
    text-align: center;
    font-size: 1.8rem;
    cursor: pointer;
    transition: all 0.2s;
}

.reward-emoji-input:focus {
    outline: none;
    border-color: #4facfe;
    box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.15);
}

.reward-editor-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.reward-name-input {
    padding: 6px 10px;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    background: white;
    font-family: inherit;
}

.reward-name-input:focus {
    outline: none;
    border-color: #4facfe;
}

.reward-tier-selector {
    display: flex;
    gap: 4px;
}

.tier-btn {
    padding: 3px 10px;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    color: #64748B;
}

.tier-btn:hover {
    border-color: #CBD5E1;
}

.tier-btn.active.tier-common {
    background: #F1F5F9;
    border-color: #64748B;
    color: #334155;
}

.tier-btn.active.tier-rare {
    background: rgba(79, 172, 254, 0.15);
    border-color: #4facfe;
    color: #0369A1;
}

.tier-btn.active.tier-legendary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%);
    border-color: #ffd700;
    color: #B8860B;
}

.reward-editor-fields {
    padding-top: 10px;
    border-top: 1px dashed #E2E8F0;
}

/* Визуализация весов */
.weights-chart {
    padding: 12px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
}

.weights-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748B;
    margin-bottom: 10px;
}

.weight-bar-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.weight-bar-row:last-child {
    margin-bottom: 0;
}

.weight-label {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 140px;
    flex-shrink: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
}

.weight-emoji {
    font-size: 1.2rem;
}

.weight-bar {
    flex: 1;
    height: 20px;
    background: #F1F5F9;
    border-radius: 10px;
    overflow: hidden;
}

.weight-fill {
    height: 100%;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    transition: width 0.5s ease;
    min-width: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.weight-fill.tier-common {
    background: linear-gradient(90deg, #94A3B8 0%, #64748B 100%);
}

.weight-fill.tier-rare {
    background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
}

.weight-fill.tier-legendary {
    background: linear-gradient(90deg, #ffd700 0%, #ff9800 100%);
    color: #1a1a1a;
    text-shadow: none;
}

.weight-value {
    min-width: 90px;
    text-align: right;
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748B;
}

.btn-icon {
    width: 32px; height: 32px; border-radius: 6px; border: 1px solid #E2E8F0; background: white;
    color: #64748B; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
    flex-shrink: 0;
}
.btn-icon.btn-danger { color: #EF4444; border-color: #EF4444; }
.btn-icon.btn-danger:hover:not(:disabled) { background: #EF4444; color: white; }
.btn-icon:disabled { opacity: 0.3; cursor: not-allowed; }

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
    color: #4facfe;
}

.icon-btn:hover { background: #E2E8F0; transform: scale(1.15); }

.icon-btn.is-selected {
    border-color: #4facfe;
    background: #EFF6FF;
    box-shadow: 0 0 0 2px rgba(79, 172, 254, 0.2);
}

/* Предпросмотр уровней */
.levels-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
}

.level-preview-card {
    border-radius: 16px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s;
}

.level-preview-card:hover {
    transform: translateY(-2px);
}

.level-preview-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.level-preview-name {
    font-weight: 800;
    font-size: 1.1rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.level-preview-desc {
    font-size: 0.8rem;
    opacity: 0.9;
    line-height: 1.4;
    flex: 1;
}

.level-preview-stats {
    display: flex;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 0.8rem;
    font-weight: 600;
}

.level-preview-stat {
    display: flex;
    align-items: center;
    gap: 4px;
}

.level-preview-stat.danger i {
    color: rgba(255, 255, 255, 0.9);
}

.level-preview-cost {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    margin-top: 4px;
}

/* Валидация */
.validation-errors-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.validation-error-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 14px;
    background: #FEF2F2;
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 8px;
    color: #991B1B;
    font-size: 0.85rem;
    line-height: 1.4;
}

.validation-error-item i {
    color: #EF4444;
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 2px;
}

/* Кнопки */
.btn-modern {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s; padding: 8px 16px;
    font-family: inherit;
}
.btn-modern.btn-primary { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #1a1a1a; box-shadow: 0 4px 6px -1px rgba(168, 237, 234, 0.3); }
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(168, 237, 234, 0.4); }
.btn-modern.btn-secondary { background: white; color: #475569; border-color: #E2E8F0; }
.btn-modern.btn-secondary:hover:not(:disabled) { background: #F1F5F9; border-color: #CBD5E1; }
.btn-modern:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-sm { padding: 6px 12px; font-size: 0.85rem; }

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
    box-shadow: 0 8px 20px rgba(168, 237, 234, 0.3);
}

.btn-modern.btn-lg:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(168, 237, 234, 0.5);
}

@media (max-width: 768px) {
    .levels-tabs { grid-template-columns: 1fr; }
    .level-tab-btn { flex-direction: row; justify-content: space-between; padding: 12px 16px; }
    .reward-editor-header { flex-wrap: wrap; }
    .levels-preview-grid { grid-template-columns: 1fr; }
    .weight-label { min-width: 100px; font-size: 0.75rem; }
    .weight-value { min-width: 70px; font-size: 0.7rem; }
    .boosters-editor { grid-template-columns: 1fr; }
    .reward-tier-selector { flex-wrap: wrap; }
}
</style>
