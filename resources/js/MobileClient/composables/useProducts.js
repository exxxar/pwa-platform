import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useProductsStore } from '@/MobileClient/stores/Shop/products.js';

/**
 * Composable для работы с товарами и категориями
 */
export function useProducts() {
    const store = useProductsStore();

    // Реактивные ссылки на состояние
    const {
        products,
        categories,
        products_paginate_object,
        categories_paginate_object,
        isLoading,
        isHydrated,
        isCategoriesLoading,
        isCategoriesHydrated,
        isRandomLoading,
        isRecommendedLoading,
        isByCategoryLoading,
        isInCategoryLoading,
        isModuleDataLoading,
        productActions,
        categoryActions,
        lastError,
        errors,
        lastSyncAt,
    } = storeToRefs(store);

    // Реактивные геттеры
    const sortedProducts = computed(() => store.sortedProducts);
    const activeProducts = computed(() => store.activeProducts);
    const stopListProducts = computed(() => store.stopListProducts);
    const recommendedProducts = computed(() => store.recommendedProducts);
    const activeCategories = computed(() => store.activeCategories);
    const recommendedCategories = computed(() => store.recommendedCategories);
    const productsCount = computed(() => store.productsCount);
    const categoriesCount = computed(() => store.categoriesCount);

    /**
     * Проверка, загружается ли товар
     */
    const isProductLoading = (productId) => {
        return store.isProductLoading(productId);
    };

    /**
     * Проверка, загружается ли категория
     */
    const isCategoryLoading = (categoryId) => {
        return store.isCategoryLoading(categoryId);
    };

    // ==========================================
    // Безопасные методы для товаров
    // ==========================================

    const loadProducts = async (payload = {}) => {
        try {
            return await store.loadProducts(payload);
        } catch (error) {
            console.error('Ошибка загрузки товаров:', error);
            throw error;
        }
    };

    const loadRandomProducts = async () => {
        try {
            return await store.loadRandomProducts();
        } catch (error) {
            console.error('Ошибка загрузки случайных товаров:', error);
            throw error;
        }
    };

    const saveProduct = async (productForm) => {
        try {
            return await store.saveProduct({ productForm });
        } catch (error) {
            console.error('Ошибка создания товара:', error);
            throw error;
        }
    };

    const removeProduct = async (productId) => {
        try {
            return await store.removeShopProduct(productId);
        } catch (error) {
            console.error('Ошибка удаления товара:', error);
            throw error;
        }
    };

    const restoreProduct = async (productId) => {
        try {
            return await store.restoreProduct(productId);
        } catch (error) {
            console.error('Ошибка восстановления товара:', error);
            throw error;
        }
    };

    const toggleStopList = async (productId) => {
        try {
            return await store.addToStopListProduct(productId);
        } catch (error) {
            console.error('Ошибка переключения стоп-листа:', error);
            throw error;
        }
    };

    const toggleRecommendation = async (payload) => {
        try {
            return await store.changeProductRecommendationStatus(payload);
        } catch (error) {
            console.error('Ошибка переключения рекомендации:', error);
            throw error;
        }
    };

    // ==========================================
    // Безопасные методы для категорий
    // ==========================================

    const loadCategories = async (payload = {}) => {
        try {
            return await store.loadCategories(payload);
        } catch (error) {
            console.error('Ошибка загрузки категорий:', error);
            throw error;
        }
    };

    const saveCategory = async (payload) => {
        try {
            return await store.storeProductCategory(payload);
        } catch (error) {
            console.error('Ошибка создания категории:', error);
            throw error;
        }
    };

    const removeCategory = async (categoryId) => {
        try {
            return await store.removeProductCategory({ category_id: categoryId });
        } catch (error) {
            console.error('Ошибка удаления категории:', error);
            throw error;
        }
    };

    const toggleCategoryStatus = async (categoryId) => {
        try {
            return await store.changeProductCategoryStatus(categoryId);
        } catch (error) {
            console.error('Ошибка переключения статуса категории:', error);
            throw error;
        }
    };

    const toggleCategoryRecommendation = async (payload) => {
        try {
            return await store.changeCategoryRecommendationStatus(payload);
        } catch (error) {
            console.error('Ошибка рекомендации категории:', error);
            throw error;
        }
    };

    // ==========================================
    // Синхронизация
    // ==========================================

    const syncFromVk = async () => {
        try {
            return await store.updateProductsFromVk();
        } catch (error) {
            console.error('Ошибка синхронизации с ВК:', error);
            throw error;
        }
    };

    const syncFromFrontPad = async () => {
        try {
            return await store.updateProductsFromFrontPad();
        } catch (error) {
            console.error('Ошибка синхронизации с FrontPad:', error);
            throw error;
        }
    };

    return {
        // Состояние
        products,
        categories,
        products_paginate_object,
        categories_paginate_object,
        isLoading,
        isHydrated,
        isCategoriesLoading,
        isCategoriesHydrated,
        isRandomLoading,
        isRecommendedLoading,
        isByCategoryLoading,
        isInCategoryLoading,
        isModuleDataLoading,
        productActions,
        categoryActions,
        lastError,
        errors,
        lastSyncAt,

        // Геттеры
        sortedProducts,
        activeProducts,
        stopListProducts,
        recommendedProducts,
        activeCategories,
        recommendedCategories,
        productsCount,
        categoriesCount,
        getProductById: store.getProductById,
        getCategoryById: store.getCategoryById,
        isProductLoading,
        isCategoryLoading,

        // Товары
        loadProducts,
        loadRandomProducts,
        loadRecommendedProducts: store.loadRecommendedProducts,
        loadProductsByCategory: store.loadProductsByCategory,
        loadMoreProductsByCategory: store.loadMoreProductsByCategory,
        loadProductsInCategory: store.loadProductsInCategory,
        loadProduct: store.loadProduct,
        saveProduct,
        removeProduct,
        restoreProduct,
        toggleStopList,
        toggleRecommendation,
        removeAllProducts: store.removeAllProducts,

        // Категории
        loadCategories,
        loadCategory: store.loadCategory,
        saveCategory,
        removeCategory,
        toggleCategoryStatus,
        toggleCategoryRecommendation,

        // Синхронизация
        syncFromVk,
        syncFromFrontPad,
        updateProductsFromFrontPadExcel: store.updateProductsFromFrontPadExcel,
        updateShopLink: store.updateShopLink,

        // Экспорт
        exportAllProducts: store.exportAllProducts,
        exportAllOrders: store.exportAllOrders,

        // Отзывы
        loadReviewsByProductId: store.loadReviewsByProductId,

        // Избранное (дублируется из favorites store)
        getFavList: store.getFavList,
        toggleProductInFavorites: store.toggleProductInFavorites,

        // Модуль
        loadShopModuleData: store.loadShopModuleData,

        // Сброс
        $reset: store.$reset,
    };
}
