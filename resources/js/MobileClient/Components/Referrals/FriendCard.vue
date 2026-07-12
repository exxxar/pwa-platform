<template>
    <div class="friend-card">
        <!-- Аватар -->
        <div class="friend-avatar">
            <img
                v-if="friend?.avatar"
                v-lazy="friend.avatar"
                :alt="friend.name"
            >
            <div v-else class="avatar-placeholder">
                {{ getInitials(friend?.name) }}
            </div>
            <div class="online-dot" v-if="friend.is_online"></div>
        </div>

        <!-- Информация -->
        <div class="friend-info">
            <div class="friend-name">{{ friend?.name || 'Друг' }}</div>
            <div class="friend-meta">
                <span v-if="friend.phone" class="meta-item">
                    <i class="fa-solid fa-phone"></i>
                    {{ formatPhone(friend.phone) }}
                </span>
                <span v-if="friend.accepted_at" class="meta-item">
                    <i class="fa-solid fa-calendar-check"></i>
                    {{ formatDate(friend.accepted_at) }}
                </span>
            </div>
        </div>

        <!-- Действия -->
        <div class="friend-actions">
            <button
                class="action-btn chat"
                @click="$emit('chat', friend.id)"
                title="Написать"
            >
                <i class="fa-solid fa-comment"></i>
            </button>
            <button
                class="action-btn remove"
                @click="$emit('remove', friend.id)"
                title="Удалить из друзей"
            >
                <i class="fa-solid fa-user-minus"></i>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FriendCard',

    props: {
        friend: {
            type: Object,
            required: true,
        },
    },

    emits: ['chat', 'remove'],

    methods: {
        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
            });
        },

        formatPhone(phone) {
            if (!phone) return '';
            // Формат: +7 (999) 123-45-67
            const digits = phone.replace(/\D/g, '');
            if (digits.length === 11) {
                return `+${digits[0]} (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9)}`;
            }
            return phone;
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$danger: #ef4444;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.friend-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    transition: all 0.2s ease;

    &:hover {
        border-color: rgba($primary, 0.3);
        box-shadow: 0 4px 12px rgba($primary, 0.08);
        transform: translateY(-1px);
    }
}

.friend-avatar {
    position: relative;
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
        background: linear-gradient(135deg, $primary, $primary-dark);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
    }
}

.online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: $success;
    border: 2px solid $bg;
    box-shadow: 0 0 0 2px rgba($success, 0.2);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.friend-info {
    flex: 1;
    min-width: 0;
}

.friend-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

.friend-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.75rem;
    color: $text-muted;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;

    i {
        font-size: 0.7rem;
        color: $primary;
    }
}

.friend-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid $border;
    background: $bg;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        transform: scale(1.1);
    }

    &:active {
        transform: scale(0.95);
    }

    &.chat {
        color: $primary;

        &:hover {
            background: $primary;
            border-color: $primary;
            color: white;
            box-shadow: 0 4px 12px rgba($primary, 0.3);
        }
    }

    &.remove {
        color: $danger;

        &:hover {
            background: $danger;
            border-color: $danger;
            color: white;
            box-shadow: 0 4px 12px rgba($danger, 0.3);
        }
    }
}

@media (max-width: 480px) {
    .friend-card {
        padding: 10px;
        gap: 10px;
    }

    .friend-avatar {
        width: 40px;
        height: 40px;
    }

    .friend-name {
        font-size: 0.85rem;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
}
</style>
