<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>КэшМен — Мобильный магазин за 1 день</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #ff7a00;
            --primary-dark: #e56f00;
            --primary-light: #ffb300;
            --accent: #f4c542;
            --dark: #0f0f14;
            --dark-2: #1a1a23;
            --dark-3: #252531;
            --light: #fffdf8;
            --gray: #6c757d;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --gradient-primary: linear-gradient(135deg, #ff7a00 0%, #ff9500 50%, #ffb300 100%);
            --gradient-dark: linear-gradient(135deg, #0f0f14 0%, #1a1a23 100%);
            --gradient-accent: linear-gradient(135deg, #f4c542 0%, #ff9500 100%);
            --shadow-sm: 0 5px 15px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.2);
            --shadow-glow: 0 0 40px rgba(255, 122, 0, 0.3);
            --radius: 20px;
            --radius-sm: 12px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #2c2c2c;
            background: var(--light);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* ============ SCROLLBAR ============ */
        ::-webkit-scrollbar {
            width: 10px;
        }
        ::-webkit-scrollbar-track {
            background: var(--dark);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--gradient-primary);
            border-radius: 10px;
        }

        /* ============ NAVBAR ============ */
        .navbar {
            background: rgba(15, 15, 20, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .navbar.scrolled {
            padding: 0.5rem 0;
            background: rgba(15, 15, 20, 0.95);
            box-shadow: var(--shadow-md);
        }

        .navbar-brand {
            font-weight: 900;
            font-size: 1.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand i {
            -webkit-text-fill-color: var(--primary);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 1rem !important;
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--gradient-primary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 80%;
        }

        .nav-cta {
            background: var(--gradient-primary);
            color: white !important;
            padding: 0.6rem 1.5rem !important;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
        }

        .nav-cta::after {
            display: none;
        }

        /* ============ HERO ============ */
        /* ============ FUTURISTIC HERO BACKGROUND ============ */
        .hero {
            background: radial-gradient(ellipse at 30% 0%, #1a1a23 0%, #0f0f14 70%);
        }

        .hero-bg-effects {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        /* 3D Сетка */
        .hero-grid {
            position: absolute;
            bottom: -20%;
            left: -10%;
            width: 120%;
            height: 70%;
            background-image:
                linear-gradient(rgba(255, 122, 0, 0.15) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 122, 0, 0.15) 1px, transparent 1px);
            background-size: 80px 80px;
            transform: perspective(800px) rotateX(65deg);
            animation: gridMove 30s linear infinite;
            will-change: background-position;
        }

        .hero-grid::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, transparent 0%, #0f0f14 80%);
        }

        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 0 80px; }
        }

        /* Светящиеся сферы */
        .hero-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.45;
            animation: orbFloat 14s ease-in-out infinite;
            will-change: transform, opacity;
        }
        .hero-orb--1 { width: 400px; height: 400px; background: #ff7a00; top: -15%; left: -10%; animation-delay: 0s; }
        .hero-orb--2 { width: 300px; height: 300px; background: #f4c542; bottom: -10%; right: -5%; animation-delay: -4.5s; }
        .hero-orb--3 { width: 220px; height: 220px; background: #ff9500; top: 35%; right: 25%; animation-delay: -9s; }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
            50% { transform: translate(35px, -45px) scale(1.15); opacity: 0.6; }
        }

        /* Сканирующая линия */
        .hero-scanline {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 122, 0, 0.7), transparent);
            box-shadow: 0 0 25px rgba(255, 122, 0, 0.6), 0 0 50px rgba(255, 122, 0, 0.3);
            animation: scanMove 7s ease-in-out infinite;
            will-change: top, opacity;
        }

        @keyframes scanMove {
            0% { top: 0; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        /* Пульсирующие кольца */
        .hero-pulse {
            position: absolute;
            top: 50%; left: 50%;
            width: 600px; height: 600px;
            margin: -300px 0 0 -300px;
            border: 1px solid rgba(255, 122, 0, 0.25);
            border-radius: 50%;
            animation: pulseRing 6s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
            will-change: transform, opacity;
        }
        .hero-pulse:nth-child(2) { animation-delay: 2s; }
        .hero-pulse:nth-child(3) { animation-delay: 4s; }

        @keyframes pulseRing {
            0% { transform: scale(0.1); opacity: 1; border-width: 2px; }
            100% { transform: scale(2.8); opacity: 0; border-width: 0px; }
        }

        /* Контент поверх анимаций */
        .hero .container,
        .hero .row,
        .hero .phone-showcase {
            position: relative;
            z-index: 2;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(50px, -50px) scale(1.1); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 122, 0, 0.1);
            border: 1px solid rgba(255, 122, 0, 0.3);
            color: var(--primary);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            animation: fadeInUp 0.8s ease;
        }

        .hero h1 {
            font-weight: 900;
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            line-height: 1.1;
            color: white;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .hero h1 .gradient-text {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero .lead {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2rem;
            max-width: 600px;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.6s both;
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

        /* ============ BUTTONS ============ */
        .btn {
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.9rem 2rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.4);
            color: white;
        }

        .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            background: transparent;
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        .btn-dark {
            background: var(--dark);
            color: white;
        }

        .btn-dark:hover {
            background: var(--dark-2);
            transform: translateY(-3px);
            color: white;
        }

        /* ============ PHONE MOCKUP FIX ============ */
        .phone-showcase {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeInUp 1s ease 0.8s both;
        }

        .phone-frame {
            position: relative;
            width: 320px;
            height: 650px;
            background: linear-gradient(145deg, #2b3448, #131822);
            border-radius: 50px;
            padding: 15px;
            border: 2px solid #2e384d;
            box-shadow:
                0 50px 100px rgba(0, 0, 0, 0.4),
                0 0 0 10px rgba(255, 255, 255, 0.05),
                inset 0 0 20px rgba(0, 0, 0, 0.3);
            transform: rotate(-5deg);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            animation: phoneFloat 6s ease-in-out infinite;
            overflow: hidden; /* КРИТИЧНО: обрезает всё что выходит за границы */
        }

        @keyframes phoneFloat {
            0%, 100% { transform: rotate(-5deg) translateY(0); }
            50% { transform: rotate(-5deg) translateY(-20px); }
        }

        .phone-frame:hover {
            transform: rotate(0deg) translateY(-10px) scale(1.02);
            box-shadow:
                0 60px 120px rgba(0, 0, 0, 0.5),
                0 0 0 10px rgba(255, 122, 0, 0.1),
                inset 0 0 20px rgba(0, 0, 0, 0.3);
        }

        /* Контейнер для iframe с правильной обрезкой */
        .phone-screen-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 38px;
            overflow: hidden; /* Обрезает содержимое iframe */
            background: white;
        }

        .phone-screen {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 0; /* Убираем radius с iframe, он будет на wrapper */
            background: white;
            display: block;
            position: absolute;
            top: 0;
            left: 0;
        }

        .phone-notch {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 25px;
            background: #000;
            border-radius: 0 0 20px 20px;
            z-index: 10;
            pointer-events: none; /* Не блокирует клики */
        }

        .phone-home {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 5px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.3);
            z-index: 10;
            pointer-events: none;
        }

        /* Дополнительная защита от выхода контента */
        .phone-frame * {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Если нужно скрыть скроллбары внутри iframe (для некоторых браузеров) */
        .phone-screen::-webkit-scrollbar {
            display: none;
        }

        .phone-screen {
            overflow: hidden;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* ============ SECTIONS ============ */
        section {
            position: relative;
            padding: 100px 0;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 122, 0, 0.1);
            color: var(--primary);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 122, 0, 0.2);
        }

        .section-title {
            font-weight: 900;
            font-size: clamp(2rem, 4vw, 3rem);
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .section-title.gradient {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto 3rem;
        }

        .bg-dark-section {
            background: var(--gradient-dark);
            color: white;
        }

        .bg-dark-section .section-title {
            color: white;
        }

        .bg-dark-section .section-subtitle {
            color: rgba(255, 255, 255, 0.7);
        }

        .bg-light-section {
            background: linear-gradient(180deg, #fffdf8 0%, #fff7ed 100%);
        }

        /* ============ CARDS ============ */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        }

        .glass-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 122, 0, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .feature-card {
            background: white;
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feature-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .feature-card:hover::after {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 0, 0.1));
            margin-bottom: 1.5rem;
            transition: all 0.4s ease;
        }

        .feature-card:hover .feature-icon {
            background: var(--gradient-primary);
            transform: rotate(10deg) scale(1.1);
        }

        .feature-icon i {
            font-size: 2rem;
            color: var(--primary);
            transition: all 0.4s ease;
        }

        .feature-card:hover .feature-icon i {
            color: white;
        }

        /* ============ STEPS ============ */
        .step-card {
            text-align: center;
            padding: 2rem 1.5rem;
            position: relative;
        }

        .step-number {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            font-size: 2rem;
            font-weight: 900;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.3);
            position: relative;
            z-index: 2;
        }

        .step-card::before {
            content: '';
            position: absolute;
            top: 60px;
            left: 50%;
            width: calc(100% - 80px);
            height: 2px;
            background: linear-gradient(90deg, var(--primary), transparent);
            z-index: 1;
        }

        .step-card:last-child::before {
            display: none;
        }

        @media (max-width: 768px) {
            .step-card::before {
                display: none;
            }
        }

        /* ============ BUSINESS CARDS ============ */
        .business-card {
            background: linear-gradient(135deg, var(--dark-2), var(--dark-3));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            transition: all 0.4s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .business-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 122, 0, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .business-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(255, 122, 0, 0.2);
        }

        .business-card:hover::before {
            opacity: 1;
        }

        .business-card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            transition: all 0.4s ease;
        }

        .business-card:hover i {
            transform: scale(1.2) rotate(10deg);
        }

        .business-card h5 {
            color: white;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .business-card p {
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
            font-size: 0.9rem;
        }

        /* ============ PRICING ============ */
        .pricing-card {
            background: linear-gradient(135deg, var(--dark-2), var(--dark-3));
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            color: white;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 122, 0, 0.1), transparent 70%);
            border-radius: 50%;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(255, 122, 0, 0.2);
        }

        .pricing-card.popular {
            border-color: var(--primary);
            transform: scale(1.05);
            background: linear-gradient(135deg, #1a1a23, #252531);
        }

        .pricing-card.popular:hover {
            transform: scale(1.05) translateY(-10px);
        }

        .popular-badge {
            position: absolute;
            top: 20px;
            right: -35px;
            background: var(--gradient-primary);
            color: white;
            padding: 0.4rem 3rem;
            font-size: 0.75rem;
            font-weight: 700;
            transform: rotate(45deg);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .pricing-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .pricing-price {
            font-size: 3rem;
            font-weight: 900;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .pricing-currency {
            font-size: 1.5rem;
            opacity: 0.7;
        }

        .pricing-period {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem;
            flex-grow: 1;
        }

        .pricing-features li {
            padding: 0.8rem 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        .pricing-features i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* ============ STATS ============ */
        .stats-section {
            margin-top: -80px;
            position: relative;
            z-index: 10;
        }

        .stats-wrapper {
            background: white;
            border-radius: 30px;
            padding: 60px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.1);
        }

        .stat-item {
            text-align: center;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 0, 0.1));
            margin: 0 auto 1rem;
        }

        .stat-icon i {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 0.3rem;
        }

        .stat-text {
            color: var(--gray);
            font-size: 0.95rem;
        }

        /* ============ TESTIMONIALS ============ */
        .testimonial-card {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .testimonial-rating {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .testimonial-text {
            color: var(--dark);
            font-style: italic;
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .testimonial-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .testimonial-name {
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        .testimonial-role {
            color: var(--gray);
            font-size: 0.85rem;
            margin: 0;
        }

        /* ============ FAQ ============ */
        .faq-item {
            background: linear-gradient(135deg, var(--dark-2), var(--dark-3));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            margin-bottom: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            border-color: rgba(255, 122, 0, 0.3);
        }

        .faq-button {
            background: transparent;
            border: none;
            color: white;
            padding: 1.5rem;
            width: 100%;
            text-align: left;
            font-weight: 600;
            font-size: 1.05rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .faq-button:hover {
            color: var(--primary);
        }

        .faq-button::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            transition: transform 0.3s ease;
            color: var(--primary);
        }

        .faq-button[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 1.5rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
        }

        /* ============ CTA ============ */
        .cta-section {
            background: var(--gradient-primary);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1), transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .cta-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .cta-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        /* ============ FOOTER ============ */
        .footer {
            background: var(--dark);
            color: white;
            padding: 80px 0 30px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }

        .footer-logo {
            font-weight: 900;
            font-size: 1.8rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-logo i {
            -webkit-text-fill-color: var(--primary);
        }

        .footer h5 {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: white;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-links a:hover {
            color: var(--primary);
            transform: translateX(5px);
        }

        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: white;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background: var(--gradient-primary);
            border-color: transparent;
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid var(--glass-border);
            padding-top: 2rem;
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            color: rgba(255, 255, 255, 0.6);
        }

        /* ============ SCROLL TO TOP ============ */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.3);
            z-index: 999;
            border: none;
        }

        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.4);
        }

        /* ============ ANIMATIONS ============ */
        .reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 992px) {
            .hero {
                padding: 100px 0 60px;
                text-align: center;
            }

            .hero .lead {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-buttons {
                justify-content: center;
            }

            .phone-frame {
                width: 280px;
                height: 560px;
                margin-top: 3rem;
            }

            .pricing-card.popular {
                transform: scale(1);
            }

            .pricing-card.popular:hover {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 768px) {
            section {
                padding: 60px 0;
            }

            .stats-wrapper {
                padding: 40px 20px;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }

        /* ============ MODAL ============ */
        .modal-content {
            background: linear-gradient(135deg, var(--dark-2), var(--dark-3));
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            color: white;
        }

        .modal-header {
            border-bottom: 1px solid var(--glass-border);
        }

        .modal-footer {
            border-top: 1px solid var(--glass-border);
        }

        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: white;
            border-radius: 12px;
            padding: 0.8rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.25);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        /* ============ CUSTOM DROPDOWN ============ */
        .custom-dropdown {
            position: relative;
        }

        .custom-dropdown .dropdown-toggle {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: white;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .custom-dropdown .dropdown-toggle:hover,
        .custom-dropdown .dropdown-toggle:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.25);
        }

        .custom-dropdown .dropdown-toggle::after {
            margin-left: auto;
            transition: transform 0.3s ease;
        }

        .custom-dropdown .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }

        .custom-dropdown .dropdown-toggle.placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .custom-dropdown .dropdown-menu {
            width: 100%;
            background: linear-gradient(135deg, #1a1a23, #252531);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            max-height: 300px;
            overflow-y: auto;
        }

        .custom-dropdown .dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .custom-dropdown .dropdown-menu::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-dropdown .dropdown-menu::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .custom-dropdown .dropdown-item {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.7rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .custom-dropdown .dropdown-item:hover,
        .custom-dropdown .dropdown-item:focus {
            background: rgba(255, 122, 0, 0.15);
            color: white;
        }

        .custom-dropdown .dropdown-item.active,
        .custom-dropdown .dropdown-item:active {
            background: var(--gradient-primary);
            color: white;
        }

        .custom-dropdown .dropdown-item i {
            width: 20px;
            text-align: center;
            color: var(--primary);
        }

        .custom-dropdown .dropdown-item.active i,
        .custom-dropdown .dropdown-item:active i {
            color: white;
        }

        .custom-dropdown .dropdown-item .check-icon {
            margin-left: auto;
            opacity: 0;
            transition: opacity 0.2s ease;
            color: var(--primary);
        }

        .custom-dropdown .dropdown-item.active .check-icon {
            opacity: 1;
            color: white;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fa-solid fa-store"></i>
            КэшМен
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="fa-solid fa-bars text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="#features">Возможности</a></li>
                <li class="nav-item"><a class="nav-link" href="#business">Для бизнеса</a></li>
                <li class="nav-item"><a class="nav-link" href="#pricing">Тарифы</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                <li class="nav-item ms-lg-3">
                    <a class="nav-link nav-cta" data-bs-toggle="modal" data-bs-target="#leadModal">
                        Оставить заявку
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">

    <div class="hero-bg-effects">
        <div class="hero-grid"></div>
        <div class="hero-orb hero-orb--1"></div>
        <div class="hero-orb hero-orb--2"></div>
        <div class="hero-orb hero-orb--3"></div>
        <div class="hero-scanline"></div>
        <div class="hero-pulse"></div>
        <div class="hero-pulse"></div>
        <div class="hero-pulse"></div>
    </div>

    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <div class="hero-badge">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    Мобильный магазин за 1 день
                </div>
                <h1>
                    Ваш магазин<br>
                    в <span class="gradient-text">телефоне клиента</span>
                </h1>
                <p class="lead">
                    Создайте современный PWA-магазин для кафе, доставки, автосервиса или любого бизнеса.
                    Клиенты оформляют заказы прямо со смартфона без установки приложения.
                </p>
                <div class="hero-buttons">
                    <a class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#leadModal">
                        <i class="fa-solid fa-eye me-2"></i>
                        Посмотреть демо
                    </a>
                    <a class="btn btn-outline-light btn-lg" data-bs-toggle="modal" data-bs-target="#leadModal">
                        <i class="fa-solid fa-paper-plane me-2"></i>
                        Заказать запуск
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="phone-showcase">
                    <div class="phone-frame">
                        <div class="phone-notch"></div>

                        <!-- Новый wrapper для iframe -->
                        <div class="phone-screen-wrapper">
                            <iframe src="/pwa" class="phone-screen" loading="lazy"></iframe>
                        </div>

                        <div class="phone-home"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-section">
    <div class="container">
        <div class="stats-wrapper reveal">
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div class="stat-number" data-count="50">0</div>
                        <div class="stat-text">типов бизнеса</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fa-solid fa-mobile-screen"></i>
                        </div>
                        <div class="stat-number" data-count="100">0</div>
                        <div class="stat-text">% мобильная адаптация</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div class="stat-number">1 день</div>
                        <div class="stat-text">на запуск проекта</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <div class="stat-number">24/7</div>
                        <div class="stat-text">приём заказов</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section id="features" class="bg-light-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-route"></i>
                Как это работает
            </span>
            <h2 class="section-title">От заявки до первого заказа — за 1 день</h2>
            <p class="section-subtitle">
                Мы берём на себя всю техническую часть. Вам остаётся только принимать заказы.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3 reveal">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5 class="fw-bold">Заявка</h5>
                    <p class="text-secondary">
                        Оставляете контакт — связываемся в течение 15 минут, уточняем задачи.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5 class="fw-bold">Настройка</h5>
                    <p class="text-secondary">
                        Загружаем меню, подключаем iiko/СБП, настраиваем лояльность и дизайн.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5 class="fw-bold">Запуск</h5>
                    <p class="text-secondary">
                        Выдаём QR-код, ссылку и иконку на рабочий стол телефона клиента.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h5 class="fw-bold">Поддержка</h5>
                    <p class="text-secondary">
                        Сопровождаем 24/7, обновляем функционал, помогаем с маркетингом.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BUSINESS TYPES -->
<section id="business">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-briefcase"></i>
                Готовые решения
            </span>
            <h2 class="section-title">Для каких бизнесов подходит?</h2>
            <p class="section-subtitle">
                Практически для любого бизнеса, который продаёт товары, оказывает услуги или принимает заказы онлайн.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="business-card">
                    <i class="fa-solid fa-burger"></i>
                    <h5>Кафе и рестораны</h5>
                    <p>Меню, доставка и онлайн-заказы</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="business-card">
                    <i class="fa-solid fa-mug-hot"></i>
                    <h5>Кофейни</h5>
                    <p>Предзаказ и программа лояльности</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="business-card">
                    <i class="fa-solid fa-cake-candles"></i>
                    <h5>Кондитерские</h5>
                    <p>Торты и праздничные заказы</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="business-card">
                    <i class="fa-solid fa-car"></i>
                    <h5>Автосервисы</h5>
                    <p>Онлайн-запись и каталог услуг</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="business-card">
                    <i class="fa-solid fa-scissors"></i>
                    <h5>Салоны красоты</h5>
                    <p>Запись клиентов и услуги</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="business-card">
                    <i class="fa-solid fa-user-gear"></i>
                    <h5>Самозанятые</h5>
                    <p>Приём заявок и продажа услуг</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="business-card">
                    <i class="fa-solid fa-gem"></i>
                    <h5>Хендмейд</h5>
                    <p>Авторские изделия и сувениры</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="business-card">
                    <i class="fa-solid fa-store"></i>
                    <h5>Розничная торговля</h5>
                    <p>Магазины и точки продаж</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- LOYALTY -->
<section class="bg-dark-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-heart"></i>
                Лояльность и геймификация
            </span>
            <h2 class="section-title gradient">Клиенты возвращаются снова и снова</h2>
            <p class="section-subtitle">
                Встроенные механики удержания, которые превращают разовых покупателей в постоянных.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4 reveal">
                <div class="glass-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <h5 class="text-white fw-bold">Многоуровневая лояльность</h5>
                    <p class="text-white-50 mb-0">
                        Silver, Gold, Platinum — чем чаще клиент заказывает, тем выше его статус и привилегии.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="glass-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <h5 class="text-white fw-bold">Кэшбэк бонусами</h5>
                    <p class="text-white-50 mb-0">
                        Процент с каждого заказа возвращается на бонусный счёт и мотивирует к повторным покупкам.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="glass-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-mug-saucer"></i>
                    </div>
                    <h5 class="text-white fw-bold">«6-я чашка в подарок»</h5>
                    <p class="text-white-50 mb-0">
                        Классическая механика штампов, которая идеально работает для кофеен и пекарен.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="glass-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-circle-nodes"></i>
                    </div>
                    <h5 class="text-white fw-bold">Реферальная программа</h5>
                    <p class="text-white-50 mb-0">
                        Клиенты приводят друзей — оба получают бонусы. Бесплатный канал привлечения.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="glass-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-dice"></i>
                    </div>
                    <h5 class="text-white fw-bold">Колесо Фортуны</h5>
                    <p class="text-white-50 mb-0">
                        Игровая механика с розыгрышем скидок и бонусов. Повышает вовлечённость в разы.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="glass-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-map"></i>
                    </div>
                    <h5 class="text-white fw-bold">Квесты и квизы</h5>
                    <p class="text-white-50 mb-0">
                        Задания для клиентов и интерактивные опросы — ещё один способ выдать бонус.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="bg-light-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-star"></i>
                Отзывы клиентов
            </span>
            <h2 class="section-title">Что говорят наши клиенты</h2>
            <p class="section-subtitle">
                Более 500 бизнесов уже используют КэшМен для увеличения продаж
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4 reveal">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        «Запустили магазин за один день! Клиенты в восторге от удобства,
                        количество повторных заказов выросло на 40%.»
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">АК</div>
                        <div>
                            <p class="testimonial-name">Алексей Козлов</p>
                            <p class="testimonial-role">Владелец кофейни «Бодрое утро»</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        «Интеграция с iiko прошла без проблем. Теперь все заказы автоматически
                        попадают на кухню, а клиенты получают бонусы.»
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">МС</div>
                        <div>
                            <p class="testimonial-name">Мария Соколова</p>
                            <p class="testimonial-role">Управляющая рестораном «Вкусно и точка»</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        «Push-уведомления — это находка! Акции и скидки доходят до клиентов
                        мгновенно. Выручка выросла на 35% за первый месяц.»
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">ДП</div>
                        <div>
                            <p class="testimonial-name">Дмитрий Петров</p>
                            <p class="testimonial-role">Сеть пиццерий «ПиццаМания»</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section id="pricing" class="bg-dark-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-tags"></i>
                Тарифные планы
            </span>
            <h2 class="section-title gradient">Выберите подходящий тариф</h2>
            <p class="section-subtitle">
                Начните бесплатно и масштабируйтесь по мере роста бизнеса
            </p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6 reveal">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h4 class="pricing-title">Старт</h4>
                        <div class="pricing-price">
                            <span class="pricing-currency">₽</span>0
                        </div>
                        <div class="pricing-period">14 дней бесплатно</div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa-solid fa-check"></i> До 50 товаров</li>
                        <li><i class="fa-solid fa-check"></i> Каталог и корзина</li>
                        <li><i class="fa-solid fa-check"></i> Онлайн-заказы</li>
                        <li><i class="fa-solid fa-check"></i> Иконка на рабочий стол</li>
                        <li><i class="fa-solid fa-check"></i> WELCOME-бонус</li>
                        <li><i class="fa-solid fa-check"></i> Техподдержка</li>
                    </ul>
                    <button class="btn btn-outline-light w-100">Начать бесплатно</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h4 class="pricing-title">Базовый</h4>
                        <div class="pricing-price">990</div>
                        <div class="pricing-period">в месяц</div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa-solid fa-check"></i> До 500 товаров</li>
                        <li><i class="fa-solid fa-check"></i> Push-рассылки</li>
                        <li><i class="fa-solid fa-check"></i> Промокоды и акции</li>
                        <li><i class="fa-solid fa-check"></i> Оплата СБП</li>
                        <li><i class="fa-solid fa-check"></i> Аналитика</li>
                        <li><i class="fa-solid fa-check"></i> UTM-метки</li>
                        <li><i class="fa-solid fa-check"></i> Поддержка 24/7</li>
                    </ul>
                    <button class="btn btn-outline-light w-100">Подключить</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="pricing-card popular">
                    <div class="popular-badge">Популярный</div>
                    <div class="pricing-header">
                        <h4 class="pricing-title">Бизнес</h4>
                        <div class="pricing-price">2490</div>
                        <div class="pricing-period">в месяц</div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa-solid fa-check"></i> Без ограничений товаров</li>
                        <li><i class="fa-solid fa-check"></i> Многоуровневая лояльность</li>
                        <li><i class="fa-solid fa-check"></i> Кэшбэк и «6-я чашка»</li>
                        <li><i class="fa-solid fa-check"></i> Колесо Фортуны, квесты</li>
                        <li><i class="fa-solid fa-check"></i> Реферальная программа</li>
                        <li><i class="fa-solid fa-check"></i> CRM-модуль</li>
                        <li><i class="fa-solid fa-check"></i> Интеграция с iiko / 1С</li>
                        <li><i class="fa-solid fa-check"></i> API-доступ</li>
                    </ul>
                    <button class="btn btn-primary w-100">Выбрать тариф</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h4 class="pricing-title">Enterprise</h4>
                        <div class="pricing-price">Инд.</div>
                        <div class="pricing-period">по запросу</div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fa-solid fa-check"></i> Всё из тарифа «Бизнес»</li>
                        <li><i class="fa-solid fa-check"></i> White Label / свой бренд</li>
                        <li><i class="fa-solid fa-check"></i> Выделенный сервер</li>
                        <li><i class="fa-solid fa-check"></i> Кастомные интеграции</li>
                        <li><i class="fa-solid fa-check"></i> Персональный менеджер</li>
                        <li><i class="fa-solid fa-check"></i> SLA и приоритетная поддержка</li>
                    </ul>
                    <button class="btn btn-outline-light w-100">Связаться</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section id="faq">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-circle-question"></i>
                FAQ
            </span>
            <h2 class="section-title">Частые вопросы</h2>
            <p class="section-subtitle">
                Ответы на самые популярные вопросы о нашей платформе
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion" id="faqAccordion">
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Нужно ли публиковать приложение в App Store / Google Play?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Нет. PWA устанавливается прямо из браузера в один клик и работает как обычное приложение —
                                с иконкой на рабочем столе и push-уведомлениями. Никаких комиссий Apple/Google.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Как быстро можно запустить магазин?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Базовый запуск — за 1 день. Если нужны интеграции с iiko/FrontPad или 1С — 2–3 дня
                                на настройку и тестирование.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                У меня уже есть сайт. PWA его заменит?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                PWA не заменяет сайт, а дополняет его. Это мобильный канал продаж с программой лояльности,
                                который работает там, где обычный сайт проигрывает — в смартфоне клиента.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Как подключается iiko / FrontPad / СБП?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Всё настраиваем мы: получаем API-ключи, синхронизируем меню и заказы, тестируем оплаты.
                                С вашей стороны — только доступы к личным кабинетам.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Что будет, если я захочу переехать на другой тариф?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Переход между тарифами — в один клик в личном кабинете. Все данные, клиенты и история
                                заказов сохраняются.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content reveal">
            <h2 class="cta-title">Готовы запустить собственный магазин?</h2>
            <p class="cta-text">
                Получите демонстрацию системы и узнайте, как начать принимать заказы уже сегодня.
            </p>
            <a class="btn btn-dark btn-lg" data-bs-toggle="modal" data-bs-target="#leadModal">
                <i class="fa-solid fa-rocket me-2"></i>
                Получить демо-доступ
            </a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-logo">
                    <i class="fa-solid fa-store"></i>
                    КэшМен
                </div>
                <p class="text-white-50">
                    Создаем современные PWA-магазины для малого бизнеса: кафе, доставки еды, автосервисов,
                    кондитерских, мастерских и сферы услуг.
                </p>
                <div class="footer-social">
                    <a href="#"><i class="fa-brands fa-telegram"></i></a>
                    <a href="#"><i class="fa-brands fa-vk"></i></a>
                    <a href="#"><i class="fa-brands fa-whatsapp"></i></a>
                    <a href="#"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <h5>Навигация</h5>
                <ul class="footer-links">
                    <li><a href="#">Главная</a></li>
                    <li><a href="#features">Возможности</a></li>
                    <li><a href="#pricing">Тарифы</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-4">
                <h5>Возможности</h5>
                <ul class="footer-links">
                    <li><a href="#">Каталог товаров</a></li>
                    <li><a href="#">Онлайн-заказы</a></li>
                    <li><a href="#">Push-уведомления</a></li>
                    <li><a href="#">Программа лояльности</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-4">
                <h5>Контакты</h5>
                <ul class="footer-links">
                    <li><a href="#"><i class="fa-brands fa-telegram"></i> @your_telegram</a></li>
                    <li><a href="mailto:info@example.com"><i class="fa-solid fa-envelope"></i> info@example.com</a></li>
                    <li><a href="tel:+70000000000"><i class="fa-solid fa-phone"></i> +7 (000) 000-00-00</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© 2026 КэшМен. Все права защищены.</div>
            <div>
                Сделано с <i class="fa-solid fa-heart text-danger"></i> для малого бизнеса
            </div>
        </div>
    </div>
</footer>

<!-- SCROLL TO TOP -->
<button class="scroll-top" id="scrollTop">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- LEAD MODAL -->
<div class="modal fade" id="leadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title fw-bold">
                    <i class="fa-solid fa-rocket text-warning me-2"></i>
                    Оставить заявку
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-white-50 mb-4">
                    Оставьте контакт — свяжемся в течение 15 минут, покажем демо и подберём тариф.
                </p>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Ваше имя</label>
                        <input type="text" class="form-control" placeholder="Иван">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Телефон или Telegram</label>
                        <input type="text" class="form-control" placeholder="+7 (___) ___-__-__ или @username">
                    </div>

                        <div class="mb-4">
                            <label class="form-label">Тип бизнеса</label>

                            <!-- Hidden input для отправки формы -->
                            <input type="hidden" name="business_type" id="businessTypeValue" value="">

                            <!-- Custom Dropdown -->
                            <div class="custom-dropdown">
                                <button
                                    class="dropdown-toggle placeholder"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    id="businessTypeBtn">
                                    <span class="dropdown-text">Выберите тип бизнеса</span>
                                </button>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="cafe">
                                            <i class="fa-solid fa-utensils"></i>
                                            <span>Кафе / Ресторан</span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="coffee">
                                            <i class="fa-solid fa-mug-hot"></i>
                                            <span>Кофейня</span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="pastry">
                                            <i class="fa-solid fa-cake-candles"></i>
                                            <span>Кондитерская</span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="delivery">
                                            <i class="fa-solid fa-motor-scooter"></i>
                                            <span>Доставка еды</span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="auto">
                                            <i class="fa-solid fa-car"></i>
                                            <span>Автосервис</span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="beauty">
                                            <i class="fa-solid fa-scissors"></i>
                                            <span>Салон красоты</span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="retail">
                                            <i class="fa-solid fa-store"></i>
                                            <span>Розничная торговля</span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="other">
                                            <i class="fa-solid fa-briefcase"></i>
                                            <span>Другое</span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="fa-solid fa-paper-plane me-2"></i>
                        Отправить заявку
                    </button>
                    <p class="small text-white-50 text-center mt-3 mb-0">
                        Нажимая кнопку, вы соглашаетесь с политикой конфиденциальности
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Custom dropdown logic
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownItems = document.querySelectorAll('.custom-dropdown .dropdown-item');
        const dropdownBtn = document.getElementById('businessTypeBtn');
        const dropdownText = dropdownBtn.querySelector('.dropdown-text');
        const hiddenInput = document.getElementById('businessTypeValue');

        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();

                // Убираем active со всех
                dropdownItems.forEach(i => i.classList.remove('active'));

                // Добавляем active к выбранному
                this.classList.add('active');

                // Обновляем текст кнопки
                const selectedText = this.querySelector('span').textContent;
                dropdownText.textContent = selectedText;

                // Убираем placeholder стиль
                dropdownBtn.classList.remove('placeholder');

                // Обновляем скрытое поле
                hiddenInput.value = this.dataset.value;
            });
        });

        // Сброс при закрытии модалки
        const leadModal = document.getElementById('leadModal');
        if (leadModal) {
            leadModal.addEventListener('hidden.bs.modal', function() {
                dropdownItems.forEach(i => i.classList.remove('active'));
                dropdownText.textContent = 'Выберите тип бизнеса';
                dropdownBtn.classList.add('placeholder');
                hiddenInput.value = '';
            });
        }
    });

    // Scroll reveal
    const reveals = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, { threshold: 0.1 });

    reveals.forEach(el => revealObserver.observe(el));

    // Animated counters
    const counters = document.querySelectorAll('[data-count]');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.dataset.count);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        entry.target.textContent = target + '+';
                        clearInterval(timer);
                    } else {
                        entry.target.textContent = Math.floor(current) + '+';
                    }
                }, 30);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => counterObserver.observe(counter));

    // Scroll to top
    const scrollTopBtn = document.getElementById('scrollTop');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 500) {
            scrollTopBtn.classList.add('visible');
        } else {
            scrollTopBtn.classList.remove('visible');
        }
    });

    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && document.querySelector(href)) {
                e.preventDefault();
                document.querySelector(href).scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
</script>

</body>
</html>
