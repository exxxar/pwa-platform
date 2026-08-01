<template>
    <div class="menu-container">
        <!-- Режим выбора партнёра -->
        <template v-if="shopMode === 'partners'">
            <PartnersList @select="onPartnerSelect" />
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

                <!-- 🆕 ИСПРАВЛЕНИЕ: Используем переменные напрямую, без productsStore. -->
                <MenuHeader
                    :settings="settings"
                    :categories="filteredProducts"
                    :collections="collections"
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
                        :categories="filteredProducts"
                        :collections-count="collections.length"
                        @select-category="onCategorySelect"
                    />

                    <template v-if="activeTab === 'products'">
                        <!-- 🆕 ПРАВИЛЬНЫЙ ДЕБАГ (покажет реальные числа, а не undefined) -->
                        <div v-if="false" style="background: #ffeb3b; color: #000; padding: 10px; font-weight: bold;">
                            DEBUG: Отфильтровано категорий: {{ filteredProducts.length }}
                        </div>

                        <ProductGrid
                            :categories="filteredProducts"
                            :collections="collections"
                            :stories="storiesStore.stories"
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
import { useProducts } from '@/MobileClient/composables/useProducts.js';
import { useStoriesStore } from '@/MobileClient/stores/Shop/stories.js';
import MenuHeader from '@/MobileClient/Components/Shop/Menu/MenuHeader.vue';
import PartnersList from '@/MobileClient/Components/Partners/PartnerList.vue';
import CategoryList from '@/MobileClient/Components/Shop/Menu/CategoryList.vue';
import ProductGrid from '@/MobileClient/Components/Shop/Menu/ProductGrid.vue';
import BookingDropdown from '@/MobileClient/Components/Shop/Booking/BookingDropdown.vue';

export default {
    name: 'Menu',

    components: {
        PartnersList,
        CategoryList,
        ProductGrid,
        BookingDropdown,
        MenuHeader,
    },

    setup() {
        // 🆕 ДЕСТРУКТУРИЗИРУЕМ НУЖНЫЕ РЕАКТИВНЫЕ ПЕРЕМЕННЫЕ И МЕТОДЫ НА ВЕРХНИЙ УРОВЕНЬ
        const {
            filteredProducts,
            collections,
            selectedPartner,
            loadProductsByCategory,
            setPartner,
            clearMenuData,
            setSearch,
            loadMoreProducts
        } = useProducts();

        const storiesStore = useStoriesStore();

        return {
            // 🆕 Возвращаем их напрямую. Vue автоматически "развернет" (unwraps) их в шаблоне и в this.methods!
            filteredProducts,
            collections,
            selectedPartner,
            storiesStore,

            // Методы тоже возвращаем для удобства
            loadProductsByCategory,
            setPartner,
            clearMenuData,
            setSearch,
            loadMoreProducts
        };
    },

    data() {
        return {
            shopMode: 'partners',
            activeTab: 'categories',
            isLoading: false,
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
            this.isLoading = true;
            try {
                // 🆕 this.selectedPartner теперь корректно развернут Vue, .value не нужен
                const partnerId = this.selectedPartner?.tenant_partner_id || null;

                await Promise.all([
                    this.loadProductsByCategory(partnerId),
                    this.storiesStore.loadPartnersStories(partnerId),
                ]);

                this.activeTab = 'products';
            } catch (error) {
                console.error('❌ Ошибка загрузки данных:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async onPartnerSelect(partner) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.setPartner(partner);
            this.shopMode = 'shop';
            await this.loadInitialData();
        },

        backToPartners() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.clearMenuData();
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
            this.setSearch(query);
        },

        async onLoadMore(categoryId, offset) {
            try {
                const partnerId = this.selectedPartner?.tenant_partner_id || null;
                await this.loadMoreProducts(categoryId, offset, partnerId);
            } catch (error) {
                console.error('Ошибка загрузки:', error);
            }
        },

        onSwipeLeft() {
            this.$router.push({ name: 'Menu' });
        },
        onSwipeRight() {
            this.$router.push({ name: 'ShopCart' });
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
/* Ваши стили остаются без изменений */
.menu-container { min-height: 100vh; }
.loading-container { min-height: 75vh; background: transparent; }
.loading-content { max-width: 400px; padding: 2rem; }
.loading-title { color: #2c3e50; font-weight: 600; margin-bottom: 0.5rem; }
.loading-subtitle { font-size: 0.9rem; }
.spinner-border { width: 3rem; height: 3rem; }
</style>
