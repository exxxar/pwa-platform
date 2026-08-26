<template>
    <div class="roles-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Роли</h1>
                <p class="page-subtitle">Управление ролями и разрешениями</p>
            </div>
            <Button
                v-if="authStore.hasPermission('roles.create')"
                variant="primary"
                @click="$router.push({ name: 'admin.roles.create' })"
            >
                <template #icon>➕</template>
                Создать роль
            </Button>
        </div>

        <DataTable
            :columns="columns"
            :data="data"
            :loading="loading"
            :pagination="{ last_page: 1 }"
            :show-pagination="false"
        >
            <template #cell-name="{ row }">
                <div class="role-name-cell">
                    <div class="role-name">{{ row.label || row.name }}</div>
                    <div class="role-system-name">{{ row.name }}</div>
                </div>
            </template>

            <template #cell-permissions_count="{ row }">
                <StatusBadge variant="info">
                    {{ row.permissions?.length || 0 }} разрешений
                </StatusBadge>
            </template>

            <template #cell-users_count="{ row }">
                <StatusBadge variant="primary">
                    {{ row.users_count || 0 }} пользователей
                </StatusBadge>
            </template>

            <template #cell-created_at="{ row }">
                {{ formatDate(row.created_at) }}
            </template>

            <template #cell-actions="{ row }">
                <div class="actions-cell">
                    <Button
                        v-if="authStore.hasPermission('roles.update')"
                        variant="outline"
                        size="sm"
                        icon-only
                        @click="$router.push({ name: 'admin.roles.edit', params: { id: row.id } })"
                        title="Редактировать"
                    >
                        ✏️
                    </Button>
                    <Button
                        v-if="authStore.hasPermission('roles.delete') && row.name !== 'super_admin'"
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
            title="Удалить роль"
            size="sm"
        >
            <p>
                Вы уверены, что хотите удалить роль <strong>{{ selectedRole?.label || selectedRole?.name }}</strong>?
                Это действие нельзя отменить.
            </p>
            <template #footer>
                <Button variant="outline" @click="showDeleteModal = false">
                    Отмена
                </Button>
                <Button
                    variant="danger"
                    :loading="deleting"
                    @click="deleteRole"
                >
                    Удалить
                </Button>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '../../composables/useApi'
import { useAuthStore } from '../../stores/auth'
import { useNotifications } from '../../composables/useNotifications'
import Button from '../../components/UI/Button.vue'
import DataTable from '../../components/UI/DataTable.vue'
import StatusBadge from '../../components/UI/StatusBadge.vue'
import Modal from '../../components/UI/Modal.vue'

const api = useApi()
const authStore = useAuthStore()
const notifications = useNotifications()

const data = ref([])
const loading = ref(true)

const loadRoles = async () => {
    loading.value = true
    try {
        const response = await api.get('/roles')
        data.value = response.data || response
    } catch (error) {
        notifications.error('Ошибка при загрузке ролей')
        console.error(error)
    } finally {
        loading.value = false
    }
}

// Columns
const columns = [
    { key: 'name', label: 'Роль' },
    { key: 'permissions_count', label: 'Разрешения' },
    { key: 'users_count', label: 'Пользователи' },
    { key: 'created_at', label: 'Создана' },
    { key: 'actions', label: 'Действия', width: '120px' },
]

// Delete
const showDeleteModal = ref(false)
const selectedRole = ref(null)
const deleting = ref(false)

const openDeleteModal = (role) => {
    selectedRole.value = role
    showDeleteModal.value = true
}

const deleteRole = async () => {
    deleting.value = true
    try {
        await api.del(`/roles/${selectedRole.value.id}`)
        notifications.success('Роль успешно удалена')
        showDeleteModal.value = false
        loadRoles()
    } catch (error) {
        notifications.error('Ошибка при удалении роли')
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

onMounted(() => {
    loadRoles()
})
</script>

<style scoped>
.roles-page {
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

.role-name-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.role-name {
    font-weight: 600;
    color: #1a202c;
}

.role-system-name {
    font-size: 12px;
    color: #718096;
    font-family: 'Courier New', monospace;
}

.actions-cell {
    display: flex;
    gap: 6px;
}
</style>
