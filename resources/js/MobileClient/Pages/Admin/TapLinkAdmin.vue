<template>
    <div class="taplink-admin p-3 pb-5">
        <!-- Заголовок -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Управление ссылками</h5>
            <button class="btn btn-primary btn-sm rounded-pill px-3" @click="openModal()">
                <i class="fa-solid fa-plus me-1"></i> Добавить
            </button>
        </div>

        <!-- Загрузка -->
        <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
        </div>

        <!-- Список ссылок -->
        <div v-else class="d-flex flex-column gap-3">
            <div v-if="links.length === 0" class="text-center text-muted py-4">
                <i class="fa-solid fa-link-slash fa-2x mb-2 opacity-50"></i>
                <p>Ссылок пока нет. Добавьте первую!</p>
            </div>

            <div
                v-for="(link, index) in links"
                :key="link.id"
                class="card border-0 shadow-sm"
                :class="{ 'opacity-50': !link.is_active }"
            >
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">

                        <!-- Иконка -->
                        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 text-white"
                             :style="{ backgroundColor: link.icon_bg, width: '40px', height: '40px' }">
                            <i :class="link.icon"></i>
                        </div>

                        <!-- Информация -->
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-semibold text-truncate">{{ link.title }}</div>
                            <div class="text-muted small text-truncate">{{ link.url }}</div>
                        </div>

                        <!-- Переключатель Активен/Неактивен -->
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox"
                                   :checked="link.is_active"
                                   @change="toggleActive(link)">
                        </div>
                    </div>

                    <!-- Панель действий -->
                    <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                        <button class="btn btn-sm btn-light text-muted"
                                :disabled="index === 0"
                                @click="moveLink(link, 'up')" title="Вверх">
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-muted"
                                :disabled="index === links.length - 1"
                                @click="moveLink(link, 'down')" title="Вниз">
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" @click="openModal(link)">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" @click="deleteLink(link.id)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно (Своя реализация без Bootstrap JS) -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal-content bg-white rounded-4 p-4 mx-3">
                <h6 class="fw-bold mb-3">{{ isEditing ? 'Редактировать' : 'Новая ссылка' }}</h6>

                <form @submit.prevent="saveLink">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Название</label>
                        <input v-model="form.title" type="text" class="form-control" required placeholder="Напр: Наш Telegram">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Ссылка (URL)</label>
                        <input v-model="form.url" type="url" class="form-control" required placeholder="https://...">
                    </div>

                    <div class="row mb-3">
                        <div class="col-8">
                            <label class="form-label small text-muted">Иконка (FontAwesome)</label>
                            <input v-model="form.icon" type="text" class="form-control" placeholder="fa-brands fa-telegram">
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted">Цвет</label>
                            <input v-model="form.icon_bg" type="color" class="form-control form-control-color w-100" title="Выберите цвет">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light flex-grow-1" @click="closeModal">Отмена</button>
                        <button type="submit" class="btn btn-primary flex-grow-1" :disabled="isSaving">
                            <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                            {{ isEditing ? 'Сохранить' : 'Добавить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
            isEditing: false,
            form: {
                id: null,
                title: '',
                url: '',
                icon: 'fa-solid fa-link',
                icon_bg: '#ff8a00'
            }
        }
    },

    mounted() {
        this.fetchLinks();
    },

    methods: {
        async fetchLinks() {
            this.isLoading = true;
            try {
                const response = await axios.get('/api/admin/tap-links');
                this.links = response.data;
            } catch (error) {
                console.error('Ошибка загрузки:', error);
            } finally {
                this.isLoading = false;
            }
        },

        openModal(link = null) {
            if (link) {
                this.isEditing = true;
                this.form = { ...link };
            } else {
                this.isEditing = false;
                this.form = { id: null, title: '', url: '', icon: 'fa-solid fa-link', icon_bg: '#ff8a00' };
            }
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
        },

        async saveLink() {
            this.isSaving = true;
            try {
                if (this.isEditing) {
                    await axios.put(`/api/admin/tap-links/${this.form.id}`, this.form);
                } else {
                    await axios.post('/api/admin/tap-links', this.form);
                }
                this.closeModal();
                await this.fetchLinks();
            } catch (error) {
                alert('Ошибка сохранения. Проверьте данные.');
            } finally {
                this.isSaving = false;
            }
        },

        async deleteLink(id) {
            if (!confirm('Удалить эту ссылку?')) return;
            try {
                await axios.delete(`/api/admin/tap-links/${id}`);
                await this.fetchLinks();
            } catch (error) {
                console.error('Ошибка удаления:', error);
            }
        },

        async toggleActive(link) {
            try {
                await axios.put(`/api/admin/tap-links/${link.id}`, {
                    ...link,
                    is_active: !link.is_active
                });
                await this.fetchLinks();
            } catch (error) {
                console.error('Ошибка обновления статуса:', error);
            }
        },

        async moveLink(link, direction) {
            try {
                const response = await axios.post(`/api/admin/tap-links/${link.id}/move`, { direction });
                this.links = response.data;
            } catch (error) {
                console.error('Ошибка сортировки:', error);
            }
        }
    }
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
}

.modal-content {
    width: 100%;
    max-width: 400px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.form-control-color {
    height: 38px;
    padding: 4px;
}
</style>
