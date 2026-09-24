<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f3f4f6; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h2 { margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 30px 20px; }
        .content p { margin-bottom: 16px; font-size: 15px; color: #4b5563; }
        .invoice-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 24px 0; text-align: center; }
        .invoice-amount { font-size: 32px; font-weight: 800; color: #1e293b; margin: 10px 0; }
        .invoice-desc { font-size: 14px; color: #64748b; margin-bottom: 20px; }
        .btn-pay { display: inline-block; background: #10b981; color: #ffffff !important; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-weight: 700; font-size: 16px; transition: background 0.2s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
        .btn-pay:hover { background: #059669; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Счёт на оплату услуг</h2>
    </div>
    <div class="content">
        <p>Здравствуйте, <strong>{{ $clientName }}</strong>!</p>
        <p>Благодарим вас за сотрудничество. Ниже представлены детали счёта, выставленного агентом <strong>{{ $agentName }}</strong>.</p>

        <div class="invoice-box">
            <div style="font-size: 14px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">К оплате</div>
            <div class="invoice-amount">{{ $amount }} ₽</div>
            <div class="invoice-desc">{{ $description }}</div>

            <a href="{{ $paymentUrl }}" class="btn-pay">💳 Оплатить счёт</a>

            <p style="font-size: 12px; color: #94a3b8; margin-top: 16px; margin-bottom: 0;">
                Нажимая на кнопку, вы будете перенаправлены на защищенную страницу платежной системы
            </p>
        </div>

        <p>Если у вас возникли вопросы по данному счёту, пожалуйста, свяжитесь с вашим агентом.</p>
        <p>С уважением,<br>Команда сервиса</p>
    </div>
    <div class="footer">
        Это письмо было отправлено автоматически. Пожалуйста, не отвечайте на него.<br>
        ID операции: {{ time() }}
    </div>
</div>
</body>
</html>
