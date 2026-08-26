<?php

namespace App\Services\Admin\Global;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\Order;
use App\Models\Tenant\Transaction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportService
{
    /**
     * Экспорт пользователей в CSV
     */
    public function exportUsersToCsv(array $filters = []): string
    {
        $query = TenantUser::query()->with(['tenant']);

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        $users = $query->get();

        $filename = 'users_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = 'exports/' . $filename;

        $handle = fopen('php://temp', 'r+');

        // Заголовки
        fputcsv($handle, [
            'ID',
            'Tenant',
            'Name',
            'Phone',
            'Email',
            'City',
            'Is Active',
            'Is VIP',
            'VIP Expires At',
            'Referral Code',
            'Referrals Count',
            'Cashback Balance',
            'Created At',
        ]);

        // Данные
        foreach ($users as $user) {
            fputcsv($handle, [
                $user->id,
                    $user->tenant?->name ?? '',
                $user->name,
                $user->phone,
                $user->email,
                $user->city,
                $user->is_active ? 'Yes' : 'No',
                $user->is_vip ? 'Yes' : 'No',
                    $user->vip_expires_at?->format('Y-m-d H:i:s') ?? '',
                $user->referral_code,
                $user->referrals_count,
                $user->cashback_balance,
                $user->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put($path, $content);

        return Storage::disk('public')->url($path);
    }

    /**
     * Экспорт заказов в CSV
     */
    public function exportOrdersToCsv(array $filters = []): string
    {
        $query = Order::query()->with(['tenant', 'tenantUser']);

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $orders = $query->get();

        $filename = 'orders_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = 'exports/' . $filename;

        $handle = fopen('php://temp', 'r+');

        // Заголовки
        fputcsv($handle, [
            'ID',
            'Tenant',
            'User Name',
            'User Phone',
            'Products Count',
            'Summary Price',
            'Delivery Price',
            'Status',
            'Order Type',
            'Receiver Name',
            'Receiver Phone',
            'Is Paid',
            'Paid At',
            'Created At',
        ]);

        // Данные
        foreach ($orders as $order) {
            fputcsv($handle, [
                $order->id,
                    $order->tenant?->name ?? '',
                    $order->tenantUser?->name ?? '',
                    $order->tenantUser?->phone ?? '',
                $order->product_count,
                $order->summary_price,
                $order->delivery_price,
                $order->status,
                $order->order_type,
                $order->receiver_name,
                $order->receiver_phone,
                $order->payed_at ? 'Yes' : 'No',
                    $order->payed_at?->format('Y-m-d H:i:s') ?? '',
                $order->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put($path, $content);

        return Storage::disk('public')->url($path);
    }

    /**
     * Экспорт транзакций в CSV
     */
    public function exportTransactionsToCsv(array $filters = []): string
    {
        $query = Transaction::query()->with(['tenant', 'user']);

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->where('paid_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('paid_at', '<=', $filters['to']);
        }

        $transactions = $query->get();

        $filename = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = 'exports/' . $filename;

        $handle = fopen('php://temp', 'r+');

        // Заголовки
        fputcsv($handle, [
            'ID',
            'Tenant',
            'User Name',
            'User Phone',
            'Order ID',
            'Provider',
            'External Payment ID',
            'Amount',
            'Currency',
            'Status',
            'Paid At',
            'Created At',
        ]);

        // Данные
        foreach ($transactions as $transaction) {
            fputcsv($handle, [
                $transaction->id,
                    $transaction->tenant?->name ?? '',
                    $transaction->user?->name ?? '',
                    $transaction->user?->phone ?? '',
                $transaction->order_id,
                $transaction->provider,
                $transaction->external_payment_id,
                $transaction->amount,
                $transaction->currency,
                $transaction->status,
                    $transaction->paid_at?->format('Y-m-d H:i:s') ?? '',
                $transaction->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put($path, $content);

        return Storage::disk('public')->url($path);
    }

    /**
     * Экспорт тенантов в CSV
     */
    public function exportTenantsToCsv(array $filters = []): string
    {
        $query = Tenant::query();

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $tenants = $query->get();

        $filename = 'tenants_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $path = 'exports/' . $filename;

        $handle = fopen('php://temp', 'r+');

        // Заголовки
        fputcsv($handle, [
            'ID',
            'UUID',
            'Name',
            'Slug',
            'Short Name',
            'Plan Slug',
            'Balance',
            'Tax Per Day',
            'Is Active',
            'Created At',
        ]);

        // Данные
        foreach ($tenants as $tenant) {
            fputcsv($handle, [
                $tenant->id,
                $tenant->uuid,
                $tenant->name,
                $tenant->slug,
                $tenant->short_name,
                $tenant->plan_slug,
                $tenant->balance,
                $tenant->tax_per_day,
                $tenant->is_active ? 'Yes' : 'No',
                $tenant->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put($path, $content);

        return Storage::disk('public')->url($path);
    }
}
