<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminOrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
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

                    // Формируем строку для каждой позиции
                    $items[] = "{$count}x {$name} (" . number_format($price, 0, '.', ' ') . " ₽)";
                }

                // 🔥 ИЗМЕНЕНИЕ ЗДЕСЬ: используем перенос строки (\n) вместо "; "
                $productsString = implode("\n", $items);
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
            // Перенос текста для колонки "Состав заказа" (F) - это критически важно для \n
            'F' => [
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => 'top' // Выравнивание по верхнему краю, чтобы список начинался сверху
                ]
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Автоподбор ширины колонок
                foreach (range('A', 'H') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Минимальная гарантированная ширина для важных колонок
                $event->sheet->getColumnDimension('A')->setWidth(12);  // ID Заказа
                $event->sheet->getColumnDimension('C')->setWidth(18);  // Дата и время
                $event->sheet->getColumnDimension('G')->setWidth(15);  // Сумма
                $event->sheet->getColumnDimension('H')->setWidth(18);  // Статус

                // 🔥 Дополнительно: можно немного увеличить ширину колонки F (Состав заказа),
                // чтобы длинные названия товаров не переносились слишком часто
                $event->sheet->getColumnDimension('F')->setWidth(45);
            },
        ];
    }
}
