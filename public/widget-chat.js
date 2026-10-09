(function () {
    const container = document.getElementById('mypwa-mobile-widget');
    if (!container) return;

    // 1. Читаем параметры (добавлен redirect-url)
    const baseUrl = container.dataset.baseUrl || 'https://mypwa.ru';
    const widgetPath = container.dataset.path || '#/';
    const redirectUrl = container.dataset.redirectUrl || ''; // Ссылка для мобильных

    const buttonIcon = container.dataset.buttonIcon || '🛒';
    const buttonTextOpen = container.dataset.buttonTextOpen || 'Открыть';
    const buttonTextClose = container.dataset.buttonTextClose || 'Закрыть';
    const buttonColor = container.dataset.buttonColor || '#2563eb';
    const initialMode = container.dataset.initialMode || 'tablet';

    // Проверка на мобильное устройство (ширина экрана или User-Agent)
    const isMobile = window.innerWidth <= 768 || /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

    // Собираем параметры для URL
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(container.dataset)) {
        if (!['baseUrl', 'path', 'redirectUrl', 'buttonIcon', 'buttonTextOpen', 'buttonTextClose', 'buttonColor', 'initialMode'].includes(key)) {
            const urlKey = key.replace(/([A-Z])/g, '-$1').toLowerCase();
            params.append(urlKey, value);
        }
    }

    const safeBaseUrl = baseUrl.replace('https://localhost', 'http://localhost:8000');
    const iframeUrl = `${safeBaseUrl}/${widgetPath}?${params.toString()}`;

    const shadow = container.attachShadow({ mode: 'open' });

    // 2. Стили
    // 2. Стили
    const style = document.createElement('style');
    style.textContent = `
    :host {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 9999;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* Якорная точка */
    .fcw-anchor {
      position: relative;
      width: 64px;
      height: 64px;
    }

    /* === АНИМАЦИИ === */
    @keyframes fcw-pulse-glow {
      0%, 100% {
        box-shadow:
          0 6px 20px rgba(0, 0, 0, 0.25),
          0 0 0 0 ${buttonColor}66;
      }
      50% {
        box-shadow:
          0 6px 20px rgba(0, 0, 0, 0.25),
          0 0 0 15px ${buttonColor}00;
      }
    }

    @keyframes fcw-wiggle {
      0%, 100% { transform: rotate(0deg); }
      15% { transform: rotate(-8deg); }
      30% { transform: rotate(6deg); }
      45% { transform: rotate(-4deg); }
      60% { transform: rotate(2deg); }
      75% { transform: rotate(-1deg); }
    }

    @keyframes fcw-badge-bounce {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.15); }
    }

    @keyframes fcw-appear {
      from {
        opacity: 0;
        transform: translateY(20px) scale(0.8);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    /* Главная кнопка */
    .fcw-button {
      position: absolute;
      bottom: 0;
      right: 0;
      height: 64px;
      min-width: 64px;
      max-width: 260px;
      border-radius: 32px;
      background: ${buttonColor};
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 0 24px;
      font-size: 16px;
      font-weight: 600;
      color: #ffffff;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      white-space: nowrap;
      box-sizing: border-box;
      z-index: 20;

      /* Анимация появления при загрузке */
      animation: fcw-appear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;

      /* Пульсация свечения + покачивание */
      animation:
        fcw-appear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards,
        fcw-pulse-glow 2s ease-in-out infinite,
        fcw-wiggle 3s ease-in-out infinite 1s;
    }

    /* Остановка анимации при наведении */
    .fcw-button:hover {
      transform: translateY(-3px) rotate(0deg) !important;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
      animation-play-state: paused;
    }

    .fcw-button:active {
      transform: translateY(-1px) rotate(0deg) !important;
    }

    /* Скрытие кнопки при открытии */
    .fcw-button.hidden {
      opacity: 0;
      transform: scale(0.5) rotate(0deg) !important;
      pointer-events: none;
      animation: none;
    }

    .fcw-icon {
      font-size: 24px;
      display: flex;
      align-items: center;
      line-height: 1;
      transition: transform 0.3s ease;
    }

    /* Иконка тоже слегка покачивается */
    .fcw-button:hover .fcw-icon {
      transform: scale(1.1) rotate(-10deg);
    }

    /* === БЕЙДЖ С АКЦИЕЙ === */
    .fcw-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      min-width: 28px;
      height: 28px;
      padding: 0 8px;
      border-radius: 14px;
      background: #ef4444;
      color: #ffffff;
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
      animation: fcw-badge-bounce 1.5s ease-in-out infinite;
      z-index: 25;
    }
    .fcw-badge.hidden {
      display: none;
    }

    /* Обертка устройства */
    .fcw-device-wrapper {
      position: absolute;
      bottom: 0;
      right: 0;
      display: flex;
      align-items: flex-end;
      gap: 12px;
      transform-origin: bottom right;
      transform: scale(0.15);
      opacity: 0;
      pointer-events: none;
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
      will-change: transform, opacity;
      z-index: 10;
    }

    .fcw-device-wrapper.open {
      transform: scale(1);
      opacity: 1;
      pointer-events: all;
    }

    /* Боковая панель */
    .fcw-sidebar {
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding-bottom: 10px;
    }
    .fcw-sidebar-btn {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: ${buttonColor};
      border: 2px solid transparent;
      color: #ffffff;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      transition: all 0.2s ease;
    }
    .fcw-sidebar-btn:hover { transform: scale(1.1); }
    .fcw-sidebar-btn.mode-btn.active {
      background: #ffffff;
      color: ${buttonColor};
      border-color: ${buttonColor};
    }

    /* Устройство */
    .fcw-device {
      background: #1a1a1a;
      border-radius: 40px;
      padding: 12px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    .fcw-device.phone { width: 375px; height: 650px; }
    .fcw-device.tablet { width: 768px; height: 550px; border-radius: 24px; }

    .fcw-screen {
      width: 100%;
      height: 100%;
      background: #ffffff;
      border-radius: 30px;
      overflow: hidden;
      position: relative;
    }
    .fcw-device.tablet .fcw-screen { border-radius: 16px; }

    .fcw-notch {
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 120px;
      height: 24px;
      background: #1a1a1a;
      border-radius: 0 0 16px 16px;
      z-index: 10;
    }
    .fcw-device.tablet .fcw-notch { display: none; }

    .fcw-iframe {
      width: 100%;
      height: 100%;
      border: none;
      background: #ffffff;
    }

    .fcw-buttons-decor {
      position: absolute;
      left: -3px;
      top: 100px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .fcw-device.tablet .fcw-buttons-decor { display: none; }
    .fcw-btn-decor { width: 3px; height: 30px; background: #2a2a2a; border-radius: 2px; }
  `;
    shadow.appendChild(style);

    // 3. Создаем DOM-элементы
    const anchor = document.createElement('div');
    anchor.className = 'fcw-anchor';

    const button = document.createElement('button');
    button.className = 'fcw-button';
    button.innerHTML = `<span class="fcw-icon">${buttonIcon}</span><span class="fcw-text">${buttonTextOpen}</span>`;

    // Бейдж с акцией (опционально)
    const badgeText = container.dataset.badgeText || '';
    if (badgeText) {
        const badge = document.createElement('div');
        badge.className = 'fcw-badge';
        badge.textContent = badgeText;
        button.appendChild(badge);
    }

    // Обертка устройства и сайдбара
    const deviceWrapper = document.createElement('div');
    deviceWrapper.className = 'fcw-device-wrapper';

    const sidebar = document.createElement('div');
    sidebar.className = 'fcw-sidebar';
    sidebar.innerHTML = `
    <button class="fcw-sidebar-btn close-btn" title="Закрыть">✕</button>
    <button class="fcw-sidebar-btn mode-btn ${initialMode === 'phone' ? 'active' : ''}" data-mode="phone" title="Телефон">📱</button>
    <button class="fcw-sidebar-btn mode-btn ${initialMode === 'tablet' ? 'active' : ''}" data-mode="tablet" title="Планшет">💻</button>
  `;

    const device = document.createElement('div');
    device.className = `fcw-device ${initialMode}`;

    const screen = document.createElement('div');
    screen.className = 'fcw-screen';

    const notch = document.createElement('div');
    notch.className = 'fcw-notch';
    screen.appendChild(notch);

    const iframe = document.createElement('iframe');
    iframe.className = 'fcw-iframe';
    iframe.src = iframeUrl;
    iframe.allow = 'camera; microphone; geolocation; clipboard-write';
    iframe.loading = 'lazy';
    screen.appendChild(iframe);

    const decorButtons = document.createElement('div');
    decorButtons.className = 'fcw-buttons-decor';
    decorButtons.innerHTML = `<div class="fcw-btn-decor"></div><div class="fcw-btn-decor"></div>`;

    device.appendChild(screen);
    device.appendChild(decorButtons);
    deviceWrapper.appendChild(sidebar);
    deviceWrapper.appendChild(device);
    anchor.appendChild(deviceWrapper);
    anchor.appendChild(button);
    shadow.appendChild(anchor);

    // 4. Логика
    let isOpen = false;

    const toggleWidget = () => {
        // Если мобильный и есть ссылка для редиректа — уходим на сайт
        if (isMobile && redirectUrl) {
            window.location.href = redirectUrl;
            return;
        }

        isOpen = !isOpen;

        if (isOpen) {
            button.classList.add('hidden');
            deviceWrapper.classList.add('open');
        } else {
            button.classList.remove('hidden');
            deviceWrapper.classList.remove('open');
        }
    };

    button.addEventListener('click', toggleWidget);

    sidebar.querySelector('.close-btn').addEventListener('click', () => {
        if (isOpen) toggleWidget();
    });

    sidebar.querySelectorAll('.mode-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            sidebar.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            device.classList.remove('phone', 'tablet');
            device.classList.add(mode);

            iframe.contentWindow.postMessage({ type: 'MODE_CHANGE', mode: mode }, safeBaseUrl);
        });
    });

})();
