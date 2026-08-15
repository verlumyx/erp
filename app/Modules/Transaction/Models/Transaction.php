<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'app_transactions';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Tipos válidos de movimiento. El signo del dinero lo determina el tipo;
     * `amount` siempre se almacena positivo.
     */
    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPES = [self::TYPE_INCOME, self::TYPE_EXPENSE];

    /**
     * Categorías de gasto. Solo válidas con type = 'expense'.
     */
    public const EXPENSE_CATEGORIES = [
        'streaming_account',
        'streaming_account_renewal',
        'petty_cash',
        'salary',
        'commission',
        'utilities',
        'tools',
        'marketing',
        'refund',
        'other_expense',
    ];

    /**
     * Categorías de ingreso. Solo válidas con type = 'income'.
     */
    public const INCOME_CATEGORIES = [
        'sale',
        'renewal',
        'partner_contribution',
        'other_income',
    ];

    /**
     * Etiquetas en español de los tipos. Fuente única de verdad para el frontend.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        self::TYPE_INCOME => 'Ingreso',
        self::TYPE_EXPENSE => 'Egreso',
    ];

    /**
     * Etiquetas en español de cada categoría. Fuente única de verdad para el frontend.
     *
     * @var array<string, string>
     */
    public const CATEGORY_LABELS = [
        'sale' => 'Venta',
        'renewal' => 'Renovación',
        'partner_contribution' => 'Aporte de socio',
        'other_income' => 'Otro ingreso',
        'streaming_account' => 'Cuenta de streaming',
        'streaming_account_renewal' => 'Renovación de cuenta',
        'petty_cash' => 'Caja chica',
        'salary' => 'Salario',
        'commission' => 'Comisión',
        'utilities' => 'Servicios',
        'tools' => 'Herramientas',
        'marketing' => 'Marketing',
        'refund' => 'Reembolso',
        'other_expense' => 'Otro gasto',
    ];

    protected $fillable = [
        'id',
        'company_id',
        'type',
        'category',
        'subcategory',
        'related_type',
        'related_id',
        'amount',
        'currency',
        'date',
        'payment_method',
        'reference',
        'period_from',
        'period_to',
        'description',
        'notes',
        'recorded_by',
        'receipt_url',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'period_from' => 'date',
            'period_to' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Deriva el tipo (income/expense) a partir de la categoría.
     */
    public static function typeForCategory(string $category): string
    {
        if (in_array($category, self::INCOME_CATEGORIES, true)) {
            return self::TYPE_INCOME;
        }

        if (in_array($category, self::EXPENSE_CATEGORIES, true)) {
            return self::TYPE_EXPENSE;
        }

        throw new \InvalidArgumentException("La categoría '{$category}' no es válida.");
    }

    /**
     * Catálogo centralizado de tipos y categorías para compartir con el frontend.
     *
     * @return array{
     *     types: list<array{value: string, label: string}>,
     *     categories: list<array{value: string, label: string, type: string}>,
     *     income: list<array{value: string, label: string}>,
     *     expense: list<array{value: string, label: string}>
     * }
     */
    public static function catalog(): array
    {
        $income = array_map(
            fn (string $category): array => ['value' => $category, 'label' => self::CATEGORY_LABELS[$category]],
            self::INCOME_CATEGORIES,
        );

        $expense = array_map(
            fn (string $category): array => ['value' => $category, 'label' => self::CATEGORY_LABELS[$category]],
            self::EXPENSE_CATEGORIES,
        );

        $categories = [];
        foreach (self::INCOME_CATEGORIES as $category) {
            $categories[] = ['value' => $category, 'label' => self::CATEGORY_LABELS[$category], 'type' => self::TYPE_INCOME];
        }
        foreach (self::EXPENSE_CATEGORIES as $category) {
            $categories[] = ['value' => $category, 'label' => self::CATEGORY_LABELS[$category], 'type' => self::TYPE_EXPENSE];
        }

        return [
            'types' => [
                ['value' => self::TYPE_INCOME, 'label' => self::TYPE_LABELS[self::TYPE_INCOME]],
                ['value' => self::TYPE_EXPENSE, 'label' => self::TYPE_LABELS[self::TYPE_EXPENSE]],
            ],
            'categories' => $categories,
            'income' => $income,
            'expense' => $expense,
        ];
    }

    /**
     * Relación polimórfica opcional con el origen del movimiento (Account, Sale, …).
     * El morph map se registra en TransactionServiceProvider.
     */
    public function related(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'related_type', 'related_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id');
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeByPeriod(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeOfMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeRelatedTo(Builder $query, string $type, string $id): Builder
    {
        return $query->where('related_type', $type)->where('related_id', $id);
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
