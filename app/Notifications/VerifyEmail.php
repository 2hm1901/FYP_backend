<?php
namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmail extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Tạo URL thủ công, không dùng temporarySignedRoute
        $frontendUrl = env('FRONTEND_URL', 'http://localhost/frontend') . "/email/verify/{$notifiable->id}/" . sha1($notifiable->email);

        \Log::info('Generated verification URL: ' . $frontendUrl);

        return (new MailMessage)
            ->subject('Xác nhận địa chỉ email')
            ->line('Nhấn vào nút dưới đây để xác nhận email của bạn.')
            ->action('Xác nhận Email', $frontendUrl)
            ->line('Nếu bạn không tạo tài khoản, vui lòng bỏ qua email này.');
    }
}