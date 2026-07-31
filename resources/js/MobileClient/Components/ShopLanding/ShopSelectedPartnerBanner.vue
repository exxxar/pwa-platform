<template>
    <transition name="banner-slide">
        <div class="partner-banner" v-if="partner">
            <!-- 1. Обложка (Cover) -->
            <div class="banner-cover">
                <img v-lazy="partner.cover_image || partner.image || '/images/default-cover.jpg'" alt="Cover" class="cover-image">
                <div class="cover-overlay"></div>

                <!-- Кнопка сброса выбора -->
                <button class="btn-reset-partner" @click="$emit('reset')" title="Выбрать другое заведение">
                    <i class="fa-solid fa-xmark"></i>
                    <span class="d-none d-sm-inline">Сменить заведение</span>
                </button>
            </div>

            <!-- 2. Основной контент -->
            <div class="banner-content container">
                <div class="banner-header">
                    <!-- Логотип -->
                    <div class="partner-logo-wrapper">
                        <div class="partner-logo">
                            <img v-lazy="partner.logo || partner.image || '/images/default-logo.png'" alt="Logo">
                        </div>
                    </div>

                    <!-- Мета-информация -->
                    <div class="partner-meta">
                        <h2 class="partner-name">{{ partner.title || partner.name }}</h2>

                        <!-- Бейджи статуса и типа -->
                        <div class="partner-badges">
                            <span class="badge status" :class="isOpen ? 'open' : 'closed'">
                                <i :class="isOpen ? 'fa-solid fa-door-open' : 'fa-solid fa-clock'"></i>
                                {{ isOpen ? 'Открыто' : 'Закрыто' }}
                                <span v-if="partner.can_buy_after_closing && !isOpen" class="preorder-hint">(Можно предзаказ)</span>
                            </span>

                            <!-- 🆕 Кнопка открытия графика работы -->
                            <button v-if="hasSchedule" class="badge schedule-btn" @click="showScheduleModal = true">
                                <i class="fa-regular fa-clock"></i> График работы
                            </button>

                            <span class="badge type" :class="partner.shop_display_type === 0 ? 'food' : 'goods'">
                                <i :class="partner.shop_display_type === 0 ? 'fa-solid fa-utensils' : 'fa-solid fa-bag-shopping'"></i>
                                {{ partner.shop_display_type === 0 ? 'Еда' : 'Товары' }}
                            </span>

                            <span class="badge delivery" v-if="partner.allow_delivery">
                                <i class="fa-solid fa-motorcycle"></i> Доставка
                            </span>
                            <span class="badge pickup" v-if="partner.allow_pickup">
                                <i class="fa-solid fa-bag-shopping"></i> Самовывоз
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. Теги заведения -->
                <div class="venue-tags" v-if="parsedTags.length > 0">
                    <span v-for="(tag, index) in parsedTags" :key="index" class="tag-pill">
                        {{ tag }}
                    </span>
                </div>

                <!-- 4. Сетка детальной информации -->
                <div class="banner-details">
                    <p class="partner-description" v-if="partner.description">
                        {{ partner.description }}
                    </p>

                    <div class="info-grid">
                        <!-- Адрес -->
                        <div class="info-card" v-if="partner.address">
                            <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="info-text">
                                <span class="info-label">Адрес</span>
                                <span class="info-value">{{ partner.address }}</span>
                                <a v-if="partner.shop_coords" :href="`https://yandex.ru/maps/?pt=${partner.shop_coords}&z=16&l=map`" target="_blank" class="info-link">
                                    <i class="fa-solid fa-map"></i> Показать на карте
                                </a>
                            </div>
                        </div>

                        <!-- Контакты / Менеджер -->
                        <div class="info-card" v-if="partner.phone || (partner.manager && partner.manager.phone)">
                            <div class="info-icon"><i class="fa-solid fa-headset"></i></div>
                            <div class="info-text">
                                <span class="info-label">Контакты</span>
                                <span class="info-value">{{ partner.manager?.name ? `Менеджер: ${partner.manager.name}` : 'Телефон' }}</span>
                                <a :href="'tel:' + (partner.manager?.phone || partner.phone)" class="info-link">
                                    <i class="fa-solid fa-phone"></i> {{ partner.manager?.phone || partner.phone }}
                                </a>
                                <a v-if="partner.manager?.social_link" :href="partner.manager.social_link" target="_blank" class="info-link">
                                    <i class="fa-brands fa-telegram"></i> Написать в Telegram
                                </a>
                            </div>
                        </div>

                        <!-- Условия доставки -->
                        <div class="info-card" v-if="partner.allow_delivery">
                            <div class="info-icon"><i class="fa-solid fa-truck-fast"></i></div>
                            <div class="info-text">
                                <span class="info-label">Доставка</span>
                                <span class="info-value">Мин. заказ: <strong>{{ partner.min_price || 0 }} ₽</strong></span>
                                <span v-if="partner.free_shipping_starts_from" class="info-highlight">
                                    <i class="fa-solid fa-gift"></i> Бесплатно от {{ partner.free_shipping_starts_from }} ₽
                                </span>
                                <span v-if="parsedCities.length > 0" class="info-cities">
                                    <i class="fa-solid fa-city"></i> {{ parsedCities.slice(0, 3).join(', ') }}<span v-if="parsedCities.length > 3"> и др.</span>
                                </span>
                            </div>
                        </div>

                        <!-- Способы оплаты -->
                        <div class="info-card" v-if="hasPaymentMethods">
                            <div class="info-icon"><i class="fa-solid fa-wallet"></i></div>
                            <div class="info-text">
                                <span class="info-label">Оплата</span>
                                <div class="payment-methods">
                                    <span v-if="partner.can_use_cash" class="pay-badge" title="Наличные"><i class="fa-solid fa-money-bill-wave"></i></span>
                                    <span v-if="partner.can_use_card || hasSbp" class="pay-badge" title="Картой / СБП"><i class="fa-solid fa-credit-card"></i></span>
                                    <span v-if="partner.need_pay_after_call" class="pay-badge" title="После звонка"><i class="fa-solid fa-phone-volume"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🆕 5. Блок социальных сетей -->
                    <div v-if="hasSocialLinks" class="social-links-block">
                        <span class="social-label">Мы в соцсетях:</span>
                        <div class="social-buttons">
                            <a v-if="socialLinks.vk" :href="formatLink(socialLinks.vk)" target="_blank" class="social-btn vk" title="ВКонтакте">
                                <i class="fa-brands fa-vk"></i>
                            </a>
                            <a v-if="socialLinks.inst" :href="formatLink(socialLinks.inst)" target="_blank" class="social-btn inst" title="Instagram">
                                <i class="fa-brands fa-instagram"></i>
                            </a>
                            <a v-if="socialLinks.site" :href="formatLink(socialLinks.site)" target="_blank" class="social-btn site" title="Сайт">
                                <i class="fa-solid fa-globe"></i>
                            </a>
                            <a v-if="socialLinks.email" :href="'mailto:' + socialLinks.email" class="social-btn email" title="Email">
                                <i class="fa-solid fa-envelope"></i>
                            </a>
                        </div>
                    </div>

                    <!-- 6. Кнопки действий -->
                    <div class="action-buttons">
                        <button v-if="partner.has_booking" class="btn-action primary" @click="goToBooking">
                            <i class="fa-solid fa-calendar-check"></i> Забронировать стол
                        </button>
                        <a v-if="partner.manager?.social_link" :href="partner.manager.social_link" target="_blank" class="btn-action secondary">
                            <i class="fa-brands fa-telegram"></i> Связаться с менеджером
                        </a>
                    </div>
                </div>
            </div>

            <!-- 🆕 7. Модальное окно графика работы -->
            <teleport to="body">
                <transition name="modal-fade">
                    <div v-if="showScheduleModal" class="schedule-modal-overlay" @click.self="showScheduleModal = false">
                        <div class="schedule-modal-content">
                            <div class="modal-header">
                                <h3>График работы</h3>
                                <button class="modal-close" @click="showScheduleModal = false">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="work-status-large" :class="{ 'is-open': isOpen }">
                                    <div class="status-indicator"></div>
                                    <span>{{ isOpen ? 'Сейчас открыто' : 'Сейчас закрыто' }}</span>
                                </div>

                                <ScheduleList v-if="hasSchedule" :schedule="partnerSchedule" />

                                <div v-else class="empty-schedule">
                                    <i class="fa-solid fa-calendar-xmark"></i>
                                    <p>График работы ещё не составлен</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </teleport>
        </div>
    </transition>
</template>

<script>
import ScheduleList from "@/MobileClient/Components/Shop/ScheduleList.vue";

export default {
    name: 'ShopSelectedPartnerBanner',
    components: {
        ScheduleList
    },
    props: {
        partner: {
            type: Object,
            required: true
        }
    },
    emits: ['reset'],
    data() {
        return {
            showScheduleModal: false,
        };
    },
    computed: {
        isOpen() {
            if (this.partner.is_disabled) return false;
            return this.partner.is_work !== false || this.partner.can_buy_after_closing;
        },
        parsedTags() {
            if (!this.partner.venue_tags) return [];
            return this.partner.venue_tags.split(',').map(t => t.trim()).filter(t => t.length > 0);
        },
        parsedCities() {
            if (!this.partner.nearest_cities) return [];
            return this.partner.nearest_cities.split(/[,;\n]+/).map(c => c.trim()).filter(c => c.length > 0);
        },
        hasSbp() {
            if (!this.partner.sbp_banks) return false;
            return Object.values(this.partner.sbp_banks).some(bank => bank.enabled);
        },
        hasPaymentMethods() {
            return this.partner.can_use_cash || this.partner.can_use_card || this.hasSbp || this.partner.need_pay_after_call;
        },
        // 🆕 Получаем расписание (поддержка разных уровней вложенности)
        partnerSchedule() {
            return this.partner.settings?.schedule ||
                this.partner.settings?.company?.schedule ||
                this.partner.schedule || [];
        },
        hasSchedule() {
            return Array.isArray(this.partnerSchedule) && this.partnerSchedule.length > 0;
        },
        // 🆕 Социальные сети
        socialLinks() {
            const settings = this.partner.settings || {};
            const companyLinks = settings.company?.links || settings.links || {};
            return {
                vk: companyLinks.vk,
                inst: companyLinks.inst,
                site: companyLinks.site,
                email: settings.company?.email || settings.email
            };
        },
        hasSocialLinks() {
            return this.socialLinks.vk || this.socialLinks.inst || this.socialLinks.site || this.socialLinks.email;
        }
    },
    methods: {
        formatLink(url) {
            if (!url) return '#';
            return url.startsWith('http://') || url.startsWith('https://') ? url : `https://${url}`;
        },
        goToBooking() {
            // Используем Vue Router для перехода к бронированию
            this.$router.push({ name: 'TableBooking' }).catch(() => {});
        }
    }
}
</script>

<style lang="scss" scoped>
.partner-banner {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
    margin-bottom: 2rem;
    position: relative;
    border: 1px solid rgba(0,0,0,0.04);
}

// 1. Обложка
.banner-cover {
    position: relative;
    height: 180px;
    width: 100%;

    .cover-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cover-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.5) 100%);
    }

    .btn-reset-partner {
        position: absolute;
        top: 16px;
        right: 16px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        border: none;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--dark, #0f0f14);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);

        &:hover {
            background: white;
            transform: translateY(-2px);
            color: var(--primary, #ff7a00);
        }
    }
}

// 2. Контент
.banner-content {
    position: relative;
    padding-top: 0;
    margin-top: -50px;
    z-index: 2;
}

.banner-header {
    display: flex;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 1rem;
}

.partner-logo-wrapper {
    flex-shrink: 0;
}

.partner-logo {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    background: white;
    padding: 6px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 14px;
    }
}

.partner-meta {
    flex: 1;
    padding-bottom: 8px;
}

.partner-name {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--dark, #0f0f14);
    margin: 0 0 12px 0;
    line-height: 1.2;

    background: #ffffff;
    border-radius: 10px 10px 0px 0px;
    padding: 10px 20px;
    display: inline-block;
}

.partner-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    border: none;
    cursor: default;

    &.status {
        &.open { background: rgba(16, 185, 129, 0.15); color: #047857; }
        &.closed { background: rgba(239, 68, 68, 0.15); color: #b91c1c; }
        .preorder-hint { font-weight: 400; font-size: 0.75rem; margin-left: 4px; opacity: 0.8; }
    }

    &.schedule-btn {
        background: rgba(99, 102, 241, 0.1);
        color: #4338ca;
        cursor: pointer;
        transition: all 0.2s;

        &:hover {
            background: rgba(99, 102, 241, 0.2);
            transform: translateY(-1px);
        }
    }

    &.type {
        &.food { background: rgba(99, 102, 241, 0.1); color: #4338ca; }
        &.goods { background: rgba(245, 158, 11, 0.1); color: #b45309; }
    }
    &.delivery { background: rgba(245, 158, 11, 0.15); color: #b45309; }
    &.pickup { background: rgba(16, 185, 129, 0.15); color: #047857; }
}

// 3. Теги
.venue-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}

.tag-pill {
    background: var(--light, #fffdf8);
    border: 1px solid rgba(0,0,0,0.08);
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    color: var(--gray, #6c757d);
    font-weight: 500;
}

// 4. Детали
.banner-details {
    // padding-top: 0.5rem;
}

.partner-description {
    font-size: 1rem;
    line-height: 1.6;
    color: var(--gray, #6c757d);
    margin: 0 0 1.5rem 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: var(--light, #fffdf8);
    padding: 12px;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.04);
}

.info-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(var(--primary-rgb, 255, 122, 0), 0.1);
    color: var(--primary, #ff7a00);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.info-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.info-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--gray, #6c757d);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark, #0f0f14);
}

.info-highlight {
    font-size: 0.85rem;
    color: #059669;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.info-cities {
    font-size: 0.8rem;
    color: var(--gray, #6c757d);
    display: flex;
    align-items: center;
    gap: 4px;
}

.info-link {
    color: var(--primary, #ff7a00);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
    transition: opacity 0.2s;

    &:hover { opacity: 0.8; }
}

.payment-methods {
    display: flex;
    gap: 8px;
    margin-top: 4px;
}

.pay-badge {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: white;
    border: 1px solid rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--dark, #0f0f14);
    font-size: 1rem;
}

// 🆕 5. Социальные сети
.social-links-block {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}

.social-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--gray, #6c757d);
    margin-bottom: 10px;
}

.social-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.social-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    font-size: 1.2rem;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        color: white !important;
    }

    &.vk {
        background: rgba(0, 119, 255, 0.08);
        color: #0077ff;
        &:hover { background: #0077ff; }
    }
    &.inst {
        background: rgba(225, 48, 108, 0.08);
        color: #e1306c;
        &:hover { background: #e1306c; }
    }
    &.site {
        background: rgba(108, 117, 125, 0.08);
        color: #6c757d;
        &:hover { background: #6c757d; }
    }
    &.email {
        background: rgba(220, 53, 69, 0.08);
        color: #dc3545;
        &:hover { background: #dc3545; }
    }
}

// 6. Кнопки действий
.action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s;
    flex: 1;
    min-width: 200px;
    border: none;
    cursor: pointer;

    &.primary {
        background: var(--primary, #ff7a00);
        color: white;
        &:hover { background: var(--primary-dark, #e56f00); transform: translateY(-2px); }
    }
    &.secondary {
        background: var(--light, #fffdf8);
        color: var(--dark, #0f0f14);
        border: 1px solid rgba(0,0,0,0.1);
        &:hover { background: #f1f5f9; transform: translateY(-2px); }
    }
}

// 🆕 7. Модальное окно графика работы
.schedule-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(6px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.schedule-modal-content {
    background: white;
    width: 100%;
    max-width: 450px;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background: #fafafa;

    h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--dark, #0f0f14);
    }
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: var(--gray, #6c757d);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: #e2e8f0;
        color: var(--dark, #0f0f14);
    }
}

.modal-body {
    padding: 1.5rem;
}

.work-status-large {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;

    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    &.is-open {
        background: rgba(16, 185, 129, 0.1);
        color: #047857;
        .status-indicator { background: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); }
    }

    &:not(.is-open) {
        background: rgba(239, 68, 68, 0.1);
        color: #b91c1c;
        .status-indicator { background: #ef4444; }
    }
}

.empty-schedule {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--gray, #6c757d);

    i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        opacity: 0.5;
    }
    p { margin: 0; font-size: 0.9rem; }
}

// Анимации
.banner-slide-enter-active, .banner-slide-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.banner-slide-enter-from, .banner-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px) scale(0.98);
}

.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.25s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

// Адаптив
@media (max-width: 768px) {
    .banner-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .partner-badges { justify-content: center; }
    .info-grid { grid-template-columns: 1fr; }
    .action-buttons { flex-direction: column; }
    .btn-action { width: 100%; }
}
</style>
