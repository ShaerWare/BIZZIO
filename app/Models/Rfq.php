<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Carbon\Carbon;

class Rfq extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;
    use AsSource, Filterable;

    protected $fillable = [
        'number',
        'title',
        'description',
        'company_id',
        'created_by',
        'type',
        'start_date',
        'end_date',
        'weight_price',
        'weight_deadline',
        'weight_advance',
        'status',
        'winner_bid_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'weight_price' => 'decimal:2',
        'weight_deadline' => 'decimal:2',
        'weight_advance' => 'decimal:2',
    ];

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
    public function winnerBid(): HasOne
    {
        return $this->hasOne(RfqBid::class, 'id', 'winner_bid_id');
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
        $prefix = 'К-' . $date . '-';
        
        // Находим последний номер за сегодня
        $lastRfq = self::where('number', 'like', $prefix . '%')
            ->orderBy('number', 'desc')
            ->first();
        
        if ($lastRfq) {
            $lastNumber = (int) substr($lastRfq->number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
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
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('number', 'like', "%{$search}%");
        });
    }
}