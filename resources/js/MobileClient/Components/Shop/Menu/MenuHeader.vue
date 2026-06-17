<template>
    <header class="menu-header sticky-top bg-white shadow-sm" style="z-index: 1000;">
        <CategorySlider
            :settings="settings"
            :categories="categories"
            :collections="collections"
            @select="$emit('select-category', $event)"
            @search="$emit('search', $event)"
        >
            <template #back-btn>
                <button
                    v-if="showBackButton"
                    class="btn btn-primary p-2 mt-2"
                    @click="$emit('back-to-partners')"
                >
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    К магазинам
                </button>
            </template>
        </CategorySlider>
    </header>
</template>

<script>
import CategorySlider from '@/MobileClient/Components/Shop/CategorySlider.vue';

export default {
    name: 'MenuHeader',

    components: {
        CategorySlider,
    },

    props: {
        settings: Object,
        categories: Array,
        collections: Array,
        showBackButton: Boolean,
    },

    emits: ['select-category', 'search', 'back-to-partners'],
};
</script>

<style scoped>
.menu-header {
    top: 0;
}
</style>
