<template>
    <div class="taplink-page" :style="{ background: gradientBackground }">
        <!-- Текстура шума для премиального вида -->
        <div class="noise-overlay"></div>

        <!-- Анимированный фон -->
        <div class="animated-bg">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>
            <div class="particles">
                <span v-for="i in 15" :key="i" class="particle" :style="particleStyle(i)"></span>
            </div>
        </div>

        <!-- Контент -->
        <div class="taplink-content">
            <!-- Основной контент (показываем сразу, если tenant передан с бэкенда) -->
            <div v-if="isReady" class="main-content">
                <!-- Профиль -->
                <div class="profile-section fade-in">
                    <div class="avatar-wrapper">
                        <div class="avatar-ring"></div>
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
                        <button class="action-btn" :class="{ 'is-copied': copied }" @click="handleCopy">
                            <i :class="copied ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
                            <span>{{ copied ? 'Скопировано' : 'Копировать' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Соцсети (если есть) -->
                <div v-if="socialLinks.length > 0" class="social-section fade-in-up" style="animation-delay: 0.15s">
                    <div class="social-buttons">
                        <a
                            v-for="link in socialLinks"
                            :key="link.id"
                            :href="link.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn"
                            :style="{ background: link.icon_bg || 'rgba(255,255,255,0.1)' }"
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
                        :style="{ animationDelay: `${0.25 + index * 0.08}s` }"
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
                            <div class="link-arrow-wrapper">
                                <i class="fa-solid fa-chevron-right link-arrow"></i>
                            </div>
                        </div>
                    </a>

                    <!-- Заглушка, если ссылок нет -->
                    <div v-if="mainLinks.length === 0" class="empty-state fade-in">
                        <p>Ссылки пока не добавлены</p>
                    </div>
                </div>

                <!-- Футер -->
                <div class="taplink-footer fade-in" style="animation-delay: 0.8s">
                    <p class="copyright">© {{ currentYear }} {{ profileName }}</p>
                    <p class="powered">Работает на <strong>PWA Store</strong></p>
                </div>
            </div>

            <!-- Ошибка (резервный вариант, если что-то пошло не так) -->
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
        </div>
    </div>
</template>

<script>
import { useRoute } from 'vue-router';
import { useTaplink } from '@/MobileClient/Composables/useTaplink.js';

export default {
    name: 'TapLinkPage',
    props: {
        tenant: {
            type: Object,
            required: true
        },
        tenant_user: {
            type: Object,
            default: null
        },
    },
    setup() {
        const route = useRoute();
        const taplink = useTaplink();
        return { route, ...taplink };
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

        // 🆕 Исправлено: добавлено отсутствующее свойство
        gradientBackground() {
            return this.tenant?.background_color || 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%)';
        },

        profileName() {
            return this.tenant?.name || this.tenant?.short_name || 'TapLink';
        },

        profileDescription() {
            return this.tenant?.description || '';
        },

        profileAvatar() {
            return this.tenant?.icon || null;
        },

        // 🆕 Маппим данные из пропса, чтобы не делать лишний API-запрос при первой загрузке
        mainLinks() {
            if (!this.tenant?.tap_links) return [];
            return this.tenant.tap_links.map(link => ({
                id: link.id,
                title: link.title,
                url: link.url,
                icon: link.icon || 'fa-solid fa-link',
                icon_bg: link.icon_bg || 'rgba(255,255,255,0.15)',
                description: link.description || '' // Добавьте это поле в БД, если нужно
            }));
        },

        socialLinks() {
            // Если в будущем добавите поле type: 'social' в таблицу tap_links,
            // можно фильтровать здесь: this.mainLinks.filter(l => l.type === 'social')
            return [];
        },

        isReady() {
            // Показываем контент сразу, так как Inertia уже передала данные
            return !!this.tenant;
        },

        canShare() {
            return !!navigator.share;
        }
    },

    async mounted() {
        // Если по какой-то причине нужно обновить данные динамически,
        // можно использовать composable, но первичная отрисовка уже прошла
        const slug = this.route.params.slug || this.tenant?.slug;
        if (slug && this.mainLinks.length === 0) {
            try {
                await this.loadTaplink(slug);
            } catch (error) {
                console.error('Ошибка загрузки taplink:', error);
            }
        }
    },

    beforeUnmount() {
        if (this.copyTimeout) clearTimeout(this.copyTimeout);
    },

    methods: {
        particleStyle(i) {
            const size = Math.random() * 3 + 1;
            const left = Math.random() * 100;
            const delay = Math.random() * 10;
            const duration = Math.random() * 10 + 15;
            return {
                width: `${size}px`,
                height: `${size}px`,
                left: `${left}%`,
                bottom: `-${size}px`,
                opacity: Math.random() * 0.4 + 0.1,
                animationDelay: `${delay}s`,
                animationDuration: `${duration}s`,
            };
        },

        async handleShare() {
            try {
                if (this.shareProfile) {
                    await this.shareProfile();
                } else {
                    // Fallback, если composable не предоставляет shareProfile
                    await navigator.share({
                        title: this.profileName,
                        text: this.profileDescription,
                        url: window.location.href
                    });
                }
            } catch (error) {
                // Игнорируем отмену пользователем
            }
        },

        async handleCopy() {
            try {
                if (this.copyLink) {
                    await this.copyLink();
                } else {
                    await navigator.clipboard.writeText(window.location.href);
                }

                this.copied = true;
                if (this.copyTimeout) clearTimeout(this.copyTimeout);
                this.copyTimeout = setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (error) {
                console.error('Ошибка копирования:', error);
            }
        },

        async reload() {
            window.location.reload();
        },
    },
};
</script>



<style lang="scss" scoped>
// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$glass-bg: rgba(255, 255, 255, 0.08);
$glass-border: rgba(255, 255, 255, 0.15);
$glass-hover: rgba(255, 255, 255, 0.15);
$text-primary: #ffffff;
$text-secondary: rgba(255, 255, 255, 0.75);
$shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
$shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
$shadow-glow: 0 0 20px rgba(255, 255, 255, 0.15);

// ==========================================
// БАЗА И ФОН
// ==========================================
.taplink-page {
    min-height: 100vh;
    min-height: 100 dvh;
    position: relative;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px 60px;
    color: $text-primary;
}

// Текстура шума для премиального вида
.noise-overlay {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    opacity: 0.04;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
}

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
    opacity: 0.5;
    will-change: transform;

    &.blob-1 {
        width: 500px;
        height: 500px;
        background: rgba(255, 255, 255, 0.15);
        top: -10%;
        right: -10%;
        animation: blobFloat 25s ease-in-out infinite;
    }

    &.blob-2 {
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.1);
        bottom: -5%;
        left: -5%;
        animation: blobFloat 30s ease-in-out infinite reverse;
    }

    &.blob-3 {
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.08);
        top: 40%;
        left: 50%;
        animation: blobFloat 20s ease-in-out infinite;
    }
}

@keyframes blobFloat {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -40px) scale(1.05);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.95);
    }
}

.particles {
    position: absolute;
    inset: 0;
}

.particle {
    position: absolute;
    background: white;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
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
        transform: translateY(-100vh) translateX(30px);
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
    max-width: 520px;
}

// ==========================================
// SKELETON
// ==========================================
.skeleton-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    padding: 40px 0;
}

.skeleton-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: $glass-bg;
}

.skeleton-title {
    width: 180px;
    height: 24px;
    border-radius: 12px;
    background: $glass-bg;
}

.skeleton-subtitle {
    width: 260px;
    height: 16px;
    border-radius: 8px;
    background: $glass-bg;
}

.skeleton-link {
    width: 100%;
    height: 72px;
    border-radius: 16px;
    background: $glass-bg;
}

.shimmer {
    background: linear-gradient(90deg, $glass-bg 0%, rgba(255, 255, 255, 0.15) 50%, $glass-bg 100%);
    background-size: 200% 100%;
    animation: shimmer 1.8s ease-in-out infinite;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

// ==========================================
// ОШИБКА
// ==========================================
.error-state {
    text-align: center;
    padding: 60px 20px;
    background: $glass-bg;
    backdrop-filter: blur(12px);
    border: 1px solid $glass-border;
    border-radius: 24px;

    .error-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin: 0 auto 20px;
    }

    h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0 0 8px;
    }

    p {
        font-size: 0.95rem;
        color: $text-secondary;
        margin: 0 0 24px;
    }
}

.retry-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: white;
    color: #111;
    border: none;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    &:hover {
        transform: translateY(-2px);
        box-shadow: $shadow-glow;
    }

    &:active {
        transform: scale(0.96);
    }
}

// ==========================================
// ПРОФИЛЬ
// ==========================================
.profile-section {
    text-align: center;
    margin-bottom: 36px;
}

.avatar-wrapper {
    position: relative;
    width: 110px;
    height: 110px;
    margin: 0 auto 24px;
}

// Премиальное градиентное кольцо
.avatar-ring {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    background: conic-gradient(from 0deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.8));
    animation: spinRing 4s linear infinite;
    mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #fff calc(100% - 2px));
    -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #fff calc(100% - 2px));
}

@keyframes spinRing {
    to {
        transform: rotate(360deg);
    }
}

.profile-avatar {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: $glass-bg;
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: $shadow-md;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    i {
        font-size: 2.2rem;
        color: rgba(255, 255, 255, 0.9);
    }
}

.profile-name {
    font-size: 1.75rem;
    font-weight: 800;
    color: $text-primary;
    margin: 0 0 8px;
    letter-spacing: -0.02em;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.profile-description {
    font-size: 1rem;
    color: $text-secondary;
    margin: 0 0 24px;
    line-height: 1.6;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.action-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: $glass-bg;
    backdrop-filter: blur(12px);
    border: 1px solid $glass-border;
    border-radius: 50px;
    color: $text-primary;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    &:hover {
        background: $glass-hover;
        transform: translateY(-2px);
        box-shadow: $shadow-sm;
    }

    &:active {
        transform: scale(0.96);
    }

    &.is-copied {
        background: rgba(34, 197, 94, 0.2);
        border-color: rgba(34, 197, 94, 0.4);
        color: #86efac;
    }
}

// ==========================================
// СОЦСЕТИ
// ==========================================
.social-section {
    margin-bottom: 28px;
}

.social-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.social-btn {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    text-decoration: none;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: $shadow-sm;

    &:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: $shadow-md;
        filter: brightness(1.1);
    }

    &:active {
        transform: scale(0.95);
    }
}

// ==========================================
// ССЫЛКИ (КАРТОЧКИ)
// ==========================================
.links-section {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-bottom: 48px;
}

.link-card {
    position: relative;
    background: $glass-bg;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 20px;
    padding: 16px 20px;
    text-decoration: none;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid $glass-border;
    box-shadow: $shadow-sm;

    &:hover {
        background: $glass-hover;
        transform: translateY(-3px);
        box-shadow: $shadow-md, $shadow-glow;
        border-color: rgba(255, 255, 255, 0.3);

        .link-arrow {
            transform: translateX(4px);
        }
    }

    &:active {
        transform: translateY(-1px) scale(0.98);
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
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
    flex-shrink: 0;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.link-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.link-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: $text-primary;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.link-description {
    font-size: 0.85rem;
    color: $text-secondary;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.link-arrow-wrapper {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    transition: background 0.3s;
}

.link-card:hover .link-arrow-wrapper {
    background: rgba(255, 255, 255, 0.15);
}

.link-arrow {
    color: $text-secondary;
    font-size: 0.85rem;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

// ==========================================
// ФУТЕР
// ==========================================
.taplink-footer {
    text-align: center;
    color: $text-secondary;
    font-size: 0.85rem;
    padding-bottom: 20px;

    .copyright {
        margin: 0 0 4px;
        font-weight: 500;
    }

    .powered {
        margin: 0;
        font-size: 0.75rem;
        opacity: 0.6;

        strong {
            font-weight: 700;
            color: rgba(255, 255, 255, 0.9);
        }
    }
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.fade-in {
    animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.fade-in-up {
    opacity: 0;
    animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(24px);
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
        padding: 32px 16px 40px;
    }
    .profile-name {
        font-size: 1.5rem;
    }
    .profile-description {
        font-size: 0.9rem;
    }
    .link-card {
        padding: 14px 16px;
        border-radius: 16px;
    }
    .link-icon {
        width: 44px;
        height: 44px;
        font-size: 1.15rem;
        border-radius: 12px;
    }
    .social-btn {
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
        border-radius: 14px;
    }
    .action-btn {
        padding: 10px 16px;
        font-size: 0.85rem;
    }
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.95rem;
}
</style>
