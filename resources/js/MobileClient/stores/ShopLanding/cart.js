import { defineStore } from 'pinia';

export const useLandingCartStore = defineStore('landing-cart', {
    state: () => ({
        items: [] // [{ id, name, price, quantity, image, ... }]
    }),

    getters: {
        // Общее количество товаров (сумма всех qty)
        totalItems: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),

        // Общая сумма
        totalPrice: (state) => state.items.reduce((sum, item) => sum + (item.price * item.quantity), 0),

        // Получить кол-во конкретного товара
        getItemQuantity: (state) => (productId) => {
            const item = state.items.find(i => i.id === productId);
            return item ? item.quantity : 0;
        }
    },

    actions: {
        addItem(product, qty = 1) {
            const existing = this.items.find(i => i.id === product.id);
            if (existing) {
                existing.quantity += qty;
            } else {
                this.items.push({ ...product, quantity: qty });
            }
        },
        removeItem(productId) {
            this.items = this.items.filter(i => i.id !== productId);
        },
        updateQuantity(productId, delta) {
            const item = this.items.find(i => i.id === productId);
            if (item) {
                item.quantity += delta;
                if (item.quantity <= 0) this.removeItem(productId);
            }
        },
        clearCart() {
            this.items = [];
        }
    }
});
