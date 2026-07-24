<template>
    <div class="waiter-dashboard">

        <!-- Кнопка статистики -->
        <button type="button" class="btn btn-statistics w-100 mb-3" data-bs-toggle="modal" data-bs-target="#statistic-modal">
            <i class="fa-solid fa-chart-pie me-2"></i>
            <span>Открыть статистику зала</span>
            <i class="fa-solid fa-chevron-right ms-auto"></i>
        </button>

        <!-- Список столиков -->
        <div v-if="tables.length > 0" class="tables-grid">
            <div v-for="table in tables" :key="table.id" class="table-card" :class="getTableStatusClass(table)">

                <!-- Визуальная часть (CSS-столик) -->
                <div class="card-visual">
                    <div class="css-table" :class="`shape-${getTableConfig(table).shape}`">
                        <div v-for="(seat, index) in getTableConfig(table).seats" :key="index"
                             class="seat" :class="[`type-${seat.type}`, `pos-${seat.pos}`]"></div>
                        <div class="table-top">
                            <span class="table-number">{{ parseInt(table.number || '0') + 1 }}</span>
                        </div>
                    </div>
                    <div class="capacity-badge">
                        <i class="fa-solid fa-user-group"></i> {{ table.clients?.length || 0 }}
                    </div>
                </div>

                <!-- Информационная часть -->
                <div class="card-info">
                    <div class="info-header">
                        <span class="status-badge">{{ getStatusText(table) }}</span>
                        <span class="time-badge" v-if="table.start_at">
                            <i class="fa-regular fa-clock"></i> {{ timeAgo(table.start_at) }}
                        </span>
                    </div>

                    <div v-if="table.officiant && table.officiant_id !== self?.id" class="waiter-info">
                        <i class="fa-solid fa-bell-concierge"></i>
                        <span>{{ table.officiant.name || table.officiant.fio_from_telegram || 'Официант' }}</span>
                    </div>

                    <!-- Блок брони -->
                    <div v-if="table.booked_info" class="booking-alert">
                        <div class="booking-row">
                            <i class="fa-regular fa-calendar"></i>
                            <span>{{ table.booked_date_at }} в {{ table.booked_time_at }}</span>
                        </div>
                        <div class="booking-row">
                            <i class="fa-regular fa-user"></i>
                            <span>{{ table.booked_info.name }} ({{ table.booked_info.phone }})</span>
                        </div>
                        <div v-if="table.booked_info.description" class="booking-row text-muted fst-italic">
                            <i class="fa-solid fa-comment"></i>
                            <span>{{ table.booked_info.description }}</span>
                        </div>
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="card-actions">
                    <button class="btn-action btn-view" @click="goToTable(table.id)" title="Просмотр">
                        <i class="fa-solid fa-eye"></i>
                    </button>

                    <template v-if="table.officiant_id == null">
                        <button class="btn-action btn-take" @click="takeATable(table.id)">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> В работу
                        </button>
                    </template>

                    <template v-if="self?.id === table.officiant_id">
                        <button class="btn-action btn-leave" @click="changeTableWaiter(table.id)">
                            <i class="fa-solid fa-right-from-bracket"></i> Выйти
                        </button>
                        <button class="btn-action btn-close" @click="closeTable(table.id)">
                            <i class="fa-solid fa-xmark"></i> Закрыть
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else-if="!isLoading" class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-mug-hot"></i></div>
            <h5>Нет активных столиков</h5>
            <p>В данный момент нет ваших столиков в обслуживании и клиентов без официанта.</p>
        </div>

        <!-- Лоадер -->
        <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>

        <!-- Пагинация -->
        <Pagination
            v-if="paginate && tables.length > 0"
            :simple="true"
            :pagination="paginate"
            @pagination_page="nextTables"
        />

        <!-- ========================================== -->
        <!-- МОДАЛЬНОЕ ОКНО СТАТИСТИКИ -->
        <!-- ========================================== -->
        <div class="modal fade" id="statistic-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-centered">
                <div class="modal-content stat-modal">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-bold">Статистика зала</h5>
                            <small class="text-muted">Сводные показатели за сегодня</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                            <div>Демо-режим: данные не соответствуют реальным показателям.</div>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-clock"></i></div>
                                <div class="stat-value">12 мин</div>
                                <div class="stat-label">Ожидание заказа</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-receipt"></i></div>
                                <div class="stat-value">1 250 ₽</div>
                                <div class="stat-label">Средний чек</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon bg-info-subtle text-info"><i class="fa-solid fa-users"></i></div>
                                <div class="stat-value">45</div>
                                <div class="stat-label">Обслужено столов</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-star"></i></div>
                                <div class="stat-value">92%</div>
                                <div class="stat-label">Удовлетворенность</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="fw-bold mb-3">Детализация</h6>
                            <ul class="list-group list-group-flush rounded-3">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa-solid fa-stopwatch text-muted me-2"></i>Время на столик</span>
                                    <span class="fw-bold">35 мин</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa-solid fa-rotate text-muted me-2"></i>Повторные заказы</span>
                                    <span class="fw-bold">28%</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa-solid fa-user-tie text-muted me-2"></i>Официантов в смену</span>
                                    <span class="fw-bold">6</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center text-danger">
                                    <span><i class="fa-solid fa-circle-exclamation me-2"></i>Просроченные заказы</span>
                                    <span class="fw-bold">3%</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary w-100 mb-2" data-bs-dismiss="modal">
                            Закрыть
                        </button>
                        <button type="button" class="btn btn-primary w-100">
                            <i class="fa-solid fa-download me-2"></i> Скачать отчет (CSV)
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Pagination from "@/MobileClient/Components/Pagination.vue";
import moment from 'moment';
import 'moment/locale/ru';

moment.locale('ru');

export default {
    name: "WaiterTablesDashboard",

    components: {
        Pagination
    },

    props: {
        selected: {
            type: [String, Number],
            default: null
        }
    },

    data() {
        return {
            tables: [],
            paginate: null,
            isLoading: false,
            sort: {
                param: null,
                direction: 'asc'
            }
        };
    },

    computed: {
        self() {
            return window.self || window.TenantUser || null;
        },


    },

    mounted() {
        this.loadTables();


    },


    methods: {
        // ==========================================
        // ХЕЛПЕР ДЛЯ CSS-СТОЛИКОВ
        // ==========================================
        getTableConfig(table) {
            const seats = table.seats || table.clients?.length || 2;
            const hasBooking = !!table.booked_info;

            let shape = 'round';
            let seatsConfig = [];

            if (seats <= 2) {
                shape = 'round';
                seatsConfig = [
                    { type: 'chair', pos: 'left' },
                    { type: 'chair', pos: 'right' }
                ];
            } else if (seats <= 4) {
                shape = hasBooking ? 'rect' : 'square';
                seatsConfig = [
                    { type: hasBooking ? 'sofa' : 'chair', pos: 'top' },
                    { type: 'chair', pos: 'bottom' },
                    { type: 'chair', pos: 'left' },
                    { type: 'chair', pos: 'right' }
                ];
            } else {
                shape = 'round';
                seatsConfig = [
                    { type: 'chair', pos: 'top' }, { type: 'chair', pos: 'bottom' },
                    { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' },
                    { type: 'chair', pos: 'tl' }, { type: 'chair', pos: 'br' }
                ].slice(0, seats);
            }
            return { shape, seats: seatsConfig, hasBooking };
        },

        getTableStatusClass(table) {
            if (table.booked_info) return 'status-booked';
            if (table.officiant_id === this.self?.id) return 'status-mine';
            if (table.officiant_id != null) return 'status-busy';
            return 'status-free';
        },

        getStatusText(table) {
            if (table.booked_info) return 'Забронирован';
            if (table.officiant_id === this.self?.id) return 'Мой столик';
            if (table.officiant_id != null) return `Занят (${table.officiant?.name || 'Официант'})`;
            return 'Свободен';
        },

        timeAgo(datetime) {
            if (!datetime) return 'Не начато';
            return moment(datetime).format('D MMM, HH:mm');
        },

        async loadTables(page = 0) {
            this.isLoading = true;
            try {
                const resp = await this.$store.dispatch("loadTables", {
                    dataObject: {},
                    page: page,
                    size: 100
                });

                // Безопасное извлечение данных (адаптируйте под структуру вашего API)
                this.tables = resp?.data?.data || resp?.data || resp || [];
                this.paginate = resp?.data?.meta || resp?.meta || null;
            } catch (error) {
                console.error("Ошибка загрузки столиков:", error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: "Не удалось загрузить список столиков",
                    type: 'error'
                });
            } finally {
                this.isLoading = false;
            }
        },

        takeATable(id) {
            this.changeTableWaiter(id);
        },

        async changeTableWaiter(id) {
            try {
                await this.$store.dispatch("changeTableWaiter", {
                    dataObject: { table_id: id }
                });

                this.$notify?.({
                    title: 'Смена официанта',
                    text: "Официант успешно изменен",
                    type: 'success'
                });

                await this.loadTables();
            } catch (error) {
                console.error(error);
                this.$notify?.({
                    title: 'Упс!',
                    text: "Ошибка смены официанта",
                    type: 'error'
                });
            }
        },

        async closeTable(tableId) {
            if (!confirm('Вы уверены, что хотите закрыть этот столик?')) return;

            try {
                await this.$store.dispatch("closeTableOrder", {
                    dataObject: { table_id: tableId }
                });

                this.$notify?.({
                    title: 'Заказ',
                    text: "Столик успешно закрыт",
                    type: 'success'
                });

                await this.loadTables();
            } catch (error) {
                console.error(error);
                this.$notify?.({
                    title: 'Упс!',
                    text: "Ошибка завершения работы столика",
                    type: 'error'
                });
            }
        },

        goToTable(tableId) {
            this.$router.push({ name: 'TableV2', params: { tableId: tableId } });
        },

        nextTables(index) {
            this.loadTables(index);
        }
    }
};
</script>

<style scoped>
/* Все стили остаются точно такими же, как в предыдущем варианте */
.waiter-dashboard {
    padding: 16px;
    padding-bottom: 40px;
    background-color: var(--bs-body-bg);
    min-height: 100vh;
}

.btn-statistics {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border: none;
    border-radius: 16px;
    padding: 16px 20px;
    font-weight: 600;
    color: white;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
    transition: transform 0.2s;
}
.btn-statistics:active { transform: scale(0.98); }

.tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}

.table-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.table-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.table-card.status-mine { border-top: 4px solid var(--bs-primary); }
.table-card.status-booked { border-top: 4px solid #ffc107; }
.table-card.status-busy { border-top: 4px solid #6c757d; }
.table-card.status-free { border-top: 4px solid #198754; }

.card-visual {
    position: relative;
    height: 140px;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.03) 0%, rgba(var(--bs-primary-rgb), 0.08) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.css-table {
    position: relative;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.table-card:hover .css-table { transform: scale(1.1); }

.table-top {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 800;
    font-size: 1rem;
    z-index: 2;
    border: 2px solid rgba(255,255,255,0.2);
}

.shape-square .table-top { border-radius: 10px; width: 55px; height: 55px; }
.shape-rect .table-top { border-radius: 12px; width: 65px; height: 45px; }

.seat { position: absolute; z-index: 1; }
.type-chair {
    width: 20px; height: 20px;
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.type-sofa {
    background: linear-gradient(135deg, #8b6b9e 0%, #4a3550 100%);
    border-radius: 8px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.3);
}

.pos-top { top: -2px; left: 50%; transform: translateX(-50%); width: 40px; height: 16px; }
.pos-bottom { bottom: -2px; left: 50%; transform: translateX(-50%); }
.pos-left { left: -2px; top: 50%; transform: translateY(-50%); }
.pos-right { right: -2px; top: 50%; transform: translateY(-50%); }
.pos-tl { top: 6px; left: 6px; width: 16px; height: 16px; }
.pos-br { bottom: 6px; right: 6px; width: 16px; height: 16px; }

.capacity-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(4px);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--bs-body-color);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 4px;
}

.card-info {
    padding: 16px;
    flex-grow: 1;
}

.info-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.status-badge {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 8px;
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
}
.status-mine .status-badge { background: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary); }
.status-booked .status-badge { background: rgba(255, 193, 7, 0.15); color: #856404; }

.time-badge {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    gap: 4px;
}

.waiter-info {
    font-size: 0.85rem;
    color: var(--bs-body-color);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: var(--bs-secondary-bg);
    border-radius: 10px;
}
.waiter-info i { color: var(--bs-primary); }

.booking-alert {
    background: #fff3cd;
    border: 1px solid #ffe69c;
    border-radius: 12px;
    padding: 10px 12px;
    font-size: 0.8rem;
}
.booking-alert .booking-row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 6px;
    color: #664d03;
}
.booking-alert .booking-row:last-child { margin-bottom: 0; }
.booking-alert i { margin-top: 2px; flex-shrink: 0; }

.card-actions {
    padding: 12px 16px 16px;
    display: flex;
    gap: 8px;
}

.btn-action {
    flex: 1;
    padding: 10px;
    border-radius: 12px;
    border: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-view { flex: 0 0 44px; background: var(--bs-light); color: var(--bs-body-color); }
.btn-view:hover { background: var(--bs-border-color); }

.btn-take { background: var(--bs-primary); color: white; }
.btn-take:hover { filter: brightness(1.1); }

.btn-leave { background: var(--bs-secondary-bg); color: var(--bs-body-color); border: 1px solid var(--bs-border-color); }
.btn-leave:hover { background: var(--bs-border-color); }

.btn-close { background: #dc3545; color: white; }
.btn-close:hover { background: #bb2d3b; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--bs-secondary-color);
}
.empty-icon {
    width: 80px; height: 80px;
    background: var(--bs-secondary-bg);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    color: var(--bs-secondary);
}

.stat-modal {
    border-radius: 24px;
    border: none;
    overflow: hidden;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.stat-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
    text-align: center;
}

.stat-icon {
    width: 40px; height: 40px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    margin: 0 auto 8px;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--bs-body-color);
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    line-height: 1.2;
}

@media (max-width: 576px) {
    .tables-grid {
        grid-template-columns: 1fr;
    }
    .card-actions {
        flex-wrap: wrap;
    }
    .btn-action {
        font-size: 0.8rem;
        padding: 12px 8px;
    }
    .btn-view { flex: 1; }
}
</style>
