import { storeToRefs } from 'pinia';
import { useProductsStore } from '@/MobileClient/stores/Shop/products.js';

export function useProducts() {
    const store = useProductsStore();

    const {
        // Состояние (State)
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

        // 🆕 ДОБАВЛЯЕМ ЭТО:
        filteredProducts,
        totalProductsCount,
    } = storeToRefs(store);

    // ... (вспомогательные функции и методы остаются без изменений) ...

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

        // 🆕 ДОБАВЛЯЕМ ЭТО В RETURN:
        filteredProducts,
        totalProductsCount,

        setRecommendations: (products) => store.setRecommendations(products),
        setTotalCount: (count) => store.setTotalCount(count),

        isProductLoading: (id) => store.isProductLoading(id),
        isCategoryLoading: (id) => store.isCategoryLoading(id),
        fetchProductsByIds: (ids) => store.fetchProductsByIds(ids),

        // Методы (остаются без изменений)
        loadProducts: (payload = {}) => store.loadProducts(payload),
        loadRandomProducts: () => store.loadRandomProducts(),
        saveProduct: (productForm) => store.saveProduct({ productForm }),
        removeProduct: (productId) => store.removeShopProduct(productId),
        restoreProduct: (productId) => store.restoreProduct(productId),
        toggleStopList: (productId) => store.addToStopListProduct(productId),
        toggleRecommendation: (payload) => store.changeProductRecommendationStatus(payload),
        loadCategories: (payload = {}) => store.loadCategories(payload),
        saveCategory: (payload) => store.storeProductCategory(payload),
        removeCategory: (categoryId) => store.removeProductCategory({ category_id: categoryId }),
        toggleCategoryStatus: (categoryId) => store.changeProductCategoryStatus(categoryId),
        toggleCategoryRecommendation: (payload) => store.changeCategoryRecommendationStatus(payload),
        loadCollections: (page = 1, partnerId = null) => store.loadCollections(page, partnerId),
        setPartner: (partner) => store.setPartner(partner),
        setSearch: (query) => store.setSearch(query),
        clearMenuData: () => store.clearMenuData(),
        syncFromVk: () => store.updateProductsFromVk(),
        syncFromFrontPad: () => store.updateProductsFromFrontPad(),
        loadRecommendedProducts: store.loadRecommendedProducts,
        loadProductsByCategory: store.loadProductsByCategory,
        loadMoreProducts: (categoryId, partnerId = null) => store.loadMoreProducts(categoryId, partnerId),
        loadProductsInCategory: store.loadProductsInCategory,
        loadProduct: store.loadProduct,
        removeAllProducts: store.removeAllProducts,
        loadCategory: store.loadCategory,
        updateProductsFromFrontPadExcel: store.updateProductsFromFrontPadExcel,
        updateShopLink: store.updateShopLink,
        exportAllProducts: store.exportAllProducts,
        exportAllOrders: store.exportAllOrders,
        loadReviewsByProductId: store.loadReviewsByProductId,
        getFavList: store.getFavList,
        toggleProductInFavorites: store.toggleProductInFavorites,
        loadShopModuleData: store.loadShopModuleData,
        $reset: store.$reset,
    };
}
