<template>
    <div class="settings-panel fade-in">
        <div class="panel-header">
            <h3>Товары</h3>
            <p>Добавляйте, редактируйте и удаляйте товары</p>
        </div>
        <div class="panel-header-row">
            <div class="form-group" style="margin-bottom: 0;">
                <input type="text" :value="search" @input="$emit('update:search', $event.target.value)" placeholder="Поиск товара..." class="search-input">
            </div>
            <button class="btn-add" @click="addProduct"><i class="fa-solid fa-plus"></i> Добавить товар</button>
        </div>

        <div class="items-list products-list">
            <div v-for="(product, idx) in filteredProducts" :key="product.id" class="item-card product-item">
                <div class="product-image-preview">
                    <img v-if="product.image" :src="product.image" :alt="product.name">
                    <div v-else class="no-image"><i class="fa-solid fa-image"></i></div>
                </div>
                <div class="item-fields product-fields">
                    <input type="text" v-model="product.name" placeholder="Название товара">
                    <div class="price-row">
                        <input type="number" v-model.number="product.price" placeholder="Цена">
                        <input type="number" v-model.number="product.oldPrice" placeholder="Старая цена">
                    </div>
                    <div class="meta-row">
                        <select v-model="product.category">
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <input type="text" v-model="product.badge" placeholder="Бейдж (Хит, Новинка...)">
                    </div>
                </div>
                <div class="item-actions">
                    <label class="btn-upload"><i class="fa-solid fa-image"></i><input type="file" accept="image/*" @change="handleImage(product, $event)" hidden></label>
                    <button class="btn-remove" @click="removeProduct(idx)"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>
        <div v-if="products.length === 0" class="empty-state">
            <i class="fa-solid fa-box-open"></i><p>Товары не добавлены</p>
            <button class="btn-add" @click="addProduct"><i class="fa-solid fa-plus"></i> Добавить первый товар</button>
        </div>
    </div>
</template>

<script>
export default {
    name: "TabProducts",
    props: {
        products: { type: Array, required: true },
        categories: { type: Array, required: true },
        search: { type: String, default: '' }
    },
    emits: ['update:search'],
    computed: {
        filteredProducts() {
            if (!this.search.trim()) return this.products;
            const q = this.search.toLowerCase();
            return this.products.filter(p => p.name?.toLowerCase().includes(q));
        }
    },
    methods: {
        addProduct() {
            this.products.push({
                id: Date.now(), name: 'Новый товар', price: 0, oldPrice: null,
                category: this.categories[0]?.id || 'all', image: '', badge: ''
            });
        },
        removeProduct(filteredIdx) {
            if (!confirm('Удалить товар?')) return;
            const realIdx = this.products.findIndex(p => p.id === this.filteredProducts[filteredIdx].id);
            if (realIdx !== -1) this.products.splice(realIdx, 1);
        },
        handleImage(product, event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => { product.image = reader.result; };
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
.panel-header-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.form-group input { width: 100%; max-width: 300px; padding: 10px 14px; border: 1px solid $border; border-radius: 10px; font-size: 0.95rem; &:focus { outline: none; border-color: $primary; } }
.btn-add { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; background: rgba($primary, 0.1); color: $primary; border: 1px dashed rgba($primary, 0.3); cursor: pointer; &:hover { background: rgba($primary, 0.15); border-color: $primary; } }
.items-list { display: flex; flex-direction: column; gap: 12px; }
.item-card { display: flex; align-items: flex-start; gap: 16px; padding: 16px; background: $bg; border: 1px solid $border; border-radius: 12px; &:hover { border-color: rgba($primary, 0.3); } }
.product-image-preview { width: 80px; height: 80px; border-radius: 10px; overflow: hidden; background: $card-bg; flex-shrink: 0; border: 1px solid $border; img { width: 100%; height: 100%; object-fit: cover; } .no-image { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: $text-muted; font-size: 1.5rem; } }
.item-fields { flex: 1; display: flex; flex-direction: column; gap: 8px; min-width: 0; input, select { width: 100%; padding: 8px 12px; border: 1px solid $border; border-radius: 8px; font-size: 0.9rem; background: $card-bg; &:focus { outline: none; border-color: $primary; } } }
.price-row, .meta-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.item-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }
.btn-remove, .btn-upload { width: 36px; height: 36px; border-radius: 8px; background: transparent; border: 1px solid $border; color: $text-muted; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.btn-remove:hover { background: $danger; border-color: $danger; color: white; }
.btn-upload:hover { background: $primary; border-color: $primary; color: white; }
.empty-state { text-align: center; padding: 40px 20px; color: $text-muted; i { font-size: 3rem; margin-bottom: 12px; opacity: 0.4; } }
@media (max-width: 640px) { .item-card { flex-direction: column; } .item-actions { flex-direction: row; justify-content: flex-end; } .price-row, .meta-row { grid-template-columns: 1fr; } }
</style>
