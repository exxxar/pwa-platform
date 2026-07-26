<template>
    <div class="tables-manager-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-utensils me-2 text-primary"></i> Управление столиками
                    </h2>
                    <p class="page-subtitle">Текущее состояние зала и активные заказы</p>
                </div>
                <div class="d-flex gap-2">
                    <router-link :to="{ name: 'AdminTableSettings' }" class="btn-modern btn-secondary">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Настройка залов</span>
                    </router-link>
                    <button class="btn-modern btn-primary" @click="refreshTables" :disabled="isTablesLoading">
                        <i class="fa-solid fa-rotate-right" :class="{ 'fa-spin': isTablesLoading }"></i>
                        <span>Обновить</span>
                    </button>
                </div>
            </div>

            <!-- Статистика (Быстрый обзор) -->
            <div class="stats-grid mb-4" v-if="tables.length > 0">
                <div class="stat-card stat-free">
                    <div class="stat-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">{{ availableTablesCount }}</span>
                        <span class="stat-label">Свободно</span>
                    </div>
                </div>
                <div class="stat-card stat-occupied">
                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">{{ occupiedTablesCount }}</span>
                        <span class="stat-label">Занято</span>
                    </div>
                </div>
                <div class="stat-card stat-closed">
                    <div class="stat-icon">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">{{ closedTablesCount }}</span>
                        <span class="stat-label">Закрыто</span>
                    </div>
                </div>
            </div>

            <!-- Состояние загрузки -->
            <div v-if="isTablesLoading && !tables.length" class="loading-state-modern">
                <div class="loader-spinner"></div>
                <p>Загрузка данных о столиках...</p>
            </div>

            <!-- Пустое состояние -->
            <div v-else-if="tables.length === 0" class="empty-state-modern">
                <div class="empty-icon-wrapper">
                    <i class="fa-solid fa-chair"></i>
                </div>
                <h5>Зал пока пуст</h5>
                <p>Настройте столики в разделе "Настройка залов", чтобы они появились здесь.</p>
                <router-link :to="{ name: 'AdminTableSettings' }" class="btn-modern btn-primary mt-3">
                    <i class="fa-solid fa-plus"></i> Настроить залы
                </router-link>
            </div>

            <!-- Сетка столиков -->
            <div v-else class="tables-grid">
                <div
                    v-for="table in tables"
                    :key="table.id"
                    class="admin-table-card"
                    :class="getTableStatusClass(table)"
                    @click="goToTableDetails(table.id)"
                >
                    <!-- Визуализация столика -->
                    <div class="table-visual-wrapper floor-plan-bg">
                        <div class="css-table" :class="`shape-${getTableShape(table)}`">
                            <!-- Стулья (генерируем динамически, как в конструкторе) -->
                            <div v-for="(seat, index) in getTableSeats(table)" :key="index" class="seat" :class="[`type-${seat.type}`, `pos-${seat.pos}`]"></div>

                            <div class="table-top">
                                <span class="table-number">{{ table.number }}</span>
                            </div>
                        </div>

                        <!-- Статусный индикатор -->
                        <div class="status-indicator" :class="getTableStatusClass(table)">
                            <span class="status-dot"></span>
                            <span class="status-text">{{ getTableStatusLabel(table) }}</span>
                        </div>

                        <!-- Бейдж с суммой (если есть) -->
                        <div class="price-badge" v-if="table.summary_price > 0">
                            <i class="fa-solid fa-ruble-sign"></i> {{ table.summary_price }}
                        </div>
                    </div>

                    <!-- Инфо под столиком -->
                    <div class="table-info">
                        <div class="table-name">Столик №{{ table.number }}</div>
                        <div class="table-meta">
                            <span v-if="table.officiant" class="meta-item occupied">
                                <i class="fa-solid fa-user-tie"></i> {{ table.officiant.name || 'Официант' }}
                            </span>
                            <span v-else class="meta-item free">
                                <i class="fa-solid fa-user-slash"></i> Свободен
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useTables } from '@/MobileClient/composables/useTables.js';

export default {
    name: 'TablesManager',

    data() {
        return {
            tablesApi: useTables(),
        };
    },

    computed: {
        tables() {
            return this.tablesApi.tables;
        },
        isTablesLoading() {
            return this.tablesApi.isTablesLoading;
        },
        // 🆕 Считаем статистику для красивых карточек
        availableTablesCount() {
            return this.tables.filter(t => !t.is_occupied && !t.closed_at && t.status !== 'occupied').length;
        },
        occupiedTablesCount() {
            return this.tables.filter(t => t.is_occupied || t.status === 'occupied' || t.officiant_id).length;
        },
        closedTablesCount() {
            return this.tables.filter(t => !!t.closed_at).length;
        }
    },

    methods: {
        async refreshTables() {
            await this.tablesApi.loadTables({ page: 0, size: 50 });
        },

        goToTableDetails(id) {
            this.$router.push({ name: 'AdminTableDetails', params: { tableId: id } });
        },

        // 🆕 Метод для генерации стульев (как в конструкторе)
        getTableSeats(table) {
            const seats = table.seats || 2;
            const hasSofa = table.description?.toLowerCase().includes('диван') || table.type === 'sofa';
            let seatsConfig = [];

            if (seats <= 2) {
                seatsConfig = [{ type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }];
            } else if (seats === 4) {
                seatsConfig = hasSofa
                    ? [{ type: 'sofa', pos: 'top' }, { type: 'chair', pos: 'bottom' }, { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }]
                    : [{ type: 'chair', pos: 'top' }, { type: 'chair', pos: 'bottom' }, { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }];
            } else if (seats >= 5) {
                seatsConfig = hasSofa
                    ? [{ type: 'sofa', pos: 'top' }, { type: 'chair', pos: 'bottom-left' }, { type: 'chair', pos: 'bottom-right' }, { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }]
                    : [{ type: 'chair', pos: 'top' }, { type: 'chair', pos: 'bottom' }, { type: 'chair', pos: 'left' }, { type: 'chair', pos: 'right' }, { type: 'chair', pos: 'tl' }, { type: 'chair', pos: 'br' }].slice(0, seats);
            }
            return seatsConfig;
        },

        getTableShape(table) {
            if (table.description?.toLowerCase().includes('диван')) return 'rect';
            return table.seats <= 2 ? 'round' : (table.seats === 4 ? 'square' : 'round');
        },

        getTableStatusClass(table) {
            if (table.closed_at) return 'status-closed';
            if (table.is_occupied || table.status === 'occupied' || table.officiant_id) return 'status-occupied';
            if (table.booked_at) return 'status-booked';
            return 'status-free';
        },

        getTableStatusLabel(table) {
            if (table.closed_at) return 'Закрыт';
            if (table.is_occupied || table.status === 'occupied' || table.officiant_id) return 'Занят';
            if (table.booked_at) return 'Бронь';
            return 'Свободен';
        }
    },

    mounted() {
        this.refreshTables();
    }
};
</script>

<style scoped>
/* ==========================================
   🎨 MODERN ADMIN VARIABLES & BASE
   ========================================== */
.tables-manager-page {
    background-color: #F8FAFC;
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #334155;
}

/* ==========================================
   HEADER & BUTTONS
   ========================================== */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.page-title { font-size: 1.5rem; font-weight: 700; color: #0F172A; margin: 0; }
.page-subtitle { font-size: 0.9rem; color: #64748B; margin: 4px 0 0 0; }

.btn-modern {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 20px;
    border: 1px solid transparent;
    border-radius: 10px;
    font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s;
    text-decoration: none;
}
.btn-modern.btn-primary {
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(37, 99, 235, 0.3); }
.btn-modern.btn-secondary {
    background: white;
    color: #475569;
    border-color: #E2E8F0;
}
.btn-modern.btn-secondary:hover { background: #F1F5F9; color: #0F172A; border-color: #CBD5E1; }
.btn-modern:disabled { opacity: 0.6; cursor: not-allowed; }

/* ==========================================
   STATS GRID (Быстрый обзор)
   ========================================== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}
.stat-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

.stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: white;
    flex-shrink: 0;
}
.stat-free .stat-icon { background: linear-gradient(135deg, #10B981 0%, #059669 100%); }
.stat-occupied .stat-icon { background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%); }
.stat-closed .stat-icon { background: linear-gradient(135deg, #64748B 0%, #475569 100%); }

.stat-info { display: flex; flex-direction: column; }
.stat-value { font-size: 1.5rem; font-weight: 800; color: #0F172A; line-height: 1; }
.stat-label { font-size: 0.8rem; font-weight: 500; color: #64748B; margin-top: 4px; }

/* ==========================================
   LOADING & EMPTY STATES
   ========================================== */
.loading-state-modern, .empty-state-modern {
    text-align: center;
    padding: 80px 20px;
    color: #94A3B8;
}
.loader-spinner {
    width: 48px; height: 48px;
    border: 3px solid #E2E8F0;
    border-top-color: #3B82F6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 20px;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-icon-wrapper {
    width: 80px; height: 80px;
    background: #F1F5F9; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px; font-size: 2rem; color: #CBD5E1;
}
.empty-state-modern h5 { color: #334155; font-weight: 600; margin-bottom: 8px; }

/* ==========================================
   TABLES GRID
   ========================================== */
.tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
}

.admin-table-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}
.admin-table-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -8px rgba(0,0,0,0.1);
    border-color: #CBD5E1;
}

/* Статусные рамки (тонкая полоска сверху) */
.admin-table-card.status-free { border-top: 3px solid #10B981; }
.admin-table-card.status-occupied { border-top: 3px solid #3B82F6; }
.admin-table-card.status-booked { border-top: 3px solid #F59E0B; }
.admin-table-card.status-closed { border-top: 3px solid #64748B; opacity: 0.7; }

/* ==========================================
   VISUAL WRAPPER (Эффект чертежа)
   ========================================== */
.table-visual-wrapper {
    position: relative;
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.floor-plan-bg {
    background-color: #F8FAFC;
    background-image: radial-gradient(#E2E8F0 1px, transparent 1px);
    background-size: 16px 16px;
}

/* ==========================================
   CSS TABLES (Из конструктора)
   ========================================== */
.css-table { position: relative; width: 110px; height: 110px; display: flex; align-items: center; justify-content: center; z-index: 1; transition: transform 0.3s ease; }
.admin-table-card:hover .css-table { transform: scale(1.05); }

.table-top {
    background: linear-gradient(145deg, #3B82F6 0%, #2563EB 100%);
    box-shadow: inset 0 2px 4px rgba(255,255,255,0.3), 0 8px 16px -4px rgba(37, 99, 235, 0.4);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; border: 2px solid rgba(255,255,255,0.2);
    z-index: 2;
}
.shape-round .table-top { border-radius: 50%; width: 60px; height: 60px; font-size: 1.2rem; }
.shape-square .table-top { border-radius: 12px; width: 65px; height: 65px; font-size: 1.2rem; }
.shape-rect .table-top { border-radius: 14px; width: 85px; height: 55px; font-size: 1.2rem; }

.seat { position: absolute; z-index: 1; }
.type-chair {
    width: 22px; height: 22px;
    background: linear-gradient(145deg, #94A3B8 0%, #64748B 100%);
    border-radius: 6px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2), inset 0 1px 1px rgba(255,255,255,0.3);
}
.type-sofa {
    background: linear-gradient(145deg, #8B5CF6 0%, #7C3AED 100%);
    border-radius: 8px;
    box-shadow: 0 6px 10px -2px rgba(124, 58, 237, 0.3);
}

.pos-top { top: -4px; left: 50%; transform: translateX(-50%); width: 50px; height: 16px; }
.pos-bottom { bottom: -4px; left: 50%; transform: translateX(-50%); width: 22px; height: 22px; }
.pos-bottom-left { bottom: 2px; left: 15%; transform: translateX(-50%); width: 22px; height: 22px; }
.pos-bottom-right { bottom: 2px; right: 15%; transform: translateX(50%); width: 22px; height: 22px; }
.pos-left { left: -4px; top: 50%; transform: translateY(-50%); width: 22px; height: 22px; }
.pos-right { right: -4px; top: 50%; transform: translateY(-50%); width: 22px; height: 22px; }
.pos-tl { top: 6px; left: 6px; width: 18px; height: 18px; }
.pos-br { bottom: 6px; right: 6px; width: 18px; height: 18px; }

/* ==========================================
   STATUS INDICATOR & BADGES
   ========================================== */
.status-indicator {
    position: absolute;
    top: 12px;
    left: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(4px);
    border-radius: 99px;
    font-size: 0.75rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    z-index: 3;
}
.status-dot {
    width: 8px; height: 8px; border-radius: 50%;
}
.status-free .status-dot { background: #10B981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }
.status-free .status-text { color: #059669; }

.status-occupied .status-dot { background: #3B82F6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
.status-occupied .status-text { color: #2563EB; }

.status-booked .status-dot { background: #F59E0B; box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2); }
.status-booked .status-text { color: #D97706; }

.status-closed .status-dot { background: #64748B; box-shadow: 0 0 0 2px rgba(100, 116, 139, 0.2); }
.status-closed .status-text { color: #475569; }

.price-badge {
    position: absolute;
    bottom: 12px;
    right: 12px;
    padding: 4px 10px;
    background: linear-gradient(135deg, #0F172A 0%, #334155 100%);
    color: white;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
    z-index: 3;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ==========================================
   TABLE INFO (Под визуализацией)
   ========================================== */
.table-info {
    padding: 16px;
    border-top: 1px solid #F1F5F9;
}
.table-name {
    font-size: 1rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 8px;
}
.table-meta { display: flex; flex-direction: column; gap: 6px; }
.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 500;
}
.meta-item.free { color: #10B981; }
.meta-item.occupied { color: #3B82F6; }

/* ==========================================
   RESPONSIVE
   ========================================== */
@media (max-width: 768px) {
    .modern-header { flex-direction: column; align-items: stretch; }
    .modern-header > div:last-child { width: 100%; display: flex; gap: 8px; }
    .btn-modern { flex: 1; justify-content: center; }
    .tables-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
    .table-visual-wrapper { height: 140px; }
    .css-table { width: 90px; height: 90px; }
    .shape-round .table-top { width: 50px; height: 50px; }
    .type-chair { width: 18px; height: 18px; }
}
</style>
