---
name: laravel-module-currency
description: "Guide for wiring currency and exchange rates into a module that holds amounts (orders, invoices, payments, credit notes, expenses). Covers the four frozen rate columns, resolving the rate from the catalog instead of the form, converting listed prices, the legal bolivar amounts, and the dual-amount UI. Activates when a new module stores money, when adding a currency, exchange_rate, total or price column, or when a screen must show an amount in two currencies."
license: MIT
metadata:
  author: project
---

# Laravel Module — Currency & Exchange Rates

Any module that holds an amount inherits this. The full rules live in `docs/monedas.md`; this skill is the recipe for wiring them into a module.

Reference implementation: **SalesOrder** (`app/Modules/SalesOrder`) — it is the module that already does every step below.

## Rules (read first)

1. **The rate is never taken from the form.** It is resolved from the catalog by `DocumentRatesResolverInterface`. The `exchange_rate` field of the payload is a *manual correction*, honoured only when the company sets `allows_rate_override = yes`.
2. **No module queries `app_exchange_rates` and no module multiplies amounts by hand.** Everything goes through `ExchangeRateResolverInterface` (`rateFor`, `tryRateFor`, `convert`) or `DocumentRatesResolverInterface` (`forDocument`, `priceInDocumentCurrency`).
3. **Every document freezes four columns**, not two: `currency` + `exchange_rate` and `base_currency` + `base_exchange_rate`. The first pair gives the amount in bolivars; the second one lets you re-express it in the company currency even after the company changes that currency.
4. **A missing rate blocks the document.** `ExchangeRateNotFoundException` is already mapped in `bootstrap/app.php` (422 with `errors.exchange_rate`, or `back()->withErrors()` for Inertia) — do not catch it in the module.
5. **Draft refreshes, confirmed freezes.** A draft re-resolves its rates on every save; from `confirmed` onwards nothing recalculates them.
6. **Bolivar amounts (`*_ves`) are persisted only by documents with legal value** — invoices and payments — and **only in the header**. Orders show the equivalent on the fly.
7. **`rateFor()` blocks, `tryRateFor()` does not.** Use the first when issuing a document, the second when merely displaying.

---

## 1. Migration

```php
$table->string('currency', 3);
$table->decimal('exchange_rate', 18, 8);
$table->string('base_currency', 3)->nullable();
$table->decimal('base_exchange_rate', 18, 8)->nullable();
```

Adding them to an existing table: nullable, no backfill. Documents issued before the change have no resolved rate and, since only drafts are editable, they get one on the next save. See `database/migrations/2026_08_16_233000_add_base_currency_to_order_tables.php`.

## 2. Model

```php
protected $fillable = [
    // …
    'currency',
    'exchange_rate',
    'base_currency',
    'base_exchange_rate',
];

protected function casts(): array
{
    return [
        'exchange_rate' => 'decimal:8',
        'base_exchange_rate' => 'decimal:8',
    ];
}
```

## 3. Command

The Command carries the **override**, never a rate:

```php
public function __construct(
    // …
    /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
    public readonly string $currency = 'USD',
    /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
    public readonly ?string $exchangeRateOverride = null,
) {}

public static function fromRequest(Create{Module}Request $request): self
{
    return new self(
        currency: strtoupper($request->string('currency')->toString()),
        exchangeRateOverride: $request->filled('exchange_rate')
            ? (string) $request->input('exchange_rate')
            : null,
    );
}
```

Never default the currency to `'USD'` when reading the request — validation already requires it, and the screen starts it from `configuration.base_currency`.

## 4. Request

```php
'currency' => ['required', 'string', new ActiveCurrency],
/** Opcional: sin valor la resuelve el sistema con el catálogo de tasas. */
'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
```

`ActiveCurrency` includes the bolivar. Use `ForeignCurrency` **only** on the `currency` of an exchange rate itself.

## 5. Service

The Service resolves the rates and hands them to the Repository:

```php
public function __construct(
    private readonly {Module}RepositoryInterface $repository,
    private readonly DocumentRatesResolverInterface $rates,
) {}

public function execute(Create{Module}Command $command): {Module}
{
    $rates = $this->rates->forDocument(
        $command->companyId,
        $command->currency,
        $command->documentDate,
        $command->exchangeRateOverride,
    );

    $this->repository->create($command, $rates);

    return $this->repository->findOrFail($command->id);
}
```

The UpdateService does the same on every save — that is what makes a draft refresh its rate. A service that only changes status (`confirmed`, `cancelled`) must **not** touch the rates.

## 6. Repository

`DocumentRatesData::toAttributes()` returns exactly the four columns:

```php
public function create(Create{Module}Command $command, DocumentRatesData $rates): void
{
    {Module}::create([
        // …
        ...$rates->toAttributes(),
    ]);
}
```

Add `DocumentRatesData $rates` to the interface too — `create()` and `update()`, not `updateStatus()`.

## 7. Resource

```php
'currency' => $this->currency,
'exchange_rate' => $this->exchange_rate,
'base_currency' => $this->base_currency,
'base_exchange_rate' => $this->base_exchange_rate,
```

## 8. Lines priced from a price list (§6)

Only if the module takes prices from `app_item_prices`. The list carries its own currency, so the price is converted **once**, when the line is captured, and frozen:

```php
$converted = round(
    $this->rates->priceInDocumentCurrency(
        $rates,
        $order->company_id,
        $order->document_date->format('Y-m-d'),
        (float) $price->price,
        $price->currency,
    ),
    $rates->priceDecimals,
);
```

The agreed price wins: if the payload's `unit_price` differs from its `list_price`, honour it; otherwise impose the resolved list price. See `SalesOrderRepository::listPrices()` / `lineAmounts()`.

## 9. Invoices and payments only: bolivar columns

Documents with legal value persist `subtotal_ves`, `tax_amount_ves` and `total_ves` **in the header**, computed as `amount × exchange_rate` and rounded to the company's `amount_decimals`. Never duplicate them per line — everything the lines need comes from the header. An invoice uses the rate of its issue date, a payment the one of its payment date; the gap between them is the exchange difference (`docs/monedas.md` §5).

## 10. Frontend

| Need | Use |
|---|---|
| Format, symbol, cross rate | `@/lib/money` — never `toLocaleString` in a page |
| Show an amount | `<AmountDual amount currency rate baseCurrency baseRate />` |
| Rate field | `<ExchangeRateField />` — renders the input only when the company allows overriding |
| Currency select | `<CurrencySelect />` (options come from the shared `currencies` prop) |
| Company policy | `useConfiguration()` |
| Today's rates | `useTodayRates()` — for amounts still being captured |

- The form's initial currency is `initialData?.currency ?? configuration?.base_currency ?? ''`.
- The form's `exchange_rate` is a **string** and starts empty: empty means automatic.
- Pass the document's frozen rate to `AmountDual` for saved amounts; omit it for amounts being captured, and the component falls back to today's rate.
- The dual amount goes in the totals. Line tables stay in the document currency, which the header already states.

## 11. Tests

`tests/Pest.php` has `todayExchangeRate($company, $user, $currency = 'USD', $rate = 36.5)`. The `*Scenario()` helpers already call it — a module without a rate cannot issue anything.

Cover these cases (see `tests/Feature/SalesOrder/SalesOrderExchangeRateTest.php`):

1. The document freezes the catalog rate without the form sending it.
2. A document in another currency also freezes the company rate.
3. A currency without a loaded rate is rejected — `assertSessionHasErrors('exchange_rate')` and nothing is persisted.
4. The rate typed in the form is ignored when `allows_rate_override = no`.
5. The rate typed in the form wins when it is `yes`.
6. Saving the draft again refreshes the rate.

When a test changes a rate mid-run, call `app()->forgetScopedInstances()` first: the resolver caches per request and the test reuses the same application.

## Common mistakes

- Treating the payload's `exchange_rate` as the rate instead of as a correction.
- Making `exchange_rate` `required` in the Request — it is `nullable`.
- Persisting `_ves` columns in an order, or in the lines of anything.
- Freezing `currency`/`exchange_rate` but forgetting `base_currency`/`base_exchange_rate`.
- Re-resolving rates in the UpdateStatus flow, which un-freezes a confirmed document.
- Using `rateFor()` in shared props or in a listing: a company missing one rate would take down every page. Use `tryRateFor()`.
- Formatting money in a page instead of going through `@/lib/money`.
