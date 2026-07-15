<script setup lang="ts">
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    pointages: { id: number; clocked_at: string; statut: string; user?: { name: string } }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage', href: '/pointage' },
    { title: 'Retards', href: '#' },
];
</script>

<template>
    <PointageLayout title="Retards & absences" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl space-y-4">
            <h1 class="text-xl font-semibold text-[#0C447C]">Retards enregistrés</h1>
            <div class="overflow-hidden rounded-[10px] border border-[#e2e0d8] bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-[#FAFAF8] text-left text-[10px] font-bold uppercase text-[#888780]">
                        <tr>
                            <th class="px-4 py-2">Collaborateur</th>
                            <th class="px-4 py-2">Horodatage</th>
                            <th class="px-4 py-2">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in pointages" :key="p.id" class="border-t border-[#F1EFE8]">
                            <td class="px-4 py-2">{{ p.user?.name }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ p.clocked_at }}</td>
                            <td class="px-4 py-2">{{ p.statut }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PointageLayout>
</template>
