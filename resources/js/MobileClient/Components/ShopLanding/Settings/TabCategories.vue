<template>
    <div class="settings-panel fade-in">
        <div class="panel-header">
            <h3>Категории товаров</h3>
            <p>Управляйте категориями для фильтрации товаров</p>
        </div>
        <button class="btn-add" @click="addCategory"><i class="fa-solid fa-plus"></i> Добавить категорию</button>
        <div class="items-list">
            <div v-for="(cat, idx) in categories" :key="idx" class="item-card">
                <div class="item-icon-preview"><i :class="cat.icon"></i></div>
                <div class="item-fields">
                    <input type="text" v-model="cat.name" placeholder="Название категории" class="field-name">
                    <input type="text" v-model="cat.icon" placeholder="fa-solid fa-icon" class="field-icon">
                </div>
                <button class="btn-remove" @click="removeCategory(idx)" :disabled="cat.id === 'all'">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TabCategories",
    props: { categories: { type: Array, required: true } },
    methods: {
        addCategory() {
            this.categories.push({ id: 'cat_' + Date.now(), name: 'Новая категория', icon: 'fa-solid fa-tag' });
        },
        removeCategory(idx) {
            if (this.categories[idx].id === 'all') return;
            if (!confirm('Удалить категорию?')) return;
            this.categories.splice(idx, 1);
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
.btn-add { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; background: rgba($primary, 0.1); color: $primary; border: 1px dashed rgba($primary, 0.3); cursor: pointer; margin-bottom: 16px; &:hover { background: rgba($primary, 0.15); } }
.items-list { display: flex; flex-direction: column; gap: 12px; }
.item-card { display: flex; align-items: center; gap: 16px; padding: 16px; background: $bg; border: 1px solid $border; border-radius: 12px; &:hover { border-color: rgba($primary, 0.3); } }
.item-icon-preview { width: 48px; height: 48px; border-radius: 12px; background: rgba($primary, 0.1); color: $primary; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.item-fields { flex: 1; display: flex; flex-direction: column; gap: 8px; min-width: 0; input { width: 100%; padding: 8px 12px; border: 1px solid $border; border-radius: 8px; font-size: 0.9rem; background: $card-bg; &:focus { outline: none; border-color: $primary; } } .field-name { font-weight: 600; } .field-icon { font-family: monospace; font-size: 0.85rem; } }
.btn-remove { width: 36px; height: 36px; border-radius: 8px; background: transparent; border: 1px solid $border; color: $text-muted; cursor: pointer; display: flex; align-items: center; justify-content: center; &:hover:not(:disabled) { background: $danger; border-color: $danger; color: white; } &:disabled { opacity: 0.3; cursor: not-allowed; } }
</style>
