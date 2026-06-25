<template>
    <div class="taplink-page" :style="{ background: gradientBackground }">

        <!-- Анимированный фон -->
        <div class="animated-bg">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>
            <div class="particles">
                <span v-for="i in 20" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
        </div>

        <!-- Контент -->
        <div class="taplink-content">

            <!-- Skeleton загрузка -->
            <div v-if="isLoading" class="skeleton-wrapper">
                <div class="skeleton-avatar shimmer"></div>
                <div class="skeleton-title shimmer"></div>
                <div class="skeleton-subtitle shimmer"></div>
                <div v-for="i in 4" :key="i" class="skeleton-link shimmer"></div>
            </div>

            <!-- Ошибка -->
            <div v-else-if="lastError" class="error-state">
                <div class="error-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3>Упс! Что-то пошло не так</h3>
                <p>{{ lastError }}</p>
                <button class="retry-btn" @click="reload">
                    <i class="fa-solid fa-rotate-right"></i>
                    Попробовать снова
                </button>
            </div>

            <!-- Основной контент -->
            <div v-else-if="isLoaded" class="main-content">

                <!-- Профиль -->
                <div class="profile-section fade-in">
                    <div class="avatar-wrapper">
                        <div class="avatar-glow"></div>
                        <div class="profile-avatar">
                            <img v-if="profileAvatar" :src="profileAvatar" alt="Avatar">
                            <i v-else class="fa-solid fa-store"></i>
                        </div>
                    </div>

                    <h1 class="profile-name">{{ profileName }}</h1>
                    <p v-if="profileDescription" class="profile-description">
                        {{ profileDescription }}
                    </p>

                    <!-- Кнопки действий -->
                    <div class="action-buttons">
                        <button v-if="canShare" class="action-btn" @click="handleShare">
                            <i class="fa-solid fa-share-nodes"></i>
                            <span>Поделиться</span>
                        </button>
                        <button class="action-btn" @click="handleCopy">
                            <i class="fa-solid fa-copy"></i>
                            <span>{{ copied ? 'Скопировано!' : 'Копировать' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Соцсети (если есть) -->
                <div v-if="socialLinks.length > 0" class="social-section fade-in-up" style="animation-delay: 0.2s">
                    <div class="social-buttons">
                        <a
                            v-for="link in socialLinks"
                            :key="link.id"
                            :href="link.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn"
                            :style="{ background: link.icon_bg || 'rgba(255,255,255,0.2)' }"
                            :title="link.title"
                        >
                            <i :class="link.icon || 'fa-solid fa-link'"></i>
                        </a>
                    </div>
                </div>

                <!-- Основные ссылки -->
                <div class="links-section">
                    <a
                        v-for="(link, index) in mainLinks"
                        :key="link.id"
                        :href="link.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="link-card fade-in-up"
                        :style="{ animationDelay: `${0.3 + index * 0.1}s` }"
                    >
                        <div class="link-content">
                            <div class="link-icon" :style="{ background: link.icon_bg || 'rgba(255,255,255,0.15)' }">
                                <i :class="link.icon || 'fa-solid fa-link'"></i>
                            </div>
                            <div class="link-info">
                                <span class="link-title">{{ link.title }}</span>
                                <span v-if="link.description" class="link-description">
                                    {{ link.description }}
                                </span>
                            </div>
                            <i class="fa-solid fa-chevron-right link-arrow"></i>
                        </div>
                        <div class="link-shine"></div>
                    </a>
                </div>

                <!-- Футер -->
                <div class="taplink-footer fade-in" style="animation-delay: 1s">
                    <p class="copyright">© {{ currentYear }} {{ profileName }}</p>
                    <p class="powered">Работает на <strong>PWA Store</strong></p>
                </div>

            </div>

        </div>

    </div>
</template>

<script>
import { useRoute } from 'vue-router';
import { useTaplink } from '@/MobileClient/Composables/useTaplink.js';

export default {
    name: 'TapLinkPage',

    setup() {
        const route = useRoute();
        const taplink = useTaplink();

        return {
            route,
            ...taplink,
        };
    },

    data() {
        return {
            copied: false,
            copyTimeout: null,
        };
    },

    computed: {
        currentYear() {
            return new Date().getFullYear();
        },
    },

    async mounted() {
        const slug = this.route.params.slug || window.Tenant?.slug;

        if (slug) {
            try {
                await this.loadTaplink(slug);
            } catch (error) {
                console.error('Ошибка загрузки taplink:', error);
            }
        }
    },

    beforeUnmount() {
        if (this.copyTimeout) {
            clearTimeout(this.copyTimeout);
        }
    },

    methods: {
        /**
         * Генерация стилей для частиц
         */
        particleStyle(i) {
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
                animationDuration: `${duration}s`,
            };
        },

        /**
         * Поделиться профилем
         */
        async handleShare() {
            try {
                await this.shareProfile();
            } catch (error) {
                // Пользователь отменил или ошибка
            }
        },

        /**
         * Копирование ссылки
         */
        async handleCopy() {
            try {
                await this.copyLink();
                this.copied = true;

                if (this.copyTimeout) {
                    clearTimeout(this.copyTimeout);
                }

                this.copyTimeout = setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (error) {
                console.error('Ошибка копирования:', error);
            }
        },

        /**
         * Перезагрузка
         */
        async reload() {
            const slug = this.route.params.slug || window.Tenant?.slug;
            if (slug) {
                await this.loadTaplink(slug);
            }
        },
    },
};
</script>

<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$white: #ffffff;
$white-90: rgba(255, 255, 255, 0.9);
$white-70: rgba(255, 255, 255, 0.7);
$white-50: rgba(255, 255, 255, 0.5);
$white-30: rgba(255, 255, 255, 0.3);  // ← Добавлено
$white-20: rgba(255, 255, 255, 0.2);
$white-10: rgba(255, 255, 255, 0.1);
$black-20: rgba(0, 0, 0, 0.2);
$black-10: rgba(0, 0, 0, 0.1);

// ==========================================
// БАЗА
// ==========================================
.taplink-page {
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
}

// ==========================================
// АНИМИРОВАННЫЙ ФОН
// ==========================================
.animated-bg {
    position: fixed;
    inset: 0;
    overflow: hidden;
    z-index: 0;
    pointer-events: none;
}

.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
    will-change: transform;

    &.blob-1 {
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.3);
        top: -10%;
        right: -10%;
        animation: blobFloat 20s ease-in-out infinite;
    }

    &.blob-2 {
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.2);
        bottom: -5%;
        left: -5%;
        animation: blobFloat 25s ease-in-out infinite reverse;
    }

    &.blob-3 {
        width: 250px;
        height: 250px;
        background: rgba(255, 255, 255, 0.15);
        top: 50%;
        left: 50%;
        animation: blobFloat 18s ease-in-out infinite;
    }
}

@keyframes blobFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.95); }
}

.particles {
    position: absolute;
    inset: 0;
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
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% {
        transform: translateY(-100vh) translateX(50px);
        opacity: 0;
    }
}

// ==========================================
// КОНТЕНТ
// ==========================================
.taplink-content {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 480px;
}

// ==========================================
// SKELETON ЗАГРУЗКА
// ==========================================
.skeleton-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 40px 0;
}

.skeleton-avatar {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background: $white-20;
}

.skeleton-title {
    width: 200px;
    height: 24px;
    border-radius: 12px;
    background: $white-20;
}

.skeleton-subtitle {
    width: 280px;
    height: 16px;
    border-radius: 8px;
    background: $white-20;
}

.skeleton-link {
    width: 100%;
    height: 72px;
    border-radius: 16px;
    background: $white-20;
}

.shimmer {
    background: linear-gradient(
            90deg,
            $white-10 0%,
            $white-20 50%,
            $white-10 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

// ==========================================
// ОШИБКА
// ==========================================
.error-state {
    text-align: center;
    padding: 60px 20px;
    color: white;

    .error-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: $white-20;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 20px;
    }

    h3 {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 8px;
    }

    p {
        font-size: 0.95rem;
        opacity: 0.8;
        margin: 0 0 24px;
    }
}

.retry-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: $white-20;
    backdrop-filter: blur(10px);
    border: 1px solid $white-50;
    border-radius: 50px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
        background: $white-30;
        transform: translateY(-2px);
    }
}

// ==========================================
// ПРОФИЛЬ
// ==========================================
.profile-section {
    text-align: center;
    margin-bottom: 32px;
}

.avatar-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
}

.avatar-glow {
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    background: radial-gradient(circle, $white-50 0%, transparent 70%);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.profile-avatar {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: $white-20;
    backdrop-filter: blur(10px);
    border: 4px solid $white-50;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: 0 8px 32px $black-20;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    i {
        font-size: 2.5rem;
        color: white;
    }
}

.profile-name {
    font-size: 1.8rem;
    font-weight: 800;
    color: white;
    margin: 0 0 8px;
    text-shadow: 0 2px 8px $black-20;
}

.profile-description {
    font-size: 1rem;
    color: $white-90;
    margin: 0 0 20px;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: $white-20;
    backdrop-filter: blur(10px);
    border: 1px solid $white-50;
    border-radius: 50px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
        background: $white-30;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px $black-20;
    }

    i {
        font-size: 0.9rem;
    }
}

// ==========================================
// СОЦСЕТИ
// ==========================================
.social-section {
    margin-bottom: 24px;
}

.social-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.social-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 12px $black-20;

    &:hover {
        transform: translateY(-4px) scale(1.1);
        box-shadow: 0 8px 20px $black-20;
    }
}

// ==========================================
// ССЫЛКИ
// ==========================================
.links-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 40px;
}

.link-card {
    position: relative;
    background: $white-90;
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 16px 20px;
    text-decoration: none;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid $white-50;
    box-shadow: 0 4px 6px $black-10;

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px $black-20;

        .link-shine {
            transform: translateX(100%);
        }

        .link-arrow {
            transform: translateX(4px);
        }
    }

    &:active {
        transform: translateY(-2px) scale(0.98);
    }
}

.link-content {
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    z-index: 1;
}

.link-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    flex-shrink: 0;
}

.link-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.link-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.link-description {
    font-size: 0.85rem;
    color: #6b7280;
}

.link-arrow {
    color: #9ca3af;
    font-size: 0.9rem;
    transition: transform 0.3s;
}

.link-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
            90deg,
            transparent 0%,
            rgba(255, 255, 255, 0.4) 50%,
            transparent 100%
    );
    transition: transform 0.6s;
}

// ==========================================
// ФУТЕР
// ==========================================
.taplink-footer {
    text-align: center;
    color: $white-70;
    font-size: 0.85rem;

    .copyright {
        margin: 0 0 4px;
    }

    .powered {
        margin: 0;
        font-size: 0.75rem;
        opacity: 0.7;

        strong {
            font-weight: 700;
        }
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.fade-in {
    animation: fadeIn 0.6s ease-out;
}

.fade-in-up {
    opacity: 0;
    animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
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

// ==========================================
// АДАПТИВ
// ==========================================
@media (max-width: 480px) {
    .taplink-page {
        padding: 30px 16px;
    }

    .profile-name {
        font-size: 1.5rem;
    }

    .profile-description {
        font-size: 0.9rem;
    }

    .link-card {
        padding: 14px 16px;
    }

    .link-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .social-btn {
        width: 44px;
        height: 44px;
        font-size: 1.1rem;
    }
}
</style>
