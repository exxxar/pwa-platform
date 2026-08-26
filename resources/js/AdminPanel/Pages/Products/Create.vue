<template>
    <div class="product-create-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Создать товар</h1>
                <p class="page-subtitle">Заполните информацию о новом товаре</p>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="product-form">
            <!-- Main Info -->
            <div class="form-section">
                <h2 class="section-title">Основная информация</h2>

                <div class="form-grid">
                    <Select
                        v-model="form.tenant_id"
                        label="Тенант"
                        :options="tenantOptions"
                        :error="errors.tenant_id?.[0]"
                        required
                    />

                    <Input
                        v-model="form.name"
                        label="Название"
                        placeholder="Введите название товара"
                        :error="errors.name?.[0]"
                        required
                    />

                    <Input
                        v-model="form.sku"
                        label="SKU"
                        placeholder="Артикул товара"
                        :error="errors.sku?.[0]"
                    />

                    <Input
                        v-model.number="form.price"
                        label="Цена"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        :error="errors.price?.[0]"
                        required
                    >
                        <template #prefix>₽</template>
                    </Input>

                    <Input
                        v-model.number="form.old_price"
                        label="Старая цена"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        :error="errors.old_price?.[0]"
                        hint="Для отображения скидки"
                    >
                        <template #prefix>₽</template>
                    </Input>

                    <Input
                        v-model.number="form.order_position"
                        label="Порядок сортировки"
                        type="number"
                        placeholder="0"
                        :error="errors.order_position?.[0]"
                    />
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Описание</label>
                    <textarea
                        v-model="form.description"
                        class="form-textarea"
                        :class="{ 'has-error': errors.description }"
                        placeholder="Описание товара..."
                        rows="4"
                    ></textarea>
                    <span v-if="errors.description" class="error-message">{{ errors.description[0] }}</span>
                </div>
            </div>

            <!-- Categories -->
            <div class="form-section">
                <h2 class="section-title">Категории</h2>

                <div v-if="loadingCategories" class="loading-state">
                    <div class="spinner"></div>
                    <span>Загрузка категорий...</span>
                </div>

                <div v-else class="categories-checkboxes">
                    <label
                        v-for="category in availableCategories"
                        :key="category.id"
                        class="category-checkbox"
                    >
                        <input
                            v-model="form.category_ids"
                            :value="category.id"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <span class="checkbox-text">{{ category.name }}</span>
                    </label>

                    <div v-if="availableCategories.length === 0" class="empty-state">
                        Нет доступных категорий
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="form-section">
                <h2 class="section-title">Изображения</h2>

                <div class="form-group">
                    <label class="form-label">URL изображений (по одному на строку)</label>
                    <textarea
                        v-model="imagesText"
                        class="form-textarea"
                        placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg"
                        rows="4"
                    ></textarea>
                    <span class="input-hint">Вставьте URL изображений, каждое с новой строки</span>
                </div>
            </div>

            <!-- Status -->
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
                            v-model="form.in_stop_list"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <span class="checkbox-text">В стоп-листе</span>
                    </label>

                    <label class="checkbox-label">
                        <input
                            v-model="form.not_for_delivery"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <span class="checkbox-text">Не для доставки</span>
                    </label>

                    <label class="checkbox-label">
                        <input
                            v-model="form.is_composite"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <span class="checkbox-text">Составной товар</span>
                    </label>

                    <label class="checkbox-label">
                        <input
                            v-model="form.is_weight_product"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <span class="checkbox-text">Весовой товар</span>
                    </label>
                </div>
            </div>

            <!-- Delivery -->
            <div class="form-section">
                <h2 class="section-title">Условия доставки</h2>

                <div class="form-group full-width">
                    <label class="form-label">Условия доставки</label>
                    <textarea
                        v-model="form.delivery_terms"
                        class="form-textarea"
                        :class="{ 'has-error': errors.delivery_terms }"
                        placeholder="Особые условия доставки..."
                        rows="3"
                    ></textarea>
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
                    :loading="loading"
                >
                    Создать товар
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../composables/useApi'
import { useNotifications } from '../../composables/useNotifications'
import Button from '../../components/UI/Button.vue'
import Input from '../../components/UI/Input.vue'
import Select from '../../components/UI/Select.vue'

const router = useRouter()
const api = useApi()
const notifications = useNotifications()

const form = reactive({
    tenant_id: '',
    name: '',
    sku: '',
    price: 0,
    old_price: null,
    description: '',
    delivery_terms: '',
    order_position: 0,
    is_active: true,
    in_stop_list: false,
    not_for_delivery: false,
    is_composite: false,
    is_weight_product: false,
    category_ids: [],
    images: [],
})

const errors = ref({})
const loading = ref(false)
const loadingCategories = ref(false)
const tenantOptions = ref([])
const availableCategories = ref([])
const imagesText = ref('')

// Convert images text to array
watch(imagesText, (newVal) => {
    form.images = newVal
        .split('\n')
        .map(url => url.trim())
        .filter(url => url.length > 0)
})

const loadFilters = async () => {
    loadingCategories.value = true
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
        availableCategories.value = categories
    } catch (error) {
        console.error('Ошибка загрузки данных:', error)
        notifications.error('Не удалось загрузить список тенантов и категорий')
    } finally {
        loadingCategories.value = false
    }
}

const handleSubmit = async () => {
    loading.value = true
    errors.value = {}

    try {
        await api.post('/products', form)
        notifications.success('Товар успешно создан')
        router.push({ name: 'admin.products.index' })
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
            notifications.error('Пожалуйста, исправьте ошибки в форме')
        } else {
            notifications.error('Ошибка при создании товара')
        }
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    loadFilters()
})
</script>

<style scoped>
.product-create-page {
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

.product-form {
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

.input-hint {
    font-size: 12px;
    color: #718096;
}

.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px;
    color: #718096;
}

.spinner {
    width: 24px;
    height: 24px;
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

.categories-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.category-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #f7fafc;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.category-checkbox:hover {
    background: #edf2f7;
}

.category-checkbox:has(input:checked) {
    background: #e9d8fd;
    border-color: #667eea;
}

.checkbox-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-text {
    font-size: 14px;
    color: #2d3748;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #a0aec0;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    .product-form {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .categories-checkboxes {
        grid-template-columns: 1fr;
    }

    .checkbox-group {
        grid-template-columns: 1fr;
    }
}
</style>
