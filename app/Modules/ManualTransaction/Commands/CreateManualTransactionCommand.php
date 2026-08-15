<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Commands;

use App\Modules\ManualTransaction\Requests\CreateManualTransactionRequest;

class CreateManualTransactionCommand
{
    /**
     * @param  array<int, array{category: string, amount: float, description: ?string}>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $date,
        public readonly string $paymentMethod,
        public readonly string $currency,
        public readonly array $lines,
        public readonly ?string $reference = null,
        public readonly ?string $description = null,
        public readonly ?string $notes = null,
        public readonly ?string $recordedBy = null,
    ) {}

    public static function fromRequest(CreateManualTransactionRequest $request, ?string $companyId = null): self
    {
        $lines = array_map(
            static fn (array $line): array => [
                'category' => (string) $line['category'],
                'amount' => (float) $line['amount'],
                'description' => $line['description'] ?? null,
            ],
            array_values($request->input('lines', [])),
        );

        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            date: $request->string('date')->toString(),
            paymentMethod: $request->string('payment_method')->toString(),
            currency: $request->string('currency')->toString(),
            lines: $lines,
            reference: $request->input('reference'),
            description: $request->input('description'),
            notes: $request->input('notes'),
            recordedBy: (string) $request->user()->id,
        );
    }
}
