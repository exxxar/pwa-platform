<template>
    <section class="shop-hero" :style="heroStyle">

        <!-- Анимированный фон (показывается если нет картинки) -->
        <div v-if="!hasBackgroundImage" class="hero-animated-bg">
            <!-- Градиентные блобы -->
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>
            <div class="blob blob-4"></div>
            <div class="blob blob-5"></div>

            <!-- Aurora-эффект сверху -->
            <div class="aurora"></div>

            <!-- Технологичная сетка -->
            <div class="tech-grid"></div>

            <!-- Плавающие частицы -->
            <div class="particles">
                <span v-for="i in 20" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
        </div>

        <!-- Оверлей (затемнение поверх фона/анимации) -->
        <div class="hero-overlay"></div>

        <!-- Контент -->
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    {{ config.badge }}
                </div>
                <h1 class="hero-title">{{ config.title }}</h1>
                <p class="hero-subtitle">{{ config.subtitle }}</p>
                <button class="btn-primary" @click="$emit('scroll-to-categories')">
                    <i class="fa-solid fa-arrow-down"></i>
                    {{ config.buttonText }}
                </button>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopHero",
    props: {
        config: {
            type: Object,
            default: () => ({
                badge: 'Мобильный магазин',
                title: 'Свежие продукты с доставкой',
                subtitle: 'Заказывайте любимые товары прямо со смартфона',
                backgroundImage: '',
                buttonText: 'Смотреть каталог'
            })
        }
    },
    emits: ['scroll-to-categories'],
    computed: {
        hasBackgroundImage() {
            return false    //!!this.config.backgroundImage && this.config.backgroundImage.trim() !== '';
        },
        heroStyle() {
            if (this.hasBackgroundImage) {
                console.log("bg", this.config.backgroundImage)
                return { backgroundImage: `url(${this.config.backgroundImage})` };
            }
            return {};
        }
    },
    methods: {
        particleStyle(i) {
            // Генерируем случайные параметры для каждой частицы
            const size = Math.random() * 4 + 2;
            const left = Math.random() * 100;
            const delay = Math.random() * 15;
            const duration = Math.random() * 15 + 15;
            const opacity = Math.random() * 0.5 + 0.2;

            return {
                width: `${size}px`,
                height: `${size}px`,
                left: `${left}%`,
                bottom: `-${size}px`,
                opacity: opacity,
                animationDelay: `${delay}s`,
                animationDuration: `${duration}s`
            };
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-hero {
    min-height: 600px;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    position: relative;
    padding: 120px 0 80px;
    overflow: hidden;

    // Если нет картинки — тёмный фон по умолчанию
    &:not([style*="background-image"]) {
        background: #0f0f14;
    }
}

/* ==========================================
   АНИМИРОВАННЫЙ ФОН
   ========================================== */
.hero-animated-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
    background: #0f0f14;
    z-index: 0;
}

/* Градиентные блобы */
.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.6;
    will-change: transform;

    &.blob-1 {
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, #ff7a00 0%, transparent 70%);
        top: -10%;
        left: -10%;
        animation: blob1 25s ease-in-out infinite;
    }

    &.blob-2 {
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, #8b5cf6 0%, transparent 70%);
        top: 40%;
        right: -5%;
        animation: blob2 30s ease-in-out infinite;
    }

    &.blob-3 {
        width: 350px;
        height: 350px;
        background: radial-gradient(circle, #ec4899 0%, transparent 70%);
        bottom: -10%;
        left: 30%;
        animation: blob3 22s ease-in-out infinite;
    }

    &.blob-4 {
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, #3b82f6 0%, transparent 70%);
        top: 20%;
        left: 40%;
        animation: blob4 28s ease-in-out infinite;
    }

    &.blob-5 {
        width: 250px;
        height: 250px;
        background: radial-gradient(circle, #fbbf24 0%, transparent 70%);
        bottom: 20%;
        right: 20%;
        animation: blob5 20s ease-in-out infinite;
    }
}

@keyframes blob1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(100px, 50px) scale(1.1); }
    50% { transform: translate(50px, 100px) scale(0.9); }
    75% { transform: translate(-50px, 50px) scale(1.05); }
}

@keyframes blob2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(-80px, 60px) scale(1.15); }
    66% { transform: translate(-40px, -80px) scale(0.95); }
}

@keyframes blob3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(60px, -80px) scale(1.1); }
    50% { transform: translate(-60px, -40px) scale(0.9); }
    75% { transform: translate(40px, 40px) scale(1.05); }
}

@keyframes blob4 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-100px, 80px) scale(1.2); }
}

@keyframes blob5 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(70px, -50px) scale(1.1); }
    66% { transform: translate(-70px, 50px) scale(0.95); }
}

/* Aurora-эффект (северное сияние) */
.aurora {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 60%;
    background: linear-gradient(
            180deg,
            transparent 0%,
            rgba(255, 122, 0, 0.08) 30%,
            rgba(139, 92, 246, 0.06) 60%,
            transparent 100%
    );
    mix-blend-mode: screen;
    animation: aurora 15s ease-in-out infinite;
    pointer-events: none;
}

@keyframes aurora {
    0%, 100% {
        opacity: 0.6;
        transform: translateY(0) skewY(0deg);
    }
    50% {
        opacity: 1;
        transform: translateY(-20px) skewY(-2deg);
    }
}

/* Технологичная сетка */
.tech-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
    animation: gridPulse 8s ease-in-out infinite;
}

@keyframes gridPulse {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 0.7; }
}

/* Плавающие частицы */
.particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.particle {
    position: absolute;
    background: white;
    border-radius: 50%;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100vh) translateX(50px);
        opacity: 0;
    }
}

/* ==========================================
   ОВЕРЛЕЙ И КОНТЕНТ
   ========================================== */
.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
            135deg,
            rgba(15, 15, 20, 0.75) 0%,
            rgba(15, 15, 20, 0.5) 100%
    );
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 700px;
    color: white;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.6rem 1.2rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    animation: fadeInUp 0.8s ease;
}

.hero-title {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.8s ease 0.2s both;
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 2rem;
    line-height: 1.6;
    animation: fadeInUp 0.8s ease 0.4s both;
}

.btn-primary {
    animation: fadeInUp 0.8s ease 0.6s both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ==========================================
   АДАПТИВНОСТЬ
   ========================================== */
@media (max-width: 768px) {
    .shop-hero {
        min-height: 500px;
        padding: 100px 0 60px;
    }

    .blob {
        filter: blur(60px);

        &.blob-1 { width: 300px; height: 300px; }
        &.blob-2 { width: 250px; height: 250px; }
        &.blob-3 { width: 200px; height: 200px; }
        &.blob-4 { width: 180px; height: 180px; }
        &.blob-5 { width: 150px; height: 150px; }
    }

    .tech-grid {
        background-size: 40px 40px;
    }
}

/* Отключаем анимацию для пользователей с prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
    .blob,
    .aurora,
    .tech-grid,
    .particle {
        animation: none !important;
    }

    .particle {
        opacity: 0.3;
    }
}
</style>
