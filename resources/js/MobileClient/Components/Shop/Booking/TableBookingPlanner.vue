<template>
    <div class="booking-page pb-5" v-if="tenant">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="booking-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h2 class="hero-title">Бронирование столика</h2>
                <p class="hero-subtitle">
                    Выберите столик и забронируйте его на удобное время
                </p>
            </div>
        </div>

        <div class="container px-3">

            <!-- ========================================== -->
            <!-- МОИ БРОНИ -->
            <!-- ========================================== -->
            <div v-if="myBookings.length > 0" class="my-bookings-section">
                <button class="bookings-toggle" @click="showMyBookings = !showMyBookings">
                    <div class="toggle-content">
                        <div class="toggle-icon">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div class="toggle-info">
                            <span class="toggle-title">Мои брони</span>
                            <span class="toggle-badge">{{ myBookings.length }}</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down toggle-arrow" :class="{ 'rotated': showMyBookings }"></i>
                </button>

                <transition name="slide-down">
                    <div v-if="showMyBookings" class="bookings-list">
                        <div v-for="booking in myBookings" :key="booking.id" class="booking-card">
                            <div class="booking-header">
                                <div class="booking-number">
                                    <span class="booking-label">Столик</span>
                                    <span class="booking-value">№{{ booking.number }}</span>
                                </div>
                                <div class="booking-persons">
                                    <i class="fa-solid fa-user-group"></i>
                                    <span>{{ booking.booked_info?.persons || 1 }}</span>
                                </div>
                            </div>

                            <div class="booking-datetime">
                                <div class="datetime-item">
                                    <i class="fa-solid fa-calendar"></i>
                                    <span>{{ formatDate(booking.booked_date_at) }}</span>
                                </div>
                                <div class="datetime-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>{{ booking.booked_time_at }}</span>
                                </div>
                            </div>

                            <div v-if="booking.booked_info?.description" class="booking-note">
                                <i class="fa-solid fa-comment"></i>
                                <span>{{ booking.booked_info.description }}</span>
                            </div>

                            <button class="cancel-booking-btn" @click="openRemoveModal(booking)">
                                <i class="fa-solid fa-xmark"></i>
                                <span>Отменить бронь</span>
                            </button>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- СТОЛИКИ НА ВЫБОР (CSS-ОТРИСОВКА) -->
            <!-- ========================================== -->
            <div v-if="sortedSelectedTables.length > 0" class="tables-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Столики на выбор</h6>
                        <p class="section-subtitle">
                            {{ sortedSelectedTables.length }}
                            {{ pluralize(sortedSelectedTables.length, 'столик', 'столика', 'столиков') }}
                            доступно
                        </p>
                    </div>
                </div>

                <div class="tables-grid">
                    <div
                        v-for="table in sortedSelectedTables"
                        :key="table.id"
                        class="table-card"
                        @click="openModal(table)"
                    >
                        <!-- НОВАЯ CSS-ОТРИСОВКА СТОЛИКА -->
                        <div class="table-visual-wrapper">
                            <div class="css-table" :class="`shape-${getTableConfig(table).shape}`">
                                <!-- Стулья и диваны -->
                                <div
                                    v-for="(seat, index) in getTableConfig(table).seats"
                                    :key="index"
                                    class="seat"
                                    :class="[`type-${seat.type}`, `pos-${seat.pos}`]"
                                ></div>

                                <!-- Столешница -->
                                <div class="table-top">
                                    <span class="table-number">{{ table.number }}</span>
                                </div>
                            </div>

                            <!-- Бейдж вместимости -->
                            <div class="table-capacity-badge">
                                <i class="fa-solid fa-user-group"></i> {{ table.seats }}
                            </div>
                        </div>

                        <div class="table-info">
                            <div class="table-name">Столик №{{ table.number }}</div>
                            <div class="table-description">{{ table.description || 'Стандартный столик' }}</div>
                        </div>

                        <div class="table-action">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h5 class="empty-title">Нет доступных столиков</h5>
                <p class="empty-text">
                    К сожалению, сейчас нет свободных столиков для бронирования
                </p>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- STICKY FOOTER -->
        <!-- ========================================== -->
        <div v-if="myBookings.length > 0" class="booking-footer">
            <button class="preorder-btn" @click="goToTableMenu">
                <i class="fa-solid fa-bag-shopping"></i>
                <span>Перейти к предзаказу</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: БРОНИРОВАНИЕ -->
        <!-- ========================================== -->
        <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content booking-modal">
                    <div class="modal-header">
                        <div class="modal-icon booking-icon">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title">Бронирование столика</h5>
                            <small class="text-muted" v-if="selectedTable">
                                Столик №{{ selectedTable.number }}
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <BookingForm
                            v-if="selectedTable"
                            :table-info="selectedTable"
                            @success="onBookingSuccess"
                            @failure="onBookingFailure"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ОТМЕНА БРОНИ -->
        <!-- ========================================== -->
        <div class="modal fade" id="removeBookingModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content remove-modal">
                    <div class="modal-header">
                        <div class="modal-icon remove-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title">Отмена брони</h5>
                            <small class="text-muted">Это действие нельзя отменить</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedTable" class="remove-info">
                            <p class="remove-text">
                                Вы уверены, что хотите отменить бронь
                                <strong>столика №{{ selectedTable.number }}</strong>
                                на <strong>{{ formatDate(selectedTable.booked_date_at) }}</strong>
                                в <strong>{{ selectedTable.booked_time_at }}</strong>?
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">
                            Нет, оставить
                        </button>
                        <button type="button" class="btn-confirm" @click="cancelBookingTable" :disabled="isCancelling">
                            <span v-if="isCancelling" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="fa-solid fa-trash me-2"></i>
                            Да, отменить
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import BookingForm from "@/MobileClient/Components/Shop/Booking/BookingForm.vue";
import { useTablesStore } from "@/MobileClient/stores/Shop/tables.js";

export default {
    name: "TableBooking",

    components: {
        BookingForm,
    },

    setup() {
        const tablesStore = useTablesStore();
        return { tablesStore };
    },

    data() {
        return {
            showMyBookings: false,
            myBookings: [],
            selectedTable: null,
            isCancelling: false,
            bookingModal: null,
            removeModal: null,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        sortedSelectedTables() {
            const tables = this.tenant?.settings?.tables_variants || [];
            return [...tables].sort((a, b) => (a.seats || 0) - (b.seats || 0));
        },
    },

    mounted() {
        this.initModals();
        this.loadMyBookings();
    },

    beforeUnmount() {
        if (this.bookingModal) this.bookingModal.dispose();
        if (this.removeModal) this.removeModal.dispose();
    },

    methods: {
        // ==========================================
        // НОВЫЙ МЕТОД: Конфигурация CSS-столика
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

        initModals() {
            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    this.bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
                    this.removeModal = new bootstrap.Modal(document.getElementById('removeBookingModal'));
                }
            });
        },

        async loadMyBookings() {
            try {
                const resp = await this.tablesStore.myUpcomingBookings();
                this.myBookings = resp.data || [];
            } catch (error) {
                console.error('Ошибка загрузки броней:', error);
            }
        },

        openModal(table) {
            this.selectedTable = table;
            this.bookingModal?.show();
        },

        openRemoveModal(booking) {
            this.selectedTable = booking;
            this.removeModal?.show();
        },

        onBookingSuccess() {
            this.bookingModal?.hide();
            this.loadMyBookings();
            this.$notify?.({
                title: 'Бронирование',
                text: 'Столик успешно забронирован!',
                type: 'success',
            });
        },

        onBookingFailure() {
            this.bookingModal?.hide();
        },

        async cancelBookingTable() {
            if (!this.selectedTable) return;
            this.isCancelling = true;

            try {
                await this.tablesStore.cancelBookingTable({
                    bookingId: this.selectedTable.id,
                });

                this.removeModal?.hide();
                this.loadMyBookings();

                this.$notify?.({
                    title: 'Бронирование',
                    text: 'Бронь успешно отменена',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка отмены брони:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отменить бронь',
                    type: 'error',
                });
            } finally {
                this.isCancelling = false;
            }
        },

        goToTableMenu() {
            this.$router.push({ name: 'TableMenu' });
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'long',
            });
        },

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
.booking-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 100px;
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.booking-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.hero-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.95;
    margin: 0;
}

/* ==========================================
   МОИ БРОНИ
   ========================================== */
.my-bookings-section {
    margin-top: -20px;
    position: relative;
    z-index: 2;
}

.bookings-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.bookings-toggle:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 6px 20px rgba(var(--bs-primary-rgb), 0.1);
}

.toggle-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.toggle-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.toggle-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.toggle-title {
    font-weight: 600;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.toggle-badge {
    padding: 2px 10px;
    background: var(--bs-primary);
    color: white;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}

.toggle-arrow {
    color: var(--bs-secondary-color);
    transition: transform 0.3s ease;
}

.toggle-arrow.rotated {
    transform: rotate(180deg);
}

.bookings-list {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.booking-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.booking-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.booking-number {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.booking-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.booking-value {
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--bs-body-color);
}

.booking-persons {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    border-radius: 20px;
    color: var(--bs-primary);
    font-weight: 600;
    font-size: 0.85rem;
}

.booking-datetime {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
}

.datetime-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.datetime-item i {
    color: var(--bs-primary);
    font-size: 0.85rem;
}

.booking-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    background: var(--bs-secondary-bg, #f8f9fa);
    border-radius: 10px;
    margin-bottom: 12px;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
    font-style: italic;
}

.booking-note i {
    margin-top: 2px;
    flex-shrink: 0;
}

.cancel-booking-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: transparent;
    border: 2px solid #dc3545;
    border-radius: 12px;
    color: #dc3545;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cancel-booking-btn:hover {
    background: #dc3545;
    color: white;
}

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
    max-height: 1000px;
}

/* ==========================================
   СТОЛИКИ (CSS-ОТРИСОВКА)
   ========================================== */
.tables-section {
    margin-top: 32px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.tables-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.table-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.table-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.15);
}

/* --- НОВЫЕ СТИЛИ ДЛЯ CSS-СТОЛИКОВ --- */
.table-visual-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 4/3;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.03) 0%, rgba(var(--bs-primary-rgb), 0.08) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

/* Сетка на фоне для эффекта "зала" */
.table-visual-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(var(--bs-primary-rgb), 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(var(--bs-primary-rgb), 0.05) 1px, transparent 1px);
    background-size: 20px 20px;
    opacity: 0.5;
}

.css-table {
    position: relative;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
    z-index: 1;
}

.table-card:hover .css-table {
    transform: scale(1.08);
}

/* Столешница */
.table-top {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    box-shadow:
        inset 0 2px 4px rgba(255, 255, 255, 0.3),
        0 6px 16px rgba(var(--bs-primary-rgb), 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 800;
    font-size: 1.1rem;
    border: 2px solid rgba(255, 255, 255, 0.2);
    z-index: 2;
}

.shape-round .table-top {
    border-radius: 50%;
}

.shape-square .table-top {
    border-radius: 12px;
    width: 65px;
    height: 65px;
}

.shape-rect .table-top {
    border-radius: 14px;
    width: 80px;
    height: 55px;
}

/* Стулья и диваны */
.seat {
    position: absolute;
    z-index: 1;
    transition: all 0.3s ease;
}

/* Тип: Стул */
.type-chair {
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border-radius: 6px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Тип: Диван */
.type-sofa {
    background: linear-gradient(135deg, #8b6b9e 0%, #4a3550 100%);
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.15);
}

/* Позиции */
.pos-top {
    top: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 18px;
}

.pos-bottom {
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 24px;
}

.pos-bottom-left {
    bottom: 2px;
    left: 15%;
    transform: translateX(-50%);
    width: 24px;
    height: 24px;
}

.pos-bottom-right {
    bottom: 2px;
    right: 15%;
    transform: translateX(50%);
    width: 24px;
    height: 24px;
}

.pos-left {
    left: -4px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
}

.pos-right {
    right: -4px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
}

.pos-tl {
    top: 8px;
    left: 8px;
    width: 20px;
    height: 20px;
}

.pos-br {
    bottom: 8px;
    right: 8px;
    width: 20px;
    height: 20px;
}

/* Бейдж вместимости */
.table-capacity-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 10px;
    background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.9);
    backdrop-filter: blur(4px);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    z-index: 3;
}

/* Информация под столиком */
.table-info {
    padding: 12px;
}

.table-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.table-description {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.table-action {
    position: absolute;
    bottom: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s ease;
    z-index: 3;
}

.table-card:hover .table-action {
    opacity: 1;
    transform: scale(1);
}

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
.empty-state {
    text-align: center;
    padding: 60px 24px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
}

.empty-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin: 0;
}

/* ==========================================
   STICKY FOOTER
   ========================================== */
.booking-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px 16px;
    background: var(--bs-body-bg);
    border-top: 1px solid var(--bs-border-color);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    z-index: 1000;
    backdrop-filter: blur(10px);
}

.preorder-btn {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.preorder-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(var(--bs-primary-rgb), 0.4);
}

.preorder-btn i:last-child {
    transition: transform 0.2s ease;
}

.preorder-btn:hover i:last-child {
    transform: translateX(4px);
}

/* ==========================================
   МОДАЛКИ
   ========================================== */
.booking-modal,
.remove-modal {
    border-radius: 20px;
    border: none;
    overflow: hidden;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.booking-icon {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.remove-icon {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.modal-title {
    font-weight: 700;
    margin-bottom: 2px;
    color: var(--bs-body-color);
}

.modal-body {
    padding: 20px;
}

.remove-info {
    text-align: center;
    padding: 20px 0;
}

.remove-text {
    font-size: 1rem;
    line-height: 1.6;
    color: var(--bs-body-color);
    margin: 0;
}

.remove-text strong {
    color: var(--bs-primary);
}

.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--bs-border-color);
    display: flex;
    gap: 10px;
}

.btn-cancel,
.btn-confirm {
    flex: 1;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.btn-cancel {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
}

.btn-cancel:hover {
    background: var(--bs-border-color);
}

.btn-confirm {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-confirm:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(220, 53, 69, 0.4);
}

.btn-confirm:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.4rem;
    }

    .tables-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .css-table {
        width: 100px;
        height: 100px;
    }

    .table-top {
        width: 50px;
        height: 50px;
        font-size: 0.9rem;
    }

    .shape-square .table-top {
        width: 55px;
        height: 55px;
    }

    .shape-rect .table-top {
        width: 65px;
        height: 45px;
    }

    .type-chair {
        width: 20px;
        height: 20px;
    }

    .pos-top {
        width: 40px;
        height: 16px;
    }

    .table-name {
        font-size: 0.85rem;
    }

    .table-description {
        font-size: 0.75rem;
    }

    .booking-datetime {
        flex-direction: column;
        gap: 8px;
    }
}
</style>
