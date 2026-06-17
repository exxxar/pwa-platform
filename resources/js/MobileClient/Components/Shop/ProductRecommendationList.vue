<script setup>
import ProductCard from "@/MobileClient/Components/Shop/ProductCard.vue";

import 'vue3-carousel/dist/carousel.css'
import {Carousel, Slide, Pagination, Navigation} from 'vue3-carousel'

const carouselConfig = {
    itemsToShow: 2.1,
    wrapAround: true
}
</script>
<template>

    <div class="row my-3" v-if="(products||[]).length>0">
        <div class="col-12">
            <h6 class="fw-bold">Также рекомендуем</h6>
        </div>
        <div class="col-12">
            <Carousel v-bind="carouselConfig">
                <Slide v-for="product in products" :key="product.id">
                    <div class="carousel__item">
                        <ProductCard
                            :item="product"
                        />
                    </div>
                </Slide>

                <template #addons>

                </template>
            </Carousel>

        </div>
    </div>


</template>

<script>

import {useProductsStore} from "@/MobileClient/stores/Shop/products";

export default {
    setup(){
      const productStore = useProductsStore();

      return {productStore}
    },
    name: 'ProductRecommendationList',
    data() {
        return {
            products: []
        };
    },
    mounted() {
      //  this.loadRecommendedProducts()
    },
    methods: {
        loadRecommendedProducts() {
            return this.productStore.loadRecommendedProducts().then((response) => {
                this.products = response.data || []
            })
        },
    },
};
</script>

<style scoped>
.carousel__item {
    padding: 5px;
}

</style>
