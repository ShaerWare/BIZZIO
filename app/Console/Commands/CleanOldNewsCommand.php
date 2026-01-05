<?php

namespace App\Console\Commands;

use App\Models\News;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanOldNewsCommand extends Command
{
    protected $signature = 'news:clean-old';
    protected $description = 'Удаление новостей старше 1 месяца';

    public function handle()
    {
        $this->info('🗑️ Удаляем новости старше 1 месяца...');

        $oneMonthAgo = Carbon::now()->subMonth();

        $deletedCount = News::where('published_at', '<', $oneMonthAgo)
            ->orWhere('created_at', '<', $oneMonthAgo)
            ->delete();

        $this->info("✅ Удалено новостей: {$deletedCount}");

        return 0;
    }
}