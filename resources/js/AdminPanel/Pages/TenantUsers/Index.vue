<template>
    <div class="tenant-users-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Пользователи</h1>
                <p class="page-subtitle">Управление пользователями тенантов</p>
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
                    <Input
                        v-model="searchQuery"
                        placeholder="Поиск по имени, телефону или email..."
                        @input="debouncedSearch"
                    >
                        <template #prefix>🔍</template>
                    </Input>

                    <Select
                        v-model="filterTenant"
                        :options="tenantOptions"
                        placeholder="Все тенанты"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="filterActive"
                        :options="activeOptions"
                        placeholder="Статус"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="filterVip"
                        :options="vipOptions"
                        placeholder="VIP статус"
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

            <template #cell-name="{ row }">
                <div class="user-name-cell">
                    <div class="user-avatar">
                        <img v-if="row.avatar" :src="row.avatar" :alt="row.name" />
                        <span v-else>{{ row.name?.charAt(0).toUpperCase() }}</span>
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ row.name }}</div>
                        <div class="user-phone">{{ row.phone || 'Не указан' }}</div>
                    </div>
                </div>
            </template>

            <template #cell-is_active="{ row }">
                <StatusBadge :variant="row.is_active && !row.is_blocked ? 'success' : 'danger'">
                    {{ row.is_blocked ? 'Заблокирован' : (row.is_active ? 'Активен' : 'Неактивен') }}
                </StatusBadge>
            </template>

            <template #cell-is_vip="{ row }">
                <div class="vip-cell">
                    <StatusBadge v-if="row.has_active_vip" variant="warning">
                        VIP
                    </StatusBadge>
                    <span v-else class="text-muted">-</span>
                    <div v-if="row.vip_days_left !== null" class="vip-days">
                        {{ row.vip_days_left }} дн.
                    </div>
                </div>
            </template>

            <template #cell-cashback_balance="{ row }">
                <div class="cashback-cell">
                    {{ formatCurrency(row.cashback_balance) }}
                </div>
            </template>

            <template #cell-orders_count="{ row }">
                <div class="orders-cell">
                    {{ row.orders_count || 0 }}
                </div>
            </template>

            <template #cell-created_at="{ row }">
                {{ formatDate(row.created_at) }}
            </template>

            <template #cell-actions="{ row }">
                <div class="actions-cell">
                    <Button
                        v-if="authStore.hasPermission('tenant_users.view')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.tenant-users.show', params: { id: row.id } })"
                        title="Просмотр"
                    >
                        👁️
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('tenant_users.update')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.tenant-users.edit', params: { id: row.id } })"
                        title="Редактировать"
                    >
                        ✏️
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('tenant_users.block')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="openToggleBlockModal(row)"
                        :title="row.is_blocked ? 'Разблокировать' : 'Заблокировать'"
                    >
                        {{ row.is_blocked ? '🔓' : '🔒' }}
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('tenant_users.grant_vip') && !row.has_active_vip"
                        variant="warning"
                        size="sm"
                        icon-only
                        @click="openGrantVipModal(row)"
                        title="Выдать VIP"
                    >
                        👑
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('tenant_users.revoke_vip') && row.has_active_vip"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="revokeVip(row)"
                        title="Отозвать VIP"
                    >
                        🚫
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Toggle Block Modal -->
        <Modal
            v-model="showToggleBlockModal"
            :title="selectedUser?.is_blocked ? 'Разблокировать пользователя' : 'Заблокировать пользователя'"
            size="sm"
        >
            <div class="modal-content">
                <p>
                    Вы уверены, что хотите {{ selectedUser?.is_blocked ? 'разблокировать' : 'заблокировать' }}
                    пользователя <strong>{{ selectedUser?.name }}</strong>?
                </p>
                <div v-if="!selectedUser?.is_blocked" class="form-group">
                    <label class="form-label">Причина блокировки (опционально)</label>
                    <textarea
                        v-model="blockMessage"
                        class="form-textarea"
                        placeholder="Укажите причину блокировки..."
                        rows="3"
                    ></textarea>
                </div>
            </div>
            <template #footer>
                <Button variant="outline" @click="showToggleBlockModal = false">
                    Отмена
                </Button>
                <Button
                    :variant="selectedUser?.is_blocked ? 'success' : 'danger'"
                    :loading="togglingBlock"
                    @click="toggleBlock"
                >
                    Подтвердить
                </Button>
            </template>
        </Modal>

        <!-- Grant VIP Modal -->
        <Modal
            v-model="showGrantVipModal"
            title="Выдать VIP статус"
            size="sm"
        >
            <div class="modal-content">
                <p>
                    Выдать VIP статус пользователю <strong>{{ selectedUser?.name }}</strong>?
                </p>
                <div class="form-group">
                    <label class="form-label">Срок действия (дней)</label>
                    <Input
                        v-model.number="vipDays"
                        type="number"
                        placeholder="30"
                        hint="Оставьте пустым для бессрочного VIP"
                    />
                </div>
            </div>
            <template #footer>
                <Button variant="outline" @click="showGrantVipModal = false">
                    Отмена
                </Button>
                <Button
                    variant="warning"
                    :loading="grantingVip"
                    @click="grantVip"
                >
                    Выдать VIP
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
import Input from '../../components/UI/Input.vue'
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
} = useTable('/tenant-users', {
    perPage: 15,
    sortBy: 'created_at',
    sortDir: 'desc',
})

// Filters
const searchQuery = ref('')
const filterTenant = ref('')
const filterActive = ref('')
const filterVip = ref('')

const tenantOptions = ref([])
const activeOptions = [
    { value: '1', label: 'Активные' },
    { value: '0', label: 'Неактивные' },
]
const vipOptions = [
    { value: '1', label: 'VIP' },
    { value: '0', label: 'Обычные' },
]

const hasActiveFilters = computed(() => {
    return searchQuery.value || filterTenant.value || filterActive.value || filterVip.value
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

// Debounced search
let searchTimeout = null
const debouncedSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 500)
}

const applyFilters = () => {
    if (searchQuery.value) {
        params.search = searchQuery.value
    } else {
        delete params.search
    }

    if (filterTenant.value) {
        params.tenant_id = filterTenant.value
    } else {
        delete params.tenant_id
    }

    if (filterActive.value) {
        params.is_active = filterActive.value === '1'
    } else {
        delete params.is_active
    }

    if (filterVip.value) {
        params.is_vip = filterVip.value === '1'
    } else {
        delete params.is_vip
    }

    params.page = 1
    fetchData()
}

const resetFilters = () => {
    searchQuery.value = ''
    filterTenant.value = ''
    filterActive.value = ''
    filterVip.value = ''
    resetTableFilters()
}

// Columns
const columns = [
    { key: 'name', label: 'Пользователь', sortable: true },
    { key: 'is_active', label: 'Статус', sortable: true },
    { key: 'is_vip', label: 'VIP', sortable: true },
    { key: 'cashback_balance', label: 'Кэшбэк', sortable: true },
    { key: 'orders_count', label: 'Заказы', sortable: true },
    { key: 'created_at', label: 'Регистрация', sortable: true },
    { key: 'actions', label: 'Действия', width: '240px' },
]

// Sort handler
const handleSort = ({ key, dir }) => {
    setSort(key, dir)
}

// Toggle block
const showToggleBlockModal = ref(false)
const selectedUser = ref(null)
const togglingBlock = ref(false)
const blockMessage = ref('')

const openToggleBlockModal = (user) => {
    selectedUser.value = user
    blockMessage.value = ''
    showToggleBlockModal.value = true
}

const toggleBlock = async () => {
    togglingBlock.value = true
    try {
        await api.patch(`/tenant-users/${selectedUser.value.id}/toggle-block`, {
            message: blockMessage.value,
        })
        notifications.success(
            selectedUser.value.is_blocked ? 'Пользователь разблокирован' : 'Пользователь заблокирован'
        )
        showToggleBlockModal.value = false
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при изменении статуса')
    } finally {
        togglingBlock.value = false
    }
}

// Grant VIP
const showGrantVipModal = ref(false)
const grantingVip = ref(false)
const vipDays = ref(null)

const openGrantVipModal = (user) => {
    selectedUser.value = user
    vipDays.value = null
    showGrantVipModal.value = true
}

const grantVip = async () => {
    grantingVip.value = true
    try {
        await api.post(`/tenant-users/${selectedUser.value.id}/grant-vip`, {
            days: vipDays.value,
        })
        notifications.success('VIP статус выдан')
        showGrantVipModal.value = false
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при выдаче VIP')
    } finally {
        grantingVip.value = false
    }
}

// Revoke VIP
const revokeVip = async (user) => {
    if (!confirm(`Отозвать VIP статус у пользователя ${user.name}?`)) return

    try {
        await api.post(`/tenant-users/${user.id}/revoke-vip`)
        notifications.success('VIP статус отозван')
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при отзыве VIP')
    }
}

// Helpers
const formatCurrency = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

onMounted(() => {
    loadTenants()
})
</script>

<style scoped>
.tenant-users-page {
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
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}

@media (max-width: 1200px) {
    .filters-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 640px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
}

.user-name-cell {
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

.vip-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.vip-days {
    font-size: 11px;
    color: #718096;
}

.text-muted {
    color: #a0aec0;
}

.cashback-cell {
    font-weight: 600;
    color: #48bb78;
}

.orders-cell {
    font-weight: 600;
    color: #4299e1;
}

.actions-cell {
    display: flex;
    gap: 6px;
}

.modal-content {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
}

.form-textarea {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    transition: all 0.2s;
    outline: none;
}

.form-textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
</style>
