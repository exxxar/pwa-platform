<template>
    <div class="select-wrapper">
        <label v-if="label" :for="id" class="select-label">
            {{ label }}
            <span v-if="required" class="required">*</span>
        </label>
        <select
            :id="id"
            :value="modelValue"
            :disabled="disabled"
            :required="required"
            :class="['select-field', { 'has-error': error }]"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
        <span v-if="error" class="select-error">{{ error }}</span>
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    id: {
        type: String,
        default: () => `select-${Math.random().toString(36).substr(2, 9)}`,
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        required: true,
        validator: (value) => value.every(opt => 'value' in opt && 'label' in opt),
    },
    error: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.select-wrapper {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.select-label {
    font-size: 14px;
    font-weight: 500;
    color: #2d3748;
}

.required {
    color: #f56565;
}

.select-field {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    outline: none;
    background: white;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23718096' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 36px;
}

.select-field:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.select-field:disabled {
    background: #f7fafc;
    cursor: not-allowed;
}

.select-field.has-error {
    border-color: #f56565;
}

.select-error {
    font-size: 12px;
    color: #f56565;
}
</style>
