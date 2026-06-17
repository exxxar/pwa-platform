<template>
    <div class="tab-content fade-in">
        <div class="section-header-page">
            <div>
                <h2>Маркетинговые материалы</h2>
                <p>Готовые материалы для работы с клиентами</p>
            </div>
        </div>

        <div class="marketing-categories">
            <div v-for="category in categories" :key="category.id" class="marketing-category">
                <div class="category-header">
                    <div class="category-icon" :style="{ background: category.color }"><i :class="category.icon"></i>
                    </div>
                    <div class="category-info">
                        <h3>{{ category.title }}</h3>
                        <p>{{ category.description }}</p>
                    </div>
                </div>
                <div class="materials-list">
                    <div v-for="material in category.materials" :key="material.id" class="material-item">
                        <div class="material-icon"><i class="fa-solid fa-file-pdf"></i></div>
                        <div class="material-info">
                            <div class="material-title">{{ material.title }}</div>
                            <div class="material-meta"><span>PDF</span><span>{{ material.size }}</span></div>
                        </div>
                        <button class="download-btn" @click="$emit('download', material)"><i
                            class="fa-solid fa-download"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card referral">
            <div class="section-header">
                <h3><i class="fa-solid fa-link"></i> Ваша реферальная ссылка</h3>
            </div>
            <div class="referral-box">
                <div class="referral-url">
                    <i class="fa-solid fa-link"></i>
                    <span>https://app.example.com/?ref=agent_12345</span>
                </div>
                <button class="copy-btn" @click="$emit('copy-referral')">
                    <i class="fa-solid fa-copy"></i> <span>Копировать</span>
                </button>
            </div>
            <div class="referral-stats">
                <div class="ref-stat"><span class="ref-value">{{ agent.referrals_count }}</span><span class="ref-label">Переходов</span>
                </div>
                <div class="ref-stat"><span class="ref-value">23</span><span class="ref-label">Конверсий</span></div>
                <div class="ref-stat"><span class="ref-value">18 500 ₽</span><span class="ref-label">Заработано</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AgentMarketing",
    props: {agent: Object, categories: Array},
    emits: ['download', 'copy-referral']
};
</script>

<style lang="scss" scoped>
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

.marketing-categories {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 24px;
}

.marketing-category {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
}

.category-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.category-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    flex-shrink: 0;
}

.category-info {
    flex: 1;
}

.category-info h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 2px 0;
    color: #1f2937;
}

.category-info p {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}

.materials-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.material-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px;
    border-radius: 10px;
    transition: background 0.2s;
}

.material-item:hover {
    background: #f9fafb;
}

.material-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.material-info {
    flex: 1;
    min-width: 0;
}

.material-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1f2937;
    margin-bottom: 2px;
}

.material-meta {
    display: flex;
    gap: 10px;
    font-size: 0.75rem;
    color: #6b7280;
}

.material-meta span {
    padding: 2px 6px;
    background: #f3f4f6;
    border-radius: 4px;
}

.download-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #3b82f6;
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: 0.2s;
}

.download-btn:hover {
    background: #2563eb;
    transform: translateY(-2px);
}

.section-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.section-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
}

.referral {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.02) 100%);
    border-color: rgba(16, 185, 129, 0.2);
}

.referral-box {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.referral-url {
    flex: 1;
    min-width: 200px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-family: monospace;
    font-size: 0.85rem;
    color: #1f2937;
}

.copy-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.copy-btn:hover {
    background: #059669;
}

.referral-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.ref-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px;
    background: white;
    border-radius: 10px;
}

.ref-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: #10b981;
}

.ref-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>
