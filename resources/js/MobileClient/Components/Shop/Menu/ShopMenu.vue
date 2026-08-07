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

                    <!-- Категории (горизонтальный скролл) -->
<!--
                    <CategoryList
                        :categories="filteredProducts"
                        :collections-count="collections.length"
                        @select-category="onCategorySelect"
                    />
-->

                    <!-- 🆕 СЕКЦИЯ КОЛЛЕКЦИЙ (над товарами) -->
                    <div v-if="activeCollections.length > 0" class="collections-section px-1 mt-3">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="fa-solid fa-layer-group"></i>
                                <span>Коллекции</span>
                            </h5>
                            <span class="section-badge">{{ activeCollections.length }}</span>
                        </div>

                        <div class="collections-grid">
                            <CollectionCard
                                v-for="collection in activeCollections"
                                :key="collection.id"
                                :item="collection"
                                @open-collection="onCollectionOpen"
                            />
                        </div>
                    </div>

                    <!-- Товары по категориям -->
                    <ProductGrid
                        :categories="filteredProducts"
                        :stories="storiesStore.stories"
                        :is-product-list="settings?.is_product_list"
                        @load-more="onLoadMore"
                        @swipe-left="onSwipeLeft"
                        @swipe-right="onSwipeRight"
                    />
                </div>

                <slot name="navigation"></slot>
            </template>
        </template>

        <!-- Модалка коллекции (глобальная) -->
        <CollectionModal ref="collectionModal" />
    </div>
</template>

<script>
import { useProducts } from '@/MobileClient/composables/useProducts.js';
import { useCollections } from '@/MobileClient/composables/useCollections.js';
import { useStoriesStore } from '@/MobileClient/stores/Shop/stories.js';
import MenuHeader from '@/MobileClient/Components/Shop/Menu/MenuHeader.vue';
import PartnersList from '@/MobileClient/Components/Partners/PartnerList.vue';
import CategoryList from '@/MobileClient/Components/Shop/Menu/CategoryList.vue';
import ProductGrid from '@/MobileClient/Components/Shop/Menu/ProductGrid.vue';
import BookingDropdown from '@/MobileClient/Components/Shop/Booking/BookingDropdown.vue';
import CollectionCard from '@/MobileClient/Components/Shop/Collections/CollectionCard.vue';
import CollectionModal from '@/MobileClient/Components/Shop/Collections/CollectionModal.vue';

export default {
    name: 'Menu',

    components: {
        PartnersList,
        CategoryList,
        ProductGrid,
        BookingDropdown,
        MenuHeader,
        CollectionCard,
        CollectionModal,
    },

    setup() {
        const {
            filteredProducts,
            selectedPartner,
            loadProductsByCategory,
            setPartner,
            clearMenuData,
            setSearch,
            loadMoreProducts
        } = useProducts();

        const {
            collections,
            activeCollections,
            loadCollections,
            loadCollection,
        } = useCollections();

        const storiesStore = useStoriesStore();

        return {
            filteredProducts,
            selectedPartner,
            storiesStore,

            // Коллекции
            collections,
            activeCollections,
            loadCollections,
            loadCollection,

            // Методы товаров
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
                const partnerId = this.selectedPartner?.tenant_partner_id || null;

                await Promise.all([
                    this.loadProductsByCategory(partnerId),
                    this.loadCollections({ partner_id: partnerId }),
                    this.storiesStore.loadPartnersStories(partnerId),
                ]);
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
            this.$nextTick(() => {
                if (category) {
                    this.scrollToCategory(category.id);
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        },

        async onCollectionOpen(collection) {
            try {

                const partnerId = this.selectedPartner?.tenant_partner_id || null;

                const fullCollection = await this.loadCollection({
                    id: collection.id,
                    partner_id: partnerId
                });

                console.log("fullCollection", fullCollection)

                this.$refs.collectionModal?.open(fullCollection || collection);
            } catch (error) {
                console.error('Ошибка открытия коллекции:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить коллекцию',
                    type: 'error',
                });
            }
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

/* Секция коллекций */
.collections-section {
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--bs-body-color, #1f2937);
}

.section-title i {
    color: #8b5cf6;
    font-size: 1.2rem;
}

.section-badge {
    background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
}

.collections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

@media (max-width: 768px) {
    .collections-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 12px;
    }
}

@media (max-width: 576px) {
    .collections-grid {
        grid-template-columns: repeat(1, 1fr);
        gap: 12px;
    }
}
</style>
