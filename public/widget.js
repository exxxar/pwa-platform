(function () {
    const container = document.getElementById('fastoran-widget');
    if (!container) return;

    // Читаем все параметры
    const baseUrl = container.dataset.baseUrl || 'http://localhost:8000';
    const widgetPath = container.dataset.path || 'widget-view';
    const height = container.dataset.height || '500px';

    // Новые параметры для стилизации
    const bgColor = container.dataset.bgColor || '#ffffff';
    const borderRadius = container.dataset.borderRadius || '8px';
    const padding = container.dataset.padding || '0px';

    const shadow = container.attachShadow({ mode: 'open' });

    const style = document.createElement('style');
    style.textContent = `
    :host {
      display: block;
      width: 100%;
      line-height: 0;
    }
    .widget-wrapper {
      background: ${bgColor};
      border-radius: ${borderRadius};
      padding: ${padding};
    }
    .widget-iframe {
      width: 100%;
      height: ${height};
      border: none;
      border-radius: ${borderRadius};
      background: transparent;
    }
  `;
    shadow.appendChild(style);

    // Оборачиваем iframe в контейнер с фоном
    const wrapper = document.createElement('div');
    wrapper.className = 'widget-wrapper';

    const iframe = document.createElement('iframe');
    iframe.className = 'widget-iframe';
    iframe.src = `${baseUrl}/${widgetPath}`;
    iframe.allow = 'camera; microphone; geolocation; clipboard-write';
    iframe.loading = 'lazy';
    iframe.title = 'Fastoran Widget';

    wrapper.appendChild(iframe);
    shadow.appendChild(wrapper);

    // Auto-resize (как в прошлом сообщении)
    window.addEventListener('message', (event) => {
        if (event.origin !== 'http://localhost:8000') return;
        if (event.data && event.data.type === 'RESIZE') {
            iframe.style.height = event.data.height;
        }
    });
})();
