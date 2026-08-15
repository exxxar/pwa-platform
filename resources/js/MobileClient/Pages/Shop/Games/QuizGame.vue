<template>
    <div class="quiz-game-page">

        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="game-hero">
            <div class="hero-background"></div>
            <div class="hero-particles">
                <span v-for="i in 15" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
            <div class="hero-content">
                <div class="hero-icon-wrapper">
                    <div class="hero-icon">
                        <i class="fa-solid fa-question"></i>
                    </div>
                    <div class="hero-sparkle sparkle-1">💡</div>
                    <div class="hero-sparkle sparkle-2">🧠</div>
                </div>
                <h1 class="hero-title">Викторина</h1>
                <p class="hero-subtitle">Отвечай на вопросы и зарабатывай бонусы!</p>

                <div class="hero-stats">
                    <div class="stat-block">
                        <div class="stat-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ userBalance }}</div>
                            <div class="stat-label">Ваши бонусы</div>
                        </div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-block">
                        <div class="stat-icon cost-icon">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">{{ attemptsLeft }}</div>
                            <div class="stat-label">Попыток</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="game-content">

            <!-- ========================================== -->
            <!-- ЭКРАН ЗАГРУЗКИ -->
            <!-- ========================================== -->
            <div v-if="gameState === 'loading'" class="loading-screen">
                <div class="loading-spinner"></div>
                <p>Загружаем вопросы...</p>
            </div>


            <div v-else-if="gameState === 'idle'" class="start-section">
                <div class="game-preview">
                    <div class="preview-icon">
                        <i class="fa-solid fa-question"></i>
                    </div>
                    <h3 class="preview-title">Готовы к викторине?</h3>
                    <p class="preview-desc">
                        Ответьте на вопросы и заработайте бонусы!
                        Минимум 75% правильных ответов для победы.
                    </p>
                    <button
                        class="start-btn"
                        @click="startQuiz"
                        :disabled="attemptsLeft <= 0 || userBalance < gameCost || isProcessing"
                    >
                        <i :class="isProcessing ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-play'"></i>
                        <span>Начать (−{{ gameCost }}₽ кэшбэка)</span>
                    </button>
                    <p v-if="userBalance < gameCost" class="insufficient">Недостаточно кэшбэка</p>
                </div>
            </div>

            <!-- ЭКРАН: НЕТ ПОПЫТОК -->
            <div v-else-if="gameState === 'no_attempts'" class="error-screen">
                <div class="error-icon">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h3>Попытки закончились</h3>
                <p>Возвращайтесь завтра для новой игры!</p>
            </div>

            <!-- ========================================== -->
            <!-- ЭКРАН ИГРЫ -->
            <!-- ========================================== -->
            <div v-else-if="gameState === 'playing' && currentQuestion" class="quiz-container">

                <!-- Прогресс-бар -->
                <div class="progress-section">
                    <div class="progress-info">
                        <span>Вопрос {{ currentQuestionIndex + 1 }} из {{ questions.length }}</span>
                        <span>Верно: {{ score }}</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                </div>

                <!-- Карточка вопроса -->
                <div class="question-card">
                    <div class="question-icon">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                    <h3 class="question-text">{{ currentQuestion.text }}</h3>
                </div>

                <!-- Варианты ответов -->
                <div class="answers-grid">
                    <button
                        v-for="answer in currentQuestion.answers"
                        :key="answer.id"
                        class="answer-btn"
                        :class="{
                            'is-correct': isAnswered && answer.isCorrect,
                            'is-incorrect': isAnswered && selectedAnswer === answer.id && !answer.isCorrect,
                            'is-disabled': isAnswered && selectedAnswer !== answer.id
                        }"
                        :disabled="isAnswered"
                        @click="selectAnswer(answer.id)"
                    >
                        <div class="answer-content">
                            <span class="answer-letter">{{ getLetter(answer.id) }}</span>
                            <span class="answer-text">{{ answer.text }}</span>
                        </div>
                        <div class="answer-status">
                            <i v-if="isAnswered && answer.isCorrect" class="fa-solid fa-check"></i>
                            <i v-else-if="isAnswered && selectedAnswer === answer.id && !answer.isCorrect" class="fa-solid fa-xmark"></i>
                        </div>
                    </button>
                </div>
            </div>

            <div v-else-if="gameState === 'finished'" class="review-container">
                <div class="review-header">
                    <h2 class="review-title">Результаты викторины</h2>
                    <div class="score-badge" :class="isWin ? 'win' : 'loss'">
                        <span class="score-value">{{ score }}</span>
                        <span class="score-total">из {{ reviewData.length }}</span>
                    </div>
                    <p class="review-subtitle">
                        {{ isWin
                        ? `Отличный результат! ${winPercentage}% правильных ответов. Приз: +${prize} бонусов`
                        : `Не хватило совсем чуть-чуть. ${winPercentage}% — нужно минимум 75%.` }}
                    </p>
                </div>

                <div class="questions-review-list">
                    <div v-for="(q, qIndex) in reviewData" :key="q.id" class="review-question-card">
                        <div class="review-q-header">
                            <span class="q-number">{{ qIndex + 1 }}</span>
                            <span class="q-text">{{ q.text }}</span>
                        </div>
                        <div class="review-answers">
                            <div
                                v-for="ans in q.answers"
                                :key="ans.id"
                                class="review-answer"
                                :class="{
                        'is-user-correct': q.user_selected_answer === ans.id && ans.is_correct,
                        'is-user-wrong': q.user_selected_answer === ans.id && !ans.is_correct,
                        'is-missed-correct': ans.is_correct && q.user_selected_answer !== ans.id
                    }"
                            >
                                <span class="ans-text">{{ ans.text }}</span>
                                <div class="ans-status">
                                    <i v-if="ans.is_correct" class="fa-solid fa-check status-icon correct"></i>
                                    <i v-else-if="q.user_selected_answer === ans.id" class="fa-solid fa-xmark status-icon wrong"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="restart-btn" @click="restartQuiz" :disabled="attemptsLeft <= 0">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>{{ attemptsLeft > 0 ? 'Пройти ещё раз' : 'Попытки закончились' }}</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- ЭКРАН ОШИБКИ -->
            <!-- ========================================== -->
            <div v-else-if="gameState === 'error'" class="error-screen">
                <div class="error-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3>Не удалось загрузить вопросы</h3>
                <p>Проверьте подключение к интернету и попробуйте снова.</p>
                <button class="retry-btn" @click="loadQuizData">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>Попробовать снова</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- АДМИН-ПАНЕЛЬ -->
            <!-- ========================================== -->
            <div v-if="isAdmin && gameState !== 'loading'" class="admin-section">
                <button class="admin-toggle" @click="showAdmin = !showAdmin">
                    <div class="admin-toggle-content">
                        <div class="admin-icon">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <div class="admin-info">
                            <span class="admin-title">Панель администратора</span>
                            <span class="admin-hint">Управление игрой</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down admin-arrow" :class="{ 'rotated': showAdmin }"></i>
                </button>
                <transition name="slide-down">
                    <div v-if="showAdmin" class="admin-content">
                        <div class="admin-grid">
                            <button class="admin-btn" @click="adminResetAll">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Сбросить всем</span>
                            </button>
                            <button class="admin-btn" @click="adminAddAttempts">
                                <i class="fa-solid fa-plus"></i>
                                <span>+1 попытка</span>
                            </button>
                            <button class="admin-btn" @click="adminAddBalance">
                                <i class="fa-solid fa-coins"></i>
                                <span>+500 бонусов</span>
                            </button>
                        </div>
                    </div>
                </transition>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ИТОГОВЫЙ ВЕРДИКТ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showResultModal" class="modal-overlay" @click.self="closeResultModal">
                <div class="modal-container result-modal">
                    <div class="result-confetti" v-if="isWin">
                        <span v-for="i in 40" :key="i" class="confetti-piece" :style="confettiStyle(i)"></span>
                    </div>
                    <div class="result-content">
                        <div class="result-rarity-badge" :class="isWin ? 'rarity-epic' : 'rarity-common'">
                            {{ isWin ? 'ПОЗДРАВЛЯЕМ!' : 'ПОПРОБУЙТЕ ЕЩЁ' }}
                        </div>
                        <div class="result-icon-wrapper">
                            <div class="result-icon" :class="isWin ? 'rarity-epic' : 'rarity-common'">
                                <i :class="isWin ? 'fa-solid fa-trophy' : 'fa-solid fa-face-sad-tear'"></i>
                            </div>
                            <div class="result-glow"></div>
                        </div>

                        <h3 class="result-title">{{ isWin ? 'Вы прошли викторину!' : 'Не хватило знаний' }}</h3>
                        <p class="result-description">
                            Вы ответили правильно на <strong>{{ score }} из {{ questions.length }}</strong> вопросов ({{ winPercentage }}%).
                        </p>

                        <div v-if="isWin" class="result-details">
                            <div class="detail-row">
                                <i class="fa-solid fa-gift"></i>
                                <span>Ваш приз: <strong>+{{ prize }} бонусов</strong></span>
                            </div>
                            <div class="detail-row">
                                <i class="fa-solid fa-ticket"></i>
                                <span>Ставка: <strong>-{{ gameCost }}</strong></span>
                            </div>
                            <div class="detail-row" :style="{ color: (prize - gameCost) >= 0 ? '#198754' : '#dc3545' }">
                                <i :class="(prize - gameCost) >= 0 ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down'"></i>
                                <span>Итого: <strong>{{ (prize - gameCost) >= 0 ? '+' : '' }}{{ prize - gameCost }}</strong></span>
                            </div>
                        </div>

                        <button class="result-btn" :class="isWin ? 'btn-win' : 'btn-loss'" @click="closeResultModal">
                            <i class="fa-solid" :class="isWin ? 'fa-check' : 'fa-arrow-down'"></i>
                            <span>{{ isWin ? 'Забрать приз' : 'Смотреть результаты' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>


export default {
    name: "QuizGame",


    data() {
        return {
            gameState: 'loading', // 'loading', 'playing', 'finished', 'error'
            questions: [], // БЕЗ isCorrect!
            currentQuestionIndex: 0,
            score: 0,
            selectedAnswer: null,
            isAnswered: false,
            isProcessing: false, // 🆕 Защита от race condition

            userBalance: 0,
            attemptsLeft: 1,
            gameCost: 500,

            // Сессия
            sessionToken: null,
            reviewData: [], // Результаты для экрана finished
            winPercentage: 0,
            isWin: false,
            prize: 0,

            // UI
            showAdmin: false,
            showResultModal: false,
        };
    },

    computed: {
        isAdmin() {
            const user = window.TenantUser;
            return user?.role === 'admin' || user?.is_admin === true;
        },

        currentQuestion() {
            return this.questions[this.currentQuestionIndex];
        },

        progressPercent() {
            if (this.questions.length === 0) return 0;
            return ((this.currentQuestionIndex) / this.questions.length) * 100;
        },
    },

    async mounted() {
        await this.loadQuizData();
    },

    methods: {

        getLetter(id) {
            const letters = { 'a': 'A', 'b': 'B', 'c': 'C', 'd': 'D' };
            return letters[id] || id;
        },

        closeResultModal() {
            this.showResultModal = false;
        },

        restartQuiz() {
            // Сброс состояния
            this.sessionToken = null;
            this.questions = [];
            this.currentQuestionIndex = 0;
            this.score = 0;
            this.selectedAnswer = null;
            this.isAnswered = false;
            this.reviewData = [];
            this.winPercentage = 0;
            this.isWin = false;
            this.prize = 0;
            this.gameState = 'idle';

            // Перезагружаем данные
            this.loadQuizData();
        },


        async loadQuizData() {
            this.gameState = 'loading';
            try {
                const response = await axios.get('/quiz/state');

                if (response.data?.success) {
                    this.userBalance = response.data.balance ?? 0;
                    this.attemptsLeft = response.data.attempts_left ?? 1;
                    this.gameCost = response.data.game_cost ?? 500;

                    // Восстановление активной сессии
                    if (response.data.active_session && !response.data.active_session.finished) {
                        this.restoreSession(response.data.active_session);
                    } else {
                        this.gameState = 'playing';
                        // Не показываем вопросы сразу — ждём старта
                        this.gameState = this.attemptsLeft > 0 ? 'idle' : 'no_attempts';
                    }
                } else {
                    this.gameState = 'error';
                }
            } catch (error) {
                console.error('Ошибка загрузки викторины:', error);
                this.gameState = 'error';
            }
        },

        restoreSession(session) {
            this.sessionToken = session.token;
            this.questions = session.questions;
            this.score = session.score || 0;
            this.currentQuestionIndex = Object.keys(session.user_answers || {}).length;
            this.gameState = 'playing';
        },


        async selectAnswer(answerId) {
            if (this.isAnswered || this.isProcessing) return;

            this.selectedAnswer = answerId;
            this.isAnswered = true;
            this.isProcessing = true;

            try {
                const response = await axios.post('/quiz/answer', {
                    token: this.sessionToken,
                    question_id: this.currentQuestion.id,
                    answer_id: answerId,
                });

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка ответа');
                }

                const data = response.data;

                // Обновляем счёт
                this.score = data.score;

                // Сохраняем для экрана результатов (с правильным ответом от сервера)
                const questionWithResult = {
                    ...this.currentQuestion,
                    user_selected_answer: answerId,
                    is_correct: data.is_correct,
                    correct_answer: data.correct_answer || answerId, // Если правильно — наш ответ правильный
                    answers: this.currentQuestion.answers.map(a => ({
                        ...a,
                        isCorrect: (data.is_correct && a.id === answerId) || (!data.is_correct && a.id === data.correct_answer),
                    })),
                };

                // Добавляем в review
                this.reviewData[this.currentQuestionIndex] = questionWithResult;

                // Ждём анимацию
                await new Promise(resolve => setTimeout(resolve, 1200));

                // Переходим к следующему вопросу или завершаем
                if (!data.is_complete) {
                    this.nextQuestion();
                } else {
                    await this.finishQuiz();
                }

            } catch (error) {
                console.error('Ошибка ответа:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось отправить ответ',
                    type: 'error',
                });

                // Откатываем состояние
                this.isAnswered = false;
                this.selectedAnswer = null;
            } finally {
                this.isProcessing = false;
            }
        },

        nextQuestion() {
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
                this.selectedAnswer = null;
                this.isAnswered = false;
            }
        },



        async finishQuiz() {
            this.isProcessing = true;

            try {
                const response = await axios.post('/quiz/finish', {
                    token: this.sessionToken,
                });

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка завершения');
                }

                const data = response.data;

                // Обновляем состояние
                this.userBalance = data.balance;
                if (window.TenantUser) {
                    window.TenantUser.cashback_balance = data.balance;
                }

                this.winPercentage = data.win_percentage;
                this.isWin = data.is_win;
                this.prize = data.prize;
                this.score = data.score;

                // Обновляем review данными с сервера
                if (data.review) {
                    this.reviewData = data.review.map(q => ({
                        ...q,
                        answers: q.answers,
                        user_selected_answer: q.user_answer_id,
                    }));
                }

                // Переходим на экран результатов
                this.gameState = 'finished';
                this.attemptsLeft--; // Локально для UI

                setTimeout(() => {
                    this.showResultModal = true;
                }, 500);

            } catch (error) {
                console.error('Ошибка завершения:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось завершить викторину',
                    type: 'error',
                });
            } finally {
                this.isProcessing = false;
            }
        },





        async startQuiz() {
            if (this.attemptsLeft <= 0 || this.userBalance < this.gameCost || this.isProcessing) return;

            this.isProcessing = true;

            try {
                const response = await axios.post('/quiz/start');

                if (!response.data?.success) {
                    throw new Error(response.data?.message || 'Ошибка старта');
                }

                // Обновляем баланс
                this.userBalance = response.data.balance;
                if (window.TenantUser) {
                    window.TenantUser.cashback_balance = response.data.balance;
                }

                // Инициализируем игру
                this.sessionToken = response.data.token;
                this.questions = response.data.questions; // БЕЗ isCorrect!
                this.currentQuestionIndex = 0;
                this.score = 0;
                this.selectedAnswer = null;
                this.isAnswered = false;
                this.reviewData = [];
                this.gameState = 'playing';

            } catch (error) {
                console.error('Ошибка старта:', error);

                if (error.response?.status === 403) {
                    this.$notify?.({
                        title: 'Нельзя начать',
                        text: error.response.data?.message || 'Проверьте условия',
                        type: 'warning',
                    });
                } else {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: error.response?.data?.message || 'Не удалось начать викторину',
                        type: 'error',
                    });
                }
            } finally {
                this.isProcessing = false;
            }
        },

        // ==========================================
        // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
        // ==========================================
        particleStyle(i) {
            const size = Math.random() * 6 + 3;
            const left = Math.random() * 100;
            const delay = Math.random() * 5;
            const duration = Math.random() * 10 + 10;
            return {
                width: `${size}px`, height: `${size}px`, left: `${left}%`,
                animationDelay: `${delay}s`, animationDuration: `${duration}s`,
            };
        },

        confettiStyle(i) {
            const colors = ['#30cfd0', '#330867', '#ffd700', '#ff6b6b', '#4ecdc4'];
            const color = colors[Math.floor(Math.random() * colors.length)];
            return {
                background: color, left: `${Math.random() * 100}%`,
                animationDelay: `${Math.random() * 2}s`,
                animationDuration: `${Math.random() * 2 + 2}s`,
                transform: `rotate(${Math.random() * 360}deg)`,
            };
        },


    },
};
</script>

<style scoped>
/* ... (Все предыдущие стили остаются без изменений) ... */

.quiz-game-page { min-height: 100vh; background: var(--bs-body-bg); }
.game-hero { position: relative; padding: 40px 24px 32px; background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; text-align: center; overflow: hidden; }
.hero-background { position: absolute; inset: 0; background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%); }
.hero-particles { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
.particle { position: absolute; bottom: -10px; background: rgba(255, 255, 255, 0.5); border-radius: 50%; animation: particleFloat linear infinite; }
@keyframes particleFloat { 0% { transform: translateY(0) rotate(0deg); opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; } }
.hero-content { position: relative; z-index: 1; }
.hero-icon-wrapper { position: relative; display: inline-block; margin-bottom: 16px; }
.hero-icon { width: 80px; height: 80px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 3px solid rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2); }
.hero-sparkle { position: absolute; font-size: 1.2rem; animation: sparkle 2s ease-in-out infinite; }
.sparkle-1 { top: -8px; right: -8px; animation-delay: 0s; }
.sparkle-2 { bottom: -8px; left: -8px; animation-delay: 0.7s; }
@keyframes sparkle { 0%, 100% { opacity: 0; transform: scale(0.5); } 50% { opacity: 1; transform: scale(1); } }
.hero-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 6px; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); }
.hero-subtitle { font-size: 0.95rem; opacity: 0.95; margin: 0 0 20px 0; }
.hero-stats { display: inline-flex; align-items: center; gap: 16px; padding: 12px 20px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 16px; }
.stat-block { display: flex; align-items: center; gap: 10px; }
.stat-icon { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #ffd700 0%, #ff9800 100%); color: #1a1a1a; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.stat-icon.cost-icon { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; }
.stat-info { text-align: left; }
.stat-value { font-size: 1.2rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.65rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-divider { width: 1px; height: 30px; background: rgba(255, 255, 255, 0.3); }

.game-content { padding: 20px 16px; max-width: 600px; margin: 0 auto; }

/* Экраны загрузки и ошибки */
.loading-screen, .error-screen { text-align: center; padding: 60px 20px; }
.loading-spinner { width: 40px; height: 40px; border: 4px solid rgba(48, 207, 208, 0.2); border-top-color: #30cfd0; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
@keyframes spin { to { transform: rotate(360deg); } }
.error-icon { width: 64px; height: 64px; border-radius: 50%; background: rgba(220, 53, 69, 0.1); color: #dc3545; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 16px; }
.retry-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #30cfd0; border: none; border-radius: 12px; color: white; font-weight: 600; cursor: pointer; margin-top: 16px; transition: all 0.2s ease; }
.retry-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(48, 207, 208, 0.4); }

/* Игровой экран */
.quiz-container { display: flex; flex-direction: column; gap: 20px; }
.progress-section { margin-bottom: 8px; }
.progress-info { display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; color: var(--bs-secondary-color); margin-bottom: 8px; }
.progress-bar-bg { width: 100%; height: 8px; background: var(--bs-secondary-bg); border-radius: 4px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: linear-gradient(90deg, #30cfd0 0%, #330867 100%); border-radius: 4px; transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
.question-card { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 16px; padding: 24px; text-align: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
.question-icon { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin: 0 auto 16px; }
.question-text { font-size: 1.1rem; font-weight: 700; color: var(--bs-body-color); line-height: 1.4; margin: 0; }
.answers-grid { display: flex; flex-direction: column; gap: 12px; }
.answer-btn { position: relative; display: flex; align-items: center; padding: 16px 20px; background: var(--bs-body-bg); border: 2px solid var(--bs-border-color); border-radius: 14px; cursor: pointer; transition: all 0.2s ease; text-align: left; }
.answer-btn:hover:not(:disabled) { border-color: #30cfd0; transform: translateX(4px); box-shadow: 0 4px 12px rgba(48, 207, 208, 0.15); }
.answer-content { display: flex; align-items: center; gap: 14px; flex: 1; }
.answer-letter { width: 32px; height: 32px; border-radius: 8px; background: var(--bs-secondary-bg); color: var(--bs-body-color); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; flex-shrink: 0; transition: all 0.2s ease; }
.answer-text { font-weight: 600; font-size: 0.95rem; color: var(--bs-body-color); }
.answer-status { font-size: 1.2rem; flex-shrink: 0; width: 24px; text-align: center; }
.answer-btn.is-correct { border-color: #198754; background: rgba(25, 135, 84, 0.08); }
.answer-btn.is-correct .answer-letter { background: #198754; color: white; }
.answer-btn.is-correct .answer-status { color: #198754; }
.answer-btn.is-incorrect { border-color: #dc3545; background: rgba(220, 53, 69, 0.08); animation: shake 0.4s ease; }
.answer-btn.is-incorrect .answer-letter { background: #dc3545; color: white; }
.answer-btn.is-incorrect .answer-status { color: #dc3545; }
.answer-btn.is-disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
@keyframes shake { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-6px); } 40% { transform: translateX(6px); } 60% { transform: translateX(-4px); } 80% { transform: translateX(4px); } }

/* ========================================== */
/* НОВЫЕ СТИЛИ: ЭКРАН РЕЗУЛЬТАТОВ (REVIEW)  */
/* ========================================== */
.review-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 40px;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.review-header {
    text-align: center;
    padding: 24px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.review-title {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--bs-body-color);
    margin: 0 0 16px 0;
}

.score-badge {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 24px;
    border-radius: 12px;
    margin-bottom: 12px;
}

.score-badge.win {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(32, 201, 151, 0.1) 100%);
    border: 2px solid #198754;
}

.score-badge.loss {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(200, 35, 51, 0.1) 100%);
    border: 2px solid #dc3545;
}

.score-value {
    font-size: 2rem;
    font-weight: 900;
    line-height: 1;
}

.score-badge.win .score-value { color: #198754; }
.score-badge.loss .score-value { color: #dc3545; }

.score-total {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin-top: 4px;
}

.review-subtitle {
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
    margin: 0;
    line-height: 1.4;
}

.questions-review-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.review-question-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.review-q-header {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
}

.q-number {
    width: 28px;
    height: 28px;
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

.q-text {
    font-weight: 700;
    font-size: 1rem;
    color: var(--bs-body-color);
    line-height: 1.4;
    padding-top: 3px;
}

.review-answers {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.review-answer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.ans-text { flex: 1; line-height: 1.3; }
.ans-status { width: 24px; text-align: center; flex-shrink: 0; }
.status-icon { font-size: 1.1rem; }
.status-icon.correct { color: #198754; }
.status-icon.wrong { color: #dc3545; }

/* Состояния ответов в обзоре */
.review-answer.is-user-correct {
    background: rgba(25, 135, 84, 0.08);
    border-color: rgba(25, 135, 84, 0.3);
    font-weight: 600;
}

.review-answer.is-user-wrong {
    background: rgba(220, 53, 69, 0.08);
    border-color: rgba(220, 53, 69, 0.3);
    text-decoration: line-through;
    opacity: 0.8;
}

.review-answer.is-missed-correct {
    background: rgba(25, 135, 84, 0.04);
    border: 1px dashed #198754;
    color: #198754;
    font-weight: 600;
}

.restart-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(48, 207, 208, 0.3);
    margin-top: 8px;
}

.restart-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(48, 207, 208, 0.4);
}

.restart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--bs-secondary-bg);
    color: var(--bs-secondary-color);
    box-shadow: none;
}

/* Админ-панель (стили без изменений) */
.admin-section { margin-top: 32px; }
.admin-toggle { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 14px; cursor: pointer; transition: all 0.2s ease; }
.admin-toggle-content { display: flex; align-items: center; gap: 12px; }
.admin-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #2c3e50 0%, #4a6278 100%); color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.admin-info { display: flex; flex-direction: column; text-align: left; }
.admin-title { font-weight: 700; font-size: 0.95rem; color: var(--bs-body-color); }
.admin-hint { font-size: 0.75rem; color: var(--bs-secondary-color); }
.admin-arrow { color: var(--bs-secondary-color); transition: transform 0.3s ease; }
.admin-arrow.rotated { transform: rotate(180deg); }
.admin-content { margin-top: 12px; padding: 16px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 14px; }
.admin-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.admin-btn { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 14px 10px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s ease; color: var(--bs-body-color); }
.admin-btn:hover { border-color: #30cfd0; background: rgba(48, 207, 208, 0.05); }
.admin-btn i { font-size: 1.2rem; color: #30cfd0; }
.admin-btn span { font-size: 0.8rem; font-weight: 600; }

/* Модалка (стили без изменений) */
.modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(6px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal-container { background: var(--bs-body-bg); border-radius: 24px; width: 100%; max-width: 420px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: modalSlideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
@keyframes modalSlideUp { from { opacity: 0; transform: translateY(30px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
.result-modal { position: relative; overflow: visible; }
.result-confetti { position: absolute; inset: -50px; pointer-events: none; overflow: visible; }
.confetti-piece { position: absolute; top: 0; width: 10px; height: 10px; animation: confettiFall linear infinite; }
@keyframes confettiFall { 0% { transform: translateY(-50px) rotate(0deg); opacity: 1; } 100% { transform: translateY(600px) rotate(720deg); opacity: 0; } }
.result-content { padding: 32px 24px; text-align: center; position: relative; z-index: 1; }
.result-rarity-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px; }
.result-rarity-badge.rarity-epic { background: linear-gradient(135deg, rgba(48, 207, 208, 0.2) 0%, rgba(51, 8, 103, 0.2) 100%); color: #30cfd0; border: 1px solid rgba(48, 207, 208, 0.3); }
.result-rarity-badge.rarity-common { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
.result-icon-wrapper { position: relative; display: inline-block; margin-bottom: 20px; }
.result-icon { width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; position: relative; z-index: 1; animation: resultIconPop 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
@keyframes resultIconPop { 0% { transform: scale(0) rotate(-180deg); } 70% { transform: scale(1.1) rotate(10deg); } 100% { transform: scale(1) rotate(0deg); } }
.result-icon.rarity-epic { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
.result-icon.rarity-common { background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%); }
.result-glow { position: absolute; inset: -20px; border-radius: 50%; background: radial-gradient(circle, rgba(48, 207, 208, 0.3) 0%, transparent 70%); animation: glowPulse 2s ease-in-out infinite; }
@keyframes glowPulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.2); opacity: 0.8; } }
.result-title { font-size: 1.5rem; font-weight: 800; color: var(--bs-body-color); margin: 0 0 8px 0; }
.result-description { font-size: 0.9rem; color: var(--bs-secondary-color); line-height: 1.5; margin: 0 0 20px 0; }
.result-description strong { color: var(--bs-body-color); }
.result-details { background: rgba(48, 207, 208, 0.08); border-radius: 14px; padding: 16px; margin-bottom: 20px; }
.detail-row { display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 1rem; color: var(--bs-body-color); }
.detail-row i { color: #30cfd0; font-size: 1.2rem; }
.detail-row strong { color: #30cfd0; font-size: 1.2rem; }
.result-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 16px 24px; border: none; border-radius: 14px; color: white; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; }
.result-btn.btn-win { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); box-shadow: 0 4px 16px rgba(48, 207, 208, 0.3); }
.result-btn.btn-loss { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); box-shadow: 0 4px 16px rgba(108, 117, 125, 0.3); }
.result-btn:hover { transform: translateY(-2px); }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; max-height: 500px; }

@media (max-width: 576px) {
    .hero-title { font-size: 1.5rem; }
    .hero-icon { width: 64px; height: 64px; font-size: 1.8rem; }
    .hero-stats { gap: 12px; padding: 10px 16px; }
    .stat-value { font-size: 1rem; }
    .question-text { font-size: 1rem; }
    .answer-btn { padding: 14px 16px; }
    .answer-letter { width: 28px; height: 28px; font-size: 0.8rem; }
    .result-icon { width: 80px; height: 80px; font-size: 2rem; }
    .result-title { font-size: 1.3rem; }
    .review-question-card { padding: 16px; }
    .q-text { font-size: 0.95rem; }
    .review-answer { padding: 10px 12px; font-size: 0.85rem; }
}
</style>
