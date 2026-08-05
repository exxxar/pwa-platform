<template>
    <div class="admin-dashboard">

        <!-- Верхняя панель -->
        <div class="admin-header">
            <button class="back-btn" @click="$router.back()">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Назад</span>
            </button>
            <h1 class="admin-title">Управление магазином</h1>
        </div>

        <!-- Навигация -->
        <div class="admin-nav-wrapper">
            <div class="admin-nav">
                <button
                    v-for="navItem in navItems"
                    :key="navItem.id"
                    class="nav-tab"
                    :class="{ 'is-active': tab === navItem.id }"
                    @click="switchTab(navItem.id)"
                >
                    <i :class="navItem.icon"></i>
                    <span>{{ navItem.label }}</span>
                </button>
            </div>
        </div>

        <!-- Основной контент -->
        <div class="admin-content">

            <!-- Вкладка: Товары (Список) -->
            <div v-if="tab === 0" class="admin-card fade-in">
                <div class="card-header-simple">
                    <h3>Список товаров</h3>
                </div>
                <ProductList v-if="!load" @select="selectProduct"/>
            </div>

            <!-- Вкладка: Форма товара -->
            <div v-if="tab === 1" class="admin-card fade-in">
                <div class="card-header-simple">
                    <button class="btn-text" @click="switchTab(0)">
                        <i class="fa-solid fa-arrow-left"></i> Назад к списку
                    </button>
                    <h3>Редактирование товара</h3>
                </div>
                <ProductForm
                    v-if="!load && selectedProduct"
                    :item="selectedProduct"
                    @refresh="refresh"
                />
            </div>

            <!-- Вкладка: Категории -->
            <div v-if="tab === 2" class="admin-card fade-in">
                <div class="card-header-simple">
                    <h3>Категории товаров</h3>
                </div>
                <ProductCategoryList v-if="!load"/>
            </div>

            <!-- Вкладка: Подборки (Комбо) -->
            <div v-if="tab === 3" class="admin-card fade-in">
                <div class="card-header-simple">
                    <h3>Подборки товаров (Комбо)</h3>
                </div>
                <CollectionList v-if="!load"/>
            </div>

            <!-- Вкладка: Обновление данных -->
            <div v-if="tab === 4" class="admin-grid fade-in">



                <!-- Блок 2: Интеграция FrontPad / IIKO -->
                <div class="admin-card" v-if="tenant.frontPad?.is_active || tenant.iiko?.is_active">
                    <div class="card-header-simple">
                        <i class="fa-solid fa-gears section-icon gray"></i>
                        <h3>Внешние системы</h3>
                    </div>

                    <div class="button-stack">
                        <button
                            v-if="tenant.frontPad?.is_active"
                            @click="openFrontPadUpdateModal"
                            class="btn-secondary-modern w-100"
                        >
                            <i class="fa-solid fa-cloud-arrow-down"></i> Обновить из FrontPad (API)
                        </button>

                        <button
                            v-if="tenant.iiko?.is_active"
                            @click="goTo('IikoV2')"
                            class="btn-secondary-modern w-100"
                        >
                            <i class="fa-solid fa-gears"></i> Перейти к настройкам IIKO
                        </button>
                    </div>
                </div>

                <!-- Блок 3: Экспорт / Импорт -->
                <div class="admin-card">
                    <div class="card-header-simple">
                        <i class="fa-solid fa-file-excel section-icon green"></i>
                        <h3>Экспорт и Импорт данных</h3>
                    </div>
                    <div class="button-stack">
                        <button @click="handleExportProducts" :disabled="load" class="btn-success-modern w-100">
                            <i class="fa-solid fa-file-export"></i> Экспорт товаров в XLS
                        </button>

                        <button @click="handleExportOrders" :disabled="load" class="btn-outline-modern w-100">
                            <i class="fa-solid fa-file-export"></i> Экспорт заказов в XLS
                        </button>

                        <button @click="importProducts" disabled class="btn-outline-modern w-100 disabled">
                            <i class="fa-solid fa-lock"></i> Импорт из XLS (В разработке)
                        </button>
                    </div>
                </div>

            </div>
        </div>



    </div>
</template>

<script>
import { useProducts } from '@/MobileClient/Composables/useProducts.js';
import ProductForm from '@/MobileClient/Components/Admin/Shop/ProductForm.vue';
import ProductList from '@/MobileClient/Components/Admin/Shop/ProductList.vue';
import ProductCategoryList from '@/MobileClient/Components/Admin/Shop/ProductCategoryList.vue';
import CollectionList from '@/MobileClient/Components/Admin/Shop/CollectionList.vue';

export default {
    name: 'AdminShop',

    components: {
        ProductForm,
        ProductList,
        ProductCategoryList,
        CollectionList,
    },

    setup() {
        // 🎯 Подключаем composable — все методы и состояние берём оттуда
        const productsComposable = useProducts();

        return {
            // Состояние из стора
            products: productsComposable.products,
            categories: productsComposable.categories,
            collections: productsComposable.collections,
            isLoading: productsComposable.isLoading,

            // Методы синхронизации


            updateProductsFromFrontPadExcel: productsComposable.updateProductsFromFrontPadExcel,
            updateShopLinkAction: productsComposable.updateShopLink,
            exportAllProducts: productsComposable.exportAllProducts,
            exportAllOrders: productsComposable.exportAllOrders,
        };
    },

    data() {
        return {
            tab: 0,
            load: false,
            url: null,
            link: null,
            frontpad_tab: 0,
            selectedFile: null,
            selectedProduct: null,
            botForm: {
                vk_shop_link: null,
            },
            navItems: [
                { id: 0, label: 'Товары', icon: 'fa-solid fa-box' },
                { id: 2, label: 'Категории', icon: 'fa-solid fa-layer-group' },
                { id: 3, label: 'Подборки (Комбо)', icon: 'fa-solid fa-box-open' },
                { id: 4, label: 'Синхронизация', icon: 'fa-solid fa-arrows-rotate' },
            ],

            // UI-состояние для модалок
            showUpdateModal: false,
            showFrontPadModal: false,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },
        self() {
            return window.TenantUser || null;
        },
    },

    mounted() {
        this.botForm.vk_shop_link = this.tenant?.vk_shop_link || null;

    },

    methods: {
        switchTab(id) {
            this.tab = id;
            if (id !== 1) {
                this.selectedProduct = null;
            }
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.selectedFile = file;
            }
        },

        async submitFrontPadFile() {
            if (!this.selectedFile) return;
            this.load = true;

            const formData = new FormData();
            formData.append('excel_file', this.selectedFile);

            try {
                // 🎯 Используем метод из composable
                await this.updateProductsFromFrontPadExcel({ form: formData });
                this.$notify?.({ title: 'Импорт данных', text: 'Файл успешно обработан!', type: 'success' });
                this.selectedFile = null;

                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }

                this.hideUpdateFrontPadModal();
            } catch (err) {
                console.error(err);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить файл', type: 'error' });
            } finally {
                this.load = false;
            }
        },




        hideUpdateFrontPadModal() {
            this.showFrontPadModal = false;
            this.selectedFile = null;
            this.frontpad_tab = 0;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        openFrontPadUpdateModal() {
            this.showFrontPadModal = true;
        },

        openUpdateModal() {
            this.showUpdateModal = true;
        },

        hideUpdateModal() {
            this.showUpdateModal = false;
        },

        goTo(name) {
            this.$router.push({ name });
        },

        // 🎯 Переименовал, чтобы не было конфликта с action из стора
        async handleUpdateShopLink() {
            if (!this.botForm.vk_shop_link?.trim()) {
                this.$notify?.({ title: 'Ошибка', text: 'Введите ссылку на ВК', type: 'error' });
                return;
            }

            this.load = true;
            try {
                // 🎯 Используем метод из composable
                await this.updateShopLinkAction({ botForm: this.botForm });
                this.$notify?.({ title: 'Настройки', text: 'Ссылка на источник ВК сохранена', type: 'success' });
                await this.fetchInitialLink();
            } catch (err) {
                console.error(err);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить ссылку', type: 'error' });
            } finally {
                this.load = false;
            }
        },

        refresh() {
            this.load = true;
            this.selectedProduct = null;
            this.$nextTick(() => {
                this.load = false;
            });
        },

        selectProduct(product) {
            this.selectedProduct = product;
            this.tab = 1;
        },

        // 🎯 Переименовал локальные методы-обёртки для избежания конфликтов
        async handleExportOrders() {
            this.load = true;
            try {
                await this.exportAllOrders();
                this.$notify?.({ title: 'Экспорт', text: 'Заказы экспортированы в файл', type: 'success' });
            } catch (err) {
                console.error(err);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось экспортировать заказы', type: 'error' });
            } finally {
                this.load = false;
            }
        },

        async handleExportProducts() {
            this.load = true;
            try {
                await this.exportAllProducts();
                this.$notify?.({ title: 'Экспорт', text: 'Товары экспортированы в файл', type: 'success' });
            } catch (err) {
                console.error(err);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось экспортировать товары', type: 'error' });
            } finally {
                this.load = false;
            }
        },

        importProducts() {
            this.$notify?.({ title: 'Информация', text: 'Функция импорта находится в разработке', type: 'info' });
        },



    },
};
</script>



<style lang="scss" scoped>
@use "sass:color";
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-warning: #f59e0b;
$admin-danger: #ff0d0d;
$admin-success: #10b981;

.admin-dashboard {
    min-height: 100vh;
    background-color: $admin-bg;
    color: $admin-text;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.admin-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: transparent;
    border: 1px solid $admin-border;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $admin-bg;
        color: $admin-text;
        border-color: $admin-text-muted;
    }
}

.admin-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}

.admin-nav-wrapper {
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    padding: 0 24px;
    overflow-x: auto;

    &::-webkit-scrollbar {
        display: none;
    }
}

.admin-nav {
    display: flex;
    gap: 4px;
    max-width: 1200px;
    margin: 0 auto;
}

.nav-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 20px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: $admin-text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    &:hover {
        color: $admin-primary;
        background: rgba($admin-primary, 0.04);
    }

    &.is-active {
        color: $admin-primary;
        border-bottom-color: $admin-primary;
    }
}

.admin-content {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 24px;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}

.admin-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.card-header-simple {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid $admin-border;

    h3 {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        flex: 1;
    }

    .btn-text {
        background: none;
        border: none;
        color: $admin-primary;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 6px;

        &:hover {
            background: rgba($admin-primary, 0.1);
        }
    }
}

.section-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;

    &.vk {
        background: rgba(0, 119, 255, 0.1);
        color: #0077ff;
    }

    &.gray {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }

    &.green {
        background: rgba(16, 185, 129, 0.1);
        color: $admin-success;
    }
}

.admin-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text-muted;
}

.form-control-modern {
    padding: 10px 14px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }
}

.btn-primary-modern, .btn-warning-modern, .btn-success-modern, .btn-secondary-modern, .btn-outline-modern {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary-modern {
    background: $admin-primary;
    color: white;
    width:100%;
    &:hover {
        background:  color.adjust($admin-primary, $lightness: -5%);
    }
}

.btn-warning-modern {
    background: $admin-warning;
    color: white;

    &:hover {
        background:  color.adjust($admin-warning, $lightness: -5%);
    }
}

.btn-success-modern {
    background: $admin-success;
    color: white;

    &:hover {
        background: color.adjust($admin-success, $lightness: -5%);
    }
}

.btn-secondary-modern {
    background: #e9ecef;
    color: $admin-text;

    &:hover {
        background: #dee2e6;
    }
}

.btn-outline-modern {
    background: transparent;
    border: 1px solid $admin-border;
    color: $admin-text-muted;

    &:hover:not(.disabled) {
        border-color: $admin-primary;
        color: $admin-primary;
        background: rgba($admin-primary, 0.04);
    }

    &.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.button-stack {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px;
    background: #e9ecef;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 0.9rem;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-top-color: $admin-text-muted;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.spinner-small {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    vertical-align: middle;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.fade-in {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.admin-modal {
    border: none;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.modal-header-simple {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid $admin-border;

    h5 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .btn-close {
        background: transparent;
        border: none;
        font-size: 1.2rem;
        color: $admin-text-muted;
        cursor: pointer;
    }
}

.modal-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;

    &.warning {
        background: rgba($admin-warning, 0.1);
        color: $admin-warning;
    }
}

.alert-warning-simple {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    background: rgba($admin-warning, 0.08);
    border: 1px solid rgba($admin-warning, 0.2);
    border-radius: 8px;
    color:  color.adjust($admin-warning, $lightness: -20%);
    font-size: 0.85rem;
    line-height: 1.4;
}

.admin-tabs {
    display: flex;
    background: $admin-bg;
    padding: 4px;
    border-radius: 8px;

    button {
        flex: 1;
        padding: 8px;
        border: none;
        background: transparent;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        color: $admin-text-muted;
        cursor: pointer;
        transition: all 0.2s;

        &.active {
            background: $admin-card-bg;
            color: $admin-text;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    }
}

.upload-area {
    text-align: center;
}

.file-input {
    display: none;
}

.file-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px;
    border: 2px dashed $admin-border;
    border-radius: 12px;
    color: $admin-text-muted;
    cursor: pointer;
    transition: all 0.2s;

    i {
        font-size: 1.5rem;
    }

    span {
        font-size: 0.9rem;
    }

    &:hover {
        border-color: $admin-primary;
        background: rgba($admin-primary, 0.04);
        color: $admin-primary;
    }
}

@media (max-width: 768px) {
    .admin-header {
        padding: 16px;
    }
    .admin-content {
        padding: 16px;
        margin: 16px auto;
    }
    .admin-grid {
        grid-template-columns: 1fr;
    }
    .nav-tab span {
        display: none;
    }
    .nav-tab {
        padding: 14px;
        justify-content: center;
    }
}

@media (max-width: 1024px) {
    .product-preview {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .control-panel {
        flex-direction: column;
        align-items: stretch;
        padding: 16px;
        gap: 12px;
    }

    .search-box {
        max-width: 100%;
    }

    .panel-actions {
        width: 100%;

        .btn-primary-modern {
            width: 100%;
        }
    }

    .filters-bar {
        flex-direction: column;
        align-items: stretch;
        padding: 16px;
        gap: 12px;
    }

    .filter-group {
        width: 100%;
        justify-content: space-between;
    }

    .select-modern {
        flex: 1;
    }

    .filter-toggles {
        width: 100%;
        justify-content: stretch;

        .toggle-btn {
            flex: 1;
            justify-content: center;
            font-size: 0.8rem;
            padding: 8px 10px;

            span {
                display: none; // На мобильных оставляем только иконки
            }
        }
    }

    .filter-stats {
        margin-left: 0;
        text-align: center;
    }

    .products-container {
        padding: 16px;
    }

    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
    }

    .card-title {
        font-size: 0.85rem;
    }

    .price-current {
        font-size: 1rem;
    }

    // Таблица — скрываем неважные колонки
    .col-rating,
    .col-integrations {
        display: none;
    }

    .products-table th,
    .products-table td {
        padding: 10px 8px;
        font-size: 0.8rem;
    }

    .table-image {
        width: 40px;
        height: 40px;
    }

    .table-actions {
        flex-wrap: wrap;
    }

    // Модалки
    .modal-overlay {
        padding: 0;
        align-items: flex-end;
    }

    .modal-container {
        border-radius: 16px 16px 0 0;
        max-height: 95vh;
        width: 100%;
    }

    .product-modal,
    .add-modal {
        max-width: 100%;
    }

    .modal-header {
        padding: 14px 16px;
    }

    .modal-body {
        padding: 16px;
    }

    .modal-tabs {
        flex-wrap: wrap;

        .modal-tab {
            font-size: 0.8rem;
            padding: 8px 12px;

            i {
                display: none;
            }
        }
    }

    .preview-gallery {
        .preview-main {
            aspect-ratio: 4/3;
        }
    }

    .confirm-modal {
        padding: 24px 20px;
    }

    .confirm-actions {
        flex-direction: column-reverse;

        button {
            width: 100%;
        }
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .card-info {
        padding: 10px;
    }

    .card-actions {
        padding: 0 10px 10px;
        gap: 4px;
    }

    .action-btn {
        padding: 6px;
        font-size: 0.75rem;
    }

    .card-title {
        font-size: 0.8rem;
        -webkit-line-clamp: 1;
    }

    .card-price {
        margin-bottom: 4px;
    }

    .price-current {
        font-size: 0.9rem;
    }

    .card-meta {
        font-size: 0.7rem;
    }

    // Таблица — компактный вид
    .products-table {
        font-size: 0.75rem;

        th, td {
            padding: 8px 6px;
        }
    }

    .table-title {
        font-size: 0.85rem;
    }

    .status-pill {
        font-size: 0.65rem;
        padding: 3px 6px;
    }

    .action-btn-small {
        width: 28px;
        height: 28px;
        font-size: 0.7rem;
    }
}

// Добавь только эти новые правила для Vue-модалок
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.2s ease;
}

.modal-container {
    background: $admin-card-bg;
    border-radius: 16px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: slideUp 0.3s ease;
}

.confirm-modal {
    max-width: 400px;
    padding: 32px 24px;
    text-align: center;
}

.frontpad-modal {
    max-width: 600px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-bottom: 1px solid $admin-border;

    h3 {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
    }
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
    border: none;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $admin-danger;
        color: white;
    }
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.confirm-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;

    &.warning {
        background: rgba($admin-warning, 0.1);
        color: $admin-warning;
    }
}

.confirm-modal {
    h4 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        color: $admin-text-muted;
        margin-bottom: 24px;
        line-height: 1.4;
    }
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>


