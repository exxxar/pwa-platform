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
                <div class="scheme-preview" :style="getPreviewStyle(scheme)">
                    <div class="preview-header"></div>
                    <div class="preview-body">
                        <div class="preview-line"></div>
                        <div class="preview-line short"></div>
                        <div class="preview-button"></div>
                    </div>
                </div>
                <div class="scheme-name">{{ scheme.name }}</div>
                <div v-if="currentSchemeId === scheme.id" class="scheme-check">
                    <i class="fa-solid fa-check"></i>
                </div>
            </button>
        </div>
    </div>
</template>

<script>
import { themeSchemes, getThemeScheme } from '@/MobileClient/constants/themeSchemes.js';

export default {
    name: "ThemeSchemePicker",

    data() {
        return {
            schemes: themeSchemes, // 🆕 Используем импорт
            currentSchemeId: 'default',
        };
    },

    mounted() {
        this.loadSavedScheme();
    },

    methods: {
        loadSavedScheme() {
            const tenantSlug = window.Tenant?.slug || 'any';
            const saved = localStorage.getItem(`theme_scheme_${tenantSlug}`);

            // 🆕 Если нет сохраненной, берем дефолтную из настроек тенанта
            const targetId = saved || (window.Tenant?.settings?.default_theme_scheme) || 'default';

            const scheme = getThemeScheme(targetId);
            if (scheme) {
                this.applyScheme(scheme, false);
            }
        },

        applyScheme(scheme, save = true) {
            this.currentSchemeId = scheme.id;
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const colors = isDark ? scheme.dark : scheme.light;

            const root = document.documentElement;
            Object.entries(colors).forEach(([key, value]) => {
                root.style.setProperty(`--bs-${key}`, value);
            });

            if (save) {
                const tenantSlug = window.Tenant?.slug || 'any';
                localStorage.setItem(`theme_scheme_${tenantSlug}`, scheme.id);
            }
        },

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
