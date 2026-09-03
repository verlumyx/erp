<?php

declare(strict_types=1);

namespace App\Modules\Menu\Models;

use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_menus';

    protected $fillable = [
        'parent_id',
        'title',
        'url',
        'permission',
        'icon',
        'is_active',
        'order',
        'section',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id', 'id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    protected static function newFactory(): MenuFactory
    {
        return MenuFactory::new();
    }
}
