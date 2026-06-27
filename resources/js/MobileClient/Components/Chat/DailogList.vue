<template>
    <div class="dialog-list">

        <!-- ========================================== -->
        <!-- ТАБЫ: ВСЕ / АРХИВ -->
        <!-- ========================================== -->
        <div class="tabs-wrapper">
            <button
                class="tab-btn"
                :class="{ active: activeTab === 'all' }"
                @click="activeTab = 'all'"
            >
                <i class="fa-solid fa-comments"></i>
                <span>Все</span>
                <span v-if="activeDialogsCount > 0" class="tab-badge">
                    {{ activeDialogsCount }}
                </span>
            </button>
            <button
                class="tab-btn"
                :class="{ active: activeTab === 'archived' }"
                @click="activeTab = 'archived'"
            >
                <i class="fa-solid fa-box-archive"></i>
                <span>Архив</span>
                <span v-if="archivedDialogsCount > 0" class="tab-badge muted">
                    {{ archivedDialogsCount }}
                </span>
            </button>

            <!-- Кнопка режима выбора -->
            <button
                v-if="!isEditMode && currentTabDialogs.length > 0"
                class="select-mode-btn"
                @click="enterEditMode"
            >
                <i class="fa-solid fa-check-double"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ РЕЖИМА ВЫБОРА -->
        <!-- ========================================== -->
        <transition name="slide-down">
            <div v-if="isEditMode" class="selection-panel">
                <div class="selection-left">
                    <label class="select-all-checkbox">
                        <input
                            type="checkbox"
                            :checked="isAllSelected"
                            :indeterminate="isIndeterminate"
                            @change="toggleSelectAll"
                        >
                        <span>Выбрать все</span>
                    </label>
                    <span class="selection-count">
                        Выбрано: <strong>{{ selectedDialogs.length }}</strong>
                    </span>
                </div>
                <div class="selection-actions">
                    <button
                        v-if="activeTab === 'all'"
                        class="action-btn archive"
                        @click="archiveSelected"
                        :disabled="selectedDialogs.length === 0"
                        title="Архивировать"
                    >
                        <i class="fa-solid fa-box-archive"></i>
                    </button>
                    <button
                        v-if="activeTab === 'archived'"
                        class="action-btn restore"
                        @click="restoreSelected"
                        :disabled="selectedDialogs.length === 0"
                        title="Восстановить"
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                    <button
                        class="action-btn delete"
                        @click="deleteSelected"
                        :disabled="selectedDialogs.length === 0"
                        title="Удалить навсегда"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <button
                        class="action-btn cancel"
                        @click="exitEditMode"
                        title="Отмена"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- ПАНЕЛЬ ДЕЙСТВИЙ С АРХИВОМ -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'archived' && archivedDialogsCount > 0" class="archive-actions">
            <button class="archive-action-btn" @click="emptyArchiveConfirm">
                <i class="fa-solid fa-trash-can"></i>
                Очистить весь архив
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ПОИСК -->
        <!-- ========================================== -->
        <div class="search-wrapper">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="activeTab === 'all' ? 'Поиск по чатам...' : 'Поиск в архиве...'"
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

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА -->
        <!-- ========================================== -->
        <div v-if="isLoading && !isHydrated" class="loading-state">
            <SkeletonLoader type="list" :count="6" />
        </div>

        <!-- ========================================== -->
        <!-- ПУСТОЙ СПИСОК -->
        <!-- ========================================== -->
        <div v-else-if="currentTabDialogs.length === 0" class="empty-state">
            <div class="empty-icon">
                <i :class="activeTab === 'all' ? 'fa-solid fa-comments' : 'fa-solid fa-box-archive'"></i>
            </div>
            <h4 v-if="activeTab === 'all'">Диалогов пока нет</h4>
            <h4 v-else>Архив пуст</h4>
            <p v-if="activeTab === 'all'">Начните общение, написав кому-нибудь</p>
            <p v-else>Архивированные диалоги будут здесь</p>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ДИАЛОГОВ -->
        <!-- ========================================== -->
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
                        'is-unread': dialog.unread_count > 0 && activeTab === 'all',
                        'is-pinned': dialog.is_pinned,
                        'is-selected': isSelected(dialog),
                        'is-archived': dialog.is_archived,
                        'is-edit-mode': isEditMode,
                    }"
                    @click="handleDialogClick(dialog)"
                    @contextmenu.prevent="showContextMenu($event, dialog)"
                >
                    <!-- Чекбокс в режиме выбора -->
                    <label v-if="isEditMode" class="dialog-checkbox" @click.stop>
                        <input
                            type="checkbox"
                            :checked="isSelected(dialog)"
                            @change="toggleSelect(dialog)"
                        >
                        <span class="checkbox-visual">
                            <i class="fa-solid fa-check"></i>
                        </span>
                    </label>

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

                        <div
                            v-if="getInterlocutor(dialog)?.is_online && activeTab === 'all'"
                            class="online-dot"
                        ></div>

                        <div
                            v-if="dialog.unread_count > 0 && activeTab === 'all'"
                            class="unread-badge"
                        >
                            {{ dialog.unread_count > 99 ? '99+' : dialog.unread_count }}
                        </div>

                        <div v-if="dialog.is_pinned && activeTab === 'all'" class="pin-icon">
                            <i class="fa-solid fa-thumbtack"></i>
                        </div>

                        <!-- Иконка архива -->
                        <div v-if="dialog.is_archived" class="archive-icon">
                            <i class="fa-solid fa-box-archive"></i>
                        </div>
                    </div>

                    <!-- Инфо -->
                    <div class="dialog-info">
                        <div class="dialog-header">
                            <span class="dialog-name">
                                {{ dialog?.title || 'Без имени' }}
                            </span>
                            <span class="dialog-time">
                                {{ formatDialogTime(dialog.last_message_at) }}
                            </span>
                        </div>

                        <div class="dialog-preview">
                            <i
                                v-if="isMyLastMessage(dialog) && activeTab === 'all'"
                                class="message-status"
                                :class="getMessageStatusClass(dialog)"
                            ></i>

                            <span class="preview-text">
                                {{ getLastMessagePreview(dialog.last_message) }}
                            </span>
                        </div>
                    </div>

                    <!-- 🆕 КНОПКА ДЕЙСТВИЙ (три точки) -->
                    <button
                        v-if="!isEditMode"
                        class="dialog-menu-btn"
                        @click.stop="showContextMenu($event, dialog)"
                    >
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОНТЕКСТНОЕ МЕНЮ -->
        <!-- ========================================== -->
        <teleport to="body">
            <transition name="fade">
                <div
                    v-if="contextMenu.visible"
                    class="context-menu-overlay"
                    @click="closeContextMenu"
                    @contextmenu.prevent="closeContextMenu"
                >
                    <div
                        class="context-menu"
                        :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
                        @click.stop
                    >
                        <div class="context-menu-header">
                            <div class="context-menu-avatar" :style="getAvatarStyle(contextMenu.dialog)">
                                <img
                                    v-if="getInterlocutor(contextMenu.dialog)?.avatar"
                                    :src="getInterlocutor(contextMenu.dialog).avatar"
                                >
                                <span v-else class="avatar-initials">
                                    {{ getInitials(getInterlocutor(contextMenu.dialog)?.name) }}
                                </span>
                            </div>
                            <div class="context-menu-info">
                                <strong>{{ getInterlocutor(contextMenu.dialog)?.name }}</strong>
                                <span>{{ contextMenu.messagesCount || 0 }} сообщений</span>
                            </div>
                        </div>

                        <div class="context-menu-divider"></div>

                        <button class="context-menu-item" @click="viewAttachments(contextMenu.dialog)">
                            <i class="fa-solid fa-paperclip"></i>
                            <span>Вложения</span>
                            <span v-if="contextMenu.attachmentsCount" class="menu-badge">
                                {{ contextMenu.attachmentsCount }}
                            </span>
                        </button>

                        <button
                            v-if="activeTab === 'all'"
                            class="context-menu-item"
                            @click="togglePin(contextMenu.dialog)"
                        >
                            <i :class="contextMenu.dialog?.is_pinned ? 'fa-solid fa-thumbtack-slash' : 'fa-solid fa-thumbtack'"></i>
                            <span>{{ contextMenu.dialog?.is_pinned ? 'Открепить' : 'Закрепить' }}</span>
                        </button>

                        <button
                            v-if="activeTab === 'all'"
                            class="context-menu-item"
                            @click="markAsRead(contextMenu.dialog)"
                        >
                            <i class="fa-solid fa-check-double"></i>
                            <span>Прочитано</span>
                        </button>

                        <div class="context-menu-divider"></div>

                        <button
                            v-if="activeTab === 'all'"
                            class="context-menu-item archive"
                            @click="archiveSingle(contextMenu.dialog)"
                        >
                            <i class="fa-solid fa-box-archive"></i>
                            <span>В архив</span>
                        </button>

                        <button
                            v-if="activeTab === 'archived'"
                            class="context-menu-item restore"
                            @click="restoreSingle(contextMenu.dialog)"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Восстановить</span>
                        </button>

                        <button class="context-menu-item delete" @click="deleteSingle(contextMenu.dialog)">
                            <i class="fa-solid fa-trash"></i>
                            <span>Удалить навсегда</span>
                        </button>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ========================================== -->
        <!-- МОДАЛКА ВЛОЖЕНИЙ -->
        <!-- ========================================== -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showAttachmentsModal" class="modal-overlay" @click.self="showAttachmentsModal = false">
                    <div class="attachments-modal">
                        <div class="modal-header">
                            <h3>
                                <i class="fa-solid fa-paperclip"></i>
                                Вложения
                            </h3>
                            <button class="modal-close" @click="showAttachmentsModal = false">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div v-if="isLoadingAttachments" class="attachments-loading">
                            <div class="loader-spinner"></div>
                        </div>

                        <div v-else-if="attachments.length === 0" class="attachments-empty">
                            <i class="fa-solid fa-inbox"></i>
                            <p>Вложений нет</p>
                        </div>

                        <div v-else class="attachments-grid">
                            <a
                                v-for="attachment in attachments"
                                :key="attachment.id"
                                :href="attachment.url"
                                target="_blank"
                                class="attachment-item"
                                :class="[`type-${attachment.type}`]"
                            >
                                <div class="attachment-icon-large">
                                    <i :class="getAttachmentIcon(attachment.type)"></i>
                                </div>
                                <div class="attachment-info">
                                    <div class="attachment-name">{{ attachment.name }}</div>
                                    <div class="attachment-meta">
                                        <span>{{ attachment.size_formatted }}</span>
                                        <span>{{ attachment.date_formatted }}</span>
                                    </div>
                                </div>
                                <i class="fa-solid fa-download attachment-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ========================================== -->
        <!-- МОДАЛКА ПОДТВЕРЖДЕНИЯ -->
        <!-- ========================================== -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="confirmModal.visible" class="modal-overlay" @click.self="cancelConfirm">
                    <div class="confirm-modal">
                        <div class="confirm-icon" :class="confirmModal.type">
                            <i :class="confirmModal.icon"></i>
                        </div>
                        <h3>{{ confirmModal.title }}</h3>
                        <p>{{ confirmModal.message }}</p>
                        <div class="confirm-actions">
                            <button class="confirm-btn cancel" @click="cancelConfirm">
                                Отмена
                            </button>
                            <button
                                class="confirm-btn"
                                :class="confirmModal.type"
                                @click="executeConfirm"
                            >
                                {{ confirmModal.confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

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
            activeTab: 'all', // 'all' | 'archived'
            isEditMode: false,
            selectedDialogs: [],

            // Контекстное меню
            contextMenu: {
                visible: false,
                x: 0,
                y: 0,
                dialog: null,
                messagesCount: 0,
                attachmentsCount: 0,
            },

            // Модалка вложений
            showAttachmentsModal: false,
            attachments: [],
            isLoadingAttachments: false,

            // Модалка подтверждения
            confirmModal: {
                visible: false,
                type: 'warning',
                icon: 'fa-solid fa-triangle-exclamation',
                title: '',
                message: '',
                confirmText: 'Подтвердить',
                callback: null,
            },

            longPressTimer: null,
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        /**
         * Активные (не архивные) диалоги
         */
        activeDialogs() {
            return (this.sortedDialogs || []).filter(d => !d.is_archived);
        },

        /**
         * Архивные диалоги
         */
        archivedDialogs() {
            return (this.sortedDialogs || []).filter(d => d.is_archived);
        },

        activeDialogsCount() {
            return this.activeDialogs.length;
        },

        archivedDialogsCount() {
            return this.archivedDialogs.length;
        },

        /**
         * Диалоги текущей вкладки
         */
        currentTabDialogs() {
            const dialogs = this.activeTab === 'all' ? this.activeDialogs : this.archivedDialogs;

            if (!this.searchQuery.trim()) return dialogs;

            const query = this.searchQuery.toLowerCase();
            return dialogs.filter(dialog => {
                const name = getInterlocutor(dialog)?.name?.toLowerCase() || '';
                const message = (dialog.last_message?.text || dialog.last_message?.message || '').toLowerCase();
                return name.includes(query) || message.includes(query);
            });
        },

        /**
         * Группировка по датам
         */
        groupedDialogs() {
            if (this.activeTab === 'archived') {
                return [{ key: 'archived', label: '📦 Архив', dialogs: this.currentTabDialogs }];
            }

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

            this.currentTabDialogs.forEach(dialog => {
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

        isAllSelected() {
            return this.currentTabDialogs.length > 0 &&
                this.selectedDialogs.length === this.currentTabDialogs.length;
        },

        isIndeterminate() {
            return this.selectedDialogs.length > 0 &&
                this.selectedDialogs.length < this.currentTabDialogs.length;
        },
    },

    watch: {
        activeTab() {
            this.exitEditMode();
        },
    },

    async mounted() {
        await this.loadDialogs();
    },

    beforeUnmount() {
        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
        }
    },

    methods: {
        // ==========================================
        // БАЗОВЫЕ МЕТОДЫ
        // ==========================================

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

        handleDialogClick(dialog) {
            if (this.isEditMode) {
                this.toggleSelect(dialog);
            } else {
                this.open(dialog);
            }
        },

        // ==========================================
        // 🆕 РЕЖИМ ВЫБОРА
        // ==========================================

        enterEditMode() {
            this.isEditMode = true;
            this.selectedDialogs = [];
        },

        exitEditMode() {
            this.isEditMode = false;
            this.selectedDialogs = [];
        },

        isSelected(dialog) {
            return this.selectedDialogs.includes(dialog.id);
        },

        toggleSelect(dialog) {
            const index = this.selectedDialogs.indexOf(dialog.id);
            if (index === -1) {
                this.selectedDialogs.push(dialog.id);
            } else {
                this.selectedDialogs.splice(index, 1);
            }
        },

        toggleSelectAll() {
            if (this.isAllSelected) {
                this.selectedDialogs = [];
            } else {
                this.selectedDialogs = this.currentTabDialogs.map(d => d.id);
            }
        },

        // ==========================================
        // 🆕 ДЕЙСТВИЯ С ВЫБРАННЫМИ
        // ==========================================

        archiveSelected() {
            if (this.selectedDialogs.length === 0) return;

            const count = this.selectedDialogs.length;
            this.showConfirm({
                type: 'warning',
                icon: 'fa-solid fa-box-archive',
                title: 'Архивировать чаты?',
                message: `${count} ${this.pluralize(count, 'чат будет', 'чата будут', 'чатов будут')} перемещен${count === 1 ? '' : 'ы'} в архив.`,
                confirmText: 'В архив',
                callback: async () => {
                    try {
                        await this.archiveMultiple(this.selectedDialogs);
                        this.$notify?.({
                            title: 'Успешно',
                            text: `Архивировано: ${count}`,
                            type: 'success',
                        });
                        this.exitEditMode();
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            text: 'Не удалось архивировать',
                            type: 'error',
                        });
                    }
                },
            });
        },

        restoreSelected() {
            if (this.selectedDialogs.length === 0) return;

            const count = this.selectedDialogs.length;
            this.showConfirm({
                type: 'success',
                icon: 'fa-solid fa-rotate-left',
                title: 'Восстановить чаты?',
                message: `${count} ${this.pluralize(count, 'чат будет', 'чата будут', 'чатов будут')} возвращен${count === 1 ? '' : 'ы'} из архива.`,
                confirmText: 'Восстановить',
                callback: async () => {
                    try {
                        for (const id of this.selectedDialogs) {
                            await this.restoreDialog(id);
                        }
                        this.$notify?.({
                            title: 'Успешно',
                            text: `Восстановлено: ${count}`,
                            type: 'success',
                        });
                        this.exitEditMode();
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            text: 'Не удалось восстановить',
                            type: 'error',
                        });
                    }
                },
            });
        },

        deleteSelected() {
            if (this.selectedDialogs.length === 0) return;

            const count = this.selectedDialogs.length;
            this.showConfirm({
                type: 'danger',
                icon: 'fa-solid fa-trash',
                title: 'Удалить чаты навсегда?',
                message: `${count} ${this.pluralize(count, 'чат будет', 'чата будут', 'чатов будут')} удален${count === 1 ? '' : 'ы'} безвозвратно. Это действие нельзя отменить.`,
                confirmText: 'Удалить',
                callback: async () => {
                    try {
                        for (const id of this.selectedDialogs) {
                            await this.deleteDialogPermanently(id);
                        }
                        this.$notify?.({
                            title: 'Удалено',
                            text: `Удалено чатов: ${count}`,
                            type: 'success',
                        });
                        this.exitEditMode();
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            text: 'Не удалось удалить',
                            type: 'error',
                        });
                    }
                },
            });
        },

        // ==========================================
        // 🆕 ОДИНОЧНЫЕ ДЕЙСТВИЯ
        // ==========================================

        archiveSingle(dialog) {
            this.closeContextMenu();
            this.showConfirm({
                type: 'warning',
                icon: 'fa-solid fa-box-archive',
                title: 'Архивировать чат?',
                message: `Чат с ${getInterlocutor(dialog)?.name} будет перемещен в архив.`,
                confirmText: 'В архив',
                callback: async () => {
                    try {
                        await this.archiveDialog(dialog.id);
                        this.$notify?.({
                            title: 'В архиве',
                            text: `Чат с ${getInterlocutor(dialog)?.name} архивирован`,
                            type: 'success',
                        });
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            type: 'error',
                        });
                    }
                },
            });
        },

        restoreSingle(dialog) {
            this.closeContextMenu();
            this.showConfirm({
                type: 'success',
                icon: 'fa-solid fa-rotate-left',
                title: 'Восстановить чат?',
                message: `Чат с ${getInterlocutor(dialog)?.name} будет возвращен в список.`,
                confirmText: 'Восстановить',
                callback: async () => {
                    try {
                        await this.restoreDialog(dialog.id);
                        this.$notify?.({
                            title: 'Восстановлен',
                            type: 'success',
                        });
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            type: 'error',
                        });
                    }
                },
            });
        },

        deleteSingle(dialog) {
            this.closeContextMenu();
            this.showConfirm({
                type: 'danger',
                icon: 'fa-solid fa-trash',
                title: 'Удалить чат навсегда?',
                message: `Чат с ${getInterlocutor(dialog)?.name} будет удалён безвозвратно.`,
                confirmText: 'Удалить',
                callback: async () => {
                    try {
                        await this.deleteDialogPermanently(dialog.id);
                        this.$notify?.({
                            title: 'Удалён',
                            type: 'success',
                        });
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            type: 'error',
                        });
                    }
                },
            });
        },

        togglePin(dialog) {
            this.closeContextMenu();
            // TODO: Реализовать API для pin/unpin
            console.log('Toggle pin', dialog.id);
        },

        markAsRead(dialog) {
            this.closeContextMenu();
            if (this.markDialogAsRead) {
                this.markDialogAsRead(dialog.id);
            }
        },

        emptyArchiveConfirm() {
            this.showConfirm({
                type: 'danger',
                icon: 'fa-solid fa-trash-can',
                title: 'Очистить весь архив?',
                message: `Все ${this.archivedDialogsCount} ${this.pluralize(this.archivedDialogsCount, 'чат', 'чата', 'чатов')} будут удалены безвозвратно.`,
                confirmText: 'Очистить архив',
                callback: async () => {
                    try {
                        await this.emptyArchive();
                        this.$notify?.({
                            title: 'Архив очищен',
                            type: 'success',
                        });
                    } catch (error) {
                        this.$notify?.({
                            title: 'Ошибка',
                            type: 'error',
                        });
                    }
                },
            });
        },

        // ==========================================
        // 🆕 КОНТЕКСТНОЕ МЕНЮ
        // ==========================================

        showContextMenu(event, dialog) {
            if (this.isEditMode) return;

            const rect = event.currentTarget.getBoundingClientRect();
            let x = event.clientX || rect.right;
            let y = event.clientY || rect.top;

            // Корректировка, чтобы меню не вылезало за пределы экрана
            const menuWidth = 280;
            const menuHeight = 400;
            if (x + menuWidth > window.innerWidth) {
                x = window.innerWidth - menuWidth - 10;
            }
            if (y + menuHeight > window.innerHeight) {
                y = window.innerHeight - menuHeight - 10;
            }

            this.contextMenu = {
                visible: true,
                x,
                y,
                dialog,
                messagesCount: dialog.messages_count || 0,
                attachmentsCount: dialog.attachments_count || 0,
            };
        },

        closeContextMenu() {
            this.contextMenu.visible = false;
            this.contextMenu.dialog = null;
        },

        // ==========================================
        // 🆕 МОДАЛКА ВЛОЖЕНИЙ
        // ==========================================

        async viewAttachments(dialog) {
            this.closeContextMenu();
            this.showAttachmentsModal = true;
            this.isLoadingAttachments = true;
            this.attachments = [];

            try {
                const data = await this.getDialogAttachments(dialog.id);
                this.attachments = (data || []).map(att => ({
                    ...att,
                    size_formatted: this.formatFileSize(att.size || 0),
                    date_formatted: this.formatAttachmentDate(att.created_at),
                }));
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить вложения',
                    type: 'error',
                });
            } finally {
                this.isLoadingAttachments = false;
            }
        },

        getAttachmentIcon(type) {
            const icons = {
                pdf: 'fa-solid fa-file-pdf',
                image: 'fa-solid fa-image',
                doc: 'fa-solid fa-file-word',
                xls: 'fa-solid fa-file-excel',
                zip: 'fa-solid fa-file-zipper',
                video: 'fa-solid fa-file-video',
                audio: 'fa-solid fa-file-audio',
            };
            return icons[type] || 'fa-solid fa-file';
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        formatAttachmentDate(date) {
            return new Date(date).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        },

        // ==========================================
        // 🆕 МОДАЛКА ПОДТВЕРЖДЕНИЯ
        // ==========================================

        showConfirm(options) {
            this.confirmModal = {
                visible: true,
                ...options,
            };
        },

        cancelConfirm() {
            this.confirmModal.visible = false;
            this.confirmModal.callback = null;
        },

        executeConfirm() {
            const callback = this.confirmModal.callback;
            this.confirmModal.visible = false;
            if (callback) callback();
        },

        // ==========================================
        // СУЩЕСТВУЮЩИЕ МЕТОДЫ
        // ==========================================

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

<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: var(--bs-primary, #667eea);
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$border: var(--bs-border-color, #e5e7eb);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$success: #22c55e;
$danger: var(--bs-danger, #ef4444);
$warning: #f59e0b;

.dialog-list {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: $bg;
}

// ==========================================
// ТАБЫ
// ==========================================
.tabs-wrapper {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    border-bottom: 1px solid $border;
    background: $bg;
}

.tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 10px;
    color: $text-muted;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $bg-secondary;
    }

    &.active {
        background: rgba($primary, 0.1);
        color: $primary;
        border-color: rgba($primary, 0.2);
    }

    i {
        font-size: 0.9rem;
    }
}

.tab-badge {
    padding: 2px 8px;
    background: $primary;
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;

    &.muted {
        background: $text-muted;
    }
}

.select-mode-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: $bg-secondary;
    border: 1px solid $border;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover {
        background: $primary;
        color: white;
        border-color: $primary;
    }
}

// ==========================================
// ПАНЕЛЬ ВЫБОРА
// ==========================================
.selection-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: rgba($primary, 0.05);
    border-bottom: 1px solid rgba($primary, 0.2);
    gap: 10px;
}

.selection-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.select-all-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 0.85rem;
    color: $text;
    user-select: none;

    input {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: $primary;
    }
}

.selection-count {
    font-size: 0.85rem;
    color: $text-muted;

    strong {
        color: $primary;
    }
}

.selection-actions {
    display: flex;
    gap: 6px;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: white;
    border: 1px solid $border;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    &.archive:hover { background: $warning; color: white; border-color: $warning; }
    &.restore:hover { background: $success; color: white; border-color: $success; }
    &.delete:hover { background: $danger; color: white; border-color: $danger; }
    &.cancel:hover { background: $text-muted; color: white; border-color: $text-muted; }
}

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

// ==========================================
// ДЕЙСТВИЯ С АРХИВОМ
// ==========================================
.archive-actions {
    padding: 8px 12px;
    background: rgba($warning, 0.05);
    border-bottom: 1px solid rgba($warning, 0.1);
}

.archive-action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: transparent;
    border: 1px dashed rgba($danger, 0.3);
    border-radius: 10px;
    color: $danger;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: all 0.2s;

    &:hover {
        background: rgba($danger, 0.05);
        border-style: solid;
    }
}

// ==========================================
// ПОИСК (остался)
// ==========================================
.search-wrapper {
    padding: 10px 12px;
    border-bottom: 1px solid $border;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 14px;
    color: $text-muted;
    font-size: 0.9rem;
}

.search-input {
    width: 100%;
    padding: 10px 40px 10px 40px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    font-size: 0.9rem;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $primary;
        background: $bg;
    }
}

.search-clear {
    position: absolute;
    right: 10px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: $border;
    border: none;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}

// ==========================================
// СОСТОЯНИЯ
// ==========================================
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
    background: rgba($primary, 0.1);
    color: $primary;
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
    color: $text-muted;
    font-size: 0.9rem;
}

// ==========================================
// СПИСОК ДИАЛОГОВ
// ==========================================
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
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: $bg-secondary;
}

.dialog-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.2s;
    border-bottom: 1px solid rgba($border, 0.5);
    position: relative;

    &:hover {
        background: rgba($primary, 0.03);
    }

    &.is-active {
        background: rgba($primary, 0.08);
    }

    &.is-unread {
        .dialog-name { font-weight: 700; }
        .preview-text { color: $text; font-weight: 600; }
    }

    &.is-archived {
        opacity: 0.75;
    }

    &.is-selected {
        background: rgba($primary, 0.1);
    }

    &.is-edit-mode {
        padding-left: 12px;
    }
}

// 🆕 Чекбокс
.dialog-checkbox {
    display: flex;
    align-items: center;
    cursor: pointer;
    flex-shrink: 0;

    input {
        position: absolute;
        opacity: 0;
    }

    .checkbox-visual {
        width: 22px;
        height: 22px;
        border: 2px solid $border;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        background: white;

        i {
            opacity: 0;
            color: white;
            font-size: 0.7rem;
            transition: opacity 0.2s;
        }
    }

    input:checked + .checkbox-visual {
        background: $primary;
        border-color: $primary;

        i {
            opacity: 1;
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
    background: $success;
    border: 2px solid $bg;
}

.unread-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: $danger;
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid $bg;
}

.pin-icon {
    position: absolute;
    top: -4px;
    left: -4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: $primary;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    border: 2px solid $bg;
}

.archive-icon {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: $warning;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    border: 2px solid $bg;
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
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dialog-time {
    font-size: 0.75rem;
    color: $text-muted;
    flex-shrink: 0;
}

.dialog-preview {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
}

.preview-text {
    color: $text-muted;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.message-status {
    font-size: 0.75rem;
    flex-shrink: 0;

    &.status-read { color: $primary; }
    &.status-delivered { color: $text; }
    &.status-sent { color: $text-muted; }
}

// 🆕 Кнопка меню
.dialog-menu-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    opacity: 0;
    flex-shrink: 0;

    .dialog-item:hover & {
        opacity: 1;
    }

    &:hover {
        background: $bg-secondary;
        color: $text;
    }
}

// На мобильных всегда показываем
@media (max-width: 768px) {
    .dialog-menu-btn {
        opacity: 1;
    }
}

// ==========================================
// 🆕 КОНТЕКСТНОЕ МЕНЮ
// ==========================================
.context-menu-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
}

.context-menu {
    position: absolute;
    width: 280px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    animation: menuFadeIn 0.2s ease;
}

@keyframes menuFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.context-menu-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: $bg-secondary;
}

.context-menu-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.context-menu-info {
    flex: 1;
    min-width: 0;

    strong {
        display: block;
        font-size: 0.9rem;
        color: $text;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    span {
        font-size: 0.75rem;
        color: $text-muted;
    }
}

.context-menu-divider {
    height: 1px;
    background: $border;
}

.context-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 16px;
    background: transparent;
    border: none;
    color: $text;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.2s;
    text-align: left;

    &:hover {
        background: $bg-secondary;
    }

    i {
        width: 20px;
        text-align: center;
        color: $text-muted;
    }

    span {
        flex: 1;
    }

    .menu-badge {
        padding: 2px 8px;
        background: rgba($primary, 0.1);
        color: $primary;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    &.archive {
        i { color: $warning; }
    }

    &.restore {
        i { color: $success; }
    }

    &.delete {
        color: $danger;
        i { color: $danger; }
        font-weight: 600;
    }
}

// ==========================================
// 🆕 МОДАЛКА ВЛОЖЕНИЙ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.attachments-modal {
    background: $bg;
    border-radius: 20px;
    width: 100%;
    max-width: 600px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid $border;

    h3 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1rem;
        margin: 0;

        i { color: $primary; }
    }
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: $bg-secondary;
    border: none;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $danger;
        color: white;
    }
}

.attachments-loading,
.attachments-empty {
    padding: 40px;
    text-align: center;
    color: $text-muted;

    i {
        font-size: 2.5rem;
        margin-bottom: 12px;
    }

    .loader-spinner {
        width: 32px;
        height: 32px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.attachments-grid {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: $bg-secondary;
    border-radius: 12px;
    text-decoration: none;
    color: $text;
    transition: all 0.2s;
    border: 1px solid transparent;

    &:hover {
        background: $bg;
        border-color: $primary;
        transform: translateX(2px);
    }

    &.type-pdf {
        border-left: 3px solid #e53e3e;
        .attachment-icon-large { background: rgba(229, 62, 62, 0.1); color: #e53e3e; }
    }

    &.type-image {
        border-left: 3px solid $success;
        .attachment-icon-large { background: rgba(34, 197, 94, 0.1); color: $success; }
    }

    &.type-doc,
    &.type-xls {
        border-left: 3px solid #3b82f6;
        .attachment-icon-large { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    }
}

.attachment-icon-large {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.attachment-info {
    flex: 1;
    min-width: 0;
}

.attachment-name {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attachment-meta {
    display: flex;
    gap: 10px;
    font-size: 0.75rem;
    color: $text-muted;
}

.attachment-download {
    color: $primary;
    font-size: 1rem;
    flex-shrink: 0;
}

// ==========================================
// 🆕 МОДАЛКА ПОДТВЕРЖДЕНИЯ
// ==========================================
.confirm-modal {
    background: $bg;
    border-radius: 20px;
    padding: 28px 24px;
    width: 100%;
    max-width: 400px;
    text-align: center;
    animation: modalSlideUp 0.3s ease;
}

.confirm-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    margin: 0 auto 16px;

    &.warning { background: linear-gradient(135deg, $warning, #d97706); }
    &.danger { background: linear-gradient(135deg, $danger, #dc2626); }
    &.success { background: linear-gradient(135deg, $success, #16a34a); }
}

.confirm-modal h3 {
    font-size: 1.2rem;
    margin: 0 0 8px;
    color: $text;
}

.confirm-modal p {
    font-size: 0.9rem;
    color: $text-muted;
    margin: 0 0 24px;
    line-height: 1.5;
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

.confirm-btn {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;

    &.cancel {
        background: $bg-secondary;
        color: $text;
        border: 1px solid $border;

        &:hover {
            background: $border;
        }
    }

    &.warning {
        background: $warning;
        color: white;
        &:hover { background: #d97706; }
    }

    &.danger {
        background: $danger;
        color: white;
        &:hover { background: #dc2626; }
    }

    &.success {
        background: $success;
        color: white;
        &:hover { background: #16a34a; }
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 480px) {
    .tabs-wrapper {
        padding: 6px 8px;
    }

    .tab-btn {
        padding: 8px 10px;
        font-size: 0.8rem;
    }

    .selection-panel {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .selection-left {
        justify-content: space-between;
    }

    .selection-actions {
        justify-content: space-between;
    }

    .dialog-item {
        padding: 10px 12px;
        gap: 10px;
    }

    .dialog-avatar {
        width: 46px;
        height: 46px;
    }

    .context-menu {
        width: calc(100vw - 40px);
        max-width: 320px;
    }
}
</style>
