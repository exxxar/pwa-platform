<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <div class="form-section">
            <h3 class="section-title">
                <i class="fa-solid fa-user-astronaut"></i>
                Имена для новых гостей
            </h3>

            <div class="alert-info">
                <i class="fa-solid fa-circle-info"></i>
                Когда новый пользователь заходит в приложение, ему присваивается случайное имя из этого списка в формате "Гость • [Имя]".
                Если список будет пуст, система автоматически использует встроенный набор из 50+ красивых вариантов.
            </div>

            <div class="form-field full-width">
                <label>Список имен (каждое с новой строки)</label>
                <textarea
                    v-model="form.identities"
                    rows="12"
                    class="monospace-textarea"
                    placeholder="Хитрый Енот&#10;Мудрая Сова&#10;Космический Кот"
                    @input="emitDirty"
                ></textarea>
                <span class="field-hint">
          Найдено вариантов: <strong>{{ countLines(form.identities) }}</strong>
        </span>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">
                <i class="fa-solid fa-comment-dots"></i>
                Шаблон приветствия
            </h3>

            <div class="form-field full-width">
                <label>Текст первого сообщения</label>
                <textarea
                    v-model="form.welcome_message"
                    rows="4"
                    placeholder="Привет, {name}! Рады тебя видеть."
                    @input="emitDirty"
                ></textarea>
                <span class="field-hint">
          Используйте <code>{name}</code> как плейсхолдер, который будет заменен на выбранное имя (например, "Хитрый Енот"). Поддерживается HTML (например, &lt;b&gt;).
        </span>
            </div>
        </div>

        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить настройки гостей' }}</span>
        </button>
    </form>
</template>

<script>
export default {
    name: 'TabGuests',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'guests');
        },

        // Подсчет количества непустых строк для информативности
        countLines(text) {
            if (!text) return 0;
            return text.split('\n').filter(line => line.trim().length > 0).length;
        },

        onSubmit() {
            // Превращаем текст из textarea обратно в массив, убирая пустые строки
            const identitiesArray = (this.form.identities || '')
                .split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0);

            const payload = {
                guests: {
                    identities: identitiesArray,
                    welcome_message: this.form.welcome_message
                }
            };

            this.$emit('save', payload);
        },
    },
};
</script>
