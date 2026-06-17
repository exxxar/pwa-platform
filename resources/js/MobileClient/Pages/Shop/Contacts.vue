<template>
    <div class="contacts-page pb-5" v-if="tenant">

        <!-- ===== HERO С АДРЕСОМ ===== -->
        <div class="contacts-hero mb-3">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <h2 class="hero-title">{{ tenant.settings.address || 'Наш адрес' }}</h2>
                <p class="hero-subtitle">Мы всегда рады видеть вас!</p>

                <!-- Кнопки действий -->
                <div class="hero-actions">
                    <a
                        v-if="mapLink"
                        :href="mapLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hero-btn primary"
                    >
                        <i class="fa-solid fa-route"></i>
                        <span>Построить маршрут</span>
                    </a>
                    <a
                        v-if="phone"
                        :href="'tel:' + phone"
                        class="hero-btn secondary"
                    >
                        <i class="fa-solid fa-phone"></i>
                        <span>Позвонить</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="container px-3">

            <!-- ===== КАРТА ===== -->
            <div class="section-block">
                <div class="section-header">
                    <div class="section-icon map-icon">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div>
                        <h6 class="section-title">Мы на карте</h6>
                        <p class="section-subtitle">Как до нас добраться</p>
                    </div>
                </div>

                <div v-if="mapLink" class="map-wrapper">
                    <div class="map-container" v-html="tenant.settings.links.map_link"></div>
                    <a
                        :href="mapLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="map-expand-btn"
                        title="Открыть в Яндекс.Картах"
                    >
                        <i class="fa-solid fa-up-right-from-square"></i>
                    </a>
                </div>
                <div v-else class="empty-map">
                    <div class="empty-icon">
                        <i class="fa-solid fa-map"></i>
                    </div>
                    <p class="empty-text">Карта скоро будет добавлена</p>
                </div>
            </div>

            <!-- ===== КОНТАКТНАЯ ИНФОРМАЦИЯ ===== -->
            <div class="section-block mt-4">
                <div class="section-header">
                    <div class="section-icon contacts-icon">
                        <i class="fa-solid fa-address-book"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="section-title">Контактная информация</h6>
                        <p class="section-subtitle">Свяжитесь с нами удобным способом</p>
                    </div>
                    <!-- Кнопка редактирования для админа -->
                    <button
                        v-if="isAdmin"
                        class="edit-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#edit-shop-footer-description-modal"
                        title="Редактировать контакты"
                    >
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                </div>

                <div class="contacts-grid">
                    <!-- Телефон -->
                    <a v-if="phone" :href="'tel:' + phone" class="contact-card">
                        <div class="contact-icon phone">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Телефон</span>
                            <span class="contact-value">{{ phone }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right contact-arrow"></i>
                    </a>

                    <!-- Email -->
                    <a v-if="tenant.settings.email" :href="'mailto:' + tenant.settings.email" class="contact-card">
                        <div class="contact-icon email">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Электронная почта</span>
                            <span class="contact-value">{{ tenant.settings.email }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right contact-arrow"></i>
                    </a>

                    <!-- Сайт -->
                    <a v-if="links.site" :href="links.site" target="_blank" rel="noopener noreferrer" class="contact-card">
                        <div class="contact-icon site">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Сайт</span>
                            <span class="contact-value">{{ links.site }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right contact-arrow"></i>
                    </a>

                    <!-- Instagram -->
                    <a v-if="links.inst" :href="'https://instagram.com/' + links.inst" target="_blank" rel="noopener noreferrer" class="contact-card">
                        <div class="contact-icon instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Instagram</span>
                            <span class="contact-value">@{{ links.inst }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right contact-arrow"></i>
                    </a>

                    <!-- VK -->
                    <a v-if="links.vk" :href="'https://vk.com/' + links.vk" target="_blank" rel="noopener noreferrer" class="contact-card">
                        <div class="contact-icon vk">
                            <i class="fa-brands fa-vk"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">ВКонтакте</span>
                            <span class="contact-value">{{ links.vk }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right contact-arrow"></i>
                    </a>
                </div>

                <!-- Соцсети (компактный блок) -->
                <div v-if="links.inst || links.vk" class="social-block mt-3">
                    <div class="social-label">Мы в соцсетях</div>
                    <div class="social-buttons">
                        <a
                            v-if="links.inst"
                            :href="'https://instagram.com/' + links.inst"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn instagram"
                        >
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a
                            v-if="links.vk"
                            :href="'https://vk.com/' + links.vk"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn vk"
                        >
                            <i class="fa-brands fa-vk"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ===== ГРАФИК РАБОТЫ ===== -->
            <div class="section-block mt-4">
                <div class="section-header">
                    <div class="section-icon schedule-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="section-title">График работы</h6>
                        <p class="section-subtitle">Время приёма заказов</p>
                    </div>
                    <!-- Кнопка редактирования для админа -->
                    <button
                        v-if="isAdmin"
                        class="edit-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#edit-shop-footer-description-modal"
                        title="Редактировать график"
                    >
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                </div>

                <div class="schedule-card">
                    <!-- Статус работы -->
                    <div class="work-status" :class="{ 'is-open': isWorkingNow }">
                        <div class="status-indicator"></div>
                        <span class="status-text">
                            {{ isWorkingNow ? 'Сейчас открыто' : 'Сейчас закрыто' }}
                        </span>
                    </div>

                    <ScheduleList
                        v-if="isCorrectSchedule"
                        :schedule="tenant.settings.schedule"
                    />
                    <div v-else class="empty-schedule">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <p>График работы ещё не составлен</p>
                    </div>
                </div>
            </div>

            <!-- ===== БЫСТРЫЕ ДЕЙСТВИЯ ===== -->
            <div class="quick-actions mt-4">
                <a v-if="phone" :href="'tel:' + phone" class="quick-action-btn call">
                    <i class="fa-solid fa-phone"></i>
                    <span>Позвонить</span>
                </a>
                <a v-if="tenant.settings.email" :href="'mailto:' + tenant.settings.email" class="quick-action-btn email">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Написать</span>
                </a>
            </div>

        </div>
    </div>

    <!-- Состояние загрузки -->
    <div v-else class="loading-state">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Загрузка...</span>
        </div>
    </div>
</template>

<script>
import ScheduleList from "@/MobileClient/Components/Shop/ScheduleList.vue";

export default {
    name: "ContactsPage",

    components: {
        ScheduleList
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        self() {
            return window.TenantUser || null;
        },

        settings() {
            return this.tenant?.settings || null;
        },

        isAdmin() {
            return this.self?.is_admin === true || this.self?.role === 'admin';
        },

        phone() {
            return this.tenant?.settings?.phones?.[0] || null;
        },

        links() {
            const links = this.tenant?.settings?.links || {};
            return {
                inst: links.inst || null,
                vk: links.vk || null,
                map_link: links.map_link || null,
                site: links.site || null,
            };
        },

        mapLink() {
            return this.links.map_link;
        },

        isCorrectSchedule() {
            if (typeof window.isCorrectSchedule !== 'function') return false;
            return window.isCorrectSchedule(this.tenant?.settings?.schedule);
        },

        isWorkingNow() {
            return this.tenant?.settings?.is_work ?? false;
        },
    },
};
</script>

<style scoped>
.contacts-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO С АДРЕСОМ
   ========================================== */
.contacts-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.hero-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.hero-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    margin-bottom: 24px;
}

.hero-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 14px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
}

.hero-btn.primary {
    background: white;
    color: var(--bs-primary);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.hero-btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.hero-btn.secondary {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
}

.hero-btn.secondary:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.section-block {
    animation: fadeInUp 0.5s ease-out;
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

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.map-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
}

.contacts-icon {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
}

.schedule-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* Кнопка редактирования */
.edit-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.edit-btn:hover {
    background: var(--bs-primary);
    color: white;
    transform: rotate(-10deg) scale(1.1);
}

/* ==========================================
   КАРТА
   ========================================== */
.map-wrapper {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--bs-border-color);
}

.map-container {
    width: 100%;
    height: 300px;
}

.map-container :deep(iframe) {
    width: 100%;
    height: 100%;
    border: none;
}

.map-expand-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.2s ease;
    z-index: 10;
}

.map-expand-btn:hover {
    background: var(--bs-primary);
    color: white;
    transform: scale(1.1);
}

.empty-map {
    padding: 40px 20px;
    text-align: center;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
}

.empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 12px;
}

.empty-text {
    margin: 0;
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
}

/* ==========================================
   КОНТАКТЫ
   ========================================== */
.contacts-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.contact-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    text-decoration: none;
    color: var(--bs-body-color);
    transition: all 0.2s ease;
}

.contact-card:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.02);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.08);
}

.contact-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.contact-icon.phone {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.contact-icon.email {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
}

.contact-icon.site {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.contact-icon.instagram {
    background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

.contact-icon.vk {
    background: linear-gradient(135deg, #4a76a8 0%, #3d6290 100%);
}

.contact-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.contact-label {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.contact-value {
    font-weight: 600;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-arrow {
    color: var(--bs-secondary-color);
    font-size: 0.75rem;
    opacity: 0;
    transform: translateX(-4px);
    transition: all 0.2s ease;
}

.contact-card:hover .contact-arrow {
    opacity: 1;
    transform: translateX(0);
}

/* Соцсети */
.social-block {
    padding: 16px;
    background: rgba(var(--bs-primary-rgb), 0.03);
    border-radius: 14px;
    text-align: center;
}

.social-label {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    margin-bottom: 12px;
    font-weight: 500;
}

.social-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.social-btn {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.social-btn.instagram {
    background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

.social-btn.vk {
    background: linear-gradient(135deg, #4a76a8 0%, #3d6290 100%);
}

.social-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

/* ==========================================
   ГРАФИК РАБОТЫ
   ========================================== */
.schedule-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 16px;
    overflow: hidden;
}

.work-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(220, 53, 69, 0.08);
    border-radius: 12px;
    margin-bottom: 16px;
}

.work-status.is-open {
    background: rgba(25, 135, 84, 0.08);
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #dc3545;
    box-shadow: 0 0 8px rgba(220, 53, 69, 0.5);
}

.work-status.is-open .status-indicator {
    background: #198754;
    box-shadow: 0 0 8px rgba(25, 135, 84, 0.5);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 8px rgba(25, 135, 84, 0.5);
    }
    50% {
        box-shadow: 0 0 16px rgba(25, 135, 84, 0.8);
    }
}

.status-text {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

.empty-schedule {
    text-align: center;
    padding: 24px;
    color: var(--bs-secondary-color);
}

.empty-schedule i {
    font-size: 2rem;
    margin-bottom: 8px;
    opacity: 0.5;
}

.empty-schedule p {
    margin: 0;
    font-size: 0.9rem;
}

/* ==========================================
   БЫСТРЫЕ ДЕЙСТВИЯ
   ========================================== */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    border-radius: 14px;
    text-decoration: none;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.quick-action-btn.call {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.quick-action-btn.email {
    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
}

.quick-action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    color: white;
}

/* ==========================================
   ЗАГРУЗКА
   ========================================== */
.loading-state {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bs-body-bg);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .hero-title {
        font-size: 1.3rem;
    }

    .hero-actions {
        flex-direction: column;
    }

    .hero-btn {
        width: 100%;
        justify-content: center;
    }

    .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>
