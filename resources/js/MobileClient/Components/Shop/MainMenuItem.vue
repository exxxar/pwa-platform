<template>
    <button
        type="button"
        v-if="item?.is_visible || true"
        @click="goTo(route)"
        style="min-height:250px;"
        :disabled="disabled || false"
        class="theme-menu-btn btn shadow-sm border-0 btn-outline-primary w-100 mb-2 card p-0"
    >
        <div class="card-body p-0 d-flex justify-content-center align-items-center flex-column w-100">
            <img v-lazy="item?.img" class="img-fluid menu-item-img w-100" alt="">

            <slot name="counter"/>

            <p style="line-height:100%; text-transform:uppercase; font-size:12px;" class="my-2 px-3">
                {{ item?.title || defaultText }} <slot name="post-text"/>
            </p>

            <span style="font-size:12px;" v-if="disabled || false">
                <i class="fa-solid fa-lock"></i> закрыто
            </span>
        </div>
    </button>
</template>

<script>
export default {
    props: ["item", "defaultImage", "defaultText", "route", "disabled"],
    methods: {
        goTo(name) {
            if (name) {
                this.$router.push({ name: name });
            }
        },
    }
}
</script>

<style scoped>
/* Базовое изображение */
.menu-item-img {
    height: 160px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

/* 🆕 Динамические стили для кнопки, реагирующие на смену темы */
.theme-menu-btn {
    color: var(--bs-primary, #0d6efd);
    border: 1px solid var(--bs-primary, #0d6efd);
    background-color: transparent;
    transition: all 0.25s ease-in-out;
    overflow: hidden;
}

/* Эффект при наведении (Hover) */
.theme-menu-btn:hover:not(:disabled) {
    /* Используем RGB-переменную с прозрачностью 10% для мягкого фона */
    background-color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.1);
    color: var(--bs-primary, #0d6efd);
    border-color: var(--bs-primary, #0d6efd);

    /* Легкое поднятие и тень цвета темы */
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.15) !important;
}

/* Эффект при наведении на картинку внутри кнопки */
.theme-menu-btn:hover:not(:disabled) .menu-item-img {
    transform: scale(1.03);
}

/* Эффект при нажатии (Active) */
.theme-menu-btn:active:not(:disabled) {
    background-color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.2);
    color: var(--bs-primary, #0d6efd);
    border-color: var(--bs-primary, #0d6efd);
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.2) !important;
}

/* Эффект при фокусе (для доступности с клавиатуры) */
.theme-menu-btn:focus:not(:disabled) {
    background-color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.1);
    color: var(--bs-primary, #0d6efd);
    border-color: var(--bs-primary, #0d6efd);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb, 13, 110, 253), 0.25) !important;
}

/* Состояние "Закрыто" / Disabled */
.theme-menu-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: var(--bs-secondary-bg, #f8f9fa);
    color: var(--bs-secondary-color, #6c757d);
    border-color: var(--bs-border-color, #dee2e6);
    box-shadow: none !important;
    transform: none;
}

.theme-menu-btn:disabled .menu-item-img {
    filter: grayscale(100%);
    opacity: 0.7;
}
</style>
