<template>
    <div class="card theme-toggle-card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center justify-content-between p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="theme-icon-box" :class="{ 'is-dark': isDarkMode }">
                    <i :class="isDarkMode ? 'fa-solid fa-moon' : 'fa-solid fa-sun'"></i>
                </div>
                <div>
                    <div class="fw-semibold">Внешний вид</div>
                    <small class="text-muted">
                        {{ isDarkMode ? 'Тёмная тема' : 'Светлая тема' }}
                    </small>
                </div>
            </div>

            <!-- Переключатель -->
            <div class="form-check form-switch m-0">
                <input
                    class="form-check-input theme-switch"
                    type="checkbox"
                    role="switch"
                    :checked="isDarkMode"
                    @change="toggleTheme"
                >
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name:"ThemeToggle",
    data(){
        return {
            // ✅ Тема
            isDarkMode: false,
        }
    },
    mounted() {
        this.initTheme(); // ✅ Инициализация темы при загрузке
    },

    methods:{
        // ✅ Инициализация темы из localStorage
        initTheme() {
            const saved = localStorage.getItem('theme');
            this.isDarkMode = saved === 'dark';
            this.applyTheme();
        },
        // ✅ Переключение темы
        toggleTheme() {
            this.isDarkMode = !this.isDarkMode;
            localStorage.setItem('theme', this.isDarkMode ? 'dark' : 'light');
            this.applyTheme();
        },

        // ✅ Применение темы к документу (Bootstrap 5.3+)
        applyTheme() {
            document.documentElement.setAttribute(
                'data-bs-theme',
                this.isDarkMode ? 'dark' : 'light'
            );
        },
    }
}
</script>
