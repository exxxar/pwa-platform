<template>
    <form @submit.prevent="onSubmit" class="settings-form">
        <!-- Внешний вид -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-palette"></i> Внешний вид</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label>Тема по умолчанию</label>
                    <select v-model="form.default_theme_scheme" @change="emitDirty">
                        <option v-for="scheme in availableSchemes" :key="scheme.id" :value="scheme.id">{{ scheme.name }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Основные параметры -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-store"></i> Основные параметры</h3>
            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Магазин выключен</h4>
                    <p>Пользователи увидят сообщение о том, что магазин не работает</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.is_disabled" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <!-- ... остальные toggle-row из оригинала (копируйте их сюда) -->
        </div>

        <!-- 🆕 Зоны доставки -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-map-location-dot"></i> Зоны доставки</h3>
            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Настройте градацию зон доставки.
            </div>
            <div class="dynamic-list">
                <div v-for="(zone, index) in form.delivery_zones" :key="zone.id" class="list-item-card">
                    <div class="list-item-header">
                        <span class="list-item-badge">Зона {{ index + 1 }}</span>
                        <button type="button" class="btn-icon-danger" @click="removeZone(zone.id)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Название зоны</label>
                            <input type="text" v-model="zone.name" placeholder="Например: Центр" @input="emitDirty">
                        </div>
                        <div class="form-field">
                            <label>Время доставки</label>
                            <input type="text" v-model="zone.time" placeholder="30-40 мин" @input="emitDirty">
                        </div>
                        <div class="form-field">
                            <label>Стоимость</label>
                            <input type="text" v-model="zone.price" placeholder="Бесплатно или 150 ₽" @input="emitDirty">
                        </div>
                        <div class="form-field">
                            <label>Мин. сумма заказа (₽)</label>
                            <input type="number" v-model="zone.minOrder" min="0" @input="emitDirty">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-add-item" @click="addZone">
                <i class="fa-solid fa-plus"></i> Добавить зону
            </button>
        </div>

        <!-- 🆕 Сервисы -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-concierge-bell"></i> Сервисы и преимущества</h3>
            <div class="dynamic-list">
                <div v-for="(service, index) in form.delivery_services" :key="service.id" class="list-item-card">
                    <div class="list-item-header">
                        <span class="list-item-badge">Преимущество {{ index + 1 }}</span>
                        <button type="button" class="btn-icon-danger" @click="removeService(service.id)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Название</label>
                            <input type="text" v-model="service.title" placeholder="Термосумки" @input="emitDirty">
                        </div>
                        <div class="form-field full-width">
                            <label>Описание</label>
                            <input type="text" v-model="service.description" placeholder="Сохраняем температуру" @input="emitDirty">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-add-item" @click="addService">
                <i class="fa-solid fa-plus"></i> Добавить сервис
            </button>
        </div>

        <!-- Кнопка сохранения -->
        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить' }}</span>
        </button>
    </form>
</template>

<script>
import { themeSchemes } from '@/MobileClient/constants/themeSchemes.js';

export default {
    name: 'TabShop',

    props: {
        form: { type: Object, required: true },
        isSaving: { type: Boolean, default: false },
        extraProps: { type: Object, default: () => ({}) },
    },

    emits: ['save', 'mark-dirty', 'notify'],

    data() {
        return {
            availableSchemes: themeSchemes,
        };
    },

    methods: {
        emitDirty() {
            this.$emit('mark-dirty', 'shop');
        },

        onSubmit() {
            this.$emit('save', this.form);
        },

        addZone() {
            this.form.delivery_zones.push({
                id: Date.now(), name: '', time: '', price: '', minOrder: 0
            });
            this.emitDirty();
        },

        removeZone(id) {
            this.form.delivery_zones = this.form.delivery_zones.filter(z => z.id !== id);
            this.emitDirty();
        },

        addService() {
            this.form.delivery_services.push({
                id: Date.now(), title: '', description: ''
            });
            this.emitDirty();
        },

        removeService(id) {
            this.form.delivery_services = this.form.delivery_services.filter(s => s.id !== id);
            this.emitDirty();
        },
    },
};
</script>
