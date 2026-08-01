<template>
    <div class="product-grid">
        <div class="container g-2">

            <!-- Комбо-меню (если есть) -->
            <template v-if="collections && collections.length > 0">
                <h5 class="divider my-4" id="cat-combo">Комбо меню</h5>
                <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 g-2 mb-4">
                    <div class="col" v-for="collection in collections" :key="collection.id">
                        <!-- <CollectionCard :item="collection" /> -->
                    </div>
                </div>
            </template>

            <!-- 🆕 ИСПРАВЛЕНИЕ: Безопасная итерация по категориям -->
            <template v-for="cat in (categories || [])" :key="cat?.id || Math.random()">

                <template v-if="cat && cat.products && cat.products.length > 0">
                    <AppDivider :text="cat.name || '-'" :id="'cat-' + cat.id" />

                    <!-- Сетка карточек -->
                    <div class="row row-cols-2 row-cols-sm-2 row-cols-lg-6 row-cols-md-4 g-2 mb-3">
                        <div class="col" v-for="product in cat.products" :key="product?.id || Math.random()">
                            <ProductCard :item="product" />
                        </div>
                    </div>

                    <!-- Кнопка "Загрузить ещё" -->
                    <!-- Кнопка "Загрузить ещё" -->
                    <div v-if="cat.products_count > cat.products.length" class="load-more-wrapper">
                        <LoadMoreButton
                            :remaining="cat.products_count - cat.products.length"
                            :is-loading="isLoadingMore"
                            :disabled="isLoadingMore"
                            @load-more="$emit('load-more', cat.id, cat.products.length)"
                        />
                    </div>
                </template>
            </template>

            <!-- Прелоадер, если категорий нет -->
            <Preloader v-if="!categories || categories.length === 0" />

        </div>
    </div>
</template>

<script>
import ProductCard from '@/MobileClient/Components/Shop/ProductCard.vue';
import StoryList from '@/MobileClient/Components/Shop/Stories/StoryList.vue';
import Preloader from '@/MobileClient/Components/Shop/Preloader.vue';
import LoadMoreButton from './LoadMoreButton.vue';
import AppDivider from '@/MobileClient/Components/AppDivider.vue';

export default {
    name: 'ProductGrid',

    components: {
        ProductCard,
        StoryList,
        Preloader,
        AppDivider,
        LoadMoreButton,
    },

    props: {
        categories: { type: Array, default: () => [] },
        collections: { type: Array, default: () => [] },
        stories: { type: Array, default: () => [] },
        isProductList: { type: Boolean, default: false },
        isLoadingMore: { type: Boolean, default: false },
    },

    emits: ['load-more', 'swipe-left', 'swipe-right'],

    data() {
        return {
            touchStartX: 0,
            touchEndX: 0,
        };
    },

    methods: {
        onTouchStart(e) {
            this.touchStartX = e.changedTouches[0].screenX;
        },
        onTouchEnd(e) {
            this.touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        },
        handleSwipe() {
            const swipeThreshold = 50;
            const diff = this.touchStartX - this.touchEndX;
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) this.$emit('swipe-left');
                else this.$emit('swipe-right');
            }
        },
    },
};
</script>

<style scoped>
.product-grid {
    min-height: 100vh;
    padding-bottom: 2rem;
}

.divider {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: var(--bs-body-color);
}

.divider::before,
.divider::after {
    flex: 1;
    content: '';
    height: 1px;
    background-color: var(--bs-primary);
    margin: 0 12px;
    opacity: 0.5;
}

.load-more-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.category-section {
    scroll-margin-top: 80px;
}
</style>
