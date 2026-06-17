// stores/favorites.js
import { defineStore } from 'pinia'
import { apiRequest } from '../utils/api.js'

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        favorites: localStorage.getItem('mypwa_favorites') == null
            ? []
            : JSON.parse(localStorage.getItem('mypwa_favorites')),
    }),

    getters: {
        inFav: (state) => (id) => {
            return state.favorites.some(item => item.id === id)
        },
        getFavorites: (state) => state.favorites,
        favoritesCount: (state) => state.favorites.length || 0,
    },

    actions: {
        setFavoritesItems(favorites) {
            this.favorites = favorites
            localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites))
        },

        pushProductToFav(product) {
            const exists = this.favorites.find(item => item.id === product.id)
            if (!exists) {
                this.favorites.push(product)
                localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites))
            }
        },

        removeProductFromFav(id) {
            this.favorites = this.favorites.filter(item => item.id !== id)
            localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites))
        },

        clearAllFavorites() {
            this.favorites = []
            localStorage.setItem('mypwa_favorites', JSON.stringify(this.favorites))
        },

        async loadActualPriceInFav() {
            try {
                const ids = this.favorites.map(item => item.id)
                const response = await apiRequest('/bot-client/shop/products/load-actual', 'POST', { ids })
                const products = response.data

                // сопоставляем актуальные цены с избранным
                const updated = this.favorites.map(fav =>
                    products.find(sub => sub.id === fav.id)
                ).filter(Boolean)

                this.setFavoritesItems(updated)
            } catch (err) {
                console.error(err.response?.data?.errors || [])
                throw err
            }
        },

        addToFavorites(product) {
            this.pushProductToFav(product)
        },

        removeFromFavorites(id) {
            this.removeProductFromFav(id)
        },

        clearFavorites() {
            this.clearAllFavorites()
        }
    }
})
