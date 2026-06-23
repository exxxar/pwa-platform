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
        theme: {
            type: String,
            default: null
        }
    },
    setup() {
        const favorites = useFavorites();
        const basket = useBasket();

        return { favorites, basket };
    },

    computed: {
        self() {
            return window.TenantUser || null;
        },

        qr() {
            // Исправлено: используем tenant.link вместо несуществующего this.link
            const link = this.tenant?.link || '';
            return `https://api.qrserver.com/v1/create-qr-code/?size=450x450&qzone=2&data=${encodeURIComponent(link)}`;
        }
    },

    created() {
        console.log("tenant", this.tenant);

        // Инициализация глобальных переменных
        window.TenantUser = this.tenant_user || null;
        window.Tenant = this.tenant; // ← С большой буквы
        window.theme = this.theme || null;

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
        // Параллельно загружаем корзину и избранное
        try {
            await Promise.allSettled([
                this.favorites.loadFavorites(),
                this.basket.loadProductsInBasket(),
            ]);
        } catch (error) {
            console.error('Ошибка инициализации:', error);
        }
    },

    methods: {
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
