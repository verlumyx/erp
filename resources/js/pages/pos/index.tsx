import { useState, useEffect, useRef, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { posApi } from './utils/posApi';
import { PosSessionProvider, usePosSession } from './context/PosSessionContext';
import { PosCartProvider, usePosCart } from './context/PosCartContext';
import { PosHeader } from './components/PosHeader';
import { PosSearchInput } from './components/PosSearchInput';
import { PosCatalogGrid } from './components/PosCatalogGrid';
import { PosCart } from './components/PosCart';
import { PosTotals } from './components/PosTotals';
import { PosPaymentModal } from './components/PosPaymentModal';
import { PosHoldSalesModal } from './components/PosHoldSalesModal';
import { PosCashMovementModal } from './components/PosCashMovementModal';
import { PosCloseSessionModal } from './components/PosCloseSessionModal';
import { PosReceiptModal } from './components/PosReceiptModal';
import { PosClientModal } from './components/PosClientModal';
import { PosDiscountModal } from './components/PosDiscountModal';
import { PosReadingXModal } from './components/PosReadingXModal';
import { PosShortcutsModal } from './components/PosShortcutsModal';
import type { PosCategory, PosClient, PosItem, PosSale, PosSession } from './types/Pos';

interface PosTerminalPageProps {
    session: PosSession;
    categories: PosCategory[];
    items: PosItem[];
    rate: number;
    heldSales: { data: PosSale[] } | PosSale[];
    clients: PosClient[];
}

function PosTerminalInner({
    categories,
    items,
    initialHeldSales,
    clients,
}: {
    categories: PosCategory[];
    items: PosItem[];
    initialHeldSales: PosSale[];
    clients: PosClient[];
}) {
    const { session, companyId } = usePosSession();
    const {
        lines,
        selectedLineIndex,
        addItem,
        incrementQuantity,
        removeLine,
        clearCart,
        client,
        subtotal,
        discountAmount,
        taxAmount,
        total,
    } = usePosCart();

    const [searchQuery, setSearchQuery] = useState<string>('');
    const [heldSales, setHeldSales] = useState<PosSale[]>(initialHeldSales);

    // Modal states
    const [isPaymentOpen, setIsPaymentOpen] = useState(false);
    const [isHoldListOpen, setIsHoldListOpen] = useState(false);
    const [isCashMovementOpen, setIsCashMovementOpen] = useState(false);
    const [isCloseSessionOpen, setIsCloseSessionOpen] = useState(false);
    const [isClientOpen, setIsClientOpen] = useState(false);
    const [isDiscountOpen, setIsDiscountOpen] = useState(false);
    const [isReadingXOpen, setIsReadingXOpen] = useState(false);
    const [isShortcutsOpen, setIsShortcutsOpen] = useState(false);
    const [completedSale, setCompletedSale] = useState<PosSale | null>(null);
    const [isReceiptOpen, setIsReceiptOpen] = useState(false);

    const searchInputRef = useRef<HTMLInputElement>(null);

    // Check if any modal is currently visible
    const isAnyModalOpen =
        isPaymentOpen ||
        isHoldListOpen ||
        isCashMovementOpen ||
        isCloseSessionOpen ||
        isClientOpen ||
        isDiscountOpen ||
        isReadingXOpen ||
        isShortcutsOpen ||
        isReceiptOpen;

    // Refresh held sales from server
    const fetchHeldSales = useCallback(async () => {
        try {
            const resp = await posApi<{ data?: PosSale[] } | PosSale[]>(`/${companyId}/pos/held/${session.id}`);
            const data = (resp as { data?: PosSale[] })?.data || resp;
            if (Array.isArray(data)) {
                setHeldSales(data);
            }
        } catch {
            // Silently ignore or retry
        }
    }, [companyId, session.id]);

    // Hold current sale
    const handleHoldCurrentSale = useCallback(async () => {
        if (lines.length === 0) return;

        try {
            const payload = {
                pos_session_id: session.id,
                client_id: client?.id || null,
                notes: `Venta pausada ${new Date().toLocaleTimeString()}`,
                lines: lines.map((line, idx) => ({
                    item_id: line.item.id,
                    measurement_unit_id: line.item.measurement_unit_id || null,
                    quantity: line.quantity,
                    unit_price: line.unit_price,
                    discount_percent: line.discount_percent,
                    discount_amount: line.discount_amount,
                    tax_id: line.tax_id || null,
                    tax_percent: line.tax_percent,
                    tax_amount: line.tax_amount,
                    subtotal: line.subtotal,
                    total: line.total,
                })),
            };

            await posApi(`/${companyId}/pos/hold`, {
                method: 'POST',
                body: payload,
            });
            clearCart();
            await fetchHeldSales();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } } };
            alert(error.response?.data?.message || 'Error al poner la venta en espera');
        }
    }, [lines, session.id, client, companyId, clearCart, fetchHeldSales]);

    // Open cash drawer (simulate ESC/POS kick or alert)
    const handleOpenCashDrawer = useCallback(() => {
        // In physical retail, a silent 0-byte or pulse command can be sent to printer service.
        // For UI feedback, show toast/notification.
        const audio = new AudioContext();
        const osc = audio.createOscillator();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(800, audio.currentTime);
        osc.connect(audio.destination);
        osc.start();
        osc.stop(audio.currentTime + 0.15);
    }, []);

    // Global keyboard shortcuts
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            // If user is typing inside an input and presses Space, allow normal typing
            const activeTag = document.activeElement?.tagName?.toLowerCase();
            const isTyping = activeTag === 'input' || activeTag === 'textarea';

            if (e.key === 'F1') {
                e.preventDefault();
                searchInputRef.current?.focus();
                searchInputRef.current?.select();
                return;
            }

            if (e.key === 'F2' || (!isTyping && !isAnyModalOpen && e.code === 'Space')) {
                e.preventDefault();
                if (lines.length > 0 && !isAnyModalOpen) {
                    setIsPaymentOpen(true);
                }
                return;
            }

            if (e.key === 'F3') {
                e.preventDefault();
                if (lines.length > 0 && !isAnyModalOpen) {
                    setIsDiscountOpen(true);
                }
                return;
            }

            if (e.key === 'F4') {
                e.preventDefault();
                if (!isAnyModalOpen) {
                    setIsClientOpen(true);
                }
                return;
            }

            if (e.key === 'F5') {
                e.preventDefault();
                if (lines.length > 0 && !isAnyModalOpen) {
                    incrementQuantity(selectedLineIndex);
                }
                return;
            }

            if (e.key === 'F6') {
                e.preventDefault();
                if (lines.length > 0 && !isAnyModalOpen) {
                    removeLine(selectedLineIndex);
                }
                return;
            }

            if (e.key === 'F8') {
                e.preventDefault();
                if (lines.length > 0 && !isAnyModalOpen) {
                    handleHoldCurrentSale();
                }
                return;
            }

            if (e.key === 'F9') {
                e.preventDefault();
                if (!isAnyModalOpen) {
                    setIsHoldListOpen(true);
                }
                return;
            }

            if (e.key === 'F10') {
                e.preventDefault();
                if (!isAnyModalOpen) {
                    setIsCashMovementOpen(true);
                }
                return;
            }

            if (e.key === 'F11') {
                e.preventDefault();
                if (!isAnyModalOpen) {
                    setIsReadingXOpen(true);
                }
                return;
            }

            if (e.key === 'F12') {
                e.preventDefault();
                handleOpenCashDrawer();
                return;
            }

            if (e.key === 'Escape') {
                if (!isAnyModalOpen && lines.length > 0) {
                    if (confirm('¿Deseas vaciar el carrito actual?')) {
                        clearCart();
                    }
                }
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [
        isAnyModalOpen,
        lines,
        selectedLineIndex,
        incrementQuantity,
        removeLine,
        clearCart,
        handleHoldCurrentSale,
        handleOpenCashDrawer,
    ]);

    // Handle barcode scanner enter in search input
    const handleSearchEnter = () => {
        if (!searchQuery.trim()) return;

        const query = searchQuery.trim().toLowerCase();
        // Exact barcode match first, then code, sku, name
        const matched =
            items.find((i) => i.barcode && i.barcode.toLowerCase() === query) ||
            items.find((i) => i.code.toLowerCase() === query) ||
            items.find((i) => i.sku && i.sku.toLowerCase() === query) ||
            items.find((i) => i.name.toLowerCase() === query);

        if (matched) {
            // Found exact item: auto add to cart
            addItem(matched);
            setSearchQuery('');
        }
    };

    const handleSaleCompleted = (sale: PosSale) => {
        setIsPaymentOpen(false);
        setCompletedSale(sale);
        setIsReceiptOpen(true);
    };

    return (
        <div className="flex h-screen w-screen flex-col overflow-hidden bg-background select-none font-sans">
            <Head title={`POS - ${session.pos_register_name || 'Terminal de Ventas'}`} />

            {/* Header */}
            <PosHeader
                onOpenCashMovement={() => setIsCashMovementOpen(true)}
                onOpenReadingX={() => setIsReadingXOpen(true)}
                onOpenCloseSession={() => setIsCloseSessionOpen(true)}
                onOpenShortcuts={() => setIsShortcutsOpen(true)}
            />

            {/* Main Content Area: 2 Columns */}
            <main className="flex flex-1 overflow-hidden p-3 gap-3">
                {/* Left Column: Search & Catalog (60%) */}
                <section className="flex flex-col flex-1 h-full min-w-0 gap-3">
                    {/* Search Bar with F1 hint */}
                    <div className="shrink-0">
                        <PosSearchInput
                            ref={searchInputRef}
                            value={searchQuery}
                            onChange={setSearchQuery}
                            onEnter={handleSearchEnter}
                        />
                    </div>

                    {/* Catalog Grid */}
                    <div className="flex-1 min-h-0">
                        <PosCatalogGrid
                            items={items}
                            categories={categories}
                            searchQuery={searchQuery}
                        />
                    </div>
                </section>

                {/* Right Column: Cart & Totals (40%) */}
                <aside className="flex flex-col w-[420px] lg:w-[460px] xl:w-[500px] shrink-0 h-full gap-3">
                    {/* Cart Items Table */}
                    <div className="flex-1 min-h-0">
                        <PosCart onOpenClientModal={() => setIsClientOpen(true)} />
                    </div>

                    {/* Totals & Action Buttons */}
                    <div className="shrink-0">
                        <PosTotals
                            heldSalesCount={heldSales.length}
                            onOpenPaymentModal={() => setIsPaymentOpen(true)}
                            onHoldCurrentSale={handleHoldCurrentSale}
                            onOpenHeldModal={() => setIsHoldListOpen(true)}
                            onOpenDiscountModal={() => setIsDiscountOpen(true)}
                        />
                    </div>
                </aside>
            </main>

            {/* Modals */}
            <PosPaymentModal
                isOpen={isPaymentOpen}
                onClose={() => setIsPaymentOpen(false)}
                onSaleCompleted={handleSaleCompleted}
            />

            <PosHoldSalesModal
                isOpen={isHoldListOpen}
                onClose={() => setIsHoldListOpen(false)}
                heldSales={heldSales}
                onRefreshHeldSales={fetchHeldSales}
            />

            <PosCashMovementModal
                isOpen={isCashMovementOpen}
                onClose={() => setIsCashMovementOpen(false)}
            />

            <PosCloseSessionModal
                isOpen={isCloseSessionOpen}
                onClose={() => setIsCloseSessionOpen(false)}
            />

            <PosReceiptModal
                isOpen={isReceiptOpen}
                onClose={() => setIsReceiptOpen(false)}
                sale={completedSale}
            />

            <PosClientModal
                isOpen={isClientOpen}
                onClose={() => setIsClientOpen(false)}
                clients={clients}
            />

            <PosDiscountModal
                isOpen={isDiscountOpen}
                onClose={() => setIsDiscountOpen(false)}
            />

            <PosReadingXModal
                isOpen={isReadingXOpen}
                onClose={() => setIsReadingXOpen(false)}
            />

            <PosShortcutsModal
                isOpen={isShortcutsOpen}
                onClose={() => setIsShortcutsOpen(false)}
            />
        </div>
    );
}

export default function PosTerminalPage({
    session,
    categories,
    items,
    rate,
    heldSales,
    clients,
}: PosTerminalPageProps) {
    const { currentCompany } = usePage<{ currentCompany?: { id: string; name: string } }>().props;
    const companyId = currentCompany?.id || '';

    const normalizedHeldSales: PosSale[] = Array.isArray(heldSales)
        ? heldSales
        : (heldSales as { data: PosSale[] })?.data || [];

    const defaultClient = clients.length > 0 ? clients[0] : null;

    return (
        <PosSessionProvider session={session} rate={rate} companyId={companyId}>
            <PosCartProvider defaultClient={defaultClient} rate={rate}>
                <PosTerminalInner
                    categories={categories}
                    items={items}
                    initialHeldSales={normalizedHeldSales}
                    clients={clients}
                />
            </PosCartProvider>
        </PosSessionProvider>
    );
}
