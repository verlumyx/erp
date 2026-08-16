import { Plus, Star, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClientFormContext } from '../contexts/ClientFormContext';

/**
 * 1.1 Contactos del cliente. Una fila puede marcarse como contacto principal;
 * el resto son contactos secundarios.
 */
export function ClientContactsSection() {
    const {
        data,
        errors,
        addContact,
        removeContact,
        updateContact,
        setPrimaryContact,
    } = useClientFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `contacts.${index}.${field}`
        ];

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.contacts && (
                <p className="text-sm text-bad">{errors.contacts}</p>
            )}

            {data.contacts.map((contact, index) => {
                const isPrimary = contact.is_primary === 'yes';
                const nameError = fieldError(index, 'name');
                const emailError = fieldError(index, 'email');

                return (
                    <div
                        key={contact.id}
                        className="grid grid-cols-1 items-end gap-3 rounded-[12px] border p-4 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1.3fr_1fr_auto_auto]"
                    >
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Nombre *
                            </Label>
                            <Input
                                type="text"
                                value={contact.name}
                                onChange={(e) =>
                                    updateContact(index, 'name', e.target.value)
                                }
                                placeholder="Ej. María Pérez"
                                maxLength={150}
                                className={`h-[42px] rounded-[10px] ${nameError ? 'border-bad' : ''}`}
                            />
                            {nameError && (
                                <p className="text-sm text-bad">{nameError}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Cargo
                            </Label>
                            <Input
                                type="text"
                                value={contact.position}
                                onChange={(e) =>
                                    updateContact(
                                        index,
                                        'position',
                                        e.target.value,
                                    )
                                }
                                placeholder="Ej. Compras"
                                maxLength={100}
                                className="h-[42px] rounded-[10px]"
                            />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Correo
                            </Label>
                            <Input
                                type="email"
                                value={contact.email}
                                onChange={(e) =>
                                    updateContact(
                                        index,
                                        'email',
                                        e.target.value,
                                    )
                                }
                                placeholder="contacto@cliente.com"
                                maxLength={255}
                                className={`h-[42px] rounded-[10px] ${emailError ? 'border-bad' : ''}`}
                            />
                            {emailError && (
                                <p className="text-sm text-bad">{emailError}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Teléfono
                            </Label>
                            <Input
                                type="text"
                                value={contact.phone}
                                onChange={(e) =>
                                    updateContact(
                                        index,
                                        'phone',
                                        e.target.value,
                                    )
                                }
                                maxLength={30}
                                className="h-[42px] rounded-[10px]"
                            />
                        </div>

                        <Button
                            type="button"
                            variant={isPrimary ? 'default' : 'outline'}
                            className="h-[42px] rounded-[10px] px-3 font-semibold"
                            onClick={() => setPrimaryContact(index)}
                            title="Marcar como contacto principal"
                        >
                            <Star
                                className={
                                    isPrimary ? 'fill-current' : undefined
                                }
                            />
                            Principal
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-[42px] rounded-[10px] bg-card"
                            onClick={() => removeContact(index)}
                            aria-label="Quitar contacto"
                        >
                            <X className="size-4" />
                        </Button>
                    </div>
                );
            })}

            {data.contacts.length === 0 && (
                <p className="text-[13px] text-muted-foreground">
                    El cliente aún no tiene contactos registrados.
                </p>
            )}

            <Button
                type="button"
                variant="outline"
                className="h-10 w-max rounded-[11px] bg-card font-semibold"
                onClick={addContact}
            >
                <Plus />
                Agregar contacto
            </Button>
        </div>
    );
}
