<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $password;
    public $centerAdmin;

    public function __construct(string $password, User $centerAdmin)
    {
        $this->password = $password;
        $this->centerAdmin = $centerAdmin;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('مرحباً بك في SAMS - تم إنشاء حسابك')
            ->greeting("مرحباً {$notifiable->name}،")
            ->line("تم إنشاء حساب معلم لك في نظام SAMS بواسطة {$this->centerAdmin->name}.")
            ->line('بيانات تسجيل الدخول الخاصة بك:')
            ->line("البريد الإلكتروني: {$notifiable->email}")
            ->line("كلمة المرور: {$this->password}")
            ->line('يرجى تغيير كلمة المرور الخاصة بك بعد تسجيل الدخول لأول مرة.')
            ->action('تسجيل الدخول', url('/login'))
            ->line('شكراً لانضمامك إلى SAMS!');
    }
}
