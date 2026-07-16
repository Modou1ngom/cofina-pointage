import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import { syncCsrfMeta } from './lib/csrf';

const appName = import.meta.env.VITE_APP_NAME || 'COFINA Pointage';

const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const requested = `./pages/${name}.vue`.replace(/\\/g, '/');
        const key = Object.keys(pages).find(
            (p) => p.replace(/\\/g, '/').toLowerCase() === requested.toLowerCase(),
        );
        if (!key) {
            throw new Error(`Page not found: ${requested}`);
        }

        return resolvePageComponent(key, pages);
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

router.on('success', (event) => {
    const token = event.detail.page.props.csrf_token as string | undefined;
    if (token) {
        syncCsrfMeta(token);
    }
});

// This will set light / dark mode on page load...
initializeTheme();
