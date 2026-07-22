<template>
    <div class="maintenance-page">
        <!-- Фоновый декоративный элемент -->
        <div class="bg-glow"></div>

        <div class="maintenance-content">
            <!-- Анимированная иконка -->
            <div class="icon-wrapper">
                <i class="fa-solid fa-gears gear-large"></i>
                <i class="fa-solid fa-gears gear-small"></i>
                <i class="fa-solid fa-wrench floating-wrench"></i>
            </div>

            <!-- Заголовок -->
            <h1 class="title">Технические работы</h1>

            <!-- Блок с причиной (если задана в настройках) -->
            <div v-if="maintenanceReason" class="reason-card">
                <div class="reason-icon">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <p class="reason-text">{{ maintenanceReason }}</p>
            </div>

            <!-- Дефолтный текст (если причина не указана) -->
            <p v-else class="default-reason">
                Мы проводим плановое обновление системы, чтобы сделать наш сервис ещё лучше и быстрее.<br>
                Пожалуйста, зайдите немного позже. Приносим извинения за временные неудобства.
            </p>

            <!-- Кнопка связи с поддержкой -->
            <a
                v-if="managerLink"
                :href="managerLink"
                target="_blank"
                class="contact-btn"
            >
                <i class="fa-solid fa-headset"></i>
                <span>Связаться с поддержкой</span>
            </a>

            <!-- 🆕 Кнопка входа для администраторов/сотрудников -->
            <button @click="goToLogin" class="login-btn">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Вход для сотрудников</span>
            </button>

            <!-- Футер -->
            <div class="maintenance-footer">
                <span>© {{ currentYear }} {{ tenantName }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MaintenancePage',
    props: {
        tenant: {
            type: Object,
            required: true
        },
        tenant_user: {
            type: Object,
            default: null
        },
        initial_data: {
            type: Object,
            default: () => ({}),
        },
    },
    created() {
        window.TenantUser = this.tenant_user || null;
        window.Tenant = this.tenant;
    },
    computed: {
        // Дублирование пропса для удобства использования в шаблоне (как было в оригинале)
        tenantData() {
            return window.Tenant || {};
        },
        settings() {
            return this.tenantData.settings || {};
        },
        tenantName() {
            return this.tenantData.name || 'Наш магазин';
        },
        maintenanceReason() {
            return this.settings.disabled_text?.trim() || null;
        },
        managerLink() {
            return this.settings.shop?.manager?.social_link || null;
        },
        currentYear() {
            return new Date().getFullYear();
        }
    },
    methods: {
        /**
         * 🆕 Переход на страницу входа через нативный редирект
         */
        goToLogin() {
            window.location.href = '/auth/login';
        }
    }
};
</script>

<style lang="scss" scoped>
// Используем переменные из вашей дизайн-системы
$primary: var(--bs-primary, #667eea);
$primary-rgb: var(--bs-primary-rgb, 102, 126, 234);
$bg: var(--bs-body-bg, #ffffff);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.maintenance-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: $bg;
    position: relative;
    overflow: hidden;
    padding: 20px;
}

// Фоновое свечение
.bg-glow {
    position: absolute;
    top: -20%;
    left: 50%;
    transform: translateX(-50%);
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba($primary-rgb, 0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}

.maintenance-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 500px;
    width: 100%;
    animation: fadeInUp 0.8s ease-out;
}

// ==========================================
// АНИМИРОВАННАЯ ИКОНКА
// ==========================================
.icon-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 32px;
}

.gear-large {
    position: absolute;
    top: 0;
    left: 0;
    font-size: 4rem;
    color: rgba($primary-rgb, 0.2);
    animation: spin-slow 10s linear infinite;
}

.gear-small {
    position: absolute;
    bottom: 0;
    right: 0;
    font-size: 2.5rem;
    color: rgba($primary-rgb, 0.4);
    animation: spin-reverse 7s linear infinite;
}

.floating-wrench {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2rem;
    color: $primary;
    background: $bg;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba($primary-rgb, 0.2);
    animation: float 3s ease-in-out infinite;
}

// ==========================================
// ТИПОГРАФИКА
// ==========================================
.title {
    font-size: 2rem;
    font-weight: 800;
    color: $text;
    margin-bottom: 24px;
    letter-spacing: -0.5px;
}

.default-reason {
    font-size: 1rem;
    line-height: 1.6;
    color: $text-muted;
    margin-bottom: 32px;
}

// ==========================================
// КАРТОЧКА ПРИЧИНЫ
// ==========================================
.reason-card {
    background: rgba($primary-rgb, 0.05);
    border: 1px solid rgba($primary-rgb, 0.15);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 32px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    text-align: left;
    animation: fadeIn 1s ease-out 0.3s backwards;
}

.reason-icon {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    background: rgba($primary-rgb, 0.1);
    color: $primary;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.reason-text {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.6;
    color: $text;
    white-space: pre-wrap; // Сохраняет переносы строк из админки
}

// ==========================================
// КНОПКИ ДЕЙСТВИЙ
// ==========================================
.contact-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 28px;
    background: linear-gradient(135deg, $primary 0%, var(--bs-primary-hover, $primary) 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba($primary-rgb, 0.3);
    animation: fadeIn 1s ease-out 0.5s backwards;
    width: 100%;
    max-width: 320px;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($primary-rgb, 0.4);
    }

    &:active {
        transform: translateY(0);
    }
}

// 🆕 Стили для кнопки входа
.login-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 28px;
    background: transparent;
    color: $text-muted;
    border: 1.5px solid $border;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 12px;
    width: 100%;
    max-width: 320px;
    animation: fadeIn 1s ease-out 0.6s backwards;

    &:hover {
        background: rgba($primary-rgb, 0.05);
        border-color: $primary;
        color: $primary;
        transform: translateY(-2px);
    }

    &:active {
        transform: translateY(0);
    }
}

// ==========================================
// ФУТЕР
// ==========================================
.maintenance-footer {
    margin-top: 48px;
    font-size: 0.8rem;
    color: $text-muted;
    opacity: 0.7;
}

// ==========================================
// АНИМАЦИИ
// ==========================================
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes spin-reverse {
    from { transform: rotate(360deg); }
    to { transform: rotate(0deg); }
}

@keyframes float {
    0%, 100% { transform: translate(-50%, -50%) translateY(0); }
    50% { transform: translate(-50%, -50%) translateY(-8px); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 576px) {
    .title {
        font-size: 1.5rem;
    }

    .icon-wrapper {
        width: 100px;
        height: 100px;
    }

    .gear-large { font-size: 3rem; }
    .gear-small { font-size: 2rem; }
    .floating-wrench {
        font-size: 1.5rem;
        width: 40px;
        height: 40px;
    }

    .reason-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .contact-btn,
    .login-btn {
        width: 100%;
        max-width: 100%;
    }
}
</style>
