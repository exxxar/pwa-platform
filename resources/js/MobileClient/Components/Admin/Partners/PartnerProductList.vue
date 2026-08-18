<template>
    <div class="partner-products-page">

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ НАСТРОЕК -->
        <!-- ========================================== -->
        <div class="settings-panel">
            <div class="setting-card">
                <div class="setting-info">
                    <div class="setting-icon charge">
                        <i class="fa-solid fa-percent"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Наценка</h4>
                        <p class="setting-description">Процент наценки на товары партнёра</p>
                    </div>
                </div>
                <div class="charge-input-wrapper">
                    <input
                        type="number"
                        v-model.number="extra_charge"
                        class="charge-input"
                        min="0"
                        max="100"
                        placeholder="0"
                        @change="saveExtraCharge"
                    >
                    <span class="charge-suffix">%</span>
                </div>
            </div>

            <div class="setting-card">
                <div class="setting-info">
                    <div class="setting-icon config">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Режим настройки</h4>
                        <p class="setting-description">Управление видимостью товаров</p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="need-product-config"
                        type="checkbox"
                        v-model="need_product_config"
                        class="switch-input"
                    >
                    <label for="need-product-config" class="switch-slider"></label>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СТАТИСТИКА -->
        <!-- ========================================== -->
        <div v-if="categories.length > 0 && !isDataLoading" class="stats-bar">
            <div class="stat-item">
                <i class="fa-solid fa-folder"></i>
                <span>{{ categories.length }} категорий</span>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-cube"></i>
                <span>{{ totalProductsCount }} товаров</span>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-eye-slash"></i>
                <span>{{ excludedCount }} скрыто</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА (SKELETON) -->
        <!-- ========================================== -->
        <div v-if="isDataLoading && categories.length === 0" class="skeleton-list">
            <div v-for="i in 3" :key="i" class="skeleton-card">
                <div class="skeleton-icon shimmer"></div>
                <div class="skeleton-content">
                    <div class="skeleton-title shimmer"></div>
                    <div class="skeleton-meta shimmer"></div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК КАТЕГОРИЙ -->
        <!-- ========================================== -->
        <div v-else-if="categories.length > 0" class="categories-container">
            <div
                v-for="category in categories"
                :key="'cat-' + category.id"
                class="category-card"
                :class="{ 'is-expanded': expandedCategories.includes(category.id) }"
            >
                <!-- Заголовок категории -->
                <div class="category-header" @click="toggleCategory(category.id)">
                    <div class="category-icon">
                        <i class="fa-solid fa-folder"></i>
                    </div>
                    <div class="category-info">
                        <h4 class="category-title">{{ category.title }}</h4>
                        <div class="category-meta">
                            <span class="meta-count">
                                <i class="fa-solid fa-cube"></i>
                                {{ category.products.length }} из {{ category.products_count || category.products.length }}
                            </span>
                            <span v-if="getCategoryExcludedCount(category) > 0" class="meta-excluded">
                                <i class="fa-solid fa-eye-slash"></i>
                                {{ getCategoryExcludedCount(category) }} скрыто
                            </span>
                        </div>
                    </div>
                    <div class="category-toggle">
                        <i class="fa-solid" :class="expandedCategories.includes(category.id) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                </div>

                <!-- Содержимое категории -->
                <transition name="expand">
                    <div v-show="expandedCategories.includes(category.id)" class="category-body">
                        <template v-if="category.products.length > 0">
                            <div class="products-list">
                                <div
                                    v-for="product in category.products"
                                    :key="'prod-' + product.id"
                                    class="product-item"
                                    :class="{ 'is-excluded': isProductExcluded(product.id) }"
                                >
                                    <div class="product-icon">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div class="product-info">
                                        <h5 class="product-title">{{ product.title }}</h5>
                                        <div class="product-price">
                                            <template v-if="extra_charge === 0">
                                                <span class="price-current">{{ formatPrice(product.current_price) }}</span>
                                            </template>
                                            <template v-else>
                                                <span class="price-original">{{ formatPrice(product.current_price) }}</span>
                                                <span class="price-with-charge">{{ formatPrice(calculatePriceWithCharge(product.current_price)) }}</span>
                                                <span class="price-badge">+{{ extra_charge }}%</span>
                                            </template>
                                        </div>

                                        <!-- Переключатель отображения -->
                                        <div v-if="need_product_config" class="product-toggle mt-2">
                                            <div class="switch-control small">
                                                <input
                                                    :id="'product-toggle-' + product.id"
                                                    type="checkbox"
                                                    :checked="!isProductExcluded(product.id)"
                                                    class="switch-input"
                                                    @change="changeStatus(product.id, isProductExcluded(product.id) ? 0 : 1)"
                                                >
                                                <label :for="'product-toggle-' + product.id" class="switch-slider"></label>
                                            </div>
                                            <label :for="'product-toggle-' + product.id" class="toggle-label">
                                                {{ isProductExcluded(product.id) ? 'Не отображается' : 'Отображается в списке' }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Кнопка "Загрузить еще" -->
                            <button
                                v-if="(category.products_count || 0) > category.products.length"
                                class="btn-load-more"
                                @click="loadMore(category.id, category.products.length)"
                                :disabled="isDataLoading"
                            >
                                <span v-if="!isDataLoading">
                                    <i class="fa-solid fa-plus"></i>
                                    Загрузить ещё ({{ (category.products_count || 0) - category.products.length }})
                                </span>
                                <span v-else class="loading-text">
                                    <span class="spinner-small"></span>
                                    Загрузка...
                                </span>
                            </button>
                        </template>

                        <div v-else class="empty-category">
                            <i class="fa-solid fa-box-open"></i>
                            <p>В категории нет товаров</p>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ПУСТОЕ СОСТОЯНИЕ -->
        <!-- ========================================== -->
        <div v-else-if="!isDataLoading" class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h3>Товары не найдены</h3>
            <p>У партнёра пока нет товаров</p>
        </div>

    </div>
</template>

<script>
import { usePartnersStore } from '@/MobileClient/stores/Shop/partners.js';

export default {
    name: 'PartnerProductList',

    props: {
        partner: {
            type: Object,
            required: true,
        },
        isLoading: {
            type: Boolean,
            default: false
        }
    },

    setup(props) {
        const partnerStore = usePartnersStore();

        const partnerData = computed(() => {
            return partnerStore.getPartnerProducts(props.partner.id);
        });

        return {
            partnerData,
            loadProductsByCategory: partnerStore.loadProductsByCategory,
            loadMoreProductsByCategory: partnerStore.loadMoreProductsByCategory,
            changePartnerProductStatus: partnerStore.changePartnerProductStatus,
        };
    },

    data() {
        return {
            extra_charge: 0,
            need_product_config: false,
            expandedCategories: [],
            localExcludes: [], // 🆕 Локальная копия excludes для избежания мутации пропса
        };
    },

    watch: {
        'partner.config.excludes': {
            immediate: true,
            handler(newVal) {
                this.localExcludes = [...(newVal || [])];
            }
        }
    },

    computed: {
        isDataLoading() {
            return this.isLoading || this.partnerData.loading;
        },

        categories() {
            return this.partnerData.categories || [];
        },

        excludes() {
            return this.localExcludes;
        },

        totalProductsCount() {
            return this.categories.reduce((sum, cat) => sum + (cat.products_count || cat.products.length || 0), 0);
        },

        excludedCount() {
            return this.excludes.length;
        },
    },

    mounted() {
        this.extra_charge = this.partner.extra_charge || 0;
        this.need_product_config = this.partner.need_product_config || false;

        if (this.categories.length === 0 && !this.isLoading && !this.partnerData.loading) {
            this.loadProducts();
        } else if (this.categories.length > 0 && this.expandedCategories.length === 0) {
            this.expandedCategories.push(this.categories[0].id);
        }
    },

    methods: {
        async loadProducts() {
            // 🆕 Для загрузки товаров нужен ID самого приложения-партнёра (tenant_partner_id)
            const tenantPartnerId = this.partner.tenant_partner_id;
            if (!tenantPartnerId) {
                console.error('Отсутствует tenant_partner_id у партнера');
                return;
            }

            try {
                await this.loadProductsByCategory({
                    partner_id: tenantPartnerId,
                });

                if (this.categories.length > 0 && this.expandedCategories.length === 0) {
                    this.expandedCategories.push(this.categories[0].id);
                }
            } catch (err) {
                console.error('Ошибка загрузки товаров:', err);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить товары',
                    type: 'error',
                });
            }
        },

        async loadMore(catId, offset) {
            // 🆕 Для дозагрузки тоже нужен tenant_partner_id
            const tenantPartnerId = this.partner.tenant_partner_id;

            try {
                const resp = await this.loadMoreProductsByCategory({
                    partner_id: tenantPartnerId,
                    category_id: catId,
                    offset: offset,
                });

                const newProducts = resp?.data || resp || [];

                if (newProducts.length === 0) return;

                const category = this.categories.find(c => c.id === catId);
                if (category) {
                    category.products.push(...newProducts);
                }
            } catch (err) {
                console.error('Ошибка дозагрузки:', err);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить товары',
                    type: 'error',
                });
            }
        },

        toggleCategory(catId) {
            const index = this.expandedCategories.indexOf(catId);
            if (index === -1) {
                this.expandedCategories.push(catId);
            } else {
                this.expandedCategories.splice(index, 1);
            }
        },

        isProductExcluded(productId) {
            return this.localExcludes.includes(productId);
        },

        getCategoryExcludedCount(category) {
            return category.products.filter(p => this.isProductExcluded(p.id)).length;
        },

        async changeStatus(productId, status) {
            const index = this.localExcludes.indexOf(productId);

            // Оптимистичное обновление ЛОКАЛЬНОЙ копии
            if (index === -1) {
                this.localExcludes.push(productId);
            } else {
                this.localExcludes.splice(index, 1);
            }

            try {
                await this.changePartnerProductStatus({
                    product_id: productId,
                    // 🆕 Для изменения статуса (excludes) нужен ID записи в таблице partners (this.partner.id)
                    partner_id: this.partner.id,
                    status: status,
                });

                this.$notify?.({
                    title: 'Успех',
                    text: status === 0 ? 'Товар отображается' : 'Товар скрыт',
                    type: 'success',
                });
            } catch (err) {
                // Откат при ошибке
                if (index === -1) {
                    const i = this.localExcludes.indexOf(productId);
                    if (i !== -1) this.localExcludes.splice(i, 1);
                } else {
                    this.localExcludes.push(productId);
                }

                console.error('Ошибка изменения статуса:', err);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось изменить статус',
                    type: 'error',
                });
            }
        },

        async saveExtraCharge() {
            // TODO: Реализовать сохранение наценки через API (updatePartner)
            console.log('Save extra charge:', this.extra_charge);
        },

        calculatePriceWithCharge(price) {
            const basePrice = parseFloat(price) || 0;
            const charge = parseFloat(this.extra_charge) || 0;
            return basePrice + (basePrice * charge / 100);
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0);
        },
    },
};
</script>
