<?php

declare(strict_types=1);

namespace App\Modules\Store\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('store-settings.edit') ?? false;
    }

    /**
     * La lista, la bodega y el tipo de cliente deben ser de la empresa de la
     * URL y estar activos: la tienda no puede apoyarse en un catálogo ajeno
     * ni en uno retirado.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = (string) $this->route('company');

        $ofCompany = fn (string $table) => Rule::exists($table, 'id')
            ->where('company_id', $company)
            ->where('status', 'active');

        return [
            'is_enabled' => ['required', 'string', 'in:yes,no'],
            'store_name' => ['required', 'string', 'max:150'],
            'brand_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'price_list_id' => ['nullable', 'uuid', $ofCompany('app_price_lists')],
            'warehouse_id' => ['nullable', 'uuid', $ofCompany('app_warehouses')],
            'shows_stock' => ['required', 'string', 'in:yes,no'],
            'allows_orders' => ['required', 'string', 'in:yes,no'],
            'default_client_type_id' => ['nullable', 'uuid', $ofCompany('app_client_types')],
            'shows_secondary_currency' => ['required', 'string', 'in:yes,no'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'store_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'string', 'in:yes,no'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_enabled.in' => 'Indica si la tienda está habilitada.',
            'store_name.required' => 'El nombre de la tienda es obligatorio.',
            'store_name.max' => 'El nombre de la tienda no puede superar 150 caracteres.',
            'brand_color.required' => 'El color de la tienda es obligatorio.',
            'brand_color.regex' => 'El color debe tener el formato #RRGGBB.',
            'price_list_id.exists' => 'La lista de precio debe ser de esta empresa y estar activa.',
            'warehouse_id.exists' => 'La bodega debe ser de esta empresa y estar activa.',
            'default_client_type_id.exists' => 'El tipo de cliente debe ser de esta empresa y estar activo.',
            'shows_stock.in' => 'Indica si se muestra la cantidad disponible.',
            'allows_orders.in' => 'Indica si la tienda acepta pedidos.',
            'shows_secondary_currency.in' => 'Indica si se muestra el precio en la moneda secundaria.',
            'contact_email.email' => 'El correo de contacto no es válido.',
            'store_url.url' => 'La URL de la tienda no es válida.',
            'logo.mimes' => 'El logo debe ser JPG, PNG o WebP.',
            'logo.max' => 'El logo no puede superar 2 MB.',
        ];
    }
}
