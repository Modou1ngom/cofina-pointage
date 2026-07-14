import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    /** RH / Admin : déclarations pointage en attente de validation RH */
    pointageRhDeclarationsPendingCount?: number;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    /** Libellé de section (ex. « RH ») dans un sous-menu, non cliquable */
    variant?: 'section-label';
    href?: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    /** Classes Tailwind pour colorer l’icône (ex. urgence en rouge) */
    iconClass?: string;
    /** Pastille numérique (ex. Centre d’urgence) */
    badge?: number;
    isActive?: boolean;
    items?: NavItem[];
    onClick?: () => void;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    csrf_token?: string;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
