<template>
    <section class="pwa-banner">
        <!-- 🆕 Фоновые эффекты -->
        <div class="banner-bg-effects">
            <div class="banner-grid"></div>
            <div class="banner-orb banner-orb--1"></div>
            <div class="banner-orb banner-orb--2"></div>
            <div class="banner-orb banner-orb--3"></div>
            <div class="banner-scanline"></div>
        </div>

        <div class="container">
            <div class="banner-content">
                <!-- Левая часть: Текст -->
                <div class="banner-text">
                    <div class="banner-badge">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                        PWA Приложение
                    </div>
                    <h2>
                        Ваш магазин<br>
                        в <span class="gradient-text">телефоне клиента</span>
                    </h2>
                    <p class="lead">
                        Установите приложение на главный экран. Это быстро, не занимает место в памяти и работает даже без интернета.
                    </p>

                    <ul class="app-features">
                        <li><i class="fa-solid fa-check-circle"></i> Push-уведомления о статусе заказа</li>
                        <li><i class="fa-solid fa-check-circle"></i> Эксклюзивные скидки для пользователей</li>
                        <li><i class="fa-solid fa-check-circle"></i> Мгновенный доступ без ввода пароля</li>
                    </ul>

                    <div class="banner-actions">
                        <button class="install-btn" @click="installApp">
                            <i class="fa-solid fa-download"></i>
                            Установить приложение
                        </button>
                        <span class="install-hint">
                            <i class="fa-solid fa-circle-info"></i>
                            Бесплатно и безопасно
                        </span>
                    </div>
                </div>

                <!-- Правая часть: Мокап телефона с iframe -->
                <div class="banner-visual">
                    <div class="phone-showcase">
                        <div class="phone-frame">
                            <div class="phone-notch"></div>

                            <div class="phone-screen-wrapper">
                                <!-- 🆕 Iframe с вашим PWA -->
                                <iframe
                                    src="/pwa"
                                    class="phone-screen"
                                    title="Предпросмотр PWA приложения"
                                    loading="lazy"
                                ></iframe>
                            </div>

                            <div class="phone-home"></div>
                        </div>
                        <!-- Декоративное свечение за телефоном -->
                        <div class="phone-glow"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopPwaBanner",
    data() {
        return {
            deferredPrompt: null
        };
    },
    mounted() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
        });
    },
    methods: {
        async installApp() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    this.$notify?.({ title: 'Успех', text: 'Приложение добавлено на главный экран!', type: 'success' });
                }
                this.deferredPrompt = null;
            } else {
                this.$notify?.({
                    title: 'Как установить',
                    text: 'Нажмите "Поделиться" (iOS) или "Меню" (Android) и выберите "На экран «Домой»"',
                    type: 'info'
                });
            }
        }
    },
    beforeUnmount() {
        window.removeEventListener('beforeinstallprompt', () => {});
    }
};
</script>

<style lang="scss" scoped>
// Переменные (с фоллбэками на случай, если они не определены глобально)
$primary: var(--primary, #ff8a00);
$primary-light: var(--primary-light, #ffb347);
$dark: var(--dark, #0f172a);
$gray: var(--gray, #64748b);
$light: var(--light, #f8fafc);

.pwa-banner {
    position: relative;
    padding: 100px 0;
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    color: white;
    overflow: hidden;
    isolation: isolate; // Создает новый контекст наложения для фоновых эффектов
}

// ==========================================
// ФОНОВЫЕ ЭФФЕКТЫ
// ==========================================
.banner-bg-effects {
    position: absolute;
    inset: 0;
    z-index: -1;
    overflow: hidden;
}

.banner-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
}

.banner-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
    animation: orbFloat 10s ease-in-out infinite alternate;

    &--1 {
        width: 400px;
        height: 400px;
        background: $primary;
        top: -100px;
        right: -100px;
    }

    &--2 {
        width: 300px;
        height: 300px;
        background: #8b5cf6; // Фиолетовый акцент
        bottom: -50px;
        left: -50px;
        animation-delay: -5s;
    }

    &--3 {
        width: 200px;
        height: 200px;
        background: $primary-light;
        top: 40%;
        left: 30%;
        opacity: 0.2;
        animation-delay: -2s;
    }
}

@keyframes orbFloat {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(30px, -30px) scale(1.1); }
}

.banner-scanline {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    animation: scanline 8s linear infinite;
}

@keyframes scanline {
    0% { top: 0%; opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}

// ==========================================
// КОНТЕНТ
// ==========================================
.banner-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: center;
    position: relative;
    z-index: 1;
}

.banner-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    color: $primary-light;
    padding: 8px 16px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.banner-text h2 {
    font-size: 3rem;
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 1.5rem;
    color: white;
}

.gradient-text {
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lead {
    font-size: 1.15rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 2rem;
    line-height: 1.6;
    max-width: 500px;
}

.app-features {
    list-style: none;
    padding: 0;
    margin: 0 0 2.5rem 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.app-features li {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.05rem;
    color: rgba(255, 255, 255, 0.9);

    i {
        color: $primary;
        font-size: 1.2rem;
        filter: drop-shadow(0 0 8px rgba(255, 138, 0, 0.4));
    }
}

.banner-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
}

.install-btn {
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    border: none;
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(255, 138, 0, 0.3);

    &:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(255, 138, 0, 0.4);
    }
}

.install-hint {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    gap: 6px;
}

// ==========================================
// МОКАП ТЕЛЕФОНА
// ==========================================
.banner-visual {
    display: flex;
    justify-content: center;
    perspective: 1000px;
}

.phone-showcase {
    position: relative;
    animation: phoneFloat 6s ease-in-out infinite;
}

@keyframes phoneFloat {
    0%, 100% { transform: translateY(0) rotateY(-5deg); }
    50% { transform: translateY(-15px) rotateY(-5deg); }
}

.phone-frame {
    position: relative;
    width: 300px;
    height: 600px;
    background: #000;
    border-radius: 45px;
    padding: 12px;
    box-shadow:
        0 0 0 2px #333,
        0 25px 50px -12px rgba(0, 0, 0, 0.8),
        inset 0 0 20px rgba(255, 255, 255, 0.1);
    z-index: 2;
}

.phone-notch {
    position: absolute;
    top: 12px;
    left: 50%;
    transform: translateX(-50%);
    width: 120px;
    height: 28px;
    background: #000;
    border-radius: 0 0 18px 18px;
    z-index: 10;
}

.phone-screen-wrapper {
    width: 100%;
    height: 100%;
    background: #fff;
    border-radius: 36px;
    overflow: hidden;
    position: relative;
}

.phone-screen {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
    // 🆕 Важно: позволяет прокручивать контент внутри iframe, если он есть
    // Если iframe должен быть просто картинкой, можно добавить pointer-events: none;
}

.phone-home {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    z-index: 10;
}

.phone-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 120%;
    height: 120%;
    background: radial-gradient(circle, rgba(255, 138, 0, 0.15) 0%, transparent 70%);
    z-index: 1;
    filter: blur(40px);
    animation: glowPulse 4s ease-in-out infinite alternate;
}

@keyframes glowPulse {
    0% { opacity: 0.5; transform: translate(-50%, -50%) scale(0.9); }
    100% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 992px) {
    .pwa-banner {
        padding: 60px 0;
    }

    .banner-content {
        grid-template-columns: 1fr;
        gap: 40px;
        text-align: center;
    }

    .banner-text h2 {
        font-size: 2.2rem;
    }

    .lead {
        margin: 0 auto 2rem;
    }

    .app-features {
        align-items: center;
        margin-bottom: 2rem;
    }

    .banner-actions {
        align-items: center;
    }

    .banner-visual {
        order: -1; // Показываем телефон первым на мобильных
        margin-bottom: 20px;
    }

    .phone-frame {
        width: 260px;
        height: 520px;
        animation: none; // Отключаем анимацию на мобильных для экономии ресурсов
        transform: none !important;
    }

    .phone-glow {
        width: 100%;
        height: 100%;
    }
}
</style>
