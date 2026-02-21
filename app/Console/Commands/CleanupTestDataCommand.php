<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Company;
use App\Models\Project;
use App\Models\Rfq;
use App\Models\RfqBid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTestDataCommand extends Command
{
    protected $signature = 'cleanup:test-data {--force : Пропустить подтверждения}';

    protected $description = 'Удаление тестовых компаний и всех связанных данных (soft-delete)';

    public function handle(): int
    {
        $companies = Company::withCount(['rfqs', 'projects', 'joinRequests'])
            ->orderBy('id')
            ->get();

        if ($companies->isEmpty()) {
            $this->info('Компании не найдены.');

            return self::SUCCESS;
        }

        // Company doesn't have auctions() relationship — count separately
        $auctionCounts = Auction::selectRaw('company_id, count(*) as cnt')
            ->whereIn('company_id', $companies->pluck('id'))
            ->groupBy('company_id')
            ->pluck('cnt', 'company_id');

        $this->table(
            ['ID', 'Название', 'Slug', 'RFQs', 'Аукционы', 'Проекты', 'Заявки'],
            $companies->map(fn (Company $c) => [
                $c->id,
                $c->name,
                $c->slug,
                $c->rfqs_count,
                $auctionCounts->get($c->id, 0),
                $c->projects_count,
                $c->join_requests_count,
            ])
        );

        $input = $this->option('force')
            ? 'all'
            : $this->ask('Введите ID компаний для удаления (через запятую) или "all" для всех');

        if (! $input) {
            $this->info('Отменено.');

            return self::SUCCESS;
        }

        if ($input === 'all') {
            $selectedCompanies = $companies;
        } else {
            $ids = array_map('intval', array_filter(explode(',', $input)));
            $selectedCompanies = $companies->whereIn('id', $ids);

            if ($selectedCompanies->isEmpty()) {
                $this->error('Компании с указанными ID не найдены.');

                return self::FAILURE;
            }
        }

        $companyIds = $selectedCompanies->pluck('id')->toArray();

        // Подсчёт каскадных записей
        $rfqIds = Rfq::whereIn('company_id', $companyIds)->pluck('id');
        $auctionIds = Auction::whereIn('company_id', $companyIds)->pluck('id');
        $projectIds = Project::whereIn('company_id', $companyIds)->pluck('id');

        $rfqBidsCount = RfqBid::whereIn('rfq_id', $rfqIds)->count();
        $auctionBidsCount = AuctionBid::whereIn('auction_id', $auctionIds)->count();
        $rfqInvitationsCount = DB::table('rfq_invitations')->whereIn('rfq_id', $rfqIds)->count();
        $auctionInvitationsCount = DB::table('auction_invitations')->whereIn('auction_id', $auctionIds)->count();
        $companyUserCount = DB::table('company_user')->whereIn('company_id', $companyIds)->count();
        $companyProjectCount = DB::table('company_project')->whereIn('company_id', $companyIds)->count();
        $joinRequestsCount = DB::table('company_join_requests')->whereIn('company_id', $companyIds)->count();

        $this->newLine();
        $this->warn('Будет удалено:');
        $this->table(
            ['Тип', 'Количество', 'Тип удаления'],
            [
                ['Компании', count($companyIds), 'soft-delete'],
                ['RFQ', $rfqIds->count(), 'soft-delete'],
                ['Ставки RFQ', $rfqBidsCount, 'soft-delete'],
                ['Аукционы', $auctionIds->count(), 'soft-delete'],
                ['Ставки аукционов', $auctionBidsCount, 'soft-delete'],
                ['Проекты', $projectIds->count(), 'soft-delete'],
                ['Приглашения RFQ', $rfqInvitationsCount, 'hard-delete'],
                ['Приглашения аукционов', $auctionInvitationsCount, 'hard-delete'],
                ['Связи компания-пользователь', $companyUserCount, 'hard-delete'],
                ['Связи компания-проект', $companyProjectCount, 'hard-delete'],
                ['Заявки на вступление', $joinRequestsCount, 'hard-delete'],
            ]
        );

        if (! $this->option('force') && ! $this->confirm('Подтвердить удаление?')) {
            $this->info('Отменено.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($companyIds, $rfqIds, $auctionIds, $projectIds) {
            // Soft-delete ставок
            RfqBid::whereIn('rfq_id', $rfqIds)->delete();
            AuctionBid::whereIn('auction_id', $auctionIds)->delete();

            // Hard-delete приглашений (нет SoftDeletes)
            DB::table('rfq_invitations')->whereIn('rfq_id', $rfqIds)->delete();
            DB::table('auction_invitations')->whereIn('auction_id', $auctionIds)->delete();

            // Soft-delete RFQ и аукционов
            Rfq::whereIn('company_id', $companyIds)->delete();
            Auction::whereIn('company_id', $companyIds)->delete();

            // Soft-delete проектов
            Project::whereIn('company_id', $companyIds)->delete();

            // Hard-delete pivot-записей
            DB::table('company_user')->whereIn('company_id', $companyIds)->delete();
            DB::table('company_project')->whereIn('company_id', $companyIds)->delete();
            DB::table('company_join_requests')->whereIn('company_id', $companyIds)->delete();

            // Soft-delete компаний
            Company::whereIn('id', $companyIds)->delete();
        });

        $this->newLine();
        $this->info('Удаление завершено. Удалено компаний: '.count($companyIds));

        return self::SUCCESS;
    }
}
