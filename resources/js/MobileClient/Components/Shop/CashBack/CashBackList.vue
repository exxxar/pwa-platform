<template>
    <div class="cashback-list">
        <div v-if="history.length === 0 && !loading" class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>История операций пуста</p>
        </div>

        <div v-else class="history-items">
            <div
                v-for="item in history"
                :key="item.id"
                class="history-item"
                :class="item.type"
            >
                <div class="history-icon">
                    <i :class="item.type === 'credit' ? 'fa-solid fa-arrow-down' : 'fa-solid fa-arrow-up'"></i>
                </div>
                <div class="history-info">
                    <div class="history-title">{{ item.description || 'Операция' }}</div>
                    <div class="history-date">{{ formatDate(item.created_at) }}</div>
                </div>
                <div class="history-amount" :class="item.type">
                    {{ item.type === 'credit' ? '+' : '-' }}{{ formatCurrency(item.amount) }}
                </div>
            </div>
        </div>

        <button
            v-if="hasMore && !loadingHistory"
            class="load-more-btn"
            @click="loadMoreHistory"
        >
            Загрузить ещё
        </button>

        <div v-if="loadingHistory" class="loading-more">
            <div class="spinner-border spinner-border-sm" role="status"></div>
        </div>
    </div>
</template>

<script>
import { useCashback } from '@/MobileClient/composables/useCashback';

export default {
    name: 'CashBackList',

    setup() {
        const {
            history,
            loadingHistory,
            hasMore,
            loadMoreHistory,
        } = useCashback();

        return {
            history,
            loadingHistory,
            hasMore,
            loadMoreHistory,
        };
    },

    methods: {
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value);
        }
    }
};
</script>

<style scoped>
.cashback-list {
    padding: 8px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--bs-secondary-color);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 12px;
    opacity: 0.3;
}

.history-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.history-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bs-body-bg);
    border-radius: 12px;
    transition: all 0.2s ease;
}

.history-item:hover {
    background: var(--bs-tertiary-bg);
}

.history-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.history-item.credit .history-icon {
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
}

.history-item.debit .history-icon {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.history-info {
    flex: 1;
    min-width: 0;
}

.history-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.history-date {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

.history-amount {
    font-weight: 700;
    font-size: 1rem;
    white-space: nowrap;
    margin-left: 12px;
}

.history-amount.credit {
    color: #198754;
}

.history-amount.debit {
    color: #dc3545;
}

.load-more-btn {
    width: 100%;
    padding: 12px;
    margin-top: 12px;
    background: var(--bs-primary);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.load-more-btn:hover {
    background: var(--bs-primary-hover, var(--bs-primary));
    transform: translateY(-1px);
}

.loading-more {
    text-align: center;
    padding: 16px;
}
</style>
