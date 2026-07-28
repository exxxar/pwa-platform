import { storeToRefs } from 'pinia';
import { useProductsStore } from '@/MobileClient/stores/Shop/products.js';

/**
 * Composable для работы с товарами и категориями.
 * Оптимизирован: убраны лишние computed-обертки и бесполезные try/catch.
 */
export function useProducts() {
    const store = useProductsStore();

    // ==========================================
    // 1. ЕДИНАЯ ДЕСТРУКТУРИЗАЦИЯ (State + Getters)
    // ==========================================
    const {
        // Состояние (State)
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

        // Геттеры (Getters) - они УЖЕ реактивны, берем их напрямую!
        sortedProducts,
        activeProducts,
        stopListProducts,
        recommendedProducts,
        activeCategories,
        recommendedCategories,
        productsCount,
        categoriesCount,
        getProductById,
        getCategoryById,
    } = storeToRefs(store);

    // ==========================================
    // 2. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
    // ==========================================
    const isProductLoading = (productId) => store.isProductLoading(productId);
    const isCategoryLoading = (categoryId) => store.isCategoryLoading(categoryId);

    // ==========================================
    // 3. МЕТОДЫ (Без избыточного try/catch)
    // ==========================================
    // Мы просто возвращаем вызов метода стора.
    // Обработкой ошибок должен заниматься компонент, вызывающий эти методы.

    // Товары
    const loadProducts = (payload = {}) => store.loadProducts(payload);
    const loadRandomProducts = () => store.loadRandomProducts();
    const saveProduct = (productForm) => store.saveProduct({ productForm });
    const removeProduct = (productId) => store.removeShopProduct(productId);
    const restoreProduct = (productId) => store.restoreProduct(productId);
    const toggleStopList = (productId) => store.addToStopListProduct(productId);
    const toggleRecommendation = (payload) => store.changeProductRecommendationStatus(payload);

    // Категории
    const loadCategories = (payload = {}) => store.loadCategories(payload);
    const saveCategory = (payload) => store.storeProductCategory(payload);
    const removeCategory = (categoryId) => store.removeProductCategory({ category_id: categoryId });
    const toggleCategoryStatus = (categoryId) => store.changeProductCategoryStatus(categoryId);
    const toggleCategoryRecommendation = (payload) => store.changeCategoryRecommendationStatus(payload);

    // Синхронизация
    const syncFromVk = () => store.updateProductsFromVk();
    const syncFromFrontPad = () => store.updateProductsFromFrontPad();

    // ==========================================
    // 4. ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
    // ==========================================
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
        getProductById,
        getCategoryById,
        isProductLoading,
        isCategoryLoading,

        // Методы: Товары
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

        // Методы: Категории
        loadCategories,
        loadCategory: store.loadCategory,
        saveCategory,
        removeCategory,
        toggleCategoryStatus,
        toggleCategoryRecommendation,

        // Методы: Синхронизация и Экспорт
        syncFromVk,
        syncFromFrontPad,
        updateProductsFromFrontPadExcel: store.updateProductsFromFrontPadExcel,
        updateShopLink: store.updateShopLink,
        exportAllProducts: store.exportAllProducts,
        exportAllOrders: store.exportAllOrders,

        // Методы: Прочее (Отзывы, Избранное, Модули)
        loadReviewsByProductId: store.loadReviewsByProductId,
        getFavList: store.getFavList,
        toggleProductInFavorites: store.toggleProductInFavorites,
        loadShopModuleData: store.loadShopModuleData,

        // Сброс стора
        $reset: store.$reset,
    };
}
