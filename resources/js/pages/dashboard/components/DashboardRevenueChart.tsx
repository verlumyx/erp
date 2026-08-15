import { useState } from 'react';
import { Card } from '@/components/ui/card';
import { money } from '@/lib/format';
import type { DashboardRevenuePoint } from '../types';

interface Props {
    data: DashboardRevenuePoint[];
}

/**
 * Barras agrupadas por mes: ingreso (azul) vs. costo (rojo) de los últimos 6
 * meses. La escala usa el mayor valor entre todos los ingresos y costos, de modo
 * que las barras son comparables y los meses con pérdida (costo > ingreso) se
 * ven con claridad. El tooltip destaca la ganancia neta del mes.
 */
export function DashboardRevenueChart({ data }: Props) {
    const max = Math.max(1, ...data.flatMap((m) => [m.income, m.expense]));
    const [hi, setHi] = useState(data.length - 1);

    return (
        <Card className="gap-0 rounded-2xl py-0">
            <div className="flex items-start justify-between gap-3 p-5 pb-0">
                <div>
                    <div className="text-base font-bold tracking-tight">
                        Ingresos vs. costo
                    </div>
                    <div className="mt-0.5 text-[13px] text-muted-foreground">
                        Últimos 6 meses · ganancia neta destacada
                    </div>
                </div>
                <div className="flex gap-3.5 text-[12.5px] font-semibold text-muted-foreground">
                    <span className="inline-flex items-center gap-1.5">
                        <i className="size-[9px] rounded-[3px] bg-primary" />
                        Ingreso
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <i className="size-[9px] rounded-[3px] bg-destructive" />
                        Costo
                    </span>
                </div>
            </div>
            <div className="flex h-[220px] items-end gap-3.5 px-5 pt-6 pb-5">
                {data.map((m, i) => {
                    const incomeH = (m.income / max) * 100;
                    const costH = (m.expense / max) * 100;
                    const on = i === hi;
                    return (
                        <div
                            key={`${m.month}-${i}`}
                            className="relative flex h-full flex-1 flex-col items-center gap-2.5"
                            onMouseEnter={() => setHi(i)}
                        >
                            <div
                                className={`pointer-events-none absolute -top-1.5 flex -translate-y-full flex-col items-center rounded-[9px] bg-foreground px-2.5 py-1.5 leading-tight whitespace-nowrap text-white transition-opacity ${
                                    on ? 'opacity-100' : 'opacity-0'
                                }`}
                            >
                                <span className="text-[13px] font-bold">
                                    {money(m.profit)}
                                </span>
                                <small className="text-[9.5px] font-semibold tracking-wider uppercase opacity-70">
                                    ganancia neta
                                </small>
                                <span className="absolute -bottom-1 left-1/2 size-2 -translate-x-1/2 rotate-45 bg-foreground" />
                            </div>
                            <div
                                className={`flex w-full max-w-16 flex-1 items-end justify-center gap-1.5 transition-opacity ${
                                    on ? '' : 'opacity-55'
                                }`}
                            >
                                <div
                                    className="w-1/2 rounded-t-[6px] rounded-b-[3px] bg-primary"
                                    style={{ height: `${incomeH}%` }}
                                    title={`Ingreso ${money(m.income)}`}
                                />
                                <div
                                    className="w-1/2 rounded-t-[6px] rounded-b-[3px] bg-destructive"
                                    style={{ height: `${costH}%` }}
                                    title={`Costo ${money(m.expense)}`}
                                />
                            </div>
                            <div
                                className={`text-[12.5px] font-semibold ${on ? 'text-foreground' : 'text-muted-foreground'}`}
                            >
                                {m.month}
                            </div>
                        </div>
                    );
                })}
            </div>
        </Card>
    );
}
