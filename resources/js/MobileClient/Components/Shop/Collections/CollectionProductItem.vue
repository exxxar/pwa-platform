<template>
    <div
        class="collection-product-item"
        :class="{ 'is-selected': isSelected }"
        @click="$emit('select', product)"
    >
        <!-- Изображение с галочкой -->
        <div class="item-image-wrapper">
            <img v-if="product.images?.[0]?.url" v-lazy="product.images?.[0]?.url" class="item-image" loading="lazy" :alt="product.name">
            <div v-else class="item-image placeholder">
                <i class="fa-solid fa-image"></i>
            </div>

            <!-- Индикатор выбора -->
            <transition name="fade-scale">
                <div v-if="isSelected" class="selected-check">
                    <i class="fa-solid fa-check"></i>
                </div>
            </transition>
        </div>

        <!-- Информация -->
        <div class="item-info">
            <span class="item-name">{{ product.name }}</span>
            <span v-if="categoryName" class="item-category">{{ categoryName }}</span>
        </div>

        <!-- Цена -->
        <div class="item-price-block">
            <span class="item-price">{{ formatPrice(product.price) }}</span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CollectionProductItem',
    props: {
        product: {
            type: Object,
            required: true
        },
        isSelected: {
            type: Boolean,
            default: false
        },
        categoryName: {
            type: String,
            default: ''
        }
    },
    emits: ['select'],
    methods: {
        formatPrice(price) {
            if (!price && price !== 0) return '0 ₽';
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg-secondary: #f8f9fa;

.collection-product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #ffffff;
    border: 1px solid $border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        border-color: #cbd5e1;
        background: #fafafa;
    }

    // Состояние "Выбрано"
    &.is-selected {
        border-color: $primary;
        background: rgba(59, 130, 246, 0.04);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
    }
}

.item-image-wrapper {
    position: relative;
    flex-shrink: 0;
}

.item-image {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    background: $bg-secondary;

    &.placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        color: $text-muted;
        font-size: 1.2rem;
    }
}

.selected-check {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: $primary;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.item-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.item-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-category {
    font-size: 0.75rem;
    color: $text-muted;
}

.item-price-block {
    flex-shrink: 0;
    text-align: right;
}

.item-price {
    font-size: 0.95rem;
    font-weight: 700;
    color: $primary;
}

// Анимация появления галочки
.fade-scale-enter-active {
    transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.fade-scale-leave-active {
    transition: all 0.15s ease;
}
.fade-scale-enter-from,
.fade-scale-leave-to {
    opacity: 0;
    transform: scale(0.5);
}
</style>
