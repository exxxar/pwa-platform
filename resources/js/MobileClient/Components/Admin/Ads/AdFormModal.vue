<template>
    <transition name="modal-fade">
        <div v-if="modelValue" class="modal-overlay" @click.self="$emit('update:modelValue', false)">
            <div class="modal-container">
                <div class="modal-header">
                    <button class="modal-close" @click="$emit('update:modelValue', false)">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div class="modal-header-content">
                        <div class="modal-icon">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div>
                            <h3 class="modal-title">{{ isEdit ? 'Редактирование рекламы' : 'Новый рекламный блок' }}</h3>
                            <p class="modal-subtitle">{{ isEdit ? 'Измените параметры блока' : 'Заполните данные для карусели' }}</p>
                        </div>
                    </div>
                </div>

                <form class="modal-body" @submit.prevent="handleSubmit">
                    <!-- ЗАГРУЗЧИК КАРТИНКИ -->
                    <div class="form-group">
                        <label class="form-label">Картинка блока</label>
                        <div
                            class="image-uploader"
                            :class="{ 'has-image': !!form.image, 'is-dragging': isDragging }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="onDrop"
                        >
                            <template v-if="form.image">
                                <img :src="form.image" class="image-preview" alt="Превью">
                                <button type="button" class="image-remove" @click="form.image = ''" :disabled="isUploading">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </template>
                            <label v-else class="image-prompt">
                                <input type="file" accept="image/*" class="image-input" @change="onFileChange" :disabled="isUploading">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span class="prompt-title">Загрузить картинку</span>
                                <span class="prompt-hint">PNG, JPG, WEBP до 5 МБ</span>
                            </label>
                            <div v-if="isUploading" class="image-loading">
                                <div class="spinner"></div>
                                <span>Загрузка...</span>
                            </div>
                        </div>
                        <span class="form-hint">Без картинки — подставится из настроек системы</span>
                    </div>

                    <!-- ЗАГОЛОВОК -->
                    <div class="form-group">
                        <label class="form-label">Заголовок <span class="required">*</span></label>
                        <input v-model="form.title" class="form-input" placeholder="Комбо недели" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Бейдж</label>
                            <input v-model="form.badge" class="form-input" placeholder="Акция">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Текст кнопки</label>
                            <input v-model="form.button_text" class="form-input" placeholder="Подробнее">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Короткий текст (на слайде)</label>
                        <input v-model="form.short_text" class="form-input" placeholder="Скидка 30% до конца недели" maxlength="255">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Полный текст (в модалке)</label>
                        <textarea v-model="form.full_text" class="form-textarea" rows="4" placeholder="Условия акции..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Действие</label>
                            <select v-model="form.action_type" class="form-input">
                                <option value="none">Без действия</option>
                                <option value="url">Внешняя ссылка</option>
                            </select>
                        </div>
                        <div class="form-group" v-if="form.action_type === 'url'">
                            <label class="form-label">URL</label>
                            <input v-model="form.action_value" class="form-input" placeholder="https://...">
                        </div>
                    </div>

                    <!-- РАСПИСАНИЕ -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Показывать с</label>
                            <input v-model="form.starts_at" type="datetime-local" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Показывать по</label>
                            <input v-model="form.ends_at" type="datetime-local" class="form-input">
                        </div>
                    </div>

                    <!-- АКТИВНОСТЬ -->
                    <div class="switch-row">
                        <div>
                            <div class="switch-title">Активен</div>
                            <div class="switch-desc">Реклама отображается в карусели</div>
                        </div>
                        <label class="switch-control">
                            <input type="checkbox" v-model="form.is_active" class="switch-input">
                            <span class="switch-slider"></span>
                        </label>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" @click="$emit('update:modelValue', false)" :disabled="isSubmitting">
                            Отмена
                        </button>
                        <button type="submit" class="btn-submit" :disabled="isSubmitting || !form.title">
                            <span v-if="isSubmitting" class="spinner-small"></span>
                            <template v-else>
                                <i class="fa-solid fa-floppy-disk"></i>
                                {{ isEdit ? 'Сохранить' : 'Создать' }}
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </transition>
</template>

<script>
import { useApi } from '@/AdminPanel/Composables/useApi.js'

export default {
    name: 'AdFormModal',
    props: {
        modelValue: Boolean,
        ad: { type: Object, default: null },
    },
    emits: ['update:modelValue', 'saved'],

    setup() {
        return { api: useApi() }
    },

    data() {
        return {
            isDragging: false,
            isUploading: false,
            isSubmitting: false,
            form: this.getDefaultForm(),
        }
    },

    computed: {
        isEdit() { return !!this.ad?.id },
    },

    watch: {
        modelValue(open) {
            if (open) {
                this.form = this.ad
                    ? { ...this.getDefaultForm(), ...this.ad }
                    : this.getDefaultForm()
            }
        },
    },

    methods: {
        getDefaultForm() {
            return {
                title: '', short_text: '', full_text: '', image: '',
                badge: '', button_text: '', action_type: 'none', action_value: '',
                is_active: true, starts_at: '', ends_at: '',
            }
        },

        async onFileChange(e) {
            const file = e.target.files?.[0]
            e.target.value = ''
            if (file) await this.uploadImage(file)
        },

        async onDrop(e) {
            this.isDragging = false
            const file = e.dataTransfer?.files?.[0]
            if (file) await this.uploadImage(file)
        },

        async uploadImage(file) {
            if (!file.type.startsWith('image/')) {
                this.$notify?.({ title: 'Ошибка', text: 'Только изображения', type: 'error' });
                return
            }
            if (file.size > 5 * 1024 * 1024) {
                this.$notify?.({ title: 'Ошибка', text: 'Файл больше 5 МБ', type: 'error' });
                return
            }

            const formData = new FormData()
            formData.append('file', file)

// Просто передаём FormData — interceptor сам всё сделает


            try {
                this.isUploading = true

                // ВАЖНО: передаём заголовки для FormData
                const response = await this.api.post('/upload-image', formData)
                console.log('Загружено:', response.url)

                // Обрабатываем ответ (useApi может возвращать response.data или сам response)
                const payload = response?.data ?? response

                console.log('Ответ загрузки:', payload) // Для дебага

                this.form.image = payload?.url || payload?.path || payload?.image

                if (!this.form.image) {
                    throw new Error('Сервер не вернул URL изображения')
                }

                this.$notify?.({ title: 'Готово', text: 'Картинка загружена', type: 'success' })
            } catch (e) {
                console.error('Ошибка загрузки:', e)
                this.$notify?.({
                    title: 'Ошибка',
                    text: e.response?.data?.message || e.message || 'Не удалось загрузить картинку',
                    type: 'error'
                })
            } finally {
                this.isUploading = false
            }
        },

        async handleSubmit() {
            this.isSubmitting = true
            try {
                const payload = { ...this.form }
                // Чистим пустые даты
                if (!payload.starts_at) delete payload.starts_at
                if (!payload.ends_at) delete payload.ends_at

                if (this.isEdit) {
                    await this.api.patch(`/promotions/${this.ad.id}`, payload)
                    this.$notify?.({ title: 'Сохранено', text: 'Реклама обновлена', type: 'success' })
                } else {
                    await this.api.post('/promotions', payload)
                    this.$notify?.({ title: 'Создано', text: 'Рекламный блок добавлен', type: 'success' })
                }
                this.$emit('saved')
            } catch (e) {
                console.error(e)
                this.$notify?.({ title: 'Ошибка', text: e.response?.data?.message || 'Не удалось сохранить', type: 'error' })
            } finally {
                this.isSubmitting = false
            }
        },
    },
}
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-danger: #ef4444;

.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
    z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.modal-container {
    background: $admin-card-bg; width: 100%; max-width: 600px; max-height: 90vh;
    border-radius: 20px; overflow: hidden; display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.modal-header {
    padding: 20px; border-bottom: 1px solid $admin-border; position: relative;
}
.modal-close {
    position: absolute; top: 16px; right: 16px; width: 36px; height: 36px;
    border-radius: 50%; background: $admin-bg; border: none; color: $admin-text;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    &:hover { background: $admin-danger; color: white; }
}
.modal-header-content { display: flex; align-items: center; gap: 14px; }
.modal-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6); color: white;
    display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;
}
.modal-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 2px; color: $admin-text; }
.modal-subtitle { font-size: 0.85rem; color: $admin-text-muted; margin: 0; }
.modal-body { padding: 20px; overflow-y: auto; flex: 1; }

.form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-label { font-size: 0.85rem; font-weight: 600; color: $admin-text; }
.required { color: $admin-danger; }
.form-input, .form-textarea {
    padding: 12px 14px; border: 1px solid $admin-border; border-radius: 10px;
    font-size: 0.95rem; background: $admin-card-bg; color: $admin-text;
    font-family: inherit; transition: all 0.2s;
    &:focus { outline: none; border-color: $admin-primary; box-shadow: 0 0 0 3px rgba($admin-primary, 0.1); }
}
.form-textarea { resize: vertical; min-height: 90px; }
.form-hint { font-size: 0.78rem; color: $admin-text-muted; }

// Загрузчик картинки
.image-uploader {
    position: relative; border: 2px dashed $admin-border; border-radius: 12px;
    background: $admin-bg; overflow: hidden; min-height: 140px;
    transition: border-color 0.2s, background 0.2s;
    &.is-dragging { border-color: $admin-primary; background: rgba($admin-primary, 0.05); }
    &.has-image { border-style: solid; }
}
.image-preview { width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; }
.image-remove {
    position: absolute; top: 8px; right: 8px;
    width: 32px; height: 32px; border-radius: 50%;
    background: rgba($admin-danger, 0.9); color: white; border: none;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    &:hover { background: $admin-danger; transform: scale(1.1); }
}
.image-prompt {
    position: relative; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 6px;
    padding: 24px 16px; cursor: pointer; text-align: center;
    color: $admin-text-muted;
    i { font-size: 1.8rem; color: $admin-primary; }
}
.prompt-title { font-size: 0.9rem; font-weight: 600; color: $admin-text; }
.prompt-hint { font-size: 0.75rem; }
.image-input {
    position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;
}
.image-loading {
    position: absolute; inset: 0; z-index: 3;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
    background: rgba(255,255,255,0.85); backdrop-filter: blur(2px);
    font-size: 0.85rem;
}
.spinner, .spinner-small {
    border: 3px solid rgba($admin-primary, 0.2); border-top-color: $admin-primary;
    border-radius: 50%; animation: spin 0.8s linear infinite;
}
.spinner { width: 28px; height: 28px; }
.spinner-small { display: inline-block; width: 16px; height: 16px; border-width: 2px; border-top-color: white; }
@keyframes spin { to { transform: rotate(360deg); } }

// Switch
.switch-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px; background: $admin-bg; border-radius: 10px; margin: 16px 0;
}
.switch-title { font-size: 0.9rem; font-weight: 700; color: $admin-text; margin-bottom: 2px; }
.switch-desc { font-size: 0.78rem; color: $admin-text-muted; }
.switch-control { position: relative; width: 48px; height: 28px; flex-shrink: 0; cursor: pointer; }
.switch-input { opacity: 0; width: 0; height: 0;
    &:checked + .switch-slider { background: $admin-primary; &::before { transform: translateX(20px); } }
}
.switch-slider {
    position: absolute; inset: 0; background: $admin-border; transition: 0.3s; border-radius: 28px;
    &::before {
        position: absolute; content: ''; height: 22px; width: 22px; left: 3px; bottom: 3px;
        background: white; transition: 0.3s; border-radius: 50%;
    }
}

.modal-footer { display: flex; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid $admin-border; }
.btn-cancel, .btn-submit {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px; border-radius: 10px; font-size: 0.9rem; font-weight: 600;
    border: none; cursor: pointer; transition: all 0.2s; min-height: 44px;
    &:disabled { opacity: 0.5; cursor: not-allowed; }
}
.btn-cancel { background: $admin-bg; color: $admin-text; border: 1px solid $admin-border; }
.btn-submit {
    background: linear-gradient(135deg, $admin-primary, #2563eb); color: white;
    box-shadow: 0 4px 12px rgba($admin-primary, 0.3);
    &:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba($admin-primary, 0.4); }
}

.modal-fade-enter-active { transition: opacity 0.3s ease;
    .modal-container { animation: modalSlideUp 0.3s ease; }
}
.modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 640px) {
    .modal-overlay { padding: 0; }
    .modal-container { max-width: 100%; max-height: 100vh; border-radius: 0; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
