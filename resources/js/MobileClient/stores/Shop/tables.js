import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/tables';

export const useTablesStore = defineStore('tables', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        tables: [],
        tables_paginate_object: null,
        currentTable: null,
        bookings: [],
        myBookings: [],
        nearestBookings: [],
        additionalServices: [],

        // Состояние загрузки по секциям
        isLoading: false,
        isHydrated: false,
        isTablesLoading: false,
        isBookingsLoading: false,
        isMyBookingsLoading: false,
        isNearestBookingsLoading: false,
        isCurrentTableLoading: false,
        isOrdersLoading: false,
        isWaiterLoading: false,

        // Действия над конкретными сущностями
        tableActions: {},
        bookingActions: {},

        // Ошибки
        lastError: null,
        errors: [],

        // Время последней синхронизации
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Все столы
         */
        getTables: (state) => state.tables || [],

        /**
         * Пагинация столов
         */
        getTablesPaginateObject: (state) => state.tables_paginate_object || null,

        /**
         * Текущий стол
         */
        getCurrentTable: (state) => state.currentTable,

        /**
         * Все бронирования
         */
        getBookings: (state) => state.bookings || [],

        /**
         * Мои бронирования
         */
        getMyBookings: (state) => state.myBookings || [],

        /**
         * Ближайшие бронирования
         */
        getNearestBookings: (state) => state.nearestBookings || [],

        /**
         * Найти стол по ID
         */
        getTableById: (state) => (id) => {
            return state.tables.find(t => String(t.id) === String(id)) || null;
        },

        /**
         * Найти бронирование по ID
         */
        getBookingById: (state) => (id) => {
            return [...state.bookings, ...state.myBookings, ...state.nearestBookings]
                .find(b => String(b.id) === String(id)) || null;
        },

        /**
         * Свободные столы
         */
        availableTables: (state) => {
            return (state.tables || []).filter(t =>
                !t.is_occupied &&
                !t.closed_at &&
                t.status !== 'occupied'
            );
        },

        /**
         * Занятые столы
         */
        occupiedTables: (state) => {
            return (state.tables || []).filter(t =>
                t.is_occupied || t.status === 'occupied'
            );
        },

        /**
         * Закрытые столы
         */
        closedTables: (state) => {
            return (state.tables || []).filter(t => !!t.closed_at);
        },

        /**
         * Предстоящие бронирования (будущие)
         */
        upcomingBookings: (state) => {
            const now = new Date();
            return (state.bookings || []).filter(b => {
                const bookingDate = new Date(b.booked_date_at || b.date || 0);
                return bookingDate >= now && b.status !== 'cancelled';
            }).sort((a, b) => new Date(a.booked_date_at) - new Date(b.booked_date_at));
        },

        /**
         * Бронирования на сегодня
         */
        todayBookings: (state) => {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            return (state.bookings || []).filter(b => {
                const bookingDate = new Date(b.booked_date_at || b.date || 0);
                return bookingDate >= today && bookingDate < tomorrow;
            });
        },

        /**
         * Активные бронирования (не отменённые)
         */
        activeBookings: (state) => {
            return (state.bookings || []).filter(b => b.status !== 'cancelled');
        },

        /**
         * Проверка, загружается ли стол
         */
        isTableLoading: (state) => (id) => {
            return !!state.tableActions[String(id)];
        },

        /**
         * Проверка, загружается ли бронирование
         */
        isBookingLoading: (state) => (id) => {
            return !!state.bookingActions[String(id)];
        },

        /**
         * Количество столов
         */
        tablesCount: (state) => state.tables?.length || 0,

        /**
         * Количество свободных столов
         */
        availableTablesCount: (state) => {
            return (state.tables || []).filter(t =>
                !t.is_occupied && !t.closed_at
            ).length;
        },

        /**
         * Количество предстоящих бронирований
         */
        upcomingBookingsCount: (state) => {
            const now = new Date();
            return (state.bookings || []).filter(b => {
                const bookingDate = new Date(b.booked_date_at || b.date || 0);
                return bookingDate >= now && b.status !== 'cancelled';
            }).length;
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ==========================================
        // СТОЛЫ: ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка списка столов (для официанта)
         */
        async loadTables(payload = { dataObject: null, page: 0, size: 50 }) {
            this.isTablesLoading = true;
            this.lastError = null;

            try {
                const page = payload.page || 0;
                const size = payload.size || 50;
                const link = `${BASE}/waiter-tables?page=${page}&size=${size}`;

                const response = await axios.post(link, payload.dataObject || {});
                const dataObject = response.data;

                this.tables = dataObject.data || [];
                const { data, ...paginate } = dataObject;
                this.tables_paginate_object = paginate;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                // Сохраняем в localStorage
                localStorage.setItem('cashman_tables', JSON.stringify(this.tables));
                localStorage.setItem('cashman_tables_paginate_object', JSON.stringify(paginate));

                return paginate;
            } catch (err) {
                console.error('[Tables Store] Ошибка загрузки столов:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить столы';
                throw err;
            } finally {
                this.isTablesLoading = false;
            }
        },

        /**
         * Загрузка данных конкретного стола
         */
        async loadTableData(payload = { dataObject: {} }) {
            try {
                const response = await axios.post(`${BASE}/table-data`, payload.dataObject);
                return response.data;
            } catch (err) {
                console.error('[Tables Store] Ошибка загрузки данных стола:', err);
                throw err;
            }
        },

        /**
         * Загрузка текущего стола (с учётом localStorage)
         */
        async loadCurrentTableData() {
            this.isCurrentTableLoading = true;

            try {
                let storedTable = localStorage.getItem('cashman_current_active_table');
                storedTable = storedTable ? JSON.parse(storedTable) : null;

                const response = await axios.post(`${BASE}/current`, {
                    table_id: storedTable?.id || null,
                });

                const data = response.data?.data || response.data;
                this.currentTable = data;

                // Обновляем localStorage
                if (data?.id) {
                    localStorage.setItem('cashman_current_active_table', JSON.stringify(data));
                }

                return data;
            } catch (err) {
                console.error('[Tables Store] Ошибка загрузки текущего стола:', err);
                throw err;
            } finally {
                this.isCurrentTableLoading = false;
            }
        },

        /**
         * Очистить текущий стол
         */
        clearCurrentTable() {
            this.currentTable = null;
            localStorage.removeItem('cashman_current_active_table');
        },

        // ==========================================
        // СТОЛЫ: CRUD
        // ==========================================

        /**
         * Создание стола
         */
        async storeTables(payload = { promoCodeForm: null }) {
            try {
                const response = await axios.post(`${BASE}/store`, payload.promoCodeForm);
                const newTable = response.data?.data || response.data;

                if (newTable?.id) {
                    this.tables.unshift(newTable);
                }

                return newTable;
            } catch (err) {
                console.error('[Tables Store] Ошибка создания стола:', err);
                throw err;
            }
        },

        /**
         * Удаление стола (оптимистично)
         */
        async removeTables(payload = { promoCodeId: null }) {
            if (!payload.promoCodeId) throw new Error('Не указан ID стола');

            this.tableActions[String(payload.promoCodeId)] = 'delete';

            // Сохраняем для отката
            const removedIndex = this.tables.findIndex(t => String(t.id) === String(payload.promoCodeId));
            const removedTable = removedIndex !== -1 ? this.tables[removedIndex] : null;

            if (removedIndex !== -1) {
                this.tables.splice(removedIndex, 1);
            }

            try {
                const response = await axios.delete(`${BASE}/${payload.promoCodeId}`);
                return response.data;
            } catch (err) {
                // Откат
                if (removedTable && removedIndex !== -1) {
                    this.tables.splice(removedIndex, 0, removedTable);
                }
                console.error('[Tables Store] Ошибка удаления стола:', err);
                throw err;
            } finally {
                delete this.tableActions[String(payload.promoCodeId)];
            }
        },

        // ==========================================
        // БРОНИРОВАНИЯ
        // ==========================================

        /**
         * Загрузка списка бронирований
         */
        async bookingList(payload = { date: null, number: null }) {
            this.isBookingsLoading = true;

            try {
                const response = await axios.post(`${BASE}/booking-list`, payload);
                this.bookings = response.data?.data || response.data || [];
                return this.bookings;
            } catch (err) {
                console.error('[Tables Store] Ошибка загрузки бронирований:', err);
                throw err;
            } finally {
                this.isBookingsLoading = false;
            }
        },

        /**
         * Загрузка ближайших бронирований
         */
        async nearestBookingList(payload = { start_date: null, end_date: null }) {
            this.isNearestBookingsLoading = true;

            try {
                const response = await axios.post(`${BASE}/nearest-booking-list`, payload);
                this.nearestBookings = response.data?.data || response.data || [];
                return this.nearestBookings;
            } catch (err) {
                console.error('[Tables Store] Ошибка загрузки ближайших бронирований:', err);
                throw err;
            } finally {
                this.isNearestBookingsLoading = false;
            }
        },

        /**
         * Загрузка моих предстоящих бронирований
         */
        async myUpcomingBookings() {
            this.isMyBookingsLoading = true;

            try {
                const response = await axios.post(`${BASE}/my-upcoming-bookings`);
                this.myBookings = response.data?.data || response.data || [];
                return this.myBookings;
            } catch (err) {
                console.error('[Tables Store] Ошибка загрузки моих бронирований:', err);
                throw err;
            } finally {
                this.isMyBookingsLoading = false;
            }
        },

        /**
         * Бронирование столика
         */
        async bookATable(payload = { dataObject: {} }) {
            this.lastError = null;

            try {
                const response = await axios.post(`${BASE}/book-table`, payload.dataObject);
                const booking = response.data?.data || response.data;

                // Добавляем в список бронирований
                if (booking?.id) {
                    this.bookings.unshift(booking);
                }

                return booking;
            } catch (err) {
                console.error('[Tables Store] Ошибка бронирования:', err);
                this.lastError = err.response?.data?.message || 'Не удалось забронировать столик';
                throw err;
            }
        },

        /**
         * Отмена бронирования (оптимистично)
         */
        async cancelBookingTable(payload = { bookingId: null }) {
            if (!payload.bookingId) throw new Error('Не указан ID бронирования');

            this.bookingActions[String(payload.bookingId)] = 'cancel';

            // Сохраняем предыдущее состояние
            const booking = this.getBookingById(payload.bookingId);
            const previousStatus = booking?.status;

            // Оптимистично отменяем
            if (booking) {
                booking.status = 'cancelled';
                booking.cancelled_at = new Date().toISOString();
            }

            try {
                const response = await axios.delete(`${BASE}/cancel-booking/${payload.bookingId}`);
                const updated = response.data?.data || response.data;

                if (booking && updated) {
                    Object.assign(booking, updated);
                }

                return updated;
            } catch (err) {
                // Откат
                if (booking) {
                    booking.status = previousStatus;
                    delete booking.cancelled_at;
                }
                console.error('[Tables Store] Ошибка отмены бронирования:', err);
                throw err;
            } finally {
                delete this.bookingActions[String(payload.bookingId)];
            }
        },

        /**
         * Экспорт ближайших бронирований
         */
        async exportNearestBookings(payload = { start_date: null, end_date: null }) {
            try {
                const response = await axios.post(`${BASE}/export-nearest-bookings`, payload);
                return response.data;
            } catch (err) {
                console.error('[Tables Store] Ошибка экспорта бронирований:', err);
                throw err;
            }
        },

        // ==========================================
        // ЗАКАЗЫ СТОЛА
        // ==========================================

        /**
         * Загрузка одобренной корзины текущего стола
         */
        async loadApprovedSelfTableBasket() {
            try {
                const response = await axios.post(`${BASE}/approved-self-basket`);
                return response.data;
            } catch (err) {
                console.error('[Tables Store] Ошибка загрузки корзины:', err);
                throw err;
            }
        },

        /**
         * Принятие заказа стола (официантом)
         */
        async acceptTableOder(payload = { dataObject: {} }) {
            this.isOrdersLoading = true;

            try {
                const response = await axios.post(`${BASE}/accept-table-order`, payload.dataObject);
                return response.data;
            } catch (err) {
                console.error('[Tables Store] Ошибка принятия заказа:', err);
                throw err;
            } finally {
                this.isOrdersLoading = false;
            }
        },

        /**
         * Запрос подтверждения стола
         */
        async requestApproveTable(payload = { dataObject: {} }) {
            this.isOrdersLoading = true;

            try {
                const response = await axios.post(`${BASE}/request-approve-table`, payload.dataObject);
                return response.data;
            } catch (err) {
                console.error('[Tables Store] Ошибка запроса подтверждения:', err);
                throw err;
            } finally {
                this.isOrdersLoading = false;
            }
        },

        /**
         * Начало оплаты стола
         */
        async startTablePay(payload) {
            this.isOrdersLoading = true;

            try {
                const response = await axios.post(`${BASE}/table-pay`, payload);
                return response.data;
            } catch (err) {
                console.error('[Tables Store] Ошибка начала оплаты:', err);
                throw err;
            } finally {
                this.isOrdersLoading = false;
            }
        },

        /**
         * Отправка заказа в чат
         */
        async sendOrderToMyChat(payload = { dataObject: {} }) {
            try {
                const response = await axios.post(`${BASE}/send-order-to-my-chat`, payload.dataObject);
                return response.data;
            } catch (err) {
                console.error('[Tables Store] Ошибка отправки заказа в чат:', err);
                throw err;
            }
        },

        /**
         * Закрытие стола
         */
        async closeTableOrder(payload = { dataObject: {} }) {
            const tableId = payload.dataObject?.table_id;
            if (tableId) {
                this.tableActions[String(tableId)] = 'close';
            }

            // Сохраняем предыдущее состояние
            const table = tableId ? this.getTableById(tableId) : this.currentTable;
            const previousClosedAt = table?.closed_at;

            // Оптимистично закрываем
            if (table) {
                table.closed_at = new Date().toISOString();
            }

            try {
                const response = await axios.post(`${BASE}/close-table`, payload.dataObject);
                const updated = response.data?.data || response.data;

                if (table && updated) {
                    Object.assign(table, updated);
                }

                return updated;
            } catch (err) {
                // Откат
                if (table) {
                    table.closed_at = previousClosedAt;
                }
                console.error('[Tables Store] Ошибка закрытия стола:', err);
                throw err;
            } finally {
                if (tableId) {
                    delete this.tableActions[String(tableId)];
                }
            }
        },

        // ==========================================
        // ОФИЦИАНТЫ
        // ==========================================

        /**
         * Вызов официанта
         */
        async requestWaiterComing(payload = { dataObject: {} }) {
            this.isWaiterLoading = true;

            try {
                const response = await axios.post(`${BASE}/call-waiter`, payload.dataObject);
                return response.data;
            } catch (err) {
                console.error('[Tables Store] Ошибка вызова официанта:', err);
                throw err;
            } finally {
                this.isWaiterLoading = false;
            }
        },

        /**
         * Смена официанта стола
         */
        async changeTableWaiter(payload = { dataObject: {} }) {
            try {
                const response = await axios.post(`${BASE}/change-table-waiter`, payload.dataObject);
                const updated = response.data?.data || response.data;

                // Обновляем текущего официанта в столе
                const tableId = payload.dataObject?.table_id;
                if (tableId && updated) {
                    const table = this.getTableById(tableId);
                    if (table) {
                        Object.assign(table, updated);
                    }
                    if (this.currentTable && String(this.currentTable.id) === String(tableId)) {
                        Object.assign(this.currentTable, updated);
                    }
                }

                return updated;
            } catch (err) {
                console.error('[Tables Store] Ошибка смены официанта:', err);
                throw err;
            }
        },

        // ==========================================
        // ДОПОЛНИТЕЛЬНЫЕ УСЛУГИ
        // ==========================================

        /**
         * Сохранение доп. услуги стола
         */
        async storeTableAdditionalServices(payload = { dataObject: null }) {
            try {
                const response = await axios.post(`${BASE}/store-additional-service`, payload.dataObject);
                const service = response.data?.data || response.data;

                if (service?.id) {
                    this.additionalServices.push(service);
                }

                return service;
            } catch (err) {
                console.error('[Tables Store] Ошибка сохранения доп. услуги:', err);
                throw err;
            }
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.tables = [];
            this.tables_paginate_object = null;
            this.currentTable = null;
            this.bookings = [];
            this.myBookings = [];
            this.nearestBookings = [];
            this.additionalServices = [];
            this.isLoading = false;
            this.isHydrated = false;
            this.isTablesLoading = false;
            this.isBookingsLoading = false;
            this.isMyBookingsLoading = false;
            this.isNearestBookingsLoading = false;
            this.isCurrentTableLoading = false;
            this.isOrdersLoading = false;
            this.isWaiterLoading = false;
            this.tableActions = {};
            this.bookingActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('cashman_tables');
            localStorage.removeItem('cashman_tables_paginate_object');
            localStorage.removeItem('cashman_current_active_table');
        },
    },
});
