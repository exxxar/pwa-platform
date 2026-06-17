<template>
    <div v-if="hasOfferLink || true" class="offer-card" :class="{ 'is-agreed': offerAgreement }">

        <!-- Иконка -->
        <div class="offer-icon">
            <i class="fa-solid fa-file-shield"></i>
        </div>

        <!-- Контент -->
        <div class="offer-content">
            <h6 class="offer-title">Условия заказа</h6>
            <p class="offer-text">
                Нажимая кнопку далее, вы соглашаетесь с условиями
                <template v-if="hasOfferLink">
                    <a :href="offerLink" class="offer-link" target="_blank" rel="noopener noreferrer">
                        договора оферты
                        <i class="fa-solid fa-arrow-up-right-from-square link-icon"></i>
                    </a>
                </template>
                <template v-else>
                    <span class="offer-link-text">договора оферты</span>
                </template>
                , а также даёте согласие на обработку персональных данных согласно
                <a :href="privacyLawLink" class="offer-link" target="_blank" rel="noopener noreferrer">
                    Федеральному закону №152-ФЗ
                    <i class="fa-solid fa-arrow-up-right-from-square link-icon"></i>
                </a>
            </p>
        </div>

        <!-- Toggle -->
        <div class="offer-toggle" @click="toggleAgreement">
            <div class="toggle-track" :class="{ 'active': offerAgreement }">
                <div class="toggle-thumb">
                    <i v-if="offerAgreement" class="fa-solid fa-check"></i>
                </div>
            </div>
            <span class="toggle-label" :class="{ 'active': offerAgreement }">
                {{ offerAgreement ? 'Согласен' : 'Согласиться' }}
            </span>
        </div>

    </div>
</template>

<script>
export default {
    name: "OfferForm",

    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            offerAgreement: false,
            privacyLawLink: 'https://www.consultant.ru/document/cons_doc_LAW_61801/',
        };
    },

    watch: {
        modelValue: {
            handler(newValue) {
                this.offerAgreement = newValue;
            },
            immediate: true,
        },
        offerAgreement(newValue) {
            this.$emit('update:modelValue', newValue);
        },
    },

    computed: {
        tenant() {
            return window.Tenant || null;
        },

        settings() {
            return this.tenant?.settings || {};
        },

        hasOfferLink() {
            return !!this.settings?.law_params?.offer_link;
        },

        offerLink() {
            return this.settings?.law_params?.offer_link || '#';
        },
    },

    methods: {
        toggleAgreement() {
            this.offerAgreement = !this.offerAgreement;
        },
    },
};
</script>

<style scoped>
.offer-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    border-radius: 16px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.offer-card:hover {
    border-color: rgba(var(--bs-primary-rgb), 0.3);
    box-shadow: 0 4px 16px rgba(var(--bs-primary-rgb), 0.08);
}

.offer-card.is-agreed {
    border-color: var(--bs-primary);
    background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    box-shadow: 0 4px 20px rgba(var(--bs-primary-rgb), 0.15);
}

/* ==========================================
   ИКОНКА
   ========================================== */
.offer-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.offer-card.is-agreed .offer-icon {
    background: var(--bs-primary);
    color: white;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

/* ==========================================
   КОНТЕНТ
   ========================================== */
.offer-content {
    flex: 1;
    min-width: 0;
}

.offer-title {
    margin: 0 0 6px 0;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--bs-body-color);
}

.offer-text {
    margin: 0;
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
    line-height: 1.5;
}

.offer-link {
    color: var(--bs-primary);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.offer-link:hover {
    color: var(--bs-primary-hover, var(--bs-primary));
    text-decoration: underline;
}

.offer-link .link-icon {
    font-size: 0.65rem;
    opacity: 0.7;
}

.offer-link-text {
    color: var(--bs-primary);
    font-weight: 600;
}

/* ==========================================
   TOGGLE
   ========================================== */
.offer-toggle {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    cursor: pointer;
    user-select: none;
}

.toggle-track {
    width: 48px;
    height: 28px;
    border-radius: 14px;
    background: var(--bs-border-color);
    position: relative;
    transition: background 0.3s ease;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-track.active {
    background: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.toggle-thumb {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    color: var(--bs-primary);
}

.toggle-track.active .toggle-thumb {
    transform: translateX(20px);
    color: var(--bs-primary);
}

.toggle-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    transition: color 0.3s ease;
}

.toggle-label.active {
    color: var(--bs-primary);
}

/* ==========================================
   АДАПТИВ
   ========================================== */
@media (max-width: 576px) {
    .offer-card {
        padding: 14px;
        gap: 12px;
    }

    .offer-icon {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }

    .offer-title {
        font-size: 0.9rem;
    }

    .offer-text {
        font-size: 0.75rem;
    }

    .toggle-track {
        width: 44px;
        height: 26px;
    }

    .toggle-thumb {
        width: 20px;
        height: 20px;
    }

    .toggle-track.active .toggle-thumb {
        transform: translateX(18px);
    }
}
</style>
