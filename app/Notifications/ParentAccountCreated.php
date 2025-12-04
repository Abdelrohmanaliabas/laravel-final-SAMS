<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParentAccountCreated extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $password;
    public $student;

    public function __construct(string $password, User $student)
    {
        $this->password = $password;
        $this->student = $student;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'parent_account_created',
            'title' => 'مرحباً بك في SAMS',
            'message' => "تم إنشاء حسابك بنجاح. يمكنك الآن متابعة تقدم ابنك/ابنتك {$this->student->name}",
            'student_name' => $this->student->name,
            'icon' => 'users',
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
            ->line("تم إنشاء حساب ولي أمر لك في نظام SAMS لمتابعة تقدم ابنك/ابنتك {$this->student->name}.")
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
