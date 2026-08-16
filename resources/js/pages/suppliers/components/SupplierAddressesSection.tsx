import { Plus, Star, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useSupplierFormContext } from '../contexts/SupplierFormContext';
import { ADDRESS_TYPE_LABELS, type AddressType } from '../types/Supplier';

/**
 * 1.2 Direcciones del proveedor. Cada tipo (facturación, retiro, almacén) puede
 * tener una dirección sugerida.
 */
export function SupplierAddressesSection() {
    const {
        data,
        errors,
        addAddress,
        removeAddress,
        updateAddress,
        setDefaultAddress,
    } = useSupplierFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `addresses.${index}.${field}`
        ];

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.addresses && (
                <p className="text-sm text-bad">{errors.addresses}</p>
            )}

            {data.addresses.map((address, index) => {
                const isDefault = address.is_default === 'yes';
                const addressError = fieldError(index, 'address');
                const defaultError = fieldError(index, 'is_default');

                return (
                    <div
                        key={address.id}
                        className="flex flex-col gap-3 rounded-[12px] border p-4"
                    >
                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_2fr_auto_auto]">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Tipo *
                                </Label>
                                <Select
                                    value={address.type}
                                    onValueChange={(value) =>
                                        updateAddress(
                                            index,
                                            'type',
                                            value as AddressType,
                                        )
                                    }
                                >
                                    <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(
                                            ADDRESS_TYPE_LABELS,
                                        ).map(([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Dirección *
                                </Label>
                                <Input
                                    type="text"
                                    value={address.address}
                                    onChange={(e) =>
                                        updateAddress(
                                            index,
                                            'address',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Calle, edificio, piso…"
                                    maxLength={500}
                                    className={`h-[42px] rounded-[10px] ${addressError ? 'border-bad' : ''}`}
                                />
                                {addressError && (
                                    <p className="text-sm text-bad">
                                        {addressError}
                                    </p>
                                )}
                            </div>

                            <Button
                                type="button"
                                variant={isDefault ? 'default' : 'outline'}
                                className="h-[42px] rounded-[10px] px-3 font-semibold"
                                onClick={() => setDefaultAddress(index)}
                                title="Marcar como dirección sugerida de este tipo"
                            >
                                <Star
                                    className={
                                        isDefault ? 'fill-current' : undefined
                                    }
                                />
                                Predeterminada
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="size-[42px] rounded-[10px] bg-card"
                                onClick={() => removeAddress(index)}
                                aria-label="Quitar dirección"
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        {defaultError && (
                            <p className="text-sm text-bad">{defaultError}</p>
                        )}

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            {(
                                [
                                    ['city', 'Ciudad'],
                                    ['state', 'Estado'],
                                    ['country', 'País'],
                                ] as Array<
                                    ['city' | 'state' | 'country', string]
                                >
                            ).map(([field, label]) => (
                                <div
                                    key={field}
                                    className="flex flex-col gap-1.5"
                                >
                                    <Label className="text-[13px] font-semibold">
                                        {label}
                                    </Label>
                                    <Input
                                        type="text"
                                        value={address[field]}
                                        onChange={(e) =>
                                            updateAddress(
                                                index,
                                                field,
                                                e.target.value,
                                            )
                                        }
                                        maxLength={100}
                                        className="h-[42px] rounded-[10px]"
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                );
            })}

            {data.addresses.length === 0 && (
                <p className="text-[13px] text-muted-foreground">
                    El proveedor aún no tiene direcciones registradas.
                </p>
            )}

            <Button
                type="button"
                variant="outline"
                className="h-10 w-max rounded-[11px] bg-card font-semibold"
                onClick={addAddress}
            >
                <Plus />
                Agregar dirección
            </Button>
        </div>
    );
}
