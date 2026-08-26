<template>
    <span :class="['badge', `badge-${variant}`, `badge-${size}`]">
        <span v-if="$slots.icon" class="badge-icon">
            <slot name="icon"></slot>
        </span>
        <span class="badge-text">
            <slot></slot>
        </span>
    </span>
</template>

<script setup>
defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'success', 'warning', 'danger', 'info', 'primary'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
})
</script>

<style scoped>
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 12px;
    font-weight: 500;
    white-space: nowrap;
}

/* Sizes */
.badge-sm {
    padding: 2px 8px;
    font-size: 11px;
}

.badge-md {
    padding: 4px 12px;
    font-size: 12px;
}

.badge-lg {
    padding: 6px 16px;
    font-size: 13px;
}

/* Variants */
.badge-default {
    background: #e2e8f0;
    color: #2d3748;
}

.badge-success {
    background: #c6f6d5;
    color: #22543d;
}

.badge-warning {
    background: #feebc8;
    color: #7c2d12;
}

.badge-danger {
    background: #fed7d7;
    color: #742a2a;
}

.badge-info {
    background: #bee3f8;
    color: #2c5282;
}

.badge-primary {
    background: #e9d8fd;
    color: #553c9a;
}

.badge-icon {
    display: flex;
    align-items: center;
    font-size: 0.9em;
}

.badge-text {
    display: flex;
    align-items: center;
}
</style>
