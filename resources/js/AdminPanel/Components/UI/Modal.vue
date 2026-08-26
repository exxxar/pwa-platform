<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="modal-overlay" @click="handleOverlayClick">
                <div class="modal-container" :class="`modal-${size}`" @click.stop>
                    <div v-if="title || $slots.header" class="modal-header">
                        <slot name="header">
                            <h3 class="modal-title">{{ title }}</h3>
                        </slot>
                        <button v-if="closable" class="modal-close" @click="close">
                            ✕
                        </button>
                    </div>

                    <div class="modal-body">
                        <slot></slot>
                    </div>

                    <div v-if="$slots.footer" class="modal-footer">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl', 'full'].includes(value),
    },
    closable: {
        type: Boolean,
        default: true,
    },
    closeOnOverlay: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['update:modelValue', 'close'])

const close = () => {
    emit('update:modelValue', false)
    emit('close')
}

const handleOverlayClick = () => {
    if (props.closeOnOverlay) {
        close()
    }
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.modal-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Sizes */
.modal-sm {
    width: 100%;
    max-width: 400px;
}

.modal-md {
    width: 100%;
    max-width: 600px;
}

.modal-lg {
    width: 100%;
    max-width: 800px;
}

.modal-xl {
    width: 100%;
    max-width: 1000px;
}

.modal-full {
    width: 95%;
    max-width: 95%;
    height: 90vh;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #718096;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f7fafc;
    color: #2d3748;
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}

/* Transition */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
    transition: transform 0.3s ease;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
    transform: scale(0.9);
}
</style>
