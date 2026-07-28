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
        // Инициализируем компосаблы
        const favorites = useFavorites();
        const basket = useBasket();
        const chat = useChat();
        const products = useProducts();

        return {
            favorites,
            basket,
            chat,
            products
        };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        // 🆕 ИСПРАВЛЕНО: Убрано лишнее .value. Vue уже распаковал ref из setup()
        totalUnread() {
            return this.chat.totalUnread || 0;
        },

        qr() {
            const link = this.tenant?.link || '';
            return `https://api.qrserver.com/v1/create-qr-code/?size=450x450&qzone=2&data=${encodeURIComponent(link)}`;
        }
    },

    created() {
        // Инициализация глобальных переменных
        window.TenantUser = this.tenant_user || null;
        window.Tenant = this.tenant;

        const schedule = this.tenant?.settings?.schedule || [];
        if (this.isCorrectSchedule(schedule)) {
            const isWork = this.checkIsWork(schedule);
            if (this.tenant?.settings) {
                this.tenant.settings.is_work = isWork;
            }
        } else {
            if (this.tenant?.settings) {
                this.tenant.settings.is_work = false;
            }
        }
    },

    async mounted() {
        // 1. Сначала синхронно заполняем сторы начальными данными (если они есть)
        this.initializeStores();

        // 2. 🆕 ИСПРАВЛЕНО: Загружаем данные ТОЛЬКО если они еще не были гидратированы из initial_data
        // Это предотвращает ДВОЙНОЙ запрос и сброс корзины в пустой массив
        try {
            const promises = [];

            if (!this.basket.isHydrated) {
                promises.push(this.basket.loadProductsInBasket());
            }
            if (!this.favorites.isHydrated) {
                promises.push(this.favorites.loadFavorites());
            }

            // Загружаем счетчик чатов (он легкий)
            promises.push(this.chat.fetchUnreadCount());

            await Promise.allSettled(promises);

            // Обновляем счетчик чатов каждую минуту
            this.unreadInterval = setInterval(async () => {
                try {
                    await this.chat.fetchUnreadCount();
                } catch (error) {
                    console.warn('Ошибка обновления счетчика чатов:', error);
                }
            }, 60000);

        } catch (error) {
            console.error('Критическая ошибка инициализации App.vue:', error);
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

            if (data.chats) {
                this.chat.setInitialData(data.chats);
            }
            if (data.cart) {
                this.basket.setInitialData(data.cart);
            }
            if (data.recommendations) {
                this.products.setRecommendations(data.recommendations);
            }
            if (data.products_count) {
                this.products.setTotalCount(data.products_count);
            }
        },

        isCorrectSchedule(schedule) {
            if (!Array.isArray(schedule) || schedule.length !== 7) return false;
            return schedule.every(day => {
                if (!day || typeof day !== "object") return false;
                if (typeof day.closed !== "boolean") return false;
                if (!day.closed) {
                    if (typeof day.start_at !== "string" || !/^\d{2}:\d{2}$/.test(day.start_at)) return false;
                    if (typeof day.end_at !== "string" || !/^\d{2}:\d{2}$/.test(day.end_at)) return false;
                }
                return true;
            });
        },

        checkIsWork(schedule) {
            if (!schedule || schedule.length === 0) return true;

            const now = new Date();
            const day = now.getDay();
            const adjustedDay = day === 0 ? 6 : day - 1;
            const today = schedule[adjustedDay];

            if (!today || today.closed) return false;

            const [startH, startM] = (today.start_at || "08:00").split(":").map(Number);
            const [endH, endM] = (today.end_at || "20:00").split(":").map(Number);

            const nowMinutes = now.getHours() * 60 + now.getMinutes();
            const startMinutes = startH * 60 + startM;
            const endMinutes = endH * 60 + endM;

            if (startMinutes <= endMinutes) {
                return nowMinutes >= startMinutes && nowMinutes < endMinutes;
            } else {
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
