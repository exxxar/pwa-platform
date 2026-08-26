<template>
    <div class="tenant-edit-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Редактировать тенанта</h1>
                <p class="page-subtitle">{{ tenant?.name || 'Загрузка...' }}</p>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <span>Загрузка...</span>
        </div>

        <form v-else-if="tenant" @submit.prevent="handleSubmit" class="tenant-form">
            <div class="form-section">
                <h2 class="section-title">Основная информация</h2>

                <div class="form-grid">
                    <Input
                        v-model="form.name"
                        label="Название"
                        placeholder="Введите название тенанта"
                        :error="errors.name?.[0]"
                        required
                    />

                    <Input
                        v-model="form.slug"
                        label="Slug"
                        placeholder="slug"
                        :error="errors.slug?.[0]"
                        required
                    />

                    <Input
                        v-model="form.short_name"
                        label="Краткое название"
                        placeholder="Краткое название"
                        :error="errors.short_name?.[0]"
                    />

                    <Select
                        v-model="form.plan_slug"
                        label="Тарифный план"
                        :options="planOptions"
                        :error="errors.plan_slug?.[0]"
                    />
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Описание</label>
                    <textarea
                        v-model="form.description"
                        class="form-textarea"
                        :class="{ 'has-error': errors.description }"
                        placeholder="Описание тенанта..."
                        rows="4"
                    ></textarea>
                    <span v-if="errors.description" class="error-message">{{ errors.description[0] }}</span>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Внешний вид</h2>

                <div class="form-grid">
                    <Input
                        v-model="form.theme_color"
                        label="Цвет темы"
                        placeholder="#667eea"
                        :error="errors.theme_color?.[0]"
                    >
                        <template #prefix>🎨</template>
                    </Input>

                    <Input
                        v-model="form.background_color"
                        label="Цвет фона"
                        placeholder="#ffffff"
                        :error="errors.background_color?.[0]"
                    >
                        <template #prefix>🖼️</template>
                    </Input>

                    <Input
                        v-model="form.app_type"
                        label="Тип приложения"
                        placeholder="restaurant, shop, service"
                        :error="errors.app_type?.[0]"
                    />
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Настройки</h2>

                <div class="form-grid">
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="checkbox-input"
                            />
                            <span class="checkbox-text">Активен</span>
                        </label>
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
import Select from '../../components/UI/Select.vue'

const route = useRoute()
const router = useRouter()
const api = useApi()
const notifications = useNotifications()

const tenantId = route.params.id
const tenant = ref(null)
const loading = ref(true)
const saving = ref(false)

const form = reactive({
    name: '',
    slug: '',
    short_name: '',
    description: '',
    theme_color: '',
    background_color: '',
    app_type: '',
    plan_slug: '',
    is_active: true,
})

const errors = ref({})

const planOptions = [
    { value: 'basic', label: 'Базовый' },
    { value: 'premium', label: 'Премиум' },
    { value: 'enterprise', label: 'Корпоративный' },
]

const loadTenant = async () => {
    loading.value = true
    try {
        const response = await api.get(`/tenants/${tenantId}`)
        tenant.value = response.data || response

        // Заполняем форму данными тенанта
        Object.keys(form).forEach(key => {
            if (tenant.value[key] !== undefined) {
                form[key] = tenant.value[key]
            }
        })
    } catch (error) {
        notifications.error('Ошибка при загрузке тенанта')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const handleSubmit = async () => {
    saving.value = true
    errors.value = {}

    try {
        await api.put(`/tenants/${tenantId}`, form)
        notifications.success('Тенант успешно обновлен')
        router.push({ name: 'admin.tenants.show', params: { id: tenantId } })
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
            notifications.error('Пожалуйста, исправьте ошибки в форме')
        } else {
            notifications.error('Ошибка при обновлении тенанта')
        }
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadTenant()
})
</script>

<style scoped>
.tenant-edit-page {
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

.tenant-form {
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

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    .tenant-form {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
