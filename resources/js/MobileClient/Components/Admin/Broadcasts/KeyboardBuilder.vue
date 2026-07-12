<template>
    <div class="keyboard-builder">

        <!-- Предпросмотр клавиатуры -->
        <div v-if="localButtons.length > 0" class="keyboard-preview">
            <div class="preview-title">
                <i class="fa-solid fa-eye"></i>
                <span>Предпросмотр ({{ totalButtonsCount }} кнопок)</span>
            </div>
            <div class="preview-keyboard">
                <div
                    v-for="(row, rowIndex) in localButtons"
                    :key="'preview-row-' + rowIndex"
                    class="preview-row"
                >
                    <button
                        type="button"
                        v-for="(btn, btnIndex) in row"
                        :key="'preview-btn-' + rowIndex + '-' + btnIndex"
                        class="preview-btn"
                        :class="{
                    'is-url': btn.type === 'url',
                    'is-empty': !btn.text || !btn.text.trim()
                }"
                    >
                        <i v-if="btn.type === 'url'" class="fa-solid fa-arrow-up-right-from-square"></i>

                        <!-- 🆕 Показываем текст или placeholder -->
                        <span v-if="btn.text && btn.text.trim()">{{ btn.text }}</span>
                        <span v-else class="empty-text">
                    <i class="fa-solid fa-pen"></i>
                    Пустая кнопка
                </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Редактор -->
        <div class="builder-editor">
            <div v-if="localButtons.length === 0" class="empty-state">
                <i class="fa-solid fa-keyboard"></i>
                <h4>Клавиатура не настроена</h4>
                <p>Добавьте кнопки, которые увидит пользователь под сообщением</p>
                <button class="add-row-btn" @click="addRow">
                    <i class="fa-solid fa-plus"></i>
                    <span>Добавить первую строку</span>
                </button>
            </div>

            <div v-else class="rows-list">
                <div
                    v-for="(row, rowIndex) in localButtons"
                    :key="'edit-row-' + rowIndex"
                    class="builder-row"
                >
                    <div class="row-header">
                        <span class="row-label">Строка {{ rowIndex + 1 }}</span>
                        <div class="row-actions">
                            <button
                                type="button"
                                v-if="rowIndex > 0"
                                class="row-action"
                                @click="moveRow(rowIndex, -1)"
                                title="Переместить вверх"
                            >
                                <i class="fa-solid fa-arrow-up"></i>
                            </button>
                            <button
                                type="button"
                                v-if="rowIndex < localButtons.length - 1"
                                class="row-action"
                                @click="moveRow(rowIndex, 1)"
                                title="Переместить вниз"
                            >
                                <i class="fa-solid fa-arrow-down"></i>
                            </button>
                            <button
                                type="button"
                                class="row-action danger"
                                @click="removeRow(rowIndex)"
                                title="Удалить строку"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Кнопки в строке -->
                    <div class="buttons-grid">
                        <div
                            v-for="(btn, btnIndex) in row"
                            :key="'edit-btn-' + rowIndex + '-' + btnIndex"
                            class="button-card"
                        >
                            <div class="button-fields">
                                <div class="field">
                                    <label>Текст</label>
                                    <input
                                        type="text"
                                        :value="btn.text"
                                        @input="updateButtonText(rowIndex, btnIndex, $event.target.value)"
                                        maxlength="100"
                                        placeholder="Текст кнопки"
                                    >
                                </div>

                                <div class="field type-select">
                                    <label>Тип</label>
                                    <select
                                        :value="btn.type"
                                        @change="updateButtonType(rowIndex, btnIndex, $event.target.value)"
                                    >
                                        <option value="callback">Callback</option>
                                        <option value="url">URL</option>
                                    </select>
                                </div>

                                <div class="field full-width">
                                    <label>
                                        {{ btn.type === 'url' ? 'Ссылка' : 'Callback-данные' }}
                                    </label>
                                    <input
                                        v-if="btn.type === 'url'"
                                        type="url"
                                        :value="btn.url"
                                        @input="updateButtonUrl(rowIndex, btnIndex, $event.target.value)"
                                        placeholder="https://..."
                                    >
                                    <input
                                        v-else
                                        type="text"
                                        :value="btn.callback_data"
                                        @input="updateButtonCallback(rowIndex, btnIndex, $event.target.value)"
                                        maxlength="64"
                                        placeholder="action:value"
                                    >
                                </div>
                            </div>

                            <button
                                type="button"
                                class="button-remove"
                                @click="removeButton(rowIndex, btnIndex)"
                                title="Удалить кнопку"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Кнопка добавления в строку -->
                    <button
                        type="button"
                        class="add-button-btn"
                        @click="addButton(rowIndex)"
                        :disabled="row.length >= 4"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Добавить кнопку</span>
                    </button>
                </div>

                <!-- Кнопка добавления новой строки -->
                <button
                    type="button"
                    class="add-row-btn"
                    @click="addRow"
                    :disabled="localButtons.length >= 8"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>Добавить строку</span>
                </button>
            </div>
        </div>

        <!-- Подсказка -->
        <div class="builder-hint">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                <strong>Подсказка:</strong>
                <span>До 8 строк и 4 кнопок в каждой. Callback-кнопки обрабатываются ботом, URL-кнопки открывают ссылку.</span>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: 'KeyboardBuilder',

    props: {
        modelValue: {
            type: Array,
            default: () => [],
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            localButtons: [],
        };
    },
    computed: {
        totalButtonsCount() {
            return this.localButtons.reduce((sum, row) => sum + row.length, 0);
        },
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(newValue) {
                this.localButtons = this.normalizeButtons(newValue);
            },
            deep: true,
        },
    },

    methods: {
        normalizeButtons(value) {
            if (!Array.isArray(value) || value.length === 0) {
                return [];
            }

            return value.map(row => {
                if (!Array.isArray(row)) return [];

                return row.map(btn => ({
                    text: btn.text || '',
                    type: btn.type || 'callback',
                    url: btn.url || '',
                    callback_data: btn.callback_data || '',
                }));
            });
        },

        addRow() {
            if (this.localButtons.length >= 8) return;

            const newRow = [{
                text: '',
                type: 'callback',
                url: '',
                callback_data: '',
            }];

            this.localButtons.push(newRow);
            this.emitUpdate();
        },

        removeRow(rowIndex) {
            this.localButtons.splice(rowIndex, 1);
            this.emitUpdate();
        },

        moveRow(rowIndex, direction) {
            const newIndex = rowIndex + direction;
            if (newIndex < 0 || newIndex >= this.localButtons.length) return;

            const row = this.localButtons[rowIndex];
            this.localButtons.splice(rowIndex, 1);
            this.localButtons.splice(newIndex, 0, row);
            this.emitUpdate();
        },

        addButton(rowIndex) {
            if (this.localButtons[rowIndex].length >= 4) return;

            const newBtn = {
                text: '',
                type: 'callback',
                url: '',
                callback_data: '',
            };

            this.localButtons[rowIndex].push(newBtn);
            this.emitUpdate();
        },

        removeButton(rowIndex, btnIndex) {
            this.localButtons[rowIndex].splice(btnIndex, 1);

            // Удаляем пустую строку
            if (this.localButtons[rowIndex].length === 0) {
                this.localButtons.splice(rowIndex, 1);
            }

            this.emitUpdate();
        },



        emitUpdate() {
            console.log('[KeyboardBuilder] localButtons:', JSON.parse(JSON.stringify(this.localButtons)));

            // 🆕 Отправляем ВСЕ кнопки, даже пустые — валидация на сервере
            const cleaned = this.localButtons
                .map(row =>
                    row.map(btn => {
                        const result = {
                            text: (btn.text || '').trim(),
                            type: btn.type || 'callback',
                        };

                        if (btn.type === 'url') {
                            result.url = (btn.url || '').trim();
                        } else {
                            result.callback_data = (btn.callback_data || '').trim();
                        }

                        return result;
                    })
                )
                .filter(row => row.length > 0); // Убираем только пустые строки

            console.log('[KeyboardBuilder] cleaned:', JSON.parse(JSON.stringify(cleaned)));
            this.$emit('update:modelValue', cleaned);
        },

        // 🆕 Методы для реактивного обновления полей
        updateButtonText(rowIndex, btnIndex, value) {
            console.log(`[KB] updateButtonText: row=${rowIndex}, btn=${btnIndex}, value="${value}"`);

            // 🆕 Создаём новый объект для реактивности
            const btn = { ...this.localButtons[rowIndex][btnIndex] };
            btn.text = value;

            // 🆕 Пересоздаём массив для триггера реактивности
            const newRow = [...this.localButtons[rowIndex]];
            newRow[btnIndex] = btn;

            const newButtons = [...this.localButtons];
            newButtons[rowIndex] = newRow;

            this.localButtons = newButtons;
            this.emitUpdate();
        },

        updateButtonType(rowIndex, btnIndex, value) {
            const btn = { ...this.localButtons[rowIndex][btnIndex] };
            btn.type = value;
            btn.url = '';
            btn.callback_data = '';

            const newRow = [...this.localButtons[rowIndex]];
            newRow[btnIndex] = btn;

            const newButtons = [...this.localButtons];
            newButtons[rowIndex] = newRow;

            this.localButtons = newButtons;
            this.emitUpdate();
        },

        updateButtonUrl(rowIndex, btnIndex, value) {
            const btn = { ...this.localButtons[rowIndex][btnIndex] };
            btn.url = value;

            const newRow = [...this.localButtons[rowIndex]];
            newRow[btnIndex] = btn;

            const newButtons = [...this.localButtons];
            newButtons[rowIndex] = newRow;

            this.localButtons = newButtons;
            this.emitUpdate();
        },

        updateButtonCallback(rowIndex, btnIndex, value) {
            const btn = { ...this.localButtons[rowIndex][btnIndex] };
            btn.callback_data = value;

            const newRow = [...this.localButtons[rowIndex]];
            newRow[btnIndex] = btn;

            const newButtons = [...this.localButtons];
            newButtons[rowIndex] = newRow;

            this.localButtons = newButtons;
            this.emitUpdate();
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$purple: #8b5cf6;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.keyboard-builder {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

// ==========================================
// ПРЕДПРОСМОТР
// ==========================================
.keyboard-preview {
    background: $bg-secondary;
    border-radius: 14px;
    padding: 16px;
    border: 1px solid $border;
}

.preview-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $text-muted;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.3px;

    i {
        color: $primary;
    }
}

.preview-keyboard {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-width: 400px;
}

.preview-row {
    display: flex;
    gap: 6px;
}

.preview-btn {
    flex: 1;
    padding: 10px 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 8px;
    color: $primary;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: default;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: all 0.2s;

    &.is-url {
        color: $success;
        border-color: rgba($success, 0.3);
    }

    &.is-empty {
        opacity: 0.5;
        border-style: dashed;
        color: $text-muted;
    }

    i {
        font-size: 0.75rem;
    }

    .empty-text {
        font-style: italic;
        font-weight: 500;
    }
}

// ==========================================
// РЕДАКТОР
// ==========================================
.builder-editor {
    background: $bg;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    background: $bg-secondary;
    border: 2px dashed $border;
    border-radius: 14px;

    i {
        font-size: 2.5rem;
        color: $primary;
        opacity: 0.5;
        margin-bottom: 12px;
    }

    h4 {
        margin: 0 0 6px;
        font-size: 1rem;
        font-weight: 700;
        color: $text;
    }

    p {
        margin: 0 0 20px;
        font-size: 0.85rem;
        color: $text-muted;
    }
}

.rows-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.builder-row {
    background: $bg-secondary;
    border: 1px solid $border;
    border-radius: 14px;
    padding: 14px;
    transition: all 0.2s;

    &:hover {
        border-color: rgba($primary, 0.3);
    }
}

.row-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid $border;
}

.row-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: $text;
    display: flex;
    align-items: center;
    gap: 6px;

    &::before {
        content: '';
        width: 4px;
        height: 14px;
        background: $primary;
        border-radius: 2px;
    }
}

.row-actions {
    display: flex;
    gap: 4px;
}

.row-action {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: transparent;
    border: 1px solid $border;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        color: $primary;
        background: rgba($primary, 0.05);
    }

    &.danger:hover {
        border-color: $danger;
        color: $danger;
        background: rgba($danger, 0.05);
    }
}

.buttons-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 12px;
}

.button-card {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 10px;
}

.button-fields {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 100px;
    gap: 8px;
    min-width: 0;
}

.field {
    &.full-width {
        grid-column: 1 / -1;
    }

    label {
        display: block;
        font-size: 0.7rem;
        font-weight: 600;
        color: $text-muted;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    input,
    select {
        width: 100%;
        padding: 8px 10px;
        background: $bg;
        border: 1px solid $border;
        border-radius: 6px;
        font-size: 0.85rem;
        color: $text;
        font-family: inherit;
        transition: all 0.2s;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 2px rgba($primary, 0.1);
        }

        &::placeholder {
            color: $text-muted;
            opacity: 0.7;
        }
    }
}

.button-remove {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid $border;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
    margin-top: 18px;

    &:hover {
        background: $danger;
        border-color: $danger;
        color: white;
    }
}

.add-button-btn,
.add-row-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: 2px dashed $border;
    border-radius: 10px;
    color: $text-muted;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        border-color: $primary;
        color: $primary;
        background: rgba($primary, 0.03);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

.add-row-btn {
    margin-top: 8px;
    padding: 12px;
}

// ==========================================
// ПОДСКАЗКА
// ==========================================
.builder-hint {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    background: rgba($primary, 0.05);
    border: 1px solid rgba($primary, 0.15);
    border-radius: 10px;
    font-size: 0.8rem;
    color: $text;
    line-height: 1.5;

    i {
        color: $primary;
        margin-top: 2px;
        flex-shrink: 0;
    }

    strong {
        font-weight: 700;
    }
}

@media (max-width: 480px) {
    .button-fields {
        grid-template-columns: 1fr;
    }

    .field.type-select {
        grid-column: 1 / -1;
    }

    .preview-btn {
        padding: 8px 10px;
        font-size: 0.75rem;
    }
}
</style>
