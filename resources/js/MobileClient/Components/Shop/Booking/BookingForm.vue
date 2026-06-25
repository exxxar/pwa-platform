<template>
    <div class="booking-form-container">

        <!-- ========================================== -->
        <!-- КАРТОЧКА СТОЛИКА -->
        <!-- ========================================== -->
        <div v-if="reservation.table" class="table-info-card">
            <div class="table-info-icon">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <div class="table-info-content">
                <div class="table-info-title">
                    Столик №{{ reservation.table.number }}
                </div>
                <div class="table-info-description">
                    {{ reservation.table.description || 'Свободен для бронирования' }}
                </div>
                <div class="table-info-meta">
                    <span class="meta-item">
                        <i class="fa-solid fa-user-group"></i>
                        {{ reservation.table.seats || 2 }} мест
                    </span>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PILL-ТАБЫ -->
        <!-- ========================================== -->
        <div class="tabs-wrapper">
            <div class="tabs-container">
                <button
                    class="tab-item"
                    :class="{ active: tab === 'form' }"
                    @click="tab = 'form'"
                >
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Бронирование</span>
                </button>
                <button
                    class="tab-item"
                    :class="{ active: tab === 'list' }"
                    @click="tab = 'list'"
                >
                    <i class="fa-solid fa-list-check"></i>
                    <span>Брони на дату</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ТАБ: ФОРМА БРОНИРОВАНИЯ -->
        <!-- ========================================== -->
        <div v-show="tab === 'form'" class="tab-content">
            <form @submit.prevent="submitReservation" class="reservation-form">

                <!-- Дата и время -->
                <div class="form-section">
                    <div class="section-label">
                        <i class="fa-solid fa-calendar-day"></i>
                        <span>Когда</span>
                    </div>
                    <div class="datetime-row">
                        <div class="form-field date-field">
                            <div class="field-icon">
                                <i class="fa-solid fa-calendar"></i>
                            </div>
                            <div class="field-content">
                                <label for="reservationDate">Дата</label>
                                <input
                                    type="date"
                                    id="reservationDate"
                                    v-model="reservation.date"
                                    :min="minDate"
                                    required
                                    class="form-input"
                                >
                            </div>
                        </div>
                        <div class="form-field time-field">
                            <div class="field-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="field-content">
                                <label for="reservationTime">Время</label>
                                <input
                                    type="time"
                                    id="reservationTime"
                                    v-model="reservation.time"
                                    required
                                    class="form-input"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Количество гостей -->
                <div class="form-section">
                    <div class="section-label">
                        <i class="fa-solid fa-user-group"></i>
                        <span>Количество гостей</span>
                    </div>
                    <div class="persons-selector">
                        <button
                            type="button"
                            class="persons-btn"
                            :disabled="reservation.persons <= 1"
                            @click="decreasePersons"
                        >
                            <i class="fa-solid fa-minus"></i>
                        </button>

                        <div class="persons-display">
                            <div class="persons-value">{{ reservation.persons }}</div>
                            <div class="persons-label">
                                {{ pluralize(reservation.persons, 'гость', 'гостя', 'гостей') }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="persons-btn"
                            :disabled="reservation.persons >= maxPersons"
                            @click="increasePersons"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="persons-hint">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        Максимум {{ maxPersons }} гостей для этого столика
                    </div>
                </div>

                <!-- Контактные данные -->
                <div class="form-section">
                    <div class="section-label">
                        <i class="fa-solid fa-address-card"></i>
                        <span>Контактные данные</span>
                    </div>

                    <div class="form-field">
                        <div class="field-icon">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="field-content">
                            <label for="userName">Имя</label>
                            <input
                                type="text"
                                id="userName"
                                v-model="reservation.name"
                                placeholder="На чьё имя бронировать"
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
                            <label for="userPhone">Телефон</label>
                            <input
                                type="tel"
                                id="userPhone"
                                v-model="reservation.phone"
                                placeholder="+7 (___) ___-__-__"
                                required
                                class="form-input"
                                @input="formatPhone"
                            >
                        </div>
                    </div>
                </div>

                <!-- Пожелания -->
                <div class="form-section">
                    <div class="section-label">
                        <i class="fa-solid fa-comment-dots"></i>
                        <span>Пожелания <span class="optional">(необязательно)</span></span>
                    </div>
                    <div class="form-field textarea-field">
                        <div class="field-icon">
                            <i class="fa-solid fa-message"></i>
                        </div>
                        <div class="field-content">
                            <textarea
                                id="reservationDescription"
                                v-model="reservation.description"
                                rows="3"
                                placeholder="Например: у окна, нужен детский стульчик, день рождения..."
                                class="form-input form-textarea"
                                maxlength="500"
                            ></textarea>
                            <div class="textarea-counter">
                                {{ reservation.description.length }} / 500
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Кнопка бронирования -->
                <button
                    type="submit"
                    class="submit-btn"
                    :disabled="isSubmitting"
                >
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fa-solid fa-calendar-check me-2"></i>
                    {{ isSubmitting ? 'Бронируем...' : 'Забронировать столик' }}
                </button>

            </form>
        </div>

        <!-- ========================================== -->
        <!-- ТАБ: СПИСОК БРОНЕЙ НА ДАТУ -->
        <!-- ========================================== -->
        <div v-show="tab === 'list'" class="tab-content">

            <!-- Выбор даты -->
            <div class="form-section">
                <div class="section-label">
                    <i class="fa-solid fa-calendar-day"></i>
                    <span>Выберите дату</span>
                </div>
                <div class="form-field">
                    <div class="field-icon">
                        <i class="fa-solid fa-calendar"></i>
                    </div>
                    <div class="field-content">
                        <label for="listDate">Дата</label>
                        <input
                            type="date"
                            id="listDate"
                            v-model="reservation.date"
                            :min="minDate"
                            required
                            class="form-input"
                        >
                    </div>
                </div>
            </div>

            <!-- Список броней -->
            <div v-if="bookings.length > 0" class="bookings-list">
                <div
                    v-for="item in bookings"
                    :key="item.id"
                    class="booking-item"
                >
                    <div class="booking-time">
                        <i class="fa-solid fa-clock"></i>
                        <span>{{ item.booked_time_at || '-' }}</span>
                    </div>
                    <div class="booking-info">
                        <div class="booking-persons">
                            <i class="fa-solid fa-user-group"></i>
                            {{ item.booked_info?.persons || 1 }}
                            {{ pluralize(item.booked_info?.persons || 1, 'гость', 'гостя', 'гостей') }}
                        </div>
                        <div v-if="item.booked_info?.name" class="booking-name">
                            {{ item.booked_info.name }}
                        </div>
                    </div>
                    <div class="booking-status">
                        <span class="status-badge booked">Занято</span>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else-if="reservation.date" class="empty-bookings">
                <div class="empty-icon">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h6 class="empty-title">Столик свободен</h6>
                <p class="empty-text">
                    На выбранную дату нет активных броней.
                    <button class="link-btn" @click="tab = 'form'">Забронировать?</button>
                </p>
            </div>

            <!-- Начальное состояние -->
            <div v-else class="empty-bookings">
                <div class="empty-icon">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <h6 class="empty-title">Выберите дату</h6>
                <p class="empty-text">
                    Укажите дату, чтобы посмотреть список броней
                </p>
            </div>

        </div>

    </div>
</template>

<script>
import { useTablesStore } from "@/MobileClient/stores/Shop/tables.js";

export default {
    name: "BookingForm",

    props: {
        tableInfo: {
            type: Object,
            default: null,
        },
    },

    emits: ['success', 'failure'],

    setup() {
        const tablesStore = useTablesStore();
        return { tablesStore };
    },

    data() {
        return {
            tab: 'form',
            isSubmitting: false,
            reservation: {
                date: '',
                time: '',
                name: '',
                phone: '',
                persons: 1,
                description: '',
                table: null,
            },
            bookings: [],
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        minDate() {
            const today = new Date();
            return today.toISOString().split('T')[0];
        },

        maxPersons() {
            return this.reservation.table?.seats || 10;
        },
    },

    watch: {
        'reservation.date'() {
            if (this.reservation.date && this.tab === 'list') {
                this.loadBookingList();
            }
        },
        tab(newValue) {
            if (newValue === 'list' && this.reservation.date) {
                this.loadBookingList();
            }
        },
    },

    mounted() {
        this.prefillForm();
    },

    methods: {
        // Заполнение формы данными из props
        prefillForm() {
            if (!this.tableInfo) return;

            this.$nextTick(() => {
                this.reservation.table = this.tableInfo;
                this.reservation.persons = Math.min(this.tableInfo.seats || 1, 2);
                this.reservation.description = '';
                this.reservation.name = this.self?.name || '';
                this.reservation.phone = this.self?.phone || '';
            });
        },

        // Увеличение количества гостей
        increasePersons() {
            if (this.reservation.persons < this.maxPersons) {
                this.reservation.persons++;
            }
        },

        // Уменьшение количества гостей
        decreasePersons() {
            if (this.reservation.persons > 1) {
                this.reservation.persons--;
            }
        },

        // Форматирование телефона
        formatPhone() {
            let value = this.reservation.phone.replace(/\D/g, '');

            if (value.startsWith('8')) value = '7' + value.slice(1);
            if (!value.startsWith('7') && value.length > 0) value = '7' + value;

            let formatted = '';
            if (value.length > 0) formatted = '+7';
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length >= 5) formatted += ') ' + value.slice(4, 7);
            if (value.length >= 8) formatted += '-' + value.slice(7, 9);
            if (value.length >= 10) formatted += '-' + value.slice(9, 11);

            this.reservation.phone = formatted;
        },

        // Загрузка списка броней
        async loadBookingList() {
            if (!this.reservation.table || !this.reservation.date) return;

            try {
                // TODO: Замени на Pinia action
                // const resp = await this.tablesStore.bookingList({
                //     number: this.reservation.table.number,
                //     date: this.reservation.date,
                // });
                // this.bookings = resp.data || [];

                // Имитация запроса
                await new Promise(resolve => setTimeout(resolve, 500));
                this.bookings = [];
            } catch (error) {
                console.error('Ошибка загрузки броней:', error);
            }
        },

        // Валидация формы
        validateForm() {
            if (!this.reservation.date || !this.reservation.time) {
                this.$notify?.({
                    title: 'Бронирование',
                    text: 'Укажите дату и время',
                    type: 'error',
                });
                return false;
            }

            if (!this.reservation.name?.trim()) {
                this.$notify?.({
                    title: 'Бронирование',
                    text: 'Укажите имя',
                    type: 'error',
                });
                return false;
            }

            const phoneDigits = this.reservation.phone.replace(/\D/g, '');
            if (phoneDigits.length !== 11) {
                this.$notify?.({
                    title: 'Бронирование',
                    text: 'Укажите корректный номер телефона',
                    type: 'error',
                });
                return false;
            }

            const selectedDateTime = new Date(`${this.reservation.date}T${this.reservation.time}`);
            if (selectedDateTime < new Date()) {
                this.$notify?.({
                    title: 'Бронирование',
                    text: 'Дата и время не могут быть в прошлом',
                    type: 'error',
                });
                return false;
            }

            return true;
        },

        // Отправка формы
        async submitReservation() {
            if (!this.validateForm()) return;
            if (this.isSubmitting) return;

            this.isSubmitting = true;

            try {
                const data = new FormData();

                Object.keys(this.reservation).forEach(key => {
                    const item = this.reservation[key];
                    if (item === null || item === undefined) return;

                    if (typeof item === 'object') {
                        data.append(key, JSON.stringify(item));
                    } else {
                        data.append(key, item);
                    }
                });

                // TODO: Замени на Pinia action
                // await this.tablesStore.bookATable(data);

                // Имитация запроса
                await new Promise(resolve => setTimeout(resolve, 1200));

                this.$notify?.({
                    title: 'Бронирование',
                    text: 'Столик успешно забронирован!',
                    type: 'success',
                });

                // Сброс формы
                this.reservation = {
                    date: '',
                    time: '',
                    name: this.self?.name || '',
                    phone: this.self?.phone || '',
                    persons: 1,
                    description: '',
                    table: this.reservation.table,
                };

                this.$emit('success');

            } catch (error) {
                console.error('Ошибка бронирования:', error);

                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось забронировать столик',
                    type: 'error',
                });

                this.$emit('failure');
            } finally {
                this.isSubmitting = false;
            }
        },

        // Склонение слов
        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
    },
};
</script>

<style scoped>
.booking-form-container {
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ==========================================
   КАРТОЧКА СТОЛИКА
   ========================================== */
.table-info-card {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.03) 100%);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
    border-radius: 16px;
    margin-bottom: 20px;
}

.table-info-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.table-info-content {
    flex: 1;
    min-width: 0;
}

.table-info-title {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.table-info-description {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    margin-bottom: 8px;
    line-height: 1.4;
}

.table-info-meta {
    display: flex;
    gap: 12px;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--bs-primary);
    font-weight: 600;
    padding: 4px 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    border-radius: 20px;
}

/* ==========================================
   ТАБЫ
   ========================================== */
.tabs-wrapper {
    margin-bottom: 20px;
}

.tabs-container {
    display: flex;
    gap: 6px;
    background: var(--bs-secondary-bg, #f5f5f5);
    padding: 4px;
    border-radius: 14px;
}

.tab-item {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tab-item:hover:not(.active) {
    color: var(--bs-body-color);
    background: rgba(var(--bs-primary-rgb), 0.05);
}

.tab-item.active {
    background: var(--bs-body-bg);
    color: var(--bs-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.tab-content {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* ==========================================
   СЕКЦИИ ФОРМЫ
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
    margin-bottom: 10px;
}

.section-label i {
    color: var(--bs-primary);
}

.optional {
    text-transform: none;
    letter-spacing: 0;
    font-weight: 400;
    color: var(--bs-secondary-color);
    opacity: 0.7;
}

/* ==========================================
   ПОЛЯ ФОРМЫ
   ========================================== */
.datetime-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.form-field {
    display: flex;
    align-items: stretch;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.form-field:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.field-icon {
    width: 35px;
    margin-right: 5px;
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
    padding: 8px 14px 8px 0;
    min-width: 0;
}

.field-content label {
    display: block;
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
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
}

.textarea-counter {
    text-align: right;
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-top: 4px;
}

/* ==========================================
   СЕЛЕКТОР ГОСТЕЙ
   ========================================== */
.persons-selector {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 8px;
    transition: all 0.2s ease;
}

.persons-btn {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.persons-btn:hover:not(:disabled) {
    background: var(--bs-primary);
    color: white;
    transform: scale(1.05);
}

.persons-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.persons-display {
    text-align: center;
    padding: 0 16px;
}

.persons-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--bs-primary);
    line-height: 1;
    margin-bottom: 4px;
}

.persons-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.persons-hint {
    margin-top: 8px;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    text-align: center;
}

/* ==========================================
   КНОПКА ОТПРАВКИ
   ========================================== */
.submit-btn {
    width: 100%;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
    margin-top: 8px;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* ==========================================
   СПИСОК БРОНЕЙ
   ========================================== */
.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.booking-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    transition: all 0.2s ease;
}

.booking-item:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.02);
}

.booking-time {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.booking-info {
    flex: 1;
    min-width: 0;
}

.booking-persons {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.booking-persons i {
    color: var(--bs-primary);
    font-size: 0.8rem;
}

.booking-name {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.status-badge.booked {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
.empty-bookings {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;
}

.empty-title {
    font-weight: 700;
    font-size: 1.05rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    margin: 0;
    line-height: 1.5;
}

.link-btn {
    background: none;
    border: none;
    color: var(--bs-primary);
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .datetime-row {
        grid-template-columns: 1fr;
    }

    .tab-item {
        font-size: 0.8rem;
        padding: 8px 10px;
    }

    .tab-item span {
        display: none;
    }

    .persons-value {
        font-size: 1.7rem;
    }
}
</style>
