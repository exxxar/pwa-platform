<template>
    <div class="referrals-filters">
        <!-- Поиск -->
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input
                type="text"
                class="search-input"
                placeholder="Поиск по имени..."
                :value="modelValue.search"
                @input="$emit('update:modelValue', { ...modelValue, search: $event.target.value })"
            >
            <button
                v-if="modelValue.search"
                class="search-clear"
                @click="$emit('update:modelValue', { ...modelValue, search: '' })"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Чипы-фильтры по статусу -->
        <div class="filter-chips">
            <button
                v-for="filter in statusFilters"
                :key="filter.value"
                class="filter-chip"
                :class="{ 'active': modelValue.status === filter.value }"
                @click="$emit('update:modelValue', { ...modelValue, status: filter.value })"
            >
                <i :class="filter.icon"></i>
                <span>{{ filter.label }}</span>
                <span class="chip-count" v-if="filter.count > 0">{{ filter.count }}</span>
            </button>
        </div>

        <!-- Вторая строка: уровень + сортировка -->
        <div class="filter-row">
            <!-- Селектор уровня -->
            <div class="level-select-wrapper">
                <select
                    class="level-select"
                    :value="modelValue.level"
                    @change="$emit('update:modelValue', { ...modelValue, level: $event.target.value })"
                >
                    <option value="all">Все уровни</option>
                    <option value="1">Уровень 1 (10%)</option>
                    <option value="2">Уровень 2 (5%)</option>
                    <option value="3">Уровень 3 (2%)</option>
                </select>
            </div>

            <!-- Сортировка -->
            <div class="sort-select-wrapper">
                <select
                    class="sort-select"
                    :value="modelValue.sort"
                    @change="$emit('update:modelValue', { ...modelValue, sort: $event.target.value })"
                >
                    <option value="date_desc">Сначала новые</option>
                    <option value="date_asc">Сначала старые</option>
                    <option value="earned_desc">По заработку ↓</option>
                    <option value="earned_asc">По заработку ↑</option>
                    <option value="spent_desc">По тратам ↓</option>
                    <option value="orders_desc">По заказам ↓</option>
                </select>
            </div>
        </div>

        <!-- Результат фильтрации -->
        <div class="filter-result" v-if="totalCount > 0">
            <span class="result-text">
                Найдено: <strong>{{ filteredCount }}</strong> из {{ totalCount }}
            </span>
            <button
                v-if="hasActiveFilters"
                class="reset-filters"
                @click="resetFilters"
            >
                <i class="fa-solid fa-rotate-left"></i>
                Сбросить
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ReferralsFilters',

    props: {
        modelValue: {
            type: Object,
            required: true,
        },
        totalCount: {
            type: Number,
            default: 0,
        },
        filteredCount: {
            type: Number,
            default: 0,
        },
        statusCounts: {
            type: Object,
            default: () => ({ all: 0, active: 0, profitable: 0, inactive: 0 }),
        },
    },

    emits: ['update:modelValue'],

    computed: {
        statusFilters() {
            return [
                { value: 'all', label: 'Все', icon: 'fa-solid fa-users', count: this.statusCounts.all },
                { value: 'profitable', label: 'Прибыльные', icon: 'fa-solid fa-sack-dollar', count: this.statusCounts.profitable },
                { value: 'active', label: 'Активные', icon: 'fa-solid fa-check', count: this.statusCounts.active },
                { value: 'inactive', label: 'Неактивные', icon: 'fa-regular fa-clock', count: this.statusCounts.inactive },
            ];
        },

        hasActiveFilters() {
            return this.modelValue.search ||
                this.modelValue.status !== 'all' ||
                this.modelValue.level !== 'all' ||
                this.modelValue.sort !== 'date_desc';
        },
    },

    methods: {
        resetFilters() {
            this.$emit('update:modelValue', {
                search: '',
                status: 'all',
                level: 'all',
                sort: 'date_desc',
            });
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

.referrals-filters {
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 14px;
    margin-bottom: 12px;
}

// ==========================================
// ПОИСК
// ==========================================
.search-wrapper {
    position: relative;
    margin-bottom: 12px;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: $text-muted;
    font-size: 0.9rem;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 11px 40px 11px 40px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.9rem;
    color: $text;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
        background: $bg;
    }

    &::placeholder {
        color: $text-muted;
    }
}

.search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba($text-muted, 0.15);
    color: $text-muted;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: rgba($text-muted, 0.25);
        color: $text;
    }
}

// ==========================================
// ЧИПЫ ФИЛЬТРОВ
// ==========================================
.filter-chips {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 4px;
    margin-bottom: 12px;
    scrollbar-width: none;

    &::-webkit-scrollbar {
        display: none;
    }
}

.filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 12px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $text-muted;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    flex-shrink: 0;

    i {
        font-size: 0.75rem;
    }

    &:hover {
        background: $border;
        color: $text;
    }

    &.active {
        background: $primary;
        color: white;
        border-color: $primary;

        .chip-count {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }
    }
}

.chip-count {
    padding: 1px 6px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

// ==========================================
// ВТОРАЯ СТРОКА: Уровень + Сортировка
// ==========================================
.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 12px;
}

.level-select-wrapper,
.sort-select-wrapper {
    position: relative;
}

.level-select,
.sort-select {
    width: 100%;
    padding: 9px 12px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 500;
    color: $text;
    cursor: pointer;
    transition: all 0.2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 32px;

    &:focus {
        outline: none;
        border-color: $primary;
    }

    &:hover {
        background-color: $border;
    }
}

// ==========================================
// РЕЗУЛЬТАТ ФИЛЬТРАЦИИ
// ==========================================
.filter-result {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10px;
    border-top: 1px solid $border;
}

.result-text {
    font-size: 0.82rem;
    color: $text-muted;

    strong {
        color: $text;
        font-weight: 700;
    }
}

.reset-filters {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: transparent;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    color: $text-muted;
    cursor: pointer;
    transition: all 0.2s;

    i {
        font-size: 0.7rem;
    }

    &:hover {
        background: $bg-secondary;
        color: $text;
        border-color: $text-muted;
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 400px) {
    .filter-row {
        grid-template-columns: 1fr;
    }

    .filter-chip {
        padding: 6px 10px;
        font-size: 0.75rem;

        span:not(.chip-count) {
            display: none;
        }
    }
}
</style>
