<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9fafb; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h2 { margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .content p { margin-bottom: 16px; font-size: 15px; }
        .payment-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0; }
        .payment-amount { font-size: 28px; font-weight: 800; color: #0369a1; margin: 10px 0; }
        .btn-pay { display: inline-block; background: #10b981; color: white !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 16px; margin-top: 10px; transition: background 0.2s; }
        .btn-pay:hover { background: #059669; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Коммерческое предложение</h2>
    </div>
    <div class="content">
        <p>Здравствуйте, <strong>{{ $clientName }}</strong>!</p>
        <p>Благодарим вас за интерес к нашей платформе. Во вложении к этому письму вы найдете детальное коммерческое предложение с расчетом стоимости и сроков разработки вашего приложения.</p>

        @if(!empty($paymentUrl))
            <div class="payment-box">
                <div style="font-size: 14px; color: #0369a1; font-weight: 600;">Итого к оплате:</div>
                <div class="payment-amount">{{ $amount }} ₽</div>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">Нажмите на кнопку ниже, чтобы безопасно оплатить заказ через СБП или банковской картой</p>
                <a href="{{ $paymentUrl }}" class="btn-pay">💳 Оплатить предложение</a>
            </div>
        @else
            <p>Наш менеджер свяжется с вами в ближайшее время для обсуждения деталей и выставления счета.</p>
        @endif

        <p>Если у вас возникнут вопросы по документу или процессу оплаты, мы всегда на связи.</p>
        <p>С уважением,<br><strong>Команда разработки</strong></p>
    </div>
    <div class="footer">
        Это письмо было отправлено автоматически. Пожалуйста, не отвечайте на него.<br>
        Документ сгенерирован автоматически на основе данных калькулятора.
    </div>
</div>
</body>
</html>
