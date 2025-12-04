<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAccountCreated extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $password;
    public $createdBy;

    public function __construct(string $password, User $createdBy)
    {
        $this->password = $password;
        $this->createdBy = $createdBy;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'student_account_created',
            'title' => 'مرحباً بك في SAMS',
            'message' => 'تم إنشاء حسابك بنجاح. يمكنك الآن تسجيل الدخول والانضمام إلى المجموعات',
            'icon' => 'academic-cap',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('مرحباً بك في SAMS - تم إنشاء حسابك')
            ->greeting("مرحباً {$notifiable->name}،")
            ->line("تم إنشاء حساب طالب لك في نظام SAMS.")
            ->line('بيانات تسجيل الدخول الخاصة بك:')
            ->line("البريد الإلكتروني: {$notifiable->email}")
            ->line("كلمة المرور: {$this->password}")
            ->line('يرجى تغيير كلمة المرور الخاصة بك بعد تسجيل الدخول لأول مرة.')
            ->action('تسجيل الدخول', url('/login'))
            ->line('شكراً لانضمامك إلى SAMS!');
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
