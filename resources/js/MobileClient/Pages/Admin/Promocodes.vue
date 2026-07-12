<template>
    <div class="promocodes-page">

        <!-- ========================================== -->
        <!-- ЗАГОЛОВОК -->
        <!-- ========================================== -->
        <div class="page-header">
            <div class="header-icon">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <h2 class="header-title">Промокоды</h2>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ПРОМОКОДОВ -->
        <!-- ========================================== -->
        <div class="promocodes-container">
            <PromoCodesList
                :bot="bot"
                @create="openCreateModal"
                @select="openEditModal"
                @refresh="onPromoCodeRefresh"
            />
        </div>

        <!-- ========================================== -->
        <!-- ПЛАВАЮЩАЯ КНОПКА СОЗДАНИЯ (FAB) -->
        <!-- ========================================== -->
        <div class="d-flex w-100 p-3">
            <button class="fab-button" @click="openCreateModal">
                <i class="fa-solid fa-plus"></i>
                <span>Создать промокод</span>
            </button>
        </div>


        <!-- ========================================== -->
        <!-- МОДАЛКА: ФОРМА ПРОМОКОДА -->
        <!-- ========================================== -->
        <div v-if="showModal" class="modal-overlay mobile-fullscreen" @click.self="closeModal">
            <div class="modal-container form-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="closeModal">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>{{ selectedPromoCode ? 'Редактирование' : 'Новый промокод' }}</h3>
                </div>

                <div class="modal-body">
                    <!-- :key принудительно пересоздает компонент при смене промокода,
                         заменяя грязный хак с loadForm и $nextTick -->
                    <PromoCodesForm
                        :key="formKey"
                        :code="selectedPromoCode"
                        @callback="onFormCallback"
                        @cancel="closeModal"
                    />
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import PromoCodesForm from '@/MobileClient/Components/Admin/Promocodes/PromoCodesForm.vue'
import PromoCodesList from '@/MobileClient/Components/Admin/Promocodes/PromoCodesList.vue'

export default {
    name: 'PromoCodesManager',

    components: {
        PromoCodesForm,
        PromoCodesList,
    },

    props: {
        bot: {
            type: Object,
            default: null,
        },
    },

    data() {
        return {
            showModal: false,
            selectedPromoCode: null,
            formKey: 0,
        }
    },

    methods: {
        openCreateModal() {
            this.selectedPromoCode = null
            this.formKey++
            this.showModal = true
        },

        openEditModal(code) {
            this.selectedPromoCode = { ...code }
            this.formKey++
            this.showModal = true
        },

        closeModal() {
            this.showModal = false
            this.selectedPromoCode = null
        },

        onFormCallback() {
            this.closeModal()
            this.$notify?.({
                title: 'Успешно',
                text: 'Промокод сохранён',
                type: 'success',
            })
        },
    },
}
</script>

<style lang="scss" scoped>

@use 'sass:color';

$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-danger: #ef4444;

.promocodes-page {
    background: $admin-bg;
    min-height: 100vh;
    padding-bottom: calc(80px + env(safe-area-inset-bottom)); // Отступ под FAB
}

// ==========================================
// ЗАГОЛОВОК
// ==========================================
.page-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    z-index: 50;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba($admin-primary, 0.1) 0%, rgba($admin-primary, 0.05) 100%);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.header-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0;
}

// ==========================================
// КОНТЕЙНЕР СПИСКА
// ==========================================
.promocodes-container {
    padding: 16px;
}

// ==========================================
// ПЛАВАЮЩАЯ КНОПКА (FAB)
// ==========================================
.fab-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 20px;
    background: $admin-primary;
    color: white;
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba($admin-primary, 0.3);
    transition: all 0.2s ease;
    width: 100%;

    &:active {
        transform: scale(0.96);
        background:  color.adjust($admin-primary, $lightness: -5%);
    }

    @media (min-width: 768px) {
        left: 50%;
        right: auto;
        transform: translateX(-50%);
        width: 300px;

        &:active {
            transform: translateX(-50%) scale(0.96);
        }
    }
}

// ==========================================
// МОДАЛКА
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    animation: fadeIn 0.2s ease;

    &.mobile-fullscreen {
        align-items: stretch;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;

    .mobile-fullscreen & {
        border-radius: 0;
        max-height: 100vh;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(100%);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-modal {
    max-width: 100%;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    background: $admin-card-bg;
    z-index: 10;

    h3 {
        flex: 1;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

.modal-back {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
    border: none;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:active {
        background: $admin-primary;
        color: white;
    }
}

.modal-body {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .page-header {
        padding: 24px;
    }

    .header-title {
        font-size: 1.25rem;
    }

    .promocodes-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 24px;
    }

    .modal-overlay {
        padding: 20px;

        &.mobile-fullscreen {
            align-items: center;
        }
    }

    .modal-container {
        max-width: 700px;
        border-radius: 16px;
        max-height: 90vh;
    }
}
</style>
