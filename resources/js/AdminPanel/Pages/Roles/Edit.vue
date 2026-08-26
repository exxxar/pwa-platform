<template>
    <div class="role-edit-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Редактировать роль</h1>
                <p class="page-subtitle">{{ role?.label || role?.name || 'Загрузка...' }}</p>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <span>Загрузка...</span>
        </div>

        <form v-else-if="role" @submit.prevent="handleSubmit" class="role-form">
            <div class="form-section">
                <h2 class="section-title">Основная информация</h2>

                <div class="form-grid">
                    <Input
                        v-model="form.name"
                        label="Системное имя"
                        placeholder="manager"
                        :error="errors.name?.[0]"
                        required
                        hint="Используется в коде, только латиница и подчеркивания"
                        :disabled="role.name === 'super_admin'"
                    />

                    <Input
                        v-model="form.label"
                        label="Отображаемое название"
                        placeholder="Менеджер"
                        :error="errors.label?.[0]"
                        required
                    />
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Разрешения</h2>

                <div v-if="loadingPermissions" class="loading-state">
                    <div class="spinner"></div>
                    <span>Загрузка разрешений...</span>
                </div>

                <div v-else>
                    <div class="permissions-header">
                        <label class="select-all-label">
                            <input
                                type="checkbox"
                                class="checkbox-input"
                                @change="toggleAllPermissions"
                                :checked="isAllSelected"
                            />
                            <span class="checkbox-text">Выбрать все</span>
                        </label>
                        <span class="selected-count">
                            Выбрано: {{ form.permission_ids.length }} из {{ availablePermissions.length }}
                        </span>
                    </div>

                    <div class="permissions-grid">
                        <label
                            v-for="permission in availablePermissions"
                            :key="permission.id"
                            class="permission-checkbox"
                        >
                            <input
                                v-model="form.permission_ids"
                                :value="permission.id"
                                type="checkbox"
                                class="checkbox-input"
                            />
                            <div class="permission-content">
                                <span class="permission-label">{{ permission.label || permission.name }}</span>
                                <span class="permission-name">{{ permission.name }}</span>
                            </div>
                        </label>

                        <div v-if="availablePermissions.length === 0" class="empty-state">
                            Нет доступных разрешений
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <Button
                    type="button"
                    variant="outline"
                    @click="$router.back()"
                >
                    Отмена
                </Button>
                <Button
                    type="submit"
                    variant="primary"
                    :loading="saving"
                >
                    Сохранить изменения
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../../composables/useApi'
import { useNotifications } from '../../composables/useNotifications'
import Button from '../../components/UI/Button.vue'
import Input from '../../components/UI/Input.vue'

const route = useRoute()
const router = useRouter()
const api = useApi()
const notifications = useNotifications()

const roleId = route.params.id
const role = ref(null)
const loading = ref(true)
const saving = ref(false)
const loadingPermissions = ref(false)

const form = reactive({
    name: '',
    label: '',
    permission_ids: [],
})

const errors = ref({})
const availablePermissions = ref([])

const isAllSelected = computed(() => {
    return availablePermissions.value.length > 0 &&
        form.permission_ids.length === availablePermissions.value.length
})

const toggleAllPermissions = (event) => {
    if (event.target.checked) {
        form.permission_ids = availablePermissions.value.map(p => p.id)
    } else {
        form.permission_ids = []
    }
}

const loadRole = async () => {
    loading.value = true
    try {
        const response = await api.get(`/roles/${roleId}`)
        role.value = response.data || response

        // Заполняем форму данными роли
        form.name = role.value.name || ''
        form.label = role.value.label || ''
        form.permission_ids = role.value.permissions?.map(p => p.id) || []
    } catch (error) {
        notifications.error('Ошибка при загрузке роли')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const loadPermissions = async () => {
    loadingPermissions.value = true
    try {
        const response = await api.get('/permissions')
        availablePermissions.value = response.data || response
    } catch (error) {
        console.error('Ошибка загрузки разрешений:', error)
        notifications.error('Не удалось загрузить список разрешений')
    } finally {
        loadingPermissions.value = false
    }
}

const handleSubmit = async () => {
    saving.value = true
    errors.value = {}

    try {
        await api.put(`/roles/${roleId}`, form)
        notifications.success('Роль успешно обновлена')
        router.push({ name: 'admin.roles.index' })
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
            notifications.error('Пожалуйста, исправьте ошибки в форме')
        } else {
            notifications.error('Ошибка при обновлении роли')
        }
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadRole()
    loadPermissions()
})
</script>

<style scoped>
.role-edit-page {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
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

.role-form {
    background: white;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.permissions-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f7fafc;
    border-radius: 8px;
    margin-bottom: 16px;
}

.select-all-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.selected-count {
    font-size: 13px;
    color: #718096;
    font-weight: 500;
}

.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
}

.permission-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    background: #f7fafc;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.permission-checkbox:hover {
    background: #edf2f7;
}

.permission-checkbox:has(input:checked) {
    background: #e9d8fd;
    border-color: #667eea;
}

.checkbox-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin-top: 2px;
}

.permission-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.permission-label {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
}

.permission-name {
    font-size: 11px;
    color: #718096;
    font-family: 'Courier New', monospace;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #a0aec0;
}

.checkbox-text {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    .role-form {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .permissions-grid {
        grid-template-columns: 1fr;
    }
}
</style>
