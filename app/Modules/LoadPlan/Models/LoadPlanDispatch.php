<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Dispatch\Models\Dispatch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadPlanDispatch extends Model
{
    use HasUuids;

    protected $table = 'app_load_plan_dispatches';

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUSES = ['active', 'inactive'];

    protected $fillable = [
        'id',
        'company_id',
        'load_plan_id',
        'dispatch_id',
        'sequence',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function loadPlan(): BelongsTo
    {
        return $this->belongsTo(LoadPlan::class, 'load_plan_id', 'id');
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class, 'dispatch_id', 'id');
    }
}
