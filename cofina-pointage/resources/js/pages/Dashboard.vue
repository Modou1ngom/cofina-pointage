<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Timer, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    statsProfils?: {
        total: number;
        actifs: number;
        inactifs: number;
    } | null;
    userRole?: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de matière',
        href: dashboard().url,
    },
];

const dashboardTitle = computed(() => {
    const role = props.userRole || 'user';
    const titles: Record<string, string> = {
        admin: 'Tableau de matière — Administration',
        rh: 'Tableau de matière — Ressources Humaines',
        user: 'Tableau de matière',
    };
    return titles[role] || 'Tableau de matière';
});
</script>

<template>
    <Head title="Tableau de matière" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-gradient-to-br from-gray-50 via-white to-gray-50 p-8">
            <div class="mb-4">
                <div class="mb-3 flex items-center gap-3">
                    <div class="h-1 w-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-500"></div>
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900">
                        {{ dashboardTitle }}
                    </h1>
                </div>
                <p class="ml-15 text-base font-medium text-gray-600">
                    Bienvenue sur COFINA Pointage & Présence
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <Link
                    :href="userRole === 'rh' ? '/pointage/rh/presence/recuperation-pointages' : '/pointage'"
                    class="group relative overflow-hidden rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-6 shadow-lg transition-all hover:scale-[1.02] hover:shadow-xl"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-blue-700">Module principal</p>
                            <p class="text-xl font-bold text-blue-900">Pointage & Présence</p>
                            <p class="mt-2 text-sm text-blue-800/80">Accéder au module pointage</p>
                        </div>
                        <div class="rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-4 shadow-lg">
                            <Timer class="h-7 w-7 text-white" />
                        </div>
                    </div>
                </Link>

                <div
                    v-if="statsProfils"
                    class="relative overflow-hidden rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100 p-6 shadow-lg"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-purple-700">Enrôlements staff</p>
                            <p class="text-3xl font-bold text-purple-900">{{ statsProfils.actifs }}</p>
                            <p class="mt-1 text-sm text-purple-800/80">
                                actifs sur {{ statsProfils.total }} profils
                            </p>
                        </div>
                        <div class="rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 p-4 shadow-lg">
                            <Users class="h-7 w-7 text-white" />
                        </div>
                    </div>
                    <Link
                        href="/profils"
                        class="mt-4 inline-block text-sm font-semibold text-purple-800 underline hover:no-underline"
                    >
                        Gérer les enrôlements →
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
