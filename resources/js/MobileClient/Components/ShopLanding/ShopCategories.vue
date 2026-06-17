<template>
    <section class="shop-categories" ref="section">
        <div class="container">
            <div class="categories-grid">
                <button
                    v-for="cat in shopStore.categories"
                    :key="cat.id"
                    class="category-btn"
                    :class="{ 'active': shopStore.activeCategoryId === cat.id }"
                    @click="selectCategory(cat.id)"
                >
                    <i :class="cat.icon"></i>
                    <span>{{ cat.name }}</span>
                </button>
            </div>
        </div>
    </section>
</template>

<script>

import { useShopLandingStore } from '@/MobileClient/stores/ShopLanding/shop'; // Укажите правильный путь

export default {
    name: "ShopCategories",
    props: {
        categories: Array,
        activeCategory: String,
    },
    emits: ['select'],
    data(){
        return {
            shopStore: useShopLandingStore(),
        }
    },
    methods:{
        selectCategory(categoryId)  {
            this.shopStore.setActiveCategory(categoryId);

            // Плавный скролл к товарам при смене категории (опционально)
            // document.querySelector('.shop-products')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-categories {
    padding: 40px 0;
    background: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    position: sticky;
    top: 70px;
    z-index: 99;
}

.categories-grid {
    display: flex;
    gap: 0.8rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;

    &::-webkit-scrollbar {
        height: 6px;
    }

    &::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 3px;
    }
}

.category-btn {
    background: var(--light);
    border: 2px solid transparent;
    padding: 0.8rem 1.5rem;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
    color: var(--dark);

    &:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    &.active {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        border-color: transparent;
    }
}
</style>
