// stores/basket.js
import {defineStore} from 'pinia'
import {apiRequest} from '../utils/api.js'

const BASE_BASKET_LINK = '/basket'

export const useBasketStore = defineStore('basket', {
    state: () => ({
        basket_items: [],
        basket_items_paginate_object: null,
    }),

    getters: {
        getProductsInBasket: (state) => state.basket_items || [],
        getBasketPaginateObject: (state) => state.basket_items_paginate_object || null,

        inCollectionCart: (state) => (id, variantId) => {
            return state.basket_items.find(bItem =>
                bItem.collection?.id === id &&
                (bItem.params?.variant_id === variantId || variantId == null)
            )?.count || 0
        },

        inCart: (state) => (id) => {
            return (state.basket_items.find(item => item.product?.id === id))?.count || 0
        },

        cartProducts: (state) => state.basket_items || [],
        cartCollections: (state) => (state.basket_items || []).filter(item => item.collection),

        cartTotalCount: (state) => {
            if (!state.basket_items?.length) return 0
            return state.basket_items.reduce((sum, item) => {
                return sum + (item.product?.is_weight_product ? 1 : item.count)
            }, 0)
        },

        cartTotalPrice: (state) => {
            if (!state.basket_items?.length) return 0
            let sum = 0

            state.basket_items.forEach((item) => {
                if (item.product) {
                    let currentPrice = item.params?.discount_price || item.product.price || 0
                    let count = item.product?.is_weight_product ? 1 : item.count
                    let price = item.product?.is_weight_product
                        ? (currentPrice * item.count) / (item.product.weight_config?.step || 100)
                        : currentPrice
                    sum += price * count
                }
                if (item.collection) {
                    let collectionPrice = 0
                    let selected = item.params.ids || []
                    item.collection.products.forEach((sub) => {
                        if (selected.includes(sub.id)) collectionPrice += sub.price
                    })
                    sum += (collectionPrice - collectionPrice * (item.collection.discount / 100)) * item.count
                }
            })
            return sum
        }
    },

    actions: {
        setBasket(payload) {
            this.basket_items = payload || []

        },

        setBasketPaginateObject(payload) {
            this.basket_items_paginate_object = payload || []
        },

        incrementItemQuantity(id) {
            const cartItem = this.basket_items.find(item => item.product?.id === id || item.collection?.id === id)
            if (cartItem) {
                cartItem.count++
            }
        },

        decrementItemQuantity(id) {
            const cartItem = this.basket_items.find(item => item.product?.id === id || item.collection?.id === id)
            if (cartItem && cartItem.count > 1) {
                cartItem.count--
            }
        },

        async useWheelOfFortunePrize(payload = {form: null}) {
            let link = `${BASE_BASKET_LINK}/use-wheel-of-fortune-prize`
            return apiRequest(link, 'POST', payload.form)
                .then(res => res.data).catch(err => {
                    throw err
                })
        },
        async createCheckoutLink(payload = {deliveryForm: null}) {
            let link = `${BASE_BASKET_LINK}/checkout-link`
            return apiRequest(link, 'POST', payload.deliveryForm)
                .then(res => res.data).catch(err => {
                    throw err
                })
        },
        async requestDeliveryPriceNew(payload) {
            let link = `${BASE_BASKET_LINK}/get-delivery-price-new`
            return apiRequest(link, 'POST', payload)
                .then(res => res.data).catch(err => {
                    throw err
                })
        },
        async addCommentToProduct(payload = {form: null}) {
            let link = `${BASE_BASKET_LINK}/comment-product`
            return apiRequest(link, 'POST', payload.form)
                .then(res => res.data).catch(err => {
                    throw err
                })
        },

        async startCheckout(payload = {deliveryForm: null}) {
            let link = `${BASE_BASKET_LINK}/checkout`
            return apiRequest(link, 'POST', payload.deliveryForm)
                .then(res => {
                    this.setBasket([])
                    this.setBasketPaginateObject(null)
                    return res.data
                }).catch(err => {
                    console.error(err.response?.data?.errors || [])
                    throw err
                })
        },

        async loadProductsInBasket(payload = {dataObject: {search: null, categories: null}, page: 0, size: 12}) {
            let page = payload.page || 0
            let size = 20
            let link = `${BASE_BASKET_LINK}?page=${page}&size=${size}`

            return apiRequest(link, 'POST').then((response) => {
                let dataObject = response.data
                this.setBasket(dataObject.data)
                delete dataObject.data
                this.setBasketPaginateObject(dataObject)
            }).catch(err => {
                console.error(err.response?.data?.errors || [])
                throw err
            })
        },

        async addProductToCart(id) {
            this.incrementItemQuantity(id)

            let link = `${BASE_BASKET_LINK}/inc-product`
            return apiRequest(link, 'POST', {product_id: id})
                .then((response) => {
                    let dataObject = response.data
                    this.setBasket(dataObject.data)
                    delete dataObject.data
                    this.setBasketPaginateObject(dataObject)
                }).catch(err => {
                    console.error(err.response?.data?.errors || [])
                    throw err
                })
        },


        async removeProductFromCart(id) {
            this.decrementItemQuantity(id)

            let link = `${BASE_BASKET_LINK}/dec-product`
            return apiRequest(link, 'POST', {product_id: id})
                .then((response) => {
                    let dataObject = response.data
                    this.setBasket(dataObject.data)
                    delete dataObject.data
                    this.setBasketPaginateObject(dataObject)
                }).catch(err => {
                    console.error(err.response?.data?.errors || [])
                    throw err
                })
        },


        async clearCart() {
            let link = `${BASE_BASKET_LINK}/clear`
            this.setBasket([])
            this.setBasketPaginateObject(null)
            return apiRequest(link, 'DELETE')
                .then(() => {
                }).catch(err => {
                    console.error(err.response?.data?.errors || [])
                    throw err
                })
        }
    }
})
