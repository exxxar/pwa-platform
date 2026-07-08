<template>
    <div class="menu-container">
        <!-- Режим выбора партнёра -->
        <template v-if="shopMode === 'partners'">
            <PartnersMain @select="onPartnerSelect" />
        </template>

        <!-- Режим магазина -->
        <template v-else>
            <!-- Индикатор загрузки -->
            <div v-if="isLoading" class="loading-container d-flex flex-column justify-content-center align-items-center">
                <div class="loading-content text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <h5 class="loading-title">Загружаем товары...</h5>
                    <p class="loading-subtitle text-muted">Пожалуйста, подождите</p>
                </div>
            </div>

            <!-- Основной контент -->
            <template v-else>
                <MenuHeader
                    :settings="settings"
                    :categories="menuStore.filteredProducts"
                    :collections="menuStore.collections"
                    :show-back-button="hasPartners"
                    @select-category="onCategorySelect"
                    @search="onSearch"
                    @back-to-partners="backToPartners"
                />

                <div class="menu-content d-flex flex-column">
                    <!-- Предупреждение о блокировке -->
                    <div v-if="settings?.is_disabled" class="p-2 mt-2">
                        <div class="alert alert-danger mb-0">
                            <p class="mb-0">{{ settings.disabled_text }}</p>
                        </div>
                    </div>

                    <!-- Бронирование -->
                    <BookingDropdown />

                    <!-- Контент в зависимости от таба -->
                    <CategoryList
                        v-show="activeTab === 'categories'"
                        :categories="menuStore.filteredProducts"
                        :collections-count="menuStore.collections.length"
                        @select-category="onCategorySelect"
                    />

                    <template v-if="activeTab === 'products'">
                        <ProductGrid
                            :categories="menuStore.filteredProducts"
                            :collections="menuStore.collections"
                            :stories="menuStore.stories"
                            :is-product-list="settings?.is_product_list"
                            @load-more="onLoadMore"
                            @swipe-left="onSwipeLeft"
                            @swipe-right="onSwipeRight"
                        />
                    </template>
                </div>

                <slot name="navigation"></slot>
            </template>
        </template>
    </div>
</template>

<script>
import { useMenuStore } from '@/MobileClient/stores/Shop/menu.js';
import { useStoriesStore } from '@/MobileClient/stores/Shop/stories.js';
import PartnersMain from '@/MobileClient/Components/Partners/PartnersMain.vue';
import MenuHeader from '@/MobileClient/Components/Shop/Menu/MenuHeader.vue';
import CategoryList from '@/MobileClient/Components/Shop/Menu/CategoryList.vue';
import ProductGrid from '@/MobileClient/Components/Shop/Menu/ProductGrid.vue';
import BookingDropdown from '@/MobileClient/Components/Shop/Booking/BookingDropdown.vue';
import SkeletonLoader from '@/MobileClient/Components/Common/SkeletonLoader.vue'

export default {
    name: 'Menu',

    components: {
        PartnersMain,
        MenuHeader,
        CategoryList,
        SkeletonLoader,
        ProductGrid,
        BookingDropdown,
    },

    setup() {
        const menuStore = useMenuStore();
        const storiesStore = useStoriesStore();
        return { menuStore, storiesStore };
    },

    data() {
        return {
            shopMode: 'partners',
            activeTab: 'categories',
            isLoading: false, // Флаг загрузки
        };
    },

    computed: {
        settings() {
            return window.Tenant?.settings;
        },
        hasPartners() {
            return this.settings?.partners?.is_active || false;
        },
    },

    mounted() {
        this.shopMode = this.hasPartners ? 'partners' : 'shop';

        if (this.shopMode === 'shop') {
            this.loadInitialData();
        }
    },

    methods: {
        async loadInitialData() {
            this.isLoading = true; // Включаем индикатор
            try {
                await Promise.all([
                    this.menuStore.loadProductsByCategory(this.menuStore.selectedPartner?.tenant_partner_id),
                    this.storiesStore.loadPartnersStories(this.menuStore.selectedPartner?.tenant_partner_id),
                ]);
                this.activeTab = 'products';
            } catch (error) {
                console.error('Ошибка загрузки данных:', error);
            } finally {
                this.isLoading = false; // Выключаем индикатор
            }
        },

        async onPartnerSelect(partner) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            console.log("partner", partner)
            this.menuStore.setPartner(partner);
            this.shopMode = 'shop';
            await this.loadInitialData();

        },

        backToPartners() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.menuStore.clearData();
            this.shopMode = 'partners';
        },

        onCategorySelect(category) {
            this.activeTab = 'products';
            this.$nextTick(() => {
                if (category) {
                    this.scrollToCategory(category.id);
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        },

        onSearch(query) {
            this.menuStore.setSearch(query);
        },

        async onLoadMore(categoryId, offset) {
            try {
                await this.menuStore.loadMoreProducts(
                    categoryId,
                    offset,
                    this.menuStore.selectedPartner?.tenant_partner_id
                );
            } catch (error) {
                console.error('Ошибка загрузки:', error);
            }
        },

        onSwipeLeft() {
            this.$router.push({ name: 'MenuV2' });
        },
        onSwipeRight() {
            this.$router.push({ name: 'ShopCartV2' });
        },

        scrollToCategory(categoryId) {
            const element = document.getElementById(`cat-${categoryId}`);
            if (element) {
                const headerOffset = 80;
                const elementPosition = element.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        },
    },
};
</script>

<style scoped>
.menu-container {
    min-height: 100vh;
}

.loading-container {
    min-height: 75vh;
    background: transparent;
}

.loading-content {
    max-width: 400px;
    padding: 2rem;
}

.loading-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.loading-subtitle {
    font-size: 0.9rem;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>
