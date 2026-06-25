<template>
    <div class="table-manager">

        <!-- ========================================== -->
        <!-- ВЫБОР СТОЛИКА -->
        <!-- ========================================== -->
        <div class="booking-section">
            <BookingDropdown @select="selectATable" />
        </div>

        <!-- ========================================== -->
        <!-- ТАБЫ -->
        <!-- ========================================== -->
        <div class="tabs-wrapper">
            <div class="tabs">
                <button
                    class="tab"
                    :class="{ 'is-active': tab === 0 }"
                    @click="changeTab(0)"
                >
                    <i class="fa-solid fa-chair"></i>
                    <span>Столик</span>
                </button>
                <button
                    v-if="cartTotalCount > 0"
                    class="tab"
                    :class="{ 'is-active': tab === 1 }"
                    @click="changeTab(1)"
                >
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>Корзина</span>
                    <span class="tab-badge">{{ cartTotalCount }}</span>
                </button>
                <button
                    v-if="table.id"
                    class="tab"
                    :class="{ 'is-active': tab === 2 }"
                    @click="changeTab(2)"
                >
                    <i class="fa-solid fa-list-check"></i>
                    <span>Заказы</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОНТЕНТ ВКЛАДОК -->
        <!-- ========================================== -->
        <div class="tab-content-wrapper">
            <transition name="tab-fade" mode="out-in">

                <!-- ========== ВКЛАДКА: СТОЛИК ========== -->
                <div v-if="tab === 0" key="table" class="tab-content">

                    <!-- Карточка брони -->
                    <div v-if="selectedTable" class="booking-card">
                        <div class="booking-header">
                            <div class="booking-icon">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <div class="booking-info">
                                <h3>Бронь столика №{{ selectedTable.number }}</h3>
                                <p>{{ selectedTable.booked_date_at }} в {{ selectedTable.booked_time_at }}</p>
                            </div>
                        </div>
                        <div class="booking-details">
                            <div class="detail-row">
                                <i class="fa-solid fa-user"></i>
                                <span>{{ selectedTable.booked_info?.name || 'Не указано' }}</span>
                            </div>
                            <div class="detail-row">
                                <i class="fa-solid fa-phone"></i>
                                <span>{{ selectedTable.booked_info?.phone || 'Не указано' }}</span>
                            </div>
                            <div class="detail-row">
                                <i class="fa-solid fa-users"></i>
                                <span>{{ selectedTable.booked_info?.persons }} персон</span>
                            </div>
                            <div v-if="selectedTable.booked_info?.description" class="detail-row">
                                <i class="fa-solid fa-comment"></i>
                                <span>{{ selectedTable.booked_info.description }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Информация о столике -->
                    <template v-if="table.id">

                        <!-- Предупреждение о закрытии -->
                        <div v-if="table.closed_at" class="closed-notice">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <div>
                                <strong>Столик закрыт</strong>
                                <p>Операции со столиком недоступны</p>
                            </div>
                        </div>

                        <!-- Информация о столике -->
                        <div class="info-card">
                            <div class="card-header">
                                <div class="header-icon primary">
                                    <i class="fa-solid fa-chair"></i>
                                </div>
                                <h3>Информация о столике</h3>
                            </div>
                            <div class="info-grid">
                                <InfoRow icon="fa-solid fa-hashtag" label="Номер" :value="'#' + (table.number || '1')" />
                                <InfoRow
                                    icon="fa-solid fa-user-plus"
                                    label="Оформлен на"
                                    :value="table.creator?.fio_from_telegram || 'Не указано'"
                                    :clickable="!!table.creator"
                                    @click="openCreatorProfile"
                                />
                                <InfoRow
                                    icon="fa-solid fa-user-tie"
                                    label="Обслуживает"
                                    :value="table.officiant?.name || table.officiant?.fio_from_telegram || 'Не указано'"
                                    :clickable="!!table.officiant"
                                    @click="openWaiterProfile"
                                />
                                <InfoRow icon="fa-solid fa-users" label="Гостей" :value="table.clients.length + ' чел.'" />
                                <InfoRow icon="fa-solid fa-utensils" label="Вы заказали" :value="self_summary_count + ' ед.'" />
                                <InfoRow icon="fa-solid fa-list" label="Всего заказано" :value="summary_count + ' ед.'" />
                            </div>

                            <!-- Итоги -->
                            <div class="totals-section">
                                <div class="total-row">
                                    <span>По вам</span>
                                    <strong>{{ self_summary_price }} ₽ <small>({{ self_summary_count }} ед.)</small></strong>
                                </div>
                                <div class="total-row">
                                    <span>Доп. услуги</span>
                                    <strong>{{ (table.additional_services || []).length }} ед. на {{ servicePrice }} ₽</strong>
                                </div>
                                <div class="total-row grand">
                                    <span>Итого по столику</span>
                                    <strong>{{ fullTablePrice }} ₽ <small>({{ summary_count }} ед.)</small></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопка вызова официанта -->
                        <button
                            class="action-btn outline"
                            :disabled="spent_time_counter > 0 || table.closed_at"
                            @click="callWaiter(false)"
                        >
                            <i class="fa-regular fa-bell"></i>
                            <span v-if="spent_time_counter <= 0">Позвать официанта</span>
                            <span v-else>Осталось ждать {{ spent_time_counter }} сек.</span>
                        </button>

                        <!-- Бонусы -->
                        <div v-if="settings.need_bonuses_section" class="bonuses-section">
                            <div class="section-divider">
                                <span>Бонусы</span>
                                <small>нажми для использования</small>
                            </div>
                            <div
                                class="cashback-toggle"
                                :class="{ 'is-active': orderForm.use_cashback }"
                                @click="orderForm.use_cashback = !orderForm.use_cashback"
                            >
                                <div class="cashback-icon">
                                    <i class="fa-solid fa-coins"></i>
                                </div>
                                <div class="cashback-info">
                                    <span>Списать баллы</span>
                                    <strong>{{ cashbackLimit }} ₽</strong>
                                </div>
                                <div class="cashback-switch" :class="{ 'is-on': orderForm.use_cashback }">
                                    <span class="switch-knob"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Форма оплаты -->
                        <form @submit.prevent="startTablePay" class="payment-form">
                            <div class="section-divider">
                                <span>Оплата заказа</span>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fa-solid fa-signature"></i>
                                    Ф.И.О.
                                </label>
                                <input
                                    type="text"
                                    v-model="orderForm.client.name"
                                    class="form-input"
                                    placeholder="Иванов Иван"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fa-solid fa-mobile-screen-button"></i>
                                    Телефон
                                </label>
                                <input
                                    type="tel"
                                    v-model="orderForm.client.phone"
                                    v-mask="['+7(###)###-##-##']"
                                    class="form-input"
                                    placeholder="+7(999) 123-45-67"
                                    required
                                >
                            </div>

                            <!-- День рождения -->
                            <div class="birthday-toggle" @click="orderForm.client.have_birthday = !orderForm.client.have_birthday">
                                <div class="birthday-icon">
                                    <i class="fa-solid fa-cake-candles"></i>
                                </div>
                                <div class="birthday-info">
                                    <strong>У меня День рождения!</strong>
                                </div>
                                <div class="birthday-switch" :class="{ 'is-on': orderForm.client.have_birthday }">
                                    <span class="switch-knob"></span>
                                </div>
                            </div>

                            <div v-if="orderForm.client.have_birthday" class="birthday-notice">
                                <i class="fa-solid fa-gift"></i>
                                <span>При предъявлении <strong>паспорта</strong> вы получите подарок от заведения!</span>
                            </div>

                            <!-- Кнопки оплаты -->
                            <div class="payment-buttons">
                                <button
                                    type="submit"
                                    class="payment-btn"
                                    :disabled="spent_time_counter > 0 || table.closed_at"
                                    @click="orderForm.is_self = true"
                                >
                                    <i class="fa-solid fa-person-circle-check"></i>
                                    <span v-if="spent_time_counter <= 0">
                                        Оплатить за себя
                                        <small>{{ self_summary_price }} ₽</small>
                                    </span>
                                    <span v-else>Осталось ждать {{ spent_time_counter }} сек.</span>
                                </button>
                                <button
                                    type="submit"
                                    class="payment-btn"
                                    :disabled="spent_time_counter > 0 || table.closed_at"
                                    @click="orderForm.is_self = false"
                                >
                                    <i class="fa-solid fa-people-line"></i>
                                    <span v-if="spent_time_counter <= 0">
                                        Оплатить за столик
                                        <small>{{ summary_price }} ₽</small>
                                    </span>
                                    <span v-else>Осталось ждать {{ spent_time_counter }} сек.</span>
                                </button>
                            </div>

                            <div class="cash-notice">
                                <span>Для оплаты наличными</span>
                                <button type="button" class="link-btn" @click="callWaiter(true)">
                                    пригласите официанта
                                </button>
                            </div>
                        </form>

                    </template>

                    <!-- Столик не обслуживается -->
                    <div v-else class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-solid fa-chair"></i>
                        </div>
                        <h3>Столик не обслуживается</h3>
                        <p>Выберите столик из списка выше</p>
                    </div>

                </div>

                <!-- ========== ВКЛАДКА: КОРЗИНА ========== -->
                <div v-else-if="tab === 1" key="cart" class="tab-content">

                    <div v-if="table.closed_at" class="closed-notice">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div>
                            <strong>Столик закрыт</strong>
                            <p>Операции со столиком недоступны</p>
                        </div>
                    </div>

                    <div class="cart-header">
                        <h3>Ваш текущий заказ</h3>
                        <p v-if="cartTotalCount > 0">
                            Заказ ещё не передан в работу. После подтверждения отменить будет нельзя.
                        </p>
                        <p v-else>В корзине пока пусто</p>
                    </div>

                    <CartProductList
                        v-if="loaded_settings"
                        :simple-mode="true"
                        :form-data="orderForm"
                        :settings="settings"
                        @select-prize="selectPrize"
                        @change-tab="changeTab"
                    />

                    <div v-else class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Вы ещё ничего не выбрали из меню</p>
                    </div>

                    <!-- Кнопки подтверждения -->
                    <template v-if="selectedTable">
                        <div v-if="cartTotalCount > 0" class="warning-notice">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>Оформленный заказ необходимо сразу оплатить!</span>
                        </div>
                        <button
                            class="action-btn primary"
                            :disabled="cartTotalCount === 0 || spent_time_counter > 0 || table.closed_at"
                            @click="changeOrderStatus"
                        >
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span v-if="spent_time_counter <= 0">Подтвердить заказ</span>
                            <span v-else>Осталось ждать {{ spent_time_counter }} сек.</span>
                        </button>
                    </template>

                    <template v-else>
                        <div v-if="sent_to_waiter" class="info-notice">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Заказ передан официанту. После подтверждения он появится в списке заказов столика.</span>
                        </div>
                        <button
                            class="action-btn primary"
                            :disabled="cartTotalCount === 0 || spent_time_counter > 0 || table.closed_at"
                            @click="makeOrder"
                        >
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span v-if="spent_time_counter <= 0">Оформить заказ</span>
                            <span v-else>Осталось ждать {{ spent_time_counter }} сек.</span>
                        </button>
                    </template>

                </div>

                <!-- ========== ВКЛАДКА: ЗАКАЗЫ ========== -->
                <div v-else-if="tab === 2" key="orders" class="tab-content">

                    <div v-if="table.closed_at" class="closed-notice">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div>
                            <strong>Столик закрыт</strong>
                            <p>Операции со столиком недоступны</p>
                        </div>
                    </div>

                    <div class="section-divider">
                        <span>Заказы клиентов</span>
                    </div>

                    <template v-if="basket.length > 0">
                        <div
                            v-for="(item, idx) in basket"
                            :key="idx"
                            class="order-card"
                        >
                            <div class="order-header">
                                <div class="client-name">
                                    <i class="fa-solid fa-user"></i>
                                    <span>{{ item.name || 'Клиент' }}</span>
                                    <span v-if="self.id === item.id" class="self-badge">
                                        <i class="fa-solid fa-star"></i> Вы
                                    </span>
                                </div>
                                <div class="order-total">
                                    {{ item.summary_price || 0 }} ₽
                                </div>
                            </div>
                            <div class="order-items">
                                <div
                                    v-for="(basketItem, bIdx) in item.basket"
                                    :key="bIdx"
                                    class="order-item"
                                >
                                    <div class="item-status">
                                        <i
                                            class="fa-solid fa-check-double"
                                            :class="basketItem.table_approved_at ? 'approved' : 'pending'"
                                        ></i>
                                    </div>
                                    <div class="item-info">
                                        <span class="item-name">{{ basketItem.product.title }}</span>
                                        <span class="item-details">
                                            {{ basketItem.count }} ед. × {{ basketItem.product.current_price }} ₽
                                        </span>
                                    </div>
                                    <div class="item-price">
                                        {{ basketItem.count * basketItem.product.current_price }} ₽
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div v-else class="empty-state small">
                        <i class="fa-solid fa-receipt"></i>
                        <p>Заказов пока нет</p>
                    </div>

                    <button
                        class="action-btn outline"
                        :disabled="spent_time_counter > 0 || table.closed_at"
                        @click="sendOrderToMyChat"
                    >
                        <i class="fa-solid fa-file-arrow-down"></i>
                        <span v-if="spent_time_counter <= 0">Сохранить заказ в чат</span>
                        <span v-else>Осталось ждать {{ spent_time_counter }} сек.</span>
                    </button>

                </div>

            </transition>
        </div>

        <!-- ========================================== -->
        <!-- НИЖНЯЯ КНОПКА -->
        <!-- ========================================== -->
        <div class="bottom-bar">
            <button class="menu-btn" @click="goToCatalog">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Вернуться к меню</span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПРОФИЛЬ СОЗДАТЕЛЯ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showCreatorModal" class="modal-overlay" @click.self="showCreatorModal = false">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>Профиль создателя</h3>
                        <button class="modal-close" @click="showCreatorModal = false">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ProfileCard
                            v-if="table.creator"
                            :can-edit="false"
                            :data="table.creator"
                        />
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПРОФИЛЬ ОФИЦИАНТА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showWaiterModal" class="modal-overlay" @click.self="showWaiterModal = false">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>Профиль официанта</h3>
                        <button class="modal-close" @click="showWaiterModal = false">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ProfileCard
                            v-if="table.officiant"
                            :can-edit="false"
                            :data="table.officiant"
                        />
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<script>

import CartProductList from "@/MobileClient/Components/Cart/CartProductList.vue";
import ProfileCard from "@/MobileClient/Components/Shop/ProfileCard.vue";
import BookingDropdown from "@/MobileClient/Components/Shop/Booking/BookingDropdown.vue";

// Вспомогательный компонент InfoRow
const InfoRow = {
    name: 'InfoRow',
    props: {
        icon: String,
        label: String,
        value: String,
        clickable: Boolean,
    },
    emits: ['click'],
    template: `
        <div class="info-row" :class="{ 'is-clickable': clickable }" @click="clickable && $emit('click')">
            <div class="info-icon"><i :class="icon"></i></div>
            <div class="info-content">
                <div class="info-label">{{ label }}</div>
                <div class="info-value">{{ value }}</div>
            </div>
            <i v-if="clickable" class="fa-solid fa-arrow-up-right-from-square info-link"></i>
        </div>
    `,
};

export default {
    name: "TableManager",

    components: {
        CartProductList,
        ProfileCard,
        BookingDropdown,
        InfoRow,
    },

    data() {
        return {
            tab: 0,
            selectedTable: null,
            spent_time_counter: 0,
            loaded_settings: true,
            sent_to_waiter: false,
            showCreatorModal: false,
            showWaiterModal: false,
            settings: {},
            orderForm: {
                is_self: false,
                action_prize: null,
                use_cashback: false,
                client: {
                    name: null,
                    phone: null,
                    have_birthday: false,
                },
                promo: {
                    discount_in_percent: false,
                    discount: 0,
                    activate_price: 0,
                    code: null,
                },
            },
            table: {
                id: null,
                bot_id: null,
                number: null,
                creator: null,
                creator_id: null,
                officiant: null,
                officiant_id: null,
                closed_at: null,
                additional_services: null,
                config: null,
                clients: [],
            },
            clients: [],
            basket: [],
            self_summary_price: 0,
            self_summary_count: 0,
            summary_price: 0,
            summary_count: 0,
            timerInterval: null,
        };
    },

    computed: {


        self() {
            return this.getSelf || window.self;
        },

        bot() {
            return window.currentBot || {};
        },

        servicePrice() {
            return (this.table.additional_services || []).reduce((sum, item) => sum + (item.price || 0), 0);
        },

        fullTablePrice() {
            return this.summary_price + this.servicePrice;
        },

        cashbackLimit() {
            const maxUserCashback = this.getSelf?.cashBack?.amount || 0;
            const summaryPrice = this.cartTotalPrice || 0;
            const botCashbackPercent = this.bot.max_cashback_use_percent || 0;
            const cashBackAmount = summaryPrice * (botCashbackPercent / 100);
            return Math.min(cashBackAmount, maxUserCashback);
        },

        cartTotalCount() {
            return this.$store.getters.cartTotalCount || 0;
        },

        cartTotalPrice() {
            return this.$store.getters.cartTotalPrice || 0;
        },
    },

    mounted() {
        this.loadCurrentTableData();
        this.loadBasketData();
        this.loadShopModuleData();

        const storedCounter = localStorage.getItem("cashman_self_table_counter");
        if (storedCounter !== null) {
            this.startTimer(storedCounter);
        }
    },

    beforeUnmount() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
    },

    methods: {
        // ==========================================
        // НАВИГАЦИЯ
        // ==========================================
        changeTab(tab) {
            this.tab = tab;
            if (tab === 1 || tab === 2) {
                this.loadCurrentTableData();
            }
        },

        goToCatalog() {
            this.$router.push({ name: 'TableMenuV2' });
        },

        // ==========================================
        // СТОЛИК
        // ==========================================
        selectATable(item) {
            this.selectedTable = null;
            this.$nextTick(() => {
                this.selectedTable = item;
                this.loadCurrentTableData();
                this.loadBasketData();
            });
        },

        openCreatorProfile() {
            this.showCreatorModal = true;
        },

        openWaiterProfile() {
            this.showWaiterModal = true;
        },

        // ==========================================
        // ЗАКАЗЫ
        // ==========================================
        async changeOrderStatus() {
            try {
                await this.$store.dispatch("acceptTableOder", {
                    dataObject: {
                        table_id: this.selectedTable?.id,
                        type: 0,
                    },
                });

                this.$notify?.({
                    title: 'Успешно',
                    text: 'Статус заказа изменён',
                    type: 'success',
                });

                this.tab = 2;
                this.loadBasketData();
                this.startTablePay();
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось изменить статус заказа',
                    type: 'error',
                });
            }
        },

        async startTablePay() {
            this.startTimer(10);

            this.$notify?.({
                title: 'Оплата',
                text: 'Формируем платёжную ссылку...',
                type: 'info',
            });

            const data = new FormData();
            Object.keys(this.orderForm).forEach(key => {
                const item = this.orderForm[key] || '';
                if (typeof item === 'object') {
                    data.append(key, JSON.stringify(item));
                } else {
                    data.append(key, item);
                }
            });
            data.append("table_id", this.table.id);

            try {
                const resp = await this.$store.dispatch("startTablePay", data);
                window.open(resp.url, '_blank');
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Заказ отправлен в чат',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить заказ',
                    type: 'error',
                });
            }
        },

        async makeOrder() {
            this.sent_to_waiter = true;
            this.startTimer(10);

            try {
                await this.$store.dispatch("requestApproveTable", {
                    dataObject: { table_id: this.table.id },
                });
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Заказ передан официанту',
                    type: 'success',
                });
            } catch (error) {
                this.sent_to_waiter = false;
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить заказ',
                    type: 'error',
                });
            }
        },

        selectPrize(item) {
            this.orderForm.action_prize = item;
        },

        // ==========================================
        // ОФИЦИАНТ
        // ==========================================
        async callWaiter(needPayment = false) {
            this.startTimer(10);

            this.$notify?.({
                title: 'Официант',
                text: 'Отправляем запрос...',
                type: 'info',
            });

            try {
                await this.$store.dispatch("requestWaiterComing", {
                    dataObject: {
                        table_id: this.table.id,
                        need_payment: needPayment,
                    },
                });
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Официант уведомлён и скоро подойдёт',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось уведомить официанта',
                    type: 'error',
                });
            }
        },

        async sendOrderToMyChat() {
            this.startTimer(10);

            try {
                await this.$store.dispatch("sendOrderToMyChat", {
                    dataObject: { table_id: this.table.id },
                });
                this.$notify?.({
                    title: 'Успешно',
                    text: 'Заказ отправлен в чат',
                    type: 'success',
                });
            } catch (error) {
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось отправить заказ',
                    type: 'error',
                });
            }
        },

        // ==========================================
        // ЗАГРУЗКА ДАННЫХ
        // ==========================================
        async loadShopModuleData() {
            this.loaded_settings = false;

            try {
                const resp = await this.$store.dispatch("loadShopModuleData");
                await this.$nextTick();

                if (resp) {
                    Object.keys(resp).forEach(key => {
                        this.settings[key] = resp[key];
                    });
                }

                this.fillForm();
                this.loaded_settings = true;
            } catch (error) {
                console.error('Ошибка загрузки настроек:', error);
                this.loaded_settings = true;
            }
        },

        async loadBasketData() {
            try {
                await this.$store.dispatch("loadProductsInBasket");
            } catch (error) {
                console.error('Ошибка загрузки корзины:', error);
            }
        },

        async loadApprovedSelfTableBasket() {
            try {
                await this.$store.dispatch("loadApprovedSelfTableBasket");
            } catch (error) {
                console.error('Ошибка:', error);
            }
        },

        async loadCurrentTableData() {
            try {
                const resp = await this.$store.dispatch("loadCurrentTableData");
                const data = resp.table;

                this.table.id = data.id || null;
                this.table.bot_id = data.bot_id || null;
                this.table.number = data.number || null;
                this.table.creator = data.creator || null;
                this.table.creator_id = data.creator_id || null;
                this.table.officiant = data.officiant || null;
                this.table.officiant_id = data.officiant_id || null;
                this.table.closed_at = data.closed_at || null;
                this.table.additional_services = data.additional_services || null;
                this.table.config = data.config || null;
                this.table.clients = data.clients || [];

                this.summary_price = resp.summary_price || 0;
                this.summary_count = resp.summary_count || 0;
                this.clients = resp.clients || [];
                this.basket = resp.basket || [];

                const selfBasket = this.basket.find(item => item.id === this.self?.id);
                this.self_summary_price = selfBasket?.summary_price || 0;
                this.self_summary_count = selfBasket?.summary_count || 0;
            } catch (error) {
                console.error('Ошибка загрузки данных столика:', error);
            }
        },

        // ==========================================
        // УТИЛИТЫ
        // ==========================================
        fillForm() {
            this.orderForm.client.name = this.getSelf?.name || this.getSelf?.fio_from_telegram || null;
            this.orderForm.client.phone = this.getSelf?.phone || null;
        },

        startTimer(time) {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }

            this.spent_time_counter = Math.min(parseInt(time) || 10, 10);

            this.timerInterval = setInterval(() => {
                if (this.spent_time_counter > 0) {
                    this.spent_time_counter--;
                } else {
                    clearInterval(this.timerInterval);
                    this.timerInterval = null;
                    this.spent_time_counter = 0;
                }
                localStorage.setItem("cashman_self_table_counter", this.spent_time_counter);
            }, 1000);
        },
    },
};
</script>

<style lang="scss" scoped>
@use 'sass:color';

// ==========================================
// ПЕРЕМЕННЫЕ
// ==========================================
$primary: #3b82f6;
$primary-dark: #2563eb;
$primary-light: #60a5fa;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$gold: #f59e0b;
$info: #06b6d4;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.table-manager {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 100px;
}

// ==========================================
// БРОНЬ
// ==========================================
.booking-section {
    padding: 0px;
    background: $card-bg;
    border-bottom: 1px solid $border;
}

// ==========================================
// ТАБЫ
// ==========================================
.tabs-wrapper {
    background: $card-bg;
    border-bottom: 1px solid $border;
    padding: 12px 16px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.tabs {
    display: flex;
    gap: 6px;
    background: $bg;
    padding: 4px;
    border-radius: 12px;
}

.tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 8px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;

    i {
        font-size: 0.95rem;
    }

    &:hover {
        color: $primary;
        background: rgba($primary, 0.05);
    }

    &.is-active {
        background: $card-bg;
        color: $primary;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
}

.tab-badge {
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: $primary;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

// ==========================================
// КОНТЕНТ
// ==========================================
.tab-content-wrapper {
    padding: 16px;
}

.tab-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.tab-fade-enter-active,
.tab-fade-leave-active {
    transition: all 0.3s ease;
}

.tab-fade-enter-from {
    opacity: 0;
    transform: translateX(-20px);
}

.tab-fade-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

// ==========================================
// КАРТОЧКА БРОНИ
// ==========================================
.booking-card {
    background: linear-gradient(135deg, rgba($primary, 0.05) 0%, rgba($primary-light, 0.02) 100%);
    border: 1px solid rgba($primary, 0.2);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
}

.booking-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid rgba($primary, 0.15);
}

.booking-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.booking-info {
    flex: 1;

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 2px 0;
        color: $text;
    }

    p {
        font-size: 0.85rem;
        color: $text-muted;
        margin: 0;
    }
}

.booking-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: $text;

    i {
        width: 20px;
        color: $primary;
        text-align: center;
    }
}

// ==========================================
// УВЕДОМЛЕНИЯ
// ==========================================
.closed-notice {
    display: flex;
    gap: 12px;
    padding: 14px;
    background: rgba($danger, 0.08);
    border: 1px solid rgba($danger, 0.2);
    border-radius: 12px;
    margin-bottom: 16px;

    i {
        color: $danger;
        font-size: 1.2rem;
        flex-shrink: 0;
        margin-top: 2px;
    }

    strong {
        display: block;
        color: $danger;
        margin-bottom: 2px;
    }

    p {
        font-size: 0.85rem;
        color: $text-muted;
        margin: 0;
    }
}

.warning-notice,
.info-notice,
.birthday-notice {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 0.9rem;
    margin-bottom: 12px;
}

.warning-notice {
    background: rgba($warning, 0.08);
    border: 1px solid rgba($warning, 0.2);
    color: color.adjust($warning, $lightness: -15%);

    i { color: $warning; }
}

.info-notice {
    background: rgba($info, 0.08);
    border: 1px solid rgba($info, 0.2);
    color:  color.adjust($info, $lightness: -15%);

    i { color: $info; }
}

.birthday-notice {
    background: rgba($gold, 0.08);
    border: 1px solid rgba($gold, 0.2);
    color: color.adjust($gold, $lightness: -20%);

    i { color: $gold; }
}

// ==========================================
// ИНФО-КАРТОЧКА
// ==========================================
.info-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid $border;

    h3 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
        color: $text;
    }
}

.header-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
    flex-shrink: 0;

    &.primary { background: linear-gradient(135deg, $primary 0%, $primary-light 100%); }
}

.info-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: $bg;
    border-radius: 10px;
    transition: all 0.2s;

    &.is-clickable {
        cursor: pointer;

        &:hover {
            background: rgba($primary, 0.05);
        }
    }
}

.info-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
    min-width: 0;
}

.info-label {
    font-size: 0.75rem;
    color: $text-muted;
    margin-bottom: 2px;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: $text;
    word-break: break-word;
}

.info-link {
    color: $primary;
    font-size: 0.8rem;
    flex-shrink: 0;
}

// Итоги
.totals-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid $border;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.9rem;
    color: $text-muted;

    strong {
        color: $text;
        font-weight: 600;

        small {
            font-weight: 500;
            color: $text-muted;
            font-size: 0.8rem;
        }
    }

    &.grand {
        padding-top: 12px;
        margin-top: 4px;
        border-top: 1px dashed $border;
        font-size: 1rem;

        strong {
            color: $primary;
            font-size: 1.1rem;
        }
    }
}

// ==========================================
// КНОПКИ ДЕЙСТВИЙ
// ==========================================
.action-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 12px;
    border: none;

    i {
        font-size: 1.1rem;
    }

    &.primary {
        background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
        color: white;
        box-shadow: 0 4px 12px rgba($primary, 0.25);

        &:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba($primary, 0.35);
        }
    }

    &.outline {
        background: $card-bg;
        border: 1px solid $border;
        color: $primary;

        &:hover:not(:disabled) {
            border-color: $primary;
            background: rgba($primary, 0.05);
        }
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

// ==========================================
// БОНУСЫ
// ==========================================
.bonuses-section {
    margin-bottom: 16px;
}

.section-divider {
    text-align: center;
    margin: 20px 0 14px;
    position: relative;

    &::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: $border;
    }

    span {
        position: relative;
        background: $bg;
        padding: 0 16px;
        font-weight: 700;
        font-size: 0.95rem;
        color: $text;
    }

    small {
        display: block;
        font-size: 0.75rem;
        color: $text-muted;
        font-weight: 500;
        margin-top: 2px;
    }
}

.cashback-toggle {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
    }

    &.is-active {
        border-color: $primary;
        background: rgba($primary, 0.05);
    }
}

.cashback-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, $gold 0%, #fbbf24 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.cashback-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;

    span {
        font-weight: 600;
        color: $text;
    }

    strong {
        color: $primary;
        font-size: 1.1rem;
    }
}

.cashback-switch,
.birthday-switch {
    width: 44px;
    height: 26px;
    border-radius: 13px;
    background: #d1d5db;
    position: relative;
    transition: background 0.2s;
    flex-shrink: 0;

    &.is-on {
        background: $primary;
    }
}

.switch-knob {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s;

    .is-on & {
        transform: translateX(18px);
    }
}

// ==========================================
// ФОРМА ОПЛАТЫ
// ==========================================
.payment-form {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 14px;

    label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        color: $text;
        margin-bottom: 6px;

        i {
            color: $primary;
            font-size: 0.8rem;
        }
    }
}

.form-input {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.95rem;
    background: $card-bg;
    color: $text;
    transition: all 0.2s;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }

    &::placeholder {
        color: #9ca3af;
    }
}

.birthday-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: $bg;
    border-radius: 10px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: rgba($gold, 0.05);
    }
}

.birthday-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba($gold, 0.1);
    color: $gold;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.birthday-info {
    flex: 1;

    strong {
        font-size: 0.9rem;
        color: $text;
    }
}

.payment-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 12px;
}

.payment-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: $card-bg;
    border: 2px solid $border;
    border-radius: 12px;
    color: $primary;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;

    i {
        font-size: 1.1rem;
    }

    span {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;

        small {
            font-size: 0.8rem;
            font-weight: 700;
            color: $primary;
        }
    }

    &:hover:not(:disabled) {
        border-color: $primary;
        background: rgba($primary, 0.05);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.cash-notice {
    text-align: center;
    font-size: 0.85rem;
    color: $text-muted;
    padding: 10px;

    .link-btn {
        background: none;
        border: none;
        color: $primary;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
        font-size: inherit;

        &:hover {
            text-decoration: underline;
        }
    }
}

// ==========================================
// КОРЗИНА
// ==========================================
.cart-header {
    margin-bottom: 16px;

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0;
    }
}

.loading-state {
    text-align: center;
    padding: 40px 20px;
    color: $text-muted;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid $border;
    border-top-color: $primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// ЗАКАЗЫ
// ==========================================
.order-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 14px;
    margin-bottom: 12px;
    overflow: hidden;
}

.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: $bg;
    border-bottom: 1px solid $border;
}

.client-name {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: $text;

    i {
        color: $primary;
    }
}

.self-badge {
    padding: 2px 8px;
    background: linear-gradient(135deg, $gold 0%, #fbbf24 100%);
    color: white;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 4px;
}

.order-total {
    font-size: 1.1rem;
    font-weight: 800;
    color: $primary;
}

.order-items {
    padding: 8px 0;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    border-bottom: 1px solid rgba($border, 0.5);

    &:last-child {
        border-bottom: none;
    }
}

.item-status {
    width: 24px;
    flex-shrink: 0;

    i {
        font-size: 0.9rem;

        &.approved {
            color: $success;
        }

        &.pending {
            color: #d1d5db;
        }
    }
}

.item-info {
    flex: 1;
    min-width: 0;
}

.item-name {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    color: $text;
    margin-bottom: 2px;
}

.item-details {
    font-size: 0.8rem;
    color: $text-muted;
}

.item-price {
    font-weight: 700;
    font-size: 0.9rem;
    color: $text;
    flex-shrink: 0;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;

    .empty-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba($primary, 0.1);
        color: $primary;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin: 0 auto 12px;
    }

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: $text;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0;
    }

    &.small {
        padding: 30px 20px;

        i {
            font-size: 2rem;
            color: $text-muted;
            margin-bottom: 8px;
            display: block;
        }
    }
}

// ==========================================
// НИЖНЯЯ ПАНЕЛЬ
// ==========================================
.bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px 16px;
    background: linear-gradient(to top, $card-bg 70%, transparent);
    z-index: 100;
}

.menu-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba($primary, 0.3);
    transition: all 0.2s;

    i {
        font-size: 1.1rem;
    }

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba($primary, 0.4);
    }
}

// ==========================================
// МОДАЛКИ
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: $card-bg;
    border-radius: 20px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid $border;

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
    }
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $danger;
        color: white;
    }
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 640px) {
    .booking-card,
    .info-card,
    .payment-form {
        padding: 16px;
    }

    .tab {
        font-size: 0.8rem;
        padding: 8px 6px;
    }

    .payment-btn span small {
        font-size: 0.75rem;
    }
}
</style>
