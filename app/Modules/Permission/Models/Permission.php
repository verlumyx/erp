<?php

declare(strict_types=1);

namespace App\Modules\Permission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_permissions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'module_id',
        'action',
        'label',
        'description',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
