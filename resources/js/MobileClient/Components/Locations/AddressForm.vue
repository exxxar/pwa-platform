<template>
    <form class="address-form" @submit.prevent="submit">

        <!-- ========================================== -->
        <!-- СЕКЦИЯ: НАЗВАНИЕ АДРЕСА -->
        <!-- ========================================== -->
        <div class="form-section">
            <div class="section-label">
                <i class="fa-solid fa-tag"></i>
                <span>Название адреса</span>
            </div>

            <!-- Пресеты -->
            <div class="preset-chips">
                <button
                    v-for="preset in presets"
                    :key="preset.value"
                    type="button"
                    class="preset-chip"
                    :class="{ 'active': form.title === preset.value }"
                    @click="selectPreset(preset)"
                >
                    <i :class="preset.icon"></i>
                    <span>{{ preset.label }}</span>
                </button>
            </div>

            <!-- Своё название -->
            <div class="form-field">
                <div class="field-icon">
                    <i class="fa-solid fa-pen"></i>
                </div>
                <div class="field-content">
                    <label>Название</label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Например: Офис, Дача..."
                        required
                        class="form-input"
                        maxlength="50"
                    >
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СЕКЦИЯ: АДРЕС -->
        <!-- ========================================== -->
        <div class="form-section">
            <div class="section-label">
                <i class="fa-solid fa-location-dot"></i>
                <span>Адрес</span>
                <span class="required">*</span>
            </div>

            <!-- Карта (если доступна) -->
            <div v-if="settings?.map_tiler" class="map-wrapper">
                <YandexMapPicker
                    :mapKey="settings.map_tiler"
                    v-model:lat="form.lat"
                    v-model:lng="form.lng"
                    v-model:address="form.address"
                    v-model:city="form.city"
                />
            </div>

            <!-- Поле адреса -->
            <div class="form-field">
                <div class="field-icon">
                    <i class="fa-solid fa-house"></i>
                </div>
                <div class="field-content">
                    <label>Улица, дом, квартира <span class="required">*</span></label>
                    <input
                        v-model="form.address"
                        type="text"
                        placeholder="Например: ул. Ленина, д. 10, кв. 25"
                        required
                        class="form-input"
                    >
                </div>
            </div>

            <!-- Город и страна -->
            <div class="form-row">
                <div class="form-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-city"></i>
                    </div>
                    <div class="field-content">
                        <label>Город</label>
                        <input
                            v-model="form.city"
                            type="text"
                            placeholder="Москва"
                            class="form-input"
                        >
                    </div>
                </div>

                <div class="form-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-globe"></i>
                    </div>
                    <div class="field-content">
                        <label>Страна</label>
                        <input
                            v-model="form.country"
                            type="text"
                            placeholder="Россия"
                            class="form-input"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СЕКЦИЯ: КООРДИНАТЫ (СКРЫТАЯ) -->
        <!-- ========================================== -->
        <div class="form-section advanced-section">
            <button
                type="button"
                class="advanced-toggle"
                @click="showCoordinates = !showCoordinates"
            >
                <div class="toggle-content">
                    <i class="fa-solid fa-map-pin"></i>
                    <span>Координаты</span>
                    <span class="advanced-hint">(для точного позиционирования)</span>
                </div>
                <i class="fa-solid fa-chevron-down toggle-arrow" :class="{ 'rotated': showCoordinates }"></i>
            </button>

            <transition name="slide-down">
                <div v-if="showCoordinates" class="coordinates-fields">
                    <div class="form-row">
                        <div class="form-field">
                            <div class="field-icon">
                                <i class="fa-solid fa-arrows-up-down"></i>
                            </div>
                            <div class="field-content">
                                <label>Широта (Lat)</label>
                                <input
                                    v-model.number="form.lat"
                                    type="number"
                                    step="any"
                                    placeholder="55.7558"
                                    class="form-input"
                                >
                            </div>
                        </div>

                        <div class="form-field">
                            <div class="field-icon">
                                <i class="fa-solid fa-arrows-left-right"></i>
                            </div>
                            <div class="field-content">
                                <label>Долгота (Lng)</label>
                                <input
                                    v-model.number="form.lng"
                                    type="number"
                                    step="any"
                                    placeholder="37.6173"
                                    class="form-input"
                                >
                            </div>
                        </div>
                    </div>

                    <div v-if="form.lat && form.lng" class="coordinates-hint">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Координаты определены</span>
                    </div>
                </div>
            </transition>
        </div>

        <!-- ========================================== -->
        <!-- СЕКЦИЯ: ОСНОВНОЙ АДРЕС -->
        <!-- ========================================== -->
        <div class="form-section">
            <button
                type="button"
                class="default-toggle"
                :class="{ 'active': form.is_default }"
                @click="form.is_default = !form.is_default"
            >
                <div class="toggle-icon">
                    <i class="fa-solid fa-star"></i>
                </div>
                <div class="toggle-info">
                    <div class="toggle-title">Сделать основным адресом</div>
                    <div class="toggle-desc">Будет использоваться по умолчанию</div>
                </div>
                <div class="toggle-switch">
                    <div class="switch-track" :class="{ 'active': form.is_default }">
                        <div class="switch-thumb"></div>
                    </div>
                </div>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ОШИБКА -->
        <!-- ========================================== -->
        <div v-if="errorMessage" class="error-banner">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ errorMessage }}</span>
        </div>

        <!-- ========================================== -->
        <!-- КНОПКИ ДЕЙСТВИЙ -->
        <!-- ========================================== -->
        <div class="form-actions">
            <button
                type="button"
                class="cancel-btn"
                @click="$emit('close')"
                :disabled="isSubmitting"
            >
                Отмена
            </button>
            <button
                type="submit"
                class="submit-btn"
                :disabled="isSubmitting || !isValid"
            >
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fa-solid fa-check me-2"></i>
                {{ isSubmitting ? 'Сохранение...' : 'Сохранить адрес' }}
            </button>
        </div>

    </form>
</template>

<script>
import YandexMapPicker from "@/MobileClient/Components/Cart/YandexMapPicker.vue";
import { useAddressesStore } from "@/MobileClient/stores/Shop/addresses.js";

export default {
    name: "AddressForm",

    components: {
        YandexMapPicker,
    },

    emits: ['close', 'saved'],

    setup() {
        const store = useAddressesStore();
        return { store };
    },

    data() {
        return {
            isSubmitting: false,
            showCoordinates: false,
            errorMessage: '',
            form: {
                title: 'Дом',
                address: '',
                city: '',
                country: '',
                lat: null,
                lng: null,
                is_default: false,
            },
            presets: [
                { value: 'Дом', label: 'Дом', icon: 'fa-solid fa-house' },
                { value: 'Работа', label: 'Работа', icon: 'fa-solid fa-briefcase' },
                { value: 'Родители', label: 'Родители', icon: 'fa-solid fa-people-roof' },
                { value: 'Дача', label: 'Дача', icon: 'fa-solid fa-tree' },
                { value: 'Другое', label: 'Другое', icon: 'fa-solid fa-location-dot' },
            ],
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        isValid() {
            return (
                this.form.title.trim() !== '' &&
                this.form.address.trim() !== ''
            );
        },
    },

    methods: {
        selectPreset(preset) {
            this.form.title = preset.value;
        },

        async submit() {
            if (!this.isValid) {
                this.errorMessage = 'Заполните обязательные поля';
                return;
            }

            this.isSubmitting = true;
            this.errorMessage = '';

            try {
                await this.store.storeAddress({ form: this.form });

                this.$notify?.({
                    title: 'Адрес',
                    text: 'Адрес успешно сохранён',
                    type: 'success',
                });

                this.$emit('saved');
                this.reset();

            } catch (error) {
                console.error('Ошибка сохранения адреса:', error);
                this.errorMessage = error.response?.data?.message || 'Не удалось сохранить адрес';

                this.$notify?.({
                    title: 'Ошибка',
                    text: this.errorMessage,
                    type: 'error',
                });
            } finally {
                this.isSubmitting = false;
            }
        },

        reset() {
            this.form = {
                title: '',
                address: '',
                city: '',
                country: '',
                lat: null,
                lng: null,
                is_default: false,
            };
            this.showCoordinates = false;
            this.errorMessage = '';
        },
    },
};
</script>

<style scoped>
.address-form {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.form-section {
    margin-bottom: 20px;
}

.section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.section-label i {
    color: var(--bs-primary);
}

.required {
    color: #dc3545;
    margin-left: 2px;
}

/* ==========================================
   ПРЕСЕТЫ НАЗВАНИЙ
   ========================================== */
.preset-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.preset-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 20px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.preset-chip:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    transform: translateY(-1px);
}

.preset-chip.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.preset-chip i {
    font-size: 0.85rem;
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
    margin-bottom: 10px;
    transition: all 0.2s ease;
}

.form-field:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.field-icon {
    width: 48px;
    display: flex;
    align-items: normal;
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

/* Ряд полей */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

/* ==========================================
   КАРТА
   ========================================== */
.map-wrapper {
    margin-bottom: 12px;
    border-radius: 14px;
    overflow: hidden;
    padding: 5px;
    border: 2px solid var(--bs-border-color);
}

/* ==========================================
   КООРДИНАТЫ (СКРЫТАЯ СЕКЦИЯ)
   ========================================== */
.advanced-section {
    background: var(--bs-secondary-bg, #f8f9fa);
    border-radius: 14px;
    padding: 12px 14px;
    margin-bottom: 20px;
}

.advanced-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--bs-body-color);
}

.toggle-content {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
}

.toggle-content i {
    color: var(--bs-primary);
}

.advanced-hint {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    font-weight: 400;
    text-transform: none;
    letter-spacing: 0;
}

.toggle-arrow {
    color: var(--bs-secondary-color);
    font-size: 0.75rem;
    transition: transform 0.3s ease;
}

.toggle-arrow.rotated {
    transform: rotate(180deg);
}

.coordinates-fields {
    margin-top: 14px;
}

.coordinates-hint {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: rgba(25, 135, 84, 0.08);
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 10px;
    font-size: 0.8rem;
    color: #198754;
    font-weight: 500;
}

.coordinates-hint i {
    font-size: 0.85rem;
}

/* ==========================================
   TOGGLE "СДЕЛАТЬ ОСНОВНЫМ"
   ========================================== */
.default-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.default-toggle:hover {
    border-color: rgba(var(--bs-primary-rgb), 0.3);
}

.default-toggle.active {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
}

.toggle-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.default-toggle.active .toggle-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.toggle-info {
    flex: 1;
    min-width: 0;
}

.toggle-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
}

.toggle-desc {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.toggle-switch {
    flex-shrink: 0;
}

.switch-track {
    width: 44px;
    height: 24px;
    border-radius: 12px;
    background: var(--bs-border-color);
    position: relative;
    transition: background 0.3s ease;
}

.switch-track.active {
    background: var(--bs-primary);
}

.switch-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.switch-track.active .switch-thumb {
    transform: translateX(20px);
}

/* ==========================================
   ОШИБКА
   ========================================== */
.error-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: rgba(220, 53, 69, 0.08);
    border: 1px solid rgba(220, 53, 69, 0.2);
    border-radius: 12px;
    color: #dc3545;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

.error-banner i {
    font-size: 1rem;
    flex-shrink: 0;
}

/* ==========================================
   КНОПКИ ДЕЙСТВИЙ
   ========================================== */
.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.cancel-btn {
    flex: 1;
    padding: 14px 20px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cancel-btn:hover:not(:disabled) {
    background: var(--bs-border-color);
}

.cancel-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.submit-btn {
    flex: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px 20px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
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
    max-height: 300px;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .preset-chips {
        gap: 6px;
    }

    .preset-chip {
        padding: 6px 12px;
        font-size: 0.8rem;
    }

    .toggle-title {
        font-size: 0.9rem;
    }

    .toggle-desc {
        font-size: 0.7rem;
    }

    .form-actions {
        flex-direction: column;
    }

    .cancel-btn,
    .submit-btn {
        flex: 1;
    }
}
</style>
