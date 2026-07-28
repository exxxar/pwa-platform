<template>
    <header class="menu-header sticky-top bg-white shadow-sm" style="z-index: 1021;">
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
                    class="btn-back-glass"
                    @click="$emit('back-to-partners')"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>К магазинам</span>
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

.btn-back-glass {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    margin: 0.25rem 0.25rem;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    color: #667eea;
    border: 2px solid rgba(102, 126, 234, 0.2);
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-back-glass:hover {
    background: rgba(102, 126, 234, 0.1);
    border-color: #667eea;
    transform: scale(1.05);
}

.btn-back-glass:active {
    transform: scale(0.98);
}
</style>
