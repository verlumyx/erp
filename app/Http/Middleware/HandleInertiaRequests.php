<?php

namespace App\Http\Middleware;

use App\Modules\Company\Models\Company;
use App\Modules\Configuration\Resources\ConfigurationResource;
use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\Currency\Services\CurrencyOptionsService;
use App\Modules\ExchangeRate\Services\TodayRatesService;
use App\Modules\Menu\Services\GetActiveMenusService;
use App\Modules\Shared\Models\UserCompany;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $menus = ['mainNavItems' => [], 'footerNavItems' => []];
        $currentCompany = null;
        $defaultCompanyId = null;
        $userCompanies = [];
        $currencies = [];
        $configuration = null;
        $todayRates = [];

        if ($request->user()) {
            $isSystemOwner = $request->user()->is_system_owner;

            $companiesQuery = $request->user()->companies()
                ->wherePivot('status', 'active');

            if (! $isSystemOwner) {
                $companiesQuery->where('app_companies.status', 'active');
            }

            $companies = $companiesQuery->get(['app_companies.id', 'app_companies.name']);

            $userCompanies = $companies->map(fn (Company $c): array => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values()->all();

            $defaultPivot = UserCompany::where('user_id', $request->user()->id)
                ->where('is_default', true)
                ->first();

            $defaultCompanyId = $defaultPivot?->company_id;

            // Prefer the company from the URL parameter; fall back to session
            $currentCompanyId = $request->route('company')
                ?? $request->session()->get('current_company_id');

            // Ensure current_company_id is in session before permission checks run
            if (! $currentCompanyId && $companies->isNotEmpty()) {
                $activeDefaultId = $companies->firstWhere('id', $defaultCompanyId)?->id;
                $currentCompanyId = $activeDefaultId ?? $companies->first()->id;
                $request->session()->put('current_company_id', $currentCompanyId);
            }

            if ($currentCompanyId) {
                $found = $companies->firstWhere('id', $currentCompanyId);
                if ($found) {
                    $currentCompany = ['id' => $found->id, 'name' => $found->name];

                    /**
                     * La moneda principal se comparte con todas las páginas: los
                     * formularios e importes no la piden a su controlador.
                     */
                    $configuration = (new ConfigurationResource(
                        app(ConfigurationFindService::class)->execute($found->id)
                    ))->resolve();

                    /**
                     * Con las tasas de hoy una pantalla enseña el equivalente
                     * de lo que se está capturando. Lo ya guardado usa la tasa
                     * congelada del propio documento.
                     */
                    $todayRates = app(TodayRatesService::class)->execute($found->id);
                }
            }

            $menus = app(GetActiveMenusService::class)->execute($request->user(), $currentCompanyId);

            if ($currentCompanyId) {
                $menus = $this->prefixMenuUrls($menus, $currentCompanyId);
            }

            $currencies = app(CurrencyOptionsService::class)->execute();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()?->getPermissions() ?? [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'store_api_key' => $request->session()->get('store_api_key'),
            ],
            'menus' => $menus,
            'currentCompany' => $currentCompany,
            'defaultCompanyId' => $defaultCompanyId,
            'userCompanies' => $userCompanies,
            'currencies' => $currencies,
            'configuration' => $configuration,
            'todayRates' => $todayRates,
        ];
    }

    /**
     * Prefix menu item URLs with the current company ID.
     *
     * @param  array<string, mixed>  $menus
     * @return array<string, mixed>
     */
    private function prefixMenuUrls(array $menus, string $companyId): array
    {
        return [
            'mainNavItems' => array_map(fn (array $item) => $this->prefixItem($item, $companyId), $menus['mainNavItems']),
            'footerNavItems' => array_map(fn (array $item) => $this->prefixItem($item, $companyId), $menus['footerNavItems']),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function prefixItem(array $item, string $companyId): array
    {
        if (isset($item['url']) && str_starts_with((string) $item['url'], '/')) {
            $item['url'] = '/'.$companyId.$item['url'];
        }

        if (! empty($item['children'])) {
            $item['children'] = array_map(fn (array $child) => $this->prefixItem($child, $companyId), $item['children']);
        }

        return $item;
    }
}
