<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Auction extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Searchable;
    use AsSource, Filterable, LogsActivity;

    protected $fillable = [
        'number',
        'title',
        'description',
        'company_id',
        'created_by',
        'type',
        'currency',
        'start_date',
        'end_date',
        'trading_start',
        'trading_end',
        'starting_price',
        'step_percent',
        'last_bid_at',
        'status',
        'winner_bid_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'trading_start' => 'datetime',
        'trading_end' => 'datetime',
        'last_bid_at' => 'datetime',
        'starting_price' => 'decimal:2',
        'step_percent' => 'decimal:2',
    ];

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
        return $this->belongsTo(Company::class);
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
                  . substr(str_shuffle('0123456789'), 0, 2);
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
        $this->addMediaCollection('technical_specification')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
        
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
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('number', 'like', "%{$search}%");
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
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
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