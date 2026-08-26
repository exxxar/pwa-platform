<template>
    <div class="input-wrapper">
        <label v-if="label" :for="id" class="input-label">
            {{ label }}
            <span v-if="required" class="required">*</span>
        </label>
        <div class="input-container">
            <span v-if="$slots.prefix" class="input-prefix">
                <slot name="prefix"></slot>
            </span>
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :class="[
                    'input-field',
                    { 'has-error': error, 'has-prefix': $slots.prefix, 'has-suffix': $slots.suffix }
                ]"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event)"
                @focus="$emit('focus', $event)"
            />
            <span v-if="$slots.suffix" class="input-suffix">
                <slot name="suffix"></slot>
            </span>
        </div>
        <span v-if="error" class="input-error">{{ error }}</span>
        <span v-if="hint && !error" class="input-hint">{{ hint }}</span>
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    id: {
        type: String,
        default: () => `input-${Math.random().toString(36).substr(2, 9)}`,
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['update:modelValue', 'blur', 'focus'])
</script>

<style scoped>
.input-wrapper {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.input-label {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
}

.required {
    color: #f56565;
}

.input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.input-field {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    outline: none;
    background: white;
}

.input-field:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-field:disabled {
    background: #f7fafc;
    cursor: not-allowed;
}

.input-field.has-error {
    border-color: #f56565;
}

.input-field.has-error:focus {
    box-shadow: 0 0 0 3px rgba(245, 101, 101, 0.1);
}

.input-field.has-prefix {
    padding-left: 40px;
}

.input-field.has-suffix {
    padding-right: 40px;
}

.input-prefix,
.input-suffix {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #718096;
    pointer-events: none;
}

.input-prefix {
    left: 12px;
}

.input-suffix {
    right: 12px;
}

.input-error {
    font-size: 12px;
    color: #f56565;
}

.input-hint {
    font-size: 12px;
    color: #718096;
}
</style>
