<template>
    <div class="taplink-admin">
        <!-- Заголовок -->
        <div class="admin-header">
            <div class="header-info">
                <h2 class="header-title">Управление ссылками</h2>
                <p class="header-subtitle">Настройте отображение кнопок в вашем профиле</p>
            </div>
            <button class="btn-primary-modern" @click="openModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Добавить ссылку</span>
            </button>
        </div>

        <!-- Загрузка -->
        <div v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p>Загрузка данных...</p>
        </div>

        <!-- Список ссылок -->
        <div v-else class="links-container">
            <div v-if="links.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-link-slash"></i>
                </div>
                <h3>Ссылок пока нет</h3>
                <p>Нажмите кнопку выше, чтобы добавить первую ссылку</p>
            </div>

            <transition-group name="list" tag="div" class="links-list">
                <div
                    v-for="(link, index) in links"
                    :key="link.id"
                    class="link-card"
                    :class="{ 'is-inactive': !link.is_active }"
                >
                    <div class="card-content">
                        <!-- Иконка -->
                        <div class="link-icon-wrapper" :style="{ backgroundColor: link.icon_bg }">
                            <i :class="link.icon || 'fa-solid fa-link'"></i>
                        </div>

                        <!-- Информация -->
                        <div class="link-info">
                            <div class="link-title">{{ link.title }}</div>
                            <div class="link-url">{{ link.url }}</div>
                        </div>

                        <!-- Переключатель -->
                        <div class="switch-wrapper">
                            <label class="modern-switch">
                                <input type="checkbox" :checked="link.is_active" @change="toggleActive(link)">
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">{{ link.is_active ? 'Вкл' : 'Выкл' }}</span>
                        </div>
                    </div>

                    <!-- Панель действий -->
                    <div class="card-actions">
                        <button class="action-btn" :disabled="index === 0" @click="moveLink(link, 'up')" title="Вверх">
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <button class="action-btn" :disabled="index === links.length - 1" @click="moveLink(link, 'down')" title="Вниз">
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                        <div class="action-divider"></div>
                        <button class="action-btn edit" @click="openModal(link)" title="Редактировать">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="action-btn delete" @click="deleteLink(link.id)" title="Удалить">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </transition-group>
        </div>

        <!-- Современная Модальное окно -->
        <transition name="modal-fade">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">
                    <!-- Шапка -->
                    <div class="modal-header">
                        <h3>{{ isEditing ? 'Редактировать ссылку' : 'Новая ссылка' }}</h3>
                        <button class="modal-close" @click="closeModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Тело -->
                    <div class="modal-body">
                        <form @submit.prevent="saveLink">
                            <div class="form-group">
                                <label class="form-label">Название</label>
                                <input v-model="form.title" type="text" class="form-input" required placeholder="Например: Наш Telegram">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Ссылка (URL)</label>
                                <input v-model="form.url" type="url" class="form-input" required placeholder="https://...">
                            </div>

                            <div class="form-row">
                                <div class="form-group flex-grow">
                                    <label class="form-label">Иконка</label>
                                    <div class="icon-selector-wrapper">
                                        <input v-model="form.icon" type="text" class="form-input icon-input" readonly placeholder="fa-solid fa-link">
                                        <button type="button" class="btn-select-icon" @click="showIconPicker = !showIconPicker">
                                            <i class="fa-solid fa-icons"></i>
                                        </button>
                                        <!-- Живой предпросмотр -->
                                        <div class="icon-preview" :style="{ backgroundColor: form.icon_bg }">
                                            <i :class="form.icon || 'fa-solid fa-link'"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group flex-shrink">
                                    <label class="form-label">Цвет</label>
                                    <div class="color-picker-wrapper">
                                        <input v-model="form.icon_bg" type="color" class="color-input">
                                        <span class="color-hex">{{ form.icon_bg }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Выпадающая сетка иконок -->
                            <transition name="slide-down">
                                <div v-if="showIconPicker" class="icon-picker-panel">
                                    <div class="picker-search">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <input v-model="iconSearchQuery" type="text" placeholder="Поиск (напр: telegram, phone)...">
                                    </div>
                                    <div class="icon-grid">
                                        <button
                                            v-for="icon in filteredIcons"
                                            :key="icon"
                                            type="button"
                                            class="icon-grid-item"
                                            :class="{ 'is-active': form.icon === icon }"
                                            @click="selectIcon(icon)"
                                        >
                                            <i :class="icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </transition>

                            <!-- Футер модалки -->
                            <div class="modal-footer">
                                <button type="button" class="btn-secondary" @click="closeModal">Отмена</button>
                                <button type="submit" class="btn-primary" :disabled="isSaving">
                                    <span v-if="isSaving" class="btn-spinner"></span>
                                    {{ isEditing ? 'Сохранить' : 'Добавить' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: "TapLinkAdmin",
    data() {
        return {
            links: [],
            isLoading: false,
            isSaving: false,
            showModal: false,
            showIconPicker: false,
            iconSearchQuery: '',
            isEditing: false,
            form: {
                id: null,
                title: '',
                url: '',
                icon: 'fa-solid fa-link',
                icon_bg: '#6366f1' // Современный индиго по умолчанию
            },
            availableIcons: [
                'fa-brands fa-telegram', 'fa-brands fa-whatsapp', 'fa-brands fa-vk',
                'fa-brands fa-instagram', 'fa-brands fa-youtube', 'fa-brands fa-tiktok',
                'fa-brands fa-facebook', 'fa-brands fa-twitter', 'fa-brands fa-github',
                'fa-solid fa-globe', 'fa-solid fa-link', 'fa-solid fa-phone',
                'fa-solid fa-envelope', 'fa-solid fa-location-dot', 'fa-solid fa-clock',
                'fa-solid fa-star', 'fa-solid fa-heart', 'fa-solid fa-bolt',
                'fa-solid fa-cart-shopping', 'fa-solid fa-bag-shopping', 'fa-solid fa-gift',
                'fa-solid fa-file-pdf', 'fa-solid fa-image', 'fa-solid fa-video',
                'fa-solid fa-music', 'fa-solid fa-calendar', 'fa-solid fa-user',
                'fa-solid fa-comments', 'fa-solid fa-share-nodes', 'fa-solid fa-qrcode'
            ]
        }
    },
    computed: {
        filteredIcons() {
            if (!this.iconSearchQuery) return this.availableIcons;
            const query = this.iconSearchQuery.toLowerCase();
            return this.availableIcons.filter(icon => icon.toLowerCase().includes(query));
        }
    },
    mounted() {
        this.fetchLinks();
    },
    methods: {
        async fetchLinks() {
            this.isLoading = true;
            try {
                const response = await axios.get('/admin/tap-links');
                this.links = response.data;
            } catch (error) {
                console.error('Ошибка загрузки:', error);
            } finally {
                this.isLoading = false;
            }
        },
        openModal(link = null) {
            this.showIconPicker = false;
            this.iconSearchQuery = '';
            if (link) {
                this.isEditing = true;
                this.form = { ...link };
            } else {
                this.isEditing = false;
                this.form = { id: null, title: '', url: '', icon: 'fa-solid fa-link', icon_bg: '#6366f1' };
            }
            this.showModal = true;
        },
        closeModal() {
            this.showModal = false;
            this.showIconPicker = false;
        },
        selectIcon(icon) {
            this.form.icon = icon;
            this.showIconPicker = false;
            this.iconSearchQuery = '';
        },
        async saveLink() {
            this.isSaving = true;
            try {
                if (this.isEditing) {
                    await axios.put(`/admin/tap-links/${this.form.id}`, this.form);
                } else {
                    await axios.post('/admin/tap-links', this.form);
                }
                this.closeModal();
                await this.fetchLinks();
            } catch (error) {
                console.error('Ошибка сохранения:', error);
                alert('Ошибка сохранения. Проверьте данные.');
            } finally {
                this.isSaving = false;
            }
        },
        async deleteLink(id) {
            if (!confirm('Вы уверены, что хотите удалить эту ссылку?')) return;
            try {
                await axios.delete(`/admin/tap-links/${id}`);
                await this.fetchLinks();
            } catch (error) {
                console.error('Ошибка удаления:', error);
            }
        },
        async toggleActive(link) {
            try {
                await axios.put(`/admin/tap-links/${link.id}`, { ...link, is_active: !link.is_active });
                await this.fetchLinks();
            } catch (error) {
                console.error('Ошибка обновления статуса:', error);
            }
        },
        async moveLink(link, direction) {
            try {
                const response = await axios.post(`/admin/tap-links/${link.id}/move`, { direction });
                this.links = response.data;
            } catch (error) {
                console.error('Ошибка сортировки:', error);
            }
        }
    }
}
</script>

<style scoped lang="scss">
// ==========================================
// ПЕРЕМЕННЫЕ И БАЗА
// ==========================================
$primary: #6366f1;
$primary-hover: #4f46e5;
$danger: #ef4444;
$danger-hover: #dc2626;
$success: #10b981;
$bg-page: #f8fafc;
$bg-card: #ffffff;
$text-main: #0f172a;
$text-muted: #64748b;
$border: #e2e8f0;
$radius: 16px;
$shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
$shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
$shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);

.taplink-admin {
    max-width: 700px;
    margin: 0 auto;
    padding: 2rem 1rem;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    color: $text-main;
}

// ==========================================
// ЗАГОЛОВОК
// ==========================================
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: $text-main;
}

.header-subtitle {
    font-size: 0.875rem;
    color: $text-muted;
    margin: 0;
}

.btn-primary-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: $primary;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 99px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
    transition: all 0.2s ease;

    &:hover {
        background: $primary-hover;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.23);
    }
    &:active { transform: translateY(0); }
}

// ==========================================
// СПИСОК И КАРТОЧКИ
// ==========================================
.links-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.link-card {
    background: $bg-card;
    border: 1px solid $border;
    border-radius: $radius;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;

    &:hover {
        border-color: rgba(99, 102, 241, 0.3);
        box-shadow: $shadow-md;
        transform: translateY(-2px);
    }

    &.is-inactive {
        opacity: 0.6;
        background: #f8fafc;

        .link-title { color: $text-muted; }
    }
}

.card-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.link-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.2);
}

.link-info {
    flex: 1;
    min-width: 0;
}

.link-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.2s;
}

.link-url {
    font-size: 0.8rem;
    color: $text-muted;
    font-family: monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

// Переключатель
.switch-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.switch-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: $text-muted;
    text-transform: uppercase;
}

.modern-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;

    input { opacity: 0; width: 0; height: 0; }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 34px;

        &:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
    }

    input:checked + .slider { background-color: $success; }
    input:checked + .slider:before { transform: translateX(20px); }
}

// Действия
.card-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px dashed $border;
}

.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: transparent;
    color: $text-muted;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: #f1f5f9;
        color: $text-main;
    }
    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    &.edit:hover { background: rgba(99, 102, 241, 0.1); color: $primary; }
    &.delete:hover { background: rgba(239, 68, 68, 0.1); color: $danger; }
}

.action-divider {
    width: 1px;
    height: 20px;
    background: $border;
    margin: 0 0.25rem;
}

// ==========================================
// ПУСТОЕ СОСТОЯНИЕ И ЗАГРУЗКА
// ==========================================
.empty-state, .loading-state {
    text-align: center;
    padding: 3rem 1rem;
    color: $text-muted;
}

.empty-icon {
    width: 64px;
    height: 64px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
    color: #94a3b8;
}

.empty-state h3 { font-size: 1.1rem; font-weight: 600; margin: 0 0 0.5rem; color: $text-main; }
.empty-state p { font-size: 0.875rem; margin: 0; }

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: $primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }

// ==========================================
// МОДАЛЬНОЕ ОКНО
// ==========================================
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 1030;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-container {
    background: white;
    width: 100%;
    max-width: 480px;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid $border;
    background: #fafafa;

    h3 { margin: 0; font-size: 1.1rem; font-weight: 700; }
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover { background: #e2e8f0; color: $text-main; }
}

.modal-body { padding: 1.5rem; }

.form-group { margin-bottom: 1.25rem; }
.form-row { display: flex; gap: 1rem; }
.flex-grow { flex: 1; }
.flex-shrink { width: 110px; }

.form-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid $border;
    border-radius: 12px;
    background: #f8fafc;
    font-size: 0.95rem;
    color: $text-main;
    transition: all 0.2s;
    outline: none;

    &:focus {
        background: white;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    &::placeholder { color: #94a3b8; }
}

// Селектор иконок
.icon-selector-wrapper {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.icon-input { flex: 1; }

.btn-select-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1px solid $border;
    background: white;
    color: $primary;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover { background: #f1f5f9; border-color: $primary; }
}

.icon-preview {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: $shadow-sm;
}

// Выбор цвета
.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 0.25rem;
    height: 42px;
}

.color-input {
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    background: transparent;
    padding: 0;
}

.color-hex {
    font-size: 0.75rem;
    font-family: monospace;
    color: $text-muted;
    text-transform: uppercase;
}

// Панель выбора иконок
.icon-picker-panel {
    background: #f8fafc;
    border: 1px solid $border;
    border-radius: 16px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    animation: fadeIn 0.2s ease;
}

.picker-search {
    position: relative;
    margin-bottom: 0.75rem;

    i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.875rem;
    }

    input {
        width: 100%;
        padding: 0.6rem 0.75rem 0.6rem 2.25rem;
        border: 1px solid $border;
        border-radius: 10px;
        background: white;
        font-size: 0.875rem;
        outline: none;

        &:focus { border-color: $primary; }
    }
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
    gap: 0.5rem;
    max-height: 180px;
    overflow-y: auto;
    padding-right: 4px;

    &::-webkit-scrollbar { width: 4px; }
    &::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
}

.icon-grid-item {
    aspect-ratio: 1;
    border: 1px solid $border;
    border-radius: 10px;
    background: white;
    color: $text-muted;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        color: $primary;
        transform: scale(1.1);
    }

    &.is-active {
        background: $primary;
        border-color: $primary;
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
}

// Футер модалки
.modal-footer {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid $border;
}

.btn-secondary, .btn-primary {
    flex: 1;
    padding: 0.75rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-secondary {
    background: #f1f5f9;
    color: $text-main;
    &:hover { background: #e2e8f0; }
}

.btn-primary {
    background: $primary;
    color: white;
    &:hover:not(:disabled) { background: $primary-hover; }
    &:disabled { opacity: 0.7; cursor: wait; }
}

.btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

// ==========================================
// АНИМАЦИИ
// ==========================================
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.slide-down-enter-active, .slide-down-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}
.slide-down-enter-from, .slide-down-leave-to {
    opacity: 0;
    max-height: 0;
    margin-top: 0;
}
.slide-down-enter-to, .slide-down-leave-from {
    opacity: 1;
    max-height: 300px;
    margin-top: 1rem; // Отступ от полей
}

.list-enter-active, .list-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.list-enter-from, .list-leave-to { opacity: 0; transform: translateX(-20px); }
.list-move { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }

// Адаптив
@media (max-width: 480px) {
    .form-row { flex-direction: column; gap: 1.25rem; }
    .flex-shrink { width: 100%; }
    .color-picker-wrapper { width: 100%; justify-content: flex-start; }
}
</style>
