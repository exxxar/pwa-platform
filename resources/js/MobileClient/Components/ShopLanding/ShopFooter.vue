<template>
    <footer class="shop-footer">
        <div class="container">
            <div class="footer-grid">

                <!-- Колонка 1: О компании -->
                <div class="footer-column brand-column">
                    <div class="footer-brand">
                        <i class="fa-solid fa-store"></i>
                        <span>{{ config.companyName }}</span>
                    </div>
                    <p class="footer-description">{{ config.description }}</p>
                    <div class="footer-socials">
                        <a
                            v-for="(social, idx) in config.socialLinks"
                            :key="idx"
                            :href="social.url"
                            target="_blank"
                            class="social-link"
                            :title="social.icon"
                        >
                            <i :class="social.icon"></i>
                        </a>
                    </div>
                </div>

                <!-- Колонка 2: Контакты -->
                <div class="footer-column">
                    <h4 class="footer-title">Контакты</h4>
                    <ul class="footer-list">
                        <li v-if="config.phone">
                            <a :href="'tel:' + config.phone.replace(/\s/g, '')">
                                <i class="fa-solid fa-phone"></i>
                                {{ config.phone }}
                            </a>
                        </li>
                        <li v-if="config.email">
                            <a :href="'mailto:' + config.email">
                                <i class="fa-solid fa-envelope"></i>
                                {{ config.email }}
                            </a>
                        </li>
                        <li v-if="config.address">
                            <span>
                                <i class="fa-solid fa-location-dot"></i>
                                {{ config.address }}
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Колонка 3: Навигация -->
                <div class="footer-column">
                    <h4 class="footer-title">Покупателям</h4>
                    <ul class="footer-list">
                        <li><a href="#" @click.prevent="scrollToTop">Наверх</a></li>
                        <li><a href="#" @click.prevent="$emit('open-feedback')">Обратная связь</a></li>
                        <li><a href="#" @click.prevent="$emit('open-privacy')">Политика конфиденциальности</a></li>
                    </ul>
                </div>

            </div>

            <!-- Нижняя полоса -->
            <div class="footer-bottom">
                <div class="copyright">
                    © {{ currentYear }} {{ config.companyName }}. Все права защищены.
                </div>
                <div class="made-with">
                    Сделано с <i class="fa-solid fa-heart"></i> для ваших клиентов
                </div>
            </div>
        </div>
    </footer>
</template>

<script>
export default {
    name: "ShopFooter",
    props: {
        config: {
            type: Object,
            required: true,
            default: () => ({
                companyName: 'Магазин',
                description: 'Доставка свежих продуктов',
                phone: '',
                email: '',
                address: '',
                socialLinks: []
            })
        }
    },
    emits: ['open-privacy', 'open-feedback'],
    computed: {
        currentYear() {
            return new Date().getFullYear();
        }
    },
    methods: {
        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
};
</script>

<style lang="scss" scoped>
.shop-footer {
    background: var(--dark);
    color: white;
    padding: 60px 0 30px;
    position: relative;

    // Декоративная линия сверху
    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--primary), transparent);
    }
}

.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.footer-column {
    display: flex;
    flex-direction: column;
}

.brand-column {
    max-width: 350px;
}

.footer-brand {
    font-weight: 900;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: white;

    i {
        color: var(--primary);
    }
}

.footer-description {
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.6;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.footer-socials {
    display: flex;
    gap: 12px;
}

.social-link {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    text-decoration: none;

    &:hover {
        background: var(--primary);
        border-color: var(--primary);
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
}

.footer-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1.2rem;
    color: white;
    position: relative;
    padding-bottom: 10px;

    &::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 30px;
        height: 2px;
        background: var(--primary);
    }
}

.footer-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;

    li {
        display: flex;
        align-items: center;
    }

    a, span {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;

        i {
            color: var(--primary);
            width: 16px;
            text-align: center;
        }

        &:hover {
            color: white;
            transform: translateX(5px);
        }
    }
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.9rem;
}

.made-with {
    display: flex;
    align-items: center;
    gap: 6px;

    i {
        color: #ef4444;
        animation: heartbeat 1.5s ease infinite;
    }
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}

// Адаптивность
@media (max-width: 992px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .brand-column {
        grid-column: 1 / -1;
        max-width: 100%;
        text-align: center;
        align-items: center;
    }

    .footer-title::after {
        left: 50%;
        transform: translateX(-50%);
    }

    .footer-list {
        align-items: center;
    }
}

@media (max-width: 640px) {
    .shop-footer {
        padding: 40px 0 20px;
    }

    .footer-grid {
        grid-template-columns: 1fr;
        gap: 30px;
        text-align: center;
    }

    .footer-title::after {
        left: 50%;
        transform: translateX(-50%);
    }

    .footer-list {
        align-items: center;
    }

    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }

    .footer-socials {
        justify-content: center;
    }
}
</style>
