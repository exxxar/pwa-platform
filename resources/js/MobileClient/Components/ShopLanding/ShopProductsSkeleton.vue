<template>
    <section class="shop-products-skeleton" id="shop-products-section">
        <div class="container">

            <!-- Скелетон панели категорий -->
            <div class="categories-skeleton">
                <div v-for="n in 6" :key="n" class="category-pill-skeleton"></div>
            </div>

            <!-- Скелетон сетки товаров -->
            <div class="products-grid-skeleton">
                <div v-for="n in 8" :key="n" class="product-card-skeleton">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-line w-70"></div>
                        <div class="skeleton-line w-40"></div>
                        <div class="skeleton-line w-100 btn-placeholder"></div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</template>

<script>
export default {
    name: "ShopProductsSkeleton"
}
</script>

<style lang="scss" scoped>
.shop-products-skeleton {
    padding: 40px 0 80px;
    background: var(--light, #f8f9fa);
}

/* --- Скелетон категорий --- */
.categories-skeleton {
    display: flex;
    gap: 0.8rem;
    overflow: hidden;
    margin-bottom: 40px;
    padding: 4px 0;
}

.category-pill-skeleton {
    flex-shrink: 0;
    width: 120px;
    height: 44px;
    border-radius: 50px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

/* --- Скелетон сетки товаров --- */
.products-grid-skeleton {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(4, 1fr);

    @media (max-width: 1400px) { grid-template-columns: repeat(4, 1fr); }
    @media (max-width: 1024px) { grid-template-columns: repeat(3, 1fr); }
    @media (max-width: 768px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 480px) { grid-template-columns: 1fr; }
}

.product-card-skeleton {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
}

.skeleton-image {
    aspect-ratio: 1;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 12px;
    flex-grow: 1;
}

.skeleton-line {
    height: 16px;
    border-radius: 8px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.w-70 { width: 70%; }
.w-40 { width: 40%; }
.w-100 { width: 100%; }

.btn-placeholder {
    height: 44px;
    margin-top: auto;
    border-radius: 12px;
}

/* --- Анимация мерцания --- */
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
