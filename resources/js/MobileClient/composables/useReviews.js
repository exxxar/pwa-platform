import { storeToRefs } from 'pinia';
import { useReviewsStore } from '@/MobileClient/stores/Shop/reviews.js';

/**
 * Composable для работы с отзывами
 */
export function useReviews() {
    const store = useReviewsStore();

    // ✅ ИСПРАВЛЕНИЕ 1: storeToRefs идеально работает и с состоянием, и с геттерами Pinia.
    // Это убирает необходимость в ручных computed() и гарантирует 100% реактивность.
    const {
        // --- Состояние (State) ---
        reviews,
        reviews_paginate_object,
        isLoading,
        isHydrated,
        isProductReviewsLoading,
        isNotifying,
        isStoring,
        lastError,
        errors,
        lastSyncAt,
        // ⚠️ Убедитесь, что reviewActions — это реактивное состояние (объект данных),
        // а не набор функций. Если это функции, уберите его отсюда и добавьте в return ниже.
        reviewActions,

        // --- Геттеры (Getters) ---
        // Pinia автоматически сделает их реактивными ref-ссылками
        sortedReviews,
        positiveReviews,
        neutralReviews,
        negativeReviews,
        recentReviews,
        averageRating,
        ratingDistribution,
        reviewsCount,
        positiveReviewsCount,
        positiveReviewsPercent,
    } = storeToRefs(store);

    // ==========================================
    // Методы, принимающие аргументы
    // ==========================================
    // ✅ ИСПРАВЛЕНИЕ 2: Возвращаем геттеры/методы с аргументами напрямую.
    // Если в сторе они объявлены как геттеры, возвращающие функции:
    // `getReviewById: (state) => (id) => state.reviews.find(r => r.id === id)`
    // То такой вызов будет корректно отслеживаться Vue при использовании в шаблоне.

    const isReviewLoading = (reviewId) => store.isReviewLoading(reviewId);
    const getReviewById = (id) => store.getReviewById(id);
    const reviewsByRating = (rating) => store.reviewsByRating(rating);
    const reviewsByProductId = (productId) => store.reviewsByProductId(productId);
    const averageRatingByProduct = (productId) => store.averageRatingByProduct(productId);

    // ==========================================
    // Безопасные методы (Actions) с обработкой ошибок
    // ==========================================

    const loadReviews = async (payload = {}) => {
        try {
            return await store.loadReviews(payload);
        } catch (error) {
            console.error('[useReviews] Ошибка загрузки отзывов:', error);
            throw error; // Пробрасываем ошибку дальше, чтобы компонент мог её обработать (показать notify)
        }
    };

    const loadReviewsByProduct = async (productId, payload = {}) => {
        try {
            return await store.loadReviewsByProductId({
                dataObject: { product_id: productId },
                ...payload,
            });
        } catch (error) {
            console.error(`[useReviews] Ошибка загрузки отзывов товара ${productId}:`, error);
            throw error;
        }
    };

    const createReview = async (reviewForm) => {
        try {
            return await store.storeReview({ reviewForm });
        } catch (error) {
            console.error('[useReviews] Ошибка создания отзыва:', error);
            throw error;
        }
    };

    const notifyUser = async (reviewForm) => {
        try {
            return await store.notifyUserForReview({ reviewForm });
        } catch (error) {
            console.error('[useReviews] Ошибка уведомления пользователя:', error);
            throw error;
        }
    };

    const removeReview = async (reviewId) => {
        try {
            return await store.removeReview(reviewId);
        } catch (error) {
            console.error(`[useReviews] Ошибка удаления отзыва ${reviewId}:`, error);
            throw error;
        }
    };

    // ==========================================
    // Возврат API композабла
    // ==========================================
    return {
        // Состояние (Refs)
        reviews,
        reviews_paginate_object,
        isLoading,
        isHydrated,
        isProductReviewsLoading,
        isNotifying,
        isStoring,
        reviewActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры (Refs)
        sortedReviews,
        positiveReviews,
        neutralReviews,
        negativeReviews,
        recentReviews,
        averageRating,
        ratingDistribution,
        reviewsCount,
        positiveReviewsCount,
        positiveReviewsPercent,

        // Методы с аргументами
        isReviewLoading,
        getReviewById,
        reviewsByRating,
        reviewsByProductId,
        averageRatingByProduct,

        // Экшены (Actions)
        loadReviews,
        loadReviewsByProduct,
        createReview,
        notifyUser,
        removeReview,

        // Утилита для сброса стора (если требуется)
        $reset: store.$reset,
    };
}
