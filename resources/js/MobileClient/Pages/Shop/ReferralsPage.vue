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

                <!-- Статистика -->
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
                        :value="referralLink"
                        readonly
                        class="link-input"
                        ref="linkInput"
                    >
                    <button class="copy-btn" @click="copyLink">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>

                <div class="share-buttons">
                    <button class="share-btn telegram" @click="shareViaTelegram">
                        <i class="fa-brands fa-telegram"></i>
                        Telegram
                    </button>
                    <button class="share-btn whatsapp" @click="shareViaWhatsApp">
                        <i class="fa-brands fa-whatsapp"></i>
                        WhatsApp
                    </button>
                    <button class="share-btn vk" @click="shareViaVk">
                        <i class="fa-brands fa-vk"></i>
                        VK
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

                <!-- Загрузка -->
                <div v-if="isLoading" class="loading-state">
                    <div class="loader-spinner"></div>
                </div>

                <template v-else>
                    <!-- Уровень 1 -->
                    <div class="level-section">
                        <div class="level-header level-1">
                            <div class="level-badge">1</div>
                            <div class="level-info">
                                <h4>Прямые рефералы</h4>
                                <p>10% от их покупок</p>
                            </div>
                            <span class="level-count">{{ referralTree.level_1.length }}</span>
                        </div>
                        <div v-if="referralTree.level_1.length === 0" class="empty-level">
                            <i class="fa-solid fa-user-plus"></i>
                            <p>Пока нет рефералов</p>
                        </div>
                        <div v-else class="users-list">
                            <UserCard
                                v-for="ref in referralTree.level_1"
                                :key="ref.id"
                                :user="ref.user"
                                :level="1"
                                :registered-at="ref.registered_at"
                            />
                        </div>
                    </div>

                    <!-- Уровень 2 -->
                    <div class="level-section">
                        <div class="level-header level-2">
                            <div class="level-badge">2</div>
                            <div class="level-info">
                                <h4>Рефералы 2-го уровня</h4>
                                <p>5% от их покупок</p>
                            </div>
                            <span class="level-count">{{ referralTree.level_2.length }}</span>
                        </div>
                        <div v-if="referralTree.level_2.length === 0" class="empty-level">
                            <i class="fa-solid fa-user-group"></i>
                            <p>Пока нет</p>
                        </div>
                        <div v-else class="users-list">
                            <UserCard
                                v-for="ref in referralTree.level_2"
                                :key="ref.id"
                                :user="ref.user"
                                :level="2"
                                :registered-at="ref.registered_at"
                            />
                        </div>
                    </div>

                    <!-- Уровень 3 -->
                    <div class="level-section">
                        <div class="level-header level-3">
                            <div class="level-badge">3</div>
                            <div class="level-info">
                                <h4>Рефералы 3-го уровня</h4>
                                <p>2% от их покупок</p>
                            </div>
                            <span class="level-count">{{ referralTree.level_3.length }}</span>
                        </div>
                        <div v-if="referralTree.level_3.length === 0" class="empty-level">
                            <i class="fa-solid fa-users"></i>
                            <p>Пока нет</p>
                        </div>
                        <div v-else class="users-list">
                            <UserCard
                                v-for="ref in referralTree.level_3"
                                :key="ref.id"
                                :user="ref.user"
                                :level="3"
                                :registered-at="ref.registered_at"
                            />
                        </div>
                    </div>
                </template>
            </div>

            <!-- ========================================== -->
            <!-- ТАБ: ДРУЗЬЯ -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'friends'" class="tab-content">

                <!-- Входящие заявки -->
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

                <!-- Список друзей -->
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
    </div>
</template>

<script>
import { useReferrals } from '@/MobileClient/Composables/useReferrals.js';
import UserCard from '@/MobileClient/Components/Referrals/UserCard.vue';
import FriendCard from '@/MobileClient/Components/Referrals/FriendCard.vue';
import FriendRequestCard from '@/MobileClient/Components/Referrals/FriendRequestCard.vue';
import RewardCard from '@/MobileClient/Components/Referrals/RewardCard.vue';

export default {
    name: 'ReferralsPage',

    components: {
        UserCard,
        FriendCard,
        FriendRequestCard,
        RewardCard,
    },

    setup() {
        const referrals = useReferrals();
        return { ...referrals };
    },

    data() {
        return {
            activeTab: 'referrals',
            copied: false,
        };
    },

    async mounted() {
        await Promise.all([
            this.loadReferralTree(),
            this.loadReferralLink(),
            this.loadFriends(),
            this.loadIncomingRequests(),
            this.loadRewardsHistory(),
        ]);
    },

    methods: {
        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.referralLink);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);

                this.$notify?.({
                    title: 'Успех',
                    text: 'Ссылка скопирована',
                    type: 'success',
                });
            } catch (err) {
                // Fallback
                this.$refs.linkInput.select();
                document.execCommand('copy');

                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Ошибка копирования ссылки',
                    type: 'error',
                });
            }
        },

        shareViaTelegram() {
            const text = encodeURIComponent(`Присоединяйся! ${this.referralLink}`);
            window.open(`https://t.me/share/url?url=${encodeURIComponent(this.referralLink)}&text=${text}`);
        },

        shareViaWhatsApp() {
            const text = encodeURIComponent(`Присоединяйся! ${this.referralLink}`);
            window.open(`https://wa.me/?text=${text}`);
        },

        shareViaVk() {
            window.open(`https://vk.com/share.php?url=${encodeURIComponent(this.referralLink)}`);
        },

        async handleAcceptRequest(requestId) {
            try {
                await this.acceptFriendRequest(requestId);
                this.$notify?.({
                    title: 'Успех',
                    text: 'Заявка принята',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось принять заявку',
                    type: 'error',
                });
            }
        },

        async handleRejectRequest(requestId) {
            try {
                await this.rejectFriendRequest(requestId);
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отклонить заявку',
                    type: 'error',
                });
            }
        },

        async handleRemoveFriend(friendId) {
            if (!confirm('Удалить из друзей?')) return;

            try {
                await this.removeFriend(friendId);
                this.$notify?.({
                    title: 'Успех',
                    text: 'Удалён из друзей',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить',
                    type: 'error',
                });
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
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
    background:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(255,255,255,0.08) 0%, transparent 40%);
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
    gap: 6px;
    padding: 12px 16px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $primary-dark;
    }
}

.share-buttons {
    display: flex;
    gap: 8px;
}

.share-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 10px;
    color: $text;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    &.telegram {
        color: #0088cc;
        &:hover { background: #0088cc; color: white; }
    }

    &.whatsapp {
        color: #25d366;
        &:hover { background: #25d366; color: white; }
    }

    &.vk {
        color: #0077ff;
        &:hover { background: #0077ff; color: white; }
    }
}

// ==========================================
// ТАБЫ
// ==========================================
.tabs-wrapper {
    display: flex;
    gap: 4px;
    background: $bg;
    padding: 4px;
    border-radius: 14px;
    margin-bottom: 16px;
    border: 1px solid $border;
    overflow-y: auto;
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

    .active & {
        background: rgba(255, 255, 255, 0.25);
    }
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

.users-list {
    padding: 8px;
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
// АДАПТИВ
// ==========================================
@media (max-width: 576px) {
    .hero-title { font-size: 1.5rem; }
    .hero-icon { width: 60px; height: 60px; font-size: 1.6rem; }

    .hero-stats {
        gap: 12px;
        padding: 12px 16px;
    }

    .stat-value { font-size: 1.2rem; }

    .share-btn span { display: none; }
}
</style>
