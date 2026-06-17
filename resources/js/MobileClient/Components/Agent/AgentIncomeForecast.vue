<template>
    <div class="tab-content fade-in">

        <!-- ========================================== -->
        <!-- ШАПКА С ПЕРИОДОМ -->
        <!-- ========================================== -->
        <div class="section-header-page">
            <div>
                <h2>Прогноз дохода</h2>
                <p>Анализ ваших заработков и прогноз на основе текущего ритма</p>
            </div>
            <div class="period-selector">
                <button
                    v-for="period in periods"
                    :key="period.id"
                    class="period-btn"
                    :class="{ 'is-active': selectedPeriod === period.id }"
                    @click="selectedPeriod = period.id"
                >
                    {{ period.label }}
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КЛЮЧЕВЫЕ МЕТРИКИ -->
        <!-- ========================================== -->
        <div class="forecast-metrics">
            <div class="metric-card total">
                <div class="metric-icon">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Прогноз на период</div>
                    <div class="metric-value">{{ formatPrice(totalForecast) }}</div>
                    <div class="metric-trend" :class="trendClass">
                        <i :class="trendIcon"></i>
                        <span>{{ trendPercent }}% к прошлому периоду</span>
                    </div>
                </div>
            </div>

            <div class="metric-card new-sales">
                <div class="metric-icon">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Новые продажи</div>
                    <div class="metric-value">{{ formatPrice(newSalesForecast) }}</div>
                    <div class="metric-hint">{{ avgNewPerMonth }} ₽/мес в среднем</div>
                </div>
            </div>

            <div class="metric-card renewals">
                <div class="metric-icon">
                    <i class="fa-solid fa-rotate"></i>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Продления подписок</div>
                    <div class="metric-value">{{ formatPrice(renewalsForecast) }}</div>
                    <div class="metric-hint">{{ renewalRate }}% клиентов продлевают</div>
                </div>
            </div>

            <div class="metric-card monthly">
                <div class="metric-icon">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Средний доход / мес</div>
                    <div class="metric-value">{{ formatPrice(avgMonthlyIncome) }}</div>
                    <div class="metric-hint">На основе истории</div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ГРАФИК -->
        <!-- ========================================== -->
        <div class="chart-section">
            <div class="chart-header">
                <h3>Динамика дохода по месяцам</h3>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color new"></span>
                        <span>Новые продажи</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color renewal"></span>
                        <span>Продления</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color trend"></span>
                        <span>Общий тренд</span>
                    </div>
                </div>
            </div>

            <!-- НОВАЯ ОБЕРТКА ДЛЯ СКРОЛЛА -->
            <div class="chart-scroll-wrapper">
                <!-- ВНУТРЕННИЙ КОНТЕЙНЕР С МИНИМАЛЬНОЙ ШИРИНОЙ -->
                <div class="chart-inner-content">

                    <!-- SVG для линии тренда -->
                    <svg class="trend-line" :viewBox="`0 0 ${chartWidth} ${chartHeight}`" preserveAspectRatio="none">
                        <path :d="trendLinePath" fill="none" stroke="url(#trendGradient)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="trendGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#8b5cf6"/>
                                <stop offset="100%" stop-color="#ec4899"/>
                            </linearGradient>
                        </defs>
                        <circle
                            v-for="(point, idx) in trendPoints"
                            :key="idx"
                            :cx="point.x"
                            :cy="point.y"
                            r="5"
                            fill="white"
                            stroke="#8b5cf6"
                            stroke-width="2.5"
                        />
                    </svg>

                    <!-- Бары -->
                    <div class="chart-bars">
                        <div
                            v-for="(month, idx) in chartData"
                            :key="month.id"
                            class="chart-bar-group"
                            @mouseenter="hoveredBar = idx"
                            @mouseleave="hoveredBar = null"
                        >
                            <!-- Тултип -->
                            <transition name="tooltip">
                                <div v-if="hoveredBar === idx" class="chart-tooltip">
                                    <div class="tooltip-title">{{ month.label }}</div>
                                    <div class="tooltip-row">
                                        <span>Новые:</span>
                                        <strong>{{ formatPrice(month.new) }}</strong>
                                    </div>
                                    <div class="tooltip-row">
                                        <span>Продления:</span>
                                        <strong>{{ formatPrice(month.renewal) }}</strong>
                                    </div>
                                    <div class="tooltip-total">
                                        <span>Итого:</span>
                                        <strong>{{ formatPrice(month.total) }}</strong>
                                    </div>
                                    <div v-if="month.is_forecast" class="tooltip-badge">
                                        <i class="fa-solid fa-crystal-ball"></i> Прогноз
                                    </div>
                                </div>
                            </transition>

                            <!-- Стак баров -->
                            <div class="bar-stack">
                                <div
                                    class="bar bar-renewal"
                                    :style="{ height: getBarHeight(month.renewal) + '%' }"
                                ></div>
                                <div
                                    class="bar bar-new"
                                    :style="{ height: getBarHeight(month.new) + '%' }"
                                ></div>
                            </div>

                            <!-- Подпись месяца -->
                            <div class="bar-label" :class="{ 'is-future': month.is_forecast }">
                                <span class="bar-month">{{ month.shortLabel }}</span>
                                <span v-if="month.is_forecast" class="bar-badge">
                                    <i class="fa-solid fa-crystal-ball"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Горизонтальные линии сетки -->
                    <div class="chart-grid">
                        <div v-for="i in 4" :key="i" class="grid-line" :style="{ bottom: (i * 25) + '%' }">
                            <span class="grid-value">{{ formatPrice(maxValue * (1 - i * 0.25)) }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ДЕТАЛЬНАЯ РАЗБИВКА -->
        <!-- ========================================== -->
        <div class="breakdown-section">
            <div class="section-header">
                <h3>Детализация по месяцам</h3>
                <div class="breakdown-tabs">
                    <button
                        class="breakdown-tab"
                        :class="{ 'is-active': breakdownView === 'list' }"
                        @click="breakdownView = 'list'"
                    >
                        <i class="fa-solid fa-list"></i>
                    </button>
                    <button
                        class="breakdown-tab"
                        :class="{ 'is-active': breakdownView === 'table' }"
                        @click="breakdownView = 'table'"
                    >
                        <i class="fa-solid fa-table-cells"></i>
                    </button>
                </div>
            </div>

            <!-- Режим списка -->
            <div v-if="breakdownView === 'list'" class="breakdown-list">
                <div
                    v-for="month in chartData"
                    :key="'list-' + month.id"
                    class="breakdown-item"
                    :class="{ 'is-future': month.is_forecast }"
                >
                    <div class="breakdown-date">
                        <div class="date-icon" :class="{ 'is-future': month.is_forecast }">
                            <i :class="month.is_forecast ? 'fa-solid fa-crystal-ball' : 'fa-solid fa-calendar-check'"></i>
                        </div>
                        <div class="date-info">
                            <div class="date-month">{{ month.label }}</div>
                            <div class="date-status">
                                {{ month.is_forecast ? 'Прогноз' : 'Факт' }}
                            </div>
                        </div>
                    </div>

                    <div class="breakdown-bars">
                        <div class="mini-bar-wrapper">
                            <div class="mini-bar-bg">
                                <div class="mini-bar-fill new" :style="{ width: getPercent(month.new, month.total) + '%' }"></div>
                                <div class="mini-bar-fill renewal" :style="{ width: getPercent(month.renewal, month.total) + '%', left: getPercent(month.new, month.total) + '%' }"></div>
                            </div>
                        </div>
                        <div class="mini-bar-legend">
                            <span class="mini-legend-item">
                                <span class="dot new"></span>
                                Новые: {{ formatPrice(month.new) }}
                            </span>
                            <span class="mini-legend-item">
                                <span class="dot renewal"></span>
                                Продления: {{ formatPrice(month.renewal) }}
                            </span>
                        </div>
                    </div>

                    <div class="breakdown-total">
                        <div class="total-value">{{ formatPrice(month.total) }}</div>
                        <div v-if="month.change !== null" class="total-change" :class="month.change >= 0 ? 'positive' : 'negative'">
                            <i :class="month.change >= 0 ? 'fa-solid fa-arrow-up' : 'fa-solid fa-arrow-down'"></i>
                            {{ Math.abs(month.change) }}%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Режим таблицы -->
            <div v-else class="breakdown-table-wrapper">
                <table class="breakdown-table">
                    <thead>
                    <tr>
                        <th>Месяц</th>
                        <th>Новые продажи</th>
                        <th>Продления</th>
                        <th>Итого</th>
                        <th>Изменение</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="month in chartData" :key="'table-' + month.id" :class="{ 'is-future': month.is_forecast }">
                        <td>
                            <div class="table-month">
                                <span>{{ month.label }}</span>
                                <span v-if="month.is_forecast" class="forecast-badge">прогноз</span>
                            </div>
                        </td>
                        <td class="text-new">{{ formatPrice(month.new) }}</td>
                        <td class="text-renewal">{{ formatPrice(month.renewal) }}</td>
                        <td class="text-total">{{ formatPrice(month.total) }}</td>
                        <td>
                                <span v-if="month.change !== null" class="change-badge" :class="month.change >= 0 ? 'positive' : 'negative'">
                                    <i :class="month.change >= 0 ? 'fa-solid fa-arrow-up' : 'fa-solid fa-arrow-down'"></i>
                                    {{ Math.abs(month.change) }}%
                                </span>
                            <span v-else class="text-muted">—</span>
                        </td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td><strong>Итого за период</strong></td>
                        <td class="text-new"><strong>{{ formatPrice(totalNewSales) }}</strong></td>
                        <td class="text-renewal"><strong>{{ formatPrice(totalRenewals) }}</strong></td>
                        <td class="text-total"><strong>{{ formatPrice(totalForecast) }}</strong></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ИНФОРМАЦИОННЫЙ БЛОК -->
        <!-- ========================================== -->
        <div class="info-section">
            <div class="info-icon">
                <i class="fa-solid fa-lightbulb"></i>
            </div>
            <div class="info-content">
                <h4>Как рассчитывается прогноз?</h4>
                <p>
                    Прогноз основан на вашем среднем темпе новых продаж за последние месяцы и проценте продлений подписок ({{ renewalRate }}%).
                    Доход от продлений — это пассивный доход, который растёт с каждым месяцем работы.
                    Чем дольше вы работаете, тем большую долю в доходе занимают продления.
                </p>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "AgentIncomeForecast",

    props: {
        agent: { type: Object, required: true },
    },

    data() {
        return {
            selectedPeriod: '3m',
            hoveredBar: null,
            breakdownView: 'list',
            chartWidth: 1000,
            chartHeight: 300,

            periods: [
                { id: '1m', label: '1 месяц', months: 1 },
                { id: '3m', label: '3 месяца', months: 3 },
                { id: '6m', label: '6 месяцев', months: 6 },
                { id: '1y', label: '1 год', months: 12 },
            ],

            // Исторические данные (прошлые месяцы)
            history: [
                { id: 'h1', month: 202603, label: 'Март 2026', shortLabel: 'Мар', new: 28000, renewal: 0, is_forecast: false },
                { id: 'h2', month: 202604, label: 'Апрель 2026', shortLabel: 'Апр', new: 35000, renewal: 4200, is_forecast: false },
                { id: 'h3', month: 202605, label: 'Май 2026', shortLabel: 'Май', new: 42000, renewal: 8500, is_forecast: false },
                { id: 'h4', month: 202606, label: 'Июнь 2026', shortLabel: 'Июн', new: 38000, renewal: 12400, is_forecast: false },
            ],

            renewalRate: 68, // % клиентов, продлевающих подписку
        };
    },

    computed: {
        selectedMonths() {
            return this.periods.find(p => p.id === this.selectedPeriod)?.months || 3;
        },

        avgNewPerMonth() {
            if (this.history.length === 0) return 0;
            const sum = this.history.reduce((s, m) => s + m.new, 0);
            return Math.round(sum / this.history.length);
        },

        // Генерация прогноза на будущие месяцы
        forecastData() {
            const forecast = [];
            const currentDate = new Date(2026, 5); // Июнь 2026
            const monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
            const monthNamesFull = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];

            // Суммарный доход от прошлых новых продаж (база для продлений)
            let cumulativeNewSales = this.history.reduce((s, m) => s + m.new, 0);

            for (let i = 1; i <= this.selectedMonths; i++) {
                const futureDate = new Date(currentDate);
                futureDate.setMonth(currentDate.getMonth() + i);

                const monthIdx = futureDate.getMonth();
                const year = futureDate.getFullYear();

                // Новые продажи — на основе среднего
                const newSales = this.avgNewPerMonth;

                // Продления — % от накопленной базы новых продаж
                const monthlyRenewalRate = this.renewalRate / 100 / 12; // месячная ставка продления
                const renewals = Math.round(cumulativeNewSales * monthlyRenewalRate);

                // Накапливаем базу
                cumulativeNewSales += newSales;

                forecast.push({
                    id: `f${i}`,
                    month: year * 100 + (monthIdx + 1),
                    label: `${monthNamesFull[monthIdx]} ${year}`,
                    shortLabel: monthNames[monthIdx],
                    new: newSales,
                    renewal: renewals,
                    is_forecast: true,
                });
            }

            return forecast;
        },

        // Объединённые данные: история + прогноз
        chartData() {
            const combined = [...this.history, ...this.forecastData];

            // Добавляем процент изменения к прошлому месяцу
            return combined.map((month, idx) => {
                const prev = idx > 0 ? combined[idx - 1] : null;
                const change = prev ? Math.round(((month.total - prev.total) / prev.total) * 100) : null;
                return { ...month, total: month.new + month.renewal, change };
            });
        },

        maxValue() {
            return Math.max(...this.chartData.map(m => m.total)) * 1.2;
        },

        totalForecast() {
            return this.forecastData.reduce((s, m) => s + m.new + m.renewal, 0);
        },

        newSalesForecast() {
            return this.forecastData.reduce((s, m) => s + m.new, 0);
        },

        renewalsForecast() {
            return this.forecastData.reduce((s, m) => s + m.renewal, 0);
        },

        totalNewSales() {
            return this.chartData.reduce((s, m) => s + m.new, 0);
        },

        totalRenewals() {
            return this.chartData.reduce((s, m) => s + m.renewal, 0);
        },

        avgMonthlyIncome() {
            if (this.chartData.length === 0) return 0;
            const total = this.chartData.reduce((s, m) => s + m.total, 0);
            return Math.round(total / this.chartData.length);
        },

        // Тренд: сравнение прогноза с прошлым периодом той же длины
        trendPercent() {
            const pastData = this.history.slice(-this.selectedMonths);
            if (pastData.length === 0) return 0;
            const pastTotal = pastData.reduce((s, m) => s + m.new + m.renewal, 0);
            if (pastTotal === 0) return 0;
            return Math.round(((this.totalForecast - pastTotal) / pastTotal) * 100);
        },

        trendClass() {
            return this.trendPercent >= 0 ? 'positive' : 'negative';
        },

        trendIcon() {
            return this.trendPercent >= 0 ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down';
        },

        // SVG линия тренда
        trendPoints() {
            const data = this.chartData;
            if (data.length === 0) return [];

            const padding = 40;
            const width = this.chartWidth - padding * 2;
            const height = this.chartHeight - padding * 2;
            const step = width / Math.max(data.length - 1, 1);

            return data.map((month, idx) => ({
                x: padding + idx * step,
                y: padding + height - (month.total / this.maxValue) * height,
            }));
        },

        trendLinePath() {
            if (this.trendPoints.length === 0) return '';
            return this.trendPoints
                .map((p, idx) => `${idx === 0 ? 'M' : 'L'} ${p.x} ${p.y}`)
                .join(' ');
        },
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0);
        },

        getBarHeight(value) {
            if (!this.maxValue) return 0;
            return Math.max(2, (value / this.maxValue) * 100);
        },

        getPercent(value, total) {
            if (!total) return 0;
            return (value / total) * 100;
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #3b82f6;
$purple: #8b5cf6;
$pink: #ec4899;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$new-color: #3b82f6;
$renewal-color: #10b981;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.tab-content { animation: fadeIn 0.3s ease; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

// ==========================================
// ШАПКА
// ==========================================
.section-header-page {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;

    h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 4px 0;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0;
    }
}

.period-selector {
    display: flex;
    gap: 4px;
    padding: 4px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
}

.period-btn {
    padding: 8px 16px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    &:hover {
        color: $primary;
        background: rgba($primary, 0.05);
    }

    &.is-active {
        background: $primary;
        color: white;
        box-shadow: 0 2px 8px rgba($primary, 0.3);
    }
}

// ==========================================
// МЕТРИКИ
// ==========================================
.forecast-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.metric-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    gap: 14px;
    position: relative;
    overflow: hidden;

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    &.total::before { background: linear-gradient(90deg, $purple 0%, $pink 100%); }
    &.new-sales::before { background: linear-gradient(90deg, $new-color 0%, #60a5fa 100%); }
    &.renewals::before { background: linear-gradient(90deg, $renewal-color 0%, #34d399 100%); }
    &.monthly::before { background: linear-gradient(90deg, $warning 0%, #fbbf24 100%); }
}

.metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;

    .total & { background: linear-gradient(135deg, $purple 0%, $pink 100%); color: white; }
    .new-sales & { background: rgba($new-color, 0.1); color: $new-color; }
    .renewals & { background: rgba($renewal-color, 0.1); color: $renewal-color; }
    .monthly & { background: rgba($warning, 0.1); color: $warning; }
}

.metric-info {
    flex: 1;
    min-width: 0;
}

.metric-label {
    font-size: 0.75rem;
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: $text;
    line-height: 1;
    margin-bottom: 6px;
}

.metric-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 8px;

    &.positive {
        color: $success;
        background: rgba($success, 0.1);
    }

    &.negative {
        color: $danger;
        background: rgba($danger, 0.1);
    }
}

.metric-hint {
    font-size: 0.8rem;
    color: $text-muted;
}

// ==========================================
// ГРАФИК (ОБНОВЛЕННЫЙ)
// ==========================================
.chart-section {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    overflow: hidden; // Чтобы скроллбар не вылезал за границы карточки
}

.chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        color: $text;
    }
}

.chart-legend {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $text-muted;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;

    &.new { background: $new-color; }
    &.renewal { background: $renewal-color; }
    &.trend {
        background: linear-gradient(135deg, $purple 0%, $pink 100%);
        border-radius: 6px;
        height: 3px;
        width: 16px;
    }
}

// --- НОВЫЕ КЛАССЫ ДЛЯ СКРОЛЛА ---
.chart-scroll-wrapper {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 12px; // Место под скроллбар

    // Кастомный скроллбар для Webkit (Chrome, Safari, Edge)
    &::-webkit-scrollbar {
        height: 8px;
    }
    &::-webkit-scrollbar-track {
        background: $bg;
        border-radius: 4px;
    }
    &::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 4px;
        &:hover { background: #9ca3af; }
    }
}

.chart-inner-content {
    min-width: 1000px; // <-- ГЛАВНОЕ: запрещаем сжиматься меньше этой ширины
    position: relative;
    height: 320px;
    padding: 20px 40px 40px 40px; // Отступы для сетки и подписей
}

.trend-line {
    position: absolute;
    inset: 20px 40px 40px 40px; // Совпадает с padding родителя
    width: calc(100% - 80px);
    height: calc(100% - 60px);
    pointer-events: none;
    z-index: 2;
}

.chart-bars {
    position: relative;
    display: flex;
    justify-content: space-between; // Равномерное распределение
    align-items: flex-end;
    height: 100%;
    z-index: 1;
}

.chart-bar-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    position: relative;
    cursor: pointer;
    min-width: 60px; // Минимальная ширина одной колонки месяца
}

.chart-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: $text;
    color: white;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 10;
    margin-bottom: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);

    &::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-top-color: $text;
    }
}

.tooltip-title {
    font-weight: 700;
    margin-bottom: 6px;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.tooltip-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 2px;
    strong { color: white; }
}

.tooltip-total {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-weight: 700;
    strong { color: #fbbf24; }
}

.tooltip-badge {
    margin-top: 6px;
    padding: 2px 8px;
    background: rgba($purple, 0.3);
    border-radius: 6px;
    font-size: 0.7rem;
    text-align: center;
}

.tooltip-enter-active, .tooltip-leave-active {
    transition: all 0.2s ease;
}
.tooltip-enter-from, .tooltip-leave-to {
    opacity: 0;
    transform: translateX(-50%) translateY(4px);
}

.bar-stack {
    width: 36px; // Чуть шире, чтобы выглядело солиднее
    height: calc(100% - 40px);
    display: flex;
    flex-direction: column-reverse;
    gap: 2px;
    transition: all 0.3s ease;

    .chart-bar-group:hover & {
        transform: scaleY(1.02);
    }
}

.bar {
    width: 100%;
    border-radius: 6px 6px 0 0;
    transition: height 0.6s cubic-bezier(0.4, 0, 0.2, 1);

    &.bar-new {
        background: linear-gradient(180deg, $new-color 0%, #60a5fa 100%);
    }

    &.bar-renewal {
        background: linear-gradient(180deg, $renewal-color 0%, #34d399 100%);
        border-radius: 0;
    }
}

.chart-bar-group:has(.bar-label.is-future) .bar {
    opacity: 0.75;
    background-image: repeating-linear-gradient(
            45deg,
            transparent,
            transparent 4px,
            rgba(255, 255, 255, 0.2) 4px,
            rgba(255, 255, 255, 0.2) 8px
    );
}

.bar-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    margin-top: 8px;
    color: $text-muted;
    font-size: 0.8rem;
    font-weight: 600;

    &.is-future {
        color: $purple;
    }
}

.bar-badge {
    font-size: 0.7rem;
    color: $purple;
}

.chart-grid {
    position: absolute;
    inset: 20px 40px 40px 40px; // Совпадает с padding родителя
    pointer-events: none;
    z-index: 0;
}

.grid-line {
    position: absolute;
    left: 0;
    right: 0;
    border-top: 1px dashed rgba($border, 0.8);

    .grid-value {
        position: absolute;
        left: -45px; // Выносим за пределы графика влево
        top: -10px;
        font-size: 0.7rem;
        color: $text-muted;
        background: $card-bg;
        padding: 0 4px;
    }
}

// ==========================================
// ДЕТАЛИЗАЦИЯ
// ==========================================
.breakdown-section {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        color: $text;
    }
}

.breakdown-tabs {
    display: flex;
    gap: 4px;
    padding: 3px;
    background: $bg;
    border-radius: 8px;
}

.breakdown-tab {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: transparent;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &.is-active {
        background: $card-bg;
        color: $primary;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
}

// Список
.breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.breakdown-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    padding: 16px;
    background: $bg;
    border-radius: 12px;
    align-items: center;
    transition: all 0.2s;

    &:hover {
        background: color.scale($bg, $lightness: -3%);
    }

    &.is-future {
        background: linear-gradient(135deg, rgba($purple, 0.05) 0%, rgba($pink, 0.03) 100%);
        border: 1px dashed rgba($purple, 0.3);
    }
}

.breakdown-date {
    display: flex;
    align-items: center;
    gap: 12px;
}

.date-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;

    &.is-future {
        background: rgba($purple, 0.1);
        color: $purple;
    }
}

.date-month {
    font-weight: 700;
    font-size: 0.95rem;
    color: $text;
}

.date-status {
    font-size: 0.75rem;
    color: $text-muted;
}

.breakdown-bars {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.mini-bar-wrapper {
    width: 100%;
}

.mini-bar-bg {
    position: relative;
    width: 100%;
    height: 8px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
    overflow: hidden;
}

.mini-bar-fill {
    position: absolute;
    top: 0;
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;

    &.new { background: $new-color; }
    &.renewal { background: $renewal-color; }
}

.mini-bar-legend {
    display: flex;
    gap: 12px;
    font-size: 0.75rem;
    color: $text-muted;
}

.mini-legend-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;

    &.new { background: $new-color; }
    &.renewal { background: $renewal-color; }
}

.breakdown-total {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.total-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: $text;
}

.total-change {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 6px;

    &.positive {
        color: $success;
        background: rgba($success, 0.1);
    }

    &.negative {
        color: $danger;
        background: rgba($danger, 0.1);
    }
}

// Таблица
.breakdown-table-wrapper {
    overflow-x: auto;
}

.breakdown-table {
    width: 100%;
    border-collapse: collapse;

    th, td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid $border;
    }

    th {
        font-size: 0.75rem;
        font-weight: 700;
        color: $text-muted;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: $bg;
    }

    tbody tr {
        transition: background 0.2s;

        &:hover {
            background: $bg;
        }

        &.is-future {
            background: rgba($purple, 0.03);
        }
    }

    tfoot {
        background: $bg;
        font-weight: 700;
    }
}

.table-month {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.forecast-badge {
    padding: 2px 6px;
    background: rgba($purple, 0.1);
    color: $purple;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
}

.text-new { color: $new-color; font-weight: 600; }
.text-renewal { color: $renewal-color; font-weight: 600; }
.text-total { color: $text; font-weight: 700; }
.text-muted { color: $text-muted; }

.change-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;

    &.positive {
        background: rgba($success, 0.1);
        color: $success;
    }

    &.negative {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

// ==========================================
// ИНФОРМАЦИОННЫЙ БЛОК
// ==========================================
.info-section {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: linear-gradient(135deg, rgba($warning, 0.08) 0%, rgba($warning, 0.03) 100%);
    border: 1px solid rgba($warning, 0.2);
    border-radius: 16px;
}

.info-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba($warning, 0.15);
    color: $warning;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.info-content {
    flex: 1;

    h4 {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: $text;
    }

    p {
        font-size: 0.85rem;
        color: $text-muted;
        margin: 0;
        line-height: 1.5;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .section-header-page {
        flex-direction: column;
        align-items: flex-start;
    }

    .period-selector {
        width: 100%;
        overflow-x: auto;
    }

    .forecast-metrics {
        grid-template-columns: 1fr;
    }

    .chart-container {
        height: 260px;
    }

    .bar-stack {
        width: 24px;
    }

    .breakdown-item {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .breakdown-total {
        align-items: flex-start;
    }

    .chart-legend {
        width: 100%;
    }
}
</style>
