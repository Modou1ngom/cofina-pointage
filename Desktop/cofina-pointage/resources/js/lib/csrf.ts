/** Met à jour la balise meta (utilisée par les fetch manuels). */
export function syncCsrfMeta(token: string): void {
    if (!token) {
        return;
    }
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    if (meta) {
        meta.content = token;
    }
}

/** Lit le jeton depuis meta, puis cookie XSRF-TOKEN (fallback Laravel). */
export function readCsrfTokenFromDom(): string {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content?.trim();
    if (meta) {
        return meta;
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    if (match?.[1]) {
        return decodeURIComponent(match[1]);
    }

    return '';
}
