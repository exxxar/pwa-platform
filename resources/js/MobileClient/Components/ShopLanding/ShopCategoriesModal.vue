<template>
    <!-- 🆕 Teleport выносит модалку прямо в <body>, минуя все родительские z-index -->
    <Teleport to="body">
        <div class="modal-overlay" @click.self="$emit('close')">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Все категории меню</h3>
                    <button class="close-btn" @click="$emit('close')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-grid">
                    <button
                        v-for="cat in validCategories"
                        :key="cat.id"
                        class="modal-category-card"
                        @click="$emit('select', cat.id)"
                    >
                        <div class="card-icon">
                            <i :class="cat.icon || 'fa-solid fa-utensils'"></i>
                        </div>
                        <div class="card-info">
                            <span class="card-name">{{ cat.name }}</span>
                            <span class="card-count">{{ cat.products_count || cat.products?.length || 0 }} товаров</span>
                        </div>
                        <i class="fa-solid fa-chevron-right card-arrow"></i>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
export default {
    name: "ShopCategoriesModal",
    props: {
        categories: {
            type: Array,
            required: true
        }
    },
    emits: ['close', 'select'],
    computed: {
        validCategories() {
            return this.categories.filter(cat => cat.products && cat.products.length > 0);
        }
    },
    mounted() {
        // 🆕 Блокируем скролл body при открытии модалки
        document.body.style.overflow = 'hidden';
        // Закрываем по Escape
        document.addEventListener('keydown', this.handleEsc);
    },
    beforeUnmount() {
        // 🆕 Возвращаем скролл при закрытии
        document.body.style.overflow = '';
        document.removeEventListener('keydown', this.handleEsc);
    },
    methods: {
        handleEsc(e) {
            if (e.key === 'Escape') {
                this.$emit('close');
            }
        }
    }
}
</script>

<style lang="scss" scoped>
/* 🆕 КРИТИЧНО: z-index заоблачный, чтобы быть поверх всего */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 15, 20, 0.7);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 999999; /* Максимальный z-index */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.25s ease-out;
}

.modal-content {
    background: white;
    border-radius: 24px;
    width: 100%;
    max-width: 800px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.4);
    animation: slideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    z-index: 1000000; /* Ещё выше, на всякий случай */
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 32px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    flex-shrink: 0;

    h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dark, #222);
    }

    .close-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: var(--light, #f8f9fa);
        color: var(--dark, #222);
        font-size: 1.2rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;

        &:hover {
            background: #fee2e2;
            color: #ef4444;
            transform: rotate(90deg);
        }
    }
}

.modal-grid {
    padding: 24px 32px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}

.modal-category-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: white;
    border: 2px solid rgba(0,0,0,0.06);
    border-radius: 16px;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s ease;

    &:hover {
        border-color: var(--primary, #ff7a00);
        background: rgba(255, 122, 0, 0.03);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);

        .card-icon {
            background: var(--primary, #ff7a00);
            color: white;
        }
        .card-arrow {
            color: var(--primary, #ff7a00);
            transform: translateX(4px);
        }
    }
}

.card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--light, #f8f9fa);
    color: var(--primary, #ff7a00);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.card-info {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    min-width: 0; /* Важно для обрезки длинных названий */
}

.card-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--dark, #222);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-count {
    font-size: 0.85rem;
    color: var(--gray, #888);
}

.card-arrow {
    color: var(--gray, #ccc);
    transition: all 0.2s ease;
    font-size: 0.9rem;
    flex-shrink: 0;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* 🆕 Мобильная версия — модалка снизу как action sheet */
@media (max-width: 600px) {
    .modal-overlay {
        padding: 0;
        align-items: flex-end;
    }

    .modal-content {
        max-height: 85vh;
        border-radius: 24px 24px 0 0;
        animation: slideUpMobile 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .modal-grid {
        grid-template-columns: 1fr;
        padding: 16px;
    }

    .modal-header {
        padding: 20px;
    }

    @keyframes slideUpMobile {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
}
</style>
