<template>
    <button
        class="edit-profile-btn"
        :class="[`variant-${variant}`, { 'is-loading': loading, 'is-disabled': disabled }]"
        :disabled="disabled || loading"
        @click="$emit('click')"
        type="button"
    >
        <!-- Иконка или спиннер загрузки -->
        <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status">
            <span class="visually-hidden">Загрузка...</span>
        </span>
        <i v-else :class="icon" class="btn-icon"></i>

        <!-- Текст -->
        <span class="btn-text">{{ label }}</span>
    </button>
</template>

<script setup>
const props = defineProps({
    // Текст кнопки
    label: {
        type: String,
        default: 'Редактировать профиль'
    },
    // Иконка FontAwesome
    icon: {
        type: String,
        default: 'fa-solid fa-pen-to-square'
    },
    // Вариант стиля: 'primary' (залитая), 'outline' (контурная), 'ghost' (прозрачная)
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'outline', 'ghost'].includes(value)
    },
    // Состояние загрузки (показывает спиннер и блокирует кнопку)
    loading: {
        type: Boolean,
        default: false
    },
    // Полная блокировка кнопки
    disabled: {
        type: Boolean,
        default: false
    }
});

defineEmits(['click']);
</script>

<style scoped>
.edit-profile-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px 24px;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 12px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.btn-icon {
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

/* ==========================================
   ВАРИАНТ: PRIMARY (Залитая, основная)
   ========================================== */
.variant-primary {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.3);
}

.variant-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.4);
}

.variant-primary:hover:not(:disabled) .btn-icon {
    transform: rotate(-10deg) scale(1.1);
}

.variant-primary:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.3);
}

/* ==========================================
   ВАРИАНТ: OUTLINE (Контурная)
   ========================================== */
.variant-outline {
    background: transparent;
    color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.variant-outline:hover:not(:disabled) {
    background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.15);
}

.variant-outline:active:not(:disabled) {
    transform: translateY(0);
    background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.1);
}

/* ==========================================
   ВАРИАНТ: GHOST (Прозрачная, для inline)
   ========================================== */
.variant-ghost {
    background: transparent;
    color: var(--bs-primary);
    padding: 8px 16px;
    width: auto;
    font-size: 0.9rem;
}

.variant-ghost:hover:not(:disabled) {
    background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.08);
}

/* ==========================================
   СОСТОЯНИЯ
   ========================================== */
.edit-profile-btn:disabled,
.edit-profile-btn.is-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

/* Адаптация под тёмную тему */
:root[data-bs-theme="dark"] .variant-outline {
    border-color: rgba(255, 255, 255, 0.2);
    color: var(--bs-primary);
}

:root[data-bs-theme="dark"] .variant-outline:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.05);
    border-color: var(--bs-primary);
}
</style>
