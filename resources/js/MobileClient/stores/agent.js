import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAgentStore = defineStore('agent', () => {
    // --- STATE (Состояние) ---
    const agent = ref({
        name: 'Александр Петров',
        status: 'verified',
        balance: 45200,
        pending_balance: 12000,
        total_earned: 187500,
        tenant_count: 12,
        clients_count: 9,
        referrals_count: 156,
        profile: {
            name: 'Александр Петров',
            phone: '+7 (999) 000-00-00',
            email: 'agent@example.com',
            legal_type: 'self_employed',
            inn: '123456789012',
            ogrn: '',
            bank_account: '40817810000000000000',
            bik: '044525225',
            bank_name: 'ПАО Сбербанк',
            documents: []
        }
    });

    const tenants = ref([
        {
            id: 1, name: 'Бот для кофейни "Арома"', client_name: 'Иван Иванов',
            status: 'active', statusText: 'Активен', icon: 'fa-solid fa-mug-hot',
            color: 'linear-gradient(135deg, #8b4513 0%, #d2691e 100%)',
            created_at: '15.06.2026', earnings: 15000
        },
        {
            id: 2, name: 'Магазин цветов "Флора"', client_name: 'Мария Сидорова',
            status: 'active', statusText: 'Активен', icon: 'fa-solid fa-seedling',
            color: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            created_at: '10.06.2026', earnings: 22000
        },
        {
            id: 3, name: 'Салон красоты "Гламур"', client_name: 'Елена Козлова',
            status: 'draft', statusText: 'Черновик', icon: 'fa-solid fa-spa',
            color: 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)',
            created_at: '05.06.2026', earnings: 0
        }
    ]);

    const transactions = ref([
        { id: 1, type: 'income', icon: 'fa-solid fa-plus', title: 'Оплата от Иванова И.И.', date: '15 июня 2026', amount: 15000, amountClass: 'income' },
        { id: 2, type: 'payout', icon: 'fa-solid fa-arrow-up', title: 'Вывод на карту', date: '12 июня 2026', amount: 30000, amountClass: 'expense' }
    ]);

    const documents = ref([
        { id: 1, title: 'Паспорт', description: 'Разворот с фото и прописка', icon: 'fa-solid fa-id-card', required: true, uploaded: true, uploaded_at: '10.05.2026' },
        { id: 2, title: 'СНИЛС', description: 'Страховое свидетельство', icon: 'fa-solid fa-shield-halved', required: true, uploaded: false }
    ]);

    const marketingCategories = ref([
        {
            id: 1, title: 'Презентации', description: 'Материалы для встреч',
            icon: 'fa-regular fa-file-powerpoint', color: 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
            materials: [{ id: 101, title: 'Презентация продукта', size: '2.4 МБ', url: '/marketing/presentation.pdf' }]
        }
    ]);

    const verificationStatus = ref('partial');
    const notifications = ref([
        { id: 1, type: 'success', icon: 'fa-solid fa-circle-check', text: 'Документы успешно верифицированы' }
    ]);

    // --- GETTERS (Вычисляемые свойства) ---
    const recentActivities = computed(() => transactions.value.slice(0, 5));
    const uploadedDocumentsCount = computed(() => documents.value.filter(d => d.uploaded).length);
    const requiredDocumentsCount = computed(() => documents.value.filter(d => d.required).length);

    // --- ACTIONS (Действия с имитацией бэкенда) ---
    const fetchAgentData = async () => {
        await new Promise(resolve => setTimeout(resolve, 600)); // Имитация задержки сети
    };

    const updateProfile = async (payload) => {
        await new Promise(resolve => setTimeout(resolve, 600));
        agent.value.profile = { ...agent.value.profile, ...payload };
        agent.value.name = payload.name || agent.value.name;
        return true;
    };

    const createTenant = async (payload) => {
        await new Promise(resolve => setTimeout(resolve, 600));
        const newTenant = {
            id: Date.now(), name: payload.name || 'Новое приложение', client_name: payload.client_name || 'Не назначен',
            status: 'draft', statusText: 'Черновик', icon: 'fa-solid fa-robot',
            color: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
            created_at: new Date().toLocaleDateString('ru-RU'), earnings: 0
        };
        tenants.value.unshift(newTenant);
        agent.value.tenant_count = tenants.value.length;
        return newTenant;
    };

    const deleteTenant = async (id) => {
        await new Promise(resolve => setTimeout(resolve, 400));
        tenants.value = tenants.value.filter(t => t.id !== id);
        agent.value.tenant_count = tenants.value.length;
    };

    const requestPayout = async (amount) => {
        await new Promise(resolve => setTimeout(resolve, 800));
        if (amount > agent.value.balance) throw new Error('Недостаточно средств на балансе');

        agent.value.balance -= amount;
        agent.value.pending_balance += amount;
        transactions.value.unshift({
            id: Date.now(), type: 'payout', icon: 'fa-solid fa-arrow-up',
            title: 'Заявка на вывод средств',
            date: new Date().toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }),
            amount: amount, amountClass: 'expense'
        });
    };

    const createInvoice = async (payload) => {
        await new Promise(resolve => setTimeout(resolve, 800));
        transactions.value.unshift({
            id: Date.now(), type: 'income', icon: 'fa-solid fa-file-invoice',
            title: `Счёт для ${payload.client_name}`,
            date: new Date().toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }),
            amount: payload.amount, amountClass: 'income'
        });
        return true;
    };

    const uploadDocument = async (docId) => {
        await new Promise(resolve => setTimeout(resolve, 600));
        const doc = documents.value.find(d => d.id === docId);
        if (doc) {
            doc.uploaded = true;
            doc.uploaded_at = new Date().toLocaleDateString('ru-RU');
            verificationStatus.value = (uploadedDocumentsCount.value === requiredDocumentsCount.value) ? 'verified' : 'partial';
        }
    };

    return {
        agent, tenants, transactions, documents, marketingCategories, verificationStatus, notifications,
        recentActivities, uploadedDocumentsCount, requiredDocumentsCount,
        fetchAgentData, updateProfile, createTenant, deleteTenant, requestPayout, createInvoice, uploadDocument
    };
});
