<template>
    <div>
        <!-- ========================================== -->
        <!-- КЛИКАБЕЛЬНЫЙ БАННЕР СТАТУСА -->
        <!-- ========================================== -->
        <transition name="status-fade">
            <div
                v-if="!isWork"
                class="status-banner is-closed"
                role="button"
                tabindex="0"
                @click="openModal"
                @keydown.enter="openModal"
                @keydown.space.prevent="openModal"
            >
                <div class="banner-icon-wrapper">
                    <i class="fa-solid fa-moon"></i>
                    <div class="pulse-ring"></div>
                </div>
                <div class="banner-content">
                    <div class="banner-title">Магазин сейчас закрыт</div>
                    <div class="banner-text">Нажмите, чтобы посмотреть график работы</div>
                </div>
                <div class="banner-action">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛЬНОЕ ОКНО С ГРАФИКОМ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">

                    <!-- Шапка модалки -->
                    <div class="modal-header">
                        <div class="modal-icon">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div class="modal-title-wrapper">
                            <h5 class="modal-title">График работы</h5>
                            <span class="modal-subtitle">Время приема заказов</span>
                        </div>
                        <button class="modal-close" @click="closeModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Тело модалки -->
                    <div class="modal-body">
                        <!-- Вариант 1: График есть -->
                        <ScheduleList
                            v-if="hasSchedule"
                            :schedule="schedule"
                        />

                        <!-- Вариант 2: Графика нет -->
                        <div v-else class="no-schedule-state">
                            <div class="no-schedule-icon">
                                <i class="fa-solid fa-circle-question"></i>
                            </div>
                            <h6 class="no-schedule-title">График временно недоступен</h6>
                            <p class="no-schedule-text">
                                К сожалению, мы не можем отобразить точное расписание прямо сейчас.
                                Пожалуйста, свяжитесь с нами по телефону или в чате для уточнения времени работы.
                            </p>
                            <button class="contact-btn" @click="closeModal">
                                <i class="fa-solid fa-phone me-2"></i>
                                Связаться с нами
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import ScheduleList from '@/MobileClient/Components/Shop/ScheduleList.vue'; // Убедись, что путь правильный

export default {
    name: "StoreStatusBanner",

    components: {
        ScheduleList,
    },

    props: {
        isWork: {
            type: Boolean,
            required: true,
        },
        schedule: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            isModalOpen: false,
        };
    },

    computed: {
        hasSchedule() {
            return this.schedule && this.schedule.length > 0;
        },
    },

    methods: {
        openModal() {
            this.isModalOpen = true;
            // Блокируем скролл фона
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.isModalOpen = false;
            // Возвращаем скролл
            document.body.style.overflow = '';
        },
    },

    watch: {
        // Если магазин открылся, автоматически закрываем модалку, если она была открыта
        isWork(newValue) {
            if (newValue && this.isModalOpen) {
                this.closeModal();
            }
        }
    },

    beforeUnmount() {
        // Гарантируем возврат скролла при уничтожении компонента
        document.body.style.overflow = '';
    }
};
</script>

<style scoped>
/* ==========================================
   БАННЕР СТАТУСА
   ========================================== */
.status-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    margin: 16px;
    background: rgba(255, 193, 7, 0.08);
    border: 1px solid rgba(255, 193, 7, 0.2);
    border-radius: 16px;
    backdrop-filter: blur(10px);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
}

.status-banner:hover,
.status-banner:focus-visible {
    background: rgba(255, 193, 7, 0.12);
    border-color: rgba(255, 193, 7, 0.4);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255, 193, 7, 0.15);
}

.status-banner:focus-visible {
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.3), 0 8px 24px rgba(255, 193, 7, 0.15);
}

.banner-icon-wrapper {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.pulse-ring {
    position: absolute;
    inset: 0;
    border-radius: 12px;
    border: 2px solid #ffc107;
    animation: statusPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes statusPulse {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(1.4); opacity: 0; }
}

.banner-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.banner-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #856404;
}

.banner-text {
    font-size: 0.8rem;
    color: #856404;
    opacity: 0.8;
}

.banner-action {
    color: #856404;
    opacity: 0.5;
    transition: all 0.3s ease;
}

.status-banner:hover .banner-action {
    opacity: 1;
    transform: translateX(4px);
}

/* Тёмная тема для баннера */
:root[data-bs-theme="dark"] .banner-title,
:root[data-bs-theme="dark"] .banner-text,
:root[data-bs-theme="dark"] .banner-action {
    color: #ffda6a;
}

:root[data-bs-theme="dark"] .status-banner {
    background: rgba(255, 193, 7, 0.1);
    border-color: rgba(255, 193, 7, 0.25);
}

/* ==========================================
   МОДАЛЬНОЕ ОКНО
   ========================================== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: var(--bs-body-bg);
    border-radius: 20px;
    width: 100%;
    max-width: 420px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
}

.modal-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.modal-title-wrapper {
    flex: 1;
}

.modal-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
}

.modal-subtitle {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #dc3545;
    color: white;
    transform: rotate(90deg);
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

/* ==========================================
   СОСТОЯНИЕ "НЕТ ГРАФИКА"
   ========================================== */
.no-schedule-state {
    text-align: center;
    padding: 20px 10px;
}

.no-schedule-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 16px;
}

.no-schedule-title {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--bs-body-color);
    margin-bottom: 8px;
}

.no-schedule-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    line-height: 1.5;
    margin-bottom: 24px;
}

.contact-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    background: var(--bs-primary);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.contact-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(var(--bs-primary-rgb), 0.3);
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.status-fade-enter-active,
.status-fade-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.status-fade-enter-from,
.status-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .status-banner {
        margin: 12px;
        padding: 14px;
        gap: 12px;
    }

    .banner-icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .banner-title { font-size: 0.9rem; }
    .banner-text { font-size: 0.75rem; }

    .modal-container {
        max-height: 90vh;
        border-radius: 16px 16px 0 0;
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        animation: modalSlideUpMobile 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes modalSlideUpMobile {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
}
</style>
