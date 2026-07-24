<template>
    <div class="partners-ui-settings">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fa-solid fa-palette"></i>
                Внешний вид и контент
            </h2>
            <button
                class="save-btn"
                @click="saveSettings"
                :disabled="isSaving"
            >
                <i class="fa-solid fa-spinner fa-spin" v-if="isSaving"></i>
                <i class="fa-solid fa-check" v-else></i>
                <span>{{ isSaving ? 'Сохранение...' : 'Сохранить настройки' }}</span>
            </button>
        </div>

        <!-- 1. НАСТРОЙКИ HERO СЕКЦИИ -->
        <div class="settings-card">
            <h3 class="card-title"><i class="fa-solid fa-image"></i> Главный экран (Hero)</h3>

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
                    <label class="image-label">Плавающая картинка {{ index + 1 }}</label>
                    <div class="image-preview" :style="{ backgroundImage: imagePreviews[img] ? `url(${imagePreviews[img]})` : 'none' }">
                        <div v-if="!imagePreviews[img]" class="image-placeholder">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Загрузить</span>
                        </div>
                        <button v-else class="remove-image" @click.prevent="removeImage(img)" type="button">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <input type="file" accept="image/*" @change="(e) => handleImageUpload(e, img)" class="file-input" hidden>
                    <button class="upload-btn" @click.prevent="triggerFileInput(img)">Выбрать файл</button>
                </div>
            </div>
        </div>

        <!-- 2. НАСТРОЙКА КУХОНЬ -->
        <div class="settings-card">
            <div class="card-header-row">
                <h3 class="card-title"><i class="fa-solid fa-utensils"></i> Популярные кухни</h3>
                <button class="add-btn" @click="addItem('cuisines')">
                    <i class="fa-solid fa-plus"></i> Добавить
                </button>
            </div>

            <div v-for="(item, index) in uiForm.cuisines" :key="item.id || index" class="dynamic-list-item">
                <div class="item-image-box">
                    <img v-if="getImagePreview('cuisine', item.id)" :src="getImagePreview('cuisine', item.id)" class="item-preview-img">
                    <div v-else class="item-preview-placeholder"><i class="fa-solid fa-image"></i></div>
                    <label class="item-image-upload">
                        <i class="fa-solid fa-camera"></i>
                        <input type="file" accept="image/*" @change="(e) => handleItemImageUpload(e, 'cuisine', item.id)" hidden>
                    </label>
                </div>
                <div class="item-inputs">
                    <input v-model="item.name" type="text" class="form-input" placeholder="Название (напр. Итальянская)">
                    <input v-model="item.slug" type="text" class="form-input" placeholder="Slug для поиска (напр. italian)">
                </div>
                <button class="remove-btn" @click="removeItem('cuisines', index)" title="Удалить">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <!-- 3. НАСТРОЙКА СЕРВИСОВ -->
        <div class="settings-card">
            <div class="card-header-row">
                <h3 class="card-title"><i class="fa-solid fa-star"></i> Преимущества (Сервисы)</h3>
                <button class="add-btn" @click="addItem('services')">
                    <i class="fa-solid fa-plus"></i> Добавить
                </button>
            </div>

            <div v-for="(item, index) in uiForm.services" :key="item.id || index" class="dynamic-list-item">
                <div class="item-icon-box" :class="{ 'has-icon': item.icon }">
                    <i v-if="item.icon" :class="item.icon"></i>
                    <span v-else>?</span>
                </div>
                <div class="item-inputs">
                    <input v-model="item.label" type="text" class="form-input" placeholder="Текст (напр. Быстрая доставка)">
                </div>
                <button class="icon-picker-btn" @click="openIconPicker(index)">
                    <i class="fa-solid fa-icons"></i> Иконка
                </button>
                <button class="remove-btn" @click="removeItem('services', index)" title="Удалить">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <!-- 4. НАСТРОЙКА ФИЛЬТРОВ (ТЕГОВ) -->
        <div class="settings-card">
            <div class="card-header-row">
                <h3 class="card-title"><i class="fa-solid fa-tags"></i> Фильтры (Теги)</h3>
                <button class="add-btn" @click="addItem('filters')">
                    <i class="fa-solid fa-plus"></i> Добавить
                </button>
            </div>

            <div v-for="(item, index) in uiForm.filters" :key="item.id || index" class="dynamic-list-item simple-item">
                <div class="item-inputs">
                    <input v-model="item.name" type="text" class="form-input" placeholder="Название (напр. Рядом с вами)">
                    <input v-model="item.slug" type="text" class="form-input" placeholder="Slug (напр. nearby)">
                </div>
                <button class="remove-btn" @click="removeItem('filters', index)" title="Удалить">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ВЫБОР ИКОНКИ FONTAWESOME -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showIconPicker" class="modal-overlay" @click.self="closeIconPicker">
                <div class="icon-picker-modal">
                    <div class="modal-header">
                        <h3>Выберите иконку</h3>
                        <button class="modal-close" @click="closeIconPicker"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input v-model="iconSearchQuery" type="text" placeholder="Поиск иконки (напр. 'bolt', 'star')..." class="form-input">
                    </div>
                    <div class="icons-grid">
                        <button
                            v-for="icon in filteredIcons"
                            :key="icon"
                            class="icon-option"
                            :class="{ 'selected': currentEditingService && currentEditingService.icon === icon }"
                            @click="selectIcon(icon)"
                        >
                            <i :class="icon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    name: 'PartnersUiSettings',

    data() {
        return {
            isSaving: false,
            showIconPicker: false,
            iconSearchQuery: '',
            currentEditingServiceIndex: null,

            // Хранилище превью картинок (чтобы не грузить сервер при каждом выборе)
            imagePreviews: {},
            itemImagePreviews: {},

            // Список популярных иконок FA для выбора
            availableIcons: [
                'fa-solid fa-bolt', 'fa-solid fa-bag-shopping', 'fa-solid fa-shield-halved',
                'fa-solid fa-motorcycle', 'fa-solid fa-truck-fast', 'fa-solid fa-clock',
                'fa-solid fa-star', 'fa-solid fa-heart', 'fa-solid fa-thumbs-up',
                'fa-solid fa-certificate', 'fa-solid fa-gem', 'fa-solid fa-crown',
                'fa-solid fa-leaf', 'fa-solid fa-fire', 'fa-solid fa-mug-hot',
                'fa-solid fa-burger', 'fa-solid fa-pizza-slice', 'fa-solid fa-ice-cream',
                'fa-solid fa-location-dot', 'fa-solid fa-phone', 'fa-solid fa-envelope',
                'fa-solid fa-gift', 'fa-solid fa-percent', 'fa-solid fa-wallet'
            ],

            uiForm: {
                hero: {
                    title: 'Вкусные блюда из любимых заведений',
                    subtitle: 'Доставка из кафе и ресторанов быстро и удобно',
                    search_placeholder: 'Поиск блюд и заведений',
                    bg_image_1: '', bg_image_2: '', bg_image_3: '', bg_image_4: ''
                },
                cuisines: [
                    { id: 1, name: 'Итальянская', slug: 'italian', image: '' },
                    { id: 2, name: 'Японская', slug: 'japanese', image: '' }
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
        tenant() {
            return window.Tenant;
        },
        filteredIcons() {
            if (!this.iconSearchQuery) return this.availableIcons;
            const query = this.iconSearchQuery.toLowerCase();
            return this.availableIcons.filter(icon => icon.includes(query));
        },
        currentEditingService() {
            if (this.currentEditingServiceIndex === null) return null;
            return this.uiForm.services[this.currentEditingServiceIndex];
        }
    },

    mounted() {
        this.loadSettings();
    },

    methods: {
        loadSettings() {
            const settings = this.tenant?.settings?.partners?.ui || {};
            if (settings.hero) this.uiForm.hero = { ...this.uiForm.hero, ...settings.hero };
            if (settings.cuisines) this.uiForm.cuisines = settings.cuisines;
            if (settings.services) this.uiForm.services = settings.services;
            if (settings.filters) this.uiForm.filters = settings.filters;

            // Восстанавливаем превью для загруженных картинок
            if (this.uiForm.hero.bg_image_1) this.imagePreviews.bg_image_1 = this.uiForm.hero.bg_image_1;
            // ... (можно добавить цикл для всех)
        },

        async saveSettings() {
            this.isSaving = true;
            try {
                // Здесь должен быть ваш API вызов. Пример:
                // await axios.post('/admin/tenant-settings/partners/ui', this.uiForm);

                // Эмуляция сохранения:
                await new Promise(resolve => setTimeout(resolve, 800));

                if (!this.tenant.settings.partners) this.tenant.settings.partners = {};
                this.tenant.settings.partners.ui = JSON.parse(JSON.stringify(this.uiForm));

                this.$notify?.({ title: 'Успех', text: 'Настройки внешнего вида сохранены', type: 'success' });
            } catch (error) {
                console.error(error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить настройки', type: 'error' });
            } finally {
                this.isSaving = false;
            }
        },

        // --- Управление списками ---
        addItem(collection) {
            const newItem = { id: Date.now() };
            if (collection === 'cuisines') { Object.assign(newItem, { name: '', slug: '', image: '' }); }
            if (collection === 'services') { Object.assign(newItem, { label: '', icon: '' }); }
            if (collection === 'filters') { Object.assign(newItem, { name: '', slug: '' }); }

            this.uiForm[collection].push(newItem);
        },

        removeItem(collection, index) {
            this.uiForm[collection].splice(index, 1);
        },

        // --- Управление картинками ---
        triggerFileInput(key) {
            // Находим input по ключу и кликаем (упрощенно, в реальном коде лучше через ref)
            event.target.parentElement.querySelector('input[type="file"]').click();
        },

        handleImageUpload(event, key) {
            const file = event.target.files[0];
            if (!file) return;

            this.imagePreviews[key] = URL.createObjectURL(file);
            this.uiForm.hero[key] = file; // В реальном приложении здесь загрузка на сервер и сохранение имени файла

            // Очистка input для возможности повторного выбора того же файла
            event.target.value = '';
        },

        removeImage(key) {
            this.imagePreviews[key] = null;
            this.uiForm.hero[key] = '';
        },

        getImagePreview(type, id) {
            return this.itemImagePreviews[`${type}_${id}`];
        },

        handleItemImageUpload(event, type, id) {
            const file = event.target.files[0];
            if (!file) return;

            const key = `${type}_${id}`;
            this.itemImagePreviews[key] = URL.createObjectURL(file);

            // Находим элемент и обновляем его image поле
            const collection = type === 'cuisine' ? 'cuisines' : type;
            const item = this.uiForm[collection].find(i => i.id === id);
            if (item) item.image = file;

            event.target.value = '';
        },

        // --- Выбор иконок ---
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
            if (this.currentEditingService) {
                this.currentEditingService.icon = iconClass;
            }
            this.closeIconPicker();
        }
    }
};
</script>

<style lang="scss" scoped>
// Используем те же переменные, что и в вашей админке
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-danger: #ef4444;
$admin-success: #10b981;

.partners-ui-settings {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 900px;
    margin: 0 auto;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: $admin-text;
    i { color: $admin-primary; }
}

.save-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: $admin-primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    &:hover:not(:disabled) { background: #2563eb; }
    &:disabled { opacity: 0.7; cursor: wait; }
}

.settings-card {
    background: $admin-card-bg;
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
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    border: 1px solid rgba($admin-primary, 0.2);
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    &:hover { background: $admin-primary; color: white; }
}

// Формы
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full-width { grid-column: 1 / -1; }

label {
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text-muted;
}

.form-input {
    padding: 10px 12px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.9rem;
    color: $admin-text;
    transition: all 0.2s;
    &:focus { outline: none; border-color: $admin-primary; box-shadow: 0 0 0 3px rgba($admin-primary, 0.1); }
}

// Загрузка картинок Hero
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
.image-label { font-size: 0.8rem; text-align: center; }
.image-preview {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 12px;
    border: 2px dashed $admin-border;
    background-size: cover;
    background-position: center;
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
    i { font-size: 1.5rem; margin-bottom: 4px; display: block; }
    span { font-size: 0.8rem; }
}
.remove-image {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba($admin-danger, 0.9);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.upload-btn {
    width: 100%;
    padding: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $admin-text;
    cursor: pointer;
    &:hover { background: #e2e8f0; }
}

// Динамические списки
.dynamic-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: $admin-bg;
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.2s;
    &:hover { background: #e9ecef; }

    &.simple-item {
        .item-inputs { flex: 1; display: grid; grid-template-columns: 2fr 1fr; gap: 12px; }
    }
}

.item-image-box {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 10px;
    overflow: hidden;
    background: white;
    border: 1px solid $admin-border;
    flex-shrink: 0;
}
.item-preview-img { width: 100%; height: 100%; object-fit: cover; }
.item-preview-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    color: $admin-text-muted; font-size: 1.2rem;
}
.item-image-upload {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0.5);
    color: white;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; cursor: pointer;
    transition: opacity 0.2s;
    &:hover { opacity: 1; }
}

.item-inputs {
    flex: 1;
    display: flex;
    gap: 12px;
    .form-input { flex: 1; }
}

.item-icon-box {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: white;
    border: 1px solid $admin-border;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: $admin-text-muted;
    flex-shrink: 0;
    &.has-icon { color: $admin-primary; background: rgba($admin-primary, 0.05); border-color: rgba($admin-primary, 0.2); }
}

.icon-picker-btn {
    padding: 10px 14px;
    background: white;
    border: 1px solid $admin-border;
    border-radius: 8px;
    color: $admin-text;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    &:hover { border-color: $admin-primary; color: $admin-primary; }
}

.remove-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba($admin-danger, 0.1);
    color: $admin-danger;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    &:hover { background: $admin-danger; color: white; }
}

// Модалка выбора иконок
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.icon-picker-modal {
    background: $admin-card-bg;
    width: 100%; max-width: 500px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid $admin-border;
    display: flex; justify-content: space-between; align-items: center;
    h3 { margin: 0; font-size: 1.1rem; }
}
.modal-close {
    width: 32px; height: 32px; border-radius: 50%;
    background: $admin-bg; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    &:hover { background: $admin-danger; color: white; }
}
.modal-search {
    padding: 16px 20px;
    display: flex; align-items: center; gap: 10px;
    background: $admin-bg;
    i { color: $admin-text-muted; }
    .form-input { border: none; background: transparent; padding: 0; font-size: 1rem; }
    .form-input:focus { box-shadow: none; }
}
.icons-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}
.icon-option {
    aspect-ratio: 1;
    border: 1px solid $admin-border;
    border-radius: 10px;
    background: white;
    font-size: 1.4rem;
    color: $admin-text-muted;
    cursor: pointer;
    transition: all 0.2s;
    &:hover { border-color: $admin-primary; color: $admin-primary; transform: scale(1.1); }
    &.selected { background: $admin-primary; color: white; border-color: $admin-primary; }
}

// Анимации
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

// Адаптив
@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .images-grid { grid-template-columns: repeat(2, 1fr); }
    .dynamic-list-item { flex-wrap: wrap; }
    .item-inputs { width: 100%; margin-top: 8px; }
    .icons-grid { grid-template-columns: repeat(5, 1fr); }
}
</style>
