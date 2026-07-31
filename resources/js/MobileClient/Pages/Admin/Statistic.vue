<template>
    <div class="statistic-page">

        <!-- ========================================== -->
        <!-- HERO -->
        <!-- ========================================== -->
        <div class="statistic-hero">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h2 class="hero-title">Статистика</h2>
                <p class="hero-subtitle">
                    Сводка всех показателей эффективности работы системы
                </p>
            </div>
        </div>

        <div class="container px-3">

            <!-- ========================================== -->
            <!-- ФИЛЬТРЫ -->
            <!-- ========================================== -->
            <div class="filters-card">
                <div class="filter-row">
                    <label class="toggle-switch">
                        <input
                            type="checkbox"
                            v-model="need_date_range"
                        >
                        <span class="switch-slider"></span>
                        <span class="switch-label">Использовать период</span>
                    </label>

                    <transition name="fade">
                        <div v-if="need_date_range" class="date-picker-wrapper">
                            <VueDatePicker
                                v-model="date"
                                locale="ru"
                                range
                                :enable-time-picker="false"
                                placeholder="Выберите период"
                            />
                        </div>
                    </transition>

                    <button class="refresh-btn" @click="prepareStatistic" :disabled="loading">
                        <i class="fa-solid fa-rotate-right" :class="{ 'fa-spin': loading }"></i>
                        <span>Обновить</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ЗАГРУЗКА -->
            <!-- ========================================== -->
            <div v-if="loading && !statistic" class="skeleton-grid">
                <div v-for="i in 6" :key="i" class="skeleton-card">
                    <div class="skeleton-icon shimmer"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-line w-60 shimmer"></div>
                        <div class="skeleton-line w-40 shimmer"></div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- КЛЮЧЕВЫЕ МЕТРИКИ -->
            <!-- ========================================== -->
            <div v-else-if="statistic" class="metrics-grid">

                <!-- Пользователи -->
                <div class="metric-card users">
                    <div class="metric-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatNumber(statistic.users_in_bd) }}</div>
                        <div class="metric-label">Всего пользователей</div>
                    </div>
                </div>

                <!-- VIP -->
                <div class="metric-card vip">
                    <div class="metric-icon">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatNumber(statistic.vip_in_bd) }}</div>
                        <div class="metric-label">VIP клиентов</div>
                    </div>
                </div>

                <!-- Админы -->
                <div class="metric-card admins">
                    <div class="metric-icon">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatNumber(statistic.admin_in_bd) }}</div>
                        <div class="metric-label">Администраторов</div>
                    </div>
                </div>

                <!-- Админы за работой -->
                <div class="metric-card working">
                    <div class="metric-icon">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatNumber(statistic.work_admin_in_bd) }}</div>
                        <div class="metric-label">За работой</div>
                    </div>
                </div>

                <!-- Кэшбэк на счетах -->
                <div class="metric-card cashback-balance">
                    <div class="metric-icon">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatPrice(statistic.summary_cashback) }}</div>
                        <div class="metric-label">
                            Кэшбэк на счетах
                            <span v-if="statistic.summary_cashback_people_count" class="people-count">
                                ({{ formatNumber(statistic.summary_cashback_people_count) }} чел)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Начислено кэшбэка -->
                <div class="metric-card cashback-up">
                    <div class="metric-icon">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatPrice(statistic.cashback_summary_up) }}</div>
                        <div class="metric-label">
                            Начислено кэшбэка
                            <span v-if="statistic.cashback_summary_up_people_count" class="people-count">
                                ({{ formatNumber(statistic.cashback_summary_up_people_count) }} чел)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Списано кэшбэка -->
                <div class="metric-card cashback-down">
                    <div class="metric-icon">
                        <i class="fa-solid fa-arrow-trend-down"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatPrice(statistic.cashback_summary_down) }}</div>
                        <div class="metric-label">
                            Списано кэшбэка
                            <span v-if="statistic.cashback_summary_down_people_count" class="people-count">
                                ({{ formatNumber(statistic.cashback_summary_down_people_count) }} чел)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Реферальные уровни -->
                <div class="metric-card referral-1">
                    <div class="metric-icon">
                        <i class="fa-solid fa-1"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatPrice(statistic.cashback_up_level_1) }}</div>
                        <div class="metric-label">
                            1-й уровень
                            <span v-if="statistic.cashback_up_level_1_people_count" class="people-count">
                                ({{ formatNumber(statistic.cashback_up_level_1_people_count) }} чел)
                            </span>
                        </div>
                    </div>
                </div>

                <div class="metric-card referral-2">
                    <div class="metric-icon">
                        <i class="fa-solid fa-2"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatPrice(statistic.cashback_up_level_2) }}</div>
                        <div class="metric-label">
                            2-й уровень
                            <span v-if="statistic.cashback_up_level_2_people_count" class="people-count">
                                ({{ formatNumber(statistic.cashback_up_level_2_people_count) }} чел)
                            </span>
                        </div>
                    </div>
                </div>

                <div class="metric-card referral-3">
                    <div class="metric-icon">
                        <i class="fa-solid fa-3"></i>
                    </div>
                    <div class="metric-info">
                        <div class="metric-value">{{ formatPrice(statistic.cashback_up_level_3) }}</div>
                        <div class="metric-label">
                            3-й уровень
                            <span v-if="statistic.cashback_up_level_3_people_count" class="people-count">
                                ({{ formatNumber(statistic.cashback_up_level_3_people_count) }} чел)
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- ОШИБКА -->
            <!-- ========================================== -->
            <div v-else-if="error" class="error-state">
                <div class="error-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h4>Не удалось загрузить статистику</h4>
                <p>{{ error }}</p>
                <button class="retry-btn" @click="prepareStatistic">
                    <i class="fa-solid fa-rotate-right"></i>
                    Попробовать снова
                </button>
            </div>

            <!-- ========================================== -->
            <!-- ПУСТОЕ СОСТОЯНИЕ -->
            <!-- ========================================== -->
            <div v-else class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h4>Нет данных</h4>
                <p>Статистика появится после первых действий в системе</p>
            </div>

            <!-- ========================================== -->
            <!-- ГРАФИКИ И ТАБЛИЦЫ -->
            <!-- ========================================== -->
            <div v-if="statistic" class="charts-section">

                <!-- Переключатель графиков -->
                <div class="charts-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" v-model="need_charts">
                        <span class="switch-slider"></span>
                        <span class="switch-label">Отобразить графики</span>
                    </label>
                </div>

                <!-- Табы -->
                <div class="tabs-wrapper">
                    <button
                        v-for="(tab, index) in tabs"
                        :key="index"
                        class="tab-btn"
                        :class="{ 'active': activeTab === index }"
                        @click="activeTab = index"
                    >
                        <i :class="tab.icon"></i>
                        <span>{{ tab.label }}</span>
                    </button>
                </div>

                <!-- Контент табов -->
                <div class="tab-content">

                    <!-- ===== ТАБ: ПОЛЬЗОВАТЕЛИ ===== -->
                    <div v-if="activeTab === 0" class="tab-panel">
                        <div v-if="need_charts && users.length > 0" class="chart-wrapper">
                            <Chart
                                :size="{ width: '100%', height: 320 }"
                                :data="users"
                                :margin="chartMargin"
                                direction="horizontal"
                                :axis="chartAxis"
                            >
                                <template #layers>
                                    <Grid strokeDasharray="2,2" />
                                    <Bar :dataKeys="['m', 'count']" :barStyle="{ fill: '#667eea' }" />
                                </template>
                                <template #widgets>
                                    <Tooltip :config="usersTooltipConfig" />
                                </template>
                            </Chart>
                        </div>

                        <div v-if="users.length > 0" class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Год</th>
                                    <th>Месяц</th>
                                    <th>Пользователей</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in users" :key="index">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ item.y }}</td>
                                    <td>{{ getMonthName(item.m) }}</td>
                                    <td class="text-bold">{{ formatNumber(item.count) }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="no-data">
                            <i class="fa-solid fa-chart-line"></i>
                            <p>Нет данных по пользователям</p>
                        </div>
                    </div>

                    <!-- ===== ТАБ: БОНУСЫ ===== -->
                    <div v-if="activeTab === 1" class="tab-panel">
                        <div v-if="need_charts" class="charts-grid">
                            <div class="chart-block">
                                <h5 class="chart-title">
                                    <i class="fa-solid fa-arrow-trend-up"></i>
                                    Начисления
                                </h5>
                                <div v-if="cashback_up.length > 0" class="chart-wrapper">
                                    <Chart
                                        :size="{ width: '100%', height: 280 }"
                                        :data="cashback_up"
                                        :margin="chartMargin"
                                        direction="horizontal"
                                        :axis="chartAxis"
                                    >
                                        <template #layers>
                                            <Grid strokeDasharray="2,2" />
                                            <Bar :dataKeys="['m', 'sum']" :barStyle="{ fill: '#10b981' }" />
                                        </template>
                                        <template #widgets>
                                            <Tooltip :config="cashbackTooltipConfig" />
                                        </template>
                                    </Chart>
                                </div>
                                <div v-else class="no-data small">
                                    <p>Нет данных</p>
                                </div>
                            </div>

                            <div class="chart-block">
                                <h5 class="chart-title">
                                    <i class="fa-solid fa-arrow-trend-down"></i>
                                    Списания
                                </h5>
                                <div v-if="cashback_down.length > 0" class="chart-wrapper">
                                    <Chart
                                        :size="{ width: '100%', height: 280 }"
                                        :data="cashback_down"
                                        :margin="chartMargin"
                                        direction="horizontal"
                                        :axis="chartAxis"
                                    >
                                        <template #layers>
                                            <Grid strokeDasharray="2,2" />
                                            <Bar :dataKeys="['m', 'sum']" :barStyle="{ fill: '#ef4444' }" />
                                        </template>
                                        <template #widgets>
                                            <Tooltip :config="cashbackTooltipConfig" />
                                        </template>
                                    </Chart>
                                </div>
                                <div v-else class="no-data small">
                                    <p>Нет данных</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="preparedCashback.length > 0" class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Год</th>
                                    <th>Месяц</th>
                                    <th>Начислено</th>
                                    <th>Списано</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in preparedCashback" :key="index">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ item.y }}</td>
                                    <td>{{ getMonthName(item.m) }}</td>
                                    <td class="text-success">{{ formatPrice(item.up) }}</td>
                                    <td class="text-danger">{{ formatPrice(item.down) }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== ТАБ: ПРОДАЖИ ===== -->
                    <div v-if="activeTab === 2" class="tab-panel">
                        <div v-if="need_charts && orders.length > 0" class="chart-wrapper">
                            <Chart
                                :size="{ width: '100%', height: 320 }"
                                :data="orders"
                                :margin="chartMargin"
                                direction="horizontal"
                                :axis="chartAxis"
                            >
                                <template #layers>
                                    <Grid strokeDasharray="2,2" />
                                    <Bar :dataKeys="['m', 'sump']" :barStyle="{ fill: '#f59e0b' }" />
                                </template>
                                <template #widgets>
                                    <Tooltip :config="ordersTooltipConfig" />
                                </template>
                            </Chart>
                        </div>

                        <div v-if="products.length > 0" class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th @click="changeSort('title')" class="sortable">
                                        Название
                                        <i v-if="sort.key === 'title'"
                                           :class="sort.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                    </th>
                                    <th @click="changeSort('price')" class="sortable">
                                        Выручка
                                        <i v-if="sort.key === 'price'"
                                           :class="sort.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                    </th>
                                    <th @click="changeSort('count')" class="sortable">
                                        Продано
                                        <i v-if="sort.key === 'count'"
                                           :class="sort.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                    </th>
                                    <th>% от объёма</th>
                                    <th>% от числа</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in sortedProducts" :key="index">
                                    <td>{{ index + 1 }}</td>
                                    <td class="text-bold">{{ item.title }}</td>
                                    <td class="text-success">{{ formatPrice(item.price) }}</td>
                                    <td>{{ formatNumber(item.count) }}</td>
                                    <td>{{ item.volume_count_ratio }}%</td>
                                    <td>{{ item.volume_price_ratio }}%</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="no-data">
                            <i class="fa-solid fa-box-open"></i>
                            <p>Нет данных по продажам за выбранный период</p>
                        </div>
                    </div>

                    <!-- ===== ТАБ: ПЕРЕХОДЫ ===== -->
                    <div v-if="activeTab === 3" class="tab-panel">
                        <TrafficStatistic :date="date" />
                    </div>

                </div>
            </div>

            <!-- ========================================== -->
            <!-- ЭКСПОРТ -->
            <!-- ========================================== -->
            <div v-if="statistic" class="export-section">
                <button class="export-btn" @click="downloadBotStatistic" :disabled="exporting">
                    <span v-if="exporting" class="btn-spinner"></span>
                    <i v-else class="fa-solid fa-file-excel"></i>
                    <span>{{ exporting ? 'Формирование...' : 'Скачать статистику' }}</span>
                </button>
            </div>

        </div>
    </div>
</template>

<script>
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { Chart, Grid, Bar, Tooltip, Pie } from 'vue3-charts';
import TrafficStatistic from "@/MobileClient/Components/Admin/Statistic/TrafficStatistic.vue";
import { useStatistic } from '@/MobileClient/Composables/useStatistic.js';

export default {
    name: 'StatisticPage',

    components: {
        VueDatePicker,
        Chart,
        Grid,
        Bar,
        Tooltip,
        Pie,
        TrafficStatistic,
    },

    setup() {
        const statistic = useStatistic();
        return { ...statistic };
    },

    data() {
        return {
            activeTab: 0,
            need_charts: false,
            need_date_range: false,
            date: null,

            sort: {
                key: 'price',
                direction: 'desc',
            },

            tabs: [
                { label: 'Пользователи', icon: 'fa-solid fa-users' },
                { label: 'Бонусы', icon: 'fa-solid fa-coins' },
                { label: 'Продажи', icon: 'fa-solid fa-cart-shopping' },
                { label: 'Переходы', icon: 'fa-solid fa-arrow-up-right-from-square' },
            ],

            // Конфигурация графиков
            chartMargin: {
                left: 0,
                top: 20,
                right: 20,
                bottom: 0,
            },
            chartAxis: {
                primary: { type: 'band' },
                secondary: {
                    domain: ['dataMin', 'dataMax + 100'],
                    type: 'linear',
                    ticks: 11,
                },
            },

            // Конфигурации tooltip
            usersTooltipConfig: {
                count: { label: 'Пользователей', color: '#667eea' },
                m: { label: 'Месяц', color: '#6b7280' },
            },
            cashbackTooltipConfig: {
                sum: { label: 'Сумма', color: '#10b981' },
                m: { label: 'Месяц', color: '#6b7280' },
            },
            ordersTooltipConfig: {
                sump: { label: 'Сумма продаж', color: '#f59e0b' },
                m: { label: 'Месяц', color: '#6b7280' },
            },
        };
    },

    computed: {
        sortedProducts() {
            const sorted = [...this.products].sort((a, b) => {
                const aVal = a[this.sort.key] || 0;
                const bVal = b[this.sort.key] || 0;

                if (typeof aVal === 'string') {
                    return this.sort.direction === 'asc'
                        ? aVal.localeCompare(bVal)
                        : bVal.localeCompare(aVal);
                }

                return this.sort.direction === 'asc' ? aVal - bVal : bVal - aVal;
            });
            return sorted;
        },
    },

    watch: {
        need_date_range() {
            this.prepareStatistic();
        },
        date() {
            this.prepareStatistic();
        },
    },

    mounted() {
        const startDate = new Date();
        const endDate = new Date();
        endDate.setDate(startDate.getDate() + 7);
        this.date = [startDate, endDate];

        this.prepareStatistic();
    },

    methods: {
        async prepareStatistic() {
            try {
                const params = {
                    need_all: !this.need_date_range,
                    sort_key: this.sort.key,
                    sort_direction: this.sort.direction,
                };

                if (this.need_date_range && this.date && this.date.length === 2) {
                    params.date_from = this.date[0];
                    params.date_to = this.date[1];
                }

                await this.loadStatistic(params);
            } catch (error) {
                console.error('[Statistic] Ошибка:', error);
            }
        },

        changeSort(param) {
            if (this.sort.key === param) {
                this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.sort.key = param;
                this.sort.direction = 'desc';
            }
        },

        async downloadBotStatistic() {
            this.$notify?.({
                title: 'Внимание',
                text: 'Началось формирование документа статистики',
                type: 'info',
            });

            try {
                await this.exportStatistic({
                    date_from: this.date?.[0],
                    date_to: this.date?.[1],
                });

                this.$notify?.({
                    title: 'Отлично',
                    text: 'Документ успешно сформирован',
                    type: 'success',
                });
            } catch (error) {
                console.error('[Statistic] Ошибка экспорта:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сформировать документ',
                    type: 'error',
                });
            }
        },

        formatNumber(value) {
            return new Intl.NumberFormat('ru-RU').format(value || 0);
        },

        formatPrice(value) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value || 0);
        },

        getMonthName(month) {
            const months = [
                'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь',
            ];
            return months[month - 1] || month;
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
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary:   #f8f9fa;
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.statistic-page {
    min-height: 100vh;
    background: $bg-secondary;
    padding-bottom: 40px;
}

// ==========================================
// HERO
// ==========================================
.statistic-hero {
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
// ФИЛЬТРЫ
// ==========================================
.filters-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 16px;
    margin: -30px 0 20px;
    position: relative;
    z-index: 2;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.filter-row {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.toggle-switch {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;

    input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;

        &:checked + .switch-slider {
            background: $primary;

            &::before {
                transform: translateX(22px);
            }
        }
    }
}

.switch-slider {
    position: relative;
    width: 52px;
    height: 30px;
    background: $border;
    border-radius: 30px;
    transition: 0.3s;

    &::before {
        position: absolute;
        content: '';
        height: 24px;
        width: 24px;
        left: 3px;
        bottom: 3px;
        background: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
}

.switch-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: $text;
}

.date-picker-wrapper {
    flex: 1;
    min-width: 200px;
}

.refresh-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $primary-dark;
        transform: translateY(-2px);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

// ==========================================
// SKELETON
// ==========================================
.skeleton-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.skeleton-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
}

.skeleton-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: $bg-secondary;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-line {
    height: 14px;
    border-radius: 7px;
    background: $bg-secondary;

    &.w-40 { width: 40%; }
    &.w-60 { width: 60%; }
}

.shimmer {
    background: linear-gradient(
            90deg,
            $bg-secondary 0%,
            color.adjust($bg-secondary, $lightness: -3%)
             50%,
            $bg-secondary 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// МЕТРИКИ
// ==========================================
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.metric-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    transition: all 0.2s;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .metric-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: white;
        flex-shrink: 0;
    }

    .metric-info {
        flex: 1;
        min-width: 0;
    }

    .metric-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: $text;
        line-height: 1.1;
        margin-bottom: 4px;
    }

    .metric-label {
        font-size: 0.8rem;
        color: $text-muted;
    }

    .people-count {
        display: block;
        font-size: 0.7rem;
        opacity: 0.8;
    }

    // Цветовые акценты
    &.users .metric-icon { background: linear-gradient(135deg, $primary, $primary-dark); }
    &.vip .metric-icon { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    &.admins .metric-icon { background: linear-gradient(135deg, $purple, #7c3aed); }
    &.working .metric-icon { background: linear-gradient(135deg, $success, #059669); }
    &.cashback-balance .metric-icon { background: linear-gradient(135deg, $cyan, #0891b2); }
    &.cashback-up .metric-icon { background: linear-gradient(135deg, $success, #059669); }
    &.cashback-down .metric-icon { background: linear-gradient(135deg, $danger, #dc2626); }
    &.referral-1 .metric-icon { background: linear-gradient(135deg, $success, #059669); }
    &.referral-2 .metric-icon { background: linear-gradient(135deg, $primary, $primary-dark); }
    &.referral-3 .metric-icon { background: linear-gradient(135deg, $purple, #7c3aed); }
}

// ==========================================
// СОСТОЯНИЯ
// ==========================================
.error-state,
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;

    .error-icon,
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

    .error-icon {
        background: rgba($danger, 0.1);
        color: $danger;
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

.retry-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $primary-dark;
        transform: translateY(-2px);
    }
}

// ==========================================
// ГРАФИКИ
// ==========================================
.charts-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
}

.charts-toggle {
    margin-bottom: 16px;
}

.tabs-wrapper {
    display: flex;
    gap: 4px;
    background: $bg-secondary;
    padding: 4px;
    border-radius: 14px;
    margin-bottom: 20px;
    overflow-x: auto;

    &::-webkit-scrollbar {
        display: none;
    }
}

.tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $text-muted;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    &:hover:not(.active) {
        color: $text;
        background: rgba($primary, 0.05);
    }

    &.active {
        background: $primary;
        color: white;
        box-shadow: 0 2px 8px rgba($primary, 0.3);
    }
}

.chart-wrapper {
    margin-bottom: 20px;
    padding: 16px;
    background: $bg-secondary;
    border-radius: 12px;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.chart-block {
    .chart-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 12px;
        color: $text;

        i {
            color: $primary;
        }
    }
}

// ==========================================
// ТАБЛИЦЫ
// ==========================================
.data-table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid $border;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;

    thead {
        background: $bg-secondary;

        th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 700;
            color: $text;
            border-bottom: 2px solid $border;

            &.sortable {
                cursor: pointer;
                user-select: none;
                transition: color 0.2s;

                &:hover {
                    color: $primary;
                }

                i {
                    margin-left: 4px;
                    font-size: 0.75rem;
                }
            }
        }
    }

    tbody {
        tr {
            transition: background 0.2s;

            &:hover {
                background: rgba($primary, 0.03);
            }

            td {
                padding: 12px 16px;
                border-bottom: 1px solid $border;
                color: $text;
            }
        }
    }
}

.text-bold {
    font-weight: 700;
}

.text-success {
    color: $success;
    font-weight: 600;
}

.text-danger {
    color: $danger;
    font-weight: 600;
}

.no-data {
    text-align: center;
    padding: 40px 20px;
    color: $text-muted;

    &.small {
        padding: 20px;
    }

    i {
        font-size: 2rem;
        opacity: 0.3;
        margin-bottom: 12px;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
    }
}

// ==========================================
// ЭКСПОРТ
// ==========================================
.export-section {
    margin-top: 20px;
}

.export-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    background: linear-gradient(135deg, $success 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba($success, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($success, 0.4);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.btn-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .statistic-hero {
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

    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }

    .metrics-grid {
        grid-template-columns: 1fr;
    }

    .tab-btn span {
        display: none;
    }

    .charts-grid {
        grid-template-columns: 1fr;
    }
}
</style>
