<template>
    <Layout>
        <template #default>
            <!-- Убедись, что компонент notifications зарегистрирован глобально -->
            <notifications
                position="top right"
                ignoreDuplicates="true"
                max="3"
                width="100%"
                speed="10"
            />
            <RouteLoader />
            <router-view :tenant="tenant" v-slot="{ Component }">
                    <transition name="page-fade" mode="out-in">
                        <component :is="Component" />
                    </transition>
            </router-view>
        </template>
    </Layout>
</template>
<script>
import Layout from "@/MobileClient/Layouts/Layout.vue";
import RouteLoader from "@/MobileClient/Components/Common/RouteLoader.vue";
import { useFavorites } from '@/MobileClient/Composables/useFavorites.js';
import { useBasket } from '@/MobileClient/Composables/useBasket.js';
import { useChat } from '@/MobileClient/Composables/useChat.js';
import { useProducts } from '@/MobileClient/Composables/useProducts.js';

export default {
    name: "App",

    components: {
        Layout,
        RouteLoader
    },

    props: {
        tenant: {
            type: Object,
            required: true
        },
        tenant_user: {
            type: Object,
            default: null
        },
        initial_data: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            unreadInterval: null,
        };
    },
    setup() {
        const favorites = useFavorites();
        const basket = useBasket();
        const chat = useChat();
        const products = useProducts();


        return { favorites, basket, chat, products };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },
        totalUnread() {
            return this.chat.totalUnread.value;
        },
        qr() {
            // Исправлено: используем tenant.link вместо несуществующего this.link
            const link = this.tenant?.link || '';
            return `https://api.qrserver.com/v1/create-qr-code/?size=450x450&qzone=2&data=${encodeURIComponent(link)}`;
        }
    },

    created() {

        // Инициализация глобальных переменных
        window.TenantUser = this.tenant_user || null;
        window.Tenant = this.tenant; // ← С большой буквы
        // Проверяем расписание работы
        const schedule = this.tenant?.settings?.schedule || [];

        if (this.isCorrectSchedule(schedule)) {
            const isWork = this.checkIsWork(schedule);

            // Добавляем is_work в настройки
            if (this.tenant?.settings) {
                this.tenant.settings.is_work = isWork;
            }

            console.log("Is work:", isWork);
        } else {
            console.warn("Schedule is invalid or empty");
            if (this.tenant?.settings) {
                this.tenant.settings.is_work = false;
            }
        }
    },

    async mounted() {

        this.initializeStores();
        // Параллельно загружаем корзину и избранное
        try {
            await Promise.allSettled([
                this.favorites.loadFavorites(),
                this.basket.loadProductsInBasket(),
                // Загружаем начальный счётчик
                await this.chat.fetchUnreadCount()
            ]);

            // Обновляем каждые 30 секунд
            this.unreadInterval = setInterval(async () => {
                try {
                    await this.chat.fetchUnreadCount();
                } catch (error) {
                    // Игнорируем ошибки
                }
            }, 60000);
        } catch (error) {
            console.error('Ошибка инициализации:', error);
        }


    },
    beforeUnmount() {
        if (this.unreadInterval) {
            clearInterval(this.unreadInterval);
        }
    },
    methods: {

        initializeStores() {
            const data = this.initial_data || {};

            // 1. Инициализация чатов
            if (data.chats) {
                this.chat.setInitialData(data.chats);
            }

            // 2. Инициализация корзины
            if (data.cart) {
                this.basket.setInitialData(data.cart);
            }

            // 3. Инициализация товаров
            if (data.recommendations) {
                this.products.setRecommendations(data.recommendations);
            }

            // 4. Количество товаров в каталоге
            if (data.products_count) {
                this.products.setTotalCount(data.products_count);
            }

            console.log('✅ Stores initialized from initial_data');
        },
        /**
         * Проверяет корректность массива расписания
         * @param {Array} schedule - массив из 7 дней
         * @returns {boolean}
         */
        isCorrectSchedule(schedule) {
            if (!Array.isArray(schedule) || schedule.length !== 7) {
                return false;
            }

            return schedule.every(day => {
                if (!day || typeof day !== "object") {
                    return false;
                }

                // closed может быть true/false
                if (typeof day.closed !== "boolean") {
                    return false;
                }

                // если день не закрыт — должны быть start_at и end_at
                if (!day.closed) {
                    if (typeof day.start_at !== "string") return false;
                    if (typeof day.end_at !== "string") return false;

                    // простая проверка формата HH:MM
                    if (!/^\d{2}:\d{2}$/.test(day.start_at)) return false;
                    if (!/^\d{2}:\d{2}$/.test(day.end_at)) return false;
                }

                return true;
            });
        },

        /**
         * Проверяет, работает ли бизнес сейчас
         * @param {Array} schedule - массив из 7 дней (понедельник = 0, воскресенье = 6)
         * @returns {boolean}
         */
        checkIsWork(schedule) {
            if (!schedule || schedule.length === 0) {
                return true; // Если расписания нет — считаем, что работаем
            }

            const now = new Date();
            const day = now.getDay(); // 0 = воскресенье, 1 = понедельник, ..., 6 = суббота

            // Конвертируем: 0 = понедельник, 6 = воскресенье
            const adjustedDay = day === 0 ? 6 : day - 1;
            const today = schedule[adjustedDay];

            // Если для текущего дня нет настроек или день закрыт
            if (!today || today.closed) {
                return false;
            }

            const [startH, startM] = (today.start_at || "08:00").split(":").map(Number);
            const [endH, endM] = (today.end_at || "20:00").split(":").map(Number);

            const nowMinutes = now.getHours() * 60 + now.getMinutes();
            const startMinutes = startH * 60 + startM;
            const endMinutes = endH * 60 + endM;

            if (startMinutes <= endMinutes) {
                // Обычный график (например 08:00–20:00)
                return nowMinutes >= startMinutes && nowMinutes < endMinutes;
            } else {
                // График через полночь (например 20:00–04:00)
                return nowMinutes >= startMinutes || nowMinutes < endMinutes;
            }
        }
    }
}
</script>
<style>
.page-fade-enter-active,
.page-fade-leave-active {
    transition: opacity 0.2s ease;
}
.page-fade-enter-from,
.page-fade-leave-to {
    opacity: 0;
}
</style>
