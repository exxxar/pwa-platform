<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>КэшМен Партнёры — Зарабатывай на продаже мобильных магазинов</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<!--
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
-->

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
            --gray-light: #f5f5f7;
            --gradient-primary: linear-gradient(135deg, #ff7a00 0%, #ff9500 50%, #ffb300 100%);
            --gradient-dark: linear-gradient(135deg, #0f0f14 0%, #1a1a23 100%);
            --shadow-sm: 0 5px 15px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.12);
            --shadow-glow: 0 0 30px rgba(255, 122, 0, 0.25);
            --radius: 20px;
            --radius-sm: 12px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            color: #2c2c2c;
            background: var(--light);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* ============ SCROLLBAR ============ */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--gray-light); }
        ::-webkit-scrollbar-thumb { background: var(--gradient-primary); border-radius: 10px; }

        /* ============ NAVBAR (упрощённый) ============ */
        .navbar {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            padding: 1rem 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .navbar.scrolled {
            padding: 0.5rem 0;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand {
            font-weight: 900;
            font-size: 1.5rem;
            color: var(--dark) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand i {
            color: var(--primary);
        }

        .navbar-brand .brand-accent {
            color: var(--primary);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 0.5rem 1rem !important;
        }

        .nav-link:hover {
            color: var(--primary) !important;
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
            color: white !important;
        }

        /* ============ HERO ============ */
        .hero {
            background: linear-gradient(135deg, #fffdf8 0%, #fff7ed 100%);
            padding: 140px 0 100px;
            position: relative;
            overflow: hidden;
        }

        /* Простые декоративные круги вместо сложных эффектов */
        .hero-decor {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            pointer-events: none;
        }
        .hero-decor--1 {
            width: 400px; height: 400px;
            background: var(--primary);
            top: -100px; right: -100px;
        }
        .hero-decor--2 {
            width: 300px; height: 300px;
            background: var(--accent);
            bottom: -80px; left: -80px;
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
            border: 1px solid rgba(255, 122, 0, 0.25);
            color: var(--primary-dark);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease;
        }

        .hero h1 {
            font-weight: 900;
            font-size: clamp(2.5rem, 5vw, 4rem);
            line-height: 1.1;
            color: var(--dark);
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
            font-size: 1.2rem;
            color: var(--gray);
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

        .hero-visual {
            position: relative;
            animation: fadeInUp 1s ease 0.8s both;
        }

        /* Карточка с "заработком" вместо телефона */
        .earnings-card {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            max-width: 420px;
            margin: 0 auto;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .earnings-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: var(--gradient-primary);
            border-radius: 24px 24px 0 0;
        }

        .earnings-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .earnings-title {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        .earnings-period {
            background: rgba(255, 122, 0, 0.1);
            color: var(--primary-dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .earnings-amount {
            font-size: 3rem;
            font-weight: 900;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .earnings-amount .currency {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .earnings-change {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: #10b981;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .earnings-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .earnings-stat {
            text-align: left;
        }

        .earnings-stat-label {
            font-size: 0.75rem;
            color: var(--gray);
            margin-bottom: 0.3rem;
        }

        .earnings-stat-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============ BUTTONS ============ */
        .btn {
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.9rem 2rem;
            border: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.35);
            color: white;
        }

        .btn-outline-dark {
            border: 2px solid var(--dark);
            color: var(--dark);
            background: transparent;
        }

        .btn-outline-dark:hover {
            background: var(--dark);
            color: white;
            transform: translateY(-3px);
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
            color: var(--primary-dark);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 122, 0, 0.2);
        }

        .section-title {
            font-weight: 900;
            font-size: clamp(2rem, 4vw, 2.8rem);
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

        .bg-dark-section .section-title { color: white; }
        .bg-dark-section .section-subtitle { color: rgba(255, 255, 255, 0.7); }

        .bg-light-section {
            background: var(--gray-light);
        }

        /* ============ STATS ============ */
        .stats-section {
            margin-top: -60px;
            position: relative;
            z-index: 10;
        }

        .stats-wrapper {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: var(--shadow-lg);
        }

        .stat-item { text-align: center; }

        .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 0, 0.1));
            margin: 0 auto 1rem;
        }

        .stat-icon i { font-size: 1.5rem; color: var(--primary); }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 0.3rem;
        }

        .stat-text {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* ============ STEPS ============ */
        .step-card {
            text-align: center;
            padding: 2rem 1.5rem;
            position: relative;
        }

        .step-number {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            font-size: 1.8rem;
            font-weight: 900;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.25);
            position: relative;
            z-index: 2;
        }

        .step-card h5 {
            font-weight: 700;
            margin-bottom: 0.8rem;
            color: var(--dark);
        }

        .step-card p {
            color: var(--gray);
            margin: 0;
        }

        .step-card::before {
            content: '';
            position: absolute;
            top: 55px;
            left: 50%;
            width: calc(100% - 70px);
            height: 2px;
            background: linear-gradient(90deg, var(--primary), transparent);
            z-index: 1;
        }

        .step-card:last-child::before { display: none; }

        @media (max-width: 768px) {
            .step-card::before { display: none; }
        }

        /* ============ EARNINGS CALCULATOR ============ */
        .earnings-section {
            background: white;
        }

        .calc-wrapper {
            background: linear-gradient(135deg, var(--dark-2), var(--dark-3));
            border-radius: 24px;
            padding: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .calc-wrapper::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255, 122, 0, 0.15), transparent 70%);
            border-radius: 50%;
        }

        .calc-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 2;
        }

        .calc-header h3 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .calc-header p {
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
        }

        .calc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 768px) {
            .calc-grid { grid-template-columns: 1fr; }
        }

        .calc-inputs {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .calc-input-group label {
            display: block;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .calc-slider-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .calc-slider {
            flex: 1;
            -webkit-appearance: none;
            height: 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.15);
            outline: none;
        }

        .calc-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--gradient-primary);
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(255, 122, 0, 0.4);
        }

        .calc-slider-value {
            min-width: 60px;
            text-align: right;
            font-weight: 800;
            font-size: 1.1rem;
            color: white;
        }

        .calc-result {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .calc-result-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
        }

        .calc-result-value {
            font-size: 3rem;
            font-weight: 900;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .calc-result-hint {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 1.5rem;
        }

        .calc-result-breakdown {
            display: flex;
            gap: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            justify-content: space-around;
        }

        .breakdown-item {
            text-align: center;
        }

        .breakdown-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.2rem;
        }

        .breakdown-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
        }

        /* ============ BENEFITS ============ */
        .benefit-card {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .benefit-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }

        .benefit-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .benefit-card:hover::after {
            transform: scaleX(1);
        }

        .benefit-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 0, 0.1));
            margin-bottom: 1.3rem;
            transition: all 0.4s ease;
        }

        .benefit-card:hover .benefit-icon {
            background: var(--gradient-primary);
            transform: rotate(8deg) scale(1.05);
        }

        .benefit-icon i {
            font-size: 1.7rem;
            color: var(--primary);
            transition: all 0.4s ease;
        }

        .benefit-card:hover .benefit-icon i {
            color: white;
        }

        .benefit-card h5 {
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: var(--dark);
        }

        .benefit-card p {
            color: var(--gray);
            margin: 0;
            font-size: 0.95rem;
        }

        /* ============ WHO CAN ============ */
        .who-card {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            transition: all 0.4s ease;
            height: 100%;
            border: 2px solid transparent;
        }

        .who-card:hover {
            border-color: var(--primary);
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
        }

        .who-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 0, 0.1));
            margin: 0 auto 1.2rem;
            transition: all 0.4s ease;
        }

        .who-card:hover .who-icon {
            background: var(--gradient-primary);
        }

        .who-icon i {
            font-size: 2rem;
            color: var(--primary);
            transition: all 0.4s ease;
        }

        .who-card:hover .who-icon i {
            color: white;
        }

        .who-card h5 {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .who-card p {
            color: var(--gray);
            font-size: 0.9rem;
            margin: 0;
        }

        /* ============ CONDITIONS (тарифы агента) ============ */
        .condition-card {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            color: var(--dark);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .condition-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .condition-card.popular {
            border-color: var(--primary);
            background: linear-gradient(135deg, #fff7ed 0%, #fffdf8 100%);
            transform: scale(1.03);
        }

        .condition-card.popular:hover {
            transform: scale(1.03) translateY(-10px);
        }

        .popular-badge {
            position: absolute;
            top: 20px; right: -35px;
            background: var(--gradient-primary);
            color: white;
            padding: 0.4rem 3rem;
            font-size: 0.75rem;
            font-weight: 700;
            transform: rotate(45deg);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }

        .condition-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .condition-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .condition-percent {
            font-size: 3.5rem;
            font-weight: 900;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .condition-percent-label {
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .condition-features {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem;
            flex-grow: 1;
        }

        .condition-features li {
            padding: 0.7rem 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            color: var(--dark);
            font-size: 0.95rem;
        }

        .condition-features li:last-child { border-bottom: none; }

        .condition-features i {
            color: var(--primary);
            font-size: 1rem;
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

        .testimonial-earning {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
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
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.06);
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
            color: var(--dark);
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
            flex-shrink: 0;
            margin-left: 1rem;
        }

        .faq-button[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 1.5rem 1.5rem;
            color: var(--gray);
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
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1), transparent 70%);
        }

        .cta-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .cta-title {
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .cta-text {
            font-size: 1.15rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ============ FOOTER ============ */
        .footer {
            background: var(--dark);
            color: white;
            padding: 70px 0 30px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }

        .footer-logo {
            font-weight: 900;
            font-size: 1.6rem;
            color: white;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-logo i { color: var(--primary); }

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

        .footer-links li { margin-bottom: 0.8rem; }

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
            width: 40px; height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background: var(--gradient-primary);
            border-color: transparent;
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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
            bottom: 30px; right: 30px;
            width: 50px; height: 50px;
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
        }

        /* ============ ANIMATIONS ============ */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* ============ MODAL ============ */
        .modal-content {
            background: white;
            border-radius: 24px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            padding: 1.5rem 2rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-title {
            color: var(--dark);
        }

        .form-control, .form-select {
            background: var(--gray-light);
            border: 1px solid rgba(0, 0, 0, 0.08);
            color: var(--dark);
            border-radius: 12px;
            padding: 0.8rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            background: white;
            border-color: var(--primary);
            color: var(--dark);
            box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.15);
        }

        .form-control::placeholder {
            color: rgba(0, 0, 0, 0.4);
        }

        .form-label {
            color: var(--dark);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 992px) {
            .hero { padding: 120px 0 60px; text-align: center; }
            .hero .lead { margin-left: auto; margin-right: auto; }
            .hero-buttons { justify-content: center; }
            .hero-visual { margin-top: 3rem; }
            .condition-card.popular { transform: scale(1); }
            .condition-card.popular:hover { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            section { padding: 60px 0; }
            .stats-wrapper { padding: 40px 20px; }
            .footer-bottom { flex-direction: column; text-align: center; }
            .calc-wrapper { padding: 2rem 1.5rem; }
            .earnings-amount { font-size: 2.3rem; }
            .calc-result-value { font-size: 2.3rem; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fa-solid fa-handshake-angle"></i>
            КэшМен<span class="brand-accent">.Партнёры</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="fa-solid fa-bars text-dark"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="#how">Как это работает</a></li>
                <li class="nav-item"><a class="nav-link" href="#earnings">Заработок</a></li>
                <li class="nav-item"><a class="nav-link" href="#benefits">Условия</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                <li class="nav-item ms-lg-3">
                    <a class="nav-link nav-cta" data-bs-toggle="modal" data-bs-target="#agentModal">
                        Стать агентом
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-decor hero-decor--1"></div>
    <div class="hero-decor hero-decor--2"></div>

    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <div class="hero-badge">
                    <i class="fa-solid fa-coins"></i>
                    Партнёрская программа
                </div>
                <h1>
                    Зарабатывай на<br>
                    <span class="gradient-text">продаже мобильных магазинов</span>
                </h1>
                <p class="lead">
                    Стань партнёром КэшМен и получай до 30% с каждого привлечённого клиента.
                    Мы берём на себя всю техническую часть — ты занимаешься только продажами.
                </p>
                <div class="hero-buttons">
                    <a class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#agentModal">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Стать партнёром
                    </a>
                    <a class="btn btn-outline-dark btn-lg" href="#how">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Узнать подробнее
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-visual">
                    <div class="earnings-card">
                        <div class="earnings-header">
                            <span class="earnings-title">Ваш заработок</span>
                            <span class="earnings-period">Этот месяц</span>
                        </div>
                        <div class="earnings-amount">
                            <span class="currency">₽</span>187 500
                        </div>
                        <div class="earnings-change">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                            +32% к прошлому месяцу
                        </div>
                        <div class="earnings-stats">
                            <div class="earnings-stat">
                                <div class="earnings-stat-label">Привлечено клиентов</div>
                                <div class="earnings-stat-value">12</div>
                            </div>
                            <div class="earnings-stat">
                                <div class="earnings-stat-label">Средний чек</div>
                                <div class="earnings-stat-value">52 000 ₽</div>
                            </div>
                        </div>
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
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-number" data-count="340">0</div>
                        <div class="stat-text">активных агентов</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                        <div class="stat-number">до 30%</div>
                        <div class="stat-text">комиссия с продаж</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="stat-number">2 раза</div>
                        <div class="stat-text">выплаты в месяц</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item">
                        <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                        <div class="stat-number">100%</div>
                        <div class="stat-text">бесплатное обучение</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section id="how" class="bg-light-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-route"></i>
                Простой старт
            </span>
            <h2 class="section-title">Как начать зарабатывать?</h2>
            <p class="section-subtitle">
                4 простых шага от регистрации до первых выплат. Никаких вложений и абонентской платы.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3 reveal">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5>Оставьте заявку</h5>
                    <p>Заполните короткую анкету — мы свяжемся в течение дня и расскажем об условиях.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5>Пройдите обучение</h5>
                    <p>Бесплатный онлайн-курс: продукт, скрипты продаж, работа с возражениями.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5>Привлекайте клиентов</h5>
                    <p>Используйте готовые материалы, свои каналы или наши лиды — выбирайте удобный формат.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 reveal">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h5>Получайте выплаты</h5>
                    <p>До 30% с каждого платежа клиента. Выплаты 2 раза в месяц на карту или счёт.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- EARNINGS CALCULATOR -->
<section id="earnings" class="earnings-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-calculator"></i>
                Калькулятор дохода
            </span>
            <h2 class="section-title">Сколько вы можете заработать?</h2>
            <p class="section-subtitle">
                Двигайте ползунки и смотрите, какой доход возможен при вашем уровне активности
            </p>
        </div>

        <div class="calc-wrapper reveal">
            <div class="calc-header">
                <h3>Рассчитайте свой доход</h3>
                <p>Все цифры — реальные показатели наших лучших агентов</p>
            </div>

            <div class="calc-grid">
                <div class="calc-inputs">
                    <div class="calc-input-group">
                        <label>Привлечённых клиентов в месяц</label>
                        <div class="calc-slider-wrapper">
                            <input type="range" class="calc-slider" id="clientsSlider" min="1" max="50" value="10">
                            <div class="calc-slider-value"><span id="clientsValue">10</span></div>
                        </div>
                    </div>
                    <div class="calc-input-group">
                        <label>Средний тариф клиента, ₽</label>
                        <div class="calc-slider-wrapper">
                            <input type="range" class="calc-slider" id="tariffSlider" min="990" max="24900" step="100" value="4990">
                            <div class="calc-slider-value"><span id="tariffValue">4 990</span></div>
                        </div>
                    </div>
                    <div class="calc-input-group">
                        <label>Ваша комиссия, %</label>
                        <div class="calc-slider-wrapper">
                            <input type="range" class="calc-slider" id="percentSlider" min="15" max="30" value="25">
                            <div class="calc-slider-value"><span id="percentValue">25</span>%</div>
                        </div>
                    </div>
                </div>

                <div class="calc-result">
                    <div class="calc-result-label">Ваш доход в месяц</div>
                    <div class="calc-result-value"><span id="resultValue">12 475</span> ₽</div>
                    <div class="calc-result-hint">Чистыми, после всех выплат</div>
                    <div class="calc-result-breakdown">
                        <div class="breakdown-item">
                            <div class="breakdown-value" id="yearResult">149 700 ₽</div>
                            <div class="breakdown-label">В год</div>
                        </div>
                        <div class="breakdown-item">
                            <div class="breakdown-value" id="dayResult">415 ₽</div>
                            <div class="breakdown-label">В день</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BENEFITS -->
<section id="benefits" class="bg-light-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-gift"></i>
                Что вы получаете
            </span>
            <h2 class="section-title">Всё для успешных продаж</h2>
            <p class="section-subtitle">
                Мы предоставляем партнёрам полный набор инструментов и поддержку на каждом этапе
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4 reveal">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <h5>Бесплатное обучение</h5>
                    <p>Онлайн-курс по продукту, техникам продаж и работе с клиентами. Сертификат по окончании.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fa-solid fa-file-contract"></i></div>
                    <h5>Готовые материалы</h5>
                    <p>Презентации, коммерческие предложения, буклеты, визитки — всё с вашими контактами.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <h5>Личный менеджер</h5>
                    <p>Персональный куратор из команды, который поможет с любыми вопросами 24/7.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <h5>Готовые лиды</h5>
                    <p>Для опытных агентов — передаём заявки с нашего сайта и рекламы в вашем регионе.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <h5>Прозрачная статистика</h5>
                    <p>Личный кабинет агента с детализацией по каждому клиенту, платежу и комиссии.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <h5>Стабильные выплаты</h5>
                    <p>2 раза в месяц на карту или расчётный счёт. Работаем с ИП, самозанятыми и ООО.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WHO CAN BECOME -->
<section>
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-user-check"></i>
                Кому подходит
            </span>
            <h2 class="section-title">Станьте нашим партнёром</h2>
            <p class="section-subtitle">
                Программа открыта для всех, кто умеет или хочет учиться продавать. Опыт в IT не обязателен.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="who-card">
                    <div class="who-icon"><i class="fa-solid fa-briefcase"></i></div>
                    <h5>Менеджерам по продажам</h5>
                    <p>Монетизируйте свои навыки и связи</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="who-card">
                    <div class="who-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    <h5>Маркетологам</h5>
                    <p>Дополнительный доход на лидогенерации</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="who-card">
                    <div class="who-icon"><i class="fa-solid fa-laptop-code"></i></div>
                    <h5>Фрилансерам</h5>
                    <p>Расширьте спектр услуг для клиентов</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="who-card">
                    <div class="who-icon"><i class="fa-solid fa-people-group"></i></div>
                    <h5>Нетворкерам</h5>
                    <p>Зарабатывайте на своих знакомствах</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="who-card">
                    <div class="who-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <h5>Бизнес-тренерам</h5>
                    <p>Предлагайте клиентам готовый продукт</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="who-card">
                    <div class="who-icon"><i class="fa-solid fa-pen-nib"></i></div>
                    <h5>Блогерам</h5>
                    <p>Реферальная программа для аудитории</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="who-card">
                    <div class="who-icon"><i class="fa-solid fa-building"></i></div>
                    <h5>Агентствам</h5>
                    <p>White Label и оптовые условия</p>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3 reveal">
                <div class="who-card">
                    <div class="who-icon"><i class="fa-solid fa-rocket"></i></div>
                    <h5>Стартаперам</h5>
                    <p>Быстрый запуск без вложений</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CONDITIONS -->
<section class="bg-dark-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge">
                <i class="fa-solid fa-handshake"></i>
                Условия сотрудничества
            </span>
            <h2 class="section-title gradient">Выберите свой уровень</h2>
            <p class="section-subtitle">
                Чем больше клиентов — тем выше процент. Начните с базового уровня и растите вместе с нами
            </p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6 reveal">
                <div class="condition-card">
                    <div class="condition-header">
                        <h4 class="condition-title">Старт</h4>
                        <div class="condition-percent">15%</div>
                        <div class="condition-percent-label">с каждого платежа клиента</div>
                    </div>
                    <ul class="condition-features">
                        <li><i class="fa-solid fa-check"></i> До 5 клиентов в месяц</li>
                        <li><i class="fa-solid fa-check"></i> Базовое обучение</li>
                        <li><i class="fa-solid fa-check"></i> Маркетинговые материалы</li>
                        <li><i class="fa-solid fa-check"></i> Личный кабинет агента</li>
                        <li><i class="fa-solid fa-check"></i> Выплаты 2 раза в месяц</li>
                    </ul>
                    <button class="btn btn-outline-dark w-100" data-bs-toggle="modal" data-bs-target="#agentModal">Начать сотрудничество</button>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="condition-card popular">
                    <div class="popular-badge">Оптимальный</div>
                    <div class="condition-header">
                        <h4 class="condition-title">Профи</h4>
                        <div class="condition-percent">25%</div>
                        <div class="condition-percent-label">с каждого платежа клиента</div>
                    </div>
                    <ul class="condition-features">
                        <li><i class="fa-solid fa-check"></i> От 5 клиентов в месяц</li>
                        <li><i class="fa-solid fa-check"></i> Расширенное обучение</li>
                        <li><i class="fa-solid fa-check"></i> Персональный менеджер</li>
                        <li><i class="fa-solid fa-check"></i> Готовые лиды из рекламы</li>
                        <li><i class="fa-solid fa-check"></i> Приоритетная поддержка</li>
                        <li><i class="fa-solid fa-check"></i> Бонусы за удержание клиентов</li>
                    </ul>
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#agentModal">Стать профи</button>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="condition-card">
                    <div class="condition-header">
                        <h4 class="condition-title">Эксперт</h4>
                        <div class="condition-percent">30%</div>
                        <div class="condition-percent-label">с каждого платежа клиента</div>
                    </div>
                    <ul class="condition-features">
                        <li><i class="fa-solid fa-check"></i> От 15 клиентов в месяц</li>
                        <li><i class="fa-solid fa-check"></i> Индивидуальные условия</li>
                        <li><i class="fa-solid fa-check"></i> White Label / свой бренд</li>
                        <li><i class="fa-solid fa-check"></i> Возможность нанимать субагентов</li>
                        <li><i class="fa-solid fa-check"></i> Участие в стратегии продукта</li>
                        <li><i class="fa-solid fa-check"></i> Ежеквартальные бонусы</li>
                    </ul>
                    <button class="btn btn-outline-dark w-100" data-bs-toggle="modal" data-bs-target="#agentModal">Обсудить условия</button>
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
                Истории успеха
            </span>
            <h2 class="section-title">Что говорят наши партнёры</h2>
            <p class="section-subtitle">
                Реальные отзывы агентов, которые уже зарабатывают с КэшМен
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4 reveal">
                <div class="testimonial-card">
                    <div class="testimonial-earning">
                        <i class="fa-solid fa-sack-dollar"></i>
                        187 000 ₽ / мес
                    </div>
                    <p class="testimonial-text">
                        «Работаю агентом 6 месяцев. Привлекаю клиентов через свои каналы в Telegram.
                        Самое классное — не нужно разбираться в технике, всё делают ребята из КэшМен.»
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">АК</div>
                        <div>
                            <p class="testimonial-name">Алексей Козлов</p>
                            <p class="testimonial-role">Маркетолог, Москва</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="testimonial-card">
                    <div class="testimonial-earning">
                        <i class="fa-solid fa-sack-dollar"></i>
                        95 000 ₽ / мес
                    </div>
                    <p class="testimonial-text">
                        «Совмещаю с основной работой. В месяц привлекаю 3-4 клиентов — этого хватает
                        на хорошую прибавку к зарплате. Обучили за 3 дня, всё понятно.»
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">МС</div>
                        <div>
                            <p class="testimonial-name">Мария Соколова</p>
                            <p class="testimonial-role">Менеджер по продажам, Казань</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="testimonial-card">
                    <div class="testimonial-earning">
                        <i class="fa-solid fa-sack-dollar"></i>
                        340 000 ₽ / мес
                    </div>
                    <p class="testimonial-text">
                        «У меня своё digital-агентство. Подключил КэшМен как дополнительный продукт —
                        клиенты довольны, я получаю стабильный пассивный доход с их подписок.»
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">ДП</div>
                        <div>
                            <p class="testimonial-name">Дмитрий Петров</p>
                            <p class="testimonial-role">Владелец агентства, СПб</p>
                        </div>
                    </div>
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
            <h2 class="section-title">Частые вопросы партнёров</h2>
            <p class="section-subtitle">
                Ответы на самые популярные вопросы о партнёрской программе
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion" id="faqAccordion">
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true">
                                Нужны ли вложения для старта?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Нет, участие в партнёрской программе полностью бесплатно. Не нужно платить
                                за обучение, материалы или доступ к личному кабинету. Вы начинаете зарабатывать
                                с первого привлечённого клиента.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Как оформляются отношения юридически?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Мы работаем официально: с самозанятыми — по договору сотрудничества,
                                с ИП и ООО — агентский договор. Все выплаты прозрачные, с закрывающими
                                документами. Вы можете легально платить налоги.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Когда я получу первую выплату?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Выплаты производятся 2 раза в месяц — 5-го и 20-го числа. Комиссия начисляется
                                после того, как клиент оплатил первый месяц. Если клиент привлечён, например,
                                15-го числа, первая выплата придёт уже 20-го.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Нужно ли мне самому настраивать магазины клиентам?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Нет, вся техническая работа — на нашей стороне. Вы приводите клиента,
                                а мы сами настраиваем магазин, загружаем меню, подключаем интеграции.
                                Вы получаете комиссию с каждого платежа клиента, даже когда он продлевает подписку.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Что если клиент уйдёт?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Вы получаете комиссию только пока клиент платит. Но мы заинтересованы
                                удерживать клиентов, поэтому вкладываемся в поддержку и развитие продукта.
                                Средний LTV клиента — 14 месяцев, что означает стабильный пассивный доход
                                для вас в течение года и более.
                            </div>
                        </div>
                    </div>
                    <div class="faq-item reveal">
                        <h2 class="accordion-header">
                            <button class="faq-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                Могу ли я совмещать с основной работой?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="faq-answer">
                                Конечно! Большинство наших агентов совмещают партнёрство с основной работой.
                                Гибкий график, никаких KPI и обязательных часов — вы сами решаете,
                                сколько времени уделять привлечению клиентов.
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
            <h2 class="cta-title">Готовы начать зарабатывать?</h2>
            <p class="cta-text">
                Оставьте заявку — мы свяжемся в течение дня, расскажем о программе подробнее
                и поможем сделать первые шаги.
            </p>
            <a class="btn btn-dark btn-lg" data-bs-toggle="modal" data-bs-target="#agentModal">
                <i class="fa-solid fa-paper-plane me-2"></i>
                Оставить заявку
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
                    <i class="fa-solid fa-handshake-angle"></i>
                    КэшМен.Партнёры
                </div>
                <p class="text-white-50">
                    Партнёрская программа для тех, кто хочет зарабатывать на продаже
                    современных мобильных магазинов. Официально, прозрачно, выгодно.
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
                    <li><a href="#how">Как это работает</a></li>
                    <li><a href="#earnings">Заработок</a></li>
                    <li><a href="#benefits">Условия</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-4">
                <h5>Партнёрам</h5>
                <ul class="footer-links">
                    <li><a href="#">Личный кабинет</a></li>
                    <li><a href="#">Обучение</a></li>
                    <li><a href="#">Маркетинг-кит</a></li>
                    <li><a href="#">Договор оферты</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-4">
                <h5>Контакты</h5>
                <ul class="footer-links">
                    <li><a href="#"><i class="fa-brands fa-telegram"></i> @cashman_partners</a></li>
                    <li><a href="mailto:partners@example.com"><i class="fa-solid fa-envelope"></i> partners@example.com</a></li>
                    <li><a href="tel:+70000000000"><i class="fa-solid fa-phone"></i> +7 (000) 000-00-00</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© 2026 КэшМен. Партнёрская программа.</div>
            <div>
                Сделано с <i class="fa-solid fa-heart text-danger"></i> для наших агентов
            </div>
        </div>
    </div>
</footer>

<!-- SCROLL TO TOP -->
<button class="scroll-top" id="scrollTop">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- AGENT MODAL -->
<div class="modal fade" id="agentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title fw-bold">
                    <i class="fa-solid fa-handshake-angle me-2" style="color: var(--primary);"></i>
                    Стать партнёром
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary mb-4">
                    Оставьте контакт — свяжемся в течение дня, расскажем об условиях и поможем начать.
                </p>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Ваше имя</label>
                        <input type="text" class="form-control" placeholder="Иван Иванов">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Телефон или Telegram</label>
                        <input type="text" class="form-control" placeholder="+7 (___) ___-__-__ или @username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ваш опыт в продажах</label>
                        <div class="custom-dropdown">
                            <button
                                class="dropdown-toggle placeholder form-control text-start"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                id="experienceBtn">
                                <span class="dropdown-text">Выберите вариант</span>
                            </button>
                            <ul class="dropdown-menu w-100">
                                <li><a class="dropdown-item" href="#" data-value="none"><i class="fa-solid fa-seedling me-2"></i>Нет опыта, хочу учиться</a></li>
                                <li><a class="dropdown-item" href="#" data-value="some"><i class="fa-solid fa-user me-2"></i>До 1 года</a></li>
                                <li><a class="dropdown-item" href="#" data-value="mid"><i class="fa-solid fa-user-tie me-2"></i>1–3 года</a></li>
                                <li><a class="dropdown-item" href="#" data-value="pro"><i class="fa-solid fa-user-gear me-2"></i>Более 3 лет</a></li>
                                <li><a class="dropdown-item" href="#" data-value="agency"><i class="fa-solid fa-building me-2"></i>У меня агентство / команда</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Комментарий (необязательно)</label>
                        <textarea class="form-control" rows="3" placeholder="Расскажите, откуда узнали о нас и какие каналы привлечения планируете использовать"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="fa-solid fa-paper-plane me-2"></i>
                        Отправить заявку
                    </button>
                    <p class="small text-secondary text-center mt-3 mb-0">
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
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    // Earnings calculator
    const clientsSlider = document.getElementById('clientsSlider');
    const tariffSlider = document.getElementById('tariffSlider');
    const percentSlider = document.getElementById('percentSlider');

    const clientsValue = document.getElementById('clientsValue');
    const tariffValue = document.getElementById('tariffValue');
    const percentValue = document.getElementById('percentValue');
    const resultValue = document.getElementById('resultValue');
    const yearResult = document.getElementById('yearResult');
    const dayResult = document.getElementById('dayResult');

    function formatNumber(num) {
        return new Intl.NumberFormat('ru-RU').format(Math.round(num));
    }

    function calculateEarnings() {
        const clients = parseInt(clientsSlider.value);
        const tariff = parseInt(tariffSlider.value);
        const percent = parseInt(percentSlider.value);

        clientsValue.textContent = clients;
        tariffValue.textContent = formatNumber(tariff);
        percentValue.textContent = percent;

        const monthly = clients * tariff * (percent / 100);
        resultValue.textContent = formatNumber(monthly);
        yearResult.textContent = formatNumber(monthly * 12) + ' ₽';
        dayResult.textContent = formatNumber(monthly / 30) + ' ₽';
    }

    [clientsSlider, tariffSlider, percentSlider].forEach(slider => {
        slider.addEventListener('input', calculateEarnings);
    });
    calculateEarnings();

    // Custom dropdown
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownItems = document.querySelectorAll('.custom-dropdown .dropdown-item');
        const dropdownBtn = document.getElementById('experienceBtn');
        const dropdownText = dropdownBtn.querySelector('.dropdown-text');

        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                dropdownText.textContent = this.textContent.trim();
                dropdownBtn.classList.remove('placeholder');
            });
        });

        const agentModal = document.getElementById('agentModal');
        if (agentModal) {
            agentModal.addEventListener('hidden.bs.modal', function() {
                dropdownItems.forEach(i => i.classList.remove('active'));
                dropdownText.textContent = 'Выберите вариант';
                dropdownBtn.classList.add('placeholder');
            });
        }
    });

    // Scroll reveal
    const reveals = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('active');
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
        scrollTopBtn.classList.toggle('visible', window.scrollY > 500);
    });
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Smooth scroll
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
