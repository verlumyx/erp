import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';
import type { PosClient } from '../types/Pos';
import { UserCheck, Plus, AlertCircle, CheckCircle2, User, Phone, FileText } from 'lucide-react';
import { posApi } from '../utils/posApi';
import { generateUUID, cn } from '@/lib/utils';
import { Select2Ajax, type AjaxOption } from '@/components/select2-ajax';
import clientsRoute from '@/routes/clients';

interface PosClientModalProps {
    isOpen: boolean;
    onClose: () => void;
    clients?: PosClient[];
}

export function PosClientModal({ isOpen, onClose }: PosClientModalProps) {
    const { client: currentClient, setClient } = usePosCart();
    const { companyId } = usePosSession();

    const [selectedOption, setSelectedOption] = useState<AjaxOption | null>(null);
    const [isCreating, setIsCreating] = useState<boolean>(false);
    const [newDocType, setNewDocType] = useState<string>('V');
    const [newTaxNumber, setNewTaxNumber] = useState<string>('');
    const [newName, setNewName] = useState<string>('');
    const [newPhone, setNewPhone] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    // Sync selected option when modal opens with currently active cart client
    useEffect(() => {
        if (isOpen) {
            if (currentClient) {
                setSelectedOption({
                    value: currentClient.id,
                    label: currentClient.name,
                    meta: {
                        name: currentClient.name,
                        document_type: currentClient.document_type,
                        document_number: currentClient.document_number,
                        tax_number: currentClient.tax_number,
                        phone: currentClient.phone,
                    },
                });
            } else {
                setSelectedOption(null);
            }
            setIsCreating(false);
            setErrorMessage(null);
        }
    }, [isOpen, currentClient]);

    const formatClientOptionLabel = (option: AjaxOption): string => {
        const meta = option.meta as {
            tax_number?: string;
            document_type?: string;
            document_number?: string;
            name?: string;
        } | undefined;

        const doc =
            meta?.tax_number ||
            (meta?.document_number
                ? `${meta.document_type ? `${meta.document_type}-` : ''}${meta.document_number}`
                : '');
        const clientName = meta?.name || option.label;

        return doc && clientName ? `${doc} — ${clientName}` : option.label;
    };

    const handleSelectOption = (option: AjaxOption | null) => {
        setSelectedOption(option);
        setErrorMessage(null);
    };

    const handleAssignSelectedClient = () => {
        if (!selectedOption) {
            setClient(null);
            onClose();
            return;
        }

        const meta = (selectedOption.meta ?? {}) as Record<string, unknown>;
        const docFormatted =
            (meta.tax_number as string) ||
            (meta.document_number
                ? `${(meta.document_type as string) || ''}-${meta.document_number as string}`
                : '');

        const clientToSet: PosClient = {
            id: selectedOption.value,
            name: (meta.name as string) || selectedOption.label,
            document_type: meta.document_type as string | undefined,
            document_number: meta.document_number as string | undefined,
            tax_number: docFormatted,
            phone: meta.phone as string | undefined,
        };

        setClient(clientToSet);
        onClose();
    };

    const handleSelectConsumerFinal = () => {
        setClient(null);
        setSelectedOption(null);
        onClose();
    };

    const handleCreateExpressClient = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newName.trim()) return;

        const cleanDocNumber = newTaxNumber.replace(/\D/g, '');
        if (!cleanDocNumber) {
            setErrorMessage('El número de cédula o RIF debe contener dígitos válidos.');
            return;
        }

        setIsSubmitting(true);
        setErrorMessage(null);

        try {
            const id = generateUUID();
            const formattedTaxNumber = `${newDocType}-${cleanDocNumber}`;

            // Post to client create
            const response = await posApi<{ client?: PosClient; message?: string }>(`/${companyId}/clients`, {
                method: 'POST',
                body: {
                    id,
                    name: newName.trim(),
                    document_type: newDocType,
                    document_number: cleanDocNumber,
                    phone: newPhone.trim() || null,
                    credit_blocked: 'no',
                    status: 'active',
                },
            });

            const created: PosClient = response?.client ?? {
                id,
                name: newName.trim(),
                document_type: newDocType,
                document_number: cleanDocNumber,
                tax_number: formattedTaxNumber,
                phone: newPhone.trim() || null,
            };

            setClient(created);
            setIsCreating(false);
            setNewTaxNumber('');
            setNewName('');
            setNewPhone('');
            setErrorMessage(null);
            onClose();
        } catch (err: unknown) {
            console.error('Error creating client express', err);
            const anyErr = err as { response?: { data?: { message?: string } }; message?: string };
            const msg = anyErr.response?.data?.message || anyErr.message || 'Error al crear el cliente.';
            setErrorMessage(msg);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog
            open={isOpen}
            onOpenChange={(open) => {
                if (!open) {
                    setErrorMessage(null);
                    onClose();
                }
            }}
        >
            <DialogContent
                className="max-w-xl p-6 rounded-2xl"
                onPointerDownOutside={(e) => {
                    const target = e.target as HTMLElement | null;
                    if (
                        target?.closest?.('.react-select__menu') ||
                        target?.closest?.('.react-select__menu-portal') ||
                        target?.closest?.('.react-select-container')
                    ) {
                        e.preventDefault();
                    }
                }}
                onInteractOutside={(e) => {
                    const target = e.target as HTMLElement | null;
                    if (
                        target?.closest?.('.react-select__menu') ||
                        target?.closest?.('.react-select__menu-portal') ||
                        target?.closest?.('.react-select-container')
                    ) {
                        e.preventDefault();
                    }
                }}
            >
                <DialogHeader className="border-b pb-3">
                    <DialogTitle className="text-lg font-bold flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <UserCheck className="h-5 w-5 text-primary" />
                            <span>Seleccionar Cliente [F4]</span>
                        </div>
                        <Button
                            type="button"
                            variant={isCreating ? 'secondary' : 'outline'}
                            size="sm"
                            onClick={() => {
                                setIsCreating(!isCreating);
                                setErrorMessage(null);
                            }}
                            className="h-8 text-xs font-semibold px-3"
                        >
                            <Plus className="h-3.5 w-3.5 mr-1" />
                            {isCreating ? 'Buscar Existente' : 'Nuevo Rápido'}
                        </Button>
                    </DialogTitle>
                </DialogHeader>

                {errorMessage && (
                    <div className="flex items-center gap-2 rounded-lg bg-destructive/15 p-3 text-xs text-destructive font-medium mt-1">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        <span>{errorMessage}</span>
                    </div>
                )}

                {isCreating ? (
                    <form onSubmit={handleCreateExpressClient} className="space-y-4 py-2">
                        <div className="grid grid-cols-3 gap-3">
                            <div>
                                <label className="text-[11px] font-bold text-muted-foreground block mb-1">Tipo:</label>
                                <select
                                    value={newDocType}
                                    onChange={(e) => setNewDocType(e.target.value)}
                                    className="w-full h-10 px-2 rounded-lg border bg-background text-xs font-bold"
                                >
                                    <option value="V">V (Venezolano)</option>
                                    <option value="J">J (Jurídico / RIF)</option>
                                    <option value="E">E (Extranjero)</option>
                                    <option value="G">G (Gubernamental)</option>
                                    <option value="C">C (Comunal)</option>
                                    <option value="P">P (Pasaporte)</option>
                                </select>
                            </div>

                            <div className="col-span-2">
                                <label className="text-[11px] font-bold text-muted-foreground block mb-1">Cédula / RIF:</label>
                                <Input
                                    value={newTaxNumber}
                                    onChange={(e) => setNewTaxNumber(e.target.value)}
                                    placeholder="12345678"
                                    className="h-10 text-xs font-mono"
                                    required
                                    autoFocus
                                />
                            </div>
                        </div>

                        <div>
                            <label className="text-[11px] font-bold text-muted-foreground block mb-1">Nombre o Razón Social:</label>
                            <Input
                                value={newName}
                                onChange={(e) => setNewName(e.target.value)}
                                placeholder="Nombre completo"
                                className="h-10 text-xs"
                                required
                            />
                        </div>

                        <div>
                            <label className="text-[11px] font-bold text-muted-foreground block mb-1">Teléfono (opcional):</label>
                            <Input
                                value={newPhone}
                                onChange={(e) => setNewPhone(e.target.value)}
                                placeholder="0414-1234567"
                                className="h-10 text-xs"
                            />
                        </div>

                        <div className="flex justify-end gap-2 pt-3 border-t">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    setIsCreating(false);
                                    setErrorMessage(null);
                                }}
                            >
                                Volver
                            </Button>
                            <Button type="submit" size="sm" disabled={isSubmitting} className="font-bold px-4">
                                {isSubmitting ? 'Guardando...' : 'Guardar y Asignar'}
                            </Button>
                        </div>
                    </form>
                ) : (
                    <div className="space-y-4 py-2">
                        {/* Quick Consumer final button */}
                        <button
                            type="button"
                            onClick={handleSelectConsumerFinal}
                            className={cn(
                                'w-full p-3 rounded-xl border border-dashed transition-all flex items-center justify-between text-left',
                                !currentClient && !selectedOption
                                    ? 'border-primary bg-primary/10 shadow-sm'
                                    : 'border-primary/40 bg-primary/5 hover:bg-primary/10'
                            )}
                        >
                            <div className="flex items-center gap-3">
                                <div className="h-9 w-9 rounded-full bg-primary/15 flex items-center justify-center text-primary font-bold text-xs shrink-0">
                                    CF
                                </div>
                                <div>
                                    <span className="font-bold text-xs text-primary block">Consumidor Final</span>
                                    <span className="text-[11px] text-muted-foreground">Venta rápida de mostrador sin datos fiscales</span>
                                </div>
                            </div>
                            <span className="text-xs font-bold text-primary font-mono bg-primary/10 px-2 py-1 rounded-md">
                                [Predeterminado]
                            </span>
                        </button>

                        <div className="relative flex py-1 items-center">
                            <div className="flex-grow border-t border-border"></div>
                            <span className="flex-shrink mx-3 text-[11px] uppercase tracking-wider text-muted-foreground font-semibold">
                                o buscar por cédula / RIF / nombre
                            </span>
                            <div className="flex-grow border-t border-border"></div>
                        </div>

                        {/* Select2Ajax */}
                        <div className="space-y-1.5">
                            <label htmlFor="pos-client-select" className="text-xs font-semibold text-foreground block">
                                Buscar Cliente Registrado:
                            </label>
                            <Select2Ajax
                                inputId="pos-client-select"
                                url={clientsRoute.lookup(companyId).url}
                                params={{ status: 'active' }}
                                value={selectedOption}
                                onChange={handleSelectOption}
                                formatLabel={formatClientOptionLabel}
                                placeholder="Escribe el número identificador (cédula o RIF) o nombre..."
                                size="md"
                                minSearchLength={0}
                                isClearable={true}
                                menuPortalTarget={null}
                            />
                        </div>

                        {/* Selected Client Preview Card */}
                        {selectedOption && (
                            <div className="p-3.5 rounded-xl border border-primary/30 bg-primary/5 space-y-2 animate-in fade-in-50 duration-200">
                                <div className="flex items-start justify-between gap-2">
                                    <div className="flex items-center gap-2 truncate">
                                        <User className="h-4 w-4 text-primary shrink-0" />
                                        <span className="text-sm font-bold text-foreground truncate">
                                            {(selectedOption.meta?.name as string) || selectedOption.label}
                                        </span>
                                    </div>
                                    <span className="font-mono text-xs font-bold px-2 py-0.5 rounded bg-background border text-primary shrink-0">
                                        {(selectedOption.meta?.tax_number as string) ||
                                            (selectedOption.meta?.document_number
                                                ? `${(selectedOption.meta?.document_type as string) || ''}-${selectedOption.meta?.document_number as string}`
                                                : 'Sin documento')}
                                    </span>
                                </div>

                                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                    {Boolean(selectedOption.meta?.phone) && (
                                        <div className="flex items-center gap-1">
                                            <Phone className="h-3.5 w-3.5" />
                                            <span>{String(selectedOption.meta?.phone)}</span>
                                        </div>
                                    )}
                                    {Boolean(selectedOption.meta?.code) && (
                                        <div className="flex items-center gap-1 font-mono text-[11px]">
                                            <FileText className="h-3.5 w-3.5" />
                                            <span>{String(selectedOption.meta?.code)}</span>
                                        </div>
                                    )}
                                </div>

                                {selectedOption.meta?.credit_blocked === 'yes' && (
                                    <div className="text-[11px] font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1 pt-1">
                                        <AlertCircle className="h-3.5 w-3.5 shrink-0" />
                                        <span>Este cliente tiene el crédito bloqueado (solo ventas de contado).</span>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Action buttons */}
                        <div className="flex items-center justify-end gap-2 pt-3 border-t">
                            <Button type="button" variant="outline" size="sm" onClick={onClose}>
                                Cancelar
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                disabled={!selectedOption}
                                onClick={handleAssignSelectedClient}
                                className="font-bold px-4"
                            >
                                <CheckCircle2 className="h-4 w-4 mr-1.5" />
                                Asignar a la Venta
                            </Button>
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
