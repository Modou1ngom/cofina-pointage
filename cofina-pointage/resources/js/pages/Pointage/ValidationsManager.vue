<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Decl {
    id: number;
    type: string;
    date_concernee: string;
    motif: string;
    statut?: string;
    justificatif_path: string | null;
    user?: { name: string; email: string };
}

defineProps<{
    pending: Decl[];
    history: Decl[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage', href: '/pointage' },
    { title: 'Validations manager', href: '#' },
];

function decide(id: number, accept: boolean) {
    router.post(`/pointage/declarations/${id}/decision-manager`, { accept, comment: '' }, { preserveScroll: true });
}
</script>

<template>
    <PointageLayout title="Validations manager" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-8">
            <h1 class="text-xl font-semibold text-[#0C447C]">Déclarations à valider (manager)</h1>

            <div class="overflow-hidden rounded-[10px] border border-[#e2e0d8] bg-white">
                <div class="border-b border-[#e2e0d8] px-4 py-3 text-sm font-semibold">En attente</div>
                <table class="w-full text-sm">
                    <thead class="bg-[#FAFAF8] text-left text-[10px] font-bold uppercase text-[#888780]">
                        <tr>
                            <th class="px-4 py-2">Employé</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Motif</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in pending" :key="d.id" class="border-t border-[#F1EFE8]">
                            <td class="px-4 py-2">{{ d.user?.name }}</td>
                            <td class="px-4 py-2">{{ d.type }}</td>
                            <td class="px-4 py-2">{{ d.date_concernee }}</td>
                            <td class="px-4 py-2">{{ d.motif }}</td>
                            <td class="space-x-2 px-4 py-2">
                                <button type="button" class="rounded bg-[#EAF3DE] px-2 py-1 text-xs text-[#3B6D11]" @click="decide(d.id, true)">
                                    Valider
                                </button>
                                <button type="button" class="rounded bg-[#FCEBEB] px-2 py-1 text-xs text-[#A32D2D]" @click="decide(d.id, false)">
                                    Rejeter
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!pending?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-[#888780]">Rien en attente.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-[10px] border border-[#e2e0d8] bg-white">
                <div class="border-b border-[#e2e0d8] px-4 py-3 text-sm font-semibold">Historique récent</div>
                <table class="w-full text-sm">
                    <thead class="bg-[#FAFAF8] text-left text-[10px] font-bold uppercase text-[#888780]">
                        <tr>
                            <th class="px-4 py-2">Employé</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in history" :key="'h-' + d.id" class="border-t border-[#F1EFE8]">
                            <td class="px-4 py-2">{{ d.user?.name }}</td>
                            <td class="px-4 py-2">{{ d.type }}</td>
                            <td class="px-4 py-2">{{ d.statut }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PointageLayout>
</template>
