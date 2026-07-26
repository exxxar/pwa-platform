<template>
    <div class="wheel-admin-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-dharmachakra me-2" style="color: #c0392b;"></i> Колесо Фортуны
                    </h2>
                    <p class="page-subtitle">Настройка призов, правил и логики розыгрыша</p>
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
                            <span class="switch-label">Активность колеса</span>
                        </label>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label-modern">Период обновления попыток</label>
                        <select class="form-input-modern form-input-sm" v-model="form.interval">
                            <option v-for="item in intervals" :key="item.value" :value="item.value">
                                {{ item.title }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Действие ДО игры</label>
                        <select class="form-input-modern form-input-sm" v-model="form.before_script">
                            <option v-for="opt in scriptOptions" :key="opt.value" :value="opt.value">
                                {{ opt.title }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Действие ПОСЛЕ выигрыша</label>
                        <select class="form-input-modern form-input-sm" v-model="form.after_script">
                            <option v-for="opt in scriptOptions" :key="opt.value" :value="opt.value">
                                {{ opt.title }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Правила игры <span class="char-counter" v-if="form.rules">{{ form.rules.length }}/4000</span></label>
                        <textarea class="form-input-modern form-input-sm" v-model="form.rules" rows="3" maxlength="4000"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Текст при выигрыше <span class="char-counter" v-if="form.win_message">{{ form.win_message.length }}/4000</span></label>
                        <textarea class="form-input-modern form-input-sm" v-model="form.win_message" rows="3" maxlength="4000"></textarea>
                    </div>
                </div>
            </div>

            <!-- 🆕 КОМПАКТНЫЕ: Управление секторами -->
            <div class="modern-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-pie-chart"></i> Сектора и призы</h5>
                    <span class="badge-sector">{{ sectorsCount }} / 10</span>
                </div>

                <div class="sectors-list">
                    <div v-for="(item, index) in form.items" :key="item.id" class="sector-card">
                        <div class="sector-header" @click="item.edit = !item.edit">
                            <div class="sector-preview">
                                <span class="sector-emoji">{{ item.value || '❓' }}</span>
                                <div class="sector-color-dot" :style="{ backgroundColor: item.bgColor }"></div>
                            </div>
                            <div class="sector-info">
                                <div class="sector-name">#{{ item.id }}: {{ item.description || 'Без названия' }}</div>
                                <div class="sector-mark" v-if="item.mark"><i class="fa-solid fa-location-dot"></i> {{ item.mark }}</div>
                            </div>
                            <div class="sector-actions">
                                <button class="btn-icon btn-sm" :class="item.edit ? 'btn-success' : 'btn-secondary'" @click.stop="item.edit = !item.edit">
                                    <i class="fa-solid" :class="item.edit ? 'fa-check' : 'fa-pen'"></i>
                                </button>
                                <button class="btn-icon btn-sm btn-danger" @click.stop="removeSector(index)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>

                        <div v-if="item.edit" class="sector-edit-mode fade-in">
                            <div class="row g-3">
                                <!-- 🆕 ЭМОДЗИ: Широкий блок с красивой сеткой -->
                                <div class="col-12 col-md-4">
                                    <label class="form-label-modern">Иконка приза</label>
                                    <div class="emoji-input-wrapper">
                                        <input type="text" v-model="item.value" class="form-input-modern form-input-sm text-center" maxlength="4" placeholder="🎁">
                                        <div class="emoji-picker-dropdown">
                                            <button
                                                class="emoji-btn"
                                                v-for="smile in smiles"
                                                :key="smile"
                                                @click="item.value = smile"
                                                :class="{ 'is-selected': item.value === smile }"
                                            >
                                                {{ smile }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- ОПИСАНИЕ И ЦВЕТА -->
                                <div class="col-12 col-md-8">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label-modern">Описание приза</label>
                                            <input type="text" v-model="item.description" class="form-input-modern form-input-sm" placeholder="Например: Бесплатный десерт или скидка 10%">
                                        </div>
                                        <div class="col-6 col-md-6">
                                            <label class="form-label-modern">Цвет фона сектора</label>
                                            <div class="color-input-wrapper">
                                                <input type="color" v-model="item.bgColor" class="color-picker-sm">
                                                <span class="color-hex">{{ item.bgColor }}</span>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-6">
                                            <label class="form-label-modern">Цвет иконки</label>
                                            <div class="color-input-wrapper">
                                                <input type="color" v-model="item.color" class="color-picker-sm">
                                                <span class="color-hex">{{ item.color }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 🆕 ГДЕ ВЫДАЕТСЯ: Удобные кнопки-переключатели на всю ширину -->
                                <div class="col-12">
                                    <label class="form-label-modern">Где выдается приз</label>
                                    <div class="mark-selector">
                                        <button type="button" class="mark-option" :class="{ 'active': item.mark === 'в заведении' }" @click="item.mark = 'в заведении'">
                                            <i class="fa-solid fa-store"></i> В заведении
                                        </button>
                                        <button type="button" class="mark-option" :class="{ 'active': item.mark === 'на доставке' }" @click="item.mark = 'на доставке'">
                                            <i class="fa-solid fa-truck-fast"></i> На доставке
                                        </button>
                                        <button type="button" class="mark-option" :class="{ 'active': item.mark === 'в заведении и на доставке' }" @click="item.mark = 'в заведении и на доставке'">
                                            <i class="fa-solid fa-store"></i> + <i class="fa-solid fa-truck-fast"></i> Везде
                                        </button>
                                    </div>
                                    <!-- Поле для кастомного ввода, если стандартных вариантов мало -->
                                    <input type="text" v-model="item.mark" class="form-input-modern form-input-sm mt-2" placeholder="Или введите свой вариант вручную...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="add-sector-block mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <label class="modern-switch mb-0">
                            <input type="checkbox" v-model="need_auto_random_smiles">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Случайный эмодзи</span>
                        </label>
                        <button class="btn-modern btn-primary" :disabled="sectorsCount >= 10" @click="addSector" style="max-width: 300px; width: 100%;">
                            <i class="fa-solid fa-plus-circle"></i>
                            {{ sectorsCount >= 10 ? 'Лимит (10)' : 'Добавить сектор' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- 🆕 ИСПРАВЛЕННЫЙ ПРЕДПРОСМОТР -->
            <div class="modern-card" v-if="form.items && form.items.length >= 3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-eye"></i> Предпросмотр</h5>
                    <button class="btn-modern btn-secondary btn-sm" @click="triggerTestSpin" :disabled="isSpinning">
                        <i class="fa-solid fa-play"></i> Тестовый прокрут
                    </button>
                </div>


                <div class="preview-wheel-wrapper">
                    <WheelOfFortuneShopVariant
                        ref="previewWheel"
                        :model-value="form.items"
                        :is-admin="true"
                        :interval="form.interval"
                    />
                </div>
                <p class="text-center text-muted small mt-3 mb-0">
                    <i class="fa-solid fa-circle-info"></i> Визуальный тест. Данные не сохраняются, попытки не списываются.
                </p>
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
                    {{ isSaving ? 'Сохранение...' : 'Сохранить настройки колеса' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import WheelOfFortuneShopVariant from "@/MobileClient/Components/Games/WheelOfFortuneShopVariant.vue";

export default {
    name: "WheelAdminSettings",
    components: { WheelOfFortuneShopVariant },
    props: { modelValue: { type: Object, default: () => ({}) } },
    emits: ['update:modelValue'],

    data() {
        return {
            isSaving: false, // 🆕 Флаг загрузки
            isSpinning: false,
            need_auto_random_smiles: true,
            smiles: ["🎁", "🏆", "💎", "☘️", "⭐", "🚀", "💡", "🔟", "9️⃣", "8️⃣", "7️⃣", "6️⃣", "5️⃣", "4️⃣", "3️⃣", "2️⃣", "1️⃣", "🎓", "👓", "🏀", "👻", "🐶", "📚", "💌", "👽", "🌻", "😎", "😊", "🎯", "🎲", "🤖", "🍕", "☕", "🍓", "🍦", "🍲", "🍅", "🍱", "🍜", "🍧", "🍨", "🧁", "🥞", "🌯", "🍟", "🍗", "🍔", "🥗", "🥤"],
            intervals: [
                { title: 'Один раз в день', value: 1 },
                { title: 'Один раз в неделю', value: 7 },
                { title: 'Один раз в месяц', value: 30 }
            ],
            scriptOptions: [
                { value: null, title: 'Не использовать (пропустить)' },
                { value: 'auth_1', title: 'Авторизация / Телефон' },
                { value: 'marketplace_1', title: 'Форма маркетплейса' },
                { value: 'review_1', title: 'Оставить отзыв' }
            ],
            form: {
                can_play: true,
                interval: 1,
                rules: 'Колесо фортуны доступно 1 раз в сутки.',
                win_message: 'Поздравляем! Вы выиграли: {prize}.',
                items: [
                    { id: 1, value: "🎁", bgColor: "#c0392b", color: "#ffffff", description: 'Секретный приз', mark: 'в заведении & на доставке', edit: false },
                    { id: 2, value: "☕", bgColor: "#ffffff", color: "#c0392b", description: 'Бесплатный кофе', mark: 'в заведении', edit: false },
                    { id: 3, value: "🍕", bgColor: "#f1c40f", color: "#ffffff", description: 'Пицца в подарок', mark: 'на доставке', edit: false },
                ]
            }
        };
    },

    computed: {
        sectorsCount() { return (this.form.items || []).length; }
    },

    watch: {
        form: {
            handler: function (newValue) {
                const cleanForm = JSON.parse(JSON.stringify(newValue));
                cleanForm.items = cleanForm.items.map(item => {
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
                    this.form.rules = newValue.rules ?? this.form.rules;
                    this.form.win_message = newValue.win_message ?? this.form.win_message;

                    if (newValue.items && Array.isArray(newValue.items) && newValue.items.length > 0) {
                        this.form.items = newValue.items.map(item => ({
                            ...item,
                            edit: false,
                            color: item.color || '#ffffff'
                        }));
                    }
                }
            },
            immediate: true
        }
    },

    methods: {
        async saveSettings() {
            this.isSaving = true;

            try {
                // 1. Очищаем форму от служебных полей (например, флаг 'edit')
                const cleanForm = JSON.parse(JSON.stringify(this.form));
                cleanForm.items = cleanForm.items.map(item => {
                    const { edit, ...rest } = item; // Убираем 'edit'
                    return rest;
                });

                // 2. Отправляем на бэкенд
                const response = await axios.put('/admin/tenant-settings/wheel', {
                    wheel: cleanForm
                });

                // 3. Показываем уведомление об успехе
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

        getRandomInt(min, max) {
            return Math.floor(Math.random() * (Math.floor(max) - Math.ceil(min) + 1)) + Math.ceil(min);
        },

        addSector() {
            if (this.sectorsCount >= 10) return;
            const newId = this.sectorsCount > 0 ? Math.max(...this.form.items.map(i => i.id)) + 1 : 1;
            const isEven = this.sectorsCount % 2 === 0;
            const value = this.need_auto_random_smiles ? this.smiles[this.getRandomInt(0, this.smiles.length - 1)] : "🎁";

            this.form.items.push({
                id: newId, value: value, bgColor: isEven ? "#c0392b" : "#ffffff", color: "#ffffff",
                description: `Приз #${newId}`, mark: 'в заведении & на доставке', edit: true
            });
        },

        removeSector(index) {
            if (confirm(`Удалить сектор #${this.form.items[index].id}?`)) {
                this.form.items.splice(index, 1);
                this.form.items.forEach((item, idx) => { item.id = idx + 1; });
            }
        },


        // 🆕 Правильный вызов прокрута через дочерний компонент
        triggerTestSpin() {
            if (this.$refs.previewWheel) {
                this.isSpinning = true;
                this.$refs.previewWheel.launchWheel();

                // Сбрасываем флаг после анимации (примерно 4-5 секунд)
                setTimeout(() => {
                    this.isSpinning = false;
                }, 5000);
            }
        }
    }
};
</script>

<style scoped>
.wheel-admin-page {
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
.card-title i { color: #c0392b; }

/* 🆕 Компактные формы */
.form-label-modern { font-size: 0.8rem; font-weight: 600; color: #64748B; margin-bottom: 4px; display: flex; justify-content: space-between; }
.char-counter { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.form-input-modern {
    width: 100%; padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s; background: #F8FAFC;
}
.form-input-modern.form-input-sm { padding: 6px 10px; font-size: 0.85rem; }
.form-input-modern:focus { outline: none; border-color: #c0392b; background: white; box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.1); }

/* Свитчи */
.modern-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
.modern-switch input { display: none; }
.switch-slider { width: 40px; height: 22px; background: #CBD5E1; border-radius: 99px; position: relative; transition: background 0.3s ease; }
.switch-slider::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #c0392b; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(18px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.9rem; }

/* Сектора */
.badge-sector { padding: 4px 10px; background: #FEF2F2; color: #c0392b; border-radius: 99px; font-weight: 700; font-size: 0.8rem; }
.sectors-list { display: flex; flex-direction: column; gap: 8px; }

.sector-card { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; overflow: hidden; transition: all 0.2s; }
.sector-card:hover { border-color: #CBD5E1; }

.sector-header { display: flex; align-items: center; gap: 12px; padding: 12px; cursor: pointer; transition: background 0.2s; }
.sector-header:hover { background: #F1F5F9; }

.sector-preview { display: flex; align-items: center; gap: 10px; }
.sector-emoji { font-size: 1.5rem; line-height: 1; }
.sector-color-dot { width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

.sector-info { flex: 1; min-width: 0; }
.sector-name { font-weight: 600; color: #0F172A; font-size: 0.9rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sector-mark { font-size: 0.75rem; color: #64748B; display: flex; align-items: center; gap: 4px; }

.sector-actions { display: flex; gap: 6px; }
.btn-icon {
    width: 32px; height: 32px; border-radius: 6px; border: 1px solid #E2E8F0; background: white;
    color: #64748B; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.btn-icon.btn-success { color: #10B981; border-color: #10B981; }
.btn-icon.btn-success:hover { background: #10B981; color: white; }
.btn-icon.btn-danger { color: #EF4444; border-color: #EF4444; }
.btn-icon.btn-danger:hover { background: #EF4444; color: white; }

/* Режим редактирования */
.sector-edit-mode { padding: 0 12px 12px 12px; border-top: 1px dashed #E2E8F0; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

.emoji-input-wrapper { position: relative; }
.emoji-picker-dropdown {
    display: flex; flex-wrap: wrap; gap: 2px; margin-top: 4px; padding: 6px; background: white;
    border: 1px solid #E2E8F0; border-radius: 8px; max-height: 100px; overflow-y: auto;
}
.emoji-btn {
    width: 28px; height: 28px; border: none; background: transparent; font-size: 1.1rem; cursor: pointer;
    border-radius: 4px; transition: background 0.2s; display: flex; align-items: center; justify-content: center;
}
.emoji-btn:hover { background: #F1F5F9; }

.color-input-wrapper { display: flex; align-items: center; gap: 8px; }
.color-picker-sm { width: 36px; height: 34px; border: 1px solid #E2E8F0; border-radius: 6px; padding: 2px; cursor: pointer; }
.color-hex { font-family: monospace; color: #64748B; font-size: 0.8rem; }

.mark-chips { display: flex; flex-wrap: wrap; gap: 4px; }
.mark-chip {
    padding: 2px 8px; background: white; border: 1px solid #E2E8F0; border-radius: 99px;
    font-size: 0.75rem; color: #c0392b; cursor: pointer; transition: all 0.2s;
}
.mark-chip.mark-chip-sm { padding: 2px 6px; font-size: 0.7rem; }
.mark-chip:hover { background: #FEF2F2; border-color: #c0392b; }

/* Кнопки */
.btn-modern {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s; padding: 8px 16px;
}
.btn-modern.btn-primary { background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%); color: white; box-shadow: 0 4px 6px -1px rgba(192, 57, 43, 0.2); }
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(192, 57, 43, 0.3); }
.btn-modern.btn-secondary { background: white; color: #475569; border-color: #E2E8F0; }
.btn-modern.btn-secondary:hover:not(:disabled) { background: #F1F5F9; }
.btn-modern:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-sm { padding: 4px 8px; font-size: 0.8rem; }

/* 🆕 ИСПРАВЛЕННЫЙ ПРЕДПРОСМОТР КОЛЕСА */
.preview-wheel-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, rgba(192, 57, 43, 0.03) 0%, rgba(241, 196, 15, 0.03) 100%);
    border-radius: 16px;
    border: 1px dashed #E2E8F0;
    width: 100%; /* 🆕 Занимает всю доступную ширину, чтобы колесу было место */
    max-width: 500px;
    margin: 0 auto;
    overflow: hidden; /* 🆕 Обрезает всё, что вылезает за границы */
}

/* 🆕 КРИТИЧЕСКИ ВАЖНО: Принудительно уменьшаем шрифт внутри SVG колеса, чтобы текст не вылезал */
.preview-wheel-wrapper :deep(div#wheel svg text) {
    font-size: 13px !important;
    font-weight: 600;
    /* Дополнительная страховка от переполнения SVG */
    max-width: 80px;
    overflow: hidden;
}

@media (max-width: 768px) {
    .sector-header { flex-wrap: wrap; }
    .sector-actions { width: 100%; justify-content: flex-end; margin-top: 4px; }
}

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
    box-shadow: 0 8px 20px rgba(192, 57, 43, 0.3);
}

.btn-modern.btn-lg:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(192, 57, 43, 0.4);
}

/* 🆕 КРАСИВЫЙ ВЫБОР ЭМОДЗИ (Сетка на всю ширину) */
.emoji-picker-dropdown {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
    gap: 6px;
    margin-top: 8px;
    padding: 10px;
    background: #ffffff;
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
    font-size: 1.4rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.emoji-btn:hover {
    background: #E2E8F0;
    transform: scale(1.15);
}

.emoji-btn.is-selected {
    border-color: #c0392b;
    background: #FEF2F2;
    box-shadow: 0 0 0 2px rgba(192, 57, 43, 0.2);
}

/* 🆕 УДОБНЫЙ ВЫБОР МЕСТА ВЫДАЧИ (Кнопки-переключатели) */
.mark-selector {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mark-option {
    flex: 1;
    min-width: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #64748B;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mark-option:hover {
    border-color: #cbd5e1;
    color: #334155;
    background: #F8FAFC;
}

.mark-option.active {
    background: #FEF2F2;
    border-color: #c0392b;
    color: #c0392b;
    font-weight: 600;
    box-shadow: 0 0 0 2px rgba(192, 57, 43, 0.15);
}

.mark-option i {
    font-size: 1rem;
}

/* Улучшенные инпуты цветов */
.color-input-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}
.color-picker-sm {
    width: 42px;
    height: 38px;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 2px;
    cursor: pointer;
    background: white;
}
.color-picker-sm:hover {
    border-color: #c0392b;
}
.color-hex {
    font-family: monospace;
    color: #64748B;
    font-size: 0.85rem;
    background: #F1F5F9;
    padding: 4px 8px;
    border-radius: 6px;
}
</style>
