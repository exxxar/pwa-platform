<template>
    <div class="theme-scheme-picker">

        <!-- Заголовок -->
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="scheme-icon">
                <i class="fa-solid fa-swatchbook"></i>
            </div>
            <div>
                <div class="fw-semibold">Цветовая схема</div>
                <small class="text-muted">Выберите стиль оформления</small>
            </div>
        </div>

        <!-- Сетка тем -->
        <div class="schemes-grid">
            <button
                v-for="scheme in schemes"
                :key="scheme.id"
                class="scheme-card"
                :class="{ 'active': currentSchemeId === scheme.id }"
                @click="applyScheme(scheme)"
                type="button"
            >
                <!-- Превью цветов темы -->
                <div class="scheme-preview" :style="getPreviewStyle(scheme)">
                    <div class="preview-header"></div>
                    <div class="preview-body">
                        <div class="preview-line"></div>
                        <div class="preview-line short"></div>
                        <div class="preview-button"></div>
                    </div>
                </div>

                <!-- Название -->
                <div class="scheme-name">{{ scheme.name }}</div>

                <!-- Галочка активной темы -->
                <div v-if="currentSchemeId === scheme.id" class="scheme-check">
                    <i class="fa-solid fa-check"></i>
                </div>
            </button>
        </div>

    </div>
</template>

<script>
export default {
    name: "ThemeSchemePicker",

    data() {
        return {
            currentSchemeId: 'default',

            // Полные цветовые схемы
            schemes: [
                {
                    id: 'default',
                    name: 'Стандарт',
                    light: {
                        primary: '#ff8a00',
                        'primary-rgb': '255, 138, 0',
                        'body-bg': '#fffdf8',
                        'body-color': '#2c2c2c',
                        'secondary-bg': '#f8f9fa',
                        'border-color': '#dee2e6',
                    },
                    dark: {
                        primary: '#ff9500',
                        'primary-rgb': '255, 149, 0',
                        'body-bg': '#1a1a1a',
                        'body-color': '#e9ecef',
                        'secondary-bg': '#2d2d2d',
                        'border-color': '#495057',
                    }
                },
                {
                    id: 'ocean',
                    name: 'Океан',
                    light: {
                        primary: '#0d6efd',
                        'primary-rgb': '13, 110, 253',
                        'body-bg': '#f0f7ff',
                        'body-color': '#1a2b4a',
                        'secondary-bg': '#e7f1ff',
                        'border-color': '#b6d4fe',
                    },
                    dark: {
                        primary: '#3d8bfd',
                        'primary-rgb': '61, 139, 253',
                        'body-bg': '#0a1929',
                        'body-color': '#e0e7ef',
                        'secondary-bg': '#132f4c',
                        'border-color': '#1e4976',
                    }
                },
                {
                    id: 'forest',
                    name: 'Лес',
                    light: {
                        primary: '#198754',
                        'primary-rgb': '25, 135, 84',
                        'body-bg': '#f0f9f4',
                        'body-color': '#1a3a2a',
                        'secondary-bg': '#d1e7dd',
                        'border-color': '#a3cfbb',
                    },
                    dark: {
                        primary: '#20c997',
                        'primary-rgb': '32, 201, 151',
                        'body-bg': '#0f1f17',
                        'body-color': '#d4e8dc',
                        'secondary-bg': '#1a3a2a',
                        'border-color': '#2d5a3f',
                    }
                },
                {
                    id: 'sunset',
                    name: 'Закат',
                    light: {
                        primary: '#dc3545',
                        'primary-rgb': '220, 53, 69',
                        'body-bg': '#fff5f5',
                        'body-color': '#4a1a1a',
                        'secondary-bg': '#f8d7da',
                        'border-color': '#f1aeb5',
                    },
                    dark: {
                        primary: '#e35d6a',
                        'primary-rgb': '227, 93, 106',
                        'body-bg': '#1f0f0f',
                        'body-color': '#f0d4d7',
                        'secondary-bg': '#3a1a1a',
                        'border-color': '#5a2d2d',
                    }
                },
                {
                    id: 'royal',
                    name: 'Королевский',
                    light: {
                        primary: '#6f42c1',
                        'primary-rgb': '111, 66, 193',
                        'body-bg': '#faf5ff',
                        'body-color': '#2d1a4a',
                        'secondary-bg': '#e2d9f3',
                        'border-color': '#c5b3e6',
                    },
                    dark: {
                        primary: '#8b5cf6',
                        'primary-rgb': '139, 92, 246',
                        'body-bg': '#1a0f2e',
                        'body-color': '#e0d4f0',
                        'secondary-bg': '#2d1a4a',
                        'border-color': '#4a2d6e',
                    }
                },
                {
                    id: 'mono',
                    name: 'Монохром',
                    light: {
                        primary: '#212529',
                        'primary-rgb': '33, 37, 41',
                        'body-bg': '#ffffff',
                        'body-color': '#212529',
                        'secondary-bg': '#f8f9fa',
                        'border-color': '#dee2e6',
                    },
                    dark: {
                        primary: '#f8f9fa',
                        'primary-rgb': '248, 249, 250',
                        'body-bg': '#000000',
                        'body-color': '#f8f9fa',
                        'secondary-bg': '#1a1a1a',
                        'border-color': '#333333',
                    }
                },
            ],
        };
    },

    mounted() {
        this.loadSavedScheme();
    },

    methods: {
        // Загрузка сохранённой темы
        loadSavedScheme() {
            const tenantSlug = window.Tenant?.slug || 'any';
            const saved = localStorage.getItem(`theme_scheme_${tenantSlug}`);
            if (saved) {
                const scheme = this.schemes.find(s => s.id === saved);
                if (scheme) {
                    this.applyScheme(scheme, false);
                }
            }
        },

        // Применение темы
        applyScheme(scheme, save = true) {
            this.currentSchemeId = scheme.id;

            // Определяем текущую тему (светлая/тёмная)
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const colors = isDark ? scheme.dark : scheme.light;

            // Применяем все CSS-переменные
            const root = document.documentElement;
            Object.entries(colors).forEach(([key, value]) => {
                root.style.setProperty(`--bs-${key}`, value);
            });

            // Сохраняем выбор
            if (save) {
                const tenantSlug = window.Tenant?.slug || 'any';
                localStorage.setItem(`theme_scheme_${tenantSlug}`, scheme.id);
            }

            // Пересоздаём превью (чтобы активная тема обновилась)
            this.$forceUpdate();
        },

        // Стиль для превью карточки
        getPreviewStyle(scheme) {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const colors = isDark ? scheme.dark : scheme.light;

            return {
                '--preview-bg': colors['body-bg'],
                '--preview-header': colors.primary,
                '--preview-line': colors['border-color'],
                '--preview-button': colors.primary,
            };
        },
    },
};
</script>

<style scoped>
.scheme-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

/* Сетка тем */
.schemes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
}

/* Карточка темы */
.scheme-card {
    position: relative;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 12px;
    padding: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.scheme-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: var(--bs-primary);
}

.scheme-card.active {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

/* Превью темы */
.scheme-preview {
    width: 100%;
    aspect-ratio: 3/4;
    background: var(--preview-bg);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--preview-line);
}

.preview-header {
    height: 20%;
    background: var(--preview-header);
}

.preview-body {
    padding: 6px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.preview-line {
    height: 3px;
    background: var(--preview-line);
    border-radius: 2px;
}

.preview-line.short {
    width: 60%;
}

.preview-button {
    margin-top: auto;
    height: 12px;
    background: var(--preview-button);
    border-radius: 3px;
}

/* Название */
.scheme-name {
    font-size: 11px;
    font-weight: 600;
    color: var(--bs-body-color);
    text-align: center;
}

/* Галочка активной темы */
.scheme-check {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 20px;
    height: 20px;
    background: var(--bs-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}
</style>
