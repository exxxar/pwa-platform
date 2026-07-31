<template>
    <div class="settings-panel fade-in">
        <div class="panel-header">
            <h3>Hero секция</h3>
            <p>Первый экран, который видят посетители.</p>
        </div>
        <div class="form-group">
            <label>Бейдж сверху</label>
            <input type="text" v-model="hero.badge" placeholder="Например: Мобильный магазин">
        </div>
        <div class="form-group">
            <label>Заголовок <span class="required">*</span></label>
            <input type="text" v-model="hero.title" placeholder="Главный заголовок">
        </div>
        <div class="form-group">
            <label>Подзаголовок</label>
            <textarea v-model="hero.subtitle" rows="3" placeholder="Краткое описание"></textarea>
        </div>
        <div class="form-group">
            <label>Текст кнопки</label>
            <input type="text" v-model="hero.buttonText" placeholder="Например: Смотреть каталог">
        </div>
        <div class="form-group">
            <label>Фоновое изображение</label>
            <div class="file-upload" :class="{ 'has-file': hero.backgroundImage }">
                <input type="file" ref="heroImageInput" @change="handleImage" accept="image/*" class="file-input">
                <div v-if="!hero.backgroundImage" class="upload-placeholder">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Нажмите или перетащите изображение</span>
                    <small>Рекомендуемый размер: 1920×1080</small>
                </div>
                <div v-else class="upload-preview">
                    <img :src="hero.backgroundImage" alt="Preview">
                    <button type="button" class="remove-image" @click="removeImage"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TabHero",
    props: { hero: { type: Object, required: true } },
    methods: {
        handleImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => { this.hero.backgroundImage = reader.result; };
        },
        removeImage() {
            this.hero.backgroundImage = '';
            if (this.$refs.heroImageInput) this.$refs.heroImageInput.value = '';
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6; $danger: #ef4444; $text: #1f2937; $text-muted: #6b7280; $border: #e5e7eb; $bg: #f9fafb; $card-bg: #ffffff;
.settings-panel { background: $card-bg; border-radius: 16px; padding: 16px; border: 1px solid $border; }
.fade-in { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.panel-header { margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid $border; h3 { font-size: 1.2rem; font-weight: 700; margin: 0 0 6px 0; color: $text; } p { font-size: 0.9rem; color: $text-muted; margin: 0; } }
.form-group { margin-bottom: 18px; label { display: block; font-size: 0.85rem; font-weight: 600; color: $text; margin-bottom: 6px; .required { color: $danger; } } input, textarea { width: 100%; padding: 10px 14px; border: 1px solid $border; border-radius: 10px; font-size: 0.95rem; background: $card-bg; color: $text; &:focus { outline: none; border-color: $primary; box-shadow: 0 0 0 3px rgba($primary, 0.1); } } textarea { resize: vertical; min-height: 80px; } }
.file-upload { border: 2px dashed $border; border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s; position: relative; &:hover { border-color: $primary; background: rgba($primary, 0.02); } &.has-file { padding: 0; border-style: solid; } }
.file-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 8px; color: $text-muted; i { font-size: 2rem; color: $primary; } span { font-weight: 600; color: $text; } small { font-size: 0.8rem; } }
.upload-preview { position: relative; width: 100%; aspect-ratio: 16/9; border-radius: 10px; overflow: hidden; img { width: 100%; height: 100%; object-fit: cover; } }
.remove-image { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%; background: rgba(0, 0, 0, 0.7); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; &:hover { background: $danger; } }
</style>
