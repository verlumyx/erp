<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Requests;

use App\Modules\ItemLot\Models\ItemLot;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemLotRequest extends FormRequest
{
    private ?ItemLot $lot = null;

    private bool $lotResolved = false;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('item-lots.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'lot_number' => [
                'required',
                'string',
                'max:60',
                Rule::unique('app_item_lots', 'lot_number')
                    ->where('company_id', $companyId)
                    ->where('item_id', $this->lot()?->item_id)
                    ->ignore($this->route('id')),
            ],
            'manufactured_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'supplier_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_suppliers', 'id')->where('company_id', $companyId),
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $manufacturedAt = $this->input('manufactured_at');
                $expiresAt = $this->input('expires_at');

                if ($manufacturedAt !== null && $expiresAt !== null && $expiresAt < $manufacturedAt) {
                    $validator->errors()->add(
                        'expires_at',
                        'La fecha de vencimiento no puede ser anterior a la de fabricación.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lot_number.required' => 'El número de lote es obligatorio.',
            'lot_number.unique' => 'Ya existe un lote con ese número para el artículo.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
        ];
    }

    /**
     * El artículo del lote no se cambia: la unicidad se valida contra el que ya
     * tiene guardado, no contra uno que venga en el cuerpo de la petición.
     */
    private function lot(): ?ItemLot
    {
        if (! $this->lotResolved) {
            $this->lot = ItemLot::query()
                ->where('company_id', session('current_company_id'))
                ->find($this->route('id'));

            $this->lotResolved = true;
        }

        return $this->lot;
    }
}
