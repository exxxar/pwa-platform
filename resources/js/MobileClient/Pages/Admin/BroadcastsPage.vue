<template>
    <div class="broadcasts-page">

        <!-- ========================================== -->
        <!-- HERO -->
        <!-- ========================================== -->
        <div class="broadcasts-hero">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <h2 class="hero-title">Рассылки сообщений</h2>
                <p class="hero-subtitle">
                    Отправляйте сообщения всем пользователям с медиа и кнопками
                </p>
            </div>
        </div>

        <div class="container px-3">

            <!-- ========================================== -->
            <!-- СТАТИСТИКА -->
            <!-- ========================================== -->
            <div class="stats-grid">
                <div class="stat-card drafts">
                    <div class="stat-icon">
                        <i class="fa-solid fa-file-pen"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ draftBroadcasts.length }}</div>
                        <div class="stat-label">Черновики</div>
                    </div>
                </div>

                <div class="stat-card scheduled">
                    <div class="stat-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ scheduledBroadcasts.length }}</div>
                        <div class="stat-label">Запланировано</div>
                    </div>
                </div>

                <div class="stat-card sent">
                    <div class="stat-icon">
                        <i class="fa-solid fa-paper-plane"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ sentBroadcasts.length }}</div>
                        <div class="stat-label">Отправлено</div>
                    </div>
                </div>

                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ broadcasts.length }}</div>
                        <div class="stat-label">Всего рассылок</div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ПАНЕЛЬ ДЕЙСТВИЙ -->
            <!-- ========================================== -->
            <div class="actions-panel">
                <router-link :to="{ name: 'AdminBroadcastCreate' }" class="create-btn">
                    <i class="fa-solid fa-plus"></i>
                    <span>Создать рассылку</span>
                </router-link>

                <div class="filter-group">
                    <button
                        v-for="filter in statusFilters"
                        :key="filter.value"
                        class="filter-btn"
                        :class="{ 'active': filters.status === filter.value }"
                        @click="setFilter('status', filter.value)"
                    >
                        <i :class="filter.icon"></i>
                        <span>{{ filter.label }}</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ЗАГРУЗКА -->
            <!-- ========================================== -->
            <div v-if="isLoading && !isHydrated" class="loading-state">
                <div class="loader-spinner"></div>
                <p>Загрузка рассылок...</p>
            </div>

            <!-- ========================================== -->
            <!-- ПУСТОЕ СОСТОЯНИЕ -->
            <!-- ========================================== -->
            <div v-else-if="broadcasts.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <h4>Рассылок пока нет</h4>
                <p>Создайте первую рассылку, чтобы отправить сообщение всем пользователям</p>
                <router-link :to="{ name: 'AdminBroadcastCreate' }" class="create-btn small">
                    <i class="fa-solid fa-plus"></i>
                    <span>Создать рассылку</span>
                </router-link>
            </div>

            <!-- ========================================== -->
            <!-- СПИСОК РАССЫЛОК -->
            <!-- ========================================== -->
            <div v-else class="broadcasts-list">
                <BroadcastCard
                    v-for="broadcast in broadcasts"
                    :key="broadcast.id"
                    :broadcast="broadcast"
                    @send="handleSend"
                    @cancel="handleCancel"
                    @delete="handleDelete"
                    @duplicate="handleDuplicate"
                />
            </div>

        </div>
    </div>
</template>

<script>
import { useBroadcasts } from '@/MobileClient/Composables/useBroadcasts.js';
import BroadcastCard from '@/MobileClient/Components/Admin/Broadcasts/BroadcastCard.vue';

export default {
    name: 'BroadcastsPage',

    components: {
        BroadcastCard,
    },

    setup() {
        const broadcasts = useBroadcasts();
        return { ...broadcasts };
    },

    data() {
        return {
            statusFilters: [
                { value: null, label: 'Все', icon: 'fa-solid fa-globe' },
                { value: 'draft', label: 'Черновики', icon: 'fa-solid fa-file-pen' },
                { value: 'scheduled', label: 'Запланированные', icon: 'fa-solid fa-clock' },
                { value: 'sent', label: 'Отправленные', icon: 'fa-solid fa-paper-plane' },
            ],
        };
    },

    watch: {
        'filters.status'() {
            this.loadBroadcasts();
        },
    },

    async mounted() {
        await this.loadBroadcasts();
    },

    methods: {
        async handleSend(broadcast) {
            if (!confirm('Отправить рассылку всем получателям?')) return;

            try {
                await this.sendBroadcast(broadcast.id);
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Рассылка отправлена',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить рассылку',
                    type: 'error',
                });
            }
        },

        async handleCancel(broadcast) {
            if (!confirm('Отменить рассылку?')) return;

            try {
                await this.cancelBroadcast(broadcast.id);
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Рассылка отменена',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отменить',
                    type: 'error',
                });
            }
        },

        async handleDelete(broadcast) {
            if (!confirm('Удалить рассылку? Это действие необратимо.')) return;

            try {
                await this.deleteBroadcast(broadcast.id);
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Рассылка удалена',
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

        async handleDuplicate(broadcast) {
            try {
                await this.duplicateBroadcast(broadcast.id);
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Рассылка дублирована',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось дублировать',
                    type: 'error',
                });
            }
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$purple: #8b5cf6;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.broadcasts-page {
    min-height: 100vh;
    background: $bg-secondary;
    padding-bottom: 40px;
}

// ==========================================
// HERO
// ==========================================
.broadcasts-hero {
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
    margin: 0;
}

// ==========================================
// СТАТИСТИКА
// ==========================================
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin: -30px 0 20px;
    position: relative;
    z-index: 2;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    transition: all 0.2s;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        flex-shrink: 0;
    }

    .stat-info {
        flex: 1;
        min-width: 0;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: $text;
        line-height: 1.1;
    }

    .stat-label {
        font-size: 0.8rem;
        color: $text-muted;
        margin-top: 2px;
    }

    &.drafts .stat-icon { background: linear-gradient(135deg, $text-muted, #4b5563); }
    &.scheduled .stat-icon { background: linear-gradient(135deg, $warning, #d97706); }
    &.sent .stat-icon { background: linear-gradient(135deg, $success, #059669); }
    &.total .stat-icon { background: linear-gradient(135deg, $primary, $primary-dark); }
}

// ==========================================
// ПАНЕЛЬ ДЕЙСТВИЙ
// ==========================================
.actions-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.create-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba($primary, 0.3);
    text-decoration: none;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba($primary, 0.4);
    }

    &.small {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}

.filter-group {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.filter-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 10px;
    color: $text-muted;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        color: $primary;
    }

    &.active {
        background: $primary;
        border-color: $primary;
        color: white;
    }
}

// ==========================================
// СОСТОЯНИЯ
// ==========================================
.loading-state,
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;

    .loader-spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 16px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

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

    h4 {
        font-weight: 700;
        margin: 0 0 8px;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0 0 20px;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// СПИСОК
// ==========================================
.broadcasts-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .broadcasts-hero {
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

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .actions-panel {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 4px;

        &::-webkit-scrollbar {
            display: none;
        }
    }

    .filter-btn span {
        display: none;
    }
}
</style>
