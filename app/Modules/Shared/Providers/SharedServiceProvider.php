<?php

declare(strict_types=1);

namespace App\Modules\Shared\Providers;

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\Shared\Repositories\Contracts\UserCompanyRepositoryInterface;
use App\Modules\Shared\Repositories\UserCompanyRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    /**
     * Alias con los que se guarda el tipo de una relación polimórfica.
     *
     * Se guarda el alias y no el FQCN para que renombrar o mover una clase no
     * rompa los datos ya escritos. El mapa vive aquí, fuera de los módulos,
     * porque lo comparten los dos extremos de cada relación.
     *
     * Se registra con `morphMap()` y no con `enforceMorphMap()`: lo segundo
     * prohibiría cualquier morph fuera del mapa y tumbaría los que ya guardan
     * el FQCN —los tokens de Sanctum sobre `users`, sin ir más lejos—.
     *
     * @var array<string, class-string>
     */
    public const MORPH_MAP = [
        SalesOrder::MORPH_ALIAS => SalesOrder::class,
        SalesOrderLine::MORPH_ALIAS => SalesOrderLine::class,
        PurchaseOrder::MORPH_ALIAS => PurchaseOrder::class,
        PurchaseOrderLine::MORPH_ALIAS => PurchaseOrderLine::class,
    ];

    public function register(): void
    {
        $this->app->bind(
            UserCompanyRepositoryInterface::class,
            UserCompanyRepository::class,
        );
    }

    public function boot(): void
    {
        Relation::morphMap(self::MORPH_MAP);
    }
}
