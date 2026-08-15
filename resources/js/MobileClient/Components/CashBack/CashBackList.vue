<template>
    <div class="cashback-list-container">

        <!-- ========================================== -->
        <!-- ИНДИКАТОР ЗАГРУЗКИ -->
        <!-- ========================================== -->
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Загрузка истории операций...</p>
        </div>

        <!-- ========================================== -->
        <!-- СПИСОК ОПЕРАЦИЙ -->
        <!-- ========================================== -->
        <div v-else-if="cashback.length > 0" class="cashback-items-list">
            <CashBackItem
                v-for="item in cashback"
                :key="'cashback-' + item.id"
                :item="item"
            />
        </div>

        <!-- ========================================== -->
        <!-- ПУСТОЕ СОСТОЯНИЕ -->
        <!-- ========================================== -->
        <div v-else class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-coins"></i>
            </div>
            <h3>Операций пока нет</h3>
            <p>История начислений и списаний CashBack появится здесь</p>
        </div>

        <!-- ========================================== -->
        <!-- ПАГИНАЦИЯ -->
        <!-- ========================================== -->
        <div v-if="cashback_paginate_object && cashback_paginate_object.last_page > 1" class="pagination-wrapper">
            <Pagination
                :simple="true"
                @pagination_page="nextCashBackPage"
                :pagination="cashback_paginate_object"
            />
        </div>

    </div>
</template>

<script>
import {mapActions} from 'pinia'
import Pagination from '@/MobileClient/Components/Pagination.vue'
import CashBackItem from '@/MobileClient/Components/CashBack/CashBackItem.vue'

export default {
    name: 'CashBackList',

    components: {
        Pagination,
        CashBackItem,
    },

    props: {
        botUser: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            isLoading: false,
            cashback: [],
            cashback_paginate_object: null,
        }
    },

    mounted() {
        this.loadCashBackPage(0)
    },

    methods: {
        ...mapActions('cashback', ['loadCashBack']),

        async loadCashBackPage(page = 0) {
            if (!this.botUser?.telegram_chat_id) return

            this.isLoading = true

            try {
                await this.loadCashBack({
                    dataObject: {
                        user_telegram_chat_id: this.botUser.telegram_chat_id,
                    },
                    page: page,
                })

                // Предполагается, что в сторе есть геттеры или action возвращает данные.
                // Если action мутирует стейт стора, нужно брать из mapState.
                // Здесь оставлена логика из оригинала, адаптированная под Promise.
                const response = await this.loadCashBack({
                    dataObject: {
                        user_telegram_chat_id: this.botUser.telegram_chat_id,
                    },
                    page: page,
                })

                // Если ваш store action возвращает данные напрямую:
                if (response) {
                    this.cashback = response.data || response.cashback || []
                    this.cashback_paginate_object = response.paginate || response.paginate_object || null
                }
            } catch (err) {
                console.error('Ошибка загрузки истории CashBack:', err)
                this.$notify?.({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить историю операций',
                    type: 'error',
                })
            } finally {
                this.isLoading = false
            }
        },

        nextCashBackPage(index) {
            this.loadCashBackPage(index)
        },
    },
}
</script>

<style lang="scss" scoped>
$admin-bg: #f4f6f9;
$admin-card-bg: #ffffff;
$admin-text: #2c3e50;
$admin-text-muted: #6c757d;
$admin-border: #e9ecef;
$admin-primary: #3b82f6;
$admin-gold: #fbbf24;

.cashback-list-container {
    background: $admin-bg;
    min-height: 100%;
    padding-bottom: env(safe-area-inset-bottom);
}

// ==========================================
// ИНДИКАТОР ЗАГРУЗКИ
// ==========================================
.loading-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: $admin-text-muted;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid $admin-border;
    border-top-color: $admin-primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 12px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

// ==========================================
// СПИСОК
// ==========================================
.cashback-items-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 0 16px 16px;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ
// ==========================================
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $admin-text-muted;

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: $admin-card-bg;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 16px;
        color: $admin-gold;
        border: 1px solid $admin-border;
    }

    h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: $admin-text;
        margin-bottom: 8px;
    }

    p {
        font-size: 0.9rem;
        margin-bottom: 20px;
        line-height: 1.4;
    }
}

// ==========================================
// ПАГИНАЦИЯ
// ==========================================
.pagination-wrapper {
    padding: 20px 16px;
    display: flex;
    justify-content: center;
}

// ==========================================
// АДАПТИВ
// ==========================================
@media (min-width: 768px) {
    .cashback-items-list,
    .pagination-wrapper {
        max-width: 700px;
        margin: 0 auto;
        padding-left: 0;
        padding-right: 0;
    }
}
</style>
