<template>
    <div class="orders-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Заказы</h1>
                <p class="page-subtitle">Управление заказами пользователей</p>
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
                        placeholder="Поиск по ID, имени получателя..."
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
                        v-model="filterStatus"
                        :options="statusOptions"
                        placeholder="Статус"
                        @change="applyFilters"
                    />

                    <Input
                        v-model="filterDateFrom"
                        type="date"
                        placeholder="С даты"
                        @change="applyFilters"
                    />

                    <Input
                        v-model="filterDateTo"
                        type="date"
                        placeholder="По дату"
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

            <template #cell-id="{ row }">
                <div class="order-id">
                    #{{ row.id }}
                </div>
            </template>

            <template #cell-user="{ row }">
                <div class="user-cell">
                    <router-link
                        v-if="row.user"
                        :to="{ name: 'admin.tenant-users.show', params: { id: row.user.id } }"
                        class="user-link"
                    >
                        {{ row.user.name }}
                    </router-link>
                    <span v-else class="text-muted">Неизвестный</span>
                    <div class="user-phone">{{ row.user?.phone || row.receiver_phone }}</div>
                </div>
            </template>

            <template #cell-tenant="{ row }">
                <div class="tenant-cell">
                    {{ row.tenant?.name || '-' }}
                </div>
            </template>

            <template #cell-product_count="{ row }">
                <div class="count-cell">
                    {{ row.product_count }} шт.
                </div>
            </template>

            <template #cell-summary_price="{ row }">
                <div class="price-cell">
                    <div class="main-price">{{ formatCurrency(row.summary_price) }}</div>
                    <div v-if="row.delivery_price" class="delivery-price">
                        + {{ formatCurrency(row.delivery_price) }} доставка
                    </div>
                </div>
            </template>

            <template #cell-status="{ row }">
                <StatusBadge :variant="getStatusVariant(row.status)">
                    {{ getStatusLabel(row.status) }}
                </StatusBadge>
            </template>

            <template #cell-is_paid="{ row }">
                <StatusBadge :variant="row.is_paid ? 'success' : 'warning'">
                    {{ row.is_paid ? 'Оплачен' : 'Не оплачен' }}
                </StatusBadge>
            </template>

            <template #cell-created_at="{ row }">
                <div class="date-cell">
                    <div>{{ formatDate(row.created_at) }}</div>
                    <div class="time">{{ formatTime(row.created_at) }}</div>
                </div>
            </template>

            <template #cell-actions="{ row }">
                <div class="actions-cell">
                    <Button
                        v-if="authStore.hasPermission('orders.view')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.orders.show', params: { id: row.id } })"
                        title="Просмотр"
                    >
                        👁️
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('orders.update_status')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="openChangeStatusModal(row)"
                        title="Изменить статус"
                    >
                        🔄
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Change Status Modal -->
        <Modal
            v-model="showChangeStatusModal"
            title="Изменить статус заказа"
            size="sm"
        >
            <div class="modal-content">
                <p>
                    Заказ <strong>#{{ selectedOrder?.id }}</strong>
                </p>
                <div class="form-group">
                    <label class="form-label">Новый статус</label>
                    <Select
                        v-model="newStatus"
                        :options="statusOptions"
                        placeholder="Выберите статус"
                    />
                </div>
            </div>
            <template #footer>
                <Button variant="outline" @click="showChangeStatusModal = false">
                    Отмена
                </Button>
                <Button
                    variant="primary"
                    :loading="changingStatus"
                    @click="changeStatus"
                >
                    Изменить
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
} = useTable('/orders', {
    perPage: 15,
    sortBy: 'created_at',
    sortDir: 'desc',
})

// Filters
const searchQuery = ref('')
const filterTenant = ref('')
const filterStatus = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')

const tenantOptions = ref([])
const statusOptions = [
    { value: '0', label: 'Новый' },
    { value: '1', label: 'В обработке' },
    { value: '2', label: 'Готовится' },
    { value: '3', label: 'В доставке' },
    { value: '4', label: 'Выполнен' },
    { value: '5', label: 'Отменен' },
]

const hasActiveFilters = computed(() => {
    return searchQuery.value || filterTenant.value || filterStatus.value || filterDateFrom.value || filterDateTo.value
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

    if (filterStatus.value) {
        params.status = filterStatus.value
    } else {
        delete params.status
    }

    if (filterDateFrom.value) {
        params.payed_from = filterDateFrom.value
    } else {
        delete params.payed_from
    }

    if (filterDateTo.value) {
        params.payed_to = filterDateTo.value
    } else {
        delete params.payed_to
    }

    params.page = 1
    fetchData()
}

const resetFilters = () => {
    searchQuery.value = ''
    filterTenant.value = ''
    filterStatus.value = ''
    filterDateFrom.value = ''
    filterDateTo.value = ''
    resetTableFilters()
}

// Columns
const columns = [
    { key: 'id', label: 'ID', sortable: true, width: '80px' },
    { key: 'user', label: 'Пользователь' },
    { key: 'tenant', label: 'Тенант' },
    { key: 'product_count', label: 'Товары', sortable: true },
    { key: 'summary_price', label: 'Сумма', sortable: true },
    { key: 'status', label: 'Статус', sortable: true },
    { key: 'is_paid', label: 'Оплата' },
    { key: 'created_at', label: 'Дата', sortable: true },
    { key: 'actions', label: 'Действия', width: '120px' },
]

// Sort handler
const handleSort = ({ key, dir }) => {
    setSort(key, dir)
}

// Status helpers
const getStatusVariant = (status) => {
    const variants = {
        0: 'info',
        1: 'warning',
        2: 'warning',
        3: 'primary',
        4: 'success',
        5: 'danger',
    }
    return variants[status] || 'default'
}

const getStatusLabel = (status) => {
    const labels = {
        0: 'Новый',
        1: 'В обработке',
        2: 'Готовится',
        3: 'В доставке',
        4: 'Выполнен',
        5: 'Отменен',
    }
    return labels[status] || 'Неизвестный'
}

// Change status
const showChangeStatusModal = ref(false)
const selectedOrder = ref(null)
const changingStatus = ref(false)
const newStatus = ref('')

const openChangeStatusModal = (order) => {
    selectedOrder.value = order
    newStatus.value = order.status
    showChangeStatusModal.value = true
}

const changeStatus = async () => {
    changingStatus.value = true
    try {
        await api.patch(`/orders/${selectedOrder.value.id}/update-status`, {
            status: newStatus.value,
        })
        notifications.success('Статус заказа изменен')
        showChangeStatusModal.value = false
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при изменении статуса')
    } finally {
        changingStatus.value = false
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

const formatTime = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
    })
}

onMounted(() => {
    loadTenants()
})
</script>

<style scoped>
.orders-page {
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
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}

@media (max-width: 1400px) {
    .filters-grid {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
}

.order-id {
    font-weight: 600;
    color: #667eea;
}

.user-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-link {
    font-weight: 600;
    color: #2d3748;
    text-decoration: none;
    transition: color 0.2s;
}

.user-link:hover {
    color: #667eea;
}

.user-phone {
    font-size: 12px;
    color: #718096;
}

.tenant-cell {
    color: #4a5568;
}

.count-cell {
    font-weight: 600;
    color: #4299e1;
}

.price-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.main-price {
    font-weight: 700;
    color: #48bb78;
    font-size: 15px;
}

.delivery-price {
    font-size: 11px;
    color: #718096;
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

.text-muted {
    color: #a0aec0;
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
</style>
