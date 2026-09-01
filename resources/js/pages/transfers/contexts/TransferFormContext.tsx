import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useTransferForm } from '../hooks/useTransferForm';
import type { TransferOptions } from '../types/Transfer';

type TransferFormContextType = ReturnType<typeof useTransferForm> & {
    options: TransferOptions;
};

const TransferFormContext = createContext<TransferFormContextType | undefined>(
    undefined,
);

interface TransferFormProviderProps {
    children: ReactNode;
    value: TransferFormContextType;
}

export function TransferFormProvider({
    children,
    value,
}: TransferFormProviderProps) {
    return (
        <TransferFormContext.Provider value={value}>
            {children}
        </TransferFormContext.Provider>
    );
}

export function useTransferFormContext(): TransferFormContextType {
    const context = useContext(TransferFormContext);
    if (!context) {
        throw new Error(
            'useTransferFormContext must be used within TransferFormProvider',
        );
    }
    return context;
}
