import { createContext, useContext, type ReactNode } from 'react';
import { useLoadPlanForm } from '../hooks/useLoadPlanForm';
import type { LoadPlan, LoadPlanOptions } from '../types/LoadPlan';

type LoadPlanFormContextType = ReturnType<typeof useLoadPlanForm>;

const LoadPlanFormContext = createContext<LoadPlanFormContextType | null>(null);

interface LoadPlanFormProviderProps {
    children: ReactNode;
    mode: 'create' | 'edit';
    initialData?: LoadPlan;
    options: LoadPlanOptions;
    onSuccess?: () => void;
}

export function LoadPlanFormProvider({
    children,
    mode,
    initialData,
    options,
    onSuccess,
}: LoadPlanFormProviderProps) {
    const form = useLoadPlanForm({
        mode,
        initialData,
        options,
        onSuccess,
    });

    return (
        <LoadPlanFormContext.Provider value={form}>
            {children}
        </LoadPlanFormContext.Provider>
    );
}

export function useLoadPlanFormContext() {
    const context = useContext(LoadPlanFormContext);
    if (!context) {
        throw new Error(
            'useLoadPlanFormContext must be used within a LoadPlanFormProvider',
        );
    }
    return context;
}
