<script setup lang="ts">
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Profil {
    id: number;
    nom: string;
    prenom: string;
    matricule: string;
    departement?: string;
    email?: string;
}

defineProps<{
    profils: Profil[];
    pointagesToday: Record<number, { arrivee?: string; statut?: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage', href: '/pointage' },
    { title: 'Équipe', href: '#' },
];
</script>

<template>
    <PointageLayout title="Pointage — Équipe" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-4">
            <h1 class="text-xl font-semibold text-[#0C447C]">Mon équipe</h1>
            <div class="overflow-hidden rounded-[10px] border border-[#e2e0d8] bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-[#FAFAF8] text-left text-[10px] font-bold uppercase text-[#888780]">
                        <tr>
                            <th class="px-4 py-2">Collaborateur</th>
                            <th class="px-4 py-2">Matricule</th>
                            <th class="px-4 py-2">Département</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in profils" :key="p.id" class="border-t border-[#F1EFE8]">
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ p.prenom }} {{ p.nom }}</div>
                                <div class="text-xs text-[#888780]">{{ p.email }}</div>
                            </td>
                            <td class="px-4 py-2 font-mono text-xs">{{ p.matricule }}</td>
                            <td class="px-4 py-2">{{ p.departement }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PointageLayout>
</template>
