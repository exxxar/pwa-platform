<template>
    <transition name="slide">
        <div class="cart-overlay" @click.self="$emit('close')">
            <div class="cart-drawer">
                <div class="cart-header">
                    <h3>{{ config.title }}</h3>
                    <button class="close-btn" @click="$emit('close')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="cart-body">
                    <div v-if="items.length === 0" class="cart-empty">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <p>{{ config.emptyText }}</p>
                    </div>

                    <template v-else>
                        <!-- Список товаров -->
                        <div class="cart-items">
                            <div v-for="item in items" :key="item.id" class="cart-item">
                                <img :src="item.image" :alt="item.name" class="item-image">
                                <div class="item-info">
                                    <div class="item-name">{{ item.name }}</div>
                                    <div class="item-price">{{ item.price }} ₽ × {{ item.quantity }}</div>
                                </div>
                                <div class="item-controls">
                                    <button class="qty-btn" @click="$emit('update-qty', item.id, -1)">−</button>
                                    <span>{{ item.quantity }}</span>
                                    <button class="qty-btn" @click="$emit('update-qty', item.id, 1)">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- Форма оформления заказа -->
                        <div class="checkout-form">
                            <h4>Данные для заказа</h4>
                            <div class="form-group">
                                <label>Ваше имя</label>
                                <input type="text" v-model="formData.name" placeholder="Иван" required>
                            </div>
                            <div class="form-group">
                                <label>Телефон</label>
                                <input type="tel" v-model="formData.phone" placeholder="+7 (999) 000-00-00" required>
                            </div>
                            <div class="form-group">
                                <label>Способ получения</label>
                                <select v-model="formData.type">
                                    <option value="delivery">Доставка</option>
                                    <option value="pickup">Самовывоз</option>
                                </select>
                            </div>
                            <div class="form-group" v-if="formData.type === 'delivery'">
                                <label>Адрес доставки</label>
                                <input type="text" v-model="formData.address" placeholder="Улица, дом, квартира">
                            </div>
                            <div class="form-group">
                                <label>Комментарий к заказу</label>
                                <textarea v-model="formData.comment" rows="2" placeholder="Например: без лука, код домофона 123"></textarea>
                            </div>
                        </div>
                    </template>
                </div>

                <div v-if="items.length > 0" class="cart-footer">
                    <div class="cart-total">
                        <span>{{ config.totalText }}</span>
                        <span class="total-value">{{ totalPrice }} ₽</span>
                    </div>
                    <button class="checkout-btn" @click="submitOrder" :disabled="!isFormValid">
                        {{ config.checkoutText }}
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>
<script>
export default {
    name: "ShopCart",
    props: {
        items: { type: Array, default: () => [] },
        config: { type: Object, default: () => ({}) }
    },
    emits: ['close', 'update-qty', 'checkout'],
    data() {
        return {
            formData: {
                name: '',
                phone: '',
                type: 'delivery',
                address: '',
                comment: ''
            }
        };
    },
    computed: {
        totalPrice() {
            return this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        },
        isFormValid() {
            return this.formData.name.trim() && this.formData.phone.trim() &&
                (this.formData.type === 'pickup' || this.formData.address.trim());
        }
    },
    methods: {
        submitOrder() {
            if (!this.isFormValid) return;

            // Отдаем данные заказа наверх вместе с товарами
            this.$emit('checkout', {
                items: this.items,
                total: this.totalPrice,
                customer: { ...this.formData }
            });

            // Сброс формы
            this.formData = { name: '', phone: '', type: 'delivery', address: '', comment: '' };
        }
    }
};
</script>


<style lang="scss" scoped>
.cart-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    justify-content: flex-end;
}

.cart-drawer {
    width: 100%;
    max-width: 450px;
    background: white;
    display: flex;
    flex-direction: column;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

.cart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);

    h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }
}

.close-btn {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray);
    transition: color 0.3s ease;

    &:hover {
        color: var(--dark);
    }
}

.cart-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.cart-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--gray);

    i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--light);
    border-radius: 12px;
}

.item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    margin-bottom: 0.3rem;
}

.item-price {
    font-size: 0.9rem;
    color: var(--gray);
}

.remove-btn {
    background: transparent;
    border: none;
    color: var(--gray);
    cursor: pointer;
    transition: color 0.3s ease;

    &:hover {
        color: #ef4444;
    }
}

.cart-footer {
    padding: 1.5rem;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.total-value {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--primary);
}

.checkout-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 122, 0, 0.25);
    }
}

.slide-enter-active, .slide-leave-active {
    transition: opacity 0.3s ease;
}

.slide-enter-from, .slide-leave-to {
    opacity: 0;
}

/* Добавляем стили для формы */
.checkout-form {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px dashed rgba(0,0,0,0.1);

    h4 { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; }
}

.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--dark); }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 10px 12px; border: 1px solid rgba(0,0,0,0.1);
    border-radius: 10px; font-size: 0.95rem; background: var(--light);
    &:focus { outline: none; border-color: var(--primary); }
}

.item-controls { display: flex; align-items: center; gap: 8px; background: var(--light); border-radius: 8px; padding: 2px; }
.qty-btn { width: 28px; height: 28px; border: none; background: transparent; font-weight: 700; cursor: pointer; border-radius: 6px; }
.qty-btn:hover { background: white; }

.checkout-btn {
    width: 100%; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white; border: none; padding: 1rem; border-radius: 12px; font-weight: 700; font-size: 1.1rem;
    cursor: pointer; transition: all 0.3s ease;
    &:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255, 122, 0, 0.25); }
    &:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
}

.slide-enter-active, .slide-leave-active { transition: opacity 0.3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; }
</style>
