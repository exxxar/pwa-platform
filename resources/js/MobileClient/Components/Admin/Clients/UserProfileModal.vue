<template>
    <div class="profile-modal" :class="{ 'is-mobile': isMobile }">

        <!-- Закрытие (мобильная версия) -->
        <div class="mobile-handle" @click="$emit('close')">
            <div class="handle-bar"></div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Загружаем профиль...</p>
        </div>

        <!-- Профиль -->
        <template v-else-if="user">

            <!-- Шапка профиля (компактная) -->
            <div class="profile-header">
                <button class="close-btn" @click="$emit('close')">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="header-content">
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            {{ getInitials(user.name) }}
                        </div>
                        <div class="status-indicator" :class="getStatusClass"></div>
                    </div>

                    <div class="header-info">
                        <h2 class="profile-name">{{ user.name || 'Без имени' }}</h2>

                        <div class="profile-meta">
                            <span class="meta-id">ID: {{ user.id }}</span>
                            <span class="meta-separator">·</span>
                            <span class="meta-uuid">{{ user.uuid?.substring(0, 8) }}</span>
                        </div>

                        <div class="profile-roles">
                <span
                    v-for="role in user.role_names || ['guest']"
                    :key="role"
                    class="role-badge"
                    :class="`role-${role}`"
                >
                    {{ getRoleLabel(role) }}
                </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Контент -->
            <div class="profile-body">

                <!-- Статусы -->
                <section class="info-section">
                    <h3 class="section-title">
                        <i class="fa-solid fa-circle-info"></i>
                        Статусы
                    </h3>
                    <div class="status-grid">
                        <div class="status-item" :class="{ 'is-active': user.is_active }">
                            <i :class="user.is_active ? 'fa-solid fa-check-circle' : 'fa-solid fa-times-circle'"></i>
                            <span>{{ user.is_active ? 'Активен' : 'Заблокирован' }}</span>
                        </div>
                        <div class="status-item" :class="{ 'is-vip': user.is_vip }">
                            <i class="fa-solid fa-crown"></i>
                            <span>{{ user.is_vip ? 'VIP клиент' : 'Обычный' }}</span>
                        </div>
                        <div class="status-item" :class="{ 'is-blocked': user.blocked_at }">
                            <i class="fa-solid fa-ban"></i>
                            <span>{{ user.blocked_at ? 'Заблокирован' : 'Не заблокирован' }}</span>
                        </div>
                    </div>

                    <div v-if="user.is_vip && user.vip_expires_at" class="vip-info">
                        <i class="fa-solid fa-calendar"></i>
                        VIP до: {{ formatDate(user.vip_expires_at) }}
                    </div>

                    <div v-if="user.blocked_at" class="blocked-info">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Заблокирован</strong>
                            <p>{{ formatDate(user.blocked_at) }}</p>
                            <p v-if="user.blocked_message">{{ user.blocked_message }}</p>
                        </div>
                    </div>
                </section>

                <!-- Контакты -->
                <section class="info-section">
                    <h3 class="section-title">
                        <i class="fa-solid fa-address-book"></i>
                        Контактная информация
                    </h3>
                    <div class="info-list">
                        <div class="info-item" v-if="user.phone">
                            <i class="fa-solid fa-phone"></i>
                            <div>
                                <span class="label">Телефон</span>
                                <span class="value">{{ user.phone }}</span>
                            </div>
                        </div>
                        <div class="info-item" v-if="user.email">
                            <i class="fa-solid fa-envelope"></i>
                            <div>
                                <span class="label">Email</span>
                                <span class="value">{{ user.email }}</span>
                            </div>
                        </div>
                        <div class="info-item" v-if="user.birthday">
                            <i class="fa-solid fa-cake-candles"></i>
                            <div>
                                <span class="label">День рождения</span>
                                <span class="value">{{ formatDate(user.birthday) }}</span>
                            </div>
                        </div>
                        <div class="info-item" v-if="user.sex">
                            <i class="fa-solid fa-venus-mars"></i>
                            <div>
                                <span class="label">Пол</span>
                                <span class="value">{{ getSexLabel(user.sex) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Статистика -->
                <section class="info-section">
                    <h3 class="section-title">
                        <i class="fa-solid fa-chart-simple"></i>
                        Статистика
                    </h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon referrals">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ user.referrals_count || 0 }}</div>
                                <div class="stat-label">Рефералов</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon friends">
                                <i class="fa-solid fa-heart"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ user.friends_count || 0 }}</div>
                                <div class="stat-label">Друзей</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon earnings">
                                <i class="fa-solid fa-coins"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ formatPrice(user.total_referral_earnings) }}</div>
                                <div class="stat-label">Заработано</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon cashback">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ formatPrice(user.cashback_balance) }}</div>
                                <div class="stat-label">Кэшбэк</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Настройки -->
                <section v-if="user.settings" class="info-section">
                    <h3 class="section-title">
                        <i class="fa-solid fa-sliders"></i>
                        Настройки
                    </h3>
                    <div class="settings-list">
                        <div class="setting-item">
                            <span>Рассылка от бота</span>
                            <span class="setting-value" :class="user.settings.need_bot_mailing ? 'active' : 'inactive'">
                                {{ user.settings.need_bot_mailing ? 'Включена' : 'Выключена' }}
                            </span>
                        </div>
                        <div class="setting-item" v-if="user.settings.favorites?.length">
                            <span>Избранные товары</span>
                            <span class="setting-value">{{ user.settings.favorites.length }} шт.</span>
                        </div>
                        <div class="setting-item" v-if="user.settings.fav_partners?.length">
                            <span>Избранные партнёры</span>
                            <span class="setting-value">{{ user.settings.fav_partners.length }} шт.</span>
                        </div>
                        <div class="setting-item" v-if="user.settings.coffee">
                            <span>Кофейная программа</span>
                            <span class="setting-value">{{ user.settings.coffee.count || 0 }} / 7</span>
                        </div>
                        <div class="setting-item" v-if="user.settings.current_promocodes?.length">
                            <span>Активные промокоды</span>
                            <span class="setting-value">{{ user.settings.current_promocodes.length }} шт.</span>
                        </div>
                    </div>
                </section>

                <!-- Адреса -->
                <section v-if="user.addresses?.length" class="info-section">
                    <h3 class="section-title">
                        <i class="fa-solid fa-location-dot"></i>
                        Адреса ({{ user.addresses.length }})
                    </h3>
                    <div class="addresses-list">
                        <div
                            v-for="address in user.addresses"
                            :key="address.id"
                            class="address-card"
                            :class="{ 'is-default': address.id === user.default_address }"
                        >
                            <i class="fa-solid fa-house"></i>
                            <div class="address-info">
                                <div class="address-title">
                                    {{ address.title || 'Адрес' }}
                                    <span v-if="address.id === user.default_address" class="default-badge">
                                        Основной
                                    </span>
                                </div>
                                <div class="address-text">{{ address.address }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Реферальная ссылка -->
                <section v-if="user.referral_link" class="info-section">
                    <h3 class="section-title">
                        <i class="fa-solid fa-link"></i>
                        Реферальная ссылка
                    </h3>
                    <div class="referral-link">
                        <input
                            type="text"
                            :value="user.referral_link"
                            readonly
                            class="link-input"
                            ref="referralInput"
                        >
                        <button class="copy-btn" @click="copyReferralLink">
                            <i class="fa-solid fa-copy"></i>
                            <span>{{ copied ? 'Скопировано!' : 'Копировать' }}</span>
                        </button>
                    </div>
                </section>

                <!-- Даты -->
                <section class="info-section">
                    <h3 class="section-title">
                        <i class="fa-solid fa-clock"></i>
                        Даты
                    </h3>
                    <div class="dates-list">
                        <div class="date-item">
                            <span class="label">Регистрация</span>
                            <span class="value">{{ formatDateTime(user.created_at) }}</span>
                        </div>
                        <div class="date-item">
                            <span class="label">Последнее обновление</span>
                            <span class="value">{{ formatDateTime(user.updated_at) }}</span>
                        </div>
                    </div>
                </section>

            </div>


            <!-- Действия -->
            <div class="profile-actions">
                <button class="action-btn primary" @click="$emit('message', user)">
                    <i class="fa-solid fa-comment"></i>
                    <span>Написать</span>
                </button>
                <button class="action-btn secondary" @click="openEditModal">
                    <i class="fa-solid fa-pen"></i>
                    <span>Редактировать</span>
                </button>
            </div>

        </template>

        <!-- Пустое состояние -->
        <div v-else class="empty-state">
            <i class="fa-solid fa-user-slash"></i>
            <p>Пользователь не найден</p>
        </div>

        <!-- Модалка редактирования -->
        <transition name="modal-fade">
            <div
                v-if="showEditModal"
                class="edit-overlay"
                @click.self="closeEditModal"
            >
                <UserEditModal
                    :user="user"
                    :loading="isSaving"
                    @close="closeEditModal"
                    @save="handleSave"
                    @toggle-block="handleToggleBlock"
                />
            </div>
        </transition>
    </div>
</template>

<script>
import UserEditModal from '@/MobileClient/Components/Admin/Clients/UserEditModal.vue';
import { useUsers } from '@/MobileClient/Composables/useUsers.js';

export default {
    name: 'UserProfileModal',
    components: {
        UserEditModal,
    },
    props: {
        user: {
            type: Object,
            default: null,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['close', 'message'],
    setup() {
        const users = useUsers();
        return { ...users };
    },
    data() {
        return {
            showEditModal: false,
            copied: false,
            isMobile: window.innerWidth < 640,
        };
    },

    computed: {
        getStatusClass() {
            if (this.user?.blocked_at) return 'blocked';
            if (this.user?.is_active) return 'active';
            return 'inactive';
        },
    },

    methods: {
        openEditModal() {
            this.showEditModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeEditModal() {
            this.showEditModal = false;
            document.body.style.overflow = '';
        },

        async handleSave(data) {
            try {
                await this.updateUser(this.user.id, data);

                // Обновляем данные пользователя
                this.$emit('updated', this.currentUser);
            } catch (error) {
                throw error;
            }
        },

        async handleToggleBlock(blockData) {
            try {
                await this.toggleBlock(this.user.id, blockData);
                this.$emit('updated', this.currentUser);
            } catch (error) {
                throw error;
            }
        },
        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        },

        getRoleLabel(role) {
            const labels = {
                guest: 'Гость',
                user: 'Пользователь',
                admin: 'Администратор',
                manager: 'Менеджер',
                deliveryman: 'Курьер',
            };
            return labels[role] || role;
        },

        getSexLabel(sex) {
            const labels = {
                male: 'Мужской',
                female: 'Женский',
                other: 'Другой',
            };
            return labels[sex] || sex;
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
            });
        },

        formatDateTime(date) {
            if (!date) return '';
            return new Date(date).toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
        },

        async copyReferralLink() {
            try {
                await navigator.clipboard.writeText(this.user.referral_link);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            } catch (err) {
                // Fallback
                this.$refs.referralInput.select();
                document.execCommand('copy');
            }
        },

        editUser() {
            this.$notify?.({
                title: 'Информация',
                text: 'Редактирование профиля в разработке',
                type: 'info',
            });
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$purple: #8b5cf6;
$cyan: #06b6d4;
$bg: #ffffff;
$bg-secondary: #f9fafb;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;

.profile-modal {
    background: $bg;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s ease;

    &.is-mobile {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 20px 20px 0 0;
    }
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mobile-handle {
    display: none;
    padding: 12px;
    cursor: pointer;

    .handle-bar {
        width: 40px;
        height: 4px;
        background: $border;
        border-radius: 2px;
        margin: 0 auto;
    }
}

// ==========================================
// ЗАГРУЗКА
// ==========================================
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    color: $text-muted;

    .loading-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 16px;
    }

    p {
        margin: 0;
        font-size: 0.95rem;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// 🆕 КОМПАКТНАЯ ШАПКА ПРОФИЛЯ
// ==========================================
.profile-header {
    position: relative;
    padding: 20px 20px 16px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
}

.close-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: all 0.2s;
    z-index: 2;

    &:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.1);
    }
}

.header-content {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-right: 32px; // Отступ под кнопку закрытия
}

.profile-avatar {
    position: relative;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
}

.avatar-circle {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    font-weight: 700;
}

.status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid white;

    &.active {
        background: $success;
    }

    &.inactive {
        background: $text-muted;
    }

    &.blocked {
        background: $danger;
    }
}

.header-info {
    flex: 1;
    min-width: 0;
}

.profile-name {
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0 0 4px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    opacity: 0.85;
    margin-bottom: 8px;
    font-family: monospace;

    .meta-separator {
        opacity: 0.5;
    }
}

.profile-roles {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.role-badge {
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(5px);

    &.role-admin {
        background: rgba($danger, 0.3);
    }

    &.role-manager {
        background: rgba($purple, 0.3);
    }

    &.role-deliveryman {
        background: rgba($success, 0.3);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .profile-header {
        padding: 16px 14px 14px;
    }

    .header-content {
        gap: 12px;
    }

    .profile-avatar {
        width: 52px;
        height: 52px;
    }

    .avatar-circle {
        font-size: 1.2rem;
    }

    .profile-name {
        font-size: 1rem;
    }

    .profile-meta {
        font-size: 0.7rem;
    }

    .role-badge {
        padding: 2px 8px;
        font-size: 0.65rem;
    }
}
// ==========================================
// КОНТЕНТ
// ==========================================
.profile-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: $bg-secondary;
}

.info-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid $border;

    i {
        color: $primary;
        font-size: 1rem;
    }
}

// Статусы
.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: $bg-secondary;
    border-radius: 10px;
    font-size: 0.85rem;
    color: $text-muted;

    i {
        font-size: 1rem;
    }

    &.is-active {
        background: rgba($success, 0.1);
        color: $success;
    }

    &.is-vip {
        background: rgba($warning, 0.1);
        color: color.adjust($warning, $lightness: -15%);
    }

    &.is-blocked {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.vip-info {
    margin-top: 12px;
    padding: 10px 12px;
    background: rgba($warning, 0.08);
    border-radius: 8px;
    font-size: 0.85rem;
    color: $text;
    display: flex;
    align-items: center;
    gap: 8px;

    i {
        color: $warning;
    }
}

.blocked-info {
    margin-top: 12px;
    padding: 12px;
    background: rgba($danger, 0.08);
    border: 1px solid rgba($danger, 0.2);
    border-radius: 10px;
    display: flex;
    gap: 10px;

    i {
        color: $danger;
        font-size: 1.2rem;
        margin-top: 2px;
    }

    strong {
        display: block;
        color: $danger;
        margin-bottom: 4px;
    }

    p {
        margin: 0;
        font-size: 0.8rem;
        color: $text-muted;
    }
}

// Контакты
.info-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: $bg-secondary;
    border-radius: 10px;

    > i {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba($primary, 0.1);
        color: $primary;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    > div {
        flex: 1;
        min-width: 0;
    }

    .label {
        display: block;
        font-size: 0.7rem;
        color: $text-muted;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 2px;
    }

    .value {
        display: block;
        font-size: 0.9rem;
        color: $text;
        font-weight: 600;
        word-break: break-all;
    }
}

// Статистика
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: $bg-secondary;
    border-radius: 10px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;

    &.referrals { background: linear-gradient(135deg, $primary, $primary-dark); }
    &.friends { background: linear-gradient(135deg, $danger, #dc2626); }
    &.earnings { background: linear-gradient(135deg, $success, #059669); }
    &.cashback { background: linear-gradient(135deg, $warning, #d97706); }
}

.stat-info {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: $text;
    line-height: 1;
    margin-bottom: 2px;
}

.stat-label {
    font-size: 0.7rem;
    color: $text-muted;
}

// Настройки
.settings-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.setting-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: $bg-secondary;
    border-radius: 8px;
    font-size: 0.85rem;

    > span:first-child {
        color: $text;
    }
}

.setting-value {
    font-weight: 600;

    &.active {
        color: $success;
    }

    &.inactive {
        color: $text-muted;
    }
}

// Адреса
.addresses-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.address-card {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    background: $bg-secondary;
    border-radius: 10px;
    border: 1px solid $border;

    &.is-default {
        border-color: $primary;
        background: rgba($primary, 0.03);
    }

    > i {
        color: $primary;
        font-size: 1rem;
        margin-top: 2px;
    }
}

.address-info {
    flex: 1;
    min-width: 0;
}

.address-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: $text;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.default-badge {
    padding: 2px 8px;
    background: $primary;
    color: white;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
}

.address-text {
    font-size: 0.8rem;
    color: $text-muted;
}

// Реферальная ссылка
.referral-link {
    display: flex;
    gap: 8px;
}

.link-input {
    flex: 1;
    padding: 10px 12px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: 0.8rem;
    font-family: monospace;
    color: $text;
}

.copy-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $primary-dark;
    }
}

// Даты
.dates-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.date-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    background: $bg-secondary;
    border-radius: 8px;
    font-size: 0.85rem;

    .label {
        color: $text-muted;
    }

    .value {
        color: $text;
        font-weight: 600;
    }
}

// ==========================================
// ДЕЙСТВИЯ
// ==========================================
.profile-actions {
    display: flex;
    gap: 10px;
    padding: 16px;
    background: $bg;
    border-top: 1px solid $border;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &.primary {
        background: linear-gradient(135deg, $primary, $primary-dark);
        color: white;
        box-shadow: 0 4px 12px rgba($primary, 0.3);

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba($primary, 0.4);
        }
    }

    &.secondary {
        background: $bg-secondary;
        color: $text;
        border: 1px solid $border;

        &:hover {
            border-color: $primary;
            color: $primary;
        }
    }
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: $text-muted;

    i {
        font-size: 3rem;
        opacity: 0.3;
        margin-bottom: 12px;
    }

    p {
        margin: 0;
        font-size: 1rem;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .mobile-handle {
        display: block;
    }

    .profile-header {
        padding: 20px 16px;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
    }

    .profile-name {
        font-size: 1.3rem;
    }

    .profile-body {
        padding: 16px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .profile-actions {
        flex-direction: column;
    }
}

.edit-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

@media (max-width: 640px) {
    .edit-overlay {
        padding: 0;
        align-items: flex-end;
    }
}
</style>
