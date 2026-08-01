import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/shop/orders';
const BASE_REVIEWS = '/shop/reviews';

export const useOrdersStore = defineStore('orders', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        orders: [],
        orders_paginate_object: null,

        reviews: [],
        reviews_paginate_object: null,
        isLoadingReviews: false,

        // Состояние загрузки по секциям
        isLoading: false,
        isHydrated: false,
        isStatusChanging: false,
        isDeliveryLoading: false,

        // Действия над заказами: { [orderId]: 'status' | 'decline' | 'repeat' }
        orderActions: {},

        // Ошибки
        lastError: null,
        errors: [],

        // Время последней синхронизации
        lastSyncAt: null,

        randomRecentOrders: [],
        isLoadingRandom: false,

        currentOrder: null,
        isLoadingOrderDetails: false,
        isUpdatingStatus: false,
        isSendingMessage: false,

        adminOrders:[],
        adminOrdersPaginate:null,

        isLoadingAdmin: false, // 🔥 ДОБАВИТЬ СЮДА!
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {

        reviewsCount: (state) => state.reviews.length,

        getRandomRecentOrders: (state) =>state.randomRecentOrders || [],
        /**
         * Все заказы
         */
        getOrders: (state) => state.orders || [],

        /**
         * Пагинация
         */
        getOrdersPaginateObject: (state) => state.orders_paginate_object || null,

        /**
         * Найти заказ по ID
         */
        getOrderById: (state) => (id) => {
            return (state.orders || []).find(o => String(o.id) === String(id)) || null;
        },

        /**
         * Отсортированные заказы (новые сверху)
         */
        sortedOrders: (state) => {
            return [...(state.orders || [])].sort((a, b) => {
                const dateA = new Date(a.created_at || 0);
                const dateB = new Date(b.created_at || 0);
                return dateB - dateA;
            });
        },

        /**
         * Заказы по статусу
         */
        ordersByStatus: (state) => (status) => {
            return (state.orders || []).filter(o => o.status === status);
        },

        /**
         * Ожидающие заказы
         */
        pendingOrders: (state) => {
            return (state.orders || []).filter(o =>
                ['pending', 'new', 'processing'].includes(o.status)
            );
        },

        /**
         * Выполненные заказы
         */
        completedOrders: (state) => {
            return (state.orders || []).filter(o =>
                ['completed', 'delivered', 'done'].includes(o.status)
            );
        },

        /**
         * Отменённые заказы
         */
        cancelledOrders: (state) => {
            return (state.orders || []).filter(o =>
                ['cancelled', 'declined', 'rejected'].includes(o.status)
            );
        },

        /**
         * Заказы на сегодня
         */
        todayOrders: (state) => {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            return (state.orders || []).filter(o => {
                const date = new Date(o.created_at || 0);
                return date >= today && date < tomorrow;
            });
        },

        /**
         * Заказы за неделю
         */
        weekOrders: (state) => {
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);

            return (state.orders || []).filter(o => {
                const date = new Date(o.created_at || 0);
                return date >= weekAgo;
            });
        },

        /**
         * Общая выручка (по выполненным заказам)
         */
        totalRevenue: (state) => {
            return (state.orders || [])
                .filter(o => ['completed', 'delivered', 'done'].includes(o.status))
                .reduce((sum, o) => sum + (o.total || o.sum || 0), 0);
        },

        /**
         * Выручка за сегодня
         */
        todayRevenue: (state) => {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            return (state.orders || [])
                .filter(o => {
                    const date = new Date(o.created_at || 0);
                    return date >= today && date < tomorrow &&
                        ['completed', 'delivered', 'done'].includes(o.status);
                })
                .reduce((sum, o) => sum + (o.total || o.sum || 0), 0);
        },

        /**
         * Проверка, загружается ли заказ
         */
        isOrderLoading: (state) => (id) => {
            return !!state.orderActions[String(id)];
        },

        /**
         * Количество заказов
         */
        ordersCount: (state) => state.orders?.length || 0,

        /**
         * Количество ожидающих заказов
         */
        pendingOrdersCount: (state) => {
            return (state.orders || []).filter(o =>
                ['pending', 'new', 'processing'].includes(o.status)
            ).length;
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {

        // ==========================================
        // ОТЗЫВЫ
        // ==========================================

        /**
         * Проверить возможность оставить отзыв
         */
        async canReviewOrder(orderId) {
            try {
                const response = await axios.post(`${BASE_REVIEWS}/can-review-order`, {
                    order_id: orderId,
                });
                return response.data;
            } catch (err) {
                console.error('[Orders Store] Ошибка проверки отзыва:', err);
                return { can_review: false, reason: 'Ошибка проверки' };
            }
        },

        async loadRandomOrders() {
            this.isLoadingRandom = true;
            try {
                const response = await axios.post(`${BASE}/rr`);
                this.randomRecentOrders = response.data.data || [];
                console.log("rr", this.randomRecentOrders)
                return this.randomRecentOrders;
            } catch (error) {
                console.error('[Orders] Ошибка загрузки random orders:', error);
                throw error;
            } finally {
                this.isLoadingRandom = false;
            }
        },
        /**
         * Создать отзыв
         */
        async storeReview(payload) {
            try {
                const response = await axios.post(`${BASE_REVIEWS}/store-review`, payload);
                return response.data?.data || response.data;
            } catch (err) {
                console.error('[Orders Store] Ошибка создания отзыва:', err);
                throw err;
            }
        },

        /**
         * Обновить отзыв
         */
        async updateReview(reviewId, payload) {
            try {
                const response = await axios.put(`${BASE_REVIEWS}/update-review/${reviewId}`, payload);
                return response.data?.data || response.data;
            } catch (err) {
                console.error('[Orders Store] Ошибка обновления отзыва:', err);
                throw err;
            }
        },

        /**
         * Удалить отзыв
         */
        async deleteReview(reviewId) {
            try {
                await axios.delete(`${BASE_REVIEWS}/delete-review/${reviewId}`);
                return true;
            } catch (err) {
                console.error('[Orders Store] Ошибка удаления отзыва:', err);
                throw err;
            }
        },
        async loadReviews(payload = {}) {
            this.isLoadingReviews = true;
            try {
                const resp = await axios.post(`${BASE_REVIEWS}`, payload);
                this.reviews = resp.data?.data || [];
                this.reviews_paginate_object = resp.data?.paginate || null;
                return resp.data;
            } finally {
                this.isLoadingReviews = false;
            }
        },


        async getReviewsByProductId(payload) {
            return await axios.post(`${BASE_REVIEWS}/by-product-id`, payload);
        },

        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка списка заказов (текущего пользователя)
         */
        async loadOrders(payload = {}) {
            this.isLoading = true;
            try {
                const response = await axios.post(`${BASE}`, payload);
                const data = response.data || response;

                // Laravel пагинация: { data, meta, links }
                if (data?.meta) {
                    this.orders = data.data || [];
                    this.orders_paginate_object = {
                        total: data.meta.total,
                        per_page: data.meta.per_page,
                        current_page: data.meta.current_page,
                        last_page: data.meta.last_page,
                        from: data.meta.from,
                        to: data.meta.to,
                    };
                }
                // Старый формат: { data, paginate }
                else if (data?.paginate) {
                    this.orders = data.data || [];
                    this.orders_paginate_object = data.paginate;
                }
                // Просто массив
                else if (Array.isArray(data)) {
                    this.orders = data;
                    this.orders_paginate_object = null;
                }

                return {
                    data: this.orders,
                    paginate: this.orders_paginate_object,
                };
            } catch (error) {
                console.error('loadOrders error:', error);
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка всех заказов (админ)
         */
        async loadAllOrders(payload = { dataObject: {}, page: 0, size: 20 }) {
            this.isLoading = true;
            this.lastError = null;

            try {
                const page = payload.page || 0;
                const size = payload.size || 20;
                const link = `${BASE}/all?page=${page}&size=${size}`;

                const response = await axios.post(link, payload.dataObject || {});
                const dataObject = response.data;

                this.orders = dataObject.data || [];
                const { data, ...paginate } = dataObject;
                this.orders_paginate_object = paginate;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                return paginate;
            } catch (err) {
                console.error('[Orders Store] Ошибка загрузки всех заказов:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить заказы';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка заказа по ID
         */
        async loadOrderById(payload = { dataObject: { order_id: null } }) {
            if (!payload.dataObject?.order_id) {
                throw new Error('Не указан ID заказа');
            }

            try {
                const response = await axios.post(
                    `${BASE}/get-order-by-id`,
                    payload.dataObject
                );
                const order = response.data?.data || response.data;

                // Обновляем в списке, если есть
                if (order?.id) {
                    const index = this.orders.findIndex(
                        o => String(o.id) === String(order.id)
                    );
                    if (index !== -1) {
                        this.orders[index] = { ...this.orders[index], ...order };
                    } else {
                        this.orders.unshift(order);
                    }
                }

                return order;
            } catch (err) {
                console.error('[Orders Store] Ошибка загрузки заказа:', err);
                throw err;
            }
        },

        // ==========================================
        // СТАТУСЫ
        // ==========================================

        /**
         * Изменение статуса заказа (оптимистично)
         */
        async changeOrderStatus(payload = { dataObject: { order_id: null, status: null } }) {
            const orderId = payload.dataObject?.order_id;
            const newStatus = payload.dataObject?.status;

            if (!orderId || !newStatus) {
                throw new Error('Не указан ID заказа или новый статус');
            }

            this.orderActions[String(orderId)] = 'status';
            this.isStatusChanging = true;

            // Сохраняем предыдущее состояние
            const order = this.getOrderById(orderId);
            const previousStatus = order?.status;

            // Оптимистично меняем статус
            if (order) {
                order.status = newStatus;
                order.status_changed_at = new Date().toISOString();
            }

            try {
                const response = await axios.post(
                    `${BASE}/change-order-status`,
                    payload.dataObject
                );
                const updated = response.data?.data || response.data;

                // Синхронизируем с сервером
                if (order && updated) {
                    Object.assign(order, updated);
                }

                // Сохраняем в localStorage
                localStorage.setItem('cashman_orders', JSON.stringify(this.orders));

                return updated;
            } catch (err) {
                // Откат
                if (order && previousStatus) {
                    order.status = previousStatus;
                    delete order.status_changed_at;
                }
                console.error('[Orders Store] Ошибка изменения статуса:', err);
                this.lastError = err.response?.data?.message || 'Не удалось изменить статус';
                throw err;
            } finally {
                delete this.orderActions[String(orderId)];
                this.isStatusChanging = false;
            }
        },

        /**
         * Отмена заказа (оптимистично)
         */
        async declineOrder(payload = { dataObject: { order_id: null } }) {
            const orderId = payload.dataObject?.order_id;
            if (!orderId) throw new Error('Не указан ID заказа');

            this.orderActions[String(orderId)] = 'decline';

            // Сохраняем предыдущее состояние
            const order = this.getOrderById(orderId);
            const previousStatus = order?.status;

            // Оптимистично отменяем
            if (order) {
                order.status = 'cancelled';
                order.cancelled_at = new Date().toISOString();
            }

            try {
                const response = await axios.post(
                    `${BASE}/decline-order`,
                    payload.dataObject
                );
                const updated = response.data?.data || response.data;

                if (order && updated) {
                    Object.assign(order, updated);
                }

                localStorage.setItem('cashman_orders', JSON.stringify(this.orders));

                return updated;
            } catch (err) {
                // Откат
                if (order && previousStatus) {
                    order.status = previousStatus;
                    delete order.cancelled_at;
                }
                console.error('[Orders Store] Ошибка отмены заказа:', err);
                this.lastError = err.response?.data?.message || 'Не удалось отменить заказ';
                throw err;
            } finally {
                delete this.orderActions[String(orderId)];
            }
        },

        // ==========================================
        // ОПЛАТА
        // ==========================================

        /**
         * Отправка СБП-инвойса
         */
        async sendSBPInvoice(payload = { dataObject: null }) {
            try {
                const response = await axios.post(
                    `${BASE}/send-sbp-invoice`,
                    payload.dataObject
                );
                return response.data;
            } catch (err) {
                console.error('[Orders Store] Ошибка отправки СБП-инвойса:', err);
                throw err;
            }
        },

        /**
         * Добавление кэшбэка к заказу
         */
        async addCashBackToOrder(payload = { order_id: null }) {
            if (!payload.order_id) throw new Error('Не указан ID заказа');

            try {
                const response = await axios.post(
                    `${BASE}/add-cashback-to-order`,
                    payload
                );
                const result = response.data?.data || response.data;

                // Обновляем заказ в списке
                if (result?.order_id || payload.order_id) {
                    const orderId = result?.order_id || payload.order_id;
                    const order = this.getOrderById(orderId);
                    if (order && result) {
                        Object.assign(order, result);
                    }
                }

                return result;
            } catch (err) {
                console.error('[Orders Store] Ошибка добавления кэшбэка:', err);
                throw err;
            }
        },

        // ==========================================
        // ДОСТАВКА
        // ==========================================

        /**
         * Расчёт стоимости доставки (новый метод)
         */
        async requestDeliveryPriceNew(payload) {
            this.isDeliveryLoading = true;

            try {
                const response = await axios.post(
                    `${BASE}/get-delivery-price-new`,
                    payload
                );
                return response.data;
            } catch (err) {
                console.error('[Orders Store] Ошибка расчёта доставки:', err);
                throw err;
            } finally {
                this.isDeliveryLoading = false;
            }
        },

        /**
         * Расчёт стоимости доставки (старый метод)
         */
        async requestDeliveryPrice(payload) {
            this.isDeliveryLoading = true;

            try {
                const response = await axios.post(
                    `${BASE}/get-delivery-price`,
                    payload
                );
                return response.data;
            } catch (err) {
                console.error('[Orders Store] Ошибка расчёта доставки:', err);
                throw err;
            } finally {
                this.isDeliveryLoading = false;
            }
        },

        async loadAdminOrders(payload = {}) {
            this.isLoadingAdmin = true;
            try {
                const response = await axios.get('/admin/orders', {
                    params: {
                        page: payload.page || 1,
                        size: payload.size || 20,
                        search: payload.search || null,
                        status: payload.status || null,
                        order_by: payload.order_by || 'id',
                        direction: payload.direction || 'desc'
                    }
                });

                const responseData = response.data;

                if (responseData && Array.isArray(responseData.data)) {
                    this.adminOrders = responseData.data;
                    this.adminOrdersPaginate = responseData.paginate || null;
                } else {
                    this.adminOrders = [];
                    this.adminOrdersPaginate = null;
                }

            } catch (error) {
                console.error('Ошибка загрузки админских заказов:', error);
                this.adminOrders = [];
                this.adminOrdersPaginate = null;
            } finally {
                this.isLoadingAdmin = false;
            }

            // 🔥 УДАЛИТЕ ЭТОТ БЛОК ОТСЮДА. Он не нужен и возвращает undefined.
            // async функция и так вернет Promise, который разрешится успешно.
        },

        async loadAdminOrderDetails(orderId) {
            this.isLoadingOrderDetails = true;
            try {
                const response = await axios.get(`/admin/orders/${orderId}`);
                this.currentOrder = response.data;
                return response.data;
            } catch (error) {
                console.error('Ошибка загрузки деталей заказа:', error);
                throw error;
            } finally {
                this.isLoadingOrderDetails = false;
            }
        },

        /**
         * Быстрая смена статуса заказа
         */
        async updateAdminOrderStatus(orderId, status) {
            this.isUpdatingStatus = true;
            try {
                const response = await axios.post(`/admin/orders/${orderId}/status`, { status });

                // 🔄 Мгновенно обновляем локальное состояние, чтобы UI отреагировал без перезагрузки
                if (this.currentOrder && this.currentOrder.id === orderId) {
                    this.currentOrder.status = status;
                }

                return response.data;
            } catch (error) {
                console.error('Ошибка обновления статуса заказа:', error);
                throw error;
            } finally {
                this.isUpdatingStatus = false;
            }
        },

        /**
         * Отправка сообщения в чат, привязанный к заказу
         */
        async sendAdminOrderMessage(orderId, message) {
            this.isSendingMessage = true;
            try {
                const response = await axios.post(`/admin/orders/${orderId}/message`, { message });
                return response.data;
            } catch (error) {
                console.error('Ошибка отправки сообщения:', error);
                throw error;
            } finally {
                this.isSendingMessage = false;
            }
        },
        // ==========================================
        // ПОВТОР ЗАКАЗА
        // ==========================================

        /**
         * Повтор заказа
         */
        async repeatOrder(payload) {
            const orderId = payload?.order_id || payload?.id;
            if (orderId) {
                this.orderActions[String(orderId)] = 'repeat';
            }

            try {
                const response = await axios.post(`${BASE}/repeat-order`, payload);
                return response.data;
            } catch (err) {
                console.error('[Orders Store] Ошибка повтора заказа:', err);
                throw err;
            } finally {
                if (orderId) {
                    delete this.orderActions[String(orderId)];
                }
            }
        },

        // ==========================================
        // ЭКСПОРТ
        // ==========================================

        /**
         * Экспорт всех заказов
         */
        async exportAllOrders() {
            try {
                const response = await axios.post(`${BASE}/export-all-orders`);
                return response.data;
            } catch (err) {
                console.error('[Orders Store] Ошибка экспорта заказов:', err);
                throw err;
            }
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.orders = [];
            this.orders_paginate_object = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isStatusChanging = false;
            this.isDeliveryLoading = false;
            this.orderActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('cashman_orders');
            localStorage.removeItem('cashman_orders_paginate_object');
        },
    },
});
