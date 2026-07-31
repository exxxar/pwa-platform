<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <!-- Подтабы -->
        <div class="sub-tabs">
            <button
                type="button"
                v-for="sub in extraProps.subTabs"
                :key="sub.key"
                class="sub-tab"
                :class="{ 'is-active': activeSubTab === sub.key }"
                @click="activeSubTab = sub.key"
            >
                <i :class="sub.icon"></i>
                <span>{{ sub.title }}</span>
            </button>
        </div>

        <!-- ===== Подтаб: Основная информация ===== -->
        <template v-if="activeSubTab === 'general'">
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fa-solid fa-mobile-screen"></i>
                    Информация о приложении
                </h3>

                <div class="alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    Эти данные используются в манифесте PWA и отображаются при установке приложения на устройство пользователя.
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Название приложения</label>
                        <input type="text" v-model="form.name" maxlength="50" placeholder="По умолчанию — название заведения" @input="emitDirty">
                        <span class="field-hint">Отображается под иконкой на рабочем столе</span>
                    </div>
                    <div class="form-field">
                        <label>Короткое название</label>
                        <input type="text" v-model="form.short_name" maxlength="12" placeholder="До 12 символов" @input="emitDirty">
                        <span class="field-hint">Для маленьких экранов</span>
                    </div>
                    <div class="form-field full-width">
                        <label>Описание приложения</label>
                        <textarea v-model="form.description" maxlength="300" rows="3" placeholder="Краткое описание для магазинов приложений" @input="emitDirty"></textarea>
                        <span class="char-counter">{{ (form.description || '').length }}/300</span>
                    </div>
                    <div class="form-field">
                        <label>Язык приложения</label>
                        <select v-model="form.lang" @change="emitDirty">
                            <option value="ru">Русский</option>
                            <option value="en">English</option>
                            <option value="es">Español</option>
                            <option value="de">Deutsch</option>
                            <option value="fr">Français</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Категории</label>
                        <select v-model="form.categories" multiple size="5" @change="emitDirty">
                            <option value="shopping">Покупки</option>
                            <option value="food">Еда</option>
                            <option value="business">Бизнес</option>
                            <option value="entertainment">Развлечения</option>
                            <option value="lifestyle">Стиль жизни</option>
                            <option value="health">Здоровье</option>
                            <option value="travel">Путешествия</option>
                        </select>
                        <span class="field-hint">Удерживайте Ctrl/Cmd для выбора нескольких</span>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== Подтаб: Внешний вид ===== -->
        <template v-if="activeSubTab === 'appearance'">
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fa-solid fa-palette"></i>
                    Цвета и ориентация
                </h3>

                <div class="form-grid">
                    <div class="form-field">
                        <label><i class="fa-solid fa-fill-drip"></i> Цвет темы</label>
                        <div class="color-picker-wrapper">
                            <input type="color" v-model="form.theme_color" @input="emitDirty">
                            <input type="text" v-model="form.theme_color" class="color-text" maxlength="7" @input="emitDirty">
                        </div>
                        <span class="field-hint">Цвет верхней панели браузера</span>
                    </div>
                    <div class="form-field">
                        <label><i class="fa-solid fa-paint-roller"></i> Фоновый цвет</label>
                        <div class="color-picker-wrapper">
                            <input type="color" v-model="form.background_color" @input="emitDirty">
                            <input type="text" v-model="form.background_color" class="color-text" maxlength="7" @input="emitDirty">
                        </div>
                        <span class="field-hint">Цвет фона при загрузке приложения</span>
                    </div>
                    <div class="form-field">
                        <label><i class="fa-solid fa-rotate"></i> Ориентация экрана</label>
                        <select v-model="form.orientation" @change="emitDirty">
                            <option value="portrait">Портретная (вертикальная)</option>
                            <option value="landscape">Альбомная (горизонтальная)</option>
                            <option value="any">Любая (авто)</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label><i class="fa-solid fa-display"></i> Режим отображения</label>
                        <select v-model="form.display" @change="emitDirty">
                            <option value="standalone">Standalone (без браузера)</option>
                            <option value="fullscreen">Fullscreen (полный экран)</option>
                            <option value="minimal-ui">Minimal UI (минимум UI)</option>
                            <option value="browser">Browser (как сайт)</option>
                        </select>
                        <span class="field-hint">Standalone — рекомендуемый режим</span>
                    </div>
                </div>

                <!-- Превью -->
                <div class="pwa-preview">
                    <h4>Предпросмотр</h4>
                    <div class="preview-browser" :style="{ borderTopColor: form.theme_color }">
                        <div class="preview-toolbar" :style="{ background: form.theme_color }">
                            <div class="preview-url">🔒 {{ tenant?.slug || 'your-app' }}.mypwa.ru</div>
                        </div>
                        <div class="preview-content" :style="{ background: form.background_color }">
                            <div class="preview-app-icon" :style="{ background: form.theme_color }">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <div class="preview-app-name">
                                {{ form.short_name || form.name || 'Название' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== Подтаб: Иконки ===== -->
        <template v-if="activeSubTab === 'icons'">
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fa-solid fa-icons"></i>
                    Иконки приложения
                </h3>

                <div class="alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    Загрузите иконки в формате PNG. Маскируемые иконки используются на Android для адаптивной иконки.
                </div>

                <div class="icons-grid">
                    <!-- Иконка 192x192 -->
                    <div class="icon-upload-card">
                        <div class="icon-preview">
                            <img v-if="getIconPreview('icon_192')" :src="getIconPreview('icon_192')" alt="">
                            <div v-else class="icon-placeholder">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        </div>
                        <div class="icon-info">
                            <h5>192×192 px</h5>
                            <p>Основная иконка</p>
                        </div>
                        <label class="upload-btn">
                            <input type="file" accept="image/png" @change="handleIconUpload($event, 'icon_192', 192, 192)">
                            <i class="fa-solid fa-upload"></i>
                            <span>Загрузить</span>
                        </label>
                    </div>

                    <!-- Иконка 512x512 -->
                    <div class="icon-upload-card">
                        <div class="icon-preview">
                            <img v-if="getIconPreview('icon_512')" :src="getIconPreview('icon_512')" alt="">
                            <div v-else class="icon-placeholder">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        </div>
                        <div class="icon-info">
                            <h5>512×512 px</h5>
                            <p>Иконка высокого разрешения</p>
                        </div>
                        <label class="upload-btn">
                            <input type="file" accept="image/png" @change="handleIconUpload($event, 'icon_512', 512, 512)">
                            <i class="fa-solid fa-upload"></i>
                            <span>Загрузить</span>
                        </label>
                    </div>

                    <!-- Маскируемая 192 -->
                    <div class="icon-upload-card">
                        <div class="icon-preview maskable">
                            <img v-if="getIconPreview('icon_192_maskable')" :src="getIconPreview('icon_192_maskable')" alt="">
                            <div v-else class="icon-placeholder">
                                <i class="fa-solid fa-image"></i>
                            </div>
                            <div class="mask-overlay"></div>
                        </div>
                        <div class="icon-info">
                            <h5>192×192 maskable</h5>
                            <p>Адаптивная (Android)</p>
                        </div>
                        <label class="upload-btn">
                            <input type="file" accept="image/png" @change="handleIconUpload($event, 'icon_192_maskable', 192, 192)">
                            <i class="fa-solid fa-upload"></i>
                            <span>Загрузить</span>
                        </label>
                    </div>

                    <!-- Маскируемая 512 -->
                    <div class="icon-upload-card">
                        <div class="icon-preview maskable">
                            <img v-if="getIconPreview('icon_512_maskable')" :src="getIconPreview('icon_512_maskable')" alt="">
                            <div v-else class="icon-placeholder">
                                <i class="fa-solid fa-image"></i>
                            </div>
                            <div class="mask-overlay"></div>
                        </div>
                        <div class="icon-info">
                            <h5>512×512 maskable</h5>
                            <p>Адаптивная (Android HD)</p>
                        </div>
                        <label class="upload-btn">
                            <input type="file" accept="image/png" @change="handleIconUpload($event, 'icon_512_maskable', 512, 512)">
                            <i class="fa-solid fa-upload"></i>
                            <span>Загрузить</span>
                        </label>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== Подтаб: Скриншоты ===== -->
        <template v-if="activeSubTab === 'screenshots'">
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fa-solid fa-camera"></i>
                    Скриншоты приложения
                </h3>

                <div class="alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    Скриншоты используются в магазинах приложений и при установке PWA.
                </div>

                <div class="screenshots-grid">
                    <!-- Мобильный скриншот -->
                    <div class="screenshot-upload-card">
                        <div class="screenshot-preview mobile">
                            <img v-if="getScreenshotPreview('mobile')" :src="getScreenshotPreview('mobile')" alt="">
                            <div v-else class="screenshot-placeholder">
                                <i class="fa-solid fa-mobile-screen"></i>
                                <span>375×667 px</span>
                            </div>
                        </div>
                        <div class="screenshot-info">
                            <h5>Мобильная версия</h5>
                            <p>Рекомендуемый размер: 375×667 px</p>
                        </div>
                        <label class="upload-btn">
                            <input type="file" accept="image/*" @change="handleScreenshotUpload($event, 'mobile')">
                            <i class="fa-solid fa-upload"></i>
                            <span>Загрузить</span>
                        </label>
                    </div>

                    <!-- Десктопный скриншот -->
                    <div class="screenshot-upload-card">
                        <div class="screenshot-preview desktop">
                            <img v-if="getScreenshotPreview('desktop')" :src="getScreenshotPreview('desktop')" alt="">
                            <div v-else class="screenshot-placeholder">
                                <i class="fa-solid fa-desktop"></i>
                                <span>1920×1080 px</span>
                            </div>
                        </div>
                        <div class="screenshot-info">
                            <h5>Десктопная версия</h5>
                            <p>Рекомендуемый размер: 1920×1080 px</p>
                        </div>
                        <label class="upload-btn">
                            <input type="file" accept="image/*" @change="handleScreenshotUpload($event, 'desktop')">
                            <i class="fa-solid fa-upload"></i>
                            <span>Загрузить</span>
                        </label>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== Подтаб: Шорткаты ===== -->
        <template v-if="activeSubTab === 'shortcuts'">
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fa-solid fa-bolt"></i>
                    Быстрые действия (шорткаты)
                </h3>

                <div class="alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    Шорткаты появляются при долгом нажатии на иконку приложения. Можно настроить до 4 действий.
                </div>

                <div class="shortcuts-list">
                    <div
                        v-for="(shortcut, key) in form.shortcuts"
                        :key="key"
                        class="shortcut-card"
                        :class="{ 'is-disabled': !shortcut.enabled }"
                    >
                        <div class="shortcut-header">
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shortcut.enabled" @change="emitDirty">
                                <span class="toggle-slider"></span>
                            </label>
                            <div class="shortcut-icon-preview" :style="{ background: form.theme_color }">
                                <i :class="getShortcutIcon(key)"></i>
                            </div>
                            <div class="shortcut-title">{{ shortcut.name || 'Без названия' }}</div>
                        </div>

                        <div v-if="shortcut.enabled" class="shortcut-fields">
                            <div class="form-field">
                                <label>Название</label>
                                <input type="text" v-model="shortcut.name" maxlength="20" @input="emitDirty">
                            </div>
                            <div class="form-field">
                                <label>Короткое название</label>
                                <input type="text" v-model="shortcut.short_name" maxlength="12" @input="emitDirty">
                            </div>
                            <div class="form-field full-width">
                                <label>URL</label>
                                <input type="text" v-model="shortcut.url" placeholder="/pwa/#/..." @input="emitDirty">
                            </div>
                            <div class="form-field full-width">
                                <label>Иконка шортката (192×192)</label>
                                <label class="upload-btn small">
                                    <input type="file" accept="image/png" @change="handleShortcutIconUpload($event, key)">
                                    <i class="fa-solid fa-upload"></i>
                                    <span>{{ shortcut.icon ? 'Изменить' : 'Загрузить иконку' }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить PWA настройки' }}</span>
        </button>
    </form>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TabPwa',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    data() {
        return {
            activeSubTab: 'general',
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },
    },

    mounted() {
        this.initFormFromTenant();
    },

    methods: {
        /**
         * Инициализируем форму напрямую из window.Tenant.settings.pwa
         * Бэкенд уже отдает полностью сформированный объект со всеми дефолтами из конфига
         */
        initFormFromTenant() {
            const pwaSettings = this.tenant?.settings?.pwa;
            if (!pwaSettings) return;

            // Просто копируем все ключи из settings.pwa в form
            // В Vue 3 реактивность работает автоматически через Proxy
            Object.keys(pwaSettings).forEach(key => {
                const value = pwaSettings[key];

                if (value && typeof value === 'object' && !Array.isArray(value)) {
                    // Для вложенных объектов делаем глубокое копирование,
                    // чтобы мутации в form не влияли на исходный tenant
                    this.form[key] = JSON.parse(JSON.stringify(value));
                } else {
                    this.form[key] = value;
                }
            });

            // Формируем URL превью для уже загруженных иконок/скриншотов
            this.initIconPreviews();
        },

        /**
         * Формируем URL превью для уже загруженных иконок/скриншотов
         */
        initIconPreviews() {
            if (!this.tenant) return;

            const tenantId = this.tenant.id;
            const baseUrl = window.location.origin;

            // Инициализируем объекты превью, если их нет
            if (!this.extraProps.iconPreviews) this.extraProps.iconPreviews = {};
            if (!this.extraProps.screenshotPreviews) this.extraProps.screenshotPreviews = {};
            if (!this.extraProps.shortcutIconPreviews) this.extraProps.shortcutIconPreviews = {};

            // Иконки
            if (this.form.icons) {
                Object.keys(this.form.icons).forEach(key => {
                    if (this.form.icons[key]) {
                        this.extraProps.iconPreviews[key] =
                            `${baseUrl}/storage/tenants/${tenantId}/icons/${this.form.icons[key]}`;
                    }
                });
            }

            // Скриншоты
            if (this.form.screenshots) {
                Object.keys(this.form.screenshots).forEach(key => {
                    if (this.form.screenshots[key]) {
                        this.extraProps.screenshotPreviews[key] =
                            `${baseUrl}/storage/tenants/${tenantId}/screenshots/${this.form.screenshots[key]}`;
                    }
                });
            }

            // Иконки шорткатов
            if (this.form.shortcuts) {
                Object.keys(this.form.shortcuts).forEach(key => {
                    if (this.form.shortcuts[key]?.icon) {
                        this.extraProps.shortcutIconPreviews[key] =
                            `${baseUrl}/storage/tenants/${tenantId}/icons/${this.form.shortcuts[key].icon}`;
                    }
                });
            }
        },

        emitDirty() {
            this.$emit('mark-dirty', 'pwa');
        },

        onSubmit() {
            this.$emit('save', this.form);
        },

        getIconPreview(key) {
            return this.extraProps.iconPreviews?.[key] || null;
        },

        getScreenshotPreview(key) {
            return this.extraProps.screenshotPreviews?.[key] || null;
        },

        getShortcutIcon(key) {
            const icons = {
                menu: 'fa-solid fa-bars',
                cart: 'fa-solid fa-cart-shopping',
                cashback: 'fa-solid fa-coins',
                wheel: 'fa-solid fa-dharmachakra'
            };
            return icons[key] || 'fa-solid fa-bolt';
        },

        async handleIconUpload(event, key, expectedWidth, expectedHeight) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                this.$emit('notify', { title: 'Ошибка', text: 'Файл слишком большой (макс. 2MB)', type: 'error' });
                return;
            }

            const img = new Image();
            img.src = URL.createObjectURL(file);
            await new Promise(resolve => img.onload = resolve);

            if (img.width !== expectedWidth || img.height !== expectedHeight) {
                this.$emit('notify', {
                    title: 'Неверный размер',
                    text: `Требуется ${expectedWidth}×${expectedHeight} px`,
                    type: 'warning'
                });
            }

            this.extraProps.iconPreviews[key] = URL.createObjectURL(file);
            const formData = new FormData();
            formData.append('icon', file);
            formData.append('type', key);

            try {
                const response = await axios.post(`/admin/tenant-settings/pwa/upload-icon`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.form.icons[key] = response.data.filename;
                this.emitDirty();
                this.$emit('notify', { title: 'Успешно', text: 'Иконка загружена', type: 'success' });
            } catch (error) {
                this.$emit('notify', { title: 'Ошибка', text: 'Не удалось загрузить иконку', type: 'error' });
            }
        },

        async handleScreenshotUpload(event, key) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                this.$emit('notify', { title: 'Ошибка', text: 'Файл слишком большой (макс. 5MB)', type: 'error' });
                return;
            }

            this.extraProps.screenshotPreviews[key] = URL.createObjectURL(file);
            const formData = new FormData();
            formData.append('screenshot', file);
            formData.append('type', key);

            try {
                const response = await axios.post(`/admin/tenant-settings/pwa/upload-screenshot`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.form.screenshots[key] = response.data.filename;
                this.emitDirty();
                this.$emit('notify', { title: 'Успешно', text: 'Скриншот загружен', type: 'success' });
            } catch (error) {
                this.$emit('notify', { title: 'Ошибка', text: 'Не удалось загрузить скриншот', type: 'error' });
            }
        },

        async handleShortcutIconUpload(event, shortcutKey) {
            const file = event.target.files[0];
            if (!file) return;

            this.extraProps.shortcutIconPreviews[shortcutKey] = URL.createObjectURL(file);
            const formData = new FormData();
            formData.append('icon', file);
            formData.append('type', `shortcut_${shortcutKey}`);

            try {
                const response = await axios.post(`/admin/tenant-settings/pwa/upload-icon`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.form.shortcuts[shortcutKey].icon = response.data.filename;
                this.emitDirty();
                this.$emit('notify', { title: 'Успешно', text: 'Иконка загружена', type: 'success' });
            } catch (error) {
                this.$emit('notify', { title: 'Ошибка', text: 'Не удалось загрузить', type: 'error' });
            }
        },
    },
};
</script>
