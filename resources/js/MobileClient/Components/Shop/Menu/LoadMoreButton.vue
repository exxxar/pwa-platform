<template>
    <div class="load-more-container">
        <button
            class="load-more-btn"
            :class="{ 'is-loading': isLoading }"
            type="button"
            :disabled="isLoading"
            @click="$emit('load-more')"
        >
            <div class="btn-content">
                <div class="btn-icon">
                    <i v-if="!isLoading" class="fa-solid fa-arrow-rotate-right"></i>
                    <div v-else class="loading-spinner"></div>
                </div>

                <div class="btn-text">
                    <span v-if="!isLoading">
                        Загрузить ещё
                        <span class="remaining-badge">{{ remaining }}</span>
                    </span>
                    <span v-else>Загружаем товары...</span>
                </div>
            </div>

            <div class="btn-glow"></div>
        </button>
    </div>
</template>

<script>
export default {
    name: 'LoadMoreButton',

    props: {
        remaining: Number,
        isLoading: Boolean,
    },

    emits: ['load-more'],
};
</script>

<style scoped>
.load-more-container {
    display: flex;
    justify-content: center;
    width: 100%;
    padding: 1rem 0;
}

.load-more-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.1) 100%);
    border: 2px solid rgba(var(--bs-primary-rgb), 0.3);
    border-radius: 12px;
    color: var(--bs-primary);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    width: 100%;
    max-width: 400px;
}

.load-more-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--bs-primary) 0%, rgba(var(--bs-primary-rgb), 0.8) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 0;
}

.load-more-btn:hover:not(:disabled)::before {
    opacity: 1;
}

.load-more-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3);
    border-color: var(--bs-primary);
}

.load-more-btn:active:not(:disabled) {
    transform: translateY(0);
}

.load-more-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: color 0.3s ease;
}

.load-more-btn:hover:not(:disabled) .btn-content {
    color: white;
}

.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

.load-more-btn:hover:not(:disabled) .btn-icon {
    transform: rotate(180deg);
}

.loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.btn-text {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.remaining-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    background: var(--bs-primary);
    color: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    transition: all 0.3s ease;
}

.load-more-btn:hover:not(:disabled) .remaining-badge {
    background: white;
    color: var(--bs-primary);
    transform: scale(1.1);
}

.load-more-btn.is-loading .btn-icon {
    animation: none;
}

/* Свечение при наведении */
.btn-glow {
    position: absolute;
    inset: -2px;
    background: linear-gradient(135deg, var(--bs-primary), rgba(var(--bs-primary-rgb), 0.5));
    border-radius: 14px;
    opacity: 0;
    filter: blur(20px);
    transition: opacity 0.3s ease;
    z-index: -1;
}

.load-more-btn:hover:not(:disabled) .btn-glow {
    opacity: 0.4;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Адаптив для мобильных */
@media (max-width: 576px) {
    .load-more-btn {
        padding: 0.875rem 1.5rem;
        font-size: 0.9rem;
    }

    .remaining-badge {
        min-width: 24px;
        height: 24px;
        font-size: 0.8rem;
    }
}
</style>
