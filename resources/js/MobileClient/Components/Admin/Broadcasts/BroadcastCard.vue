<template>
    <div class="broadcast-card" :class="`status-${broadcast.status}`">
        <!-- Заголовок -->
        <div class="card-header">
            <div class="header-left">
                <div class="status-badge" :class="broadcast.status">
                    <i :class="getStatusIcon(broadcast.status)"></i>
                </div>
                <div class="header-info">
                    <h4 class="broadcast-title">{{ broadcast.title || 'Без названия' }}</h4>
                    <div class="broadcast-meta">
                        <span class="meta-item">
                            <i class="fa-solid fa-calendar"></i>
                            {{ formatDate(broadcast.created_at) }}
                        </span>
                        <span class="meta-item">
                            <i class="fa-solid fa-users"></i>
                            {{ broadcast.total_recipients || 0 }} получателей
                        </span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button
                    v-if="canSend"
                    class="action-btn send"
                    @click="$emit('send', broadcast)"
                    title="Отправить"
                >
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
                <button
                    v-if="canCancel"
                    class="action-btn cancel"
                    @click="$emit('cancel', broadcast)"
                    title="Отменить"
                >
                    <i class="fa-solid fa-ban"></i>
                </button>
                <button
                    class="action-btn duplicate"
                    @click="$emit('duplicate', broadcast)"
                    title="Дублировать"
                >
                    <i class="fa-solid fa-copy"></i>
                </button>
                <button
                    v-if="canDelete"
                    class="action-btn delete"
                    @click="$emit('delete', broadcast)"
                    title="Удалить"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <!-- Сообщение -->
        <div v-if="broadcast.message" class="card-body">
            <p class="broadcast-message">{{ truncate(broadcast.message, 200) }}</p>
        </div>

        <!-- 🆕 Медиа (безопасная версия) -->
        <div v-if="hasMedia" class="card-media">
            <div class="media-count">
                <i class="fa-solid fa-images"></i>
                <span>{{ broadcast.media.length }} файлов</span>
            </div>
            <div class="media-preview">
                <template v-for="(media, index) in broadcast.media.slice(0, 3)" :key="media.id || index">
                    <img
                        v-if="media?.type === 'image' && media.url"
                        :src="media.url"
                        class="media-thumb"
                        :alt="media.original_name || 'Медиа'"
                    >
                    <div v-else-if="media?.type === 'video'" class="media-thumb video-thumb">
                        <i class="fa-solid fa-film"></i>
                    </div>
                    <div v-else-if="media?.type === 'audio'" class="media-thumb audio-thumb">
                        <i class="fa-solid fa-music"></i>
                    </div>
                    <div v-else class="media-thumb unknown-thumb">
                        <i class="fa-solid fa-file"></i>
                    </div>
                </template>
            </div>
        </div>

        <!-- 🆕 Кнопки (безопасная версия) -->
        <div v-if="hasButtons" class="card-buttons">
            <div class="buttons-count">
                <i class="fa-solid fa-keyboard"></i>
                <span>{{ totalButtonsCount }} кнопок</span>
            </div>
            <div class="buttons-preview">
                <div
                    v-for="(row, rowIndex) in broadcast.buttons.slice(0, 2)"
                    :key="rowIndex"
                    class="button-row"
                >
                    <button
                        v-for="(btn, btnIndex) in row.slice(0, 3)"
                        :key="btnIndex"
                        class="button-preview"
                        :class="{ 'is-url': btn.type === 'url' }"
                    >
                        {{ btn.text || 'Без текста' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Статистика -->
        <div v-if="broadcast.status === 'sent'" class="card-stats">
            <div class="stat-item">
                <div class="stat-icon sent">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ broadcast.sent_count || 0 }}</div>
                    <div class="stat-label">Отправлено</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon delivered">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ broadcast.delivered_count || 0 }}</div>
                    <div class="stat-label">Доставлено</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon read">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ broadcast.read_count || 0 }}</div>
                    <div class="stat-label">Прочитано</div>
                </div>
            </div>
        </div>

        <!-- Прогресс -->
        <div v-if="broadcast.status === 'sending'" class="card-progress">
            <div class="progress-bar">
                <div
                    class="progress-fill"
                    :style="{ width: (broadcast.progress_percent || 0) + '%' }"
                ></div>
            </div>
            <div class="progress-text">
                {{ broadcast.sent_count || 0 }} / {{ broadcast.total_recipients || 0 }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BroadcastCard',

    props: {
        broadcast: {
            type: Object,
            required: true,
        },
    },

    emits: ['send', 'cancel', 'delete', 'duplicate'],

    computed: {
        canSend() {
            return ['draft', 'scheduled'].includes(this.broadcast?.status);
        },

        canCancel() {
            return ['draft', 'scheduled'].includes(this.broadcast?.status);
        },

        canDelete() {
            return this.broadcast?.status === 'draft';
        },

        hasMedia() {
            return this.broadcast?.media &&
                Array.isArray(this.broadcast.media) &&
                this.broadcast.media.length > 0;
        },

        hasButtons() {
            return this.broadcast?.buttons &&
                Array.isArray(this.broadcast.buttons) &&
                this.broadcast.buttons.length > 0;
        },

        totalButtonsCount() {
            if (!this.hasButtons) return 0;
            return this.broadcast.buttons.reduce((sum, row) => sum + (row?.length || 0), 0);
        },
    },

    methods: {
        getStatusIcon(status) {
            const icons = {
                draft: 'fa-solid fa-file-pen',
                scheduled: 'fa-solid fa-clock',
                sending: 'fa-solid fa-spinner fa-spin',
                sent: 'fa-solid fa-paper-plane',
                failed: 'fa-solid fa-circle-exclamation',
                cancelled: 'fa-solid fa-ban',
            };
            return icons[status] || 'fa-solid fa-circle';
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        truncate(text, length) {
            if (!text) return '';
            if (text.length <= length) return text;
            return text.substring(0, length) + '...';
        },
    },
};
</script>



<style lang="scss" scoped>
$primary: #667eea;
$primary-dark: #5a67d8;
$success: #10b981;
$warning: #f59e0b;
$danger: #ef4444;
$purple: #8b5cf6;
$bg: var(--bs-body-bg, #ffffff);
$bg-secondary: var(--bs-secondary-bg, #f8f9fa);
$text: var(--bs-body-color, #1f2937);
$text-muted: var(--bs-secondary-color, #6b7280);
$border: var(--bs-border-color, #e5e7eb);

.broadcast-card {
    background: $bg;
    border: 1px solid $border;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.2s;

    &:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    &.status-sending {
        border-color: rgba($warning, 0.3);
        background: linear-gradient(135deg, rgba($warning, 0.02) 0%, $bg 100%);
    }

    &.status-sent {
        border-color: rgba($success, 0.2);
    }

    &.status-failed {
        border-color: rgba($danger, 0.3);
        background: linear-gradient(135deg, rgba($danger, 0.02) 0%, $bg 100%);
    }
}

.card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 16px;
    border-bottom: 1px solid $border;
}

.header-left {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.status-badge {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;

    &.draft { background: linear-gradient(135deg, $text-muted, #4b5563); }
    &.scheduled { background: linear-gradient(135deg, $warning, #d97706); }
    &.sending { background: linear-gradient(135deg, $warning, #f59e0b); }
    &.sent { background: linear-gradient(135deg, $success, #059669); }
    &.failed { background: linear-gradient(135deg, $danger, #dc2626); }
    &.cancelled { background: linear-gradient(135deg, $text-muted, #6b7280); }
}

.header-info {
    flex: 1;
    min-width: 0;
}

.broadcast-title {
    margin: 0 0 4px;
    font-size: 1rem;
    font-weight: 700;
    color: $text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.broadcast-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.75rem;
    color: $text-muted;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;

    i {
        font-size: 0.7rem;
    }
}

.header-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid $border;
    background: $bg;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        transform: scale(1.1);
    }

    &:active {
        transform: scale(0.95);
    }

    &.send {
        color: $primary;
        &:hover {
            background: $primary;
            border-color: $primary;
            color: white;
        }
    }

    &.cancel {
        color: $warning;
        &:hover {
            background: $warning;
            border-color: $warning;
            color: white;
        }
    }

    &.duplicate {
        color: $purple;
        &:hover {
            background: $purple;
            border-color: $purple;
            color: white;
        }
    }

    &.delete {
        color: $danger;
        &:hover {
            background: $danger;
            border-color: $danger;
            color: white;
        }
    }
}

.card-body {
    padding: 16px;
}

.broadcast-message {
    margin: 0;
    font-size: 0.9rem;
    color: $text;
    line-height: 1.5;
}

.card-media {
    padding: 0 16px 16px;
}

.media-count {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    font-size: 0.8rem;
    color: $text-muted;

    i {
        color: $primary;
    }
}

.media-preview {
    display: flex;
    gap: 8px;
}

.media-thumb {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid $border;
}

.card-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 16px;
    background: $bg-secondary;
    border-top: 1px solid $border;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: white;
    flex-shrink: 0;

    &.sent { background: $primary; }
    &.delivered { background: $success; }
    &.read { background: $purple; }
}

.stat-info {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: $text;
    line-height: 1;
}

.stat-label {
    font-size: 0.7rem;
    color: $text-muted;
    margin-top: 2px;
}

.card-progress {
    padding: 16px;
    background: $bg-secondary;
    border-top: 1px solid $border;
}

.progress-bar {
    height: 8px;
    background: $border;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, $warning, #f59e0b);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    font-size: 0.85rem;
    font-weight: 600;
    color: $text-muted;
}

@media (max-width: 576px) {
    .card-header {
        flex-direction: column;
    }

    .header-actions {
        width: 100%;
        justify-content: flex-end;
    }

    .card-stats {
        grid-template-columns: 1fr;
    }
}


// 🆕 Стили для кнопок
.card-buttons {
    padding: 12px 16px;
    background: var(--bs-secondary-bg, #f8f9fa);
    border-top: 1px solid var(--bs-border-color, #e5e7eb);
}

.buttons-count {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    font-size: 0.8rem;
    color: var(--bs-secondary-color, #6b7280);

    i {
        color: #667eea;
    }
}

.buttons-preview {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.button-row {
    display: flex;
    gap: 4px;
}

.button-preview {
    flex: 1;
    padding: 6px 10px;
    background: white;
    border: 1px solid var(--bs-border-color, #e5e7eb);
    border-radius: 6px;
    color: #667eea;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: default;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;

    &.is-url {
        color: #10b981;
        border-color: rgba(16, 185, 129, 0.3);
    }
}

// 🆕 Стили для медиа-превью
.video-thumb,
.audio-thumb,
.unknown-thumb {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
}

.video-thumb {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.audio-thumb {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
}

.unknown-thumb {
    background: linear-gradient(135deg, #6b7280, #4b5563);
}
</style>
