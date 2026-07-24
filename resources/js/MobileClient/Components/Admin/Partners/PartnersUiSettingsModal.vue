<template>
    <transition name="modal-fade">
        <div v-if="modelValue" class="settings-modal-overlay" @click.self="closeModal">
            <div class="settings-modal-container">

                <!-- Шапка модалки -->
                <div class="modal-header">
                    <div class="modal-title-wrapper">
                        <div class="modal-icon">
                            <i class="fa-solid fa-palette"></i>
                        </div>
                        <div>
                            <h3 class="modal-title">Настройки внешнего вида</h3>
                            <p class="modal-subtitle">Категории, сервисы, фильтры и главный экран</p>
                        </div>
                    </div>
                    <button class="modal-close-btn" @click="closeModal" title="Закрыть">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Тело модалки -->
                <div class="modal-body">

                    <!-- 1. HERO СЕКЦИЯ -->
                    <div class="settings-card">
                        <h4 class="card-title"><i class="fa-solid fa-image"></i> Главный экран</h4>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Заголовок</label>
                                <input v-model="uiForm.hero.title" type="text" class="form-input" placeholder="Вкусные блюда...">
                            </div>
                            <div class="form-group full-width">
                                <label>Подзаголовок</label>
                                <input v-model="uiForm.hero.subtitle" type="text" class="form-input" placeholder="Доставка из кафе...">
                            </div>
                            <div class="form-group full-width">
                                <label>Текст в поле поиска</label>
                                <input v-model="uiForm.hero.search_placeholder" type="text" class="form-input" placeholder="Поиск блюд и заведений">
                            </div>
                        </div>
                        <div class="images-grid">
                            <div v-for="(img, index) in ['bg_image_1', 'bg_image_2', 'bg_image_3', 'bg_image_4']" :key="img" class="image-upload-box">
                                <label class="image-label">Картинка {{ index + 1 }}</label>
                                <div class="image-preview" :style="{ backgroundImage: imagePreviews[img] ? `url(${imagePreviews[img]})` : 'none' }">
                                    <div v-if="!imagePreviews[img]" class="image-placeholder"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                    <button v-else class="remove-image" @click.prevent="removeImage(img)" type="button"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <input type="file" accept="image/*" @change="(e) => handleImageUpload(e, img)" class="file-input" hidden :ref="'file_' + img">
                                <button class="upload-btn" @click.prevent="triggerFileInput(img)">Выбрать</button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. КАТЕГОРИИ МЕНЮ (с эмодзи) -->
                    <div class="settings-card">
                        <div class="card-header-row">
                            <h4 class="card-title"><i class="fa-solid fa-layer-group"></i> Категории меню</h4>
                            <button class="add-btn" @click="addItem('categories')"><i class="fa-solid fa-plus"></i></button>
                        </div>

                        <div class="title-input-row">
                            <label>Заголовок секции на странице</label>
                            <input v-model="uiForm.categories_title" type="text" class="form-input title-input" placeholder="Популярные категории">
                        </div>

                        <div class="categories-grid">
                            <div v-for="(item, index) in uiForm.categories" :key="item.id || index" class="category-card">
                                <div class="category-emoji-display">
                                    {{ item.icon || '🍽️' }}
                                </div>
                                <div class="category-info">
                                    <input v-model="item.icon" type="text" class="form-input" placeholder="Эмодзи (🍕)" maxlength="4">
                                    <input v-model="item.name" type="text" class="form-input" placeholder="Название (Пицца)">
                                    <input v-model="item.slug" type="text" class="form-input" placeholder="Slug (pizza)">
                                </div>
                                <button class="remove-category-btn" @click="removeItem('categories', index)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. СЕРВИСЫ -->
                    <div class="settings-card">
                        <div class="card-header-row">
                            <h4 class="card-title"><i class="fa-solid fa-star"></i> Преимущества</h4>
                            <button class="add-btn" @click="addItem('services')"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        <div class="items-grid">
                            <div v-for="(item, index) in uiForm.services" :key="item.id || index" class="service-card">
                                <div class="service-icon-wrapper" :class="{ 'has-icon': item.icon }">
                                    <i v-if="item.icon" :class="item.icon"></i><span v-else>?</span>
                                </div>
                                <div class="service-info">
                                    <input v-model="item.label" type="text" class="form-input" placeholder="Текст преимущества">
                                </div>
                                <div class="service-actions">
                                    <button class="icon-picker-btn" @click="openIconPicker(index)"><i class="fa-solid fa-icons"></i></button>
                                    <button class="remove-service-btn" @click="removeItem('services', index)"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. ФИЛЬТРЫ (ТЕГИ) -->
                    <div class="settings-card">
                        <div class="card-header-row">
                            <h4 class="card-title"><i class="fa-solid fa-tags"></i> Фильтры</h4>
                            <button class="add-btn" @click="addItem('filters')"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        <div class="filters-grid">
                            <div v-for="(item, index) in uiForm.filters" :key="item.id || index" class="filter-tag">
                                <div class="filter-icon"><i class="fa-solid fa-tag"></i></div>
                                <div class="filter-inputs">
                                    <input v-model="item.name" type="text" class="form-input" placeholder="Название">
                                    <input v-model="item.slug" type="text" class="form-input" placeholder="Slug">
                                </div>
                                <button class="remove-filter-btn" @click="removeItem('filters', index)"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Подвал модалки -->
                <div class="modal-footer">
                    <button class="cancel-btn" @click="closeModal">Отмена</button>
                    <button class="save-btn" @click="saveSettings" :disabled="isSaving">
                        <i class="fa-solid fa-spinner fa-spin" v-if="isSaving"></i>
                        <i class="fa-solid fa-check" v-else></i>
                        <span>{{ isSaving ? 'Сохранение...' : 'Сохранить' }}</span>
                    </button>
                </div>

            </div>
        </div>
    </transition>

    <!-- Модалка выбора иконки FontAwesome (для сервисов) -->
    <transition name="modal-fade">
        <div v-if="showIconPicker" class="icon-picker-overlay" @click.self="closeIconPicker">
            <div class="icon-picker-modal">
                <div class="picker-header">
                    <h4>Выберите иконку</h4>
                    <button class="picker-close" @click="closeIconPicker"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="picker-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input v-model="iconSearchQuery" type="text" placeholder="Поиск..." class="form-input">
                </div>
                <div class="icons-grid">
                    <button v-for="icon in filteredIcons" :key="icon" class="icon-option" :class="{ 'selected': currentEditingService && currentEditingService.icon === icon }" @click="selectIcon(icon)">
                        <i :class="icon"></i>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: 'PartnersUiSettingsModal',
    props: { modelValue: { type: Boolean, default: false } },
    emits: ['update:modelValue', 'saved'],

    data() {
        return {
            isSaving: false,
            showIconPicker: false,
            iconSearchQuery: '',
            currentEditingServiceIndex: null,
            imagePreviews: {},

            availableIcons: [
                'fa-solid fa-bolt', 'fa-solid fa-bag-shopping', 'fa-solid fa-shield-halved',
                'fa-solid fa-motorcycle', 'fa-solid fa-truck-fast', 'fa-solid fa-clock',
                'fa-solid fa-star', 'fa-solid fa-heart', 'fa-solid fa-thumbs-up',
                'fa-solid fa-certificate', 'fa-solid fa-gem', 'fa-solid fa-crown',
                'fa-solid fa-leaf', 'fa-solid fa-fire', 'fa-solid fa-mug-hot',
                'fa-solid fa-location-dot', 'fa-solid fa-phone', 'fa-solid fa-gift',
                'fa-solid fa-percent', 'fa-solid fa-wallet', 'fa-solid fa-award'
            ],

            uiForm: {
                hero: {
                    title: 'Вкусные блюда из любимых заведений',
                    subtitle: 'Доставка из кафе и ресторанов быстро и удобно',
                    search_placeholder: 'Поиск блюд и заведений',
                    bg_image_1: '', bg_image_2: '', bg_image_3: '', bg_image_4: ''
                },
                categories_title: 'Популярные категории',
                categories: [
                    { id: 1, name: 'Пицца', icon: '🍕', slug: 'pizza' },
                    { id: 2, name: 'Бургеры', icon: '🍔', slug: 'burgers' },
                    { id: 3, name: 'Шаурма', icon: '🌯', slug: 'shawarma' },
                    { id: 4, name: 'Суши и роллы', icon: '🍣', slug: 'sushi' },
                    { id: 5, name: 'Шашлык', icon: '🍖', slug: 'shashlik' },
                    { id: 6, name: 'Хинкали', icon: '🥟', slug: 'khinkali' },
                    { id: 7, name: 'Хачапури', icon: '🫓', slug: 'khachapuri' },
                    { id: 8, name: 'Лапша / Wok', icon: '🍜', slug: 'wok' },
                    { id: 9, name: 'Паста', icon: '🍝', slug: 'pasta' },
                    { id: 10, name: 'Донер / Кебаб', icon: '🥙', slug: 'doner' },
                    { id: 11, name: 'Тако и буррито', icon: '🌮', slug: 'taco' },
                    { id: 12, name: 'Пельмени и вареники', icon: '🥟', slug: 'dumplings' },
                    { id: 13, name: 'Обеды', icon: '🍱', slug: 'lunch' },
                    { id: 14, name: 'Морепродукты', icon: '🦞', slug: 'seafood' },
                    { id: 15, name: 'Закуски', icon: '🍟', slug: 'snacks' },
                    { id: 16, name: 'Пивные закуски', icon: '🍺', slug: 'beer_snacks' },
                    { id: 17, name: 'Салаты', icon: '🥗', slug: 'salads' },
                    { id: 18, name: 'ПП', icon: '🥑', slug: 'pp' }
                ],
                services: [
                    { id: 1, label: 'Быстрая доставка', icon: 'fa-solid fa-bolt' },
                    { id: 2, label: 'Большой выбор', icon: 'fa-solid fa-bag-shopping' }
                ],
                filters: [
                    { id: 1, name: 'Рядом с вами', slug: 'nearby' },
                    { id: 2, name: 'С рейтингом', slug: 'rating' },
                    { id: 3, name: 'Новинки', slug: 'new' },
                    { id: 4, name: 'Акции', slug: 'promo' }
                ]
            }
        };
    },

    computed: {
        tenant() { return window.Tenant; },
        filteredIcons() {
            if (!this.iconSearchQuery) return this.availableIcons;
            return this.availableIcons.filter(icon => icon.includes(this.iconSearchQuery.toLowerCase()));
        },
        currentEditingService() {
            return this.currentEditingServiceIndex !== null ? this.uiForm.services[this.currentEditingServiceIndex] : null;
        }
    },

    watch: {
        modelValue(newValue) {
            if (newValue) {
                this.loadSettings();
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    },

    methods: {
        closeModal() { this.$emit('update:modelValue', false); },

        loadSettings() {
            const settings = this.tenant?.settings?.partners?.ui || {};
            if (settings.hero) this.uiForm.hero = { ...this.uiForm.hero, ...settings.hero };
            // Поддержка старого названия 'cuisines' для обратной совместимости
            if (settings.categories) this.uiForm.categories = JSON.parse(JSON.stringify(settings.categories));
            else if (settings.cuisines) this.uiForm.categories = JSON.parse(JSON.stringify(settings.cuisines));

            if (settings.services) this.uiForm.services = JSON.parse(JSON.stringify(settings.services));
            if (settings.filters) this.uiForm.filters = JSON.parse(JSON.stringify(settings.filters));
            if (settings.categories_title) this.uiForm.categories_title = settings.categories_title;
            else if (settings.cuisines_title) this.uiForm.categories_title = settings.cuisines_title;

            this.imagePreviews = {};
            if (this.uiForm.hero.bg_image_1) this.imagePreviews.bg_image_1 = this.uiForm.hero.bg_image_1;
            if (this.uiForm.hero.bg_image_2) this.imagePreviews.bg_image_2 = this.uiForm.hero.bg_image_2;
            if (this.uiForm.hero.bg_image_3) this.imagePreviews.bg_image_3 = this.uiForm.hero.bg_image_3;
            if (this.uiForm.hero.bg_image_4) this.imagePreviews.bg_image_4 = this.uiForm.hero.bg_image_4;
        },

        async saveSettings() {
            this.isSaving = true;
            try {
                // TODO: Заменить на реальный API запрос
                await new Promise(resolve => setTimeout(resolve, 600));

                if (!this.tenant.settings.partners) this.tenant.settings.partners = {};
                this.tenant.settings.partners.ui = JSON.parse(JSON.stringify(this.uiForm));

                this.$notify?.({ title: 'Успех', text: 'Настройки сохранены', type: 'success' });
                this.$emit('saved', this.uiForm);
                this.closeModal();
            } catch (error) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' });
            } finally {
                this.isSaving = false;
            }
        },

        addItem(collection) {
            const newItem = { id: Date.now() };
            if (collection === 'categories') Object.assign(newItem, { name: '', icon: '', slug: '' });
            if (collection === 'services') Object.assign(newItem, { label: '', icon: '' });
            if (collection === 'filters') Object.assign(newItem, { name: '', slug: '' });
            this.uiForm[collection].push(newItem);
        },

        removeItem(collection, index) { this.uiForm[collection].splice(index, 1); },

        triggerFileInput(key) { this.$refs['file_' + key][0].click(); },

        handleImageUpload(event, key) {
            const file = event.target.files[0];
            if (!file) return;
            this.imagePreviews[key] = URL.createObjectURL(file);
            this.uiForm.hero[key] = file;
            event.target.value = '';
        },

        removeImage(key) {
            this.imagePreviews[key] = null;
            this.uiForm.hero[key] = '';
        },

        openIconPicker(index) {
            this.currentEditingServiceIndex = index;
            this.iconSearchQuery = '';
            this.showIconPicker = true;
        },

        closeIconPicker() {
            this.showIconPicker = false;
            this.currentEditingServiceIndex = null;
        },

        selectIcon(iconClass) {
            if (this.currentEditingService) this.currentEditingService.icon = iconClass;
            this.closeIconPicker();
        }
    },

    beforeUnmount() { document.body.style.overflow = ''; }
};
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-danger: #ef4444;
$admin-success: #10b981;

.settings-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(6px);
    z-index: 9998;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.settings-modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid $admin-border;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: $admin-card-bg;
    flex-shrink: 0;
}

.modal-title-wrapper {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    flex: 1;
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, $admin-primary 0%, #2563eb 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.modal-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0 0 2px 0;
    white-space: nowrap;
}

.modal-subtitle {
    font-size: 0.85rem;
    color: $admin-text-muted;
    margin: 0;
}

.modal-close-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $admin-bg;
    border: none;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s;
    flex-shrink: 0;
    &:hover { background: $admin-danger; color: white; }
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid $admin-border;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    background: $admin-card-bg;
    flex-shrink: 0;
}

.cancel-btn {
    padding: 10px 20px;
    background: transparent;
    border: 1px solid $admin-border;
    border-radius: 10px;
    color: $admin-text;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    &:hover { background: $admin-bg; }
}

.save-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: $admin-primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    &:hover:not(:disabled) { background: #2563eb; transform: translateY(-1px); }
    &:disabled { opacity: 0.7; cursor: wait; }
}

.settings-card {
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 14px;
    padding: 20px;
}

.card-title {
    font-size: 1rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    i { color: $admin-primary; }
}

.card-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    .card-title { margin: 0; }
}

.add-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: white;
    color: $admin-primary;
    border: 1px solid rgba($admin-primary, 0.3);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s;
    flex-shrink: 0;
    &:hover { background: $admin-primary; color: white; border-color: $admin-primary; }
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full-width { grid-column: 1 / -1; }

label {
    font-size: 0.8rem;
    font-weight: 600;
    color: $admin-text-muted;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-input {
    padding: 10px 14px;
    border: 1px solid $admin-border;
    border-radius: 10px;
    font-size: 0.9rem;
    color: $admin-text;
    background: white;
    transition: all 0.2s;
    width: 100%;
    box-sizing: border-box;
    &:focus { outline: none; border-color: $admin-primary; box-shadow: 0 0 0 3px rgba($admin-primary, 0.1); }
}

// Изображения Hero
.images-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.image-upload-box {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.image-label { font-size: 0.75rem; text-align: center; color: $admin-text-muted; font-weight: 600; }
.image-preview {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 12px;
    border: 2px dashed $admin-border;
    background-size: cover;
    background-position: center;
    background-color: white;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    &:hover { border-color: $admin-primary; }
}
.image-placeholder {
    text-align: center;
    color: $admin-text-muted;
    i { font-size: 1.5rem; display: block; }
}
.remove-image {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba($admin-danger, 0.9);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    &:hover { transform: scale(1.1); }
}
.upload-btn {
    width: 100%;
    padding: 8px;
    background: white;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $admin-text;
    cursor: pointer;
    &:hover { background: $admin-bg; border-color: $admin-text-muted; }
}

// КАТЕГОРИИ БЛЮД (сетка карточек)
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.category-card {
    background: white;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 12px;
    transition: all 0.2s;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 10px;

    &:hover {
        border-color: $admin-primary;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
}

.category-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    background: $admin-bg;
    overflow: hidden;
}

.category-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.category-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: $admin-text-muted;
    font-size: 2rem;
}

.category-upload-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    cursor: pointer;
    transition: opacity 0.2s;
    font-size: 1.3rem;
    &:hover { opacity: 1; }
}

.category-info {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.remove-category-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba($admin-danger, 0.9);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.2s;
    z-index: 2;
    &:hover { background: $admin-danger; transform: scale(1.1); }
}

.category-card:hover .remove-category-btn {
    opacity: 1;
}

// СЕРВИСЫ
.service-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 12px;
    transition: all 0.2s;

    &:hover {
        border-color: $admin-primary;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
}

.service-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: $admin-text-muted;
    flex-shrink: 0;

    &.has-icon {
        color: $admin-primary;
        background: rgba($admin-primary, 0.05);
        border-color: rgba($admin-primary, 0.2);
    }
}

.service-info {
    flex: 1;
    min-width: 0;
}

.service-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

.icon-picker-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: white;
    border: 1px solid $admin-border;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;
    &:hover { border-color: $admin-primary; color: $admin-primary; background: rgba($admin-primary, 0.05); }
}

.remove-service-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba($admin-danger, 0.08);
    color: $admin-danger;
    border: 1px solid rgba($admin-danger, 0.2);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;
    &:hover { background: $admin-danger; color: white; border-color: $admin-danger; }
}

// ФИЛЬТРЫ (адаптивная сетка)
.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 12px;
}

.filter-tag {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    border: 1px solid $admin-border;
    border-radius: 10px;
    padding: 10px;
    transition: all 0.2s;

    &:hover {
        border-color: $admin-primary;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
}

.filter-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.filter-inputs {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.remove-filter-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba($admin-danger, 0.08);
    color: $admin-danger;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all 0.2s;
    &:hover { background: $admin-danger; color: white; }
}

// Модалка иконок
.icon-picker-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.icon-picker-modal {
    background: $admin-card-bg;
    width: 100%;
    max-width: 550px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
    max-height: 80vh;
}

.picker-header {
    padding: 16px 20px;
    border-bottom: 1px solid $admin-border;
    display: flex;
    justify-content: space-between;
    align-items: center;
    h4 { margin: 0; font-size: 1.1rem; color: $admin-text; }
}

.picker-close {
    width: 32px; height: 32px; border-radius: 50%;
    background: $admin-bg; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: $admin-text;
    &:hover { background: $admin-danger; color: white; }
}

.picker-search {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: $admin-bg;
    border-bottom: 1px solid $admin-border;
    i { color: $admin-text-muted; }
    .form-input { border: none; background: transparent; padding: 0; font-size: 1rem; flex: 1; }
    .form-input:focus { box-shadow: none; }
}

.icons-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.icon-option {
    aspect-ratio: 1;
    border: 1px solid $admin-border;
    border-radius: 10px;
    background: white;
    font-size: 1.3rem;
    color: $admin-text-muted;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    &:hover { border-color: $admin-primary; color: $admin-primary; transform: scale(1.1); z-index: 1; }
    &.selected { background: $admin-primary; color: white; border-color: $admin-primary; box-shadow: 0 4px 12px rgba($admin-primary, 0.3); }
}

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 768px) {
    .settings-modal-overlay {
        padding: 0;
        align-items: flex-end;
    }

    .settings-modal-container {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 20px 20px 0 0;
    }

    .modal-body {
        padding: 16px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .images-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .items-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .filters-grid {
        grid-template-columns: 1fr; // Одна колонка на мобильном
    }

    .service-card {
        flex-wrap: wrap;

        .service-info {
            width: 100%;
        }

        .service-actions {
            width: 100%;
            margin-top: 4px;
        }
    }

    .icons-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

@media (max-width: 480px) {
    .modal-title { font-size: 1rem; }
    .modal-subtitle { display: none; }

    .items-grid {
        grid-template-columns: 1fr; // Одна колонка на очень маленьких экранах
    }

    .icons-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .service-card {
        padding: 10px;

        .service-icon-wrapper {
            width: 40px;
            height: 40px;
        }
    }
}
</style>
