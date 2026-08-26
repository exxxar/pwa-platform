<template>
    <div class="products-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Товары</h1>
                <p class="page-subtitle">Управление товарами и меню</p>
            </div>
            <Button
                v-if="authStore.hasPermission('products.create')"
                variant="primary"
                @click="$router.push({ name: 'admin.products.create' })"
            >
                <template #icon>➕</template>
                Создать товар
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
                        placeholder="Поиск по названию или SKU..."
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
                        v-model="filterCategory"
                        :options="categoryOptions"
                        placeholder="Все категории"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="filterActive"
                        :options="activeOptions"
                        placeholder="Статус"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="filterStopList"
                        :options="stopListOptions"
                        placeholder="Стоп-лист"
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
                <div class="product-name-cell">
                    <div v-if="row.images && row.images.length > 0" class="product-image">
                        <img :src="row.images[0]" :alt="row.name" />
                    </div>
                    <div v-else class="product-image-placeholder">
                        📦
                    </div>
                    <div class="product-info">
                        <div class="product-name">{{ row.name }}</div>
                        <div class="product-sku">{{ row.sku || 'Без SKU' }}</div>
                    </div>
                </div>
            </template>

            <template #cell-categories="{ row }">
                <div class="categories-cell">
                    <StatusBadge
                        v-for="category in row.categories?.slice(0, 2)"
                        :key="category.id"
                        variant="info"
                        size="sm"
                    >
                        {{ category.name }}
                    </StatusBadge>
                    <span v-if="row.categories?.length > 2" class="more-categories">
                        +{{ row.categories.length - 2 }}
                    </span>
                    <span v-if="!row.categories || row.categories.length === 0" class="text-muted">
                        Нет категорий
                    </span>
                </div>
            </template>

            <template #cell-price="{ row }">
                <div class="price-cell">
                    <div class="current-price">{{ formatCurrency(row.price) }}</div>
                    <div v-if="row.old_price" class="old-price">
                        {{ formatCurrency(row.old_price) }}
                    </div>
                </div>
            </template>

            <template #cell-is_active="{ row }">
                <StatusBadge :variant="row.is_active ? 'success' : 'danger'">
                    {{ row.is_active ? 'Активен' : 'Неактивен' }}
                </StatusBadge>
            </template>

            <template #cell-in_stop_list="{ row }">
                <StatusBadge :variant="row.in_stop_list ? 'danger' : 'success'">
                    {{ row.in_stop_list ? 'В стоп-листе' : 'Доступен' }}
                </StatusBadge>
            </template>

            <template #cell-rating="{ row }">
                <div class="rating-cell">
                    <span class="stars">
                        {{ '★'.repeat(Math.round(row.average_rating || 0)) }}
                    </span>
                    <span class="rating-value">{{ (row.average_rating || 0).toFixed(1) }}</span>
                    <span class="reviews-count">({{ row.reviews_count || 0 }})</span>
                </div>
            </template>

            <template #cell-actions="{ row }">
                <div class="actions-cell">
                    <Button
                        v-if="authStore.hasPermission('products.update')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.products.edit', params: { id: row.id } })"
                        title="Редактировать"
                    >
                        ✏️
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('products.update')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="toggleStopList(row)"
                        :title="row.in_stop_list ? 'Убрать из стоп-листа' : 'В стоп-лист'"
                    >
                        {{ row.in_stop_list ? '✅' : '🚫' }}
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('products.update')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="toggleActive(row)"
                        :title="row.is_active ? 'Деактивировать' : 'Активировать'"
                    >
                        {{ row.is_active ? '🔴' : '🟢' }}
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('products.delete')"
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

        <!-- Delete Modal -->
        <Modal
            v-model="showDeleteModal"
            title="Удалить товар"
            size="sm"
        >
            <p>
                Вы уверены, что хотите удалить товар <strong>{{ selectedProduct?.name }}</strong>?
                Это действие нельзя отменить.
            </p>
            <template #footer>
                <Button variant="outline" @click="showDeleteModal = false">
                    Отмена
                </Button>
                <Button
                    variant="danger"
                    :loading="deleting"
                    @click="deleteProduct"
                >
                    Удалить
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
} = useTable('/products', {
    perPage: 15,
    sortBy: 'created_at',
    sortDir: 'desc',
})

// Filters
const searchQuery = ref('')
const filterTenant = ref('')
const filterCategory = ref('')
const filterActive = ref('')
const filterStopList = ref('')

const tenantOptions = ref([])
const categoryOptions = ref([])
const activeOptions = [
    { value: '1', label: 'Активные' },
    { value: '0', label: 'Неактивные' },
]
const stopListOptions = [
    { value: '1', label: 'В стоп-листе' },
    { value: '0', label: 'Доступные' },
]

const hasActiveFilters = computed(() => {
    return searchQuery.value || filterTenant.value || filterCategory.value || filterActive.value || filterStopList.value
})

// Load tenants and categories for filters
const loadFilters = async () => {
    try {
        const [tenantsResponse, categoriesResponse] = await Promise.all([
            api.get('/tenants', { per_page: 100 }),
            api.get('/categories', { per_page: 100 }),
        ])

        const tenants = tenantsResponse.data || tenantsResponse
        tenantOptions.value = tenants.map(t => ({
            value: t.id,
            label: t.name,
        }))

        const categories = categoriesResponse.data || categoriesResponse
        categoryOptions.value = categories.map(c => ({
            value: c.id,
            label: c.name,
        }))
    } catch (error) {
        console.error('Ошибка загрузки фильтров:', error)
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

    if (filterCategory.value) {
        params.category_id = filterCategory.value
    } else {
        delete params.category_id
    }

    if (filterActive.value) {
        params.is_active = filterActive.value === '1'
    } else {
        delete params.is_active
    }

    if (filterStopList.value) {
        params.in_stop_list = filterStopList.value === '1'
    } else {
        delete params.in_stop_list
    }

    params.page = 1
    fetchData()
}

const resetFilters = () => {
    searchQuery.value = ''
    filterTenant.value = ''
    filterCategory.value = ''
    filterActive.value = ''
    filterStopList.value = ''
    resetTableFilters()
}

// Columns
const columns = [
    { key: 'name', label: 'Товар', sortable: true },
    { key: 'categories', label: 'Категории' },
    { key: 'price', label: 'Цена', sortable: true },
    { key: 'is_active', label: 'Статус', sortable: true },
    { key: 'in_stop_list', label: 'Стоп-лист', sortable: true },
    { key: 'rating', label: 'Рейтинг', sortable: true },
    { key: 'actions', label: 'Действия', width: '200px' },
]

// Sort handler
const handleSort = ({ key, dir }) => {
    setSort(key, dir)
}

// Toggle actions
const toggleStopList = async (product) => {
    try {
        await api.patch(`/products/${product.id}/toggle-stop-list`)
        notifications.success(product.in_stop_list ? 'Товар убран из стоп-листа' : 'Товар добавлен в стоп-лист')
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при изменении статуса')
    }
}

const toggleActive = async (product) => {
    try {
        await api.patch(`/products/${product.id}/toggle-active`)
        notifications.success(product.is_active ? 'Товар деактивирован' : 'Товар активирован')
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при изменении статуса')
    }
}

// Delete
const showDeleteModal = ref(false)
const selectedProduct = ref(null)
const deleting = ref(false)

const openDeleteModal = (product) => {
    selectedProduct.value = product
    showDeleteModal.value = true
}

const deleteProduct = async () => {
    deleting.value = true
    try {
        await api.del(`/products/${selectedProduct.value.id}`)
        notifications.success('Товар успешно удален')
        showDeleteModal.value = false
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при удалении товара')
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

onMounted(() => {
    loadFilters()
})
</script>

<style scoped>
.products-page {
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

.product-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-image {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-image-placeholder {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    background: #f7fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 2px;
}

.product-sku {
    font-size: 12px;
    color: #718096;
    font-family: 'Courier New', monospace;
}

.categories-cell {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
}

.more-categories {
    font-size: 11px;
    color: #718096;
    font-weight: 500;
}

.text-muted {
    color: #a0aec0;
    font-size: 13px;
}

.price-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.current-price {
    font-weight: 700;
    color: #48bb78;
    font-size: 15px;
}

.old-price {
    font-size: 12px;
    color: #a0aec0;
    text-decoration: line-through;
}

.rating-cell {
    display: flex;
    align-items: center;
    gap: 4px;
}

.stars {
    color: #fbbf24;
    font-size: 14px;
}

.rating-value {
    font-weight: 600;
    color: #2d3748;
    font-size: 13px;
}

.reviews-count {
    font-size: 11px;
    color: #718096;
}

.actions-cell {
    display: flex;
    gap: 6px;
}
</style>
