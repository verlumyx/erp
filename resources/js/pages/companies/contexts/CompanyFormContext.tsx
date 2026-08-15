import { createContext, useContext, ReactNode } from 'react';
import { useCompanyForm } from '../hooks/useCompanyForm';

type CompanyFormContextType = ReturnType<typeof useCompanyForm>;

const CompanyFormContext = createContext<CompanyFormContextType | undefined>(undefined);

interface CompanyFormProviderProps {
    children: ReactNode;
    value: CompanyFormContextType;
}

export function CompanyFormProvider({ children, value }: CompanyFormProviderProps) {
    return (
        <CompanyFormContext.Provider value={value}>
            {children}
        </CompanyFormContext.Provider>
    );
}

export function useCompanyFormContext(): CompanyFormContextType {
    const context = useContext(CompanyFormContext);
    if (!context) {
        throw new Error('useCompanyFormContext must be used within CompanyFormProvider');
    }
    return context;
}
