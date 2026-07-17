import { defineStore } from 'pinia';
import axios from 'axios';

export const useShopLandingStore = defineStore('shop-landing', {
    state: () => ({
        categories: [], // Иерархия: { id, name, products: [], products_count, ... }
        isLoading: false,
        error: null,
        partnerId: null,
        activeCategoryId: 'all', // Оставляем для совместимости
    }),

    getters: {
        // Плоский список всех товаров (добавляем имя категории для удобства)
        allProducts: (state) => state.categories.flatMap(cat =>
            (cat.products || []).map(p => ({
                ...p,
                categoryName: cat.name,
                categoryId: cat.id
            }))
        ),

        // Есть ли еще товары для загрузки в категории
        hasMoreProducts: (state) => (categoryId) => {
            const cat = state.categories.find(c => c.id === categoryId);
            if (!cat) return false;
            const currentLength = (cat.products || []).length;
            const totalCount = cat.products_count || currentLength;
            return currentLength < totalCount;
        },

        // Автоматический подбор иконки, если бэкенд её не отдает
        getCategoryIcon: () => (category) => {
            const name = category.name?.toLowerCase() || '';
            if (name.includes('еда') || name.includes('пицца') || name.includes('бургер') || name.includes('ролл')) return 'fa-solid fa-burger';
            if (name.includes('напитк') || name.includes('кофе') || name.includes('чай') || name.includes('сок')) return 'fa-solid fa-mug-hot';
            if (name.includes('десерт') || name.includes('торт') || name.includes('морожен')) return 'fa-solid fa-cake-candles';
            if (category.id === -1) return 'fa-solid fa-box-open';
            return category.icon || 'fa-solid fa-utensils';
        }
    },

    actions: {
        async fetchShopData(payloadPartnerId = null) {
            this.isLoading = true;
            this.error = null;
            try {
                const partnerId = payloadPartnerId || this.partnerId;

                // ⚠️ ПРОВЕРЬ ЭТОТ URL! Он должен точно совпадать с твоими routes/api.php
                const response = await axios.post('/shop/products/by-category', {
                    partner_id: partnerId
                });

                // Универсальное получение данных (на случай, если Laravel возвращает { data: { data: [...] } } или просто { data: [...] })
                const rawData = response.data?.data || response.data || [];

                console.log('📦 Сырые данные от бэкенда (категории):', rawData);

                this.categories = rawData.map(cat => ({
                    ...cat,
                    products: cat.products || [], // Гарантируем, что это массив
                    products_count: cat.products_count || (cat.products ? cat.products.length : 0), // Страховка
                    isLoadingMore: false,
                    icon: this.getCategoryIcon(cat)
                }));

                this.partnerId = partnerId;
                console.log('✅ Категории успешно загружены в стор:', this.categories);

            } catch (err) {
                console.error('❌ Ошибка загрузки данных магазина:', err);
                this.error = 'Не удалось загрузить меню. Проверьте консоль.';
            } finally {
                this.isLoading = false;
            }
        },

        async loadMoreProducts(categoryId, offset, payloadPartnerId = null) {
            const category = this.categories.find(c => c.id === categoryId);
            if (!category || category.isLoadingMore) return;

            category.isLoadingMore = true;
            try {
                const partnerId = payloadPartnerId || this.partnerId;

                // ⚠️ ПРОВЕРЬ ЭТОТ URL для пагинации!
                const response = await axios.post('/shop/products/load-more-by-category', {
                    category_id: categoryId,
                    offset: offset,
                    partner_id: partnerId
                });

                const newProducts = response.data || []; // Или response.data.data, зависит от твоего контроллера

                if (newProducts.length > 0) {
                    category.products = [...(category.products || []), ...newProducts];
                }
            } catch (err) {
                console.error(`❌ Ошибка подгрузки для категории ${categoryId}:`, err);
            } finally {
                category.isLoadingMore = false;
            }
        },

        setActiveCategory(id) {
            this.activeCategoryId = id;
        }
    }
});
