<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    use Queueable;

    public $email;
    public $password;
    public $createdBy;

    /**
     * Create a new notification instance.
     */
    public function __construct($email, $password, $createdBy)
    {
        $this->email = $email;
        $this->password = $password;
        $this->createdBy = $createdBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Agrar Portal-a Xoş Gəlmisiniz - Hesabınız Yaradıldı')
            ->greeting('Salam ' . $notifiable->first_name . '!')
            ->line('Hesabınız ' . $this->createdBy . ' tərəfindən Agrar Portal-da yaradıldı.')
            ->line('**Giriş məlumatlarınız:**')
            ->line('Email: ' . $this->email)
            ->line('Şifrə: ' . $this->password)
            ->line('**Vacib:** Təhlükəsizlik üçün ilk girişdən sonra şifrənizi dəyişdirin.')
            ->line('İndi bu məlumatlarla portala giriş edə bilərsiniz.')
            ->action('Portala Giriş', url('/login'))
            ->line('Hər hansı sualınız varsa, dəstək komandamızla əlaqə saxlayın.')
            ->salutation('Hörmətlə, Agrar Portal Komandası');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'email' => $this->email,
            'created_by' => $this->createdBy,
        ];
    }

    /**
     * Static method to send notification
     */
    public static function send($user, $data)
    {
        $notification = new self($data['email'], $data['password'], $data['created_by']);
        $user->notify($notification);
    }
}
