<template>
    <div class="dialogs-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Диалоги</h1>
                <p class="page-subtitle">Поддержка и общение с пользователями</p>
            </div>
        </div>

        <DataTable
            :columns="columns"
            :data="data"
            :loading="loading"
            :pagination="pagination"
            :params="params"
            @sort="handleSort"
            @page="setPage"
        >
            <template #filters>
                <div class="filters-grid">
                    <Select
                        v-model="filterTenant"
                        :options="tenantOptions"
                        placeholder="Все тенанты"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="filterClosed"
                        :options="closedOptions"
                        placeholder="Статус"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="filterUnread"
                        :options="unreadOptions"
                        placeholder="Непрочитанные"
                        @change="applyFilters"
                    />

                    <Button
                        v-if="hasActiveFilters"
                        variant="outline"
                        size="sm"
                        @click="resetFilters"
                    >
                        Сбросить
                    </Button>
                </div>
            </template>

            <template #cell-user="{ row }">
                <div class="user-cell">
                    <div class="user-avatar">
                        <img v-if="row.user?.avatar" :src="row.user.avatar" :alt="row.user.name" />
                        <span v-else>{{ row.user?.name?.charAt(0).toUpperCase() || '?' }}</span>
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ row.user?.name || 'Неизвестный' }}</div>
                        <div class="user-phone">{{ row.user?.phone || '-' }}</div>
                    </div>
                </div>
            </template>

            <template #cell-title="{ row }">
                <div class="title-cell">
                    <div class="dialog-title">{{ row.title || 'Без заголовка' }}</div>
                    <div class="dialog-type">{{ row.type || 'Обычный' }}</div>
                </div>
            </template>

            <template #cell-last_message="{ row }">
                <div class="message-cell">
                    <div class="message-preview">
                        {{ row.last_message?.message || 'Нет сообщений' }}
                    </div>
                    <div class="message-meta">
                        <span class="sender-name">{{ row.last_message?.sender_name || '-' }}</span>
                        <span class="message-time">
                            {{ formatTimeAgo(row.last_message?.created_at) }}
                        </span>
                    </div>
                </div>
            </template>

            <template #cell-unread_count="{ row }">
                <div class="unread-cell">
                    <StatusBadge v-if="row.unread_count > 0" variant="danger" size="sm">
                        {{ row.unread_count }} непрочитанных
                    </StatusBadge>
                    <span v-else class="text-muted">Все прочитано</span>
                </div>
            </template>

            <template #cell-is_closed="{ row }">
                <StatusBadge :variant="row.is_closed ? 'danger' : 'success'">
                    {{ row.is_closed ? 'Закрыт' : 'Открыт' }}
                </StatusBadge>
            </template>

            <template #cell-last_message_at="{ row }">
                <div class="date-cell">
                    <div>{{ formatDate(row.last_message_at) }}</div>
                    <div class="time">{{ formatTime(row.last_message_at) }}</div>
                </div>
            </template>

            <template #cell-actions="{ row }">
                <div class="actions-cell">
                    <Button
                        v-if="authStore.hasPermission('dialogs.view')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.dialogs.show', params: { id: row.id } })"
                        title="Открыть диалог"
                    >
                        💬
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('dialogs.close') && !row.is_closed"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="openCloseModal(row)"
                        title="Закрыть диалог"
                    >
                        ✅
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Close Modal -->
        <Modal
            v-model="showCloseModal"
            title="Закрыть диалог"
            size="sm"
        >
            <p>
                Вы уверены, что хотите закрыть диалог с пользователем
                <strong>{{ selectedDialog?.user?.name }}</strong>?
            </p>
            <template #footer>
                <Button variant="outline" @click="showCloseModal = false">
                    Отмена
                </Button>
                <Button
                    variant="primary"
                    :loading="closing"
                    @click="closeDialog"
                >
                    Закрыть
                </Button>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTable } from '../../composables/useTable'
import { useApi } from '../../composables/useApi'
import { useAuthStore } from '../../stores/auth'
import { useNotifications } from '../../composables/useNotifications'
import Button from '../../components/UI/Button.vue'
import Select from '../../components/UI/Select.vue'
import DataTable from '../../components/UI/DataTable.vue'
import StatusBadge from '../../components/UI/StatusBadge.vue'
import Modal from '../../components/UI/Modal.vue'

const api = useApi()
const authStore = useAuthStore()
const notifications = useNotifications()

const {
    data,
    loading,
    params,
    pagination,
    fetchData,
    setPage,
    setSort,
    setFilter,
    resetFilters: resetTableFilters,
} = useTable('/dialogs', {
    perPage: 15,
    sortBy: 'last_message_at',
    sortDir: 'desc',
})

// Filters
const filterTenant = ref('')
const filterClosed = ref('')
const filterUnread = ref('')

const tenantOptions = ref([])
const closedOptions = [
    { value: '1', label: 'Закрытые' },
    { value: '0', label: 'Открытые' },
]
const unreadOptions = [
    { value: '1', label: 'С непрочитанными' },
    { value: '0', label: 'Все прочитано' },
]

const hasActiveFilters = computed(() => {
    return filterTenant.value || filterClosed.value || filterUnread.value
})

// Load tenants for filter
const loadTenants = async () => {
    try {
        const response = await api.get('/tenants', { per_page: 100 })
        const tenants = response.data || response
        tenantOptions.value = tenants.map(t => ({
            value: t.id,
            label: t.name,
        }))
    } catch (error) {
        console.error('Ошибка загрузки тенантов:', error)
    }
}

const applyFilters = () => {
    if (filterTenant.value) {
        params.tenant_id = filterTenant.value
    } else {
        delete params.tenant_id
    }

    if (filterClosed.value) {
        params.is_closed = filterClosed.value === '1'
    } else {
        delete params.is_closed
    }

    if (filterUnread.value) {
        params.has_unread = filterUnread.value === '1'
    } else {
        delete params.has_unread
    }

    params.page = 1
    fetchData()
}

const resetFilters = () => {
    filterTenant.value = ''
    filterClosed.value = ''
    filterUnread.value = ''
    resetTableFilters()
}

// Columns
const columns = [
    { key: 'user', label: 'Пользователь' },
    { key: 'title', label: 'Заголовок' },
    { key: 'last_message', label: 'Последнее сообщение' },
    { key: 'unread_count', label: 'Непрочитанные' },
    { key: 'is_closed', label: 'Статус', sortable: true },
    { key: 'last_message_at', label: 'Последняя активность', sortable: true },
    { key: 'actions', label: 'Действия', width: '120px' },
]

// Sort handler
const handleSort = ({ key, dir }) => {
    setSort(key, dir)
}

// Close dialog
const showCloseModal = ref(false)
const selectedDialog = ref(null)
const closing = ref(false)

const openCloseModal = (dialog) => {
    selectedDialog.value = dialog
    showCloseModal.value = true
}

const closeDialog = async () => {
    closing.value = true
    try {
        await api.patch(`/dialogs/${selectedDialog.value.id}/close`)
        notifications.success('Диалог успешно закрыт')
        showCloseModal.value = false
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при закрытии диалога')
    } finally {
        closing.value = false
    }
}

// Helpers
const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

const formatTime = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
    })
}

const formatTimeAgo = (date) => {
    if (!date) return ''
    const now = new Date()
    const messageDate = new Date(date)
    const diffMs = now - messageDate
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 1) return 'только что'
    if (diffMins < 60) return `${diffMins} мин. назад`
    if (diffHours < 24) return `${diffHours} ч. назад`
    if (diffDays < 7) return `${diffDays} дн. назад`
    return formatDate(date)
}

onMounted(() => {
    loadTenants()
})
</script>

<style scoped>
.dialogs-page {
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
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

.filters-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}

@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info {
    flex: 1;
}

.user-name {
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 2px;
}

.user-phone {
    font-size: 12px;
    color: #718096;
}

.title-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.dialog-title {
    font-weight: 500;
    color: #2d3748;
}

.dialog-type {
    font-size: 12px;
    color: #718096;
}

.message-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-width: 300px;
}

.message-preview {
    font-size: 13px;
    color: #4a5568;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.message-meta {
    display: flex;
    gap: 8px;
    font-size: 11px;
    color: #718096;
}

.sender-name {
    font-weight: 500;
}

.unread-cell {
    display: flex;
    align-items: center;
}

.text-muted {
    color: #a0aec0;
    font-size: 13px;
}

.date-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.time {
    font-size: 12px;
    color: #718096;
}

.actions-cell {
    display: flex;
    gap: 6px;
}
</style>
