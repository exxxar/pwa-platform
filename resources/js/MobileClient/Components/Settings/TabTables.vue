<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title">
                <i class="fa-solid fa-utensils"></i>
                Бронирование столиков
            </h3>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Показывать список столиков</h4>
                    <p>Клиент сможет выбирать конкретный столик при бронировании</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.need_table_list" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="form-field">
                <label>Максимальное количество столиков</label>
                <input type="number" v-model="form.max_tables" min="0" @input="emitDirty">
            </div>

            <!-- Кнопка скачивания PDF -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
                <button type="button" class="btn-download-pdf" @click="downloadTablesPdf">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Скачать PDF с QR-кодами столов</span>
                </button>
                <span class="field-hint" style="display: block; margin-top: 8px;">
          Генерирует документ со всеми активными столиками и их QR-кодами для печати.
        </span>
            </div>

            <div class="alert-info" style="margin-top: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Для детального планирования столиков используйте отдельный компонент планировщика.
            </div>
        </div>

        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить' }}</span>
        </button>
    </form>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TabTables',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'tables');
        },

        onSubmit() {
            this.$emit('save', { tables: this.form });
        },

        async downloadTablesPdf() {
            this.$emit('notify', { title: 'Генерация', text: 'Подготавливаем PDF файл...', type: 'info' });

            try {
                const response = await axios.get('/admin/tenant-settings/tables/download-qr-pdf', {
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `tables-qr-codes-${Date.now()}.pdf`);
                document.body.appendChild(link);
                link.click();
                link.remove();

                this.$emit('notify', { title: 'Успешно', text: 'PDF скачан', type: 'success' });
            } catch (error) {
                console.error('Ошибка скачивания PDF:', error);
                this.$emit('notify', { title: 'Ошибка', text: 'Не удалось сгенерировать PDF', type: 'error' });
            }
        },
    },
};
</script>
