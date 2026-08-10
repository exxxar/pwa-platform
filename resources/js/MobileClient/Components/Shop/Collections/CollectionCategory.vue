<template>
    <div class="collection-category">
        <!-- Шапка категории с правилом -->
        <div class="category-header">
            <div class="category-title">
                <i class="fa-solid fa-folder"></i>
                <span>{{ category.category_name }}</span>
            </div>
            <div class="category-rule" :class="`rule-${rule}`">
                <i :class="ruleIcon"></i>
                <span>{{ ruleLabel }}</span>
            </div>
        </div>

        <!-- Подсказка -->
        <div class="category-hint">
            <template v-if="rule === 'one'">
                Выберите <b>один</b> товар из категории
            </template>
            <template v-else-if="rule === 'all'">
                В коллекцию входят <b>все</b> товары категории
            </template>
            <template v-else-if="rule === 'several'">
                Выберите <b>один или несколько</b> товаров
            </template>
        </div>

        <!-- Список товаров -->
        <div class="category-products">
            <div
                v-for="product in category.products || []"
                :key="product.id"
                class="product-option"
                :class="{
                    'is-selected': isSelected(product.id),
                    'is-disabled': isDisabled(product.id)
                }"
                @click="toggleProduct(product)"
            >
                <div class="option-checkbox">
                    <i v-if="isSelected(product.id)" class="fa-solid fa-check"></i>
                    <i v-else-if="rule === 'one'" class="fa-regular fa-circle"></i>
                    <i v-else class="fa-regular fa-square"></i>
                </div>

                <!-- ✅ ПРАВИЛЬНО: Извлекаем URL из объекта -->
                <img
                    v-lazy="product.images?.[0]?.url || '/no-image.png'"
                    :alt="product.name"
                    class="option-image"
                >

                <div class="option-info">
                    <span class="option-name">{{ product.name }}</span>
                    <span v-if="product.short_description" class="option-desc">
                        {{ product.short_description }}
                    </span>
                </div>

                <span class="option-price">{{ formatPrice(product.price) }}</span>
            </div>
        </div>

        <div v-if="!category.products?.length" class="category-empty">
            <i class="fa-solid fa-box-open"></i>
            <span>Товаров в категории пока нет</span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CollectionCategory',

    props: {
        category: {
            type: Object,
            required: true,
        },
        // Теперь ожидаем массив объектов товаров, а не просто ID
        selectedProducts: {
            type: Array,
            default: () => [],
        },
    },

    emits: ['update:selected'],

    computed: {
        rule() {
            return this.category.selection_rule || 'one';
        },

        ruleLabel() {
            const labels = {
                one: '1 из',
                all: 'Все',
                several: 'Несколько',
            };
            return labels[this.rule] || '1 из';
        },

        ruleIcon() {
            const icons = {
                one: 'fa-regular fa-circle-dot',
                all: 'fa-solid fa-list-check',
                several: 'fa-solid fa-list',
            };
            return icons[this.rule] || 'fa-circle-dot';
        },
    },

    methods: {
        // 🆕 ИСПРАВЛЕНИЕ: Приводим к строке для надежного сравнения Number и String
        isSelected(productId) {
            return this.selectedProducts.some(p => String(p.id) === String(productId));
        },

        isDisabled(productId) {
            return this.rule === 'all';
        },

        toggleProduct(product) {
            if (this.rule === 'all') return;

            // Работаем с массивом объектов
            let selected = [...this.selectedProducts];

            if (this.rule === 'one') {
                const isAlreadySelected = this.isSelected(product.id);
                // Если уже выбран, очищаем массив, иначе кладем туда весь объект товара
                selected = isAlreadySelected ? [] : [product];
            } else if (this.rule === 'several') {
                const index = selected.findIndex(p => String(p.id) === String(product.id));
                if (index !== -1) {
                    selected.splice(index, 1); // Убираем товар
                } else {
                    selected.push(product); // Добавляем весь объект товара (с partner_id, price и т.д.)
                }
            }

            this.$emit('update:selected', selected);
        },

        formatPrice(price) {
            if (!price && price !== 0) return '';
            return new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        },
    },
};
</script>
<style lang="scss" scoped>
@use 'sass:color';

$primary: #3b82f6;
$secondary: #8b5cf6;
$success: #10b981;
$warning: #ffc107;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg-secondary: #f8f9fa;

.collection-category {
    background: $bg-secondary;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid $border;
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: white;
    border-bottom: 1px solid $border;
}

.category-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: $text;
    font-size: 0.9rem;

    i { color: $secondary; }
}

.category-rule {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.3px;

    &.rule-one {
        background: rgba($primary, 0.1);
        color: $primary;
    }

    &.rule-all {
        background: rgba($success, 0.1);
        color: $success;
    }

    &.rule-several {
        background: rgba($warning, 0.1);
        color: darken($warning, 25%);
    }
}

.category-hint {
    padding: 8px 14px;
    font-size: 0.75rem;
    color: $text-muted;
    background: rgba($primary, 0.03);
    border-bottom: 1px solid $border;

    b { color: $text; }
}

.category-products {
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.product-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 10px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover:not(.is-disabled) {
        border-color: rgba($primary, 0.3);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.04);
    }

    &.is-disabled {
        cursor: default;
        opacity: 0.8;
    }
}

.option-checkbox {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: white;
    border: 2px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.2s ease;

    .is-selected & {
        background: $primary;
        border-color: $primary;
    }
}

.option-image {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.option-info {
    flex: 1;
    min-width: 0;
}

.option-name {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.option-desc {
    display: block;
    font-size: 0.7rem;
    color: $text-muted;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.option-price {
    font-size: 0.85rem;
    font-weight: 700;
    color: $primary;
    flex-shrink: 0;
}

.category-empty {
    padding: 30px 20px;
    text-align: center;
    color: $text-muted;

    i { font-size: 2rem; opacity: 0.3; margin-bottom: 8px; }
    span { display: block; font-size: 0.8rem; }
}
</style>
