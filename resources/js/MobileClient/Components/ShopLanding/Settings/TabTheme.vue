<template>
    <div class="settings-panel fade-in">
        <div class="panel-header">
            <h3>Цветовая схема</h3>
            <p>Настройте цвета вашего магазина. Изменения сразу видны в предпросмотре.</p>
        </div>

        <!-- Живой предпросмотр -->
        <div class="theme-preview">
            <div class="preview-card" :style="previewStyles">
                <div class="preview-header" :style="{ background: `linear-gradient(135deg, ${theme?.primary || '#3b82f6'}, ${theme?.primaryLight || '#60a5fa'})` }">
                    <span>Предпросмотр</span>
                </div>
                <div class="preview-body">
                    <button class="preview-btn" :style="{ background: `linear-gradient(135deg, ${theme?.primary || '#3b82f6'}, ${theme?.primaryLight || '#60a5fa'})` }">
                        Кнопка
                    </button>
                </div>
            </div>
        </div>

        <div class="color-grid">
            <div v-for="(color, key) in colorFields" :key="key" class="color-item">
                <label>{{ color.label }}</label>
                <div class="color-input-wrapper">
                    <input type="color" v-model="theme[key]">
                    <input type="text" v-model="theme[key]" class="hex-input" maxlength="7">
                </div>
                <span class="color-hint">{{ color.hint }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TabTheme",
    props: {
        theme: {
            type: Object,
            required: true,
            // 🆕 Дефолтное значение на случай, если пропс не передан
            default: () => ({})
        }
    },
    computed: {
        previewStyles() {
            // 🆕 Защита от undefined с фоллбэками
            return {
                '--preview-bg': this.theme?.light || '#f9fafb',
                '--preview-text': this.theme?.dark || '#1f2937',
            };
        },
        colorFields() {
            return {
                primary: { label: 'Основной цвет', hint: 'Кнопки, акценты, ссылки' },
                primaryDark: { label: 'Тёмный оттенок', hint: 'Hover-эффекты, активные состояния' },
                primaryLight: { label: 'Светлый оттенок', hint: 'Градиенты, светлые акценты' },
                accent: { label: 'Акцентный цвет', hint: 'Звёзды рейтинга, бейджи' },
                dark: { label: 'Тёмный цвет', hint: 'Текст, тёмные секции' },
                light: { label: 'Светлый фон', hint: 'Фон страницы, карточки' }
            };
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.settings-panel {
    background: $card-bg;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid $border;
}

.fade-in {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.panel-header {
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid $border;

    h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0;
    }
}

.theme-preview {
    margin-bottom: 24px;
    padding: 20px;
    background: $bg;
    border-radius: 12px;
    border: 1px dashed $border;
}

.preview-card {
    max-width: 300px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background: var(--preview-bg, white);
}

.preview-header {
    padding: 16px;
    color: white;
    font-weight: 700;
    text-align: center;
}

.preview-body {
    padding: 16px;
    text-align: center;
}

.preview-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    cursor: default;
}

.color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.color-item {
    label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;
    }
}

.color-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;

    input[type="color"] {
        width: 44px;
        height: 44px;
        border: 1px solid $border;
        border-radius: 10px;
        cursor: pointer;
        padding: 2px;
        background: $card-bg;
    }

    .hex-input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid $border;
        border-radius: 10px;
        font-family: monospace;
        font-size: 0.85rem;
        text-transform: uppercase;

        &:focus {
            outline: none;
            border-color: $primary;
        }
    }
}

.color-hint {
    display: block;
    font-size: 0.75rem;
    color: $text-muted;
    margin-top: 4px;
}
</style>
