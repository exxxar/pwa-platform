import { storeToRefs } from 'pinia';
import { useProductsStore } from '@/MobileClient/stores/Shop/products.js';

export function useProducts() {
    const store = useProductsStore();

    // ==========================================
    // 1. ЕДИНАЯ ДЕСТРУКТУРИЗАЦИЯ (State + Getters)
    // ==========================================
    const {
        // Состояние (State)
        products,
        categories,
        collections,          // 🆕

        products_paginate_object,
        categories_paginate_object,
        collectionsPaginate,  // 🆕
        isLoading,
        isLoadingMore,        // 🆕
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

        // 🆕 UI состояние
        searchQuery,
        selectedCategory,
        selectedPartner,
        extraCharge,

        // Геттеры (Getters)
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
        filteredCategories,   // 🆕
        totalProductsCount,   // 🆕
    } = storeToRefs(store);

    // ==========================================
    // 2. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
    // ==========================================
    const isProductLoading = (productId) => store.isProductLoading(productId);
    const isCategoryLoading = (categoryId) => store.isCategoryLoading(categoryId);

    // ==========================================
    // 3. МЕТОДЫ
    // ==========================================
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

    // 🆕 Меню и Партнёры
    const loadCollections = (page = 1, partnerId = null) => store.loadCollections(page, partnerId);

    const setPartner = (partner) => store.setPartner(partner);
    const setSearch = (query) => store.setSearch(query);
    const clearMenuData = () => store.clearMenuData();

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
        collections,

        products_paginate_object,
        categories_paginate_object,
        collectionsPaginate,
        isLoading,
        isLoadingMore,
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
        searchQuery,
        selectedCategory,
        selectedPartner,
        extraCharge,

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
        filteredCategories,
        totalProductsCount,
        isProductLoading,
        isCategoryLoading,

        // Методы: Товары
        loadProducts,
        loadRandomProducts,
        loadRecommendedProducts: store.loadRecommendedProducts,
        loadProductsByCategory: store.loadProductsByCategory,
        loadMoreProducts: store.loadMoreProducts, // Исправлено имя для соответствия вызову
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

        // 🆕 Методы: Меню и Партнёры
        loadCollections,

        setPartner,
        setSearch,
        clearMenuData,

        // Методы: Синхронизация и Экспорт
        syncFromVk,
        syncFromFrontPad,
        updateProductsFromFrontPadExcel: store.updateProductsFromFrontPadExcel,
        updateShopLink: store.updateShopLink,
        exportAllProducts: store.exportAllProducts,
        exportAllOrders: store.exportAllOrders,

        // Методы: Прочее
        loadReviewsByProductId: store.loadReviewsByProductId,
        getFavList: store.getFavList,
        toggleProductInFavorites: store.toggleProductInFavorites,
        loadShopModuleData: store.loadShopModuleData,

        // Сброс стора
        $reset: store.$reset,
    };
}
