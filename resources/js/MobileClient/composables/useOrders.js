import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useOrdersStore } from '@/MobileClient/stores/Shop/orders';

/**
 * Composable для работы с заказами и отзывами
 */
export function useOrders() {
    const store = useOrdersStore();

    // ==========================================
    // Реактивные ссылки на состояние (заказы)
    // ==========================================
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
        // Отзывы (нужно добавить в store)
        reviews = [],
        reviews_paginate_object = null,
        isLoadingReviews = false,
    } = storeToRefs(store);

    // ==========================================
    // Реактивные геттеры
    // ==========================================
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
    const reviewsCount = computed(() => store.reviewsCount ?? 0);

    // ==========================================
    // Вспомогательные функции
    // ==========================================
    const isOrderLoading = (orderId) => store.isOrderLoading(orderId);

    // ==========================================
    // Безопасные методы (заказы)
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

    const loadRandomOrders = async () => {
        try {
            return await store.loadRandomOrders();
        } catch (error) {
            console.error('Ошибка загрузки случайных заказов:', error);
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

    const repeatOrder = async (payload) => {
        try {
            return await store.repeatOrder(payload);
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

    // ==========================================
    // Безопасные методы (отзывы)
    // ==========================================
    const loadReviews = async (payload = {}) => {
        try {
            return await store.loadReviews(payload);
        } catch (error) {
            console.error('Ошибка загрузки отзывов:', error);
            throw error;
        }
    };

    const storeReview = async (payload) => {
        try {
            return await store.storeReview(payload);
        } catch (error) {
            console.error('Ошибка сохранения отзыва:', error);
            throw error;
        }
    };

    const getReviewsByProductId = async (productId, payload = {}) => {
        try {
            return await store.getReviewsByProductId({
                dataObject: { product_id: productId, ...payload },
            });
        } catch (error) {
            console.error('Ошибка загрузки отзывов по товару:', error);
            throw error;
        }
    };

    return {
        // Состояние (заказы)
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

        // Состояние (отзывы)
        reviews,
        reviews_paginate_object,
        isLoadingReviews,

        // Геттеры (заказы)
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

        // Геттеры (отзывы)
        reviewsCount,

        // Методы (заказы)
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

        loadRandomOrders,
        getRandomRecentOrders: store.getRandomRecentOrders,
        randomRecentOrders: storeToRefs(store).randomRecentOrders,
        isLoadingRandom: storeToRefs(store).isLoadingRandom,

        // Методы (отзывы)
        getReviewsByProductId,
        loadReviews,
        storeReview,
        updateReview: store.updateReview,
        deleteReview: store.deleteReview,
        canReviewOrder: store.canReviewOrder,

        // Корзина (если есть в store)
        clearCart: store.clearCart,
        addProductToCart: store.addProductToCart,

        // Сброс
        $reset: store.$reset,
    };
}
