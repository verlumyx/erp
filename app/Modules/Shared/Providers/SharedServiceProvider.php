<?php

declare(strict_types=1);

namespace App\Modules\Shared\Providers;

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;
use App\Modules\Shared\Repositories\CompanyDisabledMenuRepository;
use App\Modules\Shared\Repositories\Contracts\CompanyDisabledMenuRepositoryInterface;
use App\Modules\Shared\Repositories\Contracts\UserCompanyRepositoryInterface;
use App\Modules\Shared\Repositories\UserCompanyRepository;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Warehouse\Models\Warehouse;
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
        /** Devoluciones: originan el despacho que saca y la entrada que reingresa. */
        PurchaseReturn::MORPH_ALIAS => PurchaseReturn::class,
        PurchaseReturnLine::MORPH_ALIAS => PurchaseReturnLine::class,
        SalesReturn::MORPH_ALIAS => SalesReturn::class,
        SalesReturnLine::MORPH_ALIAS => SalesReturnLine::class,
        Transfer::MORPH_ALIAS => Transfer::class,
        TransferLine::MORPH_ALIAS => TransferLine::class,
        Dispatch::MORPH_ALIAS => Dispatch::class,
        DispatchLine::MORPH_ALIAS => DispatchLine::class,
        /** Documento que respalda un costo del expediente de importación. */
        PurchaseInvoice::MORPH_ALIAS => PurchaseInvoice::class,
        /** Destinatarios de un despacho: un cliente, una bodega propia o un proveedor. */
        Client::MORPH_ALIAS => Client::class,
        Warehouse::MORPH_ALIAS => Warehouse::class,
        Supplier::MORPH_ALIAS => Supplier::class,
    ];

    public function register(): void
    {
        $this->app->bind(
            UserCompanyRepositoryInterface::class,
            UserCompanyRepository::class,
        );

        $this->app->bind(
            CompanyDisabledMenuRepositoryInterface::class,
            CompanyDisabledMenuRepository::class,
        );
    }

    public function boot(): void
    {
        Relation::morphMap(self::MORPH_MAP);
    }
}
