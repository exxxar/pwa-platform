<template>
    <button
        class="hamburger-btn"
        :class="{ 'is-active': isActive }"
        :aria-label="isActive ? 'Закрыть меню' : 'Открыть меню'"
        :aria-expanded="isActive"
        :aria-controls="targetId"
        @click="$emit('toggle')"
        type="button"
    >
        <!-- Иконка меняется на крестик, когда меню открыто -->
        <i :class="isActive ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'" class="hamburger-icon"></i>
    </button>
</template>

<script setup>
defineProps({
    // Состояние: открыто меню или нет (для смены иконки и стиля)
    isActive: {
        type: Boolean,
        default: false,
    },
    // ID целевого элемента (например, sidebar) для доступности (a11y)
    targetId: {
        type: String,
        default: 'sidebar-menu',
    },
});

defineEmits(['toggle']);
</script>

<style scoped>
.hamburger-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 14px; /* Современное скругление */
    background: transparent;
    border: 2px solid transparent;
    color: var(--bs-body-color);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

/* Эффект при наведении */
.hamburger-btn:hover:not(:disabled) {
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
    border-color: rgba(var(--bs-primary-rgb), 0.2);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.15);
}

/* Эффект при нажатии */
.hamburger-btn:active:not(:disabled) {
    transform: translateY(0) scale(0.95);
}

/* Состояние: Меню открыто */
.hamburger-btn.is-active {
    background: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
    box-shadow: 0 6px 16px rgba(var(--bs-primary-rgb), 0.3);
}

.hamburger-btn.is-active:hover {
    background: var(--bs-primary-hover, var(--bs-primary));
    transform: translateY(-2px) scale(1.05);
}

/* Анимация иконки */
.hamburger-icon {
    font-size: 1.3rem;
    transition: transform 0.3s ease;
}

/* Небольшой поворот иконки при наведении для игривости */
.hamburger-btn:hover .hamburger-icon {
    transform: scale(1.1);
}

/* Плавная смена иконки с гамбургера на крестик */
.hamburger-btn.is-active .hamburger-icon {
    animation: iconSwap 0.3s ease;
}

@keyframes iconSwap {
    0% { transform: rotate(0deg) scale(1); }
    50% { transform: rotate(90deg) scale(0.8); }
    100% { transform: rotate(0deg) scale(1); }
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hamburger-btn {
        width: 44px;
        height: 44px;
        border-radius: 12px;
    }

    .hamburger-icon {
        font-size: 1.2rem;
    }
}
</style>
