import { Sparkles } from 'lucide-react';
import { ProgressRing } from '@/components/progress-ring';
import { Card } from '@/components/ui/card';
import type { DashboardOccupancy as Occupancy } from '../types';

interface Props {
    occupancy: Occupancy;
}

/** Ocupación del inventario de perfiles: ocupados vs. libres. */
export function DashboardOccupancy({ occupancy }: Props) {
    const { occupied, available, total } = occupancy;

    return (
        <Card className="gap-0 rounded-2xl py-0">
            <div className="p-5 pb-0">
                <div className="text-base font-bold tracking-tight">
                    Ocupación de perfiles
                </div>
                <div className="mt-0.5 text-[13px] text-muted-foreground">
                    Inventario disponible
                </div>
            </div>
            <div className="flex items-center gap-5 p-5 pt-3">
                <ProgressRing value={occupied} total={total} />
                <div className="flex flex-1 flex-col gap-2.5">
                    <div className="flex items-center gap-2 text-sm">
                        <span className="size-[11px] rounded-[4px] bg-primary" />
                        <span className="font-semibold text-muted-foreground">
                            Ocupados
                        </span>
                        <span className="ml-auto text-base font-extrabold">
                            {occupied}
                        </span>
                    </div>
                    <div className="flex items-center gap-2 text-sm">
                        <span className="size-[11px] rounded-[4px] bg-input" />
                        <span className="font-semibold text-muted-foreground">
                            Libres
                        </span>
                        <span className="ml-auto text-base font-extrabold">
                            {available}
                        </span>
                    </div>
                    <div className="mt-1.5 flex items-center gap-1.5 rounded-[10px] bg-primary-soft px-2.5 py-2 text-[12.5px] font-semibold text-primary">
                        <Sparkles className="size-3.5 shrink-0" />
                        {available} perfiles listos para vender
                    </div>
                </div>
            </div>
        </Card>
    );
}
