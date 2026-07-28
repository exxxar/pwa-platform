import { storeToRefs } from 'pinia';
import { useOrdersStore } from '@/MobileClient/stores/Shop/orders';


/**
 * Composable-фасад для работы с заказами, отзывами и корзиной.
 * Убирает лишнюю реактивность и упрощает код компонентов.
 */
export function useOrders() {
    const store = useOrdersStore();


    // ==========================================
    // 1. ЕДИНАЯ ДЕСТРУКТУРИЗАЦИЯ РЕАКТИВНЫХ ССЫЛОК
    // ==========================================
    // Pinia геттеры уже реактивны, поэтому мы берем их прямо через storeToRefs
    const {
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
        randomRecentOrders,
        isLoadingRandom,

        // Геттеры (заказы) - УБРАНЫ лишние computed(), берем напрямую из стора
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

        // Состояние и геттеры (отзывы)
        // Примечание: дефолтные значения лучше задавать внутри самого Pinia store (state: () => ({ reviews: [] }))
        reviews,
        reviews_paginate_object,
        isLoadingReviews,
        reviewsCount,
    } = storeToRefs(store);

    // ==========================================
    // 2. БЕЗОПАСНЫЕ МЕТОДЫ (без избыточного try/catch)
    // ==========================================
    // Мы возвращаем промисы напрямую. Если нужна обработка ошибок с уведомлениями,
    // это лучше делать либо в самом store, либо в компоненте.
    // Это делает код в 2 раза короче и чище.

    const loadOrders = (payload = {}) => store.loadOrders(payload);
    const loadAllOrders = (payload = {}) => store.loadAllOrders(payload);

    const loadOrderById = (orderId) => store.loadOrderById({
        dataObject: { order_id: orderId },
    });

    const loadRandomOrders = () => store.loadRandomOrders();

    const changeStatus = (orderId, status) => store.changeOrderStatus({
        dataObject: { order_id: orderId, status },
    });

    const declineOrder = (orderId) => store.declineOrder({
        dataObject: { order_id: orderId },
    });

    const repeatOrder = (payload) => store.repeatOrder(payload);

    const calculateDeliveryPrice = (payload) => store.requestDeliveryPriceNew(payload);

    // Методы отзывов
    const loadReviews = (payload = {}) => store.loadReviews(payload);

    const storeReview = (payload) => store.storeReview(payload);

    const getReviewsByProductId = (productId, payload = {}) => store.getReviewsByProductId({
        dataObject: { product_id: productId, ...payload },
    });

    // Вспомогательная функция (не требует реактивности, просто проверка)
    const isOrderLoading = (orderId) => store.isOrderLoading(orderId);

    // ==========================================
    // 3. ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
    // ==========================================
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
        randomRecentOrders,
        isLoadingRandom,

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
        getOrderById,
        ordersByStatus,
        isOrderLoading,

        // Состояние и геттеры (отзывы)
        reviews,
        reviews_paginate_object,
        isLoadingReviews,
        reviewsCount,

        // Методы (заказы)
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

        // Методы (отзывы)
        loadReviews,
        storeReview,
        getReviewsByProductId,
        updateReview: store.updateReview,
        deleteReview: store.deleteReview,
        canReviewOrder: store.canReviewOrder,



        // Сброс стор
        $reset: store.$reset,
    };
}
