<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Modules\Shared\Providers\SharedServiceProvider::class,
    App\Modules\Auth\Providers\AuthServiceProvider::class,
    App\Modules\Menu\Providers\MenuServiceProvider::class,
    App\Modules\Permission\Providers\PermissionServiceProvider::class,
    App\Modules\Company\Providers\CompanyServiceProvider::class,
    App\Modules\Role\Providers\RoleServiceProvider::class,
    App\Modules\User\Providers\UserServiceProvider::class,
    App\Modules\Client\Providers\ClientServiceProvider::class,
    App\Modules\Lead\Providers\LeadServiceProvider::class,
    App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
];
