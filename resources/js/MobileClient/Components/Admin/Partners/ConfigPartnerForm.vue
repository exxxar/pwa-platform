<template>
    <form class="partner-config-form" @submit.prevent="handleSubmit">

        <!-- Основная информация -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-id-card"></i>
                Основная информация
            </div>

            <!-- 🆕 Ссылка на приложение -->
            <div class="form-group">
                <label class="form-label" for="partner-url">
                    <i class="fa-solid fa-link"></i>
                    Ссылка на приложение
                </label>
                <input
                    id="partner-url"
                    type="text"
                    v-model="urlInput"
                    class="form-input"
                    :class="{ 'has-error': errors.url }"
                    placeholder="https://slug.mypwa.ru"
                    :disabled="isLoading"
                >
                <span v-if="errors.url" class="form-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ errors.url }}
                </span>
                <span v-else class="form-hint">
                    Оставьте пустым, если не хотите изменять текущую ссылку. Формат: https://slug.mypwa.ru
                </span>
            </div>

            <!-- Статус -->
            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-icon status">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Активен</h4>
                        <p class="setting-description">Партнёр отображается в системе</p>
                    </div>
                </div>
                <div class="switch-control">
                    <input
                        id="partner-is-active"
                        type="checkbox"
                        v-model="form.is_active"
                        class="switch-input"
                        :disabled="isLoading"
                    >
                    <span class="switch-slider"></span>
                </div>
            </div>

            <!-- Заголовок -->
            <div class="form-group">
                <label class="form-label" for="partner-title">
                    <i class="fa-solid fa-heading"></i>
                    Заголовок
                    <span class="required">*</span>
                </label>
                <input
                    id="partner-title"
                    type="text"
                    v-model="form.title"
                    class="form-input"
                    :class="{ 'has-error': errors.title }"
                    placeholder="Название партнёра"
                    :disabled="isLoading"
                    required
                >
                <span v-if="errors.title" class="form-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ errors.title }}
                </span>
            </div>

            <!-- Позиция -->
            <div class="form-group">
                <label class="form-label" for="partner-position">
                    <i class="fa-solid fa-arrow-down-1-9"></i>
                    Позиция в выдаче
                </label>
                <input
                    id="partner-position"
                    type="number"
                    v-model.number="form.order_position"
                    class="form-input"
                    placeholder="0"
                    min="0"
                    :disabled="isLoading"
                >
                <span class="form-hint">Чем меньше число, тем выше в списке</span>
            </div>
        </div>

        <!-- Описание -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-align-left"></i>
                Описание
            </div>
            <div class="form-group">
                <label class="form-label" for="partner-description">
                    <i class="fa-solid fa-file-lines"></i>
                    Текст описания
                    <span v-if="form.description" class="char-count">
                        {{ (form.description || '').length }} символов
                    </span>
                </label>
                <textarea
                    id="partner-description"
                    v-model="form.description"
                    class="form-textarea"
                    placeholder="Расскажите о партнёре"
                    rows="4"
                    :disabled="isLoading"
                ></textarea>
            </div>
        </div>

        <!-- Адрес и расположение -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-map-location-dot"></i>
                Адрес и расположение
            </div>

            <div class="form-group">
                <label class="form-label" for="partner-address">
                    <i class="fa-solid fa-location-dot"></i>
                    Физический адрес
                </label>
                <input
                    id="partner-address"
                    type="text"
                    v-model="form.address"
                    class="form-input"
                    placeholder="г. Москва, ул. Примерная, д. 1, офис 10"
                    :disabled="isLoading"
                >
                <span class="form-hint">Этот адрес будет отображаться в карточке партнера и в модалке расположения</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="partner-coords">
                    <i class="fa-solid fa-crosshairs"></i>
                    Координаты заведения
                </label>
                <div class="input-with-suffix">
                    <input
                        id="partner-coords"
                        type="text"
                        v-model="form.shop_coords"
                        class="form-input"
                        placeholder="55.7558, 37.6173"
                        :disabled="isLoading"
                    >
                </div>
                <span class="form-hint">
                    Формат: <code>широта, долгота</code>.
                    <a href="https://yandex.ru/maps" target="_blank" class="hint-link">
                        <i class="fa-solid fa-external-link-alt"></i> Узнать координаты на Яндекс.Картах
                    </a>
                </span>
            </div>
        </div>

        <!-- ГЛОБАЛЬНЫЕ ТЕГИ ЗАВЕДЕНИЯ (Быстрый выбор) -->
        <div v-if="globalVenueTags.length > 0" class="form-section global-tags-section">
            <div class="section-title">
                <i class="fa-solid fa-store"></i>
                Глобальные теги заведения
            </div>
            <p class="form-hint" style="margin-bottom: 12px;">
                Нажмите на тег, чтобы быстро добавить его к партнёру. Это обеспечивает единый стандарт для поиска и фильтрации.
            </p>
            <div class="global-tags-list">
                <button
                    v-for="(tag, index) in globalVenueTags"
                    :key="index"
                    type="button"
                    class="global-tag-chip"
                    :class="{ 'is-added': form.tags.includes(tag.toLowerCase()) }"
                    @click="addGlobalTag(tag)"
                    :title="form.tags.includes(tag.toLowerCase()) ? 'Уже добавлен' : 'Добавить тег'"
                >
                    <i :class="form.tags.includes(tag.toLowerCase()) ? 'fa-solid fa-check' : 'fa-solid fa-plus'"></i>
                    {{ tag }}
                </button>
            </div>
        </div>

        <!-- ТЕГИ -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-tags"></i>
                Теги для поиска
            </div>
            <div class="form-group">
                <label class="form-label">
                    <i class="fa-solid fa-hashtag"></i>
                    Теги партнёра
                </label>
                <div
                    class="tags-input-container"
                    :class="{ 'has-error': errors.tags }"
                    @click="focusTagInput"
                >
                    <div class="tags-list">
                        <span v-for="(tag, index) in form.tags" :key="index" class="tag-chip">
                            {{ tag }}
                            <button type="button" class="tag-remove" @click.stop="removeTag(index)">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </span>
                        <input
                            ref="tagInputRef"
                            v-model="tagInput"
                            @keydown.enter.prevent="addTag"
                            @keydown.,.prevent="addTag"
                            @keydown.backspace="handleBackspace"
                            placeholder="Введите тег и нажмите Enter"
                            class="tag-input-field"
                            :disabled="isLoading"
                        >
                    </div>
                </div>
                <span v-if="errors.tags" class="form-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ errors.tags }}
                </span>
                <span v-else class="form-hint">
                    Нажмите Enter или запятую, чтобы добавить. Максимум 10 тегов. Только буквы, цифры и дефис.
                </span>
            </div>
        </div>

        <!-- УВЕДОМЛЕНИЯ (TELEGRAM) -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-brands fa-telegram"></i>
                Уведомления в Telegram
            </div>

            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <i class="fa-brands fa-telegram"></i>
                    </div>
                    <div class="setting-text">
                        <h4 class="setting-title">Отправлять уведомления о заказах</h4>
                        <p class="setting-description">Новые заказы будут дублироваться в указанный канал или группу</p>
                    </div>
                </div>
                <label for="telegram-enabled" class="switch-control">
                    <input
                        id="telegram-enabled"
                        type="checkbox"
                        v-model="form.config.telegram_enabled"
                        class="switch-input"
                        :disabled="isLoading"
                    >
                    <span class="switch-slider"></span>
                </label>
            </div>

            <template v-if="form.config.telegram_enabled">
                <div class="form-group">
                    <label class="form-label" for="telegram-token">
                        <i class="fa-solid fa-key"></i>
                        Токен бота (Bot Token)
                    </label>
                    <input
                        id="telegram-token"
                        type="text"
                        v-model="form.config.telegram_token"
                        class="form-input"
                        placeholder="Например: 123456789:AAHxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        :disabled="isLoading"
                    >
                    <span class="form-hint">Получите у @BotFather при создании бота</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="telegram-channel-id">
                        <i class="fa-solid fa-hashtag"></i>
                        ID канала или группы (Chat ID)
                    </label>
                    <input
                        id="telegram-channel-id"
                        type="text"
                        v-model="form.config.telegram_channel_id"
                        class="form-input"
                        placeholder="Например: -1001234567890"
                        :disabled="isLoading"
                    >
                    <span class="form-hint">Для групп и каналов ID всегда начинается с <b>-100</b>.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="telegram-thread-id">
                        <i class="fa-solid fa-layer-group"></i>
                        ID темы (Thread ID) <span style="color: var(--admin-text-muted); font-weight: 400; font-size: 0.8rem;">(необязательно)</span>
                    </label>
                    <input
                        id="telegram-thread-id"
                        type="text"
                        v-model="form.config.telegram_thread_id"
                        class="form-input"
                        placeholder="Например: 42"
                        :disabled="isLoading"
                    >
                    <span class="form-hint">Заполните только если в вашей группе включены "Темы" (Topics).</span>
                </div>
            </template>
        </div>

        <!-- Изображение -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-image"></i>
                Изображение
            </div>

            <div
                class="file-uploader"
                :class="{ 'is-dragging': isDragging, 'has-image': previewImage }"
                @dragenter.prevent="isDragging = true"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onFileDrop"
            >
                <template v-if="previewImage">
                    <div class="preview-container">
                        <img v-lazy="previewImage" class="preview-image" alt="Превью">
                        <button
                            type="button"
                            class="remove-btn"
                            @click="removeImage"
                            :disabled="isLoading"
                            title="Удалить изображение"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="file-info">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>{{ file ? file.name : 'Текущее изображение' }}</span>
                    </div>
                </template>
                <template v-else>
                    <div class="upload-prompt">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span class="upload-title">Загрузить изображение</span>
                        <span class="upload-hint">Перетащите файл или нажмите для выбора</span>
                    </div>
                </template>

                <input
                    ref="fileInput"
                    type="file"
                    class="file-input"
                    accept="image/*"
                    @change="onFileChange"
                    :disabled="isLoading"
                >
            </div>

            <button
                v-if="!previewImage"
                type="button"
                class="btn-upload"
                @click="triggerFileInput"
                :disabled="isLoading"
            >
                <i class="fa-solid fa-folder-open"></i>
                Выбрать файл
            </button>
        </div>

        <!-- Финансы -->
        <div class="form-section">
            <div class="section-title">
                <i class="fa-solid fa-coins"></i>
                Финансы
            </div>
            <div class="form-group">
                <label class="form-label" for="partner-charge">
                    <i class="fa-solid fa-percent"></i>
                    Дополнительная плата (наценка)
                </label>
                <div class="input-with-suffix">
                    <input
                        id="partner-charge"
                        type="number"
                        v-model.number="form.extra_charge"
                        class="form-input"
                        placeholder="0"
                        min="0"
                        step="0.01"
                        :disabled="isLoading"
                    >
                    <span class="input-suffix">%</span>
                </div>
                <span class="form-hint">Процент наценки на товары партнёра</span>
            </div>
        </div>

        <!-- Действия -->
        <div class="form-actions">
            <button
                type="button"
                class="btn-cancel"
                @click="$emit('cancel')"
                :disabled="isLoading"
            >
                Отмена
            </button>
            <button
                type="submit"
                class="btn-submit"
                :disabled="isLoading || !isValid"
            >
                <span v-if="isLoading" class="spinner-small"></span>
                <template v-else>
                    <i class="fa-solid fa-floppy-disk"></i>
                    Сохранить
                </template>
            </button>
        </div>

    </form>
</template>

<script>
import { usePartners } from '@/MobileClient/Composables/usePartners.js'

export default {
    name: 'ConfigPartnerForm',

    props: {
        initialData: {
            type: Object,
            default: null,
        },
    },

    emits: ['success', 'cancel', 'select'],

    setup() {
        const partners = usePartners()
        return {
            updatePartner: partners.updatePartner,
        }
    },

    data() {
        return {
            isLoading: false,
            file: null,
            preview: null,
            isDragging: false,
            tagInput: '',

            // 🆕 Поля для управления ссылкой
            urlInput: '',
            originalUrl: '',

            errors: {
                title: '',
                tags: '',
                url: '',
            },
            form: {
                id: null,
                title: '',
                description: '',
                tags: [],
                image: '',
                order_position: 0,
                is_active: true,
                extra_charge: 0,
                demo_mode: true,
                address: '',
                shop_coords: '',
                config: {
                    excludes: [],
                    bg_color: 'transparent',
                    telegram_enabled: false,
                    telegram_token: '',
                    telegram_channel_id: '',
                    telegram_thread_id: '',
                },
            }
        }
    },

    computed: {
        globalVenueTags() {
            const tags = this.settings?.venue_tags;
            if (!tags) return [];
            if (Array.isArray(tags)) return tags;
            if (typeof tags === 'string') return tags.split(',').map(t => t.trim()).filter(Boolean);
            return [];
        },
        tenant() { return window.Tenant || null; },
        self() { return window.TenantUser || null; },
        settings() { return this.tenant?.settings || null; },

        isValid() {
            return this.form.title?.trim().length > 0
        },

        previewImage() {
            if (this.preview) return this.preview
            if (this.form.image) return this.form.image
            return null
        }
    },

    mounted() {
        if (this.initialData) {
            const defaultConfig = { ...this.form.config }
            this.form = { ...this.form, ...this.initialData }

            if (!Array.isArray(this.form.tags)) {
                if (typeof this.form.tags === 'string' && this.form.tags.trim() !== '') {
                    this.form.tags = this.form.tags.split(',').map(t => t.trim()).filter(Boolean)
                } else {
                    this.form.tags = []
                }
            }

            let parsedConfig = defaultConfig;
            if (this.initialData.config) {
                if (typeof this.initialData.config === 'string') {
                    try { parsedConfig = { ...defaultConfig, ...JSON.parse(this.initialData.config) } }
                    catch (e) { parsedConfig = defaultConfig; }
                } else if (typeof this.initialData.config === 'object') {
                    parsedConfig = { ...defaultConfig, ...this.initialData.config }
                }
            }

            this.form.config = {
                excludes: parsedConfig.excludes || [],
                bg_color: parsedConfig.bg_color || 'transparent',
                telegram_enabled: Boolean(parsedConfig.telegram_enabled),
                telegram_token: parsedConfig.telegram_token || '',
                telegram_channel_id: parsedConfig.telegram_channel_id || '',
                telegram_thread_id: parsedConfig.telegram_thread_id || '',
            }

            // 🆕 Восстанавливаем текущую ссылку, если она есть в initialData
            if (this.initialData.tenant_partner_slug) {
                this.urlInput = `https://${this.initialData.tenant_partner_slug}.mypwa.ru`;
            } else if (this.initialData.current_url) {
                this.urlInput = this.initialData.current_url;
            }
            this.originalUrl = this.urlInput;
        }
    },

    beforeUnmount() {
        if (this.preview) URL.revokeObjectURL(this.preview)
    },

    methods: {
        addGlobalTag(tag) {
            const normalizedTag = tag.trim().toLowerCase();
            if (!normalizedTag) return;
            if (this.form.tags.includes(normalizedTag)) {
                this.$notify?.({ title: 'Информация', text: 'Этот тег уже добавлен', type: 'info' });
                return;
            }
            if (this.form.tags.length >= 10) {
                this.$notify?.({ title: 'Ошибка', text: 'Максимальное количество тегов: 10', type: 'warning' });
                return;
            }
            this.form.tags.push(normalizedTag);
            this.$notify?.({ title: 'Успех', text: `Тег "${tag}" добавлен`, type: 'success' });
        },

        focusTagInput() { this.$refs.tagInputRef?.focus() },

        addTag() {
            const newTag = this.tagInput.trim().toLowerCase()
            if (!newTag) return
            if (this.form.tags.length >= 10) {
                this.errors.tags = 'Максимальное количество тегов: 10'
                this.tagInput = ''
                setTimeout(() => { this.errors.tags = '' }, 2000)
                return
            }
            if (this.form.tags.includes(newTag)) {
                this.errors.tags = 'Этот тег уже добавлен'
                this.tagInput = ''
                setTimeout(() => { this.errors.tags = '' }, 2000)
                return
            }
            if (!/^[a-zа-яё0-9\-]+$/i.test(newTag)) {
                this.errors.tags = 'Тег может содержать только буквы, цифры и дефис'
                this.tagInput = ''
                setTimeout(() => { this.errors.tags = '' }, 2000)
                return
            }
            this.errors.tags = ''
            this.form.tags.push(newTag)
            this.tagInput = ''
        },

        removeTag(index) {
            this.form.tags.splice(index, 1)
            this.errors.tags = ''
        },

        handleBackspace() {
            if (!this.tagInput && this.form.tags.length > 0) {
                this.removeTag(this.form.tags.length - 1)
            }
        },

        triggerFileInput() { this.$refs.fileInput?.click() },
        onFileChange(e) { this.handleFile(e.target.files?.[0]) },
        onFileDrop(e) {
            this.isDragging = false
            const file = e.dataTransfer?.files?.[0]
            if (file && file.type.startsWith('image/')) this.handleFile(file)
        },

        handleFile(file) {
            if (!file) return
            if (this.preview) URL.revokeObjectURL(this.preview)
            this.file = file
            this.preview = URL.createObjectURL(file)
            this.$emit('select', file)
        },

        removeImage() {
            if (this.preview) URL.revokeObjectURL(this.preview)
            this.file = null
            this.preview = null
            this.form.image = ''
            if (this.$refs.fileInput) this.$refs.fileInput.value = ''
        },

        validateForm() {
            this.errors.title = ''
            this.errors.url = ''

            if (!this.form.title?.trim()) {
                this.errors.title = 'Заголовок обязателен для заполнения'
                return false
            }
            if (this.form.title.trim().length < 2) {
                this.errors.title = 'Заголовок должен содержать минимум 2 символа'
                return false
            }

            // 🆕 Валидация новой ссылки, если она введена
            if (this.urlInput.trim()) {
                let urlToParse = this.urlInput.trim().startsWith('http') ? this.urlInput.trim() : `https://${this.urlInput.trim()}`;
                try {
                    const urlObj = new URL(urlToParse);
                    if (!urlObj.hostname.endsWith('mypwa.ru')) {
                        this.errors.url = 'Ссылка должна вести на домен mypwa.ru';
                        return false;
                    }
                    const match = urlObj.hostname.match(/^([a-z0-9-]+)\.mypwa\.ru$/i);
                    if (!match) {
                        this.errors.url = 'Некорректный формат (ожидается slug.mypwa.ru)';
                        return false;
                    }
                } catch (e) {
                    this.errors.url = 'Некорректная ссылка';
                    return false;
                }
            }

            return true
        },

        async handleSubmit() {
            if (!this.validateForm()) return

            this.isLoading = true

            try {
                const data = new FormData()

                Object.keys(this.form).forEach(key => {
                    const item = this.form[key]
                    if (item !== null && item !== undefined) {
                        if (Array.isArray(item)) {
                            item.forEach(val => data.append(`${key}[]`, val))
                        } else if (typeof item === 'object') {
                            data.append(key, JSON.stringify(item))
                        } else {
                            data.append(key, item)
                        }
                    }
                })

                if (this.file) {
                    data.append('file', this.file)
                }

                // 🆕 Добавляем URL и slug, если ссылка была изменена
                if (this.urlInput.trim() && this.urlInput.trim() !== this.originalUrl) {
                    let urlToParse = this.urlInput.trim().startsWith('http') ? this.urlInput.trim() : `https://${this.urlInput.trim()}`;
                    const urlObj = new URL(urlToParse);
                    const match = urlObj.hostname.match(/^([a-z0-9-]+)\.mypwa\.ru$/i);
                    const slug = match[1];

                    data.append('url', this.urlInput.trim());
                    data.append('slug', slug);
                }

                await this.updatePartner({ form: data })

                this.$notify?.({
                    title: 'Успех',
                    text: 'Параметры партнёра сохранены',
                    type: 'success',
                })

                this.$emit('success')
            } catch (err) {
                console.error('Ошибка сохранения партнёра:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: err.response?.data?.message || 'Не удалось сохранить параметры',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },
    }
}
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-success: #10b981;
$admin-danger: #ef4444;

.partner-config-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

// ==========================================
// СЕКЦИИ
// ==========================================
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

// ==========================================
// НАСТРОЙКИ
// ==========================================
.setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 4px 0;
}

.setting-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.setting-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;

    &.status {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }
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

// ==========================================
// ФОРМЫ
// ==========================================
.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: $admin-text;

    i {
        color: $admin-primary;
        font-size: 0.85rem;
    }

    .required {
        color: $admin-danger;
        font-weight: 700;
    }

    .char-count {
        margin-left: auto;
        font-size: 0.75rem;
        color: $admin-text-muted;
        font-weight: 400;
    }
}

.form-input,
.form-textarea {
    padding: 12px 16px;
    border: 1px solid $admin-border;
    border-radius: 10px;
    font-size: 0.95rem;
    color: $admin-text;
    background: $admin-card-bg;
    transition: all 0.2s;
    min-height: 48px;
    font-family: inherit;

    &:focus {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }

    &.has-error {
        border-color: $admin-danger;

        &:focus {
            box-shadow: 0 0 0 3px rgba($admin-danger, 0.1);
        }
    }

    &:disabled {
        background: $admin-bg;
        cursor: not-allowed;
        opacity: 0.6;
    }

    &::placeholder {
        color: $admin-text-muted;
    }
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.5;
}

.form-hint {
    font-size: 0.8rem;
    color: $admin-text-muted;
    line-height: 1.4;
}

.form-error {
    font-size: 0.85rem;
    color: $admin-danger;
    display: flex;
    align-items: center;
    gap: 6px;
}

.input-with-suffix {
    position: relative;
    display: flex;
    align-items: center;

    .form-input {
        padding-right: 40px;
        width: 100%;
    }
}

.input-suffix {
    position: absolute;
    right: 16px;
    font-size: 0.9rem;
    font-weight: 600;
    color: $admin-text-muted;
    pointer-events: none;
}

// ==========================================
// СТИЛИ ДЛЯ ТЕГОВ
// ==========================================
.tags-input-container {
    border: 1px solid $admin-border;
    border-radius: 10px;
    background: $admin-card-bg;
    padding: 8px 12px;
    min-height: 48px;
    display: flex;
    align-items: center;
    transition: all 0.2s;
    cursor: text;

    &:focus-within {
        outline: none;
        border-color: $admin-primary;
        box-shadow: 0 0 0 3px rgba($admin-primary, 0.1);
    }

    &.has-error {
        border-color: $admin-danger;

        &:focus-within {
            box-shadow: 0 0 0 3px rgba($admin-danger, 0.1);
        }
    }
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba($admin-primary, 0.1);
    color: $admin-primary;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    animation: tagPop 0.2s ease;
}

.tag-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: $admin-primary;
    cursor: pointer;
    font-size: 0.7rem;
    transition: all 0.15s;

    &:hover {
        background: $admin-primary;
        color: white;
    }
}

.tag-input-field {
    flex: 1;
    min-width: 120px;
    border: none;
    outline: none;
    font-size: 0.95rem;
    color: $admin-text;
    background: transparent;
    padding: 4px 0;

    &::placeholder {
        color: $admin-text-muted;
    }

    &:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }
}

@keyframes tagPop {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

// ==========================================
// ЗАГРУЗКА ФАЙЛОВ
// ==========================================
.file-uploader {
    position: relative;
    border: 2px dashed $admin-border;
    border-radius: 12px;
    background: $admin-bg;
    transition: all 0.2s;
    overflow: hidden;

    &.is-dragging {
        border-color: $admin-primary;
        background: rgba($admin-primary, 0.04);
    }

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
        margin-bottom: 4px;
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
    aspect-ratio: 16 / 9;
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

    &:hover {
        background: $admin-danger;
        transform: scale(1.1);
    }

    &:active {
        transform: scale(0.9);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.file-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    font-size: 0.85rem;
    color: $admin-text;
    background: $admin-card-bg;
    border-top: 1px solid $admin-border;

    i {
        color: $admin-success;
    }

    span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

.btn-upload {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 10px;
    color: $admin-primary;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;

    &:hover:not(:disabled) {
        background: rgba($admin-primary, 0.04);
        border-color: $admin-primary;
    }

    &:active:not(:disabled) {
        transform: scale(0.98);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

// ==========================================
// SWITCH
// ==========================================
.switch-control {
    position: relative;
    width: 48px;
    height: 28px;
    flex-shrink: 0;
    cursor: pointer; // 👈 Добавляем курсор
}

.switch-input {
    position: absolute;
    inset: 0; // 👈 Растягиваем на весь блок вместо width: 0
    opacity: 0;
    cursor: pointer;
    z-index: 2; // 👈 Помещаем поверх slider, чтобы ловить клики

    &:checked + .switch-slider {
        background: $admin-success;

        &::before {
            transform: translateX(20px);
        }
    }

    &:disabled + .switch-slider {
        opacity: 0.5;
        cursor: not-allowed;
    }

    &:disabled {
        cursor: not-allowed;
    }
}

.switch-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: $admin-border;
    transition: 0.3s;
    border-radius: 28px;
    z-index: 1; // 👈 Помещаем под input

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
// ДЕЙСТВИЯ
// ==========================================
.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 8px;
}

.btn-cancel, .btn-submit {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 10px;
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

.btn-cancel {
    background: $admin-bg;
    color: $admin-text;
    border: 1px solid $admin-border;

    &:hover:not(:disabled) {
        background: $admin-border;
    }
}

.btn-submit {
    background: linear-gradient(135deg, $admin-primary 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 4px 12px rgba($admin-primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba($admin-primary, 0.4);
    }
}

.spinner-small {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (min-width: 768px) {
    .partner-config-form {
        max-width: 700px;
        margin: 0 auto;
    }

    .form-actions {
        justify-content: flex-end;
    }

    .btn-cancel, .btn-submit {
        flex: 0 1 auto;
        min-width: 140px;
    }
}

// ==========================================
// ГЛОБАЛЬНЫЕ ТЕГИ ЗАВЕДЕНИЯ
// ==========================================
.global-tags-section {
    background: rgba($admin-primary, 0.03);
    border-color: rgba($admin-primary, 0.2);
}

.global-tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.global-tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 8px;
    color: $admin-text;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;

    i {
        font-size: 0.75rem;
        color: $admin-primary;
        transition: all 0.2s ease;
    }

    &:hover:not(.is-added) {
        border-color: $admin-primary;
        color: $admin-primary;
        background: rgba($admin-primary, 0.05);
        transform: translateY(-1px);
    }

    &.is-added {
        background: rgba($admin-success, 0.1);
        border-color: $admin-success;
        color: $admin-success;
        cursor: default;

        i {
            color: $admin-success;
        }
    }
}
</style>
