import type { Auth } from './auth';
import type { BreadcrumbItem } from './navigation';

export * from './auth';
export * from './navigation';
export * from './ui';

export type BreadcrumbItemType = BreadcrumbItem;

export type SharedData = {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy?: unknown;
    sidebarOpen?: boolean;
    [key: string]: unknown;
};
