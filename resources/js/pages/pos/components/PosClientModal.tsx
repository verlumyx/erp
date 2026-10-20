import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';
import type { PosClient } from '../types/Pos';
import { UserCheck, Search, Plus } from 'lucide-react';
import { posApi } from '../utils/posApi';

interface PosClientModalProps {
    isOpen: boolean;
    onClose: () => void;
    clients: PosClient[];
}

export function PosClientModal({ isOpen, onClose, clients: initialClients }: PosClientModalProps) {
    const { client: currentClient, setClient } = usePosCart();
    const { session, companyId } = usePosSession();

    const [search, setSearch] = useState<string>('');
    const [clientsList, setClientsList] = useState<PosClient[]>(initialClients);
    const [isCreating, setIsCreating] = useState<boolean>(false);
    const [newDocType, setNewDocType] = useState<string>('V');
    const [newTaxNumber, setNewTaxNumber] = useState<string>('');
    const [newName, setNewName] = useState<string>('');
    const [newPhone, setNewPhone] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);

    const filteredClients = clientsList.filter((c) => {
        const q = search.toLowerCase().trim();
        const docFormatted = c.tax_number || (c.document_number ? `${c.document_type || ''}-${c.document_number}` : '');
        return (
            c.name.toLowerCase().includes(q) ||
            docFormatted.toLowerCase().includes(q) ||
            (c.document_number && c.document_number.includes(q))
        );
    });

    const handleSelectClient = (c: PosClient | null) => {
        setClient(c);
        onClose();
    };

    const handleCreateExpressClient = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newName.trim()) return;

        setIsSubmitting(true);
        try {
            const id = crypto.randomUUID();
            const cleanDocNumber = newTaxNumber.replace(/\D/g, '');
            const formattedTaxNumber = cleanDocNumber ? `${newDocType}-${cleanDocNumber}` : '';

            // Post to client create
            await posApi(`/${companyId}/clients`, {
                method: 'POST',
                body: {
                    id,
                    name: newName.trim(),
                    document_type: newDocType,
                    document_number: cleanDocNumber,
                    phone: newPhone.trim() || null,
                    status: 'active',
                },
            });

            const created: PosClient = {
                id,
                name: newName.trim(),
                document_type: newDocType,
                document_number: cleanDocNumber,
                tax_number: formattedTaxNumber,
                phone: newPhone.trim() || null,
            };

            setClientsList((prev) => [created, ...prev]);
            setClient(created);
            setIsCreating(false);
            onClose();
        } catch (err) {
            console.error('Error creating client express', err);
            // Fallback: set local client directly
            const cleanDocNumber = newTaxNumber.replace(/\D/g, '');
            const fallback: PosClient = {
                id: crypto.randomUUID(),
                name: newName.trim(),
                document_type: newDocType,
                document_number: cleanDocNumber,
                tax_number: cleanDocNumber ? `${newDocType}-${cleanDocNumber}` : '',
            };
            setClient(fallback);
            onClose();
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-md p-6 rounded-2xl">
                <DialogHeader className="border-b pb-3">
                    <DialogTitle className="text-lg font-bold flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <UserCheck className="h-5 w-5 text-primary" />
                            <span>Seleccionar Cliente [F4]</span>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setIsCreating(!isCreating)}
                            className="h-7 text-xs px-2"
                        >
                            <Plus className="h-3.5 w-3.5 mr-1" />
                            {isCreating ? 'Buscar' : 'Nuevo Rápido'}
                        </Button>
                    </DialogTitle>
                </DialogHeader>

                {isCreating ? (
                    <form onSubmit={handleCreateExpressClient} className="space-y-3 py-2">
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <label className="text-[11px] font-bold text-muted-foreground block mb-1">Tipo:</label>
                                <select
                                    value={newDocType}
                                    onChange={(e) => setNewDocType(e.target.value)}
                                    className="w-full h-9 px-2 rounded-lg border bg-background text-xs font-bold"
                                >
                                    <option value="V">V (Venezolano)</option>
                                    <option value="J">J (Jurídico / RIF)</option>
                                    <option value="E">E (Extranjero)</option>
                                    <option value="G">G (Gubernamental)</option>
                                </select>
                            </div>

                            <div className="col-span-2">
                                <label className="text-[11px] font-bold text-muted-foreground block mb-1">Cédula / RIF:</label>
                                <Input
                                    value={newTaxNumber}
                                    onChange={(e) => setNewTaxNumber(e.target.value)}
                                    placeholder="12345678"
                                    className="h-9 text-xs font-mono"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label className="text-[11px] font-bold text-muted-foreground block mb-1">Nombre o Razón Social:</label>
                            <Input
                                value={newName}
                                onChange={(e) => setNewName(e.target.value)}
                                placeholder="Nombre completo"
                                className="h-9 text-xs"
                                required
                            />
                        </div>

                        <div>
                            <label className="text-[11px] font-bold text-muted-foreground block mb-1">Teléfono (opcional):</label>
                            <Input
                                value={newPhone}
                                onChange={(e) => setNewPhone(e.target.value)}
                                placeholder="0414-1234567"
                                className="h-9 text-xs"
                            />
                        </div>

                        <div className="flex justify-end gap-2 pt-2 border-t">
                            <Button type="button" variant="ghost" size="sm" onClick={() => setIsCreating(false)}>
                                Volver
                            </Button>
                            <Button type="submit" size="sm" disabled={isSubmitting} className="font-bold">
                                {isSubmitting ? 'Guardando...' : 'Asignar a Venta'}
                            </Button>
                        </div>
                    </form>
                ) : (
                    <div className="space-y-3 py-2">
                        {/* Quick Consumer final button */}
                        <button
                            type="button"
                            onClick={() => handleSelectClient(null)}
                            className="w-full p-2.5 rounded-xl border border-dashed border-primary/40 bg-primary/5 hover:bg-primary/10 text-left transition-colors flex items-center justify-between"
                        >
                            <div>
                                <span className="font-bold text-xs text-primary block">Consumidor Final</span>
                                <span className="text-[10px] text-muted-foreground">Venta rápida de mostrador sin datos fiscales</span>
                            </div>
                            <span className="text-xs font-bold text-primary font-mono">[Predeterminado]</span>
                        </button>

                        {/* Search input */}
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Buscar por nombre o cédula/RIF..."
                                className="h-9 pl-9 text-xs"
                            />
                        </div>

                        {/* List */}
                        <div className="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                            {filteredClients.map((c) => (
                                <button
                                    key={c.id}
                                    type="button"
                                    onClick={() => handleSelectClient(c)}
                                    className={`w-full p-2.5 rounded-lg border text-left text-xs transition-colors flex items-center justify-between ${
                                        currentClient?.id === c.id
                                            ? 'bg-primary/10 border-primary font-bold'
                                            : 'hover:bg-muted/50 border-border'
                                    }`}
                                >
                                    <div className="truncate">
                                        <div className="text-foreground truncate">{c.name}</div>
                                        <div className="text-[10px] text-muted-foreground font-mono">
                                            {c.tax_number || (c.document_number ? `${c.document_type ? `${c.document_type}-` : ''}${c.document_number}` : 'Sin documento')}
                                        </div>
                                    </div>
                                    {c.phone && <span className="text-[10px] text-muted-foreground ml-2">{c.phone}</span>}
                                </button>
                            ))}
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
