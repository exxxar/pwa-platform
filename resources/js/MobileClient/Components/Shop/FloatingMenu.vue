<template>
    <div class="floating-menu-wrapper" :class="{ 'is-open': isOpen }">

        <!-- 🆕 Backdrop при открытом меню -->
        <transition name="fade">
            <div
                v-if="isOpen"
                class="floating-backdrop"
                @click="close"
            ></div>
        </transition>

        <!-- 🆕 Раскрывающиеся кнопки (снизу вверх) -->
        <transition-group
            name="menu-item"
            tag="div"
            class="floating-items"
        >
            <template v-if="isOpen">
                <div
                    v-for="(item, index) in items"
                    :key="item.key"
                    class="floating-item"
                    :style="{ transitionDelay: `${index * 0.05}s` }"
                    @click="handleItemClick(item)"
                >
                    <!-- Подпись слева -->
                    <div class="item-label">
                        <span>{{ item.label }}</span>
                        <span v-if="item.badge" class="item-badge">
                            {{ item.badge }}
                        </span>
                    </div>

                    <!-- Кнопка -->
                    <button
                        class="item-btn"
                        :style="{ background: item.color }"
                        :title="item.label"
                    >
                        <i :class="item.icon"></i>
                    </button>
                </div>
            </template>
        </transition-group>

        <!-- 🆕 Главный триггер (красный кружочек со стрелками) -->
        <button
            class="floating-trigger"
            :class="{ 'is-active': isOpen }"
            @click="toggle"
            :aria-label="isOpen ? 'Закрыть меню' : 'Открыть меню'"
        >
            <div class="trigger-icon">
                <i class="fa-solid fa-angles-up"></i>
            </div>

            <!-- Пульсирующий эффект -->
            <span v-if="!isOpen && hasUnread" class="pulse-ring"></span>
        </button>

    </div>
</template>

<script>
export default {
    name: 'FloatingMenu',

    props: {
        items: {
            type: Array,
            required: true,
            // Пример:
            // [
            //   { key: 'favorites', label: 'Избранное', icon: 'fa-solid fa-heart', color: '#ef4444', badge: 3, action: () => {} },
            //   { key: 'coffee', label: 'Кофе в подарок', icon: 'fa-solid fa-mug-hot', color: '#8b5cf6', action: () => {} },
            // ]
        },
        hasUnread: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['open', 'close', 'item-click'],

    data() {
        return {
            isOpen: false,
        };
    },

    mounted() {
        // Закрытие по Escape
        document.addEventListener('keydown', this.handleKeydown);
    },

    beforeUnmount() {
        document.removeEventListener('keydown', this.handleKeydown);
    },

    methods: {
        toggle() {
            this.isOpen = !this.isOpen;
            this.$emit(this.isOpen ? 'open' : 'close');
        },

        close() {
            if (this.isOpen) {
                this.isOpen = false;
                this.$emit('close');
            }
        },

        handleItemClick(item) {
            this.$emit('item-click', item);
            if (item.action) {
                item.action();
            }
            // Закрываем меню после клика
            this.close();
        },

        handleKeydown(e) {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        },
    },
};
</script>

<style lang="scss" scoped>
$trigger-size: 56px;
$item-size: 48px;
$item-gap: 12px;
$primary-red: #ef4444;
$primary-red-dark: #dc2626;

.floating-menu-wrapper {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: $item-gap;
}

// ==========================================
// 🆕 BACKDROP
// ==========================================
.floating-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(2px);
    z-index: -1;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

// ==========================================
// 🆕 РАСКРЫВАЮЩИЕСЯ КНОПКИ
// ==========================================
.floating-items {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: $item-gap;
}

.floating-item {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    animation: itemAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
}

@keyframes itemAppear {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.item-label {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    font-size: 0.85rem;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    animation: labelSlide 0.3s ease backwards;
    animation-delay: inherit;
}

@keyframes labelSlide {
    from {
        opacity: 0;
        transform: translateX(10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.item-badge {
    padding: 2px 8px;
    background: $primary-red;
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
}

.item-btn {
    width: $item-size;
    height: $item-size;
    border-radius: 50%;
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease;
    flex-shrink: 0;

    &:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    }

    &:active {
        transform: scale(0.95);
    }
}

// ==========================================
// 🆕 ГЛАВНЫЙ ТРИГГЕР
// ==========================================
.floating-trigger {
    position: relative;
    width: $trigger-size;
    height: $trigger-size;
    border-radius: 50%;
    background: linear-gradient(135deg, $primary-red 0%, $primary-red-dark 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 6px 20px rgba($primary-red, 0.4),
        0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    z-index: 10;

    &:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow:
            0 8px 28px rgba($primary-red, 0.5),
            0 4px 12px rgba(0, 0, 0, 0.2);
    }

    &:active {
        transform: translateY(0) scale(0.95);
    }

    &.is-active {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        box-shadow:
            0 6px 20px rgba(0, 0, 0, 0.3),
            0 2px 8px rgba(0, 0, 0, 0.15);
    }
}

.trigger-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);

    .is-active & {
        transform: rotate(180deg);
    }
}

// 🆕 Пульсирующий эффект при наличии непрочитанного
.pulse-ring {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid $primary-red;
    animation: pulseRing 2s ease-out infinite;
    pointer-events: none;
}

@keyframes pulseRing {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(1.4);
        opacity: 0;
    }
}

// ==========================================
// 🆕 АНИМАЦИЯ TRANSITION-GROUP
// ==========================================
.menu-item-enter-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.menu-item-leave-active {
    transition: all 0.2s ease-in;
}

.menu-item-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.8);
}

.menu-item-leave-to {
    opacity: 0;
    transform: translateY(10px) scale(0.9);
}

// ==========================================
// 🆕 АДАПТИВ
// ==========================================
@media (max-width: 576px) {
    .floating-menu-wrapper {
        bottom: 87px;
        right: 16px;
    }

    .floating-trigger {
        width: 52px;
        height: 52px;
    }

    .item-btn {
        width: 44px;
        height: 44px;
        font-size: 1rem;
    }

    .item-label {
        font-size: 0.8rem;
        padding: 6px 12px;
    }
}
</style>
