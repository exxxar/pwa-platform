<template>
    <div class="traffic-statistic">

        <!-- Настройки -->
        <div class="traffic-settings">
            <div class="setting-row">
                <label class="toggle-switch">
                    <input type="checkbox" v-model="sort.is_individual">
                    <span class="switch-slider"></span>
                    <span class="switch-label">Индивидуальные переходы</span>
                </label>

                <label class="toggle-switch">
                    <input type="checkbox" v-model="need_chart">
                    <span class="switch-slider"></span>
                    <span class="switch-label">Диаграмма</span>
                </label>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="isTrafficLoading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Загружаем данные о переходах...</p>
        </div>

        <!-- Данные есть -->
        <template v-else-if="traffics && traffics.length > 0">

            <!-- Статистика -->
            <div class="traffic-summary">
                <div class="summary-card">
                    <i class="fa-solid fa-chart-simple"></i>
                    <div class="summary-info">
                        <span class="summary-value">{{ totalTraffic }}</span>
                        <span class="summary-label">Всего переходов</span>
                    </div>
                </div>
                <div class="summary-card">
                    <i class="fa-solid fa-link"></i>
                    <div class="summary-info">
                        <span class="summary-value">{{ traffics.length }}</span>
                        <span class="summary-label">Источников</span>
                    </div>
                </div>
            </div>

            <!-- Таблица -->
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th
                            class="sortable"
                            @click="changeSort('id')"
                        >
                            #
                            <i v-if="sort.key === 'id'"
                               :class="sort.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                        </th>
                        <th
                            class="sortable"
                            @click="changeSort('source')"
                        >
                            Источник
                            <i v-if="sort.key === 'source'"
                               :class="sort.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                        </th>
                        <th
                            class="sortable"
                            @click="changeSort('count')"
                        >
                            Переходов
                            <i v-if="sort.key === 'count'"
                               :class="sort.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                        </th>
                        <th>Доля</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(source, index) in sortedTraffics" :key="source.id || index">
                        <td>{{ index + 1 }}</td>
                        <td class="source-cell">
                            <div class="source-badge">
                                <i class="fa-solid fa-link"></i>
                                <span>{{ source.source }}</span>
                            </div>
                        </td>
                        <td class="count-cell">
                            <strong>{{ formatNumber(source.count) }}</strong>
                        </td>
                        <td class="percent-cell">
                            <div class="percent-bar">
                                <div
                                    class="percent-fill"
                                    :style="{ width: getPercent(source.count) + '%' }"
                                ></div>
                                <span class="percent-text">{{ getPercent(source.count) }}%</span>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Диаграмма -->
            <div v-if="need_chart" class="chart-section">
                <h4 class="chart-title">
                    <i class="fa-solid fa-chart-pie"></i>
                    Распределение источников
                </h4>
                <div class="chart-wrapper">
                    <Chart
                        direction="circular"
                        :size="{ width: 400, height: 400 }"
                        :data="traffics"
                        :margin="margin"
                        :axis="axis"
                        :config="{ controlHover: false }"
                    >
                        <template #layers>
                            <Pie
                                :dataKeys="['source', 'count']"
                                :pie-style="{ innerRadius: 60, padAngle: 0.02 }"
                            />
                        </template>
                        <template #widgets>
                            <Tooltip
                                :config="tooltipConfig"
                                hideLine
                            />
                        </template>
                    </Chart>
                </div>
            </div>

        </template>

        <!-- Пустое состояние -->
        <div v-else class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <h4>Нет данных о переходах</h4>
            <p>Переходы появятся после первых переходов по ссылкам</p>
        </div>

    </div>
</template>

<script>
import { Chart, Tooltip, Pie } from 'vue3-charts';
import { useStatistic } from '@/MobileClient/Composables/useStatistic.js';

export default {
    name: 'TrafficStatistic',

    components: {
        Chart,
        Tooltip,
        Pie,
    },

    props: {
        date: {
            type: [Array, Object],
            default: null,
        },
    },

    setup() {
        const statistic = useStatistic();
        return { ...statistic };
    },

    data() {
        return {
            need_chart: false,
            sort: {
                is_individual: false,
                key: 'count',
                direction: 'desc',
            },
            tooltipConfig: {
                source: { label: 'Источник', color: '#667eea' },
                count: { label: 'Переходов', color: '#667eea' },
            },
            margin: {
                left: 0,
                top: 20,
                right: 0,
                bottom: 0,
            },
            axis: {
                primary: { type: 'band' },
                secondary: {
                    domain: ['dataMin', 'dataMax + 100'],
                    type: 'linear',
                    ticks: 11,
                },
            },
        };
    },

    computed: {
        sortedTraffics() {
            if (!this.traffics || this.traffics.length === 0) return [];

            return [...this.traffics].sort((a, b) => {
                const aVal = a[this.sort.key] || 0;
                const bVal = b[this.sort.key] || 0;

                if (typeof aVal === 'string') {
                    return this.sort.direction === 'asc'
                        ? aVal.localeCompare(bVal)
                        : bVal.localeCompare(aVal);
                }

                return this.sort.direction === 'asc' ? aVal - bVal : bVal - aVal;
            });
        },
    },

    watch: {
        date: {
            immediate: true,
            handler() {
                this.loadTrafficData();
            },
        },
        'sort.is_individual'() {
            this.loadTrafficData();
        },
    },

    methods: {
        async loadTrafficData() {
            try {
                const params = {
                    is_individual: this.sort.is_individual,
                };

                if (this.date && Array.isArray(this.date) && this.date.length === 2) {
                    params.date_from = this.date[0];
                    params.date_to = this.date[1];
                }

                await this.loadTraffic(params);
            } catch (error) {
                console.error('[Traffic] Ошибка:', error);
            }
        },

        changeSort(param) {
            if (this.sort.key === param) {
                this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.sort.key = param;
                this.sort.direction = param === 'source' ? 'asc' : 'desc';
            }
        },

        getPercent(count) {
            if (!this.totalTraffic || this.totalTraffic === 0) return 0;
            return Math.round((count / this.totalTraffic) * 100);
        },

        formatNumber(value) {
            return new Intl.NumberFormat('ru-RU').format(value || 0);
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.traffic-statistic {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

// ==========================================
// НАСТРОЙКИ
// ==========================================
.traffic-settings {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 14px 16px;
}

.setting-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
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
                transform: translateX(20px);
            }
        }
    }
}

.switch-slider {
    position: relative;
    width: 44px;
    height: 26px;
    background: $border;
    border-radius: 26px;
    transition: 0.3s;

    &::before {
        position: absolute;
        content: '';
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
}

.switch-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
}

// ==========================================
// ЗАГРУЗКА
// ==========================================
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
    color: $text-muted;

    .loading-spinner {
        width: 36px;
        height: 36px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 12px;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// СТАТИСТИКА
// ==========================================
.traffic-summary {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;

    > i {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, $primary, $primary-dark);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
}

.summary-info {
    flex: 1;
    min-width: 0;
}

.summary-value {
    display: block;
    font-size: 1.3rem;
    font-weight: 800;
    color: $text;
    line-height: 1;
    margin-bottom: 2px;
}

.summary-label {
    font-size: 0.75rem;
    color: $text-muted;
}

// ==========================================
// ТАБЛИЦА
// ==========================================
.table-wrapper {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    overflow: hidden;
}

.data-table {
    width: 100%;
    border-collapse: collapse;

    thead {
        background: $bg-secondary;

        th {
            padding: 12px 14px;
            text-align: left;
            font-size: 0.8rem;
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
                    font-size: 0.7rem;
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
                padding: 12px 14px;
                border-bottom: 1px solid $border;
                font-size: 0.9rem;
                color: $text;

                &:last-child {
                    border-bottom: none;
                }
            }
        }
    }
}

.source-cell {
    .source-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        background: rgba($primary, 0.08);
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        font-weight: 600;
        color: $primary;
        max-width: 100%;

        i {
            font-size: 0.75rem;
        }

        span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }
}

.count-cell {
    strong {
        font-weight: 700;
        color: $text;
    }
}

.percent-cell {
    min-width: 120px;
}

.percent-bar {
    position: relative;
    height: 24px;
    background: $bg-secondary;
    border-radius: 6px;
    overflow: hidden;
}

.percent-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: linear-gradient(90deg, $primary, $primary-dark);
    border-radius: 6px;
    transition: width 0.3s ease;
}

.percent-text {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-size: 0.75rem;
    font-weight: 700;
    color: $text;
}

// ==========================================
// ДИАГРАММА
// ==========================================
.chart-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 20px;
}

.chart-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    margin: 0 0 16px;

    i {
        color: $primary;
    }
}

.chart-wrapper {
    display: flex;
    justify-content: center;
    padding: 10px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 50px 20px;
    background: $bg;
    border: 2px dashed $border;
    border-radius: 14px;

    .empty-icon {
        width: 70px;
        height: 70px;
        margin: 0 auto 14px;
        background: rgba($primary, 0.1);
        color: $primary;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }

    h4 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0 0 6px;
        color: $text;
    }

    p {
        font-size: 0.85rem;
        color: $text-muted;
        margin: 0;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 640px) {
    .traffic-summary {
        grid-template-columns: 1fr;
    }

    .setting-row {
        flex-direction: column;
        gap: 10px;
    }

    .percent-cell {
        min-width: 90px;
    }

    .source-badge span {
        max-width: 150px;
    }
}
</style>
