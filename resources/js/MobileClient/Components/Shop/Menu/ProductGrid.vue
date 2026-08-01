<template>
    <div class="product-grid album pb-5 " style="min-height: 100vh;">
        <div class="container g-2">

            <!-- Комбо-меню -->
            <template v-if="collections.length > 0">
                <h5 class="my-4 divider" id="cat-combo">Комбо меню</h5>
                <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 g-2">
                    <div class="col" v-for="collection in collections" :key="collection.id">
                        <CollectionCard :item="collection" />
                    </div>
                </div>
            </template>

            <!-- Новинки (истории) -->
            <template v-if="stories.length > 0">
                <h5 class="my-4 divider">Наши новинки</h5>
                <StoryList :stories="stories" />
            </template>

            <!-- Товары по категориям -->
            <template v-for="cat in categories" :key="cat.id">

                <template v-if="cat.products.length>0">
                    <AppDivider :text="cat.name || '-'" :id="'cat-' + cat.id"></AppDivider>

                    <!-- Сетка карточек -->
                    <div
                        v-if="!isProductList"
                        class="row row-cols-2 row-cols-sm-2 row-cols-md-3 g-2"
                        @touchstart="onTouchStart"
                        @touchend="onTouchEnd"
                    >
                        <div class="col" v-for="product in cat.products" :key="product.id">
                            <ProductCard :item="product" />
                        </div>

                        <!-- Кнопка "Загрузить ещё" -->
                        <!-- Кнопка "Загрузить ещё" -->
                        <LoadMoreButton
                            v-if="cat.products_count > cat.products.length"
                            :remaining="cat.products_count - cat.products.length"
                            :is-loading="isLoadingMore"
                            @load-more="$emit('load-more', cat.id, cat.products.length)"
                        />
                    </div>

                    <!-- Список (альтернативный вид) -->
                    <template v-else>
                        <ol class="list-group list-group-numbered">
                            <ProductListItem
                                v-for="product in cat.products"
                                :key="product.id"
                                :item="product"
                            />
                        </ol>

                        <!-- Кнопка "Загрузить ещё" -->
                        <LoadMoreButton
                            v-if="cat.products_count > cat.products.length"
                            :remaining="cat.products_count - cat.products.length"
                            :is-loading="isLoadingMore"
                            @load-more="$emit('load-more', cat.id, cat.products.length)"
                        />
                    </template>
                </template>

            </template>

            <!-- Прелоадер -->
            <Preloader v-if="categories.length === 0" />
        </div>
    </div>
</template>

<script>
import ProductCard from '@/MobileClient/Components/Shop/ProductCard.vue';
/*import CollectionCard from '@/MobileClient/Components/Shop/CollectionCard.vue';*/
import StoryList from '@/MobileClient/Components/Shop/Stories/StoryList.vue';
/*import ProductListItem from '@/MobileClient/Components/Shop/ProductListItem.vue';*/
import Preloader from '@/MobileClient/Components/Shop/Preloader.vue';
import LoadMoreButton from './LoadMoreButton.vue';
import AppDivider from '@/MobileClient/Components/AppDivider.vue';

export default {
    name: 'ProductGrid',

    components: {
        ProductCard,
       // CollectionCard,
        StoryList,
       // ProductListItem,
        Preloader,
        AppDivider,
        LoadMoreButton,
    },

    props: {
        categories: Array,
        collections: Array,
        stories: Array,
        isProductList: Boolean,
        isLoadingMore: Boolean,
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
.divider {
    display: flex;
    align-items: center;
}
.divider::before,
.divider::after {
    flex: 1;
    content: '';
    padding: 1px;
    background-color: var(--bs-primary);
    margin: 5px;
}
</style>
