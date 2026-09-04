import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useImportForm } from '../hooks/useImportForm';

type ImportFormContextType = ReturnType<typeof useImportForm>;

const ImportFormContext = createContext<ImportFormContextType | undefined>(
    undefined,
);

interface ImportFormProviderProps {
    children: ReactNode;
    value: ImportFormContextType;
}

export function ImportFormProvider({
    children,
    value,
}: ImportFormProviderProps) {
    return (
        <ImportFormContext.Provider value={value}>
            {children}
        </ImportFormContext.Provider>
    );
}

export function useImportFormContext(): ImportFormContextType {
    const context = useContext(ImportFormContext);

    if (!context) {
        throw new Error(
            'useImportFormContext must be used within ImportFormProvider',
        );
    }

    return context;
}
