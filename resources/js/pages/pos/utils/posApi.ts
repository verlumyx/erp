function getCsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export async function posApi<T = unknown>(
    url: string,
    options: {
        method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
        body?: unknown;
    } = {}
): Promise<T> {
    const csrf = getCsrfToken();
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (csrf) {
        headers['X-XSRF-TOKEN'] = csrf;
    }

    if (options.body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method: options.method || 'GET',
        headers,
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    });

    const data = await response.json();

    if (!response.ok) {
        const error = new Error((data as { message?: string })?.message || `Request failed with status ${response.status}`);
        (error as unknown as { response: { data: unknown; status: number } }).response = {
            data,
            status: response.status,
        };
        throw error;
    }

    return data as T;
}
