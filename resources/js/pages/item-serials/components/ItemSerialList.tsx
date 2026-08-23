import { router, usePage } from '@inertiajs/react';
import { Edit, Eye, MoreHorizontal, Power, Search } from 'lucide-react';
import { useState } from 'react';
import { Select2Ajax, type AjaxOption } from '@/components/select2-ajax';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import itemSerials from '@/routes/item-serials';
import items from '@/routes/items';
import {
    SERIAL_STATUS_LABELS,
    SERIAL_STATUS_PILL,
    type ItemSerial,
    type ItemSerialFilters,
    type ItemSerialMeta,
    type ItemSerialStatus,
    type WarehouseOption,
} from '../types/ItemSerial';

interface ItemSerialListProps {
    serials: ItemSerial[];
    warehouses: WarehouseOption[];
    meta: ItemSerialMeta;
    filters: ItemSerialFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const STATUS_OPTIONS: OptionType[] = [
    { value: 'todos', label: 'Todos' },
    ...(
        Object.entries(SERIAL_STATUS_LABELS) as Array<
            [ItemSerialStatus, string]
        >
    ).map(([value, label]) => ({ value, label })),
];

export function ItemSerialList({
    serials,
    warehouses,
    meta,
    filters: initialFilters,
}: ItemSerialListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<ItemSerialFilters>(initialFilters);
    const [itemOption, setItemOption] = useState<AjaxOption | null>(null);

    const warehouseOptions: OptionType[] = [
        { value: 'todas', label: 'Todas' },
        ...warehouses.map((warehouse) => ({
            value: warehouse.id,
            label: warehouse.name,
        })),
    ];

    const applyFilters = (next: ItemSerialFilters) => {
        setFilters(next);
        router.get(
            itemSerials.index(companyId).url,
            next as Record<string, string>,
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setFilters({});
        setItemOption(null);
        router.get(itemSerials.index(companyId).url);
    };

    /**
     * La serie no alterna entre dos estados: recorre un ciclo de vida, así que
     * el menú ofrece el destino en lugar de un interruptor.
     */
    const handleChangeStatus = (
        serial: ItemSerial,
        status: ItemSerialStatus,
    ) => {
        router.put(
            itemSerials.updateStatus({ company: companyId, id: serial.id }).url,
            { status },
        );
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Series
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} serie{meta.total !== 1 ? 's' : ''} · se
                        registran al recibir la mercancía; cada unidad se
                        controla por separado
                    </p>
                </div>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="filter-serial-number">
                            Número de serie
                        </Label>
                        <Input
                            id="filter-serial-number"
                            type="text"
                            placeholder="Buscar por número..."
                            value={filters.serial_number ?? ''}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    serial_number: e.target.value,
                                })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters(filters)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-item">Artículo</Label>
                        <Select2Ajax
                            inputId="filter-item"
                            url={items.lookup(companyId).url}
                            params={{ type: 'serialized' }}
                            value={itemOption}
                            onChange={(option) => {
                                setItemOption(option);
                                applyFilters({
                                    ...filters,
                                    item_id: option?.value,
                                });
                            }}
                            isClearable
                            placeholder="Todos"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-warehouse">Bodega</Label>
                        <Select2
                            inputId="filter-warehouse"
                            options={warehouseOptions}
                            value={
                                warehouseOptions.find(
                                    (option) =>
                                        option.value ===
                                        (filters.warehouse_id ?? 'todas'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    warehouse_id:
                                        !option || option.value === 'todas'
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Bodega"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select2
                            inputId="filter-status"
                            options={STATUS_OPTIONS}
                            value={
                                STATUS_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.status ?? 'todos'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    status:
                                        !option || option.value === 'todos'
                                            ? undefined
                                            : option.value,
                                })
                            }
                            placeholder="Estado"
                            isSearchable={false}
                        />
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button
                        onClick={() => applyFilters(filters)}
                        variant="default"
                    >
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClear} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_1.4fr_1.1fr_1fr_0.8fr]">
                    {[
                        'Código',
                        'Serie',
                        'Artículo',
                        'Bodega',
                        'Estado',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 5 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {serials.map((serial) => (
                        <div
                            key={serial.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_1.4fr_1.1fr_1fr_0.8fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    itemSerials.show({
                                        company: companyId,
                                        id: serial.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {serial.code}
                                </span>
                            </div>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {serial.serial_number}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {serial.lot_number
                                        ? `lote ${serial.lot_number}`
                                        : 'Sin lote'}
                                </span>
                            </div>
                            <div className="hidden min-w-0 lg:block">
                                <span className="truncate text-[13px] font-semibold text-muted-foreground">
                                    {serial.item_name ?? '—'}
                                </span>
                            </div>
                            <div className="hidden min-w-0 lg:block">
                                <span className="truncate text-[13px] font-semibold text-muted-foreground">
                                    {serial.warehouse_name ?? '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={SERIAL_STATUS_PILL[serial.status]}
                                >
                                    {SERIAL_STATUS_LABELS[serial.status]}
                                </StatusPill>
                            </div>
                            <div
                                className="flex items-center justify-end gap-2"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            className="rounded-[10px] bg-card"
                                            aria-label="Opciones"
                                        >
                                            <MoreHorizontal className="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            onClick={() =>
                                                router.visit(
                                                    itemSerials.show({
                                                        company: companyId,
                                                        id: serial.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            onClick={() =>
                                                router.visit(
                                                    itemSerials.edit({
                                                        company: companyId,
                                                        id: serial.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Editar
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuSub>
                                            <DropdownMenuSubTrigger>
                                                <Power className="mr-2 h-4 w-4" />
                                                Cambiar estado
                                            </DropdownMenuSubTrigger>
                                            <DropdownMenuSubContent>
                                                {(
                                                    Object.entries(
                                                        SERIAL_STATUS_LABELS,
                                                    ) as Array<
                                                        [
                                                            ItemSerialStatus,
                                                            string,
                                                        ]
                                                    >
                                                )
                                                    .filter(
                                                        ([status]) =>
                                                            status !==
                                                            serial.status,
                                                    )
                                                    .map(([status, label]) => (
                                                        <DropdownMenuItem
                                                            key={status}
                                                            onClick={() =>
                                                                handleChangeStatus(
                                                                    serial,
                                                                    status,
                                                                )
                                                            }
                                                        >
                                                            {label}
                                                        </DropdownMenuItem>
                                                    ))}
                                            </DropdownMenuSubContent>
                                        </DropdownMenuSub>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {serials.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {serials.length} de {meta.total} serie
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
