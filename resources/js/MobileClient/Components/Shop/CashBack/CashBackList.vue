<template>
    <div class="cashback-item">
        <!-- Иконка операции -->
        <div class="item-icon" :class="iconClass">
            <i :class="iconName"></i>
        </div>

        <!-- Описание и дата -->
        <div class="item-details">
            <div class="item-title">{{ item?.title || 'Начисление CashBack' }}</div>
            <div class="item-date">{{ formatDate(item?.created_at) }}</div>
        </div>

        <!-- Сумма -->
        <div class="item-amount" :class="amountClass">
            {{ formatAmount(item?.amount || 0) }}
        </div>
    </div>
</template>

<script>
export default {
    name: "CashBackItem",

    props: {
        item: {
            type: Object,
            required: true
        }
    },

    computed: {
        // Определяем, это пополнение (+) или списание (-)
        isPositive() {
            // Адаптируй это условие под реальные данные с бэка
            // (например, item.type === 'credit' или item.amount > 0)
            return (this.item?.amount || 0) >= 0;
        },

        amountClass() {
            return this.isPositive ? 'text-success' : 'text-danger';
        },

        iconName() {
            // Адаптируй под свои типы операций
            if (!this.isPositive) return 'fa-solid fa-bag-shopping'; // Покупка/списание
            if (this.item?.title?.toLowerCase().includes('подарок') || this.item?.title?.toLowerCase().includes('бонус')) {
                return 'fa-solid fa-gift';
            }
            return 'fa-solid fa-arrow-down'; // Стандартное начисление
        },

        iconClass() {
            return this.isPositive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
        }
    },

    methods: {
        formatAmount(amount) {
            const num = Math.abs(amount || 0);
            const formatted = new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(num);

            return this.isPositive ? `+${formatted}` : `-${formatted}`;
        },

        formatDate(dateString) {
            if (!dateString) return 'Недавно';

            const date = new Date(dateString);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            // Если сегодня
            if (date.toDateString() === today.toDateString()) {
                return `Сегодня, ${date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`;
            }
            // Если вчера
            if (date.toDateString() === yesterday.toDateString()) {
                return `Вчера, ${date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`;
            }
            // Иначе полная дата
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
};
</script>

<style scoped>
.cashback-item {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color-translucent);
    transition: background-color 0.2s ease;
}

.cashback-item:last-child {
    border-bottom: none;
}

.cashback-item:hover {
    background: rgba(var(--bs-primary-rgb), 0.02);
}

/* Иконка */
.item-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-right: 14px;
}

.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.1);
}

.bg-danger-subtle {
    background-color: rgba(220, 53, 69, 0.1);
}

/* Детали */
.item-details {
    flex: 1;
    min-width: 0; /* Важно для text-overflow */
}

.item-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--bs-body-color);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-date {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

/* Сумма */
.item-amount {
    font-weight: 700;
    font-size: 1rem;
    white-space: nowrap;
    margin-left: 12px;
}

.text-success {
    color: #198754 !important;
}

.text-danger {
    color: #dc3545 !important;
}
</style>
