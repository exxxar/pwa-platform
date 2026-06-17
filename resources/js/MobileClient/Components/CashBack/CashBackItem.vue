<template>
    <div
        class="cashback-item-card"
        :class="{ 'is-expanded': isExpanded }"
        @click="toggleExpand"
    >
        <!-- ========================================== -->
        <!-- ШАПКА КАРТОЧКИ (ВСЕГДА ВИДНА) -->
        <!-- ========================================== -->
        <div class="item-header">
            <div class="header-left">
                <div class="icon-box" :class="item.operation_type ? 'success' : 'danger'">
                    <i :class="item.operation_type ? 'fa-solid fa-arrow-up' : 'fa-solid fa-arrow-down'"></i>
                </div>
                <div class="header-info">
                    <span class="amount" :class="item.operation_type ? 'text-success' : 'text-danger'">
                        {{ item.operation_type ? '+' : '-' }}{{ formatPrice(item.amount) }}
                    </span>
                    <span class="type-label">
                        {{ item.operation_type ? 'Начисление' : 'Списание' }}
                    </span>
                </div>
            </div>

            <div class="header-right">
                <span v-if="item.operation_type && item.money_in_check" class="check-badge">
                    Чек: {{ formatPrice(item.money_in_check) }}
                </span>
                <i class="fa-solid fa-chevron-down chevron" :class="{ 'rotated': isExpanded }"></i>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ДЕТАЛИ ОПЕРАЦИИ (РАСКРЫВАЮТСЯ) -->
        <!-- ========================================== -->
        <div v-if="isExpanded" class="item-details">
            <div class="detail-row">
                <span class="detail-label"><i class="fa-regular fa-calendar"></i> Дата операции</span>
                <span class="detail-value">{{ formatDate(item.created_at) }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label"><i class="fa-solid fa-align-left"></i> Описание</span>
                <span class="detail-value">{{ item.description || 'Нет описания' }}</span>
            </div>

            <div v-if="item.operation_type" class="detail-row highlight">
                <span class="detail-label"><i class="fa-solid fa-layer-group"></i> Уровень начисления</span>
                <span class="detail-value">{{ item.level || 0 }}</span>
            </div>

            <div class="divider"></div>

            <div class="detail-section-title">
                <i class="fa-solid fa-user-tie"></i>
                <span>Сотрудник, оформивший операцию</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Имя</span>
                <span class="detail-value">{{ item.employee?.fio_from_telegram || 'Не указано' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Телефон</span>
                <span class="detail-value">{{ item.employee?.phone || 'Не указано' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Telegram ID</span>
                <span class="detail-value code">{{ item.employee?.telegram_chat_id || 'Не указано' }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CashBackItem',

    props: {
        item: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            // ИСПРАВЛЕНИЕ: Не мутируем пропс item.is_open, а используем локальное состояние
            isExpanded: false,
        }
    },

    methods: {
        toggleExpand() {
            this.isExpanded = !this.isExpanded
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0)
        },

        formatDate(dateString) {
            if (!dateString) return '—'

            // Если у вас есть глобальный фильтр $filters.current, можно вернуть его:
            // return this.$filters.current(dateString)

            // Иначе используем нативное форматирование:
            const date = new Date(dateString)
            return date.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            })
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

.cashback-item-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.98);
        background: rgba($admin-primary, 0.02);
    }

    &.is-expanded {
        border-color: $admin-primary;
        box-shadow: 0 4px 12px rgba($admin-primary, 0.08);
    }
}

// ==========================================
// ШАПКА КАРТОЧКИ
// ==========================================
.item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    gap: 12px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;

    &.success {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }

    &.danger {
        background: rgba($admin-danger, 0.1);
        color: $admin-danger;
    }
}

.header-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.amount {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.2;

    &.text-success { color: $admin-success; }
    &.text-danger { color: $admin-danger; }
}

.type-label {
    font-size: 0.8rem;
    color: $admin-text-muted;
    font-weight: 500;
}

.header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    flex-shrink: 0;
}

.check-badge {
    font-size: 0.75rem;
    font-weight: 600;
    color: $admin-primary;
    background: rgba($admin-primary, 0.08);
    padding: 4px 8px;
    border-radius: 6px;
}

.chevron {
    color: $admin-text-muted;
    font-size: 0.9rem;
    transition: transform 0.3s ease;

    &.rotated {
        transform: rotate(180deg);
    }
}

// ==========================================
// ДЕТАЛИ ОПЕРАЦИИ
// ==========================================
.item-details {
    padding: 0 16px 16px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 10px 0;
    gap: 12px;

    &.highlight {
        background: rgba($admin-success, 0.04);
        margin: 0 -16px;
        padding: 10px 16px;
        border-radius: 8px;
    }
}

.detail-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: $admin-text-muted;
    flex: 1;

    i {
        width: 16px;
        text-align: center;
        color: $admin-primary;
    }
}

.detail-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text;
    text-align: right;
    word-break: break-word;

    &.code {
        font-family: monospace;
        font-size: 0.8rem;
        background: $admin-bg;
        padding: 2px 6px;
        border-radius: 4px;
    }
}

.divider {
    height: 1px;
    background: $admin-border;
    margin: 8px 0;
}

.detail-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    color: $admin-text-muted;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 12px 0 8px;

    i {
        color: $admin-primary;
    }
}
</style>
