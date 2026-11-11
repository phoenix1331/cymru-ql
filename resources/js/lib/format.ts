export function formatBytes(bytes: number): string {
    return bytes < 1024 ? `${bytes} B` : `${(bytes / 1024).toFixed(1)} kB`;
}

export function formatMs(ms: number): string {
    return ms < 1000 ? `${Math.round(ms)} ms` : `${(ms / 1000).toFixed(2)} s`;
}

/** Pretty prints a GraphQL request body (query plus variables) for display. */
export function formatGraphQLBody(body: string): { query: string; variables: string | null } {
    try {
        const parsed = JSON.parse(body) as { query?: string; variables?: Record<string, unknown> };
        const variables = parsed.variables && Object.keys(parsed.variables).length > 0 ? JSON.stringify(parsed.variables, null, 2) : null;
        return { query: (parsed.query ?? '').trim(), variables };
    } catch {
        return { query: body, variables: null };
    }
}
