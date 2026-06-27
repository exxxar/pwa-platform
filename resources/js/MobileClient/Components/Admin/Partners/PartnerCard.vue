<template>
    <div class="partner-card-content">
        <div class="card-header">
            <div class="partner-avatar" :style="avatarStyle">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="partner-info">
                <h4 class="partner-title">{{ partner.title }}</h4>
                <div class="partner-meta">
                    <span class="meta-item">
                        <i class="fa-solid fa-cube"></i>
                        {{ partner.products_count || 0 }} товаров
                    </span>
                    <span v-if="partner.link" class="meta-item telegram">
                        <i class="fa-brands fa-telegram"></i>
                        Связь
                    </span>
                </div>
            </div>
            <div class="status-badge" :class="partner.is_active ? 'active' : 'inactive'">
                <i :class="partner.is_active ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                <span>{{ partner.is_active ? 'Активен' : 'Неактивен' }}</span>
            </div>
        </div>

        <div class="card-actions">
            <button
                class="action-btn"
                @click="$emit('edit', partner)"
                title="Редактировать"
            >
                <i class="fa-solid fa-pen"></i>
                <span>Редактировать</span>
            </button>
            <button
                class="action-btn"
                @click="$emit('products', partner)"
                title="Товары"
            >
                <i class="fa-solid fa-store"></i>
                <span>Товары</span>
            </button>
            <button
                class="action-btn toggle"
                :class="{ 'active': partner.is_active }"
                @click="$emit('toggle-active', partner)"
                :disabled="isPartnerLoading(partner.id)"
                title="Изменить статус"
            >
                <i v-if="isPartnerLoading(partner.id)" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else :class="partner.is_active ? 'fa-solid fa-toggle-on' : 'fa-solid fa-toggle-off'"></i>
                <span>{{ partner.is_active ? 'Деактивировать' : 'Активировать' }}</span>
            </button>
            <button
                class="action-btn danger"
                @click="$emit('remove', partner)"
                title="Удалить"
            >
                <i class="fa-solid fa-trash"></i>
                <span>Удалить</span>
            </button>
        </div>
    </div>
</template>

<script>
import { usePartners } from '@/MobileClient/Composables/usePartners.js'

export default {
    name: 'PartnerCard',

    props: {
        partner: {
            type: Object,
            required: true,
        },
    },

    emits: ['edit', 'products', 'remove', 'toggle-active'],

    setup() {
        const partners = usePartners()
        return {
            isPartnerLoading: partners.isPartnerLoading,
            loadProducts: partners.loadProductsByCategory
        }
    },
    mounted() {
        console.log('🔍 Partner object:', this.partner)
        console.log('🔍 Keys:', Object.keys(this.partner))
        console.log('🔍 tenant_partner_id:', this.partner.tenant_partner_id)
        console.log('🔍 id:', this.partner.id)

        this.extra_charge = this.partner.extra_charge || 0
        this.loadProducts({
            partner_id:  this.partner.id
        })
    },
    computed: {
        avatarStyle() {
            const colors = [
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            ]
            const index = this.partner.id % colors.length
            return { background: colors[index] }
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
$admin-warning: #d09121;
$admin-success: #10b981;
$admin-danger: #ef4444;

.partner-card-content {
    background: $admin-card-bg;
    border: 1px solid $admin-border;
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.2s;

    &:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }
}

.card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-bottom: 1px solid $admin-border;
}

.partner-avatar {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.4rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.partner-info {
    flex: 1;
    min-width: 0;
}

.partner-title {
    font-size: 1rem;
    font-weight: 700;
    color: $admin-text;
    margin: 0 0 6px 0;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.partner-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.8rem;
    color: $admin-text-muted;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;

    i {
        font-size: 0.75rem;
    }

    &.telegram {
        color: #0088cc;
    }
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;

    &.active {
        background: rgba($admin-success, 0.1);
        color: $admin-success;
    }

    &.inactive {
        background: rgba($admin-text-muted, 0.1);
        color: $admin-text-muted;
    }

    i {
        font-size: 0.7rem;
    }
}

.card-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    padding: 12px;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    background: $admin-bg;
    border: 1px solid $admin-border;
    border-radius: 10px;
    color: $admin-text;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;

    i {
        font-size: 0.9rem;
    }

    &:hover {
        background: $admin-primary;
        border-color: $admin-primary;
        color: white;
    }

    &:active {
        transform: scale(0.95);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    &.toggle {
        &.active {
            background: rgba($admin-success, 0.1);
            border-color: $admin-success;
            color: $admin-success;

            &:hover {
                background: $admin-success;
                color: white;
            }
        }

        &:not(.active) {
            background: rgba($admin-warning, 0.1);
            border-color: $admin-warning;
            color: #d97706;

            &:hover {
                background: $admin-warning;
                color: white;
            }
        }
    }

    &.danger {
        background: rgba($admin-danger, 0.1);
        border-color: $admin-danger;
        color: $admin-danger;

        &:hover {
            background: $admin-danger;
            color: white;
        }
    }
}

@media (max-width: 480px) {
    .card-header {
        flex-wrap: wrap;
    }

    .status-badge {
        width: 100%;
        justify-content: center;
        margin-top: 8px;
    }

    .action-btn {
        span {
            display: none;
        }

        i {
            font-size: 1.1rem;
        }
    }
}
</style>
