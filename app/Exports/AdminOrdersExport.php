<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdminOrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
            'Заведение',          // 🔥 НОВАЯ КОЛОНКА
            'Дата и время',
            'Клиент',
            'Телефон',
            'Сумма (₽)',
            'Статус',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
                $order->tenant->name ?? 'Не указано', // 🔥 Берем название заведения
            $order->created_at ? $order->created_at->format('d.m.Y H:i') : '—',
                $order->tenantUser->name ?? 'Гость',
                $order->tenantUser->phone ?? 'Нет телефона',
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
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => '3B82F6']]],
        ];
    }
}
