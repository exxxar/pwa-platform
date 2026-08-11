<template>
    <div class="card-game-admin-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-layer-group me-2" style="color: #667eea;"></i> Карточная игра
                    </h2>
                    <p class="page-subtitle">Настройка призов, правил и логики игры</p>
                </div>
            </div>

            <!-- 🆕 КОМПАКТНЫЕ: Основные настройки -->
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
                        <label class="form-label-modern">Период обновления попыток</label>
                        <select class="form-input-modern form-input-sm" v-model="form.interval">
                            <option v-for="item in intervals" :key="item.value" :value="item.value">
                                {{ item.title }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Попыток за период</label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.attempts_per_period" min="1" max="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Колонок в сетке</label>
                        <select class="form-input-modern form-input-sm" v-model.number="form.grid_columns">
                            <option :value="3">3 колонки</option>
                            <option :value="4">4 колонки</option>
                            <option :value="5">5 колонок</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Строк в сетке</label>
                        <select class="form-input-modern form-input-sm" v-model.number="form.grid_rows">
                            <option :value="2">2 ряда</option>
                            <option :value="3">3 ряда</option>
                            <option :value="4">4 ряда</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Всего карт</label>
                        <input type="text" class="form-input-modern form-input-sm" :value="`${form.grid_columns * form.grid_rows} карт`" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Правила игры <span class="char-counter" v-if="form.rules">{{ form.rules.length }}/4000</span></label>
                        <textarea class="form-input-modern form-input-sm" v-model="form.rules" rows="3" maxlength="4000" placeholder="Опишите правила игры для пользователей..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Текст при выигрыше <span class="char-counter" v-if="form.win_message">{{ form.win_message.length }}/4000</span></label>
                        <textarea class="form-input-modern form-input-sm" v-model="form.win_message" rows="3" maxlength="4000" placeholder="Например: Поздравляем! Вы получили {prize}"></textarea>
                    </div>
                </div>
            </div>

            <!-- 🆕 НАСТРОЙКА РЕДКОСТЕЙ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-gem"></i> Шансы выпадения по редкостям</h5>
                <p class="text-muted small mb-3">Укажите процент вероятности выпадения приза каждой редкости. Сумма должна быть равна 100%.</p>

                <div class="rarity-chances">
                    <div class="rarity-chance-item rarity-common">
                        <div class="rarity-label">
                            <span class="rarity-dot"></span>
                            <span>Обычный</span>
                        </div>
                        <div class="rarity-input-wrapper">
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.rarity_chances.common" min="0" max="100" step="0.1">
                            <span class="percent-sign">%</span>
                        </div>
                    </div>
                    <div class="rarity-chance-item rarity-rare">
                        <div class="rarity-label">
                            <span class="rarity-dot"></span>
                            <span>Редкий</span>
                        </div>
                        <div class="rarity-input-wrapper">
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.rarity_chances.rare" min="0" max="100" step="0.1">
                            <span class="percent-sign">%</span>
                        </div>
                    </div>
                    <div class="rarity-chance-item rarity-epic">
                        <div class="rarity-label">
                            <span class="rarity-dot"></span>
                            <span>Эпический</span>
                        </div>
                        <div class="rarity-input-wrapper">
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.rarity_chances.epic" min="0" max="100" step="0.1">
                            <span class="percent-sign">%</span>
                        </div>
                    </div>
                    <div class="rarity-chance-item rarity-legendary">
                        <div class="rarity-label">
                            <span class="rarity-dot"></span>
                            <span>Легендарный</span>
                        </div>
                        <div class="rarity-input-wrapper">
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.rarity_chances.legendary" min="0" max="100" step="0.1">
                            <span class="percent-sign">%</span>
                        </div>
                    </div>
                </div>

                <div class="chances-total mt-3" :class="{ 'invalid': totalChances !== 100 }">
                    <span class="total-label">Сумма шансов:</span>
                    <span class="total-value">{{ totalChances.toFixed(1) }}%</span>
                    <i v-if="totalChances !== 100" class="fa-solid fa-triangle-exclamation text-danger ms-2"></i>
                </div>
            </div>

            <!-- 🆕 УПРАВЛЕНИЕ ПРИЗАМИ -->
            <div class="modern-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-gift"></i> Призы</h5>
                    <span class="badge-sector">{{ prizesCount }} призов</span>
                </div>

                <div class="prizes-list">
                    <div v-for="(item, index) in form.prizes" :key="item.id" class="prize-card" :class="`rarity-${item.rarity}`">
                        <div class="prize-header" @click="item.edit = !item.edit">
                            <div class="prize-preview">
                                <div class="prize-icon-preview" :class="`rarity-${item.rarity}`">
                                    <i :class="item.icon || 'fa-solid fa-gift'"></i>
                                </div>
                                <div class="prize-rarity-dot" :class="`rarity-${item.rarity}`"></div>
                            </div>
                            <div class="prize-info">
                                <div class="prize-name">{{ item.title || 'Без названия' }}</div>
                                <div class="prize-meta">
                                    <span class="prize-value">+{{ item.value }} бонусов</span>
                                    <span class="prize-rarity-tag" :class="`rarity-${item.rarity}`">{{ rarityText(item.rarity) }}</span>
                                </div>
                            </div>
                            <div class="prize-actions">
                                <button class="btn-icon btn-sm" :class="item.edit ? 'btn-success' : 'btn-secondary'" @click.stop="item.edit = !item.edit">
                                    <i class="fa-solid" :class="item.edit ? 'fa-check' : 'fa-pen'"></i>
                                </button>
                                <button class="btn-icon btn-sm btn-danger" @click.stop="removePrize(index)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>

                        <div v-if="item.edit" class="prize-edit-mode fade-in">
                            <div class="row g-3">
                                <!-- ИКОНКА -->
                                <div class="col-12 col-md-4">
                                    <label class="form-label-modern">Иконка приза</label>
                                    <div class="icon-input-wrapper">
                                        <input type="text" v-model="item.icon" class="form-input-modern form-input-sm text-center" placeholder="fa-solid fa-gift">
                                        <div class="icon-picker-dropdown">
                                            <button
                                                class="icon-btn"
                                                v-for="icon in icons"
                                                :key="icon"
                                                @click="item.icon = icon"
                                                :class="{ 'is-selected': item.icon === icon }"
                                            >
                                                <i :class="icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- НАЗВАНИЕ И ОПИСАНИЕ -->
                                <div class="col-12 col-md-8">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label-modern">Название приза</label>
                                            <input type="text" v-model="item.title" class="form-input-modern form-input-sm" placeholder="Например: Монетка или Джекпот">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label-modern">Описание</label>
                                            <input type="text" v-model="item.description" class="form-input-modern form-input-sm" placeholder="Краткое описание приза">
                                        </div>
                                    </div>
                                </div>

                                <!-- ЗНАЧЕНИЕ И РЕДКОСТЬ -->
                                <div class="col-6 col-md-4">
                                    <label class="form-label-modern">Значение (бонусы)</label>
                                    <input type="number" v-model.number="item.value" class="form-input-modern form-input-sm" min="1" placeholder="100">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label-modern">Редкость</label>
                                    <select v-model="item.rarity" class="form-input-modern form-input-sm">
                                        <option value="common">Обычный</option>
                                        <option value="rare">Редкий</option>
                                        <option value="epic">Эпический</option>
                                        <option value="legendary">Легендарный</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label-modern">Цвет редкости</label>
                                    <input type="text" class="form-input-modern form-input-sm" :value="rarityColor(item.rarity)" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="add-prize-block mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="text-muted small">
                            <i class="fa-solid fa-circle-info"></i> Добавьте призы для каждой категории редкости
                        </div>
                        <button class="btn-modern btn-primary" @click="addPrize" style="max-width: 300px; width: 100%;">
                            <i class="fa-solid fa-plus-circle"></i> Добавить приз
                        </button>
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
                    {{ isSaving ? 'Сохранение...' : 'Сохранить настройки игры' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CardGameAdminSettings",
    props: { modelValue: { type: Object, default: () => ({}) } },
    emits: ['update:modelValue'],

    data() {
        return {
            isSaving: false,
            icons: [
                'fa-solid fa-coins', 'fa-solid fa-sparkles', 'fa-solid fa-droplet', 'fa-solid fa-leaf', 'fa-solid fa-star',
                'fa-solid fa-gem', 'fa-solid fa-fire', 'fa-solid fa-bolt', 'fa-solid fa-crown', 'fa-solid fa-rocket',
                'fa-solid fa-trophy', 'fa-solid fa-gift', 'fa-solid fa-diamond', 'fa-solid fa-heart', 'fa-solid fa-shield',
                'fa-solid fa-wand-magic-sparkles', 'fa-solid fa-scroll', 'fa-solid fa-potion', 'fa-solid fa-key', 'fa-solid fa-lock'
            ],
            intervals: [
                { title: 'Один раз в день', value: 1 },
                { title: 'Один раз в неделю', value: 7 },
                { title: 'Один раз в месяц', value: 30 }
            ],
            form: {
                can_play: true,
                interval: 1,
                attempts_per_period: 1,
                grid_columns: 4,
                grid_rows: 3,
                rules: 'Выберите карту и получите бонус! Чем реже приз — тем он ценнее.',
                win_message: 'Поздравляем! Вы получили {prize}',
                rarity_chances: {
                    common: 70,
                    rare: 20,
                    epic: 8,
                    legendary: 2
                },
                prizes: [
                    { id: 1, title: 'Монетка', description: 'Небольшой бонус', icon: 'fa-solid fa-coins', value: 10, rarity: 'common', edit: false },
                    { id: 2, title: 'Искорка', description: 'Маленький приятный бонус', icon: 'fa-solid fa-sparkles', value: 15, rarity: 'common', edit: false },
                    { id: 3, title: 'Кристалл', description: 'Редкий кристалл', icon: 'fa-solid fa-gem', value: 50, rarity: 'rare', edit: false },
                    { id: 4, title: 'Корона', description: 'Королевский бонус', icon: 'fa-solid fa-crown', value: 200, rarity: 'epic', edit: false },
                    { id: 5, title: 'Джекпот', description: 'ЛЕГЕНДАРНЫЙ ПРИЗ!', icon: 'fa-solid fa-trophy', value: 1000, rarity: 'legendary', edit: false }
                ]
            }
        };
    },

    computed: {
        prizesCount() { return (this.form.prizes || []).length; },
        totalChances() {
            const chances = this.form.rarity_chances || {};
            return (chances.common || 0) + (chances.rare || 0) + (chances.epic || 0) + (chances.legendary || 0);
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
                    this.form.interval = newValue.interval ?? this.form.interval;
                    this.form.attempts_per_period = newValue.attempts_per_period ?? this.form.attempts_per_period;
                    this.form.grid_columns = newValue.grid_columns ?? this.form.grid_columns;
                    this.form.grid_rows = newValue.grid_rows ?? this.form.grid_rows;
                    this.form.rules = newValue.rules ?? this.form.rules;
                    this.form.win_message = newValue.win_message ?? this.form.win_message;

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
        rarityText(rarity) {
            const texts = { common: 'Обычный', rare: 'Редкий', epic: 'Эпический', legendary: 'Легендарный' };
            return texts[rarity] || 'Обычный';
        },

        rarityColor(rarity) {
            const colors = { common: '#6c757d', rare: '#0d6efd', epic: '#6f42c1', legendary: '#ffc107' };
            return colors[rarity] || '#6c757d';
        },

        async saveSettings() {
            if (this.totalChances !== 100) {
                this.$notify?.({ title: 'Ошибка', text: 'Сумма шансов должна быть равна 100%', type: 'error' });
                return;
            }

            this.isSaving = true;

            try {
                const cleanForm = JSON.parse(JSON.stringify(this.form));
                cleanForm.prizes = cleanForm.prizes.map(item => {
                    const { edit, ...rest } = item;
                    return rest;
                });

                const response = await axios.put('/admin/tenant-settings/card-game', {
                    card_game: cleanForm
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
        },

        addPrize() {
            const newId = this.prizesCount > 0 ? Math.max(...this.form.prizes.map(i => i.id)) + 1 : 1;

            this.form.prizes.push({
                id: newId,
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
        }
    }
};
</script>

<style scoped>
.card-game-admin-page {
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
.card-title i { color: #667eea; }

.form-label-modern { font-size: 0.8rem; font-weight: 600; color: #64748B; margin-bottom: 4px; display: flex; justify-content: space-between; }
.char-counter { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.form-input-modern {
    width: 100%; padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s; background: #F8FAFC;
}
.form-input-modern.form-input-sm { padding: 6px 10px; font-size: 0.85rem; }
.form-input-modern:focus { outline: none; border-color: #667eea; background: white; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }

.modern-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
.modern-switch input { display: none; }
.switch-slider { width: 40px; height: 22px; background: #CBD5E1; border-radius: 99px; position: relative; transition: background 0.3s ease; }
.switch-slider::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #667eea; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(18px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.9rem; }

/* Редкости */
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
    position: relative;
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

.total-label {
    font-size: 0.9rem;
}

.total-value {
    font-size: 1.1rem;
    font-weight: 700;
}

/* Призы */
.badge-sector { padding: 4px 10px; background: #EEF2FF; color: #667eea; border-radius: 99px; font-weight: 700; font-size: 0.8rem; }
.prizes-list { display: flex; flex-direction: column; gap: 8px; }

.prize-card { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; overflow: hidden; transition: all 0.2s; }
.prize-card:hover { border-color: #CBD5E1; }
.prize-card.rarity-legendary { border-color: rgba(255, 193, 7, 0.3); background: linear-gradient(135deg, rgba(255, 215, 0, 0.05) 0%, transparent 100%); }

.prize-header { display: flex; align-items: center; gap: 12px; padding: 12px; cursor: pointer; transition: background 0.2s; }
.prize-header:hover { background: #F1F5F9; }

.prize-preview { display: flex; align-items: center; gap: 10px; position: relative; }
.prize-icon-preview {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
}

.prize-icon-preview.rarity-common { background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%); }
.prize-icon-preview.rarity-rare { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); }
.prize-icon-preview.rarity-epic { background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%); }
.prize-icon-preview.rarity-legendary { background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; }

.prize-rarity-dot {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid white;
}

.prize-rarity-dot.rarity-common { background: #6c757d; }
.prize-rarity-dot.rarity-rare { background: #0d6efd; }
.prize-rarity-dot.rarity-epic { background: #6f42c1; }
.prize-rarity-dot.rarity-legendary { background: #ffc107; }

.prize-info { flex: 1; min-width: 0; }
.prize-name { font-weight: 600; color: #0F172A; font-size: 0.95rem; margin-bottom: 4px; }
.prize-meta { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; }

.prize-value {
    font-weight: 700;
    color: #667eea;
}

.prize-rarity-tag {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}

.prize-rarity-tag.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.prize-rarity-tag.rarity-rare { background: rgba(13, 110, 253, 0.15); color: #0d6efd; }
.prize-rarity-tag.rarity-epic { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.prize-rarity-tag.rarity-legendary { background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 152, 0, 0.2) 100%); color: #b8860b; }

.prize-actions { display: flex; gap: 6px; }
.btn-icon {
    width: 32px; height: 32px; border-radius: 6px; border: 1px solid #E2E8F0; background: white;
    color: #64748B; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.btn-icon.btn-success { color: #10B981; border-color: #10B981; }
.btn-icon.btn-success:hover { background: #10B981; color: white; }
.btn-icon.btn-danger { color: #EF4444; border-color: #EF4444; }
.btn-icon.btn-danger:hover { background: #EF4444; color: white; }

.prize-edit-mode { padding: 0 12px 12px 12px; border-top: 1px dashed #E2E8F0; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

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
    font-size: 1.3rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
}

.icon-btn:hover {
    background: #E2E8F0;
    transform: scale(1.15);
}

.icon-btn.is-selected {
    border-color: #667eea;
    background: #EEF2FF;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

.btn-modern {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s; padding: 8px 16px;
}
.btn-modern.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.2); }
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(102, 126, 234, 0.3); }
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
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-modern.btn-lg:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .prize-header { flex-wrap: wrap; }
    .prize-actions { width: 100%; justify-content: flex-end; margin-top: 4px; }
    .rarity-chances { grid-template-columns: 1fr; }
}
</style>
