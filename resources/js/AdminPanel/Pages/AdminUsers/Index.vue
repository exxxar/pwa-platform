<template>
    <div class="admin-users-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Администраторы</h1>
                <p class="page-subtitle">Управление глобальными администраторами</p>
            </div>
            <Button
                v-if="authStore.hasPermission('admin_users.create')"
                variant="primary"
                @click="$router.push({ name: 'admin.admin-users.create' })"
            >
                <template #icon>➕</template>
                Создать админа
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
                        placeholder="Поиск по имени или email..."
                        @input="debouncedSearch"
                    >
                        <template #prefix>🔍</template>
                    </Input>

                    <Button
                        v-if="searchQuery"
                        variant="outline"
                        size="sm"
                        @click="resetFilters"
                    >
                        Сбросить
                    </Button>
                </div>
            </template>

            <template #cell-name="{ row }">
                <div class="admin-name-cell">
                    <div class="admin-avatar">
                        {{ row.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div class="admin-info">
                        <div class="admin-name">{{ row.name }}</div>
                        <div class="admin-email">{{ row.email }}</div>
                    </div>
                </div>
            </template>

            <template #cell-roles="{ row }">
                <div class="roles-cell">
                    <StatusBadge
                        v-for="role in row.roles"
                        :key="role.id"
                        variant="primary"
                        size="sm"
                    >
                        {{ role.label || role.name }}
                    </StatusBadge>
                    <span v-if="!row.roles || row.roles.length === 0" class="text-muted">
                        Нет ролей
                    </span>
                </div>
            </template>

            <template #cell-created_at="{ row }">
                {{ formatDate(row.created_at) }}
            </template>

            <template #cell-actions="{ row }">
                <div class="actions-cell">
                    <Button
                        v-if="authStore.hasPermission('admin_users.update')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.admin-users.edit', params: { id: row.id } })"
                        title="Редактировать"
                    >
                        ✏️
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('admin_users.delete') && row.id !== authStore.user?.id"
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
            title="Удалить администратора"
            size="sm"
        >
            <p>
                Вы уверены, что хотите удалить администратора <strong>{{ selectedAdmin?.name }}</strong>?
                Это действие нельзя отменить.
            </p>
            <template #footer>
                <Button variant="outline" @click="showDeleteModal = false">
                    Отмена
                </Button>
                <Button
                    variant="danger"
                    :loading="deleting"
                    @click="deleteAdmin"
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
} = useTable('/admin-users', {
    perPage: 15,
    sortBy: 'created_at',
    sortDir: 'desc',
})

// Filters
const searchQuery = ref('')

const hasActiveFilters = computed(() => {
    return searchQuery.value
})

// Debounced search
let searchTimeout = null
const debouncedSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        if (searchQuery.value) {
            setFilter('search', searchQuery.value)
        } else {
            delete params.search
            fetchData()
        }
    }, 500)
}

const resetFilters = () => {
    searchQuery.value = ''
    resetTableFilters()
}

// Columns
const columns = [
    { key: 'name', label: 'Администратор', sortable: true },
    { key: 'roles', label: 'Роли' },
    { key: 'created_at', label: 'Создан', sortable: true },
    { key: 'actions', label: 'Действия', width: '120px' },
]

// Sort handler
const handleSort = ({ key, dir }) => {
    setSort(key, dir)
}

// Delete
const showDeleteModal = ref(false)
const selectedAdmin = ref(null)
const deleting = ref(false)

const openDeleteModal = (admin) => {
    selectedAdmin.value = admin
    showDeleteModal.value = true
}

const deleteAdmin = async () => {
    deleting.value = true
    try {
        await api.del(`/admin-users/${selectedAdmin.value.id}`)
        notifications.success('Администратор успешно удален')
        showDeleteModal.value = false
        fetchData()
    } catch (error) {
        notifications.error('Ошибка при удалении администратора')
    } finally {
        deleting.value = false
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
</script>

<style scoped>
.admin-users-page {
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
    grid-template-columns: 1fr auto;
    gap: 16px;
    align-items: end;
    max-width: 500px;
}

.admin-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-avatar {
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
}

.admin-info {
    flex: 1;
}

.admin-name {
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 2px;
}

.admin-email {
    font-size: 12px;
    color: #718096;
}

.roles-cell {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.text-muted {
    color: #a0aec0;
    font-size: 13px;
}

.actions-cell {
    display: flex;
    gap: 6px;
}
</style>
