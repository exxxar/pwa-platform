<template>
    <div class="product-form">

        <!-- ========================================== -->
        <!-- HEADER С ДЕЙСТВИЯМИ -->
        <!-- ========================================== -->
        <div class="form-header">
            <div class="header-info">
                <h2 class="form-title">{{ isEditing ? 'Редактирование товара' : 'Новый товар' }}</h2>
                <span v-if="isEditing" class="product-id-badge">ID: {{ productForm.id }}</span>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-secondary-modern" @click="$emit('cancel')">
                    <i class="fa-solid fa-xmark"></i> Отмена
                </button>
                <button type="submit" class="btn-primary-modern" @click="submit" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="spinner-small"></span>
                    <template v-else>
                        <i class="fa-solid fa-check"></i>
                        <span>Сохранить</span>
                    </template>
                </button>
            </div>
        </div>

        <form @submit.prevent="submit" class="form-body">

            <!-- ========================================== -->
            <!-- СЕКЦИЯ 1: ОСНОВНАЯ ИНФОРМАЦИЯ -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header" @click="toggleSection('basic')">
                    <div class="section-icon blue">
                        <i class="fa-solid fa-info-circle"></i>
                    </div>
                    <div class="section-info">
                        <h3>Основная информация</h3>
                        <p>Название, цена, тип и статус товара</p>
                    </div>
                    <i class="fa-solid fa-chevron-down section-chevron" :class="{ 'rotated': openSections.basic }"></i>
                </div>

                <transition name="collapse">
                    <div v-show="openSections.basic" class="section-content">

                        <!-- Статус товара -->
                        <div class="status-toggles">
                            <label class="toggle-card" :class="{ 'is-active': productForm.in_stop_list_at }">
                                <input type="checkbox" v-model="productForm.in_stop_list_at" hidden>
                                <div class="toggle-icon">
                                    <i class="fa-solid fa-ban"></i>
                                </div>
                                <div class="toggle-info">
                                    <span class="toggle-title">Стоп-лист</span>
                                    <span class="toggle-desc">Товар недоступен для заказа</span>
                                </div>
                            </label>

                            <label class="toggle-card" :class="{ 'is-active': productForm.not_for_delivery }">
                                <input type="checkbox" v-model="productForm.not_for_delivery" hidden>
                                <div class="toggle-icon">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div class="toggle-info">
                                    <span class="toggle-title">Самовывоз</span>
                                    <span class="toggle-desc">Только в точке выдачи</span>
                                </div>
                            </label>
                        </div>

                        <!-- Название -->
                        <div class="form-group">
                            <label class="form-label">
                                Название товара <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="productForm.title"
                                class="form-control-modern"
                                :class="{ 'has-error': errors.title }"
                                placeholder="Например: Пицца Маргарита 30 см"
                                required
                            >
                            <span v-if="errors.title" class="error-text">Введите название товара</span>
                        </div>

                        <!-- Артикул и Тип -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Артикул</label>
                                <input
                                    type="text"
                                    v-model="productForm.article"
                                    class="form-control-modern"
                                    placeholder="ART-001"
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label">Тип товара</label>
                                <select v-model="productForm.type" class="form-control-modern">
                                    <option v-for="type in types" :key="type.value" :value="type.value">
                                        {{ type.title }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Цены -->
                        <div class="price-block">
                            <div class="form-group">
                                <label class="form-label">
                                    Актуальная цена <span class="required">*</span>
                                </label>
                                <div class="input-with-suffix">
                                    <input
                                        type="number"
                                        min="0"
                                        v-model="productForm.current_price"
                                        class="form-control-modern"
                                        :class="{ 'has-error': errors.current_price }"
                                        placeholder="0"
                                        required
                                    >
                                    <span class="input-suffix">₽</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Старая цена</label>
                                <div class="input-with-suffix">
                                    <input
                                        type="number"
                                        min="0"
                                        v-model="productForm.old_price"
                                        class="form-control-modern"
                                        placeholder="0"
                                    >
                                    <span class="input-suffix">₽</span>
                                </div>
                                <span v-if="discountPercent > 0" class="discount-hint">
                                    Скидка {{ discountPercent }}%
                                </span>
                            </div>
                        </div>

                        <!-- Рейтинг -->
                        <div class="form-group">
                            <label class="form-label">Рейтинг товара</label>
                            <div class="rating-input">
                                <input
                                    type="range"
                                    min="0"
                                    max="5"
                                    step="0.5"
                                    v-model.number="productForm.rating"
                                    class="rating-slider"
                                >
                                <div class="rating-display">
                                    <div class="rating-stars">
                                        <i
                                            v-for="star in 5"
                                            :key="star"
                                            class="fa-solid fa-star"
                                            :class="{ 'filled': star <= Math.round(productForm.rating) }"
                                        ></i>
                                    </div>
                                    <span class="rating-value">{{ productForm.rating.toFixed(1) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Весовой товар -->
                        <label class="toggle-card weight-toggle" :class="{ 'is-active': productForm.is_weight_product }">
                            <input type="checkbox" v-model="productForm.is_weight_product" hidden>
                            <div class="toggle-icon">
                                <i class="fa-solid fa-weight-scale"></i>
                            </div>
                            <div class="toggle-info">
                                <span class="toggle-title">Весовой товар</span>
                                <span class="toggle-desc">Покупается на вес с шагом</span>
                            </div>
                        </label>

                        <!-- Настройки веса -->
                        <transition name="collapse">
                            <div v-if="productForm.is_weight_product" class="weight-config">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Мин. вес, г</label>
                                        <input
                                            type="number"
                                            min="0"
                                            v-model.number="productForm.weight_config.min"
                                            class="form-control-modern"
                                            placeholder="0"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Макс. вес, г</label>
                                        <input
                                            type="number"
                                            min="0"
                                            v-model.number="productForm.weight_config.max"
                                            class="form-control-modern"
                                            placeholder="0 = без лимита"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Шаг, г</label>
                                        <input
                                            type="number"
                                            min="1"
                                            v-model.number="productForm.weight_config.step"
                                            class="form-control-modern"
                                            placeholder="50"
                                        >
                                    </div>
                                </div>
                            </div>
                        </transition>

                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- СЕКЦИЯ 2: ОПИСАНИЕ -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header" @click="toggleSection('description')">
                    <div class="section-icon purple">
                        <i class="fa-solid fa-align-left"></i>
                    </div>
                    <div class="section-info">
                        <h3>Описание</h3>
                        <p>Подробная информация и условия доставки</p>
                    </div>
                    <i class="fa-solid fa-chevron-down section-chevron" :class="{ 'rotated': openSections.description }"></i>
                </div>

                <transition name="collapse">
                    <div v-show="openSections.description" class="section-content">
                        <div class="form-group">
                            <label class="form-label">Описание товара</label>
                            <textarea
                                v-model="productForm.description"
                                class="form-control-modern textarea"
                                placeholder="Расскажите о составе, вкусе, особенностях..."
                                rows="5"
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-truck-fast text-primary"></i>
                                Особенности доставки
                            </label>
                            <textarea
                                v-model="productForm.delivery_terms"
                                class="form-control-modern textarea"
                                placeholder="Например: Доставляется только в термосумке, срок хранения 2 часа..."
                                rows="3"
                            ></textarea>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- СЕКЦИЯ 3: ПАРАМЕТРЫ -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header" @click="toggleSection('dimensions')">
                    <div class="section-icon orange">
                        <i class="fa-solid fa-ruler-combined"></i>
                    </div>
                    <div class="section-info">
                        <h3>Параметры и размеры</h3>
                        <p>Габариты и вес для логистики</p>
                    </div>
                    <i class="fa-solid fa-chevron-down section-chevron" :class="{ 'rotated': openSections.dimensions }"></i>
                </div>

                <transition name="collapse">
                    <div v-show="openSections.dimensions" class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Высота</label>
                                <div class="input-with-suffix">
                                    <input
                                        type="number"
                                        min="0"
                                        v-model.number="productForm.dimension.height"
                                        class="form-control-modern"
                                        placeholder="0"
                                    >
                                    <span class="input-suffix">см</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ширина</label>
                                <div class="input-with-suffix">
                                    <input
                                        type="number"
                                        min="0"
                                        v-model.number="productForm.dimension.width"
                                        class="form-control-modern"
                                        placeholder="0"
                                    >
                                    <span class="input-suffix">см</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Длина</label>
                                <div class="input-with-suffix">
                                    <input
                                        type="number"
                                        min="0"
                                        v-model.number="productForm.dimension.length"
                                        class="form-control-modern"
                                        placeholder="0"
                                    >
                                    <span class="input-suffix">см</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Вес</label>
                                <div class="input-with-suffix">
                                    <input
                                        type="number"
                                        min="0"
                                        v-model.number="productForm.dimension.weight"
                                        class="form-control-modern"
                                        placeholder="0"
                                    >
                                    <span class="input-suffix">кг</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- СЕКЦИЯ 4: ИЗОБРАЖЕНИЯ -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header" @click="toggleSection('images')">
                    <div class="section-icon green">
                        <i class="fa-solid fa-images"></i>
                    </div>
                    <div class="section-info">
                        <h3>Изображения</h3>
                        <p>Фотографии товара ({{ existingImagesCount + newPhotosCount }} загружено)</p>
                    </div>
                    <i class="fa-solid fa-chevron-down section-chevron" :class="{ 'rotated': openSections.images }"></i>
                </div>

                <transition name="collapse">
                    <div v-show="openSections.images" class="section-content">

                        <!-- Зона загрузки -->
                        <label class="upload-zone">
                            <input
                                type="file"
                                multiple
                                accept="image/*"
                                @change="onChangePhotos"
                                class="upload-input"
                            >
                            <div class="upload-content">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span class="upload-title">Нажмите или перетащите фото</span>
                                <span class="upload-hint">JPG, PNG, WebP до 5 МБ</span>
                            </div>
                        </label>

                        <!-- Сетка изображений -->
                        <div v-if="existingImagesCount > 0 || newPhotosCount > 0" class="images-grid">
                            <!-- Существующие изображения -->
                            <div
                                v-for="(img, index) in productForm.images"
                                :key="'existing-' + index"
                                class="image-card"
                            >
                                <img :src="img" :alt="'Фото ' + (index + 1)">
                                <div class="image-badge existing">Сохранено</div>
                                <button type="button" class="image-remove" @click="removeImage(index)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>

                            <!-- Новые загруженные -->
                            <div
                                v-for="(photo, index) in photos"
                                :key="'new-' + index"
                                class="image-card"
                            >
                                <img :src="getPhoto(photo).imageUrl" :alt="'Новое фото ' + (index + 1)">
                                <div class="image-badge new">Новое</div>
                                <button type="button" class="image-remove" @click="removePhoto(index)">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- СЕКЦИЯ 5: ИНТЕГРАЦИИ -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header" @click="toggleSection('integrations')">
                    <div class="section-icon gray">
                        <i class="fa-solid fa-plug"></i>
                    </div>
                    <div class="section-info">
                        <h3>Интеграции</h3>
                        <p>ID товаров во внешних системах</p>
                    </div>
                    <i class="fa-solid fa-chevron-down section-chevron" :class="{ 'rotated': openSections.integrations }"></i>
                </div>

                <transition name="collapse">
                    <div v-show="openSections.integrations" class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-brands fa-vk"></i> VK Product ID
                                </label>
                                <input
                                    type="text"
                                    v-model="productForm.vk_product_id"
                                    class="form-control-modern"
                                    placeholder="Идентификатор ВК"
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-utensils"></i> IIKO Article
                                </label>
                                <input
                                    type="text"
                                    v-model="productForm.iiko_article"
                                    class="form-control-modern"
                                    placeholder="Артикул IIKO"
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-cash-register"></i> FrontPad Article
                                </label>
                                <input
                                    type="text"
                                    v-model="productForm.frontpad_article"
                                    class="form-control-modern"
                                    placeholder="Артикул FrontPad"
                                >
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- СЕКЦИЯ 6: КАТЕГОРИИ -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header" @click="toggleSection('categories')">
                    <div class="section-icon pink">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="section-info">
                        <h3>Категории</h3>
                        <p>Выберите разделы, в которых будет товар ({{ productCategories.length }} выбрано)</p>
                    </div>
                    <i class="fa-solid fa-chevron-down section-chevron" :class="{ 'rotated': openSections.categories }"></i>
                </div>

                <transition name="collapse">
                    <div v-show="openSections.categories" class="section-content">
                        <div v-if="categories.length === 0" class="empty-state">
                            <i class="fa-solid fa-folder-open"></i>
                            <p>Категории не найдены</p>
                        </div>
                        <div v-else class="categories-grid">
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                type="button"
                                class="category-chip"
                                :class="{ 'is-selected': productCategories.includes(cat.id) }"
                                @click="selectCategory(cat)"
                            >
                                <i class="fa-solid" :class="productCategories.includes(cat.id) ? 'fa-check' : 'fa-plus'"></i>
                                <span>{{ cat.title }}</span>
                            </button>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- ========================================== -->
            <!-- СЕКЦИЯ 7: ХАРАКТЕРИСТИКИ -->
            <!-- ========================================== -->
            <div class="form-section">
                <div class="section-header" @click="toggleSection('options')">
                    <div class="section-icon teal">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="section-info">
                        <h3>Характеристики</h3>
                        <p>Дополнительные параметры и секции ({{ productForm.options?.length || 0 }} характеристик)</p>
                    </div>
                    <i class="fa-solid fa-chevron-down section-chevron" :class="{ 'rotated': openSections.options }"></i>
                </div>

                <transition name="collapse">
                    <div v-show="openSections.options" class="section-content">

                        <!-- Секции -->
                        <div class="subsection">
                            <label class="form-label">Секции характеристик</label>
                            <form @submit.prevent="addSection" class="add-row">
                                <input
                                    type="text"
                                    v-model="sectionForm.section"
                                    class="form-control-modern"
                                    placeholder="Название новой секции"
                                >
                                <button type="submit" class="btn-primary-modern" :disabled="!sectionForm.section">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </form>

                            <div v-if="sections.length > 0" class="sections-list">
                                <div
                                    v-for="(section, index) in sections"
                                    :key="index"
                                    class="section-chip"
                                >
                                    <i class="fa-solid fa-folder"></i>
                                    <span>{{ section }}</span>
                                    <button type="button" @click="removeSection(index)">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Характеристики -->
                        <div class="subsection">
                            <div class="subsection-header">
                                <label class="form-label">Характеристики</label>
                                <button type="button" class="btn-text" @click="addOption">
                                    <i class="fa-solid fa-plus"></i> Добавить
                                </button>
                            </div>

                            <div v-if="!productForm.options || productForm.options.length === 0" class="empty-state small">
                                <p>Характеристики не добавлены</p>
                            </div>

                            <div
                                v-for="(option, index) in productForm.options"
                                :key="index"
                                class="option-card"
                            >
                                <div class="option-header">
                                    <span class="option-number">#{{ index + 1 }}</span>
                                    <button type="button" class="btn-remove-small" @click="removeOption(index)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Название</label>
                                        <input
                                            type="text"
                                            v-model="productForm.options[index].title"
                                            class="form-control-modern"
                                            placeholder="Например: Размер"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Значение</label>
                                        <input
                                            type="text"
                                            v-model="productForm.options[index].value"
                                            class="form-control-modern"
                                            placeholder="Например: XL"
                                        >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Секция</label>
                                    <select
                                        v-model="productForm.options[index].section"
                                        class="form-control-modern"
                                    >
                                        <option :value="null">Без секции</option>
                                        <option v-for="section in sections" :key="section" :value="section">
                                            {{ section }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                </transition>
            </div>

            <!-- Нижняя кнопка сохранения (дублирующая для удобства) -->
            <div class="form-footer">
                <button type="submit" class="btn-primary-modern large" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="spinner-small"></span>
                    <template v-else>
                        <i class="fa-solid fa-check"></i>
                        <span>Сохранить товар</span>
                    </template>
                </button>
            </div>

        </form>
    </div>
</template>

<script>


export default {
    name: "ProductForm",

    props: {
        bot: { type: Object, default: null },
        modelValue: { type: Object, default: null },
    },

    emits: ['callback', 'cancel', 'remove-product'],

    data() {
        return {
            isSubmitting: false,
            sectionForm: { section: null },
            types: [
                { title: 'Готовый продукт', value: 1 },
                { title: 'Товар на вес', value: 2 },
                { title: 'Конструктор товара', value: 3 },
            ],
            productCategories: [],
            sections: ["Общие характеристики", "Дополнительная информация"],
            photos: [],
            categories: [],
            removed_options: [],
            errors: {
                title: false,
                current_price: false,
            },
            openSections: {
                basic: true,
                description: false,
                dimensions: false,
                images: false,
                integrations: false,
                categories: false,
                options: false,
            },
            productForm: this.getDefaultForm(),
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        self() {
            return window.TenantUser || null;
        },
        isEditing() {
            return !!this.productForm.id;
        },

        discountPercent() {
            if (!this.productForm.old_price || !this.productForm.current_price) return 0;
            if (this.productForm.old_price <= this.productForm.current_price) return 0;
            return Math.round(
                ((this.productForm.old_price - this.productForm.current_price) / this.productForm.old_price) * 100
            );
        },

        existingImagesCount() {
            return this.productForm.images?.length || 0;
        },

        newPhotosCount() {
            return this.photos.length;
        },
    },

    watch: {
        modelValue: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.loadFromModel(newVal);
                }
            }
        }
    },

    mounted() {
        this.loadProductCategories();
    },

    methods: {
        getDefaultForm() {
            return {
                article: null,
                vk_product_id: null,
                frontpad_article: null,
                iiko_article: null,
                title: null,
                rating: 5,
                description: null,
                delivery_terms: null,
                images: [],
                type: 1,
                old_price: null,
                current_price: null,
                variants: [],
                in_stop_list_at: null,
                bot_id: null,
                options: [],
                reviews: [],
                not_for_delivery: false,
                is_weight_product: false,
                weight_config: { min: 0, max: 0, step: 0 },
                dimension: { width: 0, height: 0, length: 0, weight: 0 },
                categories: [],
            };
        },

        loadFromModel(model) {
            this.productForm = {
                id: model.id || null,
                article: model.article || null,
                frontpad_article: model.frontpad_article || null,
                iiko_article: model.iiko_article || null,
                vk_product_id: model.vk_product_id || null,
                title: model.title || null,
                rating: model.rating || 5,
                description: model.description || null,
                delivery_terms: model.delivery_terms || null,
                images: model.images || [],
                type: model.type || 1,
                old_price: model.old_price || null,
                current_price: model.current_price || null,
                variants: model.variants || [],
                in_stop_list_at: model.in_stop_list_at || null,
                bot_id: model.bot_id || null,
                options: model.options || [],
                reviews: model.reviews || [],
                not_for_delivery: model.not_for_delivery || false,
                is_weight_product: model.is_weight_product || false,
                weight_config: {
                    min: model.weight_config?.min || 0,
                    max: model.weight_config?.max || 0,
                    step: model.weight_config?.step || 0,
                },
                dimension: {
                    width: model.dimension?.width || 0,
                    height: model.dimension?.height || 0,
                    length: model.dimension?.length || 0,
                    weight: model.dimension?.weight || 0,
                },
            };

            this.productCategories = [];
            model.categories?.forEach(category => {
                this.productCategories.push(category.id);
            });
        },

        toggleSection(section) {
            this.openSections[section] = !this.openSections[section];
        },

        validateForm() {
            this.errors = { title: false, current_price: false };
            let isValid = true;

            if (!this.productForm.title?.trim()) {
                this.errors.title = true;
                this.openSections.basic = true;
                isValid = false;
            }
            if (!this.productForm.current_price && this.productForm.current_price !== 0) {
                this.errors.current_price = true;
                this.openSections.basic = true;
                isValid = false;
            }

            return isValid;
        },

        async submit() {
            if (!this.validateForm()) {
                this.$notify?.({
                    title: 'Ошибка валидации',
                    text: 'Заполните обязательные поля',
                    type: 'warning'
                });
                return;
            }

            this.isSubmitting = true;

            const data = new FormData();
            Object.keys(this.productForm).forEach(key => {
                const item = this.productForm[key];
                if (item === null || item === undefined) {
                    data.append(key, '');
                } else if (typeof item === 'object') {
                    data.append(key, JSON.stringify(item));
                } else {
                    data.append(key, item);
                }
            });

            if (this.bot) data.append("bot_id", this.bot.id);

            if (this.photos.length > 0) {
                for (let i = 0; i < this.photos.length; i++) {
                    data.append('photos[]', this.photos[i]);
                }
            }

            if (this.removed_options.length > 0) {
                data.append("removed_options", JSON.stringify(this.removed_options));
            }

            if (this.productCategories.length > 0) {
                data.append("categories", JSON.stringify(this.productCategories));
            }

            try {
                await this.$store.dispatch("saveProduct", { productForm: data });

                this.$notify?.({
                    title: 'Редактор товара',
                    text: 'Товар успешно сохранён',
                    type: 'success'
                });

                this.$emit("callback");
            } catch (err) {
                console.error('Ошибка сохранения товара:', err);
                this.$notify?.({
                    title: 'Редактор товара',
                    text: 'Ошибка сохранения товара',
                    type: 'error'
                });
            } finally {
                this.isSubmitting = false;
            }
        },

        onChangePhotos(e) {
            const files = Array.from(e.target.files);
            this.photos.push(...files);
            // Сбрасываем input, чтобы можно было повторно выбрать те же файлы
            e.target.value = '';
        },

        async loadProductCategories() {
            try {
                await this.$store.dispatch("loadCategories");
                this.categories = this.getCategories || [];
            } catch (err) {
                console.error('Ошибка загрузки категорий:', err);
            }
        },

        getPhoto(imgObject) {
            return { imageUrl: URL.createObjectURL(imgObject) };
        },

        selectCategory(item) {
            if (!this.productCategories) this.productCategories = [];

            const index = this.productCategories.indexOf(item.id);
            if (index !== -1) {
                this.productCategories.splice(index, 1);
            } else {
                this.productCategories.push(item.id);
            }
        },

        addSection() {
            if (!this.sectionForm.section?.trim()) return;
            this.sections.push(this.sectionForm.section.trim());
            this.sectionForm.section = null;
        },

        removeSection(index) {
            this.sections.splice(index, 1);
        },

        addOption() {
            if (!this.productForm.options) this.productForm.options = [];
            this.productForm.options.push({
                id: null,
                key: null,
                value: null,
                title: null,
                section: null,
            });
        },

        removeOption(index) {
            const option = this.productForm.options[index];
            if (option?.id) {
                this.removed_options.push(option.id);
            }
            this.productForm.options.splice(index, 1);
        },

        removeImage(index) {
            this.productForm.images.splice(index, 1);
        },

        removePhoto(index) {
            const photo = this.photos[index];
            // Освобождаем Object URL для предотвращения утечек памяти
            if (photo && typeof photo === 'object') {
                const url = this.getPhoto(photo).imageUrl;
                URL.revokeObjectURL(url);
            }
            this.photos.splice(index, 1);
        },

        clearForm() {
            // Освобождаем все Object URLs
            this.photos.forEach(photo => {
                if (typeof photo === 'object') {
                    URL.revokeObjectURL(this.getPhoto(photo).imageUrl);
                }
            });

            this.photos = [];
            this.removed_options = [];
            this.productCategories = [];
            this.productForm = this.getDefaultForm();
            this.errors = { title: false, current_price: false };
        },
    },
};
</script>

<style lang="scss" scoped>
@use "sass:color";
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-warning: #f59e0b;
$admin-success: #10b981;
$admin-danger: #ef4444;

// ==========================================
// БАЗА
// ==========================================
.product-form {
    background: $admin-bg;
    min-height: 100%;
}

// ==========================================
// HEADER
// ==========================================
.form-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    z-index: 10;
}

.header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.form-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: $admin-text;
}

.product-id-badge {
    padding: 4px 10px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
}

.header-actions {
    display: flex;
    gap: 10px;
}

// ==========================================
// FORM BODY
// ==========================================
.form-body {
    max-width: 900px;
    margin: 24px auto;
    padding: 0 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

// ==========================================
// СЕКЦИИ
// ==========================================
.form-section {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 24px;
    cursor: pointer;
    transition: background 0.2s;
    user-select: none;

    &:hover {
        background: rgba($admin-primary, 0.02);
    }
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    flex-shrink: 0;

    &.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    &.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    &.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    &.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    &.gray { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); }
    &.pink { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
    &.teal { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); }
}

.section-info {
    flex: 1;

    h3 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 2px 0;
        color: $admin-text;
    }

    p {
        font-size: 0.85rem;
        color: $admin-text-muted;
        margin: 0;
    }
}

.section-chevron {
    color: $admin-text-muted;
    transition: transform 0.3s;

    &.rotated {
        transform: rotate(180deg);
    }
}

.section-content {
    padding: 0 24px 24px;
}

// ==========================================
// ФОРМЫ
// ==========================================
.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text-muted;

    .required {
        color: $admin-danger;
    }
}

.form-control-modern {
    padding: 10px 14px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.95rem;
    color: $admin-text;
    background: $admin-card-bg;
    transition: all 0.2s;
    width: 100%;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }

    &.has-error {
        border-color: $admin-danger;
        &:focus { box-shadow: 0 0 0 3px rgba($admin-danger, 0.1); }
    }

    &.textarea {
        resize: vertical;
        min-height: 100px;
        font-family: inherit;
    }
}

select.form-control-modern {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
}

.error-text {
    font-size: 0.8rem;
    color: $admin-danger;
    margin-top: 2px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.input-with-suffix {
    position: relative;

    .form-control-modern {
        padding-right: 40px;
    }
}

.input-suffix {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: $admin-text-muted;
    font-weight: 600;
    pointer-events: none;
}

// ==========================================
// TOGGLE CARDS
// ==========================================
.status-toggles {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.toggle-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 2px solid $admin-border;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    background: $admin-card-bg;

    &:hover {
        border-color: rgba($admin-primary, 0.3);
    }

    &.is-active {
        border-color: $admin-primary;
        background: rgba($admin-primary, 0.04);
    }
}

.toggle-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: $admin-bg;
    color: $admin-text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: all 0.2s;

    .is-active & {
        background: $admin-primary;
        color: white;
    }
}

.toggle-info {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.toggle-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: $admin-text;
}

.toggle-desc {
    font-size: 0.8rem;
    color: $admin-text-muted;
    margin-top: 2px;
}

.weight-toggle {
    margin-bottom: 16px;
}

.weight-config {
    padding: 16px;
    background: $admin-bg;
    border-radius: 10px;
    margin-bottom: 16px;
}

// ==========================================
// PRICE BLOCK
// ==========================================
.price-block {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.discount-hint {
    display: inline-block;
    margin-top: 6px;
    padding: 3px 10px;
    background: rgba($admin-success, 0.1);
    color: $admin-success;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
}

// ==========================================
// RATING
// ==========================================
.rating-input {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.rating-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: $admin-border;
    outline: none;
    -webkit-appearance: none;
    appearance: none;

    &::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: $admin-primary;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba($admin-primary, 0.4);
    }

    &::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: $admin-primary;
        cursor: pointer;
        border: none;
    }
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rating-stars {
    display: flex;
    gap: 4px;

    i {
        color: $admin-border;
        font-size: 1.1rem;
        transition: color 0.2s;

        &.filled {
            color: #fbbf24;
        }
    }
}

.rating-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: $admin-text;
}

// ==========================================
// UPLOAD ZONE
// ==========================================
.upload-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 32px 20px;
    border: 2px dashed $admin-border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 20px;
    text-align: center;

    &:hover {
        border-color: $admin-primary;
        background: rgba($admin-primary, 0.04);
    }
}

.upload-input {
    display: none;
}

.upload-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;

    i {
        font-size: 2rem;
        color: $admin-primary;
    }
}

.upload-title {
    font-weight: 600;
    color: $admin-text;
}

.upload-hint {
    font-size: 0.8rem;
    color: $admin-text-muted;
}

// ==========================================
// IMAGES GRID
// ==========================================
.images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
}

.image-card {
    position: relative;
    aspect-ratio: 1;
    border-radius: 10px;
    overflow: hidden;
    background: $admin-bg;
    border: 1px solid $admin-border;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.image-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    backdrop-filter: blur(8px);

    &.existing {
        background: rgba($admin-success, 0.9);
        color: white;
    }

    &.new {
        background: rgba($admin-primary, 0.9);
        color: white;
    }
}

.image-remove {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    opacity: 0;
    transition: all 0.2s;

    .image-card:hover & {
        opacity: 1;
    }

    &:hover {
        background: $admin-danger;
    }
}

// ==========================================
// CATEGORIES
// ==========================================
.categories-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.category-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 20px;
    color: $admin-text-muted;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;

    i {
        font-size: 0.75rem;
    }

    &:hover {
        border-color: $admin-primary;
        color: $admin-primary;
    }

    &.is-selected {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;
    }
}

// ==========================================
// SECTIONS & OPTIONS
// ==========================================
.subsection {
    margin-bottom: 24px;

    &:last-child {
        margin-bottom: 0;
    }
}

.subsection-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.add-row {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.sections-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.section-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: rgba($admin-primary, 0.08);
    border-radius: 8px;
    font-size: 0.85rem;
    color: $admin-text;

    i:first-child {
        color: $admin-primary;
    }

    button {
        background: none;
        border: none;
        color: $admin-text-muted;
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 4px;
        transition: all 0.2s;

        &:hover {
            background: rgba($admin-danger, 0.1);
            color: $admin-danger;
        }
    }
}

.option-card {
    padding: 16px;
    background: $admin-bg;
    border-radius: 10px;
    margin-bottom: 12px;
    border: 1px solid $admin-border;
}

.option-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.option-number {
    font-size: 0.8rem;
    font-weight: 700;
    color: $admin-text-muted;
    padding: 3px 8px;
    background: $admin-card-bg;
    border-radius: 6px;
}

.btn-remove-small {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: transparent;
    border: 1px solid $admin-border;
    color: $admin-text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $admin-danger;
        border-color: $admin-danger;
        color: white;
    }
}

// ==========================================
// EMPTY STATE
// ==========================================
.empty-state {
    text-align: center;
    padding: 32px 20px;
    color: $admin-text-muted;

    i {
        font-size: 2rem;
        margin-bottom: 8px;
        opacity: 0.5;
    }

    p {
        margin: 0;
        font-size: 0.9rem;
    }

    &.small {
        padding: 20px;
    }
}

// ==========================================
// КНОПКИ
// ==========================================
.btn-primary-modern, .btn-secondary-modern {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-primary-modern {
    background: $admin-primary;
    color: white;

    &:hover:not(:disabled) {
        background:  color.adjust($admin-primary, $lightness: -5%);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    &.large {
        padding: 14px 24px;
        font-size: 1rem;
        width: 100%;
    }
}

.btn-secondary-modern {
    background: $admin-bg;
    color: $admin-text;
    border: 1px solid $admin-border;

    &:hover {
        background: color.adjust($admin-bg, $lightness: -3%);
    }
}

.btn-text {
    background: none;
    border: none;
    color: $admin-primary;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 6px;

    &:hover {
        background: rgba($admin-primary, 0.1);
    }
}

.spinner-small {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// FOOTER
// ==========================================
.form-footer {
    margin-top: 8px;
    padding-bottom: 24px;
}

// ==========================================
// TRANSITIONS
// ==========================================
.collapse-enter-active, .collapse-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.collapse-enter-from, .collapse-leave-to {
    opacity: 0;
    max-height: 0;
}

.collapse-enter-to, .collapse-leave-from {
    opacity: 1;
    max-height: 2000px;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .form-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding: 16px;
    }

    .header-actions {
        justify-content: stretch;

        button {
            flex: 1;
        }
    }

    .form-body {
        padding: 0 16px;
        margin: 16px auto;
    }

    .section-header {
        padding: 14px 16px;
    }

    .section-content {
        padding: 0 16px 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .status-toggles {
        grid-template-columns: 1fr;
    }

    .price-block {
        grid-template-columns: 1fr;
    }
}
</style>
