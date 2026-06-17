<template>
    <div class="theme-picker-card card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3">

            <!-- Заголовок -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="picker-icon">
                        <i class="fa-solid fa-palette"></i>
                    </div>
                    <div>
                        <div class="fw-semibold small">Цветовая гамма</div>
                        <small class="text-muted">Выберите акцентный цвет</small>
                    </div>
                </div>
                <button
                    v-if="currentColor !== defaultColor"
                    class="btn btn-sm btn-link text-muted p-0"
                    @click="resetColor"
                    title="Сбросить к стандартному"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>

            <!-- Палитра цветов -->
            <div class="color-palette">
                <button
                    v-for="color in colors"
                    :key="color.name"
                    class="color-swatch"
                    :class="{
                        'active': currentColor === color.hex,
                        'is-light': isLightColor(color.hex)
                    }"
                    :style="{ backgroundColor: color.hex }"
                    @click="selectColor(color.hex)"
                    :title="color.name"
                    type="button"
                >
                    <i v-if="currentColor === color.hex" class="fa-solid fa-check"></i>
                </button>

                <!-- Кастомный цвет (color picker) -->
                <label class="color-swatch custom-color-picker" title="Выбрать свой цвет">
                    <input
                        type="color"
                        :value="currentColor"
                        @input="selectColor($event.target.value)"
                        class="custom-color-input"
                    >
                    <i class="fa-solid fa-droplet"></i>
                </label>
            </div>

            <!-- Превью -->
            <div class="color-preview mt-3 p-2 rounded-3" :style="previewStyle">
                <div class="d-flex align-items-center gap-2">
                    <div class="preview-dot"></div>
                    <small class="fw-semibold">Превью темы</small>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "ThemeColorPicker",

    data() {
        return {
            // Предустановленные цвета
            colors: [
                { name: 'Оранжевый', hex: '#ff8a00' },
                { name: 'Синий', hex: '#0d6efd' },
                { name: 'Зелёный', hex: '#198754' },
                { name: 'Красный', hex: '#dc3545' },
                { name: 'Фиолетовый', hex: '#6f42c1' },
                { name: 'Бирюзовый', hex: '#20c997' },
                { name: 'Розовый', hex: '#d63384' },
                { name: 'Тёмный', hex: '#212529' },
            ],
            defaultColor: '#ff8a00',
            currentColor: '#ff8a00',
        };
    },

    computed: {
        previewStyle() {
            return {
                background: `linear-gradient(135deg, ${this.currentColor}20 0%, ${this.currentColor}10 100%)`,
                borderColor: `${this.currentColor}40`,
                borderWidth: '1px',
                borderStyle: 'solid',
            };
        },
    },

    mounted() {
        this.loadSavedColor();
    },

    methods: {
        // Загрузка сохранённого цвета
        loadSavedColor() {
            const tenantSlug = window.Tenant?.slug || 'any';
            const saved = localStorage.getItem(`theme_color_${tenantSlug}`);
            if (saved) {
                this.currentColor = saved;
                this.applyColor(saved);
            }
        },

        // Выбор цвета
        selectColor(hex) {
            this.currentColor = hex;
            this.applyColor(hex);
            this.saveColor(hex);
        },

        // Сброс к стандартному
        resetColor() {
            this.selectColor(this.defaultColor);
        },

        // Применение цвета через CSS-переменные
        applyColor(hex) {
            const root = document.documentElement;

            // Основной цвет
            root.style.setProperty('--bs-primary', hex);

            // RGB версия для теней и прозрачностей
            const rgb = this.hexToRgb(hex);
            if (rgb) {
                root.style.setProperty('--bs-primary-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`);
            }

            // Светлая версия (для hover, focus)
            root.style.setProperty('--bs-primary-hover', this.adjustColor(hex, -15));
            root.style.setProperty('--bs-primary-light', `${hex}20`);
        },

        // Сохранение в localStorage
        saveColor(hex) {
            const tenantSlug = window.Tenant?.slug || 'any';
            localStorage.setItem(`theme_color_${tenantSlug}`, hex);
        },

        // Конвертация HEX в RGB
        hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },

        // Осветление/затемнение цвета
        adjustColor(color, percent) {
            const num = parseInt(color.replace('#', ''), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return '#' + (
                0x1000000 +
                (R < 255 ? (R < 0 ? 0 : R) : 255) * 0x10000 +
                (G < 255 ? (G < 0 ? 0 : G) : 255) * 0x100 +
                (B < 255 ? (B < 0 ? 0 : B) : 255)
            ).toString(16).slice(1);
        },

        // Проверка, светлый ли цвет (для контрастной галочки)
        isLightColor(hex) {
            const rgb = this.hexToRgb(hex);
            if (!rgb) return false;
            const brightness = (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1000;
            return brightness > 155;
        },
    },
};
</script>

<style scoped>
.theme-picker-card {
    background: var(--bs-body-bg);
    transition: all 0.3s ease;
}

.picker-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

/* Палитра цветов */
.color-palette {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.color-swatch {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    position: relative;
    padding: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.color-swatch:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.color-swatch.active {
    border-color: var(--bs-body-color);
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

.color-swatch.is-light {
    color: #212529;
}

/* Кастомный color picker */
.custom-color-picker {
    background: linear-gradient(135deg, #ff0000 0%, #00ff00 50%, #0000ff 100%);
    position: relative;
    overflow: hidden;
}

.custom-color-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

/* Превью */
.color-preview {
    transition: all 0.3s ease;
}

.preview-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--bs-primary);
    box-shadow: 0 0 8px var(--bs-primary);
}
</style>
