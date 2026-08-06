/**
 * Глобальная конфигурация цветовых схем приложения
 * Используется в ThemeSchemePicker, AppLayout и настройках администратора
 */
export const themeSchemes = [
    {
        id: 'default',
        name: 'Стандарт',
        light: {
            primary: '#ff8a00',
            'primary-rgb': '255, 138, 0',
            'body-bg': '#fffdf8',
            'body-color': '#2c2c2c',
            'secondary-bg': '#f8f9fa',
            'border-color': '#dee2e6',
        },
        dark: {
            primary: '#ff9500',
            'primary-rgb': '255, 149, 0',
            'body-bg': '#1a1a1a',
            'body-color': '#e9ecef',
            'secondary-bg': '#2d2d2d',
            'border-color': '#495057',
        }
    },
    {
        id: 'ocean',
        name: 'Океан',
        light: {
            primary: '#0d6efd',
            'primary-rgb': '13, 110, 253',
            'body-bg': '#f0f7ff',
            'body-color': '#1a2b4a',
            'secondary-bg': '#e7f1ff',
            'border-color': '#b6d4fe',
        },
        dark: {
            primary: '#3d8bfd',
            'primary-rgb': '61, 139, 253',
            'body-bg': '#0a1929',
            'body-color': '#e0e7ef',
            'secondary-bg': '#132f4c',
            'border-color': '#1e4976',
        }
    },
    {
        id: 'forest',
        name: 'Лес',
        light: {
            primary: '#198754',
            'primary-rgb': '25, 135, 84',
            'body-bg': '#f0f9f4',
            'body-color': '#1a3a2a',
            'secondary-bg': '#d1e7dd',
            'border-color': '#a3cfbb',
        },
        dark: {
            primary: '#20c997',
            'primary-rgb': '32, 201, 151',
            'body-bg': '#0f1f17',
            'body-color': '#d4e8dc',
            'secondary-bg': '#1a3a2a',
            'border-color': '#2d5a3f',
        }
    },
    {
        id: 'sunset',
        name: 'Закат',
        light: {
            primary: '#dc3545',
            'primary-rgb': '220, 53, 69',
            'body-bg': '#fff5f5',
            'body-color': '#4a1a1a',
            'secondary-bg': '#f8d7da',
            'border-color': '#f1aeb5',
        },
        dark: {
            primary: '#e35d6a',
            'primary-rgb': '227, 93, 106',
            'body-bg': '#1f0f0f',
            'body-color': '#f0d4d7',
            'secondary-bg': '#3a1a1a',
            'border-color': '#5a2d2d',
        }
    },
    {
        id: 'royal',
        name: 'Королевский',
        light: {
            primary: '#6f42c1',
            'primary-rgb': '111, 66, 193',
            'body-bg': '#faf5ff',
            'body-color': '#2d1a4a',
            'secondary-bg': '#e2d9f3',
            'border-color': '#c5b3e6',
        },
        dark: {
            primary: '#8b5cf6',
            'primary-rgb': '139, 92, 246',
            'body-bg': '#1a0f2e',
            'body-color': '#e0d4f0',
            'secondary-bg': '#2d1a4a',
            'border-color': '#4a2d6e',
        }
    },
    {
        id: 'mono',
        name: 'Монохром',
        light: {
            primary: '#212529',
            'primary-rgb': '33, 37, 41',
            'body-bg': '#ffffff',
            'body-color': '#212529',
            'secondary-bg': '#f8f9fa',
            'border-color': '#dee2e6',
        },
        dark: {
            primary: '#f8f9fa',
            'primary-rgb': '248, 249, 250',
            'body-bg': '#000000',
            'body-color': '#f8f9fa',
            'secondary-bg': '#1a1a1a',
            'border-color': '#333333',
        }
    },
    // 🆕 НОВЫЕ ТЕМЫ
    {
        id: 'coffee',
        name: 'Кофе',
        light: {
            primary: '#6f4e37',
            'primary-rgb': '111, 78, 55',
            'body-bg': '#fdfbf7',
            'body-color': '#3e2723',
            'secondary-bg': '#efebe9',
            'border-color': '#d7ccc8',
        },
        dark: {
            primary: '#8d6e63',
            'primary-rgb': '141, 110, 99',
            'body-bg': '#1e1613',
            'body-color': '#d7ccc8',
            'secondary-bg': '#2c211d',
            'border-color': '#4e342e',
        }
    },
    {
        id: 'midnight',
        name: 'Полночь',
        light: {
            primary: '#334155',
            'primary-rgb': '51, 65, 85',
            'body-bg': '#f8fafc',
            'body-color': '#0f172a',
            'secondary-bg': '#e2e8f0',
            'border-color': '#cbd5e1',
        },
        dark: {
            primary: '#94a3b8',
            'primary-rgb': '148, 163, 184',
            'body-bg': '#020617',
            'body-color': '#f1f5f9',
            'secondary-bg': '#0f172a',
            'border-color': '#1e293b',
        }
    },
    {
        id: 'cyber',
        name: 'Киберпанк',
        light: {
            primary: '#00bcd4',
            'primary-rgb': '0, 188, 212',
            'body-bg': '#f0f8fa',
            'body-color': '#1a2b3c',
            'secondary-bg': '#e0f2f4',
            'border-color': '#b2ebf2',
        },
        dark: {
            primary: '#00f0ff',
            'primary-rgb': '0, 240, 255',
            'body-bg': '#0a0a12',
            'body-color': '#e0e0ff',
            'secondary-bg': '#151525',
            'border-color': '#2a2a4a',
        }
    },
    {
        id: 'gold',
        name: 'Золото',
        light: {
            primary: '#d97706',
            'primary-rgb': '217, 119, 6',
            'body-bg': '#fffbeb',
            'body-color': '#451a03',
            'secondary-bg': '#fef3c7',
            'border-color': '#fcd34d',
        },
        dark: {
            primary: '#fbbf24',
            'primary-rgb': '251, 191, 36',
            'body-bg': '#1c1917',
            'body-color': '#fef3c7',
            'secondary-bg': '#292524',
            'border-color': '#78716c',
        }
    },
    {
        id: 'ember',
        name: 'Уголёк',
        light: {
            primary: '#ff7a1a',
            'primary-rgb': '255, 122, 26',
            'body-bg': '#fff8f0',        // тёплый кремовый, не чисто белый
            'body-color': '#1a1410',     // глубокий тёплый чёрный
            'secondary-bg': '#fff1e0',   // лёгкий персиковый оттенок
            'border-color': '#f5d5b0',   // мягкая тёплая граница
        },
        dark: {
            primary: '#ff7a1a',
            'primary-rgb': '255, 122, 26',
            'body-bg': '#0a0a0e',        // тот самый «космически-чёрный» фон
            'body-color': '#f5f5f7',     // чистый почти-белый текст
            'secondary-bg': '#14141c',   // карточки с лёгким синеватым подтоном
            'border-color': '#2a2a35',   // мягкие границы, не режут глаз
        }
    }
];

/**
 * Хелпер для получения схемы по ID (с фоллбэком на 'default')
 */
export const getThemeScheme = (id) => {
    return themeSchemes.find(s => s.id === id) || themeSchemes[0];
};
