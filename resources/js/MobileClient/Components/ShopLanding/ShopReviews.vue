<template>
    <section class="shop-reviews">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">{{ config.title }}</h2>
                <p class="section-subtitle">{{ config.subtitle }}</p>
            </div>

            <div class="reviews-grid">
                <div v-for="review in reviews" :key="review.id" class="review-card">
                    <div class="review-rating">
                        <i v-for="star in 5" :key="star" class="fa-solid fa-star" :class="{ 'filled': star <= review.rating }"></i>
                    </div>
                    <p class="review-text">{{ review.text }}</p>
                    <div class="review-author">
                        <img v-lazy="review.avatar" :alt="review.name" class="review-avatar">
                        <div class="review-name">{{ review.name }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: "ShopReviews",
    props: {
        reviews: Array,
        config: Object,
    },
};
</script>

<style lang="scss" scoped>
.shop-reviews {
    padding: 80px 0;
    background: white;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 1rem;
    color: var(--dark);
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--gray);
}

.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
}

.review-card {
    background: var(--light);
    border-radius: 20px;
    padding: 2rem;
    transition: all 0.4s ease;

    &:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
    }
}

.review-rating {
    margin-bottom: 1rem;

    i {
        color: #ddd;
        font-size: 1.1rem;

        &.filled {
            color: var(--accent);
        }
    }
}

.review-text {
    font-size: 1rem;
    line-height: 1.7;
    color: var(--dark);
    margin-bottom: 1.5rem;
    font-style: italic;
}

.review-author {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.review-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.review-name {
    font-weight: 700;
    color: var(--dark);
}
</style>
