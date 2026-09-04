<template>
    <form @submit.prevent="onSubmit" class="settings-form">

        <!-- 1. Основные Meta-теги -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-search"></i> Основные Meta-теги</h3>
            <div class="form-grid">
                <div class="form-field full-width">
                    <label>SEO Заголовок (Title)</label>
                    <input
                        type="text"
                        v-model="form.meta.title"
                        maxlength="70"
                        placeholder="Например: Доставка пиццы в Донецке | Счастье есть"
                        @input="emitDirty"
                    >
                    <span class="field-hint" :class="{ 'text-warning': (form.meta.title?.length || 0) > 60 }">
            Рекомендуется до 60 символов. Сейчас: <strong>{{ form.meta.title?.length || 0 }}/70</strong>
          </span>
                </div>

                <div class="form-field full-width">
                    <label>SEO Описание (Description)</label>
                    <textarea
                        v-model="form.meta.description"
                        rows="3"
                        maxlength="160"
                        placeholder="Краткое описание для поисковых систем..."
                        @input="emitDirty"
                    ></textarea>
                    <span class="field-hint" :class="{ 'text-warning': (form.meta.description?.length || 0) > 150 }">
            Рекомендуется до 150 символов. Сейчас: <strong>{{ form.meta.description?.length || 0 }}/160</strong>
          </span>
                </div>

                <div class="form-field full-width">
                    <label>Ключевые слова (Keywords)</label>
                    <input
                        type="text"
                        v-model="form.meta.keywords"
                        placeholder="пицца, доставка, донецк, еда"
                        @input="emitDirty"
                    >
                    <span class="field-hint">Через запятую. Поисковики почти не учитывают этот тег, но он иногда полезен.</span>
                </div>

                <div class="form-field">
                    <label>H1 Заголовок (если отличается от Title)</label>
                    <input type="text" v-model="form.meta.h1" placeholder="Оставьте пустым, чтобы использовать Title" @input="emitDirty">
                </div>

                <div class="form-field">
                    <label>Индексация (Robots)</label>
                    <select v-model="form.meta.robots" @change="emitDirty">
                        <option value="index, follow">Индексировать и переходить по ссылкам</option>
                        <option value="noindex, follow">Не индексировать, но переходить по ссылкам</option>
                        <option value="index, nofollow">Индексировать, но не переходить по ссылкам</option>
                        <option value="noindex, nofollow">Запретить всё (noindex, nofollow)</option>
                    </select>
                </div>

                <div class="form-field full-width">
                    <label>Canonical URL</label>
                    <input type="url" v-model="form.meta.canonical" placeholder="https://example.com/shop" @input="emitDirty">
                    <span class="field-hint">Главное зеркало страницы для защиты от дублей.</span>
                </div>
            </div>
        </div>

        <!-- 2. Социальные сети и предпросмотр (Open Graph / Twitter) -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-brands fa-vk"></i> Социальные сети и предпросмотр</h3>
            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Эти данные используются, когда ссылкой на ваше заведение делятся в ВКонтакте, Telegram или других мессенджерах.
            </div>

            <div class="form-grid">
                <div class="form-field full-width">
                    <label>OG Заголовок (для соцсетей)</label>
                    <input type="text" v-model="form.og.title" placeholder="Обычно дублирует SEO Title" @input="emitDirty">
                </div>
                <div class="form-field full-width">
                    <label>OG Описание</label>
                    <textarea v-model="form.og.description" rows="2" placeholder="Обычно дублирует SEO Description" @input="emitDirty"></textarea>
                </div>
                <div class="form-field">
                    <label>Ссылка на изображение для превью (OG Image)</label>
                    <input type="text" v-model="form.og.image" placeholder="https://.../cover.jpg" @input="emitDirty">
                    <span class="field-hint">Рекомендуемый размер: 1200x630 px.</span>
                </div>
                <div class="form-field">
                    <label>Тип контента (OG Type)</label>
                    <select v-model="form.og.type" @change="emitDirty">
                        <option value="website">Веб-сайт</option>
                        <option value="business.business">Бизнес / Заведение</option>
                        <option value="restaurant">Ресторан</option>
                        <option value="product">Товар</option>
                    </select>
                </div>
            </div>

            <h4 style="margin: 24px 0 12px; font-size: 1rem; color: var(--text);">
                <i class="fa-solid fa-share-nodes" style="color: var(--primary); margin-right: 8px;"></i> Ссылки на соцсети
            </h4>
            <div class="form-grid">
                <div class="form-field">
                    <label><i class="fa-brands fa-vk"></i> ВКонтакте</label>
                    <input type="url" v-model="form.social.vk" placeholder="https://vk.com/..." @input="emitDirty">
                </div>
                <div class="form-field">
                    <label><i class="fa-brands fa-telegram"></i> Telegram</label>
                    <input type="url" v-model="form.social.telegram" placeholder="https://t.me/..." @input="emitDirty">
                </div>
                <div class="form-field">
                    <label><i class="fa-brands fa-instagram"></i> Instagram</label>
                    <input type="url" v-model="form.social.instagram" placeholder="https://instagram.com/..." @input="emitDirty">
                </div>
                <div class="form-field">
                    <label><i class="fa-brands fa-whatsapp"></i> WhatsApp</label>
                    <input type="url" v-model="form.social.whatsapp" placeholder="https://wa.me/..." @input="emitDirty">
                </div>
            </div>
        </div>

        <!-- 3. Локальное SEO (Local Business) -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-map-pin"></i> Локальное SEO</h3>
            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Помогает поисковым системам лучше понимать географию вашего бизнеса для локальной выдачи.
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Город</label>
                    <input type="text" v-model="form.local.city" placeholder="Донецк" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>Регион / Область</label>
                    <input type="text" v-model="form.local.region" placeholder="Донецкая область" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>ИНН</label>
                    <input type="text" v-model="form.local.inn" placeholder="Для доверия поисковиков" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>ОГРН</label>
                    <input type="text" v-model="form.local.ogrn" placeholder="Для доверия поисковиков" @input="emitDirty">
                </div>
                <div class="form-field full-width">
                    <label>Ближайшие города (для охвата)</label>
                    <textarea
                        v-model="form.local.nearest_cities"
                        rows="2"
                        placeholder="Макеевка, Горловка, Ясиноватая..."
                        @input="emitDirty"
                    ></textarea>
                    <span class="field-hint">Через запятую. Помогает ранжироваться по запросам из соседних населенных пунктов.</span>
                </div>
            </div>
        </div>

        <!-- 4. Микроразметка Schema.org -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-code"></i> Микроразметка Schema.org</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label>Тип организации (@type)</label>
                    <!-- СТАЛО (правильный синтаксис) -->
                    <select v-model="form.schema['@type']" @change="emitDirty">
                        <option value="LocalBusiness">Местный бизнес (LocalBusiness)</option>
                        <option value="Restaurant">Ресторан / Кафе (Restaurant)</option>
                        <option value="Store">Магазин (Store)</option>
                        <option value="FoodEstablishment">Заведение питания (FoodEstablishment)</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Ценовой диапазон (priceRange)</label>
                    <input type="text" v-model="form.schema.priceRange" placeholder="₽₽ или 500-1500 ₽" @input="emitDirty">
                    <span class="field-hint">Используется для отображения в расширенных сниппетах Google/Яндекс.</span>
                </div>
            </div>
        </div>

        <!-- 5. Изображения и иконки -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-image"></i> Изображения и иконки</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label>Alt для обложки (Cover Alt)</label>
                    <input type="text" v-model="form.images.cover_alt" placeholder="Интерьер заведения Счастье есть" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>Alt для логотипа (Logo Alt)</label>
                    <input type="text" v-model="form.images.logo_alt" placeholder="Логотип Счастье есть" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>Цвет темы браузера (Theme Color)</label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="color" v-model="form.images.theme_color" @input="emitDirty" style="width: 50px; height: 38px; padding: 2px; border: 1px solid #ccc; border-radius: 6px;">
                        <input type="text" v-model="form.images.theme_color" placeholder="#ffffff" @input="emitDirty" style="flex: 1;">
                    </div>
                    <span class="field-hint">Цвет адресной строки в мобильных браузерах.</span>
                </div>
            </div>
        </div>

        <!-- 6. Sitemap и дополнительные настройки -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-sitemap"></i> Sitemap и индексация</h3>

            <div class="form-grid" style="margin-bottom: 20px;">
                <div class="form-field">
                    <label>Приоритет страницы (Priority)</label>
                    <select v-model="form.sitemap.priority" @change="emitDirty">
                        <option value="1.0">1.0 (Главная)</option>
                        <option value="0.8">0.8 (Высокий)</option>
                        <option value="0.5">0.5 (Средний)</option>
                        <option value="0.3">0.3 (Низкий)</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Частота обновления (Changefreq)</label>
                    <select v-model="form.sitemap.changefreq" @change="emitDirty">
                        <option value="always">Всегда</option>
                        <option value="hourly">Каждый час</option>
                        <option value="daily">Ежедневно</option>
                        <option value="weekly">Еженедельно</option>
                        <option value="monthly">Ежемесячно</option>
                    </select>
                </div>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Включать в Sitemap.xml</h4>
                    <p>Страница будет добавлена в файл карты сайта для поисковых роботов</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.sitemap.include_in_sitemap" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Запрет кеширования (Noarchive)</h4>
                    <p>Запретить поисковикам сохранять копию страницы в кеше</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.advanced.noarchive" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Запрет перевода (Notranslate)</h4>
                    <p>Запретить Google предлагать перевод этой страницы</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.advanced.notranslate" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- Кнопка сохранения -->
        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение SEO...' : 'Сохранить настройки SEO' }}</span>
        </button>
    </form>
</template>

<script>
export default {
    name: 'TabSeo',

    props: {
        form: {
            type: Object,
            required: true,
            // Убедитесь, что родительский компонент инициализирует эту структуру,
            // либо добавьте здесь default, если form приходит пустым
        },
        isSaving: {
            type: Boolean,
            default: false
        },
    },

    emits: ['save', 'mark-dirty'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'seo');
        },

        onSubmit() {
            // Перед отправкой можно сделать дополнительную очистку данных
            const cleanedForm = JSON.parse(JSON.stringify(this.form)); // Глубокая копия для очистки

            // Например, убрать лишние пробелы в keywords
            if (cleanedForm.meta.keywords) {
                cleanedForm.meta.keywords = cleanedForm.meta.keywords
                    .split(',')
                    .map(k => k.trim())
                    .filter(k => k.length > 0)
                    .join(', ');
            }

            this.$emit('save', cleanedForm);
        }
    },
};
</script>

<style scoped>
/* Переиспользуем стили из вашего примера + добавляем специфичные для SEO */

.text-warning {
    color: #d97706 !important; /* Янтарный цвет для предупреждения о превышении лимита */
}

/* Стили для поля выбора цвета */
input[type="color"] {
    -webkit-appearance: none;
    border: none;
    cursor: pointer;
}
input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
}
input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}

/* Адаптация alert-info из вашего примера, если его нет глобально */
.alert-info {
    background: rgba(59, 130, 246, 0.1);
    border-left: 4px solid #3b82f6;
    color: #1e40af;
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

/* Базовые стили формы (дублируем на случай, если они не глобальные) */
.settings-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-section {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 16px;
    padding: 24px;
}

.section-title {
    margin: 0 0 20px 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark, #0f0f14);
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-field.full-width {
    grid-column: 1 / -1;
}

.form-field label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark, #0f0f14);
}

.form-field input,
.form-field textarea,
.form-field select {
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    font-family: inherit;
}

.form-field input:focus,
.form-field textarea:focus,
.form-field select:focus {
    outline: none;
    border-color: var(--primary, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.field-hint {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 2px;
}

.toggle-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid rgba(0,0,0,0.04);
}

.toggle-row:last-child {
    border-bottom: none;
}

.toggle-info h4 {
    margin: 0 0 4px 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark, #0f0f14);
}

.toggle-info p {
    margin: 0;
    font-size: 0.85rem;
    color: #64748b;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: .3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

input:checked + .toggle-slider {
    background-color: var(--primary, #3b82f6);
}

input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.save-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    max-width: 300px;
    margin: 0 auto;
    padding: 14px 24px;
    background: var(--primary, #3b82f6);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.save-button:hover:not(:disabled) {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.save-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>
