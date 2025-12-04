<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CenterAdminStatusChanged extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $status;
    public $reason;

    public function __construct(string $status, ?string $reason = null)
    {
        $this->status = $status;
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toArray($notifiable): array
    {
        $isApproved = $this->status === 'approved';

        return [
            'type' => 'center_admin_status_changed',
            'title' => $isApproved ? 'تم قبول طلبك' : 'تم رفض طلبك',
            'message' => $isApproved
                ? 'تم قبول طلب التسجيل الخاص بك كمدير مركز. يمكنك الآن تسجيل الدخول والبدء في إدارة مركزك'
                : "تم رفض طلب التسجيل الخاص بك" . ($this->reason ? ": {$this->reason}" : ''),
            'status' => $this->status,
            'reason' => $this->reason,
            'icon' => $isApproved ? 'check-circle' : 'x-circle',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toMail($notifiable): MailMessage
    {
        $isApproved = $this->status === 'approved';

        $mail = (new MailMessage)
            ->subject($isApproved ? 'تم قبول طلبك - SAMS' : 'تم رفض طلبك - SAMS')
            ->greeting("مرحباً {$notifiable->name}،");

        if ($isApproved) {
            $mail->line('تم قبول طلب التسجيل الخاص بك كمدير مركز.')
                ->line('يمكنك الآن تسجيل الدخول والبدء في إدارة مركزك.')
                ->action('تسجيل الدخول', url('/login'))
                ->line('شكراً لاستخدامك نظام SAMS!');
        } else {
            $mail->line('نأسف لإبلاغك بأنه تم رفض طلب التسجيل الخاص بك.');
            if ($this->reason) {
                $mail->line("السبب: {$this->reason}");
            }
            $mail->line('إذا كان لديك أي استفسارات، يرجى التواصل مع الإدارة.');
        }

        return $mail;
    }

    public function broadcastOn(): array
    {
        return ['private-user.' . $this->notifiable->id];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }
}
