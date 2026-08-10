<?php

namespace App\Models;

use App\Traits\HasProcurementDocuments;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Rfq extends Model implements HasMedia
{
    use AsSource, Filterable, LogsActivity;
    use HasFactory, HasProcurementDocuments, InteractsWithMedia, Searchable, SoftDeletes;

    protected $fillable = [
        'number',
        'title',
        'description',
        'company_id',
        'created_by',
        'type',
        'procedure',
        'currency',
        'start_date',
        'end_date',
        'trading_start',
        'trading_end',
        'weight_price',
        'weight_deadline',
        'weight_advance',
        'step_price',
        'step_deadline',
        'step_advance',
        'max_deadline',
        'max_advance',
        'status',
        'cancellation_reason',
        'is_results_hidden',
        'winner_bid_id',
        'linked_auction_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'trading_start' => 'datetime',
        'trading_end' => 'datetime',
        'weight_price' => 'decimal:2',
        'weight_deadline' => 'decimal:2',
        'weight_advance' => 'decimal:2',
        'step_price' => 'decimal:2',
        'step_advance' => 'decimal:2',
        'is_results_hidden' => 'boolean',
        // #195 Отметки об отправленных уведомлениях о старте этапов.
        'stage1_notified_at' => 'datetime',
        'stage2_notified_at' => 'datetime',
    ];

    public const PROCEDURE_STANDARD = 'standard';

    public const PROCEDURE_COMMERCIAL = 'commercial';

    public const CURRENCIES = [
        'RUB' => '₽',
        'USD' => '$',
        'CNY' => '¥',
    ];

    public function getCurrencySymbolAttribute(): string
    {
        return self::CURRENCIES[$this->currency ?? 'RUB'] ?? '₽';
    }

    // ========================
    // СВЯЗИ (RELATIONSHIPS)
    // ========================

    /**
     * Компания-организатор
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /**
     * Создатель RFQ
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Заявки участников
     */
    public function bids(): HasMany
    {
        return $this->hasMany(RfqBid::class);
    }

    /**
     * Приглашения компаниям
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(RfqInvitation::class);
    }

    /**
     * Заявка-победитель
     */
    public function winnerBid(): BelongsTo
    {
        return $this->belongsTo(RfqBid::class, 'winner_bid_id');
    }

    /**
     * #179 Порождённый аукцион этапа 2 (Коммерческий аукцион)
     */
    public function linkedAuction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'linked_auction_id');
    }

    /**
     * #179 Это коммерческий аукцион (двухэтапная процедура)
     */
    public function isCommercial(): bool
    {
        return $this->procedure === self::PROCEDURE_COMMERCIAL;
    }

    /**
     * #217 Коммерческий аукцион: этап 1 (запрос цен) уже закрыт, но порождённый
     * аукцион этапа 2 ещё идёт (приём заявок или торги). В этом случае процедура
     * НЕ завершена, и статус не должен отображаться как «Завершён».
     */
    public function commercialTradingInProgress(): bool
    {
        return $this->isCommercial()
            && $this->status === 'closed'
            && $this->linkedAuction
            && in_array($this->linkedAuction->status, ['active', 'trading'], true);
    }

    // ========================
    // МЕТОДЫ
    // ========================

    /**
     * Генерация уникального номера (К-ГГММДД-0001)
     */
    public static function generateNumber(): string
    {
        $date = Carbon::now()->format('ymd'); // ГГММДД
        $prefix = 'К-'.$date.'-';

        // Находим последний номер за сегодня
        $lastRfq = self::where('number', 'like', $prefix.'%')
            ->orderBy('number', 'desc')
            ->first();

        if ($lastRfq) {
            $lastNumber = (int) substr($lastRfq->number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Проверка: может ли пользователь управлять этим RFQ
     */
    public function canManage(User $user): bool
    {
        // Создатель или модератор компании-организатора
        return $this->created_by === $user->id
            || $this->company->isModerator($user)
            || $user->hasAccess('platform.systems.rfqs');
    }

    /**
     * #193 Кому доступен блок «Поделиться» (готовый текст приглашения сторонней компании).
     *
     * Организатору — с момента создания и до окончания приёма (у коммерческой процедуры это
     * окончание этапа 1): приглашать новых участников имеет смысл, пока приём открыт. Раньше блок
     * был закрыт политикой `update`, разрешающей правки только черновику, и пропадал сразу после
     * публикации. Остальным пользователям — только у открытой процедуры, идущей сейчас.
     */
    public function canBeSharedBy(?User $user): bool
    {
        if (! $user || in_array($this->status, ['closed', 'cancelled'], true)) {
            return false;
        }

        if ($this->canManage($user)) {
            return true;
        }

        return $this->type === 'open' && $this->status === 'active';
    }

    /**
     * #268 Может ли организатор приглашать компании прямо сейчас.
     *
     * Приглашать нужно и во время приёма заявок (этап 1 коммерческого аукциона — до старта
     * этапа 2), поэтому проверка идёт НЕ по политике `update`: та разрешает правки только
     * черновику, и блок приглашения пропадал сразу после публикации процедуры.
     */
    public function canInviteCompanies(?User $user): bool
    {
        if (! $user || ! in_array($this->status, ['draft', 'active'], true)) {
            return false;
        }

        return $this->canManage($user) && ! $this->isExpired();
    }

    /**
     * #148: можно ли отменить запрос цен — пока он черновик или активен (до закрытия).
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'active']);
    }

    /**
     * Проверка: активен ли RFQ (идёт приём заявок)
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && Carbon::now()->between($this->start_date, $this->end_date);
    }

    /**
     * Проверка: завершён ли приём заявок
     */
    public function isExpired(): bool
    {
        return Carbon::now()->greaterThan($this->end_date);
    }

    /**
     * Media Collections (для Технического задания PDF)
     */
    public function registerMediaCollections(): void
    {
        // #185 Конкурсная документация (notice / technical_specification / contract_draft / other_documents).
        $this->registerProcurementDocumentMediaCollections();

        $this->addMediaCollection('protocol')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Только активные RFQ
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Только закрытые RFQ
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Поиск по названию или номеру
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $op = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $q->where('title', $op, "%{$search}%")
                ->orWhere('number', $op, "%{$search}%");
        });
    }

    /**
     * Настройки логирования активности
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'number', 'status', 'type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'разместил(а) запрос цен',
                'updated' => 'обновил(а) запрос цен',
                'deleted' => 'удалил(а) запрос цен',
                default => $eventName,
            });
    }

    // ========================
    // ПОИСК (SCOUT)
    // ========================

    /**
     * Поля для индексации поиска
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }

    /**
     * Определяет, должна ли модель индексироваться
     */
    public function shouldBeSearchable(): bool
    {
        // Индексируем только активные и закрытые RFQ (не черновики)
        return in_array($this->status, ['active', 'closed']);
    }
}
