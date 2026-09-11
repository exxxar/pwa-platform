<template>
    <div class="available-shops-page">
        <!-- Шапка -->
        <div class="page-header">
            <button class="back-btn" @click="$router.back()">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="header-text">
                <h2>Доступные магазины</h2>
                <p>Выберите заведения для доставки</p>
            </div>
            <!-- 🆕 Кнопка выбрать все / снять выделение -->
            <button v-if="shops.length > 0" class="toggle-all-btn" @click="toggleAllShops">
                {{ selectedIds.length === shops.length ? 'Снять все' : 'Выбрать все' }}
            </button>
        </div>

        <!-- Состояние загрузки -->
        <div v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p>Загрузка списка магазинов...</p>
        </div>

        <!-- Список магазинов -->
        <div v-else class="shops-list">
            <div
                v-for="shop in shops"
                :key="shop.id"
                class="shop-card"
                :class="{ 'is-selected': isSelected(shop.id) }"
                @click="toggleShop(shop.id)"
            >
                <div class="shop-checkbox">
                    <i v-if="isSelected(shop.id)" class="fa-solid fa-check"></i>
                </div>

                <div class="shop-info">
                    <div class="shop-avatar">
                        <img v-if="shop.image" :src="shop.image" :alt="shop.name">
                        <i v-else class="fa-solid fa-store"></i>
                    </div>
                    <div class="shop-details">
                        <h3 class="shop-name">{{ shop.name }}</h3>
                        <p class="shop-desc">{{ shop.description }}</p>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-if="shops.length === 0" class="empty-state">
                <i class="fa-solid fa-store-slash"></i>
                <p>На данный момент нет доступных магазинов</p>
            </div>

            <!-- 🆕 СТАТИЧНАЯ панель сохранения (всегда видна внизу списка) -->
            <div class="save-bar-static">
                <div class="save-info">
                    <span class="count-badge">{{ selectedIds.length }}</span>
                    <span>{{ getShopsWord(selectedIds.length) }} выбрано</span>
                </div>
                <button class="btn-save" @click="saveSettings" :disabled="isSaving">
                    <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
                    <span v-else>Сохранить настройки</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import {usePermissions} from "@/MobileClient/composables/usePermissions";

export default {
    name: 'AvailableShops',
    setup() {
        const { isAdmin } = usePermissions();
        return { isAdmin };
    },
    data() {
        return {
            shops: [],
            selectedIds: [],
            initialSelectedIds: [],
            isLoading: true,
            isSaving: false,
        };
    },
    created() {
        if (!this.isAdmin) {
            this.$router.push({ name: 'Auth' }).catch(() => {});
        }
    },
    mounted() {
        this.fetchShops();
    },

    methods: {
        async fetchShops() {
            this.isLoading = true;
            try {
                const response = await axios.get('/deliveryman/shops');
                if (response.data.success) {
                    this.shops = response.data.data.shops;
                    this.selectedIds = [...response.data.data.selected_ids];
                    this.initialSelectedIds = [...response.data.data.selected_ids];
                }
            } catch (error) {
                console.error('Ошибка загрузки магазинов:', error);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить список магазинов', type: 'error' });
            } finally {
                this.isLoading = false;
            }
        },

        toggleAllShops() {
            if (this.selectedIds.length === this.shops.length) {
                this.selectedIds = []; // Снять все
            } else {
                this.selectedIds = this.shops.map(shop => shop.id); // Выбрать все
            }
        },

        toggleShop(shopId) {
            const index = this.selectedIds.indexOf(shopId);
            if (index === -1) {
                this.selectedIds.push(shopId);
            } else {
                this.selectedIds.splice(index, 1);
            }
        },

        isSelected(shopId) {
            return this.selectedIds.includes(shopId);
        },

        getShopsWord(count) {
            if (count === 0) return '0 магазинов';
            if (count === 1) return '1 магазин';
            if (count >= 2 && count <= 4) return `${count} магазина`;
            return `${count} магазинов`;
        },

        async saveSettings() {
            this.isSaving = true;
            try {
                const response = await axios.post('/deliveryman/shops', {
                    shop_ids: this.selectedIds
                });

                if (response.data.success) {
                    this.initialSelectedIds = [...this.selectedIds];
                    this.$notify?.({
                        title: 'Успех',
                        text: response.data.message,
                        type: 'success'
                    });
                }
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                this.$notify?.({
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось сохранить настройки',
                    type: 'error'
                });
            } finally {
                this.isSaving = false;
            }
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.available-shops-page {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 40px; // Отступ снизу вместо фиксированной панели
}

.page-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: $card-bg;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 10;

    .back-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: $bg;
        border: 1px solid $border;
        color: $text;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
        flex-shrink: 0;

        &:hover {
            background: $border;
        }
    }

    .header-text {
        flex: 1;
        h2 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: $text;
        }
        p {
            margin: 2px 0 0;
            font-size: 0.8rem;
            color: $text-muted;
        }
    }

    .toggle-all-btn {
        padding: 8px 14px;
        background: rgba($primary, 0.1);
        color: $primary;
        border: 1px solid rgba($primary, 0.2);
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;

        &:hover {
            background: rgba($primary, 0.15);
            border-color: $primary;
        }
    }
}

.loading-state, .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: $text-muted;
    text-align: center;

    .spinner {
        width: 32px;
        height: 32px;
        border: 3px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 16px;
    }

    i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }
}

@keyframes spin { to { transform: rotate(360deg); } }

.shops-list {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.shop-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: $card-bg;
    border: 2px solid $border;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        border-color: rgba($primary, 0.3);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    &.is-selected {
        border-color: $primary;
        background: rgba($primary, 0.03);

        .shop-checkbox {
            background: $primary;
            border-color: $primary;
            color: white;
        }
    }
}

.shop-checkbox {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    transition: all 0.2s;
    flex-shrink: 0;
}

.shop-info {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}

.shop-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: $bg;
    display: flex;
    align-items: center;
    justify-content: center;
    color: $text-muted;
    font-size: 1.2rem;
    flex-shrink: 0;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.shop-details {
    flex: 1;
    min-width: 0;
}

.shop-name {
    margin: 0 0 4px;
    font-size: 1rem;
    font-weight: 600;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.shop-desc {
    margin: 0;
    font-size: 0.8rem;
    color: $text-muted;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

// 🆕 Статичная панель сохранения (в конце списка)
.save-bar-static {
    margin-top: 24px;
    padding: 20px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

.save-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: $text-muted;
}

.count-badge {
    background: $primary;
    color: white;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 2px 8px;
    border-radius: 10px;
}

.btn-save {
    padding: 12px 24px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: $primary-dark;
        transform: translateY(-1px);
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
}

@media (max-width: 640px) {
    .page-header {
        padding: 12px 16px;
    }
    .shops-list {
        padding: 12px;
    }
    .save-bar-static {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
        gap: 12px;
    }
    .save-info {
        justify-content: center;
    }
}
</style>
