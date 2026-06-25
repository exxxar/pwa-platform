<template>
    <div class="dialog-list">

        <!-- Поиск -->
        <div class="search-wrapper">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Поиск по чатам..."
                    class="search-input"
                >
                <button
                    v-if="searchQuery"
                    class="search-clear"
                    @click="searchQuery = ''"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="isLoading && !isHydrated" class="loading-state">
            <SkeletonLoader type="list" :count="6" />
        </div>

        <!-- Пустой список -->
        <div v-else-if="sortedDialogs.length === 0" class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-comments"></i>
            </div>
            <h4>Диалогов пока нет</h4>
            <p>Начните общение, написав кому-нибудь</p>
        </div>

        <!-- Список диалогов -->
        <div v-else class="dialogs-container">
            <div
                v-for="group in groupedDialogs"
                :key="group.key"
                class="dialog-group"
            >
                <div class="group-label">{{ group.label }}</div>

                <div
                    v-for="dialog in group.dialogs"
                    :key="dialog.id"
                    class="dialog-item"
                    :class="{
                        'is-active': isActive(dialog),
                        'is-unread': dialog.unread_count > 0,
                        'is-pinned': dialog.is_pinned
                    }"
                    @click="open(dialog)"
                >
                    <!-- Аватар -->
                    <div class="dialog-avatar" :style="getAvatarStyle(dialog)">
                        <img
                            v-if="getInterlocutor(dialog)?.avatar"
                            :src="getInterlocutor(dialog).avatar"
                            :alt="getInterlocutor(dialog)?.name"
                        >
                        <span v-else class="avatar-initials">
                            {{ getInitials(getInterlocutor(dialog)?.name) }}
                        </span>

                        <!-- Индикатор онлайн -->
                        <div
                            v-if="getInterlocutor(dialog)?.is_online"
                            class="online-dot"
                        ></div>

                        <!-- Бейдж непрочитанных -->
                        <div v-if="dialog.unread_count > 0" class="unread-badge">
                            {{ dialog.unread_count > 99 ? '99+' : dialog.unread_count }}
                        </div>

                        <!-- Закрепление -->
                        <div v-if="dialog.is_pinned" class="pin-icon">
                            <i class="fa-solid fa-thumbtack"></i>
                        </div>
                    </div>

                    <!-- Инфо -->
                    <div class="dialog-info">
                        <div class="dialog-header">
                            <span class="dialog-name">
                                {{ getInterlocutor(dialog)?.name || 'Без имени' }}
                            </span>
                            <span class="dialog-time">
                                {{ formatDialogTime(dialog.last_message_at) }}
                            </span>
                        </div>

                        <div class="dialog-preview">
                            <!-- Статус моего сообщения -->
                            <i
                                v-if="isMyLastMessage(dialog)"
                                class="message-status"
                                :class="getMessageStatusClass(dialog)"
                            ></i>

                            <span class="preview-text">
                                {{ getLastMessagePreview(dialog.last_message) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { useChat } from '@/MobileClient/Composables/useChat.js';
import SkeletonLoader from '@/MobileClient/Components/Common/SkeletonLoader.vue';
import {
    formatDialogTime,
    getInitials,
    getAvatarGradient,
    getInterlocutor,
    getLastMessagePreview,
} from '@/MobileClient/utils/chatUtils.js';

export default {
    name: "DialogList",

    components: { SkeletonLoader },

    emits: ['select-dialog'],

    setup() {
        const chat = useChat();
        return { ...chat };
    },

    data() {
        return {
            searchQuery: '',
            searchDebounce: null,
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        /**
         * Диалоги с учётом поиска (debounced)
         */
        filteredDialogs() {
            if (!this.searchQuery.trim()) return this.sortedDialogs;

            const query = this.searchQuery.toLowerCase();
            return this.sortedDialogs.filter(dialog => {
                const name = getInterlocutor(dialog)?.name?.toLowerCase() || '';
                const message = (dialog.last_message?.text || dialog.last_message?.message || '').toLowerCase();
                return name.includes(query) || message.includes(query);
            });
        },

        /**
         * Группировка диалогов по датам
         */
        groupedDialogs() {
            const groups = {
                pinned: { key: 'pinned', label: '📌 Закреплённые', dialogs: [], order: 0 },
                today: { key: 'today', label: 'Сегодня', dialogs: [], order: 1 },
                yesterday: { key: 'yesterday', label: 'Вчера', dialogs: [], order: 2 },
                week: { key: 'week', label: 'На этой неделе', dialogs: [], order: 3 },
                older: { key: 'older', label: 'Ранее', dialogs: [], order: 4 },
            };

            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);

            this.filteredDialogs.forEach(dialog => {
                if (dialog.is_pinned) {
                    groups.pinned.dialogs.push(dialog);
                    return;
                }

                const messageDate = new Date(dialog.last_message_at || dialog.updated_at || 0);
                const messageDay = new Date(
                    messageDate.getFullYear(),
                    messageDate.getMonth(),
                    messageDate.getDate()
                );

                if (messageDay.getTime() === today.getTime()) {
                    groups.today.dialogs.push(dialog);
                } else if (messageDay.getTime() === yesterday.getTime()) {
                    groups.yesterday.dialogs.push(dialog);
                } else if (messageDay > weekAgo) {
                    groups.week.dialogs.push(dialog);
                } else {
                    groups.older.dialogs.push(dialog);
                }
            });

            return Object.values(groups)
                .filter(group => group.dialogs.length > 0)
                .sort((a, b) => a.order - b.order);
        },
    },

    async mounted() {
        await this.loadDialogs();
    },

    methods: {
        isActive(dialog) {
            return this.currentDialog?.id === dialog.id;
        },

        async open(dialog) {
            if (this.isActive(dialog)) return;

            try {
                await this.openDialog(dialog.id);
                this.$emit('select-dialog', dialog);
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось открыть диалог',
                    type: 'error',
                });
            }
        },

        getInterlocutor(dialog) {
            return getInterlocutor(dialog);
        },

        getAvatarStyle(dialog) {
            const interlocutor = getInterlocutor(dialog);
            if (interlocutor?.avatar) return {};
            return getAvatarGradient(dialog.id);
        },

        getInitials(name) {
            return getInitials(name);
        },

        formatDialogTime(timestamp) {
            return formatDialogTime(timestamp);
        },

        getLastMessagePreview(message) {
            return getLastMessagePreview(message);
        },

        isMyLastMessage(dialog) {
            if (!dialog.last_message || !this.self) return false;
            return (
                dialog.last_message.is_mine === true ||
                dialog.last_message.meta?.user_id === this.self.id
            );
        },

        getMessageStatusClass(dialog) {
            const status = dialog.last_message?.status;
            if (status === 'read') return 'fa-solid fa-check-double status-read';
            if (status === 'delivered') return 'fa-solid fa-check-double status-delivered';
            return 'fa-solid fa-check status-sent';
        },
    },
};
</script>

<style lang="scss" scoped>
.dialog-list {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: var(--bs-body-bg);
}

// Поиск
.search-wrapper {
    padding: 12px;
    border-bottom: 1px solid var(--bs-border-color);
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 14px;
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
}

.search-input {
    width: 100%;
    padding: 10px 40px 10px 40px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    font-size: 0.9rem;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: var(--bs-primary);
        background: var(--bs-body-bg);
    }
}

.search-clear {
    position: absolute;
    right: 10px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bs-border-color);
    border: none;
    color: var(--bs-body-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}

// Состояния
.loading-state,
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 16px;
}

.empty-state h4 {
    margin: 0 0 8px;
    font-weight: 700;
}

.empty-state p {
    margin: 0;
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
}

// Список диалогов
.dialogs-container {
    flex: 1;
    overflow-y: auto;
}

.dialog-group {
    margin-bottom: 8px;
}

.group-label {
    padding: 8px 16px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: var(--bs-secondary-bg);
}

.dialog-item {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.2s;
    border-bottom: 1px solid var(--bs-border-color-translucent);

    &:hover {
        background: rgba(var(--bs-primary-rgb), 0.03);
    }

    &.is-active {
        background: rgba(var(--bs-primary-rgb), 0.08);
    }

    &.is-unread {
        .dialog-name {
            font-weight: 700;
        }
        .preview-text {
            color: var(--bs-body-color);
            font-weight: 600;
        }
    }
}

// Аватар
.dialog-avatar {
    position: relative;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
    overflow: visible;

    img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
}

.avatar-initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #22c55e;
    border: 2px solid var(--bs-body-bg);
}

.unread-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: var(--bs-danger);
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--bs-body-bg);
}

.pin-icon {
    position: absolute;
    top: -4px;
    left: -4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    border: 2px solid var(--bs-body-bg);
}

// Инфо
.dialog-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
}

.dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 8px;
}

.dialog-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--bs-body-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dialog-time {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
    flex-shrink: 0;
}

.dialog-preview {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
}

.preview-text {
    color: var(--bs-secondary-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.message-status {
    font-size: 0.75rem;
    flex-shrink: 0;

    &.status-read { color: var(--bs-primary); }
    &.status-delivered { color: var(--bs-body-color); }
    &.status-sent { color: var(--bs-secondary-color); }
}
</style>
