<template>
    <div class="referrals-page">

        <!-- ========================================== -->
        <!-- HERO -->
        <!-- ========================================== -->
        <div class="referrals-hero">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
                <h2 class="hero-title">Реферальная программа</h2>
                <p class="hero-subtitle">
                    Приглашайте друзей и получайте бонусы с их покупок
                </p>

                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-value">{{ totalReferrals }}</div>
                        <div class="stat-label">Рефералов</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value">{{ formatPrice(totalEarnings) }}</div>
                        <div class="stat-label">Заработано</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value">{{ friendsCount }}</div>
                        <div class="stat-label">Друзей</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОНТЕНТ -->
        <!-- ========================================== -->
        <div class="container px-3">

            <!-- Реферальная ссылка -->
            <div class="referral-link-card">
                <div class="link-header">
                    <i class="fa-solid fa-link"></i>
                    <h3>Ваша реферальная ссылка</h3>
                </div>
                <p class="link-description">
                    Поделитесь ссылкой с друзьями и получайте <strong>10%</strong> от их покупок
                </p>

                <div class="link-box">
                    <input
                        type="text"
                        :value="shortReferralLink"
                        readonly
                        class="link-input"
                        ref="linkInput"
                        @click="$event.target.select()"
                    >
                    <button
                        class="copy-btn"
                        @click="copyLink"
                        :class="{ 'copied': copied }"
                        :disabled="!referralLink"
                    >
                        <i :class="copied ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
                    </button>
                </div>

                <div class="code-box" v-if="referralCode">
                    <div class="code-label">Или скопируйте только код:</div>
                    <div class="code-input-wrapper">
                        <input
                            type="text"
                            :value="referralCode"
                            readonly
                            class="code-input"
                            @click="$event.target.select()"
                        >
                        <button
                            class="copy-code-btn"
                            @click="copyCode"
                            :class="{ 'copied': copiedCode }"
                        >
                            <i :class="copiedCode ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
                            {{ copiedCode ? 'Скопировано!' : 'Копировать код' }}
                        </button>
                    </div>
                </div>

                <div class="share-buttons">
                    <button
                        v-if="canUseWebShare"
                        class="share-btn share-all"
                        @click="shareReferral"
                        :disabled="sharing"
                    >
                        <i class="fa-solid fa-share-nodes"></i>
                        <span>Поделиться</span>
                    </button>
                    <button class="share-btn telegram" @click="shareViaTelegram">
                        <i class="fa-brands fa-telegram"></i>
                        <span>Telegram</span>
                    </button>
                    <button class="share-btn whatsapp" @click="shareViaWhatsApp">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </button>
                    <button class="share-btn vk" @click="shareViaVk">
                        <i class="fa-brands fa-vk"></i>
                        <span>VK</span>
                    </button>
                </div>
            </div>

            <!-- Табы -->
            <div class="tabs-wrapper">
                <button
                    class="tab-btn"
                    :class="{ 'active': activeTab === 'referrals' }"
                    @click="activeTab = 'referrals'"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Рефералы</span>
                    <span class="tab-badge">{{ totalReferrals }}</span>
                </button>
                <button
                    class="tab-btn"
                    :class="{ 'active': activeTab === 'friends' }"
                    @click="activeTab = 'friends'"
                >
                    <i class="fa-solid fa-heart"></i>
                    <span>Друзья</span>
                    <span class="tab-badge">{{ friendsCount }}</span>
                </button>
                <button
                    class="tab-btn"
                    :class="{ 'active': activeTab === 'history' }"
                    @click="activeTab = 'history'"
                >
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>История</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- ТАБ: РЕФЕРАЛЫ -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'referrals'" class="tab-content">

                <div v-if="isLoading" class="loading-state">
                    <div class="loader-spinner"></div>
                </div>

                <template v-else>

                    <!-- TOP PERFORMERS -->
                    <div v-if="referralTree.stats.top_performers?.length > 0 && !hasActiveFilters" class="top-performers-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fa-solid fa-trophy"></i>
                            </div>
                            <div>
                                <h4>Топ рефералов</h4>
                                <p>Ваши самые прибыльные приглашённые</p>
                            </div>
                        </div>
                        <div class="performers-list">
                            <div
                                v-for="(performer, index) in referralTree.stats.top_performers"
                                :key="performer.user_id"
                                class="performer-item"
                                :class="{ 'gold': index === 0, 'silver': index === 1, 'bronze': index === 2 }"
                            >
                                <div class="performer-rank">
                                    <i v-if="index === 0" class="fa-solid fa-crown"></i>
                                    <span v-else class="rank-number">{{ index + 1 }}</span>
                                </div>

                                <div class="performer-info">
                                    <div class="performer-name">{{ performer.user_name }}</div>
                                    <div class="performer-invited-by" v-if="performer.invited_by">
                                        <i class="fa-solid fa-user-plus"></i>
                                        <span v-if="performer.invited_by.is_you" class="invited-you">
                                Приглашён вами
                            </span>
                                        <span v-else class="invited-other">
                                Приглашён: <strong>{{ performer.invited_by.name }}</strong>
                            </span>
                                    </div>
                                    <div class="performer-stats">
                            <span class="stat-chip">
                                <i class="fa-solid fa-shopping-bag"></i>
                                {{ performer.orders_count }} заказ{{ getOrdersEnding(performer.orders_count) }}
                            </span>
                                        <span class="stat-chip">
                                <i class="fa-solid fa-ruble-sign"></i>
                                {{ formatPrice(performer.total_spent) }}
                            </span>
                                        <span class="stat-chip level" :class="`level-${performer.level}`">
                                Ур. {{ performer.level }}
                            </span>
                                    </div>
                                </div>

                                <div class="performer-earned">
                                    <div class="earned-amount">+{{ formatPrice(performer.earned) }}</div>
                                    <div class="earned-label">ваш доход</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NETWORK STATS (скрываем при фильтрации) -->
                    <div v-if="!hasActiveFilters" class="network-stats">
                        <div class="network-stat">
                            <i class="fa-solid fa-users"></i>
                            <div>
                                <div class="stat-number">{{ referralTree.stats.total }}</div>
                                <div class="stat-text">Всего рефералов</div>
                            </div>
                        </div>
                        <div class="network-stat active">
                            <i class="fa-solid fa-fire"></i>
                            <div>
                                <div class="stat-number">{{ referralTree.stats.active_referrals }}</div>
                                <div class="stat-text">Активных</div>
                            </div>
                        </div>
                        <div class="network-stat">
                            <i class="fa-solid fa-bag-shopping"></i>
                            <div>
                                <div class="stat-number">{{ referralTree.stats.total_orders || 0 }}</div>
                                <div class="stat-text">Заказов</div>
                            </div>
                        </div>
                        <div class="network-stat money">
                            <i class="fa-solid fa-ruble-sign"></i>
                            <div>
                                <div class="stat-number">{{ formatPrice(referralTree.stats.total_spent || 0) }}</div>
                                <div class="stat-text">Оборот сети</div>
                            </div>
                        </div>
                    </div>

                    <!-- 🆕 ФИЛЬТРЫ -->
                    <ReferralsFilters
                        v-model="filters"
                        :total-count="allReferrals.length"
                        :filtered-count="filteredReferrals.length"
                        :status-counts="statusCounts"
                    />

                    <!-- 🆕 ПЛОСКИЙ СПИСОК РЕФЕРАЛОВ -->
                    <div class="referrals-list-section">

                        <!-- Пустое состояние -->
                        <div v-if="allReferrals.length === 0" class="empty-level">
                            <i class="fa-solid fa-user-plus"></i>
                            <p>Пока нет рефералов</p>
                        </div>

                        <!-- Ничего не найдено по фильтру -->
                        <div v-else-if="filteredReferrals.length === 0" class="empty-level">
                            <i class="fa-solid fa-filter-circle-xmark"></i>
                            <p>Ничего не найдено</p>
                            <button class="empty-reset-btn" @click="resetFilters">
                                Сбросить фильтры
                            </button>
                        </div>

                        <!-- Список -->
                        <div v-else class="compact-list">
                            <div
                                v-for="ref in displayedReferrals"
                                :key="ref.id"
                                class="compact-item"
                                :class="{
                        'profitable': ref.is_profitable,
                        'inactive': !ref.is_active
                    }"
                                @click="openReferralDetails(ref)"
                            >
                                <!-- Инициалы с цветом уровня -->
                                <div class="compact-initials" :class="`level-${ref.level}-bg`">
                                    {{ getInitials(ref.user?.name) }}
                                </div>

                                <div class="compact-info">
                                    <div class="compact-name-row">
                                        <div class="compact-name">{{ ref.user?.name }}</div>
                                        <span class="compact-level-badge" :class="`level-${ref.level}`">
                                Ур. {{ ref.level }}
                            </span>
                                    </div>
                                    <div class="compact-meta">
                            <span v-if="ref.is_profitable" class="compact-badge profitable">
                                <i class="fa-solid fa-sack-dollar"></i>
                                +{{ formatPrice(ref.earned_from_this || 0) }}
                            </span>
                                        <span v-else-if="ref.is_active" class="compact-badge active">
                                <i class="fa-solid fa-check"></i> Активный
                            </span>
                                        <span v-else class="compact-badge inactive">
                                <i class="fa-regular fa-clock"></i> Неактивный
                            </span>
                                        <span class="compact-orders" v-if="ref.orders_count > 0">
                                <i class="fa-solid fa-shopping-bag"></i>
                                {{ ref.orders_count }}
                            </span>
                                        <span class="compact-date">
                                <i class="fa-regular fa-calendar"></i>
                                {{ formatDate(ref.registered_at) }}
                            </span>
                                    </div>
                                </div>

                                <div class="compact-earned" v-if="ref.earned_from_this > 0">
                                    +{{ formatPrice(ref.earned_from_this) }}
                                </div>
                                <div class="compact-arrow" v-else>
                                    <i class="fa-solid fa-chevron-right"></i>
                                </div>
                            </div>
                        </div>

                        <!-- 🆕 Кнопка "Показать еще" -->
                        <div v-if="hasMoreReferrals" class="load-more-wrapper">
                            <button class="load-more-btn" @click="loadMore">
                                <i class="fa-solid fa-plus"></i>
                                Показать еще ({{ remainingCount }})
                            </button>
                            <div class="load-more-progress">
                                Показано {{ displayedReferrals.length }} из {{ filteredReferrals.length }}
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ========================================== -->
            <!-- ТАБ: ДРУЗЬЯ -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'friends'" class="tab-content">
                <div v-if="incomingRequests.length > 0" class="requests-section">
                    <h4 class="section-subtitle">
                        <i class="fa-solid fa-envelope"></i>
                        Заявки в друзья
                        <span class="requests-count">{{ incomingRequests.length }}</span>
                    </h4>
                    <div class="requests-list">
                        <FriendRequestCard
                            v-for="request in incomingRequests"
                            :key="request.id"
                            :request="request"
                            @accept="handleAcceptRequest"
                            @reject="handleRejectRequest"
                        />
                    </div>
                </div>

                <div v-if="friends.length === 0 && incomingRequests.length === 0" class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <h5>У вас пока нет друзей</h5>
                    <p>Добавляйте друзей и общайтесь с ними</p>
                </div>

                <div v-else class="friends-list">
                    <FriendCard
                        v-for="friend in friends"
                        :key="friend.id"
                        :friend="friend"
                        @remove="handleRemoveFriend"
                    />
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ТАБ: ИСТОРИЯ -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'history'" class="tab-content">
                <div v-if="rewardsHistory.length === 0" class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <h5>История пуста</h5>
                    <p>Здесь будут отображаться ваши реферальные бонусы</p>
                </div>

                <div v-else class="history-list">
                    <RewardCard
                        v-for="reward in rewardsHistory"
                        :key="reward.id"
                        :reward="reward"
                    />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: Детали реферала -->
        <!-- ========================================== -->
        <div class="modal fade" id="referral-details-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content referral-modal-content">

                    <div class="referral-modal-header" v-if="selectedReferral">
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Закрыть"
                        ></button>

                        <div class="referral-profile">
                            <div class="profile-initials-wrapper">
                                <!-- v-lazy для ленивой загрузки -->
                                <img
                                    v-if="selectedReferral.user?.avatar"
                                    v-lazy="selectedReferral.user.avatar"
                                    class="profile-avatar"
                                    :alt="selectedReferral.user?.name"
                                >
                                <div v-else class="profile-initials">
                                    {{ getInitials(selectedReferral.user?.name) }}
                                </div>
                            </div>

                            <h4 class="profile-name">{{ selectedReferral.user?.name }}</h4>
                            <div class="profile-subtitle">
                                <i class="fa-regular fa-calendar"></i>
                                С вами с {{ formatDate(selectedReferral.registered_at) }}
                                <span v-if="selectedReferral.days_since_registration" class="days-ago">
                                    ({{ selectedReferral.days_since_registration }} дн. назад)
                                </span>
                            </div>

                            <div class="profile-badge-wrapper">
                                <span v-if="selectedReferral.is_profitable" class="profile-badge profitable">
                                    <i class="fa-solid fa-sack-dollar"></i> Прибыльный реферал
                                </span>
                                <span v-else-if="selectedReferral.is_active" class="profile-badge active">
                                    <i class="fa-solid fa-check"></i> Активный
                                </span>
                                <span v-else class="profile-badge inactive">
                                    <i class="fa-regular fa-clock"></i> Неактивный
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body referral-modal-body" v-if="selectedReferral">

                        <!-- Статистика -->
                        <div class="referral-section">
                            <div class="section-title">
                                <i class="fa-solid fa-chart-line"></i>
                                Статистика
                            </div>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon bag">
                                        <i class="fa-solid fa-shopping-bag"></i>
                                    </div>
                                    <div class="stat-data">
                                        <div class="stat-value">{{ selectedReferral.orders_count || 0 }}</div>
                                        <div class="stat-label">Заказов</div>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-icon ruble">
                                        <i class="fa-solid fa-ruble-sign"></i>
                                    </div>
                                    <div class="stat-data">
                                        <div class="stat-value">{{ formatPrice(selectedReferral.total_spent || 0) }}</div>
                                        <div class="stat-label">Потратил</div>
                                    </div>
                                </div>

                                <div class="stat-card highlight">
                                    <div class="stat-icon coins">
                                        <i class="fa-solid fa-coins"></i>
                                    </div>
                                    <div class="stat-data">
                                        <div class="stat-value earned">+{{ formatPrice(selectedReferral.earned_from_this || 0) }}</div>
                                        <div class="stat-label">Ваш доход</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Последний заказ -->
                        <div class="referral-section" v-if="selectedReferral.last_order">
                            <div class="section-title">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                Последний заказ
                            </div>
                            <div class="last-order-card">
                                <div class="last-order-icon">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                                <div class="last-order-info">
                                    <div class="last-order-amount">
                                        {{ formatPrice(selectedReferral.last_order.amount) }}
                                    </div>
                                    <div class="last-order-date">
                                        {{ formatDate(selectedReferral.last_order.date) }}
                                    </div>
                                </div>
                                <div class="last-order-status" :class="`status-${selectedReferral.last_order.status}`">
                                    {{ getOrderStatusName(selectedReferral.last_order.status) }}
                                </div>
                            </div>
                        </div>

                        <!-- Начисления бонусов -->
                        <div class="referral-section" v-if="selectedReferral.rewards_count > 0">
                            <div class="section-title">
                                <i class="fa-solid fa-gift"></i>
                                Начисления бонусов
                            </div>
                            <div class="rewards-summary">
                                <div class="rewards-count">
                                    <strong>{{ selectedReferral.rewards_count }}</strong>
                                    <span>начислен{{ getRewardsEnding(selectedReferral.rewards_count) }}</span>
                                </div>
                                <div class="rewards-total">
                                    Всего: <strong>+{{ formatPrice(selectedReferral.earned_from_this || 0) }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Действия -->
                        <div class="referral-section actions-section">
                            <div class="section-title">
                                <i class="fa-solid fa-bolt"></i>
                                Действия
                            </div>
                            <div class="action-buttons">
                                <button
                                    class="action-btn primary"
                                    @click="sendFriendRequestFromModal"
                                    :disabled="isFriendButtonDisabled(selectedReferral)"
                                    :title="getFriendButtonTitle(selectedReferral)"
                                >
                                    <i :class="getFriendButtonIcon(selectedReferral)"></i>
                                    {{ getFriendButtonText(selectedReferral) }}
                                </button>

                                <button
                                    class="action-btn secondary"
                                    @click="openChatWithUser(selectedReferral.user.id)"
                                >
                                    <i class="fa-solid fa-comment-dots"></i>
                                    Написать
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useReferrals } from '@/MobileClient/Composables/useReferrals.js';
import FriendCard from '@/MobileClient/Components/Referrals/FriendCard.vue';
import FriendRequestCard from '@/MobileClient/Components/Referrals/FriendRequestCard.vue';
import RewardCard from '@/MobileClient/Components/Referrals/RewardCard.vue';
import ReferralsFilters from '@/MobileClient/Components/Referrals/ReferralsFilters.vue';

export default {
    name: 'ReferralsPage',

    components: {
        FriendCard,
        FriendRequestCard,
        RewardCard,
        ReferralsFilters,
    },

    setup() {
        const referrals = useReferrals();
        return { ...referrals };
    },

    data() {
        return {
            activeTab: 'referrals',
            copied: false,
            copiedCode: false,
            sharing: false,
            selectedReferral: null,
            referralModalInstance: null,

            // 🆕 ФИЛЬТРЫ
            filters: {
                search: '',
                status: 'all',       // all | profitable | active | inactive
                level: 'all',        // all | 1 | 2 | 3
                sort: 'date_desc',   // date_desc | date_asc | earned_desc | earned_asc | spent_desc | orders_desc
            },

            // 🆕 ПАГИНАЦИЯ
            perPage: 15,
            currentPage: 1,

            _sentRequestsMap: {},
        };
    },

    computed: {
        user() { return window.TenantUser; },
        tenant() { return window.Tenant || null; },

        referralCode() { return this.user?.referral_code || ''; },

        referralLink() {
            const code = this.referralCode;
            if (!code) return '';
            return `${window.location.origin}/pwa?ref=${encodeURIComponent(code)}`;
        },

        shortReferralLink() {
            if (!this.referralLink) return '';
            try {
                const url = new URL(this.referralLink);
                return url.host + url.pathname + url.search;
            } catch {
                return this.referralLink;
            }
        },

        canUseWebShare() { return 'share' in navigator; },

        // ==========================================
        // 🆕 ПЛОСКИЙ СПИСОК ВСЕХ РЕФЕРАЛОВ
        // ==========================================
        allReferrals() {
            const tree = this.referralTree || {};
            const list = [
                ...(tree.level_1 || []),
                ...(tree.level_2 || []),
                ...(tree.level_3 || []),
            ];
            return list;
        },

        // ==========================================
        // 🆕 ПОДСЧЁТ ПО СТАТУСАМ (для чипов)
        // ==========================================
        statusCounts() {
            const list = this.allReferrals;
            return {
                all: list.length,
                profitable: list.filter(r => r.is_profitable).length,
                active: list.filter(r => r.is_active && !r.is_profitable).length,
                inactive: list.filter(r => !r.is_active).length,
            };
        },

        // ==========================================
        // 🆕 ПРИМЕНЕНИЕ ФИЛЬТРОВ
        // ==========================================
        filteredReferrals() {
            let result = [...this.allReferrals];

            // 1. Фильтр по поиску
            if (this.filters.search) {
                const q = this.filters.search.toLowerCase().trim();
                result = result.filter(ref =>
                    ref.user?.name?.toLowerCase().includes(q)
                );
            }

            // 2. Фильтр по статусу
            if (this.filters.status !== 'all') {
                if (this.filters.status === 'profitable') {
                    result = result.filter(r => r.is_profitable);
                } else if (this.filters.status === 'active') {
                    result = result.filter(r => r.is_active && !r.is_profitable);
                } else if (this.filters.status === 'inactive') {
                    result = result.filter(r => !r.is_active);
                }
            }

            // 3. Фильтр по уровню
            if (this.filters.level !== 'all') {
                const level = parseInt(this.filters.level);
                result = result.filter(r => r.level === level);
            }

            // 4. Сортировка
            result = this.sortReferrals(result, this.filters.sort);

            return result;
        },

        // ==========================================
        // 🆕 ПАГИНАЦИЯ
        // ==========================================
        displayedReferrals() {
            const end = this.currentPage * this.perPage;
            return this.filteredReferrals.slice(0, end);
        },

        hasMoreReferrals() {
            return this.displayedReferrals.length < this.filteredReferrals.length;
        },

        remainingCount() {
            return this.filteredReferrals.length - this.displayedReferrals.length;
        },

        hasActiveFilters() {
            return this.filters.search ||
                this.filters.status !== 'all' ||
                this.filters.level !== 'all' ||
                this.filters.sort !== 'date_desc';
        },

        // ==========================================
        // 🆕 ХЕЛПЕРЫ ДЛЯ ПРОВЕРКИ СТАТУСА ДРУЖБЫ
        // ==========================================

        /**
         * Map всех друзей для быстрой проверки
         * { userId: true, userId: true, ... }
         */
        friendsMap() {
            const map = {};
            (this.friends || []).forEach(f => {
                // Друг может быть как в поле friend, так и в самом объекте
                const friendId = f.friend?.id || f.id;
                if (friendId) map[friendId] = true;
            });
            return map;
        },

        /**
         * Map входящих заявок (кто хочет добавить НАС)
         */
        incomingRequestsMap() {
            const map = {};
            (this.incomingRequests || []).forEach(r => {
                const userId = r.user?.id || r.initiator_id;
                if (userId) map[userId] = r.id; // Сохраняем ID заявки
            });
            return map;
        },

        /**
         * Map исходящих заявок (кого МЫ добавили)
         * Так как бэкенд может не отдавать их отдельно, храним локально
         */
        sentRequestsMap() {
            return this._sentRequestsMap || {};
        },
    },

    watch: {
        // 🆕 Сброс пагинации при изменении фильтров
        'filters': {
            handler() {
                this.currentPage = 1;
            },
            deep: true,
        },
    },

    async mounted() {
        await Promise.all([
            this.loadReferralTree(),
            this.loadFriends(),
            this.loadIncomingRequests(),
            this.loadRewardsHistory(),
        ]);

        this.$nextTick(() => {
            this.initReferralModal();
        });
    },

    methods: {
        // ==========================================
        // 🆕 СОРТИРОВКА
        // ==========================================
        sortReferrals(list, sortBy) {
            const sorted = [...list];

            switch (sortBy) {
                case 'date_desc':
                    return sorted.sort((a, b) => new Date(b.registered_at) - new Date(a.registered_at));
                case 'date_asc':
                    return sorted.sort((a, b) => new Date(a.registered_at) - new Date(b.registered_at));
                case 'earned_desc':
                    return sorted.sort((a, b) => (b.earned_from_this || 0) - (a.earned_from_this || 0));
                case 'earned_asc':
                    return sorted.sort((a, b) => (a.earned_from_this || 0) - (b.earned_from_this || 0));
                case 'spent_desc':
                    return sorted.sort((a, b) => (b.total_spent || 0) - (a.total_spent || 0));
                case 'orders_desc':
                    return sorted.sort((a, b) => (b.orders_count || 0) - (a.orders_count || 0));
                default:
                    return sorted;
            }
        },

        // ==========================================
        // 🆕 ПАГИНАЦИЯ
        // ==========================================
        loadMore() {
            this.currentPage++;
        },

        resetFilters() {
            this.filters = {
                search: '',
                status: 'all',
                level: 'all',
                sort: 'date_desc',
            };
        },

        // ==========================================
        // УТИЛИТЫ (без изменений)
        // ==========================================
        getInitials(name) {
            if (!name) return '?';
            const parts = name.split(' ').filter(Boolean);
            if (parts.length === 0) return '?';
            if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
            return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ru-RU', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency', currency: 'RUB', minimumFractionDigits: 0,
            }).format(price || 0);
        },

        getOrdersEnding(count) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return 'ов';
            if (n1 > 1 && n1 < 5) return 'а';
            if (n1 === 1) return '';
            return 'ов';
        },

        getRewardsEnding(count) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return 'ий';
            if (n1 === 1) return 'ие';
            if (n1 > 1 && n1 < 5) return 'ия';
            return 'ий';
        },

        getOrderStatusName(status) {
            const names = {
                0: 'Новый', 1: 'В доставке', 2: 'Завершён',
                3: 'Отменён', 4: 'Готов к доставке', 5: 'Готовится',
            };
            return names[status] || 'Неизвестно';
        },

        showNotification(title, text, type = 'info') {
            if (this.$notify) this.$notify({ title, text, type });
            else console.log(`[${type.toUpperCase()}] ${title}: ${text}`);
        },

        // ==========================================
        // МОДАЛКА (без изменений)
        // ==========================================
        initReferralModal() {
            const el = document.getElementById('referral-details-modal');
            if (el && typeof bootstrap !== 'undefined') {
                this.referralModalInstance = bootstrap.Modal.getOrCreateInstance(el);
            }
        },

        openReferralDetails(ref) {
            this.selectedReferral = ref;
            if (!this.referralModalInstance) {
                const el = document.getElementById('referral-details-modal');
                if (el && typeof bootstrap !== 'undefined') {
                    this.referralModalInstance = bootstrap.Modal.getOrCreateInstance(el);
                }
            }
            if (this.referralModalInstance) {
                this.referralModalInstance.show();
            }
        },

        closeReferralModal() {
            if (this.referralModalInstance) this.referralModalInstance.hide();
            setTimeout(() => { this.selectedReferral = null; }, 300);
        },

        async sendFriendRequestFromModal() {
            if (!this.selectedReferral) return;

            const userId = this.selectedReferral.user.id;
            const userName = this.selectedReferral.user?.name || 'Пользователь';

            // Проверка: не друг ли уже
            if (this.friendsMap[userId]) {
                this.showNotification('Информация', `${userName} уже в ваших друзьях`, 'info');
                return;
            }

            // Проверка: есть ли входящая заявка от него
            if (this.incomingRequestsMap[userId]) {
                // Автоматически принимаем заявку
                try {
                    await this.acceptFriendRequest(this.incomingRequestsMap[userId]);
                    this.showNotification('Успех', `Вы теперь друзья с ${userName}!`, 'success');
                    // Обновляем дерево, чтобы изменился статус
                    await this.loadReferralTree();
                    return;
                } catch (error) {
                    this.showNotification('Ошибка', 'Не удалось принять заявку', 'error');
                    return;
                }
            }

            // Проверка: не отправляли ли мы уже заявку
            if (this._sentRequestsMap[userId]) {
                this.showNotification('Информация', 'Заявка уже отправлена', 'info');
                return;
            }

            // Отправка заявки
            try {
                await this.sendFriendRequest(userId); // ✅ Правильное имя метода!

                // 🆕 Сохраняем локально, что отправили заявку
                this._sentRequestsMap = {
                    ...this._sentRequestsMap,
                    [userId]: true,
                };

                // Обновляем состояние в модалке
                this.selectedReferral = {
                    ...this.selectedReferral,
                    friend_request_sent: true,
                };

                this.showNotification('Успех', `Заявка отправлена пользователю ${userName}!`, 'success');

            } catch (error) {
                console.error('Ошибка отправки заявки:', error);

                const message = error.response?.data?.message || 'Не удалось отправить заявку';
                this.showNotification('Ошибка', message, 'error');
            }
        },

        openChatWithUser(userId) {
            this.closeReferralModal();
            this.$router?.push({ name: 'Chat', params: { dialogId: userId } })
                .catch(() => console.log('Переход в чат:', userId));
        },

        // ==========================================
        // ДРУЗЬЯ (без изменений)
        // ==========================================
        async handleAcceptRequest(requestId) {
            try {
                await this.acceptFriendRequest(requestId);
                this.showNotification('Успех', 'Заявка принята', 'success');
            } catch (error) {
                this.showNotification('Ошибка', 'Не удалось принять заявку', 'error');
            }
        },

        async handleRejectRequest(requestId) {
            try {
                await this.rejectFriendRequest(requestId);
                this.showNotification('Готово', 'Заявка отклонена', 'info');
            } catch (error) {
                this.showNotification('Ошибка', 'Не удалось отклонить заявку', 'error');
            }
        },

        async handleRemoveFriend(friendId) {
            if (!confirm('Удалить из друзей?')) return;
            try {
                await this.removeFriend(friendId);
                this.showNotification('Успех', 'Удалён из друзей', 'success');
            } catch (error) {
                this.showNotification('Ошибка', 'Не удалось удалить', 'error');
            }
        },

        // ==========================================
        // КОПИРОВАНИЕ (без изменений)
        // ==========================================
        async copyLink() {
            if (!this.referralLink) {
                this.showNotification('Ошибка', 'Ссылка недоступна', 'error');
                return;
            }
            try {
                await navigator.clipboard.writeText(this.referralLink);
                this.copied = true;
                this.showNotification('Скопировано!', 'Ссылка скопирована', 'success');
                setTimeout(() => { this.copied = false; }, 2000);
            } catch (err) {
                this.fallbackCopy(this.referralLink);
            }
        },

        async copyCode() {
            if (!this.referralCode) {
                this.showNotification('Ошибка', 'Код недоступен', 'error');
                return;
            }
            try {
                await navigator.clipboard.writeText(this.referralCode);
                this.copiedCode = true;
                this.showNotification('Скопировано!', `Код ${this.referralCode} скопирован`, 'success');
                setTimeout(() => { this.copiedCode = false; }, 2000);
            } catch (err) {
                this.fallbackCopy(this.referralCode);
            }
        },

        fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                this.showNotification('Скопировано!', 'Текст скопирован', 'success');
            } catch (err) {
                this.showNotification('Ошибка', 'Не удалось скопировать', 'error');
            }
            document.body.removeChild(textarea);
        },

        // ==========================================
        // ШЕРИНГ (без изменений)
        // ==========================================
        async shareReferral() {
            if (this.sharing) return;
            this.sharing = true;
            const shareData = {
                title: `Присоединяйся к ${this.tenant?.name || 'магазину'}!`,
                text: `Используй мою реферальную ссылку и получи бонус при регистрации`,
                url: this.referralLink,
            };
            try {
                if (navigator.share) await navigator.share(shareData);
                else await this.copyLink();
            } catch (error) {
                if (error.name !== 'AbortError') await this.copyLink();
            } finally {
                this.sharing = false;
            }
        },

        shareViaTelegram() {
            if (!this.referralLink) return;
            const text = `Присоединяйся к ${this.tenant?.name || 'магазину'}! Используй мою реферальную ссылку и получи бонус при регистрации`;
            const url = `https://t.me/share/url?url=${encodeURIComponent(this.referralLink)}&text=${encodeURIComponent(text)}`;
            window.open(url, '_blank', 'width=600,height=400');
        },

        shareViaWhatsApp() {
            if (!this.referralLink) return;
            const text = `Присоединяйся к ${this.tenant?.name || 'магазину'}! ${this.referralLink}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        },

        shareViaVk() {
            if (!this.referralLink) return;
            const url = `https://vk.com/share.php?url=${encodeURIComponent(this.referralLink)}`;
            window.open(url, '_blank', 'width=600,height=400');
        },

        // ==========================================
        // ДРУЗЬЯ - кнопки (без изменений)
        // ==========================================
        /**
         * 🆕 Получить статус дружбы с пользователем
         */
        getFriendshipStatus(ref) {
            const userId = ref?.user?.id || ref?.user_id;
            if (!userId) return 'none';

            if (this.friendsMap[userId]) return 'friends';
            if (this.incomingRequestsMap[userId]) return 'received';
            if (this._sentRequestsMap[userId] || ref.friend_request_sent) return 'sent';
            return 'none';
        },

        getFriendButtonText(ref) {
            const status = this.getFriendshipStatus(ref);
            switch (status) {
                case 'friends': return 'В друзьях';
                case 'sent': return 'Заявка отправлена';
                case 'received': return 'Принять заявку';
                default: return 'Добавить в друзья';
            }
        },

        getFriendButtonIcon(ref) {
            const status = this.getFriendshipStatus(ref);
            switch (status) {
                case 'friends': return 'fa-solid fa-user-check';
                case 'sent': return 'fa-solid fa-hourglass-half';
                case 'received': return 'fa-solid fa-user-plus';
                default: return 'fa-solid fa-user-plus';
            }
        },

        getFriendButtonTitle(ref) {
            const status = this.getFriendshipStatus(ref);
            switch (status) {
                case 'friends': return 'Уже в списке друзей';
                case 'sent': return 'Ожидает подтверждения';
                case 'received': return 'Принять заявку в друзья';
                default: return 'Отправить заявку в друзья';
            }
        },

        /**
         * 🆕 Проверка: кнопка должна быть заблокирована?
         */
        isFriendButtonDisabled(ref) {
            const status = this.getFriendshipStatus(ref);
            // Нельзя отправить заявку, если уже друзья или уже отправлена
            return status === 'friends' || status === 'sent';
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$warning: #f59e0b;
$purple: #8b5cf6;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);

.referrals-page {
    min-height: 100vh;
    background: $bg-secondary;
    padding-bottom: 40px;
}

// ==========================================
// HERO
// ==========================================
.referrals-hero {
    position: relative;
    padding: 40px 20px 60px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 40%),
    radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.08) 0%, transparent 40%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0 0 8px;
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0 0 24px;
}

.hero-stats {
    display: inline-flex;
    align-items: center;
    gap: 20px;
    padding: 14px 24px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.4rem;
    font-weight: 800;
    line-height: 1.1;
}

.stat-label {
    font-size: 0.75rem;
    opacity: 0.85;
    margin-top: 2px;
}

.stat-divider {
    width: 1px;
    height: 30px;
    background: rgba(255, 255, 255, 0.3);
}

// ==========================================
// РЕФЕРАЛЬНАЯ ССЫЛКА
// ==========================================
.referral-link-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    margin: -30px 0 20px;
    position: relative;
    z-index: 2;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.link-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    color: $primary;

    i { font-size: 1.1rem; }

    h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: $text;
    }
}

.link-description {
    font-size: 0.85rem;
    color: $text-muted;
    margin: 0 0 16px;

    strong { color: $primary; }
}

.link-box {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.link-input {
    flex: 1;
    padding: 12px 14px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.85rem;
    color: $text;
    font-family: monospace;
}

.copy-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s;

    &:hover:not(:disabled) {
        background: $primary-dark;
        transform: scale(1.05);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    &.copied {
        background: $success;
        animation: successPulse 0.6s ease;
    }
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.code-box {
    margin-bottom: 16px;
}

.code-label {
    font-size: 0.8rem;
    color: $text-muted;
    margin-bottom: 6px;
}

.code-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.code-input {
    flex: 1;
    padding: 10px 14px;
    background: linear-gradient(135deg, rgba($primary, 0.1), rgba($primary-dark, 0.1));
    border: 2px dashed $primary;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    color: $primary;
    font-family: monospace;
    letter-spacing: 2px;
    text-align: center;
    cursor: pointer;

    &:focus {
        outline: none;
        border-color: $primary-dark;
        box-shadow: 0 0 0 3px rgba($primary, 0.2);
    }
}

.copy-code-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 10px;
    color: $text;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    &:hover {
        background: $border;
        transform: translateY(-1px);
    }

    &.copied {
        background: $success;
        color: white;
        border-color: $success;
    }
}

.share-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.share-btn {
    flex: 1;
    min-width: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px 16px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 10px;
    color: $text;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    i { font-size: 1.1rem; }

    &.share-all {
        background: linear-gradient(135deg, $primary, $primary-dark);
        color: white;
        border: none;
    }

    &.telegram {
        color: #0088cc;
        &:hover:not(:disabled) { background: #0088cc; color: white; border-color: #0088cc; }
    }

    &.whatsapp {
        color: #25d366;
        &:hover:not(:disabled) { background: #25d366; color: white; border-color: #25d366; }
    }

    &.vk {
        color: #0077ff;
        &:hover:not(:disabled) { background: #0077ff; color: white; border-color: #0077ff; }
    }
}

// ==========================================
// ТАБЫ
// ==========================================
.tabs-wrapper {
    display: flex;
    overflow-x: auto;
    gap: 4px;
    background: $bg;
    padding: 4px;
    border-radius: 14px;
    margin-bottom: 16px;
    border: 1px solid $border;
}

.tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $text-muted;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &.active {
        background: $primary;
        color: white;
        box-shadow: 0 2px 8px rgba($primary, 0.3);
    }
}

.tab-badge {
    padding: 2px 8px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    font-size: 0.7rem;

    .active & { background: rgba(255, 255, 255, 0.25); }
}

// ==========================================
// УРОВНИ РЕФЕРАЛОВ
// ==========================================
.level-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    margin-bottom: 12px;
    overflow: hidden;
}

.level-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid $border;

    &.level-1 .level-badge { background: linear-gradient(135deg, $success, #059669); }
    &.level-2 .level-badge { background: linear-gradient(135deg, $primary, $primary-dark); }
    &.level-3 .level-badge { background: linear-gradient(135deg, $purple, #7c3aed); }
}

.level-badge {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $text-muted;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    flex-shrink: 0;
}

.level-info {
    flex: 1;
    min-width: 0;

    h4 {
        margin: 0 0 2px;
        font-size: 0.95rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.75rem;
        color: $text-muted;
    }
}

.level-count {
    padding: 4px 10px;
    background: $bg-secondary;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 700;
    color: $text-muted;
}

.empty-level {
    padding: 30px 20px;
    text-align: center;
    color: $text-muted;

    i {
        font-size: 1.5rem;
        opacity: 0.3;
        margin-bottom: 8px;
    }

    p {
        margin: 0;
        font-size: 0.85rem;
    }
}

// ==========================================
// 🆕 КОМПАКТНЫЙ СПИСОК БЕЗ АВАТАРОВ
// ==========================================
.compact-list {
    padding: 4px 8px;
}

.compact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    margin-bottom: 4px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    background: transparent;

    &:hover {
        background: $bg-secondary;
        transform: translateX(2px);
    }

    &:active {
        transform: translateX(0) scale(0.99);
    }

    &.profitable {
        border-left: 3px solid $success;
        padding-left: 11px;
    }

    &.inactive {
        opacity: 0.65;
    }
}

.compact-initials {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    color: white;
    flex-shrink: 0;

    &.level-1-bg { background: linear-gradient(135deg, $success, #059669); }
    &.level-2-bg { background: linear-gradient(135deg, $primary, $primary-dark); }
    &.level-3-bg { background: linear-gradient(135deg, $purple, #7c3aed); }
}

.compact-info {
    flex: 1;
    min-width: 0;
}

.compact-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: $text;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.compact-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.compact-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;

    i { font-size: 0.65rem; }

    &.profitable { background: rgba($success, 0.12); color: $success; }
    &.active { background: rgba(#3b82f6, 0.12); color: #3b82f6; }
    &.inactive { background: rgba(#9ca3af, 0.15); color: #6b7280; }
}

.compact-orders, .compact-date {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: $text-muted;

    i { font-size: 0.7rem; }
}

.compact-earned {
    font-weight: 700;
    color: $success;
    font-size: 0.9rem;
    padding: 4px 10px;
    background: rgba($success, 0.1);
    border-radius: 20px;
    white-space: nowrap;
    flex-shrink: 0;
}

.compact-arrow {
    color: $text-muted;
    opacity: 0.4;
    transition: all 0.2s;
    flex-shrink: 0;

    .compact-item:hover & {
        opacity: 0.8;
        transform: translateX(2px);
    }
}

// ==========================================
// TOP PERFORMERS
// ==========================================
.top-performers-section {
    background: linear-gradient(135deg, rgba($warning, 0.08), rgba($warning, 0.02));
    border: 1px solid rgba($warning, 0.25);
    border-radius: 16px;
    padding: 18px;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba($warning, 0.08);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;

    .section-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, $warning, darken($warning, 12%));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        box-shadow: 0 4px 10px rgba($warning, 0.3);
        flex-shrink: 0;
    }

    h4 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 2px 0 0;
        font-size: 0.78rem;
        color: $text-muted;
    }
}

.performers-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.performer-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    transition: all 0.2s;

    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    &.gold {
        border-color: #fbbf24;
        background: linear-gradient(135deg, rgba(#fbbf24, 0.12), rgba(#fbbf24, 0.03));

        .performer-rank {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            box-shadow: 0 2px 8px rgba(#fbbf24, 0.4);
        }
    }

    &.silver {
        border-color: #cbd5e1;
        background: linear-gradient(135deg, rgba(#cbd5e1, 0.15), transparent);

        .performer-rank {
            background: linear-gradient(135deg, #e2e8f0, #94a3b8);
            color: white;
        }
    }

    &.bronze {
        border-color: #d97706;
        background: linear-gradient(135deg, rgba(#fbbf24, 0.08), rgba(#d97706, 0.05));

        .performer-rank {
            background: linear-gradient(135deg, #fbbf24, #d97706);
            color: white;
        }
    }
}

.performer-rank {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg-secondary;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    flex-shrink: 0;

    i { font-size: 1rem; }
}

.performer-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.performer-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.performer-invited-by {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: $text-muted;

    i {
        font-size: 0.65rem;
        color: $primary;
        opacity: 0.7;
    }

    .invited-you {
        color: $primary;
        font-weight: 600;
    }

    .invited-other strong {
        color: $text;
        font-weight: 600;
    }
}

.performer-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 4px;
}

.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: $bg-secondary;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 600;
    color: $text-muted;
    white-space: nowrap;

    i { font-size: 0.65rem; opacity: 0.8; }

    &.level {
        background: rgba($primary, 0.12);
        color: $primary;

        &.level-2 { background: rgba($purple, 0.12); color: $purple; }
        &.level-3 { background: rgba(#6366f1, 0.12); color: #6366f1; }
    }
}

.performer-earned {
    text-align: right;
    flex-shrink: 0;
    min-width: 80px;

    .earned-amount {
        font-weight: 800;
        font-size: 1.05rem;
        color: $success;
        line-height: 1.1;
    }

    .earned-label {
        font-size: 0.68rem;
        color: $text-muted;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-top: 2px;
    }
}

// ==========================================
// NETWORK STATS
// ==========================================
.network-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 16px;

    @media (min-width: 576px) {
        grid-template-columns: repeat(4, 1fr);
    }
}

.network-stat {
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 10px;

    i {
        font-size: 1.3rem;
        color: $text-muted;
        width: 24px;
        text-align: center;
    }

    &.active i { color: #ef4444; }
    &.money i { color: $success; }

    .stat-number {
        font-weight: 700;
        font-size: 1rem;
        color: $text;
        line-height: 1.1;
    }

    .stat-text {
        font-size: 0.7rem;
        color: $text-muted;
    }
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 50px 20px;

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 16px;
        background: rgba($primary, 0.1);
        color: $primary;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    h5 {
        font-weight: 700;
        margin: 0 0 6px;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0;
    }
}

.loading-state {
    display: flex;
    justify-content: center;
    padding: 40px;

    .loader-spinner {
        width: 32px;
        height: 32px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// МОДАЛКА ДЕТАЛЕЙ РЕФЕРАЛА
// ==========================================
.referral-modal-content {
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.referral-modal-header {
    position: relative;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    padding: 50px 24px 24px;
    text-align: center;

    .btn-close {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 10;
        opacity: 0.8;

        &:hover { opacity: 1; }
    }
}

.referral-profile {
    position: relative;
    z-index: 1;
}

.profile-initials-wrapper {
    width: 80px;
    height: 80px;
    margin: 0 auto 12px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.profile-initials {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 3px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 800;
    color: white;
}

.profile-name {
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0 0 4px;
}

.profile-subtitle {
    font-size: 0.82rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-bottom: 12px;

    .days-ago { opacity: 0.75; }
}

.profile-badge-wrapper {
    display: flex;
    justify-content: center;
}

.profile-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    backdrop-filter: blur(10px);

    &.profitable {
        background: rgba($success, 0.25);
        color: white;
        border: 1px solid rgba($success, 0.4);
    }
    &.active {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    &.inactive {
        background: rgba(0, 0, 0, 0.2);
        color: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
}

.referral-modal-body {
    padding: 20px 20px 24px;
}

.referral-section {
    margin-bottom: 20px;

    &:last-child { margin-bottom: 0; }
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 700;
    color: $text;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    i {
        color: $primary;
        font-size: 0.9rem;
    }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.stat-card {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 12px 8px;
    text-align: center;
    transition: all 0.2s;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    &.highlight {
        background: linear-gradient(135deg, rgba($success, 0.1), rgba($success, 0.05));
        border-color: rgba($success, 0.3);

        .stat-icon {
            background: rgba($success, 0.15);
            color: $success;
        }
    }
}

.stat-icon {
    width: 36px;
    height: 36px;
    margin: 0 auto 8px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;

    &.bag { background: rgba(#8b5cf6, 0.12); color: #8b5cf6; }
    &.ruble { background: rgba(#3b82f6, 0.12); color: #3b82f6; }
    &.coins { background: rgba($success, 0.15); color: $success; }
}

.stat-data {
    .stat-value {
        font-size: 1rem;
        font-weight: 800;
        color: $text;
        line-height: 1.1;
        margin-bottom: 2px;

        &.earned { color: $success; }
    }

    .stat-label {
        font-size: 0.7rem;
        color: $text-muted;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
}

.last-order-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
}

.last-order-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, $primary, $primary-dark);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.last-order-info {
    flex: 1;
    min-width: 0;

    .last-order-amount {
        font-weight: 700;
        font-size: 1.05rem;
        color: $text;
    }

    .last-order-date {
        font-size: 0.78rem;
        color: $text-muted;
        margin-top: 2px;
    }
}

.last-order-status {
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;

    &.status-0 { background: rgba(#3b82f6, 0.12); color: #3b82f6; }
    &.status-1 { background: rgba($warning, 0.12); color: $warning; }
    &.status-2 { background: rgba($success, 0.12); color: $success; }
    &.status-3 { background: rgba(#ef4444, 0.12); color: #ef4444; }
    &.status-4, &.status-5 { background: rgba($purple, 0.12); color: $purple; }
}

.rewards-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px;
    background: linear-gradient(135deg, rgba($warning, 0.08), rgba($warning, 0.03));
    border: 1px solid rgba($warning, 0.25);
    border-radius: 12px;

    .rewards-count {
        strong {
            display: block;
            font-size: 1.3rem;
            font-weight: 800;
            color: $text;
        }
        span {
            font-size: 0.75rem;
            color: $text-muted;
        }
    }

    .rewards-total {
        font-size: 0.85rem;
        color: $text-muted;

        strong {
            color: $success;
            font-weight: 700;
            font-size: 1rem;
        }
    }
}

.actions-section {
    padding-top: 8px;
    border-top: 1px solid $border;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid $border;

    &:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    &.primary {
        background: $primary;
        color: white;
        border-color: $primary;

        &:hover:not(:disabled) {
            background: $primary-dark;
            border-color: $primary-dark;
        }
    }

    &.secondary {
        background: $bg;
        color: $text;

        &:hover:not(:disabled) {
            background: $bg-secondary;
        }
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 576px) {
    .hero-title { font-size: 1.5rem; }
    .hero-icon { width: 60px; height: 60px; font-size: 1.6rem; }
    .hero-stats { gap: 12px; padding: 12px 16px; }
    .stat-value { font-size: 1.2rem; }

    .share-btn {
        min-width: 100px;
        padding: 10px 12px;
        font-size: 0.8rem;
        span { display: none; }
    }

    .code-input-wrapper { flex-direction: column; }
    .copy-code-btn { width: 100%; }

    .stats-grid { gap: 6px; }
    .stat-card { padding: 10px 4px; }
    .stat-icon { width: 32px; height: 32px; font-size: 0.85rem; }
    .stat-data .stat-value { font-size: 0.9rem; }

    .performer-item { padding: 12px 10px; gap: 8px; }
    .performer-rank { width: 32px; height: 32px; }
    .performer-name { font-size: 0.88rem; }
    .performer-earned .earned-amount { font-size: 0.95rem; }
}

// ==========================================
// 🆕 НАЗВАНИЕ + БЕЙДЖ УРОВНЯ
// ==========================================
.compact-name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.compact-level-badge {
    padding: 1px 7px;
    border-radius: 8px;
    font-size: 0.65rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;

    &.level-1 { background: rgba($success, 0.12); color: $success; }
    &.level-2 { background: rgba($primary, 0.12); color: $primary; }
    &.level-3 { background: rgba($purple, 0.12); color: $purple; }
}

// ==========================================
// 🆕 LOAD MORE (Показать еще)
// ==========================================
.load-more-wrapper {
    padding: 16px 8px;
    text-align: center;
}

.load-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 24px;
    background: $bg;
    border: 1.5px solid $border;
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 600;
    color: $text;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 6px;

    i {
        font-size: 0.8rem;
        color: $primary;
    }

    &:hover {
        background: $bg-secondary;
        border-color: $primary;
        color: $primary;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba($primary, 0.15);
    }

    &:active {
        transform: translateY(0);
    }
}

.load-more-progress {
    font-size: 0.75rem;
    color: $text-muted;
}

// ==========================================
// 🆕 КНОПКА СБРОСА В ПУСТОМ СОСТОЯНИИ
// ==========================================
.empty-reset-btn {
    margin-top: 12px;
    padding: 8px 16px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $primary-dark;
        transform: translateY(-1px);
    }
}

// ==========================================
// 🆕 АДАПТИВ ДЛЯ ФИЛЬТРОВ
// ==========================================
@media (max-width: 400px) {
    .compact-meta {
        gap: 6px;
    }

    .compact-date {
        display: none; // Скрываем дату на очень маленьких экранах
    }
}

// ==========================================
// 🆕 КРАСИВЫЕ КАРТОЧКИ РЕФЕРАЛОВ
// ==========================================
.compact-list {
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 10px; // Отступ между карточками
}

.compact-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;

    // Лёгкая тень для глубины
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);

    // Hover эффект
    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        border-color: rgba($primary, 0.3);
        background: linear-gradient(135deg, $bg 0%, rgba($primary, 0.02) 100%);

        .compact-arrow {
            opacity: 1;
            transform: translateX(4px);
            color: $primary;
        }

        .compact-initials {
            transform: scale(1.05);
        }
    }

    // Active эффект (при клике)
    &:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    // Прибыльные рефералы (зелёная полоска слева)
    &.profitable {
        border-left: 4px solid $success;
        padding-left: 12px;
        background: linear-gradient(90deg, rgba($success, 0.03) 0%, $bg 30%);

        &:hover {
            border-left-color: darken($success, 5%);
            background: linear-gradient(90deg, rgba($success, 0.08) 0%, $bg 30%);
        }

        .compact-initials {
            box-shadow: 0 4px 12px rgba($success, 0.25);
        }
    }

    // Неактивные (серый overlay)
    &.inactive {
        opacity: 0.6;
        filter: grayscale(0.3);

        &:hover {
            opacity: 0.85;
            filter: grayscale(0);
        }
    }
}

// ==========================================
// 🆕 ИНИЦИАЛЫ (Аватар-заглушка)
// ==========================================
.compact-initials {
    width: 44px;
    height: 44px;
    border-radius: 12px; // Скруглённый квадрат вместо круга
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.25s ease;
    letter-spacing: 0.5px;

    // Градиенты для разных уровней
    &.level-1-bg {
        background: linear-gradient(135deg, $success 0%, #059669 100%);
        box-shadow: 0 4px 12px rgba($success, 0.2);
    }

    &.level-2-bg {
        background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
        box-shadow: 0 4px 12px rgba($primary, 0.2);
    }

    &.level-3-bg {
        background: linear-gradient(135deg, $purple 0%, #7c3aed 100%);
        box-shadow: 0 4px 12px rgba($purple, 0.2);
    }
}

// ==========================================
// 🆕 ИНФОРМАЦИЯ
// ==========================================
.compact-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.compact-name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.compact-name {
    font-weight: 600;
    font-size: 0.98rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
    transition: color 0.2s;

    .compact-item:hover & {
        color: $primary;
    }
}

.compact-level-badge {
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.68rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
    letter-spacing: 0.3px;
    text-transform: uppercase;

    &.level-1 {
        background: linear-gradient(135deg, rgba($success, 0.15) 0%, rgba($success, 0.08) 100%);
        color: $success;
        border: 1px solid rgba($success, 0.2);
    }

    &.level-2 {
        background: linear-gradient(135deg, rgba($primary, 0.15) 0%, rgba($primary, 0.08) 100%);
        color: $primary;
        border: 1px solid rgba($primary, 0.2);
    }

    &.level-3 {
        background: linear-gradient(135deg, rgba($purple, 0.15) 0%, rgba($purple, 0.08) 100%);
        color: $purple;
        border: 1px solid rgba($purple, 0.2);
    }
}

.compact-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.compact-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 10px;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.2s;

    i {
        font-size: 0.65rem;
    }

    &.profitable {
        background: linear-gradient(135deg, rgba($success, 0.12) 0%, rgba($success, 0.05) 100%);
        color: $success;
        border: 1px solid rgba($success, 0.15);

        .compact-item:hover & {
            background: rgba($success, 0.18);
        }
    }

    &.active {
        background: linear-gradient(135deg, rgba(#3b82f6, 0.12) 0%, rgba(#3b82f6, 0.05) 100%);
        color: #3b82f6;
        border: 1px solid rgba(#3b82f6, 0.15);
    }

    &.inactive {
        background: rgba(#9ca3af, 0.12);
        color: #6b7280;
        border: 1px solid rgba(#9ca3af, 0.15);
    }
}

.compact-orders,
.compact-date {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: $text-muted;
    white-space: nowrap;

    i {
        font-size: 0.68rem;
        opacity: 0.8;
    }
}

// ==========================================
// 🆕 ЗАРАБОТОК (справа)
// ==========================================
.compact-earned {
    font-weight: 800;
    color: $success;
    font-size: 0.95rem;
    padding: 6px 12px;
    background: linear-gradient(135deg, rgba($success, 0.12) 0%, rgba($success, 0.05) 100%);
    border-radius: 12px;
    white-space: nowrap;
    flex-shrink: 0;
    border: 1px solid rgba($success, 0.15);
    transition: all 0.2s;
    letter-spacing: 0.3px;

    .compact-item:hover & {
        background: linear-gradient(135deg, rgba($success, 0.18) 0%, rgba($success, 0.08) 100%);
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba($success, 0.2);
    }
}

// ==========================================
// 🆕 СТРЕЛКА
// ==========================================
.compact-arrow {
    color: $text-muted;
    opacity: 0.3;
    transition: all 0.25s ease;
    flex-shrink: 0;
    font-size: 0.85rem;

    i {
        transition: transform 0.25s ease;
    }
}

// ==========================================
// 🆕 АДАПТИВ ДЛЯ МАЛЕНЬКИХ ЭКРАНОВ
// ==========================================
@media (max-width: 480px) {
    .compact-list {
        padding: 6px;
        gap: 8px;
    }

    .compact-item {
        padding: 12px;
        gap: 12px;

        &.profitable {
            padding-left: 8px;
        }
    }

    .compact-initials {
        width: 40px;
        height: 40px;
        font-size: 0.88rem;
        border-radius: 10px;
    }

    .compact-name {
        font-size: 0.92rem;
    }

    .compact-meta {
        gap: 6px;
    }

    .compact-badge {
        padding: 2px 7px;
        font-size: 0.68rem;
    }

    .compact-date {
        display: none; // Скрываем дату на маленьких экранах
    }

    .compact-earned {
        padding: 5px 10px;
        font-size: 0.88rem;
    }
}

// ==========================================
// 🆕 АНИМАЦИЯ ПОЯВЛЕНИЯ
// ==========================================
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.compact-item {
    animation: slideInUp 0.3s ease backwards;

    // Каскадная задержка для эффекта водопада
    @for $i from 1 through 20 {
        &:nth-child(#{$i}) {
            animation-delay: #{$i * 0.03}s;
        }
    }
}
</style>
