<template>
    <button
        :type="type"
        :class="[
            'btn',
            `btn-${variant}`,
            `btn-${size}`,
            { 'btn-loading': loading, 'btn-block': block, 'btn-icon-only': iconOnly }
        ]"
        :disabled="disabled || loading"
        @click="$emit('click', $event)"
    >
        <span v-if="loading" class="btn-spinner"></span>
        <span v-if="$slots.icon && !loading" class="btn-icon">
            <slot name="icon"></slot>
        </span>
        <span v-if="$slots.default" class="btn-content">
            <slot></slot>
        </span>
    </button>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
        validator: (value) => ['button', 'submit', 'reset'].includes(value),
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'outline'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    block: {
        type: Boolean,
        default: false,
    },
    iconOnly: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['click'])
</script>

<style scoped>
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    white-space: nowrap;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Sizes */
.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

.btn-md {
    padding: 10px 20px;
    font-size: 14px;
}

.btn-lg {
    padding: 14px 28px;
    font-size: 16px;
}

/* Variants */
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #e2e8f0;
    color: #2d3748;
}

.btn-secondary:hover:not(:disabled) {
    background: #cbd5e0;
}

.btn-success {
    background: #48bb78;
    color: white;
}

.btn-success:hover:not(:disabled) {
    background: #38a169;
}

.btn-danger {
    background: #f56565;
    color: white;
}

.btn-danger:hover:not(:disabled) {
    background: #e53e3e;
}

.btn-warning {
    background: #ed8936;
    color: white;
}

.btn-warning:hover:not(:disabled) {
    background: #dd6b20;
}

.btn-info {
    background: #4299e1;
    color: white;
}

.btn-info:hover:not(:disabled) {
    background: #3182ce;
}

.btn-outline {
    background: transparent;
    border: 2px solid #e2e8f0;
    color: #2d3748;
}

.btn-outline:hover:not(:disabled) {
    background: #f7fafc;
    border-color: #cbd5e0;
}

/* Block */
.btn-block {
    width: 100%;
}

/* Icon only */
.btn-icon-only {
    padding: 10px;
}

.btn-icon-only.btn-sm {
    padding: 8px;
}

.btn-icon-only.btn-lg {
    padding: 14px;
}

/* Loading */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-content {
    display: flex;
    align-items: center;
}
</style>
