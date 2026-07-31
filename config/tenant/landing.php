<?php

return [
    'theme' => [
        // Извлечено из inline-стилей: style="--primary: #ff7a00; --primary-dark: #e56f00; ..."
        'primary' => '#ff7a00',
        'primaryDark' => '#e56f00',
        'primaryLight' => '#ffb300',
        'accent' => '#f4c542',
        'dark' => '#0f0f14',
        'light' => '#fffdf8',
    ],

    'hero' => [
        'badge' => 'Мобильный магазин',
        'title' => 'Свежие продукты с доставкой',
        'subtitle' => 'Выберите заведение и закажите любимые товары',
        'buttonText' => 'Выбрать заведение',
        'backgroundImage' => '', // В вашем HTML используется анимированный фон (blobs/particles), картинку можно оставить пустой или добавить URL
    ],

    'sectionsVisibility' => [
        // Все эти секции присутствуют в предоставленном HTML
        'hero' => true,
        'partners' => true,
        'promotions' => true,
        'delivery' => true,
        'pwaBanner' => true,
        'loyalty' => true,
        'wheel' => true,
        'reviews' => true,
        'faq' => true,
        'reservation' => true,
        'cta' => true,
        'footer' => true,
    ],

    'categories' => [
        // Добавил пару категорий на основе видимых заведений в HTML для красоты дефолта
        ['id' => 1, 'name' => 'Пицца и суши', 'icon' => '🍕'],
        ['id' => 2, 'name' => 'Бургеры', 'icon' => '🍔'],
        ['id' => 3, 'name' => 'Грузинская кухня', 'icon' => '🥟'],
    ],

    'items' => [], // В предоставленном HTML блок товаров пуст (<!----><!---->), оставляем пустым

    'reviews' => [
        // Извлечено из секции .shop-reviews
        [
            'id' => 1,
            'name' => 'Анна К.',
            'avatar' => '/images/avatar1.jpg',
            'rating' => 5,
            'text' => 'Отличный сервис! Заказываю каждую неделю.',
            'date' => 'Недавно',
            'isVerified' => false,
            'likes' => 0,
        ],
        [
            'id' => 2,
            'name' => 'Дмитрий П.',
            'avatar' => '/images/avatar2.jpg',
            'rating' => 5,
            'text' => 'Удобно заказывать через телефон.',
            'date' => 'Недавно',
            'isVerified' => false,
            'likes' => 0,
        ],
    ],

    'reviewsSection' => [
        // Извлечено из заголовка секции отзывов
        'title' => 'Что говорят клиенты',
        'subtitle' => 'Реальные отзывы наших покупателей',
    ],

    'cta' => [
        // Извлечено из секции .cta-section
        'title' => 'Остались вопросы?',
        'text' => 'Свяжитесь с нами — поможем с выбором и расскажем о актуальных акциях',
        'buttonText' => 'Написать нам',
    ],

    'footer' => [
        // Извлечено из секции .shop-footer
        'companyName' => 'Ваш Магазин',
        'description' => 'Доставка свежих продуктов и готовой еды',
        'phone' => '+7 (999) 123-45-67',
        'email' => 'info@example.com',
        'address' => 'г. Москва, ул. Примерная, 1',
        'socialLinks' => [
            ['icon' => 'fa-brands fa-telegram', 'url' => '#'],
            ['icon' => 'fa-brands fa-vk', 'url' => '#'],
        ],
    ],

    'cart' => [
        // Дефолтные значения (так как модалка корзины скрыта в HTML <!---->), но логичные для контекста
        'title' => 'Ваша корзина',
        'emptyText' => 'Корзина пуста. Добавьте что-нибудь вкусное!',
        'checkoutText' => 'Оформить заказ',
        'totalText' => 'Итого к оплате:',
    ],

    'feedbackModal' => [
        // Дефолтные значения для модального окна обратной связи
        'title' => 'Обратная связь',
        'subtitle' => 'Оставьте свои данные, и мы свяжемся с вами в ближайшее время',
        'nameLabel' => 'Ваше имя',
        'phoneLabel' => 'Номер телефона',
        'messageLabel' => 'Сообщение',
        'submitText' => 'Отправить',
    ],

    'privacyModal' => [
        // Дефолтные значения для политики конфиденциальности
        'title' => 'Политика конфиденциальности',
        'content' => '<p>Здесь размещается полный текст политики конфиденциальности и обработки персональных данных...</p>',
    ],
];
