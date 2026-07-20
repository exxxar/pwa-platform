<template>
    <div class="settings-page">

        <!-- ========================================== -->
        <!-- ЗАГОЛОВОК -->
        <!-- ========================================== -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fa-solid fa-gear"></i>
                    Настройки платформы
                </h1>
                <p class="page-subtitle">Управляйте всеми параметрами вашего магазина</p>
            </div>
            <div v-if="hasUnsavedChanges" class="unsaved-badge">
                <i class="fa-solid fa-circle-exclamation"></i>
                Есть несохранённые изменения
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ТАБЫ -->
        <!-- ========================================== -->
        <div class="tabs-container">
            <div class="tabs-scroll">
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab.key"
                    class="tab-button"
                    :class="{
                        'is-active': activeTab === index,
                        'is-dirty': isSectionDirty(tab.section)
                    }"
                    @click="changeActiveTab(index)"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.title }}</span>
                    <span v-if="isSectionDirty(tab.section)" class="dirty-dot"></span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ЗАГРУЗКА -->
        <!-- ========================================== -->
        <div v-if="isLoading && !isHydrated" class="loading-state">
            <div class="loader-spinner"></div>
            <p>Загрузка настроек...</p>
        </div>

        <!-- ========================================== -->
        <!-- КОНТЕНТ -->
        <!-- ========================================== -->
        <div v-else class="tab-content">

            <!-- ========== 0: ОСНОВНОЕ ========== -->
            <div v-if="activeTab === 0" class="tab-panel">
                <form @submit.prevent="saveCompany" class="settings-form">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-building"></i> Информация о заведении</h3>
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label>Название</label>
                                <input type="text" v-model="companyForm.title" maxlength="255">
                            </div>
                            <div class="form-field full-width">
                                <label>Описание</label>
                                <textarea v-model="companyForm.description" maxlength="512" rows="4"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-address-book"></i> Контакты</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label><i class="fa-solid fa-phone"></i> Телефон</label>
                                <input type="tel" v-model="companyForm.phones[0]">
                            </div>
                            <div class="form-field">
                                <label><i class="fa-solid fa-envelope"></i> Email</label>
                                <input type="email" v-model="companyForm.email">
                            </div>
                            <div class="form-field">
                                <label><i class="fa-brands fa-instagram"></i> Instagram</label>
                                <input type="text" v-model="companyForm.links.inst">
                            </div>
                            <div class="form-field">
                                <label><i class="fa-brands fa-vk"></i> ВКонтакте</label>
                                <input type="text" v-model="companyForm.links.vk">
                            </div>
                            <div class="form-field full-width">
                                <label><i class="fa-solid fa-globe"></i> Сайт</label>
                                <input type="url" v-model="companyForm.links.site">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-clock"></i> График работы</h3>
                        <div class="schedule-list">
                            <div v-for="(day, i) in companyForm.schedule" :key="i" class="schedule-day" :class="{ 'is-closed': day.closed }">
                                <div class="schedule-day-name">{{ day.day }}</div>
                                <label class="toggle-switch">
                                    <input type="checkbox" v-model="day.closed">
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">{{ day.closed ? 'Закрыто' : 'Открыто' }}</span>
                                </label>
                                <template v-if="!day.closed">
                                    <div class="time-inputs">
                                        <input type="time" v-model="day.start_at">
                                        <span>—</span>
                                        <input type="time" v-model="day.end_at">
                                    </div>
                                </template>
                                <template v-else>
                                    <input type="text" v-model="day.closed_comment" placeholder="Причина">
                                </template>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="save-button" :disabled="isSectionSaving('company')">
                        <i v-if="isSectionSaving('company')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>{{ isSectionSaving('company') ? 'Сохранение...' : 'Сохранить' }}</span>
                    </button>
                </form>
            </div>

            <!-- ========== 🆕 1: PWA ПРИЛОЖЕНИЕ ========== -->
            <div v-if="activeTab === 1" class="tab-panel">
                <form @submit.prevent="savePwa" class="settings-form">

                    <!-- Подтабы -->
                    <div class="sub-tabs">
                        <button
                            type="button"
                            v-for="sub in pwaSubTabs"
                            :key="sub.key"
                            class="sub-tab"
                            :class="{ 'is-active': activePwaSubTab === sub.key }"
                            @click="activePwaSubTab = sub.key"
                        >
                            <i :class="sub.icon"></i>
                            <span>{{ sub.title }}</span>
                        </button>
                    </div>

                    <!-- ===== Подтаб: Основная информация ===== -->
                    <template v-if="activePwaSubTab === 'general'">
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fa-solid fa-mobile-screen"></i>
                                Информация о приложении
                            </h3>

                            <div class="alert-info">
                                <i class="fa-solid fa-circle-info"></i>
                                Эти данные используются в манифесте PWA и отображаются при установке приложения на
                                устройство пользователя.
                            </div>

                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Название приложения</label>
                                    <input type="text" v-model="pwaForm.name" maxlength="50"
                                           placeholder="По умолчанию — название заведения">
                                    <span class="field-hint">Отображается под иконкой на рабочем столе</span>
                                </div>
                                <div class="form-field">
                                    <label>Короткое название</label>
                                    <input type="text" v-model="pwaForm.short_name" maxlength="12"
                                           placeholder="До 12 символов">
                                    <span class="field-hint">Для маленьких экранов</span>
                                </div>
                                <div class="form-field full-width">
                                    <label>Описание приложения</label>
                                    <textarea v-model="pwaForm.description" maxlength="300" rows="3"
                                              placeholder="Краткое описание для магазинов приложений"></textarea>
                                    <span class="char-counter">{{ (pwaForm.description || '').length }}/300</span>
                                </div>
                                <div class="form-field">
                                    <label>Язык приложения</label>
                                    <select v-model="pwaForm.lang">
                                        <option value="ru">Русский</option>
                                        <option value="en">English</option>
                                        <option value="es">Español</option>
                                        <option value="de">Deutsch</option>
                                        <option value="fr">Français</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Категории</label>
                                    <select v-model="pwaForm.categories" multiple size="5">
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
                    <template v-if="activePwaSubTab === 'appearance'">
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fa-solid fa-palette"></i>
                                Цвета и ориентация
                            </h3>

                            <div class="form-grid">
                                <div class="form-field">
                                    <label><i class="fa-solid fa-fill-drip"></i> Цвет темы</label>
                                    <div class="color-picker-wrapper">
                                        <input type="color" v-model="pwaForm.theme_color">
                                        <input type="text" v-model="pwaForm.theme_color" class="color-text"
                                               maxlength="7">
                                    </div>
                                    <span class="field-hint">Цвет верхней панели браузера</span>
                                </div>
                                <div class="form-field">
                                    <label><i class="fa-solid fa-paint-roller"></i> Фоновый цвет</label>
                                    <div class="color-picker-wrapper">
                                        <input type="color" v-model="pwaForm.background_color">
                                        <input type="text" v-model="pwaForm.background_color" class="color-text"
                                               maxlength="7">
                                    </div>
                                    <span class="field-hint">Цвет фона при загрузке приложения</span>
                                </div>
                                <div class="form-field">
                                    <label><i class="fa-solid fa-rotate"></i> Ориентация экрана</label>
                                    <select v-model="pwaForm.orientation">
                                        <option value="portrait">Портретная (вертикальная)</option>
                                        <option value="landscape">Альбомная (горизонтальная)</option>
                                        <option value="any">Любая (авто)</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label><i class="fa-solid fa-display"></i> Режим отображения</label>
                                    <select v-model="pwaForm.display">
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
                                <div class="preview-browser" :style="{ borderTopColor: pwaForm.theme_color }">
                                    <div class="preview-toolbar" :style="{ background: pwaForm.theme_color }">
                                        <div class="preview-url">🔒 {{ tenant?.slug || 'your-app' }}.mypwa.ru</div>
                                    </div>
                                    <div class="preview-content" :style="{ background: pwaForm.background_color }">
                                        <div class="preview-app-icon" :style="{ background: pwaForm.theme_color }">
                                            <i class="fa-solid fa-store"></i>
                                        </div>
                                        <div class="preview-app-name">
                                            {{ pwaForm.short_name || pwaForm.name || 'Название' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- ===== Подтаб: Иконки ===== -->
                    <template v-if="activePwaSubTab === 'icons'">
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fa-solid fa-icons"></i>
                                Иконки приложения
                            </h3>

                            <div class="alert-info">
                                <i class="fa-solid fa-circle-info"></i>
                                Загрузите иконки в формате PNG. Маскируемые иконки используются на Android для
                                адаптивной иконки.
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
                                        <input type="file" accept="image/png"
                                               @change="handleIconUpload($event, 'icon_192', 192, 192)">
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
                                        <input type="file" accept="image/png"
                                               @change="handleIconUpload($event, 'icon_512', 512, 512)">
                                        <i class="fa-solid fa-upload"></i>
                                        <span>Загрузить</span>
                                    </label>
                                </div>

                                <!-- Маскируемая 192 -->
                                <div class="icon-upload-card">
                                    <div class="icon-preview maskable">
                                        <img v-if="getIconPreview('icon_192_maskable')"
                                             :src="getIconPreview('icon_192_maskable')" alt="">
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
                                        <input type="file" accept="image/png"
                                               @change="handleIconUpload($event, 'icon_192_maskable', 192, 192)">
                                        <i class="fa-solid fa-upload"></i>
                                        <span>Загрузить</span>
                                    </label>
                                </div>

                                <!-- Маскируемая 512 -->
                                <div class="icon-upload-card">
                                    <div class="icon-preview maskable">
                                        <img v-if="getIconPreview('icon_512_maskable')"
                                             :src="getIconPreview('icon_512_maskable')" alt="">
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
                                        <input type="file" accept="image/png"
                                               @change="handleIconUpload($event, 'icon_512_maskable', 512, 512)">
                                        <i class="fa-solid fa-upload"></i>
                                        <span>Загрузить</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- ===== Подтаб: Скриншоты ===== -->
                    <template v-if="activePwaSubTab === 'screenshots'">
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
                                        <img v-if="getScreenshotPreview('mobile')" :src="getScreenshotPreview('mobile')"
                                             alt="">
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
                                        <input type="file" accept="image/*"
                                               @change="handleScreenshotUpload($event, 'mobile')">
                                        <i class="fa-solid fa-upload"></i>
                                        <span>Загрузить</span>
                                    </label>
                                </div>

                                <!-- Десктопный скриншот -->
                                <div class="screenshot-upload-card">
                                    <div class="screenshot-preview desktop">
                                        <img v-if="getScreenshotPreview('desktop')"
                                             :src="getScreenshotPreview('desktop')" alt="">
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
                                        <input type="file" accept="image/*"
                                               @change="handleScreenshotUpload($event, 'desktop')">
                                        <i class="fa-solid fa-upload"></i>
                                        <span>Загрузить</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- ===== Подтаб: Шорткаты ===== -->
                    <template v-if="activePwaSubTab === 'shortcuts'">
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fa-solid fa-bolt"></i>
                                Быстрые действия (шорткаты)
                            </h3>

                            <div class="alert-info">
                                <i class="fa-solid fa-circle-info"></i>
                                Шорткаты появляются при долгом нажатии на иконку приложения. Можно настроить до 4
                                действий.
                            </div>

                            <div class="shortcuts-list">
                                <div
                                    v-for="(shortcut, key) in pwaForm.shortcuts"
                                    :key="key"
                                    class="shortcut-card"
                                    :class="{ 'is-disabled': !shortcut.enabled }"
                                >
                                    <div class="shortcut-header">
                                        <label class="toggle-switch">
                                            <input type="checkbox" v-model="shortcut.enabled">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <div class="shortcut-icon-preview" :style="{ background: pwaForm.theme_color }">
                                            <i :class="getShortcutIcon(key)"></i>
                                        </div>
                                        <div class="shortcut-title">{{ shortcut.name || 'Без названия' }}</div>
                                    </div>

                                    <div v-if="shortcut.enabled" class="shortcut-fields">
                                        <div class="form-field">
                                            <label>Название</label>
                                            <input type="text" v-model="shortcut.name" maxlength="20">
                                        </div>
                                        <div class="form-field">
                                            <label>Короткое название</label>
                                            <input type="text" v-model="shortcut.short_name" maxlength="12">
                                        </div>
                                        <div class="form-field full-width">
                                            <label>URL</label>
                                            <input type="text" v-model="shortcut.url" placeholder="/pwa/#/...">
                                        </div>
                                        <div class="form-field full-width">
                                            <label>Иконка шортката (192×192)</label>
                                            <label class="upload-btn small">
                                                <input type="file" accept="image/png"
                                                       @change="handleShortcutIconUpload($event, key)">
                                                <i class="fa-solid fa-upload"></i>
                                                <span>{{
                                                        pwaForm.shortcuts[key].icon ? 'Изменить' : 'Загрузить иконку'
                                                    }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="submit" class="save-button" :disabled="isSectionSaving('pwa')">
                        <i v-if="isSectionSaving('pwa')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>{{ isSectionSaving('pwa') ? 'Сохранение...' : 'Сохранить PWA настройки' }}</span>
                    </button>
                </form>
            </div>

            <!-- ========== 2: МАГАЗИН ========== -->
            <div v-if="activeTab === 2" class="tab-panel">
                <form @submit.prevent="saveShop" class="settings-form">

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-palette"></i> Внешний вид</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Тема по умолчанию</label>
                                <select v-model="shopForm.default_theme_scheme">
                                    <option v-for="scheme in availableSchemes" :key="scheme.id" :value="scheme.id">{{ scheme.name }}</option>
                                </select>
                                <span class="field-hint">Эта тема будет применяться новым пользователям, пока они не выберут свою.</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-store"></i> Основные параметры</h3>
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Магазин выключен</h4>
                                <p>Пользователи увидят сообщение о том, что магазин не работает</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shopForm.is_disabled">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div v-if="shopForm.is_disabled" class="form-field full-width">
                            <label>Текст при выключении</label>
                            <textarea v-model="shopForm.disabled_text" rows="4" maxlength="4000"></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-field">
                                <label>Тип магазина</label>
                                <select v-model="shopForm.shop_display_type">
                                    <option :value="0">Продовольственный</option>
                                    <option :value="1">Бытовые товары</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Отображение товаров</label>
                                <select v-model="shopForm.is_product_list">
                                    <option :value="false">Плитка</option>
                                    <option :value="true">Список</option>
                                </select>
                            </div>
                        </div>

                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Покупки после закрытия</h4>
                                <p>Разрешить оставлять заказы вне рабочего времени</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shopForm.can_buy_after_closing">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Скрывать товары без наличия</h4>
                                <p>Товары с нулевым остатком не будут видны в каталоге</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shopForm.need_hide_disabled_products">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- 🆕 Блок: Способы получения -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-cart-shopping"></i> Способы получения</h3>
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Разрешить доставку</h4>
                                <p>Клиенты смогут выбирать доставку при оформлении заказа</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shopForm.allow_delivery">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Разрешить самовывоз</h4>
                                <p>Клиенты смогут забирать заказ самостоятельно</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shopForm.allow_pickup">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-truck"></i> Доставка</h3>
                        <!-- 🆕 Адрес перенесен сюда -->
                        <div class="form-field full-width" style="margin-bottom: 16px;">
                            <label><i class="fa-solid fa-location-dot"></i> Адрес заведения (для доставки)</label>
                            <input type="text" v-model="shopForm.address" maxlength="255" placeholder="г. Москва, ул. Примерная, д. 1">
                        </div>

                        <div class="form-grid">
                            <div class="form-field">
                                <label>Минимальная сумма заказа, ₽</label>
                                <input type="number" v-model="shopForm.min_price" min="0">
                            </div>
                        </div>
                        <div class="form-field full-width">
                            <label>Текст о доставке</label>
                            <textarea v-model="shopForm.delivery_price_text" rows="4" maxlength="4000" placeholder="Описание процесса доставки и оплаты"></textarea>
                        </div>

                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Автоматический расчёт доставки</h4>
                                <p v-if="shopForm.shop_display_type === 0">Расчёт по координатам заведения</p>
                                <p v-else>Расчёт через СДЭК</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shopForm.need_automatic_delivery_request">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <template v-if="shopForm.need_automatic_delivery_request && shopForm.shop_display_type === 0">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Базовая цена доставки, ₽</label>
                                    <input type="number" v-model="shopForm.min_base_delivery_price" min="0">
                                </div>
                                <div class="form-field">
                                    <label>Цена за 1 км, ₽</label>
                                    <input type="number" v-model="shopForm.price_per_km" min="0">
                                </div>
                                <div class="form-field">
                                    <label>Бесплатная доставка от, ₽</label>
                                    <input type="number" v-model="shopForm.free_shipping_starts_from" min="0">
                                </div>
                                <div class="form-field full-width">
                                    <label>Координаты заведения (из Яндекс.Карт)</label>
                                    <input type="text" v-model="shopForm.shop_coords" placeholder="00.000000, 00.000000">
                                </div>
                                <div class="form-field full-width">
                                    <label>Токен MapTiler</label>
                                    <input type="text" v-model="shopForm.map_tiler" placeholder="Ваш токен">
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-credit-card"></i> Оплата</h3>
                        <div class="form-field full-width">
                            <label>Информация об оплате</label>
                            <textarea v-model="shopForm.payment_info" rows="4" maxlength="4000" placeholder="Как оплатить и инструкции"></textarea>
                        </div>

                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Оплата после звонка</h4>
                                <p>Отключить оплату скриншотом — клиент ждёт звонка оператора</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shopForm.need_pay_after_call">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Наличные и перевод</h4>
                                <p>Разрешить оплату наличными или переводом</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="shopForm.can_use_cash">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <!-- 🆕 СБП перенесен сюда и расширен -->
                        <div class="sbp-banks-wrapper">
                            <h4 style="margin: 20px 0 12px; font-size: 1rem; color: var(--text);">
                                <i class="fa-solid fa-qrcode" style="color: var(--primary); margin-right: 8px;"></i>
                                Система быстрых платежей (СБП)
                            </h4>
                            <p class="field-hint" style="margin-bottom: 16px;">Выберите и настройте банки, через которые вы принимаете оплату по СБП.</p>

                            <div v-for="(bankConfig, bankKey) in shopForm.sbp_banks" :key="bankKey" class="sbp-bank-card" :class="{ 'is-active': bankConfig.enabled }">
                                <div class="sbp-bank-header">
                                    <div class="sbp-bank-name">{{ getBankName(bankKey) }}</div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" v-model="bankConfig.enabled">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div v-if="bankConfig.enabled" class="sbp-bank-fields">
                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label>Ключ терминала</label>
                                            <input type="text" v-model="bankConfig.terminal_key">
                                        </div>
                                        <div class="form-field">
                                            <label>Пароль терминала</label>
                                            <input type="password" v-model="bankConfig.terminal_password">
                                        </div>
                                        <div class="form-field">
                                            <label>Схема налогообложения</label>
                                            <select v-model="bankConfig.tax">
                                                <option value="osn">Общая</option>
                                                <option value="usn_income">УСН (доходы)</option>
                                                <option value="usn_income_outcome">УСН (доходы-расходы)</option>
                                                <option value="patent">Патентная</option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label>Ставка НДС</label>
                                            <select v-model="bankConfig.vat">
                                                <option value="none">Нет</option>
                                                <option value="vat0">0%</option>
                                                <option value="vat10">10%</option>
                                                <option value="vat20">20%</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-list-check"></i> Секции корзины</h3>
                        <div class="toggle-row">
                            <div class="toggle-info"><h4>Промокод</h4></div>
                            <label class="toggle-switch"><input type="checkbox" v-model="shopForm.need_promo_code"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="toggle-row">
                            <div class="toggle-info"><h4>Использование бонусов</h4></div>
                            <label class="toggle-switch"><input type="checkbox" v-model="shopForm.need_bonuses_section"><span class="toggle-slider"></span></label>
                        </div>
                        <template v-if="shopForm.shop_display_type === 0">
                            <div class="toggle-row">
                                <div class="toggle-info"><h4>Число персон</h4></div>
                                <label class="toggle-switch"><input type="checkbox" v-model="shopForm.need_person_counter"><span class="toggle-slider"></span></label>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info"><h4>Ограничения по здоровью</h4></div>
                                <label class="toggle-switch"><input type="checkbox" v-model="shopForm.need_health_restrictions"><span class="toggle-slider"></span></label>
                            </div>
                        </template>
                    </div>

                    <!-- 🆕 Расширенные данные менеджера -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-headset"></i> Контактное лицо (Менеджер)</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Имя менеджера</label>
                                <input type="text" v-model="shopForm.manager.name" placeholder="Иван Иванов">
                            </div>
                            <div class="form-field">
                                <label>Телефон менеджера</label>
                                <input type="tel" v-model="shopForm.manager.phone" placeholder="+7 (999) 000-00-00">
                            </div>
                            <div class="form-field">
                                <label>Email менеджера</label>
                                <input type="email" v-model="shopForm.manager.email" placeholder="manager@example.com">
                            </div>
                            <div class="form-field">
                                <label>Ссылка на соц. сеть / Мессенджер</label>
                                <input type="url" v-model="shopForm.manager.social_link" @blur="normalizeTelegramLink" placeholder="https://t.me/username">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="save-button" :disabled="isSectionSaving('shop')">
                        <i v-if="isSectionSaving('shop')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>{{ isSectionSaving('shop') ? 'Сохранение...' : 'Сохранить' }}</span>
                    </button>
                </form>
            </div>
            <!-- ========== 3: БАЛЛЫ И СЕРТИФИКАТЫ ========== -->
            <div v-if="activeTab === 3" class="tab-panel">
                <form @submit.prevent="saveCashback" class="settings-form">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-coins"></i> Кэшбэк и сгорание баллов</h3>
                        <div class="form-field">
                            <label>Макс. % списания кэшбэка</label>
                            <div class="input-with-suffix">
                                <input type="number" v-model="cashbackForm.max_cashback_use_percent" min="0" max="100">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>

                        <!-- 🆕 Настройки сгорания баллов -->
                        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed var(--border);">
                            <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 16px; color: var(--text);">Правила сгорания баллов</h4>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Период сгорания</label>
                                    <select v-model="cashbackForm.expiration_period">
                                        <option value="week">1 неделя</option>
                                        <option value="month">1 месяц</option>
                                        <option value="3_months">3 месяца</option>
                                        <option value="6_months">6 месяцев</option>
                                        <option value="12_months">12 месяцев</option>
                                        <option value="never">Не сгорают</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Процент сгорания за период</label>
                                    <div class="input-with-suffix">
                                        <input type="number" v-model="cashbackForm.expiration_percent" min="0" max="100">
                                        <span class="input-suffix">%</span>
                                    </div>
                                    <span class="field-hint">Например, 100% означает полное сгорание, 5% — частичное.</span>
                                </div>
                            </div>

                            <div class="toggle-row" style="margin-top: 16px;">
                                <div class="toggle-info">
                                    <h4>Оповещать клиентов о сгорании</h4>
                                    <p>Отправлять уведомление за указанное количество дней до списания</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" v-model="cashbackForm.notify_expiration">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div v-if="cashbackForm.notify_expiration" class="form-field" style="margin-top: 12px;">
                                <label>Оповещать за (дней до сгорания)</label>
                                <select v-model="cashbackForm.notify_days_before">
                                    <option :value="1">1 день</option>
                                    <option :value="2">2 дня</option>
                                    <option :value="3">3 дня</option>
                                    <option :value="5">5 дней</option>
                                    <option :value="7">7 дней</option>
                                    <option :value="10">10 дней</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-users"></i> Реферальная программа</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Уровень 1</label>
                                <div class="input-with-suffix"><input type="number" v-model="cashbackForm.level_1" min="0" max="100"><span class="input-suffix">%</span></div>
                            </div>
                            <div class="form-field">
                                <label>Уровень 2</label>
                                <div class="input-with-suffix"><input type="number" v-model="cashbackForm.level_2" min="0" max="100"><span class="input-suffix">%</span></div>
                            </div>
                            <div class="form-field">
                                <label>Уровень 3</label>
                                <div class="input-with-suffix"><input type="number" v-model="cashbackForm.level_3" min="0" max="100"><span class="input-suffix">%</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-gift"></i> Подарочный сертификат</h3>
                        <div class="certificate-preview">
                            <img src="/images/certificate_1.png" alt="Certificate" class="cert-bg">
                            <div class="cert-content">
                                <p class="cert-title">{{ certificateForm.title || 'Заголовок' }}</p>
                                <p class="cert-desc">{{ certificateForm.description || 'Описание' }}</p>
                                <p class="cert-date">{{ formatDate(new Date()) }}</p>
                                <div class="cert-qr">QR</div>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Название сертификата</label>
                                <input type="text" v-model="certificateForm.title">
                            </div>
                            <div class="form-field">
                                <label>Описание приза</label>
                                <input type="text" v-model="certificateForm.description">
                            </div>
                            <div class="form-field">
                                <label>Тип сертификата</label>
                                <select v-model="certificateForm.type">
                                    <option value="cashback">CashBack</option>
                                    <option value="discount">Скидка</option>
                                    <option value="gift">Подарок</option>
                                </select>
                            </div>
                            <div class="form-field" v-if="certificateForm.type !== 'gift'">
                                <label>{{ certificateForm.type === 'cashback' ? 'Сумма, ₽' : 'Процент скидки, %' }}</label>
                                <input type="number" v-model="certificateForm.amount" min="0">
                            </div>
                        </div>
                        <div class="toggle-row">
                            <div class="toggle-info"><h4>Сертификат активен</h4></div>
                            <label class="toggle-switch"><input type="checkbox" v-model="certificateForm.is_active"><span class="toggle-slider"></span></label>
                        </div>
                    </div>

                    <button type="submit" class="save-button" :disabled="isSectionSaving('cashback')">
                        <i v-if="isSectionSaving('cashback')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>Сохранить</span>
                    </button>
                </form>
            </div>

            <!-- ========== 4: ИНТЕРАКТИВ ========== -->
            <div v-if="activeTab === 4" class="tab-panel">
                <form @submit.prevent="saveInteractive" class="settings-form">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-mug-hot"></i> Кофе в подарок</h3>
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Бонусная программа кофе</h4>
                                <p>Система отметок за каждую покупку кофе</p>
                            </div>
                            <label class="toggle-switch"><input type="checkbox" v-model="coffeeForm.enabled"><span class="toggle-slider"></span></label>
                        </div>
                        <template v-if="coffeeForm.enabled">
                            <div class="form-field"><label>Необходимое количество покупок</label><input type="number" v-model="coffeeForm.max" min="1"></div>
                            <div class="form-field full-width"><label>Правила программы</label><textarea v-model="coffeeForm.rules" rows="6" maxlength="4000"></textarea></div>
                        </template>
                    </div>
                    <button type="submit" class="save-button" :disabled="isSectionSaving('interactive')">
                        <i v-if="isSectionSaving('interactive')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>Сохранить</span>
                    </button>
                </form>
            </div>

            <!-- ========== 5: СТОЛИКИ ========== -->
            <div v-if="activeTab === 5" class="tab-panel">
                <form @submit.prevent="saveTables" class="settings-form">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-utensils"></i> Бронирование столиков</h3>
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Показывать список столиков</h4>
                                <p>Клиент сможет выбирать конкретный столик при бронировании</p>
                            </div>
                            <label class="toggle-switch"><input type="checkbox" v-model="tablesForm.need_table_list"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="form-field"><label>Максимальное количество столиков</label><input type="number" v-model="tablesForm.max_tables" min="0"></div>

                        <!-- 🆕 Кнопка скачивания PDF -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
                            <button type="button" class="btn-download-pdf" @click="downloadTablesPdf">
                                <i class="fa-solid fa-file-pdf"></i>
                                <span>Скачать PDF с QR-кодами столов</span>
                            </button>
                            <span class="field-hint" style="display: block; margin-top: 8px;">Генерирует документ со всеми активными столиками и их QR-кодами для печати.</span>
                        </div>

                        <div class="alert-info" style="margin-top: 16px;">
                            <i class="fa-solid fa-circle-info"></i>
                            Для детального планирования столиков используйте отдельный компонент планировщика.
                        </div>
                    </div>
                    <button type="submit" class="save-button" :disabled="isSectionSaving('tables')">
                        <i v-if="isSectionSaving('tables')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>Сохранить</span>
                    </button>
                </form>
            </div>

            <!-- ========== 6: ПУНКТЫ МЕНЮ ========== -->
            <div v-if="activeTab === 6" class="tab-panel">
                <form @submit.prevent="saveMenu" class="settings-form">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-bars"></i> Видимость пунктов бокового меню</h3>
                        <div class="toggle-list">
                            <div v-for="(item, key) in menuForm" :key="key" class="toggle-row">
                                <div class="toggle-info">
                                    <h4><i :class="'fa-solid ' + item.icon"></i> {{ item.title }}</h4>
                                </div>
                                <label class="toggle-switch"><input type="checkbox" v-model="item.is_visible"><span class="toggle-slider"></span></label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="save-button" :disabled="isSectionSaving('menu')">
                        <i v-if="isSectionSaving('menu')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>Сохранить</span>
                    </button>
                </form>
            </div>

            <!-- ========== 7: КАЛЬКУЛЯТОРЫ ========== -->
            <div v-if="activeTab === 7" class="tab-panel">
                <form @submit.prevent="saveCalculators" class="settings-form">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-hive"></i> Калькуляторы еды (Собери сам)</h3>
                        <div class="cards-grid">
                            <div v-for="(calc, key) in calculatorsForm" :key="key" class="feature-card" :class="{ 'is-disabled': !calc.is_visible }" :style="{ backgroundImage: calc.gradient }">
                                <div class="card-emoji">{{ calc.emoji }}</div>
                                <div class="card-info">
                                    <h4>{{ calc.title }}</h4>
                                    <p>{{ calc.description }}</p>
                                    <div class="card-meta"><span>🥘 {{ calc.ingredientsCount }}</span><span>⏱️ {{ calc.time }}</span></div>
                                </div>
                                <label class="toggle-switch"><input type="checkbox" v-model="calc.is_visible"><span class="toggle-slider"></span></label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="save-button" :disabled="isSectionSaving('calculators')">
                        <i v-if="isSectionSaving('calculators')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>Сохранить</span>
                    </button>
                </form>
            </div>

            <!-- ========== 8: БОНУС-ИГРЫ ========== -->
            <div v-if="activeTab === 8" class="tab-panel">
                <form @submit.prevent="saveGames" class="settings-form">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-dice"></i> Бонус-игры</h3>
                        <div class="cards-grid">
                            <div v-for="(game, key) in gamesForm" :key="key" class="feature-card" :class="{ 'is-disabled': !game.is_visible }" :style="{ backgroundImage: game.gradient }">
                                <div class="card-icon"><i :class="game.icon"></i></div>
                                <div class="card-info">
                                    <h4>{{ game.title }}</h4>
                                    <p>{{ game.description }}</p>
                                    <div class="card-meta"><span>🎁 {{ game.prize }}</span><span>🔄 {{ game.attempts || 'Без лимита' }}</span></div>
                                </div>
                                <label class="toggle-switch"><input type="checkbox" v-model="game.is_visible"><span class="toggle-slider"></span></label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="save-button" :disabled="isSectionSaving('games')">
                        <i v-if="isSectionSaving('games')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>Сохранить</span>
                    </button>
                </form>
            </div>

            <!-- ========== 9: ГЛАВНОЕ МЕНЮ ========== -->
            <div v-if="activeTab === 9" class="tab-panel">
                <form @submit.prevent="saveMainMenu" class="settings-form">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fa-solid fa-compass"></i> Настройка главного меню</h3>
                        <div class="alert-info"><i class="fa-solid fa-circle-info"></i> Здесь вы можете изменить названия и иконки пунктов нижнего меню приложения.</div>
                        <div class="main-menu-grid">
                            <div v-for="(item, key) in mainMenuForm" :key="key" class="main-menu-card" :class="{ 'is-disabled': !item.is_visible }">
                                <div class="card-header">
                                    <div class="preview-icon">
                                        <img :src="`/images/shop/${defaultMenuIcons[key]}`" :alt="item.title" @error="$event.target.style.display='none'">
                                    </div>
                                    <div class="card-title">{{ item.title }} </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" v-model="item.is_visible" @change="markDirty('main_menu_items')">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <div v-if="item.is_visible" class="card-fields">
                                    <div class="form-field">
                                        <label>Название пункта</label>
                                        <input type="text" v-model="item.title" @input="markDirty('main_menu')" maxlength="20">
                                    </div>
                                    <div class="form-field">
                                        <label>Иконка пункта</label>
                                        <div class="icon-upload-wrapper">
                                            <div class="icon-preview-small">
                                                <img v-if="mainMenuPreviews[key] || item.img" :src="mainMenuPreviews[key] || (item.img.startsWith('/') ? item.img : `/images/menu/${item.img}`)" :alt="item.title">
                                                <i v-else class="fa-solid fa-image"></i>
                                            </div>
                                            <div class="icon-actions">
                                                <label class="upload-btn small">
                                                    <input type="file" accept="image/png, image/jpeg, image/svg+xml" @change="handleMainMenuIconUpload($event, key)">
                                                    <i class="fa-solid fa-upload"></i>
                                                    <span>{{ (mainMenuForm[key].img && mainMenuForm[key].img !== defaultMenuIcons[key]) ? 'Заменить' : 'Загрузить' }}</span>
                                                </label>
                                                <button v-if="mainMenuForm[key].img && mainMenuForm[key].img !== defaultMenuIcons[key]" type="button" class="reset-btn small" @click="resetMainMenuIcon(key)" title="Удалить кастомную иконку и вернуть стандартную">
                                                    <i class="fa-solid fa-rotate-left"></i><span>Сбросить</span>
                                                </button>
                                            </div>
                                        </div>
                                        <span class="field-hint">Рекомендуемый размер: 64×64 px (PNG, JPG или SVG)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="save-button" :disabled="isSectionSaving('main_menu_items')">
                        <i v-if="isSectionSaving('main_menu_items')" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-check"></i>
                        <span>{{ isSectionSaving('main_menu_items') ? 'Сохранение...' : 'Сохранить' }}</span>
                    </button>
                </form>
            </div>

        </div>
    </div>
</template>

<script>
import {useTenantSettings} from '@/MobileClient/Composables/useTenantSettings.js';
import { themeSchemes } from '@/MobileClient/constants/themeSchemes.js';

export default {
    name: 'TenantSettingsPage',

    setup() {
        const settings = useTenantSettings();
        return {...settings};
    },

    data() {
        return {
            activeTab: 0,
            activePwaSubTab: 'general',
            mainMenuPreviews: {},
            defaultMenuIcons: {
                shop: 'shop.png', basket: 'basket.png', profile: 'profile.png',
                booking: 'tables.png', history: 'history.png', chat: 'chat.png',
                events: 'events.png', about: 'contacts.png', referral: 'referral.png',
            },
            tabs: [
                {key: 'basic', title: 'Основное', icon: 'fa-solid fa-building', section: 'company'},
                {key: 'pwa', title: 'PWA приложение', icon: 'fa-solid fa-mobile-screen', section: 'pwa'},
                {key: 'shop', title: 'Магазин', icon: 'fa-solid fa-store', section: 'shop'},
                {key: 'cashback', title: 'Баллы', icon: 'fa-solid fa-coins', section: 'cashback'},
                {key: 'interactive', title: 'Интерактив', icon: 'fa-solid fa-gamepad', section: 'interactive'},
                {key: 'tables', title: 'Столики', icon: 'fa-solid fa-utensils', section: 'tables'},
                {key: 'menu', title: 'Пункты бокового меню', icon: 'fa-solid fa-bars', section: 'sidebar-menu'},
                {key: 'calculators', title: 'Калькуляторы', icon: 'fa-solid fa-calculator', section: 'calculators'},
                {key: 'games', title: 'Бонус-игры', icon: 'fa-solid fa-dice', section: 'games'},
                {key: 'main_menu', title: 'Пункты главного меню', icon: 'fa-solid fa-bars', section: 'main-menu'},
                // 🆕 CRM вкладка удалена
            ],
            pwaSubTabs: [
                {key: 'general', title: 'Основное', icon: 'fa-solid fa-info-circle'},
                {key: 'appearance', title: 'Внешний вид', icon: 'fa-solid fa-palette'},
                {key: 'icons', title: 'Иконки', icon: 'fa-solid fa-icons'},
                {key: 'screenshots', title: 'Скриншоты', icon: 'fa-solid fa-camera'},
                {key: 'shortcuts', title: 'Шорткаты', icon: 'fa-solid fa-bolt'},
            ],

            pwaForm: { name: null, short_name: null, description: null, theme_color: '#ff8a00', background_color: '#ffffff', orientation: 'portrait', display: 'standalone', lang: 'ru', categories: ['shopping', 'food', 'business'], icons: { icon_192: null, icon_512: null, icon_192_maskable: null, icon_512_maskable: null }, screenshots: { mobile: null, desktop: null }, shortcuts: { menu: {enabled: true, name: 'Меню', short_name: 'Меню', url: '/pwa/#/menu', icon: null}, cart: {enabled: true, name: 'Корзина', short_name: 'Корзина', url: '/pwa/#/cart', icon: null}, cashback: {enabled: true, name: 'Кэшбэк', short_name: 'Кэшбэк', url: '/pwa/#/cashback', icon: null}, wheel: { enabled: true, name: 'Колесо', short_name: 'Колесо', url: '/pwa/#/wheel-classic', icon: null } } },
            iconPreviews: {}, screenshotPreviews: {}, shortcutIconPreviews: {},

            companyForm: { id: null, title: null, description: null, phones: ['+7'], email: null, links: {vk: null, inst: null, map_link: null, site: null}, schedule: [ {day: 'Понедельник', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной'}, {day: 'Вторник', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной'}, {day: 'Среда', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной'}, {day: 'Четверг', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной'}, {day: 'Пятница', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной'}, {day: 'Суббота', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной'}, {day: 'Воскресенье', start_at: '08:00', end_at: '20:00', closed: false, closed_comment: 'Выходной'} ] },

            // 🆕 Обновленная структура shopForm
            shopForm: {
                is_disabled: false, disabled_text: null, shop_display_type: 0, is_product_list: false,
                can_buy_after_closing: false, need_hide_disabled_products: true, min_price: 80,
                delivery_price_text: null, need_automatic_delivery_request: true, need_hide_delivery_period: false,
                min_base_delivery_price: 0, price_per_km: 80, free_shipping_starts_from: 0,
                shop_coords: '0,0', map_tiler: null, payment_info: null, need_pay_after_call: false,
                can_use_cash: true, can_use_card: true,
                // 🆕 СБП с поддержкой нескольких банков
                sbp_banks: {
                    tinkoff: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                    sber: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                    psb: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                    vtb: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                    yandex: { enabled: false, terminal_key: '', terminal_password: '', tax: 'osn', vat: 'none' },
                },
                need_promo_code: true, need_bonuses_section: true, need_person_counter: true, need_health_restrictions: true,
                allow_delivery: true, allow_pickup: true, // 🆕 Способы получения
                address: '', // 🆕 Перенесено из companyForm
                manager: { name: '', phone: '', email: '', social_link: '' }, // 🆕 Расширенный менеджер
                default_theme_scheme: 'default',
            },

            // 🆕 Обновленная структура cashbackForm
            cashbackForm: {
                max_cashback_use_percent: 15, level_1: 0, level_2: 0, level_3: 0,
                expiration_period: 'never', expiration_percent: 100,
                notify_expiration: false, notify_days_before: 3,
            },

            certificateForm: { title: 'Подарочный сертификат', description: '500 рублей на CashBack', amount: 500, type: 'cashback', is_active: false },
            coffeeForm: { enabled: true, max: 7, rules: '1. За каждую покупку кофе — 1 отметка.\n2. После 7 кружек — 1 кофе бесплатно.\n3. Отметки действуют 30 дней.\n4. Бесплатный кофе нельзя обменять на деньги.' },
            tablesForm: { max_tables: 0, need_table_list: false },
            menuForm: {}, calculatorsForm: {}, gamesForm: {},
            mainMenuForm: {},
            availableSchemes: themeSchemes,
        };
    },

    async mounted() {
        try { await this.initForms(); } catch (error) { console.error('Ошибка загрузки:', error); }
    },

    methods: {
        changeActiveTab(index) {
            this.activeTab = index;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        getBankName(key) {
            const names = { tinkoff: 'Тинькофф', sber: 'Сбербанк', psb: 'Промсвязьбанк (ПСБ)', vtb: 'ВТБ', yandex: 'Яндекс.Деньги (ЮKassa)' };
            return names[key] || key;
        },

        async initForms() {
            const tenant = window.Tenant;
            if (!tenant) return;
            const settings = tenant.settings || {};

            this.companyForm.title = tenant.name || null;
            this.companyForm.description = tenant.description || null;
            const companyMeta = settings.company || {};
            this.companyForm.phones = companyMeta.phones || ['+7'];
            this.companyForm.email = companyMeta.email || null;
            this.companyForm.links = companyMeta.links || { vk: null, inst: null, map_link: null, site: null };
            if (companyMeta.schedule?.length >= 7) this.companyForm.schedule = companyMeta.schedule;

            this.shopForm = { ...this.shopForm, ...(settings.shop || {}) };
            // Миграция старых данных менеджера, если они были в старом формате
            if (typeof this.shopForm.manager === 'string' || !this.shopForm.manager.name) {
                this.shopForm.manager = { name: '', phone: '', email: '', social_link: this.shopForm.manager?.link || '' };
            }
            // Инициализация структуры СБП банков с сохранением старых данных, если они были
            if (settings.shop?.sbp) {
                this.shopForm.sbp_banks.tinkoff = { ...this.shopForm.sbp_banks.tinkoff, ...(settings.shop.sbp.tinkoff || {}) };
            }

            this.cashbackForm = { ...this.cashbackForm, ...(settings.cashback || {}) };
            this.certificateForm = { ...this.certificateForm, ...(settings.init_certificate || {}) };
            this.coffeeForm = { ...this.coffeeForm, ...(settings.coffee || {}) };
            this.tablesForm = { ...this.tablesForm, ...(settings.tables || {}) };
            this.menuForm = JSON.parse(JSON.stringify(settings.menu_items || {}));
            this.calculatorsForm = JSON.parse(JSON.stringify(settings.food_calculators || {}));
            this.gamesForm = JSON.parse(JSON.stringify(settings.bonus_games || {}));

            const mainMenuData = settings.main_menu_items || {};
            this.mainMenuForm = JSON.parse(JSON.stringify(mainMenuData));
            this.mainMenuPreviews = {};
            Object.keys(this.mainMenuForm).forEach(key => {
                if (this.mainMenuForm[key].img) this.mainMenuPreviews[key] = this.mainMenuForm[key].img;
            });

            try {
                const response = await axios.get('/admin/tenant-settings/pwa');
                const pwaData = response.data.settings || {};
                this.pwaForm = { ...this.pwaForm, ...pwaData };
                this.iconPreviews = pwaData.icons_urls || {};
                this.screenshotPreviews = pwaData.screenshots_urls || {};
                this.shortcutIconPreviews = pwaData.shortcuts_icons_urls || {};
            } catch (error) { console.error('Ошибка загрузки PWA настроек:', error); }
        },

        formatDate(date) { return new Date(date).toLocaleDateString('ru-RU'); },

        normalizeTelegramLink() {
            const link = this.shopForm.manager.social_link;
            if (!link || link.includes('https://t.me') || link.includes('https://vk.com')) return;
            if (link.startsWith('@')) this.shopForm.manager.social_link = 'https://t.me/' + link.slice(1);
            else if (!link.startsWith('https://') && /^[a-zA-Z0-9_]+$/.test(link)) this.shopForm.manager.social_link = 'https://t.me/' + link;
        },

        async downloadTablesPdf() {
            this.$notify?.({ title: 'Генерация', text: 'Подготавливаем PDF файл...', type: 'info' });
            try {
                // Замените URL на ваш реальный endpoint генерации PDF
                const response = await axios.get('/admin/tenant-settings/tables/download-qr-pdf', { responseType: 'blob' });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `tables-qr-codes-${Date.now()}.pdf`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                this.$notify?.({ title: 'Успешно', text: 'PDF скачан', type: 'success' });
            } catch (error) {
                console.error('Ошибка скачивания PDF:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сгенерировать PDF', type: 'error' });
            }
        },

        async resetMainMenuIcon(key) {
            try {
                const response = await axios.post(`/admin/tenant-settings/main-menu/reset-icon`, { menu_key: key });
                if (response.data.success) {
                    this.mainMenuForm[key].img = response.data.default_name;
                    this.mainMenuPreviews[key] = response.data.img;
                    this.markDirty('main_menu_items');
                    this.$notify?.({ title: 'Сброшено', text: response.data.message, type: 'success' });
                }
            } catch (error) {
                console.error('Ошибка сброса иконки:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сбросить иконку', type: 'error' });
            }
        },

        async handleMainMenuIconUpload(event, key) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) { this.$notify?.({title: 'Ошибка', text: 'Файл слишком большой (макс. 2MB)', type: 'error'}); return; }

            this.mainMenuPreviews[key] = URL.createObjectURL(file);
            this.markDirty('main_menu_items');

            const formData = new FormData();
            formData.append('icon', file);
            formData.append('menu_key', key);

            try {
                const response = await axios.post(`/admin/tenant-settings/main-menu/upload-icon`, formData, { headers: {'Content-Type': 'multipart/form-data'} });
                this.mainMenuForm[key].img = response.data.filename;
                this.$notify?.({title: 'Успешно', text: 'Иконка обновлена', type: 'success'});
            } catch (error) {
                console.error('Ошибка загрузки иконки меню:', error);
                this.$notify?.({title: 'Ошибка', text: 'Не удалось загрузить иконку', type: 'error'});
            }
        },

        // ... (Методы handleIconUpload, handleScreenshotUpload, handleShortcutIconUpload, getIconPreview, getScreenshotPreview, getShortcutIcon остаются без изменений) ...
        getIconPreview(key) { return this.iconPreviews[key] || null; },
        getScreenshotPreview(key) { return this.screenshotPreviews[key] || null; },
        getShortcutIcon(key) { const icons = { menu: 'fa-solid fa-bars', cart: 'fa-solid fa-cart-shopping', cashback: 'fa-solid fa-coins', wheel: 'fa-solid fa-dharmachakra' }; return icons[key] || 'fa-solid fa-bolt'; },

        async handleIconUpload(event, key, expectedWidth, expectedHeight) {
            const file = event.target.files[0]; if (!file) return;
            if (file.size > 2 * 1024 * 1024) { this.$notify?.({title: 'Ошибка', text: 'Файл слишком большой (макс. 2MB)', type: 'error'}); return; }
            const img = new Image(); img.src = URL.createObjectURL(file); await new Promise(resolve => img.onload = resolve);
            if (img.width !== expectedWidth || img.height !== expectedHeight) { this.$notify?.({ title: 'Неверный размер', text: `Требуется ${expectedWidth}×${expectedHeight} px`, type: 'warning' }); }
            this.iconPreviews[key] = URL.createObjectURL(file);
            const formData = new FormData(); formData.append('icon', file); formData.append('type', key);
            try {
                const response = await axios.post(`/admin/tenant-settings/pwa/upload-icon`, formData, { headers: {'Content-Type': 'multipart/form-data'} });
                this.pwaForm.icons[key] = response.data.filename; this.markDirty('pwa');
                this.$notify?.({title: 'Успешно', text: 'Иконка загружена', type: 'success'});
            } catch (error) { this.$notify?.({title: 'Ошибка', text: 'Не удалось загрузить иконку', type: 'error'}); }
        },

        async handleScreenshotUpload(event, key) {
            const file = event.target.files[0]; if (!file) return;
            if (file.size > 5 * 1024 * 1024) { this.$notify?.({title: 'Ошибка', text: 'Файл слишком большой (макс. 5MB)', type: 'error'}); return; }
            this.screenshotPreviews[key] = URL.createObjectURL(file);
            const formData = new FormData(); formData.append('screenshot', file); formData.append('type', key);
            try {
                const response = await axios.post(`/admin/tenant-settings/pwa/upload-screenshot`, formData, { headers: {'Content-Type': 'multipart/form-data'} });
                this.pwaForm.screenshots[key] = response.data.filename; this.markDirty('pwa');
                this.$notify?.({title: 'Успешно', text: 'Скриншот загружен', type: 'success'});
            } catch (error) { this.$notify?.({title: 'Ошибка', text: 'Не удалось загрузить скриншот', type: 'error'}); }
        },

        async handleShortcutIconUpload(event, shortcutKey) {
            const file = event.target.files[0]; if (!file) return;
            this.shortcutIconPreviews[shortcutKey] = URL.createObjectURL(file);
            const formData = new FormData(); formData.append('icon', file); formData.append('type', `shortcut_${shortcutKey}`);
            try {
                const response = await axios.post(`/admin/tenant-settings/pwa/upload-icon`, formData, { headers: {'Content-Type': 'multipart/form-data'} });
                this.pwaForm.shortcuts[shortcutKey].icon = response.data.filename; this.markDirty('pwa');
                this.$notify?.({title: 'Успешно', text: 'Иконка загружена', type: 'success'});
            } catch (error) { this.$notify?.({title: 'Ошибка', text: 'Не удалось загрузить', type: 'error'}); }
        },

        async saveMainMenu() {
            try {
                await this.saveMainMenuSettings(this.mainMenuForm);
                this.$notify?.({ title: 'Успешно', text: 'Главное меню обновлено', type: 'success' });
            } catch (e) {
                console.error("Ошибка сохранения главного меню:", e);
                this.$notify?.({ title: 'Ошибка', text: e.response?.data?.message || 'Не удалось сохранить', type: 'error' });
            }
        },

        async saveCompany() {
            try {
                const payload = {
                    name: this.companyForm.title, description: this.companyForm.description,
                    meta: { company: { phones: this.companyForm.phones, email: this.companyForm.email, links: this.companyForm.links, schedule: this.companyForm.schedule } }
                };
                await this.saveBasicInfo(payload);
                this.$notify?.({ title: 'Успешно', text: 'Основная информация обновлена', type: 'success' });
                if(window.Tenant) { window.Tenant.name = this.companyForm.title; window.Tenant.description = this.companyForm.description; }
            } catch (e) { this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' }); }
        },

        async saveShop() {
            try {
                await this.saveShopSettings(this.shopForm);
                this.$notify?.({ title: 'Успешно', text: 'Магазин обновлён', type: 'success' });
            } catch (e) { this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' }); }
        },

        async saveCashback() {
            try {
                await this.saveCashbackSettings({ ...this.cashbackForm, init_certificate: this.certificateForm });
                this.$notify?.({ title: 'Успешно', text: 'Баллы обновлены', type: 'success' });
            } catch (e) { this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' }); }
        },

        async saveInteractive() {
            try { await this.saveInteractiveSettings({ coffee: this.coffeeForm }); this.$notify?.({ title: 'Успешно', text: 'Интерактив обновлён', type: 'success' }); }
            catch (e) { this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' }); }
        },

        async saveTables() {
            try { await this.saveTablesSettings({ tables: this.tablesForm }); this.$notify?.({ title: 'Успешно', text: 'Столики обновлены', type: 'success' }); }
            catch (e) { this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' }); }
        },

        async saveMenu() {
            try { await this.saveMenuSettings(this.menuForm); this.$notify?.({ title: 'Успешно', text: 'Меню обновлено', type: 'success' }); }
            catch (e) { this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' }); }
        },

        async saveCalculators() {
            try { await this.saveCalculatorsSettings(this.calculatorsForm); this.$notify?.({ title: 'Успешно', text: 'Калькуляторы обновлены', type: 'success' }); }
            catch (e) { this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' }); }
        },

        async saveGames() {
            try { await this.saveGamesSettings(this.gamesForm); this.$notify?.({ title: 'Успешно', text: 'Игры обновлены', type: 'success' }); }
            catch (e) { this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить', type: 'error' }); }
        },
    },
};
</script>



<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ (SASS)
// ==========================================

$primary: #667eea;
$primary-dark: #5a67d8;
$bg: #ffffff;
$bg-secondary: #f8f9fa;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;
$text-muted-light: #9ca3af;
$success: #22c55e;
$danger: #ef4444;
$warning: #f59e0b;

.settings-page {
    min-height: 100vh;
    background: $bg-secondary;
    padding-bottom: 40px;
}

.page-header {
    background: $bg;
    padding: 24px 20px;
    border-bottom: 1px solid $border;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    color: $text;

    i {
        color: $primary;
    }
}

.page-subtitle {
    margin: 4px 0 0 0;
    color: $text-muted;
    font-size: 0.9rem;
}

.unsaved-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba($warning, 0.1);
    color: darken($warning, 15%);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;

    i {
        color: $warning;
    }
}

.tabs-container {
    background: $bg;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.tabs-scroll {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;

    &::-webkit-scrollbar {
        display: none;
    }
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    position: relative;

    i {
        font-size: 1rem;
    }

    &:hover {
        background: $bg-secondary;
        color: $text;
    }

    &.is-active {
        background: rgba($primary, 0.1);
        color: $primary;
        border-color: rgba($primary, 0.2);
    }

    &.is-dirty::after {
        content: '';
        position: absolute;
        top: 8px;
        right: 8px;
        width: 6px;
        height: 6px;
        background: $warning;
        border-radius: 50%;
    }
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    color: $text-muted;

    .loader-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 16px;
    }

    p {
        margin: 0;
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.tab-content {
    max-width: 900px;
    margin: 24px auto;
    padding: 0 16px;
}

.tab-panel {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-section {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 20px;
    color: $text;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;

    i {
        color: $primary;
        font-size: 1.2rem;
    }
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;

    .full-width {
        grid-column: 1 / -1;
    }
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;

    label {
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        display: flex;
        align-items: center;
        gap: 6px;

        i {
            color: $text-muted;
            font-size: 0.8rem;
        }
    }

    input, textarea, select {
        padding: 10px 14px;
        background: $bg;
        border: 1px solid $border;
        border-radius: 10px;
        font-size: 0.9rem;
        color: $text;
        transition: all 0.2s;
        font-family: inherit;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }

        &::placeholder {
            color: $text-muted-light;
        }
    }

    textarea {
        resize: vertical;
        min-height: 80px;
    }
}

.input-with-suffix {
    display: flex;
    align-items: stretch;

    input {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .input-suffix {
        padding: 10px 14px;
        background: $bg-secondary;
        border: 1px solid $border;
        border-left: none;
        border-radius: 0 10px 10px 0;
        font-weight: 600;
        color: $text-muted;
        display: flex;
        align-items: center;
    }
}

.toggle-switch {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;

    input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: relative;
        width: 44px;
        height: 24px;
        background: $border;
        border-radius: 12px;
        transition: all 0.3s;

        &::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    }

    input:checked + .toggle-slider {
        background: $primary;

        &::after {
            left: 22px;
        }
    }

    .toggle-label {
        font-size: 0.85rem;
        color: $text-muted;
    }
}

.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 0;
    border-bottom: 1px solid rgba($border, 0.5);

    &:last-child {
        border-bottom: none;
    }

    .toggle-info {
        flex: 1;

        h4 {
            margin: 0 0 4px;
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;

            i {
                color: $primary;
                font-size: 0.9rem;
            }
        }

        p {
            margin: 0;
            font-size: 0.8rem;
            color: $text-muted;
        }
    }
}

.toggle-list {
    display: flex;
    flex-direction: column;
}

.alert-info {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    background: rgba($primary, 0.05);
    border-left: 3px solid $primary;
    border-radius: 8px;
    font-size: 0.85rem;
    color: $text;
    margin: 16px 0;

    i {
        color: $primary;
        margin-top: 2px;
    }
}

.schedule-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.schedule-day {
    display: grid;
    grid-template-columns: 120px auto 1fr;
    gap: 12px;
    align-items: center;
    padding: 12px 16px;
    background: $bg-secondary;
    border-radius: 10px;

    &.is-closed {
        background: rgba($danger, 0.05);
    }
}

.schedule-day-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.time-inputs {
    display: flex;
    align-items: center;
    gap: 8px;

    input {
        padding: 8px 10px;
        border: 1px solid $border;
        border-radius: 8px;
        font-size: 0.85rem;
        background: $bg;

        &:focus {
            outline: none;
            border-color: $primary;
        }
    }

    span {
        color: $text-muted;
    }
}

.save-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba($primary, 0.3);

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba($primary, 0.4);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

// ==========================================
// СЕРТИФИКАТ (превью)
// ==========================================
.certificate-preview {
    position: relative;
    margin-bottom: 20px;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 2 / 1;
    background: $bg-secondary;

    .cert-bg {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cert-content {
        position: absolute;
        top: 30%;
        width: 100%;
        text-align: center;
        color: black;
    }

    .cert-title {
        font-size: 1rem;
        font-weight: bold;
        margin: 0 0 4px;
    }

    .cert-desc {
        font-size: 0.8rem;
        margin: 0 0 4px;
    }

    .cert-date {
        font-size: 0.7rem;
        margin: 0;
    }

    .cert-qr {
        position: absolute;
        right: 10%;
        bottom: -40%;
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        color: $text-muted;
    }
}

// ==========================================
// КАРТОЧКИ (калькуляторы, игры)
// ==========================================
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.feature-card {
    position: relative;
    padding: 20px;
    border-radius: 16px;
    color: white;
    background-size: cover;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 180px;
    transition: all 0.3s;

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.2);
    }

    > * {
        position: relative;
        z-index: 1;
    }

    &.is-disabled {
        opacity: 0.5;
        filter: grayscale(0.7);
    }
}

.card-emoji {
    font-size: 3rem;
}

.card-icon {
    font-size: 2rem;
    background: rgba(255, 255, 255, 0.2);
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-info {
    flex: 1;

    h4 {
        margin: 0 0 4px;
        font-size: 1.1rem;
    }

    p {
        margin: 0 0 8px;
        font-size: 0.85rem;
        opacity: 0.9;
    }
}

.card-meta {
    display: flex;
    gap: 12px;
    font-size: 0.8rem;
    opacity: 0.85;
}

.feature-card .toggle-switch {
    .toggle-slider {
        background: rgba(255, 255, 255, 0.3);
    }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .page-header {
        padding: 16px;
    }
    .page-title {
        font-size: 1.2rem;
    }
    .tab-content {
        padding: 0 12px;
        margin: 16px auto;
    }
    .form-section {
        padding: 16px;
    }
    .schedule-day {
        grid-template-columns: 1fr;
    }
    .form-grid {
        grid-template-columns: 1fr;
    }
    .cards-grid {
        grid-template-columns: 1fr;
    }
}

// ==========================================
// 🆕 ПОДТАБЫ
// ==========================================
.sub-tabs {
    display: flex;
    gap: 4px;
    padding: 8px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow-x: auto;

    &::-webkit-scrollbar {
        display: none;
    }
}

.sub-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;

    i {
        font-size: 0.9rem;
    }

    &:hover {
        background: $bg-secondary;
        color: $text;
    }

    &.is-active {
        background: rgba($primary, 0.1);
        color: $primary;
        border-color: rgba($primary, 0.2);
    }
}

// ==========================================
// 🆕 ПОДСКАЗКИ ПОЛЕЙ
// ==========================================
.field-hint {
    font-size: 0.75rem;
    color: $text-muted;
    margin-top: 4px;
}

.char-counter {
    position: absolute;
    bottom: 8px;
    right: 12px;
    font-size: 0.7rem;
    color: $text-muted;
}

// ==========================================
// 🆕 COLOR PICKER
// ==========================================
.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;

    input[type="color"] {
        width: 48px;
        height: 48px;
        padding: 2px;
        border: 1px solid $border;
        border-radius: 10px;
        cursor: pointer;
        background: $bg;

        &::-webkit-color-swatch-wrapper {
            padding: 2px;
        }

        &::-webkit-color-swatch {
            border: none;
            border-radius: 6px;
        }
    }

    .color-text {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid $border;
        border-radius: 10px;
        font-family: monospace;
        font-size: 0.9rem;
        text-transform: uppercase;
    }
}

// ==========================================
// 🆕 ПРЕВЬЮ PWA
// ==========================================
.pwa-preview {
    margin-top: 24px;
    padding: 20px;
    background: $bg-secondary;
    border-radius: 12px;

    h4 {
        margin: 0 0 16px;
        font-size: 0.95rem;
        color: $text;
    }
}

.preview-browser {
    max-width: 320px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border-top: 3px solid;

    .preview-toolbar {
        padding: 8px 12px;
        display: flex;
        align-items: center;
    }

    .preview-url {
        flex: 1;
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        color: white;
        text-align: center;
    }

    .preview-content {
        padding: 24px;
        text-align: center;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .preview-app-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .preview-app-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: $text;
    }
}

// ==========================================
// 🆕 ЗАГРУЗКА ИКОНОК
// ==========================================
.icons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}

.icon-upload-card {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.icon-preview {
    width: 100%;
    aspect-ratio: 1;
    max-width: 120px;
    margin: 0 auto;
    border-radius: 20px;
    overflow: hidden;
    background: $bg;
    border: 1px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    &.maskable {
        border-radius: 0;

        .mask-overlay {
            position: absolute;
            inset: 0;
            border: 3px dashed rgba($primary, 0.5);
            border-radius: 50%;
            pointer-events: none;
        }
    }
}

.icon-placeholder {
    color: $text-muted-light;
    font-size: 2rem;
}

.icon-info {
    text-align: center;

    h5 {
        margin: 0 0 4px;
        font-size: 0.9rem;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.75rem;
        color: $text-muted;
    }
}

.upload-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    background: $bg;
    border: 1px dashed $border;
    border-radius: 8px;
    color: $primary;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    input[type="file"] {
        display: none;
    }

    &:hover {
        background: rgba($primary, 0.05);
        border-color: $primary;
    }

    &.small {
        padding: 8px;
        font-size: 0.8rem;
    }
}

// ==========================================
// 🆕 ЗАГРУЗКА СКРИНШОТОВ
// ==========================================
.screenshots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.screenshot-upload-card {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.screenshot-preview {
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    background: $bg;
    border: 1px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;

    &.mobile {
        aspect-ratio: 375 / 667;
    }

    &.desktop {
        aspect-ratio: 16 / 9;
    }

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.screenshot-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: $text-muted-light;

    i {
        font-size: 2.5rem;
    }

    span {
        font-size: 0.8rem;
    }
}

.screenshot-info {
    h5 {
        margin: 0 0 4px;
        font-size: 0.95rem;
        color: $text;
    }

    p {
        margin: 0;
        font-size: 0.75rem;
        color: $text-muted;
    }
}

// ==========================================
// 🆕 ШОРТКАТЫ
// ==========================================
.shortcuts-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.shortcut-card {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;

    &.is-disabled {
        opacity: 0.6;
    }
}

.shortcut-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;

    .shortcut-icon-preview {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
    }

    .shortcut-title {
        flex: 1;
        font-weight: 600;
        color: $text;
    }
}

.shortcut-fields {
    padding: 0 16px 16px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    border-top: 1px solid $border;
    padding-top: 16px;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 768px) {
    .icons-grid,
    .screenshots-grid {
        grid-template-columns: 1fr;
    }

    .sub-tabs {
        padding: 6px;
    }

    .sub-tab {
        padding: 8px 12px;
        font-size: 0.8rem;
    }

    .preview-browser {
        max-width: 100%;
    }
}

.main-menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.main-menu-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s;

    &.is-disabled {
        opacity: 0.6;
        background: var(--bs-secondary-bg);
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .preview-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--bs-secondary-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-primary);
        font-size: 1.2rem;
        flex-shrink: 0;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    .card-title {
        flex: 1;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .card-fields {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--bs-border-color);
    }
}

// ==========================================
// 🆕 ЗАГРУЗКА ИКОНОК ГЛАВНОГО МЕНЮ
// ==========================================
.icon-upload-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.icon-preview-small {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.2rem;
    flex-shrink: 0;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: contain; // contain лучше для иконок меню, чтобы не обрезались
        padding: 4px;
    }
}

// ==========================================
// 🆕 ДЕЙСТВИЯ С ИКОНКОЙ (Загрузка + Сброс)
// ==========================================
.icon-upload-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.icon-preview-small {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.2rem;
    flex-shrink: 0;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: contain; // contain лучше для иконок, чтобы не обрезались
        padding: 4px;
    }
}

.icon-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.reset-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 10px;
    background: #ffffff;
    border: 1px dashed #ef4444; // Красная пунктирная рамка для действия "сброс"
    border-radius: 6px;
    color: #ef4444;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: rgba(239, 68, 68, 0.05);
        border-style: solid;
    }

    &.small {
        padding: 6px 10px;
        font-size: 0.8rem;
    }
}

$primary: #667eea;
$primary-dark: #5a67d8;
$bg: #ffffff;
$bg-secondary: #f8f9fa;
$border: #e5e7eb;
$text: #1f2937;
$text-muted: #6b7280;
$text-muted-light: #9ca3af;
$success: #22c55e;
$danger: #ef4444;
$warning: #f59e0b;

// ... (вставьте сюда все ваши существующие стили из исходного файла) ...

// 🆕 Стили для СБП банков
.sbp-banks-wrapper {
    margin-top: 16px;
}

.sbp-bank-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.2s;

    &.is-active {
        border-color: var(--bs-primary);
        box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.1);
    }
}

.sbp-bank-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.sbp-bank-name {
    font-weight: 600;
    font-size: 1rem;
    color: var(--text);
}

.sbp-bank-fields {
    padding-top: 12px;
    border-top: 1px dashed var(--border);
    animation: fadeIn 0.3s ease;
}

// 🆕 Кнопка скачивания PDF
.btn-download-pdf {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
    }

    i {
        font-size: 1.1rem;
    }
}
</style>
