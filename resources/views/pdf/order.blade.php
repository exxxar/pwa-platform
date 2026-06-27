<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ #{{ $orderId }}</title>
    <style>
        /* ==========================================
           БАЗОВЫЕ СТИЛИ
           ========================================== */
        @page {
            margin: 15mm 15mm 20mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11px;
            color: #2d3748;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* ==========================================
           ШАПКА ДОКУМЕНТА
           ========================================== */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin: -15mm -15mm 0 -15mm;
            padding: 25px 20px;
        }

        .header-top {
            width: 100%;
            border-collapse: collapse;
        }

        .header-top td {
            vertical-align: middle;
            padding: 0;
        }

        .logo-cell {
            width: 70px;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 15px;
            text-align: center;
            line-height: 60px;
            font-size: 28px;
        }

        .title-cell {
            padding-left: 15px;
        }

        .service-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: white;
        }

        .service-subtitle {
            font-size: 11px;
            opacity: 0.9;
            margin: 0;
        }

        .order-badge {
            text-align: right;
            vertical-align: top;
        }

        .order-number {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: bold;
            color: black;
        }

        .order-number-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
            display: block;
            margin-bottom: 2px;
            color: black;
        }

        /* ==========================================
           БЛОКИ КОНТЕНТА
           ========================================== */
        .content {
            padding: 20px 0;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 10px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #667eea;
        }

        /* ==========================================
           ИНФОРМАЦИЯ О КЛИЕНТЕ
           ========================================== */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid td {
            padding: 6px 0;
            vertical-align: top;
            width: 50%;
        }

        .info-item {
            padding-right: 15px;
        }

        .info-label {
            font-size: 9px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #2d3748;
            word-wrap: break-word;
        }

        .info-value.accent {
            color: #667eea;
        }

        /* ==========================================
           ТАБЛИЦА ТОВАРОВ
           ========================================== */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }

        .products-table thead tr {
            background: #667eea;
            color: white;
        }

        .products-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .products-table th.num {
            width: 35px;
            text-align: center;
        }

        .products-table th.count {
            width: 70px;
            text-align: center;
        }

        .products-table th.price {
            width: 100px;
            text-align: right;
        }

        .products-table th.total {
            width: 110px;
            text-align: right;
        }

        .products-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .products-table tbody tr:nth-child(even) {
            background: #f7fafc;
        }

        .products-table tbody tr:hover {
            background: #edf2f7;
        }

        .products-table td.num {
            text-align: center;
            color: #718096;
            font-weight: bold;
        }

        .products-table td.name {
            font-weight: 600;
            color: #2d3748;
        }

        .products-table td.count {
            text-align: center;
            color: #4a5568;
        }

        .products-table td.price {
            text-align: right;
            color: #4a5568;
        }

        .products-table td.total {
            text-align: right;
            font-weight: bold;
            color: #2d3748;
        }

        /* ==========================================
           БЛОК ИТОГА
           ========================================== */
        .totals-block {
            background: #f7fafc;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 0;
            vertical-align: middle;
        }

        .totals-table .label {
            font-size: 11px;
            color: #4a5568;
        }

        .totals-table .value {
            text-align: right;
            font-size: 12px;
            font-weight: bold;
            color: #2d3748;
        }

        .totals-table .discount .value {
            color: #e53e3e;
        }

        .totals-table .delivery .value {
            color: #38a169;
        }

        .totals-table .grand-total {
            border-top: 2px solid #667eea;
            padding-top: 10px;
            margin-top: 6px;
        }

        .totals-table .grand-total .label {
            font-size: 14px;
            font-weight: bold;
            color: #2d3748;
        }

        .totals-table .grand-total .value {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }

        /* ==========================================
           БЛОК ОПЛАТЫ
           ========================================== */
        .payment-block {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-left: 4px solid #e53e3e;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .payment-title {
            font-size: 13px;
            font-weight: bold;
            color: #c53030;
            margin: 0 0 8px 0;
        }

        .payment-title i {
            color: #e53e3e;
        }

        .payment-content {
            font-size: 11px;
            color: #2d3748;
            line-height: 1.6;
        }

        /* ==========================================
           БЛОК ДОП. ИНФОРМАЦИИ
           ========================================== */
        .additional-info {
            background: #fffaf0;
            border-left: 4px solid #ed8936;
            border-radius: 8px;
            padding: 12px 15px;
            margin: 15px 0;
            font-size: 10px;
            color: #744210;
        }

        .additional-info strong {
            color: #c05621;
        }

        /* ==========================================
           ФУТЕР
           ========================================== */
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px dashed #cbd5e0;
            text-align: center;
        }

        .footer-thanks {
            font-size: 12px;
            color: #4a5568;
            margin: 0 0 8px 0;
            font-style: italic;
        }

        .footer-brand {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin: 0;
        }

        .footer-meta {
            font-size: 9px;
            color: #a0aec0;
            margin-top: 10px;
        }

        .uniq-code {
            display: inline-block;
            background: #edf2f7;
            padding: 3px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 9px;
            color: #718096;
            margin-top: 6px;
        }

        /* ==========================================
           УТИЛИТЫ
           ========================================== */
        .text-muted {
            color: #718096;
        }

        .text-accent {
            color: #667eea;
        }

        .text-danger {
            color: #e53e3e;
        }

        .text-success {
            color: #38a169;
        }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 15px 0;
        }

        .divider-dashed {
            border: none;
            border-top: 2px dashed #cbd5e0;
            margin: 15px 0;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-primary {
            background: #667eea;
            color: white;
        }

        .badge-success {
            background: #38a169;
            color: white;
        }

        .badge-warning {
            background: #ed8936;
            color: white;
        }
    </style>
</head>
<body>

<!-- ==========================================
     ШАПКА ДОКУМЕНТА
     ========================================== -->
<table class="header" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <table class="header-top">
                <tr>
                    <td class="logo-cell">
                        <div class="logo"></div>
                    </td>
                    <td class="title-cell">
                        <h1 class="service-title">{{ $title ?? 'PWA Store' }}</h1>
                        <p class="service-subtitle">Счёт на оплату заказа</p>
                    </td>
                    <td class="order-badge">
                        <div class="order-number">
                            <span class="order-number-label">Заказ №</span>
                            #{{ $orderId ?? '-' }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- ==========================================
     ОСНОВНОЙ КОНТЕНТ
     ========================================== -->
<div class="content">

    <!-- Дата и уникальный номер -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="text-align: left; font-size: 10px; color: #718096;">
                Дата: <strong>{{ $currentDate ?? '-' }}</strong>
            </td>
            <td style="text-align: right; font-size: 10px; color: #718096;">
                ID: <span class="uniq-code">{{ $uniqNumber ?? '-' }}</span>
            </td>
        </tr>
    </table>

    <hr class="divider">

    <!-- ==========================================
         ИНФОРМАЦИЯ О КЛИЕНТЕ
         ========================================== -->
    <div class="section">
        <h2 class="section-title">Информация о заказчике</h2>

        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-item">
                        <div class="info-label">Имя</div>
                        <div class="info-value">{{ $name ?? 'Не указано' }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <div class="info-value accent">{{ $phone ?? 'Не указан' }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="info-item">
                        <div class="info-label">Адрес доставки</div>
                        <div class="info-value">{{ $address ?? 'Самовывоз' }}</div>
                    </div>
                </td>
            </tr>
            @if(!empty($entranceNumber) || !empty($floorNumber))
                <tr>
                    @if(!empty($entranceNumber))
                        <td>
                            <div class="info-item">
                                <div class="info-label">Подъезд</div>
                                <div class="info-value">№ {{ $entranceNumber }}</div>
                            </div>
                        </td>
                    @else
                        <td></td>
                    @endif
                    @if(!empty($floorNumber))
                        <td>
                            <div class="info-item">
                                <div class="info-label">Этаж</div>
                                <div class="info-value">№ {{ $floorNumber }}</div>
                            </div>
                        </td>
                    @else
                        <td></td>
                    @endif
                </tr>
            @endif
            <tr>
                <td>
                    <div class="info-item">
                        <div class="info-label">Способ оплаты</div>
                        <div class="info-value">
                            <span class="badge badge-primary">{{ $cashType ?? 'Не указан' }}</span>
                        </div>
                    </div>
                </td>
                <td>
                    @if(!empty($money) && $money !== 'Не указано')
                        <div class="info-item">
                            <div class="info-label">Сдача с</div>
                            <div class="info-value">{{ $money }} ₽</div>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Дополнительная информация -->
    @if(!empty($message) && $message !== 'Не указано')
        <div class="additional-info">
            <strong>Комментарий:</strong> {!! $message !!}
        </div>
    @endif

    @if(!empty($disabilitiesText) && $disabilitiesText !== 'не указаны')
        <div class="additional-info" style="background: #fff5f5; border-left-color: #e53e3e; color: #742a2a;">
             <strong>Важно:</strong> {!! $disabilitiesText !!}
        </div>
    @endif

    <hr class="divider">

    <!-- ==========================================
         СОСТАВ ЗАКАЗА
         ========================================== -->
    @if(!empty($products))
        <div class="section">
            <h2 class="section-title">Состав заказа</h2>

            <table class="products-table">
                <thead>
                <tr>
                    <th class="num">№</th>
                    <th>Наименование</th>
                    <th class="count">Кол-во</th>
                    <th class="price">Цена</th>
                    <th class="total">Сумма</th>
                </tr>
                </thead>
                <tbody>
                @foreach($products as $index => $product)
                    @php
                        $productName = $product->name ?? $product->title ?? 'Не указано';
                        $productPrice = $product->price ?? 0;
                        $productCount = $product->count ?? 1;
                        $productTotal = $productPrice * $productCount;
                    @endphp
                    <tr>
                        <td class="num">{{ $index + 1 }}</td>
                        <td class="name">{{ $productName }}</td>
                        <td class="count">{{ $productCount }} шт.</td>
                        <td class="price">{{ number_format($productPrice, 0, '.', ' ') }} руб.</td>
                        <td class="total">{{ number_format($productTotal, 0, '.', ' ') }} руб.</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- ==========================================
         ИТОГОВАЯ СУММА
         ========================================== -->
    <div class="totals-block">
        <table class="totals-table">
            <tr>
                <td class="label">Сумма заказа ({{ $totalCount ?? 0 }} ед.)</td>
                <td class="value">{{ number_format($totalPrice ?? 0, 0, '.', ' ') }} руб.</td>
            </tr>
            @if(($discount ?? 0) > 0)
                <tr class="discount">
                    <td class="label">Скидка (Бонусы)</td>
                    <td class="value">-{{ number_format($discount, 0, '.', ' ') }} руб.</td>
                </tr>
            @endif
            @if(($deliveryPrice ?? 0) > 0)
                <tr class="delivery">
                    <td class="label">Доставка
                        @if(($distance ?? 0) > 0)
                            <span class="text-muted">({{ $distance }} км)</span>
                        @endif
                    </td>
                    <td class="value">{{ number_format($deliveryPrice, 0, '.', ' ') }} руб.</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td class="label">ИТОГО К ОПЛАТЕ</td>
                <td class="value">
                    {{ number_format(($totalPrice ?? 0) - ($discount ?? 0) + ($deliveryPrice ?? 0), 0, '.', ' ') }} руб.
                </td>
            </tr>
        </table>
    </div>

    <hr class="divider">

    <!-- ==========================================
         ИНФОРМАЦИЯ ОБ ОПЛАТЕ
         ========================================== -->
    @if(!empty($paymentInfo))
        <div class="payment-block">
            <h3 class="payment-title">Как оплатить заказ</h3>
            <div class="payment-content">
                {!! $paymentInfo !!}
            </div>
        </div>
    @endif

    <!-- ==========================================
         ФУТЕР
         ========================================== -->
    <div class="footer">
        <p class="footer-thanks">
            Благодарим вас за использование нашего сервиса!<br>
            Мы стараемся стать лучше для вас каждый день.
        </p>
        <p class="footer-brand">
            Команда <span style="color: #e53e3e;">❤️</span> {{ $title ?? 'PWA Store' }}
        </p>
        <div class="footer-meta">
            Документ сформирован автоматически · {{ $currentDate ?? now()->format('Y-m-d H:i:s') }}<br>
            Уникальный идентификатор: <strong>{{ $uniqNumber ?? '-' }}</strong>
        </div>
    </div>

</div>

</body>
</html>
