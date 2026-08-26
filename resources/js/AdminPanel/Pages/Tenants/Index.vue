<template>
    <div class="tenants-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Тенанты</h1>
                <p class="page-subtitle">Управление тенантами платформы</p>
            </div>
            <Button
                v-if="authStore.hasPermission('tenants.create')"
                variant="primary"
                @click="$router.push({ name: 'admin.tenants.create' })"
            >
                <template #icon>➕</template>
                Создать тенанта
            </Button>
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
                        placeholder="Поиск по названию или slug..."
                        @input="debouncedSearch"
                    >
                        <template #prefix>🔍</template>
                    </Input>

                    <Select
                        v-model="filterActive"
                        :options="activeOptions"
                        placeholder="Статус"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="filterPlan"
                        :options="planOptions"
                        placeholder="Тариф"
                        @change="applyFilters"
                    />

                    <Button
                        v-if="hasActiveFilters"
                        variant="outline"
                        size="sm"
                        @click="resetFilters"
                    >
                        Сбросить фильтры
                    </Button>
                </div>
            </template>

            <template #cell-name="{ row }">
                <div class="tenant-name-cell">
                    <div class="tenant-avatar">
                        {{ row.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div class="tenant-info">
                        <div class="tenant-name">{{ row.name }}</div>
                        <div class="tenant-slug">/{{ row.slug }}</div>
                    </div>
                </div>
            </template>

            <template #cell-is_active="{ row }">
                <StatusBadge :variant="row.is_active ? 'success' : 'danger'">
                    {{ row.is_active ? 'Активен' : 'Неактивен' }}
                </StatusBadge>
            </template>

            <template #cell-balance="{ row }">
                <div class="balance-cell">
                    {{ formatCurrency(row.balance) }}
                </div>
            </template>

            <template #cell-plan_slug="{ row }">
                <StatusBadge variant="info">
                    {{ row.plan_slug || 'Базовый' }}
                </StatusBadge>
            </template>

            <template #cell-created_at="{ row }">
                {{ formatDate(row.created_at) }}
            </template>

            <template #cell-actions="{ row }">
                <div class="actions-cell">
                    <Button
                        v-if="authStore.hasPermission('tenants.view')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.tenants.show', params: { id: row.id } })"
                        title="Просмотр"
                    >
                        👁️
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('tenants.update')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.tenants.edit', params: { id: row.id } })"
                        title="Редактировать"
                    >
                        ✏️
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('tenants.update')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="openToggleStatusModal(row)"
                        :title="row.is_active ? 'Деактивировать' : 'Активировать'"
                    >
                        {{ row.is_active ? '🚫' : '✅' }}
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('tenants.delete')"
                        variant="danger"
                        size="sm"
                        icon-only
                        @click="openDeleteModal(row)"
                        title="Удалить"
                    >
                        🗑️
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Toggle Status Modal -->
        <Modal
            v-model="showToggleStatusModal"
            title="Изменить статус тенанта"
            size="sm"
        >
            <p>
                Вы уверены, что хотите {{ selectedTenant?.is_active ? 'деактивировать' : 'активировать' }}
                тенант <strong>{{ selectedTenant?.name }}</strong>?
            </p>
            <template #footer>
                <Button variant="outline" @click="showToggleStatusModal = false">
                    Отмена
                </Button>
                <Button
                    variant="primary"
                    :loading="togglingStatus"
                    @click="toggleStatus"
                >
                    Подтвердить
                </Button>
            </template>
        </Modal>

        <!-- Delete Modal -->
        <Modal
            v-model="showDeleteModal"
            title="Удалить тенанта"
            size="sm"
        >
            <p>
                Вы уверены, что хотите удалить тенант <strong>{{ selectedTenant?.name }}</strong>?
                Это действие нельзя отменить.
            </p>
            <template #footer>
                <Button variant="outline" @click="showDeleteModal = false">
                    Отмена
                </Button>
                <Button
                    variant="danger"
                    :loading="deleting"
                    @click="deleteTenant"
                >
                    Удалить
                </Button>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
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
    resetFilters,
} = useTable('/tenants', {
    perPage: 15,
    sortBy: 'created_at',
    sortDir: 'desc',
})

// Filters
const searchQuery = ref('')
const filterActive = ref('')
const filterPlan = ref('')

const activeOptions = [
    { value: '1', label: 'Активные' },
    { value: '0', label: 'Неактивные' },
]

const planOptions = [
    { value: 'basic', label: 'Базовый' },
    { value: 'premium', label: 'Премиум' },
    { value: 'enterprise', label: 'Корпоративный' },
]

const hasActiveFilters = computed(() => {
    return searchQuery.value || filterActive.value || filterPlan.value
})

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
        setFilter('search', searchQuery.value)
    } else {
        delete params.search
    }

    if (filterActive.value) {
        setFilter('is_active', filterActive.value === '1')
    } else {
        delete params.is_active
    }

    if (filterPlan.value) {
        setFilter('plan_slug', filterPlan.value)
    } else {
        delete params.plan_slug
    }

    fetchData()
}

// Columns
const columns = [
    { key: 'name', label: 'Название', sortable: true },
    { key: 'is_active', label: 'Статус', sortable: true },
    { key: 'balance', label: 'Баланс', sortable: true },
    { key: 'plan_slug', label: 'Тариф', sortable: true },
    { key: 'created_at', label: 'Создан', sortable: true },
    { key: 'actions', label: 'Действия', width: '200px' },
]

// Sort handler
const handleSort = ({ key, dir }) => {
    setSort(key, dir)
}

// Toggle status
const showToggleStatusModal = ref(false)
const selectedTenant = ref(null)
const togglingStatus = ref(false)

const openToggleStatusModal = (tenant) => {
    selectedTenant.value = tenant
    showToggleStatusModal.value = true
}

const toggleStatus = async () => {
    togglingStatus.value = true
    try {
        await api.patch(`/tenants/${selectedTenant.value.id}/toggle-status`)
        notifications.success('Статус тенанта изменен')
        showToggleStatusModal.value = false
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при изменении статуса')
    } finally {
        togglingStatus.value = false
    }
}

// Delete
const showDeleteModal = ref(false)
const deleting = ref(false)

const openDeleteModal = (tenant) => {
    selectedTenant.value = tenant
    showDeleteModal.value = true
}

const deleteTenant = async () => {
    deleting.value = true
    try {
        await api.del(`/tenants/${selectedTenant.value.id}`)
        notifications.success('Тенант успешно удален')
        showDeleteModal.value = false
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при удалении тенанта')
    } finally {
        deleting.value = false
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
</script>

<style scoped>
.tenants-page {
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
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}

@media (max-width: 1024px) {
    .filters-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 640px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
}

.tenant-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.tenant-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    flex-shrink: 0;
}

.tenant-info {
    flex: 1;
}

.tenant-name {
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 2px;
}

.tenant-slug {
    font-size: 12px;
    color: #718096;
}

.balance-cell {
    font-weight: 600;
    color: #48bb78;
}

.actions-cell {
    display: flex;
    gap: 8px;
}
</style>
