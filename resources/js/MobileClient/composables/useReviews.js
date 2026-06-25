import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useReviewsStore } from '@/stores/reviews.js';

/**
 * Composable для работы с отзывами
 */
export function useReviews() {
    const store = useReviewsStore();

    // Реактивные ссылки на состояние
    const {
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
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedReviews = computed(() => store.sortedReviews);
    const positiveReviews = computed(() => store.positiveReviews);
    const neutralReviews = computed(() => store.neutralReviews);
    const negativeReviews = computed(() => store.negativeReviews);
    const recentReviews = computed(() => store.recentReviews);
    const averageRating = computed(() => store.averageRating);
    const ratingDistribution = computed(() => store.ratingDistribution);
    const reviewsCount = computed(() => store.reviewsCount);
    const positiveReviewsCount = computed(() => store.positiveReviewsCount);
    const positiveReviewsPercent = computed(() => store.positiveReviewsPercent);

    /**
     * Проверка, загружается ли отзыв
     */
    const isReviewLoading = (reviewId) => {
        return store.isReviewLoading(reviewId);
    };

    // ==========================================
    // Безопасные методы
    // ==========================================

    const loadReviews = async (payload = {}) => {
        try {
            return await store.loadReviews(payload);
        } catch (error) {
            console.error('Ошибка загрузки отзывов:', error);
            throw error;
        }
    };

    const loadReviewsByProduct = async (productId, payload = {}) => {
        try {
            return await store.loadReviewsByProductId({
                dataObject: { product_id: productId },
                ...payload,
            });
        } catch (error) {
            console.error('Ошибка загрузки отзывов товара:', error);
            throw error;
        }
    };

    const createReview = async (reviewForm) => {
        try {
            return await store.storeReview({ reviewForm });
        } catch (error) {
            console.error('Ошибка создания отзыва:', error);
            throw error;
        }
    };

    const notifyUser = async (reviewForm) => {
        try {
            return await store.notifyUserForReview({ reviewForm });
        } catch (error) {
            console.error('Ошибка уведомления пользователя:', error);
            throw error;
        }
    };

    const removeReview = async (reviewId) => {
        try {
            return await store.removeReview(reviewId);
        } catch (error) {
            console.error('Ошибка удаления отзыва:', error);
            throw error;
        }
    };

    /**
     * Получить средний рейтинг товара
     */
    const getProductAverageRating = (productId) => {
        return store.averageRatingByProduct(productId);
    };

    /**
     * Получить отзывы товара
     */
    const getProductReviews = (productId) => {
        return store.reviewsByProductId(productId);
    };

    return {
        // Состояние
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

        // Геттеры
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
        getReviewById: store.getReviewById,
        reviewsByRating: store.reviewsByRating,
        reviewsByProductId: store.reviewsByProductId,
        averageRatingByProduct: store.averageRatingByProduct,
        isReviewLoading,

        // Методы
        loadReviews,
        loadReviewsByProduct,
        createReview,
        notifyUser,
        removeReview,
        getProductAverageRating,
        getProductReviews,

        // Сброс
        $reset: store.$reset,
    };
}
