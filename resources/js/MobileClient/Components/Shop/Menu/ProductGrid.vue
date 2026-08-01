<template>
    <div class="product-grid">
        <div class="container g-2">

            <!-- 1. Комбо-меню -->
            <section v-if="collections.length > 0" class="mb-4">
                <h5 class="divider mb-3" id="cat-combo">Комбо меню</h5>
                <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 g-2">
                    <div class="col" v-for="collection in collections" :key="collection.id">
                        <CollectionCard :item="collection" />
                    </div>
                </div>
            </section>

            <!-- 2. Новинки (Истории) -->
            <section v-if="stories.length > 0" class="mb-4">
                <h5 class="divider mb-3">Наши новинки</h5>
                <StoryList :stories="stories" />
            </section>

            <!-- 3. Товары по категориям -->
            <section v-for="cat in categories" :key="cat.id" class="category-section mb-4">

                <!-- Показываем блок только если есть товары -->
                <template v-if="cat.products && cat.products.length > 0">

                    <AppDivider :text="cat.name || 'Без названия'" :id="'cat-' + cat.id" />

                    <!-- Вариант А: Сетка карточек -->
                    <div
                        v-if="!isProductList"
                        class="row row-cols-2 row-cols-sm-2 row-cols-md-3 g-2 mb-3"
                        @touchstart="onTouchStart"
                        @touchend="onTouchEnd"
                    >
                        <div class="col" v-for="product in cat.products" :key="product.id">
                            <ProductCard :item="product" />
                        </div>
                    </div>

                    <!-- Вариант Б: Список -->
                    <ol v-else class="list-group list-group-numbered mb-3">
                        <ProductListItem
                            v-for="product in cat.products"
                            :key="product.id"
                            :item="product"
                        />
                    </ol>

                    <!-- 4. Кнопка "Загрузить ещё" (Вынесена из сетки, чтобы не ломать колонки) -->
                    <div v-if="cat.products_count > cat.products.length" class="load-more-wrapper">
                        <LoadMoreButton
                            :remaining="cat.products_count - cat.products.length"
                            :is-loading="isLoadingMore"
                            @load-more="$emit('load-more', cat.id, cat.products.length)"
                        />
                    </div>

                </template>
            </section>

            <!-- 5. Прелоадер (если категорий нет) -->
            <Preloader v-if="categories.length === 0" />

        </div>
    </div>
</template>

<script>
import ProductCard from '@/MobileClient/Components/Shop/ProductCard.vue';
//import CollectionCard from '@/MobileClient/Components/Shop/CollectionCard.vue'; // Раскомментировал, если нужен
import StoryList from '@/MobileClient/Components/Shop/Stories/StoryList.vue';
//import ProductListItem from '@/MobileClient/Components/Shop/ProductListItem.vue'; // Раскомментировал, если нужен
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
        categories: {
            type: Array,
            default: () => []
        },
        collections: {
            type: Array,
            default: () => []
        },
        stories: {
            type: Array,
            default: () => []
        },
        isProductList: {
            type: Boolean,
            default: false
        },
        isLoadingMore: {
            type: Boolean,
            default: false
        },
    },

    emits: ['load-more', 'swipe-left', 'swipe-right'],

    data() {
        return {
            touchStartX: 0,
            touchEndX: 0,
        };
    },

    methods: {
        // Обработка свайпов (замена v-touch)
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
                if (diff > 0) {
                    this.$emit('swipe-left');
                } else {
                    this.$emit('swipe-right');
                }
            }
        },
    },
};
</script>

<style scoped>
.product-grid {
    min-height: 100vh;
    padding-bottom: 2rem; /* Заменяет pb-5, но в CSS надежнее */
}

/* Стиль разделителя с линиями по бокам */
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
    height: 1px; /* Лучше использовать height вместо padding для линий */
    background-color: var(--bs-primary);
    margin: 0 12px;
    opacity: 0.5;
}

/* Обертка для кнопки "Загрузить ещё", чтобы она была по центру и не ломала сетку Bootstrap */
.load-more-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
    margin-bottom: 1rem;
}

/* Небольшие отступы для секций категорий */
.category-section {
    scroll-margin-top: 80px; /* Чтобы при якорной ссылке заголовок не прятался под шапкой */
}
</style>
