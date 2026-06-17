<template>
    <div class="burger-summary-card">
        <div class="summary-header">
            <div class="header-icon">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h6 class="header-title">Состав вашего бургера</h6>
            <span class="header-count">{{ totalItemsCount }} поз.</span>
        </div>

        <div class="summary-list">
            <div
                v-for="item in sortedCartItems"
                :key="item.id"
                class="summary-item"
            >
                <div class="item-left">
                    <span class="item-emoji">{{ item.emoji }}</span>
                    <div class="item-info">
                        <span class="item-name">{{ item.name }}</span>
                        <span class="item-weight">{{ item.weight }} г</span>
                    </div>
                </div>
                <div class="item-right">
                    <span v-if="item.qty > 1" class="item-qty">×{{ item.qty }}</span>
                    <span class="item-price">{{ formatPrice(item.price * item.qty) }}</span>
                </div>
            </div>
        </div>

        <div class="summary-divider"></div>

        <div class="summary-footer">
            <div class="footer-row">
                <span class="footer-label">Общий вес:</span>
                <span class="footer-value">{{ totalWeight }} г</span>
            </div>
            <div class="footer-row total-row">
                <span class="footer-label">Итого к оплате:</span>
                <span class="footer-value total-value">{{ formatPrice(totalPrice) }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "BurgerSummary",

    props: {
        // Принимаем корзину (объект { id: qty }) и полный список ингредиентов для поиска данных
        cart: {
            type: Object,
            required: true
        },
        ingredients: {
            type: Array,
            required: true
        }
    },

    computed: {
        // Превращаем объект корзины в массив с полными данными и сортируем по категориям
        sortedCartItems() {
            const categoryOrder = ['buns', 'patties', 'cheese', 'veggies', 'sauces', 'extras'];

            return Object.entries(this.cart)
                .map(([id, qty]) => {
                    const item = this.ingredients.find(i => i.id === Number(id));
                    return item ? { ...item, qty } : null;
                })
                .filter(Boolean)
                .sort((a, b) => {
                    const indexA = categoryOrder.indexOf(a.category);
                    const indexB = categoryOrder.indexOf(b.category);
                    return indexA - indexB;
                });
        },

        totalItemsCount() {
            return Object.values(this.cart).reduce((sum, qty) => sum + qty, 0);
        },

        totalWeight() {
            return this.sortedCartItems.reduce((sum, item) => sum + (item.weight * item.qty), 0);
        },

        totalPrice() {
            return this.sortedCartItems.reduce((sum, item) => sum + (item.price * item.qty), 0);
        }
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price);
        }
    }
};
</script>

<style scoped>
.burger-summary-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
}

/* Шапка сводки */
.summary-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.header-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ff9a9e 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.header-title {
    flex: 1;
    margin: 0;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.header-count {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    background: var(--bs-secondary-bg);
    padding: 4px 8px;
    border-radius: 12px;
}

/* Список ингредиентов */
.summary-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.summary-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.item-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
}

.item-emoji {
    font-size: 1.3rem;
    flex-shrink: 0;
}

.item-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.item-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-weight {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.item-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.item-qty {
    font-size: 0.75rem;
    font-weight: 700;
    color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
    padding: 2px 6px;
    border-radius: 6px;
}

.item-price {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    min-width: 60px;
    text-align: right;
}

/* Разделитель */
.summary-divider {
    height: 1px;
    background: var(--bs-border-color);
    margin: 16px 0;
    border-top: 1px dashed var(--bs-border-color);
}

/* Подвал сводки */
.summary-footer {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.footer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer-label {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

.footer-value {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.total-row {
    margin-top: 4px;
}

.total-row .footer-label {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
}

.total-value {
    font-size: 1.3rem;
    font-weight: 800;
    color: #ff6b6b;
}
</style>
