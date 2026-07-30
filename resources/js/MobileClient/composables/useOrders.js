import { storeToRefs } from 'pinia';
import {useOrdersStore} from "@/MobileClient/stores/Shop/orders";

export function useOrders() {
    const store = useOrdersStore();

    // ==========================================
    // 1. ЕДИНАЯ ДЕСТРУКТУРИЗАЦИЯ РЕАКТИВНЫХ ССЫЛОК
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
        randomRecentOrders,
        isLoadingRandom,
        isOrderLoading,

        loadRandomOrders,
        changeStatus,
        declineOrder,
        repeatOrder,
        calculateDeliveryPrice,

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
        getOrderById,
        ordersByStatus,

        reviews,
        reviews_paginate_object,
        isLoadingReviews,
        reviewsCount,

        // 🆕 ДОБАВЬТЕ ЭТИ ТРИ СТРОКИ СЮДА:
        adminOrders,
        adminOrdersPaginate,
        isLoadingAdmin,
        loadAllOrders ,
        loadOrderById  ,

        loadReviews,
        storeReview,
        getReviewsByProductId,

    } = storeToRefs(store);

    // ... (ваши методы loadOrders и т.д. остаются без изменений) ...

    const loadOrders  = (payload = {}) => store.loadOrders(payload);
    const loadAdminOrders = (payload = {}) => store.loadAdminOrders(payload);
    const loadAdminOrderDetails = (orderId) => store.loadAdminOrderDetails(orderId);
    const updateAdminOrderStatus = (orderId, status) => store.updateAdminOrderStatus(orderId, status);
    const sendAdminOrderMessage = (orderId, message) => store.sendAdminOrderMessage(orderId, message);

    // ==========================================
    // 3. ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
    // ==========================================
    return {
        // 🆕 УДАЛИТЕ отсюда store.adminOrders и верните просто переменные из storeToRefs:
        adminOrders,
        adminOrdersPaginate,
        isLoadingAdmin,

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
        randomRecentOrders,
        isLoadingRandom,

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
        getOrderById,
        ordersByStatus,
        isOrderLoading,

        reviews,
        reviews_paginate_object,
        isLoadingReviews,
        reviewsCount,

        loadOrders,
        loadAllOrders,
        loadOrderById,
        loadRandomOrders,
        changeStatus,
        declineOrder,
        repeatOrder,
        calculateDeliveryPrice,
        requestDeliveryPrice: store.requestDeliveryPrice,
        sendSBPInvoice: store.sendSBPInvoice,
        addCashBackToOrder: store.addCashBackToOrder,
        exportAllOrders: store.exportAllOrders,
        getRandomRecentOrders: store.getRandomRecentOrders,

        loadReviews,
        storeReview,
        getReviewsByProductId,
        updateReview: store.updateReview,
        deleteReview: store.deleteReview,
        canReviewOrder: store.canReviewOrder,

        loadAdminOrders,
        loadAdminOrderDetails,
        updateAdminOrderStatus,
        sendAdminOrderMessage,

        $reset: store.$reset,
    };
}
