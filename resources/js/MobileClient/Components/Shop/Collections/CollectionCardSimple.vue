<template>
    <div class="collection-card-simple" :class="{ 'is-expanded': isExpanded }">

        <!-- ШАПКА КАРТОЧКИ -->
        <div class="card-header" @click="toggleDetails">
            <div class="header-left">
                <img
                    v-if="item.collection?.image || item.image"
                    :src="item.collection?.image || item.image"
                    :alt="item.collection?.name || item.name"
                    class="card-image"
                >
                <div v-else class="card-image placeholder">
                    <i class="fa-solid fa-layer-group"></i>
                </div>

                <div class="card-info">
                    <h4 class="card-title">{{ item.collection?.name || item.name || 'Сборка' }}</h4>
                    <div class="card-count">
                        <span class="count-badge">{{ item.count }} шт.</span>
                        <span class="count-text">в корзине</span>
                    </div>
                </div>
            </div>

            <div class="header-right">
                <div class="card-total-price">{{ formatPrice(item.total_price) }}</div>
                <div class="expand-indicator">
                    <span class="expand-text">Детали</span>
                    <i class="fa-solid fa-chevron-down expand-icon" :class="{ 'rotated': isExpanded }"></i>
                </div>
            </div>
        </div>

        <!-- ТЕЛО КАРТОЧКИ -->
        <transition name="slide-fade">
            <div v-if="isExpanded" class="card-body">
                <div class="details-header">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Состав сборки:</span>
                </div>

                <div v-if="isLoadingDetails" class="loading-details">
                    <i class="fa-solid fa-spinner fa-spin"></i> Загрузка состава...
                </div>

                <div v-else-if="resolvedProducts.length > 0" class="products-list">
                    <div v-for="(prod, index) in resolvedProducts" :key="index" class="product-item">
                        <div class="product-info">
                            <span class="product-category">{{ prod.category_name || 'Товар' }}</span>
                            <span class="product-name">{{ prod.product?.name || prod.name || 'Неизвестный товар' }}</span>
                        </div>
                        <div class="product-price">
                            {{ formatPrice(prod.product?.price || prod.price || 0) }}
                        </div>
                    </div>
                </div>

                <div v-else class="empty-details">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Данные о составе недоступны</span>
                </div>

                <!-- УПРАВЛЕНИЕ -->
                <div class="card-actions">
                    <!-- 🆕 Кнопка теперь открывает модалку, а не удаляет сразу -->
                    <button class="action-btn remove-btn" @click.stop="openDeleteModal" title="Удалить сборку">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Удалить</span>
                    </button>

                    <div class="qty-controls">
                        <button class="qty-btn" @click.stop="decrementItem" :disabled="isProcessing || item.count <= 1">
                            <i v-if="isProcessing && actionType === 'dec'" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-minus"></i>
                        </button>
                        <span class="qty-value">{{ item.count }}</span>
                        <button class="qty-btn" @click.stop="incrementItem" :disabled="isProcessing">
                            <i v-if="isProcessing && actionType === 'inc'" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- 🆕 МОДАЛКА ПОДТВЕРЖДЕНИЯ УДАЛЕНИЯ -->
        <Teleport to="body">
            <transition name="modal-fade">
                <div v-if="showDeleteModal" class="delete-modal-backdrop" @click.self="closeDeleteModal">
                    <div class="delete-modal-content">
                        <div class="modal-icon-wrapper">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <h3 class="modal-title">Удалить сборку?</h3>
                        <p class="modal-text">
                            Вы уверены, что хотите удалить <strong>«{{ item.collection?.name || item.name }}»</strong> из корзины?
                            Это действие нельзя отменить.
                        </p>
                        <div class="modal-actions">
                            <button class="btn-cancel" @click="closeDeleteModal" :disabled="isProcessing">
                                Отмена
                            </button>
                            <button class="btn-confirm" @click="confirmRemove" :disabled="isProcessing">
                                <i v-if="isProcessing" class="fa-solid fa-spinner fa-spin"></i>
                                <span v-else>Удалить</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'CollectionCardSimple',
    props: {
        item: { type: Object, required: true }
    },
    data() {
        return {
            isExpanded: false,
            isProcessing: false,
            actionType: null, // 'inc', 'dec', 'remove'
            isLoadingDetails: false,
            resolvedProducts: [],
            showDeleteModal: false // 🆕 Состояние для модалки удаления
        };
    },
    computed: {
        initialProducts() {
            if (this.item.selected_products && Array.isArray(this.item.selected_products)) {
                return this.item.selected_products;
            }
            if (this.item.params?.selected_products && Array.isArray(this.item.params.selected_products)) {
                return this.item.params.selected_products;
            }
            return [];
        }
    },
    methods: {
        async toggleDetails() {
            this.isExpanded = !this.isExpanded;
            if (this.isExpanded && this.resolvedProducts.length === 0 && this.item.params?.ids) {
                await this.loadProductDetails();
            }
        },

        async loadProductDetails() {
            this.isLoadingDetails = true;
            try {
                const response = await axios.post('/shop/products/by-ids', {
                    ids: this.item.params.ids
                });

                this.resolvedProducts = this.item.params.ids.map(id => {
                    const prodData = response.data.data?.find(p => p.id === id) || response.data.find?.(p => p.id === id);
                    return {
                        product: prodData,
                        category_name: 'Товар',
                        price: prodData?.price || 0
                    };
                });
            } catch (error) {
                console.error('Ошибка загрузки деталей сборки:', error);
            } finally {
                this.isLoadingDetails = false;
            }
        },

        formatPrice(price) {
            if (!price && price !== 0) return '0 ₽';
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price);
        },

        async incrementItem() {
            this.isProcessing = true;
            this.actionType = 'inc';
            this.$emit('increment', this.item);
            setTimeout(() => { this.isProcessing = false; this.actionType = null; }, 600);
        },

        async decrementItem() {
            if (this.item.count <= 1) return;
            this.isProcessing = true;
            this.actionType = 'dec';
            this.$emit('decrement', this.item);
            setTimeout(() => { this.isProcessing = false; this.actionType = null; }, 600);
        },

        // 🆕 Методы для модалки
        openDeleteModal() {
            this.showDeleteModal = true;
        },

        closeDeleteModal() {
            if (!this.isProcessing) {
                this.showDeleteModal = false;
            }
        },

        async confirmRemove() {
            this.isProcessing = true;
            this.actionType = 'remove';

            // Эмитим событие родителю для фактического удаления
            this.$emit('remove', this.item);

            // Закрываем модалку и сбрасываем состояние через небольшую задержку
            // (чтобы пользователь увидел спиннер, пока родитель обрабатывает запрос)
            setTimeout(() => {
                this.showDeleteModal = false;
                this.isProcessing = false;
                this.actionType = null;
            }, 600);
        }
    },
    created() {
        if (this.initialProducts.length > 0) {
            this.resolvedProducts = this.initialProducts;
        }
    }
};
</script>

<style lang="scss" scoped>
.collection-card-simple {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;

    &.is-expanded {
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    }
}

/* ========================================== */
/* ШАПКА */
/* ========================================== */
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
        background-color: #f9fafb;
    }
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.card-image {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    object-fit: cover;
    flex-shrink: 0;
    background-color: #f3f4f6;

    &.placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 1.2rem;
    }
}

.card-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.card-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-count {
    display: flex;
    align-items: center;
    gap: 8px;
}

.count-badge {
    background-color: #eff6ff;
    color: #3b82f6;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
}

.count-text {
    font-size: 0.75rem;
    color: #6b7280;
}

.header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    flex-shrink: 0;
    margin-left: 12px;
}

.card-total-price {
    font-size: 1.1rem;
    font-weight: 800;
    color: #1f2937;
}

.expand-indicator {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #3b82f6;
    font-weight: 600;
}

.expand-icon {
    transition: transform 0.3s ease;

    &.rotated {
        transform: rotate(180deg);
    }
}

/* ========================================== */
/* ТЕЛО КАРТОЧКИ (Детали) */
/* ========================================== */
.card-body {
    padding: 0 16px 16px 16px;
    border-top: 1px solid #f3f4f6;
}

.details-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 0 8px 0;
    font-size: 0.8rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    i {
        color: #3b82f6;
    }
}

.products-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
}

.product-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 10px 12px;
    background-color: #f9fafb;
    border-radius: 10px;
    border: 1px solid #f3f4f6;
}

.product-info {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    margin-right: 12px;
}

.product-category {
    font-size: 0.7rem;
    font-weight: 600;
    color: #3b82f6;
    margin-bottom: 2px;
}

.product-name {
    font-size: 0.85rem;
    color: #374151;
    line-height: 1.3;
    word-break: break-word;
}

.product-price {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
}

.empty-details {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    color: #9ca3af;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

/* ========================================== */
/* УПРАВЛЕНИЕ */
/* ========================================== */
.card-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px dashed #e5e7eb;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background-color: transparent;
    border: 1px solid #fee2e2;
    color: #ef4444;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        background-color: #fef2f2;
    }
}

.qty-controls {
    display: flex;
    align-items: center;
    background-color: #f3f4f6;
    border-radius: 10px;
    padding: 4px;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: none;
    background-color: #ffffff;
    color: #3b82f6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);

    &:hover:not(:disabled) {
        background-color: #3b82f6;
        color: #ffffff;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.qty-value {
    min-width: 32px;
    text-align: center;
    font-weight: 700;
    font-size: 0.95rem;
    color: #1f2937;
}

/* ========================================== */
/* АНИМАЦИИ */
/* ========================================== */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease-out;
    max-height: 500px;
    opacity: 1;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    max-height: 0;
    opacity: 0;
    margin-top: 0;
    overflow: hidden;
}

.loading-details {
    text-align: center;
    padding: 20px;
    color: #6b7280;
    i { margin-right: 8px; }
}

/* ========================================== */
/* 🆕 СТИЛИ МОДАЛКИ УДАЛЕНИЯ */
/* ========================================== */
.delete-modal-backdrop {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.delete-modal-content {
    background: #ffffff;
    border-radius: 20px;
    padding: 32px 24px 24px;
    max-width: 400px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalPop {
    from { opacity: 0; transform: scale(0.9) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-icon-wrapper {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background-color: #fef2f2;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.modal-text {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.5;
    margin: 0 0 24px 0;

    strong {
        color: #1f2937;
        font-weight: 600;
    }
}

.modal-actions {
    display: flex;
    gap: 12px;
}

.btn-cancel,
.btn-confirm {
    flex: 1;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-cancel {
    background-color: #f3f4f6;
    color: #374151;

    &:hover:not(:disabled) {
        background-color: #e5e7eb;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-confirm {
    background-color: #ef4444;
    color: #ffffff;

    &:hover:not(:disabled) {
        background-color: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
}

/* Анимация появления/исчезновения модалки */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
