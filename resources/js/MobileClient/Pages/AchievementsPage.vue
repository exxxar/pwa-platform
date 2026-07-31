<template>
    <div class="achievements-page">

        <!-- Уведомление о разблокировке -->
        <UnlockedNotification
            v-if="recentlyUnlocked.length > 0"
            :achievement="recentlyUnlocked[0]"
            @close="clearRecentlyUnlocked"
        />

        <!-- ========================================== -->
        <!-- ЗАГОЛОВОК -->
        <!-- ========================================== -->
        <div class="page-hero">
            <div class="hero-bg">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <h1 class="hero-title">Мои достижения</h1>
                <p class="hero-subtitle">
                    Выполняйте задания и получайте награды
                </p>

                <!-- Общий прогресс -->
                <div class="overall-progress">
                    <div class="progress-circle" :style="progressCircleStyle">
                        <span class="progress-value">{{ completionPercent }}%</span>
                    </div>
                    <div class="progress-info">
                        <div class="progress-label">Общий прогресс</div>
                        <div class="progress-text">
                            {{ unlockedCount }} из {{ totalCount }} достижений
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА -->
        <!-- ========================================== -->
        <div v-if="isLoading && !isHydrated" class="loading-state">
            <div class="loader-spinner"></div>
            <p>Загрузка достижений...</p>
        </div>

        <template v-else>

            <div class="container py-3">


                <!-- ========================================== -->
                <!-- СТАТИСТИКА -->
                <!-- ========================================== -->
                <AchievementStats/>

                <!-- ========================================== -->
                <!-- КНОПКА "ЗАБРАТЬ ВСЕ НАГРАДЫ" -->
                <!-- ========================================== -->
                <div v-if="unclaimedRewardsCount > 0" class="claim-all-section">
                    <div class="claim-all-card">
                        <div class="claim-all-icon">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <div class="claim-all-info">
                            <h3>У вас {{ unclaimedRewardsCount }}
                                {{ pluralize(unclaimedRewardsCount, 'награда', 'награды', 'наград') }}!</h3>
                            <p>Заберите их прямо сейчас</p>
                        </div>
                        <button
                            class="claim-all-btn"
                            @click="handleClaimAll"
                            :disabled="isClaiming"
                        >
                            <i v-if="isClaiming" class="fa-solid fa-spinner fa-spin"></i>
                            <template v-else>
                                <i class="fa-solid fa-hand-holding-heart"></i>
                                Забрать все
                            </template>
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- ФИЛЬТРЫ ПО КАТЕГОРИЯМ -->
                <!-- ========================================== -->
                <div class="filters-section">
                    <div class="filters-scroll">
                        <button
                            v-for="filter in filters"
                            :key="filter.key"
                            class="filter-btn"
                            :class="{ 'is-active': activeFilter === filter.key }"
                            @click="activeFilter = filter.key"
                        >
                            <i :class="filter.icon"></i>
                            <span>{{ filter.label }}</span>
                            <span v-if="filter.count > 0" class="filter-count">{{ filter.count }}</span>
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- ПОЛУЧЕННЫЕ ДОСТИЖЕНИЯ -->
                <!-- ========================================== -->
                <div v-if="filteredUnlocked.length > 0" class="section">
                    <div class="section-header">
                        <h2>
                            <i class="fa-solid fa-trophy" style="color: #22c55e;"></i>
                            Полученные
                        </h2>
                        <span class="section-count">{{ filteredUnlocked.length }}</span>
                    </div>
                    <div class="achievements-grid">
                        <AchievementCard
                            v-for="item in filteredUnlocked"
                            :key="item.id"
                            :achievement="item.achievement"
                            :is-unlocked="true"
                            :unlocked-at="item.unlocked_at"
                            :reward-claimed="item.reward_claimed"
                            :is-claiming="isAchievementLoading(item.achievement_id)"
                            @claim="handleClaim"
                        />
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- В ПРОЦЕССЕ -->
                <!-- ========================================== -->
                <div v-if="filteredProgress.length > 0" class="section">
                    <div class="section-header">
                        <h2>
                            <i class="fa-solid fa-hourglass-half" style="color: #f59e0b;"></i>
                            В процессе
                        </h2>
                        <span class="section-count">{{ filteredProgress.length }}</span>
                    </div>
                    <div class="achievements-grid">
                        <AchievementCard
                            v-for="item in filteredProgress"
                            :key="item.achievement.id"
                            :achievement="item.achievement"
                            :progress="item.progress_percent"
                            :current-value="item.current_value"
                            :target-value="item.target_value"
                            :is-unlocked="item.is_unlocked"
                        />
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- ПУСТОЕ СОСТОЯНИЕ -->
                <!-- ========================================== -->
                <div v-if="filteredUnlocked.length === 0 && filteredProgress.length === 0" class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <h3>Достижений в этой категории нет</h3>
                    <p>Попробуйте выбрать другую категорию</p>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import {useAchievements} from '@/MobileClient/Composables/useAchievements.js';
import AchievementCard from '@/MobileClient/Components/Achievements/AchievementCard.vue';
import AchievementStats from '@/MobileClient/Components/Achievements/AchievementStats.vue';
import UnlockedNotification from '@/MobileClient/Components/Achievements/UnlockedNotification.vue';

export default {
    name: 'AchievementsPage',

    components: {
        AchievementCard,
        AchievementStats,
        UnlockedNotification,
    },

    setup() {
        const achievements = useAchievements();
        return {...achievements};
    },

    data() {
        return {
            activeFilter: 'all',
        };
    },

    computed: {
        filters() {
            const categories = this.achievementsByCategory;
            const filters = [
                {key: 'all', label: 'Все', icon: 'fa-solid fa-globe', count: this.totalCount},
            ];

            Object.keys(categories).forEach(key => {
                filters.push({
                    key,
                    label: this.formatConditionType(key),
                    icon: this.getCategoryIcon(key),
                    count: categories[key].length,
                });
            });

            return filters;
        },

        filteredUnlocked() {
            if (this.activeFilter === 'all') return this.unlockedAchievements;
            return this.unlockedAchievements.filter(
                item => item.achievement?.condition_type === this.activeFilter
            );
        },

        filteredProgress() {
            const progress = this.allAchievements.filter(item => !item.is_unlocked);
            if (this.activeFilter === 'all') return progress;
            return progress.filter(
                item => item.achievement?.condition_type === this.activeFilter
            );
        },

        progressCircleStyle() {
            const percent = this.completionPercent;
            const deg = (percent / 100) * 360;
            return {
                background: `conic-gradient(#22c55e ${deg}deg, #e5e7eb ${deg}deg 360deg)`,
            };
        },
    },

    async mounted() {
        if (!this.isHydrated) {
            try {
                await this.loadAchievements();
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить достижения',
                    type: 'error',
                });
            }
        }
    },

    methods: {
        async handleClaim(achievementId) {
            try {
                const result = await this.claimReward(achievementId);
                this.$notify?.({
                    title: '🎉 Поздравляем!',
                    text: `Вы получили ${this.formatRewardValue(result.reward.value, result.reward.type)}`,
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось получить награду',
                    type: 'error',
                });
            }
        },

        async handleClaimAll() {
            try {
                const results = await this.claimAllRewards();
                const successCount = results.filter(r => r.success).length;

                if (successCount > 0) {
                    this.$notify?.({
                        title: '🎉 Все награды получены!',
                        text: `Забрано наград: ${successCount}`,
                        type: 'success',
                    });
                }
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось получить награды',
                    type: 'error',
                });
            }
        },

        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

$primary: #667eea;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$success: #22c55e;
$warning: #f59e0b;

.achievements-page {
    min-height: 100vh;
    background: $bg-secondary;
    padding-bottom: 40px;
}

// ==========================================
// HERO
// ==========================================
.page-hero {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px 60px;
    color: white;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;

    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: 0.3;

        &.blob-1 {
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.3);
            top: -100px;
            right: -50px;
            animation: float 20s ease-in-out infinite;
        }

        &.blob-2 {
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.2);
            bottom: -80px;
            left: -30px;
            animation: float 25s ease-in-out infinite reverse;
        }
    }
}

@keyframes float {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    50% {
        transform: translate(20px, -20px) scale(1.1);
    }
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
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
    font-size: 2rem;
    font-weight: 800;
    margin: 0 0 8px;
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0 0 24px;
}

.overall-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
}

.progress-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;

    &::before {
        content: '';
        position: absolute;
        inset: 4px;
        background: rgba(102, 126, 234, 0.9);
        border-radius: 50%;
    }
}

.progress-value {
    position: relative;
    z-index: 1;
    font-size: 1rem;
    font-weight: 800;
    color: white;
}

.progress-info {
    text-align: left;
}

.progress-label {
    font-size: 0.85rem;
    opacity: 0.8;
    margin-bottom: 2px;
}

.progress-text {
    font-size: 1rem;
    font-weight: 700;
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

    .loader-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 16px;
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

// ==========================================
// ЗАБРАТЬ ВСЕ НАГРАДЫ
// ==========================================
.claim-all-section {
    padding: 0 16px;
    margin-bottom: 20px;
}

.claim-all-card {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid $warning;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.2);
}

.claim-all-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: $warning;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

.claim-all-info {
    flex: 1;
    min-width: 0;

    h3 {
        margin: 0 0 2px;
        font-size: 1rem;
        font-weight: 700;
        color: #78350f;
    }

    p {
        margin: 0;
        font-size: 0.8rem;
        color: #92400e;
    }
}

.claim-all-btn {
    padding: 10px 20px;
    background: $warning;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    white-space: nowrap;

    &:hover:not(:disabled) {
        background: color.adjust($warning, $lightness: -10%);
        transform: translateY(-2px);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

// ==========================================
// ФИЛЬТРЫ
// ==========================================
.filters-section {
    padding: 0 16px;
    margin-bottom: 20px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}

.filters-scroll {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 4px 0;
    -webkit-overflow-scrolling: touch;

    &::-webkit-scrollbar {
        display: none;
    }
}

.filter-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 20px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    i {
        font-size: 0.85rem;
    }

    &:hover {
        background: $bg-secondary;
        color: $text;
    }

    &.is-active {
        background: $primary;
        color: white;
        border-color: $primary;
    }
}

.filter-count {
    padding: 1px 6px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    font-size: 0.7rem;

    .is-active & {
        background: rgba(255, 255, 255, 0.2);
    }
}

// ==========================================
// СЕКЦИИ
// ==========================================
.section {
    max-width: 900px;
    margin: 0 auto 24px;
    padding: 0 16px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;

    h2 {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: $text;

        i {
            font-size: 1rem;
        }
    }
}

.section-count {
    padding: 4px 10px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 700;
    color: $text-muted;
}

.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 12px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    max-width: 900px;
    margin: 40px auto;
    padding: 60px 20px;
    text-align: center;
    background: $bg;
    border-radius: 16px;
    border: 1px solid $border;

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
        opacity: 0.5;
    }

    h3 {
        margin: 0 0 8px;
        font-size: 1.1rem;
        color: $text;
    }

    p {
        margin: 0;
        color: $text-muted;
        font-size: 0.9rem;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .page-hero {
        padding: 30px 16px 50px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .hero-icon {
        width: 60px;
        height: 60px;
        font-size: 1.6rem;
    }

    .overall-progress {
        flex-direction: column;
        gap: 12px;
    }

    .progress-info {
        text-align: center;
    }

    .claim-all-card {
        flex-direction: column;
        text-align: center;
    }

    .claim-all-btn {
        width: 100%;
        justify-content: center;
    }

    .achievements-grid {
        grid-template-columns: 1fr;
    }
}
</style>
