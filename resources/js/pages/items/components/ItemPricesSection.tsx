import { Plus, X } from 'lucide-react';
import { CurrencySelect } from '@/components/currency-select';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useItemFormContext } from '../contexts/ItemFormContext';

/**
 * 1.2 Precios por lista. La lista solo nombra el conjunto; el precio y la
 * moneda se definen aquí. Un solo precio por lista.
 */
export function ItemPricesSection() {
    const { data, errors, options, addPrice, removePrice, updatePrice } =
        useItemFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `prices.${index}.${field}`
        ];

    const priceListOptions: OptionType[] = options.priceLists.map(
        (priceList) => ({ value: priceList.id, label: priceList.name }),
    );

    return (
        <div className="flex flex-col gap-4 p-5">
            {options.priceLists.length === 0 && (
                <p className="text-[13px] text-muted-foreground">
                    Aún no hay listas de precio en esta empresa. Créalas en
                    Catálogo → Listas de precio.
                </p>
            )}

            {data.prices.map((price, index) => (
                <div
                    key={index}
                    className="grid grid-cols-1 items-end gap-3 rounded-[12px] border p-4 sm:grid-cols-[1.6fr_1fr_1fr_auto]"
                >
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Lista de precio *
                        </Label>
                        <Select2
                            options={priceListOptions}
                            value={
                                priceListOptions.find(
                                    (option) =>
                                        option.value === price.price_list_id,
                                ) ?? null
                            }
                            onChange={(option) =>
                                updatePrice(
                                    index,
                                    'price_list_id',
                                    option?.value ?? '',
                                )
                            }
                            error={!!fieldError(index, 'price_list_id')}
                            size="md"
                            placeholder="Selecciona una lista"
                        />
                        {fieldError(index, 'price_list_id') && (
                            <p className="text-sm text-bad">
                                {fieldError(index, 'price_list_id')}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Precio *
                        </Label>
                        <CurrencyInput
                            value={price.price}
                            onValueChange={(value) =>
                                updatePrice(index, 'price', value)
                            }
                            min={0}
                            decimals={6}
                            currencySymbol={price.currency || '$'}
                            /* pl-14 deja sitio al código ISO (USD) en lugar de a un símbolo de un carácter. */
                            className={`h-[42px] rounded-[10px] pl-14 ${fieldError(index, 'price') ? 'border-bad' : ''}`}
                        />
                        {fieldError(index, 'price') && (
                            <p className="text-sm text-bad">
                                {fieldError(index, 'price')}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Moneda *
                        </Label>
                        <CurrencySelect
                            value={price.currency}
                            onValueChange={(value) =>
                                updatePrice(index, 'currency', value)
                            }
                            error={fieldError(index, 'currency')}
                        />
                        {fieldError(index, 'currency') && (
                            <p className="text-sm text-bad">
                                {fieldError(index, 'currency')}
                            </p>
                        )}
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-[42px] rounded-[10px] bg-card"
                        onClick={() => removePrice(index)}
                        aria-label="Quitar precio"
                    >
                        <X className="size-4" />
                    </Button>
                </div>
            ))}

            <Button
                type="button"
                variant="outline"
                className="h-10 w-max rounded-[11px] bg-card font-semibold"
                onClick={addPrice}
                disabled={options.priceLists.length === 0}
            >
                <Plus />
                Agregar precio
            </Button>
        </div>
    );
}
