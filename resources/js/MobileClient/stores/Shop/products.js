// stores/products.js
import { defineStore } from 'pinia'
import { apiRequest } from '../utils/api.js'

const BASE_PRODUCTS_LINK = '/shop/products'

export const useProductsStore = defineStore('products', {
    state: () => ({
        products: [],
        categories: [],
        products_paginate_object: null,
        categories_paginate_object: null,
    }),

    getters: {
        getProducts: (state) => state.products || [],
        getCategories: (state) => state.categories || [],
        getProductById: (state) => (id) => state.products.find(item => item.id === id),
        getProductsPaginateObject: (state) => state.products_paginate_object || null,
        getCategoriesPaginateObject: (state) => state.categories_paginate_object || null,
    },

    actions: {
        setProducts(payload) { this.products = payload || [] },
        setCategories(payload) { this.categories = payload || [] },
        setCategoriesPaginateObject(payload) { this.categories_paginate_object = payload || null },
        setProductsPaginateObject(payload) { this.products_paginate_object = payload || null },

        // ====== Товары ======
        async loadProducts(payload) {
            const page = payload.page || 0
            const size = payload.size || 20
            const link = `${BASE_PRODUCTS_LINK}?page=${page}&size=${size}`
            const response = await apiRequest(link, 'POST', payload.dataObject)
            const dataObject = response.data
            this.setProducts(dataObject.data)
            const { data, ...paginate } = dataObject
            this.setProductsPaginateObject(paginate)
            return paginate
        },

        async loadProduct(payload) {
            const link = `${BASE_PRODUCTS_LINK}/${payload.dataObject.productId}`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async saveProduct(payload) {
            const link = `${BASE_PRODUCTS_LINK}/add-product`
            const res = await apiRequest(link, 'POST', payload.productForm)
            return res.data
        },

        async removeShopProduct(id) {
            const link = `${BASE_PRODUCTS_LINK}/${id}`
            const res = await apiRequest(link, 'DELETE')
            return res.data
        },

        async restoreProduct(id) {
            const link = `${BASE_PRODUCTS_LINK}/restore-product/${id}`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async addToStopListProduct(id) {
            const link = `${BASE_PRODUCTS_LINK}/stop-list-product/${id}`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async changeProductRecommendationStatus(payload) {
            const link = `${BASE_PRODUCTS_LINK}/change-recommendation-status`
            const res = await apiRequest(link, 'POST', payload)
            return res.data
        },

        async loadRecommendedProducts() {
            const link = `${BASE_PRODUCTS_LINK}/load-recommended-products`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async loadRandomProducts() {
            const link = `${BASE_PRODUCTS_LINK}/random`
            const res = await apiRequest(link, 'POST', {})
            const dataObject = res.data
            this.setProducts(dataObject.data)
            const { data, ...paginate } = dataObject
            this.setProductsPaginateObject(paginate)
            return paginate
        },

        async loadProductsByCategory(payload) {
            const link = `${BASE_PRODUCTS_LINK}/by-category`
            const res = await apiRequest(link, 'POST', { partner_id: payload.partner_id })
            return res.data
        },

        async loadMoreProductsByCategory(payload) {
            const link = `${BASE_PRODUCTS_LINK}/more-by-category`
            const res = await apiRequest(link, 'POST', payload)
            return res.data
        },

        async loadProductsInCategory(payload) {
            const tgData = window.Telegram?.WebApp?.initData || null
            const botDomain = window.currentBot?.bot_domain || null
            const slugId = window.currentScript || null
            payload = { tgData, slug_id: slugId, botDomain, ...payload.dataObject }
            const page = payload.page || 0
            const size = 12
            const link = `${BASE_PRODUCTS_LINK}/in-category?page=${page}&size=${size}`
            const res = await apiRequest(link, 'POST', data)
            const dataObject = res.data
            this.setProducts(dataObject.data)
            const { data, ...paginate } = dataObject
            this.setProductsPaginateObject(paginate)
            return paginate
        },

        // ====== Экспорт / Импорт ======
        async exportAllProducts() {
            const link = `${BASE_PRODUCTS_LINK}/export-all-products`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async exportAllOrders() {
            const link = `${BASE_PRODUCTS_LINK}/export-all-orders`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async removeAllProducts() {
            const link = `${BASE_PRODUCTS_LINK}/remove-all-products`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        // ====== Синхронизация с ВК ======
        async updateProductsFromVk() {
            const link = `${BASE_PRODUCTS_LINK}/update-from-vk`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async updateShopLink(payload) {
            const link = `${BASE_PRODUCTS_LINK}/update-shop-link`
            const res = await apiRequest(link, 'POST', payload.botForm)
            return res.data
        },

        // ====== Синхронизация с FrontPad ======
        async updateProductsFromFrontPad() {
            const link = `${BASE_PRODUCTS_LINK}/update-from-frontpad`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async updateProductsFromFrontPadExcel(payload) {
            const link = `${BASE_PRODUCTS_LINK}/update-from-frontpad-excel`
            const res = await apiRequest(link, 'POST', payload.form, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            return res.data
        },

        // ====== Отзывы ======
        async loadReviewsByProductId(payload) {
            const link = `${BASE_PRODUCTS_LINK}/${payload.dataObject.product_id}/reviews?page=${payload.page || 0}&size=${payload.size || 30}`
            const res = await apiRequest(link, 'GET')
            return res.data
        },

        // ====== Избранное ======
        async getFavList() {
            const link = `${BASE_PRODUCTS_LINK}/fav-list`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async toggleProductInFavorites(payload) {
            const link = `${BASE_PRODUCTS_LINK}/toggle-favorite`
            const res = await apiRequest(link, 'POST', payload.form)
            return res.data
        },

        // ====== Модуль ======
        async loadShopModuleData() {
            const link = `${BASE_PRODUCTS_LINK}/load-data`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        // ====== Категории ======
        async loadCategories(payload) {
            const page = payload.page || 0
            const size = payload.size || 5
            const link = `${BASE_PRODUCTS_LINK}/categories?page=${page}&size=${size}`
            const res = await apiRequest(link, 'POST', payload.dataObject)
            const dataObject = res.data
            this.setCategories(dataObject.data)
            const { data, ...paginate } = dataObject
            this.setCategoriesPaginateObject(paginate)
            return paginate
        },

        async loadCategory(payload) {
            const link = `${BASE_PRODUCTS_LINK}/category/${payload.dataObject.categoryId}`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async storeProductCategory(payload) {
            const link = `${BASE_PRODUCTS_LINK}/store-category`
            const res = await apiRequest(link, 'POST', payload)
            return res.data
        },

        async addProductCategory(payload) {
            const link = `${BASE_PRODUCTS_LINK}/add-category`
            const res = await apiRequest(link, 'POST', payload)
            return res.data
        },

        async removeProductCategory(payload) {
            const link = `${BASE_PRODUCTS_LINK}/remove-category/${payload.category_id}`
            const res = await apiRequest(link, 'DELETE')
            return res.data
        },

        async changeProductCategoryStatus(payload) {
            const link = `${BASE_PRODUCTS_LINK}/categories/status/${payload}`
            const res = await apiRequest(link, 'POST')
            return res.data
        },

        async changeCategoryRecommendationStatus(payload) {
            const link = `${BASE_PRODUCTS_LINK}/categories/recommendation-status`
            const res = await apiRequest(link, 'POST', payload)
            return res.data
        },
    },
})
