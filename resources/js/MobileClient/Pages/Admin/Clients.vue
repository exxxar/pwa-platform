<template>
    <div class="admin-clients">

        <!-- ========================================== -->
        <!-- ШАПКА С НАВИГАЦИЕЙ -->
        <!-- ========================================== -->
        <div class="admin-header">
            <button class="back-btn" @click="goBack">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="header-info">
                <h1 class="header-title">Клиенты</h1>
                <p class="header-subtitle">Управление пользователями</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ТАБЫ -->
        <!-- ========================================== -->
        <div class="tabs-wrapper">
            <div class="tabs">
                <button
                    class="tab"
                    :class="{ 'is-active': tab === 0 }"
                    @click="switchTab(0)"
                >
                    <i class="fa-solid fa-users"></i>
                    <span>Поиск</span>
                </button>
                <button
                    class="tab"
                    :class="{ 'is-active': tab === 1 }"
                    :disabled="!selected_bot_user"
                    @click="switchTab(1)"
                >
                    <i class="fa-solid fa-user"></i>
                    <span>Профиль</span>
                    <span v-if="selected_bot_user" class="tab-badge">
                        <i class="fa-solid fa-check"></i>
                    </span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОНТЕНТ -->
        <!-- ========================================== -->
        <div class="admin-content">

            <!-- Вкладка: Поиск -->
            <transition name="tab-fade" mode="out-in">
                <div v-if="tab === 0" key="search" class="tab-content">
                    <UserSearchForm
                        :selected-bot-user="selected_bot_user"
                        @cancel="cancelUserSelected"
                        @select="selectUser"
                    />
                </div>

                <!-- Вкладка: Профиль -->
                <div v-else-if="tab === 1" key="profile" class="tab-content">

                    <!-- Индикатор загрузки -->
                    <div v-if="isLoading" class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Загружаем данные пользователя...</p>
                    </div>

                    <!-- Профиль пользователя -->
                    <UserProfileCard
                        v-else-if="loadedUser && selected_bot_user"
                        v-model="selected_bot_user"
                    />

                    <!-- Пустое состояние -->
                    <div v-else class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-solid fa-user-slash"></i>
                        </div>
                        <h3>Пользователь не выбран</h3>
                        <p>Вернитесь на вкладку поиска и выберите клиента</p>
                        <button class="btn-primary" @click="switchTab(0)">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Перейти к поиску
                        </button>
                    </div>

                </div>
            </transition>

        </div>

    </div>
</template>

<script>
import UserSearchForm from "@/MobileClient/Components/Admin/Clients/Users.vue";
import UserProfileCard from "@/MobileClient/Components/Admin/Clients/UserProfileCard.vue";

export default {
    name: "AdminClients",

    components: {
        UserSearchForm,
        UserProfileCard,
    },

    data() {
        return {
            tab: 0,
            isLoading: false,
            loadedUser: false,
            request_telegram_chat_id: null,
            selected_bot_user: null,
            needClose: false,
        };
    },

    computed: {
        currentBot() {
            return window.currentBot || {};
        },
    },

    mounted() {
        // Если компонент открыт с параметрами из URL — загружаем пользователя
        this.loadUserFromUrl();
    },

    methods: {
        /**
         * Загрузка данных пользователя из URL-параметров
         */
        loadUserFromUrl() {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const userParam = urlParams.get('user');
                this.needClose = urlParams.has('hide_menu');

                if (userParam) {
                    const user = JSON.parse(userParam);
                    if (user) {
                        this.request_telegram_chat_id = user;
                        this.loadReceiverUserData();
                    }
                }
            } catch (error) {
                console.error('Ошибка парсинга URL-параметров:', error);
            }
        },

        /**
         * Переключение между вкладками
         */
        switchTab(tabIndex) {
            if (tabIndex === 1 && !this.selected_bot_user) return;
            this.tab = tabIndex;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /**
         * Возврат назад
         */
        goBack() {
            if (this.needClose) {
                // Если нужно закрыть приложение (например, Telegram)
                if (window.Telegram?.WebApp) {
                    window.Telegram.WebApp.close();
                } else {
                    window.close();
                }
            } else {
                this.$router.back();
            }
        },

        /**
         * Сброс выбранного пользователя
         */
        cancelUserSelected() {
            this.request_telegram_chat_id = null;
            this.selected_bot_user = null;
            this.loadedUser = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /**
         * Обновление информации о пользователе
         */
        updateUserInfo() {
            this.selected_bot_user = null;
            this.request_telegram_chat_id = null;
        },

        /**
         * Выбор пользователя из списка
         */
        selectUser(user) {
            this.request_telegram_chat_id = user.telegram_chat_id;
            this.loadReceiverUserData();
        },

        /**
         * Загрузка данных пользователя с сервера
         */
        async loadReceiverUserData() {
            this.isLoading = true;
            this.loadedUser = false;
            this.selected_bot_user = null;

            try {
                const resp = await this.$store.dispatch("loadReceiverUserData", {
                    dataObject: {
                        user_telegram_chat_id: this.request_telegram_chat_id
                    },
                });

                this.selected_bot_user = resp.data;
                this.request_telegram_chat_id = this.selected_bot_user.telegram_chat_id;

                await this.$nextTick();

                this.isLoading = false;
                this.loadedUser = true;
                this.tab = 1;
                window.scrollTo({ top: 0, behavior: 'smooth' });

            } catch (error) {
                console.error('Ошибка загрузки данных пользователя:', error);
                this.isLoading = false;
                this.loadedUser = false;

                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить данные пользователя',
                    type: 'error',
                });
            }
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
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.admin-clients {
    min-height: 100vh;
    background: $bg;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

// ==========================================
// ШАПКА
// ==========================================
.admin-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    background: $card-bg;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
}

.back-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: $bg;
    border: 1px solid $border;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover {
        background: $primary;
        border-color: $primary;
        color: white;
    }
}

.header-info {
    flex: 1;
    min-width: 0;
}

.header-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 2px 0;
    color: $text;
}

.header-subtitle {
    font-size: 0.8rem;
    color: $text-muted;
    margin: 0;
}

// ==========================================
// ТАБЫ
// ==========================================
.tabs-wrapper {
    background: $card-bg;
    border-bottom: 1px solid $border;
    padding: 12px 16px;
    position: sticky;
    top: 73px;
    z-index: 99;
}

.tabs {
    display: flex;
    gap: 8px;
    background: $bg;
    padding: 4px;
    border-radius: 12px;
}

.tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;

    i {
        font-size: 1rem;
    }

    &:hover:not(:disabled) {
        color: $primary;
        background: rgba($primary, 0.05);
    }

    &.is-active {
        background: $card-bg;
        color: $primary;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

.tab-badge {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: $success;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    margin-left: 4px;
    animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes popIn {
    0% { transform: scale(0); }
    100% { transform: scale(1); }
}

// ==========================================
// КОНТЕНТ
// ==========================================
.admin-content {
    padding: 16px;
    max-width: 800px;
    margin: 0 auto;
}

.tab-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

// Переходы между табами
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
// СОСТОЯНИЯ
// ==========================================

// Загрузка
.loading-state {
    text-align: center;
    padding: 60px 20px;
    color: $text-muted;
}

.loading-spinner {
    width: 48px;
    height: 48px;
    border: 3px solid $border;
    border-top-color: $primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-state p {
    font-size: 0.95rem;
    margin: 0;
}

// Пустое состояние
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
}

.empty-state h3 {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: $text;
}

.empty-state p {
    font-size: 0.9rem;
    color: $text-muted;
    margin: 0 0 20px 0;
    line-height: 1.5;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        background: $primary-dark;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba($primary, 0.3);
    }
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 640px) {
    .admin-header {
        padding: 12px 16px;
    }

    .header-title {
        font-size: 1.1rem;
    }

    .tabs-wrapper {
        padding: 10px 12px;
    }

    .tab {
        padding: 8px 10px;
        font-size: 0.85rem;
        gap: 6px;
    }

    .admin-content {
        padding: 12px;
    }
}
</style>
