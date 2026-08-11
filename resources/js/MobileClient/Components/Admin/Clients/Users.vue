<template>
    <div class="users-search">
        <!-- ШАПКА ПОИСКА -->
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

            <form @submit.prevent="handleSearch" class="search-form">
                <div class="input-wrapper">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input
                        type="text"
                        v-model="localFilters.search"
                        class="search-input"
                        placeholder="Телефон или имя пользователя"
                        @input="debounceSearch"
                    >
                    <button v-if="localFilters.search" type="button" class="clear-btn" @click="clearSearch">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Фильтры -->
                <div class="filters-chips">
                    <button type="button" class="filter-chip" :class="{ 'is-active': localFilters.need_admins }" @click="toggleFilter('need_admins')">
                        <i class="fa-solid fa-user-shield"></i><span>Админы</span>
                    </button>
                    <button type="button" class="filter-chip" :class="{ 'is-active': localFilters.need_with_phone }" @click="toggleFilter('need_with_phone')">
                        <i class="fa-solid fa-phone"></i><span>С телефоном</span>
                    </button>
                    <button type="button" class="filter-chip" :class="{ 'is-active': localFilters.need_without_phone }" @click="toggleFilter('need_without_phone')">
                        <i class="fa-solid fa-phone-slash"></i><span>Без телефона</span>
                    </button>
                    <button type="button" class="filter-chip" :class="{ 'is-active': localFilters.need_deliveryman }" @click="toggleFilter('need_deliveryman')">
                        <i class="fa-solid fa-motorcycle"></i><span>Курьеры</span>
                    </button>
                    <button type="button" class="filter-chip vip" :class="{ 'is-active': localFilters.need_vip }" @click="toggleFilter('need_vip')">
                        <i class="fa-solid fa-crown"></i><span>VIP</span>
                    </button>
                    <button type="button" class="filter-chip" :class="{ 'is-active': localFilters.need_not_vip }" @click="toggleFilter('need_not_vip')">
                        <i class="fa-solid fa-user"></i><span>Не VIP</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- РЕЗУЛЬТАТЫ -->
        <!-- РЕЗУЛЬТАТЫ -->
        <div v-if="!isLoading || users.length > 0" class="results-section">
            <div class="results-header">
                <div class="results-count">
                    Найдено: <strong>{{ users_paginate_object?.total || users.length }}</strong>
                </div>

                <!-- 🆕 Кнопка сортировки по ID -->
                <button class="sort-toggle-btn" @click="toggleSortDirection" :title="sortDirection === 'desc' ? 'Сначала новые (по убыванию)' : 'Сначала старые (по возрастанию)'">
                    <i class="fa-solid" :class="sortDirection === 'desc' ? 'fa-sort-numeric-down-alt' : 'fa-sort-numeric-up-alt'"></i>
                    <span>По ID</span>
                </button>
            </div>

            <!-- Список -->
            <div v-if="users.length > 0" class="users-list">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="user-card"
                    @click="openUserMenu(user)"
                >
                    <div class="user-avatar">
                        <img v-if="user.avatar" :src="user.avatar" :alt="user.name">
                        <i v-else class="fa-solid" :class="getUserIcon(user)"></i>
                    </div>

                    <div class="user-info">
                        <div class="user-name">
                            <span class="id-badge bg-primary">#{{ user.id }}</span>
                            <span class="user-name-text">{{ user.name || 'Без имени' }}</span>
                        </div>
                        <div class="user-phone">
                            <i class="fa-solid fa-phone"></i>
                            {{ user.phone || 'Нет телефона' }}
                        </div>
                        <div class="user-balance" v-if="user.cashback_balance !== undefined">
                            <i class="fa-solid fa-coins"></i>
                            {{ user.cashback_balance }} баллов
                        </div>
                    </div>

                    <div class="user-badges">
                        <span v-if="user.blocked_at" class="badge blocked" title="Заблокирован">
                            <i class="fa-solid fa-ban"></i>
                        </span>
                        <span v-if="user.is_admin" class="badge admin" title="Администратор">
                            <i class="fa-solid fa-user-shield"></i>
                        </span>
                        <span v-if="user.is_vip" class="badge vip" title="VIP">
                            <i class="fa-solid fa-crown"></i>
                        </span>
                        <span v-if="user.is_deliveryman" class="badge delivery" title="Курьер">
                            <i class="fa-solid fa-motorcycle"></i>
                        </span>
                    </div>

                    <div class="user-arrow">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </div>
                </div>

                <!-- 🆕 КНОПКА "ЗАГРУЗИТЬ ЕЩЕ" -->
                <div v-if="hasMore" class="load-more-wrapper">
                    <button class="load-more-btn" @click="loadMoreUsers" :disabled="isLoadingMore">
                        <span v-if="isLoadingMore" class="btn-spinner"></span>
                        <template v-else>
                            <i class="fa-solid fa-arrow-down"></i>
                            <span>Загрузить еще</span>
                        </template>
                    </button>
                </div>
                <div v-else-if="users.length > 0" class="all-loaded">
                    <i class="fa-solid fa-check-circle"></i>
                    Все пользователи загружены
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else-if="!isLoading" class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-user-slash"></i></div>
                <h4>Пользователи не найдены</h4>
                <p>Попробуйте изменить параметры поиска</p>
            </div>
        </div>

        <!-- Индикатор первичной загрузки -->
        <div v-if="isLoading && users.length === 0" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Загружаем пользователей...</p>
        </div>

        <!-- 🆕 BOTTOM SHEET: МЕНЮ ДЕЙСТВИЙ -->
        <teleport to="body">
            <transition name="sheet-fade">
                <div v-if="showUserMenu" class="sheet-overlay" @click="closeUserMenu">
                    <div class="sheet-container" @click.stop>
                        <div class="sheet-header">
                            <div class="sheet-avatar">
                                <img v-if="selectedUser?.avatar" :src="selectedUser.avatar" :alt="selectedUser.name">
                                <i v-else class="fa-solid" :class="getUserIcon(selectedUser)"></i>
                            </div>
                            <div class="sheet-info">
                                <div class="sheet-name">{{ selectedUser?.name || 'Пользователь' }}</div>
                                <div class="sheet-phone">{{ selectedUser?.phone || 'Нет телефона' }}</div>
                            </div>
                            <button class="sheet-close" @click="closeUserMenu">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="sheet-actions">
                            <button class="sheet-action" @click="writeToUser">
                                <div class="sheet-action-icon chat"><i class="fa-solid fa-comments"></i></div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Написать сообщение</div>
                                    <div class="sheet-action-desc">Открыть чат с пользователем</div>
                                </div>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>

                            <button class="sheet-action" @click="viewProfile">
                                <div class="sheet-action-icon profile"><i class="fa-solid fa-id-card"></i></div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Посмотреть профиль</div>
                                    <div class="sheet-action-desc">Полная информация о клиенте</div>
                                </div>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>

                            <button class="sheet-action" @click="editUser">
                                <div class="sheet-action-icon edit"><i class="fa-solid fa-pen-to-square"></i></div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Редактировать</div>
                                    <div class="sheet-action-desc">Изменить данные пользователя</div>
                                </div>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>

                            <button class="sheet-action" @click="openCashbackModal">
                                <div class="sheet-action-icon bonus"><i class="fa-solid fa-coins"></i></div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">Управление баллами</div>
                                    <div class="sheet-action-desc">Начислить или списать баллы</div>
                                </div>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>

                            <div class="sheet-divider"></div>

                            <button class="sheet-action" :class="selectedUser?.blocked_at ? 'success' : 'danger'" @click="openBlockModal">
                                <div class="sheet-action-icon" :class="selectedUser?.blocked_at ? 'unblock' : 'block'">
                                    <i :class="selectedUser?.blocked_at ? 'fa-solid fa-unlock' : 'fa-solid fa-ban'"></i>
                                </div>
                                <div class="sheet-action-info">
                                    <div class="sheet-action-title">
                                        {{ selectedUser?.blocked_at ? 'Разблокировать' : 'Заблокировать' }}
                                    </div>
                                    <div class="sheet-action-desc">
                                        {{ selectedUser?.blocked_at ? 'Восстановить доступ' : 'Закрыть доступ к сервису' }}
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- 🆕 МОДАЛКА: УПРАВЛЕНИЕ БАЛЛАМИ (НАЧИСЛЕНИЕ / СПИСАНИЕ) -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showCashbackModal" class="modal-overlay" @click.self="showCashbackModal = false">
                    <div class="action-modal">
                        <div class="modal-icon" :class="cashbackForm.type === 'credit' ? 'bonus' : 'danger'">
                            <i :class="cashbackForm.type === 'credit' ? 'fa-solid fa-coins' : 'fa-solid fa-coins fa-flip-horizontal'"></i>
                        </div>
                        <h3>{{ cashbackForm.type === 'credit' ? 'Начислить баллы' : 'Списать баллы' }}</h3>
                        <p class="modal-subtitle">
                            Пользователь: <strong>{{ selectedUser?.name }}</strong><br>
                            Текущий баланс: <strong>{{ selectedUser?.cashback_balance || 0 }}</strong> баллов
                        </p>

                        <!-- Переключатель типа операции -->
                        <div class="operation-toggle">
                            <button
                                class="toggle-btn"
                                :class="{ 'active': cashbackForm.type === 'credit' }"
                                @click="cashbackForm.type = 'credit'"
                            >
                                <i class="fa-solid fa-plus"></i> Начислить
                            </button>
                            <button
                                class="toggle-btn"
                                :class="{ 'active': cashbackForm.type === 'debit' }"
                                @click="cashbackForm.type = 'debit'"
                            >
                                <i class="fa-solid fa-minus"></i> Списать
                            </button>
                        </div>

                        <div class="form-field">
                            <label>Количество баллов</label>
                            <input
                                type="number"
                                v-model.number="cashbackForm.amount"
                                min="1"
                                :max="cashbackForm.type === 'debit' ? (selectedUser?.cashback_balance || 0) : 1000000"
                                placeholder="Например: 100"
                            >
                        </div>
                        <div class="form-field">
                            <label>Причина (опционально)</label>
                            <input
                                type="text"
                                v-model="cashbackForm.description"
                                :placeholder="cashbackForm.type === 'credit' ? 'За отзыв, компенсация...' : 'Ошибка начисления, списание за...'"
                            >
                        </div>

                        <div class="quick-amounts">
                            <button
                                v-for="amount in [50, 100, 250, 500]"
                                :key="amount"
                                @click="cashbackForm.amount = amount"
                            >
                                {{ amount }}
                            </button>
                        </div>

                        <div class="modal-actions">
                            <button class="btn-cancel" @click="showCashbackModal = false">Отмена</button>
                            <button
                                class="btn-confirm"
                                :class="cashbackForm.type === 'credit' ? 'bonus' : 'danger'"
                                @click="confirmCashback"
                                :disabled="isProcessing || !cashbackForm.amount || cashbackForm.amount <= 0"
                            >
                                <span v-if="isProcessing" class="btn-spinner"></span>
                                <span v-else>{{ cashbackForm.type === 'credit' ? 'Начислить' : 'Списать' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- 🆕 МОДАЛКА: БЛОКИРОВКА -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showBlockModal" class="modal-overlay" @click.self="showBlockModal = false">
                    <div class="action-modal">
                        <div class="modal-icon" :class="selectedUser?.blocked_at ? 'success' : 'danger'">
                            <i :class="selectedUser?.blocked_at ? 'fa-solid fa-unlock' : 'fa-solid fa-ban'"></i>
                        </div>
                        <h3>{{ selectedUser?.blocked_at ? 'Разблокировать пользователя?' : 'Заблокировать пользователя?' }}</h3>
                        <p class="modal-subtitle">
                            {{ selectedUser?.name }} {{ selectedUser?.blocked_at ? 'снова получит доступ к сервису' : 'не сможет пользоваться сервисом' }}
                        </p>

                        <div v-if="!selectedUser?.blocked_at" class="form-field">
                            <label>Причина блокировки</label>
                            <textarea v-model="blockForm.message" rows="3" placeholder="Нарушение правил, спам..."></textarea>
                        </div>

                        <div class="modal-actions">
                            <button class="btn-cancel" @click="showBlockModal = false">Отмена</button>
                            <button
                                class="btn-confirm"
                                :class="selectedUser?.blocked_at ? 'success' : 'danger'"
                                @click="confirmBlock"
                                :disabled="isProcessing"
                            >
                                <span v-if="isProcessing" class="btn-spinner"></span>
                                <span v-else>{{ selectedUser?.blocked_at ? 'Разблокировать' : 'Заблокировать' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script>
import { useUsers } from '@/MobileClient/Composables/useUsers.js';
import { useRouter } from 'vue-router';

export default {
    name: "UsersSearch",

    setup() {
        const usersStore = useUsers();
        const router = useRouter();
        return { ...usersStore, router };
    },

    data() {
        return {
            localFilters: {
                search: '',
                need_admins: false,
                need_vip: false,
                need_not_vip: false,
                need_with_phone: false,
                need_without_phone: false,
                need_deliveryman: false,
            },
            searchDebounce: null,

            sortDirection: 'desc',

            // Меню и модалки
            showUserMenu: false,
            showCashbackModal: false,
            showBlockModal: false,
            selectedUser: null,
            isProcessing: false,

            cashbackForm: { type: 'credit', amount: null, description: '' },
            blockForm: { message: '' },
        };
    },

    computed: {
        hasMore() {
            if (!this.users_paginate_object) return false;
            return this.users_paginate_object.current_page < this.users_paginate_object.last_page;
        }
    },

    mounted() {
        this.handleSearch();
    },

    beforeUnmount() {
        if (this.searchDebounce) clearTimeout(this.searchDebounce);
    },

    methods: {
        toggleSortDirection() {
            this.sortDirection = this.sortDirection === 'desc' ? 'asc' : 'desc';
            this.handleSearch(); // Сразу перезагружаем список
        },

        debounceSearch() {
            if (this.searchDebounce) clearTimeout(this.searchDebounce);
            this.searchDebounce = setTimeout(() => this.handleSearch(), 400);
        },

        clearSearch() {
            this.localFilters.search = '';
            this.handleSearch();
        },

        toggleFilter(name) {
            this.localFilters[name] = !this.localFilters[name];
            this.handleSearch();
        },

        async handleSearch() {
            this.setFilters(this.localFilters);
            try {
                await this.loadUsers({
                    dataObject: {
                        ...this.localFilters,
                        order_by: 'id',          // 🆕 Сортируем по ID
                        direction: this.sortDirection // 🆕 Направление сортировки
                    },
                    page: 0,
                });
            } catch (error) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось выполнить поиск', type: 'error' });
            }
        },

        // ===== МЕНЮ ДЕЙСТВИЙ =====
        openUserMenu(user) {
            this.selectedUser = user;
            this.showUserMenu = true;
            document.body.style.overflow = 'hidden';
        },

        closeUserMenu() {
            this.showUserMenu = false;
            document.body.style.overflow = '';
        },

        async writeToUser() {
            this.closeUserMenu();
            try {
                const dialogId = await this.startChat(this.selectedUser.id);
                this.router.push({ name: 'ChatRoom', params: { id: dialogId } }).catch(() => {
                    this.$notify?.({ title: 'Ошибка', text: 'Не удалось открыть чат', type: 'error' });
                });
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось начать чат', type: 'error' });
            }
        },

        viewProfile() {
            this.closeUserMenu();
            this.router.push({ name: 'AdminUserDetails', params: { id: this.selectedUser.id } }).catch(() => {
                this.$notify?.({ title: 'Ошибка', text: 'Страница профиля не найдена', type: 'error' });
            });
        },

        editUser() {
            this.closeUserMenu();
            this.router.push({ name: 'AdminUserDetails', params: { id: this.selectedUser.id } }).catch(() => {
                this.$notify?.({ title: 'Ошибка', text: 'Страница редактирования не найдена', type: 'error' });
            });
        },


        // ===== УПРАВЛЕНИЕ БАЛЛАМИ =====
        openCashbackModal() {
            this.closeUserMenu();
            this.cashbackForm = { type: 'credit', amount: null, description: '' };
            this.showCashbackModal = true;
        },

        async confirmCashback() {
            if (!this.cashbackForm.amount || this.cashbackForm.amount <= 0) return;

            // 🛡️ Защита от списания большего количества баллов, чем есть на балансе
            if (this.cashbackForm.type === 'debit') {
                const currentBalance = this.selectedUser?.cashback_balance || 0;
                if (this.cashbackForm.amount > currentBalance) {
                    this.$notify?.({
                        title: 'Ошибка',
                        text: `Недостаточно баллов. Доступно: ${currentBalance}`,
                        type: 'error'
                    });
                    return;
                }
            }

            this.isProcessing = true;
            try {
                const payload = {
                    amount: this.cashbackForm.amount,
                    description: this.cashbackForm.description || (this.cashbackForm.type === 'credit' ? 'Ручное начисление' : 'Ручное списание'),
                    type: this.cashbackForm.type // 'credit' или 'debit'
                };

                await this.manageCashback(this.selectedUser.id, payload);

                // 🔄 Обновляем баланс локально для мгновенного отклика UI
                const idx = this.users.findIndex(u => u.id === this.selectedUser.id);
                if (idx !== -1) {
                    const currentBalance = this.users[idx].cashback_balance || 0;
                    if (this.cashbackForm.type === 'credit') {
                        this.users[idx].cashback_balance = currentBalance + this.cashbackForm.amount;
                    } else {
                        this.users[idx].cashback_balance = Math.max(0, currentBalance - this.cashbackForm.amount);
                    }
                }

                this.$notify?.({
                    title: 'Успех',
                    text: `${this.cashbackForm.type === 'credit' ? 'Начислено' : 'Списано'} ${this.cashbackForm.amount} баллов`,
                    type: 'success'
                });
                this.showCashbackModal = false;
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: e.response?.data?.message || 'Не удалось выполнить операцию', type: 'error' });
            } finally {
                this.isProcessing = false;
            }
        },

        // ===== БЛОКИРОВКА =====
        openBlockModal() {
            this.closeUserMenu();
            this.blockForm = { message: '' };
            this.showBlockModal = true;
        },

        async confirmBlock() {
            this.isProcessing = true;
            const isBlocked = !!this.selectedUser.blocked_at;

            try {
                await this.toggleBlock(this.selectedUser.id, {
                    block: !isBlocked,
                    message: this.blockForm.message || null,
                });

                const idx = this.users.findIndex(u => u.id === this.selectedUser.id);
                if (idx !== -1) {
                    this.users[idx].blocked_at = isBlocked ? null : new Date().toISOString();
                    this.users[idx].blocked_message = isBlocked ? null : this.blockForm.message;
                }

                this.$notify?.({
                    title: 'Успех',
                    text: isBlocked ? 'Пользователь разблокирован' : 'Пользователь заблокирован',
                    type: 'success'
                });
                this.showBlockModal = false;
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: 'Операция не выполнена', type: 'error' });
            } finally {
                this.isProcessing = false;
            }
        },

        getUserIcon(user) {
            if (user?.is_admin) return 'fa-user-shield';
            if (user?.is_vip) return 'fa-crown';
            if (user?.is_deliveryman) return 'fa-motorcycle';
            return 'fa-user';
        },
    },
};
</script>

<style lang="scss" scoped>
$primary: #667eea;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
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

.search-card, .results-section {
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
    width: 48px; height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, $primary, #60a5fa);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.search-info {
    flex: 1;
    h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 2px 0; }
    p { font-size: 0.85rem; color: $text-muted; margin: 0; }
}

.input-wrapper {
    position: relative;
    margin-bottom: 16px;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: $text-muted;
}

.search-input {
    width: 100%;
    padding: 12px 42px 12px 42px;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.95rem;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }
}

.clear-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 28px; height: 28px;
    border: none;
    background: $bg;
    border-radius: 50%;
    color: $text-muted;
    cursor: pointer;

    &:hover { background: $danger; color: white; }
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

    i { font-size: 0.8rem; }

    &:hover { border-color: $primary; color: $primary; }

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

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;

    .results-count {
        font-size: 0.85rem;
        color: $text-muted;
        strong { color: $primary; }
    }

    // 🆕 Стили кнопки сортировки
    .sort-toggle-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: $bg;
        border: 1px solid $border;
        border-radius: 8px;
        color: $text-muted;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;

        &:hover {
            border-color: $primary;
            color: $primary;
            background: rgba($primary, 0.05);
        }
    }
}

// ...

// 🆕 Исправленные стили имени для красивого многострочного отображения
.user-name {
    font-weight: 600;
    font-size: 0.85rem; // Уменьшили шрифт
    line-height: 1.2;   // Компактный межстрочный интервал
    margin-bottom: 4px;
    display: flex;
    align-items: flex-start; // Выравнивание бейджа и текста по верхнему краю
    gap: 8px;
    flex-wrap: wrap; // Разрешаем перенос на новую строку
}

.user-name-text {
    flex: 1;
    word-break: break-word; // Красивый перенос длинных слов
    color: $text;
}

.id-badge {
    flex-shrink: 0; // Запрещаем сжатие бейджа
    min-width: 28px;
    height: 20px;
    padding: 0 6px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    color: white;
    background: $primary;
    margin-top: 1px; // Небольшая коррекция для визуального выравнивания с первой строкой текста
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
    }

    &:active { transform: scale(0.99); }
}

.user-avatar {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, $primary, #60a5fa);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    overflow: hidden;

    img { width: 100%; height: 100%; object-fit: cover; }
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-phone {
    font-size: 0.8rem;
    color: $text-muted;
    display: flex;
    align-items: center;
    gap: 4px;
    i { font-size: 0.7rem; }
}

.user-balance {
    font-size: 0.75rem;
    color: $warning;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
}

.user-badges {
    display: flex;
    gap: 4px;
}

.badge {
    width: 26px; height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;

    &.admin { background: rgba($danger, 0.15); color: $danger; }
    &.vip { background: linear-gradient(135deg, rgba(#fbbf24, 0.2), rgba(#f59e0b, 0.2)); color: #b45309; }
    &.delivery { background: rgba($success, 0.15); color: $success; }
    &.blocked { background: rgba($danger, 0.2); color: $danger; }
}

.user-arrow {
    color: $text-muted;
    font-size: 1rem;
}

// ===== LOAD MORE =====
.load-more-wrapper {
    margin-top: 16px;
    display: flex;
    justify-content: center;
}

.load-more-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: $card-bg;
    border: 1px solid $primary;
    color: $primary;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $primary;
        color: white;
        transform: translateY(-1px);
    }

    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

.all-loaded {
    text-align: center;
    color: $text-muted;
    font-size: 0.85rem;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    i { color: $success; }
}

// ===== BOTTOM SHEET =====
.sheet-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 3000;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.sheet-container {
    width: 100%;
    max-width: 500px;
    background: $card-bg;
    border-radius: 24px 24px 0 0;
    overflow: hidden;
    animation: sheetSlideUp 0.3s cubic-bezier(0.32, 0.72, 0, 1);
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

@keyframes sheetSlideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

.sheet-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    border-bottom: 1px solid $border;
}

.sheet-avatar {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, $primary, #60a5fa);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    overflow: hidden;

    img { width: 100%; height: 100%; object-fit: cover; }
}

.sheet-info { flex: 1; min-width: 0; }
.sheet-name { font-weight: 700; font-size: 1.1rem; }
.sheet-phone { font-size: 0.85rem; color: $text-muted; }

.sheet-close {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: $bg;
    border: none;
    cursor: pointer;
    color: $text-muted;

    &:hover { background: $danger; color: white; }
}

.sheet-actions {
    padding: 12px;
    overflow-y: auto;
}

.sheet-action {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    padding: 14px;
    background: transparent;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    text-align: left;
    color: $text;
    transition: all 0.2s;

    &:hover { background: $bg; }
    &:active { transform: scale(0.98); }

    &.danger {
        color: $danger;
        .sheet-action-title { color: $danger; }
        .sheet-action-desc { color: rgba($danger, 0.7); }
        &:hover { background: rgba($danger, 0.05); }
    }

    &.success {
        color: $success;
        .sheet-action-title { color: $success; }
        &:hover { background: rgba($success, 0.05); }
    }
}

.sheet-action-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: $bg;
    color: $text;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;

    &.chat { background: rgba($primary, 0.1); color: $primary; }
    &.profile { background: rgba(#8b5cf6, 0.1); color: #8b5cf6; }
    &.edit { background: rgba($warning, 0.1); color: $warning; }
    &.bonus { background: rgba($warning, 0.15); color: #b45309; }
    &.block { background: rgba($danger, 0.1); color: $danger; }
    &.unblock { background: rgba($success, 0.1); color: $success; }
}

.sheet-action-info { flex: 1; min-width: 0; }
.sheet-action-title { font-weight: 600; font-size: 0.95rem; margin-bottom: 2px; }
.sheet-action-desc { font-size: 0.75rem; color: $text-muted; }

.sheet-divider {
    height: 1px;
    background: $border;
    margin: 8px 14px;
}

// ===== МОДАЛКИ =====
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 3500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.action-modal {
    background: $card-bg;
    border-radius: 20px;
    padding: 28px 24px;
    width: 100%;
    max-width: 400px;
    text-align: center;
    animation: modalSlideUp 0.3s ease;

    h3 { font-size: 1.2rem; margin: 0 0 8px; }
    .modal-subtitle {
        font-size: 0.9rem;
        color: $text-muted;
        margin: 0 0 20px;
        line-height: 1.5;
        strong { color: $text; }
    }
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-icon {
    width: 64px; height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    margin: 0 auto 16px;

    &.bonus { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    &.danger { background: linear-gradient(135deg, $danger, #dc2626); }
    &.success { background: linear-gradient(135deg, $success, #16a34a); }
}

// 🆕 Переключатель операций
.operation-toggle {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    background: $bg;
    padding: 4px;
    border-radius: 10px;

    .toggle-btn {
        flex: 1;
        padding: 10px;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        color: $text-muted;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;

        &.active {
            background: $card-bg;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        &:first-child.active {
            color: $success;
        }
        &:last-child.active {
            color: $danger;
        }
    }
}

.form-field {
    text-align: left;
    margin-bottom: 12px;

    label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: $text-muted;
        margin-bottom: 4px;
    }

    input, textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid $border;
        border-radius: 8px;
        font-size: 0.95rem;
        font-family: inherit;

        &:focus {
            outline: none;
            border-color: $primary;
            box-shadow: 0 0 0 3px rgba($primary, 0.1);
        }
    }
}

.quick-amounts {
    display: flex;
    gap: 6px;
    margin-bottom: 20px;

    button {
        flex: 1;
        padding: 8px;
        background: $bg;
        border: 1px solid $border;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;

        &:hover {
            background: $primary;
            color: white;
            border-color: $primary;
        }
    }
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;

    button {
        flex: 1;
        padding: 12px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
}

.btn-cancel {
    background: $bg;
    color: $text;
    border: 1px solid $border !important;
    &:hover { background: $border; }
}

.btn-confirm {
    color: white;
    &.bonus { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    &.danger { background: $danger; &:hover { background: #dc2626; } }
    &.success { background: $success; &:hover { background: #16a34a; } }
    &:disabled { opacity: 0.6; cursor: not-allowed; }
}

// ===== УТИЛИТЫ =====
.loading-state, .empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon, .loading-spinner {
    width: 56px; height: 56px;
    border-radius: 50%;
    margin: 0 auto 12px;
}

.empty-icon {
    background: rgba($primary, 0.1);
    color: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.loading-spinner {
    border: 3px solid $border;
    border-top-color: $primary;
    animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.btn-spinner {
    width: 16px; height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

// Анимации
.sheet-fade-enter-active, .sheet-fade-leave-active,
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.25s ease;
}
.sheet-fade-enter-from, .sheet-fade-leave-to,
.fade-enter-from, .fade-leave-to { opacity: 0; }

.id-badge {
    min-width: 24px;
    height: 18px;
    padding: 0 6px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    color: white;
    background: $primary;
}
</style>
