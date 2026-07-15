<script setup lang="ts">
import PointageLayout from '@/layouts/pointage/PointageLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    logs: {
        data: {
            id: number;
            action: string;
            description?: string | null;
            severity: string;
            ip_address?: string | null;
            created_at: string;
            actor?: { name: string } | null;
            agence?: { nom: string } | null;
        }[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pointage & Présence', href: '/pointage/rh/presence/recuperation-pointages' },
    { title: 'Logs', href: '#' },
];

const sev = (s: string) =>
    s === 'fraude'
        ? 'bg-[#FCEBEB] text-[#A32D2D]'
        : s === 'alerte'
          ? 'bg-[#FAEEDA] text-[#854F0B]'
          : 'bg-[#EAF3DE] text-[#3B6D11]';
</script>

<template>
    <PointageLayout title="Logs pointage" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-4">
            <h1 class="text-xl font-semibold text-[#0C447C]">Journal système</h1>
            <div class="overflow-hidden rounded-[10px] border border-[#e2e0d8] bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-[#FAFAF8] text-left text-[10px] font-bold uppercase text-[#888780]">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Acteur</th>
                            <th class="px-4 py-2">Action</th>
                            <th class="px-4 py-2">Site</th>
                            <th class="px-4 py-2">IP</th>
                            <th class="px-4 py-2">Gravité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in logs.data" :key="l.id" class="border-t border-[#F1EFE8]">
                            <td class="px-4 py-2 font-mono text-xs">{{ l.created_at }}</td>
                            <td class="px-4 py-2">{{ l.actor?.name ?? '—' }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ l.action }}</td>
                            <td class="px-4 py-2">{{ l.agence?.nom ?? '—' }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ l.ip_address }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="sev(l.severity)">{{ l.severity }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PointageLayout>
</template>
