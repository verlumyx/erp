import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useAccountForm } from '../hooks/useAccountForm';

type AccountFormContextType = ReturnType<typeof useAccountForm>;

const AccountFormContext = createContext<AccountFormContextType | undefined>(
    undefined,
);

interface AccountFormProviderProps {
    children: ReactNode;
    value: AccountFormContextType;
}

export function AccountFormProvider({
    children,
    value,
}: AccountFormProviderProps) {
    return (
        <AccountFormContext.Provider value={value}>
            {children}
        </AccountFormContext.Provider>
    );
}

export function useAccountFormContext(): AccountFormContextType {
    const context = useContext(AccountFormContext);
    if (!context) {
        throw new Error(
            'useAccountFormContext must be used within AccountFormProvider',
        );
    }
    return context;
}
