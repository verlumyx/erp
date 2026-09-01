import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useEntryForm } from '../hooks/useEntryForm';
import type { EntryOptions } from '../types/Entry';

type EntryFormContextType = ReturnType<typeof useEntryForm> & {
    options: EntryOptions;
};

const EntryFormContext = createContext<EntryFormContextType | undefined>(
    undefined,
);

interface EntryFormProviderProps {
    children: ReactNode;
    value: EntryFormContextType;
}

export function EntryFormProvider({ children, value }: EntryFormProviderProps) {
    return (
        <EntryFormContext.Provider value={value}>
            {children}
        </EntryFormContext.Provider>
    );
}

export function useEntryFormContext(): EntryFormContextType {
    const context = useContext(EntryFormContext);
    if (!context) {
        throw new Error(
            'useEntryFormContext must be used within EntryFormProvider',
        );
    }
    return context;
}
