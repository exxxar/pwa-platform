<template>
    <div class="data-table-wrapper">
        <!-- Filters -->
        <div v-if="$slots.filters" class="table-filters">
            <slot name="filters"></slot>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                                'table-header',
                                { sortable: column.sortable },
                                { sorted: params.sort_by === column.key }
                            ]"
                        :style="{ width: column.width }"
                        @click="column.sortable && handleSort(column.key)"
                    >
                        <div class="header-content">
                            <span>{{ column.label }}</span>
                            <span v-if="column.sortable" class="sort-icon">
                                    <span v-if="params.sort_by === column.key">
                                        {{ params.sort_dir === 'asc' ? '↑' : '↓' }}
                                    </span>
                                    <span v-else>⇅</span>
                                </span>
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="loading" class="table-loading">
                    <td :colspan="columns.length">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <span>Загрузка...</span>
                        </div>
                    </td>
                </tr>
                <tr v-else-if="data.length === 0" class="table-empty">
                    <td :colspan="columns.length">
                        <div class="empty-state">
                            <span class="empty-icon">📭</span>
                            <p>{{ emptyMessage }}</p>
                        </div>
                    </td>
                </tr>
                <tr v-else v-for="(row, index) in data" :key="row.id || index" class="table-row">
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        class="table-cell"
                    >
                        <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                            {{ row[column.key] }}
                        </slot>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="showPagination && pagination.last_page > 1" class="table-pagination">
            <div class="pagination-info">
                Показано {{ pagination.from }}-{{ pagination.to }} из {{ pagination.total }}
            </div>
            <div class="pagination-controls">
                <button
                    class="pagination-btn"
                    :disabled="pagination.current_page === 1"
                    @click="setPage(pagination.current_page - 1)"
                >
                    ←
                </button>
                <span class="pagination-current">
                    {{ pagination.current_page }} / {{ pagination.last_page }}
                </span>
                <button
                    class="pagination-btn"
                    :disabled="pagination.current_page === pagination.last_page"
                    @click="setPage(pagination.current_page + 1)"
                >
                    →
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    columns: {
        type: Array,
        required: true,
        validator: (value) => value.every(col => 'key' in col && 'label' in col),
    },
    data: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        }),
    },
    params: {
        type: Object,
        default: () => ({
            sort_by: 'created_at',
            sort_dir: 'desc',
        }),
    },
    showPagination: {
        type: Boolean,
        default: true,
    },
    emptyMessage: {
        type: String,
        default: 'Нет данных для отображения',
    },
})

const emit = defineEmits(['sort', 'page'])

const handleSort = (key) => {
    const newDir = props.params.sort_by === key && props.params.sort_dir === 'asc' ? 'desc' : 'asc'
    emit('sort', { key, dir: newDir })
}

const setPage = (page) => {
    emit('page', page)
}
</script>

<style scoped>
.data-table-wrapper {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.table-filters {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    background: #f7fafc;
}

.table-container {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.table-header {
    padding: 16px 20px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #4a5568;
    background: #f7fafc;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.table-header.sortable {
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
}

.table-header.sortable:hover {
    background: #edf2f7;
}

.table-header.sorted {
    color: #667eea;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.sort-icon {
    font-size: 14px;
    opacity: 0.5;
}

.table-header.sorted .sort-icon {
    opacity: 1;
}

.table-row {
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.2s;
}

.table-row:hover {
    background: #f7fafc;
}

.table-row:last-child {
    border-bottom: none;
}

.table-cell {
    padding: 16px 20px;
    font-size: 14px;
    color: #2d3748;
}

.table-loading,
.table-empty {
    text-align: center;
}

.table-loading td,
.table-empty td {
    padding: 60px 20px;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    color: #718096;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e2e8f0;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.empty-icon {
    font-size: 48px;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

/* Pagination */
.table-pagination {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f7fafc;
}

.pagination-info {
    font-size: 13px;
    color: #718096;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pagination-btn {
    padding: 8px 16px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.pagination-btn:hover:not(:disabled) {
    background: #edf2f7;
    border-color: #cbd5e0;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-current {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
}
</style>
