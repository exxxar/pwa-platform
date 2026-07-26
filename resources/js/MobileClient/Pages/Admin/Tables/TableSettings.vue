<template>
    <div class="table-settings-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <router-link :to="{ name: 'AdminTablesManager' }" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i>
                </router-link>
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-layer-group me-2 text-primary"></i> Конструктор залов
                    </h2>
                    <p class="page-subtitle">Настройте отображение и типы столиков для ваших гостей</p>
                </div>
            </div>

            <!-- Глобальные настройки (Стеклянная карточка) -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-sliders me-2"></i> Общие настройки</h5>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="modern-switch">
                            <input type="checkbox" v-model="form.need_table_list">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Показывать выбор столиков гостям</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="modern-switch">
                            <input type="checkbox" v-model="form.can_use_booking">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Разрешить онлайн-бронирование</span>
                        </label>
                    </div>

                    <div class="col-md-6 fade-in" v-if="form.need_table_list">
                        <label class="form-label-modern">Максимальное число столиков</label>
                        <div class="input-group-modern">
                            <i class="fa-solid fa-chair input-icon"></i>
                            <input type="number" v-model.number="form.max_tables" class="form-input-modern" min="0" max="200" placeholder="50">
                        </div>
                    </div>

                    <div class="col-12 fade-in" v-if="form.need_table_list && form.max_tables > 0">
                        <a :href="qrDownloadLink" target="_blank" class="btn-download-qr">
                            <i class="fa-solid fa-qrcode"></i>
                            <span>Скачать PDF с QR-кодами для печати ({{ form.max_tables }} шт.)</span>
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Вкладки управления (Сегментированный контроль) -->
            <div class="modern-tabs mb-4">
                <button class="tab-btn" :class="{ 'active': tab === 'configurator' }" @click="tab = 'configurator'">
                    <i class="fa-solid fa-list-check"></i> Выбранные столики
                </button>
                <button class="tab-btn" :class="{ 'active': tab === 'selector' }" @click="tab = 'selector'">
                    <i class="fa-solid fa-shapes"></i> Каталог шаблонов
                </button>
            </div>

            <!-- Вкладка: Конфигуратор -->
            <div v-if="tab === 'configurator'" class="fade-in">
                <div v-if="sortedSelectedTables.length > 0" class="config-list">
                    <div v-for="(table, index) in sortedSelectedTables" :key="table.unique_id" class="config-table-card">
                        <div class="d-flex align-items-center gap-3">
                            <!-- CSS-Отрисовка (Миниатюра) -->
                            <div class="table-preview-visual floor-plan-bg">
                                <div class="css-table css-table-sm" :class="`shape-${getTableConfig(table).shape}`">
                                    <div v-for="(seat, seatIndex) in getTableConfig(table).seats" :key="seatIndex" class="seat" :class="[`type-${seat.type}`, `pos-${seat.pos}`]"></div>
                                    <div class="table-top">
                                        <span class="table-number">{{ table.number || '?' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-0 fw-bold table-card-title">Столик №{{ table.number || '?' }}</h6>
                                        <span class="badge-capacity">{{ table.seats }} мест</span>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" @click="table.edit = !table.edit" :title="table.edit ? 'Сохранить' : 'Редактировать'">
                                            <i class="fa-solid" :class="table.edit ? 'fa-check' : 'fa-pen'"></i>
                                        </button>
                                        <button class="btn-action btn-delete" @click="removeFromSelectedTableList(table.unique_id)" title="Удалить">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div v-if="!table.edit">
                                    <p class="mb-0 text-muted small">{{ table.description || 'Стандартный столик' }}</p>
                                </div>

                                <div v-else class="edit-mode-row">
                                    <div class="edit-field">
                                        <label>Номер</label>
                                        <input type="number" v-model.number="table.number" class="form-input-sm">
                                    </div>
                                    <div class="edit-field">
                                        <label>Мест</label>
                                        <input type="number" v-model.number="table.seats" class="form-input-sm">
                                    </div>
                                    <div class="edit-field flex-grow-1">
                                        <label>Описание</label>
                                        <input type="text" v-model="table.description" class="form-input-sm" placeholder="Напр.: У окна, с диваном">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="empty-state-modern">
                    <div class="empty-icon-wrapper">
                        <i class="fa-solid fa-chair"></i>
                    </div>
                    <h5>Зал пока пуст</h5>
                    <p>Перейдите во вкладку "Каталог шаблонов", чтобы добавить первые столики.</p>
                </div>
            </div>

            <!-- Вкладка: Выбор из шаблонов -->
            <div v-if="tab === 'selector'" class="fade-in">
                <!-- Фильтры -->
                <div class="filter-chips mb-4">
                    <button class="chip" :class="{ 'active': tableFilters.length === 0 }" @click="tableFilters = []">
                        <i class="fa-solid fa-border-all"></i> Все
                    </button>
                    <button v-for="seats in [2, 4, 6, 8]" :key="seats" class="chip" :class="{ 'active': tableFilters.includes(seats) }" @click="toggleFilter(seats)">
                        {{ seats }} мест
                    </button>
                </div>

                <!-- Сетка шаблонов -->
                <div class="templates-grid">
                    <div v-for="table in filteredTables" :key="table.id" class="template-card" :class="{ 'is-selected': countTableInSelection(table.id) > 0 }" @click="selectTable(table)">
                        <div class="template-visual-wrapper floor-plan-bg">
                            <div class="css-table" :class="`shape-${getTableConfig(table).shape}`">
                                <div v-for="(seat, seatIndex) in getTableConfig(table).seats" :key="seatIndex" class="seat" :class="[`type-${seat.type}`, `pos-${seat.pos}`]"></div>
                                <div class="table-top">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                            </div>
                            <div class="seats-badge">
                                <i class="fa-solid fa-user-group"></i> {{ table.seats }}
                            </div>
                        </div>

                        <div class="template-info">
                            <h6 class="template-title">{{ table.description }}</h6>
                            <div class="template-action">
                                <span v-if="countTableInSelection(table.id) === 0">Добавить в зал</span>
                                <span v-else>В зале: {{ countTableInSelection(table.id) }} шт.</span>
                                <i class="fa-solid" :class="countTableInSelection(table.id) > 0 ? 'fa-check-circle' : 'fa-plus-circle'"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredTables.length === 0" class="empty-state-modern">
                    <i class="fa-solid fa-filter fa-2x text-muted mb-3"></i>
                    <p>По выбранным фильтрам столики не найдены</p>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "TableSettings",
    props: {
        modelValue: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['update:modelValue'],

    data() {
        return {
            tab: 'configurator',
            form: {
                need_table_list: false,
                can_use_booking: false,
                max_tables: 0,
                tables_variants: []
            },
            tableFilters: [],
            // 🆕 Убрали поле 'image', теперь всё рисуется через CSS
            tablesTemplate: [
                { id: 1, description: "Угловой стол на 4 места", seats: 4 },
                { id: 2, description: "Угловой стол на 4 места", seats: 4 },
                { id: 3, description: "Круглый стол на 8 мест", seats: 8 },
                { id: 4, description: "Овальный стол на 6 мест", seats: 6 },
                { id: 5, description: "Овальный стол на 8 мест", seats: 8 },
                { id: 6, description: "Овальный стол с диваном на 8 мест", seats: 8 },
                { id: 7, description: "Прямоугольный стол с диваном на 8 мест", seats: 8 },
                { id: 8, description: "Овальный стол с диваном на 6 мест", seats: 6 },
                { id: 9, description: "Прямоугольный стол с диваном на 6 мест", seats: 6 },
                { id: 10, description: "Овальный стол с диваном на 4 места", seats: 4 },
                { id: 11, description: "Прямоугольный стол с диваном на 4 места", seats: 4 },
                { id: 12, description: "Прямоугольный стол на 4 места", seats: 4 },
                { id: 13, description: "Прямоугольный стол на 6 мест", seats: 6 },
                { id: 14, description: "Круглый стол на 6 мест", seats: 6 },
                { id: 15, description: "Круглый стол на 5 мест", seats: 5 },
                { id: 16, description: "Круглый стол на 2 места", seats: 2 },
                { id: 17, description: "Круглый стол на 4 места", seats: 4 },
                { id: 18, description: "Квадратный стол на 2 места", seats: 2 },
                { id: 19, description: "Квадратный стол на 4 места", seats: 4 },
            ]
        };
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
                    this.form = { ...this.form, ...newValue };
                    if (!this.form.tables_variants) {
                        this.form.tables_variants = [];
                    }
                }
            },
            immediate: true,
            deep: true
        }
    },

    computed: {
        bot() {
            return window.currentBot || { bot_domain: 'demo' };
        },
        scriptId() {
            return window.currentScript || 'default';
        },
        qrDownloadLink() {
            return `/bot-client/${this.bot.bot_domain}/tables-qr?count=${this.form.max_tables}&script-id=${this.scriptId}`;
        },
        filteredTables() {
            if (this.tableFilters.length === 0) {
                return [...this.tablesTemplate].sort((a, b) => a.seats - b.seats);
            }
            return this.tablesTemplate
                .filter(table => this.tableFilters.includes(table.seats))
                .sort((a, b) => a.seats - b.seats);
        },
        sortedSelectedTables() {
            if (!this.form.tables_variants) return [];
            return [...this.form.tables_variants].sort((a, b) => (a.number || 0) - (b.number || 0));
        }
    },

    methods: {
        // ==========================================
        // 🆕 МЕТОД: Конфигурация CSS-столика (из TableBooking)
        // ==========================================
        getTableConfig(table) {
            const seats = table.seats || 2;
            // Эвристика: если в описании есть "диван", используем диванную конфигурацию
            const hasSofa = table.description?.toLowerCase().includes('диван') || table.type === 'sofa';

            let shape = 'round';
            let seatsConfig = [];

            if (seats <= 2) {
                shape = 'round';
                seatsConfig = [
                    { type: 'chair', pos: 'left' },
                    { type: 'chair', pos: 'right' }
                ];
            } else if (seats === 4) {
                shape = hasSofa ? 'rect' : 'square';
                if (hasSofa) {
                    seatsConfig = [
                        { type: 'sofa', pos: 'top' },
                        { type: 'chair', pos: 'bottom' },
                        { type: 'chair', pos: 'left' },
                        { type: 'chair', pos: 'right' }
                    ];
                } else {
                    seatsConfig = [
                        { type: 'chair', pos: 'top' },
                        { type: 'chair', pos: 'bottom' },
                        { type: 'chair', pos: 'left' },
                        { type: 'chair', pos: 'right' }
                    ];
                }
            } else if (seats >= 5) {
                shape = hasSofa ? 'rect' : 'round';
                if (hasSofa) {
                    seatsConfig = [
                        { type: 'sofa', pos: 'top' },
                        { type: 'chair', pos: 'bottom-left' },
                        { type: 'chair', pos: 'bottom-right' },
                        { type: 'chair', pos: 'left' },
                        { type: 'chair', pos: 'right' }
                    ];
                } else {
                    seatsConfig = [
                        { type: 'chair', pos: 'top' },
                        { type: 'chair', pos: 'bottom' },
                        { type: 'chair', pos: 'left' },
                        { type: 'chair', pos: 'right' },
                        { type: 'chair', pos: 'tl' },
                        { type: 'chair', pos: 'br' }
                    ].slice(0, seats);
                }
            }

            return { shape, seats: seatsConfig };
        },

        toggleFilter(seats) {
            const index = this.tableFilters.indexOf(seats);
            if (index > -1) {
                this.tableFilters.splice(index, 1);
            } else {
                this.tableFilters.push(seats);
            }
        },
        countTableInSelection(templateId) {
            return (this.form.tables_variants || []).filter(i => i.id === templateId).length;
        },
        selectTable(template) {
            const newTable = {
                ...template,
                unique_id: Date.now() + Math.random(), // Уникальный ID для Vue v-for
                number: template.id,
                edit: false
            };
            this.form.tables_variants.push(newTable);

            this.$notify?.({
                title: "Успех",
                text: "Столик добавлен в список",
                type: "success"
            });
        },
        removeFromSelectedTableList(uniqueId) {
            const index = this.form.tables_variants.findIndex(i => i.unique_id === uniqueId);
            if (index !== -1) {
                this.form.tables_variants.splice(index, 1);
            }
        }
    }
};
</script>

<style scoped>
/* ==========================================
   🎨 MODERN ADMIN VARIABLES & BASE
   ========================================== */
.table-settings-page {
    background-color: #F8FAFC; /* Очень светлый серо-голубой фон */
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #334155;
}

/* Анимации появления */
.fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* ==========================================
   HEADER & CARDS
   ========================================== */
.modern-header {
    display: flex;
    align-items: center;
    gap: 16px;
}
.btn-back {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: white;
    border: 1px solid #E2E8F0;
    display: flex; align-items: center; justify-content: center;
    color: #64748B;
    transition: all 0.2s;
    text-decoration: none;
}
.btn-back:hover { background: #F1F5F9; color: #0F172A; transform: translateX(-2px); }

.page-title { font-size: 1.5rem; font-weight: 700; color: #0F172A; margin: 0; }
.page-subtitle { font-size: 0.9rem; color: #64748B; margin: 4px 0 0 0; }

.modern-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
}
.card-title { font-size: 1rem; font-weight: 600; color: #0F172A; margin: 0 0 20px 0; display: flex; align-items: center; }

/* ==========================================
   MODERN FORMS & SWITCHES
   ========================================== */
.modern-switch {
    display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none;
}
.modern-switch input { display: none; }
.switch-slider {
    width: 44px; height: 24px; background: #CBD5E1; border-radius: 99px;
    position: relative; transition: background 0.3s ease;
}
.switch-slider::after {
    content: ''; position: absolute; top: 2px; left: 2px;
    width: 20px; height: 20px; background: white; border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #3B82F6; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(20px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.95rem; }

.form-label-modern { font-size: 0.85rem; font-weight: 600; color: #64748B; margin-bottom: 6px; display: block; }
.input-group-modern { position: relative; }
.input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; }
.form-input-modern {
    width: 100%; padding: 10px 14px 10px 40px;
    border: 1px solid #E2E8F0; border-radius: 10px;
    font-size: 0.95rem; transition: all 0.2s; background: #F8FAFC;
}
.form-input-modern:focus { outline: none; border-color: #3B82F6; background: white; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

.btn-download-qr {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 12px;
    background: linear-gradient(135deg, #0F172A 0%, #334155 100%);
    color: white; border-radius: 12px; text-decoration: none;
    font-weight: 600; font-size: 0.95rem;
    transition: all 0.2s; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
}
.btn-download-qr:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15, 23, 42, 0.25); }

/* ==========================================
   TABS & CHIPS
   ========================================== */
.modern-tabs {
    display: flex; gap: 8px; background: #E2E8F0; padding: 4px;
    border-radius: 12px; width: fit-content;
}
.tab-btn {
    padding: 8px 20px; border: none; background: transparent;
    border-radius: 8px; font-weight: 600; font-size: 0.9rem; color: #64748B;
    cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;
}
.tab-btn.active { background: white; color: #0F172A; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.tab-btn:hover:not(.active) { color: #334155; }

.filter-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.chip {
    padding: 6px 16px; border: 1px solid #E2E8F0; background: white;
    border-radius: 99px; font-size: 0.85rem; font-weight: 500; color: #64748B;
    cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px;
}
.chip:hover { border-color: #94A3B8; color: #334155; }
.chip.active { background: #0F172A; color: white; border-color: #0F172A; }

/* ==========================================
   CONFIGURATOR LIST
   ========================================== */
.config-list { display: flex; flex-direction: column; gap: 12px; }
.config-table-card {
    background: white; border: 1px solid #E2E8F0; border-radius: 14px;
    padding: 16px; transition: all 0.2s;
}
.config-table-card:hover { border-color: #94A3B8; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }

.table-preview-visual {
    width: 90px; height: 90px; flex-shrink: 0;
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
}
/* Эффект чертежа/пола */
.floor-plan-bg {
    background-color: #F1F5F9;
    background-image: radial-gradient(#CBD5E1 1px, transparent 1px);
    background-size: 12px 12px;
}

.table-card-title { font-size: 1rem; color: #0F172A; }
.badge-capacity {
    display: inline-block; padding: 2px 8px; background: #F1F5F9;
    color: #475569; border-radius: 6px; font-size: 0.75rem; font-weight: 600; margin-top: 4px;
}

.action-buttons { display: flex; gap: 8px; }
.btn-action {
    width: 36px; height: 36px; border-radius: 8px; border: 1px solid #E2E8F0;
    background: white; color: #64748B; cursor: pointer; transition: all 0.2s;
    display: flex; align-items: center; justify-content: center;
}
.btn-action:hover { background: #F8FAFC; color: #0F172A; border-color: #CBD5E1; }
.btn-edit:hover { color: #3B82F6; border-color: #3B82F6; background: #EFF6FF; }
.btn-delete:hover { color: #EF4444; border-color: #EF4444; background: #FEF2F2; }

.edit-mode-row {
    display: flex; gap: 12px; margin-top: 12px; padding-top: 12px;
    border-top: 1px dashed #E2E8F0; animation: fadeIn 0.3s ease;
}
.edit-field { display: flex; flex-direction: column; gap: 4px; }
.edit-field label { font-size: 0.75rem; font-weight: 600; color: #64748B; }
.form-input-sm {
    padding: 6px 10px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.85rem; transition: all 0.2s;
}
.form-input-sm:focus { outline: none; border-color: #3B82F6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1); }

/* ==========================================
   TEMPLATE GRID (SELECTOR)
   ========================================== */
.templates-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;
}
.template-card {
    background: white; border: 2px solid #E2E8F0; border-radius: 16px;
    overflow: hidden; cursor: pointer; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.template-card:hover { transform: translateY(-4px); border-color: #94A3B8; box-shadow: 0 12px 24px -8px rgba(0,0,0,0.1); }
.template-card.is-selected { border-color: #10B981; background: #F0FDF4; }
.template-card.is-selected:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(16, 185, 129, 0.2); }

.template-visual-wrapper {
    height: 140px; display: flex; align-items: center; justify-content: center;
    position: relative;
}

.template-info { padding: 16px; border-top: 1px solid #F1F5F9; }
.template-title { font-size: 0.9rem; font-weight: 600; color: #334155; margin: 0 0 8px 0; line-height: 1.4; }
.template-action {
    display: flex; align-items: center; justify-content: space-between;
    font-size: 0.85rem; font-weight: 600;
}
.template-card:not(.is-selected) .template-action { color: #3B82F6; }
.template-card.is-selected .template-action { color: #059669; }

.seats-badge {
    position: absolute; top: 10px; right: 10px;
    padding: 4px 10px; background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px); border: 1px solid #E2E8F0;
    border-radius: 99px; font-size: 0.75rem; font-weight: 700; color: #475569;
    display: flex; align-items: center; gap: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* ==========================================
   EMPTY STATE
   ========================================== */
.empty-state-modern {
    text-align: center; padding: 60px 20px; color: #94A3B8;
}
.empty-icon-wrapper {
    width: 64px; height: 64px; background: #F1F5F9; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px; font-size: 1.5rem; color: #CBD5E1;
}
.empty-state-modern h5 { color: #334155; font-weight: 600; margin-bottom: 8px; }

/* ==========================================
   🪑 ENHANCED CSS TABLES (The Star of the Show)
   ========================================== */
.css-table { position: relative; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; z-index: 1; }
.css-table-sm { width: 70px; height: 70px; }

.table-top {
    background: linear-gradient(145deg, #3B82F6 0%, #2563EB 100%);
    box-shadow: inset 0 2px 4px rgba(255,255,255,0.3), 0 8px 16px -4px rgba(37, 99, 235, 0.4);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; border: 2px solid rgba(255,255,255,0.2);
    z-index: 2; transition: all 0.3s;
}
.css-table-sm .table-top { width: 40px !important; height: 40px !important; font-size: 0.75rem !important; }
.shape-round .table-top { border-radius: 50%; width: 50px; height: 50px; }
.shape-square .table-top { border-radius: 10px; width: 55px; height: 55px; }
.shape-rect .table-top { border-radius: 12px; width: 70px; height: 45px; }

/* Стулья и диваны с 3D-эффектом */
.seat { position: absolute; z-index: 1; transition: all 0.3s ease; }
.type-chair {
    background: linear-gradient(145deg, #94A3B8 0%, #64748B 100%);
    border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2), inset 0 1px 1px rgba(255,255,255,0.3);
    border: 1px solid rgba(255,255,255,0.1);
}
.css-table-sm .type-chair { width: 16px !important; height: 16px !important; }

.type-sofa {
    background: linear-gradient(145deg, #8B5CF6 0%, #7C3AED 100%);
    border-radius: 8px; box-shadow: 0 6px 10px -2px rgba(124, 58, 237, 0.3), inset 0 1px 1px rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.15);
}

/* Позиции (адаптированы под оба размера) */
.pos-top { top: -4px; left: 50%; transform: translateX(-50%); width: 40px; height: 14px; }
.css-table-sm .pos-top { width: 28px !important; height: 10px !important; }
.pos-bottom { bottom: -4px; left: 50%; transform: translateX(-50%); width: 20px; height: 20px; }
.pos-bottom-left { bottom: 2px; left: 15%; transform: translateX(-50%); width: 20px; height: 20px; }
.pos-bottom-right { bottom: 2px; right: 15%; transform: translateX(50%); width: 20px; height: 20px; }
.pos-left { left: -4px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; }
.pos-right { right: -4px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; }
.pos-tl { top: 6px; left: 6px; width: 16px; height: 16px; }
.css-table-sm .pos-tl { width: 12px !important; height: 12px !important; top: 4px !important; left: 4px !important; }
.pos-br { bottom: 6px; right: 6px; width: 16px; height: 16px; }
.css-table-sm .pos-br { width: 12px !important; height: 12px !important; bottom: 4px !important; right: 4px !important; }

/* Адаптивность */
@media (max-width: 576px) {
    .templates-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .edit-mode-row { flex-direction: column; gap: 8px; }
    .modern-header { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>
