<template>
    <div class="tab-content fade-in">
        <div class="section-header-page">
            <div>
                <h2>Документы</h2>
                <p>Загрузите документы для трудоустройства и верификации</p>
            </div>
        </div>

        <div class="verification-card" :class="'status-' + documents.status">
            <div class="verification-icon"><i :class="verificationIcon"></i></div>
            <div class="verification-info">
                <h3>{{ verificationTitle }}</h3>
                <p>{{ verificationText }}</p>
            </div>
            <div class="verification-progress">
                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: documents.progress.percent + '%' }"></div>
                </div>
                <span class="progress-text">{{ documents.progress.uploaded }}/{{ documents.progress.required }} документов</span>
            </div>
        </div>

        <div class="documents-grid">
            <div v-for="doc in documents.list" :key="doc.id" class="document-card"
                 :class="{ 'is-uploaded': doc.is_uploaded, 'is-required': doc.is_required }">
                <div class="doc-icon"><i :class="doc.icon || 'fa-solid fa-file'"></i></div>
                <div class="doc-info">
                    <h4>{{ doc.title }}</h4>
                    <p>{{ doc.description || 'Стандартный документ' }}</p>
                    <div v-if="doc.is_uploaded" class="doc-status uploaded">
                        <i class="fa-solid fa-circle-check"></i><span>Загружен</span>
                    </div>
                    <div v-else-if="doc.is_required" class="doc-status required">
                        <i class="fa-solid fa-circle-exclamation"></i><span>Обязательно</span>
                    </div>
                </div>
                <div class="doc-actions">
                    <button v-if="doc.is_uploaded" class="btn-secondary-modern small"
                            @click="triggerUpload(doc.id)"><i class="fa-solid fa-rotate"></i></button>
                    <button v-else class="btn-primary-modern small" @click="triggerUpload(doc.id)">
                        <i class="fa-solid fa-upload"></i> Загрузить
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AgentDocuments",
    // ИСПРАВЛЕНО: documents теперь Object, а не Array
    props: { documents: Object, verificationStatus: String },
    emits: ['document-upload-requested'], // ИСПРАВЛЕНО: имя события
    computed: {
        verificationIcon() {
            const icons = { not_started: 'fa-solid fa-hourglass-start', partial: 'fa-solid fa-triangle-exclamation', verified: 'fa-solid fa-circle-check' };
            return icons[this.documents.status] || icons.not_started;
        },
        verificationTitle() {
            const titles = { not_started: 'Верификация не начата', partial: 'Загружены не все документы', verified: 'Верификация пройдена' };
            return titles[this.documents.status] || titles.not_started;
        },
        verificationText() {
            const texts = { not_started: 'Загрузите обязательные документы для начала работы.', partial: 'Доверифицируйте профиль для снятия ограничений.', verified: 'Вы можете полноценно работать и выводить средства.' };
            return texts[this.documents.status] || texts.not_started;
        }
    },
    methods: {
        triggerUpload(docId) {
            // Создаем невидимый input для выбора файла
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.pdf,.jpg,.jpeg,.png';
            input.onchange = (e) => {
                const file = e.target.files[0];
                if (file) {
                    // Передаем ID и сам файл наверх в Dashboard
                    this.$emit('document-upload-requested', { docId, file });
                }
            };
            input.click();
        }
    }
};
</script>
<!-- Стили остаются без изменений -->

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

.verification-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.verification-card.status-verified {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.02) 100%);
    border-color: rgba(16, 185, 129, 0.3);
}

.verification-card.status-partial {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.02) 100%);
    border-color: rgba(245, 158, 11, 0.3);
}

.verification-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.status-verified .verification-icon {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.status-partial .verification-icon {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.verification-info {
    flex: 1;
    min-width: 200px;
}

.verification-info h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: #1f2937;
}

.verification-info p {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}

.verification-progress {
    min-width: 200px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 6px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-text {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 600;
}

.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}

.document-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 18px;
    display: flex;
    gap: 14px;
    transition: 0.2s;
}

.document-card:hover {
    border-color: rgba(59, 130, 246, 0.3);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.document-card.is-uploaded {
    border-color: rgba(16, 185, 129, 0.3);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.03) 0%, transparent 100%);
}

.doc-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.is-uploaded .doc-icon {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.doc-info {
    flex: 1;
    min-width: 0;
}

.doc-info h4 {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: #1f2937;
}

.doc-info p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0 0 8px 0;
}

.doc-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.doc-status.uploaded {
    color: #10b981;
}

.doc-status.required {
    color: #f59e0b;
}

.doc-actions {
    flex-shrink: 0;
    display: flex;
    align-items: flex-start;
}

.btn-primary-modern, .btn-secondary-modern {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

.btn-primary-modern {
    background: #3b82f6;
    color: white;
}

.btn-secondary-modern {
    background: #f9fafb;
    color: #1f2937;
    border: 1px solid #e5e7eb;
}
</style>
