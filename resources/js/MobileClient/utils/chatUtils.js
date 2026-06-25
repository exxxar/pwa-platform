/**
 * Утилиты для чата (общие функции форматирования)
 */

/**
 * Форматирование времени сообщения (ЧЧ:ММ)
 */
export function formatMessageTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Форматирование времени для списка диалогов
 * Сегодня → время, Вчера → "Вчера", неделя → день недели, раньше → дата
 */
export function formatDialogTime(timestamp) {
    if (!timestamp) return '';

    const date = new Date(timestamp);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const weekAgo = new Date(today);
    weekAgo.setDate(weekAgo.getDate() - 7);

    const messageDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());

    if (messageDay.getTime() === today.getTime()) {
        return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    }
    if (messageDay.getTime() === yesterday.getTime()) {
        return 'Вчера';
    }
    if (messageDay > weekAgo) {
        return date.toLocaleDateString('ru-RU', { weekday: 'short' });
    }
    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
}

/**
 * Форматирование разделителя даты в сообщениях
 */
export function formatDateSeparator(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const today = new Date();

    if (date.toDateString() === today.toDateString()) return 'Сегодня';

    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) return 'Вчера';

    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
}

/**
 * Форматирование "был(а) в сети"
 */
export function formatLastSeen(timestamp) {
    if (!timestamp) return 'был(а) недавно';
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) return 'только что';
    if (minutes < 60) return `был(а) ${minutes} мин. назад`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `был(а) ${hours} ч. назад`;

    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
}

/**
 * Инициалы для аватара
 */
export function getInitials(name) {
    if (!name) return '?';
    const words = name.trim().split(/\s+/);
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
}

/**
 * Градиент для аватара (детерминированный по ID)
 */
export function getAvatarGradient(id) {
    const gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
    ];
    const index = (id || 0) % gradients.length;
    return { background: gradients[index] };
}

/**
 * Получить собеседника из диалога
 */
export function getInterlocutor(dialog) {
    if (!dialog) return null;
    return dialog.interlocutor || dialog.user || dialog.companion || null;
}

/**
 * Проверка, моё ли сообщение
 */
export function isMyMessage(message, userId) {
    if (!message || !userId) return false;
    return (
        message.is_mine === true ||
        message.sender_id === userId ||
        message.meta?.user_id === userId
    );
}

/**
 * Текст последнего сообщения для списка диалогов
 */
export function getLastMessagePreview(message) {
    if (!message) return 'Нет сообщений';
    if (message.type === 'image') return '📷 Фото';
    if (message.type === 'file') return '📎 Файл';
    if (message.type === 'voice') return '🎤 Голосовое сообщение';
    if (message.type === 'video') return '🎥 Видео';
    return message.text || message.message || 'Вложение';
}

/**
 * Склонение слов
 */
export function pluralize(count, one, two, five) {
    const n = Math.abs(count) % 100;
    const n1 = n % 10;
    if (n > 10 && n < 20) return five;
    if (n1 > 1 && n1 < 5) return two;
    if (n1 === 1) return one;
    return five;
}
