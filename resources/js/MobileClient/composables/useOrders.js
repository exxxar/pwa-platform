import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useOrdersStore } from '@/stores/orders.js';

/**
 * Composable для работы с заказами
 */
export function useOrders() {
    const store = useOrdersStore();

    // Реактивные ссылки на состояние
    const {
        orders,
        orders_paginate_object,
        isLoading,
        isHydrated,
        isStatusChanging,
        isDeliveryLoading,
        orderActions,
        lastError,
        errors,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedOrders = computed(() => store.sortedOrders);
    const pendingOrders = computed(() => store.pendingOrders);
    const completedOrders = computed(() => store.completedOrders);
    const cancelledOrders = computed(() => store.cancelledOrders);
    const todayOrders = computed(() => store.todayOrders);
    const weekOrders = computed(() => store.weekOrders);
    const totalRevenue = computed(() => store.totalRevenue);
    const todayRevenue = computed(() => store.todayRevenue);
    const ordersCount = computed(() => store.ordersCount);
    const pendingOrdersCount = computed(() => store.pendingOrdersCount);

    /**
     * Проверка, загружается ли заказ
     */
    const isOrderLoading = (orderId) => {
        return store.isOrderLoading(orderId);
    };

    // ==========================================
    // Безопасные методы
    // ==========================================

    const loadOrders = async (payload = {}) => {
        try {
            return await store.loadOrders(payload);
        } catch (error) {
            console.error('Ошибка загрузки заказов:', error);
            throw error;
        }
    };

    const loadAllOrders = async (payload = {}) => {
        try {
            return await store.loadAllOrders(payload);
        } catch (error) {
            console.error('Ошибка загрузки всех заказов:', error);
            throw error;
        }
    };

    const loadOrderById = async (orderId) => {
        try {
            return await store.loadOrderById({
                dataObject: { order_id: orderId },
            });
        } catch (error) {
            console.error('Ошибка загрузки заказа:', error);
            throw error;
        }
    };

    const changeStatus = async (orderId, status) => {
        try {
            return await store.changeOrderStatus({
                dataObject: { order_id: orderId, status },
            });
        } catch (error) {
            console.error('Ошибка изменения статуса:', error);
            throw error;
        }
    };

    const declineOrder = async (orderId) => {
        try {
            return await store.declineOrder({
                dataObject: { order_id: orderId },
            });
        } catch (error) {
            console.error('Ошибка отмены заказа:', error);
            throw error;
        }
    };

    const repeatOrder = async (orderId) => {
        try {
            return await store.repeatOrder({ order_id: orderId });
        } catch (error) {
            console.error('Ошибка повтора заказа:', error);
            throw error;
        }
    };

    const calculateDeliveryPrice = async (payload) => {
        try {
            return await store.requestDeliveryPriceNew(payload);
        } catch (error) {
            console.error('Ошибка расчёта доставки:', error);
            throw error;
        }
    };

    return {
        // Состояние
        orders,
        orders_paginate_object,
        isLoading,
        isHydrated,
        isStatusChanging,
        isDeliveryLoading,
        orderActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры
        sortedOrders,
        pendingOrders,
        completedOrders,
        cancelledOrders,
        todayOrders,
        weekOrders,
        totalRevenue,
        todayRevenue,
        ordersCount,
        pendingOrdersCount,
        getOrderById: store.getOrderById,
        ordersByStatus: store.ordersByStatus,
        isOrderLoading,

        // Методы
        loadOrders,
        loadAllOrders,
        loadOrderById,
        changeStatus,
        declineOrder,
        repeatOrder,
        sendSBPInvoice: store.sendSBPInvoice,
        addCashBackToOrder: store.addCashBackToOrder,
        calculateDeliveryPrice,
        requestDeliveryPrice: store.requestDeliveryPrice,
        exportAllOrders: store.exportAllOrders,

        // Сброс
        $reset: store.$reset,
    };
}
