<template>
    <div class="stories-page">

        <!-- ЗАГОЛОВОК -->
        <div class="page-header">
            <div class="header-icon">
                <i class="fa-solid fa-circle-play"></i>
            </div>
            <h2 class="header-title">Истории</h2>
        </div>

        <!-- КНОПКА СОЗДАНИЯ -->
        <div class="create-section">
            <button class="btn-create" @click="openCreateModal">
                <i class="fa-solid fa-plus"></i>
                <span>Создать новую историю</span>
            </button>
        </div>

        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Загрузка историй...</p>
        </div>

        <!-- СПИСОК ИСТОРИЙ -->
        <div v-else class="stories-container">

            <div v-if="stories.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-circle-play"></i>
                </div>
                <h3>Историй пока нет</h3>
                <p>Создайте первую историю для ваших клиентов</p>
                <button class="btn-primary-modern w-100" @click="openCreateModal">
                    <i class="fa-solid fa-plus"></i> Создать историю
                </button>
            </div>

            <div v-else class="stories-grid">
                <div
                    v-for="(story, index) in stories"
                    :key="'story-' + story.id"
                    class="story-card"
                >
                    <!-- Превью -->
                    <div
                        class="story-preview"
                        :class="{ 'is-viewed': isViewed(story.id) }"
                        @click="openStory(index)"
                    >
                        <img :src="story.thumbnail" :alt="story.title">
                        <div class="preview-overlay">
                            <i class="fa-solid fa-play"></i>
                        </div>
                        <div v-if="!isViewed(story.id)" class="unviewed-indicator"></div>
                    </div>

                    <!-- Информация -->
                    <div class="story-info">
                        <h4 class="story-title">{{ story.title }}</h4>
                        <p class="story-description">{{ story.description }}</p>
                    </div>

                    <!-- Действия -->
                    <div class="story-actions">
                        <button class="action-btn" @click="openStory(index)">
                            <i class="fa-solid fa-eye"></i>
                            <span>Просмотр</span>
                        </button>
                        <button class="action-btn danger" @click="openRemoveModal(story)">
                            <i class="fa-solid fa-trash"></i>
                            <span>Удалить</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ПАГИНАЦИЯ -->
        <div v-if="stories_paginate_object && stories_paginate_object.last_page > 1" class="pagination-wrapper">
            <Pagination
                :simple="true"
                @pagination_page="loadStoriesList"
                :pagination="stories_paginate_object"
            />
        </div>

        <!-- МОДАЛКА: СОЗДАНИЕ/РЕДАКТИРОВАНИЕ -->
        <div v-if="showFormModal" class="modal-overlay mobile-fullscreen" @click.self="cancelForm">
            <div class="modal-container form-modal">
                <div class="modal-header">
                    <button class="modal-back" @click="cancelForm">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <h3>{{ isEditing ? 'Редактировать историю' : 'Новая история' }}</h3>
                </div>

                <div class="modal-body">
                    <form @submit.prevent="handleSaveStory" class="story-form">

                        <!-- Заголовок -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fa-solid fa-heading"></i>
                                Основная информация
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="story-title">
                                    Заголовок <span class="required">*</span>
                                </label>
                                <input
                                    id="story-title"
                                    type="text"
                                    v-model="formStory.title"
                                    class="form-input"
                                    placeholder="Введите заголовок"
                                    required
                                    :disabled="isStoring"
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="story-description">
                                    Описание <span class="required">*</span>
                                </label>
                                <textarea
                                    id="story-description"
                                    v-model="formStory.description"
                                    class="form-textarea"
                                    placeholder="Введите описание"
                                    rows="3"
                                    required
                                    :disabled="isStoring"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Миниатюра -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fa-solid fa-image"></i>
                                Миниатюра
                            </div>

                            <div
                                class="file-uploader"
                                :class="{ 'has-image': formStory.thumbnailPreview }"
                            >
                                <template v-if="formStory.thumbnailPreview">
                                    <div class="preview-container">
                                        <img :src="formStory.thumbnailPreview" class="preview-image" alt="Миниатюра">
                                        <button
                                            type="button"
                                            class="remove-btn"
                                            @click="removeFile('thumbnail')"
                                            :disabled="isStoring"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="upload-prompt">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                        <span class="upload-title">Загрузить миниатюру</span>
                                        <span class="upload-hint">Максимум 5 МБ</span>
                                    </div>
                                </template>
                                <input
                                    ref="thumbnailInput"
                                    type="file"
                                    class="file-input"
                                    accept="image/*"
                                    @change="handleFileUpload($event, 'thumbnail')"
                                    :disabled="isStoring"
                                    required
                                >
                            </div>
                        </div>

                        <!-- Изображение истории -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fa-solid fa-photo-film"></i>
                                Изображение истории
                            </div>

                            <div
                                class="file-uploader"
                                :class="{ 'has-image': formStory.imagePreview }"
                            >
                                <template v-if="formStory.imagePreview">
                                    <div class="preview-container">
                                        <img :src="formStory.imagePreview" class="preview-image" alt="Изображение">
                                        <button
                                            type="button"
                                            class="remove-btn"
                                            @click="removeFile('image')"
                                            :disabled="isStoring"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="upload-prompt">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                        <span class="upload-title">Загрузить изображение</span>
                                        <span class="upload-hint">Максимум 5 МБ</span>
                                    </div>
                                </template>
                                <input
                                    ref="imageInput"
                                    type="file"
                                    class="file-input"
                                    accept="image/*"
                                    @change="handleFileUpload($event, 'image')"
                                    :disabled="isStoring"
                                    required
                                >
                            </div>
                        </div>

                        <!-- Ссылка -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fa-solid fa-link"></i>
                                Ссылка (необязательно)
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="story-link">URL ссылки</label>
                                <input
                                    id="story-link"
                                    type="url"
                                    v-model="formStory.link"
                                    class="form-input"
                                    placeholder="https://..."
                                    :disabled="isStoring"
                                >
                            </div>

                            <div v-if="formStory.link" class="form-group">
                                <label class="form-label" for="story-link-type">Тип ссылки</label>
                                <select
                                    id="story-link-type"
                                    v-model="formStory.link_type"
                                    class="form-select"
                                    :disabled="isStoring"
                                >
                                    <option v-for="item in link_types" :key="item.key" :value="item.key">
                                        {{ item.title }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Настройки -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fa-solid fa-gear"></i>
                                Настройки
                            </div>

                            <div class="setting-row">
                                <div class="setting-info">
                                    <div class="setting-icon">
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </div>
                                    <div class="setting-text">
                                        <h4 class="setting-title">Авто-рассылка</h4>
                                        <p class="setting-description">Автоматически уведомлять о новой истории</p>
                                    </div>
                                </div>
                                <div class="switch-control">
                                    <input
                                        id="auto-send"
                                        type="checkbox"
                                        v-model="formStory.need_auto_send_stories"
                                        class="switch-input"
                                        :disabled="isStoring"
                                    >
                                    <span class="switch-slider"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Действия -->
                        <div class="form-actions">
                            <button
                                type="button"
                                class="btn-secondary-modern"
                                @click="cancelForm"
                                :disabled="isStoring"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                class="btn-primary-modern"
                                :disabled="isStoring"
                            >
                                <span v-if="isStoring" class="spinner-small"></span>
                                <template v-else>
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Сохранить
                                </template>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- МОДАЛКА: ПРОСМОТР ИСТОРИИ (FULLSCREEN) -->
        <div
            v-if="currentStory !== null"
            class="modal-overlay story-viewer"
            @click.self="closeStory"
        >
            <div class="story-viewer-container">
                <!-- Прогресс-бар -->
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" :style="{ width: `${progress}%` }"></div>
                </div>

                <!-- Кнопка закрытия -->
                <button class="viewer-close" @click="closeStory">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <!-- Изображение -->
                <img
                    :src="stories[currentStory].image"
                    class="viewer-image"
                    :alt="stories[currentStory].title"
                >

                <!-- Нижняя панель -->
                <div class="viewer-footer">
                    <h5 class="viewer-title">{{ stories[currentStory].title }}</h5>
                    <p class="viewer-description">{{ stories[currentStory].description }}</p>

                    <template v-if="stories[currentStory].link">
                        <a
                            v-if="stories[currentStory].link_type === 'url' || stories[currentStory].link_type === 'bot'"
                            :href="stories[currentStory].link"
                            target="_blank"
                            class="btn-link-action"
                        >
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            Перейти по ссылке
                        </a>

                        <button
                            v-if="stories[currentStory].link_type === 'product'"
                            class="btn-link-action"
                            @click="goToProductLink(stories[currentStory].link)"
                        >
                            <i class="fa-solid fa-bag-shopping"></i>
                            Открыть товар
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- МОДАЛКА: ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ -->
        <div v-if="showRemoveModal" class="modal-overlay bottom-sheet" @click.self="closeRemoveModal">
            <div class="modal-container confirm-modal">
                <div class="confirm-icon danger">
                    <i class="fa-solid fa-trash"></i>
                </div>
                <h4>Удалить историю?</h4>
                <p>
                    История <strong>«{{ selectedStory?.title }}»</strong> будет удалена.
                    Это действие нельзя отменить.
                </p>
                <div class="confirm-actions">
                    <button class="btn-secondary-modern" @click="closeRemoveModal">Отмена</button>
                    <button class="btn-primary-modern danger" @click="handleDeleteStory" :disabled="isDeleting">
                        <span v-if="isDeleting" class="spinner-small"></span>
                        <span v-else>Удалить</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Pagination from '@/MobileClient/Components/Pagination.vue';
import { useStories } from '@/MobileClient/Composables/useStories.js';

export default {
    name: 'StoriesAdmin',

    components: {
        Pagination,
    },

    setup() {
        // 🎯 Подключаем composable — всё состояние и методы берём отсюда
        const storiesComposable = useStories();

        return {
            // Реактивные данные из стора
            stories: storiesComposable.stories,
            stories_paginate_object: storiesComposable.stories_paginate_object,
            isLoading: storiesComposable.isLoading,
            isStoring: storiesComposable.isStoring,
            lastError: storiesComposable.lastError,

            // Методы из стора
            loadStories: storiesComposable.loadStories,
            saveStory: storiesComposable.saveStory,
            deleteStory: storiesComposable.deleteStory,
            isViewed: storiesComposable.isViewed,
            markAsViewed: storiesComposable.markAsViewed,
        };
    },

    data() {
        return {
            // UI-состояние (локальное, не в сторе)
            currentStory: null,
            progress: 0,
            timer: null,
            isDeleting: false,
            isEditing: false,
            showFormModal: false,
            showRemoveModal: false,
            selectedStory: null,

            // Форма создания/редактирования
            formStory: this.getEmptyForm(),

            link_types: [
                { key: 'product', title: 'Открывает товар в магазине' },
                { key: 'bot', title: 'Открывает раздел бота' },
                { key: 'url', title: 'Переход на внешнюю страницу' },
            ],
        };
    },

    mounted() {
        this.loadStoriesList();
    },

    beforeUnmount() {
        clearInterval(this.timer);
    },

    methods: {
        getEmptyForm() {
            return {
                title: '',
                need_auto_send_stories: true,
                thumbnail: null,
                thumbnailPreview: null,
                image: null,
                imagePreview: null,
                description: '',
                link: '',
                link_type: 'product',
            };
        },

        // ==========================================
        // ЗАГРУЗКА ИСТОРИЙ
        // ==========================================
        async loadStoriesList(page = 1) {
            try {
                // 🎯 Используем метод из composable — он сам обновляет stories и paginate
                await this.loadStories({ page, size: 20 });
            } catch (err) {
                console.error('Ошибка загрузки историй:', err);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить истории',
                    type: 'error',
                });
            }
        },

        // ==========================================
        // ПРОСМОТР ИСТОРИЙ
        // ==========================================
        openStory(index) {
            this.currentStory = index;
            this.progress = 0;
            this.startTimer();
            // 🎯 Используем метод из composable
            this.markAsViewed(this.stories[index].id);
        },

        closeStory() {
            this.currentStory = null;
            this.progress = 0;
            clearInterval(this.timer);
        },

        nextStory() {
            if (this.currentStory < this.stories.length - 1) {
                this.currentStory++;
                this.progress = 0;
                this.markAsViewed(this.stories[this.currentStory].id);
                this.startTimer();
            } else {
                this.closeStory();
            }
        },

        startTimer() {
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                this.progress += 2;
                if (this.progress >= 100) {
                    this.nextStory();
                }
            }, 100);
        },

        goToProductLink(url) {
            try {
                const urlObj = new URL(url);
                const startParam = urlObj.searchParams.get('start');
                const decoded = atob(startParam);
                const slugMatch = decoded.match(/^001slug(\d+)product(\d+)$/);

                if (!slugMatch) {
                    this.$router.push({ name: 'CatalogV2' });
                    return;
                }

                const productId = slugMatch[2];
                this.$router.push({ name: 'ProductV2', params: { productId } });
            } catch (e) {
                this.$router.push({ name: 'CatalogV2' });
            }
        },

        // ==========================================
        // ФОРМА
        // ==========================================
        openCreateModal() {
            this.formStory = this.getEmptyForm();
            this.isEditing = false;
            this.showFormModal = true;
        },

        cancelForm() {
            if (this.formStory.thumbnailPreview) {
                URL.revokeObjectURL(this.formStory.thumbnailPreview);
            }
            if (this.formStory.imagePreview) {
                URL.revokeObjectURL(this.formStory.imagePreview);
            }
            this.formStory = this.getEmptyForm();
            this.showFormModal = false;
        },

        handleFileUpload(event, param) {
            const file = event.target.files?.[0];
            if (!file) return;

            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Размер файла не должен превышать 5 МБ',
                    type: 'error',
                });
                event.target.value = null;
                return;
            }

            const oldPreviewKey = param + 'Preview';
            if (this.formStory[oldPreviewKey]) {
                URL.revokeObjectURL(this.formStory[oldPreviewKey]);
            }

            this.formStory[param] = file;
            this.formStory[oldPreviewKey] = URL.createObjectURL(file);
        },

        removeFile(param) {
            const previewKey = param + 'Preview';

            if (this.formStory[previewKey]) {
                URL.revokeObjectURL(this.formStory[previewKey]);
            }

            this.formStory[param] = null;
            this.formStory[previewKey] = null;

            const inputRef = param === 'thumbnail' ? 'thumbnailInput' : 'imageInput';
            if (this.$refs[inputRef]) {
                this.$refs[inputRef].value = '';
            }
        },

        async handleSaveStory() {
            try {
                const data = new FormData();

                Object.keys(this.formStory).forEach(key => {
                    if (key.endsWith('Preview')) return;

                    const item = this.formStory[key];
                    if (item !== null && item !== undefined) {
                        if (typeof item === 'object' && !(item instanceof File)) {
                            data.append(key, JSON.stringify(item));
                        } else {
                            data.append(key, item);
                        }
                    }
                });

                if (this.formStory.thumbnail) {
                    data.append('thumbnail[]', this.formStory.thumbnail);
                }
                if (this.formStory.image) {
                    data.append('image[]', this.formStory.image);
                }

                // 🎯 Используем метод из composable
                await this.saveStory(data);

                this.$notify?.({
                    title: 'Успех',
                    text: 'История успешно сохранена',
                    type: 'success',
                });

                this.cancelForm();
                this.loadStoriesList();
            } catch (err) {
                console.error('Ошибка сохранения истории:', err);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сохранить историю',
                    type: 'error',
                });
            }
        },

        // ==========================================
        // УДАЛЕНИЕ
        // ==========================================
        openRemoveModal(story) {
            this.selectedStory = story;
            this.showRemoveModal = true;
        },

        closeRemoveModal() {
            this.showRemoveModal = false;
            this.selectedStory = null;
        },

        async handleDeleteStory() {
            if (!this.selectedStory) return;

            this.isDeleting = true;

            try {
                // 🎯 Используем метод из composable
                await this.deleteStory(this.selectedStory.id);

                this.$notify?.({
                    title: 'Успех',
                    text: 'История удалена',
                    type: 'success',
                });

                this.closeRemoveModal();
                this.loadStoriesList();
            } catch (err) {
                console.error('Ошибка удаления истории:', err);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось удалить историю',
                    type: 'error',
                });
            } finally {
                this.isDeleting = false;
            }
        },
    },
};
</script>


<style lang="scss" scoped>
@use "sass:color";
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-danger: #ef4444;
$admin-warning: #f59e0b;

.stories-page {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ЗАГОЛОВОК
// ==========================================
.page-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 16px;
    background: $admin-card-bg;
    border-bottom: 1px solid $admin-border;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba($admin-primary, 0.1) 0%, rgba($admin-primary, 0.05) 100%);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.header-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0;
}

// ==========================================
// КНОПКА СОЗДАНИЯ
// ==========================================
.create-section {
    padding: 16px;
}

.btn-create {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 20px;
    background: $admin-success;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($admin-success, 0.3);
    min-height: 48px;

    &:active {
        transform: scale(0.98);
        background: color.adjust($admin-success, $lightness: -5%);
    }
}

// ==========================================
// ИНДИКАТОР ЗАГРУЗКИ
// ==========================================
.loading-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: $admin-text-muted;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid $admin-border;
    border-top-color: $admin-primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 12px;
}

.spinner-small {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    vertical-align: middle;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// КОНТЕЙНЕР ИСТОРИЙ
// ==========================================
.stories-container {
    padding: 0 16px 16px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $admin-text-muted;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: $admin-bg;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 16px;
    }

    h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: $admin-text;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
}

// ==========================================
// СЕТКА ИСТОРИЙ
// ==========================================
.stories-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;

    @media (min-width: 768px) {
        grid-template-columns: repeat(3, 1fr);
    }
}

.story-card {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.story-preview {
    position: relative;
    aspect-ratio: 9 / 16;
    background: $admin-bg;
    cursor: pointer;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: all 0.3s;
    }

    &.is-viewed img {
        filter: grayscale(60%);
        opacity: 0.7;
    }

    &:active img {
        transform: scale(0.95);
    }
}

.preview-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.5) 0%, transparent 50%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    opacity: 0;
    transition: opacity 0.2s;

    .story-preview:hover & {
        opacity: 1;
    }
}

.unviewed-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: $admin-primary;
    box-shadow: 0 0 0 2px white;
}

.story-info {
    padding: 10px 12px;
    flex: 1;
}

.story-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0 0 4px 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.story-description {
    font-size: 0.75rem;
    color: $admin-text-muted;
    margin: 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.story-actions {
    display: flex;
    gap: 6px;
    padding: 0 10px 10px;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 8px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 6px;
    color: $admin-text-muted;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 36px;

    &:active {
        transform: scale(0.95);
    }

    &.danger:active {
        background: $admin-danger;
        border-color: $admin-danger;
        color: white;
    }

    span {
        display: none;

        @media (min-width: 480px) {
            display: inline;
        }
    }
}

// ==========================================
// ПАГИНАЦИЯ
// ==========================================
.pagination-wrapper {
    padding: 20px 16px;
    display: flex;
    justify-content: center;
}

// ==========================================
// МОДАЛКИ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    animation: fadeIn 0.2s ease;

    &.mobile-fullscreen {
        align-items: stretch;
    }

    &.bottom-sheet {
        align-items: flex-end;
    }

    &.story-viewer {
        background: rgba(0, 0, 0, 0.95);
        backdrop-filter: none;
        align-items: stretch;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: $admin-card-bg;
    width: 100%;
    max-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;

    .mobile-fullscreen & {
        border-radius: 0;
        max-height: 100vh;
    }

    .bottom-sheet & {
        border-radius: 16px 16px 0 0;
        max-height: 80vh;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(100%);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-modal {
    max-width: 100%;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid $admin-border;
    position: sticky;
    top: 0;
    background: $admin-card-bg;
    z-index: 10;

    h3 {
        flex: 1;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

.modal-back {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: $admin-bg;
    border: none;
    color: $admin-text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:active {
        background: $admin-primary;
        color: white;
    }
}

.modal-body {
    padding: 16px;
    overflow-y: auto;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

// ==========================================
// ФОРМА
// ==========================================
.story-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-section {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text;
    padding-bottom: 8px;
    border-bottom: 1px solid $admin-border;

    i {
        color: $admin-primary;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text;

    .required {
        color: $admin-danger;
        font-weight: 700;
    }
}

.form-input,
.form-textarea,
.form-select {
    padding: 12px 16px;
    border: 1px solid $admin-border;
    border-radius: 8px;
    font-size: 0.95rem;
    color: $admin-text;
    background: $admin-card-bg;
    transition: all 0.2s;
    min-height: 48px;
    font-family: inherit;
    width: 100%;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }

    &:disabled {
        background: $admin-bg;
        cursor: not-allowed;
        opacity: 0.6;
    }
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
    line-height: 1.5;
}

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 36px;
}

// ==========================================
// ЗАГРУЗЧИК ФАЙЛОВ
// ==========================================
.file-uploader {
    position: relative;
    border: 2px dashed $admin-border;
    border-radius: 12px;
    background: $admin-bg;
    transition: all 0.2s;
    overflow: hidden;

    &.has-image {
        border-style: solid;
        border-color: $admin-border;
    }
}

.file-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;

    &:disabled {
        cursor: not-allowed;
    }
}

.upload-prompt {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 32px 16px;
    color: $admin-text-muted;

    i {
        font-size: 2rem;
        color: $admin-primary;
    }

    .upload-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: $admin-text;
    }

    .upload-hint {
        font-size: 0.8rem;
    }
}

.preview-container {
    position: relative;
    width: 100%;
    aspect-ratio: 9 / 16;
    background: $admin-bg;
    overflow: hidden;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.remove-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba($admin-danger, 0.9);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);

    &:active {
        transform: scale(0.9);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

// ==========================================
// НАСТРОЙКИ (SWITCH)
// ==========================================
.setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.setting-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.setting-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.setting-text {
    flex: 1;
    min-width: 0;
}

.setting-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text;
    margin: 0 0 2px 0;
}

.setting-description {
    font-size: 0.75rem;
    color: $admin-text-muted;
    margin: 0;
}

.switch-control {
    position: relative;
    width: 48px;
    height: 28px;
    flex-shrink: 0;
}

.switch-input {
    opacity: 0;
    width: 0;
    height: 0;

    &:checked + .switch-slider {
        background: $admin-primary;

        &::before {
            transform: translateX(20px);
        }
    }

    &:disabled + .switch-slider {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: $admin-border;
    transition: 0.3s;
    border-radius: 28px;

    &::before {
        position: absolute;
        content: '';
        height: 22px;
        width: 22px;
        left: 3px;
        bottom: 3px;
        background: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
}

// ==========================================
// ДЕЙСТВИЯ ФОРМЫ
// ==========================================
.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 8px;
}

.btn-primary-modern,
.btn-secondary-modern {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 48px;

    &:active:not(:disabled) {
        transform: scale(0.98);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-primary-modern {
    background: $admin-primary;
    color: white;

    &.danger {
        background: $admin-danger;
    }
}

.btn-secondary-modern {
    background: $admin-bg;
    color: $admin-text;
    border: 1px solid $admin-border;
}

// ==========================================
// ПРОСМОТР ИСТОРИИ (VIEWER)
// ==========================================
.story-viewer-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.progress-bar-container {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    z-index: 10;
}

.progress-bar-fill {
    height: 100%;
    background: white;
    transition: width 0.1s linear;
}

.viewer-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    z-index: 10;
    transition: all 0.2s;

    &:active {
        transform: scale(0.9);
        background: rgba(0, 0, 0, 0.7);
    }
}

.viewer-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.viewer-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px 16px calc(20px + env(safe-area-inset-bottom));
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
    color: white;
}

.viewer-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.viewer-description {
    font-size: 0.9rem;
    margin: 0 0 16px 0;
    opacity: 0.9;
    line-height: 1.4;
}

.btn-link-action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    background: white;
    color: $admin-text;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    width: 100%;

    &:active {
        transform: scale(0.98);
        background: rgba(255, 255, 255, 0.9);
    }
}

// ==========================================
// ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ
// ==========================================
.confirm-modal {
    padding: 24px 20px;
    text-align: center;
}

.confirm-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;

    &.danger {
        background: rgba($admin-danger, 0.1);
        color: $admin-danger;
    }
}

.confirm-modal {
    h4 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        color: $admin-text-muted;
        margin-bottom: 24px;
        line-height: 1.4;
    }
}

.confirm-actions {
    display: flex;
    gap: 10px;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .page-header {
        padding: 24px;
    }

    .header-title {
        font-size: 1.25rem;
    }

    .create-section,
    .stories-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .modal-overlay {
        padding: 20px;

        &.mobile-fullscreen {
            align-items: center;
        }

        &.bottom-sheet {
            align-items: center;
        }

        &.story-viewer {
            padding: 0;
            align-items: stretch;
        }
    }

    .modal-container {
        max-width: 700px;
        border-radius: 16px;
        max-height: 90vh;

        .confirm-modal & {
            max-width: 400px;
        }
    }
}
</style>
