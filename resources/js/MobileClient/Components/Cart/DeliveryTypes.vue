<template>
    <div v-if="deliveryForm" class="delivery-types">

        <!-- Основные способы -->
        <div class="types-grid">
            <button
                type="button"
                class="type-card"
                :class="{ 'active': !deliveryForm.need_pickup }"
                @click="deliveryForm.need_pickup = false"
            >
                <div class="type-icon">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <div class="type-info">
                    <div class="type-title">Доставка</div>
                    <div class="type-desc">Курьер привезёт заказ</div>
                </div>
                <div class="type-check">
                    <i class="fa-solid fa-check"></i>
                </div>
            </button>

            <button
                type="button"
                class="type-card"
                :class="{ 'active': deliveryForm.need_pickup }"
                @click="deliveryForm.need_pickup = true"
            >
                <div class="type-icon">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div class="type-info">
                    <div class="type-title">Самовывоз</div>
                    <div class="type-desc">Заберу сам в заведении</div>
                </div>
                <div class="type-check">
                    <i class="fa-solid fa-check"></i>
                </div>
            </button>
        </div>

        <!-- Подварианты самовывоза -->
        <transition name="slide-down">
            <div v-if="deliveryForm.need_pickup" class="pickup-options">
                <div class="options-label">Формат получения</div>
                <div class="options-grid">
                    <button
                        type="button"
                        class="option-btn"
                        :class="{ 'active': deliveryForm.pick_up_type == 0 }"
                        @click="deliveryForm.pick_up_type = 0"
                    >
                        <i class="fa-solid fa-utensils"></i>
                        <span>В заведении</span>
                    </button>
                    <button
                        type="button"
                        class="option-btn"
                        :class="{ 'active': deliveryForm.pick_up_type == 1 }"
                        @click="deliveryForm.pick_up_type = 1"
                    >
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span>С собой</span>
                    </button>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>
export default {
    name: "DeliveryTypes",

    props: {
        modelValue: {
            type: Object,
            required: true,
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            deliveryForm: null,
        };
    },

    watch: {
        deliveryForm: {
            handler(newValue) {
                this.$emit('update:modelValue', newValue);
            },
            deep: true,
        },
        modelValue: {
            handler(newValue) {
                this.deliveryForm = newValue;
            },
            deep: true,
        },
    },

    mounted() {
        this.deliveryForm = this.modelValue;
    },
};
</script>

<style scoped>
.delivery-types {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ==========================================
   СЕТКА ТИПОВ
   ========================================== */
.types-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.type-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px 12px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    overflow: hidden;
}

.type-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.1);
}

.type-card.active {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.2);
}

.type-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    transition: all 0.3s ease;
}

.type-card.active .type-icon {
    background: var(--bs-primary);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.type-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.type-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.type-desc {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.type-check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.type-card.active .type-check {
    opacity: 1;
    transform: scale(1);
}

/* ==========================================
   ПОДВАРИАНТЫ САМОВЫВОЗА
   ========================================== */
.pickup-options {
    padding: 16px;
    background: rgba(var(--bs-primary-rgb), 0.03);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.15);
    border-radius: 14px;
}

.options-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.option-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    color: var(--bs-body-color);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.option-btn:hover {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}

.option-btn.active {
    background: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.option-btn i {
    font-size: 1rem;
}

/* Анимация */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 200px;
}

/* Адаптив */
@media (max-width: 576px) {
    .type-card {
        padding: 16px 8px;
    }

    .type-icon {
        width: 44px;
        height: 44px;
        font-size: 1.1rem;
    }

    .type-title {
        font-size: 0.85rem;
    }

    .type-desc {
        font-size: 0.7rem;
    }
}
</style>
