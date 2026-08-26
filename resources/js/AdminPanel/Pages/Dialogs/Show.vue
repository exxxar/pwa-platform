<template>
    <div class="dialog-show-page">
        <div class="page-header">
            <div class="dialog-header">
                <div class="user-avatar-large">
                    <img v-if="dialog?.user?.avatar" :src="dialog.user.avatar" :alt="dialog.user.name" />
                    <span v-else>{{ dialog?.user?.name?.charAt(0).toUpperCase() || '?' }}</span>
                </div>
                <div>
                    <h1 class="page-title">{{ dialog?.user?.name || 'Загрузка...' }}</h1>
                    <p class="page-subtitle">{{ dialog?.title || 'Диалог' }}</p>
                </div>
            </div>
            <div class="header-actions">
                <Button
                    v-if="authStore.hasPermission('dialogs.close') && dialog && !dialog.is_closed"
                    variant="outline"
                    @click="closeDialog"
                >
                    ✅ Закрыть диалог
                </Button>
                <Button
                    v-if="dialog?.has_unread"
                    variant="info"
                    @click="markAsRead"
                >
                    ✓ Прочитано
                </Button>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <span>Загрузка...</span>
        </div>

        <div v-else-if="dialog" class="dialog-content">
            <!-- Dialog Info -->
            <div class="info-section">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Статус</div>
                        <div class="info-value">
                            <StatusBadge :variant="dialog.is_closed ? 'danger' : 'success'">
                                {{ dialog.is_closed ? 'Закрыт' : 'Открыт' }}
                            </StatusBadge>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Пользователь</div>
                        <div class="info-value">
                            <router-link
                                v-if="dialog.user"
                                :to="{ name: 'admin.tenant-users.show', params: { id: dialog.user.id } }"
                                class="link"
                            >
                                {{ dialog.user.name }}
                            </router-link>
                            <span v-else>Неизвестный</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <div class="info-value">{{ dialog.user?.phone || '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Непрочитанных</div>
                        <div class="info-value">
                            <StatusBadge v-if="dialog.unread_count > 0" variant="danger">
                                {{ dialog.unread_count }}
                            </StatusBadge>
                            <span v-else class="text-muted">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages-section">
                <h2 class="section-title">Сообщения</h2>

                <div v-if="loadingMessages" class="loading-state">
                    <div class="spinner"></div>
                    <span>Загрузка сообщений...</span>
                </div>

                <div v-else class="messages-container">
                    <div v-if="messages.length === 0" class="empty-state">
                        <span class="empty-icon">💬</span>
                        <p>Нет сообщений</p>
                    </div>

                    <div v-else class="messages-list">
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            class="message-item"
                            :class="{
                                'from-admin': message.sender_type === 'admin',
                                'from-user': message.sender_type === 'user',
                                'from-system': message.sender_type === 'system',
                                'unread': !message.is_read && message.sender_type !== 'admin'
                            }"
                        >
                            <div class="message-avatar">
                                <span v-if="message.sender_type === 'admin'">👨‍💼</span>
                                <span v-else-if="message.sender_type === 'system'">⚙️</span>
                                <span v-else>{{ message.sender_name?.charAt(0).toUpperCase() || '?' }}</span>
                            </div>
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="message-sender">{{ message.sender_name }}</span>
                                    <span class="message-time">{{ formatDateTime(message.created_at) }}</span>
                                </div>
                                <div class="message-text">{{ message.message }}</div>
                                <div v-if="message.has_attachment" class="message-attachment">
                                    📎 Вложение ({{ message.attachment_size_formatted }})
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="messagesPagination.last_page > 1" class="messages-pagination">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="messagesPagination.current_page === 1"
                            @click="loadMessages(messagesPagination.current_page - 1)"
                        >
                            ← Назад
                        </Button>
                        <span class="pagination-info">
                            Страница {{ messagesPagination.current_page }} из {{ messagesPagination.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="messagesPagination.current_page === messagesPagination.last_page"
                            @click="loadMessages(messagesPagination.current_page + 1)"
                        >
                            Вперед →
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Reply Form -->
            <div v-if="!dialog.is_closed && authStore.hasPermission('dialogs.reply')" class="reply-section">
                <h2 class="section-title">Ответить</h2>
                <form @submit.prevent="sendReply" class="reply-form">
                    <textarea
                        v-model="replyMessage"
                        class="reply-textarea"
                        placeholder="Введите сообщение..."
                        rows="4"
                        required
                    ></textarea>
                    <div class="reply-actions">
                        <Button
                            type="submit"
                            variant="primary"
                            :loading="sending"
                        >
                            Отправить
                        </Button>
                    </div>
                </form>
            </div>

            <div v-else-if="dialog.is_closed" class="closed-notice">
                <StatusBadge variant="danger" size="lg">
                    Диалог закрыт
                </StatusBadge>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '../../composables/useApi'
import { useAuthStore } from '../../stores/auth'
import { useNotifications } from '../../composables/useNotifications'
import Button from '../../components/UI/Button.vue'
import StatusBadge from '../../components/UI/StatusBadge.vue'

const route = useRoute()
const api = useApi()
const authStore = useAuthStore()
const notifications = useNotifications()

const dialogId = route.params.id
const dialog = ref(null)
const messages = ref([])
const messagesPagination = ref({
    current_page: 1,
    last_page: 1,
})
const loading = ref(true)
const loadingMessages = ref(false)
const sending = ref(false)
const replyMessage = ref('')

const loadDialog = async () => {
    loading.value = true
    try {
        const response = await api.get(`/dialogs/${dialogId}`)
        dialog.value = response.dialog || response
        await loadMessages()
    } catch (error) {
        notifications.error('Ошибка при загрузке диалога')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const loadMessages = async (page = 1) => {
    loadingMessages.value = true
    try {
        const response = await api.get(`/dialogs/${dialogId}`, { per_page: 50, page })
        const messagesData = response.messages || response

        if (messagesData.data) {
            messages.value = messagesData.data
            messagesPagination.value = {
                current_page: messagesData.current_page || 1,
                last_page: messagesData.last_page || 1,
            }
        } else {
            messages.value = messagesData
        }
    } catch (error) {
        notifications.error('Ошибка при загрузке сообщений')
        console.error(error)
    } finally {
        loadingMessages.value = false
    }
}

const sendReply = async () => {
    if (!replyMessage.value.trim()) return

    sending.value = true
    try {
        await api.post(`/dialogs/${dialogId}/reply`, {
            message: replyMessage.value,
        })
        notifications.success('Сообщение отправлено')
        replyMessage.value = ''
        await loadMessages(1) // Загружаем первую страницу с новыми сообщениями
        await loadDialog() // Обновляем информацию о диалоге
    } catch (error) {
        notifications.error('Ошибка при отправке сообщения')
    } finally {
        sending.value = false
    }
}

const closeDialog = async () => {
    if (!confirm('Закрыть этот диалог?')) return

    try {
        await api.patch(`/dialogs/${dialogId}/close`)
        notifications.success('Диалог закрыт')
        await loadDialog()
    } catch (error) {
        notifications.error('Ошибка при закрытии диалога')
    }
}

const markAsRead = async () => {
    try {
        await api.patch(`/dialogs/${dialogId}/mark-as-read`)
        notifications.success('Все сообщения отмечены как прочитанные')
        await loadDialog()
    } catch (error) {
        notifications.error('Ошибка при отметке сообщений')
    }
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('ru-RU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

onMounted(() => {
    loadDialog()
})
</script>

<style scoped>
.dialog-show-page {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
}

.dialog-header {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 32px;
    flex-shrink: 0;
    overflow: hidden;
}

.user-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 8px 0;
}

.page-subtitle {
    font-size: 16px;
    color: #718096;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 60px;
    color: #718096;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e2e8f0;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.info-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.info-label {
    font-size: 13px;
    font-weight: 500;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 15px;
    color: #2d3748;
    font-weight: 500;
}

.link {
    color: #667eea;
    text-decoration: none;
    transition: color 0.2s;
}

.link:hover {
    color: #764ba2;
    text-decoration: underline;
}

.text-muted {
    color: #a0aec0;
}

.messages-section,
.reply-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
}

.messages-container {
    min-height: 300px;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px;
    color: #a0aec0;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.messages-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.message-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    border-radius: 8px;
    background: #f7fafc;
}

.message-item.from-admin {
    background: #e9d8fd;
}

.message-item.from-system {
    background: #feebc8;
}

.message-item.unread {
    border-left: 3px solid #667eea;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.message-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.message-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.message-sender {
    font-weight: 600;
    color: #2d3748;
    font-size: 14px;
}

.message-time {
    font-size: 12px;
    color: #718096;
}

.message-text {
    font-size: 14px;
    color: #4a5568;
    line-height: 1.6;
    white-space: pre-wrap;
}

.message-attachment {
    font-size: 12px;
    color: #718096;
    padding: 8px 12px;
    background: white;
    border-radius: 6px;
    display: inline-block;
}

.messages-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

.pagination-info {
    font-size: 13px;
    color: #718096;
}

.reply-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.reply-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    transition: all 0.2s;
    outline: none;
}

.reply-textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.reply-actions {
    display: flex;
    justify-content: flex-end;
}

.closed-notice {
    background: white;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .header-actions {
        width: 100%;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .message-item {
        flex-direction: column;
    }
}
</style>
