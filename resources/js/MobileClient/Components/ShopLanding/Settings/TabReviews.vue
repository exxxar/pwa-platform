<template>
    <div class="settings-panel fade-in">
        <div class="panel-header">
            <h3>Отзывы клиентов</h3>
            <p>Добавьте реальные отзывы для повышения доверия</p>
        </div>
        <div class="form-group"><label>Заголовок секции</label><input type="text" v-model="reviewsSection.title"></div>
        <div class="form-group"><label>Подзаголовок секции</label><input type="text" v-model="reviewsSection.subtitle"></div>

        <button class="btn-add" @click="addReview"><i class="fa-solid fa-plus"></i> Добавить отзыв</button>

        <div class="items-list">
            <div v-for="(review, idx) in reviews" :key="idx" class="item-card review-item">
                <div class="review-avatar-preview">
                    <img v-if="review.avatar" :src="review.avatar" :alt="review.name">
                    <div v-else class="no-avatar"><i class="fa-solid fa-user"></i></div>
                </div>
                <div class="item-fields review-fields">
                    <input type="text" v-model="review.name" placeholder="Имя клиента">
                    <textarea v-model="review.text" rows="2" placeholder="Текст отзыва"></textarea>
                    <div class="rating-input">
                        <span>Оценка:</span>
                        <i v-for="star in 5" :key="star" class="fa-solid fa-star" :class="{ 'filled': star <= review.rating }" @click="review.rating = star"></i>
                    </div>
                </div>
                <div class="item-actions">
                    <label class="btn-upload"><i class="fa-solid fa-image"></i><input type="file" accept="image/*" @change="handleAvatar(review, $event)" hidden></label>
                    <button class="btn-remove" @click="removeReview(idx)"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TabReviews",
    props: { reviews: { type: Array, required: true }, reviewsSection: { type: Object, required: true } },
    methods: {
        addReview() {
            this.reviews.push({ id: Date.now(), name: '', text: '', rating: 5, avatar: '' });
        },
        removeReview(idx) {
            if (!confirm('Удалить отзыв?')) return;
            this.reviews.splice(idx, 1);
        },
        handleAvatar(review, event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => { review.avatar = reader.result; };
        }
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6; $danger: #ef4444; $warning: #f59e0b; $text: #1f2937; $text-muted: #6b7280; $border: #e5e7eb; $bg: #f9fafb; $card-bg: #ffffff;
.settings-panel { background: $card-bg; border-radius: 16px; padding: 16px; border: 1px solid $border; }
.fade-in { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.panel-header { margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid $border; h3 { font-size: 1.2rem; font-weight: 700; margin: 0 0 6px 0; color: $text; } p { font-size: 0.9rem; color: $text-muted; margin: 0; } }
.form-group { margin-bottom: 18px; label { display: block; font-size: 0.85rem; font-weight: 600; color: $text; margin-bottom: 6px; } input, textarea { width: 100%; padding: 10px 14px; border: 1px solid $border; border-radius: 10px; font-size: 0.95rem; background: $card-bg; &:focus { outline: none; border-color: $primary; } } }
.btn-add { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; background: rgba($primary, 0.1); color: $primary; border: 1px dashed rgba($primary, 0.3); cursor: pointer; margin-bottom: 16px; &:hover { background: rgba($primary, 0.15); } }
.items-list { display: flex; flex-direction: column; gap: 12px; }
.item-card { display: flex; align-items: flex-start; gap: 16px; padding: 16px; background: $bg; border: 1px solid $border; border-radius: 12px; }
.review-avatar-preview { width: 56px; height: 56px; border-radius: 50%; overflow: hidden; background: $card-bg; flex-shrink: 0; border: 1px solid $border; img { width: 100%; height: 100%; object-fit: cover; } .no-avatar { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: $text-muted; font-size: 1.3rem; } }
.item-fields { flex: 1; display: flex; flex-direction: column; gap: 8px; input, textarea { width: 100%; padding: 8px 12px; border: 1px solid $border; border-radius: 8px; font-size: 0.9rem; background: $card-bg; &:focus { outline: none; border-color: $primary; } } }
.rating-input { display: flex; align-items: center; gap: 6px; span { font-size: 0.85rem; color: $text-muted; } i { color: #ddd; cursor: pointer; transition: all 0.2s; &.filled { color: $warning; } &:hover { transform: scale(1.2); } } }
.item-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }
.btn-remove, .btn-upload { width: 36px; height: 36px; border-radius: 8px; background: transparent; border: 1px solid $border; color: $text-muted; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.btn-remove:hover { background: $danger; border-color: $danger; color: white; }
.btn-upload:hover { background: $primary; border-color: $primary; color: white; }
</style>
