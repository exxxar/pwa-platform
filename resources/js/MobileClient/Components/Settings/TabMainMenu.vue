<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title">
                <i class="fa-solid fa-compass"></i>
                Настройка главного меню
            </h3>

            <div class="alert-info">
                <i class="fa-solid fa-circle-info"></i>
                Здесь вы можете изменить названия и иконки пунктов нижнего меню приложения.
            </div>

            <div class="main-menu-grid">
                <div
                    v-for="(item, key) in form"
                    :key="key"
                    class="main-menu-card"
                    :class="{ 'is-disabled': !item.is_visible }"
                >
                    <div class="card-header">
                        <div class="preview-icon">
                            <img
                                :src="`/images/shop/${extraProps.defaultIcons[key]}`"
                                :alt="item.title"
                                @error="$event.target.style.display='none'"
                            >
                        </div>
                        <div class="card-title">{{ item.title }}</div>
                        <label class="toggle-switch">
                            <input type="checkbox" v-model="item.is_visible" @change="emitDirty">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div v-if="item.is_visible" class="card-fields">
                        <div class="form-field">
                            <label>Название пункта</label>
                            <input
                                type="text"
                                v-model="item.title"
                                maxlength="20"
                                @input="emitDirty"
                            >
                        </div>

                        <div class="form-field">
                            <label>Иконка пункта</label>
                            <div class="icon-upload-wrapper">
                                <div class="icon-preview-small">
                                    <img
                                        v-if="getPreview(key) || item.img"
                                        :src="getPreview(key) || (item.img.startsWith('/') ? item.img : `/images/menu/${item.img}`)"
                                        :alt="item.title"
                                    >
                                    <i v-else class="fa-solid fa-image"></i>
                                </div>

                                <div class="icon-actions">
                                    <label class="upload-btn small">
                                        <input
                                            type="file"
                                            accept="image/png, image/jpeg, image/svg+xml"
                                            @change="handleIconUpload($event, key)"
                                        >
                                        <i class="fa-solid fa-upload"></i>
                                        <span>{{ hasCustomIcon(key) ? 'Заменить' : 'Загрузить' }}</span>
                                    </label>

                                    <button
                                        v-if="hasCustomIcon(key)"
                                        type="button"
                                        class="reset-btn small"
                                        @click="resetIcon(key)"
                                        title="Удалить кастомную иконку и вернуть стандартную"
                                    >
                                        <i class="fa-solid fa-rotate-left"></i>
                                        <span>Сбросить</span>
                                    </button>
                                </div>
                            </div>
                            <span class="field-hint">Рекомендуемый размер: 64×64 px (PNG, JPG или SVG)</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!form || Object.keys(form).length === 0" class="empty-state">
                <i class="fa-solid fa-compass"></i>
                <p>Пункты главного меню пока не настроены</p>
            </div>
        </div>

        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить' }}</span>
        </button>
    </form>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TabMainMenu',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'main_menu_items');
        },

        onSubmit() {
            this.$emit('save', this.form);
        },

        // Получает URL превью иконки
        getPreview(key) {
            return this.extraProps.previews?.[key] || null;
        },

        // Проверяет, установлена ли кастомная иконка (не дефолтная)
        hasCustomIcon(key) {
            return this.form[key]?.img && this.form[key].img !== this.extraProps.defaultIcons?.[key];
        },

        // Загрузка иконки пункта меню
        async handleIconUpload(event, key) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                this.$emit('notify', {
                    title: 'Ошибка',
                    text: 'Файл слишком большой (макс. 2MB)',
                    type: 'error'
                });
                return;
            }

            // Локальное превью
            this.extraProps.previews[key] = URL.createObjectURL(file);
            this.emitDirty();

            const formData = new FormData();
            formData.append('icon', file);
            formData.append('menu_key', key);

            try {
                const response = await axios.post(
                    `/admin/tenant-settings/main-menu/upload-icon`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                );
                this.form[key].img = response.data.filename;
                this.$emit('notify', {
                    title: 'Успешно',
                    text: 'Иконка обновлена',
                    type: 'success'
                });
            } catch (error) {
                console.error('Ошибка загрузки иконки меню:', error);
                this.$emit('notify', {
                    title: 'Ошибка',
                    text: 'Не удалось загрузить иконку',
                    type: 'error'
                });
            }
        },

        // Сброс иконки к дефолтной
        async resetIcon(key) {
            try {
                const response = await axios.post(
                    `/admin/tenant-settings/main-menu/reset-icon`,
                    { menu_key: key }
                );

                if (response.data.success) {
                    this.form[key].img = response.data.default_name;
                    this.extraProps.previews[key] = response.data.img;
                    this.emitDirty();
                    this.$emit('notify', {
                        title: 'Сброшено',
                        text: response.data.message,
                        type: 'success'
                    });
                }
            } catch (error) {
                console.error('Ошибка сброса иконки:', error);
                this.$emit('notify', {
                    title: 'Ошибка',
                    text: 'Не удалось сбросить иконку',
                    type: 'error'
                });
            }
        },
    },
};
</script>

<style lang="scss" scoped>
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: var(--text-muted, #6b7280);
    text-align: center;

    i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }

    p {
        margin: 0;
        font-size: 0.95rem;
    }
}
</style>
