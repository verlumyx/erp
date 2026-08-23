<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Requests;

use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemSerialRequest extends FormRequest
{
    private ?ItemSerial $serial = null;

    private bool $serialResolved = false;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('item-serials.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'serial_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('app_item_serials', 'serial_number')
                    ->where('company_id', $companyId)
                    ->where('item_id', $this->serial()?->item_id)
                    ->ignore($this->route('id')),
            ],
            'lot_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_item_lots', 'id')->where('company_id', $companyId),
            ],
            'warehouse_id' => [
                'nullable',
                'uuid',
                Rule::exists('app_warehouses', 'id')->where('company_id', $companyId),
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
                $serial = $this->serial();
                $lotId = $this->input('lot_id');

                if ($serial === null || $lotId === null) {
                    return;
                }

                $lot = ItemLot::find($lotId);

                if ($lot !== null && $lot->item_id !== $serial->item_id) {
                    $validator->errors()->add('lot_id', 'El lote debe pertenecer al mismo artículo.');
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
            'serial_number.required' => 'El número de serie es obligatorio.',
            'serial_number.unique' => 'Ya existe una serie con ese número para el artículo.',
            'lot_id.exists' => 'El lote seleccionado no existe.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe.',
        ];
    }

    /**
     * El artículo de la serie no se cambia: la unicidad se valida contra el que
     * ya tiene guardado, no contra uno que venga en el cuerpo de la petición.
     */
    private function serial(): ?ItemSerial
    {
        if (! $this->serialResolved) {
            $this->serial = ItemSerial::query()
                ->where('company_id', session('current_company_id'))
                ->find($this->route('id'));

            $this->serialResolved = true;
        }

        return $this->serial;
    }
}
