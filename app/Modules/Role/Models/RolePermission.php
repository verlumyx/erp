<?php

declare(strict_types=1);

namespace App\Modules\Role\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    use HasUuids;

    protected $table = 'app_role_permissions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'role_id',
        'permission',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
