import { defineStore } from 'pinia';
import axios from 'axios';

const BASE = '/tenant';

export const useTaplinkStore = defineStore('taplink', {
    // ==========================================
    // STATE
    // ==========================================
    state: () => ({
        // Данные
        profile: null,
        links: [],
        currentSlug: null,

        // Состояние загрузки
        isLoading: false,
        isHydrated: false,

        // Ошибки
        lastError: null,

        // Время последней синхронизации
        lastSyncAt: null,
    }),

    // ==========================================
    // GETTERS
    // ==========================================
    getters: {
        /**
         * Профиль
         */
        getProfile: (state) => state.profile,

        /**
         * Ссылки
         */
        getLinks: (state) => state.links || [],

        /**
         * Активные ссылки (не скрытые)
         */
        activeLinks: (state) => {
            return (state.links || []).filter(link => link.is_active !== false);
        },

        /**
         * Имя профиля
         */
        profileName: (state) => state.profile?.name || '',

        /**
         * Описание профиля
         */
        profileDescription: (state) => state.profile?.description || '',

        /**
         * Аватар профиля
         */
        profileAvatar: (state) => state.profile?.avatar || null,

        /**
         * Цвет темы
         */
        themeColor: (state) => state.profile?.theme_color || '#ff8a00',

        /**
         * Градиентный фон
         */
        gradientBackground: (state) => {
            const color = state.profile?.theme_color || '#ff8a00';
            const darkerColor = adjustColor(color, -40);
            return `linear-gradient(135deg, ${color} 0%, ${darkerColor} 100%)`;
        },

        /**
         * Соцсети (отдельно от ссылок)
         */
        socialLinks: (state) => {
            return (state.links || []).filter(link => link.is_social);
        },

        /**
         * Основные ссылки (не соцсети)
         */
        mainLinks: (state) => {
            return (state.links || []).filter(link => !link.is_social);
        },

        /**
         * Количество ссылок
         */
        linksCount: (state) => state.links?.length || 0,

        /**
         * Проверка, загружен ли taplink
         */
        isLoaded: (state) => state.isHydrated && state.profile !== null,
    },

    // ==========================================
    // ACTIONS
    // ==========================================
    actions: {
        /**
         * Загрузка данных taplink
         */
        async loadTaplink(slug) {
            if (!slug) {
                throw new Error('Не указан slug taplink');
            }

            this.isLoading = true;
            this.lastError = null;
            this.currentSlug = slug;

            try {
                const response = await axios.get(`${BASE}/${slug}/taplink`);
                const data = response.data;

                this.profile = data.tenant || data.profile || null;
                this.links = data.links || [];
                this.isHydrated = true;
                this.lastSyncAt = new Date();

                // Сохраняем в localStorage для быстрого доступа
                localStorage.setItem(`taplink_${slug}`, JSON.stringify({
                    profile: this.profile,
                    links: this.links,
                    savedAt: Date.now(),
                }));

                return data;
            } catch (err) {
                console.error('[Taplink Store] Ошибка загрузки:', err);
                this.lastError = err.response?.data?.message || 'Не удалось загрузить данные';

                // Fallback: пробуем загрузить из localStorage
                const cached = localStorage.getItem(`taplink_${slug}`);
                if (cached) {
                    try {
                        const parsed = JSON.parse(cached);
                        // Используем кеш только если он не старше 1 часа
                        if (Date.now() - parsed.savedAt < 3600000) {
                            this.profile = parsed.profile;
                            this.links = parsed.links;
                            this.isHydrated = true;
                        }
                    } catch {
                        // Игнорируем ошибки парсинга
                    }
                }

                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Поделиться профилем (нативный шеринг)
         */
        async shareProfile() {
            if (!navigator.share) {
                throw new Error('Web Share API не поддерживается');
            }

            try {
                await navigator.share({
                    title: this.profile?.name || 'TapLink',
                    text: this.profile?.description || '',
                    url: window.location.href,
                });
                return true;
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('[Taplink Store] Ошибка шеринга:', err);
                }
                throw err;
            }
        },

        /**
         * Копирование ссылки в буфер обмена
         */
        async copyLink() {
            try {
                await navigator.clipboard.writeText(window.location.href);
                return true;
            } catch (err) {
                console.error('[Taplink Store] Ошибка копирования:', err);
                throw err;
            }
        },

        /**
         * Сброс состояния
         */
        $reset() {
            this.profile = null;
            this.links = [];
            this.currentSlug = null;
            this.isLoading = false;
            this.isHydrated = false;
            this.lastError = null;
            this.lastSyncAt = null;
        },
    },
});

/**
 * Вспомогательная функция для затемнения/осветления цвета
 */
function adjustColor(color, amount) {
    return '#' + color.replace(/^#/, '').replace(/../g, (c) => {
        const value = parseInt(c, 16);
        const adjusted = Math.min(255, Math.max(0, value + amount));
        return ('0' + adjusted.toString(16)).substr(-2);
    });
}
