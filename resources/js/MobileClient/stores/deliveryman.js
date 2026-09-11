import { defineStore } from 'pinia';
import axios from 'axios';

export const useDeliverymanStore = defineStore('deliveryman', {
    state: () => ({
        deliveryman: {
            id: null,
            name: 'Курьер',
            status: 'offline',
            earned: 0, // 🆕 Было balance
            available_orders_count: 0, // 🆕 Новое поле
            active_orders_count: 0,
            completed_orders_count: 0,
            total_delivered: 0,
            rating: 4.9
        },
        activeOrders: [],
        availableOrders: [],
        completedOrders: [],
        transactions: [],
        isOnline: false,
        isLoading: false,
        geoWatchId: null,
    }),

    actions: {
        async fetchDashboard() {
            this.isLoading = true;
            try {
                const res = await axios.get('/deliveryman/dashboard');
                this.deliveryman = res.data.data.deliveryman;
                this.isOnline = this.deliveryman.status === 'online';

                // Параллельная загрузка списков
                await Promise.all([
                    this.fetchActiveOrders(),
                    this.fetchAvailableOrders(),
                    this.fetchCompletedOrders() //
                ]);
            } catch (e) {
                console.error('Ошибка загрузки дашборда:', e);
            } finally {
                this.isLoading = false;
            }
        },

        async fetchCompletedOrders() {
            try {
                const res = await axios.get('/deliveryman/orders/completed');
                this.completedOrders = res.data.data;
            } catch (e) {
                console.error('Ошибка загрузки завершенных заказов:', e);
            }
        },
        async toggleStatus(isOnline) {
            try {
                const res = await axios.post('/deliveryman/toggle-status', { is_online: isOnline });
                this.isOnline = res.data.is_online;
                this.deliveryman.status = this.isOnline ? 'online' : 'offline';
                return true;
            } catch (e) {
                console.error('Ошибка смены статуса:', e);
                return false;
            }
        },

        async fetchActiveOrders() {
            const res = await axios.get('/deliveryman/orders/active');
            this.activeOrders = res.data.data;
            this.deliveryman.active_orders_count = this.activeOrders.length;
        },

        async fetchAvailableOrders() {
            const res = await axios.get('/deliveryman/orders/available');
            this.availableOrders = res.data.data;
        },

        async acceptOrder(orderId) {
            try {
                await axios.post(`/deliveryman/orders/${orderId}/accept`);
                await this.fetchActiveOrders(); // Обновляем списки
                await this.fetchAvailableOrders();
                return true;
            } catch (e) {
                console.error('Ошибка принятия заказа:', e);
                throw e;
            }
        },

        async confirmDelivery(orderId) {
            try {
                const res = await axios.post(`/deliveryman/orders/${orderId}/confirm-delivery`);
                await this.fetchActiveOrders();
                await this.fetchDashboard(); // Обновляем баланс
                return res.data;
            } catch (e) {
                console.error('Ошибка подтверждения доставки:', e);
                throw e;
            }
        },

        startLocationTracking() {
            if ('geolocation' in navigator) {
                this.geoWatchId = navigator.geolocation.watchPosition(
                    (position) => {
                        // Отправляем координаты на бэкенд (можно делать debounce)
                        axios.post(`/deliveryman/orders/location`, { // Упрощенный эндпоинт для примера
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        }).catch(() => {});
                    },
                    (error) => console.warn('Geo error:', error),
                    { enableHighAccuracy: true, maximumAge: 10000 }
                );
            }
        },

        stopLocationTracking() {
            if (this.geoWatchId) {
                navigator.geolocation.clearWatch(this.geoWatchId);
                this.geoWatchId = null;
            }
        }
    }
});
