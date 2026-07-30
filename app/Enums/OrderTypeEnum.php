<?php

namespace App\Enums;

enum OrderTypeEnum: int
{
    case InternalStore = 0;      // Внутренний магазин (стандартный)
    case Constructor = 1;        // Конструктор (сборка заказа)
    case ExternalStore = 2;      // Внешний магазин (маркетплейс/интеграция)
    case Pickup = 3;             // 🆕 Самовывоз
    case Table = 4;              // 🆕 Заказ со стола (QR-меню)
    case Subscription = 5;       // 🆕 Подписка / Регулярный заказ

    /**
     * Получение человеко-читаемого названия типа заказа
     */
    public function label(): string
    {
        return match($this) {
            self::InternalStore => 'Внутренний магазин',
            self::Constructor => 'Конструктор',
            self::ExternalStore => 'Внешний магазин',
            self::Pickup => 'Самовывоз',
            self::Table => 'Заказ со стола',
            self::Subscription => 'Подписка',
        };
    }
}
