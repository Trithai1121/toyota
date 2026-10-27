<?php

namespace App\Jobs;

use App\Mail\LeadReceived;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class SendLeadNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $leadId) {}

    public function handle(): void
    {
        $lead = Lead::query()->with(['vehicle', 'landingPage'])->findOrFail($this->leadId);
        $email = config('services.lead_notifications.email');
        $telegramToken = config('services.lead_notifications.telegram_bot_token');
        $telegramChatId = config('services.lead_notifications.telegram_chat_id');
        $sent = false;

        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Mail::to($email)->send(new LeadReceived($lead));
            $sent = true;
        }

        if (is_string($telegramToken) && $telegramToken !== '' && is_string($telegramChatId) && $telegramChatId !== '') {
            $this->sendTelegramMessage($telegramToken, $telegramChatId, $lead);
            $sent = true;
        }

        if (! $sent) {
            throw new RuntimeException('No valid lead notification channel is configured.');
        }
    }

    private function sendTelegramMessage(string $token, string $chatId, Lead $lead): void
    {
        $vehicleName = $lead->vehicle?->name ?? 'Chưa chọn';
        $message = implode("\n", [
            'Khách hàng tiềm năng mới',
            'Nhu cầu: '.($lead->request_type === 'test_drive' ? 'Lái thử' : 'Báo giá'),
            'Họ tên: '.$lead->full_name,
            'SĐT: '.$lead->phone,
            'Xe: '.$vehicleName,
            'Khu vực: '.$lead->region,
            'Ngày dự kiến: '.($lead->desired_date?->format('d/m/Y') ?? 'Chưa cung cấp'),
            'Ghi chú: '.($lead->customer_note ?: 'Không có'),
            'Mã lead: '.$lead->id,
        ]);

        try {
            $response = Http::asForm()
                ->connectTimeout(3)
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Telegram notification could not connect.');
        }

        if (! $response->successful() || data_get($response->json(), 'ok') !== true) {
            throw new RuntimeException('Telegram notification failed with HTTP '.$response->status().'.');
        }
    }
}
