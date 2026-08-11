<template>
    <div class="guess-admin-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-hashtag me-2" style="color: #43e97b;"></i> Угадай число
                    </h2>
                    <p class="page-subtitle">Настройка режимов игры, наград и диапазона чисел</p>
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
                            Множитель серии побед
                            <span class="unit-hint">×{{ form.streak_multiplier }}</span>
                        </label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.streak_multiplier" min="1" max="5" step="0.1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">
                            Мин. серия для бонуса
                            <span class="unit-hint">побед подряд</span>
                        </label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.streak_min_count" min="1" max="20">
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

            <!-- 🆕 НАСТРОЙКА РЕЖИМОВ (вкладки) -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-gamepad"></i> Режимы игры</h5>
                <p class="text-muted small mb-3">Каждый режим имеет свои ставки, диапазон чисел и таблицу наград</p>

                <!-- Вкладки режимов -->
                <div class="modes-tabs">
                    <button
                        v-for="mode in modeKeys"
                        :key="mode"
                        class="mode-tab-btn"
                        :class="{ active: activeMode === mode }"
                        @click="activeMode = mode"
                    >
                        <i :class="modeIcons[mode]"></i>
                        <span>{{ modeLabels[mode] }}</span>
                        <span class="mode-cost-badge">{{ form.modes[mode].cost }} 💰</span>
                    </button>
                </div>

                <!-- Содержимое активной вкладки -->
                <div class="mode-content fade-in">
                    <div class="row g-3">
                        <!-- Название и описание -->
                        <div class="col-md-6">
                            <label class="form-label-modern">Название режима</label>
                            <input type="text" class="form-input-modern form-input-sm" v-model="form.modes[activeMode].title">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-modern">Иконка (FontAwesome)</label>
                            <input type="text" class="form-input-modern form-input-sm" v-model="modeIcons[activeMode]">
                        </div>
                        <div class="col-12">
                            <label class="form-label-modern">Описание</label>
                            <textarea class="form-input-modern form-input-sm" v-model="form.modes[activeMode].description" rows="2" maxlength="200"></textarea>
                        </div>

                        <!-- Стоимость и диапазон -->
                        <div class="col-md-3">
                            <label class="form-label-modern">
                                Стоимость игры
                                <span class="unit-hint">бонусов</span>
                            </label>
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.modes[activeMode].cost" min="1" max="10000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-modern">
                                Мин. число
                                <span class="unit-hint">диапазон от</span>
                            </label>
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.modes[activeMode].min" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-modern">
                                Макс. число
                                <span class="unit-hint">диапазон до</span>
                            </label>
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.modes[activeMode].max" min="2">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-modern">
                                Макс. попыток
                            </label>
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.modes[activeMode].maxAttempts" min="1" max="50">
                        </div>

                        <!-- Цвет режима -->
                        <div class="col-md-4">
                            <label class="form-label-modern">Основной цвет</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="form.modes[activeMode].color" class="color-picker-sm">
                                <span class="color-hex">{{ form.modes[activeMode].color }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-modern">Вторичный цвет</label>
                            <div class="color-input-wrapper">
                                <input type="color" v-model="form.modes[activeMode].color_secondary" class="color-picker-sm">
                                <span class="color-hex">{{ form.modes[activeMode].color_secondary }}</span>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="mode-color-preview" :style="modePreviewStyle">
                                <i :class="modeIcons[activeMode]"></i>
                                <span>{{ modeLabels[activeMode] }}</span>
                            </div>
                        </div>

                        <!-- Статистика режима -->
                        <div class="col-12">
                            <div class="mode-stats-bar">
                                <div class="mode-stat">
                                    <i class="fa-solid fa-dice"></i>
                                    <span>Диапазон: <strong>{{ form.modes[activeMode].min }}–{{ form.modes[activeMode].max }}</strong></span>
                                </div>
                                <div class="mode-stat">
                                    <i class="fa-solid fa-hand-pointer"></i>
                                    <span>Попыток: <strong>{{ form.modes[activeMode].maxAttempts }}</strong></span>
                                </div>
                                <div class="mode-stat">
                                    <i class="fa-solid fa-ticket"></i>
                                    <span>Ставка: <strong>{{ form.modes[activeMode].cost }} бонусов</strong></span>
                                </div>
                                <div class="mode-stat">
                                    <i class="fa-solid fa-trophy"></i>
                                    <span>Макс. приз: <strong>{{ getMaxReward(activeMode) }} бонусов</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ТАБЛИЦА НАГРАД -->
                    <div class="rewards-section mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="rewards-title">
                                <i class="fa-solid fa-list-ol"></i>
                                Таблица наград
                            </h6>
                            <span class="rewards-count">{{ (form.modes[activeMode].rewards || []).length }} уровней</span>
                        </div>
                        <p class="text-muted small mb-3">
                            Настройте вознаграждение в зависимости от количества попыток. Чем меньше попыток — тем больше приз!
                        </p>

                        <div class="rewards-list">
                            <div
                                v-for="(reward, rIdx) in form.modes[activeMode].rewards"
                                :key="rIdx"
                                class="reward-row-card"
                            >
                                <div class="reward-row-header">
                                    <div class="reward-row-badge">#{{ rIdx + 1 }}</div>
                                    <div class="reward-row-preview">
                                        <span class="reward-attempts-text">
                                            <i class="fa-solid fa-hand-pointer"></i>
                                            {{ formatAttemptsRange(reward) }}
                                        </span>
                                        <span class="reward-arrow">→</span>
                                        <span class="reward-value-text">
                                            <i class="fa-solid fa-coins"></i>
                                            {{ reward.value }} бонусов
                                        </span>
                                    </div>
                                    <div class="reward-row-actions">
                                        <button
                                            class="btn-icon btn-sm btn-secondary"
                                            @click="moveReward(rIdx, -1)"
                                            :disabled="rIdx === 0"
                                            title="Вверх"
                                        >
                                            <i class="fa-solid fa-arrow-up"></i>
                                        </button>
                                        <button
                                            class="btn-icon btn-sm btn-secondary"
                                            @click="moveReward(rIdx, 1)"
                                            :disabled="rIdx === form.modes[activeMode].rewards.length - 1"
                                            title="Вниз"
                                        >
                                            <i class="fa-solid fa-arrow-down"></i>
                                        </button>
                                        <button class="btn-icon btn-sm btn-danger" @click="removeReward(rIdx)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="reward-row-fields">
                                    <div class="row g-2">
                                        <div class="col-6 col-md-4">
                                            <label class="form-label-modern">Попыток от</label>
                                            <input type="number" class="form-input-modern form-input-sm" v-model.number="reward.minAttempts" min="1">
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="form-label-modern">Попыток до</label>
                                            <input type="number" class="form-input-modern form-input-sm" v-model.number="reward.maxAttempts" min="1">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label-modern">Награда (бонусов)</label>
                                            <input type="number" class="form-input-modern form-input-sm" v-model.number="reward.value" min="1">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label-modern">Подпись (необязательно)</label>
                                            <input type="text" class="form-input-modern form-input-sm" v-model="reward.label" placeholder="Например: 1 попытка, 2-3 попытки">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button class="btn-modern btn-secondary btn-sm mt-2" @click="addReward">
                            <i class="fa-solid fa-plus"></i>
                            Добавить уровень награды
                        </button>
                    </div>
                </div>
            </div>

            <!-- 🆕 ПРЕДПРОСМОТР ВСЕХ РЕЖИМОВ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-eye"></i> Предпросмотр режимов</h5>
                <p class="text-muted small mb-3">Так пользователи увидят выбор режимов в игре</p>

                <div class="modes-preview-grid">
                    <div
                        v-for="mode in modeKeys"
                        :key="mode"
                        class="mode-preview-card"
                        :style="{
                            background: `linear-gradient(135deg, ${form.modes[mode].color} 0%, ${form.modes[mode].color_secondary} 100%)`
                        }"
                    >
                        <div class="mode-preview-icon">
                            <i :class="modeIcons[mode]"></i>
                        </div>
                        <div class="mode-preview-info">
                            <div class="mode-preview-title">{{ form.modes[mode].title }}</div>
                            <div class="mode-preview-desc">{{ form.modes[mode].description }}</div>
                        </div>
                        <div class="mode-preview-footer">
                            <span class="mode-preview-cost">
                                <i class="fa-solid fa-ticket"></i>
                                {{ form.modes[mode].cost }} бонусов
                            </span>
                            <span class="mode-preview-attempts">
                                <i class="fa-solid fa-hand-pointer"></i>
                                {{ form.modes[mode].maxAttempts }} попыток
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 СВОДНАЯ ТАБЛИЦА НАГРАД -->
            <div class="modern-card">
                <h5 class="card-title"><i class="fa-solid fa-table"></i> Сводка по всем режимам</h5>

                <div v-for="mode in modeKeys" :key="mode" class="summary-mode-block">
                    <div class="summary-mode-header" :style="{ borderLeftColor: form.modes[mode].color }">
                        <i :class="modeIcons[mode]" :style="{ color: form.modes[mode].color }"></i>
                        <span class="summary-mode-name">{{ form.modes[mode].title }}</span>
                        <span class="summary-mode-cost">{{ form.modes[mode].cost }} 💰</span>
                    </div>
                    <div class="summary-rewards-list">
                        <div v-for="(r, idx) in form.modes[mode].rewards" :key="idx" class="summary-reward-row">
                            <span class="summary-attempts">{{ formatAttemptsRange(r) }}</span>
                            <span class="summary-arrow">→</span>
                            <span class="summary-value">{{ r.value }} бонусов</span>
                        </div>
                        <div v-if="!form.modes[mode].rewards || form.modes[mode].rewards.length === 0" class="summary-empty">
                            Награды не настроены
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 ВАЛИДАЦИЯ -->
            <div class="modern-card mt-4" v-if="validationErrors.length > 0">
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
    name: "GuessNumberAdminSettings",
    props: { modelValue: { type: Object, default: () => ({}) } },
    emits: ['update:modelValue'],

    data() {
        return {
            isSaving: false,
            activeMode: 'classic',
            modeKeys: ['classic', 'jackpot', 'challenge'],
            modeLabels: {
                classic: 'Классика',
                jackpot: 'Джекпот',
                challenge: 'Вызов'
            },
            modeIcons: {
                classic: 'fa-solid fa-dice',
                jackpot: 'fa-solid fa-crown',
                challenge: 'fa-solid fa-fire'
            },
            form: {
                can_play: true,
                title: 'Угадай число',
                subtitle: 'Угадай число за минимум попыток!',
                rules: '',
                streak_multiplier: 1.5,
                streak_min_count: 3,
                modes: {
                    classic: {
                        title: 'Классика',
                        description: 'Угадай число от 1 до 100. Чем меньше попыток — тем больше приз!',
                        cost: 10,
                        min: 1,
                        max: 100,
                        maxAttempts: 10,
                        color: '#43e97b',
                        color_secondary: '#38f9d7',
                        rewards: [
                            { minAttempts: 1, maxAttempts: 1, value: 500, label: '1 попытка' },
                            { minAttempts: 2, maxAttempts: 3, value: 200, label: '2-3 попытки' },
                            { minAttempts: 4, maxAttempts: 5, value: 100, label: '4-5 попыток' },
                            { minAttempts: 6, maxAttempts: 7, value: 50, label: '6-7 попыток' },
                            { minAttempts: 8, maxAttempts: 10, value: 20, label: '8+ попыток' }
                        ]
                    },
                    jackpot: {
                        title: 'Джекпот',
                        description: 'Число от 1 до 1000. Высокие ставки — высокие выигрыши!',
                        cost: 50,
                        min: 1,
                        max: 1000,
                        maxAttempts: 15,
                        color: '#ffd700',
                        color_secondary: '#ff9800',
                        rewards: [
                            { minAttempts: 1, maxAttempts: 3, value: 5000, label: '1-3 попытки' },
                            { minAttempts: 4, maxAttempts: 7, value: 2000, label: '4-7 попыток' },
                            { minAttempts: 8, maxAttempts: 12, value: 500, label: '8-12 попыток' },
                            { minAttempts: 13, maxAttempts: 15, value: 100, label: '13+ попыток' }
                        ]
                    },
                    challenge: {
                        title: 'Вызов',
                        description: 'Только 3 попытки, чтобы угадать число. Награда за мастерство!',
                        cost: 30,
                        min: 1,
                        max: 100,
                        maxAttempts: 3,
                        color: '#fa709a',
                        color_secondary: '#fee140',
                        rewards: [
                            { minAttempts: 1, maxAttempts: 1, value: 1000, label: '1 попытка' },
                            { minAttempts: 2, maxAttempts: 2, value: 500, label: '2 попытки' },
                            { minAttempts: 3, maxAttempts: 3, value: 300, label: '3 попытки' }
                        ]
                    }
                }
            }
        };
    },

    computed: {
        modePreviewStyle() {
            const mode = this.form.modes[this.activeMode];
            return {
                background: `linear-gradient(135deg, ${mode.color} 0%, ${mode.color_secondary} 100%)`
            };
        },

        validationErrors() {
            const errors = [];

            if (!this.form.title || !this.form.title.trim()) {
                errors.push('Укажите заголовок игры');
            }

            for (const mode of this.modeKeys) {
                const m = this.form.modes[mode];
                const name = this.modeLabels[mode];

                if (!m.title || !m.title.trim()) {
                    errors.push(`Режим "${name}": не указано название`);
                }
                if (!m.cost || m.cost <= 0) {
                    errors.push(`Режим "${name}": стоимость должна быть больше 0`);
                }
                if (!m.min || m.min < 1) {
                    errors.push(`Режим "${name}": мин. число должно быть ≥ 1`);
                }
                if (!m.max || m.max <= m.min) {
                    errors.push(`Режим "${name}": макс. число должно быть больше мин.`);
                }
                if (!m.maxAttempts || m.maxAttempts < 1) {
                    errors.push(`Режим "${name}": макс. попыток должно быть ≥ 1`);
                }
                if (!m.rewards || m.rewards.length === 0) {
                    errors.push(`Режим "${name}": добавьте хотя бы одну награду`);
                } else {
                    m.rewards.forEach((r, idx) => {
                        if (!r.minAttempts || r.minAttempts < 1) {
                            errors.push(`Режим "${name}", награда #${idx + 1}: "от" должно быть ≥ 1`);
                        }
                        if (!r.maxAttempts || r.maxAttempts < r.minAttempts) {
                            errors.push(`Режим "${name}", награда #${idx + 1}: "до" должно быть ≥ "от"`);
                        }
                        if (!r.value || r.value <= 0) {
                            errors.push(`Режим "${name}", награда #${idx + 1}: значение должно быть > 0`);
                        }
                    });
                }
            }

            if (this.form.streak_multiplier < 1 || this.form.streak_multiplier > 5) {
                errors.push('Множитель серии должен быть от 1 до 5');
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
                    this.form.streak_multiplier = newValue.streak_multiplier ?? this.form.streak_multiplier;
                    this.form.streak_min_count = newValue.streak_min_count ?? this.form.streak_min_count;

                    if (newValue.modes) {
                        for (const mode of this.modeKeys) {
                            if (newValue.modes[mode]) {
                                this.form.modes[mode] = { ...this.form.modes[mode], ...newValue.modes[mode] };
                            }
                        }
                    }
                }
            },
            immediate: true
        }
    },

    methods: {
        formatAttemptsRange(reward) {
            if (reward.label && reward.label.trim()) return reward.label;
            if (reward.minAttempts === reward.maxAttempts) {
                return `${reward.minAttempts} ${this.pluralize(reward.minAttempts, 'попытка', 'попытки', 'попыток')}`;
            }
            return `${reward.minAttempts}–${reward.maxAttempts} попыток`;
        },

        pluralize(n, one, two, five) {
            let abs = Math.abs(n) % 100;
            const n1 = abs % 10;
            if (abs > 10 && abs < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },

        getMaxReward(mode) {
            const rewards = this.form.modes[mode].rewards || [];
            if (rewards.length === 0) return 0;
            return Math.max(...rewards.map(r => r.value || 0));
        },

        addReward() {
            const rewards = this.form.modes[this.activeMode].rewards;
            const maxAttempt = rewards.length > 0
                ? Math.max(...rewards.map(r => r.maxAttempts || 1))
                : 0;
            const maxModeAttempts = this.form.modes[this.activeMode].maxAttempts;

            const newMin = Math.min(maxAttempt + 1, maxModeAttempts);
            const newMax = Math.min(maxAttempt + 2, maxModeAttempts);

            rewards.push({
                minAttempts: newMin,
                maxAttempts: newMax,
                value: 10,
                label: ''
            });
        },

        removeReward(index) {
            if (confirm('Удалить этот уровень награды?')) {
                this.form.modes[this.activeMode].rewards.splice(index, 1);
            }
        },

        moveReward(index, direction) {
            const rewards = this.form.modes[this.activeMode].rewards;
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= rewards.length) return;

            const temp = rewards[index];
            rewards[index] = rewards[newIndex];
            rewards[newIndex] = temp;
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

                const response = await axios.put('/admin/tenant-settings/guess-number', {
                    guess_number: cleanForm
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
.guess-admin-page {
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
.card-title i { color: #43e97b; }
.card-title.text-danger { color: #EF4444; }
.card-title.text-danger i { color: #EF4444; }

.form-label-modern { font-size: 0.8rem; font-weight: 600; color: #64748B; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; }
.char-counter { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.unit-hint { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }

.form-input-modern {
    width: 100%; padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s; background: #F8FAFC;
    font-family: inherit;
}
.form-input-modern.form-input-sm { padding: 6px 10px; font-size: 0.85rem; }
.form-input-modern:focus { outline: none; border-color: #43e97b; background: white; box-shadow: 0 0 0 3px rgba(67, 233, 123, 0.1); }
textarea.form-input-modern { resize: vertical; min-height: 60px; }

.modern-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
.modern-switch input { display: none; }
.switch-slider { width: 40px; height: 22px; background: #CBD5E1; border-radius: 99px; position: relative; transition: background 0.3s ease; }
.switch-slider::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #43e97b; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(18px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.9rem; }

.color-input-wrapper { display: flex; align-items: center; gap: 8px; }
.color-picker-sm { width: 42px; height: 38px; border: 1px solid #E2E8F0; border-radius: 8px; padding: 2px; cursor: pointer; background: white; }
.color-hex { font-family: monospace; color: #64748B; font-size: 0.85rem; background: #F1F5F9; padding: 4px 8px; border-radius: 6px; }

/* ========================================== */
/* ВКЛАДКИ РЕЖИМОВ                             */
/* ========================================== */
.modes-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 20px;
}

.mode-tab-btn {
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

.mode-tab-btn:hover {
    border-color: #43e97b;
    color: #43e97b;
}

.mode-tab-btn.active {
    background: linear-gradient(135deg, rgba(67, 233, 123, 0.08) 0%, rgba(56, 249, 215, 0.08) 100%);
    border-color: #43e97b;
    color: #0F172A;
    box-shadow: 0 4px 12px rgba(67, 233, 123, 0.2);
}

.mode-tab-btn i {
    font-size: 1.3rem;
    color: #43e97b;
}

.mode-tab-btn.active i {
    color: #059669;
}

.mode-cost-badge {
    display: inline-block;
    padding: 2px 8px;
    background: rgba(67, 233, 123, 0.15);
    color: #059669;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.mode-content {
    padding-top: 8px;
}

.fade-in {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Превью цвета режима */
.mode-color-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 0.9rem;
    width: 100%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.mode-color-preview i {
    font-size: 1.2rem;
}

/* Статистика режима */
.mode-stats-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 16px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
}

.mode-stat {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #334155;
}

.mode-stat i {
    color: #43e97b;
    font-size: 0.95rem;
}

.mode-stat strong {
    color: #0F172A;
}

/* ========================================== */
/* ТАБЛИЦА НАГРАД                              */
/* ========================================== */
.rewards-section {
    padding-top: 16px;
    border-top: 1px dashed #E2E8F0;
}

.rewards-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #0F172A;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rewards-title i {
    color: #43e97b;
}

.rewards-count {
    padding: 3px 10px;
    background: #ECFDF5;
    color: #059669;
    border-radius: 99px;
    font-size: 0.75rem;
    font-weight: 700;
}

.rewards-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.reward-row-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}

.reward-row-card:hover {
    border-color: #CBD5E1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.reward-row-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: white;
    border-bottom: 1px dashed #E2E8F0;
}

.reward-row-badge {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.reward-row-preview {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.reward-attempts-text {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    color: #334155;
}

.reward-attempts-text i {
    color: #64748B;
}

.reward-arrow {
    color: #CBD5E1;
    font-size: 0.85rem;
}

.reward-value-text {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    font-size: 0.9rem;
    color: #059669;
}

.reward-value-text i {
    color: #ffd700;
}

.reward-row-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}

.btn-icon {
    width: 28px; height: 28px; border-radius: 6px; border: 1px solid #E2E8F0; background: white;
    color: #64748B; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;
}
.btn-icon.btn-danger { color: #EF4444; border-color: #EF4444; }
.btn-icon.btn-danger:hover { background: #EF4444; color: white; }
.btn-icon.btn-secondary { color: #64748B; }
.btn-icon.btn-secondary:hover:not(:disabled) { background: #F1F5F9; color: #334155; }
.btn-icon:disabled { opacity: 0.3; cursor: not-allowed; }

.reward-row-fields {
    padding: 12px;
}

/* ========================================== */
/* ПРЕДПРОСМОТР РЕЖИМОВ                        */
/* ========================================== */
.modes-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
}

.mode-preview-card {
    border-radius: 16px;
    padding: 20px;
    color: white;
    display: flex;
    flex-direction: column;
    gap: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s;
}

.mode-preview-card:hover {
    transform: translateY(-2px);
}

.mode-preview-icon {
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

.mode-preview-info {
    flex: 1;
}

.mode-preview-title {
    font-weight: 800;
    font-size: 1.1rem;
    margin-bottom: 4px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.mode-preview-desc {
    font-size: 0.8rem;
    opacity: 0.9;
    line-height: 1.4;
}

.mode-preview-footer {
    display: flex;
    gap: 12px;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 0.75rem;
    font-weight: 600;
}

.mode-preview-cost,
.mode-preview-attempts {
    display: flex;
    align-items: center;
    gap: 4px;
    opacity: 0.95;
}

/* ========================================== */
/* СВОДНАЯ ТАБЛИЦА                             */
/* ========================================== */
.summary-mode-block {
    margin-bottom: 16px;
}

.summary-mode-block:last-child {
    margin-bottom: 0;
}

.summary-mode-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #F8FAFC;
    border-left: 4px solid;
    border-radius: 8px;
    margin-bottom: 8px;
}

.summary-mode-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: #0F172A;
    flex: 1;
}

.summary-mode-cost {
    padding: 3px 10px;
    background: rgba(67, 233, 123, 0.15);
    color: #059669;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}

.summary-rewards-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding-left: 20px;
}

.summary-reward-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 12px;
    background: white;
    border: 1px solid #F1F5F9;
    border-radius: 8px;
    font-size: 0.85rem;
}

.summary-attempts {
    color: #64748B;
    font-weight: 600;
    min-width: 120px;
}

.summary-arrow {
    color: #CBD5E1;
}

.summary-value {
    color: #059669;
    font-weight: 700;
}

.summary-empty {
    padding: 10px;
    text-align: center;
    color: #94A3B8;
    font-size: 0.85rem;
    font-style: italic;
}

/* ========================================== */
/* ВАЛИДАЦИЯ                                   */
/* ========================================== */
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

/* ========================================== */
/* КНОПКИ                                      */
/* ========================================== */
.btn-modern {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s; padding: 8px 16px;
    font-family: inherit;
}
.btn-modern.btn-primary { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; box-shadow: 0 4px 6px -1px rgba(67, 233, 123, 0.3); }
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(67, 233, 123, 0.4); }
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
    box-shadow: 0 8px 20px rgba(67, 233, 123, 0.3);
}

.btn-modern.btn-lg:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(67, 233, 123, 0.4);
}

@media (max-width: 768px) {
    .modes-tabs { grid-template-columns: 1fr; }
    .mode-tab-btn { flex-direction: row; justify-content: space-between; padding: 12px 16px; }
    .reward-row-header { flex-wrap: wrap; }
    .reward-row-actions { width: 100%; justify-content: flex-end; margin-top: 4px; }
    .modes-preview-grid { grid-template-columns: 1fr; }
    .mode-stats-bar { flex-direction: column; gap: 8px; }
    .summary-attempts { min-width: auto; }
}
</style>
