<template>
    <div class="slot-admin-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-dice me-2" style="color: #4facfe;"></i> Слот-машина
                    </h2>
                    <p class="page-subtitle">Настройка символов, весов и таблицы выплат</p>
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
                        <label class="form-label-modern">Стоимость спина (бонусов)</label>
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
                    <div class="col-md-6">
                        <label class="form-label-modern">Заголовок <span class="char-counter">{{ form.title.length }}/100</span></label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.title" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Подзаголовок <span class="char-counter">{{ form.subtitle.length }}/150</span></label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.subtitle" maxlength="150">
                    </div>
                    <div class="col-12">
                        <label class="form-label-modern">Описание / Правила <span class="char-counter">{{ form.rules.length }}/4000</span></label>
                        <textarea class="form-input-modern form-input-sm" v-model="form.rules" rows="3" maxlength="4000"></textarea>
                    </div>
                </div>
            </div>

            <!-- 🆕 НАСТРОЙКА СИМВОЛОВ -->
            <div class="modern-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-icons"></i> Символы и их веса</h5>
                    <span class="badge-sector">{{ form.symbols.length }} символов</span>
                </div>
                <p class="text-muted small mb-3">
                    Чем больше <strong>вес</strong> — тем чаще символ выпадает. Сумма весов: <strong>{{ totalWeight }}</strong>
                </p>

                <div class="symbols-list">
                    <div v-for="(symbol, index) in form.symbols" :key="index" class="symbol-card" :class="`rarity-${symbol.rarity}`">
                        <div class="symbol-header" @click="symbol.edit = !symbol.edit">
                            <div class="symbol-preview">
                                <div class="symbol-emoji">{{ symbol.icon }}</div>
                                <div class="symbol-weight-bar">
                                    <div class="weight-fill" :style="{ width: getWeightPercent(symbol.weight) + '%' }"></div>
                                </div>
                            </div>
                            <div class="symbol-info">
                                <div class="symbol-name">{{ symbol.name || 'Без названия' }}</div>
                                <div class="symbol-meta">
                                    <span class="symbol-weight-tag">
                                        <i class="fa-solid fa-weight-hanging"></i> {{ symbol.weight }}
                                        <span class="weight-percent">({{ getWeightPercent(symbol.weight).toFixed(1) }}%)</span>
                                    </span>
                                    <span class="symbol-prize-tag">
                                        <i class="fa-solid fa-coins"></i> {{ symbol.prize_3x }} за 3×
                                    </span>
                                    <span class="symbol-rarity-tag" :class="`rarity-${symbol.rarity}`">
                                        {{ rarityLabel(symbol.rarity) }}
                                    </span>
                                </div>
                            </div>
                            <div class="symbol-actions">
                                <button class="btn-icon btn-sm" :class="symbol.edit ? 'btn-success' : 'btn-secondary'" @click.stop="symbol.edit = !symbol.edit">
                                    <i class="fa-solid" :class="symbol.edit ? 'fa-check' : 'fa-pen'"></i>
                                </button>
                                <button class="btn-icon btn-sm btn-danger" @click.stop="removeSymbol(index)" :disabled="form.symbols.length <= 3">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div v-if="symbol.edit" class="symbol-edit-mode fade-in">
                            <div class="row g-3">
                                <!-- ИКОНКА -->
                                <div class="col-12 col-md-3">
                                    <label class="form-label-modern">Иконка (эмодзи)</label>
                                    <div class="emoji-input-wrapper">
                                        <input type="text" v-model="symbol.icon" class="form-input-modern form-input-sm text-center emoji-input" maxlength="4">
                                        <div class="emoji-picker-dropdown">
                                            <button
                                                class="emoji-btn"
                                                v-for="emoji in emojis"
                                                :key="emoji"
                                                @click="symbol.icon = emoji"
                                                :class="{ 'is-selected': symbol.icon === emoji }"
                                            >
                                                {{ emoji }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- НАЗВАНИЕ И РЕДКОСТЬ -->
                                <div class="col-12 col-md-9">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-8">
                                            <label class="form-label-modern">Название символа</label>
                                            <input type="text" v-model="symbol.name" class="form-input-modern form-input-sm" placeholder="Например: Вишня, Семёрка">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label-modern">Редкость</label>
                                            <select v-model="symbol.rarity" class="form-input-modern form-input-sm">
                                                <option value="common">Обычный</option>
                                                <option value="rare">Редкий</option>
                                                <option value="epic">Эпический</option>
                                                <option value="legendary">Легендарный</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- ВЕС И ПРИЗЫ -->
                                <div class="col-6 col-md-3">
                                    <label class="form-label-modern">
                                        Вес (шанс)
                                        <span class="unit-hint">{{ getWeightPercent(symbol.weight).toFixed(1) }}%</span>
                                    </label>
                                    <input type="number" v-model.number="symbol.weight" class="form-input-modern form-input-sm" min="1" max="100">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label-modern">
                                        Приз за 3×
                                        <span class="unit-hint">бонусов</span>
                                    </label>
                                    <input type="number" v-model.number="symbol.prize_3x" class="form-input-modern form-input-sm" min="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label-modern">
                                        Описание
                                    </label>
                                    <input type="text" v-model="symbol.description" class="form-input-modern form-input-sm" placeholder="При выпадении...">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label-modern">Иконка результата</label>
                                    <input type="text" v-model="symbol.result_icon" class="form-input-modern form-input-sm text-center" placeholder="fa-solid fa-star">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="add-symbol-block mt-3 pt-3 border-top">
                    <button class="btn-modern btn-primary" @click="addSymbol" :disabled="form.symbols.length >= 10">
                        <i class="fa-solid fa-plus-circle"></i>
                        {{ form.symbols.length >= 10 ? 'Максимум (10)' : 'Добавить символ' }}
                    </button>
                </div>
            </div>

            <!-- 🆕 УТЕШИТЕЛЬНЫЙ ПРИЗ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-hand-holding-heart"></i> Утешительный приз (2 одинаковых)</h5>
                <p class="text-muted small mb-3">Приз за два одинаковых символа на линии выигрыша</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label-modern">Количество бонусов</label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.consolation_prize" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Название</label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.consolation_title">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Иконка</label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.consolation_icon">
                    </div>
                </div>
            </div>

            <!-- 🆕 ТАБЛИЦА ВЫПЛАТ (Предпросмотр) -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-table"></i> Предпросмотр таблицы выплат</h5>
                <p class="text-muted small mb-3">Так пользователи увидят возможные выигрыши</p>

                <div class="payout-preview">
                    <div
                        v-for="symbol in sortedSymbols"
                        :key="symbol.id"
                        class="payout-preview-row"
                        :class="`rarity-${symbol.rarity}`"
                    >
                        <div class="payout-symbols">
                            <span class="payout-symbol">{{ symbol.icon }}</span>
                            <span class="payout-symbol">{{ symbol.icon }}</span>
                            <span class="payout-symbol">{{ symbol.icon }}</span>
                        </div>
                        <div class="payout-info">
                            <span class="payout-name">{{ symbol.name }}</span>
                            <span class="payout-rarity-tag" :class="`rarity-${symbol.rarity}`">
                                {{ rarityLabel(symbol.rarity) }}
                            </span>
                        </div>
                        <div class="payout-prize">
                            +{{ symbol.prize_3x }} бонусов
                        </div>
                    </div>
                    <div class="payout-preview-row consolation">
                        <div class="payout-symbols">
                            <span class="payout-symbol">✖️</span>
                            <span class="payout-symbol">✖️</span>
                            <span class="payout-symbol">?</span>
                        </div>
                        <div class="payout-info">
                            <span class="payout-name">2 одинаковых</span>
                            <span class="payout-rarity-tag consolation">Утешение</span>
                        </div>
                        <div class="payout-prize">
                            +{{ form.consolation_prize }} бонусов
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 РАСПРЕДЕЛЕНИЕ ШАНСОВ (Визуализация) -->
            <div class="modern-card">
                <h5 class="card-title"><i class="fa-solid fa-chart-pie"></i> Распределение шансов</h5>
                <p class="text-muted small mb-3">Визуальное отображение вероятностей выпадения</p>

                <div class="probability-chart">
                    <div
                        v-for="symbol in form.symbols"
                        :key="symbol.id"
                        class="probability-bar-wrapper"
                    >
                        <div class="probability-label">
                            <span class="probability-emoji">{{ symbol.icon }}</span>
                            <span class="probability-name">{{ symbol.name }}</span>
                        </div>
                        <div class="probability-bar">
                            <div
                                class="probability-fill"
                                :class="`rarity-${symbol.rarity}`"
                                :style="{ width: getWeightPercent(symbol.weight) + '%' }"
                            >
                                <span class="probability-value">{{ getWeightPercent(symbol.weight).toFixed(1) }}%</span>
                            </div>
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
                    {{ isSaving ? 'Сохранение...' : 'Сохранить настройки слот-машины' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "SlotMachineAdminSettings",
    props: { modelValue: { type: Object, default: () => ({}) } },
    emits: ['update:modelValue'],

    data() {
        return {
            isSaving: false,
            emojis: [
                '🍒', '🍋', '🍊', '🍇', '🍉', '🍓', '🍑', '🥝', '🍎', '🍌',
                '🔔', '💎', '7️⃣', '⭐', '🌟', '💰', '👑', '🎰', '🎲', '🃏',
                '🍀', '🎯', '🏆', '💫', '🔮', '⚡', '🔥', '❄️', '🌈', '🦄',
                '🐉', '🦁', '🐺', '🦅', '🐬', '🦋', '🌸', '🌺', '🌻', '🍄'
            ],
            form: {
                can_play: true,
                move_cost: 40,
                attempts_per_period: 1,
                interval: 1,
                title: 'Слот-машина',
                subtitle: 'Крути барабаны и собирай выигрышные комбинации!',
                rules: 'Соберите 3 одинаковых символа для выигрыша! Два одинаковых — утешительный приз.',
                consolation_prize: 10,
                consolation_title: 'Утешительный приз',
                consolation_icon: 'fa-solid fa-hand-holding-heart',
                symbols: [
                    { id: 1, icon: '🍒', name: 'Вишня', weight: 40, prize_3x: 20, rarity: 'common', description: 'Сладкая награда', result_icon: 'fa-solid fa-star', edit: false },
                    { id: 2, icon: '🍋', name: 'Лимон', weight: 25, prize_3x: 30, rarity: 'common', description: 'Кисленький бонус', result_icon: 'fa-solid fa-star', edit: false },
                    { id: 3, icon: '🍊', name: 'Апельсин', weight: 15, prize_3x: 50, rarity: 'common', description: 'Сочный приз', result_icon: 'fa-solid fa-star', edit: false },
                    { id: 4, icon: '🔔', name: 'Колокол', weight: 10, prize_3x: 150, rarity: 'rare', description: 'Звонкая удача', result_icon: 'fa-solid fa-bell', edit: false },
                    { id: 5, icon: '💎', name: 'Алмаз', weight: 7, prize_3x: 300, rarity: 'epic', description: 'Сияющий приз', result_icon: 'fa-solid fa-gem', edit: false },
                    { id: 6, icon: '7️⃣', name: 'Семёрка', weight: 3, prize_3x: 700, rarity: 'legendary', description: 'ДЖЕКПОТ!', result_icon: 'fa-solid fa-crown', edit: false },
                ]
            }
        };
    },

    computed: {
        totalWeight() {
            return this.form.symbols.reduce((sum, s) => sum + (s.weight || 0), 0);
        },

        sortedSymbols() {
            return [...this.form.symbols].sort((a, b) => b.prize_3x - a.prize_3x);
        }
    },

    watch: {
        form: {
            handler: function (newValue) {
                const cleanForm = JSON.parse(JSON.stringify(newValue));
                cleanForm.symbols = cleanForm.symbols.map(item => {
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
                    this.form.title = newValue.title ?? this.form.title;
                    this.form.subtitle = newValue.subtitle ?? this.form.subtitle;
                    this.form.rules = newValue.rules ?? this.form.rules;
                    this.form.consolation_prize = newValue.consolation_prize ?? this.form.consolation_prize;
                    this.form.consolation_title = newValue.consolation_title ?? this.form.consolation_title;
                    this.form.consolation_icon = newValue.consolation_icon ?? this.form.consolation_icon;

                    if (newValue.symbols && Array.isArray(newValue.symbols) && newValue.symbols.length > 0) {
                        this.form.symbols = newValue.symbols.map(item => ({
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
        rarityLabel(rarity) {
            const labels = { common: 'Обычный', rare: 'Редкий', epic: 'Эпический', legendary: 'Легендарный' };
            return labels[rarity] || 'Обычный';
        },

        getWeightPercent(weight) {
            if (this.totalWeight === 0) return 0;
            return (weight / this.totalWeight) * 100;
        },

        addSymbol() {
            if (this.form.symbols.length >= 10) return;
            const newId = this.form.symbols.length > 0 ? Math.max(...this.form.symbols.map(s => s.id || 0)) + 1 : 1;

            this.form.symbols.push({
                id: newId,
                icon: '🎁',
                name: `Символ #${newId}`,
                weight: 10,
                prize_3x: 50,
                rarity: 'common',
                description: 'Новый символ',
                result_icon: 'fa-solid fa-star',
                edit: true
            });
        },

        removeSymbol(index) {
            if (this.form.symbols.length <= 3) return;
            if (confirm(`Удалить символ "${this.form.symbols[index].name}"?`)) {
                this.form.symbols.splice(index, 1);
                this.form.symbols.forEach((item, idx) => { item.id = idx + 1; });
            }
        },

        async saveSettings() {
            if (this.totalWeight === 0) {
                this.$notify?.({ title: 'Ошибка', text: 'Сумма весов не может быть 0', type: 'error' });
                return;
            }

            if (this.form.symbols.length < 3) {
                this.$notify?.({ title: 'Ошибка', text: 'Минимум 3 символа', type: 'error' });
                return;
            }

            // Проверка на пустые поля
            for (const symbol of this.form.symbols) {
                if (!symbol.icon || !symbol.name) {
                    this.$notify?.({ title: 'Ошибка', text: 'У всех символов должны быть иконка и название', type: 'error' });
                    return;
                }
                if (!symbol.weight || symbol.weight <= 0) {
                    this.$notify?.({ title: 'Ошибка', text: `Вес символа "${symbol.name}" должен быть > 0`, type: 'error' });
                    return;
                }
            }

            this.isSaving = true;

            try {
                const cleanForm = JSON.parse(JSON.stringify(this.form));
                cleanForm.symbols = cleanForm.symbols.map(item => {
                    const { edit, ...rest } = item;
                    return rest;
                });

                const response = await axios.put('/admin/tenant-settings/slot-machine', {
                    slot_machine: cleanForm
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
.slot-admin-page {
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
.card-title i { color: #4facfe; }

.form-label-modern { font-size: 0.8rem; font-weight: 600; color: #64748B; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; }
.char-counter { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.unit-hint { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.form-input-modern {
    width: 100%; padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s; background: #F8FAFC;
}
.form-input-modern.form-input-sm { padding: 6px 10px; font-size: 0.85rem; }
.form-input-modern:focus { outline: none; border-color: #4facfe; background: white; box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1); }
.form-input-modern:disabled { background: #F1F5F9; color: #94A3B8; }

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

.badge-sector { padding: 4px 10px; background: #EFF6FF; color: #4facfe; border-radius: 99px; font-weight: 700; font-size: 0.8rem; }

/* Символы */
.symbols-list { display: flex; flex-direction: column; gap: 10px; }

.symbol-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}

.symbol-card:hover { border-color: #CBD5E1; }

.symbol-card.rarity-legendary {
    border-color: rgba(255, 215, 0, 0.3);
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, transparent 100%);
}
.symbol-card.rarity-epic {
    border-color: rgba(111, 66, 193, 0.2);
    background: linear-gradient(135deg, rgba(111, 66, 193, 0.03) 0%, transparent 100%);
}
.symbol-card.rarity-rare {
    border-color: rgba(13, 110, 253, 0.2);
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.03) 0%, transparent 100%);
}

.symbol-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.symbol-header:hover { background: #F1F5F9; }

.symbol-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.symbol-emoji {
    font-size: 2rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
}

.symbol-weight-bar {
    width: 60px;
    height: 6px;
    background: #E2E8F0;
    border-radius: 3px;
    overflow: hidden;
}

.weight-fill {
    height: 100%;
    background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.symbol-info { flex: 1; min-width: 0; }

.symbol-name {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.95rem;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.symbol-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.symbol-weight-tag {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #64748B;
    font-weight: 600;
}

.weight-percent {
    color: #4facfe;
}

.symbol-prize-tag {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #10B981;
    font-weight: 600;
}

.symbol-rarity-tag {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}

.symbol-rarity-tag.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.symbol-rarity-tag.rarity-rare { background: rgba(13, 110, 253, 0.15); color: #0d6efd; }
.symbol-rarity-tag.rarity-epic { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.symbol-rarity-tag.rarity-legendary { background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%); color: #b8860b; }

.symbol-actions { display: flex; gap: 6px; }

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
.btn-icon:disabled { opacity: 0.4; cursor: not-allowed; }

.symbol-edit-mode {
    padding: 0 12px 12px 12px;
    border-top: 1px dashed #E2E8F0;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Эмодзи пикер */
.emoji-input-wrapper { position: relative; }

.emoji-input {
    font-size: 1.5rem !important;
    padding: 4px !important;
}

.emoji-picker-dropdown {
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
    scrollbar-width: thin;
}

.emoji-btn {
    width: 100%;
    aspect-ratio: 1;
    border: 1px solid transparent;
    background: #F8FAFC;
    font-size: 1.3rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.emoji-btn:hover { background: #E2E8F0; transform: scale(1.15); }

.emoji-btn.is-selected {
    border-color: #4facfe;
    background: #EFF6FF;
    box-shadow: 0 0 0 2px rgba(79, 172, 254, 0.2);
}

.add-symbol-block {
    display: flex;
    justify-content: center;
}

/* Таблица выплат */
.payout-preview {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.payout-preview-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    transition: all 0.2s;
}

.payout-preview-row:hover { transform: translateX(4px); }

.payout-preview-row.rarity-legendary {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.08) 0%, transparent 100%);
    border-color: rgba(255, 215, 0, 0.3);
}
.payout-preview-row.rarity-epic {
    background: linear-gradient(135deg, rgba(111, 66, 193, 0.05) 0%, transparent 100%);
    border-color: rgba(111, 66, 193, 0.2);
}
.payout-preview-row.rarity-rare {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, transparent 100%);
    border-color: rgba(13, 110, 253, 0.2);
}
.payout-preview-row.consolation {
    background: rgba(79, 172, 254, 0.05);
    border-color: rgba(79, 172, 254, 0.2);
}

.payout-symbols {
    display: flex;
    gap: 4px;
}

.payout-symbol {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
}

.payout-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.payout-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #0F172A;
}

.payout-rarity-tag {
    display: inline-flex;
    align-self: flex-start;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
}

.payout-rarity-tag.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.payout-rarity-tag.rarity-rare { background: rgba(13, 110, 253, 0.15); color: #0d6efd; }
.payout-rarity-tag.rarity-epic { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.payout-rarity-tag.rarity-legendary { background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%); color: #b8860b; }
.payout-rarity-tag.consolation { background: rgba(79, 172, 254, 0.15); color: #4facfe; }

.payout-prize {
    font-weight: 800;
    font-size: 1rem;
    color: #10B981;
    white-space: nowrap;
}

/* Распределение шансов */
.probability-chart {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.probability-bar-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.probability-label {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 140px;
    flex-shrink: 0;
}

.probability-emoji {
    font-size: 1.5rem;
}

.probability-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.probability-bar {
    flex: 1;
    height: 28px;
    background: #F1F5F9;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
}

.probability-fill {
    height: 100%;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 10px;
    transition: width 0.5s ease;
    min-width: 50px;
}

.probability-fill.rarity-common { background: linear-gradient(90deg, #6c757d 0%, #adb5bd 100%); }
.probability-fill.rarity-rare { background: linear-gradient(90deg, #0d6efd 0%, #6610f2 100%); }
.probability-fill.rarity-epic { background: linear-gradient(90deg, #6f42c1 0%, #d63384 100%); }
.probability-fill.rarity-legendary { background: linear-gradient(90deg, #ffd700 0%, #ff9800 100%); }

.probability-value {
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.probability-fill.rarity-legendary .probability-value {
    color: #1a1a1a;
    text-shadow: none;
}

/* Кнопки */
.btn-modern {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s; padding: 8px 16px;
}
.btn-modern.btn-primary { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; box-shadow: 0 4px 6px -1px rgba(79, 172, 254, 0.2); }
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(79, 172, 254, 0.3); }
.btn-modern.btn-secondary { background: white; color: #475569; border-color: #E2E8F0; }
.btn-modern.btn-secondary:hover:not(:disabled) { background: #F1F5F9; }
.btn-modern:disabled { opacity: 0.6; cursor: not-allowed; }

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
    box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
}

.btn-modern.btn-lg:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(79, 172, 254, 0.4);
}

@media (max-width: 768px) {
    .symbol-header { flex-wrap: wrap; }
    .symbol-actions { width: 100%; justify-content: flex-end; margin-top: 4px; }
    .probability-label { min-width: 100px; }
    .probability-name { font-size: 0.75rem; }
    .payout-preview-row { flex-wrap: wrap; }
    .payout-prize { width: 100%; text-align: right; margin-top: 4px; }
}
</style>
