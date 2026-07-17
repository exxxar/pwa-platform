<template>
    <div
        class="modal fade"
        id="bot-info-modal"
        tabindex="-1"
        aria-labelledby="shopInfoModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content shop-info-modal">

                <!-- Шапка с градиентом -->
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="shop-icon-wrapper">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-1" id="shopInfoModalLabel">
                                {{ tenant?.name || 'Наш магазин' }}
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-badge" :class="isWork ? 'open' : 'closed'">
                                    <i :class="isWork ? 'fa-solid fa-circle-check' : 'fa-solid fa-clock'"></i>
                                    {{ isWork ? 'Сейчас открыто' : 'Сейчас закрыто' }}
                                </span>
                                <button
                                    v-if="!isWork"
                                    class="btn btn-sm btn-link text-decoration-underline p-0"
                                    style="font-size: 0.8rem;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#schedule-list-display"
                                >
                                    График работы
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button
                            v-if="isAdmin"
                            class="btn btn-sm btn-outline-primary rounded-circle"
                            style="width: 36px; height: 36px; padding: 0;"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-shop-footer-description-modal"
                            title="Редактировать"
                        >
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Закрыть"
                        ></button>
                    </div>
                </div>

                <div class="modal-body pt-4">

                    <!-- Основной блок: Адрес и Телефон -->
                    <div class="info-card mb-4">
                        <div v-if="settings?.company?.address" class="info-row">
                            <div class="info-icon bg-primary-subtle text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Адрес</div>
                                <div class="info-value">{{ settings.company.address }}</div>
                            </div>
                        </div>

                        <div v-if="settings?.company?.phones?.length > 0" class="info-row">
                            <div class="info-icon bg-success-subtle text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Телефон</div>
                                <a :href="'tel:' + settings.company.phones[0]" class="info-value phone-link">
                                    {{ settings.company.phones[0] }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Социальные сети и контакты -->
                    <div v-if="hasContacts" class="mb-4">
                        <h6 class="section-title">Свяжитесь с нами</h6>
                        <div class="social-grid">
                            <a v-if="links.vk" :href="formatLink(links.vk)" target="_blank" class="social-btn vk">
                                <i class="fa-brands fa-vk"></i>
                                <span>ВКонтакте</span>
                            </a>
                            <a v-if="links.inst" :href="formatLink(links.inst)" target="_blank" class="social-btn inst">
                                <i class="fa-brands fa-instagram"></i>
                                <span>Instagram</span>
                            </a>
                            <a v-if="links.site" :href="formatLink(links.site)" target="_blank" class="social-btn site">
                                <i class="fa-solid fa-globe"></i>
                                <span>Сайт</span>
                            </a>
                            <a v-if="settings?.email" :href="'mailto:' + settings.email" class="social-btn email">
                                <i class="fa-solid fa-envelope"></i>
                                <span>Email</span>
                            </a>
                        </div>
                    </div>

                    <!-- Виджет карты -->
                    <div v-if="links.map_link" class="mb-4">
                        <h6 class="section-title">Мы на карте</h6>
                        <div class="map-wrapper">
                            <div v-html="links.map_link" class="map-iframe-container"></div>
                        </div>
                    </div>

                    <!-- Описание -->
                    <div v-if="tenant?.description" class="description-block">
                        <h6 class="section-title">О нас</h6>
                        <p class="description-text">{{ tenant.description }}</p>
                    </div>

                </div>

                <!-- Футер с действиями -->
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button
                        v-if="settings?.can_use_booking"
                        class="btn btn-primary btn-lg w-100 mb-2 shadow-sm"
                        @click="goToBookingTable"
                    >
                        <i class="fa-solid fa-calendar-check me-2"></i>
                        Забронировать столик
                    </button>
                    <button
                        class="btn btn-light w-100"
                        data-bs-dismiss="modal"
                    >
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ShopInfoModal',

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        isAdmin() {
            const user = window.TenantUser;
            return user?.is_admin === true || user?.role === 'admin';
        },

        isWork() {
            if (!this.settings?.company?.schedule) return true;
            if (typeof window.isCorrectSchedule !== 'function') return true;
            if (!window.isCorrectSchedule(this.settings.company.schedule)) return true;
            return this.settings.is_work ?? true;
        },

        links() {
            return this.settings?.company?.links || {};
        },

        hasContacts() {
            return this.links.vk || this.links.inst || this.links.site || this.settings?.email;
        }
    },

    methods: {
        formatLink(url) {
            if (!url) return '#';
            // Добавляем https:// если протокол не указан
            return url.startsWith('http://') || url.startsWith('https://')
                ? url
                : `https://${url}`;
        },

        goToBookingTable() {
            // Закрываем текущую модалку
            this.closeModal('bot-info-modal');
            // Переходим к бронированию
            this.$router.push({ name: 'TableBooking' }).catch(() => {});
        },

        closeModal(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    }
};
</script>

<style lang="scss" scoped>
// ==========================================
// ОСНОВНЫЕ СТИЛИ МОДАЛКИ
// ==========================================
.shop-info-modal {
    border: none;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    overflow: hidden;

    .modal-header {
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
        padding: 20px 24px;
    }

    .shop-icon-wrapper {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 8px 16px rgba(var(--bs-primary-rgb), 0.3);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;

        &.open {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        &.closed {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
    }
}

// ==========================================
// ИНФОРМАЦИОННЫЕ БЛОКИ
// ==========================================
.info-card {
    background: var(--bs-light);
    border-radius: 16px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
    min-width: 0;
}

.info-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
    font-weight: 600;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--bs-body-color);
    word-break: break-word;
}

.phone-link {
    color: var(--bs-primary);
    text-decoration: none;
    transition: color 0.2s;

    &:hover {
        color: var(--bs-primary-hover, var(--bs-primary));
        text-decoration: underline;
    }
}

// ==========================================
// СОЦИАЛЬНЫЕ СЕТИ
// ==========================================
.section-title {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--bs-secondary-color);
    margin-bottom: 12px;
}

.social-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    &.vk {
        background: rgba(0, 119, 255, 0.08);
        color: #0077ff;
        &:hover { background: #0077ff; color: white; }
    }
    &.inst {
        background: rgba(225, 48, 108, 0.08);
        color: #e1306c;
        &:hover { background: #e1306c; color: white; }
    }
    &.site {
        background: rgba(108, 117, 125, 0.08);
        color: #6c757d;
        &:hover { background: #6c757d; color: white; }
    }
    &.email {
        background: rgba(220, 53, 69, 0.08);
        color: #dc3545;
        &:hover { background: #dc3545; color: white; }
    }
}

// ==========================================
// КАРТА
// ==========================================
.map-wrapper {
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    background: var(--bs-light);
}

.map-iframe-container {
    width: 100%;
    height: 100%;

    :deep(iframe) {
        width: 100% !important;
        height: 250px !important;
        border: none;
        display: block;
    }
}

// ==========================================
// ОПИСАНИЕ
// ==========================================
.description-block {
    background: var(--bs-light);
    border-radius: 16px;
    padding: 16px;
}

.description-text {
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--bs-body-color);
    margin: 0;
    white-space: pre-wrap;
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 576px) {
    .shop-info-modal .modal-header {
        padding: 16px;
    }

    .shop-icon-wrapper {
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
    }

    .modal-title {
        font-size: 1.1rem;
    }

    .social-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
