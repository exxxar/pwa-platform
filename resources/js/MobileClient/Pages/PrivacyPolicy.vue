<template>
    <div class="privacy-policy-page">
        <div class="container py-4">

            <!-- Заголовок -->
            <div class="policy-header text-center mb-4">
                <div class="policy-icon mx-auto mb-3">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1 class="fw-bold">Политика конфиденциальности</h1>
                <p class="text-muted">Последнее обновление: {{ lastUpdate }}</p>
            </div>

            <!-- Содержание -->
            <div class="policy-content">

                <section class="policy-section">
                    <h2><i class="fa-solid fa-circle-info"></i> 1. Общие положения</h2>
                    <p>
                        Настоящая Политика конфиденциальности (далее — «Политика») определяет порядок обработки
                        и защиты персональных данных пользователей мобильного приложения «{{ tenantName }}»
                        (далее — «Приложение»).
                    </p>
                    <p>
                        Используя Приложение, вы соглашаетесь с условиями настоящей Политики.
                        Если вы не согласны с какими-либо положениями, пожалуйста, прекратите использование Приложения.
                    </p>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-database"></i> 2. Какие данные мы собираем</h2>
                    <p>Мы можем собирать следующие категории персональных данных:</p>
                    <ul>
                        <li><strong>Контактные данные:</strong> имя, номер телефона, адрес электронной почты</li>
                        <li><strong>Данные о заказах:</strong> история заказов, предпочтения, адреса доставки</li>
                        <li><strong>Технические данные:</strong> IP-адрес, тип устройства, операционная система, идентификатор устройства</li>
                        <li><strong>Данные о местоположении:</strong> (только с вашего разрешения) для определения адреса доставки</li>
                        <li><strong>Платёжные данные:</strong> информация о способах оплаты (без хранения данных банковских карт)</li>
                    </ul>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-bullseye"></i> 3. Цели обработки данных</h2>
                    <p>Мы обрабатываем ваши персональные данные для следующих целей:</p>
                    <ul>
                        <li>Обработка и выполнение заказов</li>
                        <li>Предоставление доступа к функционалу Приложения</li>
                        <li>Уведомление о статусе заказов и акциях</li>
                        <li>Улучшение качества обслуживания</li>
                        <li>Соблюдение требований законодательства</li>
                        <li>Защита прав и законных интересов компании</li>
                    </ul>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-lock"></i> 4. Защита персональных данных</h2>
                    <p>
                        Мы принимаем необходимые организационные и технические меры для защиты ваших персональных данных
                        от несанкционированного доступа, изменения, раскрытия или уничтожения:
                    </p>
                    <ul>
                        <li>Шифрование данных при передаче (SSL/TLS)</li>
                        <li>Ограничение доступа к данным только уполномоченным сотрудникам</li>
                        <li>Регулярный аудит систем безопасности</li>
                        <li>Хранение данных на защищённых серверах</li>
                    </ul>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-users"></i> 5. Передача данных третьим лицам</h2>
                    <p>
                        Мы не передаём ваши персональные данные третьим лицам, за исключением случаев:
                    </p>
                    <ul>
                        <li>Когда это необходимо для выполнения заказа (службы доставки, платёжные системы)</li>
                        <li>Когда это требуется по закону (по запросу государственных органов)</li>
                        <li>Когда вы дали явное согласие на передачу</li>
                    </ul>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-cookie-bite"></i> 6. Использование cookies</h2>
                    <p>
                        Приложение может использовать cookies для обеспечения корректной работы,
                        анализа трафика и персонализации контента. Вы можете отключить cookies
                        в настройках вашего браузера, однако это может повлиять на функциональность Приложения.
                    </p>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-user-check"></i> 7. Ваши права</h2>
                    <p>Вы имеете право:</p>
                    <ul>
                        <li>Получить информацию о хранящихся у нас ваших персональных данных</li>
                        <li>Требовать исправления неточных данных</li>
                        <li>Требовать удаления ваших данных (за исключением случаев, когда хранение необходимо по закону)</li>
                        <li>Отозвать согласие на обработку данных</li>
                        <li>Обжаловать наши действия в уполномоченный орган</li>
                    </ul>
                    <p>
                        Для реализации ваших прав свяжитесь с нами по контактам, указанным в разделе 9.
                    </p>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-child"></i> 8. Защита данных несовершеннолетних</h2>
                    <p>
                        Приложение не предназначено для лиц младше 18 лет. Мы не собираем сознательно
                        персональные данные несовершеннолетних. Если вам стало известно, что несовершеннолетний
                        предоставил нам свои данные, пожалуйста, свяжитесь с нами.
                    </p>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-phone"></i> 9. Контактная информация</h2>
                    <p>
                        По всем вопросам, связанным с обработкой персональных данных, вы можете обратиться:
                    </p>
                    <div class="contact-info">
                        <p v-if="tenantEmail">
                            <i class="fa-solid fa-envelope"></i>
                            Email: <a :href="'mailto:' + tenantEmail">{{ tenantEmail }}</a>
                        </p>
                        <p v-if="tenantPhone">
                            <i class="fa-solid fa-phone"></i>
                            Телефон: <a :href="'tel:' + tenantPhone">{{ tenantPhone }}</a>
                        </p>
                        <p v-if="tenantAddress">
                            <i class="fa-solid fa-location-dot"></i>
                            Адрес: {{ tenantAddress }}
                        </p>
                    </div>
                </section>

                <section class="policy-section">
                    <h2><i class="fa-solid fa-arrows-rotate"></i> 10. Изменение Политики</h2>
                    <p>
                        Мы оставляем за собой право изменять настоящую Политику.
                        Актуальная версия всегда доступна в Приложении.
                        Продолжая использовать Приложение после внесения изменений,
                        вы соглашаетесь с новой редакцией Политики.
                    </p>
                </section>

            </div>

            <!-- Кнопка назад -->
            <div class="text-center mt-4 mb-5">
                <button @click="goBack" class="btn btn-primary btn-lg px-5">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Вернуться назад
                </button>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "PrivacyPolicy",

    computed: {
        tenantName() {
            return window.Tenant?.name || 'Наше приложение';
        },

        tenantEmail() {
            return window.Tenant?.settings?.email || null;
        },

        tenantPhone() {
            return window.Tenant?.settings?.phones?.[0] || null;
        },

        tenantAddress() {
            return window.Tenant?.settings?.address || null;
        },

        lastUpdate() {
            const date = new Date();
            return date.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },
    },

    methods: {
        goBack() {
            if (window.history.length > 1) {
                this.$router.back();
            } else {
                this.$router.push('/menu');
            }
        },
    },
};
</script>

<style scoped>
.privacy-policy-page {
    min-height: 100vh;
    background: var(--bs-body-bg);
    padding-bottom: 40px;
}

.policy-header {
    padding: 30px 0;
}

.policy-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-hover, var(--bs-primary)) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.3);
}

.policy-icon i {
    font-size: 2.5rem;
    color: white;
}

.policy-header h1 {
    font-size: 1.8rem;
    color: var(--bs-body-color);
}

/* Секции политики */
.policy-section {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.2s ease;
}

.policy-section:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: var(--bs-primary);
}

.policy-section h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--bs-primary);
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--bs-border-color);
}

.policy-section h2 i {
    font-size: 1.3rem;
}

.policy-section p {
    color: var(--bs-body-color);
    line-height: 1.7;
    margin-bottom: 12px;
}

.policy-section ul {
    padding-left: 20px;
    margin-bottom: 12px;
}

.policy-section li {
    color: var(--bs-body-color);
    line-height: 1.7;
    margin-bottom: 8px;
}

.policy-section strong {
    color: var(--bs-primary);
}

/* Контактная информация */
.contact-info {
    background: rgba(var(--bs-primary-rgb), 0.05);
    border-left: 4px solid var(--bs-primary);
    padding: 16px;
    border-radius: 8px;
    margin-top: 12px;
}

.contact-info p {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.contact-info p:last-child {
    margin-bottom: 0;
}

.contact-info i {
    color: var(--bs-primary);
    width: 20px;
}

.contact-info a {
    color: var(--bs-primary);
    text-decoration: none;
    font-weight: 600;
}

.contact-info a:hover {
    text-decoration: underline;
}
</style>
