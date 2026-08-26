<template>
    <div class="admin-user-edit-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Редактировать администратора</h1>
                <p class="page-subtitle">{{ admin?.name || 'Загрузка...' }}</p>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <span>Загрузка...</span>
        </div>

        <form v-else-if="admin" @submit.prevent="handleSubmit" class="admin-form">
            <div class="form-section">
                <h2 class="section-title">Основная информация</h2>

                <div class="form-grid">
                    <Input
                        v-model="form.name"
                        label="Имя"
                        placeholder="Введите имя"
                        :error="errors.name?.[0]"
                        required
                    />

                    <Input
                        v-model="form.email"
                        label="Email"
                        type="email"
                        placeholder="admin@example.com"
                        :error="errors.email?.[0]"
                        required
                    />

                    <Input
                        v-model="form.password"
                        label="Новый пароль"
                        type="password"
                        placeholder="Оставьте пустым, чтобы не менять"
                        :error="errors.password?.[0]"
                        hint="Минимум 8 символов"
                    />

                    <Input
                        v-model="form.password_confirmation"
                        label="Подтверждение пароля"
                        type="password"
                        placeholder="Повторите новый пароль"
                        :error="errors.password_confirmation?.[0]"
                    />
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Роли</h2>

                <div v-if="loadingRoles" class="loading-state">
                    <div class="spinner"></div>
                    <span>Загрузка ролей...</span>
                </div>

                <div v-else class="roles-checkboxes">
                    <label
                        v-for="role in availableRoles"
                        :key="role.id"
                        class="checkbox-label"
                    >
                        <input
                            v-model="form.role_ids"
                            :value="role.id"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <div class="checkbox-content">
                            <span class="checkbox-text">{{ role.label || role.name }}</span>
                            <span class="checkbox-description">{{ role.name }}</span>
                        </div>
                    </label>

                    <div v-if="availableRoles.length === 0" class="empty-state">
                        Нет доступных ролей
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
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../../composables/useApi'
import { useNotifications } from '../../composables/useNotifications'
import Button from '../../components/UI/Button.vue'
import Input from '../../components/UI/Input.vue'

const route = useRoute()
const router = useRouter()
const api = useApi()
const notifications = useNotifications()

const adminId = route.params.id
const admin = ref(null)
const loading = ref(true)
const saving = ref(false)
const loadingRoles = ref(false)

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_ids: [],
})

const errors = ref({})
const availableRoles = ref([])

const loadAdmin = async () => {
    loading.value = true
    try {
        const response = await api.get(`/admin-users/${adminId}`)
        admin.value = response.data || response

        // Заполняем форму данными админа
        form.name = admin.value.name || ''
        form.email = admin.value.email || ''
        form.role_ids = admin.value.roles?.map(r => r.id) || []
    } catch (error) {
        notifications.error('Ошибка при загрузке администратора')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const loadRoles = async () => {
    loadingRoles.value = true
    try {
        const response = await api.get('/roles')
        availableRoles.value = response.data || response
    } catch (error) {
        console.error('Ошибка загрузки ролей:', error)
        notifications.error('Не удалось загрузить список ролей')
    } finally {
        loadingRoles.value = false
    }
}

const handleSubmit = async () => {
    saving.value = true
    errors.value = {}

    try {
        await api.put(`/admin-users/${adminId}`, form)
        notifications.success('Администратор успешно обновлен')
        router.push({ name: 'admin.admin-users.index' })
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
            notifications.error('Пожалуйста, исправьте ошибки в форме')
        } else {
            notifications.error('Ошибка при обновлении администратора')
        }
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadAdmin()
    loadRoles()
})
</script>

<style scoped>
.admin-user-edit-page {
    max-width: 1000px;
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

.admin-form {
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

.roles-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: #f7fafc;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.checkbox-label:hover {
    background: #edf2f7;
}

.checkbox-label:has(input:checked) {
    background: #e9d8fd;
    border-color: #667eea;
}

.checkbox-input {
    width: 20px;
    height: 20px;
    cursor: pointer;
    margin-top: 2px;
}

.checkbox-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.checkbox-text {
    font-size: 14px;
    font-weight: 600;
    color: #2d3748;
}

.checkbox-description {
    font-size: 12px;
    color: #718096;
    font-family: 'Courier New', monospace;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #a0aec0;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    .admin-form {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .roles-checkboxes {
        grid-template-columns: 1fr;
    }
}
</style>
