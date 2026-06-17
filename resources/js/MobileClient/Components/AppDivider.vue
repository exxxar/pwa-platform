<template>
    <div class="app-divider" :class="`divider-${variant}`">

        <!-- Линия слева -->
        <div class="divider-line"></div>

        <!-- Центральный элемент (текст или иконка) -->
        <div v-if="text || icon" class="divider-content">
            <i v-if="icon" :class="icon" class="divider-icon"></i>
            <span v-if="text" class="divider-text">{{ text }}</span>
        </div>

        <!-- Линия справа -->
        <div class="divider-line"></div>

    </div>
</template>

<script>
export default {
    name: "AppDivider",

    props: {
        // Текст по центру (например, "ИЛИ", "КОНТАКТЫ")
        text: {
            type: String,
            default: ""
        },
        // Иконка FontAwesome по центру (например, "fa-solid fa-star")
        icon: {
            type: String,
            default: ""
        },
        // Вариант стиля: 'gradient' (по умолчанию), 'dotted', 'glow', 'simple'
        variant: {
            type: String,
            default: "gradient",
            validator: (value) => ["gradient", "dotted", "glow", "simple"].includes(value)
        }
    }
}
</script>

<style scoped>
.app-divider {
    display: flex;
    align-items: center;
    width: 100%;
    margin: 24px 0;
    gap: 16px;
}

/* ==========================================
   ВАРИАНТ 1: GRADIENT (Современный, с текстом)
   ========================================== */
.divider-gradient .divider-line {
    flex: 1;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent 0%,
        var(--bs-border-color) 50%,
        transparent 100%
    );
}

.divider-gradient .divider-content {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    white-space: nowrap;
}

.divider-gradient .divider-text {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.divider-gradient .divider-icon {
    color: var(--bs-primary);
    font-size: 0.9rem;
}

/* ==========================================
   ВАРИАНТ 2: DOTTED (Пунктирный, минималистичный)
   ========================================== */
.divider-dotted {
    margin: 16px 0;
}

.divider-dotted .divider-line {
    flex: 1;
    border-top: 2px dashed var(--bs-border-color-translucent);
}

.divider-dotted .divider-content {
    padding: 0 12px;
    color: var(--bs-secondary-color);
    font-size: 0.85rem;
}

/* ==========================================
   ВАРИАНТ 3: GLOW (С акцентным свечением)
   ========================================== */
.divider-glow .divider-line {
    flex: 1;
    height: 2px;
    background: var(--bs-border-color);
    border-radius: 2px;
    box-shadow: 0 0 8px rgba(var(--bs-primary-rgb), 0.3);
}

.divider-glow .divider-content {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--bs-primary);
    box-shadow: 0 0 12px rgba(var(--bs-primary-rgb), 0.6);
    flex-shrink: 0;
}

/* ==========================================
   ВАРИАНТ 4: SIMPLE (Простая линия с отступом)
   ========================================== */
.divider-simple {
    margin: 20px 0;
}

.divider-simple .divider-line {
    width: 100%;
    height: 1px;
    background: var(--bs-border-color);
}

.divider-simple .divider-content {
    display: none; /* Скрываем контент в простом режиме */
}

/* Адаптация под тёмную тему */
:root[data-bs-theme="dark"] .divider-gradient .divider-content {
    background: var(--bs-secondary-bg);
}
</style>
