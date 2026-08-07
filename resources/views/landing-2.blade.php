<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Донбасс IT — PWA-приложения для бизнеса</title>
    <meta name="description"
          content="Мульти-тенантная PWA-платформа для заведений: меню, заказы, лояльность, геймификация, CRM и аналитика. Разработка — Донбасс IT.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Unbounded:wght@500;700;900&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0e;
            --bg2: #0f0f15;
            --card: #14141c;
            --line: rgba(255, 255, 255, .08);
            --txt: #f5f5f7;
            --mut: #9b9ba8;
            --or: #ff7a1a;
            --or2: #ffb25c;
            --grad: linear-gradient(135deg, #ff7a1a, #ff9d4d 60%, #ffb25c);
            --r: 22px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        html {
            scroll-behavior: smooth
        }

        body {
            background: var(--bg);
            color: var(--txt);
            font-family: 'Manrope', sans-serif;
            overflow-x: hidden
        }

        ::selection {
            background: var(--or);
            color: #111
        }

        ::-webkit-scrollbar {
            width: 10px
        }

        ::-webkit-scrollbar-thumb {
            background: #2a2a35;
            border-radius: 8px
        }

        ::-webkit-scrollbar-track {
            background: var(--bg)
        }

        h1, h2, h3, .logo {
            font-family: 'Unbounded', sans-serif
        }

        .wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 22px
        }

        section {
            padding: 96px 0;
            position: relative
        }

        .overline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--or);
            background: rgba(255, 122, 26, .1);
            border: 1px solid rgba(255, 122, 26, .35);
            padding: 8px 16px;
            border-radius: 100px;
            margin-bottom: 18px
        }

        h2 {
            font-size: clamp(26px, 4vw, 42px);
            font-weight: 700;
            line-height: 1.15;
            margin-bottom: 14px
        }

        .sub {
            color: var(--mut);
            font-size: 17px;
            max-width: 640px;
            line-height: 1.65
        }

        .grad {
            background: var(--grad);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent
        }

        /* NAV */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            backdrop-filter: blur(18px);
            background: rgba(10, 10, 14, .72);
            border-bottom: 1px solid var(--line)
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px
        }

        .logo {
            font-weight: 900;
            font-size: 19px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--txt)
        }

        .logo b {
            color: var(--or)
        }

        .logo .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--grad);
            box-shadow: 0 0 14px var(--or)
        }

        .links {
            display: flex;
            gap: 26px
        }

        .links a {
            color: var(--mut);
            text-decoration: none;
            font-weight: 600;
            font-size: 14.5px;
            transition: .2s
        }

        .links a:hover {
            color: var(--or2)
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 26px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 15px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: .25s;
            font-family: 'Manrope'
        }

        .btn-p {
            background: var(--grad);
            color: #161006;
            box-shadow: 0 8px 30px rgba(255, 122, 26, .35)
        }

        .btn-p:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 40px rgba(255, 122, 26, .5)
        }

        .btn-o {
            background: transparent;
            color: var(--txt);
            border: 1px solid var(--line)
        }

        .btn-o:hover {
            border-color: var(--or);
            color: var(--or2)
        }

        .burger {
            display: none;
            background: none;
            border: none;
            color: var(--txt);
            font-size: 26px;
            cursor: pointer
        }

        /* HERO */
        .hero {
            padding: 170px 0 90px;
            background: radial-gradient(900px 500px at 80% -10%, rgba(255, 122, 26, .16), transparent 60%), radial-gradient(700px 400px at 10% 20%, rgba(255, 122, 26, .08), transparent 60%)
        }

        .hero-g {
            display: grid;
            grid-template-columns:1.15fr .85fr;
            gap: 50px;
            align-items: center
        }

        h1 {
            font-size: clamp(30px, 4.6vw, 54px);
            font-weight: 900;
            line-height: 1.12;
            margin: 18px 0 20px
        }

        .hero p.lead {
            color: var(--mut);
            font-size: 17.5px;
            line-height: 1.7;
            max-width: 560px;
            margin-bottom: 32px
        }

        .hero-btns {
            display: flex;
            gap: 14px;
            flex-wrap: wrap
        }

        .stats {
            display: grid;
            grid-template-columns:repeat(4, 1fr);
            gap: 14px;
            margin-top: 52px
        }

        .stat {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px
        }

        .stat b {
            font-family: 'Unbounded';
            font-size: 24px;
            color: var(--or2);
            display: block;
            margin-bottom: 6px
        }

        .stat span {
            color: var(--mut);
            font-size: 13px;
            line-height: 1.4;
            display: block
        }

        /* PHONE */
        .phone-zone {
            position: relative;
            display: flex;
            justify-content: center
        }

        .phone {
            width: 290px;
            height: 600px;
            border-radius: 44px;
            background: #17171f;
            border: 1px solid rgba(255, 255, 255, .14);
            box-shadow: 0 40px 90px rgba(0, 0, 0, .6), 0 0 0 8px #0d0d12, 0 0 80px rgba(255, 122, 26, .15);
            padding: 14px;
            position: relative
        }

        .phone::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 50%;
            transform: translateX(-50%);
            width: 90px;
            height: 22px;
            background: #0d0d12;
            border-radius: 20px;
            z-index: 2
        }

        .screen {
            width: 100%;
            height: 100%;
            border-radius: 32px;
            background: linear-gradient(180deg, #1b1b24, #14141b);
            overflow: hidden;
            display: flex;
            flex-direction: column
        }

        .scr-head {
            padding: 44px 18px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .scr-head b {
            font-size: 15px
        }

        .scr-head span {
            font-size: 11px;
            color: var(--mut);
            display: block
        }

        .scr-cash {
            background: var(--grad);
            color: #161006;
            font-weight: 800;
            font-size: 12px;
            padding: 8px 12px;
            border-radius: 12px
        }

        .chips {
            display: flex;
            gap: 8px;
            padding: 6px 16px;
            overflow: hidden
        }

        .chips i {
            font-style: normal;
            font-size: 11.5px;
            font-weight: 700;
            background: rgba(255, 255, 255, .07);
            padding: 7px 12px;
            border-radius: 100px;
            white-space: nowrap
        }

        .chips i.on {
            background: var(--grad);
            color: #161006
        }

        .scr-grid {
            display: grid;
            grid-template-columns:1fr 1fr;
            gap: 10px;
            padding: 12px 16px
        }

        .food {
            background: rgba(255, 255, 255, .05);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px;
            position: relative
        }

        .food .em {
            font-size: 30px
        }

        .food b {
            display: block;
            font-size: 12px;
            margin: 8px 0 2px
        }

        .food span {
            color: var(--or2);
            font-weight: 800;
            font-size: 12.5px
        }

        .food .plus {
            position: absolute;
            right: 10px;
            bottom: 10px;
            width: 24px;
            height: 24px;
            border-radius: 8px;
            background: var(--grad);
            color: #161006;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px
        }

        .scr-banner {
            margin: 4px 16px;
            border-radius: 16px;
            padding: 14px;
            background: linear-gradient(120deg, rgba(255, 122, 26, .25), rgba(255, 122, 26, .08));
            border: 1px solid rgba(255, 122, 26, .4);
            font-size: 12.5px;
            font-weight: 700
        }

        .scr-nav {
            margin-top: auto;
            display: flex;
            justify-content: space-around;
            padding: 12px 8px 20px;
            border-top: 1px solid var(--line);
            font-size: 16px
        }

        .scr-nav i {
            font-style: normal;
            opacity: .45
        }

        .scr-nav i.on {
            opacity: 1;
            filter: drop-shadow(0 0 8px var(--or))
        }

        .float {
            position: absolute;
            background: rgba(20, 20, 28, .9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 122, 26, .4);
            border-radius: 16px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 800;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .5);
            animation: fl 5s ease-in-out infinite;
            z-index: 3
        }

        .float small {
            display: block;
            color: var(--mut);
            font-weight: 600;
            font-size: 11px;
            margin-top: 2px
        }

        .f1 {
            top: 70px;
            left: -30px
        }

        .f2 {
            bottom: 120px;
            right: -36px;
            animation-delay: 1.4s
        }

        .f3 {
            top: 45%;
            left: -56px;
            animation-delay: 2.6s
        }

        @keyframes fl {
            0%, 100% {
                transform: translateY(0)
            }
            50% {
                transform: translateY(-14px)
            }
        }

        /* MARQUEE */
        .marq {
            border-block: 1px solid var(--line);
            background: var(--bg2);
            overflow: hidden;
            padding: 16px 0
        }

        .marq-in {
            display: flex;
            gap: 44px;
            width: max-content;
            animation: mq 30s linear infinite;
            font-family: 'Unbounded';
            font-size: 13px;
            color: var(--mut);
            white-space: nowrap
        }

        .marq-in b {
            color: var(--or)
        }

        @keyframes mq {
            to {
                transform: translateX(-50%)
            }
        }

        /* CARDS */
        .grid {
            display: grid;
            gap: 18px;
            margin-top: 44px
        }

        .g3 {
            grid-template-columns:repeat(3, 1fr)
        }

        .g2 {
            grid-template-columns:repeat(2, 1fr)
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--r);
            padding: 28px;
            transition: .3s;
            position: relative;
            overflow: hidden
        }

        .card:hover {
            transform: translateY(-6px);
            border-color: rgba(255, 122, 26, .45);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .4)
        }

        .card .ic {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: rgba(255, 122, 26, .12);
            border: 1px solid rgba(255, 122, 26, .3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 18px
        }

        .card h3 {
            font-size: 17px;
            margin-bottom: 10px
        }

        .card p {
            color: var(--mut);
            font-size: 14.5px;
            line-height: 1.65
        }

        /* TECH */
        .tech-g {
            display: grid;
            grid-template-columns:repeat(3, 1fr);
            gap: 18px;
            margin-top: 44px
        }

        .tech ul {
            list-style: none;
            margin-top: 14px
        }

        .tech li {
            padding: 9px 0;
            border-bottom: 1px dashed var(--line);
            color: var(--mut);
            font-size: 14.5px;
            display: flex;
            gap: 10px
        }

        .tech li::before {
            content: '▸';
            color: var(--or)
        }

        .road {
            margin-top: 40px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap
        }

        .road span {
            background: var(--card);
            border: 1px dashed rgba(255, 122, 26, .5);
            color: var(--or2);
            border-radius: 100px;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 14px
        }

        /* PRICING */
        .price-g {
            display: grid;
            grid-template-columns:repeat(3, 1fr);
            gap: 20px;
            margin-top: 50px;
            align-items: stretch
        }

        .plan {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 26px;
            padding: 34px 30px;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: .3s
        }

        .plan:hover {
            transform: translateY(-6px)
        }

        .plan.hot {
            border: 1px solid transparent;
            background: linear-gradient(var(--card), var(--card)) padding-box, var(--grad) border-box;
            box-shadow: 0 20px 60px rgba(255, 122, 26, .18)
        }

        .badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--grad);
            color: #161006;
            font-weight: 900;
            font-size: 12px;
            padding: 7px 18px;
            border-radius: 100px;
            letter-spacing: .06em
        }

        .plan h3 {
            font-size: 19px
        }

        .plan .pr {
            font-family: 'Unbounded';
            font-size: 32px;
            font-weight: 900;
            margin: 16px 0 4px
        }

        .plan .pr small {
            font-size: 14px;
            color: var(--mut);
            font-weight: 500
        }

        .plan ul {
            list-style: none;
            margin: 22px 0 30px;
            flex: 1
        }

        .plan li {
            padding: 9px 0;
            color: var(--mut);
            font-size: 14.5px;
            display: flex;
            gap: 10px;
            line-height: 1.5
        }

        .plan li::before {
            content: '✓';
            color: var(--or);
            font-weight: 900
        }

        .plan li.hl {
            color: var(--txt)
        }

        /* TEAM */
        .team-g {
            display: grid;
            grid-template-columns:repeat(4, 1fr);
            gap: 18px;
            margin-top: 44px
        }

        .member {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--r);
            padding: 26px;
            text-align: center;
            transition: .3s
        }

        .member:hover {
            transform: translateY(-6px);
            border-color: rgba(255, 122, 26, .4)
        }

        .ava {
            width: 84px;
            height: 84px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: rgba(255, 122, 26, .12);
            border: 2px solid rgba(255, 122, 26, .4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px
        }

        .member b {
            font-size: 15.5px;
            display: block;
            margin-bottom: 6px
        }

        .member span {
            color: var(--mut);
            font-size: 13px;
            line-height: 1.55;
            display: block
        }

        .member.grow {
            border-style: dashed;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: transparent
        }

        .member.grow .ava {
            border-style: dashed;
            background: transparent
        }

        /* PORTFOLIO */
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 34px
        }

        .fbtn {
            background: var(--card);
            border: 1px solid var(--line);
            color: var(--mut);
            border-radius: 100px;
            padding: 10px 18px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: .2s;
            font-family: 'Manrope'
        }

        .fbtn.on, .fbtn:hover {
            background: var(--grad);
            color: #161006;
            border-color: transparent
        }

        .pgrid {
            display: grid;
            grid-template-columns:repeat(4, 1fr);
            gap: 14px;
            margin-top: 26px
        }

        .pcard {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px;
            cursor: pointer;
            transition: .25s;
            display: flex;
            flex-direction: column;
            gap: 8px
        }

        .pcard:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 122, 26, .5)
        }

        .pcard .tag {
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--or)
        }

        .pcard b {
            font-size: 14.5px;
            line-height: 1.3
        }

        .pcard p {
            color: var(--mut);
            font-size: 12.5px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden
        }

        .pcard .tm {
            margin-top: auto;
            color: #6d6d7a;
            font-size: 11.5px;
            font-weight: 700
        }

        /* MODAL */
        .modal {
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(5, 5, 8, .8);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px
        }

        .modal.open {
            display: flex
        }

        .mbox {
            background: var(--bg2);
            border: 1px solid rgba(255, 122, 26, .35);
            border-radius: 26px;
            max-width: 640px;
            width: 100%;
            max-height: 82vh;
            overflow: auto;
            padding: 38px;
            position: relative;
            animation: pop .25s ease
        }

        @keyframes pop {
            from {
                transform: scale(.92);
                opacity: 0
            }
        }

        .mclose {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--card);
            border: 1px solid var(--line);
            color: var(--txt);
            font-size: 18px;
            cursor: pointer
        }

        .mbox .tag {
            color: var(--or);
            font-weight: 800;
            font-size: 12px;
            letter-spacing: .1em;
            text-transform: uppercase
        }

        .mbox h3 {
            font-size: 22px;
            margin: 10px 0 4px
        }

        .mbox .meta {
            color: var(--mut);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 16px
        }

        .mbox p {
            color: var(--mut);
            line-height: 1.7;
            font-size: 15px;
            margin-bottom: 20px
        }

        .tchips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap
        }

        .tchips i {
            font-style: normal;
            background: rgba(255, 122, 26, .1);
            border: 1px solid rgba(255, 122, 26, .3);
            color: var(--or2);
            font-size: 12px;
            font-weight: 700;
            padding: 7px 12px;
            border-radius: 100px
        }

        /* CONTACT */
        .contact-g {
            display: grid;
            grid-template-columns:1fr 1fr;
            gap: 50px;
            align-items: start
        }

        .cinfo li {
            list-style: none;
            display: flex;
            gap: 14px;
            margin-bottom: 18px;
            color: var(--mut);
            font-size: 15px;
            line-height: 1.6
        }

        .cinfo b {
            color: var(--txt)
        }

        form {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 26px;
            padding: 34px;
            display: flex;
            flex-direction: column;
            gap: 16px
        }

        input, textarea, select {
            background: var(--bg2);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 15px 18px;
            color: var(--txt);
            font-family: 'Manrope';
            font-size: 15px;
            outline: none;
            transition: .2s;
            width: 100%
        }

        input:focus, textarea:focus {
            border-color: var(--or)
        }

        textarea {
            min-height: 110px;
            resize: vertical
        }

        .note {
            color: #6d6d7a;
            font-size: 12px;
            line-height: 1.5
        }

        footer {
            border-top: 1px solid var(--line);
            padding: 34px 0;
            color: var(--mut);
            font-size: 14px
        }

        .foot {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center
        }

        .toast {
            position: fixed;
            bottom: 26px;
            left: 50%;
            transform: translate(-50%, 80px);
            background: var(--grad);
            color: #161006;
            font-weight: 800;
            padding: 16px 28px;
            border-radius: 16px;
            z-index: 300;
            transition: .4s;
            opacity: 0;
            box-shadow: 0 20px 50px rgba(255, 122, 26, .4)
        }

        .toast.show {
            transform: translate(-50%, 0);
            opacity: 1
        }

        .rev {
            opacity: 0;
            transform: translateY(26px);
            transition: .7s ease
        }

        .rev.vis {
            opacity: 1;
            transform: none
        }

        @media (max-width: 980px) {
            .hero-g, .contact-g {
                grid-template-columns:1fr
            }

            .g3, .g2, .tech-g, .price-g {
                grid-template-columns:1fr 1fr
            }

            .team-g, .pgrid {
                grid-template-columns:1fr 1fr
            }

            .stats {
                grid-template-columns:1fr 1fr
            }

            .links {
                display: none;
                position: absolute;
                top: 72px;
                left: 0;
                right: 0;
                background: var(--bg2);
                flex-direction: column;
                padding: 24px;
                gap: 18px;
                border-bottom: 1px solid var(--line)
            }

            .links.open {
                display: flex
            }

            .burger {
                display: block
            }

            .phone-zone {
                margin-top: 30px
            }

            .f3 {
                left: 0
            }
        }

        @media (max-width: 620px) {
            .g3, .g2, .tech-g, .price-g, .team-g, .pgrid {
                grid-template-columns:1fr
            }

            .f1 {
                left: 0
            }

            .f2 {
                right: 0
            }

            section {
                padding: 70px 0
            }
        }
    </style>
</head>
<body>

<header>
    <div class="wrap nav">
        <a class="logo" href="#"><span class="dot"></span>ДОНБАСС <b>IT</b></a>
        <nav class="links" id="links">
            <a href="#guests">Гостям</a><a href="#business">Бизнесу</a><a href="#tech">Технологии</a>
            <a href="#pricing">Тарифы</a><a href="#team">Команда</a><a href="#portfolio">Портфолио</a>
        </nav>
        <a class="btn btn-p" href="#contact" style="padding:11px 20px">Обсудить проект</a>
        <button class="burger" onclick="document.getElementById('links').classList.toggle('open')">☰</button>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="wrap hero-g">
        <div>
            <span class="overline">🚀 Мульти-тенантная SaaS-платформа</span>
            <h1>MYPWA для вашего заведения: <span class="grad">от меню до CRM и геймификации</span></h1>
            <p class="lead">Мы создаём единое цифровое пространство для HoReCa и ритейла: PWA-приложение для гостей,
                мощная админ-панель для бизнеса, программы лояльности, игровые механики и сквозная аналитика — в одной
                экосистеме.</p>
            <div class="hero-btns">
                <a class="btn btn-p" href="#pricing">Смотреть тарифы</a>
                <a class="btn btn-o" href="#contact">Обсудить проект</a>
            </div>
            <div class="stats">
                <div class="stat"><b>39+</b><span>проектов в портфолио команды</span></div>
                <div class="stat"><b>1</b><span>платформа вместо 5–6 отдельных сервисов</span></div>
                <div class="stat"><b>×2</b><span>возвращаемость гостей за счёт геймификации</span></div>
                <div class="stat"><b>24/7</b><span>PWA работает как приложение — без App Store</span></div>
            </div>
        </div>
        <div class="phone-zone">
            <div class="float f1">🎁 +250 ₽ кэшбэк<small>начислено за заказ</small></div>
            <div class="float f2">🍽 Стол 12 · заказ отправлен<small>self-checkout доступен</small></div>
            <div class="float f3">🎰 Приз: кофе бесплатно<small>колесо фортуны</small></div>
            <div class="phone">
                <div class="screen">
                    <div class="scr-head">
                        <div><b>Привет! 👋</b><span>вы в заведении · стол 12</span></div>
                        <div class="scr-cash">5%</div>
                    </div>
                    <div class="chips"><i class="on">🍕 Пицца</i><i>🍔 Бургеры</i><i>☕ Кофе</i><i>🍣 Суши</i></div>
                    <div class="scr-grid">
                        <div class="food"><span class="em">🍔</span><b>Собери бургер</b><span>от 340 ₽</span><i
                                class="plus">+</i></div>
                        <div class="food"><span class="em">🍕</span><b>Пицца BBQ</b><span>520 ₽</span><i
                                class="plus">+</i></div>
                        <div class="food"><span class="em">☕</span><b>Раф солёная карамель</b><span>280 ₽</span><i
                                class="plus">+</i></div>
                        <div class="food"><span class="em">🧇</span><b>Вафля манго</b><span>310 ₽</span><i
                                class="plus">+</i></div>
                    </div>
                    <div class="scr-banner">🎡 Крутите колесо — приз ждёт!</div>
                    <div class="scr-nav"><i class="on">🏠</i><i>🛒</i><i>🎁</i><i>💬</i><i>👤</i></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="marq">
    <div class="marq-in" id="marq"></div>
</div>

<!-- GUESTS -->
<section id="guests">
    <div class="wrap">
        <span class="overline">📱 Frontend / PWA</span>
        <h2>Для гостей — приложение заведения <span class="grad">в кармане</span></h2>
        <p class="sub">Никаких установок из сторов: гость открывает PWA, и всё работает сразу. Быстро, вкусно,
            залипательно.</p>
        <div class="grid g3">
            <div class="card rev">
                <div class="ic">✨</div>
                <h3>Бесшовный онбординг</h3>
                <p>Без длинных регистраций: гостевой профиль создаётся автоматически, данные подтягиваются при
                    оформлении заказа. Захотели сохранить историю и бонусы — привязали телефон.</p></div>
            <div class="card rev">
                <div class="ic">🛒</div>
                <h3>E-commerce & Delivery</h3>
                <p>Каталог, корзина, оплата по СБП и оформление заказа в несколько кликов.</p></div>
            <div class="card rev">
                <div class="ic">🍽</div>
                <h3>Умный зал</h3>
                <p>Бронирование столов, цифровое меню, вызов официанта, self-checkout и привязка заказа к конкретному
                    столику.</p></div>
            <div class="card rev">
                <div class="ic">🎁</div>
                <h3>Лояльность 2.0</h3>
                <p>Кэшбэк, промокоды, реферальная система, дерево друзей и система достижений.</p></div>
            <div class="card rev">
                <div class="ic">🎮</div>
                <h3>Геймификация</h3>
                <p>Колесо фортуны, слот-машины, скретч-карты, викторины, ежедневные бонусы и другие игровые
                    механики.</p></div>
            <div class="card rev">
                <div class="ic">🍕</div>
                <h3>Интерактив</h3>
                <p>Конструкторы блюд (бургеры, пицца, кофе, суши, вафли и др.), Stories и TapLink.</p></div>
            <div class="card rev">
                <div class="ic">💬</div>
                <h3>Коммуникация</h3>
                <p>Встроенные чаты с персоналом и поддержкой, push-уведомления о статусах заказов и акциях.</p></div>
        </div>
    </div>
</section>

<!-- BUSINESS -->
<section id="business" style="background:var(--bg2)">
    <div class="wrap">
        <span class="overline">⚙️ Admin & Backoffice</span>
        <h2>Для бизнеса — <span class="grad">управление и рост</span></h2>
        <p class="sub">Владелец и персонал получают единую панель управления: от карточек товаров до аналитики
            продаж.</p>
        <div class="grid g3">
            <div class="card rev">
                <div class="ic">📦</div>
                <h3>Продукты 2.0</h3>
                <p>Отдельный сервис-хаб для управления карточками товаров с автоматической синхронизацией между iiko,
                    FrontPad, ВКонтакте и внутренними сервисами платформы.</p></div>
            <div class="card rev">
                <div class="ic">👨‍🍳</div>
                <h3>Waiter App</h3>
                <p>Управление столами, отправка заказов на кухню, разделение счёта и закрытие чеков.</p></div>
            <div class="card rev">
                <div class="ic">📊</div>
                <h3>CRM</h3>
                <p>Kanban-доски, работа с заказами, клиентами, инвойсами и транзакциями.</p></div>
            <div class="card rev">
                <div class="ic">🔐</div>
                <h3>RBAC</h3>
                <p>Гибкая ролевая модель с настраиваемыми правами доступа для каждого сотрудника.</p></div>
            <div class="card rev">
                <div class="ic">📣</div>
                <h3>Маркетинг</h3>
                <p>Push-рассылки, менеджер Stories, конструктор витрин и лендингов.</p></div>
            <div class="card rev">
                <div class="ic">📈</div>
                <h3>Аналитика</h3>
                <p>Дашборды по продажам, трафику, эффективности акций и экспорт данных.</p></div>
        </div>
    </div>
</section>

<!-- TECH -->
<section id="tech">
    <div class="wrap">
        <span class="overline">🛠 Под капотом</span>
        <h2>Архитектура, которая <span class="grad">масштабируется</span></h2>
        <div class="tech-g">
            <div class="card tech rev">
                <div class="ic">🧬</div>
                <h3>Архитектура</h3>
                <ul>
                    <li>SaaS (Multi-tenant)</li>
                    <li>Изоляция данных заведений</li>
                    <li>Быстрый онбординг новых тенантов</li>
                    <li>Event-driven подход</li>
                </ul>
            </div>
            <div class="card tech rev">
                <div class="ic">⚡</div>
                <h3>Frontend</h3>
                <ul>
                    <li>Vue 3</li>
                    <li>PWA + Service Workers</li>
                    <li>Асинхронная загрузка компонентов</li>
                    <li>Mobile-first интерфейсы</li>
                </ul>
            </div>
            <div class="card tech rev">
                <div class="ic">🐘</div>
                <h3>Backend</h3>
                <ul>
                    <li>Laravel</li>
                    <li>REST API</li>
                    <li>Webhooks</li>
                    <li>Event-driven архитектура</li>
                </ul>
            </div>
        </div>
        <div class="road">
            <span>📱 Нативные приложения</span><span>💳 Расширение платёжного функционала</span><span>📊 Глубокая аналитика</span><span>🎮 Новые игровые механики</span><span>📢 Рекламные и SEO-инструменты</span>
        </div>
    </div>
</section>

<!-- PRICING -->
<section id="pricing" style="background:var(--bg2)">
    <div class="wrap">
        <span class="overline">💎 Тарифы</span>
        <h2>Прозрачные условия <span class="grad">для любого масштаба</span></h2>
        <p class="sub">Начните с одной точки и масштабируйтесь до сети — платформа растёт вместе с вами.</p>
        <div class="price-g">
            <div class="plan rev"><h3>Старт</h3>
                <div class="pr">4 900 ₽<small>/мес</small></div>
                <ul>
                    <li class="hl">PWA-приложение заведения</li>
                    <li>Каталог, корзина, заказы</li>
                    <li>Кэшбэк и промокоды</li>
                    <li>Push-уведомления</li>
                    <li>1 заведение</li>
                    <li>Базовая поддержка</li>
                </ul>
                <button class="btn btn-o" data-plan="Старт">Выбрать тариф</button>
            </div>
            <div class="plan hot rev"><span class="badge">ХИТ 🔥</span>
                <h3>Бизнес</h3>
                <div class="pr">9 900 ₽<small>/мес</small></div>
                <ul>
                    <li>Всё из тарифа «Старт»</li>
                    <li class="hl">Зал: QR-меню, столы, официанты, self-checkout</li>
                    <li class="hl">Геймификация: колесо, слоты, квизы, достижения</li>
                    <li>Чаты с гостями и Stories</li>
                    <li>CRM: kanban, заказы, клиенты</li>
                    <li>Аналитика и экспорт</li>
                </ul>
                <button class="btn btn-p" data-plan="Бизнес">Выбрать тариф</button>
            </div>
            <div class="plan rev"><h3>Сеть / Enterprise</h3>
                <div class="pr">Индив.<small> расчёт</small></div>
                <ul>
                    <li>Multi-tenant для сети заведений</li>
                    <li class="hl">«Продукты 2.0»: синхронизация iiko, FrontPad, VK</li>
                    <li>RBAC и кастомные роли</li>
                    <li>Интеграции и доработки под задачи</li>
                    <li>SLA и выделенный менеджер</li>
                </ul>
                <button class="btn btn-o" data-plan="Сеть / Enterprise">Обсудить</button>
            </div>
        </div>
    </div>
</section>

<!-- TEAM -->
<section id="team">
    <div class="wrap">
        <span class="overline">👥 Команда</span>
        <h2>Кто стоит за платформой — <span class="grad">Донбасс IT</span></h2>
        <p class="sub">Команда полного цикла: аналитика, дизайн, frontend, backend, DevOps и поддержка. Сейчас нас трое
            — и мы растём.</p>
        <div class="team-g">
            <div class="member rev">
                <div class="ava">🧠</div>
                <b>Team Lead ·
                    Backend</b><span>Архитектура, Laravel / .NET, интеграции iiko, СБП, CRM, code review</span></div>
            <div class="member rev">
                <div class="ava">⚡</div>
                <b>Frontend · Vue 3</b><span>PWA-интерфейсы, геймификация, скорость и pixel-perfect вёрстка</span></div>
            <div class="member rev">
                <div class="ava">🎨</div>
                <b>UI/UX · Аналитика</b><span>User flow, прототипы и дизайн, который решает задачи бизнеса</span></div>
            <div class="member grow rev">
                <div class="ava">🚀</div>
                <b>Команда растёт</b><span>Подключаем новых специалистов, чтобы ускорить развитие продукта</span></div>
        </div>
    </div>
</section>

<!-- PORTFOLIO -->
<section id="portfolio" style="background:var(--bg2)">
    <div class="wrap">
        <span class="overline">💼 Портфолио</span>
        <h2>39 проектов — <span class="grad">от HoReCa до Enterprise</span></h2>
        <p class="sub">Нажмите на карточку, чтобы посмотреть детали проекта.</p>
        <div class="filters" id="filters"></div>
        <div class="pgrid" id="pgrid"></div>
    </div>
</section>

<!-- CONTACT -->
<section id="contact">
    <div class="wrap contact-g">
        <div>
            <span class="overline">💬 Контакт</span>
            <h2>Обсудим <span class="grad">ваш проект?</span></h2>
            <p class="sub" style="margin-bottom:30px">Расскажите о задаче — предложим архитектуру, сроки и смету. Или
                подключим ваше заведение к платформе.</p>
            <ul class="cinfo">
                <li>📍 <span><b>Донбасс IT</b><br>fullstack-разработка и сложные IT-решения</span></li>
                <li>✈️ <span><b>Telegram:</b><br><a href="https://t.me/EgorShipilov">@EgorShipilov</a></span></li>
                <li>📧 <span><b>Email:</b><br>inbox@mypwa.ru</span></li>
                <li>⏱ <span><b>Ответим в течение дня</b><br>и покажем живое демо платформы</span></li>
            </ul>
        </div>
        <form id="form">
            <input type="text" placeholder="Ваше имя *" required>
            <input type="text" placeholder="Telegram или телефон *" required>
            <input type="text" placeholder="Компания / заведение">
            <textarea id="msg" placeholder="Пара слов о задаче…"></textarea>
            <button class="btn btn-p" type="submit">Отправить заявку 🚀</button>
            <span class="note">Нажимая кнопку, вы соглашаетесь с политикой обработки персональных данных.</span>
        </form>
    </div>
</section>

<footer>
    <div class="wrap foot">
        <span>© 2026 <b style="color:var(--txt)">Донбасс IT</b> · PWA-приложения для бизнес</span>
        <span>Сделано с 🔥 на Vue 3 + Laravel</span>
    </div>
</footer>

<div class="modal" id="modal">
    <div class="mbox">
        <button class="mclose" onclick="closeModal()">✕</button>
        <span class="tag" id="mTag"></span>
        <h3 id="mName"></h3>
        <div class="meta" id="mMeta"></div>
        <p id="mDesc"></p>
        <div class="tchips" id="mTech"></div>
    </div>
</div>
<div class="toast" id="toast">✅ Заявка отправлена! Скоро свяжемся с вами.</div>

<script>
    /* ===== PORTFOLIO DATA ===== */
    const CATS = ['Корпоративные сайты', 'Лендинги и промо', 'eCommerce', 'Платформы и сервисы', 'Highload и кастом', 'CRM и backend', 'Enterprise', 'GameDev и Web3', 'Финтех и беттинг', 'Промышленные системы', 'EdTech и MedTech'];
    const P = [
        ['СК NOF', 0, '4 месяца', 'Сайт строительной компании: каталог объектов недвижимости, страницы услуг и проектов, админ-панель, SEO.', 'PHP,Laravel,MySQL,Bootstrap'],
        ['PetroStone', 0, '5 недель', 'Сайт производителя натурального камня: каталог продукции, галерея проектов, мультиязычность, заявки на расчёт.', 'WordPress,PHP,SCSS,ACF'],
        ['КЭТ33', 0, '4 недели', 'Полностью кастомная тема WordPress без шаблонов: каталог оборудования, формы заявок, SEO-структура.', 'WordPress,PHP,CSS3'],
        ['Booster Rus', 0, '5 недель', 'Сайт поставщика котельного оборудования: каталог, B2B-заявки, лидогенерация, кеширование и защита.', 'WordPress,PHP,MySQL'],
        ['Tola AI', 1, '3 недели', 'Лендинг AI-сервиса по Mobile First: hero, тарифы, CTA-блоки, сбор заявок, подготовка к рекламному трафику.', 'WordPress,PHP,JavaScript'],
        ['Белый парус', 1, '2 месяца', 'Продающий лендинг для HoReCa/B2B: каталог продукции, программа лояльности, логотипы партнёров, адаптив.', 'HTML5,SCSS,JavaScript'],
        ['FoxShop', 2, '6 недель', 'Интернет-магазин: категории, поиск и фильтры, корзина, личный кабинет, система скидок и акций.', 'Laravel,MySQL,Bootstrap 5,REST API'],
        ['Goodiets', 2, '5 месяцев', 'Сайт фитнес-питания: real-time чат, система уведомлений, рефакторинг и улучшение UI/UX.', 'Vue/Nuxt,Laravel,WebSockets'],
        ['CashMan', 2, '4 месяца', 'Платформа Telegram Mini Apps для общепита: интеграции iiko и FrontPad, оплата СБП, кэшбэк, геймификация, CRM, рассылки. Прообраз текущей платформы!', 'Laravel,Vue 3,Telegram API,СБП,PWA'],
        ['В ПУТЬ', 3, '5 месяцев', 'Клуб путешествий: каталог туров, анонсы экспедиций, магазин атрибутики, отзывы, админ-панель.', 'Laravel,Vue 3,Bootstrap 5'],
        ['YourOwn Beauty', 3, '10 недель', 'CRM и лояльность для салонов красоты: бонусы, сегментация базы, личные кабинеты, REST API.', 'Laravel,Vue.js,Swagger'],
        ['Panda Padel', 3, '14 недель', 'Управление падел-клубом: онлайн-бронирование кортов, расписание, тренеры, CRM + мобильное приложение.', 'Laravel,Vue,PostgreSQL,React Native'],
        ['Аукционы (Битрикс + Wolmar)', 3, '3–4 месяца', 'Аукционная платформа: парсинг лотов Wolmar, синхронизация контента, оптимизация БД.', '1С-Битрикс,PHP,Selenium,Cron'],
        ['MPSPOT Admin', 3, '4 месяца', 'Админ-панель/CRM: кастомизация UI, биндинг к бэкенду, вывод реальных данных.', 'Vue.js,SCSS,Webpack'],
        ['ГорВодоканал', 3, '6 месяцев', 'Информационная система водоканала: ЛК физ/юрлиц, показания счётчиков, оплаты (карта/СБП/QR), Telegram-бот, ролевая модель.', 'Laravel,Vue 3,Telegram API,СБП'],
        ['Мастер Кит', 3, '3 месяца', 'Виртуальное сообщество: Telegram-бот с биллингом, контроль доступа к каналам, автомодерация, рассылки, аналитика.', 'Python,FastAPI,Aiogram,Redis,Celery'],
        ['Самовыкуп', 3, '8 месяцев', 'SaaS для самовыкупов на WB/Ozon: пул аккаунтов с прокси, выкупы, отзывы, трекинг позиций, биллинг.', 'Laravel,PostgreSQL,WB/Ozon API'],
        ['Salero.io', 3, '6 месяцев', 'SaaS-аналитика Wildberries: алерты, автозаказ, оборачиваемость, ABC-анализ, финансовые графики.', 'Laravel,Vue,WB API,Chart.js'],
        ['emojis.wiki', 3, '3 месяца', 'Энциклопедия эмодзи с интерактивной клавиатурой, поиском и 7 языками интерфейса.', 'Node.js,Vue 3,i18n'],
        ['Твоя республика', 3, '6 месяцев', 'Мониторинг городской инфраструктуры: Flutter-приложение с геолокацией, тепловые карты, боты Telegram/VK, PDF-отчёты.', 'Laravel,Flutter,Nuxt,Leaflet'],
        ['Кисловодск', 3, '5 месяцев', 'Туристическая платформа: бронирование туров, роли (турист/гид/агентство), чаты, оплаты, карты.', 'Laravel,Vue 3,Redis,ЮKassa'],
        ['Dodoors', 3, '6 месяцев', 'CRM продаж дверей: интерактивный конструктор с расчётом стоимости, генерация договоров, Bitrix24 и МойСклад.', 'Laravel,Vue 3,Bitrix24 API'],
        ['LeoFlowers', 4, '3–4 месяца', 'Высоконагруженный магазин цветов: кастомный модуль расчёта доставки, расширенные промокоды, ролевые ЛК.', 'OpenCart,PHP,MySQL'],
        ['Crypto-союз', 4, '6 месяцев', 'Инвестиционная MLM-платформа: депозиты, бинарные структуры, биржевой стакан лицензий, крипто-шлюзы.', 'Laravel,Binance API,Cron'],
        ['CRM провайдера', 5, '3 месяца', 'CRM интернет-провайдера с нуля: уникальный UI, оптимизация под большие объёмы данных.', 'Laravel 10,Vue 3,Pusher'],
        ['NikahTime', 5, '6 месяцев', 'Backend-сервис: REST API, WebSocket для real-time, Swagger-документация.', 'Laravel,WebSockets,MySQL'],
        ['Спортбаза.рф', 5, '13 месяцев', 'Маркетплейс спорт-объектов: тендеры, эскроу-оплата, арбитраж, генерация смет в PDF, real-time чат.', 'Laravel,Vue,WebSocket,Redis'],
        ['Lekary', 5, '13 месяцев', 'MLM-платформа БАДов: 5 видов бонусов, 14 квалификаций, бинарное дерево, консультации через Zoom.', 'Laravel,Vue,Zoom API'],
        ['Lotofond', 5, '10 месяцев', 'Агрегатор торгов по банкротству: парсинг источников, SOAP-интеграции, каталог лотов, ЛК.', 'Laravel,Vue 3,SOAP,Swagger'],
        ['ForesightZone', 6, '24 месяца', 'AI-форсайт платформа: микросервисы .NET 8, Clean Architecture, CQRS/Saga/Outbox, Keycloak, YARP, Yandex Cloud.', ' .NET 8,React,PostgreSQL,Terraform'],
        ['myChess', 6, '3,5 года', 'Распределённая шахматная платформа: AI-движки (Stockfish, lc0), микросервисы, мониторинг Grafana/Prometheus. Роль Team Lead.', '.NET 6+,NestJS,Kubernetes,Redis'],
        ['Vulcan Forged', 7, '~3 года', 'Web3-гейминг платформа: блокчейн-игры, NFT, метавселенная VulcanVerse, собственный блокчейн Elysium.', '.NET Core,MS SQL,Redis,CI/CD'],
        ['BetRoute', 8, '~3 года', 'ERP/CRM для букмекерских контор: приём ставок, коэффициенты, финучёт, desktop и mobile клиенты.', '.NET,WPF,Xamarin,MSSQL'],
        ['Money Cup', 8, '7 месяцев', 'P2P-маркетплейс киберспорт-сделок: турнирные сетки, арбитраж, кошелёк, real-time чат с модератором.', 'Laravel,Vue,WebSockets,OAuth'],
        ['Interceptor', 9, '2 года', 'Система детектирования проездов: интеграция камер (АвтоИнтеллект, Кордон), WPF-клиент, микросервисы.', 'C#,WPF,MSSQL,WebSocket'],
        ['СДМ', 9, '5 лет', 'Мониторинг дорожного движения: видеопотоки в real-time, распознавание нарушений, Python-микросервисы парсинга.', 'Laravel,FastAPI,OpenCV,Celery'],
        ['Allotrans', 9, '4 месяца', 'Аукцион логистики: скрытые ставки, калькулятор кубатуры, верификация перевозчиков, эскроу-комиссия.', 'Laravel,Vue,Payment API'],
        ['Rosvuz School', 10, 'поддержка', 'LMS онлайн-школы: личный кабинет, учебные страницы, внутренний чат, уведомления.', 'Vue/Nuxt,Laravel,WebSockets'],
        ['Доктор Реутов', 10, '1–2 месяца', 'Медицинский портал: интерактивные тесты на Vue, клиентская логика тестирования, UX-доработки.', 'Vue.js,PHP,SCSS']
    ];
    /* ===== RENDER ===== */
    const grid = document.getElementById('pgrid'), filters = document.getElementById('filters');
    filters.innerHTML = `<button class="fbtn on" data-c="-1">Все</button>` + CATS.map((c, i) => `<button class="fbtn" data-c="${i}">${c}</button>`).join('');

    function renderP(c) {
        grid.innerHTML = P.map((p, i) => (c > -1 && p[1] != c) ? '' : `<div class="pcard" onclick="openModal(${i})"><span class="tag">${CATS[p[1]]}</span><b>${p[0]}</b><p>${p[2]} · ${p[3]}</p><span class="tm">🕐 ${p[2]}</span></div>`).join('')
    }

    renderP(-1);
    filters.addEventListener('click', e => {
        if (!e.target.classList.contains('fbtn')) return;
        document.querySelectorAll('.fbtn').forEach(b => b.classList.remove('on'));
        e.target.classList.add('on');
        renderP(+e.target.dataset.c)
    });

    /* ===== MODAL ===== */
    function openModal(i) {
        const p = P[i];
        mTag.textContent = CATS[p[1]];
        mName.textContent = p[0];
        mMeta.textContent = '🕐 Срок: ' + p[2];
        mDesc.textContent = p[3];
        mTech.innerHTML = p[4].split(',').map(t => `<i>${t}</i>`).join('');
        modal.classList.add('open');
        document.body.style.overflow = 'hidden'
    }

    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = ''
    }

    modal.addEventListener('click', e => {
        if (e.target === modal) closeModal()
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal()
    });
    /* ===== MARQUEE ===== */
    const items = ['Vue 3', 'Laravel', 'PWA', 'Multi-tenant SaaS', 'iiko', 'FrontPad', 'СБП', 'RBAC', 'Геймификация', 'CRM', 'Kanban', 'Push-рассылки', 'Stories', 'Кэшбэк', 'Self-checkout', 'REST API', 'Webhooks', 'Service Workers'];
    document.getElementById('marq').innerHTML = (items.map(i => `<span><b>●</b> ${i}</span>`).join('')).repeat(2);
    /* ===== FORM / PLANS ===== */
    document.getElementById('form').addEventListener('submit', e => {
        e.preventDefault();
        const t = document.getElementById('toast');
        t.classList.add('show');
        e.target.reset();
        setTimeout(() => t.classList.remove('show'), 3500)
    });
    document.querySelectorAll('[data-plan]').forEach(b => b.addEventListener('click', () => {
        document.getElementById('msg').value = 'Здравствуйте! Интересует тариф «' + b.dataset.plan + '». Расскажите подробнее.';
        location.hash = '#contact'
    }));
    /* ===== REVEAL ===== */
    const io = new IntersectionObserver(es => es.forEach(e => e.isIntersecting && e.target.classList.add('vis')), {threshold: .12});
    document.querySelectorAll('.rev').forEach(el => io.observe(el));
</script>
</body>
</html>
