import { Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useItemFormContext } from '../contexts/ItemFormContext';

/**
 * 1.2 Precios por lista. La lista solo nombra el conjunto; el precio, la
 * moneda y la vigencia se definen aquí.
 */
export function ItemPricesSection() {
    const { data, errors, options, addPrice, removePrice, updatePrice } =
        useItemFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `prices.${index}.${field}`
        ];

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
                    className="grid grid-cols-1 items-end gap-3 rounded-[12px] border p-4 sm:grid-cols-[1.6fr_1fr_0.7fr_1fr_1fr_auto]"
                >
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Lista de precio *
                        </Label>
                        <Select
                            value={price.price_list_id}
                            onValueChange={(value) =>
                                updatePrice(index, 'price_list_id', value)
                            }
                        >
                            <SelectTrigger
                                className={`h-[42px] w-full rounded-[10px] ${fieldError(index, 'price_list_id') ? 'border-bad' : ''}`}
                            >
                                <SelectValue placeholder="Selecciona una lista" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.priceLists.map((priceList) => (
                                    <SelectItem
                                        key={priceList.id}
                                        value={priceList.id}
                                    >
                                        {priceList.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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
                        <Input
                            type="text"
                            maxLength={3}
                            value={price.currency}
                            onChange={(e) =>
                                updatePrice(
                                    index,
                                    'currency',
                                    e.target.value.toUpperCase(),
                                )
                            }
                            placeholder="USD"
                            className={`h-[42px] rounded-[10px] uppercase ${fieldError(index, 'currency') ? 'border-bad' : ''}`}
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Vigente desde
                        </Label>
                        <Input
                            type="date"
                            value={price.valid_from}
                            onChange={(e) =>
                                updatePrice(index, 'valid_from', e.target.value)
                            }
                            className="h-[42px] rounded-[10px]"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Vigente hasta
                        </Label>
                        <Input
                            type="date"
                            value={price.valid_to}
                            onChange={(e) =>
                                updatePrice(index, 'valid_to', e.target.value)
                            }
                            className={`h-[42px] rounded-[10px] ${fieldError(index, 'valid_to') ? 'border-bad' : ''}`}
                        />
                        {fieldError(index, 'valid_to') && (
                            <p className="text-sm text-bad">
                                {fieldError(index, 'valid_to')}
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
