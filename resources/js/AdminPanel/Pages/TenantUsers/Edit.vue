<template>
    <div class="tenant-user-edit-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Редактировать пользователя</h1>
                <p class="page-subtitle">{{ user?.name || 'Загрузка...' }}</p>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <span>Загрузка...</span>
        </div>

        <form v-else-if="user" @submit.prevent="handleSubmit" class="user-form">
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
                        v-model="form.phone"
                        label="Телефон"
                        placeholder="+7 (999) 123-45-67"
                        :error="errors.phone?.[0]"
                    />

                    <Input
                        v-model="form.email"
                        label="Email"
                        type="email"
                        placeholder="email@example.com"
                        :error="errors.email?.[0]"
                    />

                    <Select
                        v-model="form.sex"
                        label="Пол"
                        :options="sexOptions"
                        :error="errors.sex?.[0]"
                    />

                    <Input
                        v-model="form.birthday"
                        label="Дата рождения"
                        type="date"
                        :error="errors.birthday?.[0]"
                    />

                    <Input
                        v-model="form.city"
                        label="Город"
                        placeholder="Москва"
                        :error="errors.city?.[0]"
                    />

                    <Input
                        v-model="form.country"
                        label="Страна"
                        placeholder="Россия"
                        :error="errors.country?.[0]"
                    />
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Адрес</label>
                    <textarea
                        v-model="form.address"
                        class="form-textarea"
                        :class="{ 'has-error': errors.address }"
                        placeholder="Полный адрес..."
                        rows="3"
                    ></textarea>
                    <span v-if="errors.address" class="error-message">{{ errors.address[0] }}</span>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Статусы</h2>

                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <span class="checkbox-text">Активен</span>
                    </label>

                    <label class="checkbox-label">
                        <input
                            v-model="form.is_vip"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <span class="checkbox-text">VIP статус</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Роли</h2>

                <div class="roles-checkboxes">
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
                        <span class="checkbox-text">{{ role.label || role.name }}</span>
                    </label>
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
import Select from '../../components/UI/Select.vue'

const route = useRoute()
const router = useRouter()
const api = useApi()
const notifications = useNotifications()

const userId = route.params.id
const user = ref(null)
const loading = ref(true)
const saving = ref(false)

const form = reactive({
    name: '',
    phone: '',
    email: '',
    sex: '',
    birthday: '',
    city: '',
    country: '',
    address: '',
    is_active: true,
    is_vip: false,
    role_ids: [],
})

const errors = ref({})
const availableRoles = ref([])

const sexOptions = [
    { value: '', label: 'Не указан' },
    { value: 'male', label: 'Мужской' },
    { value: 'female', label: 'Женский' },
    { value: 'other', label: 'Другой' },
]

const loadUser = async () => {
    loading.value = true
    try {
        const response = await api.get(`/tenant-users/${userId}`)
        user.value = response.data || response

        // Заполняем форму данными пользователя
        Object.keys(form).forEach(key => {
            if (key === 'role_ids') {
                form[key] = user.value.roles?.map(r => r.id) || []
            } else if (user.value[key] !== undefined) {
                form[key] = user.value[key]
            }
        })

        // Форматируем дату для input type="date"
        if (form.birthday) {
            form.birthday = new Date(form.birthday).toISOString().split('T')[0]
        }
    } catch (error) {
        notifications.error('Ошибка при загрузке пользователя')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const loadRoles = async () => {
    try {
        const response = await api.get('/roles')
        const roles = response.data || response
        availableRoles.value = roles
    } catch (error) {
        console.error('Ошибка загрузки ролей:', error)
    }
}

const handleSubmit = async () => {
    saving.value = true
    errors.value = {}

    try {
        await api.put(`/tenant-users/${userId}`, form)
        notifications.success('Пользователь успешно обновлен')
        router.push({ name: 'admin.tenant-users.show', params: { id: userId } })
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
            notifications.error('Пожалуйста, исправьте ошибки в форме')
        } else {
            notifications.error('Ошибка при обновлении пользователя')
        }
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadUser()
    loadRoles()
})
</script>

<style scoped>
.tenant-user-edit-page {
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

.user-form {
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
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group.full-width {
    grid-column: 1 / -1;
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

.form-textarea.has-error {
    border-color: #f56565;
}

.error-message {
    font-size: 12px;
    color: #f56565;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-text {
    font-size: 14px;
    color: #4a5568;
}

.roles-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    .user-form {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
