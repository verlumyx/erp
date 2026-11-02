export interface HelpStep {
    number: number;
    title: string;
    description: string;
    hint?: string;
}

export interface HelpKeyConcept {
    term: string;
    explanation: string;
    example?: string;
}

export interface HelpTip {
    type: 'tip' | 'warning' | 'info';
    text: string;
}

export interface HelpFaq {
    question: string;
    answer: string;
}

export interface HelpTopic {
    id: string;
    moduleId: string;
    moduleName: string;
    moduleIcon: string;
    title: string;
    subtitle: string;
    summary: string;
    whatIsIt: string;
    whyItMatters: string;
    keyConcepts: HelpKeyConcept[];
    stepByStep: HelpStep[];
    tips: HelpTip[];
    relatedModules: {
        name: string;
        topicId: string;
        relationship: string;
    }[];
    systemPath?: string;
    faqs?: HelpFaq[];
    keywords: string[];
}

export interface HelpModuleGroup {
    id: string;
    name: string;
    icon: string;
    badge: string;
    description: string;
    topics: HelpTopic[];
}
