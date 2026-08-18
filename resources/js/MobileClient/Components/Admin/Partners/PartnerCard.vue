<template>
    <div class="partner-list-item">
        <!-- 1. Аватар или Картинка -->
        <div class="partner-avatar" :style="!partner.image ? avatarStyle : {}">
            <img
                v-if="partner.image"
                v-lazy="partner.image"
                alt="Partner"
                class="avatar-img"
            >
            <i v-else class="fa-solid fa-user-tie"></i>
        </div>

        <!-- 2. Основная информация -->
        <div class="partner-info">
            <div class="info-top">
                <h4 class="partner-title">{{ partner.title }}</h4>

                <!-- Позиция в выдаче -->
                <span class="order-badge" v-if="partner.order_position !== null && partner.order_position !== undefined">
                    <i class="fa-solid fa-arrow-up-short-wide"></i>
                    #{{ partner.order_position }}
                </span>
            </div>

            <!-- Блок тегов (максимум 2 + счетчик остальных) -->
            <div class="partner-tags" v-if="Array.isArray(partner.tags) && partner.tags.length > 0">
                <span
                    v-for="(tag, index) in partner.tags.slice(0, 2)"
                    :key="index"
                    class="partner-tag"
                >
                    {{ tag }}
                </span>
                <span v-if="partner.tags.length > 2" class="partner-tag tag-more">
                    +{{ partner.tags.length - 2 }}
                </span>
            </div>

            <div class="partner-meta">
                <span class="meta-item">
                    <i class="fa-solid fa-cube"></i>
                    {{ partner.products_count || 0 }} товаров
                </span>
                <span v-if="partner.extra_charge" class="meta-item charge">
                    <i class="fa-solid fa-percent"></i>
                    {{ partner.extra_charge }}%
                </span>
                <!-- 🆕 Индикатор наличия ссылки -->
                <span v-if="partner.partner_slug" class="meta-item link-badge">
                    <i class="fa-solid fa-globe"></i>
                </span>
            </div>
        </div>

        <!-- 3. Статус и Кнопка вызова модалки -->
        <div class="partner-controls">
            <div class="status-badge" :class="partner.is_active ? 'active' : 'inactive'">
                <i :class="partner.is_active ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
            </div>

            <button class="menu-trigger-btn" @click="showModal = true" title="Действия">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        </div>
    </div>

    <!-- 4. Нижняя модалка (Bottom Sheet) -->
    <Teleport to="body">
        <transition name="sheet-fade">
            <div v-if="showModal" class="bottom-sheet-overlay" @click="showModal = false">
                <transition name="sheet-slide">
                    <div v-if="showModal" class="bottom-sheet-content" @click.stop>
                        <!-- Декоративная ручка -->
                        <div class="sheet-handle"></div>

                        <!-- Заголовок модалки -->
                        <div class="sheet-header">
                            <h5 class="sheet-title">Действия с партнёром</h5>
                            <p class="sheet-subtitle">{{ partner.title }}</p>
                        </div>

                        <!-- Основные действия -->
                        <div class="sheet-actions-group">
                            <!-- 🆕 КНОПКА ПЕРЕХОДА К ПАРТНЕРУ -->
                            <button class="sheet-action" @click="handleAction('open')">
                                <div class="action-icon bg-success-subtle">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-success"></i>
                                </div>
                                <span class="action-text">Открыть приложение</span>
                                <i class="fa-solid fa-chevron-right action-arrow"></i>
                            </button>

                            <button class="sheet-action" @click="handleAction('edit')">
                                <div class="action-icon bg-primary-subtle">
                                    <i class="fa-solid fa-pen text-primary"></i>
                                </div>
                                <span class="action-text">Редактировать</span>
                                <i class="fa-solid fa-chevron-right action-arrow"></i>
                            </button>

                            <button class="sheet-action" @click="handleAction('products')">
                                <div class="action-icon bg-info-subtle">
                                    <i class="fa-solid fa-store text-info"></i>
                                </div>
                                <span class="action-text">Товары партнёра</span>
                                <i class="fa-solid fa-chevron-right action-arrow"></i>
                            </button>

                            <button
                                class="sheet-action"
                                @click="handleAction('toggle-active')"
                                :disabled="isPartnerLoading(partner.id)"
                            >
                                <div class="action-icon" :class="partner.is_active ? 'bg-success-subtle' : 'bg-secondary-subtle'">
                                    <i v-if="isPartnerLoading(partner.id)" class="fa-solid fa-spinner fa-spin text-warning"></i>
                                    <i v-else class="fa-solid" :class="partner.is_active ? 'fa-toggle-on text-success' : 'fa-toggle-off text-secondary'"></i>
                                </div>
                                <span class="action-text">
                                    {{ partner.is_active ? 'Деактивировать' : 'Активировать' }}
                                </span>
                                <i class="fa-solid fa-chevron-right action-arrow"></i>
                            </button>
                        </div>

                        <div class="sheet-divider"></div>

                        <!-- Опасные действия -->
                        <div class="sheet-actions-group">
                            <button class="sheet-action danger" @click="handleAction('remove')">
                                <div class="action-icon bg-danger-subtle">
                                    <i class="fa-solid fa-trash text-danger"></i>
                                </div>
                                <span class="action-text">Удалить партнёра</span>
                            </button>
                        </div>

                        <!-- Отступ для безопасной зоны iPhone -->
                        <div class="sheet-safe-area"></div>
                    </div>
                </transition>
            </div>
        </transition>
    </Teleport>
</template>

<script>
import { usePartners } from '@/MobileClient/Composables/usePartners.js'

export default {
    name: 'PartnerCard',

    props: {
        partner: {
            type: Object,
            required: true,
        },
    },

    emits: ['edit', 'products', 'remove', 'toggle-active'],

    data() {
        return {
            showModal: false,
        }
    },

    setup() {
        const partners = usePartners()
        return {
            isPartnerLoading: partners.isPartnerLoading,
            // 🚨 Примечание: вызов loadProducts в mounted для каждого элемента списка
            // может вызвать избыточную нагрузку на API.
            // Лучше загружать товары только при открытии модалки "Товары партнёра".
            loadProducts: partners.loadProductsByCategory
        }
    },

    mounted() {
        // Если нужно превью товаров прямо в списке, оставьте этот вызов.
        // this.loadProducts({ partner_id: this.partner.tenant_partner_id || this.partner.id })

        this.$watch('showModal', (isOpen) => {
            document.body.style.overflow = isOpen ? 'hidden' : ''
        })
    },

    beforeUnmount() {
        document.body.style.overflow = ''
    },

    computed: {
        avatarStyle() {
            const colors = [
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            ]
            const id = this.partner.id || Math.floor(Math.random() * 1000)
            return { background: colors[id % colors.length] }
        },
    },

    methods: {
        handleAction(action) {
            this.showModal = false

            // 🆕 Обработка открытия приложения
            if (action === 'open') {
                this.openPartnerApp()
                return
            }

            this.$emit(action, this.partner)
        },

        // 🆕 Метод открытия ссылки
        openPartnerApp() {
            const slug = this.partner.partner_slug

            if (slug) {
                const url = `https://${slug}.mypwa.ru`
                window.open(url, '_blank')
            } else {
                this.$notify?.({
                    title: 'Информация',
                    text: 'Ссылка на приложение этого партнёра недоступна',
                    type: 'warning'
                })
            }
        }
    }
}
</script>



<style lang="scss" scoped>
@use 'sass:color';

$admin-bg: #f8f9fa;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-warning: #f59e0b;
$admin-danger: #ef4444;
$admin-info: #0ea5e9;

/* ==========================================
   КАРТОЧКА ПАРТНЕРА (Список)
   ========================================== */
.partner-list-item {
    display: flex;
    align-items: center;
    gap: 14px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 12px 16px;
    transition: all 0.2s ease;
    position: relative;

    &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border-color: color.adjust($admin-border, $lightness: -8%);
    }
}

.partner-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);

    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.partner-info {
    flex: 1;
    min-width: 0;
}

.info-top {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px; // 🆕 Чуть увеличили отступ для тегов
}

.partner-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.order-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: #8BC34A;
    color: #ffffff;
    font-size: 0.5rem;
    font-weight: 700;
    border-radius: 6px;
    flex-shrink: 0;
    position: absolute;
    top: -9px;
    left: 10px;
}

/* 🆕 Стили для тегов в карточке */
.partner-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 6px;
}

.partner-tag {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    background: rgba($admin-primary, 0.08);
    color: $admin-primary;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    line-height: 1.4;

    &.tag-more {
        background: rgba($admin-text-muted, 0.1);
        color: $admin-text-muted;
        font-weight: 500;
    }
}

.partner-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.75rem;
    color: $admin-text-muted;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;

    &.charge { color: $admin-warning; font-weight: 600; }

    /* 🆕 Стили для индикатора ссылки */
    &.link-badge {
        color: $admin-success;
        background: rgba($admin-success, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
    }
}

.partner-controls {
    display: flex;
    align-items: center;
    gap: 0px;
    flex-shrink: 0;
}

.status-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-size: 0.7rem;

    &.active {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }

    &.inactive {
        background: rgba($admin-text-muted, 0.1);
        color: $admin-text-muted;
    }
}

.menu-trigger-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    color: $admin-text-muted;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $admin-bg;
        color: $admin-text;
    }
}

/* ==========================================
   BOTTOM SHEET (Нижняя модалка)
   ========================================== */
.bottom-sheet-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.bottom-sheet-content {
    width: 100%;
    max-width: 500px;
    background: $admin-card-bg;
    border-radius: 24px 24px 0 0;
    padding: 8px 20px 0;
    box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.15);
}

.sheet-handle {
    width: 40px;
    height: 4px;
    background: $admin-border;
    border-radius: 2px;
    margin: 0 auto 20px;
}

.sheet-header {
    text-align: center;
    margin-bottom: 24px;
}

.sheet-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0 0 4px 0;
}

.sheet-subtitle {
    font-size: 0.85rem;
    color: $admin-text-muted;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sheet-actions-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 8px;
}

.sheet-action {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    padding: 14px 16px;
    background: transparent;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    transition: background 0.15s;
    text-align: left;

    &:hover {
        background: $admin-bg;
    }

    &:active {
        transform: scale(0.98);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    &.danger {
        color: $admin-danger;
        &:hover {
            background: rgba($admin-danger, 0.06);
        }
    }
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.bg-primary-subtle { background: rgba($admin-primary, 0.1); }
.bg-info-subtle { background: rgba($admin-info, 0.1); }
.bg-success-subtle { background: rgba($admin-success, 0.1); }
.bg-secondary-subtle { background: rgba($admin-text-muted, 0.1); }
.bg-danger-subtle { background: rgba($admin-danger, 0.1); }

.text-primary { color: $admin-primary !important; }
.text-info { color: $admin-info !important; }
.text-success { color: $admin-success !important; }
.text-secondary { color: $admin-text-muted !important; }
.text-danger { color: $admin-danger !important; }
.text-warning { color: $admin-warning !important; }

.action-text {
    flex: 1;
    font-size: 0.95rem;
    font-weight: 600;
    color: $admin-text;

    .danger & {
        color: $admin-danger;
    }
}

.action-arrow {
    color: $admin-text-muted;
    font-size: 0.8rem;
    opacity: 0.5;
}

.sheet-divider {
    height: 1px;
    background: $admin-border;
    margin: 8px 0 16px;
}

.sheet-safe-area {
    height: env(safe-area-inset-bottom, 20px);
    min-height: 20px;
}

/* ==========================================
   АНИМАЦИИ
   ========================================== */
.sheet-fade-enter-active,
.sheet-fade-leave-active {
    transition: opacity 0.3s ease;
}
.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

.sheet-slide-enter-active,
.sheet-slide-leave-active {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.sheet-slide-enter-from,
.sheet-slide-leave-to {
    transform: translateY(100%);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .partner-list-item {
        gap: 12px;
        padding: 12px;
    }

    .partner-avatar {
        width: 42px;
        height: 42px;
        font-size: 1rem;
    }

    .partner-title {
        font-size: 0.9rem;
    }

    .partner-meta {
        gap: 8px;
        font-size: 0.7rem;
    }

    .partner-tag {
        font-size: 0.65rem;
        padding: 2px 6px;
    }
}
</style>
