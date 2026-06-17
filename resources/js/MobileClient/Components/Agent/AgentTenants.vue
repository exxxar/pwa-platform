<template>
    <div class="tab-content fade-in">
        <div class="section-header-page">
            <div>
                <h2>Мои приложения</h2>
                <p>Управление созданными приложениями для клиентов</p>
            </div>
            <button class="btn-primary-modern" @click="$emit('create-bot')"><i class="fa-solid fa-plus"></i> Создать
                приложение
            </button>
        </div>

        <div class="filters-bar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" v-model="search" placeholder="Поиск по названию или клиенту...">
            </div>
            <div class="filter-chips">
                <button v-for="filter in filters" :key="filter.id" class="filter-chip"
                        :class="{ 'is-active': activeFilter === filter.id }" @click="activeFilter = filter.id">
                    {{ filter.label }} <span class="chip-count">{{ filter.count }}</span>
                </button>
            </div>
        </div>

        <div v-if="filteredTenants.length === 0" class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-robot"></i></div>
            <h3>Приложения не найдены</h3>
            <button class="btn-primary-modern" @click="$emit('create-tenant')"><i class="fa-solid fa-plus"></i> Создать
                приложение
            </button>
        </div>

        <div v-else class="tenants-grid">
            <div v-for="tenant in filteredTenants" :key="tenants.id" class="bot-card" :class="'status-' + tenants.status">
                <div class="bot-header">
                    <div class="bot-avatar" :style="{ background: tenants.color }"><i :class="tenants.icon"></i></div>
                    <div class="bot-status-badge" :class="'status-' + tenants.status">{{ tenants.statusText }}</div>
                </div>
                <div class="bot-info">
                    <h4 class="bot-name">{{ tenants.name }}</h4>
                    <p class="bot-client"><i class="fa-solid fa-user"></i> {{ tenants.client_name || 'Не назначен' }}</p>
                </div>
                <div class="bot-stats">
                    <div class="bot-stat"><span class="stat-label">Создан</span><span
                        class="stat-value">{{ tenants.created_at }}</span></div>
                    <div class="bot-stat"><span class="stat-label">Доход</span><span
                        class="stat-value">{{ formatPrice(tenants.earnings) }}</span></div>
                </div>
                <div class="bot-actions">
                    <button class="action-btn" @click="$emit('edit-bot', bot)" title="Настроить"><i
                        class="fa-solid fa-gear"></i></button>
                    <button class="action-btn" @click="$emit('view-bot', bot)" title="Просмотр"><i
                        class="fa-solid fa-eye"></i></button>
                    <button class="action-btn danger" @click="$emit('delete-bot', tenants.id)" title="Удалить"><i
                        class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "Agenttenants",
    props: {tenants: Array},
    emits: ['create-bot', 'edit-bot', 'view-bot', 'delete-bot'],
    data() {
        return {
            search: '',
            activeFilter: 'all',
            filters: [
                {id: 'all', label: 'Все', count: 0},
                {id: 'active', label: 'Активные', count: 0},
                {id: 'draft', label: 'Черновики', count: 0},
            ]
        };
    },
    computed: {
        filteredTenants() {
            let result = this.tenants;
            if (this.activeFilter !== 'all') result = result.filter(b => b.status === this.activeFilter);
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                result = result.filter(b => b.name.toLowerCase().includes(q) || b.client_name?.toLowerCase().includes(q));
            }
            // Обновляем счетчики фильтров
            this.filters[0].count = this.tenants.length;
            this.filters[1].count = this.tenants.filter(b => b.status === 'active').length;
            this.filters[2].count = this.tenants.filter(b => b.status === 'draft').length;
            return result;
        }
    },
    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(price || 0);
        }
    }
};
</script>

<style lang="scss" scoped>
/* Вставьте сюда стили для .section-header-page, .filters-bar, .tenants-grid, .bot-card из предыдущего ответа */
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

.section-header-page {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.section-header-page h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: #1f2937;
}

.section-header-page p {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

.btn-primary-modern {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}

.filters-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 200px;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-box input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
}

.filter-chips {
    display: flex;
    gap: 6px;
}

.filter-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    color: #6b7280;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
}

.filter-chip.is-active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.chip-count {
    padding: 1px 6px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 700;
}

.is-active .chip-count {
    background: rgba(255, 255, 255, 0.25);
}

.tenants-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.bot-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 18px;
    transition: 0.2s;
    display: flex;
    flex-direction: column;
}

.bot-card:hover {
    border-color: rgba(59, 130, 246, 0.3);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

.bot-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.bot-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.bot-status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}

.bot-status-badge.status-active {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.bot-status-badge.status-draft {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.bot-name {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.bot-client {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.bot-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 10px;
    margin-bottom: 14px;
}

.stat-label {
    font-size: 0.7rem;
    color: #6b7280;
    text-transform: uppercase;
}

.stat-value {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1f2937;
}

.bot-actions {
    display: flex;
    gap: 6px;
}

.action-btn {
    flex: 1;
    padding: 8px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #6b7280;
    cursor: pointer;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.action-btn.danger:hover {
    background: #ef4444;
    border-color: #ef4444;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 16px;
    color: #3b82f6;
}
</style>
