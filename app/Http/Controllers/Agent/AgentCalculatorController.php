<?php

namespace App\Http\Controllers\Agent;

use App\Facades\PaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use PhpOffice\PhpWord\PhpWord;

class AgentCalculatorController extends Controller
{
    public function getConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => config('calculator')
        ]);
    }

    public function sendEstimate(Request $request, \App\Services\Tenants\PaymentService $paymentService)
    {
        $data = $request->validate([
            'formData' => 'required|array',
            'formData.clientName' => 'required|string',
            'formData.clientPhone' => 'required|string',
            'clientEmail' => 'required|email',
            'total' => 'required|numeric',
            'monthly' => 'required|numeric',
            'planPrice' => 'required|numeric',
        ]);

        $formData = $data['formData'];
        $clientEmail = $data['clientEmail'];
        $clientName = $formData['clientName'];
        $grandTotal = $data['total'] + $data['planPrice'];

        // ==========================================
        // 1. ГЕНЕРАЦИЯ DOCX (Создаем $tempFile)
        // ==========================================
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        // Стили для документа
        $titleStyle = ['bold' => true, 'size' => 18, 'align' => 'center', 'spaceAfter' => 300, 'color' => '1E3A8A'];
        $headerStyle = ['bold' => true, 'size' => 12, 'spaceAfter' => 100, 'color' => '2563EB'];
        $textStyle = ['size' => 11, 'spaceAfter' => 100];

        $section->addTitle('Коммерческое предложение', 1, $titleStyle);

        $section->addText('Данные клиента:', $headerStyle);
        $section->addText("Имя: {$clientName}", $textStyle);
        $section->addText("Телефон: {$formData['clientPhone']}", $textStyle);
        $section->addText("Email: {$clientEmail}", $textStyle);
        $section->addTextBreak(1);

        $section->addText('Параметры приложения:', $headerStyle);

        $business = collect(config('calculator.business_types'))->firstWhere('id', $formData['businessType']);
        $section->addText("• Тип бизнеса: " . ($business['name'] ?? 'Не указан'), $textStyle);

        if (!empty($formData['features'])) {
            $features = collect(config('calculator.features'))->whereIn('id', $formData['features'])->pluck('name')->join(', ');
            $section->addText("• Функции: {$features}", $textStyle);
        }

        if (!empty($formData['integrations'])) {
            $integrations = collect(config('calculator.integrations'))->whereIn('id', $formData['integrations'])->pluck('name')->join(', ');
            $section->addText("• Интеграции: {$integrations}", $textStyle);
        }

        $design = collect(config('calculator.design_options'))->firstWhere('id', $formData['design']);
        $section->addText("• Дизайн: " . ($design['name'] ?? 'Не указан'), $textStyle);

        $section->addTextBreak(1);
        $section->addText('Стоимость:', $headerStyle);
        $section->addText("Разработка приложения: " . number_format($data['total'], 0, ',', ' ') . " ₽", array_merge($textStyle, ['bold' => true]));
        $section->addText("Тариф обслуживания: " . number_format($data['planPrice'], 0, ',', ' ') . " ₽", array_merge($textStyle, ['bold' => true]));

        $section->addText("ИТОГО К ОПЛАТЕ: " . number_format($grandTotal, 0, ',', ' ') . " ₽", ['bold' => true, 'size' => 14, 'color' => 'DC2626', 'spaceAfter' => 200]);

        // 🎯 СОЗДАНИЕ ВРЕМЕННОГО ФАЙЛА
        $tempFile = tempnam(sys_get_temp_dir(), 'estimate_') . '.docx';
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);


        // ==========================================
        // 2. ГЕНЕРАЦИЯ ССЫЛКИ НА ОПЛАТУ
        // ==========================================
        try {
            $paymentUrl = $paymentService->generateEstimatePaymentLink([
                'amount' => $grandTotal,
                'description' => "Разработка и тариф для: {$clientName}",
                'clientName' => $clientName,
                'clientPhone' => $formData['clientPhone'],
                'clientEmail' => $clientEmail,
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка генерации ссылки оплаты КП: ' . $e->getMessage());
            $paymentUrl = null;
        }


        // ==========================================
        // 3. ОТПРАВКА НА EMAIL
        // ==========================================
        \Illuminate\Support\Facades\Mail::send('emails.estimate', [
            'clientName' => $clientName,
            'paymentUrl' => $paymentUrl,
            'amount' => number_format($grandTotal, 0, ',', ' ')
        ], function ($message) use ($clientEmail, $clientName, $tempFile) {
            $message->to($clientEmail, $clientName)
                ->subject('Коммерческое предложение и ссылка на оплату')
                ->attach($tempFile, [
                    'as' => 'Kommercheskoe_predlozhenie.docx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]);
        });


        // ==========================================
        // 4. ОТПРАВКА В TELEGRAM
        // ==========================================
        $telegramToken = env('TELEGRAM_BOT_TOKEN');
        $telegramChatId = env('TELEGRAM_MANAGER_CHAT_ID');

        if ($telegramToken && $telegramChatId && file_exists($tempFile)) {
            $telegramCaption = "📄 *Новая заявка на разработку*\n\n";
            $telegramCaption .= "👤 *Клиент:* {$clientName}\n";
            $telegramCaption .= "📞 *Телефон:* {$formData['clientPhone']}\n";
            $telegramCaption .= "📧 *Email:* {$clientEmail}\n";
            $telegramCaption .= "💰 *Сумма:* " . number_format($grandTotal, 0, ',', ' ') . " ₽\n";

            if ($paymentUrl) {
                $telegramCaption .= "\n🔗 [Ссылка на оплату для клиента]({$paymentUrl})";
            }

            \Illuminate\Support\Facades\Http::asMultipart()
                ->attach('document', file_get_contents($tempFile), 'Kommercheskoe_predlozhenie.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->post("https://api.telegram.org/bot{$telegramToken}/sendDocument", [
                    'chat_id' => $telegramChatId,
                    'caption' => $telegramCaption,
                    'parse_mode' => 'Markdown'
                ]);
        }


        // ==========================================
        // 5. ОЧИСТКА ВРЕМЕННОГО ФАЙЛА
        // ==========================================
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        return response()->json([
            'success' => true,
            'message' => 'Предложение успешно отправлено на почту и в Telegram'
        ]);
    }}
