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

            <form @submit.prevent="handleSearch(1)" class="search-form">
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
                    <button type="button" class="filter-chip" :class="{ 'is-active': need_admins }" @click="toggleFilter('need_admins')">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Админы</span>
                    </button>
                    <button type="button" class="filter-chip" :class="{ 'is-active': need_with_phone }" @click="toggleFilter('need_with_phone')">
                        <i class="fa-solid fa-phone"></i>
                        <span>С телефоном</span>
                    </button>
                    <button type="button" class="filter-chip" :class="{ 'is-active': need_without_phone }" @click="toggleFilter('need_without_phone')">
                        <i class="fa-solid fa-phone-slash"></i>
                        <span>Без телефона</span>
                    </button>
                    <button type="button" class="filter-chip" :class="{ 'is-active': need_deliveryman }" @click="toggleFilter('need_deliveryman')">
                        <i class="fa-solid fa-motorcycle"></i>
                        <span>Курьеры</span>
                    </button>
                    <button type="button" class="filter-chip vip" :class="{ 'is-active': need_vip }" @click="toggleFilter('need_vip')">
                        <i class="fa-solid fa-crown"></i>
                        <span>VIP</span>
                    </button>
                    <button type="button" class="filter-chip" :class="{ 'is-active': need_not_vip }" @click="toggleFilter('need_not_vip')">
                        <i class="fa-solid fa-user"></i>
                        <span>Не VIP</span>
                    </button>
                </div>

                <button type="submit" class="search-btn" :disabled="isLoading">
                    <span v-if="isLoading" class="btn-spinner"></span>
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
        <div v-if="users && !isLoading" class="results-section">

            <!-- Счётчик результатов -->
            <div class="results-header">
                <div class="results-count">
                    Найдено: <strong>{{ users_paginate_object?.total || 0 }}</strong>
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
                            {{ truncateName(user.name || 'Не указано') }}
                        </div>
                        <div class="user-phone">
                            <i class="fa-solid fa-phone"></i>
                            {{ user.phone || 'Нет телефона' }}
                        </div>
                    </div>

                    <div class="user-badges">
                        <span v-if="user.is_admin" class="badge admin">
                            <i class="fa-solid fa-user-shield"></i>
                        </span>
                        <span v-if="user.is_vip" class="badge vip">
                            <i class="fa-solid fa-crown"></i>
                        </span>
                        <span v-if="user.is_deliveryman" class="badge delivery">
                            <i class="fa-solid fa-motorcycle"></i>
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

            <!-- ========================================== -->
            <!-- НОВАЯ ПАГИНАЦИЯ -->
            <!-- ========================================== -->
            <div v-if="hasPagination" class="pagination-wrapper">
                <Pagination
                    :pagination="users_paginate_object"
                    @pagination_page="handleSearch"
                />
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="isLoading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Загружаем пользователей...</p>
        </div>

    </div>
</template>

<script>
import { useUsers } from '@/MobileClient/Composables/useUsers.js';
// Укажите правильный путь к вашему компоненту пагинации
import Pagination from '@/MobileClient/Components/Pagination.vue';

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

    setup() {
        const usersStore = useUsers();
        return { ...usersStore };
    },

    data() {
        return {
            search: '',
            need_admins: false,
            need_vip: false,
            need_not_vip: false,
            need_with_phone: false,
            need_without_phone: false,
            need_deliveryman: false,
        };
    },

    computed: {
        /**
         * Проверяем, есть ли вообще больше 1 страницы
         */
        hasPagination() {
            return this.users_paginate_object && Number(this.users_paginate_object.last_page) > 1;
        }
    },

    mounted() {
        this.handleSearch(1);
    },

    methods: {
        /**
         * Переключение фильтра со сбросом на 1 страницу
         */
        toggleFilter(filterName) {
            this[filterName] = !this[filterName];
            this.handleSearch(1);
        },

        /**
         * Поиск пользователей (page по умолчанию = 1, а не 0)
         */
        async handleSearch(page = 1) {

            try {
                await this.loadUsers({
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
            } catch (error) {
                console.error('Ошибка поиска:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось выполнить поиск',
                    type: 'error',
                });
            }
        },

        /**
         * Выбор пользователя
         */
        selectUser(user) {
            this.$emit("select", user);
        },

        truncateName(name) {
            if (!name) return 'Не указано';
            return name.length > 26 ? name.substring(0, 26) + '...' : name;
        },

        getUserIcon(user) {
            if (user.is_admin) return 'fa-user-shield';
            if (user.is_vip) return 'fa-crown';
            if (user.is_deliveryman) return 'fa-motorcycle';
            return 'fa-user';
        },
    },
};
</script>
<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$danger: #ef4444;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.users-search {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.search-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 20px;
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
    background: linear-gradient(135deg, $primary, #60a5fa);
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
    }

    &.vip.is-active {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        border-color: #f59e0b;
    }
}

.search-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: linear-gradient(135deg, $primary, #60a5fa);
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

.results-section {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 16px;
}

.results-header {
    margin-bottom: 12px;
}

.results-count {
    font-size: 0.85rem;
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
    padding: 12px;
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
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, $primary, #60a5fa);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: $text;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-phone {
    font-size: 0.75rem;
    color: $text-muted;
    display: flex;
    align-items: center;
    gap: 4px;

    i {
        font-size: 0.65rem;
    }
}

.user-badges {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}

.badge {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;

    &.admin {
        background: rgba($danger, 0.15);
        color: $danger;
    }

    &.vip {
        background: linear-gradient(135deg, rgba(#fbbf24, 0.2), rgba(#f59e0b, 0.2));
        color: #b45309;
    }

    &.delivery {
        background: rgba($success, 0.15);
        color: $success;
    }
}

.user-arrow {
    color: $text-muted;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.empty-state {
    text-align: center;
    padding: 30px 20px;

    .empty-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: rgba($primary, 0.1);
        color: $primary;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 12px;
    }

    h4 {
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: $text;
    }

    p {
        font-size: 0.85rem;
        color: $text-muted;
        margin: 0;
    }
}

.loading-state {
    text-align: center;
    padding: 40px 20px;

    .loading-spinner {
        width: 36px;
        height: 36px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 12px;
    }

    p {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0;
    }
}

.pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid $border;
}

.pagination-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: $bg;
    border: 1px solid $border;
    color: $text;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $primary;
        border-color: $primary;
        color: white;
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

.pagination-info {
    font-size: 0.85rem;
    color: $text-muted;
    font-weight: 600;
}
</style>
