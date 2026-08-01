import { storeToRefs } from 'pinia';
import { useOrdersStore } from "@/MobileClient/stores/Shop/orders";

export function useOrders() {
    const store = useOrdersStore();

    // ==========================================
    // 1. ТОЛЬКО STATE И GETTERS идут в storeToRefs!
    // ==========================================
    const refs = storeToRefs(store);

    // ==========================================
    // 2. ACTIONS берем напрямую из store или делаем обертки
    // ==========================================
    const loadAdminOrders = (payload = {}) => store.loadAdminOrders(payload);
    const loadAdminOrderDetails = (orderId) => store.loadAdminOrderDetails(orderId);
    const updateAdminOrderStatus = (orderId, status) => store.updateAdminOrderStatus(orderId, status);
    const sendAdminOrderMessage = (orderId, message) => store.sendAdminOrderMessage(orderId, message);

    const loadOrders = (payload = {}) => store.loadOrders(payload);
    const loadAllOrders = (payload = {}) => store.loadAllOrders(payload);
    const loadOrderById = (payload = {}) => store.loadOrderById(payload);
    const repeatOrder = (payload = {}) => store.repeatOrder(payload);

    // ==========================================
    // 3. ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
    // ==========================================
    return {
        // --- Реактивные данные (Refs) ---
        adminOrders: refs.adminOrders,
        adminOrdersPaginate: refs.adminOrdersPaginate,
        isLoadingAdmin: refs.isLoadingAdmin,

        orders: refs.orders,
        orders_paginate_object: refs.orders_paginate_object,
        isLoading: refs.isLoading,
        isHydrated: refs.isHydrated,
        isStatusChanging: refs.isStatusChanging,
        isDeliveryLoading: refs.isDeliveryLoading,
        orderActions: refs.orderActions,
        lastError: refs.lastError,
        errors: refs.errors,
        lastSyncAt: refs.lastSyncAt,
        randomRecentOrders: refs.randomRecentOrders,
        isLoadingRandom: refs.isLoadingRandom,
        isOrderLoading: refs.isOrderLoading,

        sortedOrders: refs.sortedOrders,
        pendingOrders: refs.pendingOrders,
        completedOrders: refs.completedOrders,
        cancelledOrders: refs.cancelledOrders,
        todayOrders: refs.todayOrders,
        weekOrders: refs.weekOrders,
        totalRevenue: refs.totalRevenue,
        todayRevenue: refs.todayRevenue,
        ordersCount: refs.ordersCount,
        pendingOrdersCount: refs.pendingOrdersCount,
        getOrderById: refs.getOrderById,
        ordersByStatus: refs.ordersByStatus,

        reviews: refs.reviews,
        reviews_paginate_object: refs.reviews_paginate_object,
        isLoadingReviews: refs.isLoadingReviews,
        reviewsCount: refs.reviewsCount,

        // --- Функции (Actions) ---
        loadAdminOrders,
        loadAdminOrderDetails,
        updateAdminOrderStatus,
        sendAdminOrderMessage,

        loadOrders,
        loadAllOrders,
        loadOrderById,
        repeatOrder,

        // Прямые прокси для остальных действий
        changeStatus: store.changeStatus,
        declineOrder: store.declineOrder,
        calculateDeliveryPrice: store.calculateDeliveryPrice,
        requestDeliveryPrice: store.requestDeliveryPrice,
        sendSBPInvoice: store.sendSBPInvoice,
        addCashBackToOrder: store.addCashBackToOrder,
        exportAllOrders: store.exportAllOrders,
        getRandomRecentOrders: store.getRandomRecentOrders,
        loadRandomOrders: store.loadRandomOrders,

        loadReviews: store.loadReviews,
        storeReview: store.storeReview,
        getReviewsByProductId: store.getReviewsByProductId,
        updateReview: store.updateReview,
        deleteReview: store.deleteReview,
        canReviewOrder: store.canReviewOrder,

        $reset: store.$reset,
    };
}
