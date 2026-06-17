<template>
    <div v-if="settings.coffee?.enabled" class="coffee-page pb-5">

        <!-- ===== HERO СЕКЦИЯ ===== -->
        <div class="coffee-hero">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fa-solid fa-mug-hot"></i>
                    <div class="steam steam-1"></div>
                    <div class="steam steam-2"></div>
                    <div class="steam steam-3"></div>
                </div>
                <h2 class="hero-title">Кофейная карта</h2>
                <p class="hero-subtitle">
                    {{ !coffee ? 'Начните собирать чашки и получите кофе бесплатно!' : `Ваш прогресс: ${usedCups} из ${maxCups}` }}
                </p>
            </div>
        </div>

        <div class="container px-3">

            <!-- ===== НАЧАЛЬНОЕ СОСТОЯНИЕ ===== -->
            <div v-if="!coffee" class="start-section">
                <div class="start-card">
                    <div class="start-icon">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <h5 class="start-title">Получите бесплатный кофе!</h5>
                    <p class="start-description">
                        Копите чашки и получите {{ maxCups }}-ю чашку совершенно бесплатно.
                        Это просто и вкусно!
                    </p>
                    <button
                        type="button"
                        @click="InitCoffee"
                        class="start-btn"
                    >
                        <i class="fa-solid fa-play me-2"></i>
                        Начать собирать
                    </button>
                </div>
            </div>

            <!-- ===== ПРОГРЕСС ===== -->
            <template v-else>

                <!-- Визуальный трекер чашек -->
                <div class="section-block">
                    <div class="section-header">
                        <div class="section-icon cups-icon">
                            <i class="fa-solid fa-mug-hot"></i>
                        </div>
                        <div>
                            <h6 class="section-title">Ваши чашки</h6>
                            <p class="section-subtitle">Нажмите на чашку для отметки</p>
                        </div>
                    </div>

                    <div class="cups-tracker">
                        <div
                            v-for="(cup, index) in cups"
                            :key="'coffee-cup-' + index"
                            class="cup-wrapper"
                            :class="{ 'filled': cup.filled, 'clickable': !cup.filled }"
                            @click="onCupClick(cup)"
                        >
                            <div class="cup-icon">
                                <i class="fa-solid fa-mug-hot"></i>
                                <div v-if="cup.filled" class="cup-fill-animation"></div>
                            </div>
                            <div class="cup-number">{{ index + 1 }}</div>
                        </div>

                        <!-- Кнопка обновления -->
                        <div
                            class="cup-wrapper refresh-wrapper"
                            :class="{ 'disabled': spent_time > 0 }"
                            @click="spent_time === 0 && refreshCoffee()"
                        >
                            <div class="cup-icon refresh-icon">
                                <span v-if="spent_time > 0" class="refresh-timer">{{ spent_time }}</span>
                                <i v-else class="fa-solid fa-rotate-right"></i>
                            </div>
                            <div class="cup-number">Обновить</div>
                        </div>
                    </div>

                    <!-- Прогресс-бар -->
                    <div class="progress-section">
                        <div class="progress-header">
                            <span class="progress-label">Прогресс</span>
                            <span class="progress-value">{{ progressPercent }}%</span>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div
                                class="progress-bar-fill"
                                :style="{ width: progressPercent + '%' }"
                            >
                                <div class="progress-bar-shine"></div>
                            </div>
                        </div>
                        <div class="progress-footer">
                            <span>Осталось {{ maxCups - usedCups }} чашек до бесплатного кофе</span>
                        </div>
                    </div>
                </div>

                <!-- ===== НАГРАДА ===== -->
                <div v-if="usedCups >= maxCups && maxCups > 0" class="section-block mt-4">
                    <div class="reward-card">
                        <div class="reward-glow"></div>
                        <div class="reward-content">
                            <div class="reward-icon">
                                <i class="fa-solid fa-mug-saucer"></i>
                                <div class="reward-sparkle sparkle-1">✨</div>
                                <div class="reward-sparkle sparkle-2">✨</div>
                                <div class="reward-sparkle sparkle-3">✨</div>
                            </div>
                            <div class="reward-info">
                                <h5 class="reward-title">Поздравляем!</h5>
                                <p class="reward-description">
                                    Вы собрали достаточно чашек для бесплатного кофе!
                                </p>
                            </div>
                        </div>
                        <button
                            class="reward-btn"
                            @click="openExchangeModal"
                        >
                            <i class="fa-solid fa-qrcode me-2"></i>
                            Получить бесплатный кофе
                        </button>
                    </div>
                </div>

                <!-- ===== ПРАВИЛА ===== -->
                <div class="section-block mt-4">
                    <button
                        class="rules-toggle"
                        @click="toggleRules"
                    >
                        <div class="rules-toggle-content">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Правила акции</span>
                        </div>
                        <i class="fa-solid fa-chevron-down rules-arrow" :class="{ 'rotated': showRules }"></i>
                    </button>

                    <transition name="slide-down">
                        <div v-if="showRules" class="rules-content">
                            <p class="rules-text">{{ settings.coffee.rules || 'Правила акции не указаны' }}</p>
                        </div>
                    </transition>
                </div>

            </template>

        </div>

        <!-- ===== МОДАЛКА QR-КОДА ===== -->
        <div
            class="modal fade"
            id="qrModal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content qr-modal">
                    <div class="modal-header">
                        <div class="qr-modal-icon">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="modal-title mb-0">
                                {{ qrModalType === 'exchange' ? 'Бесплатный кофе' : 'Отметка чашки' }}
                            </h5>
                            <small class="text-muted">
                                {{ qrModalType === 'exchange' ? 'Покажите бариста' : 'Покажите администратору' }}
                            </small>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="qr-wrapper">
                            <img :src="markQR" alt="QR-код" class="qr-image">
                        </div>
                        <p class="qr-hint">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Отсканируйте QR-код для {{ qrModalType === 'exchange' ? 'получения' : 'отметки' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "CoffeeProgress",

    data() {
        return {
            spent_time: 0,
            intervalId: null,
            showRules: false,
            selectedCupIndex: null,
            coffee: null,
            qrModalType: 'mark', // 'mark' или 'exchange'
            qrModal: null,
        };
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || null;
        },

        self() {
            return window.TenantUser || null;
        },

        maxCups() {
            return this.settings?.coffee?.max || 6;
        },

        usedCups() {
            return this.coffee?.count || 0;
        },

        cups() {
            return Array.from({ length: this.maxCups }, (_, i) => ({
                index: i,
                filled: i < this.usedCups,
            }));
        },

        progressPercent() {
            return Math.round((this.usedCups / this.maxCups) * 100);
        },

        markQR() {
            if (this.selectedCupIndex === null && this.qrModalType !== 'exchange') return null;

            const data = this.qrModalType === 'exchange'
                ? `exchange:${this.self?.id}:${Date.now()}`
                : `mark:${this.self?.id}:${this.selectedCupIndex}`;

            return `https://api.qrserver.com/v1/create-qr-code/?size=450x450&qzone=2&data=${encodeURIComponent(data)}`;
        },
    },

    watch: {
        self: {
            handler(newValue) {
                if (newValue) {
                    this.coffee = newValue.config?.coffee || null;
                }
            },
            deep: true,
            immediate: true,
        },
    },

    mounted() {
        this.$nextTick(() => {
            if (typeof bootstrap !== 'undefined') {
                this.qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
            }
        });

        window.addEventListener("trigger-spent-timer", (event) => {
            this.spent_time = event.detail;
        });
    },

    beforeUnmount() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        if (this.qrModal) {
            this.qrModal.dispose();
        }
    },

    methods: {
        refreshCoffee() {
            this.startSpentTimer(10);
            this.$notify?.({
                title: "Кофе",
                text: "Обновляем данные...",
                type: "info",
            });
            this.InitCoffee();
        },

        startSpentTimer(seconds) {
            this.spent_time = seconds;
            const interval = setInterval(() => {
                if (this.spent_time > 0) {
                    this.spent_time--;
                } else {
                    clearInterval(interval);
                }
            }, 1000);
        },

        InitCoffee() {
            // TODO: Замени на реальный API или Pinia action
            // return axios.post('/api/coffee/init').then(resp => {
            //     this.coffee = resp.data || { count: 0 };
            // });

            // Имитация запроса
            return new Promise(resolve => {
                setTimeout(() => {
                    this.coffee = this.coffee || { count: 0 };
                    resolve(this.coffee);
                }, 500);
            });
        },

        waitForAccept() {
            let waitIterationCount = 10;
            const currentCount = this.coffee.count;

            this.intervalId = setInterval(() => {
                this.InitCoffee().then(() => {
                    waitIterationCount--;
                    if (currentCount !== this.coffee.count || waitIterationCount <= 0) {
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    }
                });
            }, 5000);
        },

        onCupClick(cup) {
            if (cup.filled) return;

            this.selectedCupIndex = cup.index;
            this.qrModalType = 'mark';
            this.qrModal?.show();
            this.waitForAccept();
        },

        openExchangeModal() {
            this.selectedCupIndex = null;
            this.qrModalType = 'exchange';
            this.qrModal?.show();
        },

        toggleRules() {
            this.showRules = !this.showRules;
        },
    },
};
</script>

<style scoped>
.coffee-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
}

/* ==========================================
   HERO СЕКЦИЯ
   ========================================== */
.coffee-hero {
    position: relative;
    padding: 40px 24px 32px;
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

/* Пар от кофе */
.steam {
    position: absolute;
    width: 8px;
    height: 20px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    filter: blur(4px);
    animation: steam 2s ease-in-out infinite;
}

.steam-1 {
    top: -10px;
    left: 25%;
    animation-delay: 0s;
}

.steam-2 {
    top: -15px;
    left: 50%;
    animation-delay: 0.5s;
}

.steam-3 {
    top: -10px;
    left: 75%;
    animation-delay: 1s;
}

@keyframes steam {
    0%, 100% {
        transform: translateY(0) scale(1);
        opacity: 0.6;
    }
    50% {
        transform: translateY(-15px) scale(1.2);
        opacity: 0;
    }
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0;
}

/* ==========================================
   НАЧАЛЬНОЕ СОСТОЯНИЕ
   ========================================== */
.start-section {
    margin-top: 24px;
}

.start-card {
    text-align: center;
    padding: 32px 24px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.start-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(111, 78, 55, 0.3);
}

.start-title {
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 12px;
    color: var(--bs-body-color);
}

.start-description {
    font-size: 0.95rem;
    color: var(--bs-secondary-color);
    line-height: 1.6;
    margin-bottom: 24px;
}

.start-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 32px;
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(111, 78, 55, 0.3);
}

.start-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(111, 78, 55, 0.4);
}

/* ==========================================
   СЕКЦИИ
   ========================================== */
.section-block {
    margin-top: 24px;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.cups-icon {
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    box-shadow: 0 4px 12px rgba(111, 78, 55, 0.3);
}

.section-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--bs-body-color);
}

.section-subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   ТРЕКЕР ЧАШЕК
   ========================================== */
.cups-tracker {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}

.cup-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: default;
    transition: all 0.3s ease;
}

.cup-wrapper.clickable {
    cursor: pointer;
}

.cup-wrapper.clickable:hover .cup-icon {
    transform: scale(1.1) rotate(-5deg);
}

.cup-icon {
    position: relative;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--bs-body-bg);
    border: 3px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: var(--bs-secondary-color);
    transition: all 0.3s ease;
    overflow: hidden;
}

.cup-wrapper.filled .cup-icon {
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    border-color: #6f4e37;
    color: white;
    box-shadow: 0 4px 16px rgba(111, 78, 55, 0.3);
}

.cup-fill-animation {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.3) 0%, transparent 100%);
    animation: fillUp 0.6s ease-out;
}

@keyframes fillUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.cup-number {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
}

.cup-wrapper.filled .cup-number {
    color: #6f4e37;
}

/* Кнопка обновления */
.refresh-wrapper {
    cursor: pointer;
}

.refresh-wrapper.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.refresh-icon {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    border-color: #198754;
    color: white;
}

.refresh-timer {
    font-size: 1.2rem;
    font-weight: 700;
}

/* ==========================================
   ПРОГРЕСС-БАР
   ========================================== */
.progress-section {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 20px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.progress-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.progress-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #6f4e37;
}

.progress-bar-wrapper {
    width: 100%;
    height: 12px;
    background: var(--bs-secondary-bg);
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #6f4e37 0%, #a0826d 100%);
    border-radius: 6px;
    transition: width 0.5s ease;
    position: relative;
    overflow: hidden;
}

.progress-bar-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 50%, transparent 100%);
    animation: shine 2s ease-in-out infinite;
}

@keyframes shine {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

.progress-footer {
    margin-top: 12px;
    text-align: center;
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   КАРТОЧКА НАГРАДЫ
   ========================================== */
.reward-card {
    position: relative;
    padding: 24px;
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    border-radius: 20px;
    color: white;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(111, 78, 55, 0.4);
}

.reward-glow {
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
    border-radius: 50%;
    animation: glow 3s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% {
        transform: scale(1);
        opacity: 0.5;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
    }
}

.reward-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.reward-icon {
    position: relative;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    flex-shrink: 0;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

.reward-sparkle {
    position: absolute;
    font-size: 1rem;
    animation: sparkle 2s ease-in-out infinite;
}

.sparkle-1 {
    top: -8px;
    right: -8px;
    animation-delay: 0s;
}

.sparkle-2 {
    bottom: -8px;
    left: -8px;
    animation-delay: 0.7s;
}

.sparkle-3 {
    top: 50%;
    right: -12px;
    animation-delay: 1.4s;
}

@keyframes sparkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0.5);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
}

.reward-info {
    flex: 1;
}

.reward-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: white;
}

.reward-description {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.95;
    line-height: 1.4;
}

.reward-btn {
    position: relative;
    z-index: 1;
    width: 100%;
    padding: 14px 24px;
    background: white;
    border: none;
    border-radius: 14px;
    color: #6f4e37;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.reward-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

/* ==========================================
   ПРАВИЛА
   ========================================== */
.rules-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--bs-body-color);
}

.rules-toggle:hover {
    border-color: #6f4e37;
    background: rgba(111, 78, 55, 0.02);
}

.rules-toggle-content {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 0.95rem;
}

.rules-toggle-content i {
    color: #6f4e37;
}

.rules-arrow {
    color: var(--bs-secondary-color);
    transition: transform 0.3s ease;
}

.rules-arrow.rotated {
    transform: rotate(180deg);
}

.rules-content {
    margin-top: 12px;
    padding: 20px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
}

.rules-text {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--bs-body-color);
    white-space: pre-line;
}

/* Анимация раскрытия */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 500px;
}

/* ==========================================
   МОДАЛКА QR-КОДА
   ========================================== */
.qr-modal {
    border-radius: 20px;
    border: none;
    overflow: hidden;
}

.qr-modal .modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--bs-border-color);
    background: rgba(111, 78, 55, 0.03);
}

.qr-modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6f4e37 0%, #a0826d 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.qr-modal .modal-body {
    padding: 32px 24px;
}

.qr-wrapper {
    display: inline-block;
    padding: 16px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.qr-image {
    width: 240px;
    height: 240px;
    display: block;
}

.qr-hint {
    margin: 0;
    font-size: 0.9rem;
    color: var(--bs-secondary-color);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .cups-tracker {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .cup-icon {
        width: 56px;
        height: 56px;
        font-size: 1.5rem;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .qr-image {
        width: 200px;
        height: 200px;
    }
}
</style>
