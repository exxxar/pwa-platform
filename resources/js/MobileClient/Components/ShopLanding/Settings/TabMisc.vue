<template>
    <div class="settings-panel fade-in">
        <div class="panel-header">
            <h3>{{ currentSection.title }}</h3>
            <p>{{ currentSection.desc }}</p>
        </div>

        <!-- CTA -->
        <template v-if="activeSection === 'cta'">
            <div class="form-group"><label>Заголовок</label><input type="text" v-model="config.cta.title"></div>
            <div class="form-group"><label>Текст</label><textarea v-model="config.cta.text" rows="3"></textarea></div>
            <div class="form-group"><label>Текст кнопки</label><input type="text" v-model="config.cta.buttonText"></div>
        </template>

        <!-- Footer -->
        <template v-if="activeSection === 'footer'">
            <div class="form-group"><label>Название компании</label><input type="text" v-model="config.footer.companyName"></div>
            <div class="form-group"><label>Описание</label><textarea v-model="config.footer.description" rows="2"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Телефон</label><input type="tel" v-model="config.footer.phone"></div>
                <div class="form-group"><label>Email</label><input type="email" v-model="config.footer.email"></div>
            </div>
            <div class="form-group"><label>Адрес</label><input type="text" v-model="config.footer.address"></div>
            <div class="form-group">
                <label>Социальные сети</label>
                <div class="social-list">
                    <div v-for="(social, idx) in config.footer.socialLinks" :key="idx" class="social-item">
                        <input type="text" v-model="social.icon" placeholder="fa-brands fa-telegram">
                        <input type="text" v-model="social.url" placeholder="https://...">
                        <button class="btn-remove" @click="config.footer.socialLinks.splice(idx, 1)"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    <button class="btn-add-small" @click="config.footer.socialLinks.push({icon: 'fa-brands fa-link', url: ''})"><i class="fa-solid fa-plus"></i> Добавить</button>
                </div>
            </div>
        </template>

        <!-- Cart -->
        <template v-if="activeSection === 'cart'">
            <div class="form-group"><label>Заголовок корзины</label><input type="text" v-model="config.cart.title"></div>
            <div class="form-group"><label>Текст пустой корзины</label><input type="text" v-model="config.cart.emptyText"></div>
            <div class="form-group"><label>Текст кнопки оформления</label><input type="text" v-model="config.cart.checkoutText"></div>
            <div class="form-group"><label>Подпись итоговой суммы</label><input type="text" v-model="config.cart.totalText"></div>
        </template>

        <!-- Feedback -->
        <template v-if="activeSection === 'feedback'">
            <div class="form-group"><label>Заголовок</label><input type="text" v-model="config.feedbackModal.title"></div>
            <div class="form-group"><label>Подзаголовок</label><input type="text" v-model="config.feedbackModal.subtitle"></div>
            <div class="form-group"><label>Подпись поля "Имя"</label><input type="text" v-model="config.feedbackModal.nameLabel"></div>
            <div class="form-group"><label>Подпись поля "Телефон"</label><input type="text" v-model="config.feedbackModal.phoneLabel"></div>
            <div class="form-group"><label>Подпись поля "Сообщение"</label><input type="text" v-model="config.feedbackModal.messageLabel"></div>
            <div class="form-group"><label>Текст кнопки отправки</label><input type="text" v-model="config.feedbackModal.submitText"></div>
        </template>

        <!-- Privacy -->
        <template v-if="activeSection === 'privacy'">
            <div class="form-group"><label>Заголовок</label><input type="text" v-model="config.privacyModal.title"></div>
            <div class="form-group">
                <label>Текст политики <span class="hint">Поддерживается HTML</span></label>
                <textarea v-model="config.privacyModal.content" rows="15" class="code-textarea"></textarea>
            </div>
        </template>
    </div>
</template>

<script>
export default {
    name: "TabMisc",
    props: {
        activeSection: { type: String, required: true },
        config: { type: Object, required: true }
    },
    computed: {
        currentSection() {
            const map = {
                cta: { title: 'Призыв к действию (CTA)', desc: 'Секция перед футером с призывом связаться' },
                footer: { title: 'Футер', desc: 'Контактная информация и ссылки' },
                cart: { title: 'Корзина', desc: 'Тексты для корзины и оформления заказа' },
                feedback: { title: 'Модалка обратной связи', desc: 'Форма для связи с клиентами' },
                privacy: { title: 'Политика конфиденциальности', desc: 'Текст политики, отображаемый в модалке' }
            };
            return map[this.activeSection] || {};
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6; $danger: #ef4444; $text: #1f2937; $text-muted: #6b7280; $border: #e5e7eb; $bg: #f9fafb; $card-bg: #ffffff;
.settings-panel { background: $card-bg; border-radius: 16px; padding: 28px; border: 1px solid $border; }
.fade-in { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.panel-header { margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid $border; h3 { font-size: 1.2rem; font-weight: 700; margin: 0 0 6px 0; color: $text; } p { font-size: 0.9rem; color: $text-muted; margin: 0; } }
.form-group { margin-bottom: 18px; label { display: block; font-size: 0.85rem; font-weight: 600; color: $text; margin-bottom: 6px; .hint { font-weight: 400; color: $text-muted; font-size: 0.8rem; margin-left: 6px; } } input, textarea { width: 100%; padding: 10px 14px; border: 1px solid $border; border-radius: 10px; font-size: 0.95rem; background: $card-bg; &:focus { outline: none; border-color: $primary; } } textarea { resize: vertical; min-height: 80px; } .code-textarea { font-family: monospace; font-size: 0.85rem; line-height: 1.5; } }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.social-list { display: flex; flex-direction: column; gap: 8px; }
.social-item { display: grid; grid-template-columns: 1fr 2fr auto; gap: 8px; align-items: center; input { padding: 8px 12px; border: 1px solid $border; border-radius: 8px; font-size: 0.9rem; &:focus { outline: none; border-color: $primary; } } }
.btn-add-small { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 0.85rem; font-weight: 600; background: rgba($primary, 0.05); color: $primary; border: 1px dashed rgba($primary, 0.3); border-radius: 8px; cursor: pointer; &:hover { background: rgba($primary, 0.1); } }
.btn-remove { width: 36px; height: 36px; border-radius: 8px; background: transparent; border: 1px solid $border; color: $text-muted; cursor: pointer; display: flex; align-items: center; justify-content: center; &:hover { background: $danger; border-color: $danger; color: white; } }
@media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } .social-item { grid-template-columns: 1fr; } }
</style>
