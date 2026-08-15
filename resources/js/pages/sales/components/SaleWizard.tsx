import { ArrowLeft, ArrowRight, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useSaleFormContext } from '../contexts/SaleFormContext';
import { SaleClientStep } from './SaleClientStep';
import { SalePlanStep } from './SalePlanStep';
import { SaleProfilesStep } from './SaleProfilesStep';

const STEPS = [
    { number: 1, title: 'Cliente' },
    { number: 2, title: 'Plan' },
    { number: 3, title: 'Perfiles' },
];

/** Contenedor del wizard de 3 pasos: indicador, paso actual y navegación. */
export function SaleWizard() {
    const { step, setStep, canContinue, form, submit } = useSaleFormContext();

    const goNext = () => {
        if (step < 3) {
            setStep(step + 1);
        } else {
            submit();
        }
    };

    const goPrev = () => {
        if (step > 1) {
            setStep(step - 1);
        }
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex items-center gap-2">
                {STEPS.map((s, index) => (
                    <div key={s.number} className="flex flex-1 items-center">
                        <div className="flex items-center gap-2.5">
                            <span
                                className={`grid size-9 place-items-center rounded-full text-sm font-bold transition-colors ${
                                    step > s.number
                                        ? 'bg-primary text-primary-foreground'
                                        : step === s.number
                                          ? 'bg-primary text-primary-foreground'
                                          : 'bg-muted text-muted-foreground'
                                }`}
                            >
                                {step > s.number ? (
                                    <Check className="size-4" />
                                ) : (
                                    s.number
                                )}
                            </span>
                            <span
                                className={`text-sm font-semibold ${
                                    step >= s.number
                                        ? ''
                                        : 'text-muted-foreground'
                                }`}
                            >
                                {s.title}
                            </span>
                        </div>
                        {index < STEPS.length - 1 && (
                            <div
                                className={`mx-3 h-0.5 flex-1 ${
                                    step > s.number ? 'bg-primary' : 'bg-border'
                                }`}
                            />
                        )}
                    </div>
                ))}
            </div>

            <Card className="rounded-2xl p-6">
                {step === 1 && <SaleClientStep />}
                {step === 2 && <SalePlanStep />}
                {step === 3 && <SaleProfilesStep />}
            </Card>

            <div className="flex justify-between">
                <Button
                    type="button"
                    variant="outline"
                    className="rounded-[11px] bg-card font-semibold"
                    onClick={goPrev}
                    disabled={step === 1}
                >
                    <ArrowLeft />
                    Atrás
                </Button>
                <Button
                    type="button"
                    className="rounded-[11px] font-semibold"
                    onClick={goNext}
                    disabled={!canContinue || form.processing}
                >
                    {step === 3 ? 'Registrar venta' : 'Continuar'}
                    {step < 3 && <ArrowRight />}
                </Button>
            </div>
        </div>
    );
}
