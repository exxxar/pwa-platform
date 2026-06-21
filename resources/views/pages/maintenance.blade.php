<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>КэшМен — Технические работы</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #ff7a00;
            --primary-dark: #e56f00;
            --primary-light: #ffb300;
            --dark: #0f0f14;
            --dark-2: #1a1a23;
            --dark-3: #252531;
            --light: #fffdf8;
            --gray: #6c757d;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            color: white;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ==========================================
           АНИМИРОВАННЫЙ ФОН
           ========================================== */
        .bg-effects {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        /* Градиентные блобы */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.4;
            will-change: transform;
        }

        .blob-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
            top: -15%;
            right: -10%;
            animation: blobFloat 20s ease-in-out infinite;
        }

        .blob-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--primary-light) 0%, transparent 70%);
            bottom: -10%;
            left: -10%;
            animation: blobFloat 25s ease-in-out infinite reverse;
        }

        .blob-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, #8b5cf6 0%, transparent 70%);
            top: 40%;
            left: 50%;
            animation: blobFloat 18s ease-in-out infinite;
            animation-delay: -5s;
        }

        @keyframes blobFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.95); }
        }

        /* Сетка */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 122, 0, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 122, 0, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
        }

        /* Частицы */
        .particles {
            position: absolute;
            inset: 0;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--primary);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--primary);
            animation: particleFloat linear infinite;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% {
                transform: translateY(-10vh) translateX(50px);
                opacity: 0;
            }
        }

        /* ==========================================
           ОСНОВНОЙ КОНТЕНТ
           ========================================== */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        /* Логотип */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 900;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease;
        }

        .logo i {
            color: var(--primary);
            font-size: 1.8rem;
        }

        .logo span {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Шестерёнки */
        .gears-container {
            position: relative;
            width: 240px;
            height: 200px;
            margin: 0 auto 40px;
            animation: fadeIn 1s ease 0.2s both;
        }

        .gear {
            position: absolute;
            color: var(--primary);
            filter: drop-shadow(0 0 20px rgba(255, 122, 0, 0.4));
        }

        .gear-1 {
            font-size: 120px;
            top: 10px;
            left: 20px;
            animation: rotateCW 8s linear infinite;
        }

        .gear-2 {
            font-size: 80px;
            top: 60px;
            right: 30px;
            color: var(--primary-light);
            animation: rotateCCW 6s linear infinite;
        }

        .gear-3 {
            font-size: 50px;
            bottom: 10px;
            left: 80px;
            color: var(--warning);
            animation: rotateCW 4s linear infinite;
        }

        @keyframes rotateCW {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes rotateCCW {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }

        /* Статус-бейдж */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--warning);
            margin-bottom: 24px;
            animation: fadeIn 1s ease 0.4s both;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--warning);
            position: relative;
        }

        .status-dot::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: var(--warning);
            opacity: 0.4;
            animation: statusPulse 2s ease-in-out infinite;
        }

        @keyframes statusPulse {
            0%, 100% { transform: scale(1); opacity: 0.4; }
            50% { transform: scale(1.5); opacity: 0; }
        }

        /* Заголовок */
        .main-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 16px;
            animation: fadeInUp 0.8s ease 0.6s both;
        }

        .main-title .gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .main-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            max-width: 600px;
            margin: 0 auto 40px;
            line-height: 1.6;
            animation: fadeInUp 0.8s ease 0.8s both;
        }

        /* ==========================================
           ТАЙМЕР ОБРАТНОГО ОТСЧЁТА
           ========================================== */
        .countdown {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease 1s both;
        }

        .countdown-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .countdown-value {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            font-weight: 900;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .countdown-value::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .countdown-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* ==========================================
           ПРОГРЕСС-БАР
           ========================================== */
        .progress-section {
            width: 100%;
            max-width: 500px;
            margin: 0 auto 40px;
            animation: fadeInUp 0.8s ease 1.2s both;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 4px;
            position: relative;
            transition: width 1s ease;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* ==========================================
           КНОПКИ
           ========================================== */
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease 1.4s both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.4);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            transform: translateY(-3px);
        }

        /* ==========================================
           ФОРМА ПОДПИСКИ
           ========================================== */
        .subscribe-section {
            width: 100%;
            max-width: 500px;
            margin: 0 auto 40px;
            padding: 24px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            animation: fadeInUp 0.8s ease 1.6s both;
        }

        .subscribe-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .subscribe-title i {
            color: var(--primary);
        }

        .subscribe-desc {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 16px;
        }

        .subscribe-form {
            display: flex;
            gap: 8px;
        }

        .subscribe-input {
            flex: 1;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .subscribe-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
        }

        .subscribe-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .subscribe-btn {
            padding: 12px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .subscribe-btn:hover {
            background: var(--primary-dark);
        }

        .subscribe-success {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            color: var(--success);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .subscribe-success.show {
            display: flex;
        }

        /* ==========================================
           КОНТАКТЫ
           ========================================== */
        .contacts {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease 1.8s both;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .contact-item:hover {
            color: var(--primary);
        }

        .contact-item i {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        /* ==========================================
           ФУТЕР
           ========================================== */
        .footer {
            padding: 20px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }

        .footer-socials {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 12px;
        }

        .footer-socials a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer-socials a:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-3px);
        }

        /* ==========================================
           АНИМАЦИИ
           ========================================== */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ==========================================
           АДАПТИВНОСТЬ
           ========================================== */
        @media (max-width: 640px) {
            .gears-container {
                width: 200px;
                height: 170px;
            }

            .gear-1 { font-size: 100px; }
            .gear-2 { font-size: 65px; }
            .gear-3 { font-size: 40px; }

            .countdown-value {
                width: 65px;
                height: 65px;
                font-size: 1.8rem;
            }

            .countdown {
                gap: 10px;
            }

            .actions {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .subscribe-form {
                flex-direction: column;
            }

            .contacts {
                flex-direction: column;
                gap: 12px;
            }
        }

        /* Уменьшение анимаций для пользователей с соответствующей настройкой */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>

<!-- Анимированный фон -->
<div class="bg-effects">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="grid-overlay"></div>
    <div class="particles" id="particles"></div>
</div>

<!-- Основной контент -->
<main class="main-content">

    <!-- Логотип -->
    <div class="logo">
        <i class="fa-solid fa-store"></i>
        <span>КэшМен</span>
    </div>

    <!-- Анимированные шестерёнки -->
    <div class="gears-container">
        <i class="fa-solid fa-gear gear gear-1"></i>
        <i class="fa-solid fa-gear gear gear-2"></i>
        <i class="fa-solid fa-gear gear gear-3"></i>
    </div>

    <!-- Статус -->
    <div class="status-badge">
        <span class="status-dot"></span>
        <span>Техническое обслуживание</span>
    </div>

    <!-- Заголовок -->
    <h1 class="main-title">
        Мы <span class="gradient">скоро вернёмся</span>
    </h1>
    <p class="main-subtitle">
        Сейчас мы проводим технические работы, чтобы сделать наш сервис ещё лучше.
        Приносим извинения за временные неудобства.
    </p>

    <!-- Таймер -->
    <div class="countdown">
        <div class="countdown-item">
            <div class="countdown-value" id="hours">02</div>
            <div class="countdown-label">Часов</div>
        </div>
        <div class="countdown-item">
            <div class="countdown-value" id="minutes">45</div>
            <div class="countdown-label">Минут</div>
        </div>
        <div class="countdown-item">
            <div class="countdown-value" id="seconds">30</div>
            <div class="countdown-label">Секунд</div>
        </div>
    </div>

    <!-- Прогресс -->
    <div class="progress-section">
        <div class="progress-header">
            <span>Прогресс работ</span>
            <span id="progressPercent">65%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 65%;"></div>
        </div>
    </div>

    <!-- Кнопки -->
    <div class="actions">
        <button class="btn btn-primary" onclick="location.reload()">
            <i class="fa-solid fa-rotate-right"></i>
            Обновить страницу
        </button>
        <a href="mailto:support@example.com" class="btn btn-outline">
            <i class="fa-solid fa-envelope"></i>
            Написать в поддержку
        </a>
    </div>

    <!-- Подписка на уведомления -->
    <div class="subscribe-section">
        <div class="subscribe-title">
            <i class="fa-solid fa-bell"></i>
            <span>Узнать о запуске</span>
        </div>
        <p class="subscribe-desc">
            Оставьте email — мы пришлём уведомление, как только всё заработает
        </p>
        <form class="subscribe-form" id="subscribeForm">
            <input
                type="email"
                class="subscribe-input"
                placeholder="your@email.com"
                required
                id="subscribeEmail"
            >
            <button type="submit" class="subscribe-btn">Подписаться</button>
        </form>
        <div class="subscribe-success" id="subscribeSuccess">
            <i class="fa-solid fa-circle-check"></i>
            <span>Спасибо! Мы уведомим вас о запуске.</span>
        </div>
    </div>

    <!-- Контакты -->
    <div class="contacts">
        <a href="tel:+70000000000" class="contact-item">
            <i class="fa-solid fa-phone"></i>
            <span>+7 (000) 000-00-00</span>
        </a>
        <a href="mailto:support@example.com" class="contact-item">
            <i class="fa-solid fa-envelope"></i>
            <span>support@example.com</span>
        </a>
        <a href="https://t.me/example" class="contact-item" target="_blank">
            <i class="fa-brands fa-telegram"></i>
            <span>@example</span>
        </a>
    </div>

</main>

<!-- Футер -->
<footer class="footer">
    <div class="footer-socials">
        <a href="#" title="Telegram"><i class="fa-brands fa-telegram"></i></a>
        <a href="#" title="VK"><i class="fa-brands fa-vk"></i></a>
        <a href="#" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        <a href="#" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
    </div>
    <div>© 2026 КэшМен. Все права защищены.</div>
</footer>

<script>
    // ==========================================
    // ЧАСТИЦЫ
    // ==========================================
    function createParticles() {
        const container = document.getElementById('particles');
        const particleCount = 30;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.opacity = Math.random() * 0.5 + 0.2;
            container.appendChild(particle);
        }
    }

    // ==========================================
    // ТАЙМЕР ОБРАТНОГО ОТСЧЁТА
    // ==========================================
    // Устанавливаем время окончания (через 2 часа 45 минут 30 секунд от текущего момента)
    // В реальном проекте это время должно приходить с сервера
    const endTime = new Date().getTime() + (2 * 60 * 60 + 45 * 60 + 30) * 1000;

    function updateCountdown() {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            document.getElementById('hours').textContent = '00';
            document.getElementById('minutes').textContent = '00';
            document.getElementById('seconds').textContent = '00';
            return;
        }

        const hours = Math.floor(distance / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById('hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
    }

    // ==========================================
    // ИМИТАЦИЯ ПРОГРЕССА
    // ==========================================
    let currentProgress = 65;

    function updateProgress() {
        // Медленно увеличиваем прогресс (имитация реальной работы)
        if (currentProgress < 95) {
            currentProgress += Math.random() * 0.3;
            currentProgress = Math.min(currentProgress, 95);

            document.getElementById('progressFill').style.width = currentProgress + '%';
            document.getElementById('progressPercent').textContent = Math.floor(currentProgress) + '%';
        }
    }

    // ==========================================
    // ФОРМА ПОДПИСКИ
    // ==========================================
    document.getElementById('subscribeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('subscribeEmail').value;

        // TODO: Здесь должен быть реальный API-запрос
        // fetch('/api/subscribe', { method: 'POST', body: JSON.stringify({ email }) })

        console.log('Подписка на email:', email);

        // Показываем сообщение об успехе
        this.style.display = 'none';
        document.getElementById('subscribeSuccess').classList.add('show');
    });

    // ==========================================
    // ИНИЦИАЛИЗАЦИЯ
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        createParticles();
        updateCountdown();

        // Обновляем таймер каждую секунду
        setInterval(updateCountdown, 1000);

        // Обновляем прогресс каждые 5 секунд
        setInterval(updateProgress, 5000);
    });
</script>

</body>
</html>
