<template>
    <div v-if="deliveryForm" class="delivery-form">

        <!-- ========================================== -->
        <!-- РЕЖИМ 1: УПРОЩЁННАЯ ФОРМА -->
        <!-- ========================================== -->
        <template v-if="mode === 1">
            <div class="form-section">
                <div class="form-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="field-content">
                        <label>ФИО <span class="required">*</span></label>
                        <input
                            type="text"
                            v-model="deliveryForm.name"
                            placeholder="Иванов Иван Иванович"
                            required
                            class="form-input"
                        >
                    </div>
                </div>

                <div class="form-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="field-content">
                        <label>Телефон <span class="required">*</span></label>
                        <input
                            type="tel"
                            v-model="deliveryForm.phone"
                            placeholder="+7 (999) 123-45-67"
                            required
                            class="form-input"
                            @input="formatPhone"
                        >
                    </div>
                </div>

                <div class="form-field textarea-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-message"></i>
                    </div>
                    <div class="field-content">
                        <label>Комментарий</label>
                        <textarea
                            v-model="deliveryForm.info"
                            placeholder="Дополнительная информация для сотрудника..."
                            rows="4"
                            class="form-input form-textarea"
                        ></textarea>
                    </div>
                </div>
            </div>
        </template>

        <!-- ========================================== -->
        <!-- РЕЖИМ 0: ПОЛНАЯ ФОРМА -->
        <!-- ========================================== -->
        <template v-if="mode === 0">

            <!-- Контактные данные -->
            <div class="form-section">
                <div class="section-label">Контактные данные</div>

                <div class="form-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="field-content">
                        <label>ФИО <span class="required">*</span></label>
                        <input
                            type="text"
                            v-model="deliveryForm.name"
                            placeholder="Иванов Иван Иванович"
                            required
                            class="form-input"
                        >
                    </div>
                </div>

                <div class="form-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="field-content">
                        <label>Телефон <span class="required">*</span></label>
                        <input
                            type="tel"
                            v-model="deliveryForm.phone"
                            placeholder="+7 (999) 123-45-67"
                            required
                            class="form-input"
                            @input="formatPhone"
                        >
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ДОСТАВКА -->
            <!-- ========================================== -->
            <template v-if="!deliveryForm.need_pickup">
                <div class="form-section ">
                    <div class="section-label">Адрес доставки</div>

                    <AddressList
                        v-model:address="deliveryForm.address"
                        v-model:location_id="deliveryForm.location_id"
                    />

                    <slot name="loadingDeliveryData"></slot>
                </div>

                <!-- Время доставки -->
                <div class="form-section">
                    <div class="section-label">Когда приготовить?</div>

                    <div class="time-selector">
                        <button
                            type="button"
                            class="time-option"
                            :class="{ 'active': deliveryForm.when_ready }"
                            @click="deliveryForm.when_ready = true"
                        >
                            <i class="fa-solid fa-stopwatch"></i>
                            <div class="time-info">
                                <div class="time-title">По готовности</div>
                                <div class="time-desc">Как можно быстрее</div>
                            </div>
                        </button>
                        <button
                            type="button"
                            class="time-option"
                            :class="{ 'active': !deliveryForm.when_ready }"
                            @click="deliveryForm.when_ready = false"
                        >
                            <i class="fa-regular fa-clock"></i>
                            <div class="time-info">
                                <div class="time-title">К указанному времени</div>
                                <div class="time-desc">Выбрать точное время</div>
                            </div>
                        </button>
                    </div>

                    <div v-if="!deliveryForm.when_ready" class="form-field mt-3">
                        <div class="field-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div class="field-content">
                            <label>Время доставки <span class="required">*</span></label>
                            <input
                                type="datetime-local"
                                v-model="deliveryForm.time"
                                required
                                class="form-input"
                            >
                        </div>
                    </div>
                </div>
            </template>

            <!-- ========================================== -->
            <!-- САМОВЫВОЗ -->
            <!-- ========================================== -->
            <template v-else>
                <div class="form-section">
                    <div class="section-label">Номер столика</div>

                    <!-- Переключатель способа выбора -->
                    <div v-if="settings.need_table_list" class="table-select-toggle">
                        <label class="toggle-label">
                            <input
                                type="checkbox"
                                v-model="needSelectTableByNumber"
                                class="toggle-input"
                            >
                            <span class="toggle-text">Выбрать из списка</span>
                        </label>
                    </div>

                    <!-- Список номеров -->
                    <div v-if="needSelectTableByNumber" class="table-numbers-grid">
                        <button
                            v-for="num in parseInt(settings.max_tables) || 10"
                            :key="num"
                            type="button"
                            class="table-number-btn"
                            :class="{ 'active': deliveryForm.table_number == num }"
                            @click="deliveryForm.table_number = num"
                        >
                            №{{ num }}
                        </button>
                    </div>

                    <!-- Ручной ввод -->
                    <div v-else-if="deliveryForm.pick_up_type == 0" class="form-field">
                        <div class="field-icon">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <div class="field-content">
                            <label>Номер столика</label>
                            <input
                                type="number"
                                min="1"
                                max="200"
                                v-model.number="deliveryForm.table_number"
                                placeholder="Например: 5"
                                class="form-input"
                            >
                        </div>
                    </div>
                </div>
            </template>

            <!-- Комментарий -->
            <div class="form-section">
                <div class="section-label">Комментарий</div>
                <div class="form-field textarea-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-message"></i>
                    </div>
                    <div class="field-content">
                        <label>{{ deliveryForm.need_pickup ? 'Для сотрудника' : 'Для курьера' }}</label>
                        <textarea
                            v-model="deliveryForm.info"
                            :placeholder="deliveryForm.need_pickup
                                ? 'Например: позвоните за 5 минут до готовности'
                                : 'Например: код домофона 1234, 3 этаж'"
                            rows="3"
                            class="form-input form-textarea"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ОГРАНИЧЕНИЯ ПО ЗДОРОВЬЮ -->
            <!-- ========================================== -->
            <template v-if="settings.need_health_restrictions && !deliveryForm.need_pickup">
                <div class="form-section">
                    <div class="section-label">Ограничения по здоровью</div>

                    <div class="health-toggle">
                        <button
                            type="button"
                            class="health-option"
                            :class="{ 'active': !deliveryForm.has_disability }"
                            @click="deliveryForm.has_disability = false"
                        >
                            <i class="fa-regular fa-heart"></i>
                            <span>Нет ограничений</span>
                        </button>
                        <button
                            type="button"
                            class="health-option"
                            :class="{ 'active': deliveryForm.has_disability }"
                            @click="deliveryForm.has_disability = true"
                        >
                            <i class="fa-solid fa-house-medical-flag"></i>
                            <span>Есть ограничения</span>
                        </button>
                    </div>

                    <!-- Список ограничений -->
                    <transition name="slide-down">
                        <div v-if="deliveryForm.has_disability" class="disabilities-list">
                            <label
                                v-for="disability in disabilitiesOptions"
                                :key="disability.value"
                                class="disability-item"
                                :class="{ 'active': deliveryForm.disabilities.includes(disability.value) }"
                            >
                                <div class="disability-icon">
                                    <i :class="disability.icon"></i>
                                </div>
                                <div class="disability-info">
                                    <div class="disability-title">{{ disability.title }}</div>
                                </div>
                                <input
                                    type="checkbox"
                                    :value="disability.value"
                                    v-model="deliveryForm.disabilities"
                                    class="disability-checkbox"
                                >
                                <div class="disability-check">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            </label>

                            <!-- Поле для аллергии -->
                            <transition name="slide-down">
                                <div v-if="deliveryForm.disabilities.includes('пищевая аллергия')" class="form-field mt-3">
                                    <div class="field-icon">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                    </div>
                                    <div class="field-content">
                                        <label>На что аллергия <span class="required">*</span></label>
                                        <input
                                            type="text"
                                            v-model="deliveryForm.allergy"
                                            placeholder="Например: орехи, лактоза"
                                            required
                                            class="form-input"
                                        >
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </transition>
                </div>
            </template>

        </template>

    </div>
</template>

<script>
import AddressList from "@/MobileClient/Components/Locations/AddressList.vue";

export default {
    name: "DeliveryForm",

    components: {
        AddressList,
    },

    props: {
        modelValue: {
            type: Object,
            required: true,
        },
        mode: {
            type: Number,
            default: 0,
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            deliveryForm: null,
            needSelectTableByNumber: false,
            disabilitiesOptions: [
                { value: 'болею', icon: 'fa-solid fa-head-side-mask', title: 'Болею' },
                { value: 'плохо слышит или говорит', icon: 'fa-solid fa-ear-deaf', title: 'Плохо слышу / говорю' },
                { value: 'слабовидящий', icon: 'fa-solid fa-glasses', title: 'Слабовидящий' },
                { value: 'ограничения мобильности', icon: 'fa-solid fa-wheelchair', title: 'Ограничения мобильности' },
                { value: 'пищевая аллергия', icon: 'fa-solid fa-person-dots-from-line', title: 'Пищевая аллергия' },
            ],
        };
    },

    watch: {
        deliveryForm: {
            handler(newValue) {
                // Сохранение в localStorage
                this.saveToLocalStorage(newValue);
                this.$emit('update:modelValue', newValue);
            },
            deep: true,
        },
        modelValue: {
            handler(newValue) {
                this.deliveryForm = newValue;
            },
            deep: true,
        },
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },
        settings() {
            return this.tenant?.settings || {};
        },
    },

    mounted() {
        this.deliveryForm = this.modelValue;
        this.loadFromLocalStorage();
    },

    methods: {
        // Форматирование телефона
        formatPhone() {
            let value = this.deliveryForm.phone.replace(/\D/g, '');

            if (value.startsWith('8')) value = '7' + value.slice(1);
            if (!value.startsWith('7') && value.length > 0) value = '7' + value;

            let formatted = '';
            if (value.length > 0) formatted = '+7';
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 5) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 8) formatted += '-' + value.slice(7, 9);
            if (value.length >= 10) formatted += '-' + value.slice(9, 11);

            this.deliveryForm.phone = formatted;
        },

        // Сохранение в localStorage
        saveToLocalStorage(form) {
            if (!form) return;

            const fields = [
                'name', 'phone', 'address', 'city', 'street',
                'building', 'flat_number', 'entrance_number',
            ];

            fields.forEach(field => {
                if (form[field] !== undefined) {
                    localStorage.setItem(`mypwa_delivery_${field}`, form[field] || '');
                }
            });

            if (form.disabilities?.length > 0) {
                localStorage.setItem('mypwa_delivery_disabilities', JSON.stringify(form.disabilities));
            } else {
                localStorage.removeItem('mypwa_delivery_disabilities');
            }
        },

        // Загрузка из localStorage
        loadFromLocalStorage() {
            const fields = [
                'name', 'phone', 'address', 'city', 'street',
                'building', 'flat_number', 'entrance_number',
            ];

            fields.forEach(field => {
                const saved = localStorage.getItem(`mypwa_delivery_${field}`);
                if (saved !== null && this.deliveryForm[field] === null) {
                    this.deliveryForm[field] = saved;
                }
            });

            const disabilities = localStorage.getItem('mypwa_delivery_disabilities');
            if (disabilities) {
                try {
                    this.deliveryForm.disabilities = JSON.parse(disabilities);
                    if (this.deliveryForm.disabilities.length > 0) {
                        this.deliveryForm.has_disability = true;
                    }
                } catch (e) {
                    console.error('Ошибка загрузки ограничений:', e);
                }
            }
        },
    },
};
</script>

<style scoped>
.delivery-form {
    display: flex;
    flex-direction: column;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.form-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
}

.required {
    color: #dc3545;
}

/* ==========================================
   ПОЛЯ ФОРМЫ
   ========================================== */
.form-field {
    display: flex;
    align-items: stretch;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 12px;
    transition: all 0.2s ease;
}

.form-field:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.field-icon {
    width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1rem;
    flex-shrink: 0;
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.field-content {
    flex: 1;
    padding: 10px 14px 10px 0;
    min-width: 0;
}

.field-content label {
    display: block;
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 600;
}

.form-input {
    width: 100%;
    border: none;
    background: transparent;
    padding: 4px 0;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--bs-body-color);
    outline: none;
}

.form-input::placeholder {
    color: var(--bs-secondary-color);
    font-weight: 400;
}

/* Textarea */
.textarea-field {
    align-items: flex-start;
}

.form-textarea {
    resize: none;
    min-height: 60px;
    line-height: 1.5;
    padding: 6px 0;
    font-family: inherit;
}

/* ==========================================
   СЕЛЕКТОР ВРЕМЕНИ
   ========================================== */
.time-selector {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.time-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.time-option:hover {
    border-color: var(--bs-primary);
}

.time-option.active {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
}

.time-option i {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.time-option.active i {
    background: var(--bs-primary);
    color: white;
}

.time-info {
    flex: 1;
    min-width: 0;
}

.time-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.time-desc {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   НОМЕРА СТОЛИКОВ
   ========================================== */
.table-select-toggle {
    margin-bottom: 12px;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.toggle-input {
    width: 18px;
    height: 18px;
    accent-color: var(--bs-primary);
}

.table-numbers-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
}

.table-number-btn {
    padding: 12px 8px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 10px;
    color: var(--bs-body-color);
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.table-number-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.table-number-btn.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

/* ==========================================
   ОГРАНИЧЕНИЯ ПО ЗДОРОВЬЮ
   ========================================== */
.health-toggle {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

.health-option {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.health-option:hover {
    border-color: var(--bs-primary);
}

.health-option.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

.health-option i {
    font-size: 1rem;
}

.disabilities-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.disability-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.disability-item:hover {
    border-color: var(--bs-primary);
}

.disability-item.active {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, transparent 100%);
}

.disability-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.disability-item.active .disability-icon {
    background: var(--bs-primary);
    color: white;
}

.disability-info {
    flex: 1;
}

.disability-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.disability-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.disability-check {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.disability-item.active .disability-check {
    opacity: 1;
    transform: scale(1);
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 500px;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .time-selector {
        grid-template-columns: 1fr;
    }

    .table-numbers-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .health-toggle {
        grid-template-columns: 1fr;
    }

    .time-option i {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
    }

    .time-title {
        font-size: 0.8rem;
    }

    .time-desc {
        font-size: 0.65rem;
    }
}
</style>
