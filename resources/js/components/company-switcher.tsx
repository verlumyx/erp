import { router, usePage } from '@inertiajs/react';
import { Building2, ChevronsUpDown, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import company from '@/routes/company';

interface Company {
    id: string;
    name: string;
}

interface PageProps {
    currentCompany?: Company | null;
    userCompanies?: Company[];
    [key: string]: unknown;
}

export function CompanySwitcher() {
    const { currentCompany, userCompanies = [] } = usePage<PageProps>().props;

    const switchCompany = (companyId: string) => {
        if (currentCompany?.id === companyId) {
            return;
        }
        router.post(
            company.switch().url,
            { company_id: companyId },
            { preserveScroll: true },
        );
    };

    if (!currentCompany && userCompanies.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="flex h-10 max-w-[200px] items-center gap-2 rounded-[11px] bg-card px-3.5 font-semibold"
                >
                    <Building2 className="h-4 w-4 shrink-0 text-muted-foreground" />
                    <span className="truncate text-sm font-semibold">
                        {currentCompany?.name ?? 'Sin empresa'}
                    </span>
                    <ChevronsUpDown className="h-3 w-3 shrink-0 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Mis empresas
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {userCompanies.map((c) => (
                    <DropdownMenuItem
                        key={c.id}
                        onClick={() => switchCompany(c.id)}
                        className="flex cursor-pointer items-center gap-2"
                    >
                        <Building2 className="h-4 w-4 text-muted-foreground" />
                        <span className="flex-1 truncate">{c.name}</span>
                        {currentCompany?.id === c.id && (
                            <Check className="h-4 w-4 text-primary" />
                        )}
                    </DropdownMenuItem>
                ))}
                {userCompanies.length === 0 && (
                    <DropdownMenuItem disabled>
                        <span className="text-xs text-muted-foreground">
                            Sin empresas asignadas
                        </span>
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
