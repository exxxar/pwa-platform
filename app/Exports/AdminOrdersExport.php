<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents; // 🔥 1. Добавляем интерфейс событий
use Maatwebsite\Excel\Events\AfterSheet;   // 🔥 2. Добавляем класс события
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminOrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents // 🔥 3. Реализуем WithEvents
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function collection()
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return [
            'ID Заказа',
            'Заведение',
            'Дата и время',
            'Клиент',
            'Телефон',
            'Состав заказа',
            'Сумма (₽)',
            'Статус',
        ];
    }

    public function map($order): array
    {
        $productsString = 'Не указано';

        if (!empty($order->product_details)) {
            $details = is_array($order->product_details)
                ? ($order->product_details[0] ?? $order->product_details)
                : $order->product_details;

            if (isset($details['products']) && is_array($details['products'])) {
                $items = [];
                foreach ($details['products'] as $product) {
                    $count = $product['count'] ?? 1;
                    $name = $product['name'] ?? 'Товар';
                    $price = $product['price'] ?? 0;
                    $items[] = "{$count}x {$name} (" . number_format($price, 0, '.', ' ') . " ₽)";
                }
                $productsString = implode("; ", $items);
            }
        }

        return [
            $order->id,
                $order->tenant->name ?? 'Не указано',
            $order->created_at ? $order->created_at->format('d.m.Y H:i') : '—',
                $order->tenantUser->name ?? 'Гость',
                $order->tenantUser->phone ?? 'Нет телефона',
            $productsString,
            number_format($order->summary_price, 2, '.', ' '),
            $this->getStatusName($order->status),
        ];
    }

    private function getStatusName($status)
    {
        $map = [
            0 => 'Новый', 1 => 'В обработке', 2 => 'Выполнен',
            3 => 'Отменен', 4 => 'Готов к доставке', 5 => 'Передан на кухню',
            'new' => 'Новый', 'processing' => 'В работе',
            'completed' => 'Выполнен', 'cancelled' => 'Отменен'
        ];
        return $map[$status] ?? $status;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Стиль шапки
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => '3B82F6']]
            ],
            // Перенос текста для колонки "Состав заказа" (F)
            'F' => [
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => 'top'
                ]
            ]
        ];
    }

    // 🔥 4. Добавляем метод настройки ширины колонок после генерации листа
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Проходим по всем колонкам от A до H (у нас 8 колонок)
                foreach (range('A', 'H') as $column) {
                    // Включаем автоподбор ширины под контент
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // 🔥 Опционально: задаем минимальную гарантированную ширину для важных колонок,
                // чтобы авто-размер не сделал их слишком узкими (например, если там только "ID")
                $event->sheet->getColumnDimension('A')->setWidth(12);  // ID Заказа
                $event->sheet->getColumnDimension('C')->setWidth(18);  // Дата и время
                $event->sheet->getColumnDimension('G')->setWidth(15);  // Сумма
                $event->sheet->getColumnDimension('H')->setWidth(18);  // Статус
            },
        ];
    }
}
