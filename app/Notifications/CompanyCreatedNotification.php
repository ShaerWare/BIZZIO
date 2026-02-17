<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CompanyCreatedNotification extends Notification
{
    use Queueable;

    public Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Новая компания: ' . $this->company->name)
            ->greeting('Здравствуйте!')
            ->line('На платформе зарегистрирована новая компания.')
            ->line('**Название:** ' . $this->company->name)
            ->line('**ИНН:** ' . ($this->company->inn ?? '—'))
            ->line('**Создатель:** ' . ($this->company->creator->name ?? '—'))
            ->action('Верифицировать в админке', route('platform.companies.edit', $this->company))
            ->line('Пожалуйста, проверьте данные и верифицируйте компанию.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'company_created',
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'url' => route('platform.companies.edit', $this->company),
        ];
    }
}
