<template>
    <div class="chat-component">
        <!-- ШАПКА ЧАТА -->
        <div class="chat-header">
            <button class="back-btn" @click="goBack">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="header-info">
                <div class="header-avatar" :style="avatarGradientStyle">
                    <img v-if="currentInterlocutor?.avatar" :src="currentInterlocutor.avatar"
                         :alt="currentInterlocutor?.name">
                    <span v-else class="avatar-initials">{{ getInitials(currentInterlocutor?.name) }}</span>
                    <div v-if="currentInterlocutor?.is_online" class="online-dot"></div>
                </div>
                <div class="header-text">
                    <div class="header-name">{{ currentInterlocutor?.name || 'Чат' }}</div>
                    <div class="header-status">
                        <template v-if="currentInterlocutor?.is_online">
                            <span class="online-indicator"></span><span class="online-text">в сети</span>
                        </template>
                        <template v-else-if="currentInterlocutor?.last_seen_at">
                            {{ formatLastSeen(currentInterlocutor.last_seen_at) }}
                        </template>
                        <template v-else>
                            <span class="offline-text">был(а) недавно</span>
                        </template>
                    </div>
                </div>
            </div>
            <button class="header-action" @click.stop="openDialogMenu">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        </div>

        <!-- СООБЩЕНИЯ -->
        <div ref="messagesContainer" class="messages-container" @scroll="handleScroll">
            <div v-if="isMessagesLoading" class="messages-loader">
                <div class="loader-spinner"></div>
            </div>
            <button v-if="hasMoreMessages && !isMessagesLoading" class="load-more-btn" @click="loadOlderMessages">
                <i class="fa-solid fa-arrow-up"></i> Загрузить ещё
            </button>
            <div v-if="sortedMessages.length === 0 && !isMessagesLoading" class="empty-chat">
                <div class="empty-icon"><i class="fa-solid fa-comments"></i></div>
                <p>Начните общение!</p><span>Напишите первое сообщение</span>
            </div>
            <template v-else>
                <template v-for="(message, index) in sortedMessages" :key="message.id">

                    <!-- 1. Разделитель дат -->
                    <div v-if="shouldShowDateSeparator(index)" class="date-separator">
                        <span>{{ formatDateSeparator(message.created_at) }}</span>
                    </div>

                    <div
                        class="message-bubble"
                        :class="{
                                'is-mine': isMine(message),
                                'is-error': message.status === 'error',
                                'is-sending': message.status === 'sending',
                                'has-tail': shouldShowTail(index),
                                'is-order': isOrderMessage(message),
                                'is-system': isSystemMessage(message),
                            }"
                    >

                        <!-- ✅ 1. СООБЩЕНИЕ С ЗАКАЗОМ (Восстановлено) -->
                        <template v-if="isOrderMessage(message)">
                            <div class="order-card">
                                <!-- Иконка в цветном кружке -->
                                <div class="order-icon-wrap">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>

                                <!-- Основной контент -->
                                <div class="order-body">
                                    <div class="order-header">
                                        <span class="order-title">Заказ #{{ message.meta?.order_id }}</span>
                                        <span class="order-time">{{ formatMessageTime(message.created_at) }}</span>
                                    </div>
                                    <div class="order-description" v-html="message.text || message.message"></div>

                                    <a
                                        v-if="message.attachment?.url"
                                        :href="message.attachment.url"
                                        target="_blank"
                                        class="order-download"
                                    >
                                        <i class="fa-solid fa-file-pdf"></i>
                                        <span>Скачать чек</span>
                                        <i class="fa-solid fa-arrow-right download-arrow"></i>
                                    </a>
                                </div>
                            </div>
                        </template>

                        <!-- ✅ 2. СООБЩЕНИЕ С ФАЙЛОМ (Защищённая версия) -->
                        <template v-else-if="message.has_attachment || message.attachment || message.meta?.attachment">
                            <div class="message-content">
                                <div class="message-text" v-if="message.text || message.message"
                                     v-html="message.text || message.message"></div>

                                <!-- 🆕 Безопасная карточка вложения -->
                                <a
                                    v-if="getAttachmentUrl(message)"
                                    :href="getAttachmentUrl(message)"
                                    target="_blank"
                                    class="attachment-card"
                                    :class="[`attachment-${getAttachmentType(message)}`]"
                                >
                                    <div class="attachment-icon">
                                        <i :class="getAttachmentIcon(getAttachmentType(message))"></i>
                                    </div>
                                    <div class="attachment-info">
                                        <div class="attachment-name">{{ getAttachmentName(message) }}</div>
                                        <div class="attachment-meta">
                                            <span class="attachment-size">{{ getAttachmentSizeFormatted(message) }}</span>
                                            <span class="attachment-type">{{ getAttachmentType(message).toUpperCase() }}</span>
                                        </div>
                                    </div>
                                    <button class="attachment-download-btn" type="button">
                                        <i class="fa-solid fa-arrow-down"></i>
                                    </button>
                                </a>

                                <!-- 🆕 Fallback: если вложение есть, но URL нет (старая структура) -->
                                <div v-else class="attachment-card attachment-unknown">
                                    <div class="attachment-icon">
                                        <i class="fa-solid fa-file"></i>
                                    </div>
                                    <div class="attachment-info">
                                        <div class="attachment-name">Файл недоступен</div>
                                        <div class="attachment-meta">
                                            <span class="attachment-size">—</span>
                                            <span class="attachment-type">FILE</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="message-meta">
            <span
                v-if="!isMine(message) && getSenderName(message)"
                class="sender-name-inline"
                @click="openInterlocutorInfo"
                title="Показать информацию о собеседнике"
            >
                {{ getSenderName(message) }}
            </span>

                                    <span class="message-time">{{ formatMessageTime(message.created_at) }}</span>
                                    <span v-if="isMine(message)" class="message-status">
                <i :class="getStatusIcon(message)"></i>
            </span>
                                </div>
                            </div>
                        </template>

                        <!-- ✅ 3. 🆕 СПЕЦИАЛЬНЫЙ БЛОК ДЛЯ СИСТЕМНЫХ СООБЩЕНИЙ -->
                        <template v-else-if="isSystemMessage(message)">
                            <div class="system-message-content">
                                <i :class="getSystemMessageIcon(message)"></i>
                                <span v-html="message.text || message.message"></span>
                            </div>
                        </template>

                        <!-- ✅ 4. ОБЫЧНОЕ ТЕКСТОВОЕ СООБЩЕНИЕ -->
                        <template v-else>
                            <div class="message-content">
                                <div class="message-text" v-if="message.text || message.message"
                                     v-html="message.text || message.message"></div>
                                <div class="message-meta">
            <span
                v-if="!isMine(message) && getSenderName(message)"
                class="sender-name-inline"
                @click="openInterlocutorInfo"
            >
                <i class="fa-regular fa-user"></i>
                {{ getSenderName(message) }}
            </span>

                                    <span class="message-time">{{ formatMessageTime(message.created_at) }}</span>
                                    <span v-if="isMine(message)" class="message-status">
                <i :class="getStatusIcon(message)"></i>
            </span>
                                </div>
                            </div>

                            <!-- 🆕 ПОДПИСЬ "ПРОСМОТРЕНО В HH:MM" -->
                            <div
                                v-if="isMine(message) && message.read_at && isLastMineInGroup(index)"
                                class="read-receipt"
                            >
                                <i class="fa-solid fa-eye"></i>
                                <span>Просмотрено в {{ formatMessageTime(message.read_at) }}</span>
                            </div>
                        </template>

                        <!-- Кнопка повторной отправки -->
                        <button v-if="message.status === 'error'" class="retry-btn" @click="retryMessage(message)"
                                title="Повторить отправку">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                </template>

                <div v-if="isTyping" class="typing-indicator">
                    <div class="typing-bubble"><span></span><span></span><span></span></div>
                </div>
            </template>
        </div>

        <!-- ПОЛЕ ВВОДА -->
        <ChatInput
            :dialog="currentDialog"
            :disabled="false"
            @send="handleSendMessage"
        />

        <!-- BOTTOM SHEET МЕНЮ ДИАЛОГА -->
        <teleport to="body">
            <transition name="sheet-fade">
                <div v-if="showDialogMenu" class="sheet-overlay" @click="closeDialogMenu">
                    <div class="sheet-container" @click.stop>
                        <div class="sheet-header">
                            <div class="sheet-avatar" :style="avatarGradientStyle">
                                <img v-if="currentInterlocutor?.avatar" :src="currentInterlocutor.avatar"
                                     :alt="currentInterlocutor?.name">
                                <span v-else class="avatar-initials">{{ getInitials(currentInterlocutor?.name) }}</span>
                            </div>
                            <div class="sheet-info">
                                <div class="sheet-name">{{ currentInterlocutor?.name || 'Чат' }}</div>
                                <div class="sheet-status">
                                    <template v-if="currentInterlocutor?.is_online"><span
                                        class="online-dot-small"></span><span>в сети</span></template>
                                    <template v-else><span>был(а) недавно</span></template>
                                </div>
                            </div>
                            <button class="sheet-close" @click="closeDialogMenu"><i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="sheet-divider"></div>
                        <div class="sheet-actions">
                            <button class="sheet-action" @click="viewAttachments">
                                <div class="sheet-action-icon attachments"><i class="fa-solid fa-paperclip"></i></div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Вложения</div>
                                    <div class="sheet-action-desc">Просмотр файлов и документов</div>
                                </div>
                                <span v-if="attachmentsCount > 0" class="sheet-action-badge">{{
                                        attachmentsCount
                                    }}</span>
                                <i class="fa-solid fa-chevron-right sheet-action-arrow"></i>
                            </button>
                            <button class="sheet-action" @click="archiveCurrentDialog">
                                <div class="sheet-action-icon archive"><i class="fa-solid fa-box-archive"></i></div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">В архив</div>
                                    <div class="sheet-action-desc">Скрыть диалог из списка</div>
                                </div>
                                <i class="fa-solid fa-chevron-right sheet-action-arrow"></i>
                            </button>
                            <button class="sheet-action" @click="clearHistory">
                                <div class="sheet-action-icon clear"><i class="fa-solid fa-broom"></i></div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Очистить историю</div>
                                    <div class="sheet-action-desc">Удалить все сообщения</div>
                                </div>
                                <i class="fa-solid fa-chevron-right sheet-action-arrow"></i>
                            </button>
                            <div class="sheet-divider"></div>
                            <button class="sheet-action danger" @click="deleteCurrentDialog">
                                <div class="sheet-action-icon delete"><i class="fa-solid fa-trash"></i></div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Удалить диалог</div>
                                    <div class="sheet-action-desc">Удалить навсегда без возможности восстановления</div>
                                </div>
                                <i class="fa-solid fa-chevron-right sheet-action-arrow"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- МОДАЛКА ВЛОЖЕНИЙ -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showAttachmentsModal" class="modal-overlay" @click.self="closeAttachmentsModal">
                    <div class="attachments-modal">
                        <div class="modal-header">
                            <h3><i class="fa-solid fa-paperclip"></i> Вложения</h3>
                            <button class="modal-close" @click="closeAttachmentsModal"><i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div v-if="isLoadingAttachments" class="attachments-loading">
                            <div class="loader-spinner"></div>
                        </div>
                        <div v-else-if="attachments.length === 0" class="attachments-empty"><i
                            class="fa-solid fa-inbox"></i>
                            <p>Вложений нет</p></div>
                        <div v-else class="attachments-grid">
                            <a v-for="attachment in attachments" :key="attachment.id" :href="attachment.url"
                               target="_blank" class="attachment-item" :class="[`type-${attachment.type}`]">
                                <div class="attachment-icon-large"><i :class="getAttachmentIcon(attachment.type)"></i>
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

        <!-- МОДАЛКА ПОДТВЕРЖДЕНИЯ -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="confirmModal.visible" class="modal-overlay" @click.self="cancelConfirm">
                    <div class="confirm-modal">
                        <div class="confirm-icon" :class="confirmModal.type"><i :class="confirmModal.icon"></i></div>
                        <h3>{{ confirmModal.title }}</h3>
                        <p>{{ confirmModal.message }}</p>
                        <div class="confirm-actions">
                            <button class="confirm-btn cancel" @click="cancelConfirm">Отмена</button>
                            <button class="confirm-btn" :class="confirmModal.type" @click="executeConfirm">
                                {{ confirmModal.confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- 🆕 МОДАЛКА ИНФОРМАЦИИ О СОБЕСЕДНИКЕ -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showInterlocutorModal" class="modal-overlay" @click.self="closeInterlocutorInfo">
                    <div class="interlocutor-modal">
                        <div class="modal-header">
                            <h3><i class="fa-solid fa-user"></i> Собеседник</h3>
                            <button class="modal-close" @click="closeInterlocutorInfo">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="interlocutor-avatar" :style="avatarGradientStyle">
                                <img v-if="currentInterlocutor?.avatar" :src="currentInterlocutor.avatar" :alt="currentInterlocutor?.name">
                                <span v-else class="avatar-initials">{{ getInitials(currentInterlocutor?.name) }}</span>
                            </div>

                            <h4 class="interlocutor-name">{{ currentInterlocutor?.name || 'Неизвестно' }}</h4>
                            <p class="interlocutor-role">
                                <i class="fa-solid fa-shield-halved" v-if="getSenderType() === 'admin'"></i>
                                {{ getSenderType() === 'admin' ? 'Администратор / Поддержка' : 'Клиент' }}
                            </p>

                            <div class="interlocutor-details">
                                <div class="detail-row" v-if="currentInterlocutor?.phone">
                                    <i class="fa-solid fa-phone"></i>
                                    <span>{{ currentInterlocutor.phone }}</span>
                                </div>
                                <div class="detail-row" v-if="currentInterlocutor?.email">
                                    <i class="fa-solid fa-envelope"></i>
                                    <span>{{ currentInterlocutor.email }}</span>
                                </div>
                                <div class="detail-row">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>{{ currentInterlocutor?.is_online ? 'В сети' : 'Был(а) недавно' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script>
import {useRoute, useRouter, onBeforeRouteLeave} from 'vue-router';
import {useChat} from '@/MobileClient/Composables/useChat.js';
import ChatInput from "@/MobileClient/Components/Chat/ChatInput.vue";
import {
    formatMessageTime,
    formatDateSeparator,
    formatLastSeen,
    getInitials,
    getAvatarGradient,
    isMyMessage,
} from '@/MobileClient/utils/chatUtils.js';

export default {
    name: "ChatComponent",
    components: {ChatInput},

    setup() {
        const chat = useChat();
        const route = useRoute();
        const router = useRouter();

        // 🧹 Очистка состояния при уходе со страницы чата (чтобы не "залипал" последний диалог)
        onBeforeRouteLeave((to, from) => {
            if (to.name !== 'ChatRoom') {
                chat.closeDialog();
            }
        });

        return {...chat, route, router};
    },

    data() {
        return {
            autoScroll: true,
            isTyping: false,
            showDialogMenu: false,
            showAttachmentsModal: false,
            showInterlocutorModal: false, // 🆕 НОВОЕ СОСТОЯНИ
            attachments: [],
            isLoadingAttachments: false,
            attachmentsCount: 0,
            confirmModal: {
                visible: false,
                type: 'warning',
                icon: 'fa-solid fa-triangle-exclamation',
                title: '',
                message: '',
                confirmText: 'Подтвердить',
                callback: null
            },
        };
    },

    computed: {
        user() {
            return window.TenantUser;
        },
        avatarGradientStyle() {
            if (!this.currentDialog) return {};
            const interlocutor = this.currentDialog.interlocutor || this.currentDialog.user || this.currentDialog.companion;
            if (interlocutor?.avatar) return {};
            return getAvatarGradient(this.currentDialog.id);
        },
    },

    watch: {
        sortedMessages: {
            handler(newMessages, oldMessages) {
                if (newMessages.length > (oldMessages?.length || 0)) {
                    this.$nextTick(() => this.scrollToBottom());
                }
            },
            deep: true,
        },
        currentDialog: {
            immediate: true,
            handler(newDialog) {
                if (newDialog) {
                    this.autoScroll = true;
                    this.showDialogMenu = false;
                }
            },
        },

        // 🛡️ АБСОЛЮТНАЯ ЗАЩИТА ДЛЯ ПРЯМЫХ ПЕРЕХОДОВ И ОБНОВЛЕНИЙ СТРАНИЦЫ
        '$route.params.id': {
            immediate: true,
            async handler(newId) {
                // 1. Если ID нет в URL вообще -> уходим в список
                if (!newId) {
                    this.router.replace({name: 'ChatList'});
                    return;
                }

                // 2. Приводим к строке для безопасного сравнения (URL всегда строка, БД может быть числом)
                const currentId = this.currentDialog ? String(this.currentDialog.id) : null;
                const targetId = String(newId);

                // 3. Если этот диалог УЖЕ открыт, не делаем лишних запросов к серверу
                if (currentId === targetId) {
                    return;
                }

                console.log(`[ChatComponent] Прямой переход или смена чата. Загружаем ID: ${targetId}`);

                try {
                    // 4. Пытаемся загрузить диалог и сообщения
                    await this.openDialog(targetId);
                } catch (error) {
                    console.error('[ChatComponent] Не удалось загрузить диалог:', error);
                    this.$notify?.({
                        title: 'Ошибка',
                        text: 'Этот чат не найден или был удален',
                        type: 'error'
                    });
                    // 5. Если ошибка (например, 404), перенаправляем в список
                    this.router.replace({name: 'ChatList'});
                }
            }
        }
    },
    mounted() {

        // 🆕 Запуск polling статусов прочтения
        if (this.currentDialog?.id) {
            this.startReadStatusPolling?.(this.currentDialog.id);
        }

        // 🆕 Обработка push из Service Worker (VAPID)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data?.type === 'NEW_CHAT_MESSAGE') {
                    const payload = event.data.payload;
                    if (this.currentDialog &&
                        String(payload.dialog_id) === String(this.currentDialog.id)) {
                        this.handleIncomingMessage?.(payload.message);
                        this.refreshReadStatuses?.(this.currentDialog.id);
                    } else {
                        this.loadDialogs?.();
                    }
                }
                if (event.data?.type === 'OPEN_CHAT' && event.data.dialogId) {
                    this.$router.push({
                        name: 'ChatRoom',
                        params: { id: event.data.dialogId }
                    }).catch(() => {});
                }
            });
        }
    },

    beforeUnmount() {
        // 🆕 Остановка polling при уходе
        this.stopReadStatusPolling?.();
    },
    methods: {
        // 🆕 Добавьте этот метод, если его нет в useChat или локально
        // 🆕 Исправленный метод повторной отправки
        retryMessage(message) {
            if (!this.sendMessage || !this.currentDialog) return;

            const payload = {
                text: message.text || message.message || '',
                // Убедимся, что attachments это массив (даже если он пустой)
                attachments: Array.isArray(message.attachments) ? message.attachments : []
            };

            this.sendMessage(this.currentDialog.id, payload)
                .catch(err => {
                    console.error('Ошибка повторной отправки:', err);
                    this.$notify?.({
                        title: 'Ошибка',
                        text: 'Не удалось отправить сообщение повторно',
                        type: 'error'
                    });
                });
        },

        getSystemMessageIcon(message) {
            const type = message.meta?.type;
            const iconMap = {
                'status_change': 'fa-solid fa-arrow-right-arrow-left',
                'payment': 'fa-solid fa-credit-card',
                'dialog_closed': 'fa-solid fa-lock',
                'user_joined': 'fa-solid fa-user-plus',
                'user_left': 'fa-solid fa-user-minus',
                'default': 'fa-solid fa-circle-info'
            };
            return iconMap[type] || iconMap['default'];
        },
        openDialogMenu() {
            this.showDialogMenu = true;
            document.body.style.overflow = 'hidden';
        },
        closeDialogMenu() {
            this.showDialogMenu = false;
            document.body.style.overflow = '';
        },

        viewAttachments() {
            this.closeDialogMenu();
            this.showAttachmentsModal = true;
            this.isLoadingAttachments = true;
            this.attachments = [];
            this.getDialogAttachments(this.currentDialog.id)
                .then(data => {
                    this.attachments = (data || []).map(att => ({
                        ...att,
                        size_formatted: this.formatFileSize(att.size || 0),
                        date_formatted: this.formatAttachmentDate(att.created_at),
                    }));
                    this.attachmentsCount = this.attachments.length;
                })
                .catch(() => {
                    this.$notify?.({title: 'Ошибка', text: 'Не удалось загрузить вложения', type: 'error'});
                })
                .finally(() => {
                    this.isLoadingAttachments = false;
                });
        },
        closeAttachmentsModal() {
            this.showAttachmentsModal = false;
        },

        archiveCurrentDialog() {
            this.closeDialogMenu();
            this.showConfirm({
                type: 'warning', icon: 'fa-solid fa-box-archive', title: 'Архивировать диалог?',
                message: `Диалог с ${this.currentInterlocutor?.name || 'собеседником'} будет перемещен в архив.`,
                confirmText: 'В архив',
                callback: async () => {
                    try {
                        await this.archiveDialog(this.currentDialog.id);
                        this.$notify?.({title: 'Архивировано', type: 'success'});
                        this.goBack();
                    } catch (error) {
                        this.$notify?.({title: 'Ошибка', text: 'Не удалось архивировать', type: 'error'});
                    }
                },
            });
        },

        clearHistory() {
            this.closeDialogMenu();
            this.showConfirm({
                type: 'warning', icon: 'fa-solid fa-broom', title: 'Очистить историю?',
                message: 'Все сообщения в этом диалоге будут удалены.',
                confirmText: 'Очистить',
                callback: async () => {
                    try {
                        // await this.clearDialogHistory(this.currentDialog.id); // TODO
                        this.$notify?.({title: 'История очищена', type: 'success'});
                        this.messages = [];
                    } catch (error) {
                        this.$notify?.({title: 'Ошибка', type: 'error'});
                    }
                },
            });
        },

        // 🆕 Методы для модального окна собеседника
        openInterlocutorInfo() {
            this.showInterlocutorModal = true;
        },
        closeInterlocutorInfo() {
            this.showInterlocutorModal = false;
        },

        // 🆕 Определяем тип собеседника для отображения в модалке
        getSenderType() {
            // Если текущий диалог имеет флаг или мы знаем, что это админ-чат
            // В простой реализации: если сообщение не мое, и это не системное,
            // мы можем проверить meta или просто вернуть 'admin' для поддержки
            return 'admin'; // Можно доработать логику, если есть четкое поле is_admin у currentInterlocutor
        },
        getSenderName(message) {
            if (message.sender_type === 'admin') {
                return message.meta?.sender_name || 'Администратор';
            }
            if (message.sender_type === 'system') {
                return 'Система';
            }
            // Если это пользователь (на случай групповых чатов в будущем)
            return message.user?.name || message.sender_name || 'Пользователь';
        },
        deleteCurrentDialog() {
            this.closeDialogMenu();
            this.showConfirm({
                type: 'danger', icon: 'fa-solid fa-trash', title: 'Удалить диалог навсегда?',
                message: `Диалог с ${this.currentInterlocutor?.name || 'собеседником'} и вся история будут удалены безвозвратно.`,
                confirmText: 'Удалить',
                callback: async () => {
                    try {
                        await this.deleteDialogPermanently(this.currentDialog.id);
                        this.$notify?.({title: 'Удалено', type: 'success'});
                        this.goBack();
                    } catch (error) {
                        this.$notify?.({title: 'Ошибка', text: 'Не удалось удалить', type: 'error'});
                    }
                },
            });
        },

        showConfirm(options) {
            this.confirmModal = {visible: true, ...options};
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

        formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024, sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
        formatAttachmentDate(date) {
            return new Date(date).toLocaleDateString('ru-RU', {day: '2-digit', month: 'short', year: 'numeric'});
        },

        // 🚀 ИЗМЕНЕНО: Используем router.back() вместо кастомной логики
        goBack() {
            this.router.back();
        },

        isOrderMessage(message) {
            const type = message.meta?.type;
            return type === 'invoice' || type === 'order_created' || type === 'order_summary' || type === 'partner_order' || !!message.meta?.order_id;
        },
        isSystemMessage(message) {
            const type = message.meta?.type;
            return type === 'status_change' || type === 'payment' || type === 'dialog_closed';
        },
        getAttachmentIcon(type) {
            const icons = {
                pdf: 'fa-solid fa-file-pdf',
                image: 'fa-solid fa-image',
                doc: 'fa-solid fa-file-word',
                xls: 'fa-solid fa-file-excel',
                zip: 'fa-solid fa-file-zipper',
                video: 'fa-solid fa-file-video',
                audio: 'fa-solid fa-file-audio'
            };
            return icons[type] || 'fa-solid fa-file';
        },
        getStatusIcon(message) {
            if (message.status === 'sending') return 'fa-solid fa-clock';
            if (message.status === 'error') return 'fa-solid fa-circle-exclamation error-icon';

            // 🆕 Если есть время прочтения — синие двойные галочки
            if (message.read_at) return 'fa-solid fa-check-double status-read';

            if (message.status === 'read') return 'fa-solid fa-check-double status-read';
            if (message.status === 'delivered') return 'fa-solid fa-check-double status-delivered';
            return 'fa-solid fa-check status-sent';
        },

        isMine(message) {
            return isMyMessage(message, this.user?.id);
        },

        isLastMineInGroup(index) {
            const current = this.sortedMessages[index];
            const next = this.sortedMessages[index + 1];
            if (!this.isMine(current)) return false;
            if (!next) return true;
            return !this.isMine(next);
        },
        shouldShowTail(index) {
            const current = this.sortedMessages[index];
            const next = this.sortedMessages[index + 1];
            if (!next) return true;
            return this.isMine(current) !== this.isMine(next);
        },
        shouldShowDateSeparator(index) {
            if (index === 0) return true;
            const current = this.sortedMessages[index];
            const prev = this.sortedMessages[index - 1];
            return new Date(current.created_at).toDateString() !== new Date(prev.created_at).toDateString();
        },

        /**
         * 🆕 Безопасное получение URL вложения
         * Поддерживает разные форматы данных:
         * - message.attachment.url (новый формат)
         * - message.meta.attachment.url (старый формат)
         * - message.attachment.path (fallback на storage)
         */
        getAttachmentUrl(message) {
            // Новый формат: message.attachment.url
            if (message.attachment?.url) {
                return message.attachment.url;
            }

            // Старый формат: message.meta.attachment.url
            if (message.meta?.attachment?.url) {
                return message.meta.attachment.url;
            }

            // Fallback: строим URL из path
            const path = message.attachment?.path || message.meta?.attachment?.path;
            if (path) {
                return `/storage/${path}`;
            }

            return null;
        },

        /**
         * 🆕 Безопасное получение типа вложения
         */
        getAttachmentType(message) {
            return (
                message.attachment?.type ||
                message.meta?.attachment?.type ||
                this.getFileTypeFromExtension(
                    message.attachment?.extension ||
                    message.meta?.attachment?.extension ||
                    message.attachment?.name ||
                    message.meta?.attachment?.name ||
                    ''
                )
            ).toLowerCase() || 'file';
        },

        /**
         * 🆕 Безопасное получение имени файла
         */
        getAttachmentName(message) {
            return (
                message.attachment?.name ||
                message.attachment?.original_name ||
                message.meta?.attachment?.name ||
                message.meta?.attachment?.original_name ||
                'Без имени'
            );
        },

        /**
         * 🆕 Безопасное получение форматированного размера
         */
        getAttachmentSizeFormatted(message) {
            // Если уже есть готовая строка
            if (message.attachment_size_formatted) {
                return message.attachment_size_formatted;
            }

            const size = (
                message.attachment?.size ||
                message.meta?.attachment?.size ||
                0
            );

            if (!size) return '—';
            return this.formatFileSize(size);
        },

        /**
         * 🆕 Определение типа файла по расширению
         */
        getFileTypeFromExtension(filename) {
            if (!filename) return 'file';

            const ext = filename.split('.').pop()?.toLowerCase() || '';

            const map = {
                'jpeg': 'image', 'jpg': 'image', 'png': 'image', 'gif': 'image', 'webp': 'image',
                'mp4': 'video', 'webm': 'video', 'mov': 'video',
                'mp3': 'audio', 'wav': 'audio', 'ogg': 'audio',
                'pdf': 'pdf',
                'doc': 'doc', 'docx': 'doc',
                'xls': 'xls', 'xlsx': 'xls',
                'zip': 'zip', 'rar': 'zip',
            };

            return map[ext] || 'file';
        },
        async handleSendMessage(text, attachments) {
            // 1. Создаем временное сообщение для мгновенного отображения
            const tempId = 'temp_' + Date.now();
            const tempMessage = {
                id: tempId,
                dialog_id: this.currentDialog.id,
                sender_id: this.user?.id, // КРИТИЧЕСКИ ВАЖНО: чтобы сработало isMine() и сообщение встало справа
                text: text || '',
                message: text || '',
                attachments: Array.isArray(attachments) ? attachments : [],
                created_at: new Date().toISOString(),
                status: 'sending' // Статус для отображения иконки "часики"
            };

            // 2. Сразу добавляем в чат и скроллим вниз
            this.messages.push(tempMessage);
            this.scrollToBottom();

            try {
                // 3. Формируем payload и отправляем на сервер
                const payload = {
                    text: text || '',
                    attachments: Array.isArray(attachments) ? attachments : []
                };

                const response = await this.sendMessage(this.currentDialog.id, payload);

                // 4. Успех! Заменяем временное сообщение на реальное от сервера
                const serverMessage = response.data || response; // Учитываем разные форматы ответа
                const index = this.messages.findIndex(m => m.id === tempId);

                if (index !== -1) {
                    // Полностью заменяем временный объект на реальный с настоящим ID и статусом
                    this.messages.splice(index, 1, {
                        ...serverMessage,
                        status: 'sent' // или 'delivered', в зависимости от вашей логики
                    });
                }

                this.scrollToBottom();

            } catch (error) {
                // 5. Ошибка! Помечаем сообщение как ошибочное, чтобы показать кнопку "Повторить"
                console.error('Ошибка отправки сообщения:', error);
                const index = this.messages.findIndex(m => m.id === tempId);
                if (index !== -1) {
                    this.messages[index].status = 'error';
                }

                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить сообщение. Нажмите на иконку для повтора.',
                    type: 'error'
                });
            }
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container && this.autoScroll) {
                container.scrollTop = container.scrollHeight;
            }
        },
        handleScroll() {
            const container = this.$refs.messagesContainer;
            if (!container) return;

            const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;
            this.autoScroll = isAtBottom;

            // 🆕 Доскроллили до низа — отмечаем как прочитанное
            if (isAtBottom && this.currentDialog) {
                this.markDialogAsRead(this.currentDialog.id);
            }

            // Подгрузка старых сообщений
            if (container.scrollTop < 100 && this.hasMoreMessages && !this.isMessagesLoading) {
                const previousHeight = container.scrollHeight;
                this.loadOlderMessages().then(() => {
                    this.$nextTick(() => {
                        const newHeight = container.scrollHeight;
                        container.scrollTop = newHeight - previousHeight;
                    });
                });
            }
        },

        formatMessageTime(timestamp) {
            return formatMessageTime(timestamp);
        },
        formatDateSeparator(timestamp) {
            return formatDateSeparator(timestamp);
        },
        formatLastSeen(timestamp) {
            return formatLastSeen(timestamp);
        },
        getInitials(name) {
            return getInitials(name);
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

// ==========================================
// БАЗА
// ==========================================
.chat-component {
    display: flex;
    flex-direction: column;
    height: 100 dvh; // 🆕 100dvh вместо 100vh: учитывает адресную строку мобильных браузеров
    background: $bg;
    overflow: hidden;
    position: relative;
}


// ==========================================
// ШАПКА
// ==========================================
.chat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    padding-top: calc(12px + env(safe-area-inset-top, 0px));
    background: $bg;
    border-bottom: 1px solid $border;
    flex-shrink: 0; // 🆕 Запрещаем сжатие, шапка всегда видна
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);

    position: fixed;
    top: 0;
    z-index: 1021;
    width: 100%;
    height: 85px;
}


.back-btn,
.header-action {
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
        background: $border;
        transform: scale(1.05);
    }

    &:active {
        transform: scale(0.95);
    }
}

.header-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    min-width: 0;
    padding: 4px 8px;
    border-radius: 12px;
    transition: background 0.2s;

    &:hover {
        background: rgba($primary, 0.05);
    }

    &:active {
        background: rgba($primary, 0.1);
    }
}

.header-avatar {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 2px solid white;

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
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.online-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: $success;
    border: 2px solid $bg;
    box-shadow: 0 0 0 2px rgba($success, 0.2);
}

.header-text {
    flex: 1;
    min-width: 0;
}

.header-name {
    font-weight: 700;
    font-size: 1rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.header-status {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: $text-muted;
    margin-top: 2px;

    .online-indicator {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: $success;
        animation: pulse 2s ease-in-out infinite;
    }

    .online-text {
        color: $success;
        font-weight: 600;
    }

    .offline-text {
        font-style: italic;
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

// ==========================================
// СООБЩЕНИЯ
// ==========================================
.messages-container {
    flex: 1; // 🆕 Занимает всё доступное пространство между шапкой и вводом
    overflow-y: auto;
    overflow-x: hidden;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding-bottom: 100px;
    -webkit-overflow-scrolling: touch;
    // 🆕 УБРАНО: scroll-behavior: smooth; (оно вызывает "прыжки" при подгрузке истории)
    // 🆕 УБРАНО: padding-bottom: 100px; (больше не нужен, так как ввод теперь в flex-потоке)
}


.messages-loader {
    display: flex;
    justify-content: center;
    padding: 12px;
}

.loader-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid $border;
    border-top-color: $primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.load-more-btn {
    align-self: center;
    padding: 6px 14px;
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 20px;
    color: $primary;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    margin-bottom: 8px;

    &:hover {
        background: rgba($primary, 0.1);
    }
}

.empty-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px 20px;

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

    p {
        margin: 0 0 4px;
        font-weight: 700;
        font-size: 1.1rem;
    }

    span {
        color: $text-muted;
        font-size: 0.9rem;
    }
}

.date-separator {
    display: flex;
    justify-content: center;
    margin: 16px 0 8px;

    span {
        padding: 4px 12px;
        background: $bg-secondary;
        border-radius: 12px;
        font-size: 0.75rem;
        color: $text-muted;
        font-weight: 600;
    }
}

// ==========================================
// ПУЗЫРЬКИ СООБЩЕНИЙ
// ==========================================
.message-bubble {
    display: flex;
    align-items: flex-end;
    gap: 6px;
    max-width: 85%;
    margin-bottom: 2px;
    animation: messageIn 0.3s ease;

    // ... ваши существующие стили для .is-mine, .is-order и т.д. ...

    // 🆕 СТИЛИ ДЛЯ СИСТЕМНЫХ СООБЩЕНИЙ
    &.is-system {
        align-self: center;
        max-width: 90%;
        margin: 12px 0;
        animation: fadeInScale 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); // Пружинистая анимация появления

        // Полностью сбрасываем стили обычного пузыря
        .message-content {
            display: none; // Скрываем стандартный контент, так как у нас свой блок
        }

        .system-message-content {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;

            // Эффект "стекла" или мягкого фона
            background: rgba(var(--bs-secondary-color-rgb, 107, 114, 128), 0.08);
            border: 1px solid rgba(var(--bs-secondary-color-rgb, 107, 114, 128), 0.12);
            backdrop-filter: blur(8px);

            border-radius: 20px; // Форма пилюли
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--bs-secondary-color, #6b7280);
            text-align: center;
            line-height: 1.4;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);

            i {
                font-size: 0.8rem;
                opacity: 0.7;
                flex-shrink: 0;
            }

            // Делаем ссылки внутри системных сообщений аккуратными
            a {
                color: var(--bs-primary, #667eea);
                text-decoration: underline;
                font-weight: 600;
            }
        }
    }
}

// 🆕 Новая анимация для системных сообщений (плавное появление с легким увеличением)
@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(5px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

// ==========================================
// 🎨 КАРТОЧКА ЗАКАЗА — СОВРЕМЕННЫЙ СТИЛЬ
// ==========================================
.order-card {
    display: flex;
    gap: 14px;
    width: 100%;
    padding: 16px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 18px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04),
    0 4px 16px rgba(0, 0, 0, 0.04);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06),
        0 8px 24px rgba(0, 0, 0, 0.08);
        border-color: rgba($primary, 0.3);
    }
}

// Иконка в цветном кружке
.order-icon-wrap {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, $primary 0%, #7c8cf5 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba($primary, 0.25);
}

// Основной контент
.order-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

// Шапка: номер заказа + время
.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.order-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: $text;
    letter-spacing: -0.01em;
}

.order-time {
    font-size: 0.7rem;
    color: $text-muted;
    flex-shrink: 0;
    font-weight: 500;
}

// Описание заказа
.order-description {
    font-size: 0.85rem;
    line-height: 1.5;
    color: $text-muted;
    white-space: pre-wrap;
    word-break: break-word;
}

// Кнопка "Скачать чек"
.order-download {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba($primary, 0.08);
    color: $primary;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    align-self: flex-start;
    margin-top: 4px;

    &:hover {
        background: rgba($primary, 0.15);
        transform: translateX(2px);

        .download-arrow {
            transform: translateX(3px);
        }
    }

    i {
        font-size: 0.85rem;
    }

    .download-arrow {
        font-size: 0.7rem;
        transition: transform 0.2s;
    }
}

// ==========================================
// 📎 КАРТОЧКА ВЛОЖЕНИЯ — СОВРЕМЕННЫЙ СТИЛЬ
// ==========================================
.attachment-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    text-decoration: none;
    color: $text;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 8px;
    max-width: 300px;
    position: relative;
    overflow: hidden;

    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border-color: rgba($primary, 0.3);

        .attachment-download-btn {
            background: $primary;
            color: white;
            transform: scale(1.05);
        }
    }

    &:active {
        transform: translateY(0);
    }

    // 🎨 Цвета для разных типов файлов (акцентная полоса слева)
    &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: $text-muted;
        border-radius: 3px 0 0 3px;
        transition: width 0.2s;
    }

    &:hover::before {
        width: 4px;
    }

    &.attachment-pdf::before {
        background: #ef4444;
    }

    &.attachment-image::before {
        background: #22c55e;
    }

    &.attachment-doc::before {
        background: #3b82f6;
    }

    &.attachment-xls::before {
        background: #10b981;
    }

    &.attachment-zip::before {
        background: #f59e0b;
    }

    &.attachment-video::before {
        background: #8b5cf6;
    }

    &.attachment-audio::before {
        background: #ec4899;
    }

    // 🔄 Инверсия внутри "своего" пузыря (синий фон)
    .is-mine & {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.25);
        color: white;
        backdrop-filter: blur(8px);

        &:hover {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .attachment-name {
            color: white;
        }

        .attachment-meta {
            color: rgba(255, 255, 255, 0.8);
        }

        .attachment-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .attachment-download-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;

            &:hover {
                background: white;
                color: $primary;
            }
        }
    }
}

// 🎯 Иконка файла в цветном квадратике
.attachment-icon {
    flex-shrink: 0;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    transition: all 0.2s;

    // Цвета иконок по типам
    .attachment-pdf & {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
    }

    .attachment-image & {
        background: rgba(34, 197, 94, 0.12);
        color: #22c55e;
    }

    .attachment-doc & {
        background: rgba(59, 130, 246, 0.12);
        color: #3b82f6;
    }

    .attachment-xls & {
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
    }

    .attachment-zip & {
        background: rgba(245, 158, 11, 0.12);
        color: #f59e0b;
    }

    .attachment-video & {
        background: rgba(139, 92, 246, 0.12);
        color: #8b5cf6;
    }

    .attachment-audio & {
        background: rgba(236, 72, 153, 0.12);
        color: #ec4899;
    }
}

// ℹ️ Инфо о файле
.attachment-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.attachment-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

.attachment-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.7rem;
    color: $text-muted;
    font-weight: 500;

    .attachment-type {
        text-transform: uppercase;
        letter-spacing: 0.03em;
        opacity: 0.8;
    }
}

// 📥 Кнопка скачивания
.attachment-download-btn {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: $bg-secondary;
    border: 1px solid $border;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: all 0.2s;

    &:hover {
        background: $primary;
        color: white;
        border-color: $primary;
    }

    &:active {
        transform: scale(0.95);
    }
}

// ==========================================
// ИНДИКАТОР "ПЕЧАТАЕТ..."
// ==========================================
.typing-indicator {
    align-self: flex-start;
    padding: 4px 0;
}

.typing-bubble {
    display: flex;
    gap: 4px;
    padding: 10px 14px;
    background: $bg-secondary;
    border-radius: 16px;
    border-bottom-left-radius: 4px;

    span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: $text-muted;
        animation: typing 1.4s infinite;

        &:nth-child(2) {
            animation-delay: 0.2s;
        }

        &:nth-child(3) {
            animation-delay: 0.4s;
        }
    }
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.5;
    }
    30% {
        transform: translateY(-6px);
        opacity: 1;
    }
}

// ==========================================
// 🆕 BOTTOM SHEET
// ==========================================
.sheet-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 3000;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.sheet-container {
    width: 100%;
    max-width: 500px;
    background: $bg;
    border-radius: 24px 24px 0 0;
    overflow: hidden;
    animation: sheetSlideUp 0.3s cubic-bezier(0.32, 0.72, 0, 1);
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    // Safe area для iPhone
    padding-bottom: env(safe-area-inset-bottom, 0px);
}

@keyframes sheetSlideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.sheet-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 20px 16px;
}

.sheet-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 2px solid white;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.sheet-info {
    flex: 1;
    min-width: 0;
}

.sheet-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.sheet-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $text-muted;

    .online-dot-small {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: $success;
    }
}

.sheet-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg-secondary;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover {
        background: $danger;
        color: white;
    }
}

.sheet-divider {
    height: 1px;
    background: $border;
    margin: 0 20px;
}

.sheet-actions {
    padding: 8px 12px;
    overflow-y: auto;
}

.sheet-action {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    padding: 14px 12px;
    background: transparent;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    color: $text;

    &:hover {
        background: $bg-secondary;
    }

    &:active {
        transform: scale(0.98);
    }

    &.danger {
        color: $danger;

        .sheet-action-title {
            color: $danger;
        }

        .sheet-action-desc {
            color: rgba($danger, 0.7);
        }

        .sheet-action-icon {
            background: rgba($danger, 0.1);
            color: $danger;
        }

        .sheet-action-arrow {
            color: $danger;
        }

        &:hover {
            background: rgba($danger, 0.05);
        }
    }
}

.sheet-action-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: $bg-secondary;
    color: $text;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;

    &.attachments {
        background: rgba($primary, 0.1);
        color: $primary;
    }

    &.archive {
        background: rgba($warning, 0.1);
        color: $warning;
    }

    &.clear {
        background: rgba(#8b5cf6, 0.1);
        color: #8b5cf6;
    }

    &.delete {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.sheet-action-info {
    flex: 1;
    min-width: 0;
}

.sheet-action-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: $text;
    margin-bottom: 2px;
}

.sheet-action-desc {
    font-size: 0.75rem;
    color: $text-muted;
    line-height: 1.3;
}

.sheet-action-badge {
    padding: 3px 10px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.sheet-action-arrow {
    color: $text-muted;
    font-size: 0.8rem;
    flex-shrink: 0;
}

// Анимация bottom sheet
.sheet-fade-enter-active {
    transition: opacity 0.3s ease;
}

.sheet-fade-leave-active {
    transition: opacity 0.2s ease;
}

.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

// ==========================================
// МОДАЛКА ВЛОЖЕНИЙ
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
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

        i {
            color: $primary;
        }
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
    gap: 14px;
    padding: 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 14px;
    text-decoration: none;
    color: $text;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;

    // Акцентная полоса слева (как в карточке сообщения)
    &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: $text-muted;
    }

    &:hover {
        transform: translateX(3px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        border-color: rgba($primary, 0.3);
    }

    &.type-pdf::before {
        background: #ef4444;
    }

    &.type-image::before {
        background: #22c55e;
    }

    &.type-doc::before {
        background: #3b82f6;
    }

    &.type-xls::before {
        background: #10b981;
    }

    &.type-zip::before {
        background: #f59e0b;
    }

    &.type-video::before {
        background: #8b5cf6;
    }

    &.type-audio::before {
        background: #ec4899;
    }
}

.attachment-icon-large {
    flex-shrink: 0;
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;

    .type-pdf & {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
    }

    .type-image & {
        background: rgba(34, 197, 94, 0.12);
        color: #22c55e;
    }

    .type-doc & {
        background: rgba(59, 130, 246, 0.12);
        color: #3b82f6;
    }

    .type-xls & {
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
    }

    .type-zip & {
        background: rgba(245, 158, 11, 0.12);
        color: #f59e0b;
    }

    .type-video & {
        background: rgba(139, 92, 246, 0.12);
        color: #8b5cf6;
    }

    .type-audio & {
        background: rgba(236, 72, 153, 0.12);
        color: #ec4899;
    }
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
    color: $text;
}

.attachment-meta {
    display: flex;
    gap: 10px;
    font-size: 0.75rem;
    color: $text-muted;
    font-weight: 500;
}

.attachment-download {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: $bg-secondary;
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: all 0.2s;

    .attachment-item:hover & {
        background: $primary;
        color: white;
        transform: scale(1.05);
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
// МОДАЛКА ПОДТВЕРЖДЕНИЯ
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

    &.warning {
        background: linear-gradient(135deg, $warning, #d97706);
    }

    &.danger {
        background: linear-gradient(135deg, $danger, #dc2626);
    }

    &.success {
        background: linear-gradient(135deg, $success, #16a34a);
    }
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

        &:hover {
            background: #d97706;
        }
    }

    &.danger {
        background: $danger;
        color: white;

        &:hover {
            background: #dc2626;
        }
    }

    &.success {
        background: $success;
        color: white;

        &:hover {
            background: #16a34a;
        }
    }
}

// Анимации
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
    .chat-header {
        padding: 10px 12px;
        gap: 10px;
    }

    .header-avatar {
        width: 40px;
        height: 40px;
    }

    .header-name {
        font-size: 0.95rem;
    }

    .message-bubble {
        max-width: 90%;

        &.is-order {
            max-width: 98%;
        }
    }

    .order-card {
        font-size: 0.8rem;
    }

    .attachment-card {
        max-width: 240px;
    }

    .sheet-container {
        max-width: 100%;
        border-radius: 20px 20px 0 0;
    }

    .sheet-header {
        padding: 16px 16px 12px;
    }

    .sheet-avatar {
        width: 48px;
        height: 48px;
        font-size: 1.1rem;
    }

    .sheet-name {
        font-size: 1rem;
    }

    .sheet-action {
        padding: 12px 10px;
        gap: 12px;
    }

    .sheet-action-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .sheet-action-title {
        font-size: 0.9rem;
    }

    .sheet-action-desc {
        font-size: 0.7rem;
    }
}

.message-content {
    padding: 8px 12px;
    border-radius: 16px;
    background: $bg-secondary;
    max-width: 100%;
    word-wrap: break-word;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);

    .is-mine & {
        background: $primary;
        color: white;
        border-bottom-right-radius: 4px;
    }

    &:not(.is-mine .message-content) {
        border-bottom-left-radius: 4px;
    }
}

.message-text {
    font-size: 0.95rem;
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
}

.message-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    justify-content: flex-end;
    margin-top: 2px;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
}

.message-status {
    font-size: 0.7rem;
    display: flex;
    align-items: center;

    .status-read {
        color: #60a5fa;
    }

    .status-delivered {
        color: rgba(255, 255, 255, 0.9);
    }

    .status-sent {
        color: rgba(255, 255, 255, 0.7);
    }

    .error-icon {
        color: $danger;
    }
}

.retry-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: $danger;
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    transition: transform 0.2s;

    &:hover {
        transform: rotate(180deg);
    }
}

/*::v-deep(.chat-input-wrapper) {
    flex-shrink: 0; // 🆕 Запрещаем сжатие, поле ввода всегда прижато к низу
    position: relative !important; // 🆕 Отменяем position: fixed из ChatInput.vue, если он там есть
    width: 100% !important;
    bottom: auto !important;
    z-index: 20;
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.03); // Легкая тень сверху для разделения
}*/
// ==========================================
// 🆕 ИМЯ ОТПРАВИТЕЛЯ НАД СООБЩЕНИЕМ
// ==========================================
.message-sender-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: $primary; // Цвет для администратора
    margin-bottom: 4px;
    margin-left: 4px;
    display: flex;
    align-items: center;
    gap: 4px;

    // Если отправитель система, делаем текст серым и нейтральным
    .message-bubble.is-system & {
        color: $text-muted;
        font-weight: 500;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    // Добавляем маленькую иконку короны для админа (опционально, через CSS)
    .message-bubble:not(.is-system) &::before {
        content: '\f023'; // Иконка щита/админа из FontAwesome (fa-solid fa-user-shield)
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 0.7rem;
        opacity: 0.7;
    }
}


// ==========================================
// 🆕 ОБНОВЛЕННЫЕ СТИЛИ META (ВРЕМЯ + ИМЯ)
// ==========================================
.message-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: flex-end;
    margin-top: 4px;
    flex-wrap: wrap; // Позволяет перенос, если имя длинное

    .sender-name-inline {
        font-size: 0.7rem;
        font-weight: 600;
        color: $primary;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-right: auto; // Прижимает время и статус вправо, а имя влево


        &:hover {
            background: rgba($primary, 0.15);
            text-decoration: underline;
        }

        // Скрываем для своих сообщений (на всякий случай)
        .is-mine & {
            display: none;
        }

        // Для системных сообщений делаем нейтральным
        .is-system & {
            color: $text-muted;
            font-weight: 500;

            &::before {
                content: '\f023'; // или fa-robot \f544
                opacity: 0.5;
            }

            &:hover {
                background: rgba($text-muted, 0.1);
                text-decoration: none;
            }
        }
    }

    .message-time {
        font-size: 0.7rem;
        opacity: 0.7;
        white-space: nowrap;
    }

    .message-status {
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        white-space: nowrap;

        .status-read { color: #60a5fa; }
        .status-delivered { color: rgba(255, 255, 255, 0.9); }
        .status-sent { color: rgba(255, 255, 255, 0.7); }
        .error-icon { color: $danger; }
    }
}

// ==========================================
// 🆕 МОДАЛКА ИНФОРМАЦИИ О СОБЕСЕДНИКЕ
// ==========================================
.interlocutor-modal {
    background: $bg;
    border-radius: 20px;
    width: 100%;
    max-width: 360px;
    overflow: hidden;
    animation: modalSlideUp 0.3s ease;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid $border;

        h3 {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            margin: 0;

            i { color: $primary; }
        }
    }

    .modal-body {
        padding: 24px;
    }

    .interlocutor-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        font-weight: 700;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    .interlocutor-name {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 4px;
        color: $text;
    }

    .interlocutor-role {
        font-size: 0.85rem;
        color: $primary;
        font-weight: 600;
        margin: 0 0 20px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .interlocutor-details {
        display: flex;
        flex-direction: column;
        gap: 12px;
        text-align: left;
        background: $bg-secondary;
        padding: 16px;
        border-radius: 12px;

        .detail-row {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            color: $text;

            i {
                width: 20px;
                text-align: center;
                color: $text-muted;
                font-size: 0.9rem;
            }
        }
    }
}

// ==========================================
// 🆕 READ RECEIPT
// ==========================================
.read-receipt {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 3px;
    margin-left: auto;
    padding: 2px 10px;
    background: rgba($primary, 0.08);
    color: $primary;
    border-radius: 10px;
    font-size: 0.72rem;
    font-weight: 500;
    animation: readReceiptIn 0.3s ease;
    max-width: fit-content;

    i {
        font-size: 0.7rem;
        opacity: 0.8;
    }
}

@keyframes readReceiptIn {
    from {
        opacity: 0;
        transform: translateY(-4px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.message-status {
    .status-read {
        color: #60a5fa !important;
        animation: readPulse 0.3s ease;
    }
}

@keyframes readPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.25); }
    100% { transform: scale(1); }
}
</style>
