<template>
    <div class="ads-page">
        <!-- HERO -->
        <div class="page-hero">
            <div class="hero-bg">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>
            <div class="hero-content">
                <div class="hero-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <h1 class="hero-title">Партнёрская реклама</h1>
                <p class="hero-subtitle">
                    Управляйте рекламными блоками, которые показываются всем клиентам на главной
                </p>
            </div>
        </div>

        <!-- СТАТИСТИКА -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.total }}</div>
                        <div class="stat-label">Всего блоков</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.active }}</div>
                        <div class="stat-label">Активных</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                        <i class="fa-solid fa-pause"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.inactive }}</div>
                        <div class="stat-label">Отключено</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.scheduled }}</div>
                        <div class="stat-label">По расписанию</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- СПИСОК -->
        <div class="ads-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-rectangle-ad"></i>
                    Рекламные блоки
                </h2>
                <div class="section-actions">
                    <button class="refresh-btn" @click="loadAds" :disabled="isLoading">
                        <i class="fa-solid fa-rotate" :class="{ 'fa-spin': isLoading }"></i>
                        <span>Обновить</span>
                    </button>
                    <button class="add-btn" @click="openFormModal()">
                        <i class="fa-solid fa-plus"></i>
                        <span>Добавить рекламу</span>
                    </button>
                </div>
            </div>

            <div v-if="isLoading && ads.length === 0" class="loading-state">
                <div class="loader-spinner"></div>
                <p>Загрузка...</p>
            </div>

            <div v-else-if="ads.length === 0" class="empty-state">
                <div class="empty-icon">📢</div>
                <h3>Пока нет рекламных блоков</h3>
                <p>Создайте первый блок — он появится в карусели на главной</p>
                <button class="add-btn" @click="openFormModal()">
                    <i class="fa-solid fa-plus"></i>
                    <span>Создать блок</span>
                </button>
            </div>

            <div v-else class="ads-list">
                <div
                    v-for="(ad, idx) in ads"
                    :key="ad.id"
                    class="ad-card"
                    :class="{ 'is-inactive': !ad.is_active, 'is-scheduled': ad.starts_at }"
                >
                    <div class="ad-card-preview">
                        <img
                            :src="ad.image || '/images/fastoran/pizza.png'"
                            :alt="ad.title"
                            class="ad-preview-image"
                        >
                        <span v-if="ad.badge" class="ad-preview-badge">{{ ad.badge }}</span>
                        <span v-if="!ad.is_active" class="ad-preview-disabled">Отключено</span>
                    </div>

                    <div class="ad-card-body">
                        <div class="ad-card-top">
                            <div class="ad-position">#{{ idx + 1 }}</div>
                            <h3 class="ad-title">{{ ad.title }}</h3>
                            <span class="ad-type-tag">
                                <i class="fa-solid fa-bullhorn"></i> Глобальная
                            </span>
                        </div>

                        <p class="ad-short-text">
                            {{ ad.short_text || 'Без описания' }}
                        </p>

                        <div class="ad-meta">
                            <span v-if="ad.button_text" class="meta-item">
                                <i class="fa-solid fa-hand-pointer"></i> {{ ad.button_text }}
                            </span>
                            <span v-if="ad.action_type === 'url'" class="meta-item">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                {{ truncateUrl(ad.action_value) }}
                            </span>
                            <span v-if="ad.starts_at" class="meta-item schedule">
                                <i class="fa-solid fa-calendar"></i>
                                с {{ formatDate(ad.starts_at) }}
                                <template v-if="ad.ends_at"> по {{ formatDate(ad.ends_at) }}</template>
                            </span>
                        </div>
                    </div>

                    <!-- Кнопка открытия Bottom Sheet -->
                    <div class="ad-card-actions">
                        <button
                            class="more-btn"
                            @click="openActionsSheet(ad, idx)"
                            title="Действия"
                        >
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- BOTTOM SHEET: ДЕЙСТВИЯ С РЕКЛАМОЙ -->
        <!-- ========================================== -->
        <transition name="bottom-sheet">
            <div
                v-if="showActionsSheet && selectedAd"
                class="sheet-backdrop"
                @click="closeActionsSheet"
            >
                <div class="sheet-container" @click.stop>
                    <div class="sheet-handle"></div>

                    <!-- Превью рекламы -->
                    <div class="sheet-preview">
                        <img
                            :src="selectedAd.image || '/images/fastoran/pizza.png'"
                            :alt="selectedAd.title"
                            class="sheet-preview-image"
                        >
                        <div class="sheet-preview-info">
                            <h3 class="sheet-preview-title">{{ selectedAd.title }}</h3>
                            <p class="sheet-preview-subtitle">
                                {{ selectedAd.short_text || 'Без описания' }}
                            </p>
                            <div class="sheet-preview-status">
                                <span
                                    class="status-dot"
                                    :class="selectedAd.is_active ? 'active' : 'inactive'"
                                ></span>
                                {{ selectedAd.is_active ? 'Активна' : 'Отключена' }}
                                <span v-if="selectedAd.starts_at" class="schedule-hint">
                                    · с {{ formatDate(selectedAd.starts_at) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Действия -->
                    <div class="sheet-actions">
                        <!-- Позиция -->
                        <div class="sheet-section-title">Позиция</div>
                        <div class="sheet-row-group">
                            <button
                                class="sheet-row"
                                :disabled="selectedAdIndex === 0"
                                @click="moveUp(selectedAdIndex)"
                            >
                                <div class="sheet-row-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                    <i class="fa-solid fa-arrow-up"></i>
                                </div>
                                <span class="sheet-row-label">Переместить вверх</span>
                            </button>
                            <button
                                class="sheet-row"
                                :disabled="selectedAdIndex === ads.length - 1"
                                @click="moveDown(selectedAdIndex)"
                            >
                                <div class="sheet-row-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                    <i class="fa-solid fa-arrow-down"></i>
                                </div>
                                <span class="sheet-row-label">Переместить вниз</span>
                            </button>
                        </div>

                        <!-- Статус и редактирование -->
                        <div class="sheet-section-title">Управление</div>
                        <div class="sheet-row-group">
                            <button class="sheet-row" @click="toggleActive(selectedAd)">
                                <div
                                    class="sheet-row-icon"
                                    :style="{
                                        background: selectedAd.is_active ? 'rgba(245, 158, 11, 0.1)' : 'rgba(16, 185, 129, 0.1)',
                                        color: selectedAd.is_active ? '#f59e0b' : '#10b981'
                                    }"
                                >
                                    <i :class="selectedAd.is_active ? 'fa-solid fa-pause' : 'fa-solid fa-play'"></i>
                                </div>
                                <span class="sheet-row-label">
                                    {{ selectedAd.is_active ? 'Отключить показ' : 'Включить показ' }}
                                </span>
                            </button>
                            <button class="sheet-row" @click="editFromSheet">
                                <div class="sheet-row-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                    <i class="fa-solid fa-pen"></i>
                                </div>
                                <span class="sheet-row-label">Редактировать</span>
                            </button>
                        </div>

                        <!-- Опасная зона -->
                        <div class="sheet-row-group danger-zone">
                            <button class="sheet-row danger" @click="deleteFromSheet">
                                <div class="sheet-row-icon">
                                    <i class="fa-solid fa-trash"></i>
                                </div>
                                <span class="sheet-row-label">Удалить блок</span>
                            </button>
                        </div>
                    </div>

                    <!-- Кнопка отмены -->
                    <button class="sheet-cancel" @click="closeActionsSheet">
                        Отмена
                    </button>
                </div>
            </div>
        </transition>

        <!-- МОДАЛКА РЕДАКТИРОВАНИЯ -->
        <AdFormModal
            v-model="showFormModal"
            :ad="editingAd"
            @saved="onAdSaved"
        />
    </div>
</template>

<script>
import AdFormModal from '@/MobileClient/Components/Admin/Ads/AdFormModal.vue'
import { useApi } from '@/AdminPanel/Composables/useApi.js'

export default {
    name: 'AdsIndex',
    components: { AdFormModal },

    setup() {
        return { api: useApi() }
    },

    data() {
        return {
            ads: [],
            stats: { total: 0, active: 0, inactive: 0, scheduled: 0 },
            isLoading: false,
            showFormModal: false,
            editingAd: null,

            // Bottom sheet
            showActionsSheet: false,
            selectedAd: null,
            selectedAdIndex: -1,
        }
    },

    mounted() {
        this.loadAds()
    },

    methods: {
        async loadAds() {
            this.isLoading = true
            try {
                const r = await this.api.get('/promotions')
                const payload = r.data ?? r
                this.ads = payload.items ?? []
                this.stats = payload.stats ?? this.stats
            } catch (e) {
                console.error(e)
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить рекламу', type: 'error' })
            } finally {
                this.isLoading = false
            }
        },

        openFormModal(ad = null) {
            this.editingAd = ad
            this.showFormModal = true
        },

        onAdSaved() {
            this.showFormModal = false
            this.editingAd = null
            this.loadAds()
        },

        // ========== BOTTOM SHEET ==========
        openActionsSheet(ad, idx) {
            this.selectedAd = ad
            this.selectedAdIndex = idx
            this.showActionsSheet = true
            document.body.style.overflow = 'hidden'
        },

        closeActionsSheet() {
            this.showActionsSheet = false
            document.body.style.overflow = ''
            // Задержка перед очисткой, чтобы анимация успела отобразиться
            setTimeout(() => {
                if (!this.showActionsSheet) {
                    this.selectedAd = null
                    this.selectedAdIndex = -1
                }
            }, 300)
        },

        editFromSheet() {
            const ad = this.selectedAd
            this.closeActionsSheet()
            this.$nextTick(() => this.openFormModal(ad))
        },

        async deleteFromSheet() {
            const ad = this.selectedAd
            if (!ad) return
            if (!confirm(`Удалить рекламу «${ad.title}»?`)) return

            this.closeActionsSheet()

            try {
                await this.api.delete(`/promotions/${ad.id}`)
                this.ads = this.ads.filter(a => a.id !== ad.id)
                this.$notify?.({ title: 'Удалено', text: 'Рекламный блок удалён', type: 'success' })
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось удалить', type: 'error' })
            }
        },

        async toggleActive(ad) {
            try {
                await this.api.patch(`/promotions/${ad.id}`, { is_active: !ad.is_active })
                ad.is_active = !ad.is_active
                this.$notify?.({
                    title: 'Готово',
                    text: ad.is_active ? 'Реклама включена' : 'Реклама отключена',
                    type: 'success',
                })
                this.closeActionsSheet()
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось изменить статус', type: 'error' })
            }
        },

        async moveUp(idx) {
            if (idx === 0) return
            [this.ads[idx - 1], this.ads[idx]] = [this.ads[idx], this.ads[idx - 1]]
            this.selectedAdIndex = idx - 1
            await this.saveOrder()
            this.closeActionsSheet()
        },

        async moveDown(idx) {
            if (idx >= this.ads.length - 1) return
            [this.ads[idx + 1], this.ads[idx]] = [this.ads[idx], this.ads[idx + 1]]
            this.selectedAdIndex = idx + 1
            await this.saveOrder()
            this.closeActionsSheet()
        },

        async saveOrder() {
            try {
                await this.api.post('/promotions/reorder', { ids: this.ads.map(a => a.id) })
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось обновить порядок', type: 'error' })
            }
        },

        formatDate(d) {
            return d ? new Date(d).toLocaleDateString('ru-RU') : ''
        },
        truncateUrl(url) {
            if (!url) return ''
            try {
                const u = new URL(url)
                return u.hostname + (u.pathname.length > 10 ? u.pathname.slice(0, 10) + '…' : u.pathname)
            } catch { return url.slice(0, 30) }
        },
    },
}
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-danger: #ef4444;

.ads-page { background: $admin-bg; min-height: 100%; padding-bottom: env(safe-area-inset-bottom); }

// ========== HERO ==========
.page-hero {
    position: relative;
    background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
    padding: 40px 20px 50px;
    color: white;
    overflow: hidden;
}
.hero-bg {
    position: absolute; inset: 0;
    .blob {
        position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.3;
        &.blob-1 { width: 300px; height: 300px; background: rgba(255,255,255,0.3); top: -100px; right: -50px; }
        &.blob-2 { width: 250px; height: 250px; background: rgba(255,255,255,0.2); bottom: -80px; left: -30px; }
    }
}
.hero-content { position: relative; z-index: 1; max-width: 900px; margin: 0 auto; text-align: center; }
.hero-icon {
    width: 72px; height: 72px; margin: 0 auto 16px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.3); border-radius: 20px;
    display: flex; align-items: center; justify-content: center; font-size: 2rem;
}
.hero-title { font-size: 2rem; font-weight: 800; margin: 0 0 8px; }
.hero-subtitle { font-size: 1rem; opacity: 0.9; margin: 0; }

// ========== STATS ==========
.stats-section { padding: 10px 16px; max-width: 900px; margin: 0 auto; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 6px; }
.stat-card {
    display: flex; align-items: center; gap: 12px; padding: 16px;
    background: #fff; border: 1px solid $admin-border; border-radius: 14px;
    transition: all 0.2s;
    &:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
}
.stat-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1.3rem; flex-shrink: 0;
}
.stat-info { flex: 1; min-width: 0; }
.stat-value { font-size: 1.4rem; font-weight: 800; color: $admin-text; line-height: 1.2; }
.stat-label { font-size: 0.75rem; color: $admin-text-muted; margin-top: 2px; }

// ========== SECTION ==========
.ads-section { padding: 0 16px 20px; max-width: 900px; margin: 0 auto; }
.section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; flex-wrap: wrap; gap: 12px;
}
.section-title {
    display: flex; align-items: center; gap: 10px; margin: 0;
    font-size: 1.2rem; font-weight: 700; color: $admin-text;
    i { color: $admin-primary; }
}
.section-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.refresh-btn, .add-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 16px; border-radius: 10px;
    font-size: 0.85rem; font-weight: 600;
    cursor: pointer; transition: all 0.2s; border: 1px solid;
}
.refresh-btn {
    background: $admin-card-bg; border-color: $admin-border; color: $admin-text;
    &:hover:not(:disabled) { background: $admin-primary; color: white; border-color: $admin-primary; }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}
.add-btn {
    background: linear-gradient(135deg, $admin-primary 0%, #2563eb 100%);
    color: white; border-color: transparent;
    box-shadow: 0 4px 12px rgba($admin-primary, 0.3);
    &:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba($admin-primary, 0.4); }
}

// ========== LOADING / EMPTY ==========
.loading-state, .empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 60px 20px; background: $admin-card-bg;
    border: 1px solid $admin-border; border-radius: 14px;
    color: $admin-text-muted; text-align: center;
}
.empty-icon { font-size: 3rem; margin-bottom: 12px; }
.empty-state h3 { margin: 0 0 6px; color: $admin-text; }
.empty-state p { margin: 0 0 16px; }
.loader-spinner {
    width: 40px; height: 40px;
    border: 3px solid $admin-border; border-top-color: $admin-primary;
    border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 16px;
}
@keyframes spin { to { transform: rotate(360deg); } }

// ========== ADS LIST ==========
.ads-list { display: flex; flex-direction: column; gap: 10px; }
.ad-card {
    display: flex; align-items: center; gap: 14px;
    background: $admin-card-bg; border: 1px solid $admin-border;
    border-radius: 14px; padding: 12px; transition: all 0.2s;
    &:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    &.is-inactive { opacity: 0.55; }
    &.is-scheduled { border-left: 3px solid #f59e0b; }
}

.ad-card-preview {
    position: relative; flex-shrink: 0;
    width: 110px; height: 80px; border-radius: 10px;
    overflow: hidden; background: #f1f5f9;
}
.ad-preview-image { width: 100%; height: 100%; object-fit: cover; }
.ad-preview-badge {
    position: absolute; top: 6px; left: 6px;
    padding: 2px 8px; background: linear-gradient(135deg, #ec4899, #8b5cf6);
    color: white; border-radius: 10px; font-size: 0.65rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.ad-preview-disabled {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.55); color: white;
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
}

.ad-card-body { flex: 1; min-width: 0; }
.ad-card-top { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap; }
.ad-position {
    padding: 2px 8px; background: rgba($admin-primary, 0.1); color: $admin-primary;
    border-radius: 8px; font-size: 0.7rem; font-weight: 800;
}
.ad-title {
    font-size: 0.95rem; font-weight: 700; color: $admin-text;
    margin: 0; flex: 1; min-width: 0;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.ad-type-tag {
    padding: 2px 8px; background: rgba($admin-success, 0.1); color: $admin-success;
    border-radius: 8px; font-size: 0.65rem; font-weight: 700;
    display: inline-flex; align-items: center; gap: 4px;
}
.ad-short-text {
    font-size: 0.8rem; color: $admin-text-muted;
    margin: 0 0 6px; line-height: 1.4;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.ad-meta { display: flex; gap: 10px; flex-wrap: wrap; }
.meta-item {
    font-size: 0.72rem; color: $admin-text-muted;
    display: inline-flex; align-items: center; gap: 4px;
    &.schedule { color: #d97706; font-weight: 600; }
}

// ========== КНОПКА ТРОЕТОЧИЯ ==========
.ad-card-actions { flex-shrink: 0; }
.more-btn {
    width: 40px; height: 40px; border-radius: 10px;
    background: $admin-bg; border: 1px solid $admin-border;
    color: $admin-text-muted; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    font-size: 1rem;
    &:hover {
        background: $admin-primary;
        color: white;
        border-color: $admin-primary;
        transform: scale(1.05);
    }
    &:active { transform: scale(0.95); }
}

// ==========================================
// BOTTOM SHEET
// ==========================================
.sheet-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 1000;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 20px;

    @media (max-width: 640px) {
        padding: 0;
    }
}

.sheet-container {
    background: $admin-card-bg;
    width: 100%;
    max-width: 480px;
    max-height: 85vh;
    border-radius: 24px 24px 0 0;
    padding: 8px 16px calc(16px + env(safe-area-inset-bottom));
    overflow-y: auto;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;

    @media (max-width: 640px) {
        max-width: 100%;
        border-radius: 24px 24px 0 0;
    }
}

.sheet-handle {
    width: 40px;
    height: 4px;
    background: #d1d5db;
    border-radius: 2px;
    margin: 8px auto 16px;
    flex-shrink: 0;
}

// Превью рекламы в шапке
.sheet-preview {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: $admin-bg;
    border-radius: 14px;
    margin-bottom: 16px;
}

.sheet-preview-image {
    width: 72px;
    height: 72px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
}

.sheet-preview-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.sheet-preview-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0 0 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sheet-preview-subtitle {
    font-size: 0.78rem;
    color: $admin-text-muted;
    margin: 0 0 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sheet-preview-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    color: $admin-text-muted;

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        &.active { background: $admin-success; box-shadow: 0 0 0 3px rgba($admin-success, 0.2); }
        &.inactive { background: #9ca3af; }
    }

    .schedule-hint {
        color: #d97706;
    }
}

// Секции
.sheet-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: $admin-text-muted;
    padding: 8px 12px 6px;
}

.sheet-row-group {
    background: $admin-bg;
    border-radius: 14px;
    padding: 4px;
    margin-bottom: 12px;

    &.danger-zone {
        background: rgba($admin-danger, 0.04);
        border: 1px solid rgba($admin-danger, 0.1);
    }
}

.sheet-row {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $admin-text;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s;

    &:hover:not(:disabled) {
        background: $admin-card-bg;
    }

    &:active:not(:disabled) {
        transform: scale(0.98);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    &.danger {
        color: $admin-danger;
        font-weight: 600;

        .sheet-row-icon {
            background: rgba($admin-danger, 0.1);
            color: $admin-danger;
        }

        &:hover:not(:disabled) {
            background: rgba($admin-danger, 0.08);
        }
    }
}

.sheet-row-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.sheet-row-label {
    flex: 1;
}

// Кнопка отмены
.sheet-cancel {
    width: 100%;
    padding: 14px;
    margin-top: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 14px;
    color: $admin-text;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $admin-border;
    }

    &:active {
        transform: scale(0.98);
    }
}

// Анимация bottom sheet
.bottom-sheet-enter-active {
    transition: opacity 0.3s ease;
    .sheet-container {
        animation: sheetSlideUp 0.35s cubic-bezier(0.32, 0.72, 0, 1);
    }
}

.bottom-sheet-leave-active {
    transition: opacity 0.25s ease;
    .sheet-container {
        animation: sheetSlideDown 0.25s cubic-bezier(0.32, 0.72, 0, 1);
    }
}

.bottom-sheet-enter-from,
.bottom-sheet-leave-to {
    opacity: 0;
}

@keyframes sheetSlideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

@keyframes sheetSlideDown {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(100%);
    }
}

// Адаптив
@media (max-width: 640px) {
    .page-hero { padding: 30px 16px 40px; }
    .hero-title { font-size: 1.5rem; }
    .ad-card { flex-direction: column; align-items: stretch; }
    .ad-card-preview { width: 100%; height: 120px; }
    .ad-card-actions {
        position: absolute;
        top: 12px;
        right: 12px;
    }
    .ad-card { position: relative; }
}
</style>
