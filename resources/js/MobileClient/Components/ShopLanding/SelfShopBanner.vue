<template>
    <transition name="banner-slide">
        <div class="partner-banner tenant-banner" v-if="tenant" :style="{ '--theme-color': themeColor }">
            <!-- 1. Обложка (Cover) -->
            <div class="banner-cover">
                <img v-lazy="tenant.image || '/images/default-cover.jpg'" alt="Cover" class="cover-image">
                <div class="cover-overlay"></div>

                <!-- Компактная кнопка связи -->
                <button class="btn-contact-compact" @click="showContactModal = true" title="Связаться с нами">
                    <i class="fa-solid fa-comment-dots"></i>
                    <span class="d-none d-sm-inline">Связаться</span>
                </button>
            </div>

            <!-- 2. Основной контент -->
            <div class="banner-content container">
                <div class="banner-header">
                    <!-- Логотип -->
                    <div class="partner-logo-wrapper">
                        <div class="partner-logo">
                            <img v-lazy="tenant.icon || tenant.image || '/images/default-logo.png'" alt="Logo">
                        </div>
                    </div>

                    <!-- Мета-информация -->
                    <div class="partner-meta">
                        <h2 class="partner-name">{{ tenant.name }}</h2>

                        <!-- Бейджи статуса и типа -->
                        <div class="partner-badges">
                            <span class="badge status" :class="isOpenRightNow ? 'open' : 'closed'">
                                <i :class="isOpenRightNow ? 'fa-solid fa-door-open' : 'fa-solid fa-clock'"></i>
                                {{ isOpenRightNow ? 'Открыто' : 'Закрыто' }}
                                <span v-if="settings.can_buy_after_closing && !isOpenRightNow" class="preorder-hint">(Можно предзаказ)</span>
                            </span>

                            <!-- Кнопка открытия графика работы -->
                            <button v-if="hasSchedule" class="badge schedule-btn" @click="showScheduleModal = true">
                                <i class="fa-regular fa-clock"></i> График работы
                            </button>

                            <span class="badge type">
                                <i class="fa-solid fa-store"></i> {{ tenant.app_type === 'partner' ? 'Партнер' : 'Заведение' }}
                            </span>

                            <span class="badge delivery" v-if="settings.allow_delivery">
                                <i class="fa-solid fa-motorcycle"></i> Доставка
                            </span>
                            <span class="badge pickup" v-if="settings.allow_pickup">
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
                    <p class="partner-description" v-if="tenant.long_description || tenant.description">
                        {{ tenant.long_description || tenant.description }}
                    </p>

                    <div class="info-grid">
                        <!-- Адрес -->
                        <div class="info-card" v-if="settings.address">
                            <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="info-text">
                                <span class="info-label">Адрес</span>
                                <span class="info-value">{{ settings.address }}</span>
                                <a v-if="settings.shop_coords" :href="getMapLink(settings.shop_coords)" target="_blank" class="info-link">
                                    <i class="fa-solid fa-map"></i> Показать на карте
                                </a>
                            </div>
                        </div>

                        <!-- Контакты / Менеджер -->
                        <div class="info-card" v-if="managerInfo.link || managerInfo.phone">
                            <div class="info-icon"><i class="fa-solid fa-headset"></i></div>
                            <div class="info-text">
                                <span class="info-label">Контакты</span>
                                <a v-if="managerInfo.link" :href="formatLink(managerInfo.link)" target="_blank" class="info-link">
                                    <i class="fa-brands fa-telegram"></i> {{ managerInfo.title || 'Написать менеджеру' }}
                                </a>
                                <a v-if="managerInfo.phone" :href="'tel:' + managerInfo.phone" class="info-link" style="margin-top: 4px;">
                                    <i class="fa-solid fa-phone"></i> {{ managerInfo.phone }}
                                </a>
                            </div>
                        </div>

                        <!-- Условия доставки -->
                        <div class="info-card" v-if="settings.allow_delivery">
                            <div class="info-icon"><i class="fa-solid fa-truck-fast"></i></div>
                            <div class="info-text">
                                <span class="info-label">Доставка</span>
                                <span class="info-value" v-if="settings.min_price > 0">Мин. заказ: <strong>{{ settings.min_price }} ₽</strong></span>
                                <span v-if="settings.free_shipping_starts_from > 0" class="info-highlight">
                                    <i class="fa-solid fa-gift"></i> Бесплатно от {{ settings.free_shipping_starts_from }} ₽
                                </span>
                            </div>
                        </div>

                        <!-- Способы оплаты -->
                        <div class="info-card" v-if="hasPaymentMethods">
                            <div class="info-icon"><i class="fa-solid fa-wallet"></i></div>
                            <div class="info-text">
                                <span class="info-label">Оплата</span>
                                <div class="payment-methods">
                                    <span v-if="settings.can_use_cash" class="pay-badge" title="Наличные"><i class="fa-solid fa-money-bill-wave"></i></span>
                                    <span v-if="settings.can_use_card" class="pay-badge" title="Банковской картой"><i class="fa-solid fa-credit-card"></i></span>
                                    <span v-if="settings.can_use_qr" class="pay-badge" title="Оплата по QR-коду"><i class="fa-solid fa-qrcode"></i></span>
                                    <span v-if="hasSbp" class="pay-badge" title="СБП"><i class="fa-solid fa-mobile-screen-button"></i></span>
                                    <span v-if="settings.need_pay_after_call" class="pay-badge" title="После звонка"><i class="fa-solid fa-phone-volume"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Кнопки действий -->
                    <div class="action-buttons">
                        <button v-if="settings.has_booking" class="btn-action primary" @click="goToBooking">
                            <i class="fa-solid fa-calendar-check"></i> Забронировать стол
                        </button>
                        <button class="btn-action secondary" @click="showContactModal = true">
                            <i class="fa-solid fa-address-book"></i> Контакты и соцсети
                        </button>
                    </div>
                </div>
            </div>

            <!-- 6. Модальное окно КОНТАКТОВ -->
            <teleport to="body">
                <transition name="modal-fade">
                    <div v-if="showContactModal" class="schedule-modal-overlay" @click.self="showContactModal = false">
                        <div class="schedule-modal-content contact-modal-content">
                            <div class="modal-header">
                                <h3>Контакты</h3>
                                <button class="modal-close" @click="showContactModal = false">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="contact-list">
                                    <div class="contact-item" v-if="managerInfo.phone">
                                        <div class="contact-icon"><i class="fa-solid fa-phone"></i></div>
                                        <div class="contact-info">
                                            <span class="contact-label">Телефон</span>
                                            <a :href="'tel:' + managerInfo.phone" class="contact-value">{{ managerInfo.phone }}</a>
                                        </div>
                                    </div>
                                    <div class="contact-item" v-if="managerInfo.link">
                                        <div class="contact-icon"><i class="fa-brands fa-telegram"></i></div>
                                        <div class="contact-info">
                                            <span class="contact-label">Менеджер</span>
                                            <a :href="formatLink(managerInfo.link)" target="_blank" class="contact-value">
                                                {{ managerInfo.title || 'Написать в Telegram' }}
                                            </a>
                                        </div>
                                    </div>
                                    <div class="contact-item" v-if="settings.address">
                                        <div class="contact-icon"><i class="fa-solid fa-location-dot"></i></div>
                                        <div class="contact-info">
                                            <span class="contact-label">Адрес</span>
                                            <span class="contact-value">{{ settings.address }}</span>
                                            <a v-if="settings.shop_coords" :href="getMapLink(settings.shop_coords)" target="_blank" class="map-link">
                                                <i class="fa-solid fa-map"></i> Показать на карте
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-divider" v-if="hasSocialLinks"></div>
                                <div class="contact-socials" v-if="hasSocialLinks">
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
                            </div>
                        </div>
                    </div>
                </transition>
            </teleport>

            <!-- 🆕 7. Модальное окно ГРАФИКА РАБОТЫ (из ShopNavbar) -->
            <teleport to="body">
                <transition name="modal-fade">
                    <div v-if="showScheduleModal"
                         class="schedule-modal-overlay"
                         @click.self="showScheduleModal = false"
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
                                    v-for="(day, index) in normalizedSchedule"
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

                            <div class="modal-footer" v-if="!isOpenRightNow && todayScheduleComment">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>{{ todayScheduleComment }}</span>
                            </div>
                        </div>
                    </div>
                </transition>
            </teleport>
        </div>
    </transition>
</template>

<script>
export default {
    name: 'ShopSelectedTenantBanner',
    props: {
        tenant: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            showScheduleModal: false,
            showContactModal: false,
        };
    },
    computed: {
        settings() {
            return this.tenant.settings || {};
        },
        parsedTags() {
            if (!this.settings.venue_tags) return [];
            return this.settings.venue_tags.split(',').map(t => t.trim()).filter(t => t.length > 0);
        },
        hasSbp() {
            if (this.settings.can_use_sbp) return true;
            if (this.settings.sbp && typeof this.settings.sbp === 'object') {
                return Object.values(this.settings.sbp).some(bank => bank && bank.enabled);
            }
            return false;
        },
        hasPaymentMethods() {
            return this.settings.can_use_cash || this.settings.can_use_card || this.settings.can_use_qr || this.hasSbp || this.settings.need_pay_after_call;
        },
        tenantSchedule() {
            return this.settings.schedule || [];
        },
        hasSchedule() {
            return Array.isArray(this.tenantSchedule) && this.tenantSchedule.length > 0;
        },
        managerInfo() {
            return this.settings.manager || {};
        },
        socialLinks() {
            return {
                vk: this.settings.vk || this.tenant.vk_shop_link,
                inst: this.settings.inst,
                site: this.settings.site,
                email: this.settings.email
            };
        },
        hasSocialLinks() {
            return this.socialLinks.vk || this.socialLinks.inst || this.socialLinks.site || this.socialLinks.email || this.managerInfo.link;
        },
        themeColor() {
            return this.tenant.theme_color || this.settings.pwa?.theme_color || '#ff8a00';
        },

        // 🆕 Точная проверка, открыто ли заведение ПРЯМО СЕЙЧАС
        isOpenRightNow() {
            const schedule = this.tenantSchedule;
            if (!schedule || !Array.isArray(schedule) || schedule.length === 0) return true;

            const now = new Date();
            const jsDay = now.getDay(); // 0 = Воскресенье, 1 = Понедельник, ..., 6 = Суббота
            const arrayIndex = jsDay === 0 ? 6 : jsDay - 1; // Преобразуем: 0 = Понедельник, 6 = Воскресенье
            const todaySchedule = schedule[arrayIndex];

            if (!todaySchedule || todaySchedule.closed) return false;

            const currentMinutes = now.getHours() * 60 + now.getMinutes();
            const [startH, startM] = todaySchedule.start_at.split(':').map(Number);
            const [endH, endM] = todaySchedule.end_at.split(':').map(Number);

            const startMinutes = startH * 60 + startM;
            const endMinutes = endH * 60 + endM;

            return currentMinutes >= startMinutes && currentMinutes <= endMinutes;
        },

        // 🆕 Индекс текущего дня (0 = Понедельник, 6 = Воскресенье)
        currentDayIndex() {
            const jsDay = new Date().getDay();
            return jsDay === 0 ? 6 : jsDay - 1;
        },

        // 🆕 Комментарий для текущего дня, если закрыто
        todayScheduleComment() {
            const today = this.tenantSchedule[this.currentDayIndex];
            return today?.closed ? today.closed_comment : 'Мы сейчас не работаем. Загляните к нам в следующее время!';
        },

        // 🆕 Нормализованное расписание (гарантирует 7 дней)
        normalizedSchedule() {
            const daysDictionary = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
            const rawSchedule = this.tenantSchedule || [];

            return daysDictionary.map((dayName, index) => {
                const dayData = rawSchedule[index];
                if (dayData && dayData.start_at && dayData.end_at && !dayData.closed) {
                    return {
                        day: dayName,
                        start_at: dayData.start_at,
                        end_at: dayData.end_at,
                        closed: false,
                        closed_comment: ''
                    };
                }
                return {
                    day: dayName,
                    start_at: '',
                    end_at: '',
                    closed: true,
                    closed_comment: dayData?.closed_comment || 'Выходной'
                };
            });
        }
    },
    watch: {
        // 🆕 Блокируем скролл фона при открытой модалке
        showScheduleModal(newVal) {
            document.body.style.overflow = newVal ? 'hidden' : '';
        },
        showContactModal(newVal) {
            document.body.style.overflow = newVal ? 'hidden' : '';
        }
    },
    beforeUnmount() {
        // 🆕 Возвращаем скролл при уничтожении компонента
        document.body.style.overflow = '';
    },
    methods: {
        formatLink(url) {
            if (!url) return '#';
            return url.startsWith('http://') || url.startsWith('https://') ? url : `https://${url}`;
        },
        getMapLink(coords) {
            if (!coords) return '#';
            const parts = coords.split(',').map(c => c.trim());
            if (parts.length === 2) {
                const num1 = parseFloat(parts[0]);
                const num2 = parseFloat(parts[1]);
                if (num1 > 40 && num2 < 40) {
                    return `https://yandex.ru/maps/?pt=${num2},${num1}&z=16&l=map`;
                }
            }
            return `https://yandex.ru/maps/?pt=${coords}&z=16&l=map`;
        },
        goToBooking() {
            this.$router.push({ name: 'TableBooking' }).catch(() => {});
        }
    }
}
</script>

<style lang="scss" scoped>
// Переменные с фоллбэками на CSS-переменные компонента
$primary: var(--theme-color, #ff8a00);
$primary-light: var(--primary-light, #ffb347);
$dark: var(--dark, #0f0f14);
$success: #10b981;
$danger: #ef4444;
$gray: var(--gray, #6c757d);

.partner-banner.tenant-banner {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
    margin-bottom: 2rem;
    position: relative;
    border: 1px solid rgba(0,0,0,0.04);

    .info-icon, .contact-icon {
        background: rgba(0, 0, 0, 0.05);
        color: $primary;
    }

    .info-link, .map-link {
        color: $primary;
    }

    .btn-action.primary {
        background: $primary;
        &:hover {
            filter: brightness(0.9);
            transform: translateY(-2px);
        }
    }
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

    .btn-contact-compact {
        position: absolute;
        top: 16px;
        right: 16px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border: none;
        padding: 8px 14px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        color: $dark;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);

        &:hover {
            background: white;
            transform: translateY(-2px);
            color: $primary;
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

.partner-logo-wrapper { flex-shrink: 0; }

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
    color: $dark;
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
        &.open { background: rgba($success, 0.15); color: #047857; }
        &.closed { background: rgba($danger, 0.15); color: #b91c1c; }
        .preorder-hint { font-weight: 400; font-size: 0.75rem; margin-left: 4px; opacity: 0.8; }
    }

    &.schedule-btn {
        background: rgba(99, 102, 241, 0.1);
        color: #4338ca;
        cursor: pointer;
        transition: all 0.2s;
        &:hover { background: rgba(99, 102, 241, 0.2); transform: translateY(-1px); }
    }

    &.type { background: rgba(99, 102, 241, 0.1); color: #4338ca; }
    &.delivery { background: rgba(245, 158, 11, 0.15); color: #b45309; }
    &.pickup { background: rgba($success, 0.15); color: #047857; }
}

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
    color: $gray;
    font-weight: 500;
}

.partner-description {
    font-size: 1rem;
    line-height: 1.6;
    color: $gray;
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
    color: $gray;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: $dark;
}

.info-highlight {
    font-size: 0.85rem;
    color: #059669;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.info-link {
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
    flex-wrap: wrap;
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
    color: $dark;
    font-size: 1rem;
}

// 5. Кнопки действий
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

    &.secondary {
        background: var(--light, #fffdf8);
        color: $dark;
        border: 1px solid rgba(0,0,0,0.1);
        &:hover { background: #f1f5f9; transform: translateY(-2px); }
    }
}

// ==========================================
// 🆕 СТИЛИ МОДАЛОК (Общие + График + Контакты)
// ==========================================
.schedule-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
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
    display: flex;
    flex-direction: column;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    flex-shrink: 0;

    h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: $dark;
        display: flex;
        align-items: center;
        gap: 8px;

        i { color: $primary; }
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
    padding: 8px 24px 24px;
    max-height: 60vh;
    overflow-y: auto;
}

// 🆕 Стили для строк расписания (решают проблему "уплывания" вправо)
.schedule-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border-radius: 12px;
    transition: background 0.2s;
    margin-bottom: 4px;
    width: 100%;

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
    flex-shrink: 0;
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
    text-align: right;
    white-space: nowrap;
    margin-left: 16px;

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
    flex-shrink: 0;

    i { font-size: 1rem; }
}

// Стили для модалки контактов
.contact-modal-content {
    .contact-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
    }
    .contact-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: $gray;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .contact-value {
        font-size: 1rem;
        font-weight: 600;
        color: $dark;
        text-decoration: none;
        &:hover { text-decoration: underline; }
    }
    .map-link {
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
        text-decoration: none;
        &:hover { opacity: 0.8; }
    }
    .modal-divider {
        height: 1px;
        background: rgba(0,0,0,0.06);
        margin: 1.5rem 0;
    }
    .contact-socials {
        .social-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: $gray;
            margin-bottom: 12px;
        }
    }
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

    &.vk { background: rgba(0, 119, 255, 0.08); color: #0077ff; &:hover { background: #0077ff; } }
    &.inst { background: rgba(225, 48, 108, 0.08); color: #e1306c; &:hover { background: #e1306c; } }
    &.site { background: rgba(108, 117, 125, 0.08); color: #6c757d; &:hover { background: #6c757d; } }
    &.email { background: rgba(220, 53, 69, 0.08); color: #dc3545; &:hover { background: #dc3545; } }
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
    transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
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
