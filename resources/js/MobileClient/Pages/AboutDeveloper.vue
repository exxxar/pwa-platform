<template>
    <div class="about-page container pt-4 pb-2">

        <!-- Заголовок и описание системы -->
        <div class="text-center mb-5 fade-in">
            <div class="developer-logo mx-auto mb-3 d-flex align-items-center justify-content-center">
                <i class="fa-solid fa-rocket fa-2x text-white"></i>
            </div>
            <h2 class="fw-bold mb-3">О платформе</h2>
            <p class="text-secondary lead" style="font-size: 1rem; max-width: 600px; margin: 0 auto;">
                Мы создаем современные PWA-решения для малого и среднего бизнеса.
                Ваш магазин, кофейня или сервис работают как нативное приложение,
                но без сложной публикации в App Store и Google Play.
            </p>
        </div>

        <!-- Ключевые возможности (Кратко) -->
        <div class="row g-3 mb-5 fade-in" style="animation-delay: 0.1s;">
            <div class="col-6">
                <div class="feature-box text-center p-3 h-100 bg-white rounded-4 shadow-sm border">
                    <i class="fa-solid fa-bolt fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1">Запуск за 1 день</h6>
                    <small class="text-muted">Быстрая настройка и старт продаж</small>
                </div>
            </div>
            <div class="col-6">
                <div class="feature-box text-center p-3 h-100 bg-white rounded-4 shadow-sm border">
                    <i class="fa-solid fa-gift fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1">Лояльность</h6>
                    <small class="text-muted">Кэшбэк, бонусы и акции</small>
                </div>
            </div>
            <div class="col-6">
                <div class="feature-box text-center p-3 h-100 bg-white rounded-4 shadow-sm border">
                    <i class="fa-solid fa-credit-card fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1">Оплата СБП</h6>
                    <small class="text-muted">Минимальная комиссия</small>
                </div>
            </div>
            <div class="col-6">
                <div class="feature-box text-center p-3 h-100 bg-white rounded-4 shadow-sm border">
                    <i class="fa-solid fa-plug fa-2x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1">Интеграции</h6>
                    <small class="text-muted">iiko, FrontPad, 1С, CRM</small>
                </div>
            </div>
        </div>

        <!-- Блок контактов -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden fade-in" style="animation-delay: 0.2s;">
            <div class="card-header bg-warning bg-gradient text-white border-0 py-3">
                <h5 class="mb-0 fw-bold text-center">
                    <i class="fa-regular fa-envelope me-2"></i> Связаться с нами
                </h5>
            </div>
            <div class="card-body p-4">
                <p class="text-center text-muted mb-4">
                    Есть вопросы, предложения или хотите заказать разработку?
                    Мы всегда на связи!
                </p>

                <div class="d-flex flex-column gap-3">
                    <!-- Telegram -->
                    <a :href="contacts.telegram" target="_blank" class="contact-item d-flex align-items-center p-3 rounded-3 text-decoration-none">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fa-brands fa-telegram fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark">Telegram</div>
                            <small class="text-muted">{{ contacts.telegramLabel }}</small>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>

                    <!-- Email -->
                    <a :href="'mailto:' + contacts.email" class="contact-item d-flex align-items-center p-3 rounded-3 text-decoration-none">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger me-3">
                            <i class="fa-solid fa-at fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark">Электронная почта</div>
                            <small class="text-muted">{{ contacts.email }}</small>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>

                    <!-- Телефон -->
                    <a :href="'tel:' + contacts.phoneClean" class="contact-item d-flex align-items-center p-3 rounded-3 text-decoration-none">
                        <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                            <i class="fa-solid fa-phone fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark">Телефон</div>
                            <small class="text-muted">{{ contacts.phone }}</small>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small"></i>
                    </a>
                </div>

                <!-- Кнопка действия -->
                <div class="text-center mt-4">
                    <a :href="contacts.website" target="_blank" class="btn btn-dark btn-lg w-100 rounded-pill p-3 dev-link-btn">
                        <i class="fa-solid fa-globe me-2"></i> Перейти на сайт разработчика
                    </a>
                </div>
            </div>
        </div>


    </div>
</template>

<script>
export default {
    name: "AboutDeveloper",

    data() {
        return {
            currentYear: new Date().getFullYear(),
            // Легко редактируемые контакты
            contacts: {
                telegram: 'https://t.me/your_username', // Замени на свой
                telegramLabel: '@your_username',
                email: 'support@pwa-store.ru',
                phone: '+7 (999) 123-45-67',
                phoneClean: '+79991234567', // Для ссылки tel:
                website: 'https://your-website.ru'
            }
        }
    }
}
</script>

<style scoped>
/* Анимация появления */
.fade-in {
    opacity: 0;
    animation: fadeUp 0.6s ease-out forwards;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Логотип разработчика */
.developer-logo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff7a00 0%, #ffb300 100%);
    box-shadow: 0 8px 20px rgba(255, 138, 0, 0.3);
}

/* Блоки возможностей */
.feature-box {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.feature-box:active {
    transform: scale(0.97);
}

/* Элементы контактов */
.contact-item {
    background-color: #f8f9fa;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.contact-item:hover, .contact-item:active {
    background-color: #fff;
    border-color: #ff8a00;
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(255, 138, 0, 0.1);
}

.icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dev-link-btn {
    line-height: 100%;
    font-size: 16px;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
