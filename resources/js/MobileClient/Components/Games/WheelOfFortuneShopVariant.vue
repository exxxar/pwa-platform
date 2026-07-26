<template>
    <div class="d-flex flex-column w-100">
        <div class="d-flex justify-content-center flex-wrap mb-3 w-100">
            <template v-for="(item, index) in settings" v-if="!selected_prize">
                <div class="item-wrap p-1">
                    <span @click="selectPrize(index)" class="btn btn-outline-light">{{ item.value }}</span>
                </div>
            </template>

            <div class="card w-100" v-else @click="selected_prize=null">
                <div class="card-body">
                    <h6 class="mb-2 text-center"> {{ selected_prize.value || '-' }} (#{{ selected_prize.id }})</h6>
                    <p class="mb-2 fst-italic">{{ selected_prize.description || 'не указано' }}</p>
                    <p class="mb-0">Способ получения: <span
                        class="fw-bold text-primary">{{ selected_prize.mark || 'не указано' }}</span></p>
                </div>
            </div>
        </div>

        <!-- 🛡️ ИСПРАВЛЕННЫЙ КОМПОНЕНТ КОЛЕСА СТРОГО ПО ДОКУМЕНТАЦИИ -->
        <div class="wrap" v-if="isReady">
            <CustomFortuneWheel
                ref="wheel"
                :items="settings"
                :size="400"
                :icon-size="24"
                :rotate-icons="false"
                :duration="4000"
                @done="done"
                @spin-start="onSpinStart"
            />
            <div v-if="enabledToPlay" class="start-panel" @click="launchWheel">
                <!-- Декоративный внешний круг (продолжение обода колеса) -->
                <div class="center-rim">
                    <!-- Зубчики по периметру -->
                    <span v-for="i in 12" :key="'tooth-' + i" class="rim-tooth" :style="{ transform: `rotate(${(i - 1) * 30}deg) translateY(-42px)` }"></span>
                </div>

                <!-- Основная кнопка -->
                <button type="button" class="spin-button">
        <span class="spin-button-inner">
            <i class="fa-solid fa-play spin-icon"></i>
            <span class="spin-text">СТАРТ</span>
        </span>
                    <!-- Свечение -->
                    <span class="spin-glow"></span>
                </button>
            </div>
        </div>

        <!-- Заглушка, если данных недостаточно -->
        <div v-else-if="loaded" class="text-center p-4 text-muted border rounded bg-light">
            <i class="fa-solid fa-triangle-exclamation fa-2x mb-2"></i>
            <p class="mb-0">Для отображения колеса необходимо настроить минимум 3 сектора.</p>
        </div>

        <div class="card mt-3" id="result" v-if="form.win">
            <div class="card-body">
                <h6 class="text-center fw-bold">
                    <span v-if="completed_at">Ваш текущий выигрыш</span>
                    <span v-else>Ваш прошлый выигрыш</span>
                </h6>
                <h6 class="mb-2 text-center"> {{ form.win.value || form.win.id || '-' }} (#{{ form.win.id }})</h6>
                <p class="mb-0 fst-italic">{{ form.win.description || 'не указано' }}</p>
                <p v-if="completed_at" class="mb-0 mt-2">Вы сможете получить приз: <span
                    class="fw-bold text-primary">{{ form.win.mark || 'не указано' }}</span></p>
                <hr class="mb-2 p-0" v-if="completed_at">
                <p class="mb-0" v-if="completed_at">
                    <span class="fw-bold">Внимание!</span> Приз возможно получить только в течении <span
                    class="fw-bold text-primary">{{ preparedInterval }}</span> с момента выигрыша.
                </p>
            </div>
        </div>
    </div>

</template>

<script>
import CustomFortuneWheel from "@/MobileClient/Components/Games/WheelOfFortune/CustomFortuneWheel.vue";

export default {
    props: ["modelValue", "canPlay", "actionData", "isAdmin", "interval"],
    components: {CustomFortuneWheel},

    computed: {
        preparedInterval() {
            if (!this.interval) return "24 часа";
            switch (this.interval) {
                case 1:
                    return "одного дня";
                case 7:
                    return "7 дней";
                case 30:
                    return "одного месяца";
                default:
                    return "24 часа";
            }
        },
        enabledToPlay() {
            if (this.isAdmin) return true;
            return !this.started && this.canPlay;
        },
        isReady() {
            return this.loaded && Array.isArray(this.settings) && this.settings.length >= 3;
        }
    },

    data() {
        return {
            loaded: false,
            selected_prize: null,
            started: false,
            completed_at: null,
            gift: 1, // 🆕 Индекс выигрыша (число, 1-based, как в документации)
            form: {win: null},
            logo: {src: "/wheel.png", width: 120, height: 120},
            settings: [
                {id: 1, value: "🍅", bgColor: "#fac600", color: "#ffffff", description: 'Приз 1', mark: 'в заведении'},
                {id: 2, value: "🍲", bgColor: "#ffffff", color: "#000000", description: 'Приз 2', mark: 'в заведении'},
                {id: 3, value: "🍦", bgColor: "#ff2e55", color: "#ffffff", description: 'Приз 3', mark: 'на доставке'}
            ]
        };
    },

    watch: {
        modelValue: {
            handler(newVal) {
                if (newVal && Array.isArray(newVal) && newVal.length >= 3) {
                    this.settings = newVal;
                }
            },
            immediate: true
        }
    },

    mounted() {
        this.$nextTick(() => {
            if (!this.settings || this.settings.length < 3) {
                console.warn("WheelOfFortuneShopVariant: данные повреждены, используются значения по умолчанию.");
            }
            this.loaded = true;
        });
    },

    methods: {
        selectPrize(index) {
            this.selected_prize = this.settings[index];
        },
        done(result) {
            // 🆕 result теперь содержит объект выигрышного сектора из массива data
            this.form.win = result;
            if (!this.isAdmin) this.$emit("win", this.form);

            this.$notify?.({
                title: 'Колесо фортуны',
                text: 'Поздравляем! Вы выиграли!',
                type: 'success'
            });
            this.completed_at = new Date();

            this.$nextTick(() => {
                const resultEl = document.querySelector("#result");
                if (resultEl) resultEl.scrollIntoView({behavior: 'smooth'});
            });
        },
        // 🆕 Этот метод вызывается при клике на кнопку "Старт"
        launchWheel() {
            if (!this.enabledToPlay) return;
            // Не крутим сразу, а просим родителя зафиксировать попытку
            this.$emit('request-spin');
        },

        // 🆕 Этот метод родитель вызовет, если сервер РАЗРЕШИЛ играть
        startActualSpin() {
            this.gift = Math.floor(Math.random() * this.settings.length) + 1;
            this.started = true;
            this.$refs.wheel.spin();
        },
    }
};
</script>

<style lang="scss">
.wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin: 20px 0;

    /* 🎯 ПРЕМИАЛЬНЫЙ ЦЕНТР КОЛЕСА */
    .start-panel {
        position: absolute;
        z-index: 99;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 110px;
        height: 110px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    /* Декоративный внешний обод (как у самого колеса) */
    .center-rim {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #d97706 100%);
        box-shadow:
            0 4px 12px rgba(0, 0, 0, 0.3),
            inset 0 2px 4px rgba(255, 255, 255, 0.4),
            inset 0 -2px 4px rgba(0, 0, 0, 0.2);
        animation: rimRotate 20s linear infinite;
    }

    /* Зубчики на ободе */
    .rim-tooth {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 6px;
        height: 6px;
        margin: -3px 0 0 -3px;
        background: #fef3c7;
        border: 1px solid #92400e;
        border-radius: 50%;
        transform-origin: center;
    }

    /* Медленное вращение обода (визуальный эффект) */
    @keyframes rimRotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* 🎯 ОСНОВНАЯ КНОПКА СТАРТ */
    .spin-button {
        position: relative;
        width: 88px;
        height: 88px;
        border-radius: 50%;
        border: none;
        padding: 0;
        cursor: pointer;
        background: linear-gradient(145deg, #c0392b 0%, #e74c3c 50%, #a93226 100%);
        box-shadow:
            0 6px 16px rgba(192, 57, 43, 0.6),
            0 2px 4px rgba(0, 0, 0, 0.3),
            inset 0 -4px 8px rgba(0, 0, 0, 0.3),
            inset 0 4px 8px rgba(255, 255, 255, 0.2);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        animation: buttonPulse 2.5s ease-in-out infinite;
        z-index: 2;
    }

    .spin-button-inner {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        color: white;
        z-index: 2;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .spin-icon {
        font-size: 1.6rem;
        margin-left: 3px; /* Визуальная компенсация для play-иконки */
        filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.3));
    }

    .spin-text {
        font-size: 0.7rem;
        font-weight: 900;
        letter-spacing: 1.5px;
        margin-top: 2px;
    }

    /* Свечение вокруг кнопки */
    .spin-glow {
        position: absolute;
        inset: -8px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(251, 191, 36, 0.5) 0%, transparent 70%);
        z-index: 1;
        animation: glowPulse 2.5s ease-in-out infinite;
        pointer-events: none;
    }

    /* Hover-эффект */
    .spin-button:hover {
        transform: scale(1.06);
        box-shadow:
            0 8px 20px rgba(192, 57, 43, 0.7),
            0 2px 4px rgba(0, 0, 0, 0.3),
            inset 0 -4px 8px rgba(0, 0, 0, 0.3),
            inset 0 4px 8px rgba(255, 255, 255, 0.2);
    }

    /* Active-эффект (нажатие) */
    .spin-button:active {
        transform: scale(0.94);
        box-shadow:
            0 3px 8px rgba(192, 57, 43, 0.5),
            0 1px 2px rgba(0, 0, 0, 0.3),
            inset 0 4px 10px rgba(0, 0, 0, 0.4),
            inset 0 -2px 4px rgba(255, 255, 255, 0.1);
        transition: all 0.1s;
    }

    /* 🎯 АНИМАЦИИ */
    @keyframes buttonPulse {
        0%, 100% {
            box-shadow:
                0 6px 16px rgba(192, 57, 43, 0.6),
                0 2px 4px rgba(0, 0, 0, 0.3),
                inset 0 -4px 8px rgba(0, 0, 0, 0.3),
                inset 0 4px 8px rgba(255, 255, 255, 0.2);
        }
        50% {
            box-shadow:
                0 8px 24px rgba(192, 57, 43, 0.8),
                0 2px 4px rgba(0, 0, 0, 0.3),
                inset 0 -4px 8px rgba(0, 0, 0, 0.3),
                inset 0 4px 8px rgba(255, 255, 255, 0.2);
        }
    }

    @keyframes glowPulse {
        0%, 100% {
            opacity: 0.4;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.15);
        }
    }
}
</style>
