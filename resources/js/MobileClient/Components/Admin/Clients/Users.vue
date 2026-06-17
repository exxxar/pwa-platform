<template>
    <div class="users-search">

        <!-- ========================================== -->
        <!-- ПОИСКОВАЯ ФОРМА -->
        <!-- ========================================== -->
        <div class="search-card">
            <div class="search-header">
                <div class="search-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div class="search-info">
                    <h3>Поиск пользователей</h3>
                    <p>Введите номер телефона или Ф.И.О.</p>
                </div>
            </div>

            <form @submit.prevent="loadUsers(0)" class="search-form">
                <div class="input-wrapper">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input
                        type="text"
                        v-model="search"
                        class="search-input"
                        placeholder="Телефон или имя пользователя"
                    >
                </div>

                <!-- Фильтры в виде чипов -->
                <div class="filters-chips">
                    <button
                        type="button"
                        class="filter-chip"
                        :class="{ 'is-active': need_admins }"
                        @click="need_admins = !need_admins"
                    >
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Администраторы</span>
                    </button>
                    <button
                        type="button"
                        class="filter-chip"
                        :class="{ 'is-active': need_with_phone }"
                        @click="need_with_phone = !need_with_phone"
                    >
                        <i class="fa-solid fa-phone"></i>
                        <span>С телефоном</span>
                    </button>
                    <button
                        type="button"
                        class="filter-chip"
                        :class="{ 'is-active': need_without_phone }"
                        @click="need_without_phone = !need_without_phone"
                    >
                        <i class="fa-solid fa-phone-slash"></i>
                        <span>Без телефона</span>
                    </button>
                    <button
                        type="button"
                        class="filter-chip"
                        :class="{ 'is-active': need_deliveryman }"
                        @click="need_deliveryman = !need_deliveryman"
                    >
                        <i class="fa-solid fa-motorcycle"></i>
                        <span>Доставщики</span>
                    </button>
                    <button
                        type="button"
                        class="filter-chip vip"
                        :class="{ 'is-active': need_vip }"
                        @click="need_vip = !need_vip"
                    >
                        <i class="fa-solid fa-crown"></i>
                        <span>VIP</span>
                    </button>
                    <button
                        type="button"
                        class="filter-chip"
                        :class="{ 'is-active': need_not_vip }"
                        @click="need_not_vip = !need_not_vip"
                    >
                        <i class="fa-solid fa-user"></i>
                        <span>Не VIP</span>
                    </button>
                </div>

                <button type="submit" class="search-btn" :disabled="loading">
                    <span v-if="loading" class="btn-spinner"></span>
                    <template v-else>
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Искать</span>
                    </template>
                </button>
            </form>
        </div>

        <!-- ========================================== -->
        <!-- РЕЗУЛЬТАТЫ ПОИСКА -->
        <!-- ========================================== -->
        <div v-if="users && !loading" class="results-section">

            <!-- Счётчик результатов -->
            <div class="results-header">
                <div class="results-count">
                    Найдено: <strong>{{ users_paginate_object?.meta?.total || 0 }}</strong> пользователей
                </div>
            </div>

            <!-- Список пользователей -->
            <div v-if="users.length > 0" class="users-list">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="user-card"
                    :class="{ 'is-selected': user.id === selectedBotUser?.id }"
                    @click="selectUser(user)"
                >
                    <div class="user-avatar">
                        <i class="fa-solid" :class="getUserIcon(user)"></i>
                    </div>

                    <div class="user-info">
                        <div class="user-name">
                            {{ truncateName(user.name || user.fio_from_telegram || 'Не указано') }}
                        </div>
                        <div class="user-phone">
                            <i class="fa-solid fa-phone"></i>
                            {{ user.phone || 'Телефон не указан' }}
                        </div>
                    </div>

                    <div class="user-badges">
                        <span v-if="user.is_admin" class="badge admin">
                            <i class="fa-solid fa-user-shield"></i>
                            Админ
                        </span>
                        <span v-if="user.is_vip" class="badge vip">
                            <i class="fa-solid fa-crown"></i>
                            VIP
                        </span>
                        <span v-if="user.is_deliveryman" class="badge delivery">
                            <i class="fa-solid fa-motorcycle"></i>
                            Курьер
                        </span>
                    </div>

                    <div class="user-arrow">
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <h4>Пользователи не найдены</h4>
                <p>Попробуйте изменить параметры поиска</p>
            </div>

            <!-- Пагинация -->
            <Pagination
                v-if="users_paginate_object && users_paginate_object.last_page > 1"
                :simple="true"
                class="mt-4"
                @pagination_page="nextUsers"
                :pagination="users_paginate_object"
            />
        </div>

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Загружаем пользователей...</p>
        </div>

        <!-- ========================================== -->
        <!-- ДЕЙСТВИЯ -->
        <!-- ========================================== -->
        <div class="actions-section">
            <button class="action-btn" @click="downloadBotUsers" :disabled="isDownloading">
                <i class="fa-solid fa-file-excel"></i>
                <span>Скачать список пользователей</span>
            </button>
            <button class="action-btn" @click="downloadCashBackHistory" :disabled="isDownloading">
                <i class="fa-solid fa-file-invoice"></i>
                <span>Скачать историю начислений</span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- ЛЕГЕНДА -->
        <!-- ========================================== -->
        <div class="legend-card">
            <div class="legend-item">
                <div class="legend-icon admin">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <span>Администратор системы</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon user">
                    <i class="fa-solid fa-user"></i>
                </div>
                <span>Обычный пользователь</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon vip">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <span>VIP-клиент</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon delivery">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <span>Курьер</span>
            </div>
        </div>

    </div>
</template>

<script>
import Pagination from "@/MobileClient/Components/Pagination.vue";

export default {
    name: "UsersSearch",

    components: {
        Pagination,
    },

    props: {
        selectedBotUser: {
            type: Object,
            default: null,
        },
    },

    emits: ['select', 'cancel'],

    data() {
        return {
            loading: false,
            isDownloading: false,
            users: null,
            search: '',
            need_admins: false,
            need_vip: false,
            need_not_vip: false,
            need_with_phone: false,
            need_without_phone: false,
            need_deliveryman: false,
            users_paginate_object: null,
        };
    },

    computed: {
        getUsers() {
            return this.$store.getters.getUsers || [];
        },
        getUsersPaginateObject() {
            return this.$store.getters.getUsersPaginateObject || null;
        },
    },

    mounted() {
        this.loadUsers(0);
    },

    methods: {
        /**
         * Загрузка списка пользователей
         */
        async loadUsers(page = 0) {
            this.loading = true;

            try {
                await this.$store.dispatch("loadUsers", {
                    dataObject: {
                        search: this.search,
                        need_admins: this.need_admins,
                        need_vip: this.need_vip,
                        need_not_vip: this.need_not_vip,
                        need_deliveryman: this.need_deliveryman,
                        need_with_phone: this.need_with_phone,
                        need_without_phone: this.need_without_phone,
                    },
                    page: page,
                });

                this.users = this.getUsers;
                this.users_paginate_object = this.getUsersPaginateObject;
                this.$emit("cancel");
            } catch (error) {
                console.error('Ошибка загрузки пользователей:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить список пользователей',
                    type: 'error',
                });
            } finally {
                this.loading = false;
            }
        },

        /**
         * Переход на следующую страницу
         */
        nextUsers(index) {
            this.loadUsers(index);
        },

        /**
         * Выбор пользователя
         */
        selectUser(user) {
            this.$emit("select", user);
        },

        /**
         * Обрезка длинного имени
         */
        truncateName(name) {
            if (!name) return 'Не указано';
            return name.length > 26 ? name.substring(0, 26) + '...' : name;
        },

        /**
         * Получение иконки для пользователя
         */
        getUserIcon(user) {
            if (user.is_admin) return 'fa-user-shield';
            if (user.is_vip) return 'fa-crown';
            if (user.is_deliveryman) return 'fa-motorcycle';
            return 'fa-user';
        },

        /**
         * Скачивание списка пользователей
         */
        async downloadBotUsers() {
            this.isDownloading = true;

            this.$notify?.({
                title: 'Формирование документа',
                text: 'Началось формирование списка пользователей...',
                type: 'info',
            });

            try {
                await this.$store.dispatch("downloadBotUsers");

                this.$notify?.({
                    title: 'Готово!',
                    text: 'Документ успешно сформирован',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка скачивания:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сформировать документ',
                    type: 'error',
                });
            } finally {
                this.isDownloading = false;
            }
        },

        /**
         * Скачивание истории начислений
         */
        async downloadCashBackHistory() {
            this.isDownloading = true;

            this.$notify?.({
                title: 'Формирование документа',
                text: 'Началось формирование истории начислений...',
                type: 'info',
            });

            try {
                await this.$store.dispatch("downloadCashBackHistory");

                this.$notify?.({
                    title: 'Готово!',
                    text: 'Документ успешно сформирован',
                    type: 'success',
                });
            } catch (error) {
                console.error('Ошибка скачивания:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось сформировать документ',
                    type: 'error',
                });
            } finally {
                this.isDownloading = false;
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
$purple: #8b5cf6;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

// ==========================================
// БАЗА
// ==========================================
.users-search {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

// ==========================================
// ПОИСКОВАЯ КАРТОЧКА
// ==========================================
.search-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.search-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}

.search-icon {
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

.search-info {
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

.search-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: $text-muted;
    font-size: 0.9rem;
}

.search-input {
    width: 100%;
    padding: 12px 14px 12px 42px;
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

// ==========================================
// ФИЛЬТРЫ-ЧИПЫ
// ==========================================
.filters-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.filter-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 20px;
    color: $text-muted;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;

    i {
        font-size: 0.8rem;
    }

    &:hover {
        border-color: $primary;
        color: $primary;
        background: rgba($primary, 0.05);
    }

    &.is-active {
        background: $primary;
        border-color: $primary;
        color: white;

        &:hover {
            background: $primary-dark;
            color: white;
        }
    }

    &.vip.is-active {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        border-color: #f59e0b;
    }
}

// ==========================================
// КНОПКА ПОИСКА
// ==========================================
.search-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba($primary, 0.3);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.btn-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

// ==========================================
// РЕЗУЛЬТАТЫ
// ==========================================
.results-section {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.results-header {
    margin-bottom: 16px;
}

.results-count {
    font-size: 0.9rem;
    color: $text-muted;

    strong {
        color: $primary;
        font-weight: 700;
    }
}

.users-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        background: rgba($primary, 0.02);
        transform: translateX(4px);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.08);
        box-shadow: 0 4px 12px rgba($primary, 0.15);
    }
}

.user-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, $primary 0%, $primary-light 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: $text;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-phone {
    font-size: 0.8rem;
    color: $text-muted;
    display: flex;
    align-items: center;
    gap: 4px;

    i {
        font-size: 0.7rem;
    }
}

.user-badges {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}

.badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;

    i {
        font-size: 0.65rem;
    }

    &.admin {
        background: rgba($danger, 0.1);
        color: $danger;
    }

    &.vip {
        background: linear-gradient(135deg, rgba(#fbbf24, 0.15) 0%, rgba(#f59e0b, 0.15) 100%);
        color: #b45309;
    }

    &.delivery {
        background: rgba($success, 0.1);
        color: $success;
    }
}

.user-arrow {
    color: $text-muted;
    font-size: 0.8rem;
    flex-shrink: 0;
}

// ==========================================
// СОСТОЯНИЯ
// ==========================================
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

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

.empty-state h4 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 6px 0;
    color: $text;
}

.empty-state p {
    font-size: 0.9rem;
    color: $text-muted;
    margin: 0;
}

.loading-state {
    text-align: center;
    padding: 60px 20px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
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

.loading-state p {
    font-size: 0.95rem;
    color: $text-muted;
    margin: 0;
}

// ==========================================
// ДЕЙСТВИЯ
// ==========================================
.actions-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    color: $text;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    i {
        color: $success;
        font-size: 1.1rem;
    }

    &:hover:not(:disabled) {
        border-color: $success;
        background: rgba($success, 0.05);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba($success, 0.15);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

// ==========================================
// ЛЕГЕНДА
// ==========================================
.legend-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 16px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    color: $text-muted;
}

.legend-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: white;
    flex-shrink: 0;

    &.admin {
        background: $danger;
    }

    &.user {
        background: $success;
    }

    &.vip {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    }

    &.delivery {
        background: $primary;
    }
}

// ==========================================
// АДАПТИВНОСТЬ
// ==========================================
@media (max-width: 640px) {
    .search-card,
    .results-section,
    .legend-card {
        padding: 16px;
    }

    .search-header {
        gap: 12px;
    }

    .search-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .search-info h3 {
        font-size: 1rem;
    }

    .filters-chips {
        gap: 6px;
    }

    .filter-chip {
        padding: 6px 10px;
        font-size: 0.8rem;
    }

    .user-card {
        padding: 12px;
        gap: 10px;
    }

    .user-avatar {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }

    .user-name {
        font-size: 0.9rem;
    }

    .user-phone {
        font-size: 0.75rem;
    }

    .badge {
        padding: 2px 6px;
        font-size: 0.65rem;
    }

    .legend-card {
        grid-template-columns: 1fr;
    }
}
</style>
