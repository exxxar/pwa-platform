import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/shop/reviews';

export const useReviewsStore = defineStore('reviews', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        reviews: [],
        reviews_paginate_object: null,

        // Состояние загрузки по секциям
        isLoading: false,
        isHydrated: false,
        isProductReviewsLoading: false,
        isNotifying: false,
        isStoring: false,

        // Действия над отзывами: { [reviewId]: 'delete' | 'update' }
        reviewActions: {},

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
         * Все отзывы
         */
        getReviews: (state) => state.reviews || [],

        /**
         * Пагинация
         */
        getReviewsPaginateObject: (state) => state.reviews_paginate_object || null,

        /**
         * Найти отзыв по ID
         */
        getReviewById: (state) => (id) => {
            return (state.reviews || []).find(r => String(r.id) === String(id)) || null;
        },

        /**
         * Отсортированные отзывы (новые сверху)
         */
        sortedReviews: (state) => {
            return [...(state.reviews || [])].sort((a, b) => {
                const dateA = new Date(a.created_at || 0);
                const dateB = new Date(b.created_at || 0);
                return dateB - dateA;
            });
        },

        /**
         * Отзывы по рейтингу
         */
        reviewsByRating: (state) => (rating) => {
            return (state.reviews || []).filter(r => r.rating === rating);
        },

        /**
         * Положительные отзывы (4-5 звёзд)
         */
        positiveReviews: (state) => {
            return (state.reviews || []).filter(r => r.rating >= 4);
        },

        /**
         * Нейтральные отзывы (3 звезды)
         */
        neutralReviews: (state) => {
            return (state.reviews || []).filter(r => r.rating === 3);
        },

        /**
         * Отрицательные отзывы (1-2 звезды)
         */
        negativeReviews: (state) => {
            return (state.reviews || []).filter(r => r.rating <= 2);
        },

        /**
         * Отзывы по товару
         */
        reviewsByProductId: (state) => (productId) => {
            return (state.reviews || []).filter(
                r => String(r.product_id) === String(productId)
            );
        },

        /**
         * Средний рейтинг (по всем отзывам)
         */
        averageRating: (state) => {
            const reviews = state.reviews || [];
            if (reviews.length === 0) return 0;
            const sum = reviews.reduce((acc, r) => acc + (r.rating || 0), 0);
            return Math.round((sum / reviews.length) * 10) / 10;
        },

        /**
         * Средний рейтинг по товару
         */
        averageRatingByProduct: (state) => (productId) => {
            const reviews = (state.reviews || []).filter(
                r => String(r.product_id) === String(productId)
            );
            if (reviews.length === 0) return 0;
            const sum = reviews.reduce((acc, r) => acc + (r.rating || 0), 0);
            return Math.round((sum / reviews.length) * 10) / 10;
        },

        /**
         * Распределение по звёздам (для гистограммы)
         * Возвращает объект: { 1: count, 2: count, 3: count, 4: count, 5: count }
         */
        ratingDistribution: (state) => {
            const distribution = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
            (state.reviews || []).forEach(r => {
                const rating = Math.min(5, Math.max(1, r.rating || 0));
                distribution[rating]++;
            });
            return distribution;
        },

        /**
         * Недавние отзывы (за последние 7 дней)
         */
        recentReviews: (state) => {
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);

            return (state.reviews || []).filter(r => {
                const date = new Date(r.created_at || 0);
                return date >= weekAgo;
            });
        },

        /**
         * Проверка, загружается ли отзыв
         */
        isReviewLoading: (state) => (id) => {
            return !!state.reviewActions[String(id)];
        },

        /**
         * Количество отзывов
         */
        reviewsCount: (state) => state.reviews?.length || 0,

        /**
         * Количество положительных отзывов
         */
        positiveReviewsCount: (state) => {
            return (state.reviews || []).filter(r => r.rating >= 4).length;
        },

        /**
         * Процент положительных отзывов
         */
        positiveReviewsPercent: (state) => {
            const total = state.reviews?.length || 0;
            if (total === 0) return 0;
            const positive = (state.reviews || []).filter(r => r.rating >= 4).length;
            return Math.round((positive / total) * 100);
        },
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        // ==========================================
        // ЗАГРУЗКА
        // ==========================================

        /**
         * Загрузка списка отзывов
         */
        async loadReviews(payload = { dataObject: null, page: 0, size: 20 }) {
            this.isLoading = true;
            this.lastError = null;
            this.errors = [];

            try {
                const page = payload.page || 0;
                const size = payload.size || 20;
                const link = `${BASE}?page=${page}&size=${size}`;

                const response = await axios.post(link, payload.dataObject || {});
                const dataObject = response.data;

                this.reviews = dataObject.data || [];
                const { data, ...paginate } = dataObject;
                this.reviews_paginate_object = paginate;

                this.isHydrated = true;
                this.lastSyncAt = new Date();

                // Сохраняем в localStorage
                localStorage.setItem('cashman_reviews', JSON.stringify(this.reviews));
                localStorage.setItem('cashman_reviews_paginate_object', JSON.stringify(paginate));

                return paginate;
            } catch (err) {
                console.error('[Reviews Store] Ошибка загрузки отзывов:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить отзывы';
                this.errors = err.response?.data?.errors || [];

                // Fallback
                const cached = localStorage.getItem('cashman_reviews');
                if (cached && this.reviews.length === 0) {
                    try {
                        this.reviews = JSON.parse(cached);
                    } catch {
                        this.reviews = [];
                    }
                }

                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Загрузка отзывов по товару
         */
        async loadReviewsByProductId(payload = {
            dataObject: { product_id: null },
            page: 0,
            size: 20,
        }) {
            if (!payload.dataObject?.product_id) {
                throw new Error('Не указан ID товара');
            }

            this.isProductReviewsLoading = true;

            try {
                const page = payload.page || 0;
                const size = payload.size || 20;
                const link = `${BASE}/by-product-id?page=${page}&size=${size}`;

                const response = await axios.post(link, payload.dataObject);
                const dataObject = response.data;

                this.reviews = dataObject.data || [];
                const { data, ...paginate } = dataObject;
                this.reviews_paginate_object = paginate;

                return paginate;
            } catch (err) {
                console.error('[Reviews Store] Ошибка загрузки отзывов товара:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить отзывы';
                throw err;
            } finally {
                this.isProductReviewsLoading = false;
            }
        },

        // ==========================================
        // СОЗДАНИЕ / УВЕДОМЛЕНИЕ
        // ==========================================

        /**
         * Создание отзыва (оптимистично)
         */
        async storeReview(payload = { reviewForm: null }) {
            this.isStoring = true;
            this.lastError = null;

            // Временный отзыв для оптимистичного UI
            const tempReview = {
                id: `temp-${Date.now()}`,
                ...payload.reviewForm,
                created_at: new Date().toISOString(),
                status: 'pending',
                temp: true,
            };

            // Оптимистично добавляем
            this.reviews.unshift(tempReview);

            try {
                const response = await axios.post(`${BASE}/store-review`, payload.reviewForm);
                const savedReview = response.data?.data || response.data;

                // Заменяем временный отзыв на реальный
                const index = this.reviews.findIndex(r => r.id === tempReview.id);
                if (index !== -1 && savedReview) {
                    this.reviews[index] = { ...savedReview, status: 'approved' };
                }

                // Сохраняем в localStorage
                localStorage.setItem('cashman_reviews', JSON.stringify(this.reviews));

                return savedReview;
            } catch (err) {
                // Помечаем как ошибочный
                const index = this.reviews.findIndex(r => r.id === tempReview.id);
                if (index !== -1) {
                    this.reviews[index].status = 'error';
                }
                console.error('[Reviews Store] Ошибка создания отзыва:', err);
                this.lastError = err.response?.data?.message || 'Не удалось создать отзыв';
                throw err;
            } finally {
                this.isStoring = false;
            }
        },

        /**
         * Уведомление пользователя о необходимости оставить отзыв
         */
        async notifyUserForReview(payload = { reviewForm: null }) {
            this.isNotifying = true;
            this.lastError = null;

            try {
                const response = await axios.post(
                    `${BASE}/notify-user`,
                    payload.reviewForm
                );
                return response.data;
            } catch (err) {
                console.error('[Reviews Store] Ошибка уведомления:', err);
                this.lastError = err.response?.data?.message || 'Не удалось отправить уведомление';
                throw err;
            } finally {
                this.isNotifying = false;
            }
        },

        /**
         * Удаление отзыва (оптимистично)
         */
        async removeReview(reviewId) {
            if (!reviewId) throw new Error('Не указан ID отзыва');

            this.reviewActions[String(reviewId)] = 'delete';

            // Сохраняем для отката
            const removedIndex = this.reviews.findIndex(r => String(r.id) === String(reviewId));
            const removedReview = removedIndex !== -1 ? this.reviews[removedIndex] : null;

            if (removedIndex !== -1) {
                this.reviews.splice(removedIndex, 1);
                localStorage.setItem('cashman_reviews', JSON.stringify(this.reviews));
            }

            try {
                const response = await axios.delete(`${BASE}/${reviewId}`);

                localStorage.setItem('cashman_reviews', JSON.stringify(this.reviews));

                return response.data;
            } catch (err) {
                // Откат
                if (removedReview && removedIndex !== -1) {
                    this.reviews.splice(removedIndex, 0, removedReview);
                    localStorage.setItem('cashman_reviews', JSON.stringify(this.reviews));
                }
                console.error('[Reviews Store] Ошибка удаления отзыва:', err);
                this.lastError = err.response?.data?.message || 'Не удалось удалить отзыв';
                throw err;
            } finally {
                delete this.reviewActions[String(reviewId)];
            }
        },

        // ==========================================
        // СБРОС
        // ==========================================

        $reset() {
            this.reviews = [];
            this.reviews_paginate_object = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.isProductReviewsLoading = false;
            this.isNotifying = false;
            this.isStoring = false;
            this.reviewActions = {};
            this.lastError = null;
            this.errors = [];
            this.lastSyncAt = null;

            localStorage.removeItem('cashman_reviews');
            localStorage.removeItem('cashman_reviews_paginate_object');
        },
    },
});
