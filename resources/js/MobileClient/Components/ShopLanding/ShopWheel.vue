<template>
    <section class="shop-wheel">
        <div class="container">
            <div class="wheel-wrapper">
                <div class="wheel-info">
                    <span class="section-badge">
                        <i class="fa-solid fa-gift"></i> Подарок за подписку
                    </span>
                    <h2 class="wheel-title">Крутите колесо и получайте скидку!</h2>
                    <p class="wheel-desc">
                        Оставьте свой телефон, крутите колесо фортуны и получите гарантированный приз —
                        скидку от 5% до 30% на следующий заказ!
                    </p>
                    <ul class="wheel-features">
                        <li><i class="fa-solid fa-check-circle"></i> 100% выигрыш</li>
                        <li><i class="fa-solid fa-check-circle"></i> Приз активируется сразу</li>
                        <li><i class="fa-solid fa-check-circle"></i> Действует 7 дней</li>
                    </ul>
                </div>

                <div class="wheel-game">
                    <div class="wheel-container">
                        <!-- Указатель -->
                        <div class="wheel-pointer">
                            <i class="fa-solid fa-caret-down"></i>
                        </div>

                        <!-- SVG Колесо — вращается целиком -->
                        <svg
                            :viewBox="`-${radius} -${radius} ${diameter} ${diameter}`"
                            class="wheel-svg"
                            :style="wheelStyle"
                        >
                            <g
                                v-for="(sector, idx) in sectors"
                                :key="idx"
                                :transform="`rotate(${idx * sectorAngle - 90})`"
                            >
                                <path
                                    :d="sectorPath"
                                    :fill="sector.color"
                                    stroke="white"
                                    stroke-width="2"
                                />
                                <text
                                    :transform="`rotate(${sectorAngle / 2}) translate(${radius * 0.62}) rotate(90)`"
                                    text-anchor="middle"
                                    fill="white"
                                    font-size="14"
                                    font-weight="800"
                                >
                                    {{ sector.label }}
                                </text>
                            </g>
                        </svg>

                        <!-- Центральная кнопка (НЕ крутится) -->
                        <button class="wheel-spin-btn" @click="spin" :disabled="isSpinning || hasWon">
                            <span v-if="hasWon">
                                <i class="fa-solid fa-check"></i>
                            </span>
                            <span v-else-if="isSpinning" class="spinner"></span>
                            <span v-else>КРУТИТЬ</span>
                        </button>
                    </div>

                    <!-- Форма для получения приза -->
                    <!-- Форма для получения приза -->
                    <div v-if="!hasWon" class="wheel-form">
                        <input
                            type="tel"
                            v-model="phone"
                            placeholder="+7 (___) ___-__-__"
                            class="phone-input"
                        >

                        <!-- 🆕 ЧЕКБОКС ПОЛИТИКИ КОНФИДЕНЦИАЛЬНОСТИ -->
                        <label class="policy-checkbox">
                            <input type="checkbox" v-model="agreeToPolicy">
                            <span class="checkmark"></span>
                            <span class="policy-text">
            Я согласен с <a href="#" @click.prevent="$emit('open-privacy')">политикой конфиденциальности</a> и обработкой персональных данных
        </span>
                        </label>

                        <button class="spin-trigger" @click="validateAndSpin" :disabled="!canSpin">
                            Получить приз
                        </button>

                        <p class="form-hint">
                            <i class="fa-solid fa-lock"></i>
                            Мы не передаём ваши данные третьим лицам
                        </p>
                    </div>

                    <!-- Модалка выигрыша -->
                    <transition name="modal-fade">
                        <div v-if="showWinModal" class="win-overlay" @click.self="showWinModal = false">
                            <div class="win-modal">
                                <div class="confetti" v-for="i in 30" :key="i" :style="confettiStyle(i)"></div>
                                <div class="win-icon">
                                    <i class="fa-solid fa-trophy"></i>
                                </div>
                                <h3>Поздравляем!</h3>
                                <p class="win-prize">Вам выпала скидка</p>
                                <div class="win-value">{{ wonPrize.label }}</div>
                                <p class="win-code">Промокод: <strong class="text-danger fw-bold" >{{ wonPrize.code }}</strong></p>
                                <p class="win-hint">Скидка автоматически применится при следующем заказе</p>
                                <button class="win-btn" @click="showWinModal = false">Отлично!</button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import {
    shuffleArray,
    shuffleSectors,
    generateRandomSectors,
    getRandomSectorByWeight,
    generatePromoCode
} from '@/MobileClient/Components/ShopLanding/utils/wheelUtils';
import { WHEEL_SECTORS_TEMPLATE } from '@/MobileClient/Components/ShopLanding/utils/wheelTemplate';

export default {
    name: "ShopWheel",
    data() {
        return {
            radius: 180,
            isSpinning: false,
            hasWon: false,
            showWinModal: false,
            phone: '',
            agreeToPolicy: false, // 🆕 Состояние чекбокса
            currentRotation: 0,
            wonPrize: null,
            // Пересчитанные скидки: максимум 30%
            sectors: [
                { label: '5%',  color: '#ff7a00', code: 'WHEEL5' },
                { label: '10%', color: '#8b5cf6', code: 'WHEEL10' },
                { label: '5%',  color: '#10b981', code: 'WHEEL5B' },
                { label: '15%', color: '#3b82f6', code: 'WHEEL15' },
                { label: '7%',  color: '#ec4899', code: 'WHEEL7' },
                { label: '4%', color: '#f59e0b', code: 'WHEEL20' },
                { label: '10%', color: '#06b6d4', code: 'WHEEL10B' },
                { label: '17%', color: '#0f0f14', code: 'WHEEL30' }
            ]
        };
    },
    computed: {
        diameter() { return this.radius * 2; },
        sectorAngle() { return 360 / this.sectors.length; },
        sectorPath() {
            const angle = (this.sectorAngle * Math.PI) / 180;
            const x = this.radius * Math.cos(angle);
            const y = this.radius * Math.sin(angle);
            return `M 0 0 L ${this.radius} 0 A ${this.radius} ${this.radius} 0 0 1 ${x} ${y} Z`;
        },
        canSpin() {
            return this.isPhoneValid && this.agreeToPolicy;
        },
        isPhoneValid() {
            return this.phone.replace(/\D/g, '').length >= 10;
        },
        // ГЛАВНОЕ ИСПРАВЛЕНИЕ: inline-стиль для вращения всего колеса
        wheelStyle() {
            return {
                transform: `rotate(${this.currentRotation}deg)`,
                transition: this.isSpinning
                    ? 'transform 5s cubic-bezier(0.17, 0.67, 0.12, 0.99)'
                    : 'none'
            };
        }
    },
    mounted() {
        this.reshuffleWheel();
    },
    methods: {
        reshuffleWheel() {
            if (this.isSpinning) return;

            // Генерируем новый набор секторов из шаблона
            this.sectors = generateRandomSectors(WHEEL_SECTORS_TEMPLATE).map((sector, idx) => ({
                ...sector,
                // Уникальный ID для корректного v-for при перемешивании
                id: `sector-${this.shuffleCounter}-${idx}`
            }));

            this.shuffleCounter++;

            // Сбрасываем состояние игры
            this.hasWon = false;
            this.wonPrize = null;
            this.currentRotation = 0;

            this.$notify?.({
                title: 'Колесо перемешано',
                text: 'Призы расположены в новом порядке',
                type: 'info'
            });
        },
        validateAndSpin() {
            // Двойная проверка для надежности
            if (!this.canSpin) {
                if (!this.isPhoneValid) {
                    this.$notify?.({ title: 'Ошибка', text: 'Введите корректный номер телефона', type: 'error' });
                } else if (!this.agreeToPolicy) {
                    this.$notify?.({ title: 'Внимание', text: 'Необходимо согласиться с политикой конфиденциальности', type: 'warning' });
                }
                return;
            }
            this.spin();
        },
        spin() {
            if (this.isSpinning || this.hasWon) return;

            this.isSpinning = true;

            // Выбираем выигрышный сектор (взвешенная случайность)
            const winIndex = this.getRandomSector();
            this.wonPrize = this.sectors[winIndex];

            // Рассчитываем конечный угол вращения
            // Центр выигрышного сектора находится на угле: winIndex * sectorAngle + sectorAngle/2
            // Чтобы указатель (сверху) указал на него, нужно повернуть колесо так,
            // чтобы этот угол оказался под указателем
            const sectorCenter = winIndex * this.sectorAngle + this.sectorAngle / 2;
            const spins = 6; // 6 полных оборотов для эффекта
            const targetRotation = this.currentRotation + (spins * 360) + (360 - sectorCenter);

            // Небольшая случайная погрешность, чтобы не останавливался ровно в центре
            const randomOffset = (Math.random() - 0.5) * (this.sectorAngle * 0.6);

            this.currentRotation = targetRotation + randomOffset;

            // Показываем модалку после завершения анимации
            setTimeout(() => {
                this.isSpinning = false;
                this.hasWon = true;
                this.showWinModal = true;

                this.$notify?.({
                    title: 'Поздравляем!',
                    text: `Ваш промокод: ${this.wonPrize.code}`,
                    type: 'success'
                });
            }, 5200);
        },
        getRandomSector() {
            // Взвешенная случайность: маленькие скидки выпадают чаще
            // Сумма = 100%
            const weights = [22, 15, 20, 12, 15, 8, 5, 3];
            //              5%  10%  5%  15%  7%  20% 10% 30%
            const rand = Math.random() * 100;
            let cumulative = 0;
            for (let i = 0; i < weights.length; i++) {
                cumulative += weights[i];
                if (rand <= cumulative) return i;
            }
            return 0;
        },
        confettiStyle(i) {
            const colors = ['#ff7a00', '#8b5cf6', '#10b981', '#3b82f6', '#ec4899', '#fbbf24'];
            return {
                '--x': (Math.random() - 0.5) * 400 + 'px',
                '--y': (Math.random() - 0.5) * 400 + 'px',
                '--r': Math.random() * 720 + 'deg',
                background: colors[Math.floor(Math.random() * colors.length)],
                animationDelay: Math.random() * 0.5 + 's'
            };
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-wheel {
    padding: 80px 0;
    background: linear-gradient(135deg, #0f0f14 0%, #1a1a23 100%);
    color: white;
    position: relative;
    overflow: hidden;

    &::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(circle at 30% 50%, rgba(255, 122, 0, 0.15) 0%, transparent 50%);
    }
}

.wheel-wrapper {
    display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
    position: relative; z-index: 2;
    @media (max-width: 992px) { grid-template-columns: 1fr; }
}

.section-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255, 122, 0, 0.15); color: var(--primary);
    padding: 8px 16px; border-radius: 50px; font-weight: 700;
    font-size: 0.9rem; margin-bottom: 1rem;
    border: 1px solid rgba(255, 122, 0, 0.3);
}

.wheel-title { font-size: 2.5rem; font-weight: 900; margin-bottom: 1rem; line-height: 1.2; }
.wheel-desc { font-size: 1.1rem; opacity: 0.85; line-height: 1.6; margin-bottom: 2rem; }
.wheel-features { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
.wheel-features li { display: flex; align-items: center; gap: 10px; font-size: 1.05rem; }
.wheel-features i { color: var(--primary); font-size: 1.2rem; }

.wheel-game { display: flex; flex-direction: column; align-items: center; gap: 24px; }

.wheel-container {
    position: relative; width: 400px; height: 400px;
    @media (max-width: 480px) { width: 320px; height: 320px; }
}

.wheel-pointer {
    position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
    z-index: 10; color: white; font-size: 2.5rem;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
}

/* ГЛАВНОЕ: SVG теперь вращается через inline-стиль wheelStyle */
.wheel-svg {
    width: 100%; height: 100%;
    filter: drop-shadow(0 20px 40px rgba(0,0,0,0.4));
}

.wheel-spin-btn {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 100px; height: 100px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    border: 4px solid white; color: white;
    font-weight: 900; font-size: 0.9rem; letter-spacing: 1px;
    cursor: pointer; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    transition: transform 0.3s, box-shadow 0.3s;
    z-index: 5;

    &:hover:not(:disabled) {
        transform: translate(-50%, -50%) scale(1.1);
        box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    }
    &:disabled { cursor: not-allowed; }
}

.spinner {
    width: 30px; height: 30px; border: 3px solid rgba(255,255,255,0.3);
    border-top-color: white; border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

.wheel-form {
    width: 100%; max-width: 400px;
    display: flex; flex-direction: column; gap: 12px;
}
.phone-input {
    width: 100%; padding: 14px 18px;
    background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.2); border-radius: 12px;
    color: white; font-size: 1rem;

    &::placeholder { color: rgba(255,255,255,0.5); }
    &:focus { outline: none; border-color: var(--primary); }
}
.spin-trigger {
    padding: 14px; background: var(--primary); color: white;
    border: none; border-radius: 12px; font-weight: 700;
    font-size: 1rem; cursor: pointer; transition: 0.3s;

    &:hover:not(:disabled) { background: var(--primary-dark); }
    &:disabled { opacity: 0.5; cursor: not-allowed; }
}
.form-hint {
    font-size: 0.8rem; opacity: 0.6; text-align: center;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}

/* Модалка выигрыша */
.win-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.8); backdrop-filter: blur(8px);
    z-index: 9999; display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.win-modal {
    background: white; border-radius: 24px; padding: 3rem 2rem;
    max-width: 400px; width: 100%; text-align: center;
    position: relative; overflow: hidden; color: var(--dark);
    animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.5); }
    to { opacity: 1; transform: scale(1); }
}

.confetti {
    position: absolute; width: 10px; height: 10px;
    top: 50%; left: 50%;
    animation: confettiFly 2s ease-out forwards;
}
@keyframes confettiFly {
    0% { transform: translate(0, 0) rotate(0); opacity: 1; }
    100% { transform: translate(var(--x), var(--y)) rotate(var(--r)); opacity: 0; }
}

.win-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ff9500 100%);
    color: white; font-size: 2.5rem;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem;
    box-shadow: 0 10px 30px rgba(255, 149, 0, 0.4);
    animation: bounce 1s ease infinite;
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.win-modal h3 { font-size: 2rem; font-weight: 900; margin-bottom: 0.5rem; }
.win-prize { color: var(--gray); margin-bottom: 1rem; }
.win-value {
    font-size: 4rem; font-weight: 900;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 1.5rem; line-height: 1;
}
.win-code {
    background: var(--light); padding: 12px; border-radius: 12px;
    margin-bottom: 1rem; font-size: 1.1rem;
}
.win-code strong { color: var(--primary); font-size: 1.3rem; letter-spacing: 2px; }
.win-hint { font-size: 0.85rem; color: var(--gray); margin-bottom: 1.5rem; }
.win-btn {
    width: 100%; padding: 14px;
    background: var(--primary); color: white; border: none;
    border-radius: 12px; font-weight: 700; font-size: 1rem;
    cursor: pointer; transition: 0.3s;

    &:hover { background: var(--primary-dark); }
}

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

/* ==========================================
   🆕 ЧЕКБОКС ПОЛИТИКИ КОНФИДЕНЦИАЛЬНОСТИ
   ========================================== */
.policy-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    user-select: none;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.75);
    line-height: 1.4;
    margin-top: 4px;

    // Скрываем стандартный чекбокс
    input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .checkmark {
        width: 22px;
        height: 22px;
        min-width: 22px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        margin-top: 1px;
        background: rgba(255, 255, 255, 0.05);

        &::after {
            content: '+'; // Иконка галочки FontAwesome
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 12px;
            color: white;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
    }

    // Состояние: отмечено
    input[type="checkbox"]:checked + .checkmark {
        background: var(--primary, #ff7a00);
        border-color: var(--primary, #ff7a00);
        box-shadow: 0 0 12px rgba(255, 122, 0, 0.4);

        &::after {
            opacity: 1;
            transform: scale(1);
        }
    }

    .policy-text {
        a {
            color: var(--primary, #ff7a00);
            text-decoration: underline;
            text-underline-offset: 3px;
            font-weight: 600;
            transition: opacity 0.2s;

            &:hover {
                opacity: 0.8;
                text-decoration: none;
            }
        }
    }
}
</style>
