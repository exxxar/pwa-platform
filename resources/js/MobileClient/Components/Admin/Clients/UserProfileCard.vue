<template>
    <div class="action-card" :class="[`color-${color}`, { 'is-expanded': isExpanded }]">
        <div class="action-header" @click="toggle">
            <div class="action-icon">
                <i :class="icon"></i>
            </div>
            <div class="action-info">
                <div class="action-title">{{ title }}</div>
                <div class="action-description">{{ description }}</div>
            </div>
            <button class="action-toggle">
                <i class="fa-solid" :class="isExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
        <transition name="expand">
            <div v-if="isExpanded" class="action-body">
                <slot></slot>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    name: "ActionCard",
    props: {
        icon: { type: String, required: true },
        title: { type: String, required: true },
        description: { type: String, default: '' },
        color: {
            type: String,
            default: 'primary',
            validator: (v) => ['primary', 'success', 'danger', 'warning', 'purple', 'info'].includes(v)
        },
    },
    data() {
        return {
            isExpanded: false,
        };
    },
    methods: {
        toggle() {
            this.isExpanded = !this.isExpanded;
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$purple: #8b5cf6;
$info: #06b6d4;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;

.action-card {
    background: white;
    border: 1px solid $border;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;

    &.is-expanded {
        border-color: var(--card-color);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    // Цветовые варианты
    &.color-primary { --card-color: #{$primary}; --card-bg: rgba(#{$primary}, 0.1); }
    &.color-success { --card-color: #{$success}; --card-bg: rgba(#{$success}, 0.1); }
    &.color-danger { --card-color: #{$danger}; --card-bg: rgba(#{$danger}, 0.1); }
    &.color-warning { --card-color: #{$warning}; --card-bg: rgba(#{$warning}, 0.1); }
    &.color-purple { --card-color: #{$purple}; --card-bg: rgba(#{$purple}, 0.1); }
    &.color-info { --card-color: #{$info}; --card-bg: rgba(#{$info}, 0.1); }
}

.action-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    cursor: pointer;
    transition: background 0.2s;

    &:hover {
        background: $bg;
    }
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--card-bg);
    color: var(--card-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.action-info {
    flex: 1;
    min-width: 0;
}

.action-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: $text;
    margin-bottom: 2px;
}

.action-description {
    font-size: 0.8rem;
    color: $text-muted;
    line-height: 1.3;
}

.action-toggle {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid $border;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;

    .is-expanded & {
        background: var(--card-color);
        border-color: var(--card-color);
        color: white;
    }
}

.action-body {
    padding: 14px;
    border-top: 1px solid $border;
    background: $bg;
}

// Анимация раскрытия
.expand-enter-active,
.expand-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
    opacity: 0;
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.expand-enter-to,
.expand-leave-from {
    opacity: 1;
    max-height: 500px;
}
</style>
