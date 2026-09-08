<template>
    <div class="users-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">👥 Пользователи</h1>
                <p class="page-subtitle">Клиенты платформы по всем тенантам</p>
            </div>
        </div>

        <!-- Мини-статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <span class="stat-label">Всего</span>
                    <span class="stat-value">{{ stats.total ?? 0 }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-label">Активных</span>
                    <span class="stat-value">{{ stats.active ?? 0 }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-info">
                    <span class="stat-label">VIP</span>
                    <span class="stat-value">{{ stats.vip ?? 0 }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🆕</div>
                <div class="stat-info">
                    <span class="stat-label">Новых сегодня</span>
                    <span class="stat-value">{{ stats.today ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="filters-bar">
            <div class="filter-group grow">
                <input
                    v-model="filters.search"
                    type="text"
                    class="filter-input"
                    placeholder="🔍 Поиск по имени, email, телефону..."
                >
            </div>
            <div class="filter-group">
                <select v-model="filters.tenant_id" class="filter-select">
                    <option value="">Все тенанты</option>
                    <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </div>
            <div class="filter-group">
                <select v-model="filters.is_active" class="filter-select">
                    <option value="">Любой статус</option>
                    <option value="1">Активные</option>
                    <option value="0">Заблокированные</option>
                </select>
            </div>
            <div class="filter-group">
                <select v-model="filters.is_vip" class="filter-select">
                    <option value="">Все</option>
                    <option value="1">Только VIP</option>
                    <option value="0">Не VIP</option>
                </select>
            </div>
            <button class="btn-reset" @click="resetFilters">Сбросить</button>
        </div>

        <!-- Таблица -->
        <div class="table-container">
            <div v-if="loading" class="loading-state">⏳ Загрузка...</div>

            <table v-else class="users-table">
                <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Телефон</th>
                    <th>Тенант</th>
                    <th>Статус</th>
                    <th>VIP</th>
                    <th>Кэшбэк</th>
                    <th>Заказов</th>
                    <th>Регистрация</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar">{{ initials(user.name) }}</div>
                            <div class="user-meta">
                                <router-link
                                    :to="{ name: 'admin.users.show', params: { id: user.id } }"
                                    class="user-name"
                                >
                                    {{ user.name }}
                                </router-link>
                                <span class="user-email">{{ user.email }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="mono">{{ user.phone || '—' }}</td>
                    <td>
                        <span class="tenant-badge">{{ user.tenant?.name || '—' }}</span>
                    </td>
                    <td>
                            <span class="status-badge" :class="user.is_active ? 'active' : 'blocked'">
                                {{ user.is_active ? 'Активен' : 'Заблокирован' }}
                            </span>
                    </td>
                    <td>
                        <span v-if="user.is_vip" class="vip-badge">⭐ VIP</span>
                        <span v-else class="muted">—</span>
                    </td>
                    <td class="mono">{{ user.balance ?? 0 }}</td>
                    <td class="mono">{{ user.orders_count ?? 0 }}</td>
                    <td class="date-cell">{{ formatDate(user.created_at) }}</td>
                    <td>
                        <div class="actions-cell">
                            <router-link
                                :to="{ name: 'admin.users.show', params: { id: user.id } }"
                                class="btn-icon"
                                title="Профиль"
                            >👁️</router-link>
                            <button
                                class="btn-icon"
                                :title="user.is_vip ? 'Убрать VIP' : 'Сделать VIP'"
                                @click="toggleVip(user)"
                            >⭐</button>
                            <button
                                class="btn-icon"
                                :title="user.is_active ? 'Заблокировать' : 'Разблокировать'"
                                @click="toggleActive(user)"
                            >{{ user.is_active ? '🚫' : '✅' }}</button>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="!loading && users.length === 0" class="empty-state">
                <span class="empty-icon">📭</span>
                <p>Пользователи не найдены</p>
            </div>
        </div>

        <!-- Пагинация -->
        <div class="pagination" v-if="meta.last_page > 1">
            <button class="page-btn" :disabled="meta.current_page === 1" @click="goPage(meta.current_page - 1)">← Назад</button>
            <span class="page-info">Страница {{ meta.current_page }} из {{ meta.last_page }} (всего {{ meta.total }})</span>
            <button class="page-btn" :disabled="meta.current_page === meta.last_page" @click="goPage(meta.current_page + 1)">Вперёд →</button>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useApi } from '../../composables/useApi'
import { useNotifications } from '../../composables/useNotifications'

const api = useApi()
const notifications = useNotifications()

const users = ref([])
const tenants = ref([])
const stats = ref({})
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)

const filters = reactive({
    search: '',
    tenant_id: '',
    is_active: '',
    is_vip: '',
    page: 1,
})

const fetchUsers = async () => {
    loading.value = true
    try {
        const response = await api.get('/admin/users', { params: filters })
        const payload = response.data ?? response
        users.value = payload.items ?? payload.data ?? []
        meta.value = payload.meta ?? meta.value
        stats.value = payload.stats ?? stats.value
    } catch (e) {
        console.error(e)
        notifications.error('Не удалось загрузить пользователей')
    } finally {
        loading.value = false
    }
}

const fetchTenants = async () => {
    try {
        const response = await api.get('/admin/tenants', { params: { per_page: 100 } })
        const payload = response.data ?? response
        tenants.value = payload.data ?? payload.items ?? []
    } catch (e) {
        tenants.value = []
    }
}

// Debounce поиска
let searchTimer = null
watch(() => filters.search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        filters.page = 1
        fetchUsers()
    }, 400)
})

watch(() => [filters.tenant_id, filters.is_active, filters.is_vip], () => {
    filters.page = 1
    fetchUsers()
})

const goPage = (page) => {
    filters.page = page
    fetchUsers()
}

const resetFilters = () => {
    Object.assign(filters, { search: '', tenant_id: '', is_active: '', is_vip: '', page: 1 })
    fetchUsers()
}

const patchUser = async (user, payload, successText) => {
    try {
        await api.patch(`/admin/users/${user.id}`, payload)
        Object.assign(user, payload)
        notifications.success(successText)
    } catch (e) {
        notifications.error('Не удалось обновить пользователя')
    }
}

const toggleVip = (user) => patchUser(user, { is_vip: !user.is_vip }, user.is_vip ? 'VIP снят' : 'VIP выдан ⭐')
const toggleActive = (user) => patchUser(user, { is_active: !user.is_active }, user.is_active ? 'Пользователь заблокирован' : 'Пользователь разблокирован')

const initials = (name = '') => name.trim().split(/\s+/).slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('') || '?'
const formatDate = (d) => d ? new Date(d).toLocaleDateString('ru-RU') : '—'

onMounted(() => {
    fetchUsers()
    fetchTenants()
})
</script>

<style scoped>
.users-page { padding: 24px; }
.page-header { margin-bottom: 24px; }
.page-title { margin: 0 0 4px; font-size: 24px; }
.page-subtitle { margin: 0; color: #666; }

.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.stat-icon { font-size: 32px; }
.stat-label { font-size: 12px; color: #888; text-transform: uppercase; display: block; }
.stat-value { font-size: 22px; font-weight: 600; }

.filters-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; background: #fff; padding: 16px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.filter-group { min-width: 150px; }
.filter-group.grow { flex: 1; }
.filter-input, .filter-select { width: 100%; padding: 10px 14px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; background: #fafafa; }
.filter-input:focus, .filter-select:focus { outline: none; border-color: #667eea; background: #fff; }
.btn-reset { padding: 10px 16px; background: #f0f0f0; border: none; border-radius: 8px; cursor: pointer; }
.btn-reset:hover { background: #e0e0e0; }

.table-container { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.users-table { width: 100%; border-collapse: collapse; }
.users-table th { background: #f8f9fa; padding: 14px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: #666; border-bottom: 2px solid #e9ecef; }
.users-table td { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: middle; }
.users-table tbody tr:hover { background: #f8f9fa; }

.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; flex-shrink: 0; }
.user-meta { display: flex; flex-direction: column; }
.user-name { color: #1a202c; font-weight: 600; text-decoration: none; }
.user-name:hover { color: #667eea; }
.user-email { font-size: 12px; color: #888; }

.mono { font-family: 'SF Mono', Monaco, monospace; font-size: 13px; color: #555; }
.muted { color: #bbb; }
.date-cell { color: #666; font-size: 13px; white-space: nowrap; }

.tenant-badge { padding: 4px 10px; background: #eef2ff; color: #4c5fd5; border-radius: 12px; font-size: 12px; font-weight: 500; }
.status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.blocked { background: #f8d7da; color: #721c24; }
.vip-badge { padding: 4px 10px; background: #fff3cd; color: #856404; border-radius: 12px; font-size: 12px; font-weight: 600; }

.actions-cell { display: flex; gap: 6px; }
.btn-icon { width: 32px; height: 32px; border: none; background: #f0f0f0; border-radius: 8px; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: background 0.2s; }
.btn-icon:hover { background: #e0e0e0; }

.loading-state, .empty-state { text-align: center; padding: 60px 20px; color: #999; }
.empty-icon { font-size: 48px; display: block; margin-bottom: 12px; }

.pagination { display: flex; justify-content: center; align-items: center; gap: 16px; margin-top: 24px; }
.page-btn { padding: 10px 20px; border: 1px solid #e0e0e0; background: #fff; border-radius: 8px; cursor: pointer; }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.page-info { font-size: 14px; color: #666; }
</style>
