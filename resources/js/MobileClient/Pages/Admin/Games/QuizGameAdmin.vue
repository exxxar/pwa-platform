<template>
    <div class="quiz-admin-page pb-5">
        <div class="container py-4">
            <!-- Шапка -->
            <div class="modern-header mb-4">
                <div>
                    <h2 class="page-title">
                        <i class="fa-solid fa-question me-2" style="color: #30cfd0;"></i> Викторина
                    </h2>
                    <p class="page-subtitle">Настройка вопросов, ответов и призов</p>
                </div>
            </div>

            <!-- 🆕 ОБЩИЕ НАСТРОЙКИ -->
            <div class="modern-card mb-4">
                <h5 class="card-title"><i class="fa-solid fa-sliders"></i> Общие настройки</h5>
                <div class="row g-3">
                    <div class="col-md-3 d-flex align-items-end">
                        <label class="modern-switch mb-0">
                            <input type="checkbox" v-model="form.can_play">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Активность игры</span>
                        </label>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <label class="modern-switch mb-0">
                            <input type="checkbox" v-model="form.shuffle_questions">
                            <span class="switch-slider"></span>
                            <span class="switch-label">Перемешивать вопросы</span>
                        </label>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-modern">
                            % для победы
                            <span class="unit-hint">минимум правильных</span>
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" class="form-input-modern form-input-sm" v-model.number="form.win_threshold" min="1" max="100">
                            <span class="input-suffix">%</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-modern">
                            Приз за победу
                            <span class="unit-hint">бонусов</span>
                        </label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.prize_amount" min="1" max="10000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Попыток за период</label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.attempts_per_period" min="1" max="50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Период обновления</label>
                        <select class="form-input-modern form-input-sm" v-model.number="form.interval">
                            <option :value="1">Каждый день</option>
                            <option :value="7">Раз в неделю</option>
                            <option :value="30">Раз в месяц</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">
                            Время на ответ
                            <span class="unit-hint">сек (0 = без лимита)</span>
                        </label>
                        <input type="number" class="form-input-modern form-input-sm" v-model.number="form.time_per_question" min="0" max="120">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Заголовок <span class="char-counter">{{ (form.title || '').length }}/100</span></label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.title" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-modern">Подзаголовок <span class="char-counter">{{ (form.subtitle || '').length }}/150</span></label>
                        <input type="text" class="form-input-modern form-input-sm" v-model="form.subtitle" maxlength="150">
                    </div>
                    <div class="col-12">
                        <label class="form-label-modern">Правила игры <span class="char-counter">{{ (form.rules || '').length }}/4000</span></label>
                        <textarea class="form-input-modern form-input-sm" v-model="form.rules" rows="3" maxlength="4000"></textarea>
                    </div>
                </div>
            </div>

            <!-- 🆕 УПРАВЛЕНИЕ ВОПРОСАМИ -->
            <div class="modern-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-circle-question"></i> Вопросы викторины</h5>
                    <span class="badge-sector">{{ form.questions.length }} вопросов</span>
                </div>
                <p class="text-muted small mb-3">
                    Каждый вопрос должен иметь <strong>ровно 4 варианта ответа</strong> и <strong>1 правильный</strong>.
                    <span class="validation-warning" v-if="validationErrors.length > 0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Найдено ошибок: {{ validationErrors.length }}
                    </span>
                </p>

                <div class="questions-list">
                    <div
                        v-for="(question, qIndex) in form.questions"
                        :key="question.id"
                        class="question-card"
                        :class="{ 'has-error': hasQuestionError(question) }"
                    >
                        <div class="question-header" @click="question.edit = !question.edit">
                            <div class="question-number-badge">
                                <span>Q{{ qIndex + 1 }}</span>
                            </div>
                            <div class="question-preview">
                                <div class="question-text-preview">
                                    {{ question.text || 'Текст вопроса не задан' }}
                                </div>
                                <div class="question-meta">
                                    <span class="answers-count">
                                        <i class="fa-solid fa-list-check"></i>
                                        {{ (question.answers || []).length }}/4 ответов
                                    </span>
                                    <span class="correct-badge" v-if="getCorrectAnswerCount(question) === 1">
                                        <i class="fa-solid fa-check"></i>
                                        1 правильный
                                    </span>
                                    <span class="correct-badge error" v-else>
                                        <i class="fa-solid fa-xmark"></i>
                                        {{ getCorrectAnswerCount(question) }} правильных
                                    </span>
                                </div>
                            </div>
                            <div class="question-actions">
                                <button class="btn-icon btn-sm btn-secondary" @click.stop="moveQuestion(qIndex, -1)" :disabled="qIndex === 0" title="Вверх">
                                    <i class="fa-solid fa-arrow-up"></i>
                                </button>
                                <button class="btn-icon btn-sm btn-secondary" @click.stop="moveQuestion(qIndex, 1)" :disabled="qIndex === form.questions.length - 1" title="Вниз">
                                    <i class="fa-solid fa-arrow-down"></i>
                                </button>
                                <button class="btn-icon btn-sm" :class="question.edit ? 'btn-success' : 'btn-secondary'" @click.stop="question.edit = !question.edit">
                                    <i class="fa-solid" :class="question.edit ? 'fa-check' : 'fa-pen'"></i>
                                </button>
                                <button class="btn-icon btn-sm btn-danger" @click.stop="removeQuestion(qIndex)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div v-if="question.edit" class="question-edit-mode fade-in">
                            <div class="row g-3">
                                <!-- ТЕКСТ ВОПРОСА -->
                                <div class="col-12">
                                    <label class="form-label-modern">
                                        Текст вопроса
                                        <span class="char-counter">{{ (question.text || '').length }}/500</span>
                                    </label>
                                    <textarea
                                        v-model="question.text"
                                        class="form-input-modern form-input-sm"
                                        rows="2"
                                        maxlength="500"
                                        placeholder="Например: Какой ингредиент является основой классической пиццы Маргарита?"
                                    ></textarea>
                                </div>

                                <!-- ВАРИАНТЫ ОТВЕТОВ -->
                                <div class="col-12">
                                    <label class="form-label-modern">
                                        Варианты ответов
                                        <span class="hint-text">Нажмите на кружок слева, чтобы отметить правильный</span>
                                    </label>
                                    <div class="answers-editor">
                                        <div
                                            v-for="(answer, aIndex) in question.answers"
                                            :key="answer.id"
                                            class="answer-editor-row"
                                            :class="{ 'is-correct': answer.isCorrect }"
                                        >
                                            <button
                                                type="button"
                                                class="correct-toggle"
                                                :class="{ 'active': answer.isCorrect }"
                                                @click="toggleCorrectAnswer(question, aIndex)"
                                                :title="answer.isCorrect ? 'Правильный ответ' : 'Сделать правильным'"
                                            >
                                                <i class="fa-solid" :class="answer.isCorrect ? 'fa-check' : 'fa-circle'"></i>
                                            </button>
                                            <span class="answer-letter-badge">{{ getLetter(aIndex) }}</span>
                                            <input
                                                type="text"
                                                v-model="answer.text"
                                                class="form-input-modern form-input-sm answer-input"
                                                :placeholder="`Вариант ${getLetter(aIndex)}`"
                                                maxlength="200"
                                            >
                                            <span class="correct-label" v-if="answer.isCorrect">
                                                <i class="fa-solid fa-star"></i> Правильный
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ПОДСКАЗКА/ОБЪЯСНЕНИЕ (опционально) -->
                                <div class="col-12">
                                    <label class="form-label-modern">
                                        Объяснение ответа (опционально)
                                        <span class="unit-hint">покажется после ответа</span>
                                    </label>
                                    <input
                                        type="text"
                                        v-model="question.explanation"
                                        class="form-input-modern form-input-sm"
                                        placeholder="Например: Классическая Маргарита символизирует цвета итальянского флага"
                                        maxlength="300"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="add-question-block mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="text-muted small">
                            <i class="fa-solid fa-circle-info"></i>
                            Рекомендуется 5-20 вопросов для увлекательной викторины
                        </div>
                        <button class="btn-modern btn-primary" @click="addQuestion">
                            <i class="fa-solid fa-plus-circle"></i>
                            Добавить вопрос
                        </button>
                    </div>
                </div>
            </div>

            <!-- 🆕 ПРЕДПРОСМОТР ВИКТОРИНЫ -->
            <div class="modern-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="fa-solid fa-eye"></i> Предпросмотр викторины</h5>
                    <button class="btn-modern btn-secondary btn-sm" @click="previewMode = !previewMode">
                        <i class="fa-solid" :class="previewMode ? 'fa-eye-slash' : 'fa-eye'"></i>
                        {{ previewMode ? 'Скрыть' : 'Показать' }}
                    </button>
                </div>

                <div v-if="previewMode" class="quiz-preview">
                    <div class="preview-info">
                        <div class="preview-stat">
                            <i class="fa-solid fa-list-ol"></i>
                            <span>Вопросов: <strong>{{ form.questions.length }}</strong></span>
                        </div>
                        <div class="preview-stat">
                            <i class="fa-solid fa-trophy"></i>
                            <span>Для победы: <strong>{{ Math.ceil(form.questions.length * form.win_threshold / 100) }} из {{ form.questions.length }}</strong></span>
                        </div>
                        <div class="preview-stat">
                            <i class="fa-solid fa-gift"></i>
                            <span>Приз: <strong>{{ form.prize_amount }} бонусов</strong></span>
                        </div>
                    </div>

                    <div class="preview-questions">
                        <div
                            v-for="(q, idx) in form.questions.slice(0, 3)"
                            :key="q.id"
                            class="preview-question-item"
                        >
                            <div class="preview-q-number">{{ idx + 1 }}</div>
                            <div class="preview-q-content">
                                <div class="preview-q-text">{{ q.text || 'Вопрос не задан' }}</div>
                                <div class="preview-q-answers">
                                    <div
                                        v-for="(a, aIdx) in q.answers"
                                        :key="a.id"
                                        class="preview-answer"
                                        :class="{ 'is-correct': a.isCorrect }"
                                    >
                                        <span class="preview-letter">{{ getLetter(aIdx) }}</span>
                                        <span class="preview-text">{{ a.text || 'Пусто' }}</span>
                                        <i v-if="a.isCorrect" class="fa-solid fa-check preview-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.questions.length > 3" class="preview-more">
                            <i class="fa-solid fa-ellipsis"></i>
                            ещё {{ form.questions.length - 3 }} вопросов
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 СТАТИСТИКА / ВАЛИДАЦИЯ -->
            <div class="modern-card" v-if="validationErrors.length > 0">
                <h5 class="card-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Ошибки валидации</h5>
                <div class="validation-errors-list">
                    <div v-for="(err, idx) in validationErrors" :key="idx" class="validation-error-item">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>{{ err }}</span>
                    </div>
                </div>
            </div>

            <!-- 🆕 КНОПКА СОХРАНЕНИЯ -->
            <div class="save-actions-bar">
                <button
                    class="btn-modern btn-primary btn-lg"
                    @click="saveSettings"
                    :disabled="isSaving || validationErrors.length > 0"
                >
                    <i v-if="isSaving" class="fa-solid fa-circle-notch fa-spin me-2"></i>
                    <i v-else class="fa-solid fa-floppy-disk me-2"></i>
                    {{ isSaving ? 'Сохранение...' : 'Сохранить настройки викторины' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "QuizAdminSettings",
    props: { modelValue: { type: Object, default: () => ({}) } },
    emits: ['update:modelValue'],

    data() {
        return {
            isSaving: false,
            previewMode: false,
            form: {
                can_play: true,
                shuffle_questions: true,
                win_threshold: 75,
                prize_amount: 400,
                attempts_per_period: 1,
                interval: 1,
                time_per_question: 0,
                title: 'Викторина',
                subtitle: 'Отвечай на вопросы и зарабатывай бонусы!',
                rules: 'Отвечайте правильно на 75% вопросов, чтобы получить приз!',
                questions: [
                    {
                        id: 1,
                        text: 'Какой ингредиент является основой классической пиццы Маргарита?',
                        explanation: 'Классическая Маргарита символизирует цвета итальянского флага: красный (томаты), белый (моцарелла) и зелёный (базилик).',
                        answers: [
                            { id: 'a', text: 'Томатный соус, моцарелла и базилик', isCorrect: true },
                            { id: 'b', text: 'Грибы, ветчина и оливки', isCorrect: false },
                            { id: 'c', text: 'Ананасы и бекон', isCorrect: false },
                            { id: 'd', text: 'Только сыр и тесто', isCorrect: false }
                        ],
                        edit: false
                    },
                    {
                        id: 2,
                        text: 'Сколько минут обычно выпекается неаполитанская пицца в дровяной печи?',
                        explanation: 'Настоящая неаполитанская пицца выпекается при температуре около 485°C всего 60-90 секунд.',
                        answers: [
                            { id: 'a', text: '15-20 минут', isCorrect: false },
                            { id: 'b', text: '60-90 секунд', isCorrect: true },
                            { id: 'c', text: '5-7 минут', isCorrect: false },
                            { id: 'd', text: '30 минут', isCorrect: false }
                        ],
                        edit: false
                    },
                    {
                        id: 3,
                        text: 'Какой сыр традиционно используется в пицце Четыре Сыра?',
                        answers: [
                            { id: 'a', text: 'Только Моцарелла', isCorrect: false },
                            { id: 'b', text: 'Моцарелла, Горгонзола, Пармезан и Эмменталь', isCorrect: true },
                            { id: 'c', text: 'Чеддер и Гауда', isCorrect: false },
                            { id: 'd', text: 'Фета и Брынза', isCorrect: false }
                        ],
                        edit: false
                    }
                ]
            }
        };
    },

    computed: {
        validationErrors() {
            const errors = [];

            if (this.form.questions.length === 0) {
                errors.push('Добавьте хотя бы один вопрос');
            }

            this.form.questions.forEach((q, idx) => {
                const qNum = idx + 1;

                if (!q.text || !q.text.trim()) {
                    errors.push(`Вопрос ${qNum}: не задан текст вопроса`);
                }

                if (!q.answers || q.answers.length !== 4) {
                    errors.push(`Вопрос ${qNum}: должно быть ровно 4 варианта ответа (сейчас: ${q.answers?.length || 0})`);
                } else {
                    const emptyAnswers = q.answers.filter(a => !a.text || !a.text.trim());
                    if (emptyAnswers.length > 0) {
                        errors.push(`Вопрос ${qNum}: ${emptyAnswers.length} вариант(ов) ответа без текста`);
                    }

                    const correctCount = q.answers.filter(a => a.isCorrect).length;
                    if (correctCount !== 1) {
                        errors.push(`Вопрос ${qNum}: должен быть ровно 1 правильный ответ (сейчас: ${correctCount})`);
                    }
                }
            });

            if (this.form.win_threshold < 1 || this.form.win_threshold > 100) {
                errors.push('Процент для победы должен быть от 1 до 100');
            }

            if (!this.form.prize_amount || this.form.prize_amount <= 0) {
                errors.push('Приз за победу должен быть больше 0');
            }

            return errors;
        }
    },

    watch: {
        form: {
            handler: function (newValue) {
                const cleanForm = JSON.parse(JSON.stringify(newValue));
                cleanForm.questions = cleanForm.questions.map(q => {
                    const { edit, ...rest } = q;
                    return rest;
                });
                this.$emit("update:modelValue", cleanForm);
            },
            deep: true
        },
        modelValue: {
            handler: function (newValue) {
                if (newValue) {
                    this.form.can_play = newValue.can_play ?? this.form.can_play;
                    this.form.shuffle_questions = newValue.shuffle_questions ?? this.form.shuffle_questions;
                    this.form.win_threshold = newValue.win_threshold ?? this.form.win_threshold;
                    this.form.prize_amount = newValue.prize_amount ?? this.form.prize_amount;
                    this.form.attempts_per_period = newValue.attempts_per_period ?? this.form.attempts_per_period;
                    this.form.interval = newValue.interval ?? this.form.interval;
                    this.form.time_per_question = newValue.time_per_question ?? this.form.time_per_question;
                    this.form.title = newValue.title ?? this.form.title;
                    this.form.subtitle = newValue.subtitle ?? this.form.subtitle;
                    this.form.rules = newValue.rules ?? this.form.rules;

                    if (newValue.questions && Array.isArray(newValue.questions) && newValue.questions.length > 0) {
                        this.form.questions = newValue.questions.map(q => ({
                            ...q,
                            answers: q.answers || this.getDefaultAnswers(),
                            edit: false
                        }));
                    }
                }
            },
            immediate: true
        }
    },

    methods: {
        getLetter(index) {
            return ['A', 'B', 'C', 'D'][index] || String.fromCharCode(65 + index);
        },

        getDefaultAnswers() {
            return [
                { id: 'a', text: '', isCorrect: false },
                { id: 'b', text: '', isCorrect: false },
                { id: 'c', text: '', isCorrect: false },
                { id: 'd', text: '', isCorrect: false }
            ];
        },

        getCorrectAnswerCount(question) {
            return (question.answers || []).filter(a => a.isCorrect).length;
        },

        hasQuestionError(question) {
            if (!question.text || !question.text.trim()) return true;
            if (!question.answers || question.answers.length !== 4) return true;
            if (question.answers.some(a => !a.text || !a.text.trim())) return true;
            if (this.getCorrectAnswerCount(question) !== 1) return true;
            return false;
        },

        toggleCorrectAnswer(question, answerIndex) {
            // Сбрасываем все остальные ответы и устанавливаем текущий как правильный
            question.answers.forEach((a, idx) => {
                a.isCorrect = idx === answerIndex;
            });
        },

        addQuestion() {
            const newId = this.form.questions.length > 0
                ? Math.max(...this.form.questions.map(q => q.id || 0)) + 1
                : 1;

            this.form.questions.push({
                id: newId,
                text: '',
                explanation: '',
                answers: [
                    { id: 'a', text: '', isCorrect: false },
                    { id: 'b', text: '', isCorrect: false },
                    { id: 'c', text: '', isCorrect: false },
                    { id: 'd', text: '', isCorrect: true } // По умолчанию последний — правильный
                ],
                edit: true
            });
        },

        removeQuestion(index) {
            if (confirm(`Удалить вопрос "${this.form.questions[index].text || 'без названия'}"?`)) {
                this.form.questions.splice(index, 1);
                this.form.questions.forEach((q, idx) => { q.id = idx + 1; });
            }
        },

        moveQuestion(index, direction) {
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= this.form.questions.length) return;

            const temp = this.form.questions[index];
            this.form.questions[index] = this.form.questions[newIndex];
            this.form.questions[newIndex] = temp;
        },

        async saveSettings() {
            if (this.validationErrors.length > 0) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: `Найдено ошибок: ${this.validationErrors.length}. Исправьте их перед сохранением.`,
                    type: 'error'
                });
                return;
            }

            this.isSaving = true;

            try {
                const cleanForm = JSON.parse(JSON.stringify(this.form));
                cleanForm.questions = cleanForm.questions.map(q => {
                    const { edit, ...rest } = q;
                    return rest;
                });

                const response = await axios.put('/admin/tenant-settings/quiz', {
                    quiz: cleanForm
                });

                this.$notify?.({
                    title: 'Успех',
                    text: response.data.message || 'Настройки викторины сохранены',
                    type: 'success'
                });

            } catch (error) {
                console.error('Ошибка сохранения:', error);
                const errorMsg = error.response?.data?.message || 'Не удалось сохранить настройки';
                this.$notify?.({ title: 'Ошибка', text: errorMsg, type: 'error' });
            } finally {
                this.isSaving = false;
            }
        }
    }
};
</script>

<style scoped>
.quiz-admin-page {
    background-color: #F8FAFC;
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #334155;
}

.modern-header { display: flex; justify-content: space-between; align-items: center; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px; }
.page-subtitle { font-size: 0.9rem; color: #64748B; margin: 4px 0 0 0; }

.modern-card {
    background: white; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
}
.card-title { font-size: 1rem; font-weight: 600; color: #0F172A; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; }
.card-title i { color: #30cfd0; }
.card-title.text-danger { color: #EF4444; }
.card-title.text-danger i { color: #EF4444; }

.form-label-modern { font-size: 0.8rem; font-weight: 600; color: #64748B; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; }
.char-counter { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.unit-hint { font-weight: 400; color: #94A3B8; font-size: 0.7rem; }
.hint-text { font-weight: 400; color: #94A3B8; font-size: 0.7rem; font-style: italic; }

.form-input-modern {
    width: 100%; padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s; background: #F8FAFC;
    font-family: inherit;
}
.form-input-modern.form-input-sm { padding: 6px 10px; font-size: 0.85rem; }
.form-input-modern:focus { outline: none; border-color: #30cfd0; background: white; box-shadow: 0 0 0 3px rgba(48, 207, 208, 0.1); }
textarea.form-input-modern { resize: vertical; min-height: 60px; }

.input-with-suffix {
    position: relative;
}
.input-with-suffix input { padding-right: 36px; }
.input-suffix {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    font-weight: 600;
    font-size: 0.85rem;
    pointer-events: none;
}

.modern-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
.modern-switch input { display: none; }
.switch-slider { width: 40px; height: 22px; background: #CBD5E1; border-radius: 99px; position: relative; transition: background 0.3s ease; }
.switch-slider::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modern-switch input:checked + .switch-slider { background: #30cfd0; }
.modern-switch input:checked + .switch-slider::after { transform: translateX(18px); }
.switch-label { font-weight: 500; color: #334155; font-size: 0.9rem; }

.badge-sector { padding: 4px 10px; background: #ECFEFF; color: #0891B2; border-radius: 99px; font-weight: 700; font-size: 0.8rem; }

.validation-warning {
    color: #EF4444;
    font-weight: 600;
    margin-left: 12px;
}

/* ========================================== */
/* ВОПРОСЫ                                     */
/* ========================================== */
.questions-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.question-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}

.question-card:hover {
    border-color: #CBD5E1;
}

.question-card.has-error {
    border-color: rgba(239, 68, 68, 0.3);
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.03) 0%, transparent 100%);
}

.question-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.question-header:hover {
    background: #F1F5F9;
}

.question-number-badge {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.85rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(48, 207, 208, 0.3);
}

.question-preview {
    flex: 1;
    min-width: 0;
}

.question-text-preview {
    font-weight: 600;
    color: #0F172A;
    font-size: 0.9rem;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.question-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.answers-count {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #64748B;
    font-weight: 600;
}

.correct-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: rgba(16, 185, 129, 0.15);
    color: #10B981;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
}

.correct-badge.error {
    background: rgba(239, 68, 68, 0.15);
    color: #EF4444;
}

.question-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}

.btn-icon {
    width: 32px; height: 32px; border-radius: 6px; border: 1px solid #E2E8F0; background: white;
    color: #64748B; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.btn-icon.btn-success { color: #10B981; border-color: #10B981; }
.btn-icon.btn-success:hover { background: #10B981; color: white; }
.btn-icon.btn-danger { color: #EF4444; border-color: #EF4444; }
.btn-icon.btn-danger:hover { background: #EF4444; color: white; }
.btn-icon.btn-secondary { color: #64748B; }
.btn-icon.btn-secondary:hover:not(:disabled) { background: #F1F5F9; color: #334155; }
.btn-icon:disabled { opacity: 0.35; cursor: not-allowed; }

.question-edit-mode {
    padding: 0 12px 16px 12px;
    border-top: 1px dashed #E2E8F0;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ========================================== */
/* РЕДАКТОР ОТВЕТОВ                           */
/* ========================================== */
.answers-editor {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.answer-editor-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: white;
    border: 2px solid #E2E8F0;
    border-radius: 10px;
    transition: all 0.2s;
}

.answer-editor-row.is-correct {
    border-color: #10B981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%);
}

.correct-toggle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #E2E8F0;
    background: white;
    color: #94A3B8;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.correct-toggle:hover {
    border-color: #10B981;
    color: #10B981;
}

.correct-toggle.active {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    border-color: #10B981;
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
}

.answer-letter-badge {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #F1F5F9;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.answer-editor-row.is-correct .answer-letter-badge {
    background: rgba(16, 185, 129, 0.15);
    color: #10B981;
}

.answer-input {
    flex: 1;
    min-width: 0;
    border: 1px solid #E2E8F0 !important;
}

.answer-editor-row.is-correct .answer-input {
    border-color: rgba(16, 185, 129, 0.3) !important;
    background: rgba(16, 185, 129, 0.02) !important;
}

.correct-label {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    flex-shrink: 0;
    white-space: nowrap;
}

.add-question-block {
    display: flex;
    justify-content: center;
}

/* ========================================== */
/* ПРЕДПРОСМОТР                                */
/* ========================================== */
.quiz-preview {
    padding: 16px;
    background: linear-gradient(135deg, #ECFEFF 0%, #F0F9FF 100%);
    border-radius: 12px;
    border: 1px solid #E2E8F0;
}

.preview-info {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 16px;
    background: white;
    border-radius: 10px;
    margin-bottom: 16px;
}

.preview-stat {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #334155;
}

.preview-stat i {
    color: #30cfd0;
    font-size: 1rem;
}

.preview-stat strong {
    color: #0F172A;
}

.preview-questions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.preview-question-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: white;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
}

.preview-q-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.preview-q-content {
    flex: 1;
    min-width: 0;
}

.preview-q-text {
    font-weight: 600;
    font-size: 0.9rem;
    color: #0F172A;
    margin-bottom: 8px;
    line-height: 1.4;
}

.preview-q-answers {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.preview-answer {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    background: #F8FAFC;
    border-radius: 6px;
    font-size: 0.8rem;
    color: #64748B;
}

.preview-answer.is-correct {
    background: rgba(16, 185, 129, 0.1);
    color: #065F46;
    font-weight: 600;
}

.preview-letter {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    background: white;
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.7rem;
    flex-shrink: 0;
}

.preview-answer.is-correct .preview-letter {
    background: #10B981;
    color: white;
}

.preview-text {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.preview-check {
    color: #10B981;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.preview-more {
    text-align: center;
    padding: 8px;
    color: #64748B;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

/* ========================================== */
/* ВАЛИДАЦИЯ                                   */
/* ========================================== */
.validation-errors-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.validation-error-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 14px;
    background: #FEF2F2;
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 8px;
    color: #991B1B;
    font-size: 0.85rem;
    line-height: 1.4;
}

.validation-error-item i {
    color: #EF4444;
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 2px;
}

/* ========================================== */
/* КНОПКИ                                      */
/* ========================================== */
.btn-modern {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
    cursor: pointer; transition: all 0.2s; padding: 8px 16px;
}
.btn-modern.btn-primary { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; box-shadow: 0 4px 6px -1px rgba(48, 207, 208, 0.3); }
.btn-modern.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 12px -2px rgba(48, 207, 208, 0.4); }
.btn-modern.btn-secondary { background: white; color: #475569; border-color: #E2E8F0; }
.btn-modern.btn-secondary:hover:not(:disabled) { background: #F1F5F9; }
.btn-modern:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-sm { padding: 4px 8px; font-size: 0.8rem; }

.save-actions-bar {
    position: sticky;
    bottom: 20px;
    display: flex;
    justify-content: center;
    margin-top: 20px;
    z-index: 50;
}

.btn-modern.btn-lg {
    padding: 14px 32px;
    font-size: 1.05rem;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(48, 207, 208, 0.3);
}

.btn-modern.btn-lg:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(48, 207, 208, 0.4);
}

@media (max-width: 768px) {
    .question-header { flex-wrap: wrap; }
    .question-actions { width: 100%; justify-content: flex-end; margin-top: 4px; }
    .answer-editor-row { flex-wrap: wrap; }
    .correct-label { width: 100%; justify-content: center; }
    .preview-info { flex-direction: column; gap: 8px; }
    .preview-question-item { flex-direction: column; gap: 8px; }
    .preview-q-number { align-self: flex-start; }
}
</style>
