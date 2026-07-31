<template>
    <nav class="shop-navbar" :class="{ 'scrolled': isScrolled }">
        <div class="container">
            <div class="navbar-content">
                <!-- Бренд и статус работы -->
                <div class="navbar-brand-wrapper">
                    <div class="navbar-brand">
                        <i class="fa-solid fa-store"></i>
                        <span>{{ tenantName }}</span>
                    </div>

                    <!-- Индикатор статуса (кликабельный) -->
                    <div class="status-badge" :class="{ 'is-open': isCurrentlyOpen }" @click="showScheduleModal = true"
                         title="Нажмите, чтобы посмотреть график работы">
                        <i :class="isCurrentlyOpen ? 'fa-solid fa-door-open' : 'fa-solid fa-door-closed'"></i>
                        <span class="status-text">{{ isCurrentlyOpen ? 'Открыто' : 'Закрыто' }}</span>
                    </div>
                </div>

                <!-- Действия -->
                <div class="navbar-actions">
                    <button class="nav-btn" @click="$emit('open-feedback')">
                        <i class="fa-solid fa-phone"></i>
                        <span class="btn-text">Связаться</span>
                    </button>
                    <button class="nav-btn cart-btn" @click="$emit('open-cart')">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="btn-text">Корзина</span>
                        <span v-if="cartTotalCount > 0" class="cart-badge">
                            {{ cartTotalCount }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
        <Teleport to="body">
            <!-- 🆕 Модалка с графиком работы -->
            <transition name="modal-fade">
                <div v-if="showScheduleModal" class="schedule-modal-overlay" @click.self="showScheduleModal = false"
                     @keydown.esc="showScheduleModal = false">
                    <div class="schedule-modal-content" tabindex="0" @keydown.esc="showScheduleModal = false">
                        <div class="modal-header">
                            <h3><i class="fa-regular fa-clock"></i> Режим работы</h3>
                            <button class="modal-close" @click="showScheduleModal = false">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div
                                v-for="(day, index) in schedule"
                                :key="index"
                                class="schedule-row"
                                :class="{ 'is-today': index === currentDayIndex }"
                            >
                            <span class="day-name">
                                {{ day.day }}
                                <span v-if="index === currentDayIndex" class="today-badge">Сегодня</span>
                            </span>
                                <span class="day-time" :class="{ 'is-closed': day.closed }">
                                <template v-if="day.closed">
                                    <i class="fa-solid fa-ban"></i> {{ day.closed_comment || 'Выходной' }}
                                </template>
                                <template v-else>
                                    {{ day.start_at }} — {{ day.end_at }}
                                </template>
                            </span>
                            </div>
                        </div>

                        <div class="modal-footer" v-if="!isCurrentlyOpen && todayScheduleComment">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>{{ todayScheduleComment }}</span>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </nav>
</template>

<script>
import {useBasket} from '@/MobileClient/composables/useBasket';

export default {
    name: "ShopNavbar",

    emits: ['open-cart', 'open-feedback'],

    setup() {
        return {
            basket: useBasket()
        };
    },

    data() {
        return {
            isScrolled: false,
            showScheduleModal: false,
        };
    },

    computed: {
        // 🎯 Берем данные напрямую из глобального объекта
        tenant() {
            return window.Tenant || {};
        },

        tenantName() {
            return this.tenant.name || this.tenant.settings?.company?.name || 'Магазин';
        },

        schedule() {
            return this.tenant.settings?.company?.schedule || [];
        },

        cartTotalCount() {
            return this.basket.cartTotalCount?.value || 0;
        },

        // 🎯 Вычисляем, открыт ли магазин прямо сейчас
        isCurrentlyOpen() {
            const schedule = this.schedule;
            if (!schedule || !Array.isArray(schedule) || schedule.length === 0) return true; // Если графика нет, считаем открытым

            const now = new Date();
            const jsDay = now.getDay(); // 0 = Воскресенье, 1 = Понедельник, ..., 6 = Суббота

            // Преобразуем в индекс массива (где 0 = Понедельник, ..., 6 = Воскресенье)
            const arrayIndex = jsDay === 0 ? 6 : jsDay - 1;
            const todaySchedule = schedule[arrayIndex];

            if (!todaySchedule || todaySchedule.closed) return false;

            // Переводим текущее время и время работы в минуты для удобного сравнения
            const currentMinutes = now.getHours() * 60 + now.getMinutes();
            const [startH, startM] = todaySchedule.start_at.split(':').map(Number);
            const [endH, endM] = todaySchedule.end_at.split(':').map(Number);

            const startMinutes = startH * 60 + startM;
            const endMinutes = endH * 60 + endM;

            return currentMinutes >= startMinutes && currentMinutes <= endMinutes;
        },

        currentDayIndex() {
            const jsDay = new Date().getDay();
            return jsDay === 0 ? 6 : jsDay - 1;
        },

        todayScheduleComment() {
            const today = this.schedule[this.currentDayIndex];
            return today?.closed ? today.closed_comment : 'Мы сейчас не работаем. Загляните к нам в следующее время!';
        }
    },

    mounted() {
        window.addEventListener('scroll', this.handleScroll);
    },

    beforeUnmount() {
        window.removeEventListener('scroll', this.handleScroll);
        // Блокируем скролл страницы, когда открыта модалка
        if (this.showScheduleModal) {
            document.body.style.overflow = '';
        }
    },

    watch: {
        showScheduleModal(newVal) {
            // Блокируем скролл фона при открытой модалке
            document.body.style.overflow = newVal ? 'hidden' : '';
        }
    },

    methods: {
        handleScroll() {
            this.isScrolled = window.scrollY > 50;
        },
    },
};
</script>

<style lang="scss" scoped>
// Переменные (используем те, что есть в проекте, или фоллбэки)
$primary: var(--primary, #ff8a00);
$primary-light: var(--primary-light, #ffb347);
$dark: var(--dark, #1f2937);
$success: #10b981;
$danger: #ef4444;
$gray: var(--gray, #6b7280);

.shop-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    padding: 1rem 0;
    transition: all 0.3s ease;
    z-index: 1000;

    &.scrolled {
        padding: 0.6rem 0;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    }
}

.navbar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.navbar-brand-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.navbar-brand {
    font-weight: 900;
    font-size: 1.4rem;
    color: $dark;
    display: flex;
    align-items: center;
    gap: 0.5rem;

    i {
        color: $primary;
    }
}

// 🆕 Индикатор статуса
.status-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba($danger, 0.1);
    color: $danger;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid rgba($danger, 0.2);

    &:hover {
        background: rgba($danger, 0.15);
        transform: translateY(-1px);
    }

    &.is-open {
        background: rgba($success, 0.1);
        color: $success;
        border-color: rgba($success, 0.2);

        &:hover {
            background: rgba($success, 0.15);
        }
    }
}

.navbar-actions {
    display: flex;
    gap: 0.8rem;
}

.nav-btn {
    background: transparent;
    border: 1px solid rgba(0, 0, 0, 0.1);
    padding: 0.6rem 1.2rem;
    border-radius: 50px;
    color: $dark;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;

    &:hover {
        border-color: $primary;
        color: $primary;
    }
}

.cart-btn {
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    border: none;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba($primary, 0.25);
        color: white;
    }
}

.cart-badge {
    background: white;
    color: $primary;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    animation: popIn 0.3s ease;
}

@keyframes popIn {
    0% {
        transform: scale(0);
    }
    70% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

// ==========================================
// 🆕 СТИЛИ МОДАЛКИ ГРАФИКА РАБОТЫ
// ==========================================
.schedule-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.schedule-modal-content {
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);

    h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: $dark;
        display: flex;
        align-items: center;
        gap: 8px;

        i {
            color: $primary;
        }
    }
}

.modal-close {
    background: rgba(0, 0, 0, 0.05);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: $gray;
    transition: all 0.2s;

    &:hover {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.modal-body {
    padding: 8px 24px;
    max-height: 60vh;
    overflow-y: auto;
}

.schedule-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border-radius: 12px;
    transition: background 0.2s;
    margin-bottom: 4px;

    &.is-today {
        background: rgba($primary, 0.08);
        border: 1px solid rgba($primary, 0.15);
    }

    &:hover:not(.is-today) {
        background: rgba(0, 0, 0, 0.02);
    }
}

.day-name {
    font-weight: 600;
    color: $dark;
    display: flex;
    align-items: center;
    gap: 8px;
}

.today-badge {
    font-size: 0.65rem;
    background: $primary;
    color: white;
    padding: 2px 8px;
    border-radius: 50px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.day-time {
    font-weight: 500;
    color: $gray;
    font-size: 0.95rem;

    &.is-closed {
        color: $danger;
        display: flex;
        align-items: center;
        gap: 6px;
    }
}

.modal-footer {
    padding: 16px 24px;
    background: rgba($danger, 0.05);
    border-top: 1px solid rgba($danger, 0.1);
    color: $danger;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;

    i {
        font-size: 1rem;
    }
}

// Анимации перехода модалки
.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}

// Адаптив
@media (max-width: 640px) {
    .btn-text {
        display: none;
    }
    .nav-btn {
        padding: 0.6rem;
    }

    .navbar-brand {
        font-size: 1.2rem;
    }

    .status-text {
        display: none; // На мобильных показываем только иконку в бейдже
    }

    .status-badge {
        padding: 6px 10px;
    }
}
</style>
