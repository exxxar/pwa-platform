<template>
    <div class="request-card" :class="{ 'is-processing': isProcessing }">
        <!-- Аватар отправителя -->
        <div class="request-avatar">
            <img
                v-if="request.from?.avatar"
                v-lazy="request.from.avatar"
                :alt="request.from.name"
            >
            <div v-else class="avatar-placeholder">
                {{ getInitials(request.from?.name) }}
            </div>
        </div>

        <!-- Информация -->
        <div class="request-info">
            <div class="request-header">
                <span class="request-name">{{ request.from?.name || 'Пользователь' }}</span>
                <span class="request-badge">
                    <i class="fa-solid fa-user-plus"></i>
                    Заявка
                </span>
            </div>
            <div class="request-date">
                <i class="fa-solid fa-clock"></i>
                {{ formatDate(request.created_at) }}
            </div>
        </div>

        <!-- Кнопки действий -->
        <div class="request-actions">
            <button
                class="action-btn accept"
                @click="handleAccept"
                :disabled="isProcessing"
                title="Принять"
            >
                <i v-if="isProcessing && processingAction === 'accept'" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-check"></i>
            </button>
            <button
                class="action-btn reject"
                @click="handleReject"
                :disabled="isProcessing"
                title="Отклонить"
            >
                <i v-if="isProcessing && processingAction === 'reject'" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FriendRequestCard',

    props: {
        request: {
            type: Object,
            required: true,
        },
    },

    emits: ['accept', 'reject'],

    data() {
        return {
            isProcessing: false,
            processingAction: null,
        };
    },

    methods: {
        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        },

        formatDate(date) {
            if (!date) return '';
            const now = new Date();
            const requestDate = new Date(date);
            const diffMs = now - requestDate;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'только что';
            if (diffMins < 60) return `${diffMins} мин. назад`;
            if (diffHours < 24) return `${diffHours} ч. назад`;
            if (diffDays < 7) return `${diffDays} дн. назад`;

            return requestDate.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
            });
        },

        async handleAccept() {
            this.isProcessing = true;
            this.processingAction = 'accept';

            try {
                this.$emit('accept', this.request.id);
            } finally {
                setTimeout(() => {
                    this.isProcessing = false;
                    this.processingAction = null;
                }, 500);
            }
        },

        async handleReject() {
            this.isProcessing = true;
            this.processingAction = 'reject';

            try {
                this.$emit('reject', this.request.id);
            } finally {
                setTimeout(() => {
                    this.isProcessing = false;
                    this.processingAction = null;
                }, 500);
            }
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
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.request-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: linear-gradient(135deg, rgba($warning, 0.03) 0%, $bg 100%);
    border: 1px solid rgba($warning, 0.2);
    border-left: 3px solid $warning;
    border-radius: 12px;
    transition: all 0.2s ease;
    position: relative;

    &.is-processing {
        opacity: 0.7;
        pointer-events: none;
    }

    &:hover {
        box-shadow: 0 4px 16px rgba($warning, 0.12);
    }
}

.request-avatar {
    width: 48px;
    height: 48px;
    flex-shrink: 0;

    img, .avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-placeholder {
        background: linear-gradient(135deg, $warning, #d97706);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
    }
}

.request-info {
    flex: 1;
    min-width: 0;
}

.request-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.request-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.request-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: rgba($warning, 0.15);
    color: color.adjust($warning, $lightness: -15%);
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    flex-shrink: 0;

    i {
        font-size: 0.6rem;
    }
}

.request-date {
    font-size: 0.75rem;
    color: $text-muted;
    display: flex;
    align-items: center;
    gap: 4px;

    i {
        font-size: 0.7rem;
    }
}

.request-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

    &:hover:not(:disabled) {
        transform: scale(1.1) translateY(-2px);
    }

    &:active:not(:disabled) {
        transform: scale(0.95);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    &.accept {
        background: linear-gradient(135deg, $success, #059669);
        box-shadow: 0 4px 12px rgba($success, 0.3);

        &:hover:not(:disabled) {
            box-shadow: 0 6px 16px rgba($success, 0.4);
        }
    }

    &.reject {
        background: linear-gradient(135deg, $danger, #dc2626);
        box-shadow: 0 4px 12px rgba($danger, 0.3);

        &:hover:not(:disabled) {
            box-shadow: 0 6px 16px rgba($danger, 0.4);
        }
    }
}

@media (max-width: 480px) {
    .request-card {
        padding: 12px;
        gap: 10px;
    }

    .request-avatar {
        width: 40px;
        height: 40px;
    }

    .request-name {
        font-size: 0.85rem;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
}
</style>
