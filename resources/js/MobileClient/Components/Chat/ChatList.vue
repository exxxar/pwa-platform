<template>
    <div class="chat-list-container">

        <!-- ========================================== -->
        <!-- ШАПКА С ПОИСКОМ -->
        <!-- ========================================== -->
        <div class="chat-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <div>
                    <h5 class="header-title">Чаты</h5>
                    <p class="header-subtitle">
                        {{ dialogs.length > 0
                        ? `${dialogs.length} ${pluralize(dialogs.length, 'диалог', 'диалога', 'диалогов')}`
                        : 'Нет активных диалогов' }}
                    </p>
                </div>
            </div>

            <!-- Поиск -->
            <div class="search-wrapper">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="search-input"
                        placeholder="Поиск по чатам..."
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
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ЧАТОВ -->
        <!-- ========================================== -->
        <div class="chat-list">

            <!-- Skeleton загрузка -->
            <div v-if="isLoading" class="skeleton-list">
                <div v-for="i in 5" :key="i" class="skeleton-item">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-line w-60"></div>
                        <div class="skeleton-line w-80"></div>
                    </div>
                    <div class="skeleton-badge"></div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else-if="filteredDialogs.length === 0" class="empty-state">
                <div class="empty-icon-wrapper">
                    <div class="empty-icon">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <div class="empty-decoration decoration-1"></div>
                    <div class="empty-decoration decoration-2"></div>
                </div>
                <h5 class="empty-title">
                    {{ searchQuery ? 'Ничего не найдено' : 'Нет чатов' }}
                </h5>
                <p class="empty-text">
                    {{ searchQuery
                    ? `По запросу "${searchQuery}" чатов не найдено`
                    : 'Начните общение — ваши диалоги появятся здесь' }}
                </p>
                <button v-if="searchQuery" class="empty-btn" @click="searchQuery = ''">
                    <i class="fa-solid fa-xmark me-2"></i>
                    Сбросить поиск
                </button>
            </div>

            <!-- Группированный список -->
            <div v-else class="grouped-list">
                <div
                    v-for="(group, groupKey) in groupedDialogs"
                    :key="groupKey"
                    class="chat-group"
                >
                    <!-- Заголовок группы -->
                    <div class="group-header">
                        <span class="group-label">{{ group.label }}</span>
                        <span class="group-count">{{ group.dialogs.length }}</span>
                    </div>

                    <!-- Карточки чатов -->
                    <div
                        v-for="dialog in group.dialogs"
                        :key="dialog.id"
                        class="chat-card"
                        :class="{
                            'is-active': isActive(dialog),
                            'is-unread': dialog.unread_count > 0,
                            'is-pinned': dialog.is_pinned
                        }"
                        @click="open(dialog)"
                    >
                        <!-- Аватар -->
                        <div class="chat-avatar">
                            <div class="avatar-image">
                                <img v-if="dialog.avatar" :src="dialog.avatar" :alt="dialog.title">
                                <div v-else class="avatar-initials" :style="getAvatarGradient(dialog.id)">
                                    {{ getInitials(dialog.title) }}
                                </div>
                            </div>

                            <!-- Онлайн индикатор -->
                            <div v-if="dialog.is_online" class="online-indicator"></div>

                            <!-- Иконка закрепления -->
                            <div v-if="dialog.is_pinned" class="pin-indicator">
                                <i class="fa-solid fa-thumbtack"></i>
                            </div>
                        </div>

                        <!-- Контент -->
                        <div class="chat-content">
                            <!-- Верхний ряд -->
                            <div class="chat-top-row">
                                <div class="chat-name">
                                    {{ dialog.title }}
                                </div>
                                <div class="chat-time">
                                    {{ formatTime(dialog.last_message_at) }}
                                </div>
                            </div>

                            <!-- Нижний ряд -->
                            <div class="chat-bottom-row">
                                <div class="chat-preview">
                                    <!-- Статус сообщения -->
                                    <i
                                        v-if="isMyLastMessage(dialog)"
                                        class="message-status"
                                        :class="getMessageStatusClass(dialog)"
                                    ></i>

                                    <!-- Префикс -->
                                    <span v-if="isMyLastMessage(dialog)" class="preview-prefix">Вы: </span>

                                    <!-- Текст -->
                                    <span class="preview-text">
                                        {{ getLastMessageText(dialog) }}
                                    </span>
                                </div>

                                <!-- Бейдж непрочитанных -->
                                <div v-if="dialog.unread_count > 0" class="unread-badge">
                                    {{ dialog.unread_count > 99 ? '99+' : dialog.unread_count }}
                                </div>

                                <!-- Индикатор "печатает..." -->
                                <div v-else-if="dialog.is_typing" class="typing-indicator">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
import { useChatStore } from '@/MobileClient/stores/Shop/chat.js';

export default {
    name: "DialogList",

    setup() {
        const store = useChatStore();
        return { store };
    },

    data() {
        return {
            isLoading: false,
            searchQuery: '',
        };
    },

    computed: {
        dialogs() {
            return this.store.getDialogs || [];
        },

        currentDialog() {
            return this.store.getCurrentDialog;
        },

        self() {
            return window.TenantUser || null;
        },

        // Фильтрация по поиску
        filteredDialogs() {
            if (!this.searchQuery.trim()) return this.dialogs;

            const query = this.searchQuery.toLowerCase();
            return this.dialogs.filter(dialog =>
                dialog.title?.toLowerCase().includes(query) ||
                dialog.last_message?.message?.toLowerCase().includes(query)
            );
        },

        // Группировка по датам
        groupedDialogs() {
            const groups = {
                pinned: { label: 'Закреплённые', dialogs: [], order: 0 },
                today: { label: 'Сегодня', dialogs: [], order: 1 },
                yesterday: { label: 'Вчера', dialogs: [], order: 2 },
                week: { label: 'На этой неделе', dialogs: [], order: 3 },
                older: { label: 'Ранее', dialogs: [], order: 4 },
            };

            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);

            this.filteredDialogs.forEach(dialog => {
                // Закреплённые — отдельно
                if (dialog.is_pinned) {
                    groups.pinned.dialogs.push(dialog);
                    return;
                }

                const messageDate = new Date(dialog.last_message_at);
                const messageDay = new Date(messageDate.getFullYear(), messageDate.getMonth(), messageDate.getDate());

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

            // Фильтруем пустые группы и сортируем
            return Object.entries(groups)
                .filter(([_, group]) => group.dialogs.length > 0)
                .sort((a, b) => a[1].order - b[1].order)
                .reduce((acc, [key, group]) => {
                    acc[key] = group;
                    return acc;
                }, {});
        },
    },

    async mounted() {
        this.isLoading = true;
        try {
            await this.store.loadDialogs();
        } catch (error) {
            console.error('Ошибка загрузки чатов:', error);
        } finally {
            this.isLoading = false;
        }
    },

    methods: {
        isActive(dialog) {
            return this.currentDialog?.id === dialog.id;
        },

        async open(dialog) {
            if (this.isActive(dialog)) return;

            this.store.setCurrentDialog(dialog);

            try {
                await this.store.loadMessages(dialog.id);
                this.$emit('select-dialog', dialog);
            } catch (error) {
                console.error('Ошибка открытия чата:', error);
            }
        },

        // Форматирование времени
        formatTime(timestamp) {
            if (!timestamp) return '';

            const date = new Date(timestamp);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            const messageDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());

            // Сегодня — время
            if (messageDay.getTime() === today.getTime()) {
                return date.toLocaleTimeString('ru-RU', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            // Вчера
            if (messageDay.getTime() === yesterday.getTime()) {
                return 'Вчера';
            }

            // На этой неделе — день недели
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            if (messageDay > weekAgo) {
                return date.toLocaleDateString('ru-RU', { weekday: 'short' });
            }

            // Раньше — дата
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short'
            });
        },

        // Последнее сообщение от меня?
        isMyLastMessage(dialog) {
            return dialog.last_message?.meta?.user_id === this.self?.id;
        },

        // Статус последнего сообщения
        getMessageStatusClass(dialog) {
            const status = dialog.last_message?.status;
            if (status === 'read') return 'status-read';
            if (status === 'delivered') return 'status-delivered';
            return 'status-sent';
        },

        // Текст последнего сообщения
        getLastMessageText(dialog) {
            if (!dialog.last_message) return 'Нет сообщений';

            const msg = dialog.last_message;

            // Если это изображение
            if (msg.type === 'image') return '📷 Фото';
            if (msg.type === 'file') return '📎 Файл';
            if (msg.type === 'voice') return '🎤 Голосовое сообщение';

            return msg.message || 'Вложение';
        },

        // Инициалы для аватара
        getInitials(name) {
            if (!name) return '?';
            const words = name.trim().split(/\s+/);
            if (words.length >= 2) {
                return (words[0][0] + words[1][0]).toUpperCase();
            }
            return name.slice(0, 2).toUpperCase();
        },

        // Градиент для аватара (на основе ID)
        getAvatarGradient(id) {
            const gradients = [
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
            ];
            const index = (id || 0) % gradients.length;
            return { background: gradients[index] };
        },

        // Склонение слов
        pluralize(count, one, two, five) {
            const n = Math.abs(count) % 100;
            const n1 = n % 10;
            if (n > 10 && n < 20) return five;
            if (n1 > 1 && n1 < 5) return two;
            if (n1 === 1) return one;
            return five;
        },
    },
};
</script>

<style scoped>
.chat-list-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: var(--bs-body-bg);
}

/* ==========================================
   ШАПКА
   ========================================== */
.chat-header {
    padding: 16px;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
    position: sticky;
    top: 0;
    z-index: 10;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.header-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--bs-body-color);
}

.header-subtitle {
    margin: 0;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
}

/* Поиск */
.search-wrapper {
    margin-top: 4px;
}

.search-box {
    display: flex;
    align-items: center;
    background: var(--bs-secondary-bg, #f5f5f5);
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 0 14px;
    transition: all 0.2s ease;
}

.search-box:focus-within {
    border-color: var(--bs-primary);
    background: var(--bs-body-bg);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
}

.search-icon {
    color: var(--bs-secondary-color);
    font-size: 0.9rem;
    margin-right: 10px;
}

.search-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px 0;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    outline: none;
}

.search-input::placeholder {
    color: var(--bs-secondary-color);
}

.search-clear {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--bs-border-color);
    border: none;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-clear:hover {
    background: var(--bs-primary);
    color: white;
}

/* ==========================================
   СПИСОК ЧАТОВ
   ========================================== */
.chat-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
    -webkit-overflow-scrolling: touch;
}

.chat-list::-webkit-scrollbar {
    width: 4px;
}

.chat-list::-webkit-scrollbar-thumb {
    background: var(--bs-border-color);
    border-radius: 2px;
}

/* ==========================================
   SKELETON ЗАГРУЗКА
   ========================================== */
.skeleton-list {
    padding: 8px 16px;
}

.skeleton-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

.skeleton-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(90deg,
    var(--bs-secondary-bg) 0%,
    var(--bs-border-color) 50%,
    var(--bs-secondary-bg) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-line {
    height: 12px;
    border-radius: 6px;
    background: linear-gradient(90deg,
    var(--bs-secondary-bg) 0%,
    var(--bs-border-color) 50%,
    var(--bs-secondary-bg) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-line.w-60 { width: 60%; }
.skeleton-line.w-80 { width: 80%; }

.skeleton-badge {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: linear-gradient(90deg,
    var(--bs-secondary-bg) 0%,
    var(--bs-border-color) 50%,
    var(--bs-secondary-bg) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ==========================================
   ПУСТОЕ СОСТОЯНИЕ
   ========================================== */
.empty-state {
    text-align: center;
    padding: 60px 24px;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.empty-icon-wrapper {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto 24px;
}

.empty-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    position: relative;
    z-index: 1;
}

.empty-decoration {
    position: absolute;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.15);
}

.decoration-1 {
    width: 30px;
    height: 30px;
    top: -10px;
    right: -10px;
    animation: float 3s ease-in-out infinite;
}

.decoration-2 {
    width: 20px;
    height: 20px;
    bottom: -5px;
    left: -5px;
    animation: float 3s ease-in-out infinite 1s;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.empty-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: var(--bs-body-color);
}

.empty-text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-bottom: 24px;
    line-height: 1.5;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    background: var(--bs-primary);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

/* ==========================================
   ГРУППЫ ЧАТОВ
   ========================================== */
.grouped-list {
    padding: 0 8px;
}

.chat-group {
    margin-bottom: 16px;
}

.group-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    margin-bottom: 4px;
}

.group-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.group-count {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    opacity: 0.7;
}

/* ==========================================
   КАРТОЧКА ЧАТА
   ========================================== */
.chat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    background: var(--bs-body-bg);
    border-radius: 14px;
    margin-bottom: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.chat-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 0;
    background: var(--bs-primary);
    transition: width 0.2s ease;
}

.chat-card:hover {
    background: rgba(var(--bs-primary-rgb), 0.03);
}

.chat-card:hover::before {
    width: 3px;
}

.chat-card:active {
    transform: scale(0.98);
}

/* Активный чат */
.chat-card.is-active {
    background: rgba(var(--bs-primary-rgb), 0.08);
}

.chat-card.is-active::before {
    width: 4px;
}

/* Непрочитанный чат */
.chat-card.is-unread .chat-name {
    font-weight: 700;
}

.chat-card.is-unread .preview-text {
    color: var(--bs-body-color);
    font-weight: 500;
}

/* Закреплённый чат */
.chat-card.is-pinned {
    background: rgba(var(--bs-primary-rgb), 0.02);
}

/* ==========================================
   АВАТАР
   ========================================== */
.chat-avatar {
    position: relative;
    flex-shrink: 0;
}

.avatar-image {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--bs-secondary-bg);
}

.avatar-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: 1px;
}

/* Онлайн индикатор */
.online-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #22c55e;
    border: 3px solid var(--bs-body-bg);
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.5);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 8px rgba(34, 197, 94, 0.5); }
    50% { box-shadow: 0 0 16px rgba(34, 197, 94, 0.8); }
}

/* Иконка закрепления */
.pin-indicator {
    position: absolute;
    top: -2px;
    right: -2px;
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

/* ==========================================
   КОНТЕНТ ЧАТА
   ========================================== */
.chat-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.chat-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.chat-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--bs-body-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.chat-time {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    white-space: nowrap;
    flex-shrink: 0;
}

.chat-bottom-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.chat-preview {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

/* Статус сообщения */
.message-status {
    font-size: 0.75rem;
    flex-shrink: 0;
}

.message-status::before {
    content: '✓';
}

.message-status.status-sent {
    color: var(--bs-secondary-color);
}

.message-status.status-delivered {
    color: var(--bs-secondary-color);
}

.message-status.status-delivered::before {
    content: '✓✓';
}

.message-status.status-read {
    color: var(--bs-primary);
}

.message-status.status-read::before {
    content: '✓✓';
}

.preview-prefix {
    color: var(--bs-secondary-color);
    flex-shrink: 0;
}

.preview-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ==========================================
   БЕЙДЖ НЕПРОЧИТАННЫХ
   ========================================== */
.unread-badge {
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    background: var(--bs-primary);
    color: white;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    flex-shrink: 0;
    animation: badgePop 0.3s ease;
}

@keyframes badgePop {
    0% { transform: scale(0); }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* ==========================================
   ИНДИКАТОР "ПЕЧАТАЕТ..."
   ========================================== */
.typing-indicator {
    display: flex;
    align-items: center;
    gap: 3px;
    padding: 4px 8px;
    background: var(--bs-secondary-bg);
    border-radius: 12px;
}

.typing-indicator span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--bs-secondary-color);
    animation: typing 1.4s ease-in-out infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-4px);
        opacity: 1;
    }
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .chat-header {
        padding: 12px;
    }

    .header-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .header-title {
        font-size: 1.1rem;
    }

    .chat-card {
        padding: 10px 12px;
        gap: 12px;
    }

    .avatar-image {
        width: 48px;
        height: 48px;
    }

    .chat-name {
        font-size: 0.9rem;
    }

    .chat-preview {
        font-size: 0.8rem;
    }
}
</style>
