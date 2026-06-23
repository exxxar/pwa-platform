<template>
    <transition name="fade">
        <div v-if="isLoading" class="route-loader">
            <div class="loader-content">
                <div class="loader-spinner"></div>
                <p class="loader-text">Загрузка...</p>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "RouteLoader",
    data() {
        return {
            isLoading: false,
            timer: null,
        };
    },
    mounted() {
        // Перед переходом — показываем спиннер с задержкой 200мс
        // (чтобы быстрые переходы не мигали)
        this.$router.beforeEach((to, from, next) => {
            this.timer = setTimeout(() => {
                this.isLoading = true;
            }, 200);
            next();
        });

        // После перехода — скрываем
        this.$router.afterEach(() => {
            clearTimeout(this.timer);
            this.isLoading = false;
        });
    },
    beforeUnmount() {
        clearTimeout(this.timer);
    },
};
</script>

<style lang="scss" scoped>
.route-loader {
    position: fixed;
    inset: 0;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loader-content {
    text-align: center;
}

.loader-spinner {
    width: 48px;
    height: 48px;
    border: 3px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
}

.loader-text {
    font-size: 0.95rem;
    color: #6b7280;
    font-weight: 500;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
