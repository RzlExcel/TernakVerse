export interface Citation {
    chunk_id: number;
    document_title: string;
    source: string;
    published_year?: number;
    category?: string;
    excerpt?: string;
}

export interface ToolStatus {
    tool: string;
    status: 'running' | 'completed' | 'failed';
    label: string;
    result?: any;
}

export interface ChatMessage {
    id: string | number;
    role: 'user' | 'assistant' | 'system' | 'tool';
    content: string;
    tool_calls?: any[];
    citations?: Citation[];
    feedback_rating?: number;
    timestamp?: string;
    isStreaming?: boolean;
    toolStatuses?: ToolStatus[];
}

export interface SsePayload {
    event: 'metadata' | 'tool_start' | 'tool_end' | 'chunk' | 'citation' | 'done' | 'error';
    data: any;
}

export interface QuickPrompt {
    label: string;
    prompt: string;
    category?: 'zonasi' | 'kasus' | 'klinis' | 'rph' | 'populasi';
}
