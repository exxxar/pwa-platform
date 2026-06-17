/**
 * Утилиты для работы с колесом фортуны
 */

/**
 * Алгоритм Фишера-Йетса — классическое равномерное перемешивание массива
 * НЕ мутирует исходный массив, возвращает новый
 *
 * @param {Array} array - массив для перемешивания
 * @returns {Array} - новый перемешанный массив
 */
export function shuffleArray(array) {
    const shuffled = [...array]; // Копия, чтобы не мутировать оригинал

    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }

    return shuffled;
}

/**
 * Перемешивает секторы колеса, сохраняя привязку веса к конкретному призу
 * (а не к позиции на колесе). Это важно для честности игры!
 *
 * @param {Array} sectors - массив секторов с полем weight
 * @returns {Array} - новый перемешанный массив секторов
 */
export function shuffleSectors(sectors) {
    return shuffleArray(sectors);
}

/**
 * Генерирует случайный набор секторов колеса на основе шаблона
 * Шаблон определяет возможные призы и их вероятности,
 * а функция создаёт колесо с перемешанным порядком
 *
 * @param {Array} template - шаблон секторов (с полем weight)
 * @returns {Array} - готовый массив секторов для колеса
 */
export function generateRandomSectors(template) {
    return shuffleSectors(template);
}

/**
 * Выбирает выигрышный сектор с учётом весов (вероятностей)
 *
 * @param {Array} sectors - массив секторов с полем weight
 * @returns {number} - индекс выигрышного сектора
 */
export function getRandomSectorByWeight(sectors) {
    const totalWeight = sectors.reduce((sum, s) => sum + s.weight, 0);
    const rand = Math.random() * totalWeight;

    let cumulative = 0;
    for (let i = 0; i < sectors.length; i++) {
        cumulative += sectors[i].weight;
        if (rand <= cumulative) return i;
    }

    return 0;
}

/**
 * Генерирует уникальный промокод на основе приза
 *
 * @param {Object} sector - сектор с полем code
 * @returns {string} - уникальный промокод
 */
export function generatePromoCode(sector) {
    const timestamp = Date.now().toString(36).toUpperCase().slice(-4);
    const random = Math.random().toString(36).toUpperCase().slice(2, 6);
    return `${sector.code}-${timestamp}${random}`;
}
