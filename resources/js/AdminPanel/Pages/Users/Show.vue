<template>
    <div class="user-show">
        <div class="back-link">
            <router-link :to="{ name: 'admin.users.index' }">← Назад к списку</router-link>
        </div>

        <div v-if="loading" class="loading-state">⏳ Загрузка...</div>

        <div v-else-if="user" class="profile-card">
            <div class="profile-header">
                <div class="avatar-lg">{{ initials(user.name) }}</div>
                <div class="profile-title">
                    <h1>{{ user.name }}</h1>
                    <div class="badges-row">
                        <span class="status-badge" :class="user.is_active ? 'active' : 'blocked'">
                            {{ user.is_active ? 'Активен' : 'Заблокирован' }}
                        </span>
                        <span v-if="user.is_vip" class="vip-badge">⭐ VIP</span>
                        <span class="tenant-badge">{{ user.tenant?.name || 'Без тенанта' }}</span>
                    </div>
                </div>
                <div class="profile-actions">
                    <button class="btn-secondary" @click="toggleVip">
                        {{ user.is_vip ? 'Убрать VIP' : 'Выдать VIP' }} ⭐
                    </button>
                    <button class="btn-danger" v-if="user.is_active" @click="toggleActive">🚫 Заблокировать</button>
                    <button class="btn-primary" v-else @click="toggleActive">✅ Разблокировать</button>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h3>📇 Контакты</h3>
                    <div class="info-row"><span class="label">Email:</span><span>{{ user.email || '—' }}</span></div>
                    <div class="info-row"><span class="label">Телефон:</span><span class="mono">{{ user.phone || '—' }}</span></div>
                    <div class="info-row"><span class="label">Реферальный код:</span><span class="mono">{{ user.referral_code || '—' }}</span></div>
                </div>

                <div class="info-section">
                    <h3>💰 Финансы</h3>
                    <div class="info-row"><span class="label">Кэшбэк-баланс:</span><span class="mono">{{ user.balance ?? 0 }}</span></div>
                    <div class="info-row"><span class="label">Заказов:</span><span class="mono">{{ user.orders_count ?? 0 }}</span></div>
                    <div class="info-row"><span class="label">Сумма заказов:</span><span class="mono">{{ formatCurrency(user.orders_sum ?? 0) }}</span></div>
                </div>

                <div class="info-section">
                    <h3>🧾 Системное</h3>
                    <div class="info-row"><span class="label">UUID:</span><span class="mono small">{{ user.uuid }}</span></div>
                    <div class="info-row"><span class="label">Тенант ID:</span><span class="mono">{{ user.tenant_id }}</span></div>
                    <div class="info-row"><span class="label">Регистрация:</span><span>{{ formatDate(user.created_at) }}</span></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '../../composables/useApi'
import { useNotifications } from '../../composables/useNotifications'

const props = defineProps({ id: [String, Number] })

const api = useApi()
const notifications = useNotifications()
const user = ref(null)
const loading = ref(true)

const fetchUser = async () => {
    loading.value = true
    try {
        const response = await api.get(`/admin/users/${props.id}`)
        user.value = response.data ?? response
    } catch (e) {
        notifications.error('Пользователь не найден')
    } finally {
        loading.value = false
    }
}

const patchUser = async (payload, text) => {
    try {
        await api.patch(`/admin/users/${props.id}`, payload)
        Object.assign(user.value, payload)
        notifications.success(text)
    } catch (e) {
        notifications.error('Не удалось обновить пользователя')
    }
}

const toggleVip = () => patchUser({ is_vip: !user.value.is_vip }, 'VIP-статус обновлён')
const toggleActive = () => patchUser({ is_active: !user.value.is_active }, 'Статус блокировки обновлён')

const initials = (name = '') => name.trim().split(/\s+/).slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('') || '?'
const formatDate = (d) => d ? new Date(d).toLocaleString('ru-RU') : '—'
const formatCurrency = (n) => new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(n)

onMounted(fetchUser)
</script>

<style scoped>
.user-show { padding: 24px; }
.back-link { margin-bottom: 20px; }
.back-link a { color: #667eea; text-decoration: none; font-size: 14px; }
.loading-state { text-align: center; padding: 60px; color: #999; }

.profile-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.profile-header { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; padding-bottom: 24px; border-bottom: 1px solid #eee; margin-bottom: 24px; }
.avatar-lg { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; }
.profile-title { flex: 1; }
.profile-title h1 { margin: 0 0 8px; font-size: 24px; }
.badges-row { display: flex; gap: 8px; flex-wrap: wrap; }
.profile-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.blocked { background: #f8d7da; color: #721c24; }
.vip-badge { padding: 4px 10px; background: #fff3cd; color: #856404; border-radius: 12px; font-size: 12px; font-weight: 600; }
.tenant-badge { padding: 4px 10px; background: #eef2ff; color: #4c5fd5; border-radius: 12px; font-size: 12px; font-weight: 500; }

.btn-primary, .btn-danger, .btn-secondary { padding: 10px 20px; border: none; border-radius: 10px; font-size: 14px; font-weight: 500; cursor: pointer; }
.btn-primary { background: #28a745; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-secondary { background: #f0f0f0; color: #333; }

.info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; }
.info-section h3 { margin: 0 0 12px; font-size: 15px; }
.info-row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
.label { color: #888; }
.mono { font-family: 'SF Mono', Monaco, monospace; font-size: 13px; }
.mono.small { font-size: 11px; word-break: break-all; }

@media (max-width: 900px) { .info-grid { grid-template-columns: 1fr; } }
</style>
