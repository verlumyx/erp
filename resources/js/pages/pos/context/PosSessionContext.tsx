import { createContext, useContext, ReactNode } from 'react';
import type { PosSession } from '../types/Pos';

interface PosSessionContextType {
    session: PosSession;
    rate: number;
    companyId: string;
}

const PosSessionContext = createContext<PosSessionContextType | undefined>(undefined);

export function PosSessionProvider({
    session,
    rate,
    companyId,
    children,
}: {
    session: PosSession;
    rate: number;
    companyId: string;
    children: ReactNode;
}) {
    return (
        <PosSessionContext.Provider value={{ session, rate, companyId }}>
            {children}
        </PosSessionContext.Provider>
    );
}

export function usePosSession(): PosSessionContextType {
    const context = useContext(PosSessionContext);
    if (!context) {
        throw new Error('usePosSession must be used within a PosSessionProvider');
    }
    return context;
}
