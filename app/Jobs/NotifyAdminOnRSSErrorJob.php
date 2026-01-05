<?php

namespace App\Jobs;

use App\Models\RSSSource;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAdminOnRSSErrorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $source;
    public $errorMessage;

    /**
     * Create a new job instance.
     */
    public function __construct(RSSSource $source, string $errorMessage)
    {
        $this->source = $source;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Получаем всех администраторов
        $admins = User::whereHas('roles', function ($query) {
            $query->where('slug', 'admin');
        })->get();

        if ($admins->isEmpty()) {
            Log::warning('Нет администраторов для отправки уведомления об ошибке RSS');
            return;
        }

        foreach ($admins as $admin) {
            try {
                // В продакшене здесь будет отправка email
                // Mail::to($admin->email)->send(new RSSErrorMail($this->source, $this->errorMessage));
                
                // Для разработки — просто логируем
                Log::info("Уведомление админу {$admin->email}: Ошибка парсинга RSS-источника {$this->source->name}", [
                    'source_url' => $this->source->url,
                    'error' => $this->errorMessage,
                ]);
            } catch (\Exception $e) {
                Log::error("Ошибка отправки уведомления админу: {$e->getMessage()}");
            }
        }
    }
}