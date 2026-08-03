<template>
    <footer class="app-footer" v-if="!$route.meta.hideBottomMenu">
        <div class="footer-content">

            <!-- Секция 1: Информация о компании -->
            <div class="footer-section company-info">
                <div class="company-header">
                    <i class="fa-solid fa-store"></i>
                    <h5>{{ tenant?.name || 'Магазин' }}</h5>
                </div>

                <p v-if="tenant?.description" class="company-description">
                    {{ tenant.description }}
                </p>

                <div v-if="settings?.address" class="company-address">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>{{ settings.address }}</span>
                </div>

                <div v-if="settings?.phones?.length > 0" class="company-phone">
                    <i class="fa-solid fa-phone"></i>
                    <a :href="'tel:' + settings.phones[0]">{{ settings.phones[0] }}</a>
                </div>

                <div v-if="settings?.email" class="company-email">
                    <i class="fa-solid fa-envelope"></i>
                    <a :href="'mailto:' + settings.email">{{ settings.email }}</a>
                </div>
            </div>

            <!-- Разделитель -->
            <div class="footer-divider"></div>

            <!-- Секция 2: Юридическая информация -->
            <div class="footer-section legal-info">
                <h6 class="section-title">
                    <i class="fa-solid fa-scale-balanced"></i>
                    Правовая информация
                </h6>

                <ul class="legal-links">
                    <li>
                        <router-link to="/privacy-policy">
                            <i class="fa-solid fa-shield-halved"></i>
                            Политика конфиденциальности
                        </router-link>
                    </li>
                    <li>
                        <router-link to="/terms-of-service">
                            <i class="fa-solid fa-file-contract"></i>
                            Пользовательское соглашение
                        </router-link>
                    </li>
                    <li>
                        <router-link to="/cookie-policy">
                            <i class="fa-solid fa-cookie-bite"></i>
                            Политика использования cookies
                        </router-link>
                    </li>
                </ul>
            </div>

            <!-- Разделитель -->
            <div class="footer-divider"></div>

            <!-- Секция 3: Поделиться -->
            <div class="footer-section share-section">
                <h6 class="section-title">
                    <i class="fa-solid fa-share-nodes"></i>
                    Поделитесь с друзьями
                </h6>
                <ShareLink />
            </div>

            <!-- Разделитель -->
            <div class="footer-divider"></div>

            <!-- Секция 4: Обратная связь -->
            <div v-if="$route.name !== 'FeedBack'" class="footer-section feedback-section">
                <button @click="goTo('FeedBack')" class="btn-feedback">
                    <i class="fa-solid fa-comment-dots"></i>
                    Обратная связь
                </button>
            </div>

            <div class="footer-section share-section">
                <h6 class="section-title">
                    <i class="fa-solid fa-share-nodes"></i>
                    Подписаться на обновления
                </h6>
                <Subscribe />
            </div>

            <!-- Копирайт и Версия -->
            <div class="footer-copyright">
                <p>© {{ currentYear }} {{ tenant?.name }}. Все права защищены.</p>

                <!-- 🆕 ДОБАВЛЕНО: Версия приложения -->
                <p class="app-version">v {{ appVersion }}</p>
            </div>

            <!-- Кнопка наверх -->
            <div class="footer-scroll-top">
                <button @click="scrollTop" class="btn-scroll-top">
                    <i class="fa-solid fa-arrow-up"></i>
                    <span>Наверх</span>
                </button>
            </div>

        </div>
    </footer>
</template>

<script>
import ShareLink from "@/MobileClient/Components/ShareLink.vue";
import Subscribe from "@/MobileClient/Components/Subscribe.vue";

export default {
    name: "AppFooter",

    components: {
        ShareLink,
        Subscribe,
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || null;
        },

        currentYear() {
            return new Date().getFullYear();
        },

        // 🆕 ДОБАВЛЕНО: Получение версии из переменных окружения Vite
        appVersion() {
            return import.meta.env.VITE_APP_VERSION || '1.0.0';
        }
    },

    methods: {
        goTo(name) {
            if (!name) return;
            this.$router.push({ name });
        },

        scrollTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        },
    },
};
</script>

<style scoped>
.app-footer {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    color: #e0e0e0;
    padding: 0;
    position: relative;
    overflow: hidden;
}

/* Декоративный градиент сверху */
.app-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
}

.footer-content {
    max-width: 600px;
    margin: 0 auto;
    padding: 30px 20px 20px;
}

/* Секции футера */
.footer-section {
    margin-bottom: 24px;
}

/* Информация о компании */
.company-info {
    text-align: center;
}

.company-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 16px;
}

.company-header i {
    font-size: 2rem;
    color: var(--bs-primary-hover);
}

.company-header h5 {
    margin: 0;
    font-weight: 700;
    color: #ffffff;
    font-size: 1.4rem;
}

.company-description {
    color: #b0b0b0;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 16px;
}

.company-address,
.company-phone,
.company-email {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.company-address i,
.company-phone i,
.company-email i {
    color: var(--bs-primary);
    width: 20px;
}

.company-phone a,
.company-email a {
    color: #e0e0e0;
    text-decoration: none;
    transition: color 0.2s ease;
}

.company-phone a:hover,
.company-email a:hover {
    color: var(--bs-primary);
}

/* Разделитель */
.footer-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
    margin: 24px 0;
}

/* Заголовки секций */
.section-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
}

.section-title i {
    color: var(--bs-primary);
}

/* Юридические ссылки */
.legal-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.legal-links li {
    margin-bottom: 12px;
}

.legal-links a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #b0b0b0;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.legal-links a:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: var(--bs-primary);
    color: #ffffff;
    transform: translateX(4px);
}

.legal-links a i {
    color: var(--bs-primary);
    width: 20px;
    text-align: center;
}

/* Секция "Поделиться" */
.share-section {
    text-align: center;
}

/* Кнопка обратной связи */
.btn-feedback {
    width: 100%;
    padding: 14px 20px;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid var(--bs-primary);
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-feedback:hover {
    background: var(--bs-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3);
}

.btn-feedback i {
    font-size: 1.2rem;
}

/* Копирайт */
.footer-copyright {
    text-align: center;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-copyright p {
    margin: 0;
    color: #808080;
    font-size: 0.85rem;
}

/* 🆕 ДОБАВЛЕНО: Стиль для версии приложения */
.app-version {
    margin-top: 8px;
    font-size: 0.75rem;
    color: #505050; /* Еще более приглушенный цвет, чем у копирайта */
    font-family: monospace; /* Моноширинный шрифт для технического вида */
    letter-spacing: 0.5px;
    opacity: 0.8;
}

/* Кнопка наверх */
.footer-scroll-top {
    text-align: center;
    margin-top: 20px;
}

.btn-scroll-top {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #b0b0b0;
    padding: 10px 20px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.btn-scroll-top:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--bs-primary);
    color: #ffffff;
}

/* Адаптив для тёмной темы */
:root[data-bs-theme="dark"] .app-footer {
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #2a2a2a 100%);
}

:root[data-bs-theme="dark"] .app-version {
    color: #404040;
}
</style>
