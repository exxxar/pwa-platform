<template>
    <div class="anonymous-box-card">
        <div class="box-glow"></div>

        <!-- Левая часть: визуал бокса -->
        <div class="box-visual">
            <div class="box-icon-wrapper">
                <div class="box-icon">
                    <span class="gift-emoji">🎁</span>
                </div>
                <div class="sparkles">
                    <span class="sparkle s1">✨</span>
                    <span class="sparkle s2">⭐</span>
                    <span class="sparkle s3">✨</span>
                </div>
            </div>
            <div class="mystery-label">СЮРПРИЗ</div>
        </div>

        <!-- Центральная часть: информация -->
        <div class="box-info">
            <div class="box-header">
                <div class="box-title-group">
                    <div class="box-title">🎁 Анонимный бокс</div>
                    <div class="partner-info" v-if="item.tenant_name">
                        <i class="fa-solid fa-store"></i>
                        <span>{{ item.tenant_name }}</span>
                    </div>
                </div>
                <div class="box-amount">{{ formatPrice(item.box_amount) }}</div>
            </div>
            <div class="box-description">
                Состав — сюрприз! Узнаете при получении
            </div>

            <div class="box-footer">
                <div class="quantity-control">
                    <button
                        class="qty-btn minus"
                        @click="$emit('decrement', item)"
                        :disabled="isProcessing"
                    >
                        <i class="fa-solid fa-minus"></i>
                    </button>
                    <div class="qty-value">{{ item.count }}</div>
                    <button
                        class="qty-btn plus"
                        @click="$emit('increment', item)"
                        :disabled="isProcessing"
                    >
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>

                <div class="box-total">
                    <div class="total-label">Итого:</div>
                    <div class="total-value">{{ formatPrice(item.box_amount * item.count) }}</div>
                </div>
            </div>
        </div>

        <!-- Кнопка удаления -->
        <button
            class="remove-btn"
            @click="$emit('remove', item)"
            :disabled="isProcessing"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</template>

<script>
export default {
    name: 'AnonymousBoxCardSimple',
    props: {
        item: {
            type: Object,
            required: true,
        },
    },
    emits: ['remove', 'increment', 'decrement'],
    data() {
        return {
            isProcessing: false,
        };
    },
    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price || 0);
        },
    },
};
</script>

<style scoped>
.anonymous-box-card {
    position: relative;
    display: flex;
    gap: 12px;
    padding: 14px;
    background: linear-gradient(135deg, #fff 0%, #fdfcff 100%);
    border: 2px solid transparent;
    border-radius: 18px;
    background-clip: padding-box;
    box-shadow:
        0 2px 8px rgba(118, 75, 162, 0.08),
        0 0 0 2px rgba(118, 75, 162, 0.15);
    overflow: hidden;
    isolation: isolate;
    transition: all 0.3s ease;
}

.anonymous-box-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 18px;
    padding: 2px;
    background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
    opacity: 0.6;
}

.box-glow {
    position: absolute;
    top: -40%;
    right: -20%;
    width: 150px;
    height: 150px;
    background: radial-gradient(circle, rgba(240, 147, 251, 0.15) 0%, transparent 70%);
    border-radius: 50%;
    z-index: -1;
    animation: floatGlow 6s ease-in-out infinite;
}

@keyframes floatGlow {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-15px, 15px) scale(1.1); }
}

/* ========== ВИЗУАЛ БОКСА ========== */
.box-visual {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.box-icon-wrapper {
    position: relative;
}

.box-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    background-size: 200% 200%;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 6px 16px rgba(118, 75, 162, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    animation: gradientShift 6s ease infinite, pulseGift 2.5s ease-in-out infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

@keyframes pulseGift {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.05) rotate(-3deg); }
}

.gift-emoji {
    font-size: 32px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.sparkles {
    position: absolute;
    inset: -8px;
    pointer-events: none;
}

.sparkle {
    position: absolute;
    font-size: 12px;
    animation: twinkle 2s ease-in-out infinite;
}

.sparkle.s1 { top: -4px; right: -4px; animation-delay: 0s; }
.sparkle.s2 { top: 50%; left: -8px; animation-delay: 0.7s; }
.sparkle.s3 { bottom: -4px; right: 30%; animation-delay: 1.4s; }

@keyframes twinkle {
    0%, 100% { opacity: 0.3; transform: scale(0.8); }
    50% { opacity: 1; transform: scale(1.3); }
}

.mystery-label {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: #764ba2;
    background: rgba(118, 75, 162, 0.1);
    padding: 2px 8px;
    border-radius: 10px;
}

/* ========== ИНФОРМАЦИЯ ========== */
.box-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
    gap: 6px;
}

.box-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
}

.box-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--bs-body-color);
    line-height: 1.2;
}

.box-amount {
    font-size: 0.85rem;
    font-weight: 700;
    color: #764ba2;
    background: rgba(118, 75, 162, 0.1);
    padding: 3px 8px;
    border-radius: 8px;
    white-space: nowrap;
    flex-shrink: 0;
}

.box-description {
    font-size: 0.78rem;
    color: var(--bs-secondary-color);
    line-height: 1.3;
}

.box-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 4px;
}

/* ========== КОНТРОЛЬ КОЛИЧЕСТВА ========== */
.quantity-control {
    display: flex;
    align-items: center;
    gap: 0;
    background: var(--bs-secondary-bg, #f5f5f5);
    border-radius: 10px;
    padding: 3px;
}

.qty-btn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: var(--bs-body-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    transition: all 0.2s ease;
}

.qty-btn:hover:not(:disabled) {
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
}

.qty-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.qty-btn.minus:hover:not(:disabled) {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.qty-value {
    min-width: 28px;
    text-align: center;
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--bs-body-color);
}

/* ========== ИТОГО ========== */
.box-total {
    text-align: right;
}

.total-label {
    font-size: 0.7rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1;
    margin-bottom: 2px;
}

.total-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--bs-body-color);
}

/* ========== КНОПКА УДАЛЕНИЯ ========== */
.remove-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 26px;
    height: 26px;
    border: none;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.08);
    color: #dc3545;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    transition: all 0.2s ease;
    z-index: 2;
}

.remove-btn:hover:not(:disabled) {
    background: #dc3545;
    color: white;
    transform: scale(1.1);
}

.remove-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ========== АДАПТИВ ========== */
@media (max-width: 380px) {
    .box-icon {
        width: 56px;
        height: 56px;
    }

    .gift-emoji {
        font-size: 28px;
    }

    .box-title {
        font-size: 0.88rem;
    }

    .box-amount {
        font-size: 0.78rem;
    }
}

.box-title-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    flex: 1;
}

.partner-info {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    background: rgba(118, 75, 162, 0.08);
    color: #764ba2;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    width: fit-content;
    max-width: 100%;
    overflow: hidden;
}

.partner-info i {
    font-size: 0.65rem;
    flex-shrink: 0;
}

.partner-info span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
