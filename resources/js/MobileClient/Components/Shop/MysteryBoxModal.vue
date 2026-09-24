
<template>
    <transition name="fade">
        <div v-if="isVisible" class="mystery-box-overlay" @click.self="close">
            <transition name="slide-up">
                <div v-if="isVisible" class="mystery-box-sheet">
                    <div class="sheet-handle"></div>

                    <div class="sheet-header">
                        <h4>🎁 Анонимный бокс</h4>
                        <button class="close-btn" @click="close">&times;</button>
                    </div>

                    <div class="sheet-body">
                        <p class="description">
                            Хочешь сюрприз? Мы соберем для тебя случайный пакет из вкусных товаров
                            ровно на выбранную сумму. Состав бокса ты узнаешь только в момент получения!
                        </p>

                        <div class="price-grid">
                            <div
                                v-for="option in priceOptions"
                                :key="option.value"
                                class="price-card"
                                :class="{ active: selectedPrice === option.value }"
                                @click="selectedPrice = option.value"
                            >
                                <div class="price-value">{{ option.value }} ₽</div>
                                <div class="price-label">{{ option.label }}</div>
                            </div>
                        </div>

                        <button
                            class="btn-add-to-cart"
                            :class="{ disabled: !selectedPrice || isLoading }"
                            @click="addToCart"
                        >
                            <span v-if="!isLoading">Добавить в корзину</span>
                            <span v-else class="spinner"></span>
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </transition>
</template>

<script>
export default {
    name: 'MysteryBoxModal',
    props: {
        isVisible: { type: Boolean, default: false },
    },
    emits: ['close', 'add-to-cart'],
    data() {
        return {
            priceOptions: [
                { value: 1000, label: 'Базовый' },
                { value: 2000, label: 'Оптимальный' },
                { value: 3000, label: 'Премиум' },
                { value: 5000, label: 'Максимальный' }
            ],
            selectedPrice: null,
            isLoading: false,
        };
    },
    watch: {
        isVisible(val) {
            if (val) {
                this.selectedPrice = null;
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    },
    methods: {
        close() { this.$emit('close'); },
        async addToCart() {
            if (!this.selectedPrice || this.isLoading) return;
            this.isLoading = true;
            this.$emit('add-to-cart', this.selectedPrice);
            setTimeout(() => { this.isLoading = false; }, 2000);
        }
    },
    beforeUnmount() { document.body.style.overflow = ''; }
};
</script>

<style scoped>
.mystery-box-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.6); z-index: 1050;
    display: flex; align-items: flex-end; justify-content: center;
}
.mystery-box-sheet {
    background: #fff; width: 100%; max-width: 500px;
    border-radius: 24px 24px 0 0; padding: 16px 20px 24px;
    box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
}
.sheet-handle { width: 40px; height: 4px; background: #e0e0e0; border-radius: 2px; margin: 0 auto 16px; }
.sheet-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.sheet-header h4 { margin: 0; font-weight: 700; font-size: 1.25rem; }
.close-btn { background: none; border: none; font-size: 1.5rem; line-height: 1; color: #666; cursor: pointer; }
.description { color: #666; font-size: 0.95rem; margin-bottom: 20px; line-height: 1.4; }
.price-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
.price-card {
    border: 2px solid #eee; border-radius: 16px; padding: 16px; text-align: center;
    cursor: pointer; transition: all 0.2s ease; background: #fafafa;
}
.price-card.active {
    border-color: var(--bs-primary, #007bff); background: rgba(0, 123, 255, 0.05);
    transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,123,255,0.15);
}
.price-value { font-size: 1.5rem; font-weight: 700; color: #333; }
.price-label { font-size: 0.85rem; color: #888; margin-top: 4px; }
.btn-add-to-cart {
    width: 100%; padding: 14px; border-radius: 12px;
    background: linear-gradient(135deg, #ff416c, #ff4b2b); color: white;
    border: none; font-size: 1.1rem; font-weight: 600; cursor: pointer;
    box-shadow: 0 4px 15px rgba(255, 75, 43, 0.4); transition: transform 0.1s;
}
.btn-add-to-cart:active { transform: scale(0.98); }
.btn-add-to-cart.disabled { background: #ccc; box-shadow: none; cursor: not-allowed; }
.spinner {
    display: inline-block; width: 20px; height: 20px;
    border: 3px solid rgba(255,255,255,.3); border-radius: 50%;
    border-top-color: white; animation: spin 1s ease-in-out infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
