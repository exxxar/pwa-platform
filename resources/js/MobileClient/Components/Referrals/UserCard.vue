<template>
    <div class="user-card" :class="`level-${level}`">
        <div class="user-avatar">
            <img
                v-if="user?.avatar"
                v-lazy="user.avatar"
                :alt="user.name"
            >
            <div v-else class="avatar-placeholder">
                {{ getInitials(user?.name) }}
            </div>
            <div class="level-badge">{{ level }}</div>
        </div>

        <div class="user-info">
            <div class="user-name">{{ user?.name || 'Пользователь' }}</div>
            <div class="user-date">
                <i class="fa-solid fa-calendar"></i>
                {{ formatDate(registeredAt) }}
            </div>
        </div>

        <div class="user-percent">
            {{ percent }}%
        </div>
    </div>
</template>

<script>
export default {
    name: 'UserCard',

    props: {
        user: { type: Object, required: true },
        level: { type: Number, required: true },
        registeredAt: { type: [String, Date], default: null },
    },

    computed: {
        percent() {
            const percents = { 1: 10, 2: 5, 3: 2 };
            return percents[this.level] || 0;
        },
    },

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
                year: 'numeric',
            });
        },
    },
};
</script>

<style lang="scss" scoped>
$success: #10b981;
$primary: #667eea;
$purple: #8b5cf6;

.user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: 10px;
    transition: background 0.2s;

    &:hover {
        background: var(--bs-secondary-bg, #f8f9fa);
    }

    &.level-1 .level-badge { background: $success; }
    &.level-2 .level-badge { background: $primary; }
    &.level-3 .level-badge { background: $purple; }
}

.user-avatar {
    position: relative;
    width: 44px;
    height: 44px;
    flex-shrink: 0;

    img, .avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-placeholder {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }
}

.level-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #6b7280;
    color: white;
    font-size: 0.6rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-date {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
}

.user-percent {
    padding: 4px 10px;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.85rem;
}
</style>
