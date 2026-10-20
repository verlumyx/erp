<?php

declare(strict_types=1);

namespace App\Modules\Pos\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'pos_register_id' => $this->pos_register_id,
            'pos_register_name' => $this->whenLoaded('register', fn () => $this->register?->name),
            'warehouse_id' => $this->whenLoaded('register', fn () => $this->register?->warehouse_id),
            'warehouse_name' => $this->whenLoaded('register', fn () => $this->register?->warehouse?->name),
            'default_client_id' => $this->whenLoaded('register', fn () => $this->register?->default_client_id),
            'default_client' => $this->whenLoaded('register', fn () => $this->register?->defaultClient ? [
                'id' => $this->register->defaultClient->id,
                'name' => $this->register->defaultClient->name,
                'tax_number' => $this->register->defaultClient->tax_number,
                'document_type' => $this->register->defaultClient->document_type,
            ] : null),
            'price_list_id' => $this->whenLoaded('register', fn () => $this->register?->price_list_id),
            'allow_price_override' => $this->whenLoaded('register', fn () => $this->register?->allow_price_override),
            'allow_custom_discounts' => $this->whenLoaded('register', fn () => $this->register?->allow_custom_discounts),
            'max_discount_percent' => $this->whenLoaded('register', fn () => (float) ($this->register?->max_discount_percent ?? 0)),
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'user_email' => $this->whenLoaded('user', fn () => $this->user?->email),
            'opened_at' => $this->opened_at?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),
            'opening_cash_usd' => (float) $this->opening_cash_usd,
            'opening_cash_ves' => (float) $this->opening_cash_ves,
            'expected_cash_usd' => (float) $this->expected_cash_usd,
            'expected_cash_ves' => (float) $this->expected_cash_ves,
            'expected_card_ves' => (float) $this->expected_card_ves,
            'expected_mobile_ves' => (float) $this->expected_mobile_ves,
            'expected_other' => (float) $this->expected_other,
            'declared_cash_usd' => $this->declared_cash_usd !== null ? (float) $this->declared_cash_usd : null,
            'declared_cash_ves' => $this->declared_cash_ves !== null ? (float) $this->declared_cash_ves : null,
            'declared_card_ves' => $this->declared_card_ves !== null ? (float) $this->declared_card_ves : null,
            'declared_mobile_ves' => $this->declared_mobile_ves !== null ? (float) $this->declared_mobile_ves : null,
            'declared_other' => $this->declared_other !== null ? (float) $this->declared_other : null,
            'difference_cash_usd' => $this->difference_cash_usd !== null ? (float) $this->difference_cash_usd : null,
            'difference_cash_ves' => $this->difference_cash_ves !== null ? (float) $this->difference_cash_ves : null,
            'sales_count' => (int) $this->sales_count,
            'cancelled_sales_count' => (int) $this->cancelled_sales_count,
            'total_sales_amount' => (float) $this->total_sales_amount,
            'total_discounts_amount' => (float) $this->total_discounts_amount,
            'supervisor_id' => $this->supervisor_id,
            'supervisor_name' => $this->whenLoaded('supervisor', fn () => $this->supervisor?->name),
            'closing_notes' => $this->closing_notes,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'cash_movements' => $this->whenLoaded('cashMovements', fn () => $this->cashMovements->map(fn ($m) => [
                'id' => $m->id,
                'movement_type' => $m->movement_type,
                'concept' => $m->concept,
                'currency' => $m->currency,
                'amount' => (float) $m->amount,
                'reason' => $m->reason,
                'authorizer_name' => $m->authorizer?->name,
                'created_at' => $m->created_at?->format('Y-m-d H:i:s'),
            ])),
            'denominations' => $this->whenLoaded('denominations', fn () => $this->denominations->map(fn ($d) => [
                'id' => $d->id,
                'currency' => $d->currency,
                'denomination' => (float) $d->denomination,
                'count' => (int) $d->count,
                'total_amount' => (float) $d->total_amount,
            ])),
        ];
    }
}
