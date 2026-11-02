import React, { useEffect, useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import { HelpArticle } from './components/HelpArticle';
import { HelpLayout } from './components/HelpLayout';
import { HelpOverview } from './components/HelpOverview';
import { HelpSearchResults } from './components/HelpSearchResults';
import { ALL_TOPICS, getTopicById, searchTopics } from './data/help-topics';

interface HelpPageProps {
    companyId?: string | null;
}

export default function HelpIndex({ companyId }: HelpPageProps) {
    const [activeTopicId, setActiveTopicId] = useState<string | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

    // Read initial topic from URL hash (e.g. /ayuda#inventario-kardex)
    useEffect(() => {
        const handleHashChange = () => {
            const hash = window.location.hash.replace('#', '').trim();
            if (hash && getTopicById(hash)) {
                setActiveTopicId(hash);
            } else if (!hash) {
                setActiveTopicId(null);
            }
        };

        handleHashChange();
        window.addEventListener('hashchange', handleHashChange);
        return () => window.removeEventListener('hashchange', handleHashChange);
    }, []);

    const handleSelectTopic = (topicId: string | null) => {
        setSearchQuery('');
        setActiveTopicId(topicId);
        if (topicId) {
            window.location.hash = topicId;
        } else {
            window.location.hash = '';
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Filter search results
    const searchResults = useMemo(() => {
        return searchTopics(searchQuery);
    }, [searchQuery]);

    const activeTopic = activeTopicId ? getTopicById(activeTopicId) : null;

    const pageTitle = activeTopic
        ? `${activeTopic.title} - Centro de Ayuda`
        : 'Guía de Usuario & Centro de Ayuda';

    return (
        <HelpLayout
            companyId={companyId}
            activeTopicId={activeTopicId}
            onSelectTopic={handleSelectTopic}
            searchQuery={searchQuery}
            onSearchChange={setSearchQuery}
        >
            <Head title={pageTitle} />

            {searchQuery.trim() ? (
                <HelpSearchResults
                    query={searchQuery}
                    results={searchResults}
                    onSelectTopic={handleSelectTopic}
                    onClearSearch={() => setSearchQuery('')}
                />
            ) : activeTopic ? (
                <HelpArticle
                    topic={activeTopic}
                    companyId={companyId}
                    onSelectTopic={handleSelectTopic}
                />
            ) : (
                <HelpOverview
                    onSelectTopic={handleSelectTopic}
                />
            )}
        </HelpLayout>
    );
}
