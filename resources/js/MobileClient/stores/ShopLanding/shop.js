import { defineStore } from 'pinia';

export const useShopLandingStore = defineStore('shop-landing', {
    state: () => ({
        activeCategoryId: 'all',

        categories: [
            { id: 'all', name: 'Все товары', icon: 'fa-solid fa-grid-2' },
            { id: 'food', name: 'Еда', icon: 'fa-solid fa-burger' },
            { id: 'drinks', name: 'Напитки', icon: 'fa-solid fa-mug-hot' },
            { id: 'desserts', name: 'Десерты', icon: 'fa-solid fa-cake-candles' },
            { id: 'other', name: 'Другое', icon: 'fa-solid fa-box-open' },
        ],

        products: [
            {
                id: 1,
                categoryId: 'food',
                name: 'Пицца Маргарита',
                price: 590,
                oldPrice: 690,
                image: 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=500&q=80',
                badge: 'Хит'
            },
            {
                id: 2,
                categoryId: 'drinks',
                name: 'Капучино',
                price: 250,
                oldPrice: null,
                image: 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=500&q=80',
                badge: null
            },
            {
                id: 3,
                categoryId: 'desserts',
                name: 'Чизкейк Нью-Йорк',
                price: 350,
                oldPrice: null,
                image: 'https://images.unsplash.com/photo-1524351199678-941a58a3df50?w=500&q=80',
                badge: 'Новинка'
            },
            {
                id: 4,
                categoryId: 'food',
                name: 'Бургер Классик',
                price: 450,
                oldPrice: 520,
                image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&q=80',
                badge: null
            },
            {
                id: 5,
                categoryId: 'drinks',
                name: 'Латте с сиропом',
                price: 280,
                oldPrice: null,
                image: 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=500&q=80',
                badge: null
            },
            {
                id: 6,
                categoryId: 'desserts',
                name: 'Тирамису',
                price: 380,
                oldPrice: 450,
                image: 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=500&q=80',
                badge: 'Скидка'
            },
            {
                id: 7,
                categoryId: 'other',
                name: 'Фирменный мерч (Футболка)',
                price: 1200,
                oldPrice: null,
                image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&q=80',
                badge: 'New'
            }
        ]
    }),

    getters: {
        // Получить активную категорию
        activeCategory: (state) => state.categories.find(c => c.id === state.activeCategoryId),

        // Получить товары для активной категории (или все, если 'all')
        filteredProducts: (state) => {
            if (state.activeCategoryId === 'all') {
                return state.products;
            }
            return state.products.filter(p => p.categoryId === state.activeCategoryId);
        },

        // Массив категорий, в которые ВЛОЖЕНЫ продукты (как вы просили)
        categoriesWithProducts: (state) => {
            return state.categories.map(category => {
                const categoryProducts = category.id === 'all'
                    ? state.products
                    : state.products.filter(p => p.categoryId === category.id);

                return {
                    ...category,
                    products: categoryProducts
                };
            });
        },

        // Найти конкретный товар по ID
        getProductById: (state) => (id) => state.products.find(p => p.id === id),
    },

    actions: {
        setActiveCategory(categoryId) {
            this.activeCategoryId = categoryId;
        },

        // Имитация загрузки данных с сервера
        async fetchShopData() {
            // Здесь будет ваш API запрос: const response = await api.get('/shop/data');
            // this.categories = response.data.categories;
            // this.products = response.data.products;
            console.log('Данные магазина загружены');
        },

        // Действия для админки (пример)
        addProduct(product) {
            this.products.push({ ...product, id: Date.now() });
        },

        updateProduct(updatedProduct) {
            const index = this.products.findIndex(p => p.id === updatedProduct.id);
            if (index !== -1) {
                this.products[index] = updatedProduct;
            }
        },

        deleteProduct(productId) {
            this.products = this.products.filter(p => p.id !== productId);
        }
    }
});
