<template>
    <div class="media-uploader">

        <!-- Переключатель типа -->
        <div class="type-tabs">
            <button
                type="button"
                v-for="type in mediaTypes"
                :key="type.key"
                class="type-tab"
                :class="{ 'active': activeType === type.key }"
                @click="activeType = type.key"
            >
                <i :class="type.icon"></i>
                <span>{{ type.label }}</span>
                <span class="type-count">{{ getCountByType(type.key) }}</span>
            </button>
        </div>

        <!-- Зона загрузки -->
        <div
            class="drop-zone"
            :class="{
                'is-dragging': isDragging,
                'has-error': errorMessage
            }"
            @dragenter.prevent="isDragging = true"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="triggerFileInput"
        >
            <div class="drop-icon">
                <i :class="currentTypeConfig.icon"></i>
            </div>
            <div class="drop-text">
                <strong>Перетащите {{ currentTypeConfig.labelAccusative }} сюда</strong>
                <p>или нажмите для выбора файлов</p>
            </div>
            <div class="drop-hint">
                <span>
                    <i class="fa-solid fa-file"></i>
                    {{ currentTypeConfig.formats }}
                </span>
                <span>
                    <i class="fa-solid fa-weight-hanging"></i>
                    до {{ currentTypeConfig.maxSize }}MB
                </span>
            </div>
            <input
                ref="fileInput"
                type="file"
                :accept="currentTypeConfig.accept"
                :multiple="currentTypeConfig.multiple"
                class="file-input-hidden"
                @change="handleFileSelect"
            >
        </div>

        <!-- Ошибка -->
        <transition name="fade">
            <div v-if="errorMessage" class="error-message">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ errorMessage }}</span>
                <button
                    type="button"
                    @click="errorMessage = null">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </transition>

        <!-- Список загруженных файлов -->
        <div v-if="groupedFiles[activeType]?.length" class="files-list">
            <transition-group name="list" tag="div" class="files-grid">
                <div
                    v-for="(file, index) in groupedFiles[activeType]"
                    :key="file.id"
                    class="file-item"
                    :class="{ 'is-uploading': file.uploading }"
                >
                    <!-- Превью -->
                    <div class="file-preview">
                        <img
                            v-if="file.type === 'image' && file.preview"
                            :src="file.preview"
                            :alt="file.name"
                        >
                        <div v-else-if="file.type === 'video'" class="preview-video">
                            <i class="fa-solid fa-film"></i>
                        </div>
                        <div v-else class="preview-audio">
                            <i class="fa-solid fa-music"></i>
                        </div>

                        <!-- Прогресс загрузки -->
                        <div v-if="file.uploading" class="upload-overlay">
                            <div class="upload-spinner"></div>
                            <span>{{ file.progress || 0 }}%</span>
                        </div>
                    </div>

                    <!-- Информация -->
                    <div class="file-info">
                        <div class="file-name">{{ file.name }}</div>
                        <div class="file-meta">
                            <span>{{ formatSize(file.size) }}</span>
                        </div>
                    </div>

                    <!-- Кнопка удаления -->
                    <button
                        type="button"
                        class="file-remove"
                        @click="removeFile(file, index)"
                        :disabled="file.uploading"
                        title="Удалить"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </transition-group>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="empty-files">
            <i class="fa-solid fa-inbox"></i>
            <p>Файлы ещё не загружены</p>
        </div>

    </div>
</template>

<script>
export default {
    name: 'MediaUploader',

    props: {
        modelValue: {
            type: Array,
            default: () => [],
        },
        broadcastId: {
            type: [Number, String],
            default: null,
        },
        maxFiles: {
            type: Number,
            default: 10,
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            activeType: 'image',
            isDragging: false,
            errorMessage: null,
            localFiles: [...(this.modelValue || [])],
            nextId: 1,

            mediaTypes: [
                {
                    key: 'image',
                    label: 'Изображения',
                    labelAccusative: 'изображения',
                    icon: 'fa-solid fa-image',
                    formats: 'JPG, PNG, GIF, WEBP',
                    accept: 'image/jpeg,image/png,image/gif,image/webp',
                    multiple: true,
                    maxSize: 10,
                    maxSizeBytes: 10 * 1024 * 1024,
                },
                {
                    key: 'video',
                    label: 'Видео',
                    labelAccusative: 'видео',
                    icon: 'fa-solid fa-video',
                    formats: 'MP4, MOV, WEBM',
                    accept: 'video/mp4,video/mov,video/webm',
                    multiple: false,
                    maxSize: 50,
                    maxSizeBytes: 50 * 1024 * 1024,
                },
                {
                    key: 'audio',
                    label: 'Аудио',
                    labelAccusative: 'аудио',
                    icon: 'fa-solid fa-music',
                    formats: 'MP3, WAV, OGG',
                    accept: 'audio/mpeg,audio/wav,audio/ogg',
                    multiple: true,
                    maxSize: 20,
                    maxSizeBytes: 20 * 1024 * 1024,
                },
            ],
        };
    },

    computed: {
        currentTypeConfig() {
            return this.mediaTypes.find(t => t.key === this.activeType) || this.mediaTypes[0];
        },

        groupedFiles() {
            return {
                image: this.localFiles.filter(f => f.type === 'image'),
                video: this.localFiles.filter(f => f.type === 'video'),
                audio: this.localFiles.filter(f => f.type === 'audio'),
            };
        },
    },

    methods: {
        getCountByType(type) {
            return this.localFiles.filter(f => f.type === type).length;
        },

        triggerFileInput() {
            this.$refs.fileInput.click();
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files || []);
            this.processFiles(files);
            event.target.value = ''; // Сброс для повторной загрузки
        },

        handleDrop(event) {
            this.isDragging = false;
            const files = Array.from(event.dataTransfer.files || []);
            this.processFiles(files);
        },

        async processFiles(files) {
            this.errorMessage = null;

            for (const file of files) {
                // Валидация типа
                if (!this.currentTypeConfig.accept.includes(file.type)) {
                    this.errorMessage = `Файл "${file.name}" имеет неподдерживаемый формат`;
                    return;
                }

                // Валидация размера
                if (file.size > this.currentTypeConfig.maxSizeBytes) {
                    this.errorMessage = `Файл "${file.name}" слишком большой (макс. ${this.currentTypeConfig.maxSize}MB)`;
                    return;
                }

                // Лимит файлов
                const currentCount = this.getCountByType(this.activeType);
                if (!this.currentTypeConfig.multiple && currentCount > 0) {
                    this.errorMessage = `Можно загрузить только одно ${this.currentTypeConfig.labelAccusative.toLowerCase()}`;
                    return;
                }

                if (this.localFiles.length >= this.maxFiles) {
                    this.errorMessage = `Достигнут лимит в ${this.maxFiles} файлов`;
                    return;
                }

                await this.addFile(file);
            }
        },

        async addFile(file) {
            const fileId = this.nextId++;
            const fileObj = {
                id: fileId,
                type: this.activeType,
                name: file.name,
                size: file.size,
                file: file,
                preview: null,
                uploading: false,
                progress: 0,
            };

            // Создание превью для изображений
            if (this.activeType === 'image') {
                fileObj.preview = await this.createPreview(file);
            }

            this.localFiles.push(fileObj);
            this.emitUpdate();

            // Имитация загрузки (если нужен реальный upload - раскомментировать)
            // await this.uploadFile(fileObj);
        },

        createPreview(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.readAsDataURL(file);
            });
        },

        async uploadFile(fileObj) {
            fileObj.uploading = true;
            fileObj.progress = 0;

            // Здесь можно реализовать реальную загрузку на сервер
            // const interval = setInterval(() => {
            //     fileObj.progress += 10;
            //     if (fileObj.progress >= 100) {
            //         clearInterval(interval);
            //         fileObj.uploading = false;
            //     }
            // }, 200);

            fileObj.uploading = false;
            fileObj.progress = 100;
        },

        removeFile(file, index) {
            // Ревокация URL для освобождения памяти
            if (file.preview && file.preview.startsWith('blob:')) {
                URL.revokeObjectURL(file.preview);
            }

            const globalIndex = this.localFiles.findIndex(f => f.id === file.id);
            if (globalIndex !== -1) {
                this.localFiles.splice(globalIndex, 1);
                this.emitUpdate();
            }
        },

        emitUpdate() {
            // Возвращаем только объекты File для FormData
            const filesOnly = this.localFiles.map(f => f.file);
            this.$emit('update:modelValue', filesOnly);
        },

        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$purple: #8b5cf6;
$cyan: #06b6d4;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.media-uploader {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

// ==========================================
// ТАБЫ ТИПОВ
// ==========================================
.type-tabs {
    display: flex;
    gap: 6px;
    background: $bg-secondary;
    padding: 4px;
    border-radius: 12px;
}

.type-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: $text-muted;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(.active) {
        color: $text;
        background: rgba($primary, 0.05);
    }

    &.active {
        background: $bg;
        color: $primary;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    i {
        font-size: 0.9rem;
    }
}

.type-count {
    padding: 1px 7px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 18px;
    text-align: center;
}

.type-tab.active .type-count {
    background: $primary;
    color: white;
}

// ==========================================
// ЗОНА ЗАГРУЗКИ
// ==========================================
.drop-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 32px 20px;
    background: $bg;
    border: 2px dashed $border;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;

    &:hover {
        border-color: $primary;
        background: rgba($primary, 0.02);
    }

    &.is-dragging {
        border-color: $primary;
        background: rgba($primary, 0.08);
        transform: scale(1.01);
    }

    &.has-error {
        border-color: $danger;
        background: rgba($danger, 0.03);
    }
}

.drop-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin-bottom: 8px;
}

.drop-text {
    strong {
        display: block;
        font-size: 1rem;
        color: $text;
        margin-bottom: 4px;
    }

    p {
        margin: 0;
        font-size: 0.85rem;
        color: $text-muted;
    }
}

.drop-hint {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin-top: 8px;

    span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        color: $text-muted;
        padding: 4px 10px;
        background: $bg-secondary;
        border-radius: 10px;

        i {
            font-size: 0.7rem;
            color: $primary;
        }
    }
}

.file-input-hidden {
    display: none;
}

// ==========================================
// ОШИБКА
// ==========================================
.error-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: rgba($danger, 0.08);
    border: 1px solid rgba($danger, 0.2);
    border-radius: 10px;
    color: $danger;
    font-size: 0.85rem;

    i:first-child {
        font-size: 1rem;
        flex-shrink: 0;
    }

    span {
        flex: 1;
    }

    button {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: transparent;
        border: none;
        color: $danger;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;

        &:hover {
            background: rgba($danger, 0.15);
        }
    }
}

// ==========================================
// СПИСОК ФАЙЛОВ
// ==========================================
.files-list {
    margin-top: 4px;
}

.files-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.file-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    transition: all 0.2s;

    &:hover {
        border-color: rgba($primary, 0.3);
    }

    &.is-uploading {
        opacity: 0.7;
    }
}

.file-preview {
    position: relative;
    width: 56px;
    height: 56px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    background: $bg-secondary;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-video,
    .preview-audio {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: white;
    }

    .preview-video {
        background: linear-gradient(135deg, $purple, #7c3aed);
    }

    .preview-audio {
        background: linear-gradient(135deg, $cyan, #0891b2);
    }
}

.upload-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(2px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    color: white;

    span {
        font-size: 0.75rem;
        font-weight: 700;
    }
}

.upload-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.file-info {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.file-meta {
    display: flex;
    gap: 10px;
    font-size: 0.75rem;
    color: $text-muted;
}

.file-remove {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: transparent;
    border: 1px solid $border;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        background: $danger;
        border-color: $danger;
        color: white;
        transform: scale(1.1);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-files {
    text-align: center;
    padding: 24px;
    color: $text-muted;

    i {
        font-size: 2rem;
        opacity: 0.3;
        margin-bottom: 8px;
    }

    p {
        margin: 0;
        font-size: 0.85rem;
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateX(-20px);
}

.list-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (max-width: 480px) {
    .drop-zone {
        padding: 24px 16px;
    }

    .drop-icon {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }

    .drop-text strong {
        font-size: 0.9rem;
    }

    .type-tab span:not(.type-count) {
        display: none;
    }

    .file-preview {
        width: 48px;
        height: 48px;
    }
}
</style>
