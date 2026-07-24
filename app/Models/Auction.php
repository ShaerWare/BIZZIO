<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasProcurementDocuments;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Auction extends Model implements HasMedia
{
    use AsSource, Filterable, LogsActivity;
    use HasFactory, HasProcurementDocuments, InteractsWithMedia, Searchable, SoftDeletes;

    protected $fillable = [
        'number',
        'title',
        'description',
        'company_id',
        'rfq_id',
        'created_by',
        'type',
        'procedure',
        'currency',
        'start_date',
        'end_date',
        'trading_start',
        'trading_end',
        'starting_price',
        'step_percent',
        'weight_price',
        'weight_deadline',
        'weight_advance',
        'step_price',
        'step_deadline',
        'step_advance',
        'max_deadline',
        'max_advance',
        'last_bid_at',
        'status',
        'cancellation_reason',
        'is_results_hidden',
        'winner_bid_id',
        'best_bid_id',
    ];

    protected $casts = [
        'is_results_hidden' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'trading_start' => 'datetime',
        'trading_end' => 'datetime',
        'last_bid_at' => 'datetime',
        'starting_price' => 'decimal:2',
        'step_percent' => 'decimal:2',
        'weight_price' => 'decimal:2',
        'weight_deadline' => 'decimal:2',
        'weight_advance' => 'decimal:2',
        'step_price' => 'decimal:2',
        'step_advance' => 'decimal:2',
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
     * Создатель аукциона
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Все ставки (заявки + торги)
     */
    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class)->orderBy('created_at', 'desc');
    }

    /**
     * Только заявки на участие (initial)
     */
    public function initialBids(): HasMany
    {
        return $this->hasMany(AuctionBid::class)
            ->where('type', 'initial')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Только ставки в торгах (bid)
     */
    public function tradingBids(): HasMany
    {
        return $this->hasMany(AuctionBid::class)
            ->where('type', 'bid')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Приглашения компаниям
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(AuctionInvitation::class);
    }

    /**
     * Ставка-победитель
     */
    public function winnerBid(): BelongsTo
    {
        return $this->belongsTo(AuctionBid::class, 'winner_bid_id');
    }

    /**
     * #179 RFQ этапа 1 (для коммерческого аукциона)
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    /**
     * #179 Текущее «Лучшее предложение» (принцип непрерывного лидерства)
     */
    public function bestBid(): BelongsTo
    {
        return $this->belongsTo(AuctionBid::class, 'best_bid_id');
    }

    /**
     * #179 Коммерческие предложения этапа 2 (type=offer), новейшие сверху
     */
    public function offerBids(): HasMany
    {
        return $this->hasMany(AuctionBid::class)
            ->where('type', 'offer')
            ->orderBy('created_at', 'desc');
    }

    /**
     * #179 Базовые (первые) предложения участников этапа 2
     */
    public function baseBids(): HasMany
    {
        return $this->hasMany(AuctionBid::class)
            ->where('type', 'offer')
            ->where('is_base', true);
    }

    /**
     * #179 История «Лучших предложений» (лидеров) по возрастанию времени
     */
    public function bestOfferHistory(): HasMany
    {
        return $this->hasMany(AuctionBid::class)
            ->where('type', 'offer')
            ->whereNotNull('became_best_at')
            ->orderBy('became_best_at', 'asc');
    }

    // ========================
    // МЕТОДЫ
    // ========================

    /**
     * Генерация уникального номера (А-ГГММДД-0001)
     */
    public static function generateNumber(): string
    {
        $prefix = 'А';
        $date = now()->format('ymd'); // ГГММДД

        // Найти последний номер за сегодня (включая удалённые)
        $lastNumber = static::withTrashed()
            ->where('number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('number', 'desc')
            ->value('number');

        if ($lastNumber) {
            // Извлечь последний порядковый номер
            $lastSequence = (int) substr($lastNumber, -4);
            $newSequence = $lastSequence + 1;
        } else {
            // Первый аукцион за сегодня
            $newSequence = 1;
        }

        // Формат: А-ГГММДД-0001
        return sprintf('%s-%s-%04d', $prefix, $date, $newSequence);
    }

    /**
     * Генерация уникального 4-символьного кода для участника
     */
    public static function generateAnonymousCode(): string
    {
        do {
            // Генерируем случайный код (2 буквы + 2 цифры)
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2))
                  .substr(str_shuffle('0123456789'), 0, 2);
        } while (AuctionBid::where('anonymous_code', $code)->exists());

        return $code;
    }

    /**
     * Проверка: может ли пользователь управлять этим аукционом
     */
    public function canManage(User $user): bool
    {
        // Создатель или модератор компании-организатора или админ
        return $this->created_by === $user->id
            || $this->company->isModerator($user)
            || $user->hasAccess('platform.systems.auctions');
    }

    /**
     * #148: можно ли отменить аукцион — только до начала торгов (черновик или приём заявок).
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'active']);
    }

    /**
     * Проверка: идёт ли приём заявок на участие
     */
    public function isAcceptingApplications(): bool
    {
        return $this->status === 'active'
        && $this->start_date->isPast()
        && $this->end_date->isFuture();
    }

    /**
     * Проверка: идут ли торги
     */
    public function isTrading(): bool
    {
        return $this->status === 'trading';
    }

    /**
     * #179 Это коммерческий аукцион (этап 2 двухэтапной процедуры)
     */
    public function isCommercial(): bool
    {
        return $this->procedure === self::PROCEDURE_COMMERCIAL;
    }

    /**
     * Проверка: завершён ли аукцион
     */
    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'cancelled']);
    }

    /**
     * Получить текущую минимальную цену (последняя ставка или начальная цена)
     */
    public function getCurrentPrice(): float
    {
        $lastBid = $this->tradingBids()->first();

        return $lastBid ? (float) $lastBid->price : (float) $this->starting_price;
    }

    /**
     * Рассчитать минимальный и максимальный шаг снижения цены
     */
    public function getStepRange(): array
    {
        $currentPrice = $this->getCurrentPrice();

        return [
            'min' => $currentPrice * 0.005, // 0.5%
            'max' => $currentPrice * 0.05,  // 5%
        ];
    }

    /**
     * Media Collections (для Технического задания PDF и Протокола)
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
     * Только активные аукционы (приём заявок)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Только аукционы в торгах
     */
    public function scopeTrading($query)
    {
        return $query->where('status', 'trading');
    }

    /**
     * Только закрытые аукционы
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
                'created' => 'разместил(а) аукцион',
                'updated' => 'обновил(а) аукцион',
                'deleted' => 'удалил(а) аукцион',
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
        // Индексируем только активные, торгующиеся и закрытые аукционы (не черновики)
        return in_array($this->status, ['active', 'trading', 'closed']);
    }
}
