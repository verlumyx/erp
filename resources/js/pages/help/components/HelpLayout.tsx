import React, { ReactNode, useState } from 'react';
import { HelpHeader } from './HelpHeader';
import { HelpSidebar } from './HelpSidebar';

interface HelpLayoutProps {
    children: ReactNode;
    companyId?: string | null;
    activeTopicId: string | null;
    onSelectTopic: (topicId: string | null) => void;
    searchQuery: string;
    onSearchChange: (query: string) => void;
}

export function HelpLayout({
    children,
    companyId,
    activeTopicId,
    onSelectTopic,
    searchQuery,
    onSearchChange,
}: HelpLayoutProps) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const handlePrint = () => {
        window.print();
    };

    return (
        <div className="min-h-screen bg-background text-foreground antialiased selection:bg-primary/20 selection:text-primary">
            {/* Top Navigation - Fixed at top of viewport */}
            <div className="print:hidden">
                <HelpHeader
                    companyId={companyId}
                    searchQuery={searchQuery}
                    onSearchChange={onSearchChange}
                    onPrint={handlePrint}
                    mobileMenuOpen={mobileMenuOpen}
                    onToggleMobileMenu={() => setMobileMenuOpen(!mobileMenuOpen)}
                />
            </div>

            <div className="flex min-h-[calc(100vh-4rem)] pt-16 print:pt-0">
                {/* Desktop Sidebar - Sticky right below fixed header (top-16) */}
                <div className="hidden w-72 shrink-0 border-r border-border/80 md:block print:hidden sticky top-16 h-[calc(100vh-4rem)]">
                    <HelpSidebar
                        activeTopicId={activeTopicId}
                        onSelectTopic={onSelectTopic}
                    />
                </div>

                {/* Mobile Drawer */}
                {mobileMenuOpen && (
                    <div className="fixed inset-0 z-50 flex md:hidden print:hidden pt-16">
                        <div
                            className="fixed inset-0 bg-background/80 backdrop-blur-sm"
                            onClick={() => setMobileMenuOpen(false)}
                        />
                        <div className="relative z-10 flex h-[calc(100vh-4rem)] w-80 max-w-[85vw] flex-col bg-card shadow-2xl border-r border-border">
                            <HelpSidebar
                                activeTopicId={activeTopicId}
                                onSelectTopic={onSelectTopic}
                                onCloseMobile={() => setMobileMenuOpen(false)}
                            />
                        </div>
                    </div>
                )}

                {/* Main Content Area */}
                <main className="flex-1 px-4 py-8 sm:px-8 lg:px-12 print:px-0 print:py-0">
                    {children}
                </main>
            </div>

            {/* Custom Styles */}
            <style>{`
                html {
                    scroll-padding-top: 5rem;
                }
                @media print {
                    header, aside, nav, .print\\:hidden, button, a[href^="/"] {
                        display: none !important;
                    }
                    div {
                        padding-top: 0 !important;
                    }
                    body, main {
                        background: white !important;
                        color: #111827 !important;
                        font-size: 11pt !important;
                        line-height: 1.5 !important;
                        padding: 0 !important;
                        margin: 0 !important;
                        width: 100% !important;
                    }
                    article {
                        max-width: 100% !important;
                        padding: 0 !important;
                    }
                    .border, .border-border, .border-border\\/80 {
                        border-color: #e5e7eb !important;
                    }
                    .bg-card, .bg-muted, .bg-primary\\/5 {
                        background: #f9fafb !important;
                    }
                    h1 {
                        font-size: 20pt !important;
                        color: #111827 !important;
                    }
                    h2, h3, h4 {
                        color: #111827 !important;
                        page-break-after: avoid;
                    }
                    p, li {
                        orphans: 3;
                        widows: 3;
                    }
                }
            `}</style>
        </div>
    );
}
