import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

// Базовый префикс для всех запросов агента
const API_BASE = '/agent';

export const useAgentStore = defineStore('agent', () => {
    // ==========================================
    // STATE (Состояние)
    // ==========================================
    const agent = ref({
        id: null,
        name: '',
        status: 'pending', // pending, active, suspended
        verification_status: 'not_started', // not_started, partial, verified
        balance: 0,
        pending_balance: 0,
        total_earned: 0,
        tenant_count: 0,
        clients_count: 0,
        referrals_count: 0,
        referral_code: '',
        referral_url: '',
        profile: {
            phone: '',
            email: '',
            legal_type: 'self_employed',
            inn: '',
            ogrn: '',
            bank_account: '',
            bik: '',
            bank_name: '',
        }
    });

    const tenants = ref([]);
    const clients = ref([]);
    const transactions = ref([]);
    const payouts = ref([]);
    const invoices = ref([]);

    // 🆕 Флаг наличия профиля (для показа экрана онбординга)
    const hasProfile = ref(true);

    const documents = ref({
        list: [],
        progress: { uploaded: 0, required: 0, percent: 0 },
        status: 'not_started'
    });

    const marketingCategories = ref([]);
    const referralStats = ref({ total_clicks: 0, conversions: 0, total_bonus: 0 });

    // UI States
    const isLoading = ref(false);
    const error = ref(null);
    const notifications = ref([]);

    // ==========================================
    // GETTERS (Вычисляемые свойства)
    // ==========================================
    const isVerified = computed(() => agent.value.verification_status === 'verified');
    const canOperate = computed(() => agent.value.status === 'active' && isVerified.value);
    const recentTransactions = computed(() => transactions.value.slice(0, 5));

    // ==========================================
    // ACTIONS (Действия)
    // ==========================================

    /**
     * Вспомогательный метод для добавления уведомлений
     */
    const addNotification = (type, message) => {
        const id = Date.now();
        notifications.value.push({ id, type, message });
        setTimeout(() => {
            notifications.value = notifications.value.filter(n => n.id !== id);
        }, 5000);
    };

    /**
     * 🆕 ОБЪЕДИНЕННЫЙ МЕТОД: Загрузка первичных данных с проверкой наличия профиля
     */
    const fetchInitialData = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            // 1. Сначала проверяем, есть ли профиль вообще
            const profileRes = await axios.get(`${API_BASE}/profile`);

            // Если бэкенд вернул пустые данные (или мы ожидаем null при отсутствии)
            if (!profileRes.data || !profileRes.data.data) {
                hasProfile.value = false;
                isLoading.value = false;
                return; // Прерываем выполнение, на фронте покажется AgentOnboarding
            }

            // 2. Если профиль есть, загружаем все остальные данные параллельно для скорости
            hasProfile.value = true;

            const [dashboardRes, docsRes, tenantsRes, clientsRes, marketingRes, referralsRes] = await Promise.all([
                axios.get(`${API_BASE}/dashboard`),
                axios.get(`${API_BASE}/documents`),
                axios.get(`${API_BASE}/tenants`),
                axios.get(`${API_BASE}/clients`),
                axios.get(`${API_BASE}/marketing`),
                axios.get(`${API_BASE}/referrals/link`),
            ]);

            // Объединяем данные профиля и дашборда
            agent.value = {
                ...agent.value,
                ...profileRes.data.data,
                ...dashboardRes.data.data,
                referral_code: referralsRes.data.data?.referral_code || '',
                referral_url: referralsRes.data.data?.referral_url || '',
            };

            documents.value = docsRes.data.data || documents.value;
            tenants.value = tenantsRes.data.data || [];
            clients.value = clientsRes.data.data || [];
            marketingCategories.value = marketingRes.data.data || [];

        } catch (err) {
            // 🆕 Ловим именно 404, который бэкенд должен отдавать, если agentProfile не найден
            if (err.response?.status === 404) {
                hasProfile.value = false;
                isLoading.value = false;
                return;
            }

            error.value = err.response?.data?.message || 'Ошибка загрузки данных';
            addNotification('error', error.value);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * 🆕 Создание профиля (для экрана онбординга)
     */
    const createProfile = async (payload) => {
        isLoading.value = true;
        try {
            const response = await axios.post(`${API_BASE}/profile`, payload);

            hasProfile.value = true;
            agent.value = { ...agent.value, ...response.data.data };

            addNotification('success', response.data.message || 'Профиль успешно создан!');

            // После успешного создания сразу подгружаем остальные данные дашборда
            await fetchInitialData();

            return true;
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка создания профиля';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Обновление существующего профиля
     */
    const updateProfile = async (payload) => {
        isLoading.value = true;
        try {
            const response = await axios.put(`${API_BASE}/profile`, payload);
            agent.value = { ...agent.value, ...response.data.data };
            addNotification('success', response.data.message || 'Профиль обновлен');
            return true;
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка обновления профиля';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Загрузка документа (использует FormData)
     */
    const uploadDocument = async (documentTypeId, file) => {
        isLoading.value = true;
        try {
            const formData = new FormData();
            formData.append('document_type_id', documentTypeId);
            formData.append('file', file);

            const response = await axios.post(`${API_BASE}/documents/upload`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            // После успешной загрузки обновляем список документов и статус
            await fetchDocuments();
            addNotification('success', response.data.message);
            return true;
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка загрузки документа';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Получение актуального списка документов (для обновления после загрузки)
     */
    const fetchDocuments = async () => {
        try {
            const response = await axios.get(`${API_BASE}/documents`);
            documents.value = response.data.data;
        } catch (err) {
            console.error('Failed to refresh documents', err);
        }
    };

    /**
     * Запрос на выплату
     */
    const requestPayout = async (amount, comment = '') => {
        isLoading.value = true;
        try {
            const response = await axios.post(`${API_BASE}/finance/payout`, { amount, comment });

            // Локально обновляем балансы для мгновенного UI-отклика (оптимистичный UI)
            agent.value.balance -= amount;
            agent.value.pending_balance += amount;

            addNotification('success', response.data.message);
            return response.data.data;
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка создания заявки на выплату';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Загрузка транзакций (с пагинацией и фильтрами)
     */
    const fetchTransactions = async (params = { page: 1, per_page: 20 }) => {
        try {
            const response = await axios.get(`${API_BASE}/finance/transactions`, { params });
            transactions.value = response.data.data;
            return response.data.meta; // Возвращаем мета-данные для пагинации на фронте
        } catch (err) {
            addNotification('error', 'Не удалось загрузить историю операций');
        }
    };

    /**
     * Создание клиента
     */
    const createClient = async (payload) => {
        isLoading.value = true;
        try {
            const response = await axios.post(`${API_BASE}/clients`, payload);
            clients.value.unshift(response.data.data);
            agent.value.clients_count += 1;
            addNotification('success', response.data.message);
            return response.data.data;
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка добавления клиента';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Удаление (деактивация) клиента
     */
    const deleteClient = async (clientId) => {
        isLoading.value = true;
        try {
            const response = await axios.delete(`${API_BASE}/clients/${clientId}`);
            clients.value = clients.value.filter(c => c.id !== clientId);
            agent.value.clients_count = Math.max(0, agent.value.clients_count - 1);
            addNotification('success', response.data.message);
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка удаления клиента';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Создание счета
     */
    const createInvoice = async (payload) => {
        isLoading.value = true;
        try {
            const response = await axios.post(`${API_BASE}/invoices`, payload);
            invoices.value.unshift(response.data.data);
            addNotification('success', response.data.message);
            return response.data.data;
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка создания счета';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Отправка счета клиенту
     */
    const sendInvoice = async (invoiceId) => {
        isLoading.value = true;
        try {
            const response = await axios.post(`${API_BASE}/invoices/${invoiceId}/send`);
            // Обновляем статус в локальном массиве
            const idx = invoices.value.findIndex(inv => inv.id === invoiceId);
            if (idx !== -1) {
                invoices.value[idx] = response.data.data;
            }
            addNotification('success', response.data.message);
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка отправки счета';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    const createAndSendInvoice = async (payload) => {
        isLoading.value = true;
        try {
            const response = await axios.post(`${API_BASE}/invoices/create-and-send`, payload);
            addNotification('success', response.data.message || 'Счёт создан и отправлен клиенту');
            return response.data.data; // Вернет { order_id, payment_url }
        } catch (err) {
            const msg = err.response?.data?.message || 'Ошибка создания и отправки счёта';
            addNotification('error', msg);
            throw new Error(msg);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Трекинг скачивания маркетингового материала
     */
    const downloadMarketingMaterial = async (materialId) => {
        try {
            const response = await axios.post(`${API_BASE}/marketing/${materialId}/download`);
            // Инициируем реальное скачивание файла браузером
            window.open(response.data.data.download_url, '_blank');
            addNotification('success', response.data.message);
        } catch (err) {
            addNotification('error', 'Ошибка при скачивании материала');
        }
    };

    // ==========================================
    // RETURN (Экспорт в компоненты)
    // ==========================================
    return {
        // State
        agent,
        tenants,
        clients,
        transactions,
        payouts,
        invoices,
        documents,
        marketingCategories,
        referralStats,
        isLoading,
        error,
        notifications,
        hasProfile, // 🆕 Важно для онбординга

        // Getters
        isVerified,
        canOperate,
        recentTransactions,

        // Actions
        fetchInitialData,
        createProfile, // 🆕 Важно для онбординга
        updateProfile,
        uploadDocument,
        fetchDocuments,
        requestPayout,
        fetchTransactions,
        createClient,
        deleteClient,
        createInvoice,
        sendInvoice,
        createAndSendInvoice,
        downloadMarketingMaterial,
        addNotification
    };
});
