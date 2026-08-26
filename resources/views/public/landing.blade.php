<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>{{ $appName }} — Создайте свой онлайн-магазин за 5 минут</title>
    <meta name="description"
          content="Современная платформа для создания PWA-магазинов. Заказы, оплата, доставка, CRM — всё в одном месте.">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <!--link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
-->
    <style>
        /* ==========================================
           RESET & BASE
           ========================================== */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1f2937;
            background: #ffffff;
            overflow-x: hidden;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: none;
        }

        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --primary-light: #a5b4fc;
            --secondary: #764ba2;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --text: #1f2937;
            --text-muted: #6b7280;
            --bg: #ffffff;
            --bg-secondary: #f8f9fa;
            --border: #e5e7eb;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.12);
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ==========================================
           ХЕДЕР
           ========================================== */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .header.scrolled {
            border-bottom-color: var(--border);
            box-shadow: var(--shadow-sm);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-links {
            display: flex;
            gap: 28px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn-header {
            padding: 10px 24px;
            background: var(--gradient);
            color: white;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-header:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .burger {
            display: none;
            width: 36px;
            height: 36px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .burger span {
            width: 22px;
            height: 2px;
            background: var(--text);
            border-radius: 2px;
            transition: all 0.3s;
        }

        .burger.active span:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
        }

        .burger.active span:nth-child(2) {
            opacity: 0;
        }

        .burger.active span:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
        }

        /* ==========================================
           HERO
           ========================================== */
        .hero {
            position: relative;
            padding: 140px 20px 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 40%),
            radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.08) 0%, transparent 40%);
            pointer-events: none;
        }

        .hero-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            pointer-events: none;
        }

        .hero-blob-1 {
            width: 400px;
            height: 400px;
            background: #f59e0b;
            top: -100px;
            right: -100px;
            animation: blobFloat 20s ease-in-out infinite;
        }

        .hero-blob-2 {
            width: 300px;
            height: 300px;
            background: #10b981;
            bottom: -100px;
            left: -100px;
            animation: blobFloat 25s ease-in-out infinite reverse;
        }

        @keyframes blobFloat {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -30px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.95);
            }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
        }

        .hero-badge i {
            color: #fbbf24;
        }

        .hero-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 20px;
            letter-spacing: -0.02em;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.2rem);
            opacity: 0.95;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .hero-alert {
            max-width: 600px;
            margin: 0 auto 32px;
            padding: 20px 24px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            text-align: left;
        }

        .hero-alert-content {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .hero-alert i {
            font-size: 1.4rem;
            color: #fbbf24;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .hero-alert-text h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .hero-alert-text p {
            font-size: 0.9rem;
            opacity: 0.9;
            line-height: 1.5;
        }

        .hero-alert-text .slug {
            font-family: 'Courier New', monospace;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .hero-cta {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.2);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            max-width: 700px;
            margin: 60px auto 0;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-value {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            opacity: 0.85;
        }

        /* ==========================================
           SECTIONS (общие)
           ========================================== */
        .section {
            padding: 100px 20px;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 60px;
        }

        .section-label {
            display: inline-block;
            padding: 6px 14px;
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ==========================================
           FEATURES (преимущества)
           ========================================== */
        .features {
            background: var(--bg-secondary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px 28px;
            transition: all 0.3s;
            opacity: 0;
            transform: translateY(30px);
        }

        .feature-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 20px;
        }

        .feature-icon.blue {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .feature-icon.green {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .feature-icon.orange {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .feature-icon.red {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .feature-icon.purple {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .feature-icon.cyan {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ==========================================
           HOW IT WORKS (шаги)
           ========================================== */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 32px;
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
        }

        .step-card {
            text-align: center;
            padding: 24px;
            position: relative;
            opacity: 0;
            transform: translateY(30px);
        }

        .step-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .step-number {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
            position: relative;
            z-index: 1;
        }

        .step-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .step-desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ==========================================
           PRICING (тарифы)
           ========================================== */
        .pricing {
            background: var(--bg-secondary);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .price-card {
            background: white;
            border: 2px solid var(--border);
            border-radius: 20px;
            padding: 36px 32px;
            position: relative;
            transition: all 0.3s;
            opacity: 0;
            transform: translateY(30px);
        }

        .price-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .price-card.featured {
            border-color: var(--primary);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.15);
            transform: translateY(-8px);
        }

        .price-card.featured.visible {
            transform: translateY(-8px);
        }

        .price-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 16px;
            background: var(--gradient);
            color: white;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .price-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .price-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 24px;
            min-height: 44px;
        }

        .price-amount {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 8px;
        }

        .price-value {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .price-period {
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .price-note {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 24px;
            min-height: 20px;
        }

        .price-features {
            list-style: none;
            margin-bottom: 28px;
        }

        .price-features li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            font-size: 0.95rem;
            color: var(--text);
        }

        .price-features i {
            color: var(--success);
            margin-top: 4px;
            flex-shrink: 0;
        }

        .price-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--gradient);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
        }

        .price-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .price-btn.ghost {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            box-shadow: none;
        }

        .price-btn.ghost:hover {
            background: var(--primary);
            color: white;
        }

        /* ==========================================
           CTA FORM (заявка)
           ========================================== */
        .cta-section {
            background: var(--gradient);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 40%),
            radial-gradient(circle at 90% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 40%);
            pointer-events: none;
        }

        .cta-content {
            position: relative;
            z-index: 1;
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
        }

        .cta-title {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 800;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .cta-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 36px;
        }

        .cta-form {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            text-align: left;
            color: var(--text);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text);
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-input.error {
            border-color: var(--danger);
        }

        .form-error {
            display: none;
            color: var(--danger);
            font-size: 0.8rem;
            margin-top: 4px;
        }

        .form-error.visible {
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-submit {
            width: 100%;
            padding: 14px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .form-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .form-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .form-success {
            display: none;
            text-align: center;
            padding: 30px 20px;
        }

        .form-success.visible {
            display: block;
        }

        .form-success i {
            font-size: 3rem;
            color: var(--success);
            margin-bottom: 16px;
        }

        .form-success h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-success p {
            color: var(--text-muted);
        }

        /* ==========================================
           FOOTER
           ========================================== */
        .footer {
            background: #0f172a;
            color: white;
            padding: 60px 20px 30px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto 40px;
        }

        .footer-brand .logo {
            color: white;
            margin-bottom: 16px;
        }

        .footer-brand p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .footer-social {
            display: flex;
            gap: 12px;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .footer-social a:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .footer-col h4 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul a {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .footer-col ul a:hover {
            color: white;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }

        /* ==========================================
           АДАПТИВ
           ========================================== */
        @media (max-width: 768px) {
            .nav-links, .btn-header {
                display: none;
            }

            .burger {
                display: flex;
            }

            .hero {
                padding: 120px 16px 70px;
            }

            .hero-stats {
                gap: 16px;
                margin-top: 40px;
            }

            .section {
                padding: 70px 16px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .price-card.featured {
                transform: none;
            }

            .price-card.featured.visible {
                transform: none;
            }
        }

        /* Мобильное меню */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 72px;
            left: 0;
            right: 0;
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 20px;
            box-shadow: var(--shadow-md);
            z-index: 999;
        }

        .mobile-menu.active {
            display: block;
        }

        .mobile-menu ul {
            list-style: none;
        }

        .mobile-menu ul li {
            margin-bottom: 12px;
        }

        .mobile-menu ul a {
            display: block;
            padding: 10px 0;
            font-weight: 500;
            color: var(--text);
        }

        .mobile-menu .btn {
            width: 100%;
            justify-content: center;
            margin-top: 12px;
        }
    </style>
</head>
<body>

<!-- ==========================================
     ХЕДЕР
     ========================================== -->
<header class="header" id="header">
    <div class="header-content">
        <a href="{{ route('public.landing') }}" class="logo">
            <div class="logo-icon">
                <i class="fa-solid fa-bolt"></i>
            </div>
            {{ $appName }}
        </a>

        <nav class="nav">
            <ul class="nav-links">
                <li><a href="#features">Возможности</a></li>
                <li><a href="#how-it-works">Как это работает</a></li>
                <li><a href="#pricing">Тарифы</a></li>
                <li><a href="#contact">Контакты</a></li>
            </ul>
            <a href="#cta" class="btn-header">Создать магазин</a>
        </nav>

        <div class="burger" id="burger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</header>

<!-- Мобильное меню -->
<div class="mobile-menu" id="mobileMenu">
    <ul>
        <li><a href="#features">Возможности</a></li>
        <li><a href="#how-it-works">Как это работает</a></li>
        <li><a href="#pricing">Тарифы</a></li>
        <li><a href="#contact">Контакты</a></li>
    </ul>
    <a href="#cta" class="btn btn-primary">Создать магазин</a>
</div>

<!-- ==========================================
     HERO
     ========================================== -->
<section class="hero">
    <div class="hero-blob hero-blob-1"></div>
    <div class="hero-blob hero-blob-2"></div>

    <div class="hero-content">
        @if($requestedSlug)
            <div class="hero-alert">
                <div class="hero-alert-content">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div class="hero-alert-text">
                        <h3>Магазин <span class="slug">{{ $requestedSlug }}</span> не найден</h3>
                        <p>Возможно, он ещё не создан. Оставьте заявку — и мы поможем его запустить!</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="hero-badge">
            <i class="fa-solid fa-star"></i>
            <span>Запустите свой магазин за 5 минут</span>
        </div>

        <h1 class="hero-title">
            Создайте современный <span class="gradient-text">PWA-магазин</span><br>
            для вашего бизнеса
        </h1>

        <p class="hero-subtitle">
            Заказы, оплата, доставка, CRM и чат с клиентами — всё в одном месте.<br>
            Работает на любых устройствах, устанавливается как приложение.
        </p>

        <div class="hero-cta">
            <a href="#cta" class="btn btn-primary">
                <i class="fa-solid fa-rocket"></i>
                Оставить заявку
            </a>
            <a href="#features" class="btn btn-ghost">
                <i class="fa-solid fa-circle-info"></i>
                Узнать больше
            </a>
        </div>

        <div class="hero-stats">
            <div class="hero-stat">
                <div class="hero-stat-value" data-counter="500">0</div>
                <div class="hero-stat-label">Активных магазинов</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value" data-counter="50000">0</div>
                <div class="hero-stat-label">Заказов в месяц</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value" data-counter="99">0</div>
                <div class="hero-stat-label">% Uptime</div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================
     FEATURES
     ========================================== -->
<section class="section features" id="features">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Возможности</div>
            <h2 class="section-title">Всё, что нужно для онлайн-продаж</h2>
            <p class="section-subtitle">
                Полный набор инструментов для запуска и развития вашего магазина
            </p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon blue">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
                <h3 class="feature-title">PWA-технологии</h3>
                <p class="feature-desc">
                    Магазин устанавливается как приложение. Работает оффлайн, быстро загружается.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon green">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <h3 class="feature-title">Умная корзина</h3>
                <p class="feature-desc">
                    Автоматический расчёт доставки, промокоды, кэшбэк и бонусные программы.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon orange">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <h3 class="feature-title">Все виды оплаты</h3>
                <p class="feature-desc">
                    СБП, банковские карты, наличные, переводы — клиент выбирает удобное.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon purple">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h3 class="feature-title">Встроенный чат</h3>
                <p class="feature-desc">
                    Общайтесь с клиентами прямо в приложении. Уведомления о заказах в реальном времени.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon cyan">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3 class="feature-title">Аналитика и CRM</h3>
                <p class="feature-desc">
                    Подробная статистика продаж, автоматические отчёты, интеграция с Kanban-доской.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon red">
                    <i class="fa-solid fa-gamepad"></i>
                </div>
                <h3 class="feature-title">Геймификация</h3>
                <p class="feature-desc">
                    Колесо фортуны, квесты, система достижений — повышайте вовлечённость клиентов.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================
     HOW IT WORKS
     ========================================== -->
<section class="section" id="how-it-works">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Процесс</div>
            <h2 class="section-title">Как это работает</h2>
            <p class="section-subtitle">
                Всего 4 простых шага от заявки до первого заказа
            </p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="step-title">Оставьте заявку</h3>
                <p class="step-desc">
                    Заполните простую форму — мы свяжемся с вами в течение часа
                </p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="step-title">Настройка магазина</h3>
                <p class="step-desc">
                    Поможем загрузить товары, настроить оплату и доставку
                </p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="step-title">Тестирование</h3>
                <p class="step-desc">
                    Вместе проверим все функции перед запуском в продакшен
                </p>
            </div>

            <div class="step-card">
                <div class="step-number">4</div>
                <h3 class="step-title">Запуск!</h3>
                <p class="step-desc">
                    Ваш магазин готов принимать первые заказы от клиентов
                </p>
            </div>
        </div>
    </div>
</section>

@php
    $plans = \App\Services\Tenants\PricingService::getActivePlans();
    $display = \App\Services\Tenants\PricingService::getDisplaySettings();
@endphp

<section class="section pricing" id="pricing">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Тарифы</div>
            <h2 class="section-title">Выберите подходящий план</h2>
            <p class="section-subtitle">
                Прозрачные цены. Без скрытых платежей. Отмена в любой момент.
            </p>
        </div>

        <div class="pricing-grid">
            @foreach($plans as $plan)
                <div class="price-card {{ $plan['is_featured'] ? 'featured' : '' }}">
                    @if($plan['badge'] && $display['show_badge'])
                        <div class="price-badge">{{ $plan['badge'] }}</div>
                    @endif

                    <div class="price-name">{{ $plan['title'] }}</div>
                    <div class="price-desc">{{ $plan['description'] }}</div>

                    <div class="price-amount">
                        @if($plan['old_price'] && $display['show_old_price'])
                            <span class="price-old">{{ $plan['formatted_old_price'] }} ₽</span>
                        @endif
                        <span class="price-value">{{ $plan['formatted_price'] }}</span>
                        <span class="price-period">{{ $plan['period_label'] }}</span>
                    </div>

                    @if($plan['price_note'] && $display['show_price_note'])
                        <div class="price-note">{{ $plan['price_note'] }}</div>
                    @endif

                    <ul class="price-features">
                        @foreach($plan['features'] ?? [] as $feature)
                            <li><i class="fa-solid fa-check"></i><span>{{ $feature }}</span></li>
                        @endforeach
                    </ul>

                    <a href="{{ $plan['button_url'] ?? '#cta' }}"
                       class="price-btn {{ $plan['is_featured'] ? '' : 'ghost' }}">
                        {{ $plan['button_text'] ?? 'Выбрать' }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ==========================================
     CTA FORM
     ========================================== -->
<section class="section cta-section" id="cta">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Готовы запустить свой магазин?</h2>
            <p class="cta-subtitle">
                Оставьте заявку — мы свяжемся с вами в течение часа и поможем всё настроить
            </p>

            <div class="cta-form">
                <form id="leadForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Имя *</label>
                            <input type="text" name="name" class="form-input" placeholder="Ваше имя" required>
                            <div class="form-error">Введите имя</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Телефон *</label>
                            <input type="tel" name="phone" class="form-input" placeholder="+7 (___) ___-__-__" required>
                            <div class="form-error">Введите корректный телефон</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="your@email.com">
                        <div class="form-error">Введите корректный email</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Желаемый адрес магазина</label>
                        <input type="text" name="slug" class="form-input" placeholder="myshop"
                               @if($requestedSlug) value="{{ $requestedSlug }}" @endif>
                        <div class="form-error">Только латиница, цифры и дефис</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Расскажите о вашем бизнесе</label>
                        <textarea name="message" class="form-input" rows="3"
                                  placeholder="Чем занимаетесь, что продаёте..."></textarea>
                    </div>

                    <button type="submit" class="form-submit" id="submitBtn">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Отправить заявку</span>
                    </button>
                </form>

                <div class="form-success" id="formSuccess">
                    <i class="fa-solid fa-circle-check"></i>
                    <h3>Заявка отправлена!</h3>
                    <p>Мы свяжемся с вами в ближайшее время</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================
     FOOTER
     ========================================== -->
<footer class="footer" id="contact">
    <div class="footer-grid">
        <div class="footer-brand">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                {{ $appName }}
            </a>
            <p>
                Современная платформа для создания PWA-магазинов.
                Помогаем бизнесу расти в онлайне.
            </p>
            <div class="footer-social">
                <a href="#" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>
                <a href="#" aria-label="VK"><i class="fa-brands fa-vk"></i></a>
                <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>

        <div class="footer-col">
            <h4>Продукт</h4>
            <ul>
                <li><a href="#features">Возможности</a></li>
                <li><a href="#pricing">Тарифы</a></li>
                <li><a href="#how-it-works">Как работает</a></li>
                <li><a href="#">Документация</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Компания</h4>
            <ul>
                <li><a href="#">О нас</a></li>
                <li><a href="#">Блог</a></li>
                <li><a href="#">Карьера</a></li>
                <li><a href="#">Контакты</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Поддержка</h4>
            <ul>
                <li><a href="#">Помощь</a></li>
                <li><a href="#">FAQ</a></li>
                <li><a href="#">Статус</a></li>
                <li><a href="mailto:support@mypwa.ru">support@mypwa.ru</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div>© {{ $year }} {{ $appName }}. Все права защищены.</div>
        <div>
            <a href="#" style="color: rgba(255,255,255,0.5); margin-right: 20px;">Политика конфиденциальности</a>
            <a href="#" style="color: rgba(255,255,255,0.5);">Условия использования</a>
        </div>
    </div>
</footer>

<!-- ==========================================
     VANILLA JS
     ========================================== -->
<script>
    (function () {
        'use strict';

        // ==========================================
        // ХЕДЕР: скролл-эффект
        // ==========================================
        const header = document.getElementById('header');

        function handleHeaderScroll() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }

        window.addEventListener('scroll', handleHeaderScroll, {passive: true});
        handleHeaderScroll();

        // ==========================================
        // МОБИЛЬНОЕ МЕНЮ
        // ==========================================
        const burger = document.getElementById('burger');
        const mobileMenu = document.getElementById('mobileMenu');

        if (burger && mobileMenu) {
            burger.addEventListener('click', function () {
                burger.classList.toggle('active');
                mobileMenu.classList.toggle('active');
            });

            // Закрытие при клике на ссылку
            mobileMenu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    burger.classList.remove('active');
                    mobileMenu.classList.remove('active');
                });
            });
        }

        // ==========================================
        // ПЛАВНЫЙ СКРОЛЛ
        // ==========================================
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#' || href.length < 2) return;

                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    const offset = header.offsetHeight + 20;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // ==========================================
        // АНИМАЦИЯ ПОЯВЛЕНИЯ ПРИ СКРОЛЛЕ
        // ==========================================
        const animatedElements = document.querySelectorAll('.feature-card, .step-card, .price-card');

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry, index) {
                    if (entry.isIntersecting) {
                        // Задержка для каскадного эффекта
                        setTimeout(function () {
                            entry.target.classList.add('visible');
                        }, index * 80);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            animatedElements.forEach(function (el) {
                observer.observe(el);
            });
        } else {
            // Fallback для старых браузеров
            animatedElements.forEach(function (el) {
                el.classList.add('visible');
            });
        }

        // ==========================================
        // АНИМАЦИЯ СЧЁТЧИКОВ
        // ==========================================
        const counters = document.querySelectorAll('[data-counter]');
        let countersAnimated = false;

        function animateCounter(el) {
            const target = parseInt(el.getAttribute('data-counter'), 10);
            const duration = 2000;
            const start = performance.now();

            function update(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);

                // Easing функция (ease-out)
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.floor(eased * target);

                // Форматирование с разделителями
                el.textContent = current.toLocaleString('ru-RU');

                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.textContent = target.toLocaleString('ru-RU');
                }
            }

            requestAnimationFrame(update);
        }

        if (counters.length > 0) {
            const counterObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting && !countersAnimated) {
                        countersAnimated = true;
                        counters.forEach(animateCounter);
                        counterObserver.disconnect();
                    }
                });
            }, {threshold: 0.3});

            counters.forEach(function (counter) {
                counterObserver.observe(counter);
            });
        }

        // ==========================================
        // МАСКА ТЕЛЕФОНА
        // ==========================================
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');

                if (value.startsWith('8')) {
                    value = '7' + value.slice(1);
                }
                if (!value.startsWith('7') && value.length > 0) {
                    value = '7' + value;
                }

                let formatted = '';
                if (value.length > 0) formatted = '+' + value[0];
                if (value.length > 1) formatted += ' (' + value.slice(1, 4);
                if (value.length >= 4) formatted += ') ' + value.slice(4, 7);
                if (value.length >= 7) formatted += '-' + value.slice(7, 9);
                if (value.length >= 9) formatted += '-' + value.slice(9, 11);

                e.target.value = formatted;
            });
        }

        // ==========================================
        // ВАЛИДАЦИЯ И ОТПРАВКА ФОРМЫ
        // ==========================================
        const form = document.getElementById('leadForm');
        const submitBtn = document.getElementById('submitBtn');
        const formSuccess = document.getElementById('formSuccess');

        if (form) {
            function validateField(input) {
                const name = input.name;
                const value = input.value.trim();
                const errorEl = input.nextElementSibling;
                let valid = true;

                if (input.required && !value) {
                    valid = false;
                }

                if (name === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    valid = false;
                }

                if (name === 'phone' && value.replace(/\D/g, '').length !== 11) {
                    valid = false;
                }

                if (name === 'slug' && value && !/^[a-zA-Z0-9_-]+$/.test(value)) {
                    valid = false;
                }

                if (valid) {
                    input.classList.remove('error');
                    if (errorEl) errorEl.classList.remove('visible');
                } else {
                    input.classList.add('error');
                    if (errorEl) errorEl.classList.add('visible');
                }

                return valid;
            }

            // Валидация при blur
            form.querySelectorAll('.form-input').forEach(function (input) {
                input.addEventListener('blur', function () {
                    validateField(this);
                });

                input.addEventListener('input', function () {
                    if (this.classList.contains('error')) {
                        validateField(this);
                    }
                });
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                let allValid = true;
                form.querySelectorAll('.form-input').forEach(function (input) {
                    if (!validateField(input)) {
                        allValid = false;
                    }
                });

                if (!allValid) return;

                // Имитация отправки (замените на реальный запрос)
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Отправка...</span>';

                setTimeout(function () {
                    // TODO: Замените на реальный fetch/axios
                    // fetch('/api/leads', { method: 'POST', body: new FormData(form) })

                    form.style.display = 'none';
                    formSuccess.classList.add('visible');

                    // Скролл к сообщению
                    formSuccess.scrollIntoView({behavior: 'smooth', block: 'center'});
                }, 1500);
            });
        }

        // ==========================================
        // ПАРАЛЛАКС BLOB-ЭФФЕКТ (легкий)
        // ==========================================
        const blobs = document.querySelectorAll('.hero-blob');
        if (blobs.length > 0 && window.innerWidth > 768) {
            let ticking = false;

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    requestAnimationFrame(function () {
                        const scrolled = window.pageYOffset;
                        blobs.forEach(function (blob, i) {
                            const speed = (i + 1) * 0.1;
                            blob.style.transform = 'translateY(' + (scrolled * speed) + 'px)';
                        });
                        ticking = false;
                    });
                    ticking = true;
                }
            }, {passive: true});
        }

        console.log('🚀 Landing initialized');
    })();
</script>

</body>
</html>
